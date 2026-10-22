<?php
declare(strict_types=1);

function catalog_categories(): array
{
    return db()->query('SELECT * FROM categories ORDER BY sort_order, name')->fetchAll();
}

function catalog_search(array $f): array
{
    $sql = 'SELECT p.*, c.name AS category_name, c.slug AS category_slug
            FROM products p
            JOIN categories c ON c.id = p.category_id
            WHERE p.status = ?';
    $params = ['active'];

    if (!empty($f['q'])) {
        $q = '%' . $f['q'] . '%';
        $sql .= ' AND (p.name LIKE ? OR p.description LIKE ? OR p.sku LIKE ? OR c.name LIKE ?)';
        array_push($params, $q, $q, $q, $q);
    }
    if (!empty($f['category'])) {
        $sql .= ' AND c.slug = ?';
        $params[] = $f['category'];
    }
    if (!empty($f['gender']) && in_array($f['gender'], ['women', 'men', 'unisex'], true)) {
        $sql .= ' AND (p.gender = ? OR p.gender = ?)';
        $params[] = $f['gender'];
        $params[] = 'unisex';
    }
    if (isset($f['min']) && $f['min'] !== '') {
        $sql .= ' AND p.price >= ?';
        $params[] = (float)$f['min'];
    }
    if (isset($f['max']) && $f['max'] !== '') {
        $sql .= ' AND p.price <= ?';
        $params[] = (float)$f['max'];
    }
    if (!empty($f['in_stock'])) {
        $sql .= ' AND p.stock > 0';
    }

    $sort = $f['sort'] ?? 'newest';
    $sql .= match ($sort) {
        'price_asc' => ' ORDER BY p.price ASC',
        'price_desc' => ' ORDER BY p.price DESC',
        'name' => ' ORDER BY p.name ASC',
        default => ' ORDER BY p.created_at DESC',
    };

    $stmt = db()->prepare($sql);
    $stmt->execute($params);
    return $stmt->fetchAll();
}

function catalog_product(string $slug): ?array
{
    $stmt = db()->prepare(
        'SELECT p.*, c.name AS category_name, c.slug AS category_slug
         FROM products p JOIN categories c ON c.id = p.category_id
         WHERE p.slug = ? AND p.status = ? LIMIT 1'
    );
    $stmt->execute([$slug, 'active']);
    $row = $stmt->fetch();
    return $row ?: null;
}

function catalog_related(array $product, int $limit = 4): array
{
    $stmt = db()->prepare(
        'SELECT p.*, c.name AS category_name FROM products p
         JOIN categories c ON c.id = p.category_id
         WHERE p.status = ? AND p.id != ? AND (p.category_id = ? OR p.gender = ?)
         ORDER BY RAND() LIMIT ' . (int)$limit
    );
    $stmt->execute(['active', $product['id'], $product['category_id'], $product['gender']]);
    return $stmt->fetchAll();
}

function product_image_url(?string $image): string
{
    if ($image && is_file(dirname(__DIR__) . '/uploads/products/' . $image)) {
        return url('uploads/products/' . $image);
    }
    return url('assets/img/placeholder.svg');
}

function save_product_image(array $file): ?string
{
    if (($file['error'] ?? UPLOAD_ERR_NO_FILE) === UPLOAD_ERR_NO_FILE) {
        return null;
    }
    if (($file['error'] ?? 0) !== UPLOAD_ERR_OK) {
        throw new RuntimeException('Image upload failed.');
    }
    if (($file['size'] ?? 0) > 3 * 1024 * 1024) {
        throw new RuntimeException('Image must be under 3MB.');
    }
    $finfo = new finfo(FILEINFO_MIME_TYPE);
    $mime = $finfo->file($file['tmp_name']);
    $allowed = [
        'image/jpeg' => 'jpg',
        'image/png' => 'png',
        'image/webp' => 'webp',
    ];
    if (!isset($allowed[$mime])) {
        throw new RuntimeException('Only JPG, PNG or WEBP images are allowed.');
    }
    $name = bin2hex(random_bytes(12)) . '.' . $allowed[$mime];
    $dest = dirname(__DIR__) . '/uploads/products/' . $name;
    if (!move_uploaded_file($file['tmp_name'], $dest)) {
        throw new RuntimeException('Could not store the image.');
    }
    return $name;
}

