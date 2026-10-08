<?php
// The lab's own data: the patient and their orders.
// In a real lab this would be a database (LIS). Here the orders live in a JSON file,
// so the demo needs no database. Error handling for an unwritable storage folder omitted for demo simplicity.

const ORDERS_FILE = __DIR__ . '/../storage/orders.json';

function patient(): array
{
    return require __DIR__ . '/../data/patient.php';
}

// All orders, keyed by order id.
function load_orders(): array
{
    return json_decode(file_get_contents(ORDERS_FILE), true);
}

// Returns one order, or null when it does not exist.
function find_order(int $id): ?array
{
    return load_orders()[$id] ?? null;
}

// Stores one order (creates it or overwrites the existing one with the same id).
function save_order(array $order): void
{
    $orders = load_orders();
    $orders[$order['id']] = $order;
    save_orders($orders);
}

function save_orders(array $orders): void
{
    file_put_contents(ORDERS_FILE, json_encode($orders, JSON_PRETTY_PRINT), LOCK_EX);
}

// Adds a new order and gives it an id and an order number. Returns the stored order.
function add_order(array $order): array
{
    $orders = load_orders();
    $order['id'] = $orders ? max(array_keys($orders)) + 1 : 1;
    $order['number'] = sprintf('ML-%s-%06d', date('Y'), 200 + $order['id']);
    save_order($order);
    return $order;
}
