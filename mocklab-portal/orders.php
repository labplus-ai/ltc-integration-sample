<?php
// Orders list: one card per order, newest first.
require __DIR__ . '/includes/bootstrap.php';
require_login();

$orders = load_orders();
$patient = patient();
krsort($orders);

$flash = $_SESSION['flash'] ?? null;
unset($_SESSION['flash']);

$pageTitle = 'Orders';
$heroEyebrow = 'Patient portal';
$heroTitle = 'Your lab results';
$heroSubtitle = 'Pick an order to see its results.';
require __DIR__ . '/includes/header.php';
?>

<?php if ($flash): ?>
    <div class="notice"><?= e($flash) ?></div>
<?php endif; ?>

<div class="card info-card">
    <div><span class="label">Patient</span><?= e($patient['firstName'] . ' ' . $patient['lastName']) ?></div>
    <div><span class="label">Gender, age</span><?= e(patient_text()) ?></div>
    <div><span class="label">Date of birth</span><?= e($patient['birthDate']) ?></div>
    <div><span class="label">National ID</span><?= e($patient['nationalId']) ?></div>
</div>

<div class="eyebrow eyebrow-line">Orders</div>
<h2 class="section-title">Your orders</h2>
<p class="muted">All tests done for you at MockLab.</p>

<?php require __DIR__ . '/includes/demo_box.php'; // demo only ?>

<div class="grid">
    <?php foreach ($orders as $order): $outOfRange = count_out_of_range($order); ?>
        <a class="card order-card" href="order.php?id=<?= (int) $order['id'] ?>">
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

<?php require __DIR__ . '/includes/footer.php'; ?>
