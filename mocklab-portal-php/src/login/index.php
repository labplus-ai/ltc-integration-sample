<?php
// Login page. The form is pre-filled with the demo credentials.
session_start();
require_once __DIR__ . '/../services/helpers.php';

// Already logged in: go straight to the orders list.
if (isset($_SESSION['user'])) {
    header('Location: /orders/');
    exit();
}

// Demo credentials, hardcoded and compared in plain text.
// Password hashing, rate limiting and CSRF protection omitted for demo simplicity.
$username = 'patient123';
$password = 'veryStrongPassword';

$error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Input validation omitted for demo simplicity.
    if (($_POST['username'] ?? '') === $username && ($_POST['password'] ?? '') === $password) {
        session_regenerate_id(true);
        $_SESSION['user'] = $username;
        header('Location: /orders/');
        exit();
    }
    $error = 'Invalid username or password.';
}

$pageTitle = 'Log in';
$heroEyebrow = 'Patient portal';
$heroTitle = 'Your lab results';
$heroSubtitle = 'Log in to see your orders and test results.';

include __DIR__ . '/template.php';
