# 📦 Enterprise Inventory Management System (IMS)

An enterprise-grade, full-cycle Inventory Replenishment, Point of Sale (POS), Procurement, Double-Entry Accounting, and Business Intelligence platform built with PHP 8.2, Vanilla ES6+ JavaScript, HTML5/CSS3 Glassmorphic UI, and MySQL/MariaDB.

---

## 🚀 How to Run the Project

### 📋 Prerequisites
- **PHP 8.2 or higher** with `pdo_mysql`, `pdo_sqlite`, and `mysqli` extensions enabled (`php -v`).
- **MySQL / MariaDB** (via XAMPP, WAMP, or standalone MySQL service).
- Any modern web browser (Google Chrome, Microsoft Edge, Mozilla Firefox).

---

### Step 1: Start MySQL Database
1. Launch **XAMPP Control Panel** (or your local database manager).
2. Click **Start** beside the **MySQL** module.

---

### Step 2: Import Database Schema
1. Open your browser and navigate to **phpMyAdmin**:
   ```
   http://localhost/phpmyadmin/
   ```
2. Create a new database named:
   ```sql
   ims
   ```
3. Click the **Import** tab, choose the schema file from the repository, and click **Go**:
   ```
   D:\students\IMS\ims.sql
   ```
   *(Alternatively, run from terminal: `mysql -u root ims < D:\students\IMS\ims.sql`)*

---

### Step 3: Start the Web Application
Open your terminal or command prompt in the project root directory and start PHP's built-in web server:

```powershell
Set-Location 'D:\students\IMS'
php -S localhost:8080
```

---

### Step 4: Access Application in Browser
Open your browser and navigate to the application:

- 🔐 **Login / Authentication Portal:** [http://localhost:8080/index.html](http://localhost:8080/index.html)
- 📊 **Executive Dashboard:** [http://localhost:8080/dashboard.html](http://localhost:8080/dashboard.html)
- 🏷️ **Barcode & Thermal Label Station:** [http://localhost:8080/barcode_scanner.html](http://localhost:8080/barcode_scanner.html)
- 📈 **BI Analytics Dashboard:** [http://localhost:8080/analytics_bi.html](http://localhost:8080/analytics_bi.html)
- 📑 **Double-Entry General Ledger:** [http://localhost:8080/double_entry.html](http://localhost:8080/double_entry.html)
- 🛡️ **Security Audit Logs Console:** [http://localhost:8080/audit_logs.php](http://localhost:8080/audit_logs.php)

---

### 🔑 Default Login Credentials

| Role | Username / Email | Password | Access Level |
|---|---|---|---|
| **System Administrator** | `admin@ims.com` | `admin123` | Complete administrative access (P&L reports, ledger, user management, audit logs) |
| **Store Staff** | `staff@ims.com` | `staff123` | Operational access (POS checkout, barcode scanner, stock lookup) |

---

### 🧪 How to Run Automated Unit Tests (Optional)
The system includes a zero-dependency automated test harness powered by an in-memory SQLite fixture (no MySQL or Apache required):

```powershell
Set-Location 'D:\students\IMS'
php tests/run_tests.php
```

All 23 automated unit tests and 83 assertions execute in under 30 ms with a 100% pass rate.

---

## 📖 Project Description

### Overview
The **Enterprise Inventory Management System (IMS)** is an integrated commercial resource planning and inventory control application engineered to automate the end-to-end operational lifecycle of wholesale, distribution, and retail enterprises. 

The platform eliminates inventory stockouts, mitigates overstock holding costs, streamlines supplier procurement, accelerates point-of-sale customer checkout, and maintains financial integrity through real-time double-entry general ledger accounting.

---

### Core Business Modules & Scope

1. **Live Inventory & Stock Control (`inventory.html`, `add_product.html`)**
   - Real-time stock-on-hand tracking with categorical filtering and low-stock threshold alerting.
   - Dynamic SKU generation and multi-attribute product catalog management (unit cost, selling price, categories, supplier mapping).

2. **Inbound Procurement & Purchasing (`purchase.html`, `PurchaseOrderService.php`)**
   - Comprehensive supplier purchase order lifecycle (Draft $\rightarrow$ Pending $\rightarrow$ Received).
   - Automated recalculation of **Weighted Average Cost (WAC)** upon inbound inventory receipt to maintain accurate valuation:
     $$\text{WAC} = \frac{(\text{Current Qty} \times \text{Current Cost}) + (\text{Received Qty} \times \text{Inbound Cost})}{\text{Current Qty} + \text{Received Qty}}$$

3. **High-Velocity Point of Sale (POS) (`sales.html`, `PosCheckoutService.php`)**
   - Fast retail cashier terminal supporting instant product search, quantity adjustments, line-item discounts, and automated 13% VAT tax calculation.
   - Atomic database-level stock reservation preventing race conditions and overselling (`WHERE quantity >= ?`).
   - Thermal receipt generator formatted for 80mm/58mm POS receipt printers.

4. **Barcode & QR Label Station (`barcode_scanner.html`, `BarcodeQrService.php`)**
   - Laser scanner integration for instant SKU identification and live stock lookup.
   - On-demand generation of Code-128 SVG vector barcodes and HMAC-SHA256 cryptographically verifiable QR product seals.

5. **Executive Business Intelligence & Inventory Analytics (`analytics_bi.html`, `InventoryAnalyticsService.php`)**
   - **Economic Order Quantity (EOQ):** Wilson formula optimization to minimize annual ordering and carrying costs:
     $$\text{EOQ} = \sqrt{\frac{2 \times D \times S}{H}}$$
   - **Reorder Point (ROP) & Safety Stock:** Lead-time demand forecasting with statistical safety stock buffering.
   - **ABC Pareto Classification:** 80/20 inventory analysis classifying products into Class A (high-value), Class B (moderate), and Class C (bulk/low-value).
   - Dynamic Chart.js financial and stock distribution visual charts.

6. **Double-Entry General Ledger & Accounts Aging (`double_entry.html`, `AccountsLedgerService.php`)**
   - Financial journal entry engine strictly enforcing double-entry mathematical balance:
     $$\sum \text{Debits} = \sum \text{Credits}$$
   - Automated trial balance generation and real-time Profit & Loss statement compilation.
   - Accounts Receivable (AR) and Accounts Payable (AP) credit aging schedules categorized into 0–30, 31–60, 61–90, and 90+ days aging buckets.

7. **Operational Expense & Overhead Tracking (`expenses.html`)**
   - Daily operational expense logging categorized across utilities, logistics, rent, packaging, and maintenance.

8. **Customer CRM & Supplier Directory (`customers.html`, `suppliers.html`)**
   - Centralized profiles, transaction history, credit balances, and communication directories for clients and vendors.

9. **Human Resources & Payroll Engine (`staff.html`, `PayrollEngineService.php`)**
   - Daily staff attendance logging (Present, Absent, Leave).
   - Automated payroll computation factoring in basic salary proration, 1.5x overtime hours, and Tax Deducted at Source (TDS) withholding.

10. **Enterprise Security & Compliance (`src/Security/`, `audit_logs.php`)**
    - **Anti-CSRF Protection (`CsrfGuard.php`):** Timing-safe cryptographic tokens safeguarding state-changing forms.
    - **Sliding-Window Rate Limiter (`RateLimiter.php`):** Throttling protection guarding against brute-force credential stuffing and API abuse.
    - **Role-Based Access Control (`Session.php`):** Multi-tier session privileges separating Administrator and Store Staff roles.
    - **Tamper-Evident Audit Logging (`AuditLogger.php`):** Comprehensive audit trail capturing actor identity, action type, IP address, and before/after data state snapshots.
    - **Input Sanitization (`InputValidator.php`):** Strict XSS prevention and regex verification for SKUs, emails, and PAN tax IDs.

---

### Technical Highlights
- **Architecture:** Decoupled PSR-4 compliant modular service architecture (`src/` namespace `IMS\`).
- **Database Engine:** Dual-mode PDO singleton seamlessly supporting live MySQL/MariaDB for production and in-memory SQLite fixtures for automated testing.
- **Frontend Experience:** Modern dark-mode glassmorphic interface built with pure Vanilla ES6+ JavaScript, CSS3 Grid/Flexbox, and responsive media queries without heavy external JavaScript frameworks.
- **Data Integrity:** Fully parameterized PDO prepared statements completely mitigating SQL injection vulnerabilities.
