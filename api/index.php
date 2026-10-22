<?php
declare(strict_types=1);

require dirname(__DIR__) . '/includes/bootstrap.php';

$action = (string)($_POST['action'] ?? $_GET['action'] ?? '');

if ($action === 'paystack_webhook') {
    $raw = (string)file_get_contents('php://input');
    $secret = (string)setting('paystack_secret', '');
    $sig = $_SERVER['HTTP_X_PAYSTACK_SIGNATURE'] ?? '';
    if (!$secret || !hash_equals(hash_hmac('sha512', $raw, $secret), $sig)) {
        json_response(['ok' => false], 401);
    }
    $event = json_decode($raw, true);
    if (($event['event'] ?? '') === 'charge.success') {
        $ref = $event['data']['reference'] ?? '';
        $stmt = db()->prepare('SELECT * FROM orders WHERE paystack_reference = ? LIMIT 1');
        $stmt->execute([$ref]);
        $order = $stmt->fetch();
        if ($order) {
            mark_order_paid((int)$order['id'], $ref, ((float)$event['data']['amount']) / 100, $raw);
        }
    }
    json_response(['ok' => true]);
}

verify_csrf();

try {
    if ($action === 'wishlist') {
        $on = wishlist_toggle((int)$_POST['product_id']);
        json_response(['ok' => true, 'on' => $on]);
    }
    if ($action === 'cart_add') {
        cart_add((int)$_POST['product_id'], max(1, (int)($_POST['qty'] ?? 1)));
        json_response(['ok' => true, 'count' => cart_count()]);
    }
    if ($action === 'fulfill') {
        require_staff();
        fulfill_order((int)$_POST['id']);
        json_response(['ok' => true]);
    }
    if ($action === 'dashboard') {
        require_staff();
        require_ability('dashboard');
        $s = dashboard_stats();
        $labels = [];
        $values = [];
        $map = [];
        foreach ($s['trend'] as $row) {
            $map[$row['d']] = (float)$row['t'];
        }
        for ($i = 13; $i >= 0; $i--) {
            $d = date('Y-m-d', strtotime("-$i days"));
            $labels[] = date('d M', strtotime($d));
            $values[] = $map[$d] ?? 0;
        }
        foreach ($s['pendingOrders'] as &$o) {
            $o['total_fmt'] = money((float)$o['total']);
        }
        json_response([
            'ok' => true,
            'stats' => [
                'sales_fmt' => money($s['sales']),
                'today_fmt' => money($s['today']),
                'pending' => $s['pending'],
                'low' => $s['low'],
                'units' => $s['units'],
                'expenses_fmt' => money($s['expenses']),
                'pendingOrders' => $s['pendingOrders'],
                'trend_labels' => $labels,
                'trend_values' => $values,
            ],
        ]);
    }
} catch (Throwable $e) {
    json_response(['ok' => false, 'error' => $e->getMessage()], 400);
}

json_response(['ok' => false, 'error' => 'Unknown action'], 400);
