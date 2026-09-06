<?php
/**
 * RODAX — Configuração de Conexão com o Banco de Dados MySQL (PDO)
 */

// Função auxiliar simples para ler arquivo .env caso não haja autoload / dotenv
if (!function_exists('env')) {
    function env(string $key, $default = null) {
        static $vars = null;
        if ($vars === null) {
            $vars = [];
            $envPath = __DIR__ . '/../.env';
            if (file_exists($envPath)) {
                $lines = file($envPath, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
                foreach ($lines as $line) {
                    $line = trim($line);
                    if ($line === '' || str_starts_with($line, '#')) {
                        continue;
                    }
                    if (str_contains($line, '=')) {
                        [$name, $value] = explode('=', $line, 2);
                        $vars[trim($name)] = trim($value, "\"' \t\n\r\0\x0B");
                    }
                }
            }
        }
        return $vars[$key] ?? getenv($key) ?: $default;
    }
}

return [
    'host'     => env('DB_HOST', '127.0.0.1'),
    'port'     => env('DB_PORT', '3306'),
    'dbname'   => env('DB_NAME', 'rodax_db'),
    'username' => env('DB_USER', 'root'),
    'password' => env('DB_PASS', ''),
    'charset'  => env('DB_CHARSET', 'utf8mb4'),
    'options'  => [
        PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES   => false,
    ]
];
