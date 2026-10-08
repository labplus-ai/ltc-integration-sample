<?php
// Login page. The form is pre-filled with the demo credentials.
require __DIR__ . '/includes/bootstrap.php';

// Already logged in: go straight to the orders list.
if (is_logged_in()) {
    header('Location: orders.php');
    exit;
}

$error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Input validation and CSRF token check omitted for demo simplicity.
    if (attempt_login($_POST['username'] ?? '', $_POST['password'] ?? '')) {
        header('Location: orders.php');
        exit;
    }
    $error = 'Invalid username or password.';
}

$pageTitle = 'Log in';
$heroEyebrow = 'Patient portal';
$heroTitle = 'Your lab results';
$heroSubtitle = 'Log in to see your orders and test results.';
require __DIR__ . '/includes/header.php';
?>

<div class="card login-card">
    <h2>Log in</h2>
    <p class="muted">Demo credentials are already filled in. Just press the button.</p>

    <?php if ($error): ?>
        <div class="alert"><?= e($error) ?></div>
    <?php endif; ?>

    <form method="post">
        <label for="username">Username</label>
        <input id="username" name="username" type="text" value="<?= e(DEMO_USER) ?>">

        <label for="password">Password</label>
        <!-- Pre-filled on purpose (demo only). Never do this in a real application. -->
        <input id="password" name="password" type="password" value="<?= e(DEMO_PASSWORD) ?>">

        <button class="btn btn-primary" type="submit">Log in</button>
    </form>
</div>

<?php require __DIR__ . '/includes/footer.php'; ?>
