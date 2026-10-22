<?php
ob_start();
?>
<div class="top"><h1>Users</h1></div>
<div class="grid-2">
    <div class="panel">
        <table>
            <thead><tr><th>Name</th><th>Role</th><th>Status</th><th></th></tr></thead>
            <tbody>
            <?php foreach ($users as $u): ?>
                <tr>
                    <td><?= e($u['name']) ?><br><small><?= e($u['username']) ?></small></td>
                    <td><?= e($u['role']) ?></td>
                    <td><?= e($u['status']) ?></td>
                    <td>
                        <?php if ($u['role'] !== 'owner'): ?>
                        <form method="post" style="display:inline">
                            <?= csrf_field() ?>
                            <input type="hidden" name="toggle_id" value="<?= (int)$u['id'] ?>">
                            <button class="btn"><?= $u['status'] === 'active' ? 'Disable' : 'Enable' ?></button>
                        </form>
                        <?php endif; ?>
                    </td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    </div>
    <form method="post" class="panel">
        <?= csrf_field() ?>
        <input type="hidden" name="create_user" value="1">
        <label>Name</label><input name="name" required>
        <label>Username</label><input name="username" required>
        <label>Email</label><input type="email" name="email" required>
        <label>Phone</label><input name="phone">
        <label>Role</label>
        <select name="role">
            <option value="client">Client</option>
            <option value="sales">Sales · orders, jewellery, clients</option>
            <option value="finance">Finance · money, invoices, expenses</option>
            <option value="it">IT · full access</option>
        </select>
        <label>Password</label><input type="password" name="password" required minlength="10">
        <p><button class="btn btn-maroon">Create user</button></p>
    </form>
</div>
<?php
$content = ob_get_clean();
$title = 'Users';
$active = 'users';
require __DIR__ . '/layout.php';
