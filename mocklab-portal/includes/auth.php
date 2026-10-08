<?php
// Login handling of the portal.

// Demo credentials, hardcoded and compared in plain text.
// Password hashing, rate limiting and CSRF protection omitted for demo simplicity.
const DEMO_USER = 'patient123';
const DEMO_PASSWORD = 'veryStrongPassword';

function is_logged_in(): bool
{
    return !empty($_SESSION['user']);
}

// Redirect to the login page unless the user is logged in.
function require_login(): void
{
    if (!is_logged_in()) {
        header('Location: index.php');
        exit;
    }
}

// Logs the user in when the credentials match. Returns whether it succeeded.
function attempt_login(string $username, string $password): bool
{
    if ($username !== DEMO_USER || $password !== DEMO_PASSWORD) {
        return false;
    }
    session_regenerate_id(true);
    $_SESSION['user'] = DEMO_USER;
    return true;
}
