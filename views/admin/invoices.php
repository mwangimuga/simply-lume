<?php
ob_start();
?>
<div class="top"><h1>Invoices</h1></div>
<div class="panel">
    <table>
        <thead><tr><th>No.</th><th>Client</th><th>Total</th><th>Status</th><th></th></tr></thead>
        <tbody>
        <?php foreach ($invoices as $i): ?>
            <tr>
                <td><?= e($i['invoice_number']) ?></td>
                <td><?= e($i['client_name']) ?></td>
                <td><?= e(money($i['total'])) ?></td>
                <td><?= e($i['status']) ?></td>
                <td><a href="<?= e(url('admin/invoice?id=' . $i['id'])) ?>">Open</a></td>
            </tr>
        <?php endforeach; ?>
        </tbody>
    </table>
</div>
<?php
$content = ob_get_clean();
$title = 'Invoices';
$active = 'invoices';
require __DIR__ . '/layout.php';
