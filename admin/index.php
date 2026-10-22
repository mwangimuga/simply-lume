<?php
declare(strict_types=1);

require dirname(__DIR__) . '/includes/bootstrap.php';

require_staff();

$route = trim((string)($_GET['r'] ?? ''), '/');

function admin_products(): array
{
    return db()->query(
        'SELECT p.*, c.name AS category_name FROM products p JOIN categories c ON c.id = p.category_id ORDER BY p.created_at DESC'
    )->fetchAll();
}

if ($route === '' || $route === 'dashboard') {
    require_ability('dashboard');
    $stats = dashboard_stats();
    require dirname(__DIR__) . '/views/admin/dashboard.php';
    exit;
}

if ($route === 'products') {
    require_any(['products', 'products.view']);
    $products = admin_products();
    require dirname(__DIR__) . '/views/admin/products.php';
    exit;
}

if ($route === 'product') {
    require_ability('products');
    $id = (int)($_GET['id'] ?? 0);
    $product = null;
    if ($id) {
        $stmt = db()->prepare('SELECT * FROM products WHERE id = ?');
        $stmt->execute([$id]);
        $product = $stmt->fetch() ?: null;
    }
    $categories = catalog_categories();
    $error = '';
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        verify_csrf();
        try {
            $name = trim((string)$_POST['name']);
            $sku = trim((string)$_POST['sku']);
            $slug = slugify($name);
            $image = save_product_image($_FILES['image'] ?? ['error' => UPLOAD_ERR_NO_FILE]);
            if ($id) {
                $sql = 'UPDATE products SET sku=?, name=?, slug=?, description=?, category_id=?, gender=?, price=?, stock=?, low_stock_threshold=?, status=?';
                $params = [
                    $sku, $name, $slug, trim((string)$_POST['description']),
                    (int)$_POST['category_id'], $_POST['gender'], $_POST['price'],
                    (int)$_POST['stock'], (int)$_POST['low_stock_threshold'], $_POST['status'],
                ];
                if ($image) {
                    $sql .= ', image=?';
                    $params[] = $image;
                }
                $sql .= ' WHERE id=?';
                $params[] = $id;
                db()->prepare($sql)->execute($params);
            } else {
                db()->prepare(
                    'INSERT INTO products (sku, name, slug, description, category_id, gender, price, stock, low_stock_threshold, image, status, created_by)
                     VALUES (?,?,?,?,?,?,?,?,?,?,?,?)'
                )->execute([
                    $sku, $name, $slug, trim((string)$_POST['description']),
                    (int)$_POST['category_id'], $_POST['gender'], $_POST['price'],
                    (int)$_POST['stock'], (int)$_POST['low_stock_threshold'], $image, $_POST['status'],
                    current_user()['id'],
                ]);
            }
            audit('product.save', $sku);
            redirect('admin/products?ok=1');
        } catch (Throwable $e) {
            $error = $e->getMessage();
        }
    }
    require dirname(__DIR__) . '/views/admin/product-form.php';
    exit;
}

if ($route === 'inventory') {
    require_ability('inventory');
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        verify_csrf();
        foreach (($_POST['stock'] ?? []) as $pid => $qty) {
            db()->prepare('UPDATE products SET stock = ? WHERE id = ?')->execute([(int)$qty, (int)$pid]);
        }
        audit('inventory.update', 'Bulk stock');
        redirect('admin/inventory');
    }
    $products = admin_products();
    require dirname(__DIR__) . '/views/admin/inventory.php';
    exit;
}