function wishlist_ids(): array
{
    $user = current_user();
    if ($user && $user['role'] === 'client') {
        $stmt = db()->prepare('SELECT product_id FROM wishlist WHERE user_id = ?');
        $stmt->execute([$user['id']]);
        return array_map('intval', $stmt->fetchAll(PDO::FETCH_COLUMN));
    }
    return array_map('intval', $_SESSION['wishlist'] ?? []);
}

function wishlist_toggle(int $productId): bool
{
    $user = current_user();
    if ($user) {
        $stmt = db()->prepare('SELECT id FROM wishlist WHERE user_id = ? AND product_id = ?');
        $stmt->execute([$user['id'], $productId]);
        if ($stmt->fetch()) {
            db()->prepare('DELETE FROM wishlist WHERE user_id = ? AND product_id = ?')->execute([$user['id'], $productId]);
            return false;
        }
        db()->prepare('INSERT INTO wishlist (user_id, product_id) VALUES (?, ?)')->execute([$user['id'], $productId]);
        return true;
    }
    $ids = $_SESSION['wishlist'] ?? [];
    if (in_array($productId, $ids, true)) {
        $_SESSION['wishlist'] = array_values(array_filter($ids, fn($id) => (int)$id !== $productId));
        return false;
    }
    $ids[] = $productId;
    $_SESSION['wishlist'] = $ids;
    return true;
}

function wishlist_products(): array
{
    $ids = wishlist_ids();
    if (!$ids) {
        return [];
    }
    $in = implode(',', array_fill(0, count($ids), '?'));
    $stmt = db()->prepare(
        "SELECT p.*, c.name AS category_name FROM products p
         JOIN categories c ON c.id = p.category_id
         WHERE p.id IN ($in) AND p.status = 'active'"
    );
    $stmt->execute($ids);
    return $stmt->fetchAll();
}

function cart_add(int $productId, int $qty = 1): void
{
    $stmt = db()->prepare('SELECT * FROM products WHERE id = ? AND status = ?');
    $stmt->execute([$productId, 'active']);
    $p = $stmt->fetch();
    if (!$p || (int)$p['stock'] < 1) {
        throw new RuntimeException('This piece is unavailable.');
    }
    $cart = cart();
    $key = (string)$productId;
    $newQty = ($cart[$key]['qty'] ?? 0) + $qty;
    if ($newQty > (int)$p['stock']) {
        $newQty = (int)$p['stock'];
    }
    $cart[$key] = [
        'id' => (int)$p['id'],
        'name' => $p['name'],
        'price' => (float)$p['price'],
        'image' => $p['image'],
        'qty' => $newQty,
        'sku' => $p['sku'],
    ];
    cart_set($cart);
}

function dashboard_stats(): array
{
    $pdo = db();
    $sales = (float)$pdo->query(
        "SELECT COALESCE(SUM(total),0) FROM orders WHERE payment_status = 'paid' OR order_status IN ('paid','fulfilled')"
    )->fetchColumn();
    $today = (float)$pdo->query(
        "SELECT COALESCE(SUM(total),0) FROM orders
         WHERE DATE(created_at) = CURDATE() AND (payment_status = 'paid' OR order_status IN ('paid','fulfilled'))"
    )->fetchColumn();
    $pending = (int)$pdo->query("SELECT COUNT(*) FROM orders WHERE order_status = 'pending'")->fetchColumn();
    $low = (int)$pdo->query('SELECT COUNT(*) FROM products WHERE stock <= low_stock_threshold AND status = "active"')->fetchColumn();
    $stockValue = (float)$pdo->query('SELECT COALESCE(SUM(stock * price),0) FROM products WHERE status = "active"')->fetchColumn();
    $expenses = (float)$pdo->query(
        'SELECT COALESCE(SUM(amount),0) FROM expenses WHERE MONTH(expense_date) = MONTH(CURDATE()) AND YEAR(expense_date) = YEAR(CURDATE())'
    )->fetchColumn();
    $units = (int)$pdo->query('SELECT COALESCE(SUM(stock),0) FROM products WHERE status = "active"')->fetchColumn();

    $trend = $pdo->query(
        "SELECT DATE(created_at) d, COALESCE(SUM(total),0) t
         FROM orders
         WHERE created_at >= (CURDATE() - INTERVAL 13 DAY)
           AND (payment_status = 'paid' OR order_status IN ('paid','fulfilled'))
         GROUP BY DATE(created_at) ORDER BY d"
    )->fetchAll();

    $pendingOrders = $pdo->query(
        "SELECT * FROM orders WHERE order_status = 'pending' ORDER BY created_at DESC LIMIT 12"
    )->fetchAll();

    $inventory = $pdo->query(
        'SELECT p.name, p.stock, p.low_stock_threshold, c.name AS category_name
         FROM products p JOIN categories c ON c.id = p.category_id
         WHERE p.status = "active" ORDER BY p.stock ASC LIMIT 10'
    )->fetchAll();

    return compact('sales', 'today', 'pending', 'low', 'stockValue', 'expenses', 'units', 'trend', 'pendingOrders', 'inventory');
}

