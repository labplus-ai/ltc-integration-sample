<?php
// Order details with the results table.
session_start();
require_once __DIR__ . '/../services/db.php';
require_once __DIR__ . '/../services/helpers.php';

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

$pageTitle = $order['number'] ?? 'Order not found';
$heroEyebrow = 'Order ' . ($order['number'] ?? '');
$heroTitle = $order ? order_title($order) : 'Order not found';
$heroSubtitle = $order ? 'Results from ' . $order['date'] : '';

include __DIR__ . '/template.php';
