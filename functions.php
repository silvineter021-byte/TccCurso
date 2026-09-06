<?php

/**
 * RODAX — Funções Auxiliares Globais de Segurança e Utilitários
 */

if (!function_exists('e')) {
    /**
     * Escapa dados HTML para prevenir XSS (Cross-Site Scripting)
     */
    function e(?string $value): string {
        return htmlspecialchars($value ?? '', ENT_QUOTES, 'UTF-8');
    }
}

if (!function_exists('baseUrl')) {
    /**
     * Retorna a URL base da aplicação
     */
    function baseUrl(string $path = ''): string {
        if (!empty($_SERVER['HTTP_HOST'])) {
            $scheme = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
            $host = $_SERVER['HTTP_HOST'];
            $scriptName = $_SERVER['SCRIPT_NAME'] ?? '';
            $dir = rtrim(dirname($scriptName), '/\\');
            if ($dir === '/' || $dir === '\\') {
                $dir = '';
            }
            $baseUrl = "{$scheme}://{$host}{$dir}";
        } else {
            $baseUrl = rtrim(env('APP_URL', 'http://localhost:8000'), '/');
        }
        return rtrim($baseUrl, '/') . '/' . ltrim($path, '/');
    }
}

if (!function_exists('redirect')) {
    /**
     * Redireciona para uma URL interna de forma segura
     */
    function redirect(string $path): void {
        $url = str_starts_with($path, 'http') ? $path : baseUrl($path);
        header("Location: {$url}");
        exit;
    }
}

if (!function_exists('view')) {
    /**
     * Renderiza uma view com variáveis injetadas
     */
    function view(string $viewPath, array $data = []): void {
        extract($data);
        $file = __DIR__ . '/../../views/' . str_replace('.', '/', $viewPath) . '.php';
        if (!file_exists($file)) {
            throw new Exception("View file not found: {$viewPath}");
        }
        require $file;
    }
}

if (!function_exists('csrf_token')) {
    /**
     * Gera ou recupera o token CSRF da sessão atual
     */
    function csrf_token(): string {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
        if (empty($_SESSION['csrf_token'])) {
            $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
        }
        return $_SESSION['csrf_token'];
    }
}

if (!function_exists('csrf_field')) {
    /**
     * Gera o campo HTML hidden com o token CSRF
     */
    function csrf_field(): string {
        $token = csrf_token();
        return '<input type="hidden" name="csrf_token" value="' . e($token) . '">';
    }
}

if (!function_exists('verify_csrf_token')) {
    /**
     * Valida se o token fornecido bate com o token da sessão de forma segura contra timing attacks
     */
    function verify_csrf_token(?string $token): bool {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
        $sessionToken = $_SESSION['csrf_token'] ?? '';
        return !empty($token) && !empty($sessionToken) && hash_equals($sessionToken, $token);
    }
}


if (!function_exists('formatMoney')) {
    /**
     * Formata um valor numérico para o padrão de moeda brasileira (R$)
     */
    function formatMoney(?float $amount): string {
        return 'R$ ' . number_format($amount ?? 0, 2, ',', '.');
    }
}

if (!function_exists('formatKm')) {
    /**
     * Formata a quilometragem do veículo
     */
    function formatKm(?int $km): string {
        return number_format($km ?? 0, 0, '', '.') . ' km';
    }
}

if (!function_exists('slugify')) {
    /**
     * Transforma qualquer texto em uma URL amigável (slug)
     */
    function slugify(string $text): string {
        $text = iconv('UTF-8', 'ASCII//TRANSLIT//IGNORE', $text);
        $text = preg_replace('~[^\pL\d]+~u', '-', $text);
        $text = trim($text, '-');
        $text = strtolower($text);
        return empty($text) ? 'n-a' : $text;
    }
}
