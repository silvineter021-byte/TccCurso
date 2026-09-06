<?php

namespace App\Controllers;

use App\Services\Database;
use App\Services\FipeService;

/**
 * RODAX — Controller de Busca, Catálogo e Filtros Avançados
 */
class VehicleController
{
    /**
     * Catálogo Geral de Veículos com Filtros Avançados, Ordenação e Paginação
     */
    public function index(): void
    {
        $pdo = Database::getConnection();

        // Parâmetros de Filtro
        $categorySlug = trim($_GET['categoria'] ?? '');
        $keyword      = trim($_GET['q'] ?? '');
        $brand        = trim($_GET['marca'] ?? '');
        $model        = trim($_GET['modelo'] ?? '');
        $minPrice     = $_GET['preco_min'] ?? '';
        $maxPrice     = $_GET['preco_max'] ?? '';
        $minYear      = $_GET['ano_min'] ?? '';
        $maxYear      = $_GET['ano_max'] ?? '';
        $maxKm        = $_GET['km_max'] ?? '';
        $fuelType     = trim($_GET['combustivel'] ?? '');
        $transmission = trim($_GET['transmissao'] ?? '');
        $state        = trim($_GET['estado'] ?? '');
        $city         = trim($_GET['cidade'] ?? '');
        $sellerType   = trim($_GET['vendedor'] ?? '');

        // Filtros Específicos por Categoria
        $bodyStyle     = trim($_GET['carroceria'] ?? '');
        $minCc         = $_GET['cc_min'] ?? '';
        $traction      = trim($_GET['tracao'] ?? '');
        $cabinType     = trim($_GET['cabine'] ?? '');
        $implementType = trim($_GET['tipo_implemento'] ?? '');

        // Ordenação e Paginação
        $sort = $_GET['ordem'] ?? 'recentes';
        $page = max(1, (int)($_GET['pagina'] ?? 1));
        $limit = 12;
        $offset = ($page - 1) * $limit;

        // Construção da Query SQL Dinâmica com Prepared Statements
        $where = ["v.status = 'active'"];
        $params = [];

        if (!empty($categorySlug)) {
            $where[] = "vt.slug = :category_slug";
            $params['category_slug'] = $categorySlug;
        }

        if (!empty($keyword)) {
            $where[] = "(v.title LIKE :kw1 OR v.description LIKE :kw2 OR v.brand LIKE :kw3 OR v.model LIKE :kw4 OR v.version LIKE :kw5)";
            $kwVal = '%' . $keyword . '%';
            $params['kw1'] = $kwVal;
            $params['kw2'] = $kwVal;
            $params['kw3'] = $kwVal;
            $params['kw4'] = $kwVal;
            $params['kw5'] = $kwVal;
        }

        if (!empty($brand)) {
            $where[] = "v.brand = :brand";
            $params['brand'] = $brand;
        }

        if (!empty($model)) {
            $where[] = "v.model = :model";
            $params['model'] = $model;
        }

        if ($minPrice !== '') {
            $where[] = "v.price >= :min_price";
            $params['min_price'] = (float)$minPrice;
        }

        if ($maxPrice !== '') {
            $where[] = "v.price <= :max_price";
            $params['max_price'] = (float)$maxPrice;
        }

        if ($minYear !== '') {
            $where[] = "v.year_model >= :min_year";
            $params['min_year'] = (int)$minYear;
        }

        if ($maxYear !== '') {
            $where[] = "v.year_model <= :max_year";
            $params['max_year'] = (int)$maxYear;
        }

        if ($maxKm !== '') {
            $where[] = "v.mileage <= :max_km";
            $params['max_km'] = (int)$maxKm;
        }

        if (!empty($fuelType)) {
            $where[] = "v.fuel_type = :fuel_type";
            $params['fuel_type'] = $fuelType;
        }

        if (!empty($transmission)) {
            $where[] = "v.transmission = :transmission";
            $params['transmission'] = $transmission;
        }

        if (!empty($state)) {
            $where[] = "v.state = :state";
            $params['state'] = strtoupper($state);
        }

        if (!empty($city)) {
            $where[] = "v.city LIKE :city";
            $params['city'] = '%' . $city . '%';
        }

        if (!empty($sellerType)) {
            $where[] = "v.seller_type = :seller_type";
            $params['seller_type'] = $sellerType;
        }

        // Filtros de Categoria
        if (!empty($bodyStyle)) {
            $where[] = "v.body_style = :body_style";
            $params['body_style'] = $bodyStyle;
        }

        if ($minCc !== '') {
            $where[] = "v.engine_capacity_cc >= :min_cc";
            $params['min_cc'] = (int)$minCc;
        }

        if (!empty($traction)) {
            $where[] = "v.traction = :traction";
            $params['traction'] = $traction;
        }

        if (!empty($cabinType)) {
            $where[] = "v.cabin_type = :cabin_type";
            $params['cabin_type'] = $cabinType;
        }

        if (!empty($implementType)) {
            $where[] = "v.implement_type = :implement_type";
            $params['implement_type'] = $implementType;
        }

        $whereClause = implode(" AND ", $where);

        // Cláusula de Ordenação
        $orderBy = match ($sort) {
            'preco_asc'  => 'v.price ASC',
            'preco_desc' => 'v.price DESC',
            'km_asc'     => 'v.mileage ASC',
            'ano_desc'    => 'v.year_model DESC',
            default      => 'v.id DESC'
        };

        // Query de Contagem Total para Paginação
        $countSql = "
            SELECT COUNT(*) 
            FROM vehicles v
            JOIN vehicle_types vt ON v.vehicle_type_id = vt.id
            WHERE {$whereClause}
        ";
        $stmtCount = $pdo->prepare($countSql);
        $stmtCount->execute($params);
        $totalResults = (int)$stmtCount->fetchColumn();

        $totalPages = max(1, ceil($totalResults / $limit));

        // Query Principal com Limit e Offset
        $sql = "
            SELECT 
                v.*, 
                vt.name AS category_name, vt.slug AS category_slug,
                img.image_path AS main_image
            FROM vehicles v
            JOIN vehicle_types vt ON v.vehicle_type_id = vt.id
            LEFT JOIN vehicle_images img ON v.id = img.vehicle_id AND img.is_main = 1
            WHERE {$whereClause}
            ORDER BY {$orderBy}
            LIMIT {$limit} OFFSET {$offset}
        ";

        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);
        $vehicles = $stmt->fetchAll();

