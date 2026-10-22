<?php
ob_start();
?>
<section class="section wrap">
    <p class="kicker">Kept aside</p>
    <h2 class="display">Wishlist</h2>
    <?php if (!$products): ?>
        <div class="empty">Nothing saved yet. Wander the <a href="<?= e(url('shop')) ?>">collection</a>.</div>
    <?php else: ?>
        <div class="product-grid">
            <?php foreach ($products as $p) { include __DIR__ . '/partial-card.php'; } ?>
        </div>
    <?php endif; ?>
</section>
<?php
$content = ob_get_clean();
$title = 'Wishlist · Simply Lume';
require __DIR__ . '/layout.php';
