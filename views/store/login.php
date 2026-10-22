<?php
ob_start();
?>
<div class="auth-card">
    <p class="kicker">Members</p>
    <h1>Enter</h1>
    <?php if (!empty($error)): ?><div class="flash"><?= e($error) ?></div><?php endif; ?>
    <form method="post">
        <?= csrf_field() ?>
        <div class="field"><label>Username or email</label><input name="username" required autocomplete="username"></div>
        <div class="field"><label>Password</label><input type="password" name="password" required autocomplete="current-password"></div>
        <button class="btn btn-solid" type="submit">Sign in</button>
    </form>
    <p class="notice">New client? <a href="<?= e(url('register')) ?>">Create an account</a></p>
</div>
<?php
$content = ob_get_clean();
$title = 'Enter · Simply Lume';
require __DIR__ . '/layout.php';
