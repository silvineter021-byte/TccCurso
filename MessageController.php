<?php

namespace App\Controllers;

use App\Models\Message;
use App\Models\Vehicle;
use App\Models\User;

/**
 * RODAX — Controller do Sistema de Mensagens e Chat
 */
class MessageController
{
    private Message $messageModel;
    private Vehicle $vehicleModel;
    private User $userModel;

    public function __construct()
    {
        $this->messageModel = new Message();
        $this->vehicleModel = new Vehicle();
        $this->userModel    = new User();
    }

    /**
     * Caixa de Entrada / Lista de Conversas
     */
    public function index(): void
    {
        $userId = $_SESSION['user_id'] ?? 0;
        $conversations = $this->messageModel->getUserConversations($userId);

        view('messages.index', [
            'pageTitle'     => 'Minhas Mensagens — RODAX',
            'conversations' => $conversations
        ]);
    }

    /**
     * Exibe o Histórico de Conversa (Thread) entre dois usuários sobre um anúncio
     */
    public function showThread(string $vehicleId, string $partnerId): void
    {
        $userId    = $_SESSION['user_id'] ?? 0;
        $vehicleId = (int)$vehicleId;
        $partnerId = (int)$partnerId;

        $vehicle = $this->vehicleModel->findById($vehicleId);
        $partner = $this->userModel->findById($partnerId);

        if (!$vehicle || !$partner) {
            $_SESSION['flash_error'] = "Conversa ou veículo não encontrado.";
            redirect('/mensagens');
        }

        // Marcar mensagens recebidas nesta conversa como lidas
        $this->messageModel->markThreadAsRead($vehicleId, $userId, $partnerId);

        // Buscar histórico de mensagens
        $thread = $this->messageModel->getConversationThread($vehicleId, $userId, $partnerId);

        view('messages.thread', [
            'pageTitle' => 'Chat: ' . $vehicle['title'] . ' — RODAX',
            'vehicle'   => $vehicle,
            'partner'   => $partner,
            'thread'    => $thread
        ]);
    }

    /**
     * Formulário para Iniciar Primeira Mensagem a partir do anúncio
     */
    public function newMessage(string $vehicleId): void
    {
        $userId    = $_SESSION['user_id'] ?? 0;
        $vehicleId = (int)$vehicleId;

        $vehicle = $this->vehicleModel->findById($vehicleId);
        if (!$vehicle) {
            $_SESSION['flash_error'] = "Veículo não encontrado.";
            redirect('/veiculos');
        }

        if ((int)$vehicle['user_id'] === $userId) {
            $_SESSION['flash_error'] = "Você é o proprietário deste anúncio.";
            redirect("/veiculos/{$vehicle['slug']}");
        }

        $seller = $this->userModel->findById((int)$vehicle['user_id']);

        view('messages.new', [
            'pageTitle' => 'Contatar Vendedor — RODAX',
            'vehicle'   => $vehicle,
            'seller'    => $seller
        ]);
    }

    /**
     * Processa o Envio de Mensagem (POST)
     */
    public function send(): void
    {
        $userId     = $_SESSION['user_id'] ?? 0;
        $vehicleId  = (int)($_POST['vehicle_id'] ?? 0);
        $receiverId = (int)($_POST['receiver_id'] ?? 0);
        $text       = trim($_POST['message_text'] ?? '');

        if (empty($text)) {
            $_SESSION['flash_error'] = "Digite uma mensagem antes de enviar.";
            redirect("/mensagens/{$vehicleId}/{$receiverId}");
        }

        if ($userId === $receiverId) {
            $_SESSION['flash_error'] = "Você não pode enviar mensagem para si mesmo.";
            redirect('/mensagens');
        }

        $this->messageModel->send($vehicleId, $userId, $receiverId, $text);

        $_SESSION['flash_success'] = "Mensagem enviada com sucesso!";
        redirect("/mensagens/{$vehicleId}/{$receiverId}");
    }
}
