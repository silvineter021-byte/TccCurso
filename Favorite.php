<?php

namespace App\Models;

use App\Services\Database;
use PDO;
use Exception;

/**
 * RODAX — Model de Gerenciamento de Favoritos
 */
class Favorite
{
    private PDO $db;

    public function __construct(?PDO $db = null)
    {
        $this->db = $db ?? Database::getConnection();
    }

    /**
     * Adiciona um veículo aos favoritos do usuário (Impede Duplicidade)
     */
    public function add(int $userId, int $vehicleId): bool
    {
        $stmt = $this->db->prepare("
            INSERT INTO favorites (user_id, vehicle_id, created_at)
            VALUES (:user_id, :vehicle_id, NOW())
            ON DUPLICATE KEY UPDATE created_at = NOW()
        ");
        return $stmt->execute([
            'user_id'    => $userId,
            'vehicle_id' => $vehicleId
        ]);
    }

    /**
     * Remove um veículo dos favoritos do usuário
     */
    public function remove(int $userId, int $vehicleId): bool
    {
        $stmt = $this->db->prepare("
            DELETE FROM favorites 
            WHERE user_id = :user_id AND vehicle_id = :vehicle_id
        ");
        $stmt->execute([
            'user_id'    => $userId,
            'vehicle_id' => $vehicleId
        ]);
        return $stmt->rowCount() > 0;
    }

    /**
     * Alterna o estado do favorito (Adiciona se não existir, remove se existir)
     */
    public function toggle(int $userId, int $vehicleId): bool
    {
        if ($this->isFavorited($userId, $vehicleId)) {
            return $this->remove($userId, $vehicleId);
        } else {
            return $this->add($userId, $vehicleId);
        }
    }

    /**
     * Verifica se um veículo é favoritado por determinado usuário
     */
    public function isFavorited(int $userId, int $vehicleId): bool
    {
        $stmt = $this->db->prepare("
            SELECT COUNT(*) 
            FROM favorites 
            WHERE user_id = :user_id AND vehicle_id = :vehicle_id
        ");
        $stmt->execute([
            'user_id'    => $userId,
            'vehicle_id' => $vehicleId
        ]);
        return (int)$stmt->fetchColumn() > 0;
    }

    /**
     * Busca todos os veículos favoritados pelo usuário
     */
    public function getUserFavorites(int $userId): array
    {
        $stmt = $this->db->prepare("
            SELECT 
                v.*, 
                vt.name AS category_name,
                img.image_path AS main_image,
                fav.created_at AS favorited_at
            FROM favorites fav
            JOIN vehicles v ON fav.vehicle_id = v.id
            JOIN vehicle_types vt ON v.vehicle_type_id = vt.id
            LEFT JOIN vehicle_images img ON v.id = img.vehicle_id AND img.is_main = 1
            WHERE fav.user_id = :user_id AND v.status != 'deleted'
            ORDER BY fav.id DESC
        ");
        $stmt->execute(['user_id' => $userId]);
        return $stmt->fetchAll();
    }
}
