<?php
ob_start();
$wished = wishlist_ids();
?>
<section class="section wrap">
    <p class="kicker">The collection</p>
    <h2 class="display">All jewellery</h2>
    <div class="layout">
        <form class="filters" method="get" action="<?= e(url('shop')) ?>">
            <h3>Search</h3>
            <input type="search" name="q" value="<?= e($filters['q'] ?? '') ?>" placeholder="Name, type, SKU">
            <h3>Type</h3>
            <select name="category">
                <option value="">All types</option>
                <?php foreach ($categories as $c): ?>
                    <option value="<?= e($c['slug']) ?>" <?= (($filters['category'] ?? '') === $c['slug']) ? 'selected' : '' ?>><?= e($c['name']) ?></option>
                <?php endforeach; ?>
            </select>
            <h3>Worn by</h3>
            <select name="gender">
                <option value="">Everyone</option>
                <option value="women" <?= (($filters['gender'] ?? '') === 'women') ? 'selected' : '' ?>>Women</option>
                <option value="men" <?= (($filters['gender'] ?? '') === 'men') ? 'selected' : '' ?>>Men</option>
                <option value="unisex" <?= (($filters['gender'] ?? '') === 'unisex') ? 'selected' : '' ?>>Unisex</option>
            </select>
            <h3>Price (KSh)</h3>
            <div class="price-row">
                <input type="number" name="min" min="0" step="50" placeholder="Min" value="<?= e((string)($filters['min'] ?? '')) ?>">
                <input type="number" name="max" min="0" step="50" placeholder="Max" value="<?= e((string)($filters['max'] ?? '')) ?>">
            </div>
            <label><input type="checkbox" name="in_stock" value="1" <?= !empty($filters['in_stock']) ? 'checked' : '' ?>> In stock</label>
            <h3>Sort</h3>
            <select name="sort">
                <option value="newest">Newest</option>
                <option value="price_asc" <?= (($filters['sort'] ?? '') === 'price_asc') ? 'selected' : '' ?>>Price · low to high</option>
                <option value="price_desc" <?= (($filters['sort'] ?? '') === 'price_desc') ? 'selected' : '' ?>>Price · high to low</option>
                <option value="name" <?= (($filters['sort'] ?? '') === 'name') ? 'selected' : '' ?>>Name</option>
            </select>
            <button class="btn btn-solid" style="margin-top:16px;width:100%">Filter</button>
        </form>
        <div>
            <?php if (!$products): ?>
                <div class="empty">No pieces match those filters.</div>
            <?php else: ?>
                <div class="product-grid">
                    <?php foreach ($products as $p) { include __DIR__ . '/partial-card.php'; } ?>
                </div>
            <?php endif; ?>
        </div>
    </div>
</section>
<?php
$content = ob_get_clean();
$title = 'Collection · Simply Lume';
require __DIR__ . '/layout.php';
