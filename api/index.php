<?php
require __DIR__ . '/bootstrap.php';

// Utilities
$pdo = db();

function find_user_by_email(string $email)
{
    global $pdo;
    $stmt = $pdo->prepare('SELECT * FROM users WHERE email = ? LIMIT 1');
    $stmt->execute([$email]);
    return $stmt->fetch();
}

function find_user_by_id(int $id)
{
    global $pdo;
    $stmt = $pdo->prepare('SELECT * FROM users WHERE id = ? LIMIT 1');
    $stmt->execute([$id]);
    return $stmt->fetch();
}

route('POST', '/login', function () {
    $data = parse_json() ?: $_POST;
    $values = require_fields(['email', 'password'], $data);
    $user = find_user_by_email($values['email']);
    if (!$user || !password_verify($values['password'], $user['senha_hash'])) {
        json_response(['error' => 'Credenciais inválidas'], 401);
    }
    unset($user['senha_hash']);
    $_SESSION['user'] = $user;
    // cookies auxiliares de auth para contornar perda de sessão
    setcookie('CELULAR_UID', $user['id'], [
        'path' => '/',
        'httponly' => false,
        'samesite' => 'Lax',
    ]);
    setcookie('CELULAR_ROLE', $user['tipo'], [
        'path' => '/',
        'httponly' => false,
        'samesite' => 'Lax',
    ]);
    json_response(['user' => $user]);
});

route('POST', '/logout', function () {
    session_destroy();
    json_response(['message' => 'Logout efetuado']);
});

route('POST', '/register', function () {
    global $pdo;
    $data = parse_json() ?: $_POST;
    $values = require_fields(['nome', 'email', 'password', 'telefone'], $data);
    if (find_user_by_email($values['email'])) {
        json_response(['error' => 'E-mail já cadastrado'], 409);
    }
    $stmt = $pdo->prepare('INSERT INTO users (nome, email, senha_hash, telefone, tipo, company_id, created_at) VALUES (?, ?, ?, ?, ?, ?, NOW())');
    $stmt->execute([
        $values['nome'],
        $values['email'],
        password_hash($values['password'], PASSWORD_DEFAULT),
        $values['telefone'],
        'cliente',
        $data['company_id'] ?? null,
    ]);
    $id = $pdo->lastInsertId();
    json_response(['message' => 'Cadastro realizado', 'user_id' => $id], 201);
});

route('GET', '/me', function () {
    $user = require_auth();
    json_response(['user' => $user]);
});

// Users (admin)
route('GET', '/users', function () {
    require_auth('admin');
    global $pdo;
    $type = $_GET['tipo'] ?? null;
    if ($type) {
        $stmt = $pdo->prepare('SELECT u.id, u.nome, u.email, u.telefone, u.tipo, u.created_at, c.nome AS company FROM users u LEFT JOIN companies c ON c.id = u.company_id WHERE u.tipo = ? ORDER BY u.nome');
        $stmt->execute([$type]);
    } else {
        $stmt = $pdo->query('SELECT u.id, u.nome, u.email, u.telefone, u.tipo, u.created_at, c.nome AS company FROM users u LEFT JOIN companies c ON c.id = u.company_id ORDER BY u.nome');
    }
    json_response(['users' => $stmt->fetchAll()]);
});

// Users (create by admin)
route('POST', '/users', function () {
    require_auth('admin');
    global $pdo;
    $data = parse_json() ?: $_POST;
    $fields = require_fields(['nome','email','password','tipo'], $data);
    if (find_user_by_email($fields['email'])) {
        json_response(['error' => 'E-mail já cadastrado'], 409);
    }
    $stmt = $pdo->prepare('INSERT INTO users (nome, email, senha_hash, telefone, tipo, company_id, created_at) VALUES (?, ?, ?, ?, ?, ?, NOW())');
    $stmt->execute([
        $fields['nome'],
        $fields['email'],
        password_hash($fields['password'], PASSWORD_DEFAULT),
        $data['telefone'] ?? null,
        $fields['tipo'],
        $data['company_id'] ?? null,
    ]);
    json_response(['message' => 'Usuário criado']);
});

// Companies
route('GET', '/companies', function () {
    require_auth('admin');
    global $pdo;
    $stmt = $pdo->query('SELECT * FROM companies ORDER BY nome');
    json_response(['companies' => $stmt->fetchAll()]);
});

