// Entry point: sets up the API server and mounts the endpoints.
using Mocklab.Api;

var builder = WebApplication.CreateBuilder(args);

// Listen on API_PORT (default 3001), or on ASPNETCORE_URLS when it is set (Docker does that).
builder.WebHost.UseUrls(Env.Get("ASPNETCORE_URLS") ?? $"http://localhost:{Env.Get("API_PORT", "3001")}");

builder.Services.AddSingleton<Database>();
builder.Services.AddSingleton<DataSimulator>(); // DEMO ONLY

// Sessions are kept in memory (lost on restart).
builder.Services.AddDistributedMemoryCache();
builder.Services.AddSession(options =>
{
    options.Cookie.HttpOnly = true;
    options.Cookie.IsEssential = true;
});

var app = builder.Build();
app.UseSession();

// DEMO ONLY: on the first run put the starting orders in place.
app.Services.GetRequiredService<DataSimulator>().EnsureStartingOrders();

// Login endpoints are open, everything else under /api requires a logged-in user.
app.MapGroup("/api").MapAuthEndpoints();

var protectedApi = app.MapGroup("/api").AddEndpointFilter(async (context, next) =>
    context.HttpContext.Session.GetString("user") is null
        ? Results.Json(new { error = "Not logged in" }, statusCode: 401)
        : await next(context));
protectedApi.MapOrdersEndpoints();
protectedApi.MapSimulationEndpoints(); // DEMO ONLY

app.Run();
