<?php
/**
 * RODAX — Teste de Conexão com o Banco de Dados
 */

require_once __DIR__ . '/../app/Services/Database.php';

use App\Services\Database;

echo "=== RODAX: TESTANDO CONEXÃO COM O BANCO DE DADOS ===\n\n";

try {
    $pdo = Database::getConnection();
    echo "[OK] Conexão PDO estabelecida com sucesso!\n";

    $stmt = $pdo->query("SELECT DATABASE() as db_name, VERSION() as version");
    $info = $stmt->fetch();
    echo "[OK] Banco Conectado: " . $info['db_name'] . "\n";
    echo "[OK] Versão do MySQL/MariaDB: " . $info['version'] . "\n\n";

    // Verificar tabela vehicle_types
    $stmt = $pdo->query("SELECT COUNT(*) as total FROM vehicle_types");
    $count = $stmt->fetchColumn();
    echo "[OK] Categorias de veículos cadastradas: {$count}\n";

    $stmt = $pdo->query("SELECT name, slug FROM vehicle_types ORDER BY id ASC");
    while ($row = $stmt->fetch()) {
        echo " - Category: {$row['name']} (slug: {$row['slug']})\n";
    }

    echo "\n=== TESTE DE CONEXÃO CONCLUÍDO COM SUCESSO! ===\n";
    exit(0);

} catch (Exception $e) {
    echo "[ERRO] Falha no teste de conexão: " . $e->getMessage() . "\n";
    exit(1);
}
