<?php

/**
 * RODAX — Bateria de Testes Automatizados de Autenticação e Segurança Brute Force
 */

require_once __DIR__ . '/../app/Helpers/Autoloader.php';
require_once __DIR__ . '/../app/Helpers/functions.php';

use App\Services\Database;
use App\Models\User;

echo "=== RODAX: BATERIA DE TESTES DE AUTENTICAÇÃO E SEGURANÇA ===\n\n";

try {
    $pdo = Database::getConnection();
    $userModel = new User($pdo);

    $testEmail = 'test_security_' . time() . '@rodax.com.br';
    $rawPassword = 'SenhaSeguraRODAX123!';

    // 1. Teste de Cadastro com Hash de Senha
    echo "[TESTE 1/5] Testando Cadastro de Usuário com password_hash()... ";
    $userId = $userModel->create([
        'name'      => 'Usuário Teste Segurança',
        'email'     => $testEmail,
        'password'  => $rawPassword,
        'phone'     => '(11) 98888-7777',
        'user_type' => 'particular',
        'city'      => 'São Paulo',
        'state'     => 'SP'
    ]);

    $createdUser = $userModel->findById($userId);

    if ($createdUser && password_verify($rawPassword, $createdUser['password_hash'])) {
        echo "OK (ID: {$userId}, Hash BCrypt verificado!)\n";
    } else {
        throw new Exception("Falha na criação de usuário ou hash de senha inválido.");
    }

    // 2. Teste de Duplicidade de E-mail
    echo "[TESTE 2/5] Testando Prevenção de E-mail Duplicado... ";
    $duplicate = $userModel->findByEmail($testEmail);
    if ($duplicate && (int)$duplicate['id'] === $userId) {
        echo "OK (E-mail existente detectado corretamente)\n";
    } else {
        throw new Exception("Falha na verificação de e-mail existente.");
    }

    // 3. Teste de Validação de Senha Incorreta
    echo "[TESTE 3/5] Testando Validação de Senha Incorreta... ";
    $isPasswordValid = password_verify('SenhaErrada123', $createdUser['password_hash']);
    if (!$isPasswordValid) {
        echo "OK (Senha incorreta recusada com sucesso)\n";
    } else {
        throw new Exception("Falha: Senha incorreta foi aceita!");
    }

    // 4. Teste de Proteção contra Força Bruta (Brute Force Lockout)
    echo "[TESTE 4/5] Testando Proteção contra Força Bruta (5 tentativas incorretas)... ";
    for ($attempt = 1; $attempt <= 5; $attempt++) {
        $userState = $userModel->findById($userId);
        $userModel->recordFailedLogin($userId, (int)($userState['login_attempts'] ?? 0));
    }

    $lockedUser = $userModel->findById($userId);
    if ((int)$lockedUser['login_attempts'] >= 5 && $userModel->isLockedOut($lockedUser)) {
        echo "OK (Conta bloqueada temporariamente até " . $lockedUser['lockout_until'] . ")\n";
    } else {
        throw new Exception("Falha na proteção Brute Force: a conta não foi bloqueada após 5 tentativas.");
    }

    // 5. Teste de Reset de Tentativas após Desbloqueio
    echo "[TESTE 5/5] Testando Reset de Tentativas de Login... ";
    $userModel->resetLoginAttempts($userId);
    $unlockedUser = $userModel->findById($userId);

    if ((int)$unlockedUser['login_attempts'] === 0 && !$userModel->isLockedOut($unlockedUser)) {
        echo "OK (Tentativas zeradas e conta desbloqueada com sucesso)\n";
    } else {
        throw new Exception("Falha ao resetar tentativas de login.");
    }

    // Limpeza de dados do teste
    $pdo->prepare("DELETE FROM users WHERE id = :id")->execute(['id' => $userId]);

    echo "\n=== TODOS OS TESTES DE AUTENTICAÇÃO E SEGURANÇA PASSARAM COM SUCESSO! ===\n";
    exit(0);

} catch (Exception $e) {
    echo "\n[ERRO NO TESTE DE AUTENTICAÇÃO]: " . $e->getMessage() . "\n";
    echo "Trace: " . $e->getTraceAsString() . "\n";
    exit(1);
}
