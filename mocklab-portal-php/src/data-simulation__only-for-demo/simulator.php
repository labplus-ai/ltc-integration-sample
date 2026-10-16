<?php
// DEMO ONLY: pretends to be the lab that produces new test results.
// There is no need to read this when studying the integration: a real portal has nothing like it,
// orders would simply appear when the lab releases the results.
//
// The files in results/ stand for what a lab keeps in its own database: the examinations of one
// order with their measured parameters, as plain data. They are used in file name order.
require_once __DIR__ . '/../labplus/preinterpretation.php';

class DataSimulator
{
    const DOCTOR = 'Dr. Emily Carter';

    private Database $db;

    function __construct(Database $db)
    {
        $this->db = $db;
    }

    // Starting point of the demo: the first two result sets are already in the patient's account.
    function reset(): void
    {
        $files = $this->resultFiles();
        $this->db->saveOrders([]);
        $this->startPreinterpretation($this->db->addOrder($this->orderFromResults($files[0], 14)));
        $this->startPreinterpretation($this->db->addOrder($this->orderFromResults($files[1], 7)));
    }

    // "The lab has just released new results": adds the next result set in the queue (3rd, 4th, ...,
    // and from the beginning again after the last one) as a new order. Returns the new order.
    function releaseNextResults(): array
    {
        $files = $this->resultFiles();
        $next = count($this->db->getOrders()) % count($files);
        $order = $this->db->addOrder($this->orderFromResults($files[$next], 0));
        $this->startPreinterpretation($order);
        return $order;
    }

    // INTEGRATION: in a real lab this is the moment the LIS releases the results of an order.
    // Start the preinterpretation now, so it is ready when the patient logs in.
    // A failure is only logged: the order is added anyway (the order page tries to start it again).
    // Done right away for demo simplicity; a real LIS would do this in a background job.
    private function startPreinterpretation(array $order): void
    {
        try {
            (new PreinterpretationService($this->db))->start($order);
        } catch (Throwable $e) {
            error_log('Could not start the Labplus preinterpretation of order ' . $order['id'] . ': ' . $e->getMessage());
        }
    }

    private function resultFiles(): array
    {
        $files = glob(__DIR__ . '/results/*.json');
        sort($files);
        return $files;
    }

    // Builds an order (without id and number) from a results file. $daysAgo is how long ago the lab released it.
    private function orderFromResults(string $file, int $daysAgo): array
    {
        $date = strtotime("-$daysAgo days");
        return [
            'date' => date('Y-m-d', $date),
            'collectedAt' => date('Y-m-d', strtotime('-1 day', $date)) . ' 08:15:00', // sample taken the day before
            'doctor' => self::DOCTOR,
            'examinations' => json_decode(file_get_contents($file), true)['examinations'],
        ];
    }
}
