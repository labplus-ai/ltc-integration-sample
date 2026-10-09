<?php
require_once __DIR__ . '/../services/db.php';

// What the Labplus integration stores per order, in $order['labplus'] (like extra columns in the lab's orders table):
//   platformToken                     - identifies the order's results at Labplus (shared by PRE and LTC)
//   preinterpretationId               - id of the preinterpretation
//   preinterpretationAccessSignature  - needed to get the preinterpretation result
//   preinterpretationResult           - the final answer of Labplus, kept so Labplus is not asked again
//   interviewToken                    - identifies the patient's LabTest Checker interview, needed to come back later
function labplus_data(?array $order): array
{
    return $order['labplus'] ?? [];
}

// Saves some of these fields of an order, e.g. save_labplus_data($db, 3, ['interviewToken' => $token]).
function save_labplus_data(Database $db, int $orderId, array $fields): void
{
    $db->updateOrder($orderId, function (array $order) use ($fields) {
        $order['labplus'] = $fields + labplus_data($order);
        return $order;
    });
}

// Runs $action while holding the lock named $name, so the same step never runs twice at the same time
// (e.g. a double click creating two LabTest Checker interviews). The lock files live in the system temp folder.
function with_lock(string $name, callable $action)
{
    $lock = fopen(sys_get_temp_dir() . "/mocklab-$name.lock", 'c');
    flock($lock, LOCK_EX);
    try {
        return $action();
    } finally {
        flock($lock, LOCK_UN);
        fclose($lock);
    }
}
