<?php

namespace App\Core;

use Exception;

/**
 * RODAX — Roteador de URLs Amigáveis de Alta Performance
 * 
 * Mapeia URLs como '/veiculos/volkswagen/golf-1-4-2017' para Controllers e Actions.
 */
class Router
{
    private array $routes = [];

    /**
     * Adiciona uma rota GET
     */
    public function get(string $path, array|callable $handler, array $middlewares = []): void
    {
        $this->addRoute('GET', $path, $handler, $middlewares);
    }

    /**
     * Adiciona uma rota POST
     */
    public function post(string $path, array|callable $handler, array $middlewares = []): void
    {
        $this->addRoute('POST', $path, $handler, $middlewares);
    }

    /**
     * Registra rota interna com regex de correspondência
     */
    private function addRoute(string $method, string $path, array|callable $handler, array $middlewares): void
    {
        // Converter parâmetros como {slug} em regex ([^/]+)
        $pattern = preg_replace('/\{([a-zA-Z0-9_]+)\}/', '(?P<$1>[^/]+)', $path);
        $pattern = '#^' . rtrim($pattern, '/') . '/?$#';

        $this->routes[] = [
            'method'      => strtoupper($method),
            'path'        => $path,
            'pattern'     => $pattern,
            'handler'     => $handler,
            'middlewares' => $middlewares
        ];
    }

    /**
     * Executa a busca e o despacho da rota atual
     */
    public function dispatch(string $method, string $uri): mixed
    {
        $uri = parse_url($uri, PHP_URL_PATH);
        $uri = rawurldecode($uri);
        
        // Remover caminho base se rodando em subdiretório (ex: /rodax/public)
        $scriptName = dirname($_SERVER['SCRIPT_NAME'] ?? '');
        if ($scriptName !== '/' && $scriptName !== '\\' && str_starts_with($uri, $scriptName)) {
            $uri = substr($uri, strlen($scriptName));
        }
        $uri = '/' . ltrim($uri, '/');

        $method = strtoupper($method);

        foreach ($this->routes as $route) {
            if ($route['method'] !== $method) {
                continue;
            }

            if (preg_match($route['pattern'], $uri, $matches)) {
                // Extrair argumentos nomeados
                $args = array_filter($matches, fn($key) => !is_int($key), ARRAY_FILTER_USE_KEY);

                // Executar Middlewares registrados
                foreach ($route['middlewares'] as $middlewareClass) {
                    if (class_exists($middlewareClass)) {
                        $middleware = new $middlewareClass();
                        $middleware->handle();
                    }
                }

                // Executar Callback / Controller
                $handler = $route['handler'];

                if (is_callable($handler)) {
                    return call_user_func_array($handler, $args);
                }

                if (is_array($handler) && count($handler) === 2) {
                    [$controllerClass, $methodName] = $handler;

                    if (!class_exists($controllerClass)) {
                        throw new Exception("Controller class not found: {$controllerClass}");
                    }

                    $controller = new $controllerClass();

                    if (!method_exists($controller, $methodName)) {
                        throw new Exception("Method {$methodName} not found in {$controllerClass}");
                    }

                    return call_user_func_array([$controller, $methodName], $args);
                }
            }
        }

        // Rota não encontrada (404)
        http_response_code(404);
        if (file_exists(__DIR__ . '/../../views/errors/404.php')) {
            require __DIR__ . '/../../views/errors/404.php';
        } else {
            echo "<h1>404 — Página Não Encontrada</h1><p>A página requisitada no RODAX não existe.</p>";
        }
        exit;
    }
}
