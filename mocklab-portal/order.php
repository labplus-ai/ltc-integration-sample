<?php
// Order details with the results table.
require __DIR__ . '/includes/bootstrap.php';
require_login();

$patient = patient();
$id = (int) ($_GET['id'] ?? 0); // Further input validation omitted for demo simplicity.
$order = find_order($id);

if (!$order) {
    http_response_code(404);
}

$pageTitle = $order['number'] ?? 'Order not found';
$heroEyebrow = 'Order ' . ($order['number'] ?? '');
$heroTitle = $order ? order_title($order) : 'Order not found';
$heroSubtitle = $order ? 'Results from ' . $order['date'] : '';
require __DIR__ . '/includes/header.php';
?>

<a class="back-link" href="orders.php">&larr; Back to orders</a>

<?php if (!$order): ?>
    <div class="card"><p>This order does not exist.</p></div>
<?php else: ?>
    <div class="card info-card">
        <div><span class="label">Order number</span><?= e($order['number']) ?></div>
        <div><span class="label">Date</span><?= e($order['date']) ?></div>
        <div><span class="label">Patient</span><?= e($patient['firstName'] . ' ' . $patient['lastName']) ?></div>
        <div><span class="label">Doctor</span><?= e($order['doctor']) ?></div>
    </div>

    <div class="eyebrow eyebrow-line">Results</div>
    <h2 class="section-title">Test results</h2>

    <div class="table-wrap">
        <table>
            <thead>
                <tr><th>Test</th><th>Result</th><th>Unit</th><th>Reference range</th><th>Status</th></tr>
            </thead>
            <tbody>
            <?php foreach ($order['examinations'] as $exam): ?>
                <tr class="exam-row"><td colspan="5"><?= e($exam['name']) ?></td></tr>
                <?php foreach ($exam['params'] as $param): $flag = result_flag($param); ?>
                    <tr>
                        <td><strong><?= e($param['name']) ?></strong></td>
                        <td><?= e($param['value']) ?></td>
                        <td><?= e($param['unit']) ?></td>
                        <td><?= e(reference_text($param)) ?></td>
                        <td><?php if ($flag === 'none'): ?>
                                <span class="muted">-</span>
                            <?php else: ?>
                                <span class="badge badge-<?= $flag ?>"><?= ucfirst($flag) ?></span>
                            <?php endif; ?></td>
                    </tr>
                <?php endforeach; ?>
            <?php endforeach; ?>
            </tbody>
        </table>
    </div>

    <!-- Integration extension point: the LabTest Checker integration will be added below the results. -->
<?php endif; ?>

<?php require __DIR__ . '/includes/footer.php'; ?>
