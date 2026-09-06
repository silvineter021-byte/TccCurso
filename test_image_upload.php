<?php

/**
 * RODAX — Teste Automatizado de Upload de Imagens e Segurança (Etapa 6)
 */

require_once __DIR__ . '/../app/Helpers/Autoloader.php';
require_once __DIR__ . '/../app/Helpers/functions.php';

use App\Services\Database;
use App\Services\ImageUploadService;
use App\Models\User;
use App\Models\Vehicle;

echo "=== RODAX: BATERIA DE TESTES DE UPLOAD DE IMAGENS E SEGURANÇA ===\n\n";

try {
    $pdo = Database::getConnection();
    $userModel = new User($pdo);
    $vehicleModel = new Vehicle($pdo);
    $imageService = new ImageUploadService($pdo);

    // 1. Criar Usuário Vendedor e Veículo de Teste
    $sellerId = $userModel->create([
        'name'      => 'Vendedor Teste Upload',
        'email'     => 'seller_img_' . time() . '@rodax.com.br',
        'password'  => 'SenhaSegura123',
        'phone'     => '(11) 97777-2222',
        'user_type' => 'particular',
        'city'      => 'São Paulo',
        'state'     => 'SP'
    ]);

    $vehicleId = $vehicleModel->create([
        'user_id'          => $sellerId,
        'vehicle_type_id'  => 1,
        'title'            => 'Toyota Corolla 2.0 XEi 2022',
        'brand'            => 'Toyota',
        'model'            => 'Corolla',
        'year_manufacture' => 2021,
        'year_model'       => 2022,
        'mileage'          => 30000,
        'price'            => 129900.00,
        'color'            => 'Prata',
        'fuel_type'        => 'Flex',
        'state'            => 'SP',
        'city'             => 'São Paulo'
    ]);

    // Helper para gerar um arquivo JPEG binário válido sem depender da GD
    $createSampleJpeg = function(string $filename) {
        $binaryJpeg = "\xFF\xD8\xFF\xE0\x00\x10JFIF\x00\x01\x01\x01\x00\x48\x00\x48\x00\x00\xFF\xDB\x00\x43\x00\x08\x06\x06\x07\x06\x05\x08\x07\x07\x07\x09\x09\x08\x0A\x0C\x14\x0D\x0C\x0B\x0B\x0C\x19\x12\x13\x0F\x14\x1D\x1A\x1F\x1E\x1D\x1A\x1C\x1C\x20\x24\x2E\x27\x20\x22\x2C\x23\x1C\x1C\x28\x37\x29\x2C\x30\x31\x34\x34\x34\x1F\x27\x39\x3D\x38\x32\x3C\x2E\x33\x34\x32\xFF\xC0\x00\x0B\x08\x00\x01\x00\x01\x01\x01\x11\x00\xFF\xC4\x00\x1F\x00\x00\x01\x05\x01\x01\x01\x01\x01\x01\x00\x00\x00\x00\x00\x00\x00\x00\x01\x02\x03\x04\x05\x06\x07\x08\x09\x0A\x0B\xFF\xDA\x00\x08\x01\x01\x00\x00\x3F\x00\xBF\x00\xFF\xD9";
        $tempPath = sys_get_temp_dir() . '/' . $filename;
        file_put_contents($tempPath, $binaryJpeg);
        return $tempPath;
    };

    // 2. Teste de Upload Válido de Imagens (1ª Foto deve ser definida como is_main = 1)
    echo "[TESTE 1/5] Testando upload de imagem JPEG válida (1ª Foto)... ";
    $sample1 = $createSampleJpeg('rodax_test_1.jpg');

    $filesMock1 = [
        'name'     => 'corolla_frente.jpg',
        'type'     => 'image/jpeg',
        'tmp_name' => $sample1,
        'error'    => UPLOAD_ERR_OK,
        'size'     => filesize($sample1)
    ];

    $uploadResult1 = $imageService->uploadVehicleImages($vehicleId, $sellerId, $filesMock1);
    
    if ($uploadResult1['success'] && count($uploadResult1['uploaded']) === 1) {
        $imgId1 = $uploadResult1['uploaded'][0]['id'];
        $stmtCheck = $pdo->prepare("SELECT is_main, image_path FROM vehicle_images WHERE id = :id");
        $stmtCheck->execute(['id' => $imgId1]);
        $row1 = $stmtCheck->fetch();

        if ((int)$row1['is_main'] === 1 && file_exists(__DIR__ . '/../public/' . $row1['image_path'])) {
            echo "OK (ID: {$imgId1}, Foto Principal ativada automaticamente!)\n";
        } else {
            throw new Exception("Imagem não salva corretamente no banco de dados ou no disco.");
        }
    } else {
        throw new Exception("Falha no upload da primeira imagem: " . implode(', ', $uploadResult1['errors']));
    }

    // 3. Upload de 2ª Imagem (deve ser is_main = 0)
    echo "[TESTE 2/5] Testando upload de 2ª imagem (is_main = 0)... ";
    $sample2 = $createSampleJpeg('rodax_test_2.jpg');
    $filesMock2 = [
        'name'     => 'corolla_traseira.jpg',
        'type'     => 'image/jpeg',
        'tmp_name' => $sample2,
        'error'    => UPLOAD_ERR_OK,
        'size'     => filesize($sample2)
    ];

    $uploadResult2 = $imageService->uploadVehicleImages($vehicleId, $sellerId, $filesMock2);
    $imgId2 = $uploadResult2['uploaded'][0]['id'];

    $stmtCheck2 = $pdo->prepare("SELECT is_main FROM vehicle_images WHERE id = :id");
    $stmtCheck2->execute(['id' => $imgId2]);
    $row2 = $stmtCheck2->fetch();

    if ((int)$row2['is_main'] === 0) {
        echo "OK (ID: {$imgId2}, is_main = 0 para foto secundária)\n";
    } else {
        throw new Exception("2ª foto não deveria ser marcada como principal.");
    }

    // 4. Teste de Alternância de Foto Principal (setMainImage)
    echo "[TESTE 3/5] Testando alternância de Foto Principal (setMainImage)... ";
    $imageService->setMainImage($imgId2, $vehicleId, $sellerId);

    $stmtCheck1 = $pdo->prepare("SELECT is_main FROM vehicle_images WHERE id = :id");
    $stmtCheck1->execute(['id' => $imgId1]);
    $stmtCheck2->execute(['id' => $imgId2]);

    if ((int)$stmtCheck1->fetchColumn() === 0 && (int)$stmtCheck2->fetchColumn() === 1) {
        echo "OK (Foto 2 promovida a Principal e Foto 1 desmarcada com sucesso)\n";
    } else {
        throw new Exception("Falha na alternância de foto principal.");
    }

    // 5. Teste de Bloqueio de Arquivos Maliciosos Disfarçados (Segurança MIME)
    echo "[TESTE 4/5] Testando Bloqueio de Arquivo Malicioso (.php disfarçado)... ";
    $fakeMaliciousFile = sys_get_temp_dir() . '/shell_fake.jpg';
    file_put_contents($fakeMaliciousFile, "<?php echo 'HACKED'; ?>");

    $filesMockBad = [
        'name'     => 'exploit.php.jpg',
        'type'     => 'image/jpeg',
        'tmp_name' => $fakeMaliciousFile,
        'error'    => UPLOAD_ERR_OK,
        'size'     => filesize($fakeMaliciousFile)
    ];

    $uploadResultBad = $imageService->uploadVehicleImages($vehicleId, $sellerId, $filesMockBad);
    @unlink($fakeMaliciousFile);

    if (!$uploadResultBad['success'] && !empty($uploadResultBad['errors'])) {
        echo "OK (Sucesso! O detector MIME barrou o script malicioso: '" . reset($uploadResultBad['errors']) . "')\n";
    } else {
        throw new Exception("FALHA CRÍTICA DE SEGURANÇA: Arquivo malicioso PHP foi aceito pelo sistema!");
    }

    // 6. Teste de Exclusão Física e Remoção de Registro (deleteImage)
    echo "[TESTE 5/5] Testando Exclusão de Imagem (Remoção do Banco + Unlink Físico)... ";
    $stmtPath = $pdo->prepare("SELECT image_path FROM vehicle_images WHERE id = :id");
    $stmtPath->execute(['id' => $imgId1]);
    $imgPath1 = $stmtPath->fetchColumn();

    $imageService->deleteImage($imgId1, $vehicleId, $sellerId);

    $fullPathOnDisk = __DIR__ . '/../public/' . $imgPath1;
    $stmtDbCheck = $pdo->prepare("SELECT COUNT(*) FROM vehicle_images WHERE id = :id");
    $stmtDbCheck->execute(['id' => $imgId1]);

    if (!file_exists($fullPathOnDisk) && (int)$stmtDbCheck->fetchColumn() === 0) {
        echo "OK (Registro removido do MySQL e arquivo apagado do disco com sucesso!)\n";
    } else {
        throw new Exception("Falha ao excluir a imagem do disco ou do banco.");
    }

    // Limpeza de dados do teste
    $imageService->deleteImage($imgId2, $vehicleId, $sellerId);
    $pdo->prepare("DELETE FROM users WHERE id = :id")->execute(['id' => $sellerId]);
    @unlink($sample1);
    @unlink($sample2);

    echo "\n=== TODOS OS TESTES DE UPLOAD E SEGURANÇA PASSARAM COM SUCESSO! ===\n";
    exit(0);

} catch (Exception $e) {
    echo "\n[ERRO NO TESTE DE UPLOAD]: " . $e->getMessage() . "\n";
    echo "Trace: " . $e->getTraceAsString() . "\n";
    exit(1);
}
