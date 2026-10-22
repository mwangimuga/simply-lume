<?php
$nav = [
    '' => 'Dashboard',
    'products' => 'Jewellery',
    'inventory' => 'Inventory',
    'orders' => 'Orders',
    'customers' => 'Clients',
    'expenses' => 'Expenses',
    'invoices' => 'Invoices',
    'receipts' => 'Receipts',
    'payments' => 'Payments',
    'users' => 'Users',
    'settings' => 'Settings',
];
$role = current_user()['role'];
$hide = [];
if ($role === 'finance') {
    $hide = ['products', 'inventory', 'customers', 'users', 'settings'];
}
if ($role === 'sales') {
    $hide = ['expenses', 'receipts', 'payments', 'users', 'settings'];
}
?><!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="<?= e(csrf_token()) ?>">
    <title><?= e($title ?? 'Dashboard') ?> · Simply Lume</title>
    <link rel="icon" href="<?= e(url('assets/img/favicon.svg')) ?>" type="image/svg+xml">
    <link href="https://fonts.googleapis.com/css2?family=Cormorant+Garamond:wght@500;600&family=Jost:wght@300;400;500&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="<?= e(url('assets/css/admin.css')) ?>">
</head>
<body>
<div class="admin">
    <aside class="side no-print">
        <div class="brand">Simply Lume</div>
        <?php foreach ($nav as $path => $label): ?>
            <?php if (in_array($path, $hide, true)) continue; ?>
            <a href="<?= e(url('admin/' . $path)) ?>" class="<?= ($active ?? '') === $path ? 'active' : '' ?>"><?= e($label) ?></a>
        <?php endforeach; ?>
        <a href="<?= e(url()) ?>">View atelier</a>
        <a href="<?= e(url('logout')) ?>">Sign out</a>
        <p style="margin-top:24px;font-size:.72rem;color:#9b9088;letter-spacing:.12em;text-transform:uppercase"><?= e(current_user()['role']) ?></p>
    </aside>
    <div class="main">
        <?= $content ?>
    </div>
</div>
<script>window.SL_BASE = <?= json_encode(rtrim(url(), '/') . '/') ?>;</script>
<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.1/dist/chart.umd.min.js"></script>
<script src="<?= e(url('assets/js/admin.js')) ?>"></script>
<?php if (!empty($chartBoot)): ?>
<script>
(() => {
  const ctx = document.getElementById('salesChart');
  if (!ctx || !window.Chart) return;
  window.salesChart = new Chart(ctx, {
    type: 'line',
    data: {
      labels: <?= json_encode($chartBoot['labels']) ?>,
      datasets: [{
        label: 'Sales',
        data: <?= json_encode($chartBoot['values']) ?>,
        borderColor: '#6b1428',
        backgroundColor: 'rgba(107,20,40,.2)',
        tension: .35,
        fill: true
      }]
    },
    options: { plugins: { legend: { display: false } }, scales: { x: { ticks: { color: '#9b9088' } }, y: { ticks: { color: '#9b9088' } } } }
  });
})();
</script>
<?php endif; ?>
</body>
</html>
