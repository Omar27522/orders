/**
 * IQA Metal — Orders Registry Logic
 */

/**
 * Filters the global batch list based on search input
 */
function filterOrders() {
    const input = document.getElementById('order-search');
    if (!input) return;

    const filter = input.value.toLowerCase();
    const cards = document.getElementsByClassName('order-card');
    let hasResults = false;

    for (let i = 0; i < cards.length; i++) {
        const searchBlob = cards[i].getAttribute('data-search') || "";
        if (searchBlob.includes(filter)) {
            cards[i].style.display = "";
            hasResults = true;
        } else {
            cards[i].style.display = "none";
        }
    }

    // Handle empty state during search
    let emptyState = document.querySelector('.orders-empty-state');
    const grid = document.getElementById('orders-grid');

    if (!hasResults) {
        if (!emptyState && grid) {
            emptyState = document.createElement('div');
            emptyState.className = 'orders-empty-state';
            emptyState.style.cssText = 'grid-column: 1/-1; padding: 60px; text-align: center; background: white; border-radius: 20px; border: 2px dashed #eee; color: #94a3b8; font-weight: 600;';
            grid.appendChild(emptyState);
        }
        
        if (emptyState) {
            emptyState.style.display = 'block';
            emptyState.innerText = `No batches found matching "${input.value}"`;
        }
    } else if (emptyState) {
        emptyState.style.display = 'none';
    }
}
