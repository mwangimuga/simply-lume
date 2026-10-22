<?php
ob_start();
?>
<div class="panel" style="max-width:720px;margin:0 auto;background:#fff;color:#111">
    <p class="kicker" style="color:#6b1428">Simply Lume</p>
    <h1 style="font-family:var(--serif);color:#111"><?= e($docTitle) ?></h1>
    <p><?= e($document['invoice_number'] ?? $document['receipt_number']) ?></p>
    <p><?= e(setting('business_address', '')) ?><br><?= e(setting('business_email', '')) ?></p>
    <p>Bill to: <?= e($document['client_name'] ?? $order['customer_name'] ?? '') ?></p>
    <table>
        <thead><tr><th>Description</th><th>Qty</th><th>Amount</th></tr></thead>
        <tbody>
        <?php foreach ($items as $i): ?>
            <tr><td><?= e($i['product_name']) ?></td><td><?= (int)$i['qty'] ?></td><td><?= e(money($i['line_total'])) ?></td></tr>
        <?php endforeach; ?>
        </tbody>
    </table>
    <p><strong>Total <?= e(money($document['total'] ?? $document['amount'])) ?></strong></p>
    <p class="no-print"><button class="btn btn-maroon" onclick="window.print()">Print / Save PDF</button></p>
</div>
<?php
$content = ob_get_clean();
$title = $docTitle;
$active = $active ?? 'invoices';
require __DIR__ . '/layout.php';
