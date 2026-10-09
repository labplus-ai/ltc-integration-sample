// Entry point: sets up the API server and mounts the endpoints.
using Mocklab.Api;

var builder = WebApplication.CreateBuilder(args);

// Listen on API_PORT (default 3001), or on ASPNETCORE_URLS when it is set (Docker does that).
builder.WebHost.UseUrls(Env.Get("ASPNETCORE_URLS") ?? $"http://localhost:{Env.Get("API_PORT", "3001")}");

builder.Services.AddSingleton<Database>();
builder.Services.AddSingleton<DataSimulator>(); // DEMO ONLY

// Labplus integration (api/Labplus/): one HttpClient for all calls to the Labplus API, with a 15 s timeout.
builder.Services.AddSingleton(new HttpClient { Timeout = TimeSpan.FromSeconds(15) });
builder.Services.AddSingleton<LabplusClient>();
builder.Services.AddSingleton<PlatformTokenService>();
builder.Services.AddSingleton<PreinterpretationService>();
builder.Services.AddSingleton<LtcService>();

// Sessions are kept in memory (lost on restart).
builder.Services.AddDistributedMemoryCache();
builder.Services.AddSession(options =>
{
    options.Cookie.HttpOnly = true;
    options.Cookie.IsEssential = true;
});

var app = builder.Build();
app.UseSession();

// Without the Labplus keys the portal works as before, only the integration (or its missing part) is switched off.
if (!app.Services.GetRequiredService<PreinterpretationService>().IsConfigured || !app.Services.GetRequiredService<LtcService>().IsConfigured)
{
    app.Logger.LogWarning("Labplus integration is not fully configured: set the LABPLUS_* values in .env (see .env.dist).");
}

// DEMO ONLY: on the first run put the starting orders in place.
await app.Services.GetRequiredService<DataSimulator>().EnsureStartingOrdersAsync();

// Login endpoints are open, everything else under /api requires a logged-in user.
app.MapGroup("/api").MapAuthEndpoints();

var protectedApi = app.MapGroup("/api").AddEndpointFilter(async (context, next) =>
    context.HttpContext.Session.GetString("user") is null
        ? Results.Json(new { error = "Not logged in" }, statusCode: 401)
        : await next(context));
protectedApi.MapOrdersEndpoints();
protectedApi.MapLabplusEndpoints(); // preinterpretation and LabTest Checker on the order page
protectedApi.MapSimulationEndpoints(); // DEMO ONLY

app.Run();
