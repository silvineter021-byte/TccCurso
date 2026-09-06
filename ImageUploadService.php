<?php

namespace App\Services;

use PDO;
use Exception;

/**
 * RODAX — Serviço Seguro de Upload e Gestão de Imagens
 * 
 * Funcionalidades:
 * - Validação estrita de extensão (JPG, PNG, WEBP) e MIME-type real (via finfo)
 * - Limite de tamanho por imagem (5MB) e quantidade por anúncio (15 fotos)
 * - Geração de nomes aleatórios com hash criptográfico (bin2hex(random_bytes(16)))
 * - Definição e alternância de Foto Principal (is_main)
 * - Exclusão física do arquivo no disco e remoção do banco de dados com checagem IDOR
 */
class ImageUploadService
{
    private PDO $db;
    private string $uploadDir;
    private array $allowedMimeTypes = [
        'image/jpeg' => 'jpg',
        'image/jpg'  => 'jpg',
        'image/png'  => 'png',
        'image/webp' => 'webp'
    ];
    private int $maxFileSize = 5242880; // 5MB em bytes
    private int $maxImagesPerVehicle = 15;

    public function __construct(?PDO $db = null, ?string $uploadDir = null)
    {
        $this->db = $db ?? Database::getConnection();
        $this->uploadDir = $uploadDir ?? __DIR__ . '/../../public/uploads/vehicles/';

        if (!file_exists($this->uploadDir)) {
            mkdir($this->uploadDir, 0755, true);
        }
    }

