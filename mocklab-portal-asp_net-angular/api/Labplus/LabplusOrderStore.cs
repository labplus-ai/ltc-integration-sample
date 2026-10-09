namespace Mocklab.Api;

// Saves the Labplus data of an order (stored with the order, see LabplusOrderData in Services/Models.cs).
public static class LabplusOrderStore
{
    // Changes the order's Labplus data and saves the order, e.g. db.UpdateLabplus(id, data => data with { InterviewToken = token }).
    // Database.UpdateOrder does it under one lock, so two requests that change different fields at the same time
    // (e.g. the preinterpretation result and the LTC interview token) do not overwrite each other.
    // Does nothing when the order no longer exists (DEMO: "Reset demo data" removes the orders).
    public static void UpdateLabplus(this Database db, int orderId, Func<LabplusOrderData, LabplusOrderData> change) =>
        db.UpdateOrder(orderId, order => order with { Labplus = change(order.Labplus ?? new LabplusOrderData()) });
}
