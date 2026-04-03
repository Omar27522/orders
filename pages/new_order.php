<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

$db_dir = 'assets/db';
if (!is_dir($db_dir)) {
    mkdir($db_dir, 0777, true);
}
$db_file = $db_dir . '/orders.db';

try {
    // Create connection to SQLite database
    $conn = new PDO("sqlite:" . $db_file);
    $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    // NEW! Ensure orders table exists for tracking multiple batches
    $conn->exec("CREATE TABLE IF NOT EXISTS orders (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        order_id TEXT NOT NULL UNIQUE,
        customer_id TEXT NOT NULL,
        status TEXT NOT NULL DEFAULT 'draft',
        created_at DATETIME DEFAULT CURRENT_TIMESTAMP
    )");

    // Ensure items table supports grouping by order_id
    $conn->exec("CREATE TABLE IF NOT EXISTS items (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        order_id TEXT NOT NULL DEFAULT 'ORD-DEFAULT',
        customer_id TEXT NOT NULL,
        brand TEXT NOT NULL,
        model TEXT NOT NULL,
        series TEXT NOT NULL,
        description TEXT NOT NULL,
        quantity INTEGER NOT NULL,
        unit_price REAL DEFAULT 0.00,
        created_at DATETIME DEFAULT CURRENT_TIMESTAMP
    )");

    // Migration: Check if we need to add order_id to existing table
    $columns = $conn->query("PRAGMA table_info(items)")->fetchAll(PDO::FETCH_ASSOC);
    $has_order_id = false;
    foreach($columns as $col) {
        if ($col['name'] === 'order_id') $has_order_id = true;
    }
    
    if (!$has_order_id) {
        $conn->exec("ALTER TABLE items ADD COLUMN order_id TEXT NOT NULL DEFAULT 'ORD-DEFAULT'");
    }

    // Migration Check (for CPU/Gen support)
    $has_cpu = false;
    foreach($columns as $col) {
        if ($col['name'] === 'cpu') $has_cpu = true;
    }
    if (!$has_cpu) {
        $conn->exec("ALTER TABLE items ADD COLUMN cpu TEXT DEFAULT ''");
    }

    // Handle Form Submission
    if ($_SERVER["REQUEST_METHOD"] == "POST") {
        if (isset($_POST['action']) && $_POST['action'] === 'delete') {
            $delete_id = $_POST['delete_id'] ?? 0;
            $stmt = $conn->prepare("DELETE FROM items WHERE id = ?");
            if ($stmt->execute([$delete_id])) {
                $_SESSION['message'] = "<div class='alert success'>Item removed from order.</div>";
            }
        } else {
            $brand = $_POST['brand'] ?? '';
            $models = $_POST['models'] ?? '';
            $series = $_POST['series'] ?? '';
            $cpu = $_POST['cpu'] ?? '';
            $description = $_POST['description'] ?? '';
            $qty = $_POST['qty'] ?? 1;
            $price = $_POST['price'] ?? 0.00;
            $order_num = $_POST['order_id'] ?? 'ORD-DEFAULT';
            $customer_id = $_POST['customer_id'] ?? 'Anonymous';

            $stmt = $conn->prepare("INSERT INTO items (order_id, customer_id, brand, model, series, cpu, description, quantity, unit_price) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)");
            
            if ($stmt->execute([$order_num, $customer_id, $brand, $models, $series, $cpu, $description, $qty, $price])) {
                $_SESSION['message'] = "<div class='alert success'>Item added to batch <strong>{$order_num}</strong>!</div>";
            } else {
                $_SESSION['message'] = "<div class='alert error'>Error adding item.</div>";
            }
        }
        
        // PRG Pattern
        $cust_param = urlencode($_POST['customer_id'] ?? $current_customer);
        $order_param = urlencode($_POST['order_id'] ?? $current_order);
        header("Location: index.php?customer_id=" . $cust_param . "&order_id=" . $order_param);
        exit();
    }
} catch(PDOException $e) {
    die("Database Connection failed: " . $e->getMessage());
}

$message = $_SESSION['message'] ?? "";
unset($_SESSION['message']);
    // Fetch customer details for header
    $customer_name = 'Customer';
    try {
        $c_db = new PDO("sqlite:assets/db/customers.db");
        $c_stmt = $c_db->prepare("SELECT company_name FROM customers WHERE customer_id = ?");
        $c_stmt->execute([$current_customer]);
        $customer_name = $c_stmt->fetchColumn() ?: $current_customer;
    } catch(Exception $e) {}
?>

<!-- Load dedicated builder styles -->
<link rel="stylesheet" href="assets/styles/new_order.css">

