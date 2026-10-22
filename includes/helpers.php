<?php
declare(strict_types=1);

function e(?string $value): string
{
    return htmlspecialchars((string)$value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
}

function url(string $path = ''): string
{
    $base = rtrim(BASE_URL, '/');
    $path = ltrim($path, '/');
    return $path === '' ? $base . '/' : $base . '/' . $path;
}

function redirect(string $path): void
{
    header('Location: ' . url($path));
    exit;
}

function json_response(array $data, int $code = 200): void
{
    http_response_code($code);
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode($data);
    exit;
}

function money(float|int|string $amount): string
{
    return CURRENCY_SYMBOL . ' ' . number_format((float)$amount, 2);
}

function slugify(string $text): string
{
    $text = strtolower(trim($text));
    $text = preg_replace('/[^a-z0-9]+/i', '-', $text) ?? '';
    return trim($text, '-') ?: 'item';
}

function setting(string $key, ?string $default = null): ?string
{
    static $cache = null;
    if ($cache === null) {
        $rows = db()->query('SELECT setting_key, setting_value FROM settings')->fetchAll();
        $cache = [];
        foreach ($rows as $row) {
            $cache[$row['setting_key']] = $row['setting_value'];
        }
    }
    return $cache[$key] ?? $default;
}

function setting_set(string $key, string $value): void
{
    $stmt = db()->prepare(
        'INSERT INTO settings (setting_key, setting_value) VALUES (?, ?)
         ON DUPLICATE KEY UPDATE setting_value = VALUES(setting_value)'
    );
    $stmt->execute([$key, $value]);
}

function next_number(string $key, string $prefix): string
{
    $pdo = db();
    $pdo->beginTransaction();
    try {
        $stmt = $pdo->prepare('SELECT counter_value FROM counters WHERE counter_key = ? FOR UPDATE');
        $stmt->execute([$key]);
        $row = $stmt->fetch();
        if (!$row) {
            $pdo->prepare('INSERT INTO counters (counter_key, counter_value) VALUES (?, 1)')->execute([$key]);
            $n = 1;
        } else {
            $n = (int)$row['counter_value'] + 1;
            $pdo->prepare('UPDATE counters SET counter_value = ? WHERE counter_key = ?')->execute([$n, $key]);
        }
        $pdo->commit();
        return $prefix . str_pad((string)$n, 4, '0', STR_PAD_LEFT);
    } catch (Throwable $e) {
        $pdo->rollBack();
        throw $e;
    }
}

function audit(string $action, string $details = ''): void
{
    $stmt = db()->prepare('INSERT INTO audit_log (user_id, action, details, ip) VALUES (?, ?, ?, ?)');
    $stmt->execute([
        current_user()['id'] ?? null,
        $action,
        $details,
        client_ip(),
    ]);
}

function client_ip(): string
{
    $ip = $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0';
    return filter_var($ip, FILTER_VALIDATE_IP) ? $ip : '0.0.0.0';
}

function cart(): array
{
    return $_SESSION['cart'] ?? [];
}

function cart_set(array $cart): void
{
    $_SESSION['cart'] = $cart;
}

function cart_count(): int
{
    $n = 0;
    foreach (cart() as $item) {
        $n += (int)$item['qty'];
    }
    return $n;
}

function cart_total(): float
{
    $t = 0.0;
    foreach (cart() as $item) {
        $t += (float)$item['price'] * (int)$item['qty'];
    }
    return $t;
}

function format_whatsapp_message(array $items): string
{
    $names = [];
    foreach ($items as $item) {
        $qty = (int)($item['qty'] ?? 1);
        $name = (string)$item['name'];
        $names[] = $qty > 1 ? $qty . 'x ' . $name : $name;
    }
    if (count($names) === 0) {
        return "Hello, I'd like to order jewellery from Simply Lume";
    }
    if (count($names) === 1) {
        return "Hello, I'd like to order " . $names[0];
    }
    $last = array_pop($names);
    return "Hello, I'd like to order " . implode(', ', $names) . ' and ' . $last;
}

function whatsapp_link(string $message): string
{
    $number = preg_replace('/\D+/', '', (string)setting('whatsapp_number', ''));
    return 'https://wa.me/' . $number . '?text=' . rawurlencode($message);
}

function csrf_token(): string
{
    if (empty($_SESSION['_csrf'])) {
        $_SESSION['_csrf'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['_csrf'];
}

function csrf_field(): string
{
    return '<input type="hidden" name="_csrf" value="' . e(csrf_token()) . '">';
}

function verify_csrf(?string $token = null): void
{
    $token = $token ?? ($_POST['_csrf'] ?? $_SERVER['HTTP_X_CSRF_TOKEN'] ?? '');
    if (!hash_equals(csrf_token(), (string)$token)) {
        http_response_code(419);
        exit('Invalid security token. Please refresh and try again.');
    }
}

function can(string $ability): bool
{
    $user = current_user();
    if (!$user) {
        return false;
    }
    $role = $user['role'];
    if (in_array($role, ['owner', 'it'], true)) {
        return true;
    }
    $map = [
        'finance' => [
            'dashboard', 'finance', 'expenses', 'invoices', 'receipts', 'payments',
            'orders.view', 'products.view', 'reports',
        ],
        'sales' => [
            'dashboard', 'orders', 'orders.fulfill', 'products', 'inventory',
            'customers', 'invoices', 'invoices.create', 'sales',
        ],
    ];
    return in_array($ability, $map[$role] ?? [], true);
}

function require_ability(string $ability): void
{
    if (!can($ability)) {
        http_response_code(403);
        exit('You do not have permission to access this area.');
    }
}

function require_any(array $abilities): void
{
    foreach ($abilities as $ability) {
        if (can($ability)) {
            return;
        }
    }
    http_response_code(403);
    exit('You do not have permission to access this area.');
}

function current_user(): ?array
{
    return $_SESSION['user'] ?? null;
}

function require_login(): void
{
    if (!current_user()) {
        redirect('login?next=' . urlencode($_SERVER['REQUEST_URI'] ?? '/'));
    }
}

function require_staff(): void
{
    $user = current_user();
    if (!$user || $user['role'] === 'client') {
        redirect('login');
    }
}

function send_security_headers(): void
{
    header('X-Content-Type-Options: nosniff');
    header('X-Frame-Options: SAMEORIGIN');
    header('Referrer-Policy: strict-origin-when-cross-origin');
    header('X-XSS-Protection: 0');
}

function logger(string $message): void
{
    $file = dirname(__DIR__) . '/storage/logs/app.log';
    @file_put_contents($file, '[' . date('c') . '] ' . $message . PHP_EOL, FILE_APPEND | LOCK_EX);
}
