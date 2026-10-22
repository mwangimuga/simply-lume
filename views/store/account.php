<?php
ob_start();
?>
<section class="section wrap">
    <p class="kicker">Client</p>
    <h2 class="display"><?= e($user['name']) ?></h2>
    <p><a class="btn" href="<?= e(url('wishlist')) ?>">Wishlist</a></p>
    <h3 class="kicker" style="margin-top:40px">Orders</h3>
    <?php if (!$orders): ?>
        <p class="notice">No orders yet.</p>
    <?php else: ?>
        <table class="table">
            <thead><tr><th>No.</th><th>Total</th><th>Payment</th><th>Status</th><th>Date</th></tr></thead>
            <tbody>
            <?php foreach ($orders as $o): ?>
                <tr>
                    <td><?= e($o['order_number']) ?></td>
                    <td><?= e(money($o['total'])) ?></td>
                    <td><?= e($o['payment_method'] . ' · ' . $o['payment_status']) ?></td>
                    <td><?= e($o['order_status']) ?></td>
                    <td><?= e($o['created_at']) ?></td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    <?php endif; ?>
</section>
<?php
$content = ob_get_clean();
$title = 'Account · Simply Lume';
require __DIR__ . '/layout.php';
