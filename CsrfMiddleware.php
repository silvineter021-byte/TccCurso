<?php

namespace App\Middleware;

/**
 * RODAX — Middleware de Proteção CSRF
 * 
 * Valida o token CSRF em requisições POST para evitar ataques maliciosos.
 */
class CsrfMiddleware
{
    public function handle(): void
    {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $token = $_POST['csrf_token'] ?? $_SERVER['HTTP_X_CSRF_TOKEN'] ?? '';
            $sessionToken = $_SESSION['csrf_token'] ?? '';

            if (empty($token) || empty($sessionToken) || !hash_equals($sessionToken, $token)) {
                http_response_code(403);
                die("<h1>403 Forbidden — Erro de Validação CSRF</h1><p>A requisição foi recusada por motivos de segurança. Recarregue a página e tente novamente.</p>");
            }
        }
    }
}