    /**
     * Processa o upload de arquivos enviados via $_FILES
     */
    public function uploadVehicleImages(int $vehicleId, int $userId, array $files): array
    {
        // 1. Verificar se o veículo pertence ao usuário logado (Proteção IDOR)
        $stmtOwner = $this->db->prepare("SELECT id FROM vehicles WHERE id = :id AND user_id = :user_id LIMIT 1");
        $stmtOwner->execute(['id' => $vehicleId, 'user_id' => $userId]);
        if (!$stmtOwner->fetch()) {
            return ['success' => false, 'error' => 'Operação não autorizada para este veículo.'];
        }

        // 2. Contar imagens já existentes
        $stmtCount = $this->db->prepare("SELECT COUNT(*) FROM vehicle_images WHERE vehicle_id = :id");
        $stmtCount->execute(['id' => $vehicleId]);
        $currentImageCount = (int)$stmtCount->fetchColumn();

        if ($currentImageCount >= $this->maxImagesPerVehicle) {
            return ['success' => false, 'error' => "Limite máximo de {$this->maxImagesPerVehicle} fotos atingido."];
        }

        // Reorganizar estrutura de $_FILES se vier em array de múltiplos arquivos
        $normalizedFiles = $this->normalizeFilesArray($files);
        $uploadedResults = [];
        $errors = [];

        foreach ($normalizedFiles as $file) {
            if ($file['error'] !== UPLOAD_ERR_OK) {
                continue;
            }

            if ($currentImageCount >= $this->maxImagesPerVehicle) {
                $errors[] = "Algumas fotos não foram enviadas pois o limite máximo de {$this->maxImagesPerVehicle} fotos foi atingido.";
                break;
            }

            // A) Validar Tamanho
            if ($file['size'] > $this->maxFileSize) {
                $errors[] = "A foto '{$file['name']}' excede o tamanho máximo permitido de 5MB.";
                continue;
            }

            // B) Validar MIME-type real através da extensão PHP 'finfo'
            $finfo = finfo_open(FILEINFO_MIME_TYPE);
            $mimeType = finfo_file($finfo, $file['tmp_name']);
            finfo_close($finfo);

            if (!isset($this->allowedMimeTypes[$mimeType])) {
                $errors[] = "O arquivo '{$file['name']}' não é uma imagem válida (Formatos permitidos: JPG, PNG, WEBP).";
                continue;
            }

            // C) Gerar nome aleatório seguro
            $extension = $this->allowedMimeTypes[$mimeType];
            $randomName = bin2hex(random_bytes(16)) . '.' . $extension;
            $destinationPath = $this->uploadDir . $randomName;
            $relativePath = 'uploads/vehicles/' . $randomName;

            // D) Mover arquivo para o diretório final
            if (move_uploaded_file($file['tmp_name'], $destinationPath) || copy($file['tmp_name'], $destinationPath)) {
                // Verificar se é a primeira imagem para defini-la como principal
                $isMain = ($currentImageCount === 0) ? 1 : 0;
                $displayOrder = $currentImageCount + 1;

                $stmtInsert = $this->db->prepare("
                    INSERT INTO vehicle_images (vehicle_id, image_path, is_main, display_order)
                    VALUES (:vehicle_id, :path, :is_main, :display_order)
                ");
                $stmtInsert->execute([
                    'vehicle_id'    => $vehicleId,
                    'path'          => $relativePath,
                    'is_main'       => $isMain,
                    'display_order' => $displayOrder
                ]);

                $uploadedResults[] = [
                    'id'   => (int)$this->db->lastInsertId(),
                    'path' => $relativePath
                ];

                $currentImageCount++;
            } else {
                $errors[] = "Falha ao salvar a imagem '{$file['name']}' no servidor.";
            }
        }

        return [
            'success'  => count($uploadedResults) > 0,
            'uploaded' => $uploadedResults,
            'errors'   => $errors
        ];
    }

    /**
     * Define uma foto específica como a Principal (is_main = 1) do anúncio
     */
    public function setMainImage(int $imageId, int $vehicleId, int $userId): bool
    {
        // Checagem IDOR
        $stmtOwner = $this->db->prepare("
            SELECT img.id 
            FROM vehicle_images img
            JOIN vehicles v ON img.vehicle_id = v.id
            WHERE img.id = :image_id AND v.id = :vehicle_id AND v.user_id = :user_id
            LIMIT 1
        ");
        $stmtOwner->execute([
            'image_id'   => $imageId,
            'vehicle_id' => $vehicleId,
            'user_id'    => $userId
        ]);

        if (!$stmtOwner->fetch()) {
            return false;
        }

        $this->db->beginTransaction();
        try {
            // Desmarcar todas as outras fotos do veículo
            $stmtReset = $this->db->prepare("UPDATE vehicle_images SET is_main = 0 WHERE vehicle_id = :vehicle_id");
            $stmtReset->execute(['vehicle_id' => $vehicleId]);

            // Marcar a imagem selecionada
            $stmtSet = $this->db->prepare("UPDATE vehicle_images SET is_main = 1 WHERE id = :id");
            $stmtSet->execute(['id' => $imageId]);

            $this->db->commit();
            return true;
        } catch (Exception $e) {
            $this->db->rollBack();
            return false;
        }
    }

    /**
     * Exclui uma imagem (remoção do banco e exclusão física no disco)
     */
    public function deleteImage(int $imageId, int $vehicleId, int $userId): bool
    {
        // Checagem IDOR e busca do caminho da imagem
        $stmtImage = $this->db->prepare("
            SELECT img.id, img.image_path, img.is_main 
            FROM vehicle_images img
            JOIN vehicles v ON img.vehicle_id = v.id
            WHERE img.id = :image_id AND v.id = :vehicle_id AND v.user_id = :user_id
            LIMIT 1
        ");
        $stmtImage->execute([
            'image_id'   => $imageId,
            'vehicle_id' => $vehicleId,
            'user_id'    => $userId
        ]);
        $image = $stmtImage->fetch();

        if (!$image) {
            return false;
        }

        // Deletar do banco de dados
        $stmtDelete = $this->db->prepare("DELETE FROM vehicle_images WHERE id = :id");
        $stmtDelete->execute(['id' => $imageId]);

        // Apagar arquivo físico se existir
        $fullPath = __DIR__ . '/../../public/' . $image['image_path'];
        if (file_exists($fullPath)) {
            @unlink($fullPath);
        }

        // Se era a imagem principal, eleger a próxima imagem como principal
        if (!empty($image['is_main'])) {
            $stmtNext = $this->db->prepare("
                SELECT id FROM vehicle_images WHERE vehicle_id = :vehicle_id ORDER BY display_order ASC LIMIT 1
            ");
            $stmtNext->execute(['vehicle_id' => $vehicleId]);
            $nextId = $stmtNext->fetchColumn();
            if ($nextId) {
                $this->db->prepare("UPDATE vehicle_images SET is_main = 1 WHERE id = :id")->execute(['id' => $nextId]);
            }
        }

        return true;
    }

    /**
     * Organiza a estrutura do $_FILES
     */
    private function normalizeFilesArray(array $files): array
    {
        $normalized = [];
        if (!isset($files['name'])) {
            return $normalized;
        }

        if (is_array($files['name'])) {
            foreach ($files['name'] as $idx => $name) {
                $normalized[] = [
                    'name'     => $name,
                    'type'     => $files['type'][$idx] ?? '',
                    'tmp_name' => $files['tmp_name'][$idx] ?? '',
                    'error'    => $files['error'][$idx] ?? UPLOAD_ERR_NO_FILE,
                    'size'     => $files['size'][$idx] ?? 0
                ];
            }
        } else {
            $normalized[] = $files;
        }

        return $normalized;
    }
}
