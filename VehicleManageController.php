<?php

namespace App\Controllers;

use App\Models\Vehicle;
use App\Models\Message;
use App\Services\Database;
use App\Services\ImageUploadService;

/**
 * RODAX — Controller de Gestão e Anúncios de Veículos (Painel do Usuário)
 */
class VehicleManageController
{
    private Vehicle $vehicleModel;
    private ImageUploadService $imageService;
    private Message $messageModel;

    public function __construct()
    {
        $this->vehicleModel = new Vehicle();
        $this->imageService = new ImageUploadService();
        $this->messageModel = new Message();
    }

    /**
     * Dashboard do Usuário — Métricas e Lista de Meus Anúncios
     */
    public function dashboard(): void
    {
        $userId = $_SESSION['user_id'] ?? 0;
        $vehicles = $this->vehicleModel->findByUserId($userId);
        $stats = $this->vehicleModel->getUserStats($userId);
        $unreadMessages = $this->messageModel->getUnreadCount($userId);

        view('dashboard.index', [
            'pageTitle'      => 'Painel do Usuário — RODAX',
            'vehicles'       => $vehicles,
            'stats'          => $stats,
            'unreadMessages' => $unreadMessages
        ]);
    }

    /**
     * Formulário de Criação de Anúncio
     */
    public function create(): void
    {
        $pdo = Database::getConnection();
        $categories = $pdo->query("SELECT * FROM vehicle_types ORDER BY id ASC")->fetchAll();

        view('vehicles.create', [
            'pageTitle'  => 'Anunciar Veículo — RODAX',
            'categories' => $categories
        ]);
    }

    /**
     * Salva o Novo Anúncio no Banco de Dados
     */
    public function store(): void
    {
        $userId        = $_SESSION['user_id'] ?? 0;
        $vehicleTypeId = (int)($_POST['vehicle_type_id'] ?? 0);
        $title         = trim($_POST['title'] ?? '');
        $brand         = trim($_POST['brand'] ?? '');
        $model         = trim($_POST['model'] ?? '');
        $version       = trim($_POST['version'] ?? '');
        $yearManuf     = (int)($_POST['year_manufacture'] ?? 0);
        $yearModel     = (int)($_POST['year_model'] ?? 0);
        $mileage       = (int)($_POST['mileage'] ?? 0);
        $price         = (float)str_replace(['R$', '.', ' '], '', str_replace(',', '.', $_POST['price'] ?? '0'));
        $color         = trim($_POST['color'] ?? '');
        $fuelType      = trim($_POST['fuel_type'] ?? '');
        $state         = strtoupper(trim($_POST['state'] ?? ''));
        $city          = trim($_POST['city'] ?? '');
        $description   = trim($_POST['description'] ?? '');

        // Validações no PHP
        $errors = [];

        if ($vehicleTypeId <= 0) $errors[] = "Selecione uma categoria de veículo válida.";
        if (empty($title)) $errors[] = "Informe o título do anúncio.";
        if (empty($brand) || empty($model)) $errors[] = "Informe a marca e o modelo do veículo.";
        if ($yearManuf <= 1900 || $yearModel <= 1900) $errors[] = "Informe os anos de fabricação e modelo válidos.";
        if ($price <= 0) $errors[] = "Informe um preço de venda válido.";
        if (empty($color)) $errors[] = "Informe a cor do veículo.";

        if (!empty($errors)) {
            $_SESSION['flash_error'] = implode('<br>', $errors);
            $this->create();
            return;
        }

        // Consultar Referência FIPE se houver código FIPE
        $fipeCode = $_POST['fipe_code'] ?? null;
        $fipeRefPrice = null;

        if (!empty($fipeCode)) {
            $pdo = Database::getConnection();
            $stmtFipe = $pdo->prepare("SELECT fipe_price FROM fipe_vehicle_details WHERE fipe_code = :code LIMIT 1");
            $stmtFipe->execute(['code' => $fipeCode]);
            $fipeRefPrice = $stmtFipe->fetchColumn() ?: null;
        }

        $vehicleData = array_merge($_POST, [
            'user_id'              => $userId,
            'vehicle_type_id'      => $vehicleTypeId,
            'title'                => $title,
            'brand'                => $brand,
            'model'                => $model,
            'version'              => $version,
            'year_manufacture'     => $yearManuf,
            'year_model'           => $yearModel,
            'mileage'              => $mileage,
            'price'                => $price,
            'fipe_code'            => $fipeCode,
            'fipe_reference_price' => $fipeRefPrice,
            'color'                => $color,
            'fuel_type'            => $fuelType,
            'state'                => $state,
            'city'                 => $city,
            'description'          => $description,
            'seller_type'          => $_SESSION['user_type'] ?? 'particular'
        ]);

        $vehicleId = $this->vehicleModel->create($vehicleData);

        if (!empty($_FILES['images'])) {
            $this->imageService->uploadVehicleImages($vehicleId, $userId, $_FILES['images']);
        }

        $_SESSION['flash_success'] = "Anúncio cadastrado com sucesso! Gerencie as fotos do seu veículo.";
        redirect("/veiculos/fotos/{$vehicleId}");
    }

