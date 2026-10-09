using System.Text.Json;

namespace Mocklab.Api;

// DEMO ONLY: pretends to be the lab that produces new test results.
// There is no need to read this when studying the integration: a real portal has nothing like it,
// orders would simply appear when the lab releases the results.
//
// The files in results/ stand for what a lab keeps in its own database: the examinations of one
// order with their measured parameters, as plain data. They are used in file name order.
public class DataSimulator(Database db, IWebHostEnvironment env,
    PreinterpretationService preinterpretation, ILogger<DataSimulator> logger)
{
    private const string Doctor = "Dr. Emily Carter";

    private static readonly JsonSerializerOptions Json = new(JsonSerializerDefaults.Web);

    // First run: put the starting orders in place.
    public async Task EnsureStartingOrdersAsync()
    {
        if (!db.HasOrders()) await ResetAsync();
    }

    // Starting point of the demo: the first two result sets are already in the patient's account.
    public async Task ResetAsync()
    {
        var files = ResultFiles();
        db.SaveOrders(new Dictionary<int, Order>());
        await StartPreinterpretationAsync(db.AddOrder(OrderFromResults(files[0], daysAgo: 14)));
        await StartPreinterpretationAsync(db.AddOrder(OrderFromResults(files[1], daysAgo: 7)));
    }

    // "The lab has just released new results": adds the next result set in the queue (3rd, 4th, ...,
    // and from the beginning again after the last one) as a new order. Returns the new order.
    public async Task<Order> ReleaseNextResultsAsync()
    {
        var files = ResultFiles();
        var next = db.GetOrders().Count % files.Length;
        var order = db.AddOrder(OrderFromResults(files[next], daysAgo: 0));
        await StartPreinterpretationAsync(order);
        return order;
    }

    // INTEGRATION: in a real lab this is the moment the LIS releases the results of an order.
    // Start the preinterpretation now, so it is ready when the patient logs in.
    // A failure is only logged: the order is added anyway (the order page tries to start it again).
    // Awaited here for demo simplicity; a real LIS would do this in a background job.
    private async Task StartPreinterpretationAsync(Order order)
    {
        try
        {
            await preinterpretation.StartAsync(order);
        }
        catch (Exception e)
        {
            logger.LogError(e, "Could not start the Labplus preinterpretation of order {OrderId}", order.Id);
        }
    }

    private string[] ResultFiles()
    {
        var files = Directory.GetFiles(Path.Combine(env.ContentRootPath, "data-simulation__only-for-demo", "results"), "*.json");
        Array.Sort(files, StringComparer.Ordinal);
        return files;
    }

    // Builds an order (without id and number) from a results file. daysAgo is how long ago the lab released it.
    private static Order OrderFromResults(string file, int daysAgo)
    {
        var date = DateTime.Now.AddDays(-daysAgo);
        var results = JsonSerializer.Deserialize<ResultsFile>(File.ReadAllText(file), Json)!;
        return new Order(
            Id: 0,
            Number: "",
            Date: date.ToString("yyyy-MM-dd"),
            CollectedAt: date.AddDays(-1).ToString("yyyy-MM-dd") + " 08:15:00", // sample taken the day before
            Doctor: Doctor,
            Examinations: results.Examinations);
    }

    private record ResultsFile(List<Examination> Examinations);
}
