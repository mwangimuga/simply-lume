<?php
ob_start();
?>
<div class="auth-card">
    <p class="kicker">Clients</p>
    <h1>Join</h1>
    <?php if (!empty($error)): ?><div class="flash"><?= e($error) ?></div><?php endif; ?>
    <form method="post">
        <?= csrf_field() ?>
        <div class="field"><label>Name</label><input name="name" required></div>
        <div class="field"><label>Username</label><input name="username" required></div>
        <div class="field"><label>Email</label><input type="email" name="email" required></div>
        <div class="field"><label>Phone</label><input name="phone"></div>
        <div class="field"><label>Password</label><input type="password" name="password" required minlength="10"></div>
        <button class="btn btn-solid" type="submit">Create account</button>
    </form>
</div>
<?php
$content = ob_get_clean();
$title = 'Join · Simply Lume';
require __DIR__ . '/layout.php';
