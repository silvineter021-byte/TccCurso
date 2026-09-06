<?php
$config = require 'C:/Rodax/config/database.php';
$dsn = "mysql:host={$config['host']};port={$config['port']};dbname={$config['dbname']};charset={$config['charset']}";
$pdo = new PDO($dsn, $config['username'], $config['password'], $config['options']);

echo "=== LIMPEZA DE DADOS DE TESTE E RE-SEEDING DO MARKETPLACE RODAX ===\n\n";

// Disable Foreign Key Checks for clean truncation / deletion
$pdo->exec("SET FOREIGN_KEY_CHECKS = 0");

// Limpar tabelas dependentes de veículos
$pdo->exec("TRUNCATE TABLE vehicle_images");
$pdo->exec("TRUNCATE TABLE vehicle_features");
$pdo->exec("TRUNCATE TABLE favorites");
$pdo->exec("TRUNCATE TABLE reports");
$pdo->exec("TRUNCATE TABLE messages");
$pdo->exec("TRUNCATE TABLE admin_logs");
$pdo->exec("TRUNCATE TABLE vehicles");

echo "✓ Tabelas de veículos, imagens, mensagens, denúncias e logs limpas com sucesso!\n";

// Deletar usuários temporários de teste (mantendo usuario@rodax.com, admin@rodax.com, marcielferreiravrg@gmail.com)
$pdo->exec("DELETE FROM users WHERE email NOT IN ('usuario@rodax.com', 'admin@rodax.com', 'marcielferreiravrg@gmail.com')");
echo "✓ Usuários temporários de teste removidos!\n";

// Garantir que os usuários principais existem
$stmtUser = $pdo->prepare("SELECT id FROM users WHERE email = 'usuario@rodax.com'");
$stmtUser->execute();
$userId = $stmtUser->fetchColumn();

if (!$userId) {
    $passHash = password_hash('senha123', PASSWORD_BCRYPT);
    $stmt = $pdo->prepare("INSERT INTO users (name, email, password_hash, phone, user_type, city, state, is_admin) VALUES ('Usuário RODAX', 'usuario@rodax.com', ?, '(11) 98888-7777', 'particular', 'São Paulo', 'SP', 0)");
    $stmt->execute([$passHash]);
    $userId = $pdo->lastInsertId();
}

$stmtAdmin = $pdo->prepare("SELECT id FROM users WHERE email = 'admin@rodax.com'");
$stmtAdmin->execute();
$adminId = $stmtAdmin->fetchColumn();

if (!$adminId) {
    $passHash = password_hash('admin123', PASSWORD_BCRYPT);
    $stmt = $pdo->prepare("INSERT INTO users (name, email, password_hash, phone, user_type, company_name, city, state, is_admin) VALUES ('Administrador RODAX', 'admin@rodax.com', ?, '(11) 99999-0000', 'loja', 'RODAX Motors', 'São Paulo', 'SP', 1)");
    $stmt->execute([$passHash]);
    $adminId = $pdo->lastInsertId();
}

// Re-enable Foreign Key Checks
$pdo->exec("SET FOREIGN_KEY_CHECKS = 1");

