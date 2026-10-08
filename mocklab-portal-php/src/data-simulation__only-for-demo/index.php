<?php
// DEMO ONLY: handles the buttons from demo_box.php (simulate new results / reset).
// There is no need to read this when studying the integration.
// CSRF protection and other validation omitted for demo simplicity.
session_start();
require_once __DIR__ . '/../services/db.php';
require_once __DIR__ . '/../services/helpers.php';
require_once __DIR__ . '/simulator.php';

if (!isset($_SESSION['user'])) {
    header('Location: /login/');
    exit();
}

$simulator = new DataSimulator(new Database());

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (($_POST['action'] ?? '') === 'reset') {
        $simulator->reset();
        $_SESSION['flash'] = 'Demo data was reset to the initial 2 orders.';
    } else {
        $order = $simulator->releaseNextResults();
        $_SESSION['flash'] = 'New results arrived: order ' . $order['number'] . ' (' . order_title($order) . ').';
    }
}

header('Location: /orders/');
