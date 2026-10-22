<?php
declare(strict_types=1);

$configFile = __DIR__ . '/includes/config.php';
$installed = is_file($configFile);
$error = '';
$ok = false;

if ($_SERVER['REQUEST_METHOD'] === 'POST' && !$installed) {
    $host = trim($_POST['db_host'] ?? 'localhost');
    $name = trim($_POST['db_name'] ?? '');
    $user = trim($_POST['db_user'] ?? '');
    $pass = (string)($_POST['db_pass'] ?? '');
    $base = rtrim(trim($_POST['base_url'] ?? ''), '/');
    $whatsapp = preg_replace('/\D+/', '', (string)($_POST['whatsapp'] ?? ''));
    $key = bin2hex(random_bytes(32));

    if ($name === '' || $user === '' || $base === '') {
        $error = 'Database name, user and site URL are required.';
    } else {
        try {
            try {
                $pdo = new PDO('mysql:host=' . $host . ';dbname=' . $name . ';charset=utf8mb4', $user, $pass, [
                    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                ]);
            } catch (PDOException $e) {
                $pdo = new PDO('mysql:host=' . $host . ';charset=utf8mb4', $user, $pass, [
                    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                ]);
                $dbIdent = '`' . str_replace('`', '``', $name) . '`';
                $pdo->exec("CREATE DATABASE IF NOT EXISTS $dbIdent CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci");
                $pdo->exec("USE $dbIdent");
            }
            $sql = (string)file_get_contents(__DIR__ . '/database/schema.sql');
            foreach (preg_split('/;\s*[\r\n]+/', $sql) as $statement) {
                $statement = trim($statement);
                if ($statement === '' || str_starts_with($statement, '--')) {
                    continue;
                }
                $pdo->exec($statement);
            }

            $hash = password_hash('Christine@2026', PASSWORD_DEFAULT);
            $owner = $pdo->prepare(
                'INSERT INTO users (name, username, email, phone, password_hash, role) VALUES (?, ?, ?, ?, ?, ?)'
            );
            $owner->execute(['Christine Anyango', 'christineanyango', 'christine@simplylume.com', $whatsapp, $hash, 'owner']);

            $catStmt = $pdo->prepare('INSERT INTO categories (name, slug, description, sort_order) VALUES (?, ?, ?, ?)');
            $cats = require __DIR__ . '/database/categories.php';
            $catIds = [];
            foreach ($cats as $c) {
                $catStmt->execute($c);
                $catIds[$c[1]] = (int)$pdo->lastInsertId();
            }

            $pStmt = $pdo->prepare(
                'INSERT INTO products (sku, name, slug, description, category_id, gender, price, stock, status, created_by)
                 VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, 1)'
            );
            foreach (require __DIR__ . '/database/products.php' as $p) {
                [$sku, $pname, $desc, $cslug, $gender, $price, $stock] = $p;
                $slug = strtolower(preg_replace('/[^a-z0-9]+/i', '-', $pname));
                $pStmt->execute([$sku, $pname, $slug, $desc, $catIds[$cslug], $gender, $price, $stock, 'active']);
            }

            $set = $pdo->prepare('INSERT INTO settings (setting_key, setting_value) VALUES (?, ?)');
            foreach ([
                ['whatsapp_number', $whatsapp],
                ['paystack_public', ''],
                ['paystack_secret', ''],
                ['business_name', 'Simply Lume'],
                ['business_address', 'Nairobi, Kenya'],
                ['business_email', 'hello@simplylume.com'],
                ['currency', 'KES'],
            ] as $row) {
                $set->execute($row);
            }
            foreach ([['order', 0], ['invoice', 0], ['receipt', 0]] as $c) {
                $pdo->prepare('INSERT INTO counters (counter_key, counter_value) VALUES (?, ?)')->execute($c);
            }

            $cfg = "<?php\ndeclare(strict_types=1);\n\n"
                . "define('DB_HOST', " . var_export($host, true) . ");\n"
                . "define('DB_NAME', " . var_export($name, true) . ");\n"
                . "define('DB_USER', " . var_export($user, true) . ");\n"
                . "define('DB_PASS', " . var_export($pass, true) . ");\n"
                . "define('DB_CHARSET', 'utf8mb4');\n"
                . "define('APP_NAME', 'Simply Lume');\n"
                . "define('APP_KEY', " . var_export($key, true) . ");\n"
                . "define('BASE_URL', " . var_export($base, true) . ");\n"
                . "define('APP_ENV', 'production');\n"
                . "define('SESSION_NAME', 'sl_session');\n"
                . "define('CURRENCY', 'KES');\n"
                . "define('CURRENCY_SYMBOL', 'KSh');\n";

            if (file_put_contents($configFile, $cfg) === false) {
                throw new RuntimeException('Could not write includes/config.php. Check folder permissions.');
            }
            $ok = true;
            $installed = true;
        } catch (Throwable $e) {
            $error = $e->getMessage();
        }
    }
}