route('POST', '/companies', function () {
    require_auth('admin');
    global $pdo;
    $data = parse_json() ?: $_POST;
    $fields = require_fields(['nome'], $data);
    $stmt = $pdo->prepare('INSERT INTO companies (nome, cnpj) VALUES (?, ?)');
    $stmt->execute([$fields['nome'], $data['cnpj'] ?? null]);
    json_response(['message' => 'Empresa criada', 'company_id' => $pdo->lastInsertId()]);
});

// Hoses CRUD
route('GET', '/hoses', function () {
    global $pdo;
    $user = require_auth();
    if ($user['tipo'] === 'admin') {
        $stmt = $pdo->query('SELECT * FROM hoses ORDER BY created_at DESC');
    } else {
        $stmt = $pdo->prepare(
            'SELECT h.* FROM hoses h 
             JOIN rentals r ON r.item_type = "hose" AND r.item_id = h.id
             WHERE r.user_id = ? AND r.status IN ("alugado","atrasado","em uso")'
        );
        $stmt->execute([$user['id']]);
    }
    json_response(['hoses' => $stmt->fetchAll()]);
});

route('POST', '/hoses', function () {
    require_auth('admin');
    global $pdo;
    $data = $_POST + parse_json();
    $fields = require_fields(['serial', 'tipo', 'bitola', 'pressao', 'comprimento', 'marca', 'data_compra', 'status'], $data);
    $foto = save_upload('foto') ?? ($data['foto'] ?? null);
    $stmt = $pdo->prepare(
        'INSERT INTO hoses (serial, tipo, bitola, pressao, comprimento, marca, data_compra, foto, status, observacoes, created_at) 
         VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, NOW())'
    );
    $stmt->execute([
        $fields['serial'],
        $fields['tipo'],
        $fields['bitola'],
        $fields['pressao'],
        $fields['comprimento'],
        $fields['marca'],
        $fields['data_compra'],
        $foto,
        $fields['status'],
        $data['observacoes'] ?? null,
    ]);
    json_response(['message' => 'Mangueira cadastrada']);
});

route('PUT', '/hoses/{id}', function ($params) {
    require_auth('admin');
    global $pdo;
    parse_str(file_get_contents('php://input'), $body);
    $data = parse_json() ?: $body;
    $id = (int) $params['id'];
    $allowed = ['tipo','bitola','pressao','comprimento','marca','data_compra','status','observacoes'];
    $updates = [];
    $values = [];
    foreach ($allowed as $field) {
        if (isset($data[$field])) {
            $updates[] = "$field = ?";
            $values[] = $data[$field];
        }
    }
    if (isset($_FILES['foto']) && $_FILES['foto']['error'] === UPLOAD_ERR_OK) {
        $foto = save_upload('foto');
        $updates[] = 'foto = ?';
        $values[] = $foto;
    }
    if (!$updates) {
        json_response(['error' => 'Nada para atualizar'], 422);
    }
    $values[] = $id;
    $sql = 'UPDATE hoses SET ' . implode(',', $updates) . ' WHERE id = ?';
    $stmt = $pdo->prepare($sql);
    $stmt->execute($values);
    json_response(['message' => 'Mangueira atualizada']);
});

// Parts CRUD
route('GET', '/parts', function () {
    global $pdo;
    $user = require_auth();
    if ($user['tipo'] === 'admin') {
        $stmt = $pdo->query('SELECT * FROM parts ORDER BY created_at DESC');
    } else {
        $stmt = $pdo->prepare(
            'SELECT p.* FROM parts p 
             JOIN rentals r ON r.item_type = "part" AND r.item_id = p.id
             WHERE r.user_id = ? AND r.status IN ("alugado","atrasado","em uso")'
        );
        $stmt->execute([$user['id']]);
    }
    json_response(['parts' => $stmt->fetchAll()]);
});

route('POST', '/parts', function () {
    require_auth('admin');
    global $pdo;
    $data = $_POST + parse_json();
    $fields = require_fields(['serial', 'tipo', 'aplicacao', 'marca', 'data_compra', 'status'], $data);
    $foto = save_upload('foto') ?? ($data['foto'] ?? null);
    $stmt = $pdo->prepare(
        'INSERT INTO parts (serial, tipo, aplicacao, marca, data_compra, foto, status, observacoes, created_at) 
         VALUES (?, ?, ?, ?, ?, ?, ?, ?, NOW())'
    );
    $stmt->execute([
        $fields['serial'],
        $fields['tipo'],
        $fields['aplicacao'],
        $fields['marca'],
        $fields['data_compra'],
        $foto,
        $fields['status'],
        $data['observacoes'] ?? null,
    ]);
    json_response(['message' => 'Peça cadastrada']);
});

