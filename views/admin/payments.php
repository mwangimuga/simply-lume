<?php
ob_start();
?>
<div class="top"><h1>Payments</h1></div>
<div class="panel">
    <table>
        <thead><tr><th>Ref</th><th>Provider</th><th>Amount</th><th>Status</th><th>Date</th></tr></thead>
        <tbody>
        <?php foreach ($payments as $p): ?>
            <tr>
                <td><?= e($p['reference']) ?></td>
                <td><?= e($p['provider']) ?></td>
                <td><?= e(money($p['amount'])) ?></td>
                <td><?= e($p['status']) ?></td>
                <td><?= e($p['created_at']) ?></td>
            </tr>
        <?php endforeach; ?>
        </tbody>
    </table>
</div>
<?php
$content = ob_get_clean();
$title = 'Payments';
$active = 'payments';
require __DIR__ . '/layout.php';
