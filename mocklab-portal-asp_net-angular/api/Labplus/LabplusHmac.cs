using System.Security.Cryptography;
using System.Text;

namespace Mocklab.Api;

// The whole request signing for the Labplus API, in one place (Labplus documentation, section 1 "Labplus API Authorization").
// Every request to Labplus must carry the four headers returned by Sign().
public static class LabplusHmac
{
    // Returns the authorization headers for one request. path is the path without the host, exactly as it is
    // requested (e.g. /v1/token), and body is exactly the bytes that will be sent (empty for a GET).
    public static Dictionary<string, string> Sign(string method, string path, byte[] body, string clientHash, string appKey)
    {
        // Step 1 - prepare the required parts.
        // METHOD: uppercase HTTP method (POST, PUT or GET), "POST" and not "post".
        var upperMethod = method.ToUpperInvariant();
        // timestamp: current Unix time in seconds. Must be within ±5 minutes of Labplus' clock (keep the server clock synced with NTP).
        var timestamp = DateTimeOffset.UtcNow.ToUnixTimeSeconds().ToString();
        // nonce: a random UUID v4, new for every request (Labplus rejects a nonce used twice).
        var nonce = Guid.NewGuid().ToString();
        // bodyHash: lowercase hex SHA-256 of the UTF-8 request body. For a GET or an empty body it is the
        // SHA-256 of the empty string (e3b0c44298fc1c149afbf4c8996fb92427ae41e4649b934ca495991b7852b855).
        var bodyHash = Convert.ToHexStringLower(SHA256.HashData(body));

        // Step 2 - build the message: the fields separated by "\n" (LF only).
        var message = upperMethod + "\n" + path + "\n" + timestamp + "\n" + nonce + "\n" + bodyHash;

        // Step 3 - X-Signature = HMAC-SHA256(appKey, message) as 64-character lowercase hex.
        // The key is the appKey (not the clientHash). The appKey itself is never sent anywhere.
        var signature = Convert.ToHexStringLower(
            HMACSHA256.HashData(Encoding.UTF8.GetBytes(appKey), Encoding.UTF8.GetBytes(message)));

        return new Dictionary<string, string>
        {
            ["X-Client-Hash"] = clientHash, // partner ID assigned by Labplus
            ["X-Timestamp"] = timestamp,
            ["X-Nonce"] = nonce,
            ["X-Signature"] = signature,
        };
    }
}
