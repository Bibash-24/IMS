<?php
require_once __DIR__ . '/src/autoload.php';

use IMS\Core\Session;
use IMS\Security\AuditLogger;

Session::start();
Session::requireAdmin('index.html');

$logs = AuditLogger::getRecentLogs(100);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Security & Transaction Audit Logs - IMS</title>
    <link rel="stylesheet" href="style.css">
    <link rel="stylesheet" href="audit_logs.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.6.0/css/all.min.css">
</head>
<body>
    <div class="app-layout">
        <aside class="sidebar">
            <div class="logo">
                <i class="fa-solid fa-boxes-stacked"></i>
                <h2>IMS Admin</h2>
            </div>
            <nav class="nav-links">
                <a href="dashboard.html"><i class="fa-solid fa-gauge-high"></i> Dashboard</a>
                <a href="inventory.html"><i class="fa-solid fa-box"></i> Inventory</a>
                <a href="sales.html"><i class="fa-solid fa-cart-shopping"></i> Sales POS</a>
                <a href="purchase.html"><i class="fa-solid fa-truck-ramp-box"></i> Purchase</a>
                <a href="barcode_scanner.html"><i class="fa-solid fa-barcode"></i> Barcode Station</a>
                <a href="analytics_bi.html"><i class="fa-solid fa-chart-line"></i> BI Analytics</a>
                <a href="audit_logs.php" class="active"><i class="fa-solid fa-shield-halved"></i> Audit Logs</a>
                <a href="double_entry.html"><i class="fa-solid fa-scale-balanced"></i> General Ledger</a>
                <a href="reports.html"><i class="fa-solid fa-chart-pie"></i> Reports</a>
                <a href="logout.php"><i class="fa-solid fa-arrow-right-from-bracket"></i> Logout</a>
            </nav>
        </aside>

        <main class="main-content">
            <header class="top-nav">
                <div class="page-title">
                    <h1><i class="fa-solid fa-shield-halved"></i> Security & Transaction Audit Trail</h1>
                    <p>Tamper-evident logs of administrative actions, stock changes, and financial postings.</p>
                </div>
                <div class="user-badge">
                    <span style="color: #10b981;"><i class="fa-solid fa-lock"></i> RBAC Enforced</span>
                </div>
            </header>

            <div class="card">
                <div class="table-responsive">
                    <table class="data-table">
                        <thead>
                            <tr>
                                <th>Log ID</th>
                                <th>Timestamp</th>
                                <th>Actor</th>
                                <th>Event Type</th>
                                <th>Entity Affected</th>
                                <th>IP Address</th>
                                <th>Transaction Details</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (empty($logs)): ?>
                                <tr>
                                    <td colspan="7" style="text-align: center; padding: 30px; color: #94a3b8;">
                                        Zero critical audit exceptions detected. System running in nominal state.
                                    </td>
                                </tr>
                            <?php else: ?>
                                <?php foreach ($logs as $log): ?>
                                    <tr>
                                        <td style="font-family: monospace; color: #94a3b8;">#<?php echo htmlspecialchars((string) $log['id']); ?></td>
                                        <td style="font-family: monospace; font-size: 0.82rem;"><?php echo htmlspecialchars($log['created_at']); ?></td>
                                        <td><b><?php echo htmlspecialchars($log['user_name'] ?? 'System'); ?></b></td>
                                        <td>
                                            <?php 
                                            $action = htmlspecialchars($log['action']);
                                            $class = 'badge-security';
                                            if (str_contains($action, 'SALE')) $class = 'badge-sale';
                                            elseif (str_contains($action, 'PURCHASE')) $class = 'badge-purchase';
                                            elseif (str_contains($action, 'LEDGER')) $class = 'badge-ledger';
                                            ?>
                                            <span class="badge-action <?php echo $class; ?>"><?php echo $action; ?></span>
                                        </td>
                                        <td><code><?php echo htmlspecialchars($log['entity_type'] . ' #' . $log['entity_id']); ?></code></td>
                                        <td style="font-family: monospace; color: #64748b;"><?php echo htmlspecialchars($log['ip_address'] ?? '127.0.0.1'); ?></td>
                                        <td style="font-size: 0.8rem; font-family: monospace; max-width: 320px; word-break: break-all;">
                                            <?php echo htmlspecialchars($log['details'] ?? '{}'); ?>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </main>
    </div>
</body>
</html>
