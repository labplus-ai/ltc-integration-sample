using System.Text.Json;

namespace Mocklab.Api;

// Data types used across the API. They are sent to the web app as JSON (property names in camelCase).

// One measured parameter, e.g. "Hemoglobin". Norms are null when open-ended (e.g. only an upper limit).
// Value is a number, or text for qualitative results (e.g. "detected"), so it is kept as raw JSON.
public record Param(int ParamId, string Name, JsonElement Value, string? Unit, double? NormLow, double? NormHigh);

public record Examination(int ExaminationId, string Name, List<Param> Params);

// Date is YYYY-MM-DD (the day the lab released the results), CollectedAt is YYYY-MM-DD HH:MM:SS (when the sample was taken).
public record Order(int Id, string Number, string Date, string CollectedAt, string Doctor, List<Examination> Examinations);

// What the lab stores about the person. The national ID identifies them (like PESEL in Poland).
public record Patient(string FirstName, string LastName, string Gender, string BirthDate, string NationalId, string Email, string Phone);
