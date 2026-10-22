<?php
$wished = wishlist_ids();
?>
<article class="card">
    <a href="<?= e(url('product/' . $p['slug'])) ?>">
        <div class="card-img">
            <img src="<?= e(product_image_url($p['image'] ?? null)) ?>" alt="<?= e($p['name']) ?>">
            <button class="wish" type="button" data-wish="<?= (int)$p['id'] ?>" aria-label="Wishlist"><?= in_array((int)$p['id'], $wished, true) ? '♥' : '♡' ?></button>
        </div>
        <p class="meta"><?= e($p['category_name'] ?? '') ?> · <?= e($p['gender']) ?></p>
        <h3><?= e($p['name']) ?></h3>
        <div class="price"><?= e(money($p['price'])) ?></div>
    </a>
</article>
