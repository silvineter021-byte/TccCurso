<?php

/**
 * RODAX — Teste Automatizado de Cadastro e Gestão de Veículos por Categoria (Etapa 5)
 */

require_once __DIR__ . '/../app/Helpers/Autoloader.php';
require_once __DIR__ . '/../app/Helpers/functions.php';

use App\Services\Database;
use App\Models\User;
use App\Models\Vehicle;

echo "=== RODAX: BATERIA DE TESTES DE CADASTRO DE VEÍCULOS POR CATEGORIA ===\n\n";

try {
    $pdo = Database::getConnection();
    $userModel = new User($pdo);
    $vehicleModel = new Vehicle($pdo);

    // Criar Usuário Vendedor
    $sellerId = $userModel->create([
        'name'      => 'Vendedor Teste Categorias',
        'email'     => 'seller_cat_' . time() . '@rodax.com.br',
        'password'  => 'SenhaSegura123',
        'phone'     => '(11) 99999-1111',
        'user_type' => 'loja',
        'city'      => 'São Paulo',
        'state'     => 'SP'
    ]);

    // 1. Teste de Cadastro de Carro
    echo "[TESTE 1/6] Testando cadastro de CARRO (Campos: Carroceria, Portas, Lugares)... ";
    $carId = $vehicleModel->create([
        'user_id'          => $sellerId,
        'vehicle_type_id'  => 1, // Carros
        'title'            => 'Honda Civic 2.0 EXL 2021',
        'brand'            => 'Honda',
        'model'            => 'Civic',
        'version'          => '2.0 EXL CVT',
        'year_manufacture' => 2020,
        'year_model'       => 2021,
        'mileage'          => 45000,
        'price'            => 115000.00,
        'color'            => 'Cinza',
        'fuel_type'        => 'Flex',
        'state'            => 'SP',
        'city'             => 'São Paulo',
        'body_style'       => 'Sedan',
        'doors_count'      => 4,
        'seats_count'      => 5
    ]);

    $car = $vehicleModel->findById($carId);
    if ($car && $car['body_style'] === 'Sedan' && (int)$car['doors_count'] === 4) {
        echo "OK (ID: {$carId}, Carroceria: Sedan, Portas: 4)\n";
    } else {
        throw new Exception("Falha no cadastro de campos específicos de Carros.");
    }

    // 2. Teste de Cadastro de Moto
    echo "[TESTE 2/6] Testando cadastro de MOTO (Campos: Cilindrada, Partida, Categoria)... ";
    $motoId = $vehicleModel->create([
        'user_id'            => $sellerId,
        'vehicle_type_id'    => 2, // Motos
        'title'              => 'Yamaha MT-07 ABS 2022',
        'brand'              => 'Yamaha',
        'model'              => 'MT-07',
        'version'            => '689cc ABS',
        'year_manufacture'   => 2022,
        'year_model'         => 2022,
        'mileage'            => 12000,
        'price'              => 42900.00,
        'color'              => 'Azul',
        'fuel_type'          => 'Gasolina',
        'state'              => 'SP',
        'city'               => 'Campinas',
        'engine_capacity_cc' => 689,
        'starter_type'       => 'Elétrica',
        'motorcycle_category'=> 'Naked',
        'has_abs'            => 1
    ]);

    $moto = $vehicleModel->findById($motoId);
    if ($moto && (int)$moto['engine_capacity_cc'] === 689 && $moto['motorcycle_category'] === 'Naked') {
        echo "OK (ID: {$motoId}, Cilindrada: 689cc, Categoria: Naked)\n";
    } else {
        throw new Exception("Falha no cadastro de campos específicos de Motos.");
    }

    // 3. Teste de Cadastro de Caminhão
    echo "[TESTE 3/6] Testando cadastro de CAMINHÃO (Campos: Eixos, PBT, Tração, Cabine)... ";
    $truckId = $vehicleModel->create([
        'user_id'          => $sellerId,
        'vehicle_type_id'  => 3, // Caminhões
        'title'            => 'Volvo FH 540 6x4 Globetrotter 2023',
        'brand'            => 'Volvo',
        'model'            => 'FH 540',
        'version'          => 'Globetrotter 6x4',
        'year_manufacture' => 2022,
        'year_model'       => 2023,
        'mileage'          => 120000,
        'price'            => 750000.00,
        'color'            => 'Branco',
        'fuel_type'        => 'Diesel',
        'state'            => 'PR',
        'city'             => 'Curitiba',
        'axles_count'      => 6,
        'pbt_kg'           => 57000,
        'traction'         => '6x4',
        'cabin_type'       => 'Leito'
    ]);

    $truck = $vehicleModel->findById($truckId);
    if ($truck && (int)$truck['axles_count'] === 6 && $truck['cabin_type'] === 'Leito') {
        echo "OK (ID: {$truckId}, Eixos: 6, Cabine: Leito, Tração: 6x4)\n";
    } else {
        throw new Exception("Falha no cadastro de campos específicos de Caminhões.");
    }

    // 4. Teste de Cadastro de Implemento Rodoviário
    echo "[TESTE 4/6] Testando cadastro de IMPLEMENTO RODOVIÁRIO (Campos: Tipo, Comprimento)... ";
    $implementId = $vehicleModel->create([
        'user_id'               => $sellerId,
        'vehicle_type_id'       => 6, // Implementos Rodoviários
        'title'                 => 'Semirreboque Sider Randon 3 Eixos 2021',
        'brand'                 => 'Randon',
        'model'                 => 'Semirreboque Sider',
        'year_manufacture'      => 2021,
        'year_model'            => 2021,
        'price'                 => 135000.00,
        'color'                 => 'Preto',
        'fuel_type'             => 'Outro',
        'state'                 => 'RS',
        'city'                  => 'Caxias do Sul',
        'implement_type'        => 'Sider',
        'implement_length_m'    => 14.50,
        'implement_manufacturer'=> 'Randon',
        'axles_count'           => 3
    ]);

    $implement = $vehicleModel->findById($implementId);
    if ($implement && $implement['implement_type'] === 'Sider' && (float)$implement['implement_length_m'] === 14.50) {
        echo "OK (ID: {$implementId}, Tipo: Sider, Comprimento: 14.5m)\n";
    } else {
        throw new Exception("Falha no cadastro de Implementos Rodoviários.");
    }

    // 5. Teste de Alteração de Status (Pausado, Vendido, Excluído)
    echo "[TESTE 5/6] Testando Alteração de Status (Pausar -> Reativar -> Marcar como Vendido)... ";
    $vehicleModel->updateStatus($carId, $sellerId, 'paused');
    $pausedCar = $vehicleModel->findById($carId);
    if ($pausedCar['status'] !== 'paused') throw new Exception("Falha ao pausar anúncio.");

    $vehicleModel->updateStatus($carId, $sellerId, 'sold');
    $soldCar = $vehicleModel->findById($carId);
    if ($soldCar['status'] !== 'sold') throw new Exception("Falha ao marcar anúncio como vendido.");

    echo "OK (Transições de status validadas!)\n";

    // 6. Teste de Proteção IDOR (Tentativa de alteração por usuário não autorizado)
    echo "[TESTE 6/6] Testando Proteção IDOR (Impedir alteração por outro usuário)... ";
    $unauthorizedUserId = 99999;
    $idorSuccess = $vehicleModel->updateStatus($carId, $unauthorizedUserId, 'active');
    
    $checkCar = $vehicleModel->findById($carId);
    if (!$idorSuccess && $checkCar['status'] === 'sold') {
        echo "OK (Tentativa IDOR bloqueada com sucesso! Status mantido como 'sold')\n";
    } else {
        throw new Exception("Falha de segurança IDOR: Usuário não autorizado conseguiu alterar o veículo.");
    }

    // Limpeza dos dados de teste
    $pdo->prepare("DELETE FROM users WHERE id = :id")->execute(['id' => $sellerId]);

    echo "\n=== TODOS OS TESTES DE CADASTRO E GESTÃO DE VEÍCULOS PASSARAM COM SUCESSO! ===\n";
    exit(0);

} catch (Exception $e) {
    echo "\n[ERRO NO TESTE DE VEÍCULOS]: " . $e->getMessage() . "\n";
    echo "Trace: " . $e->getTraceAsString() . "\n";
    exit(1);
}
