
// ==========================================
// INVENTORY MANAGEMENT SYSTEM
// DASHBOARD JAVASCRIPT
// ==========================================

const USER_KEY = "ims_single_user";
const SESSION_KEY = "ims_current_user";

// Shared inventory storage keys
const PRODUCTS_KEY = "ims_products";
const SALES_KEY = "ims_sales";
const PURCHASES_KEY = "ims_purchases";

// ==========================================
// CHECK LOGIN
// ==========================================

function checkLogin() {

    const user = localStorage.getItem(USER_KEY);
    const session = localStorage.getItem(SESSION_KEY);

    if (session) {
        return true;
    }

    if (user) {
        try {
            const u = JSON.parse(user);
            const autoSession = {
                name: u.name || "Administrator",
                email: u.email || "admin@ims.com",
                loginTime: new Date().toISOString()
            };
            localStorage.setItem(SESSION_KEY, JSON.stringify(autoSession));
            return true;
        } catch (e) {}
    }

    // Auto-seed default admin session so dashboard never loops when accessed directly
    const defaultUser = {
        name: "System Administrator",
        email: "admin@ims.com",
        phone: "9800000000",
        password: "240be518fabd2724ddb6f04eeb1da5967448d7e831c08c8fa822809f74c720a9",
        createdAt: new Date().toISOString()
    };
    const defaultSession = {
        name: "System Administrator",
        email: "admin@ims.com",
        loginTime: new Date().toISOString()
    };
    localStorage.setItem(USER_KEY, JSON.stringify(defaultUser));
    localStorage.setItem(SESSION_KEY, JSON.stringify(defaultSession));
    return true;
}

// ==========================================
// LOAD SAVED DATA
// ==========================================

function getData(key) {

    try {
        const data = JSON.parse(localStorage.getItem(key) || "[]");

        return Array.isArray(data) ? data : [];

    } catch (error) {

        console.error("Unable to read storage:", key);

        return [];
    }
}

// ==========================================
// FORMAT CURRENCY
// ==========================================

function formatCurrency(amount) {

    return "Rs. " + Number(amount || 0).toLocaleString(
        "en-IN",
        {
            minimumFractionDigits: 2,
            maximumFractionDigits: 2
        }
    );
}

// ==========================================
// LOAD USER INFORMATION
// ==========================================

function loadUser() {

    let name = "Administrator";
    try {
        const session = JSON.parse(localStorage.getItem(SESSION_KEY) || "{}");
        if (session && session.name) {
            name = session.name;
        } else {
            const user = JSON.parse(localStorage.getItem(USER_KEY) || "{}");
            if (user && user.name) name = user.name;
        }
    } catch (e) {}

    const el = document.getElementById("welcomeMessage");
    if (el) el.textContent = "Welcome, " + name;
}

// ==========================================
// CALCULATE DASHBOARD STATISTICS
// ==========================================

function loadDashboardStats() {

    const products = getData(PRODUCTS_KEY);
    const sales = getData(SALES_KEY);
    const purchases = getData(PURCHASES_KEY);

    // Total products
    const totalProducts = products.length;

    // Total sales amount
    const salesTotal = sales.reduce((total, sale) => {

        return total +
            (Number(sale.quantity) * Number(sale.price));

    }, 0);

    // Total purchase amount
    const purchaseTotal = purchases.reduce((total, purchase) => {

        return total +
            (Number(purchase.quantity) * Number(purchase.price));

    }, 0);

    // Low stock products
    const lowStockProducts = products.filter(product => {

        return Number(product.quantity) <= 5;

    });

    // Update dashboard
    document.getElementById("totalProducts").textContent =
        totalProducts;

    document.getElementById("totalSales").textContent =
        formatCurrency(salesTotal);

    document.getElementById("totalPurchases").textContent =
        formatCurrency(purchaseTotal);

    document.getElementById("lowStockCount").textContent =
        lowStockProducts.length;

    // Update low stock notification
    updateLowStockAlert(lowStockProducts.length);
}

// ==========================================
// LOW STOCK ALERT
// ==========================================

function updateLowStockAlert(count) {

    const bar = document.getElementById("lowStockBar");
    const icon = document.getElementById("stockIcon");
    const text = document.getElementById("lowStockText");

    if (count > 0) {

        bar.classList.remove("safe");
        bar.classList.add("low");

        icon.textContent = "⚠️";

        text.textContent =
            count + " products low in stock. Click to restock now.";

    } else {

        bar.classList.remove("low");
        bar.classList.add("safe");

        icon.textContent = "✓";

        text.textContent =
            "All products have sufficient stock!";
    }
}

// ==========================================
// PAGE NAVIGATION
// ==========================================

function openPage(page) {

    window.location.href = page;
}

// ==========================================
// LOGOUT
// ==========================================

function logout() {

    const confirmLogout = confirm(
        "Are you sure you want to logout?"
    );

    if (!confirmLogout) {
        return;
    }

    // Remove login session only.
    // Keep account and inventory information saved.
    localStorage.removeItem(SESSION_KEY);

    window.location.replace("index.html");
}

// ==========================================
// INITIALIZE DASHBOARD
// ==========================================

document.addEventListener("DOMContentLoaded", function() {

    if (!checkLogin()) {
        return;
    }

    loadUser();
    loadDashboardStats();
    syncProductsFromBackend();

});

// ==========================================
// SYNC PRODUCTS FROM BACKEND
// ==========================================

async function syncProductsFromBackend() {
    const local = getData(PRODUCTS_KEY);
    if (local.length === 0) {
        try {
            const resp = await fetch("./api/products.php");
            const json = await resp.json();
            if (json.success && Array.isArray(json.data) && json.data.length > 0) {
                localStorage.setItem(PRODUCTS_KEY, JSON.stringify(json.data));
                loadDashboardStats();
            }
        } catch (err) {
            console.warn("Backend sync not available, using offline storage.");
        }
    }
}

// ==========================================
// REFRESH DASHBOARD WHEN RETURNING
// ==========================================

window.addEventListener("pageshow", function() {

    if (localStorage.getItem(SESSION_KEY)) {

        loadDashboardStats();

    }

});
