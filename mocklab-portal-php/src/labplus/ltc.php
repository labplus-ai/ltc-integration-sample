<?php
require_once __DIR__ . '/platform_token.php';

// LabTest Checker, Plug&Play (Labplus documentation, section 4): a medical interview and a Health Report
// in an iframe. Labplus runs everything in the iframe; we only start (or resume) the interview and pass
// the initToken to the iframe (see labplus.js).
class LtcService
{
    private Database $db;
    private LabplusClient $labplus;
    private PlatformTokenService $platformToken;
    // Address of the LTC endpoints, e.g. https://api.example.com/v1/ltc (/interpretation, /re-entry and
    // /interview-status are added below), and the host of the page shown in the iframe (see .env.dist).
    private ?string $ltcApiUrl;
    private ?string $iframeHost;

    function __construct(Database $db)
    {
        $this->db = $db;
        $this->labplus = new LabplusClient();
        $this->platformToken = new PlatformTokenService($db);
        $this->ltcApiUrl = rtrim(env('LABPLUS_LTC_API_URL', ''), '/');
        $this->iframeHost = rtrim(env('LABPLUS_LTC_IFRAME_URL', ''), '/');
    }

    // Needs the platform token (keys + its address), the LTC endpoints and the iframe host.
    function isConfigured(): bool
    {
        return $this->platformToken->isConfigured() && $this->ltcApiUrl !== '' && $this->iframeHost !== '';
    }

    // LTC supports adults only, so the button is not shown to minors. Pregnant women are not supported either,
    // but the lab does not know about a pregnancy: LTC asks about it in the questionnaire itself.
    function isSupported(array $patient): bool
    {
        return (new DateTime($patient['birthDate']))->diff(new DateTime('today'))->y >= 18;
    }

    // The status for the button on the order page: not_configured, not_supported, new, in_progress or finished.
    function getStatus(array $order): string
    {
        if (!$this->isConfigured()) {
            return 'not_configured';
        }
        if (!$this->isSupported($this->db->getPatient())) {
            return 'not_supported';
        }
        $interviewToken = labplus_data($order)['interviewToken'] ?? null;
        if (!$interviewToken) {
            return 'new';
        }

        return match ($this->getInterviewStatus($interviewToken)) {
            'FINISHED' => 'finished',
            'PROCESSING' => 'in_progress',
            'NOT_FOUND' => 'new', // Labplus does not know this interview, starting again creates a new one
            default => throw new RuntimeException('Unknown LTC interview status'),
        };
    }

    // Prepares LTC to be opened right now: a new interview the first time (POST .../interpretation),
    // or a return to the existing one (POST .../re-entry). Both return a new initToken, which is
    // single use and expires after 180 s, so it is never stored and is fetched right before opening the iframe.
    // Returns { iframeUrl, origin, initToken, interviewStatus } for labplus.js.
    function start(array $order): array
    {
        // One start at a time per order, so a double click never creates two interviews.
        return with_lock("ltc-{$order['id']}", function () use ($order) {
            // Read the order again: another request may have started the interview in the meantime.
            $interviewToken = labplus_data($this->db->getOrder($order['id']))['interviewToken'] ?? null;
            if ($interviewToken && $this->getInterviewStatus($interviewToken) === 'NOT_FOUND') {
                $interviewToken = null;
            }

            return $interviewToken ? $this->reEnter($interviewToken) : $this->startInterview($order);
        });
    }

    // First time: starts the interpretation of the order's results (never called again for the same interview,
    // it would start a new interview from scratch instead of letting the patient continue).
    private function startInterview(array $order): array
    {
        $token = $this->platformToken->ensureToken($order);
        // The body is an empty object.
        [, $answer] = $this->labplus->send('POST', "$this->ltcApiUrl/interpretation", '{}', ['X-Token' => $token]);

        // { status: "interview_ready", data: { interviewToken, initToken, unknownIds? } }
        // The interviewToken identifies this interview for good: keep it, it is needed to come back later.
        $interviewToken = $answer['data']['interviewToken'];
        save_labplus_data($this->db, $order['id'], ['interviewToken' => $interviewToken]);

        // Ids of our examinations or parameters that Labplus could not map (see the id dictionaries shared with Labplus).
        if (!empty($answer['data']['unknownIds'])) {
            error_log("Labplus did not recognise these ids of order {$order['id']}: " . json_encode($answer['data']['unknownIds']));
        }

        return $this->ltcStart($interviewToken, $answer['data']['initToken'], 'new');
    }

    // The patient comes back (page refreshed, new session, unfinished questionnaire or finished report):
    // a new initToken for the existing interview. Safe to call any number of times.
    private function reEnter(string $interviewToken): array
    {
        [, $answer] = $this->labplus->send('POST', "$this->ltcApiUrl/re-entry", json_encode(['interviewToken' => $interviewToken]));

        // { status: "re_entry_ready", data: { interviewToken, initToken, interviewStatus, surveyExpired } }
        // surveyExpired (the questionnaire is past its validity window) does not block re-entry; a portal could
        // decide not to show the iframe then. Here LTC is always shown and handles it itself.
        return $this->ltcStart($answer['data']['interviewToken'], $answer['data']['initToken'], $answer['data']['interviewStatus']);
    }

    // FINISHED, PROCESSING or NOT_FOUND.
    private function getInterviewStatus(string $interviewToken): string
    {
        [, $answer] = $this->labplus->send('POST', "$this->ltcApiUrl/interview-status", json_encode(['interviewToken' => $interviewToken]));
        return $answer['status'];
    }

    // The iframe address is {iframe host}/interview/{clientHash}?token={interviewToken}&init=true&lang=en-EN
    // (lang: the language of LabTest Checker, English like the rest of the portal). The iframe's messages
    // come from the iframe host's origin (scheme, host and port), labplus.js checks it and sends the initToken only there.
    private function ltcStart(string $interviewToken, string $initToken, string $interviewStatus): array
    {
        $host = parse_url($this->iframeHost);
        return [
            'iframeUrl' => "$this->iframeHost/interview/" . rawurlencode($this->labplus->clientHash())
                . '?token=' . rawurlencode($interviewToken) . '&init=true&lang=en-EN',
            'origin' => $host['scheme'] . '://' . $host['host'] . (isset($host['port']) ? ':' . $host['port'] : ''),
            'initToken' => $initToken,
            'interviewStatus' => $interviewStatus, // new, in_progress or finished
        ];
    }
}
