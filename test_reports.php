<?php

require_once __DIR__ . '/../app/Helpers/functions.php';
require_once __DIR__ . '/../app/Helpers/Autoloader.php';

use App\Models\User;
use App\Models\Vehicle;
use App\Models\Report;

echo "=== TEST: Stage 12 - Sistema de Denúncias (Report System) ===\n";

$userModel    = new User();
$vehicleModel = new Vehicle();
$reportModel  = new Report();

// 1. Criar Vendedor e Denunciante
$sellerEmail = 'seller_report_' . time() . '@rodax.com';
$reporterEmail = 'reporter_' . time() . '@rodax.com';
$adminEmail = 'admin_report_' . time() . '@rodax.com';

$sellerId = $userModel->create([
    'name' => 'Vendedor Teste Denúncia',
    'email' => $sellerEmail,
    'password' => 'Senha123!',
    'phone' => '11911112222',
    'user_type' => 'particular'
]);

$reporterId = $userModel->create([
    'name' => 'Denunciante Vigilante',
    'email' => $reporterEmail,
    'password' => 'Senha123!',
    'phone' => '11933334444',
    'user_type' => 'particular'
]);

$adminId = $userModel->create([
    'name' => 'Admin Moderador',
    'email' => $adminEmail,
    'password' => 'Senha123!',
    'is_admin' => 1
]);

echo "[PASS] Usuários de teste criados: Vendedor (ID: {$sellerId}), Denunciante (ID: {$reporterId}), Admin (ID: {$adminId})\n";

// 2. Criar Veículo
$vehicleId = $vehicleModel->create([
    'user_id' => $sellerId,
    'vehicle_type_id' => 1,
    'title' => 'Carro Suspeito com Preço Irreal',
    'brand' => 'Chevrolet',
    'model' => 'Onix',
    'year_manufacture' => 2023,
    'year_model' => 2023,
    'mileage' => 5000,
    'price' => 5000.00, // Preço suspeito (golpe)
    'color' => 'Branco',
    'fuel_type' => 'Flex',
    'seller_type' => 'particular',
    'state' => 'SP',
    'city' => 'São Paulo'
]);

echo "[PASS] Veículo de teste criado (ID: {$vehicleId})\n";

// 3. Teste: Criar Denúncia Válida
$reason = 'Fraude / Golpe';
$description = 'Anúncio suspeito! Valor de tabela FIPE é R$ 80.000, mas está anunciado por R$ 5.000 com pedido de sinal antecipado.';

$reportId = $reportModel->create([
    'reporter_id' => $reporterId,
    'vehicle_id'  => $vehicleId,
    'reason'      => $reason,
    'description' => $description
]);

if ($reportId > 0) {
    echo "[PASS] Denúncia registrada com sucesso! (Report ID: {$reportId})\n";
} else {
    echo "[FAIL] Falha ao registrar denúncia no banco de dados.\n";
    exit(1);
}

// 4. Teste: Verificar busca por ID e status 'pending'
$reportData = $reportModel->findById($reportId);
if ($reportData && $reportData['status'] === 'pending' && $reportData['reason'] === 'Fraude / Golpe') {
    echo "[PASS] Dados da denúncia validados no MySQL (Status: pending, Motivo: {$reportData['reason']})\n";
} else {
    echo "[FAIL] Dados da denúncia não correspondem ao inserido.\n";
    exit(1);
}

// 5. Teste: Prevenção de Denúncia Duplicada
$alreadyReported = $reportModel->hasUserReportedVehicle($reporterId, $vehicleId);
if ($alreadyReported) {
    echo "[PASS] Detecção de denúncia duplicada funcionando! (hasUserReportedVehicle = true)\n";
} else {
    echo "[FAIL] hasUserReportedVehicle retornou false para usuário que já denunciou.\n";
    exit(1);
}

// 6. Teste: Admin atualiza status para 'resolved'
$statusUpdated = $reportModel->updateStatus($reportId, 'resolved', $adminId);
if ($statusUpdated) {
    $updatedReport = $reportModel->findById($reportId);
    if ($updatedReport['status'] === 'resolved' && (int)$updatedReport['resolved_by'] === $adminId) {
        echo "[PASS] Admin alterou o status da denúncia para 'resolved' com sucesso!\n";
    } else {
        echo "[FAIL] Status da denúncia não foi atualizado corretamente pelo Admin.\n";
        exit(1);
    }
} else {
    echo "[FAIL] Falha ao executar updateStatus().\n";
    exit(1);
}

// 7. Teste: Listar denúncias
$allReports = $reportModel->getAll('resolved');
if (count($allReports) > 0) {
    echo "[PASS] Listagem de denúncias para painel administrativo funcionando! (" . count($allReports) . " denúncias resolvidas encontradas)\n";
} else {
    echo "[FAIL] getAll() não retornou a denúncia recém-resolvida.\n";
    exit(1);
}

echo "=== TODOS OS TESTES DO SISTEMA DE DENÚNCIAS PASSARAM COM SUCESSO! ===\n";
