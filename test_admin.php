<?php

require_once __DIR__ . '/../app/Helpers/functions.php';
require_once __DIR__ . '/../app/Helpers/Autoloader.php';

use App\Models\User;
use App\Models\Vehicle;
use App\Models\Report;
use App\Models\AdminLog;
use App\Middleware\AdminMiddleware;

echo "=== TEST: Stage 13 - Painel Administrativo, Bloqueio, Moderação e Audit Logs ===\n";

$userModel     = new User();
$vehicleModel  = new Vehicle();
$reportModel   = new Report();
$adminLogModel = new AdminLog();

// 1. Criar Usuário Comum e Administrador
$normalUserEmail = 'user_normal_' . time() . '@rodax.com';
$adminUserEmail  = 'admin_super_' . time() . '@rodax.com';

$normalUserId = $userModel->create([
    'name' => 'Usuário Comum',
    'email' => $normalUserEmail,
    'password' => 'Senha123!',
    'phone' => '11977778888',
    'user_type' => 'particular'
]);

$adminUserId = $userModel->create([
    'name' => 'Super Administrador',
    'email' => $adminUserEmail,
    'password' => 'Senha123!',
    'is_admin' => 1
]);

echo "[PASS] Usuários criados: Comum (ID: {$normalUserId}) e Admin (ID: {$adminUserId})\n";

// 2. Testar Bloqueio e Desbloqueio de Usuário pelo Admin
$userModel->toggleBlock($normalUserId);
$blockedUser = $userModel->findById($normalUserId);

if (!empty($blockedUser['is_blocked']) && $userModel->isLockedOut($blockedUser)) {
    echo "[PASS] Usuário comum bloqueado com sucesso pelo Administrador!\n";
} else {
    echo "[FAIL] Falha ao bloquear usuário comum.\n";
    exit(1);
}

// Registrar Log do Bloqueio
$logId1 = $adminLogModel->log(
    $adminUserId,
    'block_user',
    'user',
    $normalUserId,
    "Bloqueio preventivo por atividade suspeita."
);
if ($logId1 > 0) {
    echo "[PASS] Log de auditoria para o bloqueio de usuário registrado com sucesso! (Log ID: {$logId1})\n";
} else {
    echo "[FAIL] Falha ao registrar log de auditoria.\n";
    exit(1);
}

// Desbloquear Usuário
$userModel->toggleBlock($normalUserId);
$unblockedUser = $userModel->findById($normalUserId);
if (empty($unblockedUser['is_blocked'])) {
    echo "[PASS] Usuário desbloqueado com sucesso!\n";
} else {
    echo "[FAIL] Falha ao desbloquear usuário.\n";
    exit(1);
}

// 3. Testar Moderação de Anúncios pelo Admin
$vehicleId = $vehicleModel->create([
    'user_id' => $normalUserId,
    'vehicle_type_id' => 1,
    'title' => 'Anúncio Irregular a ser Moderado',
    'brand' => 'Fiat',
    'model' => 'Uno',
    'year_manufacture' => 2015,
    'year_model' => 2015,
    'mileage' => 100000,
    'price' => 25000.00,
    'color' => 'Prata',
    'fuel_type' => 'Flex',
    'seller_type' => 'particular',
    'state' => 'SP',
    'city' => 'São Paulo'
]);

echo "[PASS] Veículo de teste criado para moderação (ID: {$vehicleId})\n";

// Admin altera status do veículo para 'deleted'
$modSuccess = $vehicleModel->adminUpdateStatus($vehicleId, 'deleted');
if ($modSuccess) {
    $vehicleCheck = $vehicleModel->findById($vehicleId);
    if ($vehicleCheck['status'] === 'deleted') {
        echo "[PASS] Administrador removeu/desativou anúncio irregular com sucesso!\n";
    } else {
        echo "[FAIL] Anúncio não teve seu status alterado para 'deleted'.\n";
        exit(1);
    }
} else {
    echo "[FAIL] adminUpdateStatus() retornou false.\n";
    exit(1);
}

$adminLogModel->log(
    $adminUserId,
    'update_vehicle_status',
    'vehicle',
    $vehicleId,
    "Anúncio removido por infração às regras da plataforma."
);

// 4. Testar Resolução Administrativa de Denúncia
$reportId = $reportModel->create([
    'reporter_id' => $adminUserId,
    'vehicle_id'  => $vehicleId,
    'reason'      => 'Informações Falsas',
    'description' => 'Dados do veículo divergentes da documentação.'
]);

$resolveSuccess = $reportModel->updateStatus($reportId, 'resolved', $adminUserId);
if ($resolveSuccess) {
    $reportCheck = $reportModel->findById($reportId);
    if ($reportCheck['status'] === 'resolved' && (int)$reportCheck['resolved_by'] === $adminUserId) {
        echo "[PASS] Denúncia resolvida e associada ao ID do administrador responsável!\n";
    } else {
        echo "[FAIL] Status da denúncia não foi atualizado corretamente.\n";
        exit(1);
    }
} else {
    echo "[FAIL] Falha ao atualizar denúncia.\n";
    exit(1);
}

// 5. Testar Consulta Geral de Audit Logs
$logs = $adminLogModel->getAll(10);
if (count($logs) >= 2) {
    echo "[PASS] Consulta de Audit Logs finalizada com sucesso! (" . count($logs) . " logs encontrados)\n";
} else {
    echo "[FAIL] Audit logs não retornaram os registros criados.\n";
    exit(1);
}

echo "=== TODOS OS TESTES DO PAINEL ADMINISTRATIVO PASSARAM COM SUCESSO! ===\n";
