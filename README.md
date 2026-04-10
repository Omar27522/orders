# 🚀 IQA Dynamic Order & Customer Manager

A modern, responsive web application for managing hardware inventory and customer orders. Built with a focus on speed, security, and a premium "app-like" user experience — fully optimized for iOS Safari and desktop.

---

## ✨ Key Features

-   **B2B Manifest Generation**: Creates final, professional billing summaries with auto-calculated totals and unit prices.
-   **CSV Portable Exports**: Instant "IQA Metal B2B Purchase Form" export with dedicated columns for Brand and Model, compatible with Excel and LibreOffice.
-   **ODT Thermal Label Printing**: Generate compliant 2"×1" `.odt` labels from the Edit Item modal — no external dependencies required.
-   **Fulfillment Tracking**: A Global Batch Registry to track orders through lifecycle states (Active, Pending, Paid, Dispatched, Finalized).
-   **Live Search Everywhere**: Flexible client-side search bars in the Order Builder Summary, Checkout Manifest, and Batch Registry.
-   **Interactive Checkout Modal**: Click any manifest row to open a premium glassmorphism Edit Item modal with AJAX **Live Sync** (no page reload) and Print Label support.
-   **Inline Quick-Edit**: Pencil icon on the Order Summary allows direct Qty/Price edits with smart page-anchor scrolling on reload.
-   **Technical CPU Tracking**: Specialized hardware categorization from 2nd Gen through 12th Gen for high-volume entry.
-   **Two-Phase Workflow**: Clean separation between customer management and order entry.
-   **Full Customer CRM**: Manage company specifics, addresses, and internal notes.
-   **Anti-Refresh Pattern (PRG)**: Implements the **Post/Redirect/Get** pattern for zero-error form submissions.
-   **Zero-Config Backend**: Utilizes **SQLite** — completely portable, no server setup required.
-   **iOS Safari Optimized**: `16px` input enforcement, `100dvh` viewport fix, `-webkit-overflow-scrolling: touch`, and clipboard fallback.

---

## 🛠️ Technology Stack

| Layer | Technology |
| :--- | :--- |
| **Backend** | PHP 8+ with PDO (SQLite Driver) |
| **Database** | SQLite v3 (`customers.db`, `orders.db`) |
| **Frontend UI** | Modern HTML5 & Vanilla CSS (glassmorphism, CSS Variables) |
| **Logic** | Vanilla JavaScript — split between `checkout.js` and `new_order.js` |
| **State** | PHP Sessions for secure messaging and PRG flow |

---

## 📂 Project Structure

```text
├── index.php                   # Main application entry point & router
├── checkout.php                # Finalized B2B manifest, modal editor & export hub
├── generate_odt.php            # 2×1 Thermal Label ODT generator (Flat XML, no ZipArchive)
├── pages/
│   ├── customer_registry.php   # Customer list, search, and selection UI
│   ├── new_customer.php        # Detailed customer registration module
│   ├── new_order.php           # Core hardware intake, search & inline summary editing
│   ├── orders.php              # Global batch fulfillment registry
│   └── settings.php           # Admin controls, staff management, maintenance tools
├── core/
│   ├── auth.php                # Session guard
│   ├── login.php               # Login form
│   └── logout.php             # Session destroyer
├── assets/
│   ├── styles/
│   │   ├── style.css           # Universal design system tokens & base styles
│   │   ├── checkout.css        # Manifest layout, modal animations & iOS fixes
│   │   ├── orders.css          # Batch registry & card grid styles
│   │   ├── customer_registry.css # Account list & sidebar styles
│   │   └── new_order.css       # Batch builder specific styling
│   ├── js/
│   │   ├── checkout.js         # All checkout manifest JS logic (externalized)
│   │   └── new_order.js        # Hardware inventory & UI population logic
│   ├── ts/                     # TypeScript source (legacy/dev reference)
│   └── db/
│       ├── customers.db        # SQLite: customer records (gitignored)
│       └── orders.db           # SQLite: hardware orders & items (gitignored)
├── DOCS/
│   └── DOCUMENTATION.md        # Full technical documentation
└── README.md
```

---

## 🚀 Getting Started

### 1. Requirements
-   A local PHP server (XAMPP, WAMP, Laragon, or `php -S localhost:8000`).
-   SQLite3 extension enabled in your `php.ini`.
-   **No ZipArchive needed** — label generation uses the Flat XML ODT format.

### 2. Installation
1.  Clone or download this repository to your `htdocs` or public directory.
2.  Ensure the `assets/db/` directory has **write permissions** (necessary for SQLite to generate and update database files).
3.  Open your browser and navigate to the project URL (e.g., `http://localhost/orders/`).

### 3. Usage
1.  **Register a Customer**: Start by adding a new company in the registration view.
2.  **Select Customer**: Pick an active customer from the Registry searchable list.
3.  **Build Order**: Add hardware specifications on the left; view and search the live summary on the right. Use the ✏️ icon to quickly adjust Qty/Price inline.
4.  **Checkout**: Review the full manifest, use the search bar to locate specific items, click any row to edit full metadata or print a label.
5.  **Finalize**: Save changes and finalize the batch to mark it complete.

---

## 🔧 Maintenance

-   **Database**: The `.db` files are in `assets/db/`. Open with any SQLite browser for manual audit.
-   **Styling**: All design tokens (colors, spacing, shadows) are CSS Variables in `:root` inside `style.css`.
-   **JavaScript**: Checkout logic is fully externalized to `assets/js/checkout.js`. PHP data is injected inline as `var` variables (ensuring `window` scope access) before the external script loads.
-   **Label Printing**: `generate_odt.php` produces Flat OpenDocument XML. If labels don't open, ensure LibreOffice is set as the default `.odt` handler.

---

> [!TIP]
> Built with ❤️ for speed and reliability. For developer support, refer to `DOCS/DOCUMENTATION.md` and inline source comments.
