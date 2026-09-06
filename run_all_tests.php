<?php

echo "====================================================================\n";
echo "       RODAX — SUÍTE DE TESTES AUTOMATIZADOS GLOBAL DO SISTEMA      \n";
echo "       Slogan: 'RODAX — Seu próximo veículo está aqui.'             \n";
echo "====================================================================\n\n";

$tests = [
    'Stage 1 (DB Conexão)'      => __DIR__ . '/test_db_connection.php',
    'Stage 1 (DB CRUD)'         => __DIR__ . '/test_db_crud.php',
    'Stage 2 (Serviço FIPE)'    => __DIR__ . '/test_fipe_service.php',
    'Stage 2 (Economia Cache)'  => __DIR__ . '/test_fipe_cache_economy.php',
    'Stage 3 (Roteador Base)'   => __DIR__ . '/test_router.php',
    'Stage 4 (Autenticação)'    => __DIR__ . '/test_auth.php',
    'Stage 5 (Criar Veículos)'  => __DIR__ . '/test_vehicle_creation.php',
    'Stage 6 (Upload Imagens)'  => __DIR__ . '/test_image_upload.php',
    'Stage 7 (Pesquisa/Filtros)' => __DIR__ . '/test_vehicle_search.php',
    'Stage 8 (Detalhes/FIPE)'   => __DIR__ . '/test_vehicle_details.php',
    'Stage 9 (Favoritos)'       => __DIR__ . '/test_favorites.php',
    'Stage 10 (Chat/Mensagens)' => __DIR__ . '/test_messages.php',
    'Stage 11 (Painel/Edição)'  => __DIR__ . '/test_user_dashboard.php',
    'Stage 12 (Denúncias)'      => __DIR__ . '/test_reports.php',
    'Stage 13 (Painel Admin)'   => __DIR__ . '/test_admin.php',
    'Stage 14 (Segurança)'      => __DIR__ . '/test_security_audit.php',
];

$passedCount = 0;
$failedCount = 0;
$results = [];

$phpBinary = PHP_BINARY ? '"' . PHP_BINARY . '"' : 'php';

foreach ($tests as $title => $filePath) {
    echo "--------------------------------------------------------------------\n";
    echo "▶ Executando: {$title}\n";
    echo "--------------------------------------------------------------------\n";

    $output = [];
    $returnVar = 0;
    exec("{$phpBinary} \"{$filePath}\" 2>&1", $output, $returnVar);

    echo implode("\n", $output) . "\n";

    if ($returnVar === 0) {
        $passedCount++;
        $results[$title] = '✓ PASS';
    } else {
        $failedCount++;
        $results[$title] = '✗ FAIL';
    }
    echo "\n";
}

echo "====================================================================\n";
echo "                    RELATÓRIO CONSOLIDADO DE TESTES                  \n";
echo "====================================================================\n";

foreach ($results as $title => $status) {
    printf("%-35s %s\n", $title, $status);
}

echo "--------------------------------------------------------------------\n";
echo "TOTAL DE TESTES: " . count($tests) . "\n";
echo "APROVADOS:       {$passedCount}\n";
echo "FALHAS:          {$failedCount}\n";
echo "TAXA DE SUCESSO: " . round(($passedCount / count($tests)) * 100, 2) . "%\n";
echo "====================================================================\n";

if ($failedCount > 0) {
    exit(1);
}
