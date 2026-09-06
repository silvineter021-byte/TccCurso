<?php

/**
 * RODAX — Teste Automatizado do Sistema de Favoritos (Etapa 9)
 */

require_once __DIR__ . '/../app/Helpers/Autoloader.php';
require_once __DIR__ . '/../app/Helpers/functions.php';

use App\Services\Database;
use App\Models\User;
use App\Models\Vehicle;
use App\Models\Favorite;

echo "=== RODAX: BATERIA DE TESTES DO SISTEMA DE FAVORITOS ===\n\n";

try {
    $pdo = Database::getConnection();
    $userModel = new User($pdo);
    $vehicleModel = new Vehicle($pdo);
    $favoriteModel = new Favorite($pdo);

    // 1. Criar Vendedor, Comprador e Veículo de Teste
    $sellerId = $userModel->create([
        'name'      => 'Vendedor Teste Fav',
        'email'     => 'seller_fav_' . time() . '@rodax.com.br',
        'password'  => 'Senha123',
        'user_type' => 'particular'
    ]);

    $buyerId = $userModel->create([
        'name'      => 'Comprador Teste Fav',
        'email'     => 'buyer_fav_' . time() . '@rodax.com.br',
        'password'  => 'Senha123',
        'user_type' => 'particular'
    ]);

    $vehicleId = $vehicleModel->create([
        'user_id'          => $sellerId,
        'vehicle_type_id'  => 1,
        'title'            => 'Ford Mustang GT 5.0 V8 2020',
        'brand'            => 'Ford',
        'model'            => 'Mustang',
        'year_manufacture' => 2020,
        'year_model'       => 2020,
        'mileage'          => 15000,
        'price'            => 380000.00,
        'color'            => 'Vermelho',
        'fuel_type'        => 'Gasolina',
        'state'            => 'SP',
        'city'             => 'São Paulo'
    ]);

    // 2. Teste de Adição aos Favoritos
    echo "[TESTE 1/5] Testando adicionar veículo aos favoritos... ";
    $addResult = $favoriteModel->add($buyerId, $vehicleId);
    $isFav = $favoriteModel->isFavorited($buyerId, $vehicleId);

    if ($addResult && $isFav) {
        echo "OK (Veículo ID: {$vehicleId} adicionado aos favoritos do usuário ID: {$buyerId})\n";
    } else {
        throw new Exception("Falha ao adicionar aos favoritos.");
    }

    // 3. Teste de Prevenção de Duplicidade (Constraint UNIQUE)
    echo "[TESTE 2/5] Testando prevenção de duplicidade em favoritos... ";
    $dupResult = $favoriteModel->add($buyerId, $vehicleId);
    
    $stmtCount = $pdo->prepare("SELECT COUNT(*) FROM favorites WHERE user_id = :u AND vehicle_id = :v");
    $stmtCount->execute(['u' => $buyerId, 'v' => $vehicleId]);

    if ((int)$stmtCount->fetchColumn() === 1) {
        echo "OK (Constraint UNIQUE impediu a duplicação no banco de dados)\n";
    } else {
        throw new Exception("Falha: Registro duplicado no banco de dados.");
    }

    // 4. Teste de Recuperação da Lista de Favoritos do Usuário
    echo "[TESTE 3/5] Testando consulta da lista de favoritos (getUserFavorites)... ";
    $favList = $favoriteModel->getUserFavorites($buyerId);

    if (count($favList) === 1 && $favList[0]['title'] === 'Ford Mustang GT 5.0 V8 2020') {
        echo "OK (Veículo '{$favList[0]['title']}' listado nos favoritos)\n";
    } else {
        throw new Exception("Falha ao recuperar a lista de favoritos do usuário.");
    }

    // 5. Teste de Alternância (Toggle / Remoção de Favorito)
    echo "[TESTE 4/5] Testando remoção dos favoritos (toggle)... ";
    $favoriteModel->toggle($buyerId, $vehicleId);
    $isFavAfterToggle = $favoriteModel->isFavorited($buyerId, $vehicleId);

    if (!$isFavAfterToggle) {
        echo "OK (Veículo removido dos favoritos com sucesso)\n";
    } else {
        throw new Exception("Falha na remoção do veículo dos favoritos.");
    }

    // 6. Teste de isolamento por Usuário (IDOR Check)
    echo "[TESTE 5/5] Testando isolamento entre usuários (Comprador vs Outro Usuário)... ";
    $isFavOtherUser = $favoriteModel->isFavorited($sellerId, $vehicleId);
    if (!$isFavOtherUser) {
        echo "OK (Favoritos isolados por ID de usuário)\n";
    } else {
        throw new Exception("Falha de isolamento de favoritos entre usuários.");
    }

    // Limpeza de dados do teste
    $pdo->prepare("DELETE FROM users WHERE id IN (:u1, :u2)")->execute(['u1' => $sellerId, 'u2' => $buyerId]);

    echo "\n=== TODOS OS TESTES DO SISTEMA DE FAVORITOS PASSARAM COM SUCESSO! ===\n";
    exit(0);

} catch (Exception $e) {
    echo "\n[ERRO NO TESTE DE FAVORITOS]: " . $e->getMessage() . "\n";
    echo "Trace: " . $e->getTraceAsString() . "\n";
    exit(1);
}
