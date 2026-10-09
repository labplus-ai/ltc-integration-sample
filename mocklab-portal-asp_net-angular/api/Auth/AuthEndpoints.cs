namespace Mocklab.Api;

// Login, logout and "who am I" endpoints.
public static class AuthEndpoints
{
    // Demo credentials, hardcoded and compared in plain text.
    // Password hashing, rate limiting and CSRF protection omitted for demo simplicity.
    private const string Username = "patient123";
    private const string Password = "veryStrongPassword";

    private record LoginRequest(string? Username, string? Password);

    public static void MapAuthEndpoints(this IEndpointRouteBuilder api)
    {
        // Used by the web app to check whether the user is logged in.
        api.MapGet("/me", (HttpContext http) =>
            http.Session.GetString("user") is { } user
                ? Results.Json(new { user })
                : Results.Json(new { error = "Not logged in" }, statusCode: 401));

        api.MapPost("/login", (LoginRequest body, HttpContext http) =>
        {
            // Input validation omitted for demo simplicity.
            if (body.Username != Username || body.Password != Password)
            {
                return Results.Json(new { error = "Invalid username or password." }, statusCode: 401);
            }
            http.Session.SetString("user", Username);
            return Results.Json(new { user = Username });
        });

        api.MapPost("/logout", (HttpContext http) =>
        {
            http.Session.Clear();
            return Results.Json(new { ok = true });
        });
    }
}
