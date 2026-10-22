<?php
ob_start();
$wished = wishlist_ids();
?>
<section class="wrap product-hero">
    <div class="card-img">
        <img src="<?= e(product_image_url($product['image'])) ?>" alt="<?= e($product['name']) ?>">
    </div>
    <div class="product-copy">
        <p class="meta"><?= e($product['category_name']) ?> · <?= e($product['gender']) ?></p>
        <h1><?= e($product['name']) ?></h1>
        <p><?= e($product['description']) ?></p>
        <p class="price" style="font-size:1.3rem"><?= e(money($product['price'])) ?></p>
        <p class="notice"><?= (int)$product['stock'] > 0 ? (int)$product['stock'] . ' in atelier' : 'Currently spoken for' ?></p>
        <div class="qty">
            <span>Qty</span>
            <input type="number" name="qty" min="1" max="<?= (int)$product['stock'] ?>" value="1">
        </div>
        <div class="actions">
            <button class="btn btn-solid" data-add="<?= (int)$product['id'] ?>">Add to selection</button>
            <button class="btn" data-wish="<?= (int)$product['id'] ?>"><?= in_array((int)$product['id'], $wished, true) ? 'In wishlist' : 'Wishlist' ?></button>
        </div>
    </div>
</section>
<section class="section wrap" style="padding-top:0">
    <p class="kicker">Continue</p>
    <h2 class="display">Kindred pieces</h2>
    <div class="product-grid">
        <?php foreach ($related as $p) { include __DIR__ . '/partial-card.php'; } ?>
    </div>
</section>
<?php
$content = ob_get_clean();
$title = $product['name'] . ' · Simply Lume';
require __DIR__ . '/layout.php';
