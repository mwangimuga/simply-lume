<?php
ob_start();
?>
<div class="top"><h1><?= e($order['order_number']) ?></h1></div>
<div class="panel">
    <p><?= e($order['customer_name']) ?> · <?= e($order['customer_phone']) ?> · <?= e($order['customer_email'] ?? '') ?></p>
    <p><?= e($order['payment_method']) ?> · <?= e($order['payment_status']) ?> · <?= e($order['order_status']) ?></p>
    <?php if ($order['whatsapp_message']): ?>
        <p><em><?= e($order['whatsapp_message']) ?></em></p>
        <p><a class="btn" href="<?= e(whatsapp_link($order['whatsapp_message'])) ?>" target="_blank" rel="noopener">Open WhatsApp</a></p>
    <?php endif; ?>
    <table>
        <thead><tr><th>Piece</th><th>Qty</th><th>Line</th></tr></thead>
        <tbody>
        <?php foreach ($items as $i): ?>
            <tr><td><?= e($i['product_name']) ?></td><td><?= (int)$i['qty'] ?></td><td><?= e(money($i['line_total'])) ?></td></tr>
        <?php endforeach; ?>
        </tbody>
    </table>
    <p>Total <?= e(money($order['total'])) ?></p>
    <p class="no-print">
        <a class="btn" href="<?= e(url('admin/invoice?order_id=' . $order['id'])) ?>">Invoice</a>
        <?php if ($order['order_status'] !== 'fulfilled'): ?>
        <form method="post" action="<?= e(url('admin/orders')) ?>" style="display:inline">
            <?= csrf_field() ?>
            <input type="hidden" name="fulfill_id" value="<?= (int)$order['id'] ?>">
            <button class="btn btn-maroon">Fulfil</button>
        </form>
        <?php endif; ?>
    </p>
</div>
<?php
$content = ob_get_clean();
$title = $order['order_number'];
$active = 'orders';
require __DIR__ . '/layout.php';
