<?php
ob_start();
?>
<div class="top"><h1>Atelier pulse</h1><span>Refreshes every few seconds</span></div>
<div data-dashboard>
    <div class="stats">
        <div class="stat"><span>Sales to date</span><strong data-stat="sales"><?= e(money($stats['sales'])) ?></strong></div>
        <div class="stat"><span>Today</span><strong data-stat="today"><?= e(money($stats['today'])) ?></strong></div>
        <div class="stat"><span>Pending orders</span><strong data-stat="pending"><?= (int)$stats['pending'] ?></strong></div>
        <div class="stat"><span>Low stock</span><strong data-stat="low"><?= (int)$stats['low'] ?></strong></div>
        <div class="stat"><span>Units on hand</span><strong data-stat="units"><?= (int)$stats['units'] ?></strong></div>
        <div class="stat"><span>Expenses this month</span><strong data-stat="expenses"><?= e(money($stats['expenses'])) ?></strong></div>
    </div>
    <div class="grid-2">
        <div class="panel">
            <h3>Sales · 14 days</h3>
            <canvas id="salesChart" height="120"></canvas>
        </div>
        <div class="panel">
            <h3>Inventory pressure</h3>
            <?php foreach ($stats['inventory'] as $row): ?>
                <?php $max = max(12, (int)$row['stock']); $pct = min(100, ((int)$row['stock'] / $max) * 100); ?>
                <p><?= e($row['name']) ?> · <?= (int)$row['stock'] ?></p>
                <div class="bar"><i style="width:<?= (int)$pct ?>%"></i></div>
            <?php endforeach; ?>
        </div>
    </div>
    <div class="panel">
        <h3>Pending orders · fulfil manually</h3>
        <table>
            <thead><tr><th>No.</th><th>Client</th><th>Total</th><th>Status</th><th></th></tr></thead>
            <tbody data-pending-body>
            <?php foreach ($stats['pendingOrders'] as $o): ?>
                <tr>
                    <td><?= e($o['order_number']) ?></td>
                    <td><?= e($o['customer_name']) ?></td>
                    <td><?= e(money($o['total'])) ?></td>
                    <td><span class="tag pending"><?= e($o['order_status']) ?></span></td>
                    <td class="no-print"><?php if (can('orders.fulfill')): ?><button class="btn btn-maroon" data-fulfill="<?= (int)$o['id'] ?>">Fulfil</button><?php endif; ?></td>
                </tr>
            <?php endforeach; ?>
            <?php if (!$stats['pendingOrders']): ?>
                <tr><td colspan="5">No pending orders.</td></tr>
            <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>
<?php
$labels = [];
$values = [];
$map = [];
foreach ($stats['trend'] as $row) {
    $map[$row['d']] = (float)$row['t'];
}
for ($i = 13; $i >= 0; $i--) {
    $d = date('Y-m-d', strtotime("-$i days"));
    $labels[] = date('d M', strtotime($d));
    $values[] = $map[$d] ?? 0;
}
$chartBoot = ['labels' => $labels, 'values' => $values];
$content = ob_get_clean();
$title = 'Dashboard';
$active = '';
require __DIR__ . '/layout.php';
