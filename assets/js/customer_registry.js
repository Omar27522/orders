/**
 * IQA Metal — Customer Registry Logic
 * Handled safely with modern ES6+ standards.
 */

/**
 * @typedef {Object} Order
 * @property {string} order_id
 * @property {string} created_at
 * @property {string} status
 */

/**
 * @typedef {Object} Customer
 * @property {string} customer_id
 * @property {string} company_name
 * @property {string} website
 * @property {string} contact_person
 * @property {string} address
 * @property {string} email
 * @property {string} phone
 * @property {string} shipping_address
 * @property {string} internal_notes
 * @property {string} callback_date
 * @property {string} message_date
 * @property {Order[]} orders_list
 */

/**
 * Displays customer details in the sidebar
 * @param {HTMLElement} el
 */
function showDetails(el) {
    const cards = document.getElementsByClassName('cust-card');
    for (let c of cards) {
        c.classList.remove('active');
    }
    el.classList.add('active');

    const rawData = el.getAttribute('data-customer');
    if (!rawData) return;

    try {
        const data = JSON.parse(rawData);
        renderDetailView(data);
    } catch (e) {
        console.error("Failed to parse customer data", e);
    }
}

/**
 * Escapes HTML to prevent XSS
 * @param {string|null|undefined} str
 * @returns {string}
 */
const escapeHTML = (str) => {
    if (!str) return '—';
    return str.toString().replace(/[&<>"']/g, m => ({
        '&': '&amp;',
        '<': '&lt;',
        '>': '&gt;',
        '"': '&quot;',
        "'": '&#39;'
    }[m]));
};

/**
 * Renders the detail view in the sidebar
 * @param {Customer} data
 */
function renderDetailView(data) {
    const side = document.getElementById('side-details');
    if (!side) return;

    const drafts = (data.orders_list || []).filter(o => !['finalized', 'paid', 'dispatched'].includes(o.status.toLowerCase()));
    const history = (data.orders_list || []).filter(o => ['finalized', 'paid', 'dispatched'].includes(o.status.toLowerCase()));

    side.innerHTML = `
        <div class="detail-box">
            <div style="display:flex; justify-content:space-between; align-items:flex-start; margin-bottom: 20px;">
                <div class="detail-item" style="margin:0;">
                    <div class="detail-label">Full Company Name</div>
                    <div class="detail-value text-main" style="font-size: 1.25rem;">${escapeHTML(data.company_name)}</div>
                    <div style="margin-top: 6px; font-size: 0.7rem; font-family: monospace; background: #f1f5f9; color: #475569; padding: 3px 8px; border-radius: 6px; display: inline-block; font-weight: 700; letter-spacing: 0.05em;">${escapeHTML(data.customer_id)}</div>
                </div>
                <button onclick='handleEditClick(${JSON.stringify(data).replace(/'/g, "&apos;")})' class="btn-view-cust" title="Edit Account">✎</button>
            </div>

            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 20px; border-bottom: 1px dashed var(--border-color); padding-bottom: 15px; margin-bottom: 20px;">
                <div class="detail-item" style="margin:0;">
                    <div class="detail-label" style="display: flex; align-items: center; gap: 5px;">📅 Next Callback</div>
                    <div class="detail-value" style="font-weight: 700; color: var(--accent-color);">${data.callback_date ? escapeHTML(data.callback_date) : 'Not Set'}</div>
                </div>
                <div class="detail-item" style="margin:0;">
                    <div class="detail-label" style="display: flex; align-items: center; gap: 5px;">✉️ Last Message Date</div>
                    <div class="detail-value" style="font-weight: 700;">${data.message_date ? escapeHTML(data.message_date) : 'Not Set'}</div>
                </div>
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
                    ${drafts.length > 0 ? drafts.map(o => `
                        <a href="index.php?customer_id=${encodeURIComponent(data.customer_id)}&order_id=${encodeURIComponent(o.order_id)}"
                           class="order-row-link">
                            <div>
                                <div style="font-weight: 700; font-size: 0.9rem;">Batch: ${o.created_at}</div>
                            </div>
                            <span class="qty-chip" style="font-size: 0.7rem; background: #fffbeb; color: #92400e; box-shadow: none;">Resume →</span>
                        </a>
                    `).join('') : '<div class="empty-state" style="padding: 10px; font-size: 0.75rem; color: #94a3b8;">No draft orders.</div>'}
                </div>
            </div>

            <div class="detail-item">
                <div class="detail-label">Completed / History</div>
                <div id="side-completed" style="display: flex; flex-direction: column; gap: 8px; margin-bottom: 20px;">
                    ${history.length > 0 ? history.map(o => `
                        <a href="checkout.php?customer_id=${encodeURIComponent(data.customer_id)}&order_id=${encodeURIComponent(o.order_id)}"
                           class="order-row-link completed">
                            <div>
                                <div style="font-weight: 600; font-size: 0.85rem; color: #64748b;">Batch: ${o.created_at}</div>
                            </div>
                            <span class="qty-chip" style="font-size: 0.7rem; background: #f1f5f9; color: #475569; box-shadow: none;">Modify</span>
                        </a>
                    `).join('') : '<div class="empty-state" style="padding: 10px; font-size: 0.75rem; color: #94a3b8;">No completion history.</div>'}
                </div>
            </div>

            <div class="detail-item" style="background: #f8fafc; padding: 15px; border-radius: 12px; margin-bottom: 20px; border: 1px solid var(--border-color);">
                <div class="detail-label" style="display: flex; align-items: center; gap: 5px;">📝 CRM Notes</div>
                <div class="detail-value" style="font-size: 0.85rem; white-space: pre-wrap; color: var(--text-secondary); line-height: 1.5;">${data.internal_notes ? escapeHTML(data.internal_notes) : '<i style="opacity:0.5;">No notes recorded for this customer.</i>'}</div>
            </div>

            <div style="padding-top: 10px; border-top: 1px dashed var(--border-color); margin-top: 20px;">
                <a href="index.php?customer_id=${encodeURIComponent(data.customer_id)}&action=create_new_order" class="btn-main" style="text-decoration:none; display:flex; align-items:center; justify-content:center; padding: 16px; border-radius: 12px; background: var(--accent-color); color: white; font-weight: 800; text-align: center; gap: 8px; box-shadow: 0 10px 15px -3px rgba(140, 198, 63, 0.3);">
                    <span>+</span> Start New Fresh Order
                </a>
            </div>
        </div>
    `;
}

