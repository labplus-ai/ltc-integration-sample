<?php
// The whole request signing for the Labplus API, in one place (Labplus documentation, section 1 "Labplus API Authorization").
// Every request to Labplus must carry the four headers returned by sign().
class LabplusHmac
{
    // Returns the authorization headers for one request. $path is the path without the host, exactly as it is
    // requested (e.g. /v1/token), and $body is exactly the string that will be sent ("" for a GET).
    static function sign(string $method, string $path, string $body, string $clientHash, string $appKey): array
    {
        // Step 1 - prepare the required parts.
        // METHOD: uppercase HTTP method (POST, PUT or GET), "POST" and not "post".
        $method = strtoupper($method);
        // timestamp: current Unix time in seconds. Must be within ±5 minutes of Labplus' clock (keep the server clock synced with NTP).
        $timestamp = (string) time();
        // nonce: a random UUID v4, new for every request (Labplus rejects a nonce used twice).
        $nonce = self::uuidV4();
        // bodyHash: lowercase hex SHA-256 of the UTF-8 request body. For a GET or an empty body it is the
        // SHA-256 of the empty string (e3b0c44298fc1c149afbf4c8996fb92427ae41e4649b934ca495991b7852b855).
        $bodyHash = hash('sha256', $body);

        // Step 2 - build the message: the fields separated by "\n" (LF only).
        $message = $method . "\n" . $path . "\n" . $timestamp . "\n" . $nonce . "\n" . $bodyHash;

        // Step 3 - X-Signature = HMAC-SHA256(appKey, message) as 64-character lowercase hex.
        // The key is the appKey (not the clientHash). The appKey itself is never sent anywhere.
        $signature = hash_hmac('sha256', $message, $appKey);

        return [
            'X-Client-Hash' => $clientHash, // partner ID assigned by Labplus
            'X-Timestamp' => $timestamp,
            'X-Nonce' => $nonce,
            'X-Signature' => $signature,
        ];
    }

    // A random UUID v4, e.g. "550e8400-e29b-41d4-a716-446655440000".
    private static function uuidV4(): string
    {
        $bytes = random_bytes(16);
        $bytes[6] = chr(ord($bytes[6]) & 0x0f | 0x40); // version 4
        $bytes[8] = chr(ord($bytes[8]) & 0x3f | 0x80); // variant
        return vsprintf('%s%s-%s-%s-%s-%s%s%s', str_split(bin2hex($bytes), 4));
    }
}
