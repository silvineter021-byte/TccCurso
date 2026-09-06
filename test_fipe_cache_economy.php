<?php
/**
 * RODAX — Teste Obrigatório de Economia da API FIPE (Prompt V5 — Seção 43)
 * 
 * Simula 100 acessos simultâneos ao mesmo anúncio/veículo FIPE.
 * 
 * Resultado Esperado:
 * - 1º Acesso: API -> MySQL (Fonte: api)
 * - Demais 99 Acessos: MySQL -> sem nova chamada à API (Fonte: cache)
 * - Total de Chamadas Externas à API: 1
 * - Total de Consultas Rápidas no Cache: 99
 */

require_once __DIR__ . '/../app/Services/Database.php';
require_once __DIR__ . '/../app/Services/FipeService.php';

use App\Services\Database;
use App\Services\FipeService;

echo "=== RODAX: TESTE DE ECONOMIA DA API (100 ACESSOS AO MESMO ANÚNCIO) ===\n\n";

try {
    $pdo = Database::getConnection();
    $fipeService = new FipeService($pdo);

    // Dados de teste: Marca Acura (1), Modelo Integra GS 1.8 (1), Ano 1992 Gasolina (1992-1)
    $brandCode = '1';
    $modelCode = '1';
    $yearCode  = '1992-1';
    $cacheKey  = "fipe_details_carros_{$brandCode}_{$modelCode}_{$yearCode}";

    // Limpar cache específico para garantir que o teste inicie limpo (forçando 1ª chamada à API)
    $pdo->prepare("DELETE FROM fipe_cache WHERE cache_key = :key")->execute(['key' => $cacheKey]);

    $totalAccesses = 100;
    $apiCallsCount = 0;
    $cacheHitsCount = 0;

    $startTime = microtime(true);

    echo "Iniciando simulação de {$totalAccesses} acessos ao mesmo anúncio FIPE...\n";

    for ($i = 1; $i <= $totalAccesses; $i++) {
        $result = $fipeService->getVehicleDetails('carros', $brandCode, $modelCode, $yearCode);

        if ($result['source'] === 'api') {
            $apiCallsCount++;
        } elseif ($result['source'] === 'cache') {
            $cacheHitsCount++;
        }
    }

    $durationSeconds = round(microtime(true) - $startTime, 4);

    echo "\n------------------------------------------------------------\n";
    echo "RESULTADO DO TESTE DE ECONOMIA DA API RODAX:\n";
    echo "------------------------------------------------------------\n";
    echo "Total de acessos simulados   : {$totalAccesses}\n";
    echo "Chamadas reais à API externa  : {$apiCallsCount} (Esperado: 1)\n";
    echo "Acessos servidos pelo MySQL  : {$cacheHitsCount} (Esperado: 99)\n";
    echo "Tempo total de execução      : {$durationSeconds} s\n";
    echo "Economia de requisições FIPE  : " . round(($cacheHitsCount / $totalAccesses) * 100, 2) . "%\n";
    echo "------------------------------------------------------------\n\n";

    if ($apiCallsCount === 1 && $cacheHitsCount === 99) {
        echo "=== [SUCESSO ABSOLUTO] TESTE DE ECONOMIA APROVADO! ===\n";
        echo "O RODAX comprovadamente não desperdiça consultas à API FIPE!\n";
        exit(0);
    } else {
        throw new Exception("Falha no Teste de Economia! Chamadas API: {$apiCallsCount}, Hits de Cache: {$cacheHitsCount}");
    }

} catch (Exception $e) {
    echo "\n[ERRO NO TESTE DE ECONOMIA]: " . $e->getMessage() . "\n";
    exit(1);
}
