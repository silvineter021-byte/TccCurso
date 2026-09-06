<?php

namespace App\Controllers;

use App\Models\Favorite;
use App\Models\Vehicle;

/**
 * RODAX — Controller do Sistema de Favoritos
 */
class FavoriteController
{
    private Favorite $favoriteModel;
    private Vehicle $vehicleModel;

    public function __construct()
    {
        $this->favoriteModel = new Favorite();
        $this->vehicleModel = new Vehicle();
    }

    /**
     * Exibe a lista de Veículos Favoritados do Usuário Logado
     */
    public function index(): void
    {
        $userId = $_SESSION['user_id'] ?? 0;
        $favorites = $this->favoriteModel->getUserFavorites($userId);

        view('favorites.index', [
            'pageTitle' => 'Meus Favoritos — RODAX',
            'favorites' => $favorites
        ]);
    }

    /**
     * Alterna Favoritar / Desfavoritar (POST)
     */
    public function toggle(): void
    {
        $userId    = $_SESSION['user_id'] ?? 0;
        $vehicleId = (int)($_POST['vehicle_id'] ?? 0);

        $vehicle = $this->vehicleModel->findById($vehicleId);
        if (!$vehicle) {
            $_SESSION['flash_error'] = "Veículo não encontrado.";
            redirect('/veiculos');
        }

        $isFavNow = $this->favoriteModel->isFavorited($userId, $vehicleId);
        $this->favoriteModel->toggle($userId, $vehicleId);

        if ($isFavNow) {
            $_SESSION['flash_success'] = "Veículo removido dos favoritos.";
        } else {
            $_SESSION['flash_success'] = "Veículo adicionado aos seus favoritos!";
        }

        // Redirecionar de volta para a página anterior
        $referer = $_SERVER['HTTP_REFERER'] ?? baseUrl("veiculos/{$vehicle['slug']}");
        header("Location: {$referer}");
        exit;
    }
}
