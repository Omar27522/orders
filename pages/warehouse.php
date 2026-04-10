<?php
include 'core/warehouse_db.php';
include 'core/auth.php'; // Session is already started and checked

$current_user = $_SESSION['username'];
$selected_sector = $_GET['sector'] ?? 'Laptops';
$selected_loc = $_GET['loc'] ?? null;

// Handle Add/Edit/Delete Item
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    if ($_POST['action'] === 'delete_inventory' && isset($_POST['item_id'])) {
        $stmt = $conn_wh->prepare("DELETE FROM inventory WHERE id=?");
        $stmt->execute([$_POST['item_id']]);
        
        $sector = $_GET['sector'] ?? $_POST['sector'] ?? 'Laptops';
        $loc = $_GET['loc'] ?? $_POST['location_code'] ?? '';
        header("Location: index.php?view=warehouse&sector=" . urlencode($sector) . "&loc=" . urlencode($loc) . "&msg=deleted#wh-form-title");
        exit();
    }

    if ($_POST['action'] === 'add_inventory' || $_POST['action'] === 'edit_inventory') {
        $brand = $_POST['brand'];
        $model = $_POST['model'];
        $loc = $_POST['location_code'];
        $qty = (int)$_POST['quantity'];
        $sector = $_POST['sector'];
        
        // Dynamic Specs mapping based on sector
        $specs = [];
        if ($sector === 'Laptops') {
            $specs = [
                'cpu' => $_POST['cpu'] ?? '', 
                'gpu' => $_POST['gpu'] ?? '',
                'ram' => $_POST['ram'] ?? '',
                'storage' => $_POST['storage'] ?? '',
                'battery' => $_POST['battery'] ?? '',
                'windows' => $_POST['windows'] ?? '',
                'series' => $_POST['series'] ?? '',
                'gen' => $_POST['gen'] ?? '',
                'condition' => $_POST['condition'] ?? '',
                'notes' => $_POST['notes'] ?? ''
            ];
        } elseif ($sector === 'Gaming') {
            $specs = [
                'category' => $_POST['gaming_category'] ?? 'Consoles', 'series' => $_POST['series'] ?? '',
                'condition' => $_POST['condition'] ?? '', 'notes' => $_POST['notes'] ?? '',
                'ram' => $_POST['ram'] ?? '', 'storage' => $_POST['storage'] ?? '', 'cpu' => $_POST['cpu'] ?? '', 'gpu' => $_POST['gpu'] ?? ''
            ];
        } else {
            $specs = ['condition' => $_POST['condition'] ?? '', 'notes' => $_POST['notes'] ?? ''];
        }

        $specs_json = json_encode($specs);

        if ($_POST['action'] === 'edit_inventory' && isset($_POST['item_id'])) {
            $stmt = $conn_wh->prepare("UPDATE inventory SET brand=?, model=?, specs_json=?, quantity=?, last_updated_by=?, updated_at=CURRENT_TIMESTAMP WHERE id=?");
            $stmt->execute([$brand, $model, $specs_json, $qty, $current_user, $_POST['item_id']]);
        } else {
            $stmt = $conn_wh->prepare("INSERT INTO inventory (user_owner, sector, location_code, brand, model, specs_json, quantity) VALUES (?, ?, ?, ?, ?, ?, ?)");
            $stmt->execute([$current_user, $sector, $loc, $brand, $model, $specs_json, $qty]);
        }
        $msg = ($_POST['action'] === 'edit_inventory') ? 'updated' : 'added';
        header("Location: index.php?view=warehouse&sector=" . urlencode($sector) . "&loc=" . urlencode($loc) . "&msg=" . $msg . "#wh-form-title");
        exit();
    }
}

// Fetch All Unique Locations
$stmt_locs = $conn_wh->query("SELECT DISTINCT location_code FROM inventory ORDER BY location_code ASC");
$existing_locs = $stmt_locs->fetchAll(PDO::FETCH_COLUMN);

