<?php
ob_start();
?>
<div class="top"><h1>Expenses</h1></div>
<div class="grid-2">
    <div class="panel">
        <table>
            <thead><tr><th>Date</th><th>Title</th><th>Category</th><th>Amount</th></tr></thead>
            <tbody>
            <?php foreach ($expenses as $x): ?>
                <tr>
                    <td><?= e($x['expense_date']) ?></td>
                    <td><?= e($x['title']) ?></td>
                    <td><?= e($x['category']) ?></td>
                    <td><?= e(money($x['amount'])) ?></td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
        <p>This month <?= e(money($monthTotal)) ?></p>
    </div>
    <form method="post" class="panel">
        <?= csrf_field() ?>
        <label>Title</label><input name="title" required>
        <label>Category</label>
        <select name="category">
            <option>stock</option><option>rent</option><option>marketing</option><option>salaries</option><option>general</option>
        </select>
        <label>Amount</label><input type="number" step="0.01" name="amount" required>
        <label>Date</label><input type="date" name="expense_date" value="<?= e(date('Y-m-d')) ?>" required>
        <label>Notes</label><textarea name="notes" rows="3"></textarea>
        <p><button class="btn btn-maroon">Add expense</button></p>
    </form>
</div>
<?php
$content = ob_get_clean();
$title = 'Expenses';
$active = 'expenses';
require __DIR__ . '/layout.php';
