<?php
ob_start();
?>
<div class="top"><h1>Receipts</h1></div>
<div class="panel">
    <table>
        <thead><tr><th>No.</th><th>Amount</th><th>Method</th><th>Date</th><th></th></tr></thead>
        <tbody>
        <?php foreach ($receipts as $r): ?>
            <tr>
                <td><?= e($r['receipt_number']) ?></td>
                <td><?= e(money($r['amount'])) ?></td>
                <td><?= e($r['payment_method']) ?></td>
                <td><?= e($r['issued_at']) ?></td>
                <td><a href="<?= e(url('admin/receipt?id=' . $r['id'])) ?>">Open</a></td>
            </tr>
        <?php endforeach; ?>
        </tbody>
    </table>
</div>
<?php
$content = ob_get_clean();
$title = 'Receipts';
$active = 'receipts';
require __DIR__ . '/layout.php';
