<?php
ob_start();
?>
<div class="top">
    <h1>Jewellery</h1>
    <a class="btn btn-maroon" href="<?= e(url('admin/product')) ?>">Add piece</a>
</div>
<?php if (!empty($_GET['ok'])): ?><div class="flash">Saved.</div><?php endif; ?>
<div class="panel">
    <table>
        <thead><tr><th>SKU</th><th>Name</th><th>Type</th><th>Gender</th><th>Price</th><th>Stock</th><th></th></tr></thead>
        <tbody>
        <?php foreach ($products as $p): ?>
            <tr>
                <td><?= e($p['sku']) ?></td>
                <td><?= e($p['name']) ?></td>
                <td><?= e($p['category_name']) ?></td>
                <td><?= e($p['gender']) ?></td>
                <td><?= e(money($p['price'])) ?></td>
                <td><?= (int)$p['stock'] ?></td>
                <td><a href="<?= e(url('admin/product?id=' . $p['id'])) ?>">Edit</a></td>
            </tr>
        <?php endforeach; ?>
        </tbody>
    </table>
</div>
<?php
$content = ob_get_clean();
$title = 'Jewellery';
$active = 'products';
require __DIR__ . '/layout.php';
