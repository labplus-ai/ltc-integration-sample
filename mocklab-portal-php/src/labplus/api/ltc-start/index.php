<?php
// Called by labplus.js when the patient clicks the LabTest Checker button: everything needed to open the iframe,
// { iframeUrl, origin, initToken, interviewStatus }. CSRF protection omitted for demo simplicity.
session_start();
require_once __DIR__ . '/../../ltc.php';

header('Content-Type: application/json');
if (!isset($_SESSION['user'])) {
    http_response_code(401);
    exit(json_encode(['error' => 'Not logged in']));
}
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    exit(json_encode(['error' => 'Use POST']));
}

$db = new Database();
$order = $db->getOrder((int) ($_GET['id'] ?? 0)); // Further input validation omitted for demo simplicity.
if (!$order) {
    http_response_code(404);
    exit(json_encode(['error' => 'This order does not exist.']));
}

$ltc = new LtcService($db);
// Defensive: the order page does not show the button in these two cases.
if (!$ltc->isConfigured()) {
    http_response_code(503);
    exit(json_encode(['status' => 'not_configured']));
}
if (!$ltc->isSupported($db->getPatient())) {
    http_response_code(403);
    exit(json_encode(['status' => 'not_supported']));
}

try {
    echo json_encode($ltc->start($order));
} catch (Throwable $e) {
    // The call to Labplus failed (rejected request, Labplus unavailable, unexpected answer).
    error_log('Labplus LTC start of order ' . $order['id'] . ' failed: ' . $e->getMessage());
    http_response_code(502);
    echo json_encode(['status' => 'error']);
}
