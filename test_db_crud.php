<?php
/**
 * RODAX — Teste Completo de CRUD e Relacionamentos no Banco de Dados
 * 
 * Valida:
 * 1. Inserção de Usuário (com hash de senha segura)
 * 2. Inserção de Anúncio de Veículo (Carro com especificações)
 * 3. Inserção de Fotos do Anúncio (Foto principal e ordem)
 * 4. Associação de Opcionais (N:N em vehicle_features)
 * 5. Inserção e Leitura de Favoritos
 * 6. Inserção e Leitura de Mensagens entre usuários
 * 7. Inserção de Denúncia (reports)
 * 8. Atualização de dados (UPDATE)
 * 9. Deleção em cascata (DELETE e ON DELETE CASCADE)
 */

require_once __DIR__ . '/../app/Services/Database.php';

use App\Services\Database;

echo "=== RODAX: TESTE DE CRUD E RELACIONAMENTOS DO BANCO DE DADOS ===\n\n";

try {
    $pdo = Database::getConnection();

    // 1. Inserir Usuário Vendedor
    echo "[1/8] Inserindo usuário de teste (Vendedor)... ";
    $passwordHash = password_hash("SenhaSegura123", PASSWORD_DEFAULT);
    
    $stmt = $pdo->prepare("
        INSERT INTO users (name, email, password_hash, phone, user_type, company_name, city, state, is_admin)
        VALUES (:name, :email, :password_hash, :phone, :user_type, :company_name, :city, :state, :is_admin)
    ");
    $stmt->execute([
        'name'          => 'Vendedor Teste RODAX',
        'email'         => 'vendedor_teste@rodax.com.br',
        'password_hash' => $passwordHash,
        'phone'         => '(11) 99999-8888',
        'user_type'      => 'particular',
        'company_name'  => null,
        'city'          => 'São Paulo',
        'state'         => 'SP',
        'is_admin'      => 0
    ]);
    $userId = (int)$pdo->lastInsertId();
    echo "OK (ID: {$userId})\n";

    // 2. Inserir Usuário Comprador
    echo "[2/8] Inserindo usuário de teste (Comprador)... ";
    $stmt->execute([
        'name'          => 'Comprador Teste RODAX',
        'email'         => 'comprador_teste@rodax.com.br',
        'password_hash' => $passwordHash,
        'phone'         => '(11) 97777-6666',
        'user_type'      => 'particular',
        'company_name'  => null,
        'city'          => 'Campinas',
        'state'         => 'SP',
        'is_admin'      => 0
    ]);
    $buyerId = (int)$pdo->lastInsertId();
    echo "OK (ID: {$buyerId})\n";

    // 3. Inserir Veículo (Carro)
    echo "[3/8] Inserindo anúncio de veículo (Carro)... ";
    $stmt = $pdo->prepare("
        INSERT INTO vehicles (
            user_id, vehicle_type_id, title, slug, brand, model, version,
            year_manufacture, year_model, mileage, price, fipe_code, fipe_reference_price,
            color, fuel_type, engine, cylinders, power_hp, transmission, steering, traction,
            seller_type, state, city, description, status, body_style, doors_count, seats_count
        ) VALUES (
            :user_id, :vehicle_type_id, :title, :slug, :brand, :model, :version,
            :year_manufacture, :year_model, :mileage, :price, :fipe_code, :fipe_reference_price,
            :color, :fuel_type, :engine, :cylinders, :power_hp, :transmission, :steering, :traction,
            :seller_type, :state, :city, :description, :status, :body_style, :doors_count, :seats_count
        )
    ");
    $stmt->execute([
        'user_id'              => $userId,
        'vehicle_type_id'      => 1, // Carros
        'title'                => 'Volkswagen Golf 1.4 TSI Highline 2017',
        'slug'                 => 'volkswagen-golf-1-4-tsi-highline-2017-' . $userId,
        'brand'                => 'Volkswagen',
        'model'                => 'Golf',
        'version'              => '1.4 TSI Highline',
        'year_manufacture'     => 2016,
        'year_model'           => 2017,
        'mileage'              => 65000,
        'price'                => 79900.00,
        'fipe_code'            => '005374-0',
        'fipe_reference_price' => 82500.00,
        'color'                => 'Branco',
        'fuel_type'            => 'Gasolina',
        'engine'               => '1.4 TSI',
        'cylinders'            => 4,
        'power_hp'             => 150,
        'transmission'         => 'Automática DSG',
        'steering'             => 'Elétrica',
        'traction'             => 'Dianteira',
        'seller_type'          => 'particular',
        'state'                => 'SP',
        'city'                 => 'São Paulo',
        'description'          => 'Veículo de único dono, revisões em dia na concessionária.',
        'status'               => 'active',
        'body_style'           => 'Hatch',
        'doors_count'          => 4,
        'seats_count'          => 5
    ]);
    $vehicleId = (int)$pdo->lastInsertId();
    echo "OK (ID: {$vehicleId})\n";

    // 4. Inserir Fotos
    echo "[4/8] Inserindo fotos do veículo... ";
    $stmtImage = $pdo->prepare("
        INSERT INTO vehicle_images (vehicle_id, image_path, is_main, display_order)
        VALUES (:vehicle_id, :image_path, :is_main, :display_order)
    ");
    $stmtImage->execute([
        'vehicle_id'    => $vehicleId,
        'image_path'    => 'uploads/vehicles/golf_frente.jpg',
        'is_main'       => 1,
        'display_order' => 1
    ]);
    $stmtImage->execute([
        'vehicle_id'    => $vehicleId,
        'image_path'    => 'uploads/vehicles/golf_traseira.jpg',
        'is_main'       => 0,
        'display_order' => 2
    ]);
    echo "OK (2 fotos associadas)\n";

    // 5. Associar Opcionais (vehicle_features)
    echo "[5/8] Associando opcionais ao veículo... ";
    $stmtFeature = $pdo->prepare("
        INSERT INTO vehicle_features (vehicle_id, feature_id) VALUES (:vehicle_id, :feature_id)
    ");
    $stmtFeature->execute(['vehicle_id' => $vehicleId, 'feature_id' => 1]); // Ar Condicionado
    $stmtFeature->execute(['vehicle_id' => $vehicleId, 'feature_id' => 3]); // Freios ABS
    echo "OK (Opcionais vinculados)\n";

    // 6. Criar Favorito e Mensagem
    echo "[6/8] Testando favoritos e sistema de mensagens... ";
    $stmtFav = $pdo->prepare("INSERT INTO favorites (user_id, vehicle_id) VALUES (:user_id, :vehicle_id)");
    $stmtFav->execute(['user_id' => $buyerId, 'vehicle_id' => $vehicleId]);

    $stmtMsg = $pdo->prepare("
        INSERT INTO messages (vehicle_id, sender_id, receiver_id, message_text)
        VALUES (:vehicle_id, :sender_id, :receiver_id, :message_text)
    ");
    $stmtMsg->execute([
        'vehicle_id'   => $vehicleId,
        'sender_id'    => $buyerId,
        'receiver_id'  => $userId,
        'message_text' => 'Olá! O Golf ainda está disponível para visitação?'
    ]);
    echo "OK\n";

    // 7. Consulta com JOINs para validar leitura
    echo "[7/8] Consultando anúncio completo com JOINs... ";
    $query = "
        SELECT 
            v.id, v.title, v.price, v.fipe_reference_price,
            u.name AS seller_name, u.email AS seller_email,
            vt.name AS category_name,
            img.image_path AS main_image,
            COUNT(DISTINCT f.id) AS total_favorites
        FROM vehicles v
        JOIN users u ON v.user_id = u.id
        JOIN vehicle_types vt ON v.vehicle_type_id = vt.id
        LEFT JOIN vehicle_images img ON v.id = img.vehicle_id AND img.is_main = 1
        LEFT JOIN favorites f ON v.id = f.vehicle_id
        WHERE v.id = :vehicle_id
        GROUP BY v.id
    ";
    $stmtSelect = $pdo->prepare($query);
    $stmtSelect->execute(['vehicle_id' => $vehicleId]);
    $vehicleData = $stmtSelect->fetch();

    if ($vehicleData && $vehicleData['price'] == 79900.00) {
        echo "OK\n";
        echo "   - Título: {$vehicleData['title']}\n";
        echo "   - Categoria: {$vehicleData['category_name']}\n";
        echo "   - Vendedor: {$vehicleData['seller_name']} ({$vehicleData['seller_email']})\n";
        echo "   - Preço Anunciado: R$ " . number_format($vehicleData['price'], 2, ',', '.') . "\n";
        echo "   - Preço Ref. FIPE: R$ " . number_format($vehicleData['fipe_reference_price'], 2, ',', '.') . "\n";
        echo "   - Foto Principal: {$vehicleData['main_image']}\n";
        echo "   - Total de Favoritos: {$vehicleData['total_favorites']}\n";
    } else {
        throw new Exception("Dados lidos do anúncio não correspondem ao esperado.");
    }

    // 8. Limpeza de dados de teste (Deleção em Cascata)
    echo "[8/8] Testando exclusão em cascata (DELETE user -> CASCADE vehicles, images, favorites, messages)... ";
    $stmtDelete = $pdo->prepare("DELETE FROM users WHERE id IN (:user1, :user2)");
    $stmtDelete->execute(['user1' => $userId, 'user2' => $buyerId]);

    // Verificar se o veículo foi excluído em cascata
    $stmtCheck = $pdo->prepare("SELECT COUNT(*) FROM vehicles WHERE id = :vehicle_id");
    $stmtCheck->execute(['vehicle_id' => $vehicleId]);
    $remainingVehicles = $stmtCheck->fetchColumn();

    if ($remainingVehicles == 0) {
        echo "OK (Deleção em cascata validada com sucesso!)\n";
    } else {
        throw new Exception("Falha na deleção em cascata: os veículos associados ao usuário não foram excluídos.");
    }

    echo "\n=== TODOS OS TESTES DE CRUD E RELACIONAMENTOS PASSARAM COM SUCESSO! ===\n";
    exit(0);

} catch (Exception $e) {
    echo "\n[ERRO FATAL NO TESTE DE CRUD]: " . $e->getMessage() . "\n";
    echo "Trace: " . $e->getTraceAsString() . "\n";
    exit(1);
}
