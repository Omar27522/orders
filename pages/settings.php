<?php
// Secure Account Settings Fragment
$db_file = 'assets/db/users.db';
$message = '';
$error = '';

try {
    $conn_u = new PDO("sqlite:" . $db_file);
    $conn_u->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    // Migration: ensure display_name column exists
    $cols = $conn_u->query("PRAGMA table_info(users)")->fetchAll(PDO::FETCH_ASSOC);
    if (!in_array('display_name', array_column($cols, 'name'))) {
        $conn_u->exec("ALTER TABLE users ADD COLUMN display_name TEXT DEFAULT ''");
    }

    // 1. Handle Password Change (All Users)
    if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['action']) && $_POST['action'] === 'change_password') {
        $old_pass = $_POST['old_password'] ?? '';
        $new_pass = $_POST['new_password'] ?? '';
        $confirm_pass = $_POST['confirm_password'] ?? '';
        $user_id = $_SESSION['username'];

        $stmt = $conn_u->prepare("SELECT password FROM users WHERE username = ?");
        $stmt->execute([$user_id]);
        $current_hash = $stmt->fetchColumn();

        if ($current_hash && password_verify($old_pass, $current_hash)) {
            if ($new_pass === $confirm_pass) {
                if (strlen($new_pass) >= 3) {
                    $new_hash = password_hash($new_pass, PASSWORD_BCRYPT);
                    $stmt_u = $conn_u->prepare("UPDATE users SET password = ? WHERE username = ?");
                    $stmt_u->execute([$new_hash, $user_id]);
                    $message = "Password updated successfully!";
                } else {
                    $error = "New password must be at least 3 characters.";
                }
            } else {
                $error = "New passwords do not match.";
            }
        } else {
            $error = "Incorrect current password.";
        }
    }

    // 1b. Handle Signature / Display Name Update (All Users)
    if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['action']) && $_POST['action'] === 'update_signature') {
        $display_name = trim($_POST['display_name'] ?? '');
        if ($display_name !== '') {
            $stmt_sig = $conn_u->prepare("UPDATE users SET display_name = ? WHERE username = ?");
            $stmt_sig->execute([$display_name, $_SESSION['username']]);
            $_SESSION['display_name'] = $display_name;
            $message = "Signature updated! Your name will appear on future invoices.";
        } else {
            $error = "Signature name cannot be empty.";
        }
    }

    // 2. Handle User Management (Admin Only)
    if ($_SESSION['username'] === 'admin') {
        if (isset($_POST['action'])) {
            if ($_POST['action'] === 'add_user' && !empty($_POST['new_username'])) {
                $nu = trim($_POST['new_username']);
                $np = $_POST['new_password'];
                if (strlen($np) >= 3) {
                    $hash = password_hash($np, PASSWORD_BCRYPT);
                    try {
                        $auth_add = $conn_u->prepare("INSERT INTO users (username, password) VALUES (?, ?)");
                        $auth_add->execute([$nu, $hash]);
                        $message = "New user '{$nu}' added successfully!";
                    } catch(Exception $e) { $error = "Error: Username might already exist."; }
                } else { $error = "User password must be at least 3 characters."; }
            }

            if ($_POST['action'] === 'delete_user' && !empty($_POST['del_username'])) {
                $du = $_POST['del_username'];
                if ($du !== 'admin') {
                    $auth_del = $conn_u->prepare("DELETE FROM users WHERE username = ?");
                    $auth_del->execute([$du]);
                    $message = "User '{$du}' removed.";
                }
            }
        }
    }
} catch (PDOException $e) { $error = "Database error: " . $e->getMessage(); }

?>

