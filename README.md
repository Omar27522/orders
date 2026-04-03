# 🚀 IQA Dynamic Order & Customer Manager

A modern, responsive web application for managing hardware hardware inventory and customer orders. Built with a focus on speed, security, and a premium "app-like" user experience.

---

## ✨ Key Features

-   **B2B Manifest Generation**: Creates final, professional billing summaries with auto-calculated totals and unit prices.
-   **CSV Portable Exports**: Instant "IQA Metal B2B Purchase Form" export compatible with Excel and LibreOffice.
-   **Fulfillment Tracking**: A Global Batch Registry to track orders through lifecycle states (Pending, Paid, Dispatched, Finalized).
-   **Technical CPU Tracking**: Specialized hardware categorization from 2nd Gen through 12th Gen for high-volume entry.
-   **Two-Phase Workflow**: Clean separation between customer management and order entry.
-   **Dynamic Order Builder**: Add items with real-time visual summaries and sidebar tracking.
-   **Full Customer CRM**: Manage company specifics, addresses, and internal notes.
-   **Anti-Refresh Pattern (PRG)**: Implements the **Post/Redirect/Get** pattern for zero-error form submissions.
-   **Zero-Config Backend**: Utilizes **SQLite**—completely portable, no server setup required.
-   **Pro Design**: Split-column responsive layout with custom external stylesheets and branding icons.

---

## 🛠️ Technology Stack

| Layer | Technology |
| :--- | :--- |
| **Backend** | PHP 8+ with PDO (SQLite Driver) |
| **Database** | SQLite v3 |
| **Frontend UI** | Modern HTML5 & Vanilla CSS |
| **Logic** | TypeScript (Native browser support via Babel Standalone) |
| **State** | PHP Sessions for secure messaging and PRG flow |

---

## 📂 Project Structure

```text
├── index.php                 # Main application entry point & router
├── customer_registry.php     # Customer list, search, and selection UI
├── new_customer.php          # Detailed customer registration module
├── new_order.php             # Core hardware intake & order summary logic
├── orders.php                # Global batch fulfillment registry
├── checkout.php              # Finalized B2B manifest & billing summary
├── assets/
│   ├── styles/
│   │   ├── style.css         # Universal design system tokens
│   │   ├── checkout.css      # Manifest & print-ready layout styles
│   │   ├── orders.css        # Batch registry & card grid styles
│   │   ├── customer_registry.css# Account list & sidebar styles
│   │   └── new_order.css     # Batch builder specific styling
│   ├── ts/
│   │   └── new_order.ts      # Hardware inventory & UI population logic
│   ├── db/
│   │   ├── customers.db      # SQLite database for customer records (ignored by git)
│   │   └── orders.db         # SQLite database for hardware orders (ignored by git)
│   └── icon/
│       └── [logo].png        # Professional site icon
└── README.md                 # Project documentation
```

---

## 🚀 Getting Started

### 1. Requirements
-   A local PHP server (XAMPP, WAMP, Laragon, or `php -S localhost:8000`).
-   SQLite3 extension enabled in your `php.ini`.

### 2. Installation
1.  Clone or download this repository to your `htdocs` or public directory.
2.  Ensure the `assets/db/` directory has **write permissions** (necessary for SQLite to generate and update database files).
3.  Open your browser and navigate to the project URL (e.g., `http://localhost/orders/`).

### 3. Usage
1.  **Register a Customer**: Start by adding a new company in the registration view.
2.  **Select Customer**: Pick an active customer from the Registry searchable list.
3.  **Build Order**: Add hardware specifications on the left; view the live summary update on the right.

---

## 🔧 Maintenance

-   **Database**: The `.db` files are located in `assets/db/`. They can be opened with any SQLite browser for manual audit.
-   **Styling**: All design tokens (colors, spacing, shadows) are stored as CSS Variables in `:root` inside `style.css` for easy branding changes.
-   **TypeScript**: Logic is written in `.ts`. The browser compiles this on the fly using `@babel/standalone` included in `index.php`.

---

> [!TIP]
> Built with ❤️ for speed and reliability. For developer support, refer to the internal notes in the source code.
>admin / admin123