function create_order_from_cart(array $customer, string $method): array
{
    $cart = cart();
    if (!$cart) {
        throw new RuntimeException('Your selection is empty.');
    }
    $pdo = db();
    $pdo->beginTransaction();
    try {
        $items = [];
        $subtotal = 0.0;
        foreach ($cart as $row) {
            $stmt = $pdo->prepare('SELECT * FROM products WHERE id = ? FOR UPDATE');
            $stmt->execute([$row['id']]);
            $p = $stmt->fetch();
            if (!$p || (int)$p['stock'] < (int)$row['qty']) {
                throw new RuntimeException($row['name'] . ' does not have enough stock.');
            }
            $line = (float)$p['price'] * (int)$row['qty'];
            $subtotal += $line;
            $items[] = [
                'product' => $p,
                'qty' => (int)$row['qty'],
                'line' => $line,
                'name' => $p['name'],
            ];
        }

        $message = format_whatsapp_message(array_map(fn($i) => ['name' => $i['name'], 'qty' => $i['qty']], $items));
        $number = next_number('order', 'SL-');
        $payStatus = $method === 'paystack' ? 'pending' : 'pending';
        $orderStatus = 'pending';

        $ins = $pdo->prepare(
            'INSERT INTO orders (order_number, user_id, customer_name, customer_email, customer_phone, subtotal, total, payment_method, payment_status, order_status, whatsapp_message)
             VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)'
        );
        $ins->execute([
            $number,
            current_user()['id'] ?? null,
            $customer['name'],
            $customer['email'] ?? null,
            $customer['phone'],
            $subtotal,
            $subtotal,
            $method,
            $payStatus,
            $orderStatus,
            $message,
        ]);
        $orderId = (int)$pdo->lastInsertId();

        $itemStmt = $pdo->prepare(
            'INSERT INTO order_items (order_id, product_id, product_name, sku, qty, unit_price, line_total) VALUES (?, ?, ?, ?, ?, ?, ?)'
        );
        foreach ($items as $item) {
            $p = $item['product'];
            $itemStmt->execute([$orderId, $p['id'], $p['name'], $p['sku'], $item['qty'], $p['price'], $item['line']]);
            if ($method !== 'paystack') {
                $pdo->prepare('UPDATE products SET stock = stock - ? WHERE id = ?')->execute([$item['qty'], $p['id']]);
            }
        }

        $pdo->commit();
        $order = ['id' => $orderId, 'order_number' => $number, 'total' => $subtotal, 'message' => $message];
        if ($method !== 'paystack') {
            cart_set([]);
        }
        return $order;
    } catch (Throwable $e) {
        $pdo->rollBack();
        throw $e;
    }
}

function paystack_initialize(array $order, string $email): array
{
    $secret = (string)setting('paystack_secret', '');
    if ($secret === '') {
        throw new RuntimeException('Paystack is not configured. Add keys in Settings.');
    }
    $payload = json_encode([
        'email' => $email,
        'amount' => (int)round(((float)$order['total']) * 100),
        'currency' => 'KES',
        'reference' => $order['order_number'] . '-' . bin2hex(random_bytes(4)),
        'callback_url' => url('checkout/verify'),
        'metadata' => ['order_id' => $order['id']],
    ]);
    $ch = curl_init('https://api.paystack.co/transaction/initialize');
    curl_setopt_array($ch, [
        CURLOPT_POST => true,
        CURLOPT_POSTFIELDS => $payload,
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_HTTPHEADER => [
            'Authorization: Bearer ' . $secret,
            'Content-Type: application/json',
        ],
        CURLOPT_TIMEOUT => 20,
    ]);
    $raw = curl_exec($ch);
    if ($raw === false) {
        throw new RuntimeException('Could not reach Paystack.');
    }
    $res = json_decode($raw, true);
    if (empty($res['status'])) {
        throw new RuntimeException($res['message'] ?? 'Paystack initialization failed.');
    }
    db()->prepare('UPDATE orders SET paystack_reference = ? WHERE id = ?')->execute([
        $res['data']['reference'], $order['id']
    ]);
    return $res['data'];
}

