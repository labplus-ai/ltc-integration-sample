<?php
// Destroy the session and go back to the login page.
require __DIR__ . '/includes/bootstrap.php';

$_SESSION = [];
session_destroy();

header('Location: index.php');
exit;
