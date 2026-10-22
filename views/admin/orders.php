<?php
ob_start();
?>
<div class="top"><h1>Orders</h1></div>
<div class="panel">
    <table>
        <thead><tr><th>No.</th><th>Client</th><th>Total</th><th>Pay</th><th>Status</th><th></th></tr></thead>
        <tbody>
        <?php foreach ($orders as $o): ?>
            <tr>
                <td><a href="<?= e(url('admin/order?id=' . $o['id'])) ?>"><?= e($o['order_number']) ?></a></td>
                <td><?= e($o['customer_name']) ?><br><small><?= e($o['customer_phone']) ?></small></td>
                <td><?= e(money($o['total'])) ?></td>
                <td><?= e($o['payment_method']) ?> · <?= e($o['payment_status']) ?></td>
                <td><span class="tag <?= e($o['order_status']) ?>"><?= e($o['order_status']) ?></span></td>
                <td>
                    <?php if ($o['order_status'] === 'pending' || $o['order_status'] === 'paid'): ?>
                        <form method="post" action="<?= e(url('admin/orders')) ?>" style="display:inline">
                            <?= csrf_field() ?>
                            <input type="hidden" name="fulfill_id" value="<?= (int)$o['id'] ?>">
                            <button class="btn btn-maroon">Fulfil</button>
                        </form>
                        <form method="post" action="<?= e(url('admin/orders')) ?>" style="display:inline">
                            <?= csrf_field() ?>
                            <input type="hidden" name="cancel_id" value="<?= (int)$o['id'] ?>">
                            <button class="btn">Cancel</button>
                        </form>
                    <?php endif; ?>
                    <?php if ($o['payment_status'] === 'pending' && can('finance')): ?>
                        <form method="post" action="<?= e(url('admin/orders')) ?>" style="display:inline">
                            <?= csrf_field() ?>
                            <input type="hidden" name="mark_paid" value="<?= (int)$o['id'] ?>">
                            <button class="btn">Mark paid</button>
                        </form>
                    <?php endif; ?>
                </td>
            </tr>
        <?php endforeach; ?>
        </tbody>
    </table>
</div>
<?php
$content = ob_get_clean();
$title = 'Orders';
$active = 'orders';
require __DIR__ . '/layout.php';
