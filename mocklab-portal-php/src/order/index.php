<?php
// Order details with the results table.
session_start();
require_once __DIR__ . '/../services/db.php';
require_once __DIR__ . '/../services/helpers.php';
require_once __DIR__ . '/../labplus/preinterpretation.php';
require_once __DIR__ . '/../labplus/ltc.php';

if (!isset($_SESSION['user'])) {
    header('Location: /login/');
    exit();
}

$db = new Database();
$patient = $db->getPatient();

$id = (int) ($_GET['id'] ?? 0); // Further input validation omitted for demo simplicity.
$order = $db->getOrder($id);

if (!$order) {
    http_response_code(404);
}

// Labplus integration: the results summary (see src/labplus/). A failed call to Labplus only shows a short note there.
if ($order) {
    try {
        $preinterpretation = (new PreinterpretationService($db))->get($order);
    } catch (Throwable $e) {
        error_log('Labplus preinterpretation of order ' . $order['id'] . ' failed: ' . $e->getMessage());
        $preinterpretation = ['status' => 'error'];
    }
    try {
        $ltcStatus = (new LtcService($db))->getStatus($order);
    } catch (Throwable $e) {
        error_log('Labplus LTC status of order ' . $order['id'] . ' failed: ' . $e->getMessage());
        $ltcStatus = 'error';
    }
}

$pageTitle = $order['number'] ?? 'Order not found';
$heroEyebrow = 'Order ' . ($order['number'] ?? '');
$heroTitle = $order ? order_title($order) : 'Order not found';
$heroSubtitle = $order ? 'Results from ' . $order['date'] : '';

include __DIR__ . '/template.php';
