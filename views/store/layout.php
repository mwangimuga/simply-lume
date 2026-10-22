<?php
$user = current_user();
?><!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="<?= e(csrf_token()) ?>">
    <title><?= e($title ?? 'Simply Lume') ?></title>
    <link rel="icon" href="<?= e(url('assets/img/favicon.svg')) ?>" type="image/svg+xml">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=Cormorant+Garamond:ital,wght@0,500;0,600;1,500&family=Jost:wght@300;400;500&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="<?= e(url('assets/css/store.css')) ?>">
</head>
<body>
<header class="nav">
    <a class="brand" href="<?= e(url()) ?>">Simply Lume</a>
    <nav class="nav-links">
        <a href="<?= e(url('shop')) ?>">Atelier</a>
        <a href="<?= e(url('shop?gender=women')) ?>">Women</a>
        <a href="<?= e(url('shop?gender=men')) ?>">Men</a>
        <a href="<?= e(url('wishlist')) ?>">Wishlist</a>
    </nav>
    <div class="nav-icons">
        <?php if ($user): ?>
            <a href="<?= e($user['role'] === 'client' ? url('account') : url('admin')) ?>"><?= e($user['name']) ?></a>
            <a href="<?= e(url('logout')) ?>">Leave</a>
        <?php else: ?>
            <a href="<?= e(url('login')) ?>">Enter</a>
        <?php endif; ?>
        <a href="<?= e(url('cart')) ?>">Selection <span class="badge" data-cart-count><?= (int)cart_count() ?></span></a>
    </div>
</header>
<main>
    <?= $content ?>
</main>
<footer class="footer wrap">
    <div>Simply Lume — jewellery composed in maroon, black and light.</div>
    <div>WhatsApp orders · Paystack when you prefer</div>
</footer>
<script>window.SL_BASE = <?= json_encode(rtrim(url(), '/') . '/') ?>;</script>
<script src="<?= e(url('assets/js/store.js')) ?>"></script>
</body>
</html>
