<?php

namespace App\Middleware;

/**
 * RODAX — Middleware de Autenticação
 * 
 * Protege páginas privadas exigindo login de usuário.
 */
class AuthMiddleware
{
    public function handle(): void
    {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }

        if (empty($_SESSION['user_id'])) {
            $_SESSION['flash_error'] = "Você precisa estar logado para acessar esta página.";
            redirect('/login');
        }
    }
}
