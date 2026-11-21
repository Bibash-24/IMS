/**
 * Business Intelligence & Chart.js Data Visualizer
 */

document.addEventListener("DOMContentLoaded", () => {
    initSalesPurchasesChart();
    initAbcParetoChart();
});

function initSalesPurchasesChart() {
    const ctx = document.getElementById("salesPurchasesChart");
    if (!ctx) return;

    new Chart(ctx, {
        type: "bar",
        data: {
            labels: ["June", "July", "August", "September", "October", "November"],
            datasets: [
                {
                    label: "Gross Sales (NPR)",
                    data: [420000, 560000, 610000, 580000, 720000, 840000],
                    backgroundColor: "rgba(59, 130, 246, 0.8)",
                    borderRadius: 6
                },
                {
                    label: "Inbound Purchases (NPR)",
                    data: [310000, 420000, 490000, 430000, 510000, 610000],
                    backgroundColor: "rgba(16, 185, 129, 0.8)",
                    borderRadius: 6
                }
            ]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                legend: {
                    labels: { color: "#94a3b8" }
                }
            },
            scales: {
                x: {
                    grid: { color: "rgba(51, 65, 85, 0.4)" },
                    ticks: { color: "#94a3b8" }
                },
                y: {
                    grid: { color: "rgba(51, 65, 85, 0.4)" },
                    ticks: { color: "#94a3b8" }
                }
            }
        }
    });
}

function initAbcParetoChart() {
    const ctx = document.getElementById("abcParetoChart");
    if (!ctx) return;

    new Chart(ctx, {
        type: "doughnut",
        data: {
            labels: ["Class A (High Value - 75%)", "Class B (Moderate - 18%)", "Class C (Low Value - 7%)"],
            datasets: [{
                data: [75, 18, 7],
                backgroundColor: [
                    "rgba(59, 130, 246, 0.9)",
                    "rgba(6, 182, 212, 0.9)",
                    "rgba(148, 163, 184, 0.6)"
                ],
                borderWidth: 2,
                borderColor: "#1e293b"
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                legend: {
                    position: "bottom",
                    labels: { color: "#94a3b8", padding: 15 }
                }
            }
        }
    });
}
