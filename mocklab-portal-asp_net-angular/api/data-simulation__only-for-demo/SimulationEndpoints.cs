namespace Mocklab.Api;

// DEMO ONLY: endpoints behind the buttons of the demo box in the web app (simulate new results / reset).
// There is no need to read this when studying the integration.
// CSRF protection and other validation omitted for demo simplicity.
public static class SimulationEndpoints
{
    public static void MapSimulationEndpoints(this IEndpointRouteBuilder api)
    {
        // "The lab has just released new results": adds the next result set as a new order.
        api.MapPost("/data-simulation__only-for-demo/release-next-results",
            (DataSimulator simulator) => simulator.ReleaseNextResults());

        // Back to the initial 2 orders.
        api.MapPost("/data-simulation__only-for-demo/reset", (DataSimulator simulator) =>
        {
            simulator.Reset();
            return new { ok = true };
        });
    }
}
