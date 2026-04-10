<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

$db_dir = 'assets/db';
if (!is_dir($db_dir)) {
    mkdir($db_dir, 0777, true);
}
$db_file = $db_dir . '/customers.db';
$db_orders = $db_dir . '/orders.db';

try {
    $conn = new PDO("sqlite:" . $db_file);
    $conn_orders = new PDO("sqlite:" . $db_orders);
    $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $conn_orders->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    // Create orders table if first run
    $conn_orders->exec("CREATE TABLE IF NOT EXISTS orders (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        order_id TEXT NOT NULL UNIQUE,
        customer_id TEXT NOT NULL,
        status TEXT NOT NULL DEFAULT 'draft',
        created_at DATETIME DEFAULT CURRENT_TIMESTAMP
    )");

    // Initial schema (redundant if already created)
    $conn->exec("CREATE TABLE IF NOT EXISTS customers (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        customer_id TEXT NOT NULL UNIQUE,
        company_name TEXT NOT NULL,
        website TEXT,
        contact_person TEXT,
        address TEXT,
        email TEXT,
        phone TEXT,
        shipping_address TEXT,
        internal_notes TEXT,
        created_at DATETIME DEFAULT CURRENT_TIMESTAMP
    )");

    // CRM Migration
    $cols = $conn->query("PRAGMA table_info(customers)")->fetchAll(PDO::FETCH_ASSOC);
    $has_cb = false; $has_msg = false;
    foreach($cols as $c) {
        if ($c['name'] === 'callback_date') $has_cb = true;
        if ($c['name'] === 'message_date') $has_msg = true;
    }
    if (!$has_cb) $conn->exec("ALTER TABLE customers ADD COLUMN callback_date TEXT DEFAULT ''");
    if (!$has_msg) $conn->exec("ALTER TABLE customers ADD COLUMN message_date TEXT DEFAULT ''");

    if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['action']) && $_POST['action'] === 'edit_customer') {
        $stmt = $conn->prepare("UPDATE customers SET
            company_name = ?, website = ?, contact_person = ?, address = ?,
            email = ?, phone = ?, shipping_address = ?, internal_notes = ?,
            callback_date = ?, message_date = ?
            WHERE customer_id = ?");
        $stmt->execute([
            $_POST['company_name'], $_POST['website'], $_POST['contact_person'], $_POST['address'],
            $_POST['email'], $_POST['phone'], $_POST['shipping_address'], $_POST['internal_notes'],
            $_POST['callback_date'] ?? '', $_POST['message_date'] ?? '',
            $_POST['customer_id']
        ]);
        header("Location: index.php");
        exit();
    }

} catch (PDOException $e) {
    die("Database Connection failed: " . $e->getMessage());
}
?>

<!-- Load dedicated registry styles -->


<div class="form-side">
    <header>
        <h1>Active Customers</h1>
        <p class="subtitle">Select a customer below or register a new one to begin.</p>
    </header>

    <div style="margin-bottom: 30px;">
        <a href="index.php?view=register" class="btn-main" style="text-decoration:none; display:inline-block; padding: 12px 24px; border-radius: 12px; background: var(--accent-color); color: white; font-weight: 700; width: 100%; text-align: center;">+ Register New Customer</a>
    </div>

    <div class="search-box" style="margin-bottom: 20px;">
        <input type="text" id="cust-search" placeholder="Search by name or ID..." style="font-size: 0.9rem;" onkeyup="filterCustomers()">
    </div>

    <div class="registry-list" id="customer-list">
        <?php
        $stmt = $conn->query("SELECT * FROM customers ORDER BY company_name ASC");
        $customers = $stmt->fetchAll(PDO::FETCH_ASSOC);

        foreach($customers as &$c) {
            $stmt_o = $conn_orders->prepare("SELECT order_id, created_at, status FROM orders WHERE customer_id = ? ORDER BY created_at DESC");
            $stmt_o->execute([$c['customer_id']]);
            $c['orders_list'] = $stmt_o->fetchAll(PDO::FETCH_ASSOC);
            
            // Calculate specific counts
            $completed_count = 0;
            $active_count = 0;
            foreach ($c['orders_list'] as $o) {
                if (in_array(strtolower($o['status']), ['finalized', 'paid', 'dispatched'])) $completed_count++;
                else $active_count++;
            }
            $c['completed_count'] = $completed_count;
            $c['active_count'] = $active_count;
        }
        unset($c);

        if (count($customers) > 0) {
                foreach($customers as $c) {
                $json_data = htmlspecialchars(json_encode($c), ENT_QUOTES, 'UTF-8');
                echo "<div class='cust-card' onclick='showDetails(this)' data-customer='{$json_data}' data-search='" . htmlspecialchars($c['company_name'] . " " . $c['customer_id']) . "'>
                        <div class='cust-main'>
                            <div class='cust-name'>" . htmlspecialchars($c['company_name']) . "</div>
                            <div style='font-size: 0.75rem; font-family: monospace; color: var(--text-secondary); margin-bottom: 8px;'>" . htmlspecialchars($c['customer_id']) . "</div>
                            <div style='display:flex; align-items:center; flex-wrap: wrap; gap: 8px;'>
                                <div style='font-size: 0.7rem; background: #f0fdf4; color:#166534; padding:3px 8px; border-radius:10px; font-weight:700; white-space: nowrap;'>{$c['completed_count']} Completed</div>
                                " . ($c['active_count'] > 0 ? "<div style='font-size: 0.7rem; background: #fffbeb; color:#92400e; padding:3px 8px; border-radius:10px; font-weight:700; white-space: nowrap;'>{$c['active_count']} Drafts</div>" : "") . "
                            </div>
                        </div>
                        <div class='card-actions'>
                            <a href=\"#customer-details\"><div class='btn-view-cust' title='View Details'>👁</div></a>
                        </div>
                      </div>";
            }
        }
 else {
            echo "<div class='empty-state' style='padding: 40px;'>No customers registered yet.</div>";
        }
        ?>
    </div>
</div>

<div class="summary-side" id="customer-details">
    <section class="item-list">
        <h2>Customer Details</h2>
        <div id="side-details">
            <div class="empty-state" style='padding: 60px;'>
                Select a customer on the left to see full details.
            </div>
        </div>
    </section>
</div>


