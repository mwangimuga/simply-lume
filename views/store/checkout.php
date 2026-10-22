<?php
ob_start();
$u = current_user();
?>
<section class="section wrap">
    <p class="kicker">Complete</p>
    <h2 class="display">Checkout</h2>
    <?php if (!empty($error)): ?><div class="flash"><?= e($error) ?></div><?php endif; ?>
    <p>Total <?= e(money(cart_total())) ?></p>
    <form method="post" class="auth-card" style="margin:20px 0">
        <?= csrf_field() ?>
        <div class="field"><label>Name</label><input name="name" required value="<?= e($u['name'] ?? '') ?>"></div>
        <div class="field"><label>Phone</label><input name="phone" required value="<?= e($u['phone'] ?? '') ?>"></div>
        <div class="field"><label>Email</label><input type="email" name="email" value="<?= e($u['email'] ?? '') ?>" placeholder="Required for Paystack"></div>
        <p class="notice">WhatsApp message will read: <em><?= e(format_whatsapp_message(array_values(cart()))) ?></em></p>
        <div class="actions" style="margin-top:20px">
            <button class="btn btn-solid" name="method" value="whatsapp">Order on WhatsApp</button>
            <button class="btn" name="method" value="paystack">Pay with Paystack</button>
        </div>
    </form>
</section>
<?php
$content = ob_get_clean();
$title = 'Checkout · Simply Lume';
require __DIR__ . '/layout.php';
