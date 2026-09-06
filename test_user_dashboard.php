<?php

require_once __DIR__ . '/../app/Helpers/functions.php';
require_once __DIR__ . '/../app/Helpers/Autoloader.php';

use App\Models\User;
use App\Models\Vehicle;

echo "=== TEST: Stage 11 - Painel do Usuário, Edição de Anúncios e Perfil ===\n";

$userModel = new User();
$vehicleModel = new Vehicle();

// 1. Criar Usuários de Teste (User A e User B)
$emailA = 'test_dash_a_' . time() . '@rodax.com';
$emailB = 'test_dash_b_' . time() . '@rodax.com';

$userA_id = $userModel->create([
    'name' => 'Usuário Dash A',
    'email' => $emailA,
    'password' => 'Senha123!',
    'phone' => '11999990001',
    'user_type' => 'particular',
    'city' => 'São Paulo',
    'state' => 'SP'
]);

$userB_id = $userModel->create([
    'name' => 'Usuário Dash B',
    'email' => $emailB,
    'password' => 'Senha123!',
    'phone' => '11999990002',
    'user_type' => 'loja',
    'city' => 'Campinas',
    'state' => 'SP'
]);

echo "[PASS] Usuários criados: User A (ID: {$userA_id}) e User B (ID: {$userB_id})\n";

// 2. Criar Anúncios para User A
$vehicleA1_id = $vehicleModel->create([
    'user_id' => $userA_id,
    'vehicle_type_id' => 1,
    'title' => 'Honda Civic EXL 2.0 2020',
    'brand' => 'Honda',
    'model' => 'Civic',
    'year_manufacture' => 2020,
    'year_model' => 2020,
    'mileage' => 45000,
    'price' => 115000.00,
    'color' => 'Preto',
    'fuel_type' => 'Flex',
    'seller_type' => 'particular',
    'state' => 'SP',
    'city' => 'São Paulo'
]);

$vehicleA2_id = $vehicleModel->create([
    'user_id' => $userA_id,
    'vehicle_type_id' => 2,
    'title' => 'Yamaha MT-07 2022',
    'brand' => 'Yamaha',
    'model' => 'MT-07',
    'year_manufacture' => 2022,
    'year_model' => 2022,
    'mileage' => 12000,
    'price' => 42000.00,
    'color' => 'Azul',
    'fuel_type' => 'Gasolina',
    'seller_type' => 'particular',
    'state' => 'SP',
    'city' => 'São Paulo'
]);

echo "[PASS] Anúncios criados para User A: ID {$vehicleA1_id} e ID {$vehicleA2_id}\n";

// Alterar status de A2 para 'paused' para testar estatísticas
$vehicleModel->updateStatus($vehicleA2_id, $userA_id, 'paused');

// 3. Testar getUserStats()
$statsA = $vehicleModel->getUserStats($userA_id);
if ($statsA['active'] === 1 && $statsA['paused'] === 1 && $statsA['sold'] === 0) {
    echo "[PASS] Estatísticas do Dashboard validadas com sucesso! (Ativos: {$statsA['active']}, Pausados: {$statsA['paused']})\n";
} else {
    echo "[FAIL] Estatísticas incorretas: " . json_encode($statsA) . "\n";
    exit(1);
}

// 4. Testar Edição de Anúncio pelo dono (User A)
$updateSuccess = $vehicleModel->update($vehicleA1_id, $userA_id, [
    'title' => 'Honda Civic EXL 2.0 2020 - Impecável Revisitado',
    'brand' => 'Honda',
    'model' => 'Civic',
    'version' => 'EXL 2.0 Flex',
    'year_manufacture' => 2020,
    'year_model' => 2020,
    'mileage' => 46000,
    'price' => 112000.00,
    'color' => 'Preto Metálico',
    'fuel_type' => 'Flex',
    'engine' => '2.0 i-VTEC',
    'transmission' => 'Automático',
    'state' => 'SP',
    'city' => 'São Paulo',
    'description' => 'Revisado em concessionária.'
]);

if ($updateSuccess) {
    $updatedVehicle = $vehicleModel->findById($vehicleA1_id);
    if ($updatedVehicle['price'] == 112000.00 && $updatedVehicle['title'] === 'Honda Civic EXL 2.0 2020 - Impecável Revisitado') {
        echo "[PASS] Edição de anúncio pelo próprio dono efetuada com sucesso!\n";
    } else {
        echo "[FAIL] Anúncio não refletiu as mudanças esperadas.\n";
        exit(1);
    }
} else {
    echo "[FAIL] Falha ao atualizar anúncio do próprio dono.\n";
    exit(1);
}

// 5. Testar Proteção IDOR (User B tentando editar o anúncio de User A)
$idorAttempt = $vehicleModel->update($vehicleA1_id, $userB_id, [
    'title' => 'HACKED TITLE BY USER B',
    'brand' => 'Honda',
    'model' => 'Civic',
    'year_manufacture' => 2020,
    'year_model' => 2020,
    'mileage' => 100,
    'price' => 1.00,
    'color' => 'Rosa',
    'fuel_type' => 'Flex',
    'state' => 'SP',
    'city' => 'São Paulo'
]);

if (!$idorAttempt) {
    $vehicleCheck = $vehicleModel->findById($vehicleA1_id);
    if ($vehicleCheck['title'] !== 'HACKED TITLE BY USER B') {
        echo "[PASS] Proteção IDOR confirmada! User B não conseguiu editar o anúncio de User A.\n";
    } else {
        echo "[FAIL] FALHA DE SEGURANÇA IDOR! Anúncio foi modificado por outro usuário.\n";
        exit(1);
    }
} else {
    echo "[FAIL] update() retornou true para tentativa de IDOR.\n";
    exit(1);
}

// 6. Testar Atualização de Perfil de Usuário
$profileUpdated = $userModel->updateProfile($userA_id, [
    'name' => 'Usuário Dash A Editado',
    'phone' => '11988887777',
    'company_name' => 'A Motors',
    'city' => 'Santos',
    'state' => 'SP'
]);

if ($profileUpdated) {
    $userCheck = $userModel->findById($userA_id);
    if ($userCheck['name'] === 'Usuário Dash A Editado' && $userCheck['city'] === 'Santos') {
        echo "[PASS] Atualização de perfil de usuário efetuada com sucesso!\n";
    } else {
        echo "[FAIL] Perfil não atualizou os campos no banco de dados.\n";
        exit(1);
    }
} else {
    echo "[FAIL] Falha ao atualizar perfil do usuário.\n";
    exit(1);
}

// 7. Testar Alteração de Senha
$newPasswordHash = password_hash('NovaSenha456!', PASSWORD_DEFAULT);
$passUpdated = $userModel->updatePassword($userA_id, $newPasswordHash);

if ($passUpdated) {
    $userCheck = $userModel->findById($userA_id);
    if (password_verify('NovaSenha456!', $userCheck['password_hash'])) {
        echo "[PASS] Alteração de senha do usuário efetuada com sucesso!\n";
    } else {
        echo "[FAIL] A nova senha não bate com o hash salvo.\n";
        exit(1);
    }
} else {
    echo "[FAIL] Falha ao atualizar a senha no banco de dados.\n";
    exit(1);
}

echo "=== TODOS OS TESTES DA ETAPA 11 PASSARAM COM SUCESSO! ===\n";
