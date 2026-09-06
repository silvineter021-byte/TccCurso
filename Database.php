<?php

namespace App\Services;

use PDO;
use PDOException;
use Exception;

/**
 * RODAX — Serviço de Conexão PDO (Singleton)
 * 
 * Garante conexões seguras e eficientes via PDO com prepared statements.
 */
class Database
{
    private static ?PDO $instance = null;

    /**
     * Construtor privado para padrão Singleton
     */
    private function __construct() {}

    /**
     * Clona desativado
     */
    private function __clone() {}

    /**
     * Retorna a instância única do PDO
     * 
     * @return PDO
     * @throws Exception
     */
    public static function getConnection(): PDO
    {
        if (self::$instance === null) {
            $config = require __DIR__ . '/../../config/database.php';

            $dsn = sprintf(
                "mysql:host=%s;port=%s;dbname=%s;charset=%s",
                $config['host'],
                $config['port'],
                $config['dbname'],
                $config['charset']
            );

            try {
                self::$instance = new PDO(
                    $dsn,
                    $config['username'],
                    $config['password'],
                    $config['options']
                );
            } catch (PDOException $e) {
                // Registrar log de erro sem expor dados sensíveis ao usuário
                error_log("RODAX Database Connection Error: " . $e->getMessage());
                throw new Exception("Falha na conexão com o banco de dados do RODAX.");
            }
        }

        return self::$instance;
    }

    /**
     * Reseta a conexão (útil para testes)
     */
    public static function resetConnection(): void
    {
        self::$instance = null;
    }
}
