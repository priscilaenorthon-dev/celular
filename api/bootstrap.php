<?php
// Shared bootstrap for API endpoints
$config = require __DIR__ . '/../config/config.php';

// Sessão com nome fixo e caminho raiz para funcionar em /public e /api
session_name('CELULARSESS');
session_set_cookie_params([
    'path' => '/',
    'httponly' => true,
    'samesite' => 'Lax',
]);
session_start();

function cors_headers(): string
{
    $origin = $_SERVER['HTTP_ORIGIN'] ?? ($_SERVER['HTTP_HOST'] ?? '*');
    $allowOrigin = $origin ? (str_starts_with($origin, 'http') ? $origin : 'http://' . $origin) : '*';
    header('Access-Control-Allow-Origin: ' . $allowOrigin);
    header('Access-Control-Allow-Credentials: true');
    return $allowOrigin;
}

function db(): PDO
{
    static $pdo = null;
    global $config;
    if ($pdo === null) {
        $dsn = sprintf(
            'mysql:host=%s;dbname=%s;charset=%s',
            $config['db']['host'],
            $config['db']['name'],
            $config['db']['charset']
        );
        $pdo = new PDO($dsn, $config['db']['user'], $config['db']['pass'], [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        ]);
    }
    return $pdo;
}

function json_response($data, int $status = 200): void
{
    cors_headers();
    http_response_code($status);
    header('Content-Type: application/json');
    echo json_encode($data);
    exit;
}

function require_fields(array $fields, array $source): array
{
    $missing = [];
    $values = [];
    foreach ($fields as $field) {
        if (!isset($source[$field]) || $source[$field] === '') {
            $missing[] = $field;
        } else {
            $values[$field] = is_string($source[$field]) ? trim($source[$field]) : $source[$field];
        }
    }
    if ($missing) {
        json_response(['error' => 'Campos obrigatórios faltando', 'fields' => $missing], 422);
    }
    return $values;
}

function current_user(): ?array
{
    if (isset($_SESSION['user'])) {
        return $_SESSION['user'];
    }
    // fallback via cookies se a sessão se perder
    if (!empty($_COOKIE['CELULAR_UID'])) {
        $id = (int) $_COOKIE['CELULAR_UID'];
        $user = find_user_by_id($id);
        if ($user) {
            unset($user['senha_hash']);
            $_SESSION['user'] = $user;
            return $user;
        }
    }
    return null;
}

function require_auth(string $role = null): array
{
    $user = current_user();
    if (!$user) {
        json_response(['error' => 'Não autenticado'], 401);
    }
    if ($role && $user['tipo'] !== $role) {
        json_response(['error' => 'Permissão negada'], 403);
    }
    return $user;
}

function save_upload(string $field): ?string
{
    global $config;
    if (empty($_FILES[$field]) || $_FILES[$field]['error'] !== UPLOAD_ERR_OK) {
        return null;
    }
    $ext = pathinfo($_FILES[$field]['name'], PATHINFO_EXTENSION);
    $filename = uniqid('upload_', true) . ($ext ? '.' . $ext : '');
    $target = rtrim($config['app']['upload_dir'], '/') . '/' . $filename;
    if (!is_dir($config['app']['upload_dir'])) {
        mkdir($config['app']['upload_dir'], 0775, true);
    }
    if (!move_uploaded_file($_FILES[$field]['tmp_name'], $target)) {
        json_response(['error' => 'Falha ao salvar upload'], 500);
    }
    return $config['app']['upload_url'] . '/' . $filename;
}

function parse_json(): array
{
    $raw = file_get_contents('php://input');
    $data = json_decode($raw, true);
    return is_array($data) ? $data : [];
}

function route(string $method, string $pattern, callable $handler): void
{
    static $routes = [];
    $routes[] = compact('method', 'pattern', 'handler');
    $origin = $_SERVER['HTTP_ORIGIN'] ?? ($_SERVER['HTTP_HOST'] ?? '*');
    $allowOrigin = $origin ? (str_starts_with($origin, 'http') ? $origin : 'http://' . $origin) : '*';
    if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
        header('Access-Control-Allow-Origin: ' . $allowOrigin);
        header('Access-Control-Allow-Credentials: true');
        header('Access-Control-Allow-Headers: Content-Type, Authorization');
        header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS');
        exit;
    }
    if (php_sapi_name() === 'cli') {
        return;
    }
    $requestUri = strtok($_SERVER['REQUEST_URI'], '?');
    $base = rtrim($GLOBALS['config']['app']['base_url'] . '/api', '/');
    if (strpos($requestUri, $base) !== 0) {
        return;
    }
    $path = substr($requestUri, strlen($base));
    foreach ($routes as $r) {
        if ($method !== $_SERVER['REQUEST_METHOD']) {
            continue;
        }
        $patternRegex = '#^' . preg_replace('#\{([a-zA-Z0-9_]+)\}#', '(?P<$1>[^/]+)', $r['pattern']) . '$#';
        if (preg_match($patternRegex, $path, $matches)) {
            header('Access-Control-Allow-Origin: ' . $allowOrigin);
            header('Access-Control-Allow-Credentials: true');
            $params = array_filter($matches, 'is_string', ARRAY_FILTER_USE_KEY);
            $r['handler']($params);
            exit;
        }
    }
}
