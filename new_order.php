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
            $description = $_POST['description'] ?? '';
            $qty = $_POST['qty'] ?? 1;
            $price = $_POST['price'] ?? 0.00;
            $order_num = $_POST['order_id'] ?? 'ORD-DEFAULT';
            $customer_id = $_POST['customer_id'] ?? 'Anonymous';

            $stmt = $conn->prepare("INSERT INTO items (order_id, customer_id, brand, model, series, description, quantity, unit_price) VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
            
            if ($stmt->execute([$order_num, $customer_id, $brand, $models, $series, $description, $qty, $price])) {
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
?>

<div class="form-side">
    <header>
        <h1>Batch: <?= htmlspecialchars($current_order ?? 'No Active Order') ?></h1>
        <p class="subtitle">Assigning items to <strong><?= htmlspecialchars($current_customer) ?></strong></p>
    </header>

    <?php echo $message; ?>

    <form action="" method="POST">
        <!-- Hidden Context -->
        <input type="hidden" name="customer_id" value="<?= htmlspecialchars($current_customer) ?>">
        <input type="hidden" name="order_id" value="<?= htmlspecialchars($current_order) ?>">

        <a href="index.php" style="font-size: 0.8rem; text-decoration: none; color: var(--accent-color); font-weight: 700; display: block; margin-bottom: 20px;">← Switch Batch / Account</a>

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
            <label for="series">Series*</label>
            <input list="series-options" id="series" name="series" placeholder="Type or select series..." required aria-label="Series Selection">
            <datalist id="series-options"></datalist>
        </div>

        <div class="form-group">
            <label for="description">Description*</label>
            <input list="description-options" id="description" name="description" placeholder="Type or select description..." required aria-label="Description Selection">
            <datalist id="description-options">
                <option value="Untested">
                <option value="Tested">
                <option value="Parts">
                <option value="Not Working">
            </datalist>
        </div>
        
        <!-- Quantity and Price -->
        <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 20px;">
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
                                                    <span class='desc-badge'>" . htmlspecialchars($row['description']) . "</span>
                                                </div>
                                            </div>
                                        </div>
                                    </td>
                                    <td>
                                        <div style='text-align: right;'>
                                            <span class='qty-chip'>" . htmlspecialchars($row['quantity']) . "</span>
                                            <div style='font-size: 0.75rem; color: var(--text-secondary); margin-top: 4px; font-weight: 700;'>
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
            <div class="summary-footer" style="padding-top: 20px; border-top: 1px dashed var(--border-color); margin-top: auto;">
                <a href="checkout.php?customer_id=<?= urlencode($current_customer) ?>" class="btn-main" style="text-decoration:none; display:block; padding: 16px; border-radius: 12px; background: var(--text-main); color: white; text-align: center; font-weight: 800; font-size: 1rem; transition: transform 0.2s;">
                    Complete & Checkout
                </a>
            </div>
        <?php endif; ?>
    </section>
</div>

