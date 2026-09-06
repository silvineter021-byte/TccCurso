<?php

namespace App\Middleware;

use App\Models\User;

/**
 * RODAX — Middleware de Segurança para o Painel Administrativo
 */
class AdminMiddleware
{
    public function handle(): void
    {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }

        // Verifica se o usuário está logado
        if (empty($_SESSION['user_id'])) {
            $_SESSION['flash_error'] = "Você precisa estar logado para acessar esta página.";
            redirect('/login');
        }

        // Se a variável de sessão is_admin não estiver definida, checar no banco de dados para garantia
        if (empty($_SESSION['is_admin'])) {
            $userModel = new User();
            $user = $userModel->findById((int)$_SESSION['user_id']);

            if (!$user || empty($user['is_admin'])) {
                $_SESSION['flash_error'] = "Acesso não autorizado. Esta área é restrita a administradores.";
                redirect('/');
            }

            // Atualiza a sessão para requisições subsequentes
            $_SESSION['is_admin'] = 1;
        }
    }
}
