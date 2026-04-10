# 📦 IQA Metal: Multi-Order Management System
## 📋 Project Overview
A robust, high-performance order procurement application designed for building and managing complex inventory batches. Optimized for both rapid warehouse intake on mobile devices and professional desktop manifest generation.

---

## 🚀 Core Features

### 1. Active Customer Registry
Manage your entire B2B database from a centralized dashboard.
- **Smart Search**: Instantly filter customers by name or ID.
- **Dynamic Detail View**: Select a customer to see their order history, active drafts, and account details.
- **In-Place Editing**: Update company profiles without navigating away.

### 2. Batch Builder (Order Entry) — `pages/new_order.php`
The central tool for adding hardware to active orders.
- **Dynamic Logic**: Intelligent dropdowns that filter models and series based on the selected brand (Dell, HP, Apple, etc.).
- **Live Search**: Real-time search bar in the Order Summary panel filters all added items instantly without a page reload.
- **Inline Editing**: Each item row includes a ✏️ pencil icon to toggle Qty and Unit Price edit fields, with auto-submit on change and a smart `#order-summary` anchor on reload so the viewport never jumps.
- **No Limit**: The full order is always displayed (the previous 20-item cap has been removed), so the search box always scans the complete manifest.
- **Optimized for Mobile**: Touch-friendly inputs enforcing `16px` font sizes to prevent iOS Safari auto-zoom.

### 3. Verification & Checkout — `checkout.php`
The final stage before manifest delivery.
- **Live Manifest Search**: A flexible search bar below the "Final Batch Verification" header filters all item rows in real time.
- **Interactive Row Editing**: Click any item row to open a premium glassmorphism **Edit Item** modal with animated slide-up/scale-in transitions.
  - Full metadata editing: Brand, Model, Series, CPU/Gen, Condition/Comments, Qty, Unit Price.
  - **AJAX Live Sync**: Changes are persisted and immediately reflected in the main table UI without a page reload, preserving unsaved changes in other manifest rows.
  - **🖨️ Print Label (.odt)**: Generate a 2"×1" Thermal Label as a standards-compliant OpenDocument Text file directly from the modal.
- **Adaptive Layout**: Tables transform into mobile-friendly cards on small screens to prevent horizontal scrolling.
- **Live Recalculation**: Subtotals and grand totals update instantly as you adjust quantities or pricing.
- **Export Formats**:
  - 🖨 **Print Manifest**: Professional PDF-ready layout with approval signature lines.
  - 📊 **CSV Export**: Clean, Excel-ready data distribution with separate columns for Brand and Model.

### 4. ODT Label Generation — `generate_odt.php`
A standalone PHP label printer requiring zero external dependencies.
- **No ZipArchive needed**: Uses the Flat OpenDocument (FODT) XML format — a single-file XML structure that LibreOffice and compatible word processors open natively.
- **2"×1" Page Dimensions**: Hardcoded for thermal label printers.
- **Typography**: 16pt Times New Roman bold title (wrapped across up to 3 lines) + 7pt Arial bold technical specs.
- **Triggered from**: The Edit Item modal in `checkout.php`.

### 5. Global Batch Registry — `pages/orders.php`
- **Live Search**: Filter all batches by Order ID, Company Name, or Customer ID.
- **Status Management**: Update fulfillment state (Active → Pending → Paid → Dispatched → Finalized) via an inline dropdown with AJAX submission.

### 6. System Settings
Administrative control panel for system-wide configuration.
- **Staff Management**: Add or remove access for employees.
- **Invoice Signatures**: Define the "Approved By" name used on official documents.
- **Maintenance Tools**: One-click cleanup of inactive customers with zero orders.

---

## 🛠 Technical Architecture

| Component | Technology |
| :--- | :--- |
| **Backend** | PHP 8.x (XAMPP Environment) |
| **Database** | SQLite 3 (Distributed: `customers.db`, `orders.db`, `users.db`) |
| **Frontend Logic** | Vanilla JavaScript — all checkout logic lives in `assets/js/checkout.js` |
| **Styling** | Modern Vanilla CSS with CSS Variables, glassmorphism, and mobile-first flex layouts |

### Mobile-First & iOS Safari Optimizations
- **16px Input Enforcement**: All modal inputs use `font-size: 16px !important` to prevent iOS Safari auto-zoom on focus.
- **Dynamic Viewport Height**: Overlay uses `100dvh` so it fits correctly behind Safari's collapsible toolbar.
- **Momentum Scrolling**: Modal content uses `-webkit-overflow-scrolling: touch` for native-feel scrolling when the keyboard appears.
- **Clipboard Fallback**: Copy-to-clipboard uses `navigator.clipboard` with a hidden `<textarea>` fallback for non-HTTPS contexts (older Safari).

### JS Architecture
- **PHP Data Injection (inline)**: `checkout.php` injects `rawItems`, `customerName`, `orderID`, and `orderDate` as `var` variables to ensure they are available on the `window` object for the external script.
- **External Logic File**: All function definitions live in `assets/js/checkout.js` for clean separation of concerns and browser caching.

---

## 📂 Project Structure

```bash
├── assets/
│   ├── db/            # SQLite databases (Critical Data — gitignored)
│   ├── js/
│   │   ├── checkout.js   # All checkout manifest JS logic
│   │   └── new_order.js  # Batch builder JS (compiled from TS)
│   ├── styles/        # Per-page CSS files
│   └── ts/            # TypeScript source (Legacy/Dev reference)
├── core/              # Authentication and shared logic
│   ├── auth.php
│   ├── login.php
│   └── logout.php
├── pages/             # Page fragments included by index.php
│   ├── customer_registry.php
│   ├── new_customer.php
│   ├── new_order.php
│   ├── orders.php
│   └── settings.php
├── DOCS/
│   └── DOCUMENTATION.md
├── index.php          # Main application gateway & router
├── checkout.php       # Verification, modal editor & export hub
├── generate_odt.php   # 2×1 Thermal Label ODT generator
└── README.md
```

---

## ⚙️ Maintenance & Troubleshooting

### Database Cleanup
To keep the registry tidy, admins can use the **Cleanup Tool** in the Settings menu. This utility cross-references the `customers` and `orders` databases to identify and safely remove profiles that have never been used in a transaction.

### Label Generation
`generate_odt.php` uses PHP's native `file_put_contents()` and `tempnam()` — no extensions required. If a label downloads but doesn't open, ensure LibreOffice or compatible software is set as the default handler for `.odt` files on your device.

### Critical Paths
The application utilizes `realpath()` for all database attachments. This ensures that the SQLite `ATTACH DATABASE` commands resolve correctly, even when pages are included deep within the folder hierarchy.

### iOS Safari Notes
- The app is fully tested on Safari for iOS. Ensure the server is accessed over HTTPS or localhost for the `navigator.clipboard` API to be available; otherwise the fallback `execCommand('copy')` is used automatically.
