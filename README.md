# 🚀 IQA Dynamic Order & Customer Manager

A modern, responsive web application for managing hardware hardware inventory and customer orders. Built with a focus on speed, security, and a premium "app-like" user experience.

---

## ✨ Key Features

-   **Two-Phase Workflow**: Clean separation between customer management and order entry.
-   **Dynamic Order Builder**: Add items (Brand, Model, Series, Description) to a live summary with real-time visual updates.
-   **Full Customer CRM**: Track company names, primary contacts, websites, shipping addresses, and internal notes.
-   **Anti-Refresh Pattern (PRG)**: Implements the **Post/Redirect/Get** pattern to eliminate duplicate form submissions and "Confirm Resubmit" browser errors on refresh.
-   **Automated IDs**: Internal customer identifiers (`CUST-XXXXXXXX`) are generated automatically to prevent manual entry errors.
-   **Zero-Config Backend**: Utilizes **SQLite** for a portable, file-based database—no complex MySQL/PostgreSQL server setup required.
-   **Pro Design**: A two-column responsive layout with modern glassmorphism elements, custom scrollbars, and interactive "chip-style" status badges.

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
├── assets/
│   ├── styles/
│   │   └── style.css         # Universal design system & component styles
│   ├── ts/
│   │   └── new_order.ts      # TypeScript logic for dynamic form population
│   └── db/
│       ├── customers.db      # SQLite database for customer records
│       └── orders.db         # SQLite database for hardware orders
└── README.md                 # This file
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
