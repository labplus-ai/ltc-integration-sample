<?php
// The lab's own data: the patient and their orders.
// In a real lab this would be a database (LIS). Here the orders live in a JSON file (storage/orders.json),
// so the demo needs no database. Error handling for an unwritable storage folder omitted for demo simplicity.
class Database
{
    private string $ordersFile = __DIR__ . '/../../storage/orders.json';

    function __construct()
    {
        // DEMO ONLY: on the first run put the starting orders in place.
        if (!file_exists($this->ordersFile)) {
            require_once __DIR__ . '/../data-simulation__only-for-demo/simulator.php';
            (new DataSimulator($this))->reset();
        }
    }

    // The patient who logs in. DEMO ONLY: read from the demo data, a real system reads its own database.
    function getPatient(): array
    {
        return require __DIR__ . '/../data-simulation__only-for-demo/patient.php';
    }

    // All orders, keyed by order id.
    function getOrders(): array
    {
        return json_decode(file_get_contents($this->ordersFile), true);
    }

    // One order, or null when it does not exist.
    function getOrder(int $id): ?array
    {
        return $this->getOrders()[$id] ?? null;
    }

    // Stores one order (creates it or overwrites the existing one with the same id).
    function saveOrder(array $order): void
    {
        $orders = $this->getOrders();
        $orders[$order['id']] = $order;
        $this->saveOrders($orders);
    }

    function saveOrders(array $orders): void
    {
        file_put_contents($this->ordersFile, json_encode($orders, JSON_PRETTY_PRINT), LOCK_EX);
    }

    // Changes one order and saves it. Reading, changing and saving happen under one file lock, so two requests
    // that change the same order at the same time do not overwrite each other. Does nothing when the order does not exist.
    function updateOrder(int $id, callable $change): void
    {
        $lock = fopen(sys_get_temp_dir() . '/mocklab-orders.lock', 'c');
        flock($lock, LOCK_EX);
        $orders = $this->getOrders();
        if (isset($orders[$id])) {
            $orders[$id] = $change($orders[$id]);
            $this->saveOrders($orders);
        }
        flock($lock, LOCK_UN);
        fclose($lock);
    }

    // Adds a new order and gives it an id and an order number. Returns the stored order.
    function addOrder(array $order): array
    {
        $orders = $this->getOrders();
        $order['id'] = $orders ? max(array_keys($orders)) + 1 : 1;
        $order['number'] = sprintf('ML-%s-%06d', date('Y'), 200 + $order['id']);
        $this->saveOrder($order);
        return $order;
    }
}
