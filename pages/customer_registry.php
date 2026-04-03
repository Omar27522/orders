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

    if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['action']) && $_POST['action'] === 'edit_customer') {
        $stmt = $conn->prepare("UPDATE customers SET
            company_name = ?, website = ?, contact_person = ?, address = ?,
            email = ?, phone = ?, shipping_address = ?, internal_notes = ?
            WHERE customer_id = ?");
        $stmt->execute([
            $_POST['company_name'], $_POST['website'], $_POST['contact_person'], $_POST['address'],
            $_POST['email'], $_POST['phone'], $_POST['shipping_address'], $_POST['internal_notes'],
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
<link rel="stylesheet" href="assets/styles/customer_registry.css">

<div class="form-side">
    <header>
        <h1>Active Customers</h1>
        <p class="subtitle">Select a customer below or register a new one to begin.</p>
    </header>

    <div style="margin-bottom: 30px;">
        <a href="index.php?view=register" class="btn-main" style="text-decoration:none; display:inline-block; padding: 12px 24px; border-radius: 12px; background: var(--accent-color); color: white; font-weight: 700; width: 100%; text-align: center;">+ Register New Customer</a>
    </div>

    <div class="search-box" style="margin-bottom: 20px;">
        <input type="text" id="cust-search" placeholder="Search by name or ID..." style="height: 40px; font-size: 0.9rem;" onkeyup="filterCustomers()">
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

        if (count($customers) > 0) {
            foreach($customers as $c) {
                $json_data = htmlspecialchars(json_encode($c), ENT_QUOTES, 'UTF-8');
                echo "<div class='cust-card' onclick='showDetails(this)' data-customer='{$json_data}' data-search='" . htmlspecialchars($c['company_name'] . " " . $c['customer_id']) . "' style='margin-bottom:0;'>
                        <div class='cust-main'>
                            <div class='cust-name'>" . htmlspecialchars($c['company_name']) . "</div>
                            <div style='display:flex; align-items:center; gap: 8px;'>
                                <div style='font-size: 0.7rem; background: #f0fdf4; color:#166534; padding:2px 6px; border-radius:10px; font-weight:700;'>{$c['completed_count']} Completed Orders</div>
                                " . ($c['active_count'] > 0 ? "<div style='font-size: 0.7rem; background: #fffbeb; color:#92400e; padding:2px 6px; border-radius:10px; font-weight:700;'>{$c['active_count']} Drafts</div>" : "") . "
                            </div>
                        </div>
                        <div class='card-actions'>
                            <a href='#customer-details' class='btn-view-cust' title='View Details' onclick='event.stopPropagation(); showDetails(this.closest(\".cust-card\"));'>👁</a>
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

<script>
function showDetails(el) {
    const cards = document.getElementsByClassName('cust-card');
    for(let c of cards) c.classList.remove('active');
    el.classList.add('active');
    const data = JSON.parse(el.getAttribute('data-customer'));
    renderDetailView(data);
}

const escapeHTML = (str) => {
    if (!str) return '—';
    return str.toString().replace(/[&<>"']/g, m => ({'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#39;'}[m]));
};

function renderDetailView(data) {
    const side = document.getElementById('side-details');
    side.innerHTML = `
        <div class="detail-box">
            <div style="display:flex; justify-content:space-between; align-items:flex-start; margin-bottom: 20px;">
                <div class="detail-item" style="margin:0;">
                    <div class="detail-label">Full Company Name</div>
                    <div class="detail-value text-main" style="font-size: 1.25rem;">${escapeHTML(data.company_name)}</div>
                    <div style="margin-top: 6px; font-size: 0.7rem; font-family: monospace; background: #f1f5f9; color: #475569; padding: 3px 8px; border-radius: 6px; display: inline-block; font-weight: 700; letter-spacing: 0.05em;">${escapeHTML(data.customer_id)}</div>
                </div>
                <button onclick='renderEditView(${JSON.stringify(data).replace(/'/g, "&apos;")})' class="btn-view-cust" title="Edit Account">✎</button>
            </div>

            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 20px;">
                <div class="detail-item">
                    <div class="detail-label">Contact Person</div>
                    <div class="detail-value">${escapeHTML(data.contact_person)}</div>
                </div>
                <div class="detail-item">
                    <div class="detail-label">Website</div>
                    <div class="detail-value">${data.website ? `<a href="${escapeHTML(data.website)}" target="_blank" style="color: var(--accent-color); text-decoration:none;">Visit</a>` : '—'}</div>
                </div>
            </div>

            <div class="detail-item">
                <div class="detail-label">Draft / Open Batches</div>
                <div id="side-drafts" style="display: flex; flex-direction: column; gap: 8px; margin-bottom: 20px;">
                    ${data.orders_list && data.orders_list.filter(o => !['finalized','paid','dispatched'].includes(o.status.toLowerCase())).length > 0 ?
                        data.orders_list.filter(o => !['finalized','paid','dispatched'].includes(o.status.toLowerCase())).map(o => `
                            <a href="index.php?customer_id=${encodeURIComponent(data.customer_id)}&order_id=${encodeURIComponent(o.order_id)}"
                               class="order-row-link">
                                <div>
                                    <div style="font-weight: 700; font-size: 0.9rem;">Batch: ${o.created_at}</div>
                                </div>
                                <span class="qty-chip" style="font-size: 0.7rem; background: #fffbeb; color: #92400e; box-shadow: none;">Resume →</span>
                            </a>
                        `).join('') :
                        '<div class="empty-state" style="padding: 10px; font-size: 0.75rem; color: #94a3b8;">No draft orders.</div>'
                    }
                </div>
            </div>

            <div class="detail-item">
                <div class="detail-label">Completed / History</div>
                <div id="side-completed" style="display: flex; flex-direction: column; gap: 8px; margin-bottom: 20px;">
                    ${data.orders_list && data.orders_list.filter(o => ['finalized','paid','dispatched'].includes(o.status.toLowerCase())).length > 0 ?
                        data.orders_list.filter(o => ['finalized','paid','dispatched'].includes(o.status.toLowerCase())).map(o => `
                            <a href="checkout.php?customer_id=${encodeURIComponent(data.customer_id)}&order_id=${encodeURIComponent(o.order_id)}"
                               class="order-row-link completed">
                                <div>
                                    <div style="font-weight: 600; font-size: 0.85rem; color: #64748b;">Batch: ${o.created_at}</div>
                                </div>
                                <span class="qty-chip" style="font-size: 0.7rem; background: #f1f5f9; color: #475569; box-shadow: none;">Modify</span>
                            </a>
                        `).join('') :
                        '<div class="empty-state" style="padding: 10px; font-size: 0.75rem; color: #94a3b8;">No completion history.</div>'
                    }
                </div>
            </div>

            <div style="padding-top: 10px; border-top: 1px dashed var(--border-color); margin-top: 20px;">
                <a href="index.php?customer_id=${encodeURIComponent(data.customer_id)}&action=create_new_order" class="btn-main" style="text-decoration:none; display:flex; align-items:center; justify-content:center; padding: 16px; border-radius: 12px; background: var(--accent-color); color: white; font-weight: 800; text-align: center; gap: 8px; box-shadow: 0 10px 15px -3px rgba(140, 198, 63, 0.3);">
                    <span>+</span> Start New Fresh Order
                </a>
            </div>
        </div>
    `;
}

function renderEditView(data) {
    const side = document.getElementById('side-details');
    side.innerHTML = `
        <form method="POST" class="detail-box">
            <input type="hidden" name="action" value="edit_customer">
            <input type="hidden" name="customer_id" value="${data.customer_id}">

            <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom: 20px;">
                <h3 style="font-size:0.8rem; text-transform:uppercase; color:var(--text-secondary); letter-spacing:0.1em; font-weight:800;">Edit Account Details</h3>
                <button type="button" onclick='renderDetailView(${JSON.stringify(data).replace(/'/g, "&apos;")})' class="btn-view-cust">✖</button>
            </div>

            <div class="form-group" style="margin-bottom:12px;">
                <label>Company Name</label>
                <input type="text" name="company_name" value="${escapeHTML(data.company_name)}" style="height:38px; font-size:0.85rem;" required>
            </div>

            <div style="display:grid; grid-template-columns: 1fr 1fr; gap: 12px; margin-bottom:12px;">
                <div class="form-group">
                    <label>Contact Person</label>
                    <input type="text" name="contact_person" value="${escapeHTML(data.contact_person)}" style="height:38px; font-size:0.85rem;">
                </div>
                <div class="form-group">
                    <label>Website</label>
                    <input type="text" name="website" value="${escapeHTML(data.website)}" style="height:38px; font-size:0.85rem;">
                </div>
            </div>

            <div style="display:grid; grid-template-columns: 1fr 1fr; gap: 12px; margin-bottom:12px;">
                <div class="form-group">
                    <label>Email</label>
                    <input type="email" name="email" value="${escapeHTML(data.email)}" style="height:38px; font-size:0.85rem;">
                </div>
                <div class="form-group">
                    <label>Phone</label>
                    <input type="text" name="phone" value="${escapeHTML(data.phone)}" style="height:38px; font-size:0.85rem;">
                </div>
            </div>

            <div class="form-group" style="margin-bottom:12px;">
                <label>Business Address</label>
                <input type="text" name="address" value="${escapeHTML(data.address)}" style="height:38px; font-size:0.85rem;">
            </div>

            <div class="form-group" style="margin-bottom:12px;">
                <label>Shipping Address</label>
                <input type="text" name="shipping_address" value="${escapeHTML(data.shipping_address)}" style="height:38px; font-size:0.85rem;">
            </div>

            <div class="form-group" style="margin-bottom:20px;">
                <label>Internal Notes</label>
                <textarea name="internal_notes" class="detail-notes" style="width:100%; min-height:80px;">${escapeHTML(data.internal_notes)}</textarea>
            </div>

            <button type="submit" class="btn-main" style="width:100%; padding:14px; border-radius:12px; background:var(--text-main); color:white; font-weight:800; border:none; cursor:pointer;">💾 Save Account Changes</button>
        </form>
    `;
}

function filterCustomers() {
    let input = document.getElementById('cust-search');
    let filter = input.value.toLowerCase();
    let cards = document.getElementsByClassName('cust-card');

    for (let i = 0; i < cards.length; i++) {
        let search = cards[i].getAttribute('data-search').toLowerCase();
        cards[i].style.display = search.includes(filter) ? "" : "none";
    }
}
</script>
