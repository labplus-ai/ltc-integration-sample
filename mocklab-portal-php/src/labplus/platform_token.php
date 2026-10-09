<?php
require_once __DIR__ . '/client.php';
require_once __DIR__ . '/order_data.php';

// The platform token (Labplus documentation, section 2): the order's results are sent to Labplus once,
// and the token Labplus returns identifies them in every later call. One token per order, shared by the
// preinterpretation and LabTest Checker: never create a second one for the same results.
class PlatformTokenService
{
    // The lab works in Romanian time: UTC+2 in winter, UTC+3 in summer (daylight saving time).
    const LAB_TIME_ZONE = 'Europe/Bucharest';

    private Database $db;
    private LabplusClient $labplus;
    // Address of the platform token endpoint, e.g. https://api.example.com/v1/token (see .env.dist).
    private ?string $tokenUrl;
    // Secret of our own (not from Labplus) used to make the patientHash, see patientHash() below.
    private ?string $patientHashSecret;

    function __construct(Database $db)
    {
        $this->db = $db;
        $this->labplus = new LabplusClient();
        $this->tokenUrl = env('LABPLUS_TOKEN_URL');
        $this->patientHashSecret = env('PATIENT_HASH_SECRET');
    }

    // Needs the keys and the address. The token is shared by the preinterpretation and LabTest Checker.
    function isConfigured(): bool
    {
        return $this->labplus->isConfigured() && !empty($this->tokenUrl);
    }

    // Returns the order's platform token, creating it (POST to LABPLUS_TOKEN_URL) when the order does not have one yet.
    function ensureToken(array $order): string
    {
        // One at a time per order, so two requests never create two tokens.
        return with_lock("token-{$order['id']}", function () use ($order) {
            // Read the order again: another request may have created the token in the meantime.
            $existing = labplus_data($this->db->getOrder($order['id']))['platformToken'] ?? null;
            if ($existing) {
                return $existing;
            }

            // Encoded once: this exact string is signed and sent (see LabplusClient).
            $body = json_encode($this->payload($order, $this->db->getPatient()));
            [, $answer] = $this->labplus->send('POST', $this->tokenUrl, $body, [
                'X-Context-Type' => 'ExaminationResults', // the only context Labplus supports
                'X-Context-Version' => '1.0', // data schema version
            ]);

            // 201 { token, mutability, createdAt }: keep the token with the order.
            $token = $answer['token'] ?? throw new RuntimeException('Labplus answer has no token: ' . json_encode($answer));
            save_labplus_data($this->db, $order['id'], ['platformToken' => $token]);
            return $token;
        });
    }

    // The lab's data in the Labplus format. Ids and names are the lab's own: the dictionaries of examination
    // and parameter ids are shared with Labplus during the integration, so Labplus can map them.
    private function payload(array $order, array $patient): array
    {
        return [
            // metaData (campaign, collection point) is optional and not used here.

            // Only what the interpretation needs. firstName, lastName, email, phoneNumber and contactConsent are
            // optional and not needed, so they are not sent.
            'patient' => [
                'gender' => in_array($patient['gender'], ['male', 'female', 'other']) ? $patient['gender'] : 'unknown',
                'birthDate' => $patient['birthDate'], // YYYY-MM-DD
                'patientHash' => $this->patientHash($patient['nationalId']),
            ],
            'examinations' => array_map(fn($examination) => [
                'examinationName' => $examination['name'],
                'examinationId' => $examination['examinationId'],
                'examinationParams' => array_map(fn($param) => [
                    'paramId' => $param['paramId'],
                    // A number, or text for qualitative results (e.g. "wykryto"), sent as it is.
                    'paramValue' => $param['value'],
                    'paramName' => $param['name'],
                    'paramUnit' => $param['unit'] === '' ? null : $param['unit'],
                    // null when there is no norm on that side.
                    'paramNormHigh' => $param['normHigh'],
                    'paramNormLow' => $param['normLow'],
                    // When the sample was taken (also the start of the "test without collection" window),
                    // with the lab's time zone offset, e.g. "2026-10-08 08:15:00+03:00" (without it Labplus assumes UTC).
                    'paramDate' => self::withLabTimeZone($order['collectedAt']),
                ], $examination['params']),
            ], $order['examinations']),
        ];
    }

    // "2026-10-08 08:15:00" (the lab's local time) -> "2026-10-08 08:15:00+03:00".
    private static function withLabTimeZone(string $localDateTime): string
    {
        return (new DateTime($localDateTime, new DateTimeZone(self::LAB_TIME_ZONE)))->format('Y-m-d H:i:sP');
    }

    // patientHash = lowercase hex HMAC-SHA256(PATIENT_HASH_SECRET, national ID). It must stay the same for
    // the same patient over time. Keyed with our secret because a plain SHA-256 of a national ID can be reversed
    // by trying all possible IDs. Without the secret no hash is sent (the field may be null).
    private function patientHash(string $nationalId): ?string
    {
        if (empty($this->patientHashSecret)) {
            error_log('PATIENT_HASH_SECRET is not set (see .env.dist), the patientHash is not sent to Labplus.');
            return null;
        }
        return hash_hmac('sha256', $nationalId, $this->patientHashSecret);
    }
}
