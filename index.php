<?php

/**
 * RODAX — Front Controller / Ponto de Entrada da Aplicação
 */

// Cabeçalhos de Segurança HTTP (Hardening)
header('X-Content-Type-Options: nosniff');
header('X-Frame-Options: SAMEORIGIN');
header('X-XSS-Protection: 1; mode=block');
header('Referrer-Policy: strict-origin-when-cross-origin');
header('Permissions-Policy: geolocation=(), microphone=(), camera=()');

// Iniciar sessão segura
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Carregar Autoloader e Funções Globais
require_once __DIR__ . '/../app/Helpers/Autoloader.php';
require_once __DIR__ . '/../app/Helpers/functions.php';


use App\Core\Router;
use App\Controllers\HomeController;
use App\Controllers\VehicleController;
use App\Controllers\VehicleManageController;
use App\Controllers\AuthController;
use App\Controllers\FavoriteController;
use App\Controllers\MessageController;
use App\Controllers\ReportController;
use App\Controllers\AdminController;
use App\Middleware\AuthMiddleware;
use App\Middleware\AdminMiddleware;
use App\Middleware\CsrfMiddleware;

// Instanciar Roteador
$router = new Router();

// Rotas Públicas
$router->get('/', [HomeController::class, 'index']);
$router->get('/veiculos', [VehicleController::class, 'index']);
$router->get('/veiculos/{slug}', [VehicleController::class, 'show']);
$router->post('/denunciar', [ReportController::class, 'store'], [CsrfMiddleware::class]);

// Rotas Administrativas (Protegidas por AdminMiddleware)
$router->get('/admin', [AdminController::class, 'index'], [AdminMiddleware::class]);
$router->get('/admin/usuarios', [AdminController::class, 'users'], [AdminMiddleware::class]);
$router->post('/admin/usuarios/bloquear', [AdminController::class, 'toggleBlockUser'], [AdminMiddleware::class, CsrfMiddleware::class]);
$router->get('/admin/anuncios', [AdminController::class, 'vehicles'], [AdminMiddleware::class]);
$router->post('/admin/anuncios/status', [AdminController::class, 'updateVehicleStatus'], [AdminMiddleware::class, CsrfMiddleware::class]);
$router->get('/admin/denuncias', [AdminController::class, 'reports'], [AdminMiddleware::class]);
$router->post('/admin/denuncias/status', [AdminController::class, 'updateReportStatus'], [AdminMiddleware::class, CsrfMiddleware::class]);
$router->get('/admin/logs', [AdminController::class, 'logs'], [AdminMiddleware::class]);



// Rotas de Autenticação
$router->get('/cadastro', [AuthController::class, 'showRegister']);
$router->post('/cadastro', [AuthController::class, 'register'], [CsrfMiddleware::class]);
$router->get('/login', [AuthController::class, 'showLogin']);
$router->post('/login', [AuthController::class, 'login'], [CsrfMiddleware::class]);
$router->get('/logout', [AuthController::class, 'logout']);

// Rotas Privadas (Exigem Autenticação)
$router->get('/perfil', [AuthController::class, 'profile'], [AuthMiddleware::class]);
$router->get('/painel', [VehicleManageController::class, 'dashboard'], [AuthMiddleware::class]);
$router->get('/anunciar', [VehicleManageController::class, 'create'], [AuthMiddleware::class]);
$router->post('/anunciar', [VehicleManageController::class, 'store'], [AuthMiddleware::class, CsrfMiddleware::class]);
$router->post('/veiculos/status', [VehicleManageController::class, 'changeStatus'], [AuthMiddleware::class, CsrfMiddleware::class]);
$router->post('/veiculos/excluir', [VehicleManageController::class, 'destroy'], [AuthMiddleware::class, CsrfMiddleware::class]);

// Rotas de Gestão de Fotos
$router->get('/veiculos/fotos/{id}', [VehicleManageController::class, 'manageImages'], [AuthMiddleware::class]);
$router->post('/veiculos/fotos/upload', [VehicleManageController::class, 'uploadImages'], [AuthMiddleware::class, CsrfMiddleware::class]);
$router->post('/veiculos/fotos/principal', [VehicleManageController::class, 'setMainImage'], [AuthMiddleware::class, CsrfMiddleware::class]);
$router->post('/veiculos/fotos/excluir', [VehicleManageController::class, 'deleteImage'], [AuthMiddleware::class, CsrfMiddleware::class]);

// Rotas do Sistema de Favoritos
$router->get('/favoritos', [FavoriteController::class, 'index'], [AuthMiddleware::class]);
$router->post('/favoritos/toggle', [FavoriteController::class, 'toggle'], [AuthMiddleware::class, CsrfMiddleware::class]);

// Rotas do Sistema de Mensagens
$router->get('/mensagens', [MessageController::class, 'index'], [AuthMiddleware::class]);
$router->get('/mensagens/nova/{id}', [MessageController::class, 'newMessage'], [AuthMiddleware::class]);
$router->get('/mensagens/{vehicle_id}/{partner_id}', [MessageController::class, 'showThread'], [AuthMiddleware::class]);
$router->post('/mensagens/enviar', [MessageController::class, 'send'], [AuthMiddleware::class, CsrfMiddleware::class]);

// Rota de Status do Sistema RODAX
$router->get('/status', function() {
    header('Content-Type: application/json');
    echo json_encode([
        'app'     => 'RODAX',
        'slogan'  => 'RODAX — Seu próximo veículo está aqui.',
        'status'  => 'online',
        'env'     => env('APP_ENV', 'development'),
        'version' => '1.0.0'
    ]);
});

// Despachar a requisição
$requestMethod = $_SERVER['REQUEST_METHOD'] ?? 'GET';
$requestUri    = $_SERVER['REQUEST_URI'] ?? '/';

$router->dispatch($requestMethod, $requestUri);