if ($route === 'orders') {
    require_any(['orders', 'orders.view']);
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        verify_csrf();
        if (!empty($_POST['fulfill_id'])) {
            fulfill_order((int)$_POST['fulfill_id']);
        }
        if (!empty($_POST['cancel_id'])) {
            require_any(['orders']);
            $cid = (int)$_POST['cancel_id'];
            $pdo = db();
            $pdo->beginTransaction();
            $st = $pdo->prepare('SELECT * FROM orders WHERE id = ? FOR UPDATE');
            $st->execute([$cid]);
            $row = $st->fetch();
            if ($row && $row['order_status'] !== 'fulfilled' && $row['order_status'] !== 'cancelled') {
                if ($row['payment_method'] !== 'paystack' || $row['payment_status'] === 'paid') {
                    $its = $pdo->prepare('SELECT * FROM order_items WHERE order_id = ?');
                    $its->execute([$cid]);
                    foreach ($its->fetchAll() as $item) {
                        if ($item['product_id']) {
                            $pdo->prepare('UPDATE products SET stock = stock + ? WHERE id = ?')->execute([$item['qty'], $item['product_id']]);
                        }
                    }
                }
                $pdo->prepare("UPDATE orders SET order_status = 'cancelled' WHERE id = ?")->execute([$cid]);
            }
            $pdo->commit();
            audit('order.cancel', (string)$cid);
        }
            require_any(['finance', 'payments']);
            $oid = (int)$_POST['mark_paid'];
            $o = db()->prepare('SELECT * FROM orders WHERE id = ?');
            $o->execute([$oid]);
            $row = $o->fetch();
            if ($row) {
                mark_order_paid($oid, 'manual-' . $row['order_number'], (float)$row['total'], 'manual');
                db()->prepare("UPDATE orders SET payment_method = IF(payment_method='whatsapp','manual', payment_method) WHERE id = ?")->execute([$oid]);
            }
        }
        redirect('admin/orders');
    }
    $orders = db()->query('SELECT * FROM orders ORDER BY created_at DESC LIMIT 200')->fetchAll();
    require dirname(__DIR__) . '/views/admin/orders.php';
    exit;
}

if ($route === 'order') {
    require_any(['orders', 'orders.view']);
    $stmt = db()->prepare('SELECT * FROM orders WHERE id = ?');
    $stmt->execute([(int)($_GET['id'] ?? 0)]);
    $order = $stmt->fetch();
    if (!$order) {
        exit('Order not found.');
    }
    $items = db()->prepare('SELECT * FROM order_items WHERE order_id = ?');
    $items->execute([$order['id']]);
    $items = $items->fetchAll();
    require dirname(__DIR__) . '/views/admin/order.php';
    exit;
}

if ($route === 'customers') {
    require_ability('customers');
    $clients = db()->query("SELECT * FROM users WHERE role = 'client' ORDER BY created_at DESC")->fetchAll();
    require dirname(__DIR__) . '/views/admin/customers.php';
    exit;
}

if ($route === 'expenses') {
    require_ability('expenses');
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        verify_csrf();
        db()->prepare(
            'INSERT INTO expenses (title, category, amount, expense_date, notes, created_by) VALUES (?,?,?,?,?,?)'
        )->execute([
            trim((string)$_POST['title']),
            (string)$_POST['category'],
            $_POST['amount'],
            $_POST['expense_date'],
            trim((string)($_POST['notes'] ?? '')),
            current_user()['id'],
        ]);
        audit('expense.create', (string)$_POST['title']);
        redirect('admin/expenses');
    }
    $expenses = db()->query('SELECT * FROM expenses ORDER BY expense_date DESC, id DESC')->fetchAll();
    $monthTotal = (float)db()->query(
        'SELECT COALESCE(SUM(amount),0) FROM expenses WHERE MONTH(expense_date)=MONTH(CURDATE()) AND YEAR(expense_date)=YEAR(CURDATE())'
    )->fetchColumn();
    require dirname(__DIR__) . '/views/admin/expenses.php';
    exit;
}

if ($route === 'invoices') {
    require_ability('invoices');
    $invoices = db()->query('SELECT * FROM invoices ORDER BY issued_at DESC')->fetchAll();
    require dirname(__DIR__) . '/views/admin/invoices.php';
    exit;
}

