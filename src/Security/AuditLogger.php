<?php
declare(strict_types=1);

namespace IMS\Security;

use IMS\Core\Database;
use IMS\Core\Session;
use PDO;

/**
 * Enterprise Audit Trail & Compliance Logger
 * Tracks financial transactions, stock adjustments, pricing alterations, and administrative activities.
 */
class AuditLogger
{
    public static function log(string $action, string $entityType, string $entityId, array $details = []): bool
    {
        try {
            $pdo = Database::getInstance();
            Session::start();

            $userId = Session::get('user_id');
            $userName = Session::get('user_name', 'System / CLI');
            $ipAddress = $_SERVER['REMOTE_ADDR'] ?? '127.0.0.1';
            $detailsJson = json_encode($details, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);

            $stmt = $pdo->prepare("INSERT INTO audit_logs 
                (user_id, user_name, action, entity_type, entity_id, ip_address, details, created_at)
                VALUES (?, ?, ?, ?, ?, ?, ?, datetime('now'))");

            return $stmt->execute([
                $userId,
                $userName,
                $action,
                $entityType,
                $entityId,
                $ipAddress,
                $detailsJson
            ]);
        } catch (\Throwable $e) {
            // Fail safely without interrupting user transactions
            error_log("Audit log failed: " . $e->getMessage());
            return false;
        }
    }

    public static function getRecentLogs(int $limit = 50): array
    {
        try {
            $pdo = Database::getInstance();
            $stmt = $pdo->prepare("SELECT * FROM audit_logs ORDER BY id DESC LIMIT ?");
            $stmt->bindValue(1, $limit, PDO::PARAM_INT);
            $stmt->execute();
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (\Throwable $e) {
            return [];
        }
    }
}