    /**
     * Formulário de Edição de Anúncio com Proteção IDOR
     */
    public function edit(string $vehicleId): void
    {
        $userId  = $_SESSION['user_id'] ?? 0;
        $vehicle = $this->vehicleModel->findById((int)$vehicleId);

        if (!$vehicle || (int)$vehicle['user_id'] !== $userId) {
            $_SESSION['flash_error'] = "Você não possui autorização para editar este anúncio.";
            redirect('/painel');
        }

        $pdo = Database::getConnection();
        $categories = $pdo->query("SELECT * FROM vehicle_types ORDER BY id ASC")->fetchAll();

        view('vehicles.edit', [
            'pageTitle'  => 'Editar Anúncio — ' . $vehicle['title'],
            'vehicle'    => $vehicle,
            'categories' => $categories
        ]);
    }

    /**
     * Processa a Atualização de um Anúncio (POST) com Proteção IDOR
     */
    public function update(string $vehicleId): void
    {
        $userId    = $_SESSION['user_id'] ?? 0;
        $vehicleId = (int)$vehicleId;

        $price = (float)str_replace(['R$', '.', ' '], '', str_replace(',', '.', $_POST['price'] ?? '0'));
        $_POST['price'] = $price;

        $success = $this->vehicleModel->update($vehicleId, $userId, $_POST);

        if ($success) {
            $_SESSION['flash_success'] = "Anúncio atualizado com sucesso!";
            redirect('/painel');
        } else {
            $_SESSION['flash_error'] = "Não foi possível atualizar o anúncio. Operação não autorizada.";
            redirect("/veiculos/editar/{$vehicleId}");
        }
    }

    /**
     * Tela de Gerenciamento de Fotos do Veículo
     */
    public function manageImages(string $vehicleId): void
    {
        $userId = $_SESSION['user_id'] ?? 0;
        $vehicle = $this->vehicleModel->findById((int)$vehicleId);

        if (!$vehicle || (int)$vehicle['user_id'] !== $userId) {
            $_SESSION['flash_error'] = "Veículo não encontrado ou não autorizado.";
            redirect('/painel');
        }

        $pdo = Database::getConnection();
        $stmtImages = $pdo->prepare("SELECT * FROM vehicle_images WHERE vehicle_id = :id ORDER BY is_main DESC, display_order ASC");
        $stmtImages->execute(['id' => $vehicle['id']]);
        $images = $stmtImages->fetchAll();

        view('vehicles.images', [
            'pageTitle' => 'Gerenciar Fotos — ' . $vehicle['title'],
            'vehicle'   => $vehicle,
            'images'    => $images
        ]);
    }

    /**
     * Processa Upload de Fotos
     */
    public function uploadImages(): void
    {
        $userId    = $_SESSION['user_id'] ?? 0;
        $vehicleId = (int)($_POST['vehicle_id'] ?? 0);

        if (!empty($_FILES['images'])) {
            $result = $this->imageService->uploadVehicleImages($vehicleId, $userId, $_FILES['images']);
            if ($result['success']) {
                $_SESSION['flash_success'] = count($result['uploaded']) . " foto(s) enviada(s) com sucesso!";
            }
            if (!empty($result['errors'])) {
                $_SESSION['flash_error'] = implode('<br>', $result['errors']);
            }
        }
        redirect("/veiculos/fotos/{$vehicleId}");
    }

    /**
     * Define Foto Principal
     */
    public function setMainImage(): void
    {
        $userId    = $_SESSION['user_id'] ?? 0;
        $vehicleId = (int)($_POST['vehicle_id'] ?? 0);
        $imageId   = (int)($_POST['image_id'] ?? 0);

        $success = $this->imageService->setMainImage($imageId, $vehicleId, $userId);
        if ($success) {
            $_SESSION['flash_success'] = "Foto principal definida com sucesso!";
        } else {
            $_SESSION['flash_error'] = "Operação não autorizada.";
        }
        redirect("/veiculos/fotos/{$vehicleId}");
    }

    /**
     * Exclui Foto
     */
    public function deleteImage(): void
    {
        $userId    = $_SESSION['user_id'] ?? 0;
        $vehicleId = (int)($_POST['vehicle_id'] ?? 0);
        $imageId   = (int)($_POST['image_id'] ?? 0);

        $success = $this->imageService->deleteImage($imageId, $vehicleId, $userId);
        if ($success) {
            $_SESSION['flash_success'] = "Foto excluída com sucesso!";
        } else {
            $_SESSION['flash_error'] = "Falha ao excluir foto.";
        }
        redirect("/veiculos/fotos/{$vehicleId}");
    }

    /**
     * Altera Status do Anúncio
     */
    public function changeStatus(): void
    {
        $userId    = $_SESSION['user_id'] ?? 0;
        $vehicleId = (int)($_POST['vehicle_id'] ?? 0);
        $status    = $_POST['status'] ?? '';

        $success = $this->vehicleModel->updateStatus($vehicleId, $userId, $status);
        if ($success) {
            $_SESSION['flash_success'] = "Status do anúncio atualizado com sucesso.";
        } else {
            $_SESSION['flash_error'] = "Operação não autorizada.";
        }
        redirect('/painel');
    }

    /**
     * Exclui Anúncio (Soft Delete)
     */
    public function destroy(): void
    {
        $userId    = $_SESSION['user_id'] ?? 0;
        $vehicleId = (int)($_POST['vehicle_id'] ?? 0);

        $success = $this->vehicleModel->updateStatus($vehicleId, $userId, 'deleted');
        if ($success) {
            $_SESSION['flash_success'] = "Anúncio excluído com sucesso.";
        } else {
            $_SESSION['flash_error'] = "Operação não autorizada.";
        }
        redirect('/painel');
    }
}
