<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Order Entry | IQA Metal</title>
    <link rel="stylesheet" href="assets/styles/style.css">
    </head>
<body>
    <div class="breadcrumb-container" style="max-width: 800px; margin: 0 auto 20px auto; width: 100%;">
        <nav class="breadcrumbs">
            <a href="index.php" class="crumb <?= !isset($_GET['customer_id']) && !isset($_GET['view']) ? 'active' : '' ?>">
                <span class="step-num">1</span> Customers
            </a>
            
            <?php if (isset($_GET['view']) && $_GET['view'] === 'register'): ?>
                <span class="separator">/</span>
                <a href="#" class="crumb active">
                    <span class="step-num">2</span> Register
                </a>
            <?php endif; ?>

            <?php if (isset($_GET['customer_id'])): ?>
                <span class="separator">/</span>
                <a href="#" class="crumb active">
                    <span class="step-num">2</span> Order: <?= htmlspecialchars($_GET['customer_id']) ?>
                </a>
            <?php endif; ?>
        </nav>
    </div>

    <div class="container">
        <?php 
        // Order Creation Logic
        if (isset($_GET['action']) && $_GET['action'] === 'create_new_order' && isset($_GET['customer_id'])) {
            $db_dir = 'assets/db';
            if (!is_dir($db_dir)) mkdir($db_dir, 0777, true);
            
            $conn_o = new PDO("sqlite:" . $db_dir . "/orders.db");
            $new_order_id = 'ORD-' . strtoupper(substr(bin2hex(random_bytes(4)), 0, 8));
            
            $stmt = $conn_o->prepare("INSERT INTO orders (order_id, customer_id) VALUES (?, ?)");
            $stmt->execute([$new_order_id, $_GET['customer_id']]);
            
            header("Location: index.php?customer_id=" . urlencode($_GET['customer_id']) . "&order_id=" . $new_order_id);
            exit();
        }

        if (isset($_GET['customer_id'])) {
            $current_customer = $_GET['customer_id'];
            $current_order = $_GET['order_id'] ?? null;
            include 'new_order.php'; 
        } elseif (isset($_GET['view']) && $_GET['view'] === 'register') {
            include 'new_customer.php'; 
        } else {
            include 'customer_registry.php'; 
        }
        ?>
    </div>
    <!-- Live TypeScript compiler for browser -->
    <script src="https://unpkg.com/@babel/standalone/babel.min.js"></script>
    <script type="text/babel" data-presets="typescript" src="assets/ts/new_order.ts"></script>
</body>
</html>

