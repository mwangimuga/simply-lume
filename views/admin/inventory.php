<?php
ob_start();
?>
<div class="top"><h1>Inventory</h1></div>
<form method="post" class="panel">
    <?= csrf_field() ?>
    <table>
        <thead><tr><th>Piece</th><th>Type</th><th>Stock</th><th>Adjust</th></tr></thead>
        <tbody>
        <?php foreach ($products as $p): ?>
            <tr>
                <td><?= e($p['name']) ?></td>
                <td><?= e($p['category_name']) ?></td>
                <td><?= (int)$p['stock'] ?><?php if ((int)$p['stock'] <= (int)$p['low_stock_threshold']): ?> <span class="tag pending">low</span><?php endif; ?></td>
                <td><input type="number" name="stock[<?= (int)$p['id'] ?>]" value="<?= (int)$p['stock'] ?>" style="width:90px"></td>
            </tr>
        <?php endforeach; ?>
        </tbody>
    </table>
    <p><button class="btn btn-maroon">Update stock</button></p>
</form>
<?php
$content = ob_get_clean();
$title = 'Inventory';
$active = 'inventory';
require __DIR__ . '/layout.php';
