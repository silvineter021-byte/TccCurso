<?php

namespace App\Controllers;

use App\Models\User;

/**
 * RODAX — Controller de Autenticação e Gestão de Contas
 */
class AuthController
{
    private User $userModel;

    public function __construct()
    {
        $this->userModel = new User();
    }

    /**
     * Renderiza Formulário de Cadastro
     */
    public function showRegister(): void
    {
        if (!empty($_SESSION['user_id'])) {
            redirect('/painel');
        }
        view('auth.register', [
            'pageTitle' => 'Criar Conta — RODAX'
        ]);
    }

    /**
     * Processa Cadastro de Usuário
     */
    public function register(): void
    {
        $name            = trim($_POST['name'] ?? '');
        $email           = strtolower(trim($_POST['email'] ?? ''));
        $password        = $_POST['password'] ?? '';
        $passwordConfirm = $_POST['password_confirm'] ?? '';
        $phone           = trim($_POST['phone'] ?? '');
        $userType        = $_POST['user_type'] ?? 'particular';
        $companyName     = trim($_POST['company_name'] ?? '');
        $city            = trim($_POST['city'] ?? '');
        $state           = trim($_POST['state'] ?? '');

        // Validações
        $errors = [];

        if (empty($name) || strlen($name) < 3) {
            $errors[] = "Informe seu nome completo (mínimo de 3 caracteres).";
        }

        if (empty($email) || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $errors[] = "Informe um e-mail válido.";
        } else {
            $existingUser = $this->userModel->findByEmail($email);
            if ($existingUser) {
                $errors[] = "Este e-mail já está cadastrado no RODAX.";
            }
        }

        if (empty($password) || strlen($password) < 6) {
            $errors[] = "A senha deve conter no mínimo 6 caracteres.";
        }

        if ($password !== $passwordConfirm) {
            $errors[] = "A confirmação de senha não confere com a senha digitada.";
        }

        if (!empty($errors)) {
            $_SESSION['flash_error'] = implode('<br>', $errors);
            view('auth.register', [
                'pageTitle' => 'Criar Conta — RODAX',
                'old'       => $_POST
            ]);
            return;
        }

        $userId = $this->userModel->create([
            'name'         => $name,
            'email'        => $email,
            'password'     => $password,
            'phone'        => $phone,
            'user_type'    => $userType,
            'company_name' => $companyName,
            'city'         => $city,
            'state'        => $state
        ]);

        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
        session_regenerate_id(true);
        $_SESSION['user_id']   = $userId;
        $_SESSION['user_name'] = $name;
        $_SESSION['user_type'] = $userType;

        $_SESSION['flash_success'] = "Conta criada com sucesso! Bem-vindo ao RODAX.";
        redirect('/painel');
    }

    /**
     * Renderiza Formulário de Login
     */
    public function showLogin(): void
    {
        if (!empty($_SESSION['user_id'])) {
            redirect('/painel');
        }
        view('auth.login', [
            'pageTitle' => 'Entrar no RODAX — Marketplace de Veículos'
        ]);
    }

    /**
     * Processa Login com Proteção Brute Force
     */
    public function login(): void
    {
        $email    = strtolower(trim($_POST['email'] ?? ''));
        $password = $_POST['password'] ?? '';

        if (empty($email) || empty($password)) {
            $_SESSION['flash_error'] = "Preencha o e-mail e a senha.";
            view('auth.login', ['pageTitle' => 'Entrar no RODAX', 'old' => $_POST]);
            return;
        }

        $user = $this->userModel->findByEmail($email);
        $invalidMsg = "E-mail ou senha incorretos.";

        if (!$user) {
            $_SESSION['flash_error'] = $invalidMsg;
            view('auth.login', ['pageTitle' => 'Entrar no RODAX']);
            return;
        }

        if ($this->userModel->isLockedOut($user)) {
            $_SESSION['flash_error'] = "Conta temporariamente bloqueada por segurança devido a múltiplas tentativas incorretas. Tente novamente mais tarde.";
            view('auth.login', ['pageTitle' => 'Entrar no RODAX']);
            return;
        }

        if (!password_verify($password, $user['password_hash'])) {
            $this->userModel->recordFailedLogin($user['id'], (int)($user['login_attempts'] ?? 0));
            $_SESSION['flash_error'] = $invalidMsg;
            view('auth.login', ['pageTitle' => 'Entrar no RODAX']);
            return;
        }

        $this->userModel->resetLoginAttempts($user['id']);

        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
        session_regenerate_id(true);

        $_SESSION['user_id']   = $user['id'];
        $_SESSION['user_name'] = $user['name'];
        $_SESSION['user_email']= $user['email'];
        $_SESSION['user_type'] = $user['user_type'];
        $_SESSION['is_admin']  = (bool)$user['is_admin'];

        $_SESSION['flash_success'] = "Login realizado com sucesso! Olá, " . e($user['name']);
        redirect('/painel');
    }

    /**
     * Realiza Logout Seguro
     */
    public function logout(): void
    {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
        $_SESSION = [];
        if (ini_get("session.use_cookies")) {
            $params = session_get_cookie_params();
            setcookie(session_name(), '', time() - 42000,
                $params["path"], $params["domain"],
                $params["secure"], $params["httponly"]
            );
        }
        session_destroy();

        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
        $_SESSION['flash_success'] = "Você saiu da sua conta com segurança.";
        redirect('/login');
    }

    /**
     * Visualização de Perfil
     */
    public function profile(): void
    {
        $userId = $_SESSION['user_id'] ?? 0;
        $user = $this->userModel->findById($userId);

        if (!$user) {
            redirect('/login');
        }

        view('auth.profile', [
            'pageTitle' => 'Meu Perfil — RODAX',
            'user'      => $user
        ]);
    }

    /**
     * Processa Atualização do Perfil (POST)
     */
    public function updateProfile(): void
    {
        $userId = $_SESSION['user_id'] ?? 0;
        $name = trim($_POST['name'] ?? '');

        if (empty($name) || strlen($name) < 3) {
            $_SESSION['flash_error'] = "Informe seu nome completo (mínimo 3 caracteres).";
            redirect('/perfil');
        }

        $this->userModel->updateProfile($userId, $_POST);
        $_SESSION['user_name'] = $name;

        $_SESSION['flash_success'] = "Dados de perfil atualizados com sucesso!";
        redirect('/perfil');
    }

    /**
     * Processa Alteração de Senha (POST)
     */
    public function updatePassword(): void
    {
        $userId          = $_SESSION['user_id'] ?? 0;
        $currentPassword = $_POST['current_password'] ?? '';
        $newPassword     = $_POST['new_password'] ?? '';
        $confirmPassword = $_POST['confirm_password'] ?? '';

        $user = $this->userModel->findById($userId);

        if (!$user || !password_verify($currentPassword, $user['password_hash'])) {
            $_SESSION['flash_error'] = "Senha atual incorreta.";
            redirect('/perfil');
        }

        if (empty($newPassword) || strlen($newPassword) < 6) {
            $_SESSION['flash_error'] = "A nova senha deve conter no mínimo 6 caracteres.";
            redirect('/perfil');
        }

        if ($newPassword !== $confirmPassword) {
            $_SESSION['flash_error'] = "A confirmação da nova senha não confere.";
            redirect('/perfil');
        }

        $newHash = password_hash($newPassword, PASSWORD_DEFAULT);
        $this->userModel->updatePassword($userId, $newHash);

        $_SESSION['flash_success'] = "Sua senha foi alterada com sucesso!";
        redirect('/perfil');
    }
}
