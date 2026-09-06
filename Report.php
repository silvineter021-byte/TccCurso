<?php

namespace App\Models;

use App\Services\Database;
use PDO;

/**
 * RODAX — Model de Denúncias (Report System)
 */
class Report
{
    private PDO $db;

    public static array $allowedReasons = [
        'Fraude / Golpe',
        'Veículo Inexistente',
        'Preço Enganoso',
        'Conteúdo Inadequado',
        'Anúncio Duplicado',
        'Informações Falsas',
        'Outro'
    ];

    public function __construct(?PDO $db = null)
    {
        $this->db = $db ?? Database::getConnection();
    }

    /**
     * Registra uma nova denúncia para um veículo
     */
    public function create(array $data): int
    {
        $stmt = $this->db->prepare("
            INSERT INTO reports (reporter_id, vehicle_id, reason, description, status)
            VALUES (:reporter_id, :vehicle_id, :reason, :description, 'pending')
        ");

        $stmt->execute([
            'reporter_id' => !empty($data['reporter_id']) ? (int)$data['reporter_id'] : null,
            'vehicle_id'  => (int)$data['vehicle_id'],
            'reason'      => trim($data['reason']),
            'description' => !empty($data['description']) ? trim($data['description']) : null
        ]);

        return (int)$this->db->lastInsertId();
    }

    /**
     * Verifica se um usuário já denunciou um determinado veículo
     */
    public function hasUserReportedVehicle(?int $reporterId, int $vehicleId): bool
    {
        if (!$reporterId) {
            return false;
        }

        $stmt = $this->db->prepare("
            SELECT COUNT(*) FROM reports 
            WHERE reporter_id = :reporter_id AND vehicle_id = :vehicle_id AND status != 'dismissed'
        ");
        $stmt->execute([
            'reporter_id' => $reporterId,
            'vehicle_id'  => $vehicleId
        ]);

        return (int)$stmt->fetchColumn() > 0;
    }

    /**
     * Busca uma denúncia pelo ID com informações do veículo e do denunciante
     */
    public function findById(int $id): ?array
    {
        $stmt = $this->db->prepare("
            SELECT 
                r.*,
                v.title AS vehicle_title,
                v.price AS vehicle_price,
                v.user_id AS seller_id,
                u.name AS reporter_name,
                u.email AS reporter_email
            FROM reports r
            JOIN vehicles v ON r.vehicle_id = v.id
            LEFT JOIN users u ON r.reporter_id = u.id
            WHERE r.id = :id
            LIMIT 1
        ");
        $stmt->execute(['id' => $id]);
        $report = $stmt->fetch();
        return $report ?: null;
    }

    /**
     * Retorna todas as denúncias com filtros (para o painel admin)
     */
    public function getAll(string $statusFilter = ''): array
    {
        $sql = "
            SELECT 
                r.*,
                v.title AS vehicle_title,
                v.user_id AS seller_id,
                u.name AS reporter_name,
                u.email AS reporter_email
            FROM reports r
            JOIN vehicles v ON r.vehicle_id = v.id
            LEFT JOIN users u ON r.reporter_id = u.id
        ";

        $params = [];
        if (!empty($statusFilter) && in_array($statusFilter, ['pending', 'resolved', 'dismissed'])) {
            $sql .= " WHERE r.status = :status";
            $params['status'] = $statusFilter;
        }

        $sql .= " ORDER BY r.created_at DESC";

        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll();
    }

    /**
     * Atualiza o status da denúncia (Admin)
     */
    public function updateStatus(int $reportId, string $newStatus, int $adminId): bool
    {
        if (!in_array($newStatus, ['pending', 'resolved', 'dismissed'])) {
            return false;
        }

        $stmt = $this->db->prepare("
            UPDATE reports 
            SET status = :status, resolved_by = :admin_id 
            WHERE id = :id
        ");

        return $stmt->execute([
            'status'   => $newStatus,
            'admin_id' => $adminId,
            'id'       => $reportId
        ]);
    }
}
