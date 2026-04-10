<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

$db_dir = __DIR__ . '/../assets/db';
if (!is_dir($db_dir)) mkdir($db_dir, 0777, true);
$db_file = $db_dir . '/users.db';

try {
    $conn_auth = new PDO("sqlite:" . $db_file);
    $conn_auth->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    // Initial table setup
    $conn_auth->exec("CREATE TABLE IF NOT EXISTS users (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        username TEXT NOT NULL UNIQUE,
        password TEXT NOT NULL,
        display_name TEXT DEFAULT '',
        created_at DATETIME DEFAULT CURRENT_TIMESTAMP
    )");

    // Migration: add display_name if older DB
    $cols = $conn_auth->query("PRAGMA table_info(users)")->fetchAll(PDO::FETCH_ASSOC);
    if (!in_array('display_name', array_column($cols, 'name'))) {
        $conn_auth->exec("ALTER TABLE users ADD COLUMN display_name TEXT DEFAULT ''");
    }

    // Seed default user if empty (admin / admin123)
    $stmt = $conn_auth->query("SELECT COUNT(*) FROM users");
    if ($stmt->fetchColumn() == 0) {
        $hash = password_hash('admin123', PASSWORD_BCRYPT);
        $stmt_s = $conn_auth->prepare("INSERT INTO users (username, password, display_name) VALUES (?, ?, ?)");
        $stmt_s->execute(['admin', $hash, 'Administrator']);
    }

    $error = null;
    if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['username']) && isset($_POST['password'])) {
        $username = trim($_POST['username']);
        $password = $_POST['password'];

        $stmt_l = $conn_auth->prepare("SELECT * FROM users WHERE username = ?");
        $stmt_l->execute([$username]);
        $user = $stmt_l->fetch(PDO::FETCH_ASSOC);

        if ($user && password_verify($password, $user['password'])) {
            $_SESSION['authenticated'] = true;
            $_SESSION['username'] = $user['username'];
            $_SESSION['display_name'] = $user['display_name'] ?: $user['username'];
            $_SESSION['role'] = $user['role'] ?? 'Operator';
            header("Location: ../index.php");
            exit();
        } else {
            $error = "Invalid username or password";
        }
    }
} catch (PDOException $e) {
    die("Auth error: " . $e->getMessage());
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login | IQA Metal Portal</title>
    <link rel="stylesheet" href="../assets/styles/style.css">
    <link rel="stylesheet" href="../assets/styles/login.css">
    <link rel="icon" type="image/png" href="../assets/icon/smart-home-sensor-wifi-black-outline-25276_1024.png">
</head>
<body class="login-body">

    <div class="login-card">
        <div class="login-logo">
            <img src="../assets/icon/smart-home-sensor-wifi-black-outline-25276_1024.png" alt="IQA Logo">
        </div>

        <div class="login-header">
            <h1>IQA Metal Portal</h1>
            <p>Enter your credentials to access order management.</p>
        </div>

        <?php if ($error): ?>
            <div class="login-error"><?= htmlspecialchars($error) ?></div>
        <?php endif; ?>

        <form method="POST" action="">
            <div class="login-form-group">
                <label for="username">Username</label>
                <input type="text" id="username" name="username" class="login-input" placeholder="admin" required>
            </div>

            <div class="login-form-group">
                <label for="password">Password</label>
                <input type="password" id="password" name="password" class="login-input" placeholder="••••••••" required>
            </div>

            <button type="submit" class="btn-login">🔒 Sign In Safely</button>
        </form>

        <div class="login-footer">
            <small>&copy; <?= date('Y') ?> IQA Metal | Secured Batch fulfillment</small>
        </div>
    </div>

</body>
</html>
