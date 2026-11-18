<?php

declare(strict_types=1);
require dirname(__DIR__).'/vendor/autoload.php';
Dotenv\Dotenv::createImmutable(dirname(__DIR__))->safeLoad();
if (PHP_SAPI !== 'cli') {
    session_start();
}
try {
    $db = new PDO('mysql:host='.($_ENV['DB_HOST'] ?? '127.0.0.1').';port='.($_ENV['DB_PORT'] ?? '3306').';dbname='.($_ENV['DB_NAME'] ?? 'huit_db').';charset=utf8mb4', $_ENV['DB_USER'] ?? 'root', $_ENV['DB_PASSWORD'] ?? '', [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,PDO::ATTR_EMULATE_PREPARES => false]);
} catch (PDOException $e) {
    http_response_code(500);
    exit('Không thể kết nối cơ sở dữ liệu. Kiểm tra .env và import database.sql.');
}
function h($x): string
{
    return htmlspecialchars((string)$x, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
}
function all(PDO $db, string $sql, array $args = []): array
{
    $s = $db->prepare($sql);
    $s->execute($args);
    return $s->fetchAll();
}
function go(string $url = 'index.php'): never
{
    header('Location: '.$url);
    exit;
}
function csrf(): void
{
    if (!hash_equals($_SESSION['csrf'] ?? '', $_POST['csrf'] ?? '')) {
        http_response_code(403);
        exit('Phiên làm việc không hợp lệ.');
    }
}
$_SESSION['csrf'] ??= bin2hex(random_bytes(32));
$days = [2 => 'Thứ Hai',3 => 'Thứ Ba',4 => 'Thứ Tư',5 => 'Thứ Năm',6 => 'Thứ Sáu',7 => 'Thứ Bảy',8 => 'Chủ nhật'];
function occurrence(string $from, string $to, int $day): bool
{
    if ($from > $to) {
        return false;
    }$d = new DateTimeImmutable($from);
    $wanted = $day === 8 ? 7 : $day - 1;
    $offset = ($wanted - (int)$d->format('N') + 7) % 7;
    return $d->modify("+$offset days")->format('Y-m-d') <= $to;
}
function requireAdmin(): void
{
    if (($_SESSION['user']['role'] ?? '') !== 'admin') {
        http_response_code(403);
        exit('Bạn không có quyền truy cập.');
    }
}
