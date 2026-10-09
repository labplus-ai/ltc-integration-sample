namespace Mocklab.Api;

// The preinterpretation (Labplus documentation, section 3): a short description of the results in HTML,
// with recommended further examinations. Labplus prepares it in the background, so it is started once
// (when the lab releases the results) and the result is then asked for until it is ready.
public class PreinterpretationService(LabplusClient labplus, PlatformTokenService platformToken, Database db)
{
    // Final statuses: the result will not change any more, so it is stored and Labplus is not asked again.
    private static readonly string[] FinalStatuses = ["done", "fallback", "error"];

    // Address of the preinterpretation endpoint, e.g. https://api.example.com/v1/preinterpretation (see .env.dist).
    // POST starts the preinterpretation, GET on the same address returns its result.
    private readonly string? preinterpretationUrl = Env.Get("LABPLUS_PRE_URL");

    // Needs the platform token (keys + its address) and the preinterpretation address.
    public bool IsConfigured => platformToken.IsConfigured && !string.IsNullOrWhiteSpace(preinterpretationUrl);

    // One start at a time, so the same order is never started twice.
    private readonly SemaphoreSlim startLock = new(1, 1);

    // Sends the results (platform token) and starts the preinterpretation. Does nothing when it was already started.
    public async Task StartAsync(Order order)
    {
        if (!IsConfigured) return;

        await startLock.WaitAsync();
        try
        {
            // Read the order again: another request may have started it in the meantime.
            if (db.GetOrder(order.Id)?.Labplus?.PreinterpretationAccessSignature is not null) return;

            var token = await platformToken.EnsureTokenAsync(order);
            // The body is an empty object.
            var response = await labplus.SendAsync(HttpMethod.Post, preinterpretationUrl!, "{}", new()
            {
                ["X-Token"] = token,
            });

            // 200 { id, accessSignature }: the accessSignature is required to get the result, keep both.
            var id = response.GetString("id");
            var accessSignature = response.GetString("accessSignature");
            db.UpdateLabplus(order.Id, data => data with
            {
                PreinterpretationId = id,
                PreinterpretationAccessSignature = accessSignature,
            });
        }
        finally
        {
            startLock.Release();
        }
    }

    // The preinterpretation of the order for the web app: { status, content, recommendations, examinations }
    // as Labplus returns them, or only { status } while there is no result.
    public async Task<object> GetAsync(Order order)
    {
        if (!IsConfigured) return new { status = "not_configured" };

        var data = order.Labplus;
        if (data?.PreinterpretationResult is { } stored) return stored;
        if (data?.PreinterpretationAccessSignature is null)
        {
            // Normally started when the results were released (see Simulator.cs). When that did not happen
            // (e.g. the keys were added later, or Labplus was unavailable then), start it now.
            await StartAsync(order);
            return new { status = "processing" };
        }

        // No body: the signature uses the SHA-256 of the empty string (section 1: "for a GET or empty body").
        // The "{}" shown in the documentation's GET example is not sent on purpose.
        var response = await labplus.SendAsync(HttpMethod.Get, preinterpretationUrl!, null, new()
        {
            ["X-Token"] = data.PlatformToken!,
            ["X-Access-Signature"] = data.PreinterpretationAccessSignature,
        });

        // 202: still processing, the web app asks again on the schedule from the documentation (3 s, 5 s, 10 s, then every 30 s).
        if (response.StatusCode == 202) return new { status = "processing" };

        // 200: done, fallback (simplified content) or error (processing failed on Labplus' side).
        if (FinalStatuses.Contains(response.GetString("status")))
        {
            db.UpdateLabplus(order.Id, current => current with { PreinterpretationResult = response.Body });
        }
        return response.Body;
    }
}