// Fetch Sectors
$sectors = $conn_wh->query("SELECT * FROM sectors")->fetchAll(PDO::FETCH_ASSOC);

// Fetch Inventory for selected sector and LOCATION
$items = [];
if ($selected_loc) {
    if ($selected_loc === 'GLOBAL') {
        $stmt_i = $conn_wh->prepare("SELECT * FROM inventory WHERE sector = ? ORDER BY id DESC");
        $stmt_i->execute([$selected_sector]);
    } else {
        $stmt_i = $conn_wh->prepare("SELECT * FROM inventory WHERE sector = ? AND location_code = ? ORDER BY id DESC");
        $stmt_i->execute([$selected_sector, $selected_loc]);
    }
    $items = $stmt_i->fetchAll(PDO::FETCH_ASSOC);
}
?>

<script>
    // Global context for warehouse logic
    window.activeSector = "<?= htmlspecialchars($selected_sector) ?>";
</script>


<div class="warehouse-container">
    <header class="warehouse-header">
        <div class="warehouse-header-main">
            <div class="warehouse-title-block">
                <h1>Warehouse Control Center</h1>
                <p class="subtitle">Managing stock and locations across all inventory sectors.</p>
            </div>
            <?php if ($selected_loc): ?>
                <div class="active-loc-display">
                    <div class="loc-label">Active Location</div>
                    <a href="index.php?view=warehouse&sector=<?= urlencode($selected_sector) ?>" class="loc-active-badge">
                        <span class="loc-pin">📍</span>
                        <span class="loc-text"><?= htmlspecialchars($selected_loc) ?></span>
                        <span class="loc-change">Change</span>
                    </a>
                </div>
            <?php endif; ?>
        </div>
    </header>

    <?php if (!$selected_loc): ?>
        <div class="location-gate">
            <div class="gate-options-container">
                <!-- OPTION 1: REGISTRATION / WORKING ZONE -->
                <div class="gate-card main-gate">
                    <h2 style="font-weight:900; margin-bottom:10px;">Select Working Zone</h2>
                    <p style="color:var(--text-secondary); margin-bottom:30px;">Choose a shelf to register or edit stock.</p>
                    
                    <div class="loc-grid">
                        <?php foreach ($existing_locs as $loc): ?>
                            <a href="index.php?view=warehouse&sector=<?= urlencode($selected_sector) ?>&loc=<?= urlencode($loc) ?>" class="loc-item">
                                <span class="loc-icon">📦</span>
                                <span class="loc-name"><?= htmlspecialchars($loc) ?></span>
                            </a>
                        <?php endforeach; ?>
                        
                        <div class="loc-item new-loc">
                            <form method="GET" action="index.php" style="width:100%;">
                                <input type="hidden" name="view" value="warehouse">
                                <input type="hidden" name="sector" value="<?= htmlspecialchars($selected_sector) ?>">
                                <input type="text" name="loc" placeholder="+ New Zone" required style="width:100%; border:none; background:transparent; text-align:center; font-weight:800; outline:none;">
                                <button type="submit" style="display:none;"></button>
                            </form>
                        </div>
                    </div>
                </div>

                <!-- OPTION 2: GLOBAL VIEWING -->
                <div class="gate-card">
                    <div style="font-size: 3.5rem; margin-bottom: 25px;">🌐</div>
                    <h2 style="font-weight:900; margin-bottom:10px;">Global Warehouse</h2>
                    <p style="color:var(--text-secondary); margin-bottom:30px;">View and search inventory across all zones at once without registration tools.</p>
                    <div style="flex: 1; display:flex; align-items:center; justify-content:center;">
                        <a href="index.php?view=warehouse&sector=<?= urlencode($selected_sector) ?>&loc=GLOBAL" 
                           style="display: block; width: 100%; padding: 20px; background: var(--text-main); color: white; border-radius: 16px; font-weight: 800; text-decoration: none; transition: 0.2s; font-size: 1.1rem; box-shadow: 0 10px 15px -3px rgba(0,0,0,0.1);">
                           Enter Global View
                        </a>
                    </div>
                </div>
            </div>
        </div>
    <?php else: ?>

    <!-- Sector Navigation -->
    <div class="sector-nav">
        <?php foreach ($sectors as $s): ?>
            <a href="index.php?view=warehouse&sector=<?= urlencode($s['name']) ?>&loc=<?= urlencode($selected_loc) ?>" 
               class="sector-card <?= $selected_sector === $s['name'] ? 'active' : '' ?>" 
               data-sector="<?= htmlspecialchars($s['name']) ?>">
                <span class="sector-icon"><?= $s['icon'] ?></span>
                <span class="sector-name"><?= htmlspecialchars($s['name']) ?></span>
            </a>
        <?php endforeach; ?>
    </div>

    <!-- Main Content Grid -->
    <div class="warehouse-layout">
        
        <!-- Inventory List -->
        <section class="inventory-feed">
            <div class="inventory-feed-header">
                <div class="inventory-summary-title">
                    <h2><?= htmlspecialchars($selected_sector) ?> Inventory</h2>
                    <?php 
                        $total_qty = 0;
                        foreach($items as $it) $total_qty += (int)($it['quantity'] ?? 0);
                    ?>
                    <div class="inventory-total-count">
                        Total Qty: <span class="count-value"><?= number_format($total_qty) ?> Units</span>
                    </div>
                </div>
                <div class="inventory-actions">
                    <div class="search-container">
                        <i class="search-icon">🔍</i>
                        <input type="text" id="wh-search" placeholder="Search items..." aria-label="Search warehouse inventory" onkeyup="filterWarehouse()" class="search-input">
                    </div>
                    <button type="button" onclick="downloadWarehouseCSV()" class="btn-export">
                        📊 Export CSV
                    </button>
                </div>
            </div>

            <div class="inventory-table-container">
                <table class="inventory-table">
                    <thead>
                        <tr>
                            <th class="col-type">Type</th>
                            <th class="col-main">Make/Model</th>
                            <th class="col-qty">QTY</th>
                            <?php if ($selected_sector === 'Laptops'): ?>
                                <th>CPU</th>
                                <th>Ram/Storage</th>
                                <th>Series</th>
                            <?php elseif ($selected_sector === 'Gaming'): ?>
                                <th>Category</th>
                                <th>CPU / GPU</th>
                                <th>RAM / Storage</th>
                            <?php endif; ?>
                            <th>Notes</th>
                            <th class="col-log">Staff Log</th>
                            <th class="col-actions"></th>
                        </tr>
                    </thead>
                    <tbody id="inventory-list">
                        <?php if (empty($items)): ?>
                            <tr>
                                <td colspan="10" style="padding: 60px; text-align: center; color: #94a3b8; font-weight: 600;">
                                    No items found in this sector.
                                </td>
                            </tr>
                        <?php else: ?>
                            <?php foreach ($items as $item): 
                                $specs = json_decode($item['specs_json'], true) ?: [];
                                $created_date = date('m/d/y', strtotime($item['created_at']));
                                $updated_date = date('m/d/y', strtotime($item['updated_at']));
                            ?>
                                <tr class="inventory-card" 
                                     data-sector-theme="<?= htmlspecialchars($item['sector']) ?>"
                                     data-brand="<?= htmlspecialchars($item['brand']) ?>"
                                     data-model="<?= htmlspecialchars($item['model']) ?>"
                                     data-specs='<?= htmlspecialchars($item['specs_json'], ENT_QUOTES) ?>'
                                     data-search="<?= htmlspecialchars(strtolower($item['brand'] . ' ' . $item['model'] . ' ' . $item['location_code'])) ?>">
                                    
                                    <td><span class="location-tag"><?= htmlspecialchars($item['location_code']) ?></span></td>
                                    
                                    <td>
                                        <div class="cell-make"><?= htmlspecialchars($item['brand']) ?></div>
                                        <div class="cell-model"><?= htmlspecialchars($item['model']) ?></div>
                                    </td>

                                    <td><span class="qty-pill"><?= (int)$item['quantity'] ?></span></td>

                                    <?php if ($selected_sector === 'Laptops'): ?>
                                        <td><div class="spec-value"><?= htmlspecialchars($specs['cpu'] ?? '-') ?></div></td>
                                        <td><div class="spec-value"><?= htmlspecialchars(($specs['ram'] ?? '-') . ' / ' . ($specs['storage'] ?? '-')) ?></div></td>
                                        <td><div class="spec-value"><?= htmlspecialchars(($specs['series'] ?? '-') . ' (' . ($specs['gen'] ?? '-') . ')') ?></div></td>
                                    <?php elseif ($selected_sector === 'Gaming'): ?>
                                        <td><div class="spec-value"><?= htmlspecialchars($specs['category'] ?? '-') ?></div></td>
                                        <td><div class="spec-value"><?= htmlspecialchars(($specs['cpu'] ?? '-') . ' / ' . ($specs['gpu'] ?? '-')) ?></div></td>
                                        <td><div class="spec-value"><?= htmlspecialchars(($specs['ram'] ?? '-') . ' / ' . ($specs['storage'] ?? '-')) ?></div></td>
                                    <?php endif; ?>

                                    <td>
                                        <div class="notes-cell-wrapper">
                                            <div class="status-row">
                                                <span class="status-badge status-<?= $item['status'] ?>"><?= $item['status'] ?></span>
                                                <span class="condition-label"><?= htmlspecialchars($specs['condition'] ?? 'Used') ?></span>
                                            </div>
                                            <div class="notes-text"><?= htmlspecialchars($specs['notes'] ?? '') ?></div>
                                        </div>
                                    </td>

                                    <td>
                                        <div class="staff-log-wrapper">
                                            <div class="log-entry">
                                                <span class="log-user">👤 <?= htmlspecialchars($item['user_owner']) ?></span>
                                                <span class="log-date">Created <?= $created_date ?></span>
                                            </div>
                                            <?php if ($item['last_updated_by']): ?>
                                                <div class="log-entry updated">
                                                    <span class="log-user">✏️ <?= htmlspecialchars($item['last_updated_by']) ?></span>
                                                    <span class="log-date">Edited <?= $updated_date ?></span>
                                                </div>
                                            <?php endif; ?>
                                        </div>
                                    </td>

                                    <td>
                                        <div class="row-actions">
                                            <button type="button" class="row-action-btn btn-edit" onclick='editWarehouseItem(<?= json_encode($item) ?>)' title="Edit Entry">📝</button>
                                            <form method="POST" action="" onsubmit="return confirm('Are you sure?');">
                                                <input type="hidden" name="action" value="delete_inventory">
                                                <input type="hidden" name="item_id" value="<?= (int)$item['id'] ?>">
                                                <input type="hidden" name="sector" value="<?= htmlspecialchars($selected_sector) ?>">
                                                <input type="hidden" name="location_code" value="<?= htmlspecialchars($selected_loc) ?>">
                                                <button type="submit" class="row-action-btn btn-delete" title="Delete Entry">🗑️</button>
                                            </form>
                                        </div>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </section>

        <!-- Add Item Sidebar (Hidden in Global View) -->
        <?php if ($selected_loc !== 'GLOBAL'): ?>
        <aside class="warehouse-sidebar">
            <div style="background: white; padding: 25px; border-radius: 20px; box-shadow: 0 4px 6px -1px rgba(0,0,0,0.05); position: sticky; top: 20px;">
                
                <h3 id="wh-form-title" style="font-weight: 800; margin-bottom: 20px;">📥 Register Stock</h3>
                <form method="POST" action="" id="wh-main-form">
                    <input type="hidden" name="action" id="wh-form-action" value="add_inventory">
                    <input type="hidden" name="item_id" id="wh-edit-id" value="">
                    <input type="hidden" name="sector" value="<?= htmlspecialchars($selected_sector) ?>">
                    
                    <div class="form-group" style="margin-bottom: 15px;">
                        <label for="wh-location-code">Location Code (Zone/Shelf)</label>
                        <input type="text" id="wh-location-code" name="location_code" value="<?= htmlspecialchars($selected_loc) ?>" readonly style="width:100%; height:42px; border-radius:10px; border:1px solid #ddd; padding: 0 12px; background:#f8fafc; color:#64748b; font-weight:700;">
                    </div>
                    
                    <div style="display: flex; gap: 10px; margin-bottom: 15px;">
                        <div class="form-group" style="flex: 1;">
                            <label for="wh-brand">Brand</label>
                            <input type="text" name="brand" list="brand-options" id="wh-brand" placeholder="Dell" required style="width:100%; height:42px; border-radius:10px; border:1px solid #ddd; padding: 0 12px;">
                            <datalist id="brand-options"></datalist>
                        </div>
                        <div class="form-group" style="flex: 1;">
                            <label for="wh-model">Model</label>
                            <input type="text" name="model" list="model-options" id="wh-model" placeholder="Latitude" required style="width:100%; height:42px; border-radius:10px; border:1px solid #ddd; padding: 0 12px;">
                            <datalist id="model-options"></datalist>
                        </div></div>
                        <?php if (isset($_GET['msg'])): ?>
                    <div style="background: #dcfce7; color: #15803d; padding: 12px 15px; border-radius: 12px; margin-bottom: 20px; font-weight: 700; font-size: 0.85rem; border: 1px solid #bef264; display: flex; align-items: center; gap: 10px; animation: slideDown 0.3s ease;">
                        <span>✅</span>
                        <span>
                            <?php 
                                if($_GET['msg'] === 'added') echo "Stock registered successfully!";
                                elseif($_GET['msg'] === 'updated') echo "Entry updated successfully!";
                                elseif($_GET['msg'] === 'deleted') echo "Entry removed from stock.";
                            ?>
                        </span>
                    </div>
                <?php endif; ?>
                    

                    <!-- Sector Specific Fields -->
                    <div id="sector-specific-fields" style="border-top: 1px dashed #eee; padding-top: 15px; margin-bottom: 15px;">
                        <?php if ($selected_sector === 'Laptops'): ?>
                            <div style="display: flex; gap: 10px; margin-bottom: 10px;">
                                <div class="form-group" style="flex: 1;">
                                    <label for="wh-spec-cpu">CPU</label>
                                    <input type="text" id="wh-spec-cpu" name="cpu" placeholder="Core i7-1185G7" style="width:100%; height:38px; border-radius:8px; border:1px solid #ddd; padding: 0 10px;">
                                </div>
                                <div class="form-group" style="flex: 1;">
                                    <label for="wh-spec-gpu">GPU</label>
                                    <input type="text" id="wh-spec-gpu" name="gpu" placeholder="Integrated / RTX" style="width:100%; height:38px; border-radius:8px; border:1px solid #ddd; padding: 0 10px;">
                                </div>
                            </div>
                            <div style="display: flex; gap: 10px; margin-bottom: 10px;">
                                <div class="form-group" style="flex: 1;">
                                    <label for="wh-spec-ram">RAM</label>
                                    <input type="text" id="wh-spec-ram" name="ram" placeholder="16GB DDR4" style="width:100%; height:38px; border-radius:8px; border:1px solid #ddd; padding: 0 10px;">
                                </div>
                                <div class="form-group" style="flex: 1;">
                                    <label for="wh-spec-storage">Storage</label>
                                    <input type="text" id="wh-spec-storage" name="storage" placeholder="512GB NVMe" style="width:100%; height:38px; border-radius:8px; border:1px solid #ddd; padding: 0 10px;">
                                </div>
                            </div>
                            <div style="display: flex; gap: 10px; margin-bottom: 10px;">
                                <div class="form-group" style="flex: 1;">
                                    <label for="wh-spec-battery">Battery Health</label>
                                    <input type="text" id="wh-spec-battery" name="battery" placeholder="85% Health" style="width:100%; height:38px; border-radius:8px; border:1px solid #ddd; padding: 0 10px;">
                                </div>
                                <div class="form-group" style="flex: 1;">
                                    <label for="wh-spec-windows">Windows Version</label>
                                    <input type="text" id="wh-spec-windows" name="windows" placeholder="Win 11 Pro" style="width:100%; height:38px; border-radius:8px; border:1px solid #ddd; padding: 0 10px;">
                                </div>
                            </div>
                            <div style="display: flex; gap: 10px; margin-bottom: 10px;">
                                <div class="form-group" style="flex: 1;">
                                    <label for="wh-spec-series">Model Number</label>
                                    <input type="text" id="wh-spec-series" name="series" required placeholder="E7450" style="width:100%; height:38px; border-radius:8px; border:1px solid #ddd; padding: 0 10px;">
                                </div>
                                <div class="form-group" style="flex: 1;">
                                    <label for="wh-spec-gen">Generation</label>
                                    <input type="text" id="wh-spec-gen" name="gen" required placeholder="11th Gen" style="width:100%; height:38px; border-radius:8px; border:1px solid #ddd; padding: 0 10px;">
                                </div>
                            </div>
                        <?php elseif ($selected_sector === 'Gaming'): ?>
                            <div class="form-group" style="margin-bottom: 10px;">
                                <label>Category</label>
                                <select name="gaming_category" id="wh-gaming-cat" onchange="toggleGamingFields()" style="width:100%; height:38px; border-radius:8px; border:1px solid #ddd; padding: 0 10px; font-weight:700;">
                                    <option value="PC">PC / Custom Build</option>
                                    <option value="Consoles">Consoles</option>
                                    <option value="Controllers">Controllers</option>
                                    <option value="Games">Games</option>
                                </select>
                            </div>

                            <!-- PC Specific -->
                            <div id="wh-gaming-pc-fields">
                                <div style="display: flex; gap: 10px; margin-bottom: 10px;">
                                    <div class="form-group" style="flex: 1;">
                                        <label>CPU</label>
                                        <input type="text" name="cpu" placeholder="Ryzen 7" style="width:100%; height:38px; border-radius:8px; border:1px solid #ddd; padding: 0 10px;">
                                    </div>
                                    <div class="form-group" style="flex: 1;">
                                        <label>GPU</label>
                                        <input type="text" name="gpu" placeholder="RTX 3070" style="width:100%; height:38px; border-radius:8px; border:1px solid #ddd; padding: 0 10px;">
                                    </div>
                                </div>
                            </div>

                            <!-- Specific Specs for everything else -->
                            <div class="form-group" style="margin-bottom: 10px;">
                                <label id="wh-gaming-spec-label">Specs / Series</label>
                                <input type="text" name="series" list="series-options" id="wh-series" placeholder="Series / Edition" style="width:100%; height:38px; border-radius:8px; border:1px solid #ddd; padding: 0 10px;">
                                <datalist id="series-options"></datalist>
                                <div id="wh-gaming-extra-specs" style="display: flex; gap: 10px; margin-top:5px;">
                                    <div class="form-group" style="flex: 1;">
                                        <input type="text" name="ram" id="wh-ram" placeholder="RAM / Color" style="width:100%; height:38px; border-radius:8px; border:1px solid #ddd; padding: 0 10px;">
                                    </div>
                                    <div class="form-group" style="flex: 1;">
                                        <input type="text" name="storage" id="wh-storage" placeholder="Storage" style="width:100%; height:38px; border-radius:8px; border:1px solid #ddd; padding: 0 10px;">
                                    </div>
                                </div>
                            </div>
                        <?php elseif ($selected_sector === 'Electronics'): ?>
                            <div class="form-group" style="margin-bottom: 10px;">
                                <label>Device Type</label>
                                <input type="text" name="type" placeholder="Charger / Hub" style="width:100%; height:38px; border-radius:8px; border:1px solid #ddd; padding: 0 10px;">
                            </div>
                            <div class="form-group" style="margin-bottom: 10px;">
                                <label>Specs / Condition</label>
                                <input type="text" name="voltage" placeholder="65W / 19.5V" style="width:100%; height:38px; border-radius:8px; border:1px solid #ddd; padding: 0 10px;">
                                <input type="text" name="condition" placeholder="New" style="width:100%; height:38px; border-radius:8px; border:1px solid #ddd; padding: 0 10px; margin-top:5px;">
                            </div>
                        <?php endif; ?>
                    </div>

                    <div style="display: flex; gap: 10px; margin-bottom: 15px;">
                        <div class="form-group" style="flex: 1;">
                            <label for="wh-condition">Condition</label>
                            <select id="wh-condition" name="condition" style="width:100%; height:42px; border-radius:10px; border:1px solid #ddd; padding: 0 12px; font-weight:700;">
                                <option value="A Grade">A Grade</option>
                                <option value="B Grade">B Grade</option>
                                <option value="C Grade">C Grade</option>
                            </select>
                        </div>
                        <div class="form-group" style="flex: 1;">
                            <label for="wh-quantity">Initial Quantity</label>
                            <input type="number" id="wh-quantity" name="quantity" value="1" min="1" required style="width:100%; height:42px; border-radius:10px; border:1px solid #ddd; padding: 0 12px; font-weight: 800;">
                        </div>
                    </div>

                    <div class="form-group" style="margin-bottom: 20px;">
                        <label for="wh-notes">Notes / Observations</label>
                        <textarea id="wh-notes" name="notes" placeholder="Any scratches or specifics..." style="width:100%; height:80px; border-radius:10px; border:1px solid #ddd; padding: 10px; font-family:inherit; resize:none;"></textarea>
                    </div>

                    <button type="submit" id="wh-submit-btn" style="width:100%; height:50px; background:var(--text-main); color:white; border:none; border-radius:14px; font-weight:800; cursor:pointer;">
                        📥 Add to Stock
                    </button>
                    <button type="button" id="wh-cancel-edit" onclick="resetWarehouseForm()" style="display:none; width:100%; margin-top:10px; background:none; border:none; color:#64748b; font-weight:700; cursor:pointer;">Cancel Edit</button>
                </form>
            </div>
        </aside>
        <?php else: ?>
        <aside class="warehouse-sidebar" style="background:#f8fafc; border:2px dashed #cbd5e1; border-radius:20px; padding:40px; text-align:center; color:#64748b;">
            <div style="font-size:2rem; margin-bottom:15px;">🚫</div>
            <h3 style="font-weight:800;">Registration Locked</h3>
            <p>You are in <b>Global View</b>. To add or edit specific stock, please select a specific <b>Working Zone</b> from the gate.</p>
            <a href="index.php?view=warehouse&sector=<?= urlencode($selected_sector) ?>" style="display:inline-block; margin-top:20px; color:var(--text-main); font-weight:800;">Back to Gate</a>
        </aside>
        <?php endif; ?>

    <?php endif; ?>
</div>

<!-- warehouse.js is now loaded globally in index.php -->
<script>
    // Ensure gaming fields are correctly toggled on load if Gaming is selected
    if (typeof toggleGamingFields === 'function') toggleGamingFields();
</script>
