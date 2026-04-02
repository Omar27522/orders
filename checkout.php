<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

$db_dir = 'assets/db';
if (!is_dir($db_dir)) {
    mkdir($db_dir, 0777, true);
}
$db_items = $db_dir . '/orders.db';
$db_cust = $db_dir . '/customers.db';

if (!isset($_GET['customer_id'])) {
    header("Location: index.php");
    exit();
}

$customer_id = $_GET['customer_id'];

try {
    $conn_items = new PDO("sqlite:" . $db_items);
    $conn_cust = new PDO("sqlite:" . $db_cust);
    $conn_items->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    // Handle Quantity and Price Updates
    if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['action']) && $_POST['action'] === 'update_items') {
        $prices = $_POST['unit_prices'] ?? [];
        $qtys = $_POST['quantities'] ?? [];
        $stmt = $conn_items->prepare("UPDATE items SET unit_price = ?, quantity = ? WHERE id = ? AND customer_id = ?");
        foreach($prices as $id => $val) {
            $qty = (int)($qtys[$id] ?? 0);
            $stmt->execute([(float)$val, $qty, (int)$id, $customer_id]);
        }
        header("Location: " . $_SERVER['PHP_SELF'] . "?customer_id=" . urlencode($customer_id));
        exit();
    }

    // Migration Check (for order_id support)
    $cols = $conn_items->query("PRAGMA table_info(items)")->fetchAll(PDO::FETCH_ASSOC);
    $has_id = false;
    foreach($cols as $c) if ($c['name'] === 'order_id') $has_id = true;
    if (!$has_id) $conn_items->exec("ALTER TABLE items ADD COLUMN order_id TEXT NOT NULL DEFAULT 'ORD-DEFAULT'");

    // Fetch customer details
    $stmt = $conn_cust->prepare("SELECT * FROM customers WHERE customer_id = ?");
    $stmt->execute([$customer_id]);
    $customer = $stmt->fetch(PDO::FETCH_ASSOC);

    // Fetch items for specific order_id (using current context from GET or session)
    $active_order_id = $_GET['order_id'] ?? 'ORD-DEFAULT';
    $stmt = $conn_items->prepare("SELECT * FROM items WHERE customer_id = ? AND order_id = ? ORDER BY id ASC");
    $stmt->execute([$customer_id, $active_order_id]);
    $items = $stmt->fetchAll(PDO::FETCH_ASSOC);

} catch (PDOException $e) {
    die("Error: " . $e->getMessage());
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Order Checkout - IQA</title>
    <link rel="stylesheet" href="assets/styles/style.css">
    <style>
        .receipt-card {
            background: white;
            padding: 40px;
            border-radius: 20px;
            max-width: 600px;
            width: 100%;
            box-shadow: var(--shadow-md);
            animation: fadeInDown 0.6s ease;
        }
        .header-success {
            text-align: center;
            margin-bottom: 30px;
        }
        .icon-check {
            width: 60px;
            height: 60px;
            background: var(--accent-color);
            color: white;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 2rem;
            margin: 0 auto 16px auto;
            box-shadow: 0 4px 12px rgba(140, 198, 63, 0.3);
        }
        .receipt-table {
            width: 100%;
            border-collapse: collapse;
            margin: 24px 0;
        }
        .receipt-table th {
            text-align: left;
            font-size: 0.75rem;
            text-transform: uppercase;
            color: var(--text-secondary);
            padding: 10px 0;
            border-bottom: 2px solid #f1f5f9;
        }
        .receipt-table td {
            padding: 14px 0;
            border-bottom: 1px solid #f8fafc;
            font-size: 0.95rem;
        }
        .total-row {
            font-weight: 800;
            font-size: 1.1rem;
            padding-top: 20px !important;
            border-bottom: none !important;
        }
    </style>
</head>
<body style="flex-direction: column;">

    <div class="receipt-card">
        <div class="header-success">
            <div class="icon-check">✓</div>
            <h1>Order Confirmed</h1>
            <p style="color: var(--text-secondary);">Your hardware request has been processed.</p>
        </div>

        <div style="border-bottom: 1px dashed var(--border-color); padding-bottom: 20px; margin-bottom: 20px;">
            <div style="font-size: 0.75rem; font-weight: 800; text-transform: uppercase; color: var(--text-secondary); margin-bottom: 4px;">Billing Account</div>
            <div style="font-weight: 700; color: var(--text-main); font-size: 1.1rem;"><?= htmlspecialchars($customer['company_name'] ?? 'Account Not Found') ?></div>
            <div style="font-size: 0.85rem; color: var(--text-secondary);"><?= htmlspecialchars($customer['customer_id'] ?? $customer_id) ?></div>
        </div>

        <form method="POST" id="checkout-form">
            <input type="hidden" name="action" value="update_items">
            <table class="receipt-table">
                <thead>
                    <tr>
                        <th>Description</th>
                        <th style="text-align: center; width: 100px;">Qty</th>
                        <th style="text-align: right; width: 120px;">Unit Price</th>
                        <th style="text-align: right; width: 120px;">Subtotal</th>
                    </tr>
                </thead>
                <tbody>
                    <?php 
                    $total_items = 0;
                    $grand_total = 0;
                    foreach($items as $item): 
                        $qty = $item['quantity'];
                        $price = $item['unit_price'] ?? 0;
                        $subtotal = $qty * $price;
                        $total_items += $qty;
                        $grand_total += $subtotal;
                    ?>
                    <tr class="item-row" data-id="<?= $item['id'] ?>">
                        <td>
                            <div style="font-weight: 700;"><?= htmlspecialchars($item['brand'] . " " . $item['model']) ?></div>
                            <div style="font-size: 0.825rem; color: var(--text-secondary);"><?= htmlspecialchars($item['series'] . " | " . $item['description']) ?></div>
                        </td>
                        <td style="text-align: center;">
                            <input type="number" 
                                   name="quantities[<?= $item['id'] ?>]" 
                                   value="<?= (int)$qty ?>" 
                                   min="1"
                                   class="qty-input"
                                   style="width: 60px; text-align: center; height: 32px; border: 1px solid var(--border-color); border-radius: 6px; font-weight: 700;"
                                   oninput="recalculateTotals()">
                        </td>
                        <td style="text-align: right;">
                            <div style="display: flex; align-items: center; justify-content: flex-end; gap: 4px;">
                                <span>$</span>
                                <input type="number" 
                                       name="unit_prices[<?= $item['id'] ?>]" 
                                       value="<?= (int)$price ?>" 
                                       step="1" 
                                       min="0"
                                       class="price-input"
                                       style="width: 80px; text-align: right; height: 32px; padding: 4px 8px; border: 1px solid var(--border-color); border-radius: 6px; font-weight: 700;"
                                       oninput="recalculateTotals()">
                            </div>
                        </td>
                        <td style="text-align: right; font-weight: 700; color: var(--text-main);">
                            $<span class="row-subtotal"><?= number_format($subtotal, 0) ?></span>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                    <tr>
                        <td colspan="3" class="total-row">Order Grand Total</td>
                        <td class="total-row" style="text-align: right; color: var(--accent-color);">
                            $<span id="grand-total-display"><?= number_format($grand_total, 0) ?></span>
                        </td>
                    </tr>
                </tbody>
            </table>

            <div style="margin-top: 30px; display: flex; gap: 12px; align-items: stretch;">
                <button type="submit" class="btn-main" style="flex: 1.5; border: none; cursor: pointer; height: 54px; background: var(--text-main); color: white; border-radius: 12px; font-weight: 800; font-size: 1rem; box-shadow: 0 10px 15px -3px rgba(15, 23, 42, 0.2);">
                    💾 Save Changes & Update Order
                </button>
                <button type="button" onclick="window.print()" class="crumb" style="flex: 1; justify-content: center; height: 54px; background: #f8fafc; cursor:pointer; font-weight: 700; border-radius: 12px;">
                    🖨 Print
                </button>
            </div>
        </form>

        <a href="index.php" class="btn-main" style="text-decoration:none; display:flex; align-items:center; justify-content:center; background: var(--accent-color); color: white; border-radius: 12px; font-weight: 800; height: 54px; margin-top: 12px; box-shadow: 0 4px 12px rgba(140, 198, 63, 0.2);">
            ✅ Finalize & Finish
        </a>

        <script>
        function recalculateTotals() {
            let grandTotal = 0;
            document.querySelectorAll('.item-row').forEach(row => {
                const qtyInput = row.querySelector('.qty-input');
                const qty = parseFloat(qtyInput.value) || 0;
                
                const priceInput = row.querySelector('.price-input');
                const price = parseFloat(priceInput.value) || 0;
                
                const subtotal = Math.round(qty * price);
                
                row.querySelector('.row-subtotal').innerText = subtotal.toLocaleString(undefined, {maximumFractionDigits: 0});
                grandTotal += subtotal;
            });
            document.getElementById('grand-total-display').innerText = grandTotal.toLocaleString(undefined, {maximumFractionDigits: 0});
        }
        </script>
        
    </div>

    <p style="margin-top: 24px; font-size: 0.85rem; color: var(--text-secondary);">
        <a href="index.php?customer_id=<?= urlencode($customer_id) ?>" style="color: var(--accent-color); text-decoration: none;">← Back to Order Entry (Edit)</a>
    </p>

</body>
</html>
