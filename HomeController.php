<?php

namespace App\Controllers;

use App\Services\Database;

/**
 * RODAX — Controller da Homepage
 */
class HomeController
{
    public function index(): void
    {
        $pdo = Database::getConnection();

        // Buscar categorias ativas
        $stmtTypes = $pdo->query("SELECT * FROM vehicle_types ORDER BY id ASC");
        $categories = $stmtTypes->fetchAll();

        // Buscar anúncios em destaque / recentes
        $stmtVehicles = $pdo->query("
            SELECT 
                v.*, 
                vt.name AS category_name,
                img.image_path AS main_image
            FROM vehicles v
            JOIN vehicle_types vt ON v.vehicle_type_id = vt.id
            LEFT JOIN vehicle_images img ON v.id = img.vehicle_id AND img.is_main = 1
            WHERE v.status = 'active'
            ORDER BY v.id DESC
            LIMIT 8
        ");
        $featuredVehicles = $stmtVehicles->fetchAll();

        // Estatísticas para a homepage
        $totalVehicles = $pdo->query("SELECT COUNT(*) FROM vehicles WHERE status = 'active'")->fetchColumn();

        view('home.index', [
            'pageTitle'        => 'RODAX — Seu próximo veículo está aqui',
            'categories'       => $categories,
            'featuredVehicles' => $featuredVehicles,
            'totalVehicles'    => $totalVehicles
        ]);
    }
}
