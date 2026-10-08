<?php include __DIR__ . '/../partials/header.php'; ?>

<div class="card login-card">
    <h2>Log in</h2>
    <p class="muted">Demo credentials are already filled in. Just press the button.</p>

    <?php if ($error): ?>
        <div class="alert"><?= e($error) ?></div>
    <?php endif; ?>

    <form method="post">
        <label for="username">Username</label>
        <input id="username" name="username" type="text" value="<?= e($username) ?>">

        <label for="password">Password</label>
        <!-- Pre-filled on purpose (demo only). Never do this in a real application. -->
        <input id="password" name="password" type="password" value="<?= e($password) ?>">

        <button class="btn btn-primary" type="submit">Log in</button>
    </form>
</div>

<?php include __DIR__ . '/../partials/footer.php'; ?>
