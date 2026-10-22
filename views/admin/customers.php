<?php
ob_start();
?>
<div class="top"><h1>Clients</h1></div>
<div class="panel">
    <table>
        <thead><tr><th>Name</th><th>Username</th><th>Email</th><th>Phone</th><th>Status</th></tr></thead>
        <tbody>
        <?php foreach ($clients as $c): ?>
            <tr>
                <td><?= e($c['name']) ?></td>
                <td><?= e($c['username']) ?></td>
                <td><?= e($c['email']) ?></td>
                <td><?= e($c['phone']) ?></td>
                <td><?= e($c['status']) ?></td>
            </tr>
        <?php endforeach; ?>
        </tbody>
    </table>
</div>
<?php
$content = ob_get_clean();
$title = 'Clients';
$active = 'customers';
require __DIR__ . '/layout.php';
