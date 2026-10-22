<?php
ob_start();
?>
<div class="top"><h1>Settings</h1></div>
<form method="post" class="panel" style="max-width:640px">
    <?= csrf_field() ?>
    <label>WhatsApp number (country code, no +)</label>
    <input name="whatsapp_number" value="<?= e(setting('whatsapp_number', '')) ?>">
    <label>Business address</label>
    <input name="business_address" value="<?= e(setting('business_address', '')) ?>">
    <label>Business email</label>
    <input name="business_email" value="<?= e(setting('business_email', '')) ?>">
    <label>Paystack public key</label>
    <input name="paystack_public" value="<?= e(setting('paystack_public', '')) ?>">
    <label>Paystack secret key</label>
    <input name="paystack_secret" type="password" value="<?= e(setting('paystack_secret', '')) ?>">
    <p class="notice">Keys stay on this server. Use test keys until you go live.</p>
    <p><button class="btn btn-maroon">Save</button></p>
</form>
<?php
$content = ob_get_clean();
$title = 'Settings';
$active = 'settings';
require __DIR__ . '/layout.php';
