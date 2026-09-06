<?php

namespace App\Models;

use App\Services\Database;
use PDO;
use Exception;

/**
 * RODAX — Model de Anúncios de Veículos
 */
class Vehicle
{
    private PDO $db;

    public function __construct(?PDO $db = null)
    {
        $this->db = $db ?? Database::getConnection();
    }

    /**
     * Busca anúncio por ID
     */
    public function findById(int $id): ?array
    {
        $stmt = $this->db->prepare("
            SELECT v.*, vt.name AS category_name, vt.slug AS category_slug
            FROM vehicles v
            JOIN vehicle_types vt ON v.vehicle_type_id = vt.id
            WHERE v.id = :id
            LIMIT 1
        ");
        $stmt->execute(['id' => $id]);
        $vehicle = $stmt->fetch();
        return $vehicle ?: null;
    }

    /**
     * Busca todos os anúncios de um usuário (Dashboard do Usuário)
     */
    public function findByUserId(int $userId): array
    {
        $stmt = $this->db->prepare("
            SELECT 
                v.*, 
                vt.name AS category_name,
                img.image_path AS main_image,
                (SELECT COUNT(*) FROM favorites f WHERE f.vehicle_id = v.id) AS total_favorites
            FROM vehicles v
            JOIN vehicle_types vt ON v.vehicle_type_id = vt.id
            LEFT JOIN vehicle_images img ON v.id = img.vehicle_id AND img.is_main = 1
            WHERE v.user_id = :user_id AND v.status != 'deleted'
            ORDER BY v.id DESC
        ");
        $stmt->execute(['user_id' => $userId]);
        return $stmt->fetchAll();
    }

    /**
     * Retorna estatísticas resumidas do usuário para os cards do Dashboard
     */
    public function getUserStats(int $userId): array
    {
        $stmt = $this->db->prepare("
            SELECT 
                SUM(CASE WHEN status = 'active' THEN 1 ELSE 0 END) AS total_active,
                SUM(CASE WHEN status = 'paused' THEN 1 ELSE 0 END) AS total_paused,
                SUM(CASE WHEN status = 'sold' THEN 1 ELSE 0 END) AS total_sold,
                SUM(views_count) AS total_views
            FROM vehicles
            WHERE user_id = :user_id AND status != 'deleted'
        ");
        $stmt->execute(['user_id' => $userId]);
        $stats = $stmt->fetch();

        return [
            'active' => (int)($stats['total_active'] ?? 0),
            'paused' => (int)($stats['total_paused'] ?? 0),
            'sold'   => (int)($stats['total_sold'] ?? 0),
            'views'  => (int)($stats['total_views'] ?? 0)
        ];
    }

    /**
     * Cria um novo anúncio de veículo com campos gerais e específicos por categoria
     */
    public function create(array $data): int
    {
        $sql = "
            INSERT INTO vehicles (
                user_id, vehicle_type_id, title, slug, brand, model, version,
                year_manufacture, year_model, mileage, price, fipe_code, fipe_reference_price,
                color, fuel_type, engine, cylinders, power_hp, torque_kgfm, transmission,
                steering, traction, seller_type, state, city, description, status,
                body_style, doors_count, seats_count, engine_capacity_cc, starter_type,
                motorcycle_category, brakes_type, has_abs, axles_count, pbt_kg,
                load_capacity_kg, cabin_type, passenger_capacity, implement_type,
                implement_length_m, implement_manufacturer
            ) VALUES (
                :user_id, :vehicle_type_id, :title, :slug, :brand, :model, :version,
                :year_manufacture, :year_model, :mileage, :price, :fipe_code, :fipe_reference_price,
                :color, :fuel_type, :engine, :cylinders, :power_hp, :torque_kgfm, :transmission,
                :steering, :traction, :seller_type, :state, :city, :description, :status,
                :body_style, :doors_count, :seats_count, :engine_capacity_cc, :starter_type,
                :motorcycle_category, :brakes_type, :has_abs, :axles_count, :pbt_kg,
                :load_capacity_kg, :cabin_type, :passenger_capacity, :implement_type,
                :implement_length_m, :implement_manufacturer
            )
        ";

        $slugBase = slugify($data['brand'] . ' ' . $data['model'] . ' ' . $data['year_model']);
        $uniqueSlug = $slugBase . '-' . time() . '-' . rand(100, 999);

        $stmt = $this->db->prepare($sql);
        $stmt->execute([
            'user_id'              => (int)$data['user_id'],
            'vehicle_type_id'      => (int)$data['vehicle_type_id'],
            'title'                => trim($data['title']),
            'slug'                 => $uniqueSlug,
            'brand'                => trim($data['brand']),
            'model'                => trim($data['model']),
            'version'              => !empty($data['version']) ? trim($data['version']) : null,
            'year_manufacture'     => (int)$data['year_manufacture'],
            'year_model'           => (int)$data['year_model'],
            'mileage'              => (int)($data['mileage'] ?? 0),
            'price'                => (float)$data['price'],
            'fipe_code'            => !empty($data['fipe_code']) ? trim($data['fipe_code']) : null,
            'fipe_reference_price' => !empty($data['fipe_reference_price']) ? (float)$data['fipe_reference_price'] : null,
            'color'                => trim($data['color']),
            'fuel_type'            => trim($data['fuel_type']),
            'engine'               => !empty($data['engine']) ? trim($data['engine']) : null,
            'cylinders'            => !empty($data['cylinders']) ? (int)$data['cylinders'] : null,
            'power_hp'             => !empty($data['power_hp']) ? (int)$data['power_hp'] : null,
            'torque_kgfm'          => !empty($data['torque_kgfm']) ? (float)$data['torque_kgfm'] : null,
            'transmission'         => !empty($data['transmission']) ? trim($data['transmission']) : null,
            'steering'             => !empty($data['steering']) ? trim($data['steering']) : null,
            'traction'             => !empty($data['traction']) ? trim($data['traction']) : null,
            'seller_type'          => in_array($data['seller_type'] ?? '', ['particular', 'loja']) ? $data['seller_type'] : 'particular',
            'state'                => strtoupper(trim($data['state'])),
            'city'                 => trim($data['city']),
            'description'          => trim($data['description'] ?? ''),
            'status'               => 'active',
            
            // Campos Categoria Específica
            'body_style'           => $data['body_style'] ?? null,
            'doors_count'          => !empty($data['doors_count']) ? (int)$data['doors_count'] : null,
            'seats_count'          => !empty($data['seats_count']) ? (int)$data['seats_count'] : null,
            'engine_capacity_cc'   => !empty($data['engine_capacity_cc']) ? (int)$data['engine_capacity_cc'] : null,
            'starter_type'         => $data['starter_type'] ?? null,
            'motorcycle_category'  => $data['motorcycle_category'] ?? null,
            'brakes_type'          => $data['brakes_type'] ?? null,
            'has_abs'              => !empty($data['has_abs']) ? 1 : 0,
            'axles_count'          => !empty($data['axles_count']) ? (int)$data['axles_count'] : null,
            'pbt_kg'               => !empty($data['pbt_kg']) ? (int)$data['pbt_kg'] : null,
            'load_capacity_kg'     => !empty($data['load_capacity_kg']) ? (int)$data['load_capacity_kg'] : null,
            'cabin_type'           => $data['cabin_type'] ?? null,
            'passenger_capacity'   => !empty($data['passenger_capacity']) ? (int)$data['passenger_capacity'] : null,
            'implement_type'       => $data['implement_type'] ?? null,
            'implement_length_m'   => !empty($data['implement_length_m']) ? (float)$data['implement_length_m'] : null,
            'implement_manufacturer' => $data['implement_manufacturer'] ?? null
        ]);

        return (int)$this->db->lastInsertId();
    }

    /**
     * Atualiza dados de um anúncio existente com checagem de autorização (IDOR)
     */
    public function update(int $vehicleId, int $userId, array $data): bool
    {
        $sql = "
            UPDATE vehicles SET
                title = :title,
                brand = :brand,
                model = :model,
                version = :version,
                year_manufacture = :year_manufacture,
                year_model = :year_model,
                mileage = :mileage,
                price = :price,
                color = :color,
                fuel_type = :fuel_type,
                engine = :engine,
                transmission = :transmission,
                state = :state,
                city = :city,
                description = :description,
                body_style = :body_style,
                doors_count = :doors_count,
                seats_count = :seats_count,
                engine_capacity_cc = :engine_capacity_cc,
                axles_count = :axles_count
            WHERE id = :id AND user_id = :user_id
        ";

        $stmt = $this->db->prepare($sql);
        $stmt->execute([
            'title'              => trim($data['title']),
            'brand'              => trim($data['brand']),
            'model'              => trim($data['model']),
            'version'            => !empty($data['version']) ? trim($data['version']) : null,
            'year_manufacture'   => (int)$data['year_manufacture'],
            'year_model'         => (int)$data['year_model'],
            'mileage'            => (int)$data['mileage'],
            'price'              => (float)$data['price'],
            'color'              => trim($data['color']),
            'fuel_type'          => trim($data['fuel_type']),
            'engine'             => !empty($data['engine']) ? trim($data['engine']) : null,
            'transmission'       => !empty($data['transmission']) ? trim($data['transmission']) : null,
            'state'              => strtoupper(trim($data['state'])),
            'city'               => trim($data['city']),
            'description'        => trim($data['description'] ?? ''),
            'body_style'         => $data['body_style'] ?? null,
            'doors_count'        => !empty($data['doors_count']) ? (int)$data['doors_count'] : null,
            'seats_count'        => !empty($data['seats_count']) ? (int)$data['seats_count'] : null,
            'engine_capacity_cc' => !empty($data['engine_capacity_cc']) ? (int)$data['engine_capacity_cc'] : null,
            'axles_count'        => !empty($data['axles_count']) ? (int)$data['axles_count'] : null,
            'id'                 => $vehicleId,
            'user_id'            => $userId
        ]);

        return $stmt->rowCount() > 0;
    }

    /**
     * Altera o status do anúncio com verificação estrita de autorização (IDOR)
     */
    public function updateStatus(int $vehicleId, int $userId, string $newStatus): bool
    {
        $allowedStatuses = ['active', 'paused', 'sold', 'deleted'];
        if (!in_array($newStatus, $allowedStatuses)) {
            return false;
        }

        $stmt = $this->db->prepare("
            UPDATE vehicles 
            SET status = :status 
            WHERE id = :id AND user_id = :user_id
        ");
        $stmt->execute([
            'status'  => $newStatus,
            'id'      => $vehicleId,
            'user_id' => $userId
        ]);

        return $stmt->rowCount() > 0;
    }

    /**
     * Retorna o total de anúncios ativos no sistema
     */
    public function countActive(): int
    {
        $stmt = $this->db->query("SELECT COUNT(*) FROM vehicles WHERE status = 'active'");
        return (int)$stmt->fetchColumn();
    }

    /**
     * Busca anúncios para o Painel Administrativo com filtros
     */
    public function adminGetAll(array $filters = []): array
    {
        $sql = "
            SELECT v.*, u.name AS seller_name, u.email AS seller_email, vt.name AS category_name
            FROM vehicles v
            JOIN users u ON v.user_id = u.id
            JOIN vehicle_types vt ON v.vehicle_type_id = vt.id
            WHERE 1=1
        ";

        $params = [];
        if (!empty($filters['status'])) {
            $sql .= " AND v.status = :status";
            $params['status'] = $filters['status'];
        }

        if (!empty($filters['search'])) {
            $sql .= " AND (v.title LIKE :s1 OR v.brand LIKE :s2 OR v.model LIKE :s3 OR u.name LIKE :s4)";
            $searchVal = '%' . trim($filters['search']) . '%';
            $params['s1'] = $searchVal;
            $params['s2'] = $searchVal;
            $params['s3'] = $searchVal;
            $params['s4'] = $searchVal;
        }

        $sql .= " ORDER BY v.id DESC LIMIT 100";

        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);

        return $stmt->fetchAll();
    }

    /**
     * Atualização de status sem restrição de IDOR (exclusivo para Administrador)
     */
    public function adminUpdateStatus(int $vehicleId, string $newStatus): bool
    {
        $allowedStatuses = ['active', 'paused', 'sold', 'deleted'];
        if (!in_array($newStatus, $allowedStatuses)) {
            return false;
        }

        $stmt = $this->db->prepare("UPDATE vehicles SET status = :status WHERE id = :id");
        return $stmt->execute([
            'status' => $newStatus,
            'id'     => $vehicleId
        ]);
    }
}

