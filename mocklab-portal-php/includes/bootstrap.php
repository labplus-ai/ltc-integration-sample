<?php
// Shared setup, included at the top of every page.

session_start();

require __DIR__ . '/env.php';
require __DIR__ . '/auth.php';
require __DIR__ . '/lab.php';
require __DIR__ . '/simulator.php';
require __DIR__ . '/view.php';

// First run: put the starting orders in place.
if (!file_exists(ORDERS_FILE)) {
    reset_demo();
}
