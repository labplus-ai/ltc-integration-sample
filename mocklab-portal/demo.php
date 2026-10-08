<?php
// DEMO ONLY: handles the buttons from includes/demo_box.php.
// CSRF protection and other validation omitted for demo simplicity.
require __DIR__ . '/includes/bootstrap.php';
require_login();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (($_POST['action'] ?? '') === 'reset') {
        reset_demo();
        $_SESSION['flash'] = 'Demo data was reset to the initial 2 orders.';
    } else {
        $order = release_next_results();
        $_SESSION['flash'] = 'New results arrived: order ' . $order['number'] . ' (' . order_title($order) . ').';
    }
}

header('Location: orders.php');
exit;
