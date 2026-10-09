namespace Mocklab.Api;

// Our endpoints for the Labplus features on the order page. The web app calls these, never Labplus itself,
// so the keys stay on the server. All endpoints require a logged-in user (see Program.cs).
public static class LabplusEndpoints
{
    public static void MapLabplusEndpoints(this IEndpointRouteBuilder api)
    {
        // The preinterpretation of the order (the web app asks again while it is "processing").
        api.MapGet("/orders/{id}/preinterpretation", (string id, Database db, PreinterpretationService preinterpretation, ILoggerFactory loggers) =>
            ForOrder(id, db, loggers, async order => Results.Json(await preinterpretation.GetAsync(order))));

        // Which LabTest Checker button to show.
        api.MapGet("/orders/{id}/ltc/status", (string id, Database db, LtcService ltc, ILoggerFactory loggers) =>
            ForOrder(id, db, loggers, async order => Results.Json(new { status = await ltc.GetStatusAsync(order) })));

        // Called when the patient clicks the button: everything needed to open the LabTest Checker iframe.
        // CSRF protection omitted for demo simplicity.
        api.MapPost("/orders/{id}/ltc/start", (string id, Database db, LtcService ltc, ILoggerFactory loggers) =>
            ForOrder(id, db, loggers, async order =>
            {
                // Defensive: the web app does not show the button in these two cases.
                if (!ltc.IsConfigured) return Results.Json(new { status = "not_configured" }, statusCode: 503);
                if (!ltc.IsSupported(db.GetPatient())) return Results.Json(new { status = "not_supported" }, statusCode: 403);
                return Results.Json(await ltc.StartAsync(order));
            }));
    }

    // Finds the order (404 when it does not exist) and runs the action. When the call to Labplus fails
    // (rejected request, Labplus unavailable, unexpected answer) the error is logged and the web app gets
    // { status: "error" } with HTTP 502, so the rest of the page keeps working.
    private static async Task<IResult> ForOrder(string id, Database db, ILoggerFactory loggers, Func<Order, Task<IResult>> action)
    {
        // Further input validation omitted for demo simplicity.
        var order = int.TryParse(id, out var orderId) ? db.GetOrder(orderId) : null;
        if (order is null) return Results.Json(new { error = "This order does not exist." }, statusCode: 404);

        try
        {
            return await action(order);
        }
        catch (Exception e)
        {
            loggers.CreateLogger("Labplus").LogError(e, "Labplus request for order {OrderId} failed", order.Id);
            return Results.Json(new { status = "error" }, statusCode: 502);
        }
    }
}
