<?php
declare(strict_types=1);

function auth_login(string $username, string $password): bool
{
    $ip = client_ip();
    if (login_locked($ip, $username)) {
        return false;
    }

    $stmt = db()->prepare('SELECT * FROM users WHERE username = ? OR email = ? LIMIT 1');
    $stmt->execute([$username, $username]);
    $user = $stmt->fetch();

    if (!$user || $user['status'] !== 'active' || !password_verify($password, $user['password_hash'])) {
        db()->prepare('INSERT INTO login_attempts (ip, username) VALUES (?, ?)')->execute([$ip, $username]);
        return false;
    }

    if (password_needs_rehash($user['password_hash'], PASSWORD_DEFAULT)) {
        $hash = password_hash($password, PASSWORD_DEFAULT);
        db()->prepare('UPDATE users SET password_hash = ? WHERE id = ?')->execute([$hash, $user['id']]);
    }

    session_regenerate_id(true);
    unset($user['password_hash']);
    $_SESSION['user'] = $user;
    db()->prepare('UPDATE users SET last_login_at = NOW() WHERE id = ?')->execute([$user['id']]);
    if (!empty($_SESSION['wishlist'])) {
        $ins = db()->prepare('INSERT IGNORE INTO wishlist (user_id, product_id) VALUES (?, ?)');
        foreach ($_SESSION['wishlist'] as $pid) {
            $ins->execute([$user['id'], (int)$pid]);
        }
        unset($_SESSION['wishlist']);
    }
    audit('login', 'User signed in');
    return true;
}

function login_locked(string $ip, string $username): bool
{
    $stmt = db()->prepare(
        'SELECT COUNT(*) FROM login_attempts
         WHERE (ip = ? OR username = ?) AND attempted_at > (NOW() - INTERVAL 15 MINUTE)'
    );
    $stmt->execute([$ip, $username]);
    return (int)$stmt->fetchColumn() >= 8;
}

function auth_logout(): void
{
    $_SESSION = [];
    if (ini_get('session.use_cookies')) {
        $p = session_get_cookie_params();
        setcookie(session_name(), '', time() - 42000, $p['path'], $p['domain'], $p['secure'], $p['httponly']);
    }
    session_destroy();
}

function auth_register(array $data): array
{
    $name = trim($data['name'] ?? '');
    $email = strtolower(trim($data['email'] ?? ''));
    $username = strtolower(trim($data['username'] ?? ''));
    $phone = trim($data['phone'] ?? '');
    $password = $data['password'] ?? '';

    $errors = [];
    if ($name === '' || strlen($name) < 2) {
        $errors[] = 'Please enter your name.';
    }
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errors[] = 'Please enter a valid email.';
    }
    if (!preg_match('/^[a-z0-9._]{4,40}$/', $username)) {
        $errors[] = 'Username must be 4–40 characters, letters, numbers, dots or underscores.';
    }
    if (strlen($password) < 10) {
        $errors[] = 'Password must be at least 10 characters.';
    }
    if ($errors) {
        return $errors;
    }

    $exists = db()->prepare('SELECT id FROM users WHERE email = ? OR username = ?');
    $exists->execute([$email, $username]);
    if ($exists->fetch()) {
        return ['That email or username is already registered.'];
    }

    $stmt = db()->prepare(
        'INSERT INTO users (name, username, email, phone, password_hash, role) VALUES (?, ?, ?, ?, ?, ?)'
    );
    $stmt->execute([$name, $username, $email, $phone, password_hash($password, PASSWORD_DEFAULT), 'client']);
    $id = (int)db()->lastInsertId();
    $user = db()->prepare('SELECT id, name, username, email, phone, role, status FROM users WHERE id = ?');
    $user->execute([$id]);
    session_regenerate_id(true);
    $_SESSION['user'] = $user->fetch();
    return [];
}