$guessUrl = ((isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http')
    . '://' . ($_SERVER['HTTP_HOST'] ?? 'localhost')
    . rtrim(str_replace('\\', '/', dirname($_SERVER['SCRIPT_NAME'] ?? '')), '/');
?>
<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Install Simply Lume</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=Cormorant+Garamond:wght@500;600&family=Jost:wght@300;400;500&display=swap" rel="stylesheet">
    <style>
        :root { --maroon:#6b1428; --black:#0c0c0c; --ivory:#f7f3ef; }
        * { box-sizing: border-box; }
        body { margin:0; min-height:100vh; display:grid; place-items:center; background:radial-gradient(1200px 600px at 10% -10%, #3a0c16, var(--black)); color:var(--ivory); font-family:Jost,sans-serif; }
        .card { width:min(520px,92vw); background:rgba(255,255,255,.04); border:1px solid rgba(255,255,255,.12); padding:40px; }
        h1 { font-family:"Cormorant Garamond",serif; font-weight:600; letter-spacing:.18em; text-transform:uppercase; font-size:1.6rem; margin:0 0 8px; }
        p { color:#cfc6c0; font-weight:300; }
        label { display:block; font-size:.72rem; letter-spacing:.16em; text-transform:uppercase; margin:14px 0 6px; }
        input { width:100%; background:transparent; border:0; border-bottom:1px solid rgba(255,255,255,.25); color:#fff; padding:8px 0; font:inherit; }
        button { margin-top:24px; width:100%; background:var(--maroon); color:#fff; border:0; padding:14px; letter-spacing:.2em; text-transform:uppercase; cursor:pointer; }
        .err { background:#6b1428; padding:10px 12px; margin:12px 0; font-size:.9rem; }
        .ok a { color:#fff; }
    </style>
</head>
<body>
<div class="card">
    <h1>Simply Lume</h1>
    <?php if ($ok): ?>
        <p class="ok">Installation complete. Sign in as <strong>christineanyango</strong>. Delete <code>install.php</code> from the server, then open the <a href="admin">dashboard</a> or the <a href="./">shop</a>.</p>
    <?php elseif ($installed): ?>
        <p>This boutique is already installed. Remove <code>includes/config.php</code> only if you intend to reinstall.</p>
    <?php else: ?>
        <p>Create the database on cPanel first, then complete this form.</p>
        <?php if ($error): ?><div class="err"><?= htmlspecialchars($error) ?></div><?php endif; ?>
        <form method="post">
            <label>Database host</label>
            <input name="db_host" value="localhost" required>
            <label>Database name</label>
            <input name="db_name" required>
            <label>Database user</label>
            <input name="db_user" required>
            <label>Database password</label>
            <input name="db_pass" type="password">
            <label>Public site URL</label>
            <input name="base_url" value="<?= htmlspecialchars($guessUrl) ?>" required>
            <label>WhatsApp number (country code, no +)</label>
            <input name="whatsapp" placeholder="2547XXXXXXXX" required>
            <button type="submit">Install</button>
        </form>
    <?php endif; ?>
</div>
</body>
</html>
