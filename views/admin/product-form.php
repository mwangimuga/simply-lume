<?php
ob_start();
$p = $product ?? [];
?>
<div class="top"><h1><?= empty($p['id']) ? 'New piece' : 'Edit piece' ?></h1></div>
<?php if (!empty($error)): ?><div class="flash"><?= e($error) ?></div><?php endif; ?>
<form method="post" enctype="multipart/form-data" class="panel">
    <?= csrf_field() ?>
    <div class="form-grid">
        <div><label>Name</label><input name="name" required value="<?= e($p['name'] ?? '') ?>"></div>
        <div><label>SKU</label><input name="sku" required value="<?= e($p['sku'] ?? '') ?>"></div>
        <div>
            <label>Type</label>
            <select name="category_id" required>
                <?php foreach ($categories as $c): ?>
                    <option value="<?= (int)$c['id'] ?>" <?= ((int)($p['category_id'] ?? 0) === (int)$c['id']) ? 'selected' : '' ?>><?= e($c['name']) ?></option>
                <?php endforeach; ?>
            </select>
        </div>
        <div>
            <label>Gender</label>
            <select name="gender">
                <?php foreach (['women','men','unisex'] as $g): ?>
                    <option value="<?= $g ?>" <?= (($p['gender'] ?? '') === $g) ? 'selected' : '' ?>><?= $g ?></option>
                <?php endforeach; ?>
            </select>
        </div>
        <div><label>Price (KSh)</label><input type="number" step="0.01" name="price" required value="<?= e((string)($p['price'] ?? '')) ?>"></div>
        <div><label>Stock</label><input type="number" name="stock" required value="<?= e((string)($p['stock'] ?? '0')) ?>"></div>
        <div><label>Low stock alert</label><input type="number" name="low_stock_threshold" value="<?= e((string)($p['low_stock_threshold'] ?? '3')) ?>"></div>
        <div>
            <label>Status</label>
            <select name="status">
                <?php foreach (['active','draft','archived'] as $s): ?>
                    <option value="<?= $s ?>" <?= (($p['status'] ?? 'active') === $s) ? 'selected' : '' ?>><?= $s ?></option>
                <?php endforeach; ?>
            </select>
        </div>
    </div>
    <label>Description</label>
    <textarea name="description" rows="5"><?= e($p['description'] ?? '') ?></textarea>
    <label>Image</label>
    <input type="file" name="image" accept="image/jpeg,image/png,image/webp">
    <p><button class="btn btn-maroon" type="submit">Save</button></p>
</form>
<?php
$content = ob_get_clean();
$title = 'Piece';
$active = 'products';
require __DIR__ . '/layout.php';
