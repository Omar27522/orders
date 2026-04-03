<?php include 'core/auth.php'; ?>
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

    // Handle Quantity, Price Updates & Finalization
    if ($_SERVER["REQUEST_METHOD"] == "POST") {
        $prices = $_POST['unit_prices'] ?? [];
        $qtys = $_POST['quantities'] ?? [];
        $order_id = $_GET['order_id'] ?? ($_POST['order_id'] ?? 'ORD-DEFAULT');

        // 1. Update individual item values (always save changes)
        $stmt = $conn_items->prepare("UPDATE items SET unit_price = ?, quantity = ? WHERE id = ? AND customer_id = ?");
        foreach($prices as $id => $val) {
            $qty = (int)($qtys[$id] ?? 0);
            $stmt->execute([(float)$val, $qty, (int)$id, $customer_id]);
        }

        // 2. Determine if we should also finalize the status
        $is_finalize = (isset($_POST['finalize_order']) && $_POST['finalize_order'] === 'true') || 
                       (isset($_POST['finalize_status']) && $_POST['finalize_status'] === 'true');

        if ($is_finalize) {
            $stmt_f = $conn_items->prepare("UPDATE orders SET status = 'finalized' WHERE order_id = ?");
            $stmt_f->execute([$order_id]);
        }

        // 3. Handle Responses
        if (!empty($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) == 'xmlhttprequest') {
            echo json_encode(['status' => 'success']);
            exit();
        }

        if ($is_finalize) {
            header("Location: index.php?view=orders&type=completed");
        } else {
            header("Location: " . $_SERVER['PHP_SELF'] . "?customer_id=" . urlencode($customer_id) . "&order_id=" . urlencode($order_id));
        }
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

    // Fetch items for specific order_id (with fallback to latest order for the account)
    $active_order_id = $_GET['order_id'] ?? null;
    
    if (!$active_order_id) {
        $stmt = $conn_items->prepare("SELECT order_id FROM orders WHERE customer_id = ? ORDER BY id DESC LIMIT 1");
        $stmt->execute([$customer_id]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        $active_order_id = $row['order_id'] ?? 'ORD-DEFAULT';
    }

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
    <link rel="stylesheet" href="assets/styles/checkout.css">
    <link rel="icon" type="image/png" href="assets/icon/smart-home-sensor-wifi-black-outline-25276_1024.png">
</head>
<body style="flex-direction: column; background: #f8fafc;">

    <div class="receipt-card">
        <!-- Minimal Print Title -->
        <h1 class="print-only" style="text-align: center; margin-bottom: 30px; border-bottom: 1px solid #eee; padding-bottom: 10px;">Order Manifest</h1>

        <div class="header-success no-print" style="text-align: center; margin-bottom: 30px;">
            <div class="icon-check">✓</div>
            <h1 style="font-size: 1.5rem; font-weight: 700; color: var(--text-main); margin-bottom: 8px;">Final Batch Verification</h1>
            <p class="subtitle">Review quantities and pricing for this manifest before final submission.</p>
        </div>

        <div style="border-bottom: 1px dashed var(--border-color); padding-bottom: 20px; margin-bottom: 20px; display: flex; justify-content: space-between;">
            <div>
                <div style="font-size: 0.75rem; font-weight: 800; text-transform: uppercase; color: var(--text-secondary); margin-bottom: 4px;">Billing Account</div>
                <div style="font-weight: 700; color: var(--text-main); font-size: 1.1rem;"><?= htmlspecialchars($customer['company_name'] ?? 'Account Not Found') ?></div>
            </div>
            <div style="text-align: right;">
                <div style="font-size: 0.75rem; font-weight: 800; text-transform: uppercase; color: var(--text-secondary); margin-bottom: 4px;">Order Date</div>
                <div style="font-weight: 700; color: var(--text-main); font-size: 0.9rem;"><?= date('M d, Y') ?></div>
            </div>
        </div>

        <form method="POST" id="checkout-form">
            <input type="hidden" name="action" value="update_items">
            <table class="receipt-table">
                <thead>
                    <tr>
                        <th style="padding-left: 0;">Item Description</th>
                        <th style="text-align: center; width: 70px;">Qty</th>
                        <th style="text-align: right; width: 100px;">Price</th>
                        <th style="text-align: right; width: 110px; padding-right: 0;">Subtotal</th>
                    </tr>
                </thead>
                <tbody>
                    <?php
                    $total_items = 0;
                    $grand_total = 0;
                    if (count($items) > 0):
                        foreach($items as $item):
                            $qty = $item['quantity'];
                            $price = $item['unit_price'] ?? 0;
                            $subtotal = $qty * $price;
                            $total_items += $qty;
                            $grand_total += $subtotal;
                    ?>
                    <tr class="item-row" data-id="<?= $item['id'] ?>">
                        <td style="padding-left: 0;">
                            <div style="font-weight: 700;"><?= htmlspecialchars($item['brand'] . " " . $item['model']) ?></div>
                            <div style="font-size: 0.825rem; color: var(--text-secondary);"><?= htmlspecialchars($item['series']) ?> | <span style="color: var(--accent-color); font-weight:800;"><?= htmlspecialchars($item['cpu'] ?? '') ?></span> | <?= htmlspecialchars($item['description']) ?></div>
                        </td>
                        <td style="text-align: center;">
                            <span class="print-only" style="font-weight: 700;"><?= (int)$qty ?></span>
                            <input type="number" name="quantities[<?= $item['id'] ?>]" value="<?= (int)$qty ?>" min="1" class="qty-input no-print" style="width: 105px; text-align: center; height: 40px; border: 1px solid var(--border-color); border-radius: 8px; font-weight: 800; font-size: 1.1rem; background: #fff;" oninput="recalculateTotals()">
                        </td>
                        <td style="text-align: right;">
                            <span class="print-only">$<?= number_format($price, 0) ?></span>
                            <div class="no-print" style="display: flex; align-items: center; justify-content: flex-end; gap: 4px;">
                                <span style="font-weight: 700;">$</span>
                                <input type="number" name="unit_prices[<?= $item['id'] ?>]" value="<?= (int)$price ?>" step="1" min="0" class="price-input" style="width: 90px; text-align: right; height: 40px; padding: 0 10px; border: 1px solid var(--border-color); border-radius: 8px; font-weight: 800; font-size: 1.1rem; background: #fff;" oninput="recalculateTotals()">
                            </div>
                        </td>
                        <td style="text-align: right; font-weight: 700; color: var(--text-main); padding-right: 0;">
                            $<span class="row-subtotal"><?= number_format($subtotal, 0) ?></span>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                    <?php else: ?>
                    <tr>
                        <td colspan="4"><div class="empty-state">No items found.</div></td>
                    </tr>
                    <?php endif; ?>
                    <tr>
                        <td colspan="3" class="total-row" style="padding-left: 0;">Total Amount Due</td>
                        <td class="total-row" style="text-align: right; color: var(--accent-color); padding-right: 0;">
                            $<span id="grand-total-display"><?= number_format($grand_total, 0) ?></span>
                        </td>
                    </tr>
                </tbody>
            </table>


            <div style="margin-top: 30px; display: flex; flex-direction: column; gap: 12px; align-items: stretch;" class="no-print">
                <div style="display: flex; gap: 12px; flex-wrap: wrap;">
                    <button type="submit" name="action" value="update_items" class="btn-main" style="flex: 1.5; min-width: 150px; border: none; cursor: pointer; height: 50px; background: var(--text-main); color: white; border-radius: 12px; font-weight: 800; font-size: 1rem; box-shadow: 0 10px 15px -3px rgba(15, 23, 42, 0.1);">
                        💾 Save Changes
                    </button>
                    <button type="button" onclick="handlePrintManifest()" class="crumb btn-print-action" style="flex: 1; min-width: 120px; justify-content: center; height: 50px; background: #fff; cursor:pointer; font-weight: 700; border-radius: 12px; border: 1px solid var(--border-color);">
                        🖨 Print Manifest
                    </button>
                    <button type="button" onclick="downloadCSV()" class="crumb" style="flex: 1; min-width: 120px; justify-content: center; height: 50px; background: #fff; cursor:pointer; font-weight: 700; border-radius: 12px; border: 1px solid var(--border-color); color: #166534;">
                        📊 CSV Form
                    </button>
                </div>
                
                <button type="submit" name="finalize_order" value="true" class="btn-main" style="border: none; cursor: pointer; text-decoration:none; display:flex; align-items:center; justify-content:center; background: var(--accent-color); color: white; border-radius: 12px; font-weight: 800; height: 54px; box-shadow: 0 4px 12px rgba(140, 198, 63, 0.2);">
                    ✅ Finalize & Finish Batch
                </button>
            </div>
        </form>

        <script>
        const rawItems = <?= json_encode($items) ?>;
        const customerName = "<?= addslashes($customer['company_name'] ?? 'Account') ?>";
        const orderID = "<?= addslashes($active_order_id) ?>";
        const orderDate = "<?= date('M d, Y') ?>";

        async function handlePrintManifest() {
            const form = document.getElementById('checkout-form');
            const formData = new FormData(form);
            formData.set('action', 'update_items');
            formData.set('finalize_status', 'true');

            const printBtn = document.querySelector('.btn-print-action');
            const originalText = printBtn.innerHTML;
            
            try {
                printBtn.innerHTML = 'Saving...';
                printBtn.disabled = true;

                const response = await fetch(window.location.href, {
                    method: 'POST',
                    body: formData
                });

                if (response.ok) {
                    printBtn.innerHTML = 'Preparing...';
                    setTimeout(() => {
                        window.print();
                        printBtn.innerHTML = originalText;
                        printBtn.disabled = false;
                    }, 400);
                } else { throw new Error(); }
            } catch (err) {
                alert('Save failed. Try manually saving first.');
                printBtn.innerHTML = originalText;
                printBtn.disabled = false;
            }
        }

        function downloadCSV() {
            // Header Template (Quoted to prevent splitting on spaces)
            let csv = "\"IQA Metal B2B Purchase Form\",,,,,,,,\n\n";
            csv += `\"Name\",\"${customerName}\",,,,,,,\n`;
            csv += `\"Date\",\"${orderDate}\",,,,,,,\n`;
            csv += `\"Order #\",\"${orderID}\",,,,,,,\n\n`;
            
            // Column Headers
            csv += "\"Type\",\"Brand\",\"Model\",\"Series\",\"CPU / Gen\",\"Description\",\"Price\",\"QTY\",\"Total\"\n";
            
            let totalQty = 0;
            let grandTotal = 0;

            rawItems.forEach(item => {
                const row = document.querySelector(`input[name="quantities[${item.id}]"]`).closest('tr');
                const liveQty = parseInt(row.querySelector('.qty-input').value) || 0;
                const livePrice = parseFloat(row.querySelector('.price-input').value) || 0;
                const rowTotal = liveQty * livePrice;

                // Mandatory Quoting for all text fields
                const type = `"${(item.type || '').replace(/"/g, '""')}"`;
                const brand = `"${(item.brand || '').replace(/"/g, '""')}"`;
                const model = `"${(item.model || '').replace(/"/g, '""')}"`;
                const series = `"${(item.series || '').replace(/"/g, '""')}"`;
                const cpu = `"${(item.cpu || '').replace(/"/g, '""')}"`;
                const desc = `"${(item.description || '').replace(/"/g, '""')}"`;

                csv += `${type},${brand},${model},${series},${cpu},${desc},${livePrice},${liveQty},${rowTotal.toFixed(2)}\n`;
                totalQty += liveQty;
                grandTotal += rowTotal;
            });

            // Footer (Aligned to QTY and Total columns)
            csv += `\n,,,,,,,,\"Total QTY\",\"${totalQty}\"\n`;
            csv += `,,,,,,,,\"Total Amount\",\"$${grandTotal.toFixed(2)}\"\n`;

            const blob = new Blob(["\uFEFF" + csv], { type: 'text/csv;charset=utf-8;' });
            const link = document.createElement("a");
            const url = URL.createObjectURL(blob);
            link.setAttribute("href", url);
            link.setAttribute("download", `IQA_B2B_Form_${orderID}.csv`);
            document.body.appendChild(link);
            link.click();
            document.body.removeChild(link);
        }

        function recalculateTotals() {
            let grandTotal = 0;
            document.querySelectorAll('.item-row').forEach(row => {
                const qtyInput = row.querySelector('.qty-input');
                const qty = parseFloat(qtyInput.value) || 0;
                const priceInput = row.querySelector('.price-input');
                const price = parseFloat(priceInput.value) || 0;
                const subtotal = Math.round(qty * price);
                row.querySelector('.row-subtotal').innerText = subtotal.toLocaleString();
                
                const spans = row.querySelectorAll('.print-only');
                if (spans.length >= 2) {
                    spans[0].innerText = qty;
                    spans[1].innerText = '$' + price.toLocaleString();
                }
                grandTotal += subtotal;
            });
            document.getElementById('grand-total-display').innerText = grandTotal.toLocaleString();
        }
        </script>

        <!-- Final Manifest Approval (Print Exclusive) -->
        <div class="manifest-print-footer print-only">
            <div class="footer-row">
                <span><strong>Manifest Generated:</strong> <?= date('Y-m-d H:i:s') ?></span>
                <span style="flex: 1; text-align: center; border-bottom: 1px solid #000; margin: 0 40px; padding-bottom: 4px;">
                    <strong>Approved By:</strong> <?= htmlspecialchars($_SESSION['display_name'] ?? $_SESSION['username'] ?? '________________________') ?>
                </span>
                <span><strong>Page 1 of 1</strong></span>
            </div>
        </div>
    </div>

    <p style="margin-top: 24px; font-size: 0.85rem; color: var(--text-secondary);" class="no-print">
        <a href="index.php?customer_id=<?= urlencode($customer_id) ?>&order_id=<?= urlencode($active_order_id) ?>" style="color: var(--accent-color); text-decoration: none; font-weight: 700;">← Back to Order Entry (Edit)</a>
    </p>
</body>
</html>
