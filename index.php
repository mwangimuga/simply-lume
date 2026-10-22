<?php
declare(strict_types=1);

require __DIR__ . '/includes/bootstrap.php';

$route = trim((string)($_GET['r'] ?? ''), '/');
if ($route === 'index.php') {
    $route = '';
}

if ($route === '' || $route === 'home') {
    $categories = catalog_categories();
    $featured = catalog_search(['sort' => 'newest']);
    $featured = array_slice($featured, 0, 8);
    require __DIR__ . '/views/store/home.php';
    exit;
}

if ($route === 'shop') {
    $filters = [
        'q' => trim((string)($_GET['q'] ?? '')),
        'category' => (string)($_GET['category'] ?? ''),
        'gender' => (string)($_GET['gender'] ?? ''),
        'min' => $_GET['min'] ?? '',
        'max' => $_GET['max'] ?? '',
        'in_stock' => !empty($_GET['in_stock']),
        'sort' => (string)($_GET['sort'] ?? 'newest'),
    ];
    $categories = catalog_categories();
    $products = catalog_search($filters);
    require __DIR__ . '/views/store/shop.php';
    exit;
}

if (str_starts_with($route, 'product/')) {
    $slug = substr($route, 8);
    $product = catalog_product($slug);
    if (!$product) {
        http_response_code(404);
        exit('Piece not found.');
    }
    $related = catalog_related($product);
    require __DIR__ . '/views/store/product.php';
    exit;
}

if ($route === 'wishlist') {
    $products = wishlist_products();
    require __DIR__ . '/views/store/wishlist.php';
    exit;
}

if ($route === 'cart') {
    require __DIR__ . '/views/store/cart.php';
    exit;
}

if ($route === 'checkout/verify') {
    $ref = (string)($_GET['reference'] ?? '');
    try {
        paystack_verify($ref);
        $_SESSION['flash_ok'] = 'Payment received. Thank you.';
        redirect('account');
    } catch (Throwable $e) {
        $error = $e->getMessage();
        require __DIR__ . '/views/store/checkout.php';
        exit;
    }
}

if ($route === 'checkout') {
    if (!cart()) {
        redirect('cart');
    }
    $error = '';
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        verify_csrf();
        $customer = [
            'name' => trim((string)$_POST['name']),
            'phone' => trim((string)$_POST['phone']),
            'email' => trim((string)$_POST['email']),
        ];
        $method = $_POST['method'] === 'paystack' ? 'paystack' : 'whatsapp';
        if ($customer['name'] === '' || $customer['phone'] === '') {
            $error = 'Name and phone are required.';
        } elseif ($method === 'paystack' && !filter_var($customer['email'], FILTER_VALIDATE_EMAIL)) {
            $error = 'Email is required for Paystack.';
        } else {
            try {
                $order = create_order_from_cart($customer, $method);
                audit('order.create', $order['order_number']);
                if ($method === 'whatsapp') {
                    header('Location: ' . whatsapp_link($order['message']));
                    exit;
                }
                $pay = paystack_initialize($order, $customer['email']);
                header('Location: ' . $pay['authorization_url']);
                exit;
            } catch (Throwable $e) {
                $error = $e->getMessage();
            }
        }
    }
    require __DIR__ . '/views/store/checkout.php';
    exit;
}

if ($route === 'login') {
    $error = '';
    if (current_user()) {
        redirect(current_user()['role'] === 'client' ? 'account' : 'admin');
    }
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        verify_csrf();
        if (auth_login(trim((string)$_POST['username']), (string)$_POST['password'])) {
            $next = current_user()['role'] === 'client' ? 'account' : 'admin';
            redirect($next);
        }
        $error = 'Those details are not recognised, or the account is locked after too many attempts.';
    }
    require __DIR__ . '/views/store/login.php';
    exit;
}

if ($route === 'register') {
    $error = '';
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        verify_csrf();
        $errors = auth_register($_POST);
        if (!$errors) {
            redirect('account');
        }
        $error = implode(' ', $errors);
    }
    require __DIR__ . '/views/store/register.php';
    exit;
}

if ($route === 'logout') {
    auth_logout();
    redirect('');
}

if ($route === 'account') {
    require_login();
    if (current_user()['role'] !== 'client') {
        redirect('admin');
    }
    $user = current_user();
    $stmt = db()->prepare('SELECT * FROM orders WHERE user_id = ? ORDER BY created_at DESC');
    $stmt->execute([$user['id']]);
    $orders = $stmt->fetchAll();
    require __DIR__ . '/views/store/account.php';
    exit;
}

http_response_code(404);
exit('Page not found.');