<div class="form-side">
    <header>
        <h1>Active Batch Builder</h1>
        <p class="subtitle">Assigning items for <strong><?= htmlspecialchars($customer_name) ?></strong></p>
    </header>

    <?php echo $message; ?>

    <form action="" method="POST">
        <!-- Hidden Context -->
        <input type="hidden" name="customer_id" value="<?= htmlspecialchars($current_customer) ?>">
        <input type="hidden" name="order_id" value="<?= htmlspecialchars($current_order) ?>">

        <a href="index.php" class="builder-back-link">← Switch Batch / Account</a>

        <!-- Brand Selection Dropdown -->
        <div class="form-group">
            <label for="brand">Choose Brand*</label>
            <select id="brand" name="brand" required aria-label="Brand Selection">
                <option value="" selected disabled>— Select Brand —</option>
                <option value="Dell">Dell</option>
                <option value="HP">HP</option>
                <option value="Lenovo">Lenovo</option>
                <option value="Apple">Apple</option>
                <option value="Microsoft">Microsoft</option>
                <option value="MSI">MSI</option>
                <option value="Asus">Asus</option>
                <option value="Acer">Acer</option>
                <option value="Samsung">Samsung</option>
                <option value="Other">Other</option>
            </select>
        </div>

        <!-- Main Models Searchable Selection -->
        <div class="form-group">
            <label for="models">Main Models*</label>
            <input list="model-options" id="models" name="models" placeholder="Type or select model..." required aria-label="Models Selection">
            <datalist id="model-options"></datalist>
        </div>

        <!-- Series Searchable Selection -->
        <div class="form-group">
            <label for="series">Series / Project ID*</label>
            <input list="series-options" id="series" name="series" placeholder="Type or select series..." required aria-label="Series Selection">
            <datalist id="series-options"></datalist>
        </div>

        <!-- CPU Selection -->
        <div class="form-group">
            <label for="cpu">CPU / Gen*</label>
            <input list="cpu-options" id="cpu" name="cpu" placeholder="e.g. Core i7 11th Gen" required aria-label="CPU Selection">
            <datalist id="cpu-options"></datalist>
        </div>

        <!-- Working Status Description -->
        <div class="form-group">
            <label for="description">Condition / Comments*</label>
            <input list="desc-options" id="description" name="description" placeholder="e.g. Working, Screen Damage" required aria-label="Condition Selection">
            <datalist id="desc-options">
                <option value="Working / Good">
                <option value="Refurbished">
                <option value="Untested">
                <option value="Tested">
                <option value="Parts">
                <option value="Not Working">
            </datalist>
        </div>
        
        <!-- Quantity and Price -->
        <div class="builder-fields-row">
            <div class="form-group">
                <label for="qty">Quantity*</label>
                <input type="number" id="qty" name="qty" placeholder="1" value="1" min="1" required>
            </div>
            <div class="form-group">
                <label for="price">Unit Price ($)*</label>
                <input type="number" id="price" name="price" placeholder="0.00" step="0.01" min="0" required>
            </div>
        </div>
        <input type="submit" value="Add to Order">
    </form>
</div>

<div class="summary-side">
    <section class="item-list">
        <h2>Order Summary</h2>
        <div class="table-container">
            <table>
                <thead>
                    <tr>
                        <th>Item</th>
                        <th>Qty</th>
                    </tr>
                </thead>
                <tbody>
                    <?php
                    $stmt = $conn->prepare("SELECT * FROM items WHERE customer_id = ? AND order_id = ? ORDER BY id DESC LIMIT 20");
                    $stmt->execute([$current_customer, $current_order]);
                    $items = $stmt->fetchAll(PDO::FETCH_ASSOC);
                    
                    if (count($items) > 0) {
                        foreach($items as $row) {
                            echo "<tr>
                                    <td>
                                        <div class='item-row-content'>
                                            <div class='item-info'>
                                                <div class='item-main'>" . htmlspecialchars($row['brand']) . " " . htmlspecialchars($row['model']) . "</div>
                                                <div class='item-sub'>
                                                     <span>" . htmlspecialchars($row['series']) . "</span>
                                                     <span class='cpu-highlight'>" . htmlspecialchars($row['cpu'] ?? '') . "</span>
                                                     <span class='desc-badge'>" . htmlspecialchars($row['description']) . "</span>
                                                 </div>
                                            </div>
                                        </div>
                                    </td>
                                    <td>
                                        <div class='qty-pricing-box'>
                                            <span class='qty-chip'>" . htmlspecialchars($row['quantity']) . "</span>
                                            <div class='qty-unit-cost'>
                                                $" . number_format($row['unit_price'] ?? 0, 0) . "
                                            </div>
                                        </div>
                                    </td>
                                    <td class='action-cell'>
                                        <form method='POST' style='display:inline;' onsubmit=\"return confirm('Remove this item?');\">
                                            <input type='hidden' name='action' value='delete'>
                                            <input type='hidden' name='delete_id' value='{$row['id']}'>
                                            <button type='submit' class='btn-delete' title='Remove Item'>&times;</button>
                                        </form>
                                    </td>
                                  </tr>";
                        }
                    } else {
                        echo "<tr><td colspan='3'>
                                <div class='empty-state'>
                                    <p>Your order is empty</p>
                                    <small>Add items from the left to start building your order.</small>
                                </div>
                              </td></tr>";
                    }
                    ?>
                </tbody>
            </table>
        </div>
        
        <?php if (count($items) > 0): ?>
            <div class="builder-footer">
                <a href="checkout.php?customer_id=<?= urlencode($current_customer) ?>&order_id=<?= urlencode($current_order) ?>" class="btn-checkout-link">
                    Complete & Checkout
                </a>
            </div>
        <?php endif; ?>
    </section>
</div>
