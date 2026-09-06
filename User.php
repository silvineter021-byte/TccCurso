<?php

namespace App\Models;

use App\Services\Database;
use PDO;
use Exception;

/**
 * RODAX — Model de Usuário com Segurança e Proteção Brute Force
 */
class User
{
    private PDO $db;

    public function __construct(?PDO $db = null)
    {
        $this->db = $db ?? Database::getConnection();
    }

    /**
     * Busca usuário por ID
     */
    public function findById(int $id): ?array
    {
        $stmt = $this->db->prepare("SELECT * FROM users WHERE id = :id LIMIT 1");
        $stmt->execute(['id' => $id]);
        $user = $stmt->fetch();
        return $user ?: null;
    }

    /**
     * Busca usuário por E-mail
     */
    public function findByEmail(string $email): ?array
    {
        $stmt = $this->db->prepare("SELECT * FROM users WHERE email = :email LIMIT 1");
        $stmt->execute(['email' => strtolower(trim($email))]);
        $user = $stmt->fetch();
        return $user ?: null;
    }

    /**
     * Cria um novo usuário com senha hash
     */
    public function create(array $data): int
    {
        $sql = "
            INSERT INTO users (
                name, email, password_hash, phone, user_type, company_name, cpf_cnpj, city, state, is_admin
            ) VALUES (
                :name, :email, :password_hash, :phone, :user_type, :company_name, :cpf_cnpj, :city, :state, :is_admin
            )
        ";

        $stmt = $this->db->prepare($sql);
        $stmt->execute([
            'name'          => trim($data['name']),
            'email'         => strtolower(trim($data['email'])),
            'password_hash' => password_hash($data['password'], PASSWORD_DEFAULT),
            'phone'         => $data['phone'] ?? null,
            'user_type'      => in_array($data['user_type'] ?? '', ['particular', 'loja']) ? $data['user_type'] : 'particular',
            'company_name'  => $data['company_name'] ?? null,
            'cpf_cnpj'      => $data['cpf_cnpj'] ?? null,
            'city'          => $data['city'] ?? null,
            'state'         => !empty($data['state']) ? strtoupper($data['state']) : null,
            'is_admin'      => $data['is_admin'] ?? 0
        ]);

        return (int)$this->db->lastInsertId();
    }

    /**
     * Incrementa tentativas de login incorretas e bloqueia se exceder o limite (5 tentativas)
     */
    public function recordFailedLogin(int $userId, int $currentAttempts): void
    {
        $newAttempts = $currentAttempts + 1;
        
        if ($newAttempts >= 5) {
            // Bloqueio temporário por 15 minutos
            $lockoutUntil = date('Y-m-d H:i:s', strtotime('+15 minutes'));
            $stmt = $this->db->prepare("
                UPDATE users 
                SET login_attempts = :attempts, lockout_until = :lockout 
                WHERE id = :id
            ");
            $stmt->execute([
                'attempts' => $newAttempts,
                'lockout'  => $lockoutUntil,
                'id'       => $userId
            ]);
        } else {
            $stmt = $this->db->prepare("UPDATE users SET login_attempts = :attempts WHERE id = :id");
            $stmt->execute([
                'attempts' => $newAttempts,
                'id'       => $userId
            ]);
        }
    }

    /**
     * Reseta as tentativas de login após um login bem-sucedido
     */
    public function resetLoginAttempts(int $userId): void
    {
        $stmt = $this->db->prepare("
            UPDATE users 
            SET login_attempts = 0, lockout_until = NULL 
            WHERE id = :id
        ");
        $stmt->execute(['id' => $userId]);
    }

    /**
     * Verifica se o usuário está bloqueado por tentativas excessivas de login ou por ação administrativa
     */
    public function isLockedOut(array $user): bool
    {
        if (!empty($user['is_blocked'])) {
            return true;
        }

        if (!empty($user['lockout_until'])) {
            $lockoutTime = strtotime($user['lockout_until']);
            if ($lockoutTime > time()) {
                return true;
            }
        }

        return false;
    }

    /**
     * Atualiza dados de perfil do usuário
     */
    public function updateProfile(int $id, array $data): bool
    {
        $stmt = $this->db->prepare("
            UPDATE users 
            SET name = :name, phone = :phone, company_name = :company_name, city = :city, state = :state
            WHERE id = :id
        ");
        return $stmt->execute([
            'name'         => trim($data['name']),
            'phone'        => $data['phone'] ?? null,
            'company_name' => $data['company_name'] ?? null,
            'city'         => $data['city'] ?? null,
            'state'        => !empty($data['state']) ? strtoupper($data['state']) : null,
            'id'           => $id
        ]);
    }

    /**
     * Atualiza a senha do usuário com novo hash seguro
     */
    public function updatePassword(int $id, string $newPasswordHash): bool
    {
        $stmt = $this->db->prepare("UPDATE users SET password_hash = :hash WHERE id = :id");
        return $stmt->execute([
            'hash' => $newPasswordHash,
            'id'   => $id
        ]);
    }

    /**
     * Retorna o total de usuários cadastrados
     */
    public function countAll(): int
    {
        $stmt = $this->db->query("SELECT COUNT(*) FROM users");
        return (int)$stmt->fetchColumn();
    }

    /**
     * Busca todos os usuários com suporte a filtro de pesquisa (para Admin)
     */
    public function getAllUsers(string $search = ''): array
    {
        $sql = "
            SELECT u.*, 
                   COUNT(v.id) AS total_vehicles
            FROM users u
            LEFT JOIN vehicles v ON u.id = v.user_id AND v.status != 'deleted'
        ";

        $params = [];
        if (!empty($search)) {
            $sql .= " WHERE u.name LIKE :s1 OR u.email LIKE :s2 OR u.phone LIKE :s3";
            $searchVal = '%' . trim($search) . '%';
            $params['s1'] = $searchVal;
            $params['s2'] = $searchVal;
            $params['s3'] = $searchVal;
        }

        $sql .= " GROUP BY u.id ORDER BY u.id DESC";

        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll();
    }


    /**
     * Alterna o status de bloqueio do usuário (Admin)
     */
    public function toggleBlock(int $id): bool
    {
        $stmt = $this->db->prepare("
            UPDATE users 
            SET is_blocked = CASE WHEN is_blocked = 1 THEN 0 ELSE 1 END 
            WHERE id = :id
        ");
        return $stmt->execute(['id' => $id]);
    }
}

