<?php
// DEMO ONLY: pretends to be the lab that produces new test results.
// There is no need to read this when studying the integration: a real portal has nothing like it,
// orders would simply appear when the lab releases the results.
//
// The files in results/ stand for what a lab keeps in its own database: the examinations of one
// order with their measured parameters, as plain data. They are used in file name order.
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
        $this->db->addOrder($this->orderFromResults($files[0], 14));
        $this->db->addOrder($this->orderFromResults($files[1], 7));
    }

    // "The lab has just released new results": adds the next result set in the queue (3rd, 4th, ...,
    // and from the beginning again after the last one) as a new order. Returns the new order.
    function releaseNextResults(): array
    {
        $files = $this->resultFiles();
        $next = count($this->db->getOrders()) % count($files);
        return $this->db->addOrder($this->orderFromResults($files[$next], 0));
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
