<?php
// The preinterpretation of an order, asked for by labplus.js while Labplus is still preparing it.
// Answers { status, html, testsHtml }: the same parts of the results summary as the order page renders.
session_start();
require_once __DIR__ . '/../../../services/helpers.php';
require_once __DIR__ . '/../../preinterpretation.php';

header('Content-Type: application/json');
if (!isset($_SESSION['user'])) {
    http_response_code(401);
    exit(json_encode(['error' => 'Not logged in']));
}

$db = new Database();
$order = $db->getOrder((int) ($_GET['id'] ?? 0)); // Further input validation omitted for demo simplicity.
if (!$order) {
    http_response_code(404);
    exit(json_encode(['error' => 'This order does not exist.']));
}

try {
    $preinterpretation = (new PreinterpretationService($db))->get($order);
} catch (Throwable $e) {
    error_log('Labplus preinterpretation of order ' . $order['id'] . ' failed: ' . $e->getMessage());
    $preinterpretation = ['status' => 'error'];
}

ob_start();
include __DIR__ . '/../../summary_preinterpretation.php';
$html = ob_get_clean();

ob_start();
include __DIR__ . '/../../summary_tests.php';
$testsHtml = ob_get_clean();

echo json_encode(['status' => $preinterpretation['status'], 'html' => $html, 'testsHtml' => $testsHtml]);
