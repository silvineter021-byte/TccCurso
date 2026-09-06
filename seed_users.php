<?php

require_once __DIR__ . '/../app/Helpers/functions.php';
require_once __DIR__ . '/../app/Helpers/Autoloader.php';

use App\Models\User;

$userModel = new User();

// Criar ou atualizar usuário comum de teste
$user = $userModel->findByEmail('usuario@rodax.com');
if (!$user) {
    $userModel->create([
        'name'      => 'Usuário Teste RODAX',
        'email'     => 'usuario@rodax.com',
        'password'  => 'senha123',
        'phone'     => '(11) 98888-7777',
        'user_type' => 'particular',
        'city'      => 'São Paulo',
        'state'     => 'SP'
    ]);
    echo "Usuário comum criado: usuario@rodax.com / senha123\n";
} else {
    echo "Usuário comum já existe: usuario@rodax.com\n";
}

// Criar ou atualizar administrador de teste
$admin = $userModel->findByEmail('admin@rodax.com');
if (!$admin) {
    $userModel->create([
        'name'      => 'Administrador RODAX',
        'email'     => 'admin@rodax.com',
        'password'  => 'admin123',
        'phone'     => '(11) 99999-0000',
        'user_type' => 'loja',
        'is_admin'  => 1
    ]);
    echo "Administrador criado: admin@rodax.com / admin123\n";
} else {
    echo "Administrador já existe: admin@rodax.com\n";
}