route('PUT', '/parts/{id}', function ($params) {
    require_auth('admin');
    global $pdo;
    parse_str(file_get_contents('php://input'), $body);
    $data = parse_json() ?: $body;
    $id = (int) $params['id'];
    $allowed = ['tipo','aplicacao','marca','data_compra','status','observacoes'];
    $updates = [];
    $values = [];
    foreach ($allowed as $field) {
        if (isset($data[$field])) {
            $updates[] = "$field = ?";
            $values[] = $data[$field];
        }
    }
    if (isset($_FILES['foto']) && $_FILES['foto']['error'] === UPLOAD_ERR_OK) {
        $foto = save_upload('foto');
        $updates[] = 'foto = ?';
        $values[] = $foto;
    }
    if (!$updates) {
        json_response(['error' => 'Nada para atualizar'], 422);
    }
    $values[] = $id;
    $sql = 'UPDATE parts SET ' . implode(',', $updates) . ' WHERE id = ?';
    $stmt = $pdo->prepare($sql);
    $stmt->execute($values);
    json_response(['message' => 'Peça atualizada']);
});

// Rentals
route('GET', '/rentals', function () {
    global $pdo;
    $user = require_auth();
    if ($user['tipo'] === 'admin') {
        $stmt = $pdo->query(
            'SELECT r.*, u.nome as cliente, 
                CASE WHEN r.item_type="hose" THEN h.serial ELSE p.serial END AS serial,
                CASE WHEN r.item_type="hose" THEN h.tipo ELSE p.tipo END AS item_tipo
             FROM rentals r
             LEFT JOIN users u ON u.id = r.user_id
             LEFT JOIN hoses h ON h.id = r.item_id AND r.item_type="hose"
             LEFT JOIN parts p ON p.id = r.item_id AND r.item_type="part"
             ORDER BY r.data_saida DESC'
        );
    } else {
        $stmt = $pdo->prepare(
            'SELECT r.*, 
                CASE WHEN r.item_type="hose" THEN h.serial ELSE p.serial END AS serial,
                CASE WHEN r.item_type="hose" THEN h.tipo ELSE p.tipo END AS item_tipo
             FROM rentals r
             LEFT JOIN hoses h ON h.id = r.item_id AND r.item_type="hose"
             LEFT JOIN parts p ON p.id = r.item_id AND r.item_type="part"
             WHERE r.user_id = ?
             ORDER BY r.data_saida DESC'
        );
        $stmt->execute([$user['id']]);
    }
    json_response(['rentals' => $stmt->fetchAll()]);
});

route('POST', '/rentals', function () {
    require_auth('admin');
    global $pdo;
    $data = parse_json() ?: $_POST;
    $fields = require_fields(['user_id','item_type','item_id','data_saida','previsao_retorno','condicao_entrega'], $data);
    $stmt = $pdo->prepare(
        'INSERT INTO rentals (user_id, item_type, item_id, data_saida, previsao_retorno, status, condicao_entrega, observacoes, created_at) 
         VALUES (?, ?, ?, ?, ?, ?, ?, ?, NOW())'
    );
    $stmt->execute([
        $fields['user_id'],
        $fields['item_type'],
        $fields['item_id'],
        $fields['data_saida'],
        $fields['previsao_retorno'],
        $data['status'] ?? 'alugado',
        $fields['condicao_entrega'],
        $data['observacoes'] ?? null,
    ]);
    json_response(['message' => 'Aluguel registrado']);
});

route('PUT', '/rentals/{id}', function ($params) {
    require_auth('admin');
    global $pdo;
    parse_str(file_get_contents('php://input'), $body);
    $data = parse_json() ?: $body;
    $id = (int) $params['id'];
    $allowed = ['data_retorno','status','previsao_retorno','condicao_entrega','observacoes'];
    $updates = [];
    $values = [];
    foreach ($allowed as $field) {
        if (isset($data[$field])) {
            $updates[] = "$field = ?";
            $values[] = $data[$field];
        }
    }
    if (!$updates) {
        json_response(['error' => 'Nada para atualizar'], 422);
    }
    $values[] = $id;
    $sql = 'UPDATE rentals SET ' . implode(',', $updates) . ' WHERE id = ?';
    $stmt = $pdo->prepare($sql);
    $stmt->execute($values);
    json_response(['message' => 'Aluguel atualizado']);
});

