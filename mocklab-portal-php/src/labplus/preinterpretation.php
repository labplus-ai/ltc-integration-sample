<?php
require_once __DIR__ . '/platform_token.php';

// The preinterpretation (Labplus documentation, section 3): a short description of the results,
// with recommended further examinations. Labplus prepares it in the background, so it is started once
// (when the lab releases the results) and the result is then asked for until it is ready.
class PreinterpretationService
{
    // Final statuses: the result will not change any more, so it is stored and Labplus is not asked again.
    const FINAL_STATUSES = ['done', 'fallback', 'error'];

    private Database $db;
    private LabplusClient $labplus;
    private PlatformTokenService $platformToken;
    // Address of the preinterpretation endpoint, e.g. https://api.example.com/v1/preinterpretation (see .env.dist).
    // POST starts the preinterpretation, GET on the same address returns its result.
    private ?string $preinterpretationUrl;

    function __construct(Database $db)
    {
        $this->db = $db;
        $this->labplus = new LabplusClient();
        $this->platformToken = new PlatformTokenService($db);
        $this->preinterpretationUrl = env('LABPLUS_PRE_URL');
    }

    // Needs the platform token (keys + its address) and the preinterpretation address.
    function isConfigured(): bool
    {
        return $this->platformToken->isConfigured() && !empty($this->preinterpretationUrl);
    }

    // Sends the results (platform token) and starts the preinterpretation. Does nothing when it was already started.
    function start(array $order): void
    {
        if (!$this->isConfigured()) {
            return;
        }

        // One start at a time per order, so the same order is never started twice.
        with_lock("preinterpretation-{$order['id']}", function () use ($order) {
            // Read the order again: another request may have started it in the meantime.
            if (isset(labplus_data($this->db->getOrder($order['id']))['preinterpretationAccessSignature'])) {
                return;
            }

            $token = $this->platformToken->ensureToken($order);
            // The body is an empty object.
            [, $answer] = $this->labplus->send('POST', $this->preinterpretationUrl, '{}', ['X-Token' => $token]);

            // 200 { id, accessSignature }: the accessSignature is required to get the result, keep both.
            save_labplus_data($this->db, $order['id'], [
                'preinterpretationId' => $answer['id'],
                'preinterpretationAccessSignature' => $answer['accessSignature'],
            ]);
        });
    }

    // The preinterpretation of the order: { status, content, recommendations, examinations } as Labplus
    // returns them, or only { status } ("not_configured" or "processing") while there is no result.
    function get(array $order): array
    {
        if (!$this->isConfigured()) {
            return ['status' => 'not_configured'];
        }

        $data = labplus_data($order);
        if (isset($data['preinterpretationResult'])) {
            return $data['preinterpretationResult'];
        }
        if (!isset($data['preinterpretationAccessSignature'])) {
            // Normally started when the results were released (see the simulator). When that did not happen
            // (e.g. the keys were added later, or Labplus was unavailable then), start it now.
            $this->start($order);
            return ['status' => 'processing'];
        }

        // No body: the signature uses the SHA-256 of the empty string (section 1: "for a GET or empty body").
        [$status, $answer] = $this->labplus->send('GET', $this->preinterpretationUrl, null, [
            'X-Token' => $data['platformToken'],
            'X-Access-Signature' => $data['preinterpretationAccessSignature'],
        ]);

        // 202: still processing, the page asks again on the schedule from the documentation (3 s, 5 s, 10 s, then every 30 s).
        if ($status === 202) {
            return ['status' => 'processing'];
        }

        // 200: done, fallback (simplified content) or error (processing failed on Labplus' side).
        if (in_array($answer['status'] ?? null, self::FINAL_STATUSES)) {
            save_labplus_data($this->db, $order['id'], ['preinterpretationResult' => $answer]);
        }
        return $answer;
    }
}

// The content from Labplus is shown as HTML: only simple formatting tags are kept (no scripts, no attributes).
function labplus_safe_html(string $html): string
{
    $html = preg_replace('#<(script|style)\b[^>]*>.*?</\1>#is', '', $html);
    $html = strip_tags($html, '<p><br><strong><b><em><i><ul><ol><li>');
    return preg_replace('/<(\/?)(\w+)[^>]*>/', '<$1$2>', $html);
}
