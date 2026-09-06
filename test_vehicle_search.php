<?php

/**
 * RODAX — Teste Automatizado de Busca, Filtros Avançados e Paginação (Etapa 7)
 */

require_once __DIR__ . '/../app/Helpers/Autoloader.php';
require_once __DIR__ . '/../app/Helpers/functions.php';

use App\Services\Database;
use App\Models\User;
use App\Models\Vehicle;
use App\Controllers\VehicleController;

echo "=== RODAX: BATERIA DE TESTES DE BUSCA E FILTROS AVANÇADOS ===\n\n";

try {
    $pdo = Database::getConnection();
    $userModel = new User($pdo);
    $vehicleModel = new Vehicle($pdo);

    // Criar Usuário Vendedor para População do Teste
    $sellerId = $userModel->create([
        'name'      => 'Vendedor Teste Filtros',
        'email'     => 'seller_search_' . time() . '@rodax.com.br',
        'password'  => 'Senha123',
        'phone'     => '(11) 96666-5555',
        'user_type' => 'particular',
        'city'      => 'São Paulo',
        'state'     => 'SP'
    ]);

    // Inserir conjunto variado de anúncios de teste
    $v1 = $vehicleModel->create([
        'user_id'          => $sellerId,
        'vehicle_type_id'  => 1, // Carros
        'title'            => 'Volkswagen Golf 1.4 TSI 2017',
        'brand'            => 'Volkswagen',
        'model'            => 'Golf',
        'year_manufacture' => 2016,
        'year_model'       => 2017,
        'mileage'          => 60000,
        'price'            => 80000.00,
        'color'            => 'Preto',
        'fuel_type'        => 'Flex',
        'state'            => 'SP',
        'city'             => 'São Paulo',
        'body_style'       => 'Hatch'
    ]);

    $v2 = $vehicleModel->create([
        'user_id'          => $sellerId,
        'vehicle_type_id'  => 1, // Carros
        'title'            => 'Honda Civic 2.0 EXL 2021',
        'brand'            => 'Honda',
        'model'            => 'Civic',
        'year_manufacture' => 2020,
        'year_model'       => 2021,
        'mileage'          => 35000,
        'price'            => 120000.00,
        'color'            => 'Branco',
        'fuel_type'        => 'Flex',
        'state'            => 'SP',
        'city'             => 'Campinas',
        'body_style'       => 'Sedan'
    ]);

    $v3 = $vehicleModel->create([
        'user_id'            => $sellerId,
        'vehicle_type_id'    => 2, // Motos
        'title'              => 'Honda CB 500F 2020',
        'brand'              => 'Honda',
        'model'              => 'CB 500F',
        'year_manufacture'   => 2020,
        'year_model'         => 2020,
        'mileage'            => 18000,
        'price'              => 32000.00,
        'color'              => 'Vermelho',
        'fuel_type'          => 'Gasolina',
        'state'              => 'RJ',
        'city'               => 'Niterói',
        'engine_capacity_cc' => 500,
        'motorcycle_category'=> 'Naked'
    ]);

    // Função auxiliar para extrair apenas a seção de grid de veículos do HTML
    $getGridHtml = function(string $fullHtml): string {
        if (preg_match('/<div class="vehicles-grid[^">]*">(.*?)<\/div>\s*<!-- CONTROLE DE PAGINAÇÃO -->/s', $fullHtml, $matches)) {
            return $matches[1];
        }
        return $fullHtml;
    };

    // 1. Teste de Busca por Palavra-Chave (Keyword)
    echo "[TESTE 1/6] Testando busca por Palavra-chave (q='Golf')... ";
    $_GET = ['q' => 'Golf'];
    ob_start();
    (new VehicleController())->index();
    $grid1 = $getGridHtml(ob_get_clean());

    if (str_contains($grid1, 'Volkswagen Golf 1.4 TSI 2017') && !str_contains($grid1, 'Honda Civic 2.0 EXL 2021')) {
        echo "OK (Filtrou apenas o Golf no catálogo)\n";
    } else {
        throw new Exception("Falha na busca por palavra-chave.");
    }

    // 2. Teste de Filtro por Categoria (slug = 'motos')
    echo "[TESTE 2/6] Testando filtro por Categoria (categoria='motos')... ";
    $_GET = ['categoria' => 'motos'];
    ob_start();
    (new VehicleController())->index();
    $grid2 = $getGridHtml(ob_get_clean());

    if (str_contains($grid2, 'Honda CB 500F 2020') && !str_contains($grid2, 'Volkswagen Golf')) {
        echo "OK (Filtrou apenas Motos)\n";
    } else {
        throw new Exception("Falha no filtro por categoria.");
    }

    // 3. Teste de Filtro por Faixa de Preço (preco_min=70000, preco_max=100000)
    echo "[TESTE 3/6] Testando filtro por Faixa de Preço (R$ 70k - R$ 100k)... ";
    $_GET = ['preco_min' => '70000', 'preco_max' => '100000'];
    ob_start();
    (new VehicleController())->index();
    $grid3 = $getGridHtml(ob_get_clean());

    if (str_contains($grid3, 'Volkswagen Golf') && !str_contains($grid3, 'Honda Civic 2.0 EXL 2021') && !str_contains($grid3, 'CB 500F')) {
        echo "OK (Golf de R$ 80k retornado com precisão)\n";
    } else {
        throw new Exception("Falha no filtro por faixa de preço.");
    }

    // 4. Teste de Filtro Específico por Carroceria (carroceria='Sedan')
    echo "[TESTE 4/6] Testando filtro por Carroceria (carroceria='Sedan')... ";
    $_GET = ['carroceria' => 'Sedan'];
    ob_start();
    (new VehicleController())->index();
    $grid4 = $getGridHtml(ob_get_clean());

    if (str_contains($grid4, 'Honda Civic') && !str_contains($grid4, 'Volkswagen Golf')) {
        echo "OK (Apenas o Sedan retornado no catálogo)\n";
    } else {
        throw new Exception("Falha no filtro específico por carroceria.");
    }

    // 5. Teste de Ordenação por Menor Preço (ordem='preco_asc')
    echo "[TESTE 5/6] Testando Ordenação por Menor Preço (ordem='preco_asc')... ";
    $_GET = ['ordem' => 'preco_asc'];
    ob_start();
    (new VehicleController())->index();
    $grid5 = $getGridHtml(ob_get_clean());

    preg_match_all('/R\$\s*([\d\.,]+)/', $grid5, $matches);
    $parsedPrices = array_map(function($p) {
        return (float)str_replace(['.', ','], ['', '.'], $p);
    }, $matches[1] ?? []);

    $isSorted = true;
    for ($i = 0; $i < count($parsedPrices) - 1; $i++) {
        if ($parsedPrices[$i] > $parsedPrices[$i + 1]) {
            $isSorted = false;
            break;
        }
    }

    if ($isSorted && count($parsedPrices) > 0) {
        echo "OK (Preços no catálogo renderizados em ordem estritamente crescente)\n";
    } else {
        throw new Exception("Falha na ordenação por menor preço.");
    }



    // 6. Teste de Limpeza de Filtros
    echo "[TESTE 6/6] Testando Limpeza de Filtros... ";
    $_GET = [];
    ob_start();
    (new VehicleController())->index();
    $grid6 = $getGridHtml(ob_get_clean());

    if (str_contains($grid6, 'Golf') && str_contains($grid6, 'Civic') && str_contains($grid6, 'CB 500F')) {
        echo "OK (Todos os veículos listados)\n";
    } else {
        throw new Exception("Falha ao resetar filtros.");
    }

    // Limpeza de dados do teste
    $pdo->prepare("DELETE FROM users WHERE id = :id")->execute(['id' => $sellerId]);

    echo "\n=== TODOS OS TESTES DE BUSCA E FILTROS PASSARAM COM SUCESSO! ===\n";
    exit(0);

} catch (Exception $e) {
    if (ob_get_level()) ob_end_clean();
    echo "\n[ERRO NO TESTE DE BUSCA]: " . $e->getMessage() . "\n";
    echo "Trace: " . $e->getTraceAsString() . "\n";
    exit(1);
}
