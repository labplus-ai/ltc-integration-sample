using System.Net.Http.Headers;
using System.Text;
using System.Text.Json;

namespace Mocklab.Api;

// Sends signed requests to the Labplus API. Server to server only: the web app talks to our API,
// never to Labplus, so the keys never reach the browser.
public class LabplusClient(HttpClient http, ILogger<LabplusClient> logger)
{
    // Provided by Labplus, read from .env (see .env.dist). The addresses of the services are set per service
    // (see PlatformTokenService, PreinterpretationService and LtcService), so each can point elsewhere.
    private readonly string? clientHash = Env.Get("LABPLUS_CLIENT_HASH");
    private readonly string? appKey = Env.Get("LABPLUS_APP_KEY");

    // Without the keys the integration is switched off and nothing is sent to Labplus.
    public bool IsConfigured => !string.IsNullOrWhiteSpace(clientHash) && !string.IsNullOrWhiteSpace(appKey);

    // Needed by the LabTest Checker iframe (part of its URL).
    public string ClientHash => clientHash!;

    // Sends one signed request. url is the full address (e.g. https://api.example.com/v1/token), jsonBody is null
    // for a request without a body (GET), headers are the extra ones (e.g. X-Token). Throws when Labplus answers with an error.
    public async Task<LabplusResponse> SendAsync(
        HttpMethod method, string url, string? jsonBody = null, Dictionary<string, string>? headers = null)
    {
        // The signature covers the path of the address without the host, exactly as requested (e.g. /v1/token).
        var path = new Uri(url).AbsolutePath;

        // The body is turned into bytes once: exactly these bytes are hashed for the signature and sent.
        var body = Encoding.UTF8.GetBytes(jsonBody ?? "");

        using var request = new HttpRequestMessage(method, url);
        foreach (var (name, value) in LabplusHmac.Sign(method.Method, path, body, clientHash!, appKey!))
        {
            request.Headers.Add(name, value);
        }
        foreach (var (name, value) in headers ?? new Dictionary<string, string>())
        {
            request.Headers.Add(name, value);
        }
        if (jsonBody is not null)
        {
            request.Content = new ByteArrayContent(body);
            request.Content.Headers.ContentType = new MediaTypeHeaderValue("application/json");
        }

        // Timeout: 15 s (set on the HttpClient in Program.cs). Retries and backoff on 429 omitted for demo simplicity.
        using var response = await http.SendAsync(request);
        var text = await response.Content.ReadAsStringAsync();
        var status = (int)response.StatusCode;
        logger.LogInformation("Labplus {Method} {Path} answered {Status}", method, path, status);

        if (!response.IsSuccessStatusCode)
        {
            throw new HttpRequestException($"Labplus {method} {path} answered {status}: {text}", null, response.StatusCode);
        }
        return new LabplusResponse(status, text.Length > 0 ? JsonSerializer.Deserialize<JsonElement>(text) : default);
    }
}

// A successful answer of the Labplus API: the HTTP status code and the JSON body.
public record LabplusResponse(int StatusCode, JsonElement Body)
{
    // A text field of the body, e.g. GetString("data", "initToken"). Throws a clear error when it is missing.
    public string GetString(params string[] names)
    {
        var value = Body;
        foreach (var name in names)
        {
            if (value.ValueKind != JsonValueKind.Object || !value.TryGetProperty(name, out value))
            {
                throw new InvalidOperationException($"Labplus answer has no \"{string.Join('.', names)}\": {Body}");
            }
        }
        return value.GetString() ?? throw new InvalidOperationException($"Labplus answer has an empty \"{string.Join('.', names)}\": {Body}");
    }
}