        // Categorias para os seletores
        $categories = $pdo->query("SELECT * FROM vehicle_types ORDER BY id ASC")->fetchAll();

        view('vehicles.index', [
            'pageTitle'    => 'Pesquisa de Veículos — RODAX',
            'vehicles'     => $vehicles,
            'categories'   => $categories,
            'filters'      => $_GET,
            'totalResults' => $totalResults,
            'currentPage'  => $page,
            'totalPages'   => $totalPages,
            'currentSort'  => $sort
        ]);
    }

    /**
     * Página de Detalhes do Veículo (URL Amigável)
     */
    public function show(string $slug): void
    {
        $pdo = Database::getConnection();

        $stmt = $pdo->prepare("
            SELECT 
                v.*, 
                vt.name AS category_name, vt.slug AS category_slug,
                u.name AS seller_name, u.phone AS seller_phone, u.email AS seller_email,
                u.user_type AS seller_user_type, u.created_at AS seller_since
            FROM vehicles v
            JOIN vehicle_types vt ON v.vehicle_type_id = vt.id
            JOIN users u ON v.user_id = u.id
            WHERE v.slug = :slug OR v.id = :id
            LIMIT 1
        ");
        $stmt->execute([
            'slug' => $slug,
            'id'   => is_numeric($slug) ? (int)$slug : 0
        ]);
        $vehicle = $stmt->fetch();

        if (!$vehicle) {
            http_response_code(404);
            view('errors.404', ['message' => 'Anúncio de veículo não encontrado no RODAX.']);
            return;
        }

        // Incrementar contador de visualizações
        $pdo->prepare("UPDATE vehicles SET views_count = views_count + 1 WHERE id = :id")->execute(['id' => $vehicle['id']]);

        // Galeria de imagens
        $stmtImages = $pdo->prepare("SELECT * FROM vehicle_images WHERE vehicle_id = :id ORDER BY is_main DESC, display_order ASC");
        $stmtImages->execute(['id' => $vehicle['id']]);
        $images = $stmtImages->fetchAll();

        // Opcionais
        $stmtFeatures = $pdo->prepare("
            SELECT f.name, f.category
            FROM vehicle_features vf
            JOIN features f ON vf.feature_id = f.id
            WHERE vf.vehicle_id = :id
        ");
        $stmtFeatures->execute(['id' => $vehicle['id']]);
        $features = $stmtFeatures->fetchAll();

        // Consulta de Referência FIPE via Cache MySQL
        $fipeData = null;
        if (!empty($vehicle['fipe_code'])) {
            $stmtFipe = $pdo->prepare("
                SELECT fipe_price, reference_month, raw_json 
                FROM fipe_vehicle_details 
                WHERE fipe_code = :code AND model_year = :year
                LIMIT 1
            ");
            $stmtFipe->execute(['code' => $vehicle['fipe_code'], 'year' => $vehicle['year_model']]);
            $fipeRow = $stmtFipe->fetch();
            if ($fipeRow) {
                $fipeData = json_decode($fipeRow['raw_json'], true);
            }
        }

        view('vehicles.show', [
            'pageTitle' => $vehicle['title'] . ' — RODAX',
            'vehicle'   => $vehicle,
            'images'    => $images,
            'features'  => $features,
            'fipeData'  => $fipeData
        ]);
    }
}
