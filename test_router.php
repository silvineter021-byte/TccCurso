<?php

/**
 * RODAX — Teste Automatizado do Roteador e Estrutura PHP (Etapa 3)
 */

require_once __DIR__ . '/../app/Helpers/Autoloader.php';
require_once __DIR__ . '/../app/Helpers/functions.php';

use App\Core\Router;

echo "=== RODAX: TESTANDO ROTEADOR E ESTRUTURA BASE PHP ===\n\n";

try {
    $router = new Router();

    // 1. Registrar Rota Simples
    echo "[TESTE 1/4] Testando Rota Simples (Home)... ";
    $homeExecuted = false;
    $router->get('/', function() use (&$homeExecuted) {
        $homeExecuted = true;
        return 'HOME_OK';
    });

    $_SERVER['REQUEST_METHOD'] = 'GET';
    $_SERVER['REQUEST_URI']    = '/';
    $res1 = $router->dispatch('GET', '/');
    if ($homeExecuted && $res1 === 'HOME_OK') {
        echo "OK\n";
    } else {
        throw new Exception("Falha na rota simples");
    }

    // 2. Registrar Rota Dinâmica com Parâmetros Amigáveis
    echo "[TESTE 2/4] Testando Rota Amigável com Parâmetro ({slug})... ";
    $slugCaptured = '';
    $router->get('/veiculos/{slug}', function($slug) use (&$slugCaptured) {
        $slugCaptured = $slug;
        return "VEHICLE_" . strtoupper($slug);
    });

    $res2 = $router->dispatch('GET', '/veiculos/volkswagen-golf-2017');
    if ($slugCaptured === 'volkswagen-golf-2017' && $res2 === 'VEHICLE_VOLKSWAGEN-GOLF-2017') {
        echo "OK (Slug capturado: '{$slugCaptured}')\n";
    } else {
        throw new Exception("Falha na captura de parâmetros amigáveis");
    }

    // 3. Testando Helper Functions
    echo "[TESTE 3/4] Testando Funções Auxiliares (XSS, Money, Slugify)... ";
    $xssTest = e("<script>alert('xss')</script>");
    if ($xssTest !== "&lt;script&gt;alert(&#039;xss&#039;)&lt;/script&gt;") {
        throw new Exception("Falha na proteção XSS e()");
    }

    $moneyTest = formatMoney(89900.50);
    if ($moneyTest !== 'R$ 89.900,50') {
        throw new Exception("Falha no formatMoney(): {$moneyTest}");
    }

    $slugTest = slugify("Chevrolet Onix 1.0 Turbo 2023!");
    if ($slugTest !== 'chevrolet-onix-1-0-turbo-2023') {
        throw new Exception("Falha no slugify(): {$slugTest}");
    }
    echo "OK (Todas as funções auxiliares validadas)\n";

    // 4. Testando Renderização da Homepage
    echo "[TESTE 4/4] Testando Renderização do HomeController... ";
    ob_start();
    $_SERVER['SCRIPT_NAME'] = '/index.php';
    $_SERVER['REQUEST_URI'] = '/';
    $homeController = new \App\Controllers\HomeController();
    $homeController->index();
    $output = ob_get_clean();

    if (str_contains($output, 'RODAX') && str_contains($output, 'Seu próximo veículo está aqui')) {
        echo "OK (Homepage renderizada com a marca oficial RODAX!)\n";
    } else {
        throw new Exception("Falha na renderização da homepage");
    }

    echo "\n=== TODOS OS TESTES DA ESTRUTURA BASE E ROTEADOR PASSARAM COM SUCESSO! ===\n";
    exit(0);

} catch (Exception $e) {
    if (ob_get_level()) ob_end_clean();
    echo "\n[ERRO NO TESTE DO ROTEADOR]: " . $e->getMessage() . "\n";
    exit(1);
}