// Maintenance
route('GET', '/maintenance', function () {
    global $pdo;
    $user = require_auth();
    if ($user['tipo'] === 'admin') {
        $stmt = $pdo->query('SELECT * FROM maintenance ORDER BY data_abertura DESC');
    } else {
        $stmt = $pdo->prepare(
            'SELECT m.* FROM maintenance m
             JOIN rentals r ON r.item_id = m.item_id AND r.item_type = m.item_type
             WHERE r.user_id = ?'
        );
        $stmt->execute([$user['id']]);
    }
    json_response(['maintenance' => $stmt->fetchAll()]);
});

route('POST', '/maintenance', function () {
    require_auth('admin');
    global $pdo;
    $data = parse_json() ?: $_POST;
    $fields = require_fields(['item_type','item_id','data_prevista','motivo','status'], $data);
    $fotos = $data['fotos'] ?? null;
    $stmt = $pdo->prepare(
        'INSERT INTO maintenance (item_type, item_id, data_abertura, data_prevista, motivo, checklist, custo, status, fotos, observacoes) 
         VALUES (?, ?, NOW(), ?, ?, ?, ?, ?, ?, ?)'
    );
    $stmt->execute([
        $fields['item_type'],
        $fields['item_id'],
        $fields['data_prevista'],
        $fields['motivo'],
        $data['checklist'] ?? null,
        $data['custo'] ?? 0,
        $fields['status'],
        $fotos,
        $data['observacoes'] ?? null,
    ]);
    json_response(['message' => 'Manutenção criada']);
});

route('PUT', '/maintenance/{id}', function ($params) {
    require_auth('admin');
    global $pdo;
    parse_str(file_get_contents('php://input'), $body);
    $data = parse_json() ?: $body;
    $id = (int) $params['id'];
    $allowed = ['data_prevista','data_conclusao','motivo','checklist','custo','status','fotos','observacoes'];
    $updates = [];
    $values = [];
    foreach ($allowed as $field) {
        if (isset($data[$field])) {
            $updates[] = "$field = ?";
            $values[] = $data[$field];
        }
    }
    if (!$updates) {
        json_response(['error' => 'Nada para atualizar'], 422);
    }
    $values[] = $id;
    $sql = 'UPDATE maintenance SET ' . implode(',', $updates) . ' WHERE id = ?';
    $stmt = $pdo->prepare($sql);
    $stmt->execute($values);
    json_response(['message' => 'Manutenção atualizada']);
});

// Tickets
route('GET', '/tickets', function () {
    global $pdo;
    $user = require_auth();
    if ($user['tipo'] === 'admin') {
        $stmt = $pdo->query('SELECT * FROM tickets ORDER BY created_at DESC');
    } else {
        $stmt = $pdo->prepare('SELECT * FROM tickets WHERE user_id = ? ORDER BY created_at DESC');
        $stmt->execute([$user['id']]);
    }
    json_response(['tickets' => $stmt->fetchAll()]);
});

route('POST', '/tickets', function () {
    $user = require_auth();
    global $pdo;
    $data = $_POST + parse_json();
    $fields = require_fields(['item_type','descricao','urgencia'], $data);
    $itemId = isset($data['item_id']) && $data['item_id'] !== '' ? (int)$data['item_id'] : 0;
    $foto = save_upload('foto') ?? ($data['foto'] ?? null);
    $stmt = $pdo->prepare(
        'INSERT INTO tickets (user_id, item_type, item_id, descricao, foto, urgencia, status, created_at) 
         VALUES (?, ?, ?, ?, ?, ?, ?, NOW())'
    );
    $stmt->execute([
        $user['id'],
        $fields['item_type'],
        $itemId,
        $fields['descricao'],
        $foto,
        $fields['urgencia'],
        'aberto',
    ]);
    json_response(['message' => 'Chamado aberto']);
});

route('PUT', '/tickets/{id}', function ($params) {
    require_auth('admin');
    global $pdo;
    parse_str(file_get_contents('php://input'), $body);
    $data = parse_json() ?: $body;
    $id = (int) $params['id'];
    $allowed = ['status','descricao'];
    $updates = [];
    $values = [];
    foreach ($allowed as $field) {
        if (isset($data[$field])) {
            $updates[] = "$field = ?";
            $values[] = $data[$field];
        }
    }
    if (!$updates) {
        json_response(['error' => 'Nada para atualizar'], 422);
    }
    $values[] = $id;
    $sql = 'UPDATE tickets SET ' . implode(',', $updates) . ' WHERE id = ?';
    $stmt = $pdo->prepare($sql);
    $stmt->execute($values);
    json_response(['message' => 'Chamado atualizado']);
});

// Default response for unknown route
json_response(['error' => 'Endpoint não encontrado'], 404);
