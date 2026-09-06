<?php

/**
 * RODAX — Teste Automatizado da Página de Detalhes e Referência FIPE (Etapa 8)
 */

require_once __DIR__ . '/../app/Helpers/Autoloader.php';
require_once __DIR__ . '/../app/Helpers/functions.php';

use App\Services\Database;
use App\Models\User;
use App\Models\Vehicle;
use App\Controllers\VehicleController;

echo "=== RODAX: BATERIA DE TESTES DA PÁGINA DE DETALHES DO VEÍCULO ===\n\n";

try {
    $pdo = Database::getConnection();
    $userModel = new User($pdo);
    $vehicleModel = new Vehicle($pdo);

    // 1. Criar Vendedor e Veículo de Teste com Código FIPE e views_count = 0
    $sellerId = $userModel->create([
        'name'      => 'Vendedor Teste Detalhes',
        'email'     => 'seller_details_' . time() . '@rodax.com.br',
        'password'  => 'Senha123',
        'phone'     => '(11) 95555-4444',
        'user_type' => 'particular',
        'city'      => 'São Paulo',
        'state'     => 'SP'
    ]);

    // Popular FIPE Cache local para simulação
    $fipeCode = '005374-0';
    $yearModel = 2017;
    $pdo->prepare("
        INSERT INTO fipe_vehicle_details (
            fipe_code, brand, model, model_year, fuel, fipe_price, reference_month, vehicle_type_slug, raw_json, cached_at, expires_at
        ) VALUES (
            :code, 'Volkswagen', 'Golf 1.4 TSI Highline', :year, 'Gasolina', 82500.00, 'Setembro de 2026', 'carros',
            '{\"Valor\":\"R$ 82.500,00\",\"Marca\":\"Volkswagen\",\"Modelo\":\"Golf 1.4 TSI\",\"AnoModelo\":2017,\"CodigoFipe\":\"005374-0\",\"MesReferencia\":\"setembro de 2026\"}',
            NOW(), DATE_ADD(NOW(), INTERVAL 30 DAY)
        ) ON DUPLICATE KEY UPDATE fipe_price = 82500.00
    ")->execute(['code' => $fipeCode, 'year' => $yearModel]);

    $vehicleId = $vehicleModel->create([
        'user_id'              => $sellerId,
        'vehicle_type_id'      => 1,
        'title'                => 'Volkswagen Golf 1.4 TSI Highline 2017 Completo',
        'brand'                => 'Volkswagen',
        'model'                => 'Golf',
        'version'              => '1.4 TSI Highline',
        'year_manufacture'     => 2016,
        'year_model'           => 2017,
        'mileage'              => 50000,
        'price'                => 79900.00,
        'fipe_code'            => $fipeCode,
        'fipe_reference_price' => 82500.00,
        'color'                => 'Branco',
        'fuel_type'            => 'Flex',
        'state'                => 'SP',
        'city'                 => 'São Paulo',
        'description'          => 'Veículo em impecável estado de conservação, com revisões na concessionária.',
        'body_style'           => 'Hatch',
        'doors_count'          => 4
    ]);

    $initialVehicle = $vehicleModel->findById($vehicleId);
    $slug = $initialVehicle['slug'];

    // 2. Teste de Visualização e Incremento de views_count
    echo "[TESTE 1/3] Testando renderização da página e incremento de views_count... ";
    ob_start();
    (new VehicleController())->show($slug);
    $html = ob_get_clean();

    $updatedVehicle = $vehicleModel->findById($vehicleId);
    if ((int)$updatedVehicle['views_count'] === 1) {
        echo "OK (Contador de visualizações incrementado para 1)\n";
    } else {
        throw new Exception("Contador views_count não foi incrementado.");
    }

    // 3. Teste de Renderização dos Dados de Referência FIPE e Vendedor
    echo "[TESTE 2/3] Testando exibição do Preço Anunciado vs Referência FIPE... ";
    if (str_contains($html, 'R$ 79.900,00') && str_contains($html, 'R$ 82.500,00') && str_contains($html, '005374-0')) {
        echo "OK (Preço Anunciado R$ 79.900,00 e Ref. FIPE R$ 82.500,00 renderizados!)\n";
    } else {
        throw new Exception("Falha na exibição dos dados de referência FIPE.");
    }

    // 4. Teste de 404 para Slug Inexistente
    echo "[TESTE 3/3] Testando resposta 404 para slug inexistente... ";
    ob_start();
    (new VehicleController())->show('slug-invalido-inexistente-12345');
    $html404 = ob_get_clean();

    if (str_contains($html404, '404') || str_contains($html404, 'não encontrado')) {
        echo "OK (Resposta 404 renderizada corretamente)\n";
    } else {
        throw new Exception("Falha na resposta 404 para página de detalhes inexistente.");
    }

    // Limpeza de dados do teste
    $pdo->prepare("DELETE FROM users WHERE id = :id")->execute(['id' => $sellerId]);

    echo "\n=== TODOS OS TESTES DA PÁGINA DE DETALHES PASSARAM COM SUCESSO! ===\n";
    exit(0);

} catch (Exception $e) {
    if (ob_get_level()) ob_end_clean();
    echo "\n[ERRO NO TESTE DE DETALHES]: " . $e->getMessage() . "\n";
    echo "Trace: " . $e->getTraceAsString() . "\n";
    exit(1);
}
