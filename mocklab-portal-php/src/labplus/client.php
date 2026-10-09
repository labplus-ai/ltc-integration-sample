<?php
require_once __DIR__ . '/../services/env.php';
require_once __DIR__ . '/hmac.php';

// Sends signed requests to the Labplus API. Server to server only: the browser talks to our pages,
// never to Labplus, so the keys never reach it.
class LabplusClient
{
    private ?string $clientHash;
    private ?string $appKey;

    function __construct()
    {
        // Provided by Labplus, read from .env (see .env.dist). The address of each service is set per service
        // (see PlatformTokenService, PreinterpretationService and LtcService), so each can point elsewhere.
        $this->clientHash = env('LABPLUS_CLIENT_HASH');
        $this->appKey = env('LABPLUS_APP_KEY');
    }

    // Without the keys the integration is switched off and nothing is sent to Labplus.
    function isConfigured(): bool
    {
        return !empty($this->clientHash) && !empty($this->appKey);
    }

    // Needed by the LabTest Checker iframe (part of its URL).
    function clientHash(): string
    {
        return $this->clientHash;
    }

    // Sends one signed request. $url is the full address (e.g. https://api.example.com/v1/token), $jsonBody is null
    // for a request without a body (GET), $headers are the extra ones (e.g. X-Token).
    // Returns [HTTP status code, JSON body as an array]. Throws when Labplus answers with an error.
    function send(string $method, string $url, ?string $jsonBody = null, array $headers = []): array
    {
        // The signature covers the path of the address without the host, exactly as requested (e.g. /v1/token),
        // and exactly the string that is sent as the body.
        $path = parse_url($url, PHP_URL_PATH);
        $headers += LabplusHmac::sign($method, $path, $jsonBody ?? '', $this->clientHash, $this->appKey);
        if ($jsonBody !== null) {
            $headers['Content-Type'] = 'application/json';
        }

        $headerLines = array_map(fn($name, $value) => "$name: $value", array_keys($headers), $headers);
        $headerLines[] = 'Expect:'; // no "100-continue" round trip before the body

        $curl = curl_init($url);
        curl_setopt_array($curl, [
            CURLOPT_CUSTOMREQUEST => $method,
            CURLOPT_HTTPHEADER => $headerLines,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT => 15, // Retries and backoff on 429 omitted for demo simplicity.
        ]);
        if ($jsonBody !== null) {
            curl_setopt($curl, CURLOPT_POSTFIELDS, $jsonBody);
        }

        $text = curl_exec($curl);
        $status = curl_getinfo($curl, CURLINFO_RESPONSE_CODE);
        if ($text === false) {
            throw new RuntimeException("Labplus $method $path failed: " . curl_error($curl));
        }
        if ($status < 200 || $status >= 300) {
            throw new RuntimeException("Labplus $method $path answered $status: $text");
        }
        return [$status, $text === '' ? [] : json_decode($text, true)];
    }
}