function paystack_verify(string $reference): array
{
    $secret = (string)setting('paystack_secret', '');
    $ch = curl_init('https://api.paystack.co/transaction/verify/' . rawurlencode($reference));
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_HTTPHEADER => ['Authorization: Bearer ' . $secret],
        CURLOPT_TIMEOUT => 20,
    ]);
    $raw = curl_exec($ch);
    $res = json_decode((string)$raw, true);
    if (empty($res['status']) || ($res['data']['status'] ?? '') !== 'success') {
        throw new RuntimeException('Payment could not be verified.');
    }
    $ref = $res['data']['reference'];
    $stmt = db()->prepare('SELECT * FROM orders WHERE paystack_reference = ? LIMIT 1');
    $stmt->execute([$ref]);
    $order = $stmt->fetch();
    if (!$order) {
        throw new RuntimeException('Order not found for this payment.');
    }
    mark_order_paid((int)$order['id'], $ref, (float)$res['data']['amount'] / 100, $raw);
    cart_set([]);
    return $order;
}

function mark_order_paid(int $orderId, string $reference, float $amount, string $raw = ''): void
{
    $pdo = db();
    $pdo->beginTransaction();
    try {
        $stmt = $pdo->prepare('SELECT * FROM orders WHERE id = ? FOR UPDATE');
        $stmt->execute([$orderId]);
        $order = $stmt->fetch();
        if (!$order) {
            throw new RuntimeException('Order missing.');
        }
        if ($order['payment_status'] !== 'paid') {
            $pdo->prepare("UPDATE orders SET payment_status = 'paid', order_status = 'paid' WHERE id = ?")->execute([$orderId]);
            if ($order['payment_method'] === 'paystack') {
                $items = $pdo->prepare('SELECT * FROM order_items WHERE order_id = ?');
                $items->execute([$orderId]);
                foreach ($items->fetchAll() as $item) {
                    if ($item['product_id']) {
                        $pdo->prepare('UPDATE products SET stock = GREATEST(stock - ?, 0) WHERE id = ?')
                            ->execute([$item['qty'], $item['product_id']]);
                    }
                }
            }
            $pdo->prepare(
                'INSERT INTO payments (order_id, provider, reference, amount, status, raw_payload) VALUES (?, ?, ?, ?, ?, ?)'
            )->execute([$orderId, 'paystack', $reference, $amount, 'success', $raw]);
            maybe_auto_documents($orderId, $amount, 'paystack');
        }
        $pdo->commit();
    } catch (Throwable $e) {
        $pdo->rollBack();
        throw $e;
    }
}

function maybe_auto_documents(int $orderId, float $amount, string $method): void
{
    $order = db()->prepare('SELECT * FROM orders WHERE id = ?');
    $order->execute([$orderId]);
    $o = $order->fetch();
    if (!$o) {
        return;
    }
    $invNo = next_number('invoice', 'SL-INV-');
    db()->prepare(
        'INSERT INTO invoices (invoice_number, order_id, client_id, client_name, client_email, amount, tax, total, status, created_by)
         VALUES (?, ?, ?, ?, ?, ?, 0, ?, ?, ?)'
    )->execute([$invNo, $orderId, $o['user_id'], $o['customer_name'], $o['customer_email'], $amount, $amount, 'paid', current_user()['id'] ?? null]);
    $invId = (int)db()->lastInsertId();
    $rcp = next_number('receipt', 'SL-RCP-');
    db()->prepare(
        'INSERT INTO receipts (receipt_number, invoice_id, order_id, amount, payment_method, created_by) VALUES (?, ?, ?, ?, ?, ?)'
    )->execute([$rcp, $invId, $orderId, $amount, $method, current_user()['id'] ?? null]);
}

function fulfill_order(int $id): void
{
    require_ability('orders.fulfill');
    $stmt = db()->prepare("UPDATE orders SET order_status = 'fulfilled', fulfilled_at = NOW(), fulfilled_by = ? WHERE id = ? AND order_status != 'cancelled'");
    $stmt->execute([current_user()['id'], $id]);
    audit('order.fulfill', 'Order #' . $id);
}
