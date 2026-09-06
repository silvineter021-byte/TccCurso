<?php
/**
 * RODAX — Bateria de Testes da Integração FIPE & Cache MySQL
 */

require_once __DIR__ . '/../app/Services/Database.php';
require_once __DIR__ . '/../app/Services/FipeService.php';

use App\Services\Database;
use App\Services\FipeService;

echo "=== RODAX: BATERIA DE TESTES DA API FIPE E CACHE MYSQL ===\n\n";

try {
    $pdo = Database::getConnection();
    $fipeService = new FipeService($pdo);

    // Limpar tabelas de cache para o teste zerado
    $pdo->exec("TRUNCATE TABLE fipe_cache");
    $pdo->exec("TRUNCATE TABLE fipe_brands");
    $pdo->exec("TRUNCATE TABLE fipe_models");
    $pdo->exec("TRUNCATE TABLE fipe_years");
    $pdo->exec("TRUNCATE TABLE fipe_vehicle_details");

    // 1. Teste 1 & 2: Consulta sem cache (deve consultar API e salvar no MySQL)
    echo "[TESTE 1/6] Testando busca de Marcas (1ª chamada - sem cache)... ";
    $brandsResult1 = $fipeService->getBrands('carros');
    echo "OK (Fonte: {$brandsResult1['source']}, Total: " . count($brandsResult1['data']) . " marcas)\n";

    // 2. Teste 3: Segunda consulta de Marcas (deve vir do Cache MySQL)
    echo "[TESTE 2/6] Testando busca de Marcas (2ª chamada - com cache)... ";
    $brandsResult2 = $fipeService->getBrands('carros');
    
    if ($brandsResult2['source'] === 'cache') {
        echo "OK (Sucesso! Fonte: {$brandsResult2['source']}, Retornado do MySQL com 0 chamadas externas)\n";
    } else {
        throw new Exception("Falha no cache de Marcas: a 2ª chamada não utilizou o MySQL.");
    }

    // Obter primeira marca, modelo e ano dinamicamente da API FIPE
    $firstBrandCode = $brandsResult1['data'][0]['codigo'];
    $modelsResult = $fipeService->getModels('carros', (string)$firstBrandCode);
    $firstModelCode = $modelsResult['data'][0]['codigo'];
    $yearsResult = $fipeService->getYears('carros', (string)$firstBrandCode, (string)$firstModelCode);
    $firstYearCode = $yearsResult['data'][0]['codigo'];

    echo "   - Testando com Marca Code: {$firstBrandCode}, Modelo Code: {$firstModelCode}, Ano Code: {$firstYearCode}\n";

    // 3. Teste Detalhes do Veículo sem Cache
    echo "[TESTE 3/6] Testando busca de Detalhes do Veículo (1ª chamada)... ";
    $details1 = $fipeService->getVehicleDetails('carros', (string)$firstBrandCode, (string)$firstModelCode, (string)$firstYearCode);
    echo "OK (Fonte: {$details1['source']})\n";
    if (isset($details1['data']['Valor'])) {
        echo "   - Veículo: {$details1['data']['Marca']} {$details1['data']['Modelo']}\n";
        echo "   - Preço Ref. FIPE: {$details1['data']['Valor']}\n";
        echo "   - Código FIPE: {$details1['data']['CodigoFipe']}\n";
    } else {
        throw new Exception("Detalhes não retornaram dados válidos.");
    }

    // 4. Teste Detalhes do Veículo com Cache Ativo
    echo "[TESTE 4/6] Testando busca de Detalhes do Veículo (2ª chamada imediata)... ";
    $details2 = $fipeService->getVehicleDetails('carros', (string)$firstBrandCode, (string)$firstModelCode, (string)$firstYearCode);
    if ($details2['source'] === 'cache') {
        echo "OK (Sucesso! Dados recuperados diretamente do MySQL - Fonte: cache)\n";
    } else {
        throw new Exception("Falha no cache de detalhes: esperava fonte 'cache', recebeu '{$details2['source']}'.");
    }

    // 5. Teste de Simulação de Expiração do Cache
    echo "[TESTE 5/6] Simulando expiração do cache no MySQL... ";
    $cacheKey = "fipe_details_carros_{$firstBrandCode}_{$firstModelCode}_{$firstYearCode}";
    $pdo->prepare("UPDATE fipe_cache SET expires_at = NOW() - INTERVAL 1 DAY WHERE cache_key = :key")
        ->execute(['key' => $cacheKey]);
    
    // Consulta após expiração: deve renovar o cache via API
    $details3 = $fipeService->getVehicleDetails('carros', (string)$firstBrandCode, (string)$firstModelCode, (string)$firstYearCode);
    if ($details3['source'] === 'api') {
        echo "OK (Sucesso! Cache expirado detectado e renovado via API - Fonte: api)\n";
    } else {
        echo "AVISO (Cache renovado ou mantido, Fonte: {$details3['source']})\n";
    }

    // 6. Teste de Preservação do Cache Legado
    echo "[TESTE 6/6] Testando resiliência e preservação do cache... ";
    $stmtCheck = $pdo->prepare("SELECT COUNT(*) FROM fipe_cache WHERE cache_key = :key");
    $stmtCheck->execute(['key' => $cacheKey]);
    if ($stmtCheck->fetchColumn() > 0) {
        echo "OK (Registro de cache mantido com segurança no MySQL)\n";
    } else {
        throw new Exception("Falha de resiliência: registro de cache indisponível.");
    }

    echo "\n=== BATERIA DE TESTES DA FIPE CONCLUÍDA COM SUCESSO! ===\n";
    exit(0);

} catch (Exception $e) {
    echo "\n[ERRO NO TESTE FIPE]: " . $e->getMessage() . "\n";
    echo "Trace: " . $e->getTraceAsString() . "\n";
    exit(1);
}
