<?php
// DEMO ONLY: pretends to be the lab that produces new test results.
// A real portal has nothing like this; orders would simply appear when the lab releases the results.
//
// The files in data/results/ stand for what a lab keeps in its own database: the examinations of one
// order with their measured parameters, as plain data. They are used in file name order.

const DOCTOR = 'Dr. Emily Carter';

function result_files(): array
{
    $files = glob(__DIR__ . '/../data/results/*.json');
    sort($files);
    return $files;
}

// Builds an order (without id and number) from a results file. $daysAgo is how long ago the lab released it.
function order_from_results(string $file, int $daysAgo): array
{
    $date = strtotime("-$daysAgo days");
    return [
        'date' => date('Y-m-d', $date),
        'collectedAt' => date('Y-m-d', strtotime('-1 day', $date)) . ' 08:15:00', // sample taken the day before
        'doctor' => DOCTOR,
        'examinations' => json_decode(file_get_contents($file), true)['examinations'],
    ];
}

// Starting point of the demo: the first two result sets are already in the patient's account.
function reset_demo(): void
{
    $files = result_files();
    save_orders([]);
    add_order(order_from_results($files[0], 14));
    add_order(order_from_results($files[1], 7));
}

// "The lab has just released new results": adds the next result set in the queue (3rd, 4th, ...,
// and from the beginning again after the last one) as a new order. Returns the new order.
function release_next_results(): array
{
    $files = result_files();
    $next = count(load_orders()) % count($files);
    return add_order(order_from_results($files[$next], 0));
}