/**
 * Intermediary to avoid nested inline JSON issues
 * @param {Customer} data
 */
function handleEditClick(data) {
    renderEditView(data);
}

/**
 * Renders the edit view in the sidebar
 * @param {Customer} data
 */
function renderEditView(data) {
    const side = document.getElementById('side-details');
    if (!side) return;

    side.innerHTML = `
        <form method="POST" class="detail-box">
            <input type="hidden" name="action" value="edit_customer">
            <input type="hidden" name="customer_id" value="${data.customer_id}">

            <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom: 20px;">
                <h3 style="font-size:0.8rem; text-transform:uppercase; color:var(--text-secondary); letter-spacing:0.1em; font-weight:800;">Edit Account Details</h3>
                <button type="button" onclick='handleCancelEdit(${JSON.stringify(data).replace(/'/g, "&apos;")})' class="btn-view-cust">✖</button>
            </div>

            <div class="form-group" style="margin-bottom:12px;">
                <label for="edit-company-name">Company Name</label>
                <input type="text" id="edit-company-name" name="company_name" value="${escapeHTML(data.company_name)}" style="height:38px; font-size:0.85rem;" required>
            </div>

            <div style="display:grid; grid-template-columns: 1fr 1fr; gap: 12px; margin-bottom:12px;">
                <div class="form-group">
                    <label for="edit-contact">Contact Person</label>
                    <input type="text" id="edit-contact" name="contact_person" value="${escapeHTML(data.contact_person)}" style="height:38px; font-size:0.85rem;">
                </div>
                <div class="form-group">
                    <label for="edit-website">Website</label>
                    <input type="text" id="edit-website" name="website" value="${escapeHTML(data.website)}" style="height:38px; font-size:0.85rem;">
                </div>
            </div>

            <div style="display:grid; grid-template-columns: 1fr 1fr; gap: 12px; margin-bottom:12px;">
                <div class="form-group">
                    <label for="edit-email">Email</label>
                    <input type="email" id="edit-email" name="email" value="${escapeHTML(data.email)}" style="height:38px; font-size:0.85rem;">
                </div>
                <div class="form-group">
                    <label for="edit-phone">Phone</label>
                    <input type="text" id="edit-phone" name="phone" value="${escapeHTML(data.phone)}" style="height:38px; font-size:0.85rem;">
                </div>
            </div>

            <div style="display:grid; grid-template-columns: 1fr 1fr; gap: 12px; margin-bottom:12px;">
                <div class="form-group">
                    <label for="edit-callback">Next Callback</label>
                    <input type="date" id="edit-callback" name="callback_date" value="${escapeHTML(data.callback_date)}" style="height:38px; font-size:0.85rem; padding-right:10px;">
                </div>
                <div class="form-group">
                    <label for="edit-msg-date">Last Message Date</label>
                    <input type="date" id="edit-msg-date" name="message_date" value="${escapeHTML(data.message_date)}" style="height:38px; font-size:0.85rem; padding-right:10px;">
                </div>
            </div>

            <div class="form-group" style="margin-bottom:12px;">
                <label for="edit-address">Business Address</label>
                <input type="text" id="edit-address" name="address" value="${escapeHTML(data.address)}" style="height:38px; font-size:0.85rem;">
            </div>

            <div class="form-group" style="margin-bottom:12px;">
                <label for="edit-ship-addr">Shipping Address</label>
                <input type="text" id="edit-ship-addr" name="shipping_address" value="${escapeHTML(data.shipping_address)}" style="height:38px; font-size:0.85rem;">
            </div>

            <div class="form-group" style="margin-bottom:20px;">
                <label for="edit-notes">Internal Notes</label>
                <textarea id="edit-notes" name="internal_notes" class="detail-notes" style="width:100%; min-height:80px;">${escapeHTML(data.internal_notes)}</textarea>
            </div>

            <button type="submit" class="btn-main" style="width:100%; padding:14px; border-radius:12px; background:var(--text-main); color:white; font-weight:800; border:none; cursor:pointer;">💾 Save Account Changes</button>
        </form>
    `;
}

/**
 * Handles cancelling the edit view
 * @param {Customer} data
 */
function handleCancelEdit(data) {
    renderDetailView(data);
}

/**
 * Filters the customer list based on search input
 */
function filterCustomers() {
    const input = document.getElementById('cust-search');
    if (!input) return;

    const filter = input.value.toLowerCase();
    const cards = document.getElementsByClassName('cust-card');

    for (let i = 0; i < cards.length; i++) {
        const search = cards[i].getAttribute('data-search')?.toLowerCase() || "";
        cards[i].style.display = search.includes(filter) ? "" : "none";
    }
}
