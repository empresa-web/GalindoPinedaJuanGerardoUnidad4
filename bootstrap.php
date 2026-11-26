<?php
declare(strict_types=1);

ini_set('display_errors', '0');
error_reporting(E_ALL);

// La ruta /sessions se comparte entre app1 y app2 mediante Docker Compose.
if (getenv('SHARED_SESSION_PATH')) {
    session_save_path((string)getenv('SHARED_SESSION_PATH'));
}

if (session_status() !== PHP_SESSION_ACTIVE) {
    session_name('donaciones_u4_session');
    $https = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off')
        || (getenv('TRUST_PROXY') === '1'
            && strtolower((string)($_SERVER['HTTP_X_FORWARDED_PROTO'] ?? '')) === 'https');
    session_set_cookie_params([
        'httponly' => true,
        'secure' => $https,
        'samesite' => 'Lax',
        'path' => '/',
    ]);
    session_start();
}

header('X-Content-Type-Options: nosniff');
header('X-Frame-Options: DENY');
header('Referrer-Policy: strict-origin-when-cross-origin');
header("Content-Security-Policy: default-src 'self'; script-src 'self'; style-src 'self'; img-src 'self' data:; connect-src 'self' https://api.frankfurter.dev; object-src 'none'; base-uri 'self'; form-action 'self'");

function db(): PDO
{
    static $connection = null;
    if ($connection instanceof PDO) {
        return $connection;
    }
    $host = getenv('DB_HOST') ?: 'db';
    $port = getenv('DB_PORT') ?: '3306';
    $name = getenv('DB_NAME') ?: 'donaciones_u4';
    $user = getenv('DB_USER') ?: 'donaciones';
    $pass = getenv('DB_PASSWORD');
    if ($pass === false || $pass === '') {
        throw new RuntimeException('DB_PASSWORD no está configurada.');
    }
    $dsn = sprintf('mysql:host=%s;port=%s;dbname=%s;charset=utf8mb4', $host, $port, $name);
    $connection = new PDO($dsn, $user, $pass, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES => false,
    ]);
    return $connection;
}

// La interfaz de la Unidad 3 espera id, title, category, goal, desc y emoji.
function load_campaigns(): array
{
    $emoji = [1 => '🎒', 2 => '🥫', 3 => '🌱'];
    $rows = db()->query('SELECT id, titulo, categoria, descripcion, meta FROM campanas ORDER BY id')->fetchAll();
    return array_map(static function (array $row) use ($emoji): array {
        $id = (int)$row['id'];
        return [
            'id' => $id,
            'title' => $row['titulo'],
            'category' => $row['categoria'],
            'goal' => (float)$row['meta'],
            'desc' => $row['descripcion'],
            'emoji' => $emoji[$id] ?? '💚',
        ];
    }, $rows);
}

function pledges(): array
{
    $sql = 'SELECT id, campana_id AS campaign_id, nombre AS name, correo AS email, monto AS amount, fecha AS created_at FROM aportaciones ORDER BY fecha, id';
    $rows = db()->query($sql)->fetchAll();
    foreach ($rows as &$row) {
        $row['campaign_id'] = (int)$row['campaign_id'];
        $row['amount'] = (int)$row['amount'];
    }
    unset($row);
    return $rows;
}

function save_pledge(array $entry): bool
{
    try {
        $sql = 'INSERT INTO aportaciones (id, campana_id, nombre, correo, monto, fecha) VALUES (:id, :campaign_id, :name, :email, :amount, UTC_TIMESTAMP())';
        $stmt = db()->prepare($sql);
        return $stmt->execute([
            ':id' => (string)$entry['id'],
            ':campaign_id' => (int)$entry['campaign_id'],
            ':name' => (string)$entry['name'],
            ':email' => (string)$entry['email'],
            ':amount' => (int)$entry['amount'],
        ]);
    } catch (PDOException $exception) {
        error_log('Error al registrar aportación: ' . $exception->getCode());
        return false;
    }
}

function e(string $s): string
{
    return htmlspecialchars($s, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
}
function csrf(): string
{
    return $_SESSION['csrf'] ??= bin2hex(random_bytes(32));
}
function verify_csrf(): bool
{
    return isset($_POST['csrf']) && is_string($_POST['csrf'])
        && hash_equals(csrf(), $_POST['csrf']);
}
function json_out($data, int $status = 200): never
{
    http_response_code($status);
    header('Content-Type: application/json; charset=utf-8');
    header('Cache-Control: no-store');
    echo json_encode($data, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
    exit;
}
function find_campaign(int $id): ?array
{
    global $campaigns;
    foreach ($campaigns as $campaign) {
        if ($campaign['id'] === $id) {
            return $campaign;
        }
    }
    return null;
}
function total_by_campaign(int $id): int
{
    $stmt = db()->prepare('SELECT COALESCE(SUM(monto), 0) FROM aportaciones WHERE campana_id = :id');
    $stmt->execute([':id' => $id]);
    return (int)$stmt->fetchColumn();
}
function require_admin(): void
{
    if (empty($_SESSION['admin'])) {
        header('Location: /login');
        exit;
    }
}

try {
    $campaigns = load_campaigns();
} catch (PDOException $exception) {
    error_log('Error de conexión MySQL: ' . $exception->getCode());
    http_response_code(503);
    exit('Servicio temporalmente no disponible.');
}
