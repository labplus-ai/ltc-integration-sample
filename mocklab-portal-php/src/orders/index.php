<?php
// Orders list: one card per order, newest first.
session_start();
require_once __DIR__ . '/../services/db.php';
require_once __DIR__ . '/../services/helpers.php';

if (!isset($_SESSION['user'])) {
    header('Location: /login/');
    exit();
}

$db = new Database();
$patient = $db->getPatient();
$orders = $db->getOrders();
krsort($orders);

// Patient description, e.g. "Male, 33 years".
$age = (new DateTime($patient['birthDate']))->diff(new DateTime())->y;
$patientText = ucfirst($patient['gender']) . ', ' . $age . ' years';

// DEMO ONLY: message shown after using the simulation buttons.
$flash = $_SESSION['flash'] ?? null;
unset($_SESSION['flash']);

$pageTitle = 'Orders';
$heroEyebrow = 'Patient portal';
$heroTitle = 'Your lab results';
$heroSubtitle = 'Pick an order to see its results.';

include __DIR__ . '/template.php';