// Inserir veículos de demonstração reais e limpos
$sampleVehicles = [
    [
        'user_id' => $userId,
        'vehicle_type_id' => 1, // Carros
        'title' => 'Volkswagen Golf 1.4 TSI Highline 2017',
        'slug' => 'volkswagen-golf-14-tsi-highline-2017',
        'brand' => 'Volkswagen',
        'model' => 'Golf',
        'version' => '1.4 TSI Highline Automático',
        'year_manufacture' => 2016,
        'year_model' => 2017,
        'mileage' => 65000,
        'price' => 79900.00,
        'fipe_code' => '005374-0',
        'fipe_reference_price' => 82500.00,
        'color' => 'Branco',
        'fuel_type' => 'Flex',
        'engine' => '1.4 TSI Turbo',
        'power_hp' => 150,
        'transmission' => 'Automática',
        'steering' => 'Elétrica',
        'seller_type' => 'particular',
        'state' => 'SP',
        'city' => 'São Paulo',
        'description' => 'Golf Highline impecável, todas as revisões feitas na concessionária. Teto solar panorâmico, bancos em couro, rodas aro 17 e central multimídia original. Veículo de não fumante.',
        'status' => 'active',
        'body_style' => 'Hatch',
        'doors_count' => 4,
        'seats_count' => 5
    ],
    [
        'user_id' => $userId,
        'vehicle_type_id' => 1, // Carros
        'title' => 'Honda Civic 2.0 EXL Flex 2021',
        'slug' => 'honda-civic-20-exl-flex-2021',
        'brand' => 'Honda',
        'model' => 'Civic',
        'version' => '2.0 EXL 16V Flex 4P Automático CVT',
        'year_manufacture' => 2020,
        'year_model' => 2021,
        'mileage' => 42000,
        'price' => 115000.00,
        'fipe_code' => '004487-3',
        'fipe_reference_price' => 118000.00,
        'color' => 'Cinza',
        'fuel_type' => 'Flex',
        'engine' => '2.0 i-VTEC',
        'power_hp' => 155,
        'transmission' => 'CVT',
        'steering' => 'Elétrica',
        'seller_type' => 'particular',
        'state' => 'RJ',
        'city' => 'Rio de Janeiro',
        'description' => 'Honda Civic EXL em estado de zero km. Único dono, chave reserva, manual carimbado. Pneus novos, ar condicionado digital dual zone e faróis em LED.',
        'status' => 'active',
        'body_style' => 'Sedan',
        'doors_count' => 4,
        'seats_count' => 5
    ],
    [
        'user_id' => $adminId,
        'vehicle_type_id' => 2, // Motos
        'title' => 'Honda CB 500F Abs 2020',
        'slug' => 'honda-cb-500f-abs-2020',
        'brand' => 'Honda',
        'model' => 'CB 500F',
        'version' => '500cc ABS',
        'year_manufacture' => 2020,
        'year_model' => 2020,
        'mileage' => 18000,
        'price' => 32500.00,
        'fipe_code' => '811162-8',
        'fipe_reference_price' => 34000.00,
        'color' => 'Vermelho',
        'fuel_type' => 'Gasolina',
        'engine' => '471cc Bicilíndrico',
        'power_hp' => 50,
        'transmission' => 'Manual',
        'seller_type' => 'loja',
        'state' => 'PR',
        'city' => 'Curitiba',
        'description' => 'Moto extremamente nova e bem cuidada. Freios ABS, painel 100% digital, pneus em excelente estado. Pronta para rodar!',
        'status' => 'active',
        'engine_capacity_cc' => 500,
        'starter_type' => 'Elétrica',
        'motorcycle_category' => 'Naked',
        'has_abs' => 1
    ],
    [
        'user_id' => $adminId,
        'vehicle_type_id' => 3, // Caminhões
        'title' => 'Volvo FH 540 6x4 Globetrotter 2021',
        'slug' => 'volvo-fh-540-6x4-globetrotter-2021',
        'brand' => 'Volvo',
        'model' => 'FH 540',
        'version' => '6x4 Globetrotter Cabine Leito',
        'year_manufacture' => 2021,
        'year_model' => 2021,
        'mileage' => 280000,
        'price' => 680000.00,
        'color' => 'Prata',
        'fuel_type' => 'Diesel',
        'engine' => '13.0 D13C',
        'power_hp' => 540,
        'transmission' => 'Automática',
        'seller_type' => 'loja',
        'state' => 'SC',
        'city' => 'Joinville',
        'description' => 'Volvo FH 540 6x4 de segundo dono. Manutenção em dia, câmbio I-Shift, freio Retarder, ar condicionado de teto e defletor completo.',
        'status' => 'active',
        'traction' => '6x4',
        'cabin_type' => 'Leito',
        'load_capacity_kg' => 40000
    ],
    [
        'user_id' => $userId,
        'vehicle_type_id' => 4, // Vans
        'title' => 'Mercedes-Benz Sprinter 416 CDI Van 2022',
        'slug' => 'mercedes-benz-sprinter-416-cdi-van-2022',
        'brand' => 'Mercedes-Benz',
        'model' => 'Sprinter',
        'version' => '416 CDI Teto Alto Passageiro',
        'year_manufacture' => 2021,
        'year_model' => 2022,
        'mileage' => 55000,
        'price' => 210000.00,
        'color' => 'Preto',
        'fuel_type' => 'Diesel',
        'engine' => '2.2 Turbodiesel',
        'power_hp' => 163,
        'transmission' => 'Manual',
        'seller_type' => 'particular',
        'state' => 'SP',
        'city' => 'Guarulhos',
        'description' => 'Sprinter 15+1 lugares em estado impecável. Bancos reclináveis, ar condicionado duto central, piloto automático e assistente de vento lateral.',
        'status' => 'active',
        'seats_count' => 16,
        'body_style' => 'Minivan'
    ],
    [
        'user_id' => $adminId,
        'vehicle_type_id' => 6, // Implementos
        'title' => 'Semirreboque Sider Randon 3 Eixos 2021',
        'slug' => 'semirreboque-sider-randon-3-eixos-2021',
        'brand' => 'Randon',
        'model' => 'Sider 3 Eixos',
        'version' => '14.5m 28 Paletes',
        'year_manufacture' => 2021,
        'year_model' => 2021,
        'mileage' => 120000,
        'price' => 145000.00,
        'color' => 'Vermelho',
        'fuel_type' => 'Outros',
        'seller_type' => 'loja',
        'state' => 'PR',
        'city' => 'Londrina',
        'description' => 'Carreta Sider Randon 14,50m de comprimento para 28 paletes. Assoalho de chapa xadrez, suspensão pneumática no 1º eixo, lonas em ótimo estado.',
        'status' => 'active',
        'implement_type' => 'Sider',
        'implement_manufacturer' => 'Randon',
        'implement_length_m' => 14.5
    ]
];

