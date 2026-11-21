/**
 * Barcode & QR Code Label Station Controller
 */

const STORAGE_PRODUCTS_KEY = "ims_products";
let scanHistory = [];

const mockProducts = [
    { id: 1, name: "Dell XPS 15 Laptop", sku: "SKU-DELL-15", category: "Electronics", price: 185000.0, quantity: 12 },
    { id: 2, name: "Logitech MX Master 3S", sku: "SKU-LOGI-MX3", category: "Accessories", price: 14500.0, quantity: 45 },
    { id: 3, name: "Samsung 32-inch 4K Monitor", sku: "SKU-SAM-4K32", category: "Displays", price: 48000.0, quantity: 8 },
    { id: 4, name: "Ergonomic Mesh Chair", sku: "SKU-FURN-CHAIR", category: "Furniture", price: 22000.0, quantity: 15 }
];

function getCatalogProducts() {
    try {
        const stored = JSON.parse(localStorage.getItem(STORAGE_PRODUCTS_KEY) || "[]");
        if (Array.isArray(stored) && stored.length > 0) {
            return stored;
        }
    } catch (e) {
        // Fallback
    }
    return mockProducts;
}

function handleBarcodeScan() {
    const input = document.getElementById("barcodeInput");
    const code = (input.value || "").trim().toUpperCase();

    if (!code) return;

    const products = getCatalogProducts();
    const product = products.find(p => 
        (p.sku && p.sku.toUpperCase() === code) || 
        (p.name && p.name.toUpperCase().includes(code)) ||
        String(p.id) === code
    );

    const resultBox = document.getElementById("scanResult");

    if (product) {
        updateLabelPreview(product);
        logScanHistory(product, code);

        resultBox.style.display = "block";
        resultBox.innerHTML = `
            <div style="display: flex; justify-content: space-between; align-items: center;">
                <div>
                    <h3 style="margin: 0 0 4px 0; color: #10b981;"><i class="fa-solid fa-circle-check"></i> Found: ${escapeHTML(product.name)}</h3>
                    <div style="color: #94a3b8; font-size: 0.85rem;">SKU: <b>${escapeHTML(product.sku || 'N/A')}</b> | Category: ${escapeHTML(product.category || 'General')}</div>
                </div>
                <div style="text-align: right;">
                    <div style="font-size: 1.25rem; font-weight: 800; color: #38bdf8;">NPR ${Number(product.price).toLocaleString()}</div>
                    <span class="badge-stock ${product.quantity > 5 ? 'badge-in-stock' : 'badge-low-stock'}">${product.quantity} in Stock</span>
                </div>
            </div>
        `;
    } else {
        resultBox.style.display = "block";
        resultBox.innerHTML = `
            <div style="color: #ef4444;">
                <i class="fa-solid fa-triangle-exclamation"></i> No product matching SKU / Code "<b>${escapeHTML(code)}</b>" found in catalog.
            </div>
        `;
    }

    input.value = "";
    input.focus();
}

function simulateScan(sku) {
    document.getElementById("barcodeInput").value = sku;
    handleBarcodeScan();
}

function updateLabelPreview(product) {
    document.getElementById("labelProductName").textContent = product.name;
    document.getElementById("labelProductSku").textContent = product.sku || "SKU-ITEM";
    document.getElementById("labelProductPrice").textContent = "NPR " + Number(product.price).toLocaleString();

    // Render pseudo barcode SVG
    const svg = generateBarcodeSvg(product.sku || "SKU-DEFAULT");
    document.getElementById("labelBarcodeSvg").innerHTML = svg;
}

function generateBarcodeSvg(code) {
    let hash = 0;
    for (let i = 0; i < code.length; i++) {
        hash = (hash << 5) - hash + code.charCodeAt(i);
        hash |= 0;
    }

    let bars = "";
    let x = 10;
    const barWidth = 2;

    for (let i = 0; i < 42; i++) {
        const bit = ((hash >> (i % 31)) & 1) ^ (i % 2);
        if (bit === 1) {
            bars += `<rect x="${x}" y="5" width="${barWidth}" height="35" fill="#000000" />`;
        }
        x += barWidth + 1;
    }

    return `
        <svg width="180" height="50" viewBox="0 0 180 50">
            <rect width="100%" height="100%" fill="#ffffff" />
            ${bars}
            <text x="90" y="46" text-anchor="middle" font-family="monospace" font-size="9" fill="#000000">${code}</text>
        </svg>
    `;
}

function logScanHistory(product, scannedCode) {
    const entry = {
        time: new Date().toLocaleTimeString(),
        code: scannedCode,
        name: product.name,
        category: product.category || "General",
        quantity: product.quantity,
        price: product.price
    };

    scanHistory.unshift(entry);
    renderScanHistory();
}

function renderScanHistory() {
    const tbody = document.getElementById("scanHistoryBody");
    if (!tbody) return;

    if (scanHistory.length === 0) {
        tbody.innerHTML = `<tr><td colspan="7" class="text-center">No barcode scans recorded in this session.</td></tr>`;
        return;
    }

    tbody.innerHTML = scanHistory.map(item => `
        <tr>
            <td style="color: #94a3b8; font-family: monospace;">${item.time}</td>
            <td><code style="color: #06b6d4;">${escapeHTML(item.code)}</code></td>
            <td><b>${escapeHTML(item.name)}</b></td>
            <td>${escapeHTML(item.category)}</td>
            <td><span class="badge-stock ${item.quantity > 5 ? 'badge-in-stock' : 'badge-low-stock'}">${item.quantity} Units</span></td>
            <td style="font-weight: 700;">NPR ${Number(item.price).toLocaleString()}</td>
            <td>
                <button type="button" class="btn-chip" onclick="simulateScan('${escapeHTML(item.code)}')">Reprint</button>
            </td>
        </tr>
    `).join("");
}

function escapeHTML(str) {
    return String(str || "").replace(/[&<>"']/g, c => ({
        "&": "&amp;", "<": "&lt;", ">": "&gt;", '"': "&quot;", "'": "&#039;"
    })[c]);
}

// Initial label load
document.addEventListener("DOMContentLoaded", () => {
    updateLabelPreview(mockProducts[0]);
});
