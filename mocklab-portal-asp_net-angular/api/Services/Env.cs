namespace Mocklab.Api;

// Settings: a real environment variable wins (e.g. set by Docker), otherwise the optional
// .env file in the project root is used (copy .env.dist to .env).
// Parsing is deliberately minimal: KEY=value lines, '#' comments, optional quotes.
public static class Env
{
    private static readonly Dictionary<string, string> FileValues = ReadEnvFile();

    // Returns the value of the setting, or the fallback when it is missing.
    public static string? Get(string key, string? fallback = null) =>
        Environment.GetEnvironmentVariable(key) ?? FileValues.GetValueOrDefault(key) ?? fallback;

    private static Dictionary<string, string> ReadEnvFile()
    {
        var values = new Dictionary<string, string>();
        var file = Path.Combine(Directory.GetCurrentDirectory(), "..", ".env"); // project root, next to api/ and web/
        if (!File.Exists(file)) return values;

        foreach (var line in File.ReadAllLines(file))
        {
            if (line.Length == 0 || line[0] == '#' || !line.Contains('=')) continue;
            var parts = line.Split('=', 2);
            values[parts[0].Trim()] = parts[1].Trim().Trim('"', '\'');
        }
        return values;
    }
}
