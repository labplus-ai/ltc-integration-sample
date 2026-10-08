<?php include __DIR__ . '/../partials/header.php'; ?>

<?php if ($flash): ?>
    <div class="notice"><?= e($flash) ?></div>
<?php endif; ?>

<div class="card info-card">
    <div><span class="label">Patient</span><?= e($patient['firstName'] . ' ' . $patient['lastName']) ?></div>
    <div><span class="label">Gender, age</span><?= e($patientText) ?></div>
    <div><span class="label">Date of birth</span><?= e($patient['birthDate']) ?></div>
    <div><span class="label">National ID</span><?= e($patient['nationalId']) ?></div>
</div>

<div class="eyebrow eyebrow-line">Orders</div>
<h2 class="section-title">Your orders</h2>
<p class="muted">All tests done for you at MockLab.</p>

<?php include __DIR__ . '/../data-simulation__only-for-demo/demo_box.php'; // DEMO ONLY ?>

<div class="grid">
    <?php foreach ($orders as $order): $outOfRange = count_out_of_range($order); ?>
        <a class="card order-card" href="/order/?id=<?= (int) $order['id'] ?>">
            <span class="num-badge"><?= sprintf('%02d', $order['id']) ?></span>
            <h3><?= e(order_title($order)) ?></h3>
            <p class="muted"><?= e($order['number']) ?> &middot; <?= e($order['date']) ?></p>
            <div class="tags">
                <span class="pill pill-grey"><?= count(order_params($order)) ?> results</span>
                <?php if ($outOfRange): ?>
                    <span class="pill pill-alert"><?= $outOfRange ?> out of range</span>
                <?php else: ?>
                    <span class="pill pill-mint">All in range</span>
                <?php endif; ?>
            </div>
        </a>
    <?php endforeach; ?>
</div>

<?php include __DIR__ . '/../partials/footer.php'; ?>
