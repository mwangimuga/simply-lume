<?php
ob_start();
$items = cart();
?>
<section class="section wrap">
    <p class="kicker">Your selection</p>
    <h2 class="display">Order</h2>
    <?php if (!$items): ?>
        <div class="empty">The tray is empty.</div>
    <?php else: ?>
        <table class="table">
            <thead><tr><th></th><th>Piece</th><th>Qty</th><th>Price</th></tr></thead>
            <tbody>
            <?php foreach ($items as $item): ?>
                <tr>
                    <td><img src="<?= e(product_image_url($item['image'] ?? null)) ?>" alt=""></td>
                    <td><?= e($item['name']) ?></td>
                    <td><?= (int)$item['qty'] ?></td>
                    <td><?= e(money($item['price'] * $item['qty'])) ?></td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
        <p style="text-align:right;font-family:var(--serif);font-size:1.6rem">Total <?= e(money(cart_total())) ?></p>
        <p><a class="btn btn-solid" href="<?= e(url('checkout')) ?>">Checkout</a></p>
    <?php endif; ?>
</section>
<?php
$content = ob_get_clean();
$title = 'Selection · Simply Lume';
require __DIR__ . '/layout.php';
