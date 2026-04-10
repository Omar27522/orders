<?php include 'core/auth.php'; ?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Order Entry | IQA Metal</title>
    <meta name="description" content="IQA Metal Order Management and Warehouse Control System. Efficiently manage batches, inventory, and customer fulfillments.">

    <!-- Optimize Third-Party Connections (Non-blocking Fonts) -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link rel="preload" as="style" href="https://fonts.googleapis.com/css2?family=Outfit:wght@300;400;500;600;700;800;900&display=swap">
    <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Outfit:wght@300;400;500;600;700;800;900&display=swap" media="print" onload="this.media='all'">
    <noscript>
        <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Outfit:wght@300;400;500;600;700;800;900&display=swap">
    </noscript>

    <!-- Primary Stylesheet (LCP Priority) -->
    <link rel="stylesheet" href="assets/styles/style.css?v=1.1">

    <!-- Conditional Style Discovery (Avoids Chaining) -->
    <?php
        $view = $_GET['view'] ?? '';
        $v = "1.1";
        if (isset($_GET['customer_id'])) echo '<link rel="stylesheet" href="assets/styles/new_order.css?v='.$v.'">';
        if ($view === 'orders') echo '<link rel="stylesheet" href="assets/styles/orders.css?v='.$v.'">';
        if ($view === 'warehouse') echo '<link rel="stylesheet" href="assets/styles/warehouse.css?v='.$v.'">';
        if ($view === 'register') echo '<link rel="stylesheet" href="assets/styles/customer_registry.css?v='.$v.'">';
    ?>

    <link rel="icon" type="image/png" href="assets/icon/smart-home-sensor-wifi-black-outline-25276_1024.png">

    <!-- Logic Initialization (Deferred) -->
    <script src="assets/js/inventory_data.js?v=1.1" defer></script>
</head>

<body>
    <div class="breadcrumb-container" role="banner" style="max-width: 800px; margin: 0 auto 20px auto; width: 100%; display: flex; justify-content: space-between; align-items: center;">
        <nav class="breadcrumbs">
            <a href="index.php"
                class="crumb <?= !isset($_GET['customer_id']) && !isset($_GET['view']) ? 'active' : '' ?>">
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
                <span class="step-num">2</span> Order Entry
            </a>
            <?php endif; ?>

            <?php if (isset($_GET['view']) && $_GET['view'] === 'settings'): ?>
            <span class="separator">/</span>
            <a href="#" class="crumb active">
                <span class="step-num">⚙️</span> Settings
            </a>
            <?php endif; ?>
        </nav>

        <nav class="breadcrumbs" style="display: flex; gap: 20px; align-items: center;">
            <a href="index.php?view=warehouse" class="crumb <?= isset($_GET['view']) && $_GET['view'] === 'warehouse' ? 'active' : '' ?>" style="margin:0;">
                🏬 Warehouse
            </a>
            <a href="index.php?view=orders" class="crumb <?= isset($_GET['view']) && $_GET['view'] === 'orders' ? 'active' : '' ?>" style="margin:0;">
                📦 All Orders
            </a>
            <a href="index.php?view=settings" class="crumb icon-only <?= isset($_GET['view']) && $_GET['view'] === 'settings' ? 'active' : '' ?>" style="margin:0;">⚙️</a>
        </nav>
    </div>

    <div class="container <?= isset($_GET['customer_id']) || (isset($_GET['view']) && $_GET['view'] === 'orders') || (isset($_GET['view']) && $_GET['view'] === 'warehouse') ? 'order-view' : '' ?>" role="main">
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
            include 'pages/new_order.php';
        } elseif (isset($_GET['view']) && $_GET['view'] === 'register') {
            include 'pages/new_customer.php';
        } elseif (isset($_GET['view']) && $_GET['view'] === 'orders') {
            include 'pages/orders.php';
        } elseif (isset($_GET['view']) && $_GET['view'] === 'warehouse') {
            include 'pages/warehouse.php';
        } elseif (isset($_GET['view']) && $_GET['view'] === 'settings') {
            include 'pages/settings.php';
        } else {
            include 'pages/customer_registry.php';
        }
        ?>
    </div>
    <footer class="footer" role="contentinfo">
    <nav class="breadcrumbs">
            <a href="index.php"
                class="crumb <?= !isset($_GET['customer_id']) && !isset($_GET['view']) ? 'active' : '' ?>">
                <span class="step-num">&#8507;</span> Customers
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
                <span class="step-num">2</span> Order Entry
            </a>
            <?php endif; ?>

            <?php if (isset($_GET['view']) && $_GET['view'] === 'settings'): ?>
            <span class="separator">/</span>
            <a href="#" class="crumb active">
                <span class="step-num">⚙️</span> Settings
            </a>
            <?php endif; ?>
        </nav>
    </footer>
    <!-- Load compiled JavaScript directly for performance/mobile compatibility -->
    <script src="assets/js/new_order.js?v=1.1" defer></script>
    <script src="assets/js/warehouse.js?v=1.1" defer></script>
    <script src="assets/js/customer_registry.js?v=1.1" defer></script>
</body>

</html>