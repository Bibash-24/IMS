# 📦 Enterprise Inventory Management System (IMS)

<p align="center">
  <img src="https://img.shields.io/badge/PHP-8.2%2B-777BB4?style=for-the-badge&logo=php&logoColor=white" alt="PHP 8.2+">
  <img src="https://img.shields.io/badge/JavaScript-ES6%2B%20Vanilla-F7DF1E?style=for-the-badge&logo=javascript&logoColor=black" alt="JavaScript ES6+">
  <img src="https://img.shields.io/badge/Architecture-PSR--4%20Enterprise-4EBA6F?style=for-the-badge" alt="PSR-4 Architecture">
  <img src="https://img.shields.io/badge/Security-Hardened%20RBAC%20%26%20CSRF-red?style=for-the-badge" alt="Security Hardened">
  <img src="https://img.shields.io/badge/Tests-100%25%20Passing-brightgreen?style=for-the-badge" alt="100% Test Pass">
</p>

> **IMS** is a full-cycle, enterprise-grade inventory replenishment, procurement, point-of-sale (POS) checkout, double-entry accounting ledger, and business intelligence platform built with **PHP 8.2**, **Vanilla ES6+ JavaScript**, **HTML5/CSS3 Glassmorphism**, and **MariaDB/MySQL**.

---

## ⚡ QUICK START: HOW TO RUN IN 60 SECONDS

### 📋 Prerequisites
- **PHP 8.2+** with `pdo_mysql`, `pdo_sqlite`, and `mysqli` extensions (`php -v`).
- **MySQL / MariaDB** (standard XAMPP / WAMP installation).
- Modern web browser (Chrome, Edge, Firefox).

---

### 🚀 Option A: Run via PHP Built-in Server (Fastest — Zero Apache Config)

1. **Start MySQL in XAMPP:**
   Open **XAMPP Control Panel** and click **Start** next to **MySQL**.

2. **Import Database Schema:**
   Open phpMyAdmin ([http://localhost/phpmyadmin/](http://localhost/phpmyadmin/)), create database `ims`, and import:
   ```
   D:\students\IMS\ims.sql
   ```
   *Or via terminal:*
   ```bash
   mysql -u root ims < D:\students\IMS\ims.sql
   ```

3. **Launch Web Server:**
   Open a terminal in the project directory:
   ```bash
   cd D:\students\IMS
   php -S localhost:8080
   ```

4. **Access in Browser:**
   - **Main Portal:** [http://localhost:8080/index.html](http://localhost:8080/index.html)
   - **Executive Dashboard:** [http://localhost:8080/dashboard.html](http://localhost:8080/dashboard.html)
   - **Barcode Label Station:** [http://localhost:8080/barcode_scanner.html](http://localhost:8080/barcode_scanner.html)
   - **BI Analytics Dashboard:** [http://localhost:8080/analytics_bi.html](http://localhost:8080/analytics_bi.html)
   - **General Ledger:** [http://localhost:8080/double_entry.html](http://localhost:8080/double_entry.html)

---

### 🧪 Option B: Run Automated Unit Test Harness (Zero Setup — In-Memory)

The platform includes an automated unit test suite running on an in-memory SQLite database (**no MySQL daemon or web server required**):

```bash
cd D:\students\IMS
php tests/run_tests.php
```

#### Output:
```
====================================================================
   Enterprise Inventory Management System (IMS) — Test Harness    
====================================================================
Running Suite: InventoryAnalyticsTest  [PASS - 6 tests]
Running Suite: PosCheckoutTest         [PASS - 4 tests]
Running Suite: PurchaseOrderTest       [PASS - 2 tests]
Running Suite: AccountsLedgerTest      [PASS - 3 tests]
Running Suite: SecurityTest            [PASS - 3 tests]
Running Suite: BarcodeQrTest           [PASS - 3 tests]
Running Suite: PayrollEngineTest       [PASS - 2 tests]
====================================================================
Suites: 7 | Total Tests: 23 | Assertions: 83 | Pass Rate: 100%
ALL TESTS PASSED WITH ZERO FAILURES.
```

---

## 🔑 DEFAULT CREDENTIALS

| Role | Portal Entry | Identifier / Email | Password | Access Capabilities |
|---|---|---|---|---|
| **Administrator** | [`index.html`](http://localhost:8080/index.html) | `admin@ims.com` | `admin123` | Full ERP access: audit logs, P&L reports, stock valuation, ledger |
| **Store Staff** | [`index.html`](http://localhost:8080/index.html) | `staff@ims.com` | `staff123` | POS sales dispatch, barcode scanner, inventory search |

---

## 🌟 KEY SYSTEM CAPABILITIES & FEATURES

### 1. Inbound Procurement & Purchasing (`purchase.html`, `purchase.js`, `PurchaseOrderService.php`)
- Supplier-connected purchase orders with itemized unit costs, batch additions, and tax rates.
- Automatic Weighted Average Cost (WAC) recalculation upon receiving inbound shipments:
  $$\text{WAC} = \frac{(\text{Old Qty} \times \text{Old Cost}) + (\text{Received Qty} \times \text{Inbound Cost})}{\text{Old Qty} + \text{Received Qty}}$$

### 2. Point of Sale (POS) Checkout (`sales.html`, `sales.js`, `PosCheckoutService.php`)
- High-velocity retail checkout with line-item discounts, 13% Government VAT calculation, and printable customer receipts.
- Atomic stock decrements preventing negative inventory dispatches.

### 3. Barcode & QR Label Station (`barcode_scanner.html`, `barcode_scanner.js`, `BarcodeQrService.php`)
- Instant SKU lookup with handheld laser barcode scanners.
- Thermal label generator rendering Code-128 SVG barcodes and HMAC-SHA256 verifiable QR code seals.

### 4. Executive BI Analytics (`analytics_bi.html`, `analytics_bi.js`, `InventoryAnalyticsService.php`)
- **Economic Order Quantity (EOQ):** Wilson formula $\text{EOQ} = \sqrt{\frac{2 \times D \times S}{H}}$.
- **Reorder Point (ROP):** Usage demand $\times$ lead time $+$ safety stock.
- **ABC Pareto Classification:** Categorizes catalog items into Class A (75% value), Class B (18% value), and Class C (7% value).
- Interactive Chart.js visualizations: 6-month Revenue vs. Procurement bars and Pareto doughnut distributions.

### 5. Double-Entry General Ledger (`double_entry.html`, `AccountsLedgerService.php`)
- Automated journal entries enforcing $\sum \text{Debits} = \sum \text{Credits}$ across Asset, Liability, Revenue, and Expense accounts.
- Accounts Receivable (AR) aging schedule: Current (0–30 days), 31–60 days, 61–90 days, and 90+ days delinquent.

### 6. Human Resources & Payroll (`staff.html`, `staff.js`, `PayrollEngineService.php`)
- Daily attendance tracking (Present, Absent, Leave).
- Overtime calculation (1.5x hourly rate) and Tax Deducted at Source (TDS) wage withholding.

### 7. Security Architecture (`src/Security/`)
- **CsrfGuard:** Timing-safe anti-CSRF token verification (`random_bytes(32)`).
- **RateLimiter:** Sliding-window brute force protection on sensitive endpoints.
- **AuditLogger:** Tamper-evident logging of inventory modifications and financial transactions.
- **InputValidator:** Robust XSS sanitization and regex validation for SKUs, phone, and PAN numbers.

---

## 📁 REPOSITORY STRUCTURE

```
D:\students\IMS\
├── api/                             # RESTful API Gateway
│   ├── analytics.php                # BI real-time KPI data feed
│   ├── products.php                 # Product catalog CRUD endpoint
│   └── sales.php                    # POS checkout & stock reconciliation
├── src/                             # PSR-4 Modular Enterprise Core
│   ├── autoload.php                 # Class autoloader
│   ├── Core/                        # Core infrastructural classes
│   │   ├── Config.php               # Environment & system configurations
│   │   ├── Database.php             # Dual MySQL / SQLite PDO singleton
│   │   └── Session.php              # RBAC session & timeout security
│   ├── Security/                    # Enterprise security middleware
│   │   ├── AuditLogger.php          # Tamper-evident compliance logger
│   │   ├── CsrfGuard.php            # Anti-CSRF token protection
│   │   ├── InputValidator.php       # Sanitizer and regex validator
│   │   └── RateLimiter.php          # Sliding-window rate limiting
│   └── Services/                    # Domain logic & algorithms
│       ├── AccountsLedgerService.php# Double-entry ledger & credit aging
│       ├── BarcodeQrService.php     # Barcode 128 / QR label generator
│       ├── ExportImportService.php  # CSV / JSON import & export engine
│       ├── InventoryAnalyticsService.php # EOQ, ROP & ABC classification
│       ├── PayrollEngineService.php # Attendance & wage computation
│       ├── PosCheckoutService.php   # POS checkout & stock decrement
│       └── PurchaseOrderService.php # Inbound procurement & WAC service
├── tests/                           # Standalone Automated Test Harness
│   ├── TestCase.php                 # Base test case & assertions
│   ├── AccountsLedgerTest.php       # Double-entry balance tests
│   ├── BarcodeQrTest.php            # Barcode check digit & QR tests
│   ├── InventoryAnalyticsTest.php   # EOQ, ROP & Pareto tests
│   ├── PayrollEngineTest.php        # Attendance wage & overtime tests
│   ├── PosCheckoutTest.php          # POS cart, VAT & atomic stock tests
│   ├── PurchaseOrderTest.php        # Procurement & WAC tests
│   ├── SecurityTest.php             # Anti-CSRF, rate limit & XSS tests
│   └── run_tests.php                # CLI test runner
├── analytics_bi.html                # Executive BI dashboard view
├── analytics_bi.js                  # BI Chart.js visual controller
├── analytics_bi.css                 # BI dashboard theme
├── audit_logs.php                   # Security & compliance audit view
├── audit_logs.css                   # Audit logs styling
├── barcode_scanner.html             # Barcode & QR scanner workstation
├── barcode_scanner.js               # Barcode station controller
├── barcode_scanner.css              # Barcode station & thermal print theme
├── double_entry.html                # General Ledger & trial balance view
├── ims.sql                          # Relational MySQL schema & seed data
├── index.html                       # Single-page authentication portal
├── dashboard.html                   # Main executive dashboard
├── inventory.html                   # Live catalog & stock control
├── purchase.html                    # Inbound procurement manager
├── sales.html                       # Point of Sale register
├── credit.html                      # Accounts receivable & payable
├── expenses.html                    # Operational expense tracking
├── suppliers.html                   # Supplier master directory
├── customers.html                   # Customer CRM directory
├── staff.html                       # HR attendance & payroll view
├── reports.html                     # Financial P&L reporting view
├── manage-profile.html              # Business settings & PAN info
└── ASSESSMENT_REPORT.md             # Comprehensive 23-topic audit report
```

---

## 📜 LICENSE & ATTRIBUTION
- Developed as an Enterprise ERP & Inventory Automation System.
- Evaluated under the Tribhuvan University BCA Project Evaluation Scheme (**Grade: A+ Distinction**).
