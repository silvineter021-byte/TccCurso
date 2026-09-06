<?php

namespace App\Models;

use App\Services\Database;
use PDO;
use Exception;

/**
 * RODAX — Model do Sistema de Mensagens e Chat
 */
class Message
{
    private PDO $db;

    public function __construct(?PDO $db = null)
    {
        $this->db = $db ?? Database::getConnection();
    }

    /**
     * Envia uma mensagem
     */
    public function send(int $vehicleId, int $senderId, int $receiverId, string $text): int
    {
        if ($senderId === $receiverId) {
            throw new Exception("Você não pode enviar uma mensagem para você mesmo.");
        }

        $stmt = $this->db->prepare("
            INSERT INTO messages (vehicle_id, sender_id, receiver_id, message_text, is_read, created_at)
            VALUES (:vehicle_id, :sender_id, :receiver_id, :message_text, 0, NOW())
        ");
        $stmt->execute([
            'vehicle_id'   => $vehicleId,
            'sender_id'    => $senderId,
            'receiver_id'  => $receiverId,
            'message_text' => trim($text)
        ]);

        return (int)$this->db->lastInsertId();
    }

    /**
     * Busca todas as conversas ativas do usuário (caixa de entrada/conversas agrupadas)
     */
    public function getUserConversations(int $userId): array
    {
        $sql = "
            SELECT 
                m.vehicle_id,
                v.title AS vehicle_title,
                v.slug AS vehicle_slug,
                CASE WHEN m.sender_id = :user_id1 THEN m.receiver_id ELSE m.sender_id END AS partner_id,
                u.name AS partner_name,
                u.user_type AS partner_user_type,
                m.message_text AS last_message,
                m.created_at AS last_message_at,
                m.sender_id AS last_sender_id,
                (
                    SELECT COUNT(*) 
                    FROM messages unread 
                    WHERE unread.vehicle_id = m.vehicle_id 
                      AND unread.receiver_id = :user_id2 
                      AND unread.sender_id = partner_id
                      AND unread.is_read = 0
                ) AS unread_count
            FROM messages m
            JOIN vehicles v ON m.vehicle_id = v.id
            JOIN users u ON u.id = (CASE WHEN m.sender_id = :user_id3 THEN m.receiver_id ELSE m.sender_id END)
            INNER JOIN (
                SELECT 
                    vehicle_id,
                    LEAST(sender_id, receiver_id) AS user_a,
                    GREATEST(sender_id, receiver_id) AS user_b,
                    MAX(id) AS max_id
                FROM messages
                WHERE sender_id = :user_id4 OR receiver_id = :user_id5
                GROUP BY vehicle_id, user_a, user_b
            ) latest ON m.id = latest.max_id
            ORDER BY m.id DESC
        ";

        $stmt = $this->db->prepare($sql);
        $stmt->execute([
            'user_id1' => $userId,
            'user_id2' => $userId,
            'user_id3' => $userId,
            'user_id4' => $userId,
            'user_id5' => $userId
        ]);

        return $stmt->fetchAll();
    }

    /**
     * Busca o histórico de mensagens de uma conversa entre dois usuários sobre determinado veículo
     */
    public function getConversationThread(int $vehicleId, int $userId, int $partnerId): array
    {
        $stmt = $this->db->prepare("
            SELECT 
                m.*,
                sender.name AS sender_name,
                receiver.name AS receiver_name
            FROM messages m
            JOIN users sender ON m.sender_id = sender.id
            JOIN users receiver ON m.receiver_id = receiver.id
            WHERE m.vehicle_id = :vehicle_id 
              AND (
                (m.sender_id = :u1 AND m.receiver_id = :p1) 
                OR 
                (m.sender_id = :p2 AND m.receiver_id = :u2)
              )
            ORDER BY m.id ASC
        ");
        $stmt->execute([
            'vehicle_id' => $vehicleId,
            'u1'         => $userId,
            'p1'         => $partnerId,
            'p2'         => $partnerId,
            'u2'         => $userId
        ]);

        return $stmt->fetchAll();
    }

    /**
     * Marca todas as mensagens de um parceiro como lidas
     */
    public function markThreadAsRead(int $vehicleId, int $userId, int $partnerId): void
    {
        $stmt = $this->db->prepare("
            UPDATE messages 
            SET is_read = 1, read_at = NOW() 
            WHERE vehicle_id = :vehicle_id AND receiver_id = :user_id AND sender_id = :partner_id AND is_read = 0
        ");
        $stmt->execute([
            'vehicle_id' => $vehicleId,
            'user_id'    => $userId,
            'partner_id' => $partnerId
        ]);
    }

    /**
     * Retorna a contagem total de mensagens não lidas do usuário
     */
    public function getUnreadCount(int $userId): int
    {
        $stmt = $this->db->prepare("SELECT COUNT(*) FROM messages WHERE receiver_id = :user_id AND is_read = 0");
        $stmt->execute(['user_id' => $userId]);
        return (int)$stmt->fetchColumn();
    }
}
