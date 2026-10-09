using System.Globalization;
using System.Text.Json;

namespace Mocklab.Api;

// What the web app needs to open LabTest Checker: the iframe address, the origin its messages come from,
// the one-time initToken for the handshake and the interview status (new, in_progress or finished).
public record LtcStart(string IframeUrl, string Origin, string InitToken, string InterviewStatus);

// LabTest Checker, Plug&Play (Labplus documentation, section 4): a medical interview and a Health Report
// in an iframe. Labplus runs everything in the iframe; we only start (or resume) the interview and pass
// the initToken to the iframe.
public class LtcService(LabplusClient labplus, PlatformTokenService platformToken, Database db, ILogger<LtcService> logger)
{
    // Address of the LTC endpoints, e.g. https://api.example.com/v1/ltc (/interpretation, /re-entry and
    // /interview-status are added below), and the host of the page shown in the iframe (see .env.dist).
    private readonly string? ltcApiUrl = Env.Get("LABPLUS_LTC_API_URL")?.TrimEnd('/');
    private readonly string? iframeHost = Env.Get("LABPLUS_LTC_IFRAME_URL")?.TrimEnd('/');

    // Needs the platform token (keys + its address), the LTC endpoints and the iframe host.
    public bool IsConfigured =>
        platformToken.IsConfigured && !string.IsNullOrWhiteSpace(ltcApiUrl) && !string.IsNullOrWhiteSpace(iframeHost);

    // One start at a time, so a double click never creates two interviews for the same order.
    private readonly SemaphoreSlim startLock = new(1, 1);

    // LTC supports adults only, so the button is not shown to minors. Pregnant women are not supported either,
    // but the lab does not know about a pregnancy: LTC asks about it in the questionnaire itself.
    public bool IsSupported(Patient patient) =>
        DateOnly.ParseExact(patient.BirthDate, "yyyy-MM-dd", CultureInfo.InvariantCulture).AddYears(18)
            <= DateOnly.FromDateTime(DateTime.Today);

    // The status for the button on the order page: not_configured, not_supported, new, in_progress or finished.
    public async Task<string> GetStatusAsync(Order order)
    {
        if (!IsConfigured) return "not_configured";
        if (!IsSupported(db.GetPatient())) return "not_supported";
        if (order.Labplus?.InterviewToken is not { } interviewToken) return "new";

        return await GetInterviewStatusAsync(interviewToken) switch
        {
            "FINISHED" => "finished",
            "PROCESSING" => "in_progress",
            "NOT_FOUND" => "new", // Labplus does not know this interview, starting again creates a new one
            var other => throw new InvalidOperationException($"Unknown LTC interview status: {other}"),
        };
    }

    // Prepares LTC to be opened right now: a new interview the first time (POST /v1/ltc/interpretation),
    // or a return to the existing one (POST /v1/ltc/re-entry). Both return a new initToken, which is
    // single use and expires after 180 s, so it is never stored and is fetched right before opening the iframe.
    public async Task<LtcStart> StartAsync(Order order)
    {
        await startLock.WaitAsync();
        try
        {
            // Read the order again: another request may have started the interview in the meantime.
            var interviewToken = db.GetOrder(order.Id)?.Labplus?.InterviewToken;
            if (interviewToken is not null && await GetInterviewStatusAsync(interviewToken) == "NOT_FOUND")
            {
                interviewToken = null;
            }

            return interviewToken is null
                ? await StartInterviewAsync(order)
                : await ReEnterAsync(interviewToken);
        }
        finally
        {
            startLock.Release();
        }
    }

    // First time: starts the interpretation of the order's results (never called again for the same interview,
    // it would start a new interview from scratch instead of letting the patient continue).
    private async Task<LtcStart> StartInterviewAsync(Order order)
    {
        var token = await platformToken.EnsureTokenAsync(order);
        // The body is an empty object.
        var response = await labplus.SendAsync(HttpMethod.Post, $"{ltcApiUrl}/interpretation", "{}", new()
        {
            ["X-Token"] = token,
        });

        // { status: "interview_ready", data: { interviewToken, initToken, unknownIds? } }
        // The interviewToken identifies this interview for good: keep it, it is needed to come back later.
        var interviewToken = response.GetString("data", "interviewToken");
        db.UpdateLabplus(order.Id, data => data with { InterviewToken = interviewToken });

        // Ids of our examinations or parameters that Labplus could not map (see the id dictionaries shared with Labplus).
        if (response.Body.GetProperty("data").TryGetProperty("unknownIds", out var unknownIds))
        {
            logger.LogWarning("Labplus did not recognise these ids of order {OrderId}: {UnknownIds}", order.Id, unknownIds);
        }

        return ToLtcStart(interviewToken, response.GetString("data", "initToken"), "new");
    }

    // The patient comes back (page refreshed, new session, unfinished questionnaire or finished report):
    // a new initToken for the existing interview. Safe to call any number of times.
    private async Task<LtcStart> ReEnterAsync(string interviewToken)
    {
        var response = await labplus.SendAsync(HttpMethod.Post, $"{ltcApiUrl}/re-entry",
            JsonSerializer.Serialize(new { interviewToken }));

        // { status: "re_entry_ready", data: { interviewToken, initToken, interviewStatus, surveyExpired } }
        // surveyExpired (the questionnaire is past its validity window) does not block re-entry; a portal could
        // decide not to show the iframe then. Here LTC is always shown and handles it itself.
        return ToLtcStart(
            response.GetString("data", "interviewToken"),
            response.GetString("data", "initToken"),
            response.GetString("data", "interviewStatus")); // in_progress or finished
    }

    // FINISHED, PROCESSING or NOT_FOUND.
    private async Task<string> GetInterviewStatusAsync(string interviewToken)
    {
        var response = await labplus.SendAsync(HttpMethod.Post, $"{ltcApiUrl}/interview-status",
            JsonSerializer.Serialize(new { interviewToken }));
        return response.GetString("status");
    }

    // The iframe address is {iframe host}/interview/{clientHash}?token={interviewToken}&init=true&lang=en-EN
    // (lang: the language of LabTest Checker, English like the rest of the portal). The iframe's
    // messages come from the iframe host's origin (scheme, host and port), the web app checks it and sends the initToken only there.
    private LtcStart ToLtcStart(string interviewToken, string initToken, string interviewStatus) => new(
        IframeUrl: $"{iframeHost}/interview/{Uri.EscapeDataString(labplus.ClientHash)}"
            + $"?token={Uri.EscapeDataString(interviewToken)}&init=true&lang=en-EN",
        Origin: new Uri(iframeHost!).GetLeftPart(UriPartial.Authority),
        InitToken: initToken,
        InterviewStatus: interviewStatus);
}
