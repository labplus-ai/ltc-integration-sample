using System.Globalization;
using System.Security.Cryptography;
using System.Text;
using System.Text.Json;

namespace Mocklab.Api;

// The platform token (Labplus documentation, section 2): the order's results are sent to Labplus once,
// and the token Labplus returns identifies them in every later call. One token per order, shared by the
// preinterpretation and LabTest Checker: never create a second one for the same results.
public class PlatformTokenService(LabplusClient labplus, Database db, ILogger<PlatformTokenService> logger)
{
    // Address of the platform token endpoint, e.g. https://api.example.com/v1/token (see .env.dist).
    private readonly string? tokenUrl = Env.Get("LABPLUS_TOKEN_URL");

    // Needs the keys and the address. The token is shared by the preinterpretation and LabTest Checker.
    public bool IsConfigured => labplus.IsConfigured && !string.IsNullOrWhiteSpace(tokenUrl);

    // The lab works in Romanian time: UTC+2 in winter, UTC+3 in summer (daylight saving time).
    private static readonly TimeZoneInfo LabTimeZone = TimeZoneInfo.FindSystemTimeZoneById("Europe/Bucharest");

    // Secret of our own (not from Labplus) used to make the patientHash, see PatientHash() below.
    private readonly string? patientHashSecret = Env.Get("PATIENT_HASH_SECRET");

    // One token is created at a time, so two requests for the same order never create two tokens.
    private readonly SemaphoreSlim createLock = new(1, 1);

    // Returns the order's platform token, creating it (POST /v1/token) when the order does not have one yet.
    public async Task<string> EnsureTokenAsync(Order order)
    {
        await createLock.WaitAsync();
        try
        {
            // Read the order again: another request may have created the token in the meantime.
            if (db.GetOrder(order.Id)?.Labplus?.PlatformToken is { } existing) return existing;

            // Serialized once: this exact string is signed and sent (see LabplusClient).
            var body = JsonSerializer.Serialize(Payload(order, db.GetPatient()));
            var response = await labplus.SendAsync(HttpMethod.Post, tokenUrl!, body, new()
            {
                ["X-Context-Type"] = "ExaminationResults", // the only context Labplus supports
                ["X-Context-Version"] = "1.0", // data schema version
            });

            // 201 { token, mutability, createdAt }: keep the token with the order.
            var token = response.GetString("token");
            db.UpdateLabplus(order.Id, data => data with { PlatformToken = token });
            return token;
        }
        finally
        {
            createLock.Release();
        }
    }

    // The lab's data in the Labplus format. Ids and names are the lab's own: the dictionaries of examination
    // and parameter ids are shared with Labplus during the integration, so Labplus can map them.
    private object Payload(Order order, Patient patient) => new
    {
        // metaData (campaign, collection point) is optional and not used here.

        // Only what the interpretation needs. firstName, lastName, email, phoneNumber and contactConsent are
        // optional and not needed, so they are not sent.
        patient = new
        {
            gender = patient.Gender is "male" or "female" or "other" ? patient.Gender : "unknown",
            birthDate = patient.BirthDate, // YYYY-MM-DD
            patientHash = PatientHash(patient.NationalId),
        },
        examinations = order.Examinations.Select(examination => new
        {
            examinationName = examination.Name,
            examinationId = examination.ExaminationId,
            examinationParams = examination.Params.Select(param => new
            {
                paramId = param.ParamId,
                // A number, or text for qualitative results (e.g. "wykryto"), sent as it is.
                paramValue = param.Value,
                paramName = param.Name,
                paramUnit = string.IsNullOrEmpty(param.Unit) ? null : param.Unit,
                // null when there is no norm on that side.
                paramNormHigh = param.NormHigh,
                paramNormLow = param.NormLow,
                // When the sample was taken (also the start of the "test without collection" window),
                // with the lab's time zone offset, e.g. "2026-10-08 08:15:00+03:00" (without it Labplus assumes UTC).
                paramDate = WithLabTimeZone(order.CollectedAt),
            }),
        }),
    };

    // "2026-10-08 08:15:00" (the lab's local time) -> "2026-10-08 08:15:00+03:00".
    private static string WithLabTimeZone(string localDateTime)
    {
        var local = DateTime.ParseExact(localDateTime, "yyyy-MM-dd HH:mm:ss", CultureInfo.InvariantCulture);
        return new DateTimeOffset(local, LabTimeZone.GetUtcOffset(local)).ToString("yyyy-MM-dd HH:mm:sszzz", CultureInfo.InvariantCulture);
    }

    // patientHash = lowercase hex HMAC-SHA256(PATIENT_HASH_SECRET, national ID). It must stay the same for
    // the same patient over time. Keyed with our secret because a plain SHA-256 of a national ID can be reversed
    // by trying all possible IDs. Without the secret no hash is sent (the field may be null).
    private string? PatientHash(string nationalId)
    {
        if (string.IsNullOrWhiteSpace(patientHashSecret))
        {
            logger.LogWarning("PATIENT_HASH_SECRET is not set (see .env.dist), the patientHash is not sent to Labplus.");
            return null;
        }
        return Convert.ToHexStringLower(
            HMACSHA256.HashData(Encoding.UTF8.GetBytes(patientHashSecret), Encoding.UTF8.GetBytes(nationalId)));
    }
}
