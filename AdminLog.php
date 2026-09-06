<?php

namespace App\Models;

use App\Services\Database;
use PDO;

/**
 * RODAX — Model de Audit Logs Administrativos (admin_logs)
 */
class AdminLog
{
    private PDO $db;

    public function __construct(?PDO $db = null)
    {
        $this->db = $db ?? Database::getConnection();
    }

    /**
     * Registra uma ação administrativa no log de auditoria
     */
    public function log(
        int $adminId,
        string $action,
        string $targetType,
        int $targetId,
        ?string $details = null,
        ?string $ipAddress = null
    ): int {
        $stmt = $this->db->prepare("
            INSERT INTO admin_logs (admin_id, action, target_type, target_id, details, ip_address)
            VALUES (:admin_id, :action, :target_type, :target_id, :details, :ip_address)
        ");

        $stmt->execute([
            'admin_id'    => $adminId,
            'action'      => trim($action),
            'target_type' => trim($targetType),
            'target_id'   => $targetId,
            'details'     => $details ? trim($details) : null,
            'ip_address'  => $ipAddress ?? ($_SERVER['REMOTE_ADDR'] ?? '127.0.0.1')
        ]);

        return (int)$this->db->lastInsertId();
    }

    /**
     * Busca os últimos registros de auditoria com nome do admin
     */
    public function getAll(int $limit = 50, int $offset = 0): array
    {
        $stmt = $this->db->prepare("
            SELECT 
                l.*,
                u.name AS admin_name,
                u.email AS admin_email
            FROM admin_logs l
            JOIN users u ON l.admin_id = u.id
            ORDER BY l.id DESC
            LIMIT :limit OFFSET :offset
        ");

        $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
        $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
        $stmt->execute();

        return $stmt->fetchAll();
    }
}