if ($route === 'invoice') {
    require_ability('invoices');
    $id = (int)($_GET['id'] ?? 0);
    $orderId = (int)($_GET['order_id'] ?? 0);
    if ($orderId && !$id) {
        $existing = db()->prepare('SELECT * FROM invoices WHERE order_id = ?');
        $existing->execute([$orderId]);
        $document = $existing->fetch();
        if (!$document) {
            $order = db()->prepare('SELECT * FROM orders WHERE id = ?');
            $order->execute([$orderId]);
            $order = $order->fetch();
            if ($order) {
                $invNo = next_number('invoice', 'SL-INV-');
                db()->prepare(
                    'INSERT INTO invoices (invoice_number, order_id, client_id, client_name, client_email, amount, tax, total, status, created_by)
                     VALUES (?,?,?,?,?,?,0,?,?,?)'
                )->execute([
                    $invNo, $orderId, $order['user_id'], $order['customer_name'], $order['customer_email'],
                    $order['total'], $order['total'], $order['payment_status'] === 'paid' ? 'paid' : 'issued',
                    current_user()['id'],
                ]);
                $id = (int)db()->lastInsertId();
            }
        } else {
            $id = (int)$document['id'];
        }
    }
    $stmt = db()->prepare('SELECT * FROM invoices WHERE id = ?');
    $stmt->execute([$id]);
    $document = $stmt->fetch();
    if (!$document) {
        exit('Invoice not found.');
    }
    $items = [];
    $order = null;
    if ($document['order_id']) {
        $os = db()->prepare('SELECT * FROM orders WHERE id = ?');
        $os->execute([$document['order_id']]);
        $order = $os->fetch();
        $is = db()->prepare('SELECT * FROM order_items WHERE order_id = ?');
        $is->execute([$document['order_id']]);
        $items = $is->fetchAll();
    }
    $docTitle = 'Invoice';
    $active = 'invoices';
    require dirname(__DIR__) . '/views/admin/document.php';
    exit;
}

if ($route === 'receipts') {
    require_ability('receipts');
    $receipts = db()->query('SELECT * FROM receipts ORDER BY issued_at DESC')->fetchAll();
    require dirname(__DIR__) . '/views/admin/receipts.php';
    exit;
}

if ($route === 'receipt') {
    require_ability('receipts');
    $stmt = db()->prepare('SELECT * FROM receipts WHERE id = ?');
    $stmt->execute([(int)($_GET['id'] ?? 0)]);
    $document = $stmt->fetch();
    if (!$document) {
        exit('Receipt not found.');
    }
    $items = [];
    $order = null;
    if ($document['order_id']) {
        $is = db()->prepare('SELECT * FROM order_items WHERE order_id = ?');
        $is->execute([$document['order_id']]);
        $items = $is->fetchAll();
    }
    $docTitle = 'Receipt';
    $active = 'receipts';
    require dirname(__DIR__) . '/views/admin/document.php';
    exit;
}

if ($route === 'payments') {
    require_ability('payments');
    $payments = db()->query('SELECT * FROM payments ORDER BY created_at DESC')->fetchAll();
    require dirname(__DIR__) . '/views/admin/payments.php';
    exit;
}

if ($route === 'users') {
    require_any(['users']);
    // owner/it only because users not in finance/sales maps
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        verify_csrf();
        if (!empty($_POST['toggle_id'])) {
            db()->prepare("UPDATE users SET status = IF(status='active','disabled','active') WHERE id = ? AND role != 'owner'")
                ->execute([(int)$_POST['toggle_id']]);
        }
        if (!empty($_POST['create_user'])) {
            $role = (string)$_POST['role'];
            if (!in_array($role, ['client', 'sales', 'finance', 'it'], true)) {
                $role = 'client';
            }
            db()->prepare(
                'INSERT INTO users (name, username, email, phone, password_hash, role) VALUES (?,?,?,?,?,?)'
            )->execute([
                trim((string)$_POST['name']),
                strtolower(trim((string)$_POST['username'])),
                strtolower(trim((string)$_POST['email'])),
                trim((string)($_POST['phone'] ?? '')),
                password_hash((string)$_POST['password'], PASSWORD_DEFAULT),
                $role,
            ]);
            audit('user.create', (string)$_POST['username']);
        }
        redirect('admin/users');
    }
    $users = db()->query('SELECT id, name, username, email, phone, role, status, created_at FROM users ORDER BY role, name')->fetchAll();
    require dirname(__DIR__) . '/views/admin/users.php';
    exit;
}

if ($route === 'settings') {
    require_ability('settings');
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        verify_csrf();
        foreach (['whatsapp_number', 'business_address', 'business_email', 'paystack_public', 'paystack_secret'] as $k) {
            $val = trim((string)($_POST[$k] ?? ''));
            if ($k === 'whatsapp_number') {
                $val = preg_replace('/\D+/', '', $val) ?? '';
            }
            setting_set($k, $val);
        }
        audit('settings.update', 'Store settings');
        redirect('admin/settings');
    }
    require dirname(__DIR__) . '/views/admin/settings.php';
    exit;
}

http_response_code(404);
exit('Admin page not found.');