<div class="settings-page-wrapper" style="width: 100%; display: flex; flex-direction: column; align-items: center; gap: 40px; padding-bottom: 60px;">
    <style>
        .settings-card {
            background: white;
            width: 100%;
            max-width: 500px;
            padding: 40px;
            border-radius: 20px;
            box-shadow: var(--shadow-md);
            border: 1px solid var(--border-color);
            margin-top: 20px;
        }
        .settings-header { margin-bottom: 30px; }
        .settings-header h1 { font-size: 1.4rem; margin-bottom: 8px; }
        .status-msg { padding: 12px; border-radius: 12px; font-size: 0.85rem; font-weight: 700; margin-bottom: 20px; text-align: center; }
        .msg-success { background: #f0fdf4; color: #166534; border: 1px solid #dcfce7; }
        .msg-error { background: #fef2f2; color: #991b1b; border: 1px solid #fee2e2; }
        
        .user-list { list-style: none; margin-top: 20px; border-top: 1px solid var(--border-color); padding-top: 20px; }
        .user-item { display: flex; justify-content: space-between; align-items: center; padding: 10px; border-radius: 8px; background: #f8fafc; margin-bottom: 8px; }
        .user-name { font-weight: 700; font-size: 0.9rem; color: var(--text-main); }
        .btn-delete-small { background: #fee2e2; color: #b91c1c; border: none; padding: 6px 12px; border-radius: 6px; cursor: pointer; font-size: 0.75rem; font-weight: 800; }
    </style>

    <!-- Global System Feedback -->
    <?php if ($message): ?>
        <div class="status-msg msg-success" style="width:100%; max-width:500px; margin-top:20px;"><?= $message ?></div>
    <?php endif; ?>
    <?php if ($error): ?>
        <div class="status-msg msg-error" style="width:100%; max-width:500px; margin-top:20px;"><?= $error ?></div>
    <?php endif; ?>

    <!-- 1. PERSONAL SECURITY CARD -->
    <div class="settings-card">
        <div class="settings-header" style="display:flex; justify-content:space-between; align-items:flex-start;">
            <div>
                <h1>Account Security</h1>
                <p class="subtitle">Update your password to keep your account secure.</p>
            </div>
            <a href="core/logout.php" style="text-decoration: none; background: #fef2f2; color: #991b1b; padding: 10px 16px; border-radius: 10px; font-size: 0.8rem; font-weight: 800; border: 1px solid #fee2e2;">
                🚪 Sign Out
            </a>
        </div>

        <form method="POST">
            <input type="hidden" name="action" value="change_password">
            <div class="form-group" style="margin-bottom: 20px;">
                <label>Current Password</label>
                <input type="password" name="old_password" placeholder="••••••••" required>
            </div>
            <div style="border-top: 1px dashed var(--border-color); padding-top: 20px; margin-top: 20px;">
                <div class="form-group" style="margin-bottom: 20px;">
                    <label>New Password</label>
                    <input type="password" name="new_password" placeholder="Min 3 characters" required>
                </div>
                <div class="form-group" style="margin-bottom: 30px;">
                    <label>Confirm New Password</label>
                    <input type="password" name="confirm_password" placeholder="Confirm new password" required>
                </div>
            </div>
            <button type="submit" class="btn-main" style="width: 100%; padding: 16px; border-radius: 12px; background: var(--text-main); color: white; border: none; font-weight: 800; cursor: pointer;">
                💾 Update Password
            </button>
        </form>
    </div>

    <!-- 2. SIGNATURE / INVOICE NAME CARD (ALL USERS) -->
    <div class="settings-card">
        <div class="settings-header">
            <h1>Invoice Signature</h1>
            <p class="subtitle">This name appears in the <strong>Approved By</strong> field on all printed manifests.</p>
        </div>

        <?php
            $sig_stmt = $conn_u->prepare("SELECT display_name FROM users WHERE username = ?");
            $sig_stmt->execute([$_SESSION['username']]);
            $current_sig = $sig_stmt->fetchColumn() ?: $_SESSION['username'];
        ?>

        <div style="background: #f8fafc; border: 1px solid #e2e8f0; border-radius: 12px; padding: 16px 20px; margin-bottom: 24px; font-size: 0.9rem; color: var(--text-secondary);">
            Current signature: <strong style="color: var(--text-main); font-size: 1rem;"><?= htmlspecialchars($current_sig) ?></strong>
        </div>

        <form method="POST">
            <input type="hidden" name="action" value="update_signature">
            <div class="form-group" style="margin-bottom: 20px;">
                <label>Signature / Approved By Name</label>
                <input type="text" name="display_name" value="<?= htmlspecialchars($current_sig) ?>" placeholder="e.g. John Smith — Operations Manager" required>
            </div>
            <button type="submit" class="btn-main" style="width: 100%; padding: 16px; border-radius: 12px; background: var(--accent-color); color: white; border: none; font-weight: 800; cursor: pointer;">
                ✍️ Save Signature
            </button>
        </form>
    </div>

    <!-- 3. USER MANAGEMENT CARD (ADMIN ONLY) -->
    <?php if ($_SESSION['username'] === 'admin'): ?>
    <div class="settings-card">
        <div class="settings-header">
            <h1>Staff Management</h1>
            <p class="subtitle">Assign additional accounts to help manage inventory batches.</p>
        </div>

        <form method="POST" style="background: #f8fafc; padding: 20px; border-radius: 16px; border: 1px solid #e2e8f0;">
            <input type="hidden" name="action" value="add_user">
            <div class="form-group" style="margin-bottom: 12px;">
                <label>New Username</label>
                <input type="text" name="new_username" placeholder="e.g. omar_sales" required>
            </div>
            <div class="form-group" style="margin-bottom: 18px;">
                <label>Assign Password</label>
                <input type="password" name="new_password" placeholder="Min 3 characters" required>
            </div>
            <button type="submit" class="btn-main" style="width: 100%; height: 44px; border-radius: 10px; background: var(--accent-color); color: white; border: none; font-weight: 800; cursor: pointer;">
                ⊕ Add New Staff Member
            </button>
        </form>

        <ul class="user-list">
            <li style="font-size: 0.75rem; font-weight: 800; color: var(--text-secondary); text-transform: uppercase; margin-bottom: 10px;">Active Accounts</li>
            <?php
                $users = $conn_u->query("SELECT username FROM users ORDER BY username ASC")->fetchAll(PDO::FETCH_ASSOC);
                foreach($users as $u) {
                    $is_admin = ($u['username'] === 'admin');
                    echo "<li class='user-item'>
                            <span class='user-name'>" . htmlspecialchars($u['username']) . ($is_admin ? " <small style='color:var(--accent-color)'>(Root)</small>" : "") . "</span>";
                    if (!$is_admin) {
                        echo "<form method='POST' style='display:inline;' onsubmit=\"return confirm('Remove access for this user?');\">
                                <input type='hidden' name='action' value='delete_user'>
                                <input type='hidden' name='del_username' value='" . htmlspecialchars($u['username']) . "'>
                                <button type='submit' class='btn-delete-small'>Revoke</button>
                              </form>";
                    }
                    echo "</li>";
                }
            ?>
        </ul>
    </div>
    <?php endif; ?>
</div>