$sql = "INSERT INTO vehicles (
    user_id, vehicle_type_id, title, slug, brand, model, version,
    year_manufacture, year_model, mileage, price, fipe_code, fipe_reference_price,
    color, fuel_type, engine, power_hp, transmission, steering, seller_type,
    state, city, description, status, body_style, doors_count, seats_count,
    engine_capacity_cc, starter_type, motorcycle_category, has_abs, traction,
    cabin_type, load_capacity_kg, implement_type, implement_manufacturer, implement_length_m
) VALUES (
    :user_id, :vehicle_type_id, :title, :slug, :brand, :model, :version,
    :year_manufacture, :year_model, :mileage, :price, :fipe_code, :fipe_reference_price,
    :color, :fuel_type, :engine, :power_hp, :transmission, :steering, :seller_type,
    :state, :city, :description, :status, :body_style, :doors_count, :seats_count,
    :engine_capacity_cc, :starter_type, :motorcycle_category, :has_abs, :traction,
    :cabin_type, :load_capacity_kg, :implement_type, :implement_manufacturer, :implement_length_m
)";

$stmt = $pdo->prepare($sql);

foreach ($sampleVehicles as $v) {
    $stmt->execute([
        ':user_id' => $v['user_id'],
        ':vehicle_type_id' => $v['vehicle_type_id'],
        ':title' => $v['title'],
        ':slug' => $v['slug'],
        ':brand' => $v['brand'],
        ':model' => $v['model'],
        ':version' => $v['version'] ?? null,
        ':year_manufacture' => $v['year_manufacture'],
        ':year_model' => $v['year_model'],
        ':mileage' => $v['mileage'],
        ':price' => $v['price'],
        ':fipe_code' => $v['fipe_code'] ?? null,
        ':fipe_reference_price' => $v['fipe_reference_price'] ?? null,
        ':color' => $v['color'],
        ':fuel_type' => $v['fuel_type'],
        ':engine' => $v['engine'] ?? null,
        ':power_hp' => $v['power_hp'] ?? null,
        ':transmission' => $v['transmission'] ?? null,
        ':steering' => $v['steering'] ?? null,
        ':seller_type' => $v['seller_type'],
        ':state' => $v['state'],
        ':city' => $v['city'],
        ':description' => $v['description'],
        ':status' => $v['status'],
        ':body_style' => $v['body_style'] ?? null,
        ':doors_count' => $v['doors_count'] ?? null,
        ':seats_count' => $v['seats_count'] ?? null,
        ':engine_capacity_cc' => $v['engine_capacity_cc'] ?? null,
        ':starter_type' => $v['starter_type'] ?? null,
        ':motorcycle_category' => $v['motorcycle_category'] ?? null,
        ':has_abs' => $v['has_abs'] ?? 0,
        ':traction' => $v['traction'] ?? null,
        ':cabin_type' => $v['cabin_type'] ?? null,
        ':load_capacity_kg' => $v['load_capacity_kg'] ?? null,
        ':implement_type' => $v['implement_type'] ?? null,
        ':implement_manufacturer' => $v['implement_manufacturer'] ?? null,
        ':implement_length_m' => $v['implement_length_m'] ?? null
    ]);
    echo "✓ Veículo de demonstração cadastrado: {$v['title']}\n";
}

echo "\n=== LIMPEZA E RE-SEEDING CONCLUÍDOS COM SUCESSO! ===\n";
