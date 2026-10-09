namespace Mocklab.Api;

// The patient and their orders. All endpoints require a logged-in user (see Program.cs).
public static class OrdersEndpoints
{
    public static void MapOrdersEndpoints(this IEndpointRouteBuilder api)
    {
        api.MapGet("/patient", (Database db) => db.GetPatient());

        // All orders, newest first.
        api.MapGet("/orders", (Database db) => db.GetOrders().Values.OrderByDescending(o => o.Id));

        api.MapGet("/orders/{id}", (string id, Database db) =>
        {
            // Further input validation omitted for demo simplicity.
            var order = int.TryParse(id, out var orderId) ? db.GetOrder(orderId) : null;
            return order is null
                ? Results.Json(new { error = "This order does not exist." }, statusCode: 404)
                : Results.Json(order);
        });
    }
}
