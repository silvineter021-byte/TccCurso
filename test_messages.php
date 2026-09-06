<?php

/**
 * RODAX — Teste Automatizado do Sistema de Mensagens e Chat (Etapa 10)
 */

require_once __DIR__ . '/../app/Helpers/Autoloader.php';
require_once __DIR__ . '/../app/Helpers/functions.php';

use App\Services\Database;
use App\Models\User;
use App\Models\Vehicle;
use App\Models\Message;

echo "=== RODAX: BATERIA DE TESTES DO SISTEMA DE MENSAGENS E CHAT ===\n\n";

try {
    $pdo = Database::getConnection();
    $userModel = new User($pdo);
    $vehicleModel = new Vehicle($pdo);
    $messageModel = new Message($pdo);

    // 1. Criar Vendedor, Comprador e Veículo de Teste
    $sellerId = $userModel->create([
        'name'      => 'Vendedor Teste Chat',
        'email'     => 'seller_chat_' . time() . '@rodax.com.br',
        'password'  => 'Senha123',
        'user_type' => 'loja'
    ]);

    $buyerId = $userModel->create([
        'name'      => 'Comprador Teste Chat',
        'email'     => 'buyer_chat_' . time() . '@rodax.com.br',
        'password'  => 'Senha123',
        'user_type' => 'particular'
    ]);

    $vehicleId = $vehicleModel->create([
        'user_id'          => $sellerId,
        'vehicle_type_id'  => 1,
        'title'            => 'Jeep Compass 1.3 Turbo 2022',
        'brand'            => 'Jeep',
        'model'            => 'Compass',
        'year_manufacture' => 2022,
        'year_model'       => 2022,
        'price'            => 145000.00,
        'color'            => 'Preto',
        'fuel_type'        => 'Flex',
        'state'            => 'SP',
        'city'             => 'São Paulo'
    ]);

    // 2. Teste de Envio de Primeira Mensagem do Comprador -> Vendedor
    echo "[TESTE 1/5] Testando envio de mensagem do comprador para o vendedor... ";
    $msgText1 = "Olá! Tenho interesse no Jeep Compass. Aceita financiamento?";
    $msgId1 = $messageModel->send($vehicleId, $buyerId, $sellerId, $msgText1);

    if ($msgId1 > 0) {
        echo "OK (Mensagem ID: {$msgId1} enviada com sucesso!)\n";
    } else {
        throw new Exception("Falha ao enviar mensagem.");
    }

    // 3. Teste de Contagem de Não Lidas no Vendedor
    echo "[TESTE 2/5] Testando contagem de mensagens não lidas no vendedor... ";
    $unreadSeller = $messageModel->getUnreadCount($sellerId);
    if ($unreadSeller === 1) {
        echo "OK (Vendedor possui 1 mensagem não lida)\n";
    } else {
        throw new Exception("Contagem de não lidas incorreta: {$unreadSeller}");
    }

    // 4. Teste de Listagem de Conversas na Caixa de Entrada
    echo "[TESTE 3/5] Testando listagem da Caixa de Entrada do Vendedor... ";
    $sellerConvs = $messageModel->getUserConversations($sellerId);

    if (count($sellerConvs) === 1 && $sellerConvs[0]['vehicle_title'] === 'Jeep Compass 1.3 Turbo 2022') {
        echo "OK (Conversa sobre o Jeep Compass listada na caixa de entrada do vendedor)\n";
    } else {
        throw new Exception("Falha ao recuperar as conversas do usuário.");
    }

    // 5. Teste de Leitura do Chat e Marcação como Lida
    echo "[TESTE 4/5] Testando leitura da conversa e atualização de status (markThreadAsRead)... ";
    $messageModel->markThreadAsRead($vehicleId, $sellerId, $buyerId);
    $unreadAfter = $messageModel->getUnreadCount($sellerId);

    if ($unreadAfter === 0) {
        echo "OK (Mensagens marcadas como lidas com sucesso! Contagem zerada)\n";
    } else {
        throw new Exception("Falha ao marcar thread como lida.");
    }

    // 6. Teste de Resposta do Vendedor ao Comprador
    echo "[TESTE 5/5] Testando resposta do vendedor e atualização do histórico... ";
    $replyText = "Olá! Sim, aceitamos financiamento com entrada facilitada.";
    $msgId2 = $messageModel->send($vehicleId, $sellerId, $buyerId, $replyText);

    $thread = $messageModel->getConversationThread($vehicleId, $buyerId, $sellerId);

    if (count($thread) === 2 && $thread[1]['message_text'] === $replyText) {
        echo "OK (Histórico completo com 2 mensagens trocadas!)\n";
    } else {
        throw new Exception("Falha na recuperação do histórico do chat.");
    }

    // Limpeza de dados do teste
    $pdo->prepare("DELETE FROM users WHERE id IN (:u1, :u2)")->execute(['u1' => $sellerId, 'u2' => $buyerId]);

    echo "\n=== TODOS OS TESTES DO SISTEMA DE MENSAGENS PASSARAM COM SUCESSO! ===\n";
    exit(0);

} catch (Exception $e) {
    echo "\n[ERRO NO TESTE DE MENSAGENS]: " . $e->getMessage() . "\n";
    echo "Trace: " . $e->getTraceAsString() . "\n";
    exit(1);
}
