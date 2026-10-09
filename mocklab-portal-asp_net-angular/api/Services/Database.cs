using System.Text.Json;

namespace Mocklab.Api;

// The lab's own data: the patient and their orders.
// In a real lab this would be a database (LIS). Here the orders live in a JSON file (storage/orders.json),
// so the demo needs no database. Error handling for an unwritable storage folder omitted for demo simplicity.
public class Database
{
    private static readonly JsonSerializerOptions Json = new(JsonSerializerDefaults.Web) { WriteIndented = true };
    private static readonly object FileLock = new();

    private readonly string storageDir;
    private readonly string ordersFile;
    private readonly string patientFile;

    public Database(IWebHostEnvironment env)
    {
        storageDir = Path.Combine(env.ContentRootPath, "storage");
        ordersFile = Path.Combine(storageDir, "orders.json");
        patientFile = Path.Combine(env.ContentRootPath, "data-simulation__only-for-demo", "patient.json");
    }

    public bool HasOrders() => File.Exists(ordersFile);

    // The patient who logs in. DEMO ONLY: read from the demo data, a real system reads its own database.
    public Patient GetPatient() =>
        JsonSerializer.Deserialize<Patient>(File.ReadAllText(patientFile), Json)!;

    // All orders, keyed by order id.
    public Dictionary<int, Order> GetOrders()
    {
        lock (FileLock)
        {
            return JsonSerializer.Deserialize<Dictionary<int, Order>>(File.ReadAllText(ordersFile), Json)!;
        }
    }

    // One order, or null when it does not exist.
    public Order? GetOrder(int id) => GetOrders().GetValueOrDefault(id);

    public void SaveOrders(Dictionary<int, Order> orders)
    {
        lock (FileLock)
        {
            Directory.CreateDirectory(storageDir);
            File.WriteAllText(ordersFile, JsonSerializer.Serialize(orders, Json));
        }
    }

    // Adds a new order and gives it an id and an order number. Returns the stored order.
    public Order AddOrder(Order order)
    {
        var orders = HasOrders() ? GetOrders() : new Dictionary<int, Order>();
        var id = orders.Count > 0 ? orders.Keys.Max() + 1 : 1;
        var saved = order with { Id = id, Number = $"ML-{DateTime.Now.Year}-{200 + id:D6}" };
        orders[id] = saved;
        SaveOrders(orders);
        return saved;
    }
}
