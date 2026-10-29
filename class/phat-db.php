<?php
declare(strict_types=1);
/** Các truy vấn dùng riêng cho chức năng của Phát; không ghi đè cấu hình dự án. */
final class PhatDb
{
    public static function connect(): mysqli
    {
        $path = dirname(__DIR__) . '/.env';
        $env = is_file($path) ? parse_ini_file($path, false, INI_SCANNER_RAW) : false;
        if (!is_array($env)) throw new RuntimeException('Không thể kết nối dữ liệu.');
        $read = static function (string $key, string $fallback = '') use ($env): string {
            $v = getenv($key);
            return trim((string)($v !== false ? $v : ($env[$key] ?? $fallback)), " \t\n\r\0\x0B\"'");
        };
        $host = $read('DB_HOST');
        $user = $read('DB_USER');
        $name = $read('DB_NAME');
        $port = $read('DB_PORT', '3306');
        if (!$host || !$user || !$name || str_contains($host, '://') || !ctype_digit($port)) {
            throw new RuntimeException('Không thể kết nối dữ liệu.');
        }
        mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);
        $db = new mysqli($host, $user, $read('DB_PASSWORD'), $name, (int)$port);
        $db->set_charset('utf8mb4');
        return $db;
    }

    public static function hasTable(mysqli $db, string $table): bool
    {
        $stmt = $db->prepare('SELECT 1 FROM information_schema.TABLES WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = ? LIMIT 1');
        $stmt->bind_param('s', $table);
        $stmt->execute();
        return (bool)$stmt->get_result()->fetch_row();
    }

    public static function hasColumn(mysqli $db, string $table, string $column): bool
    {
        $stmt = $db->prepare('SELECT 1 FROM information_schema.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = ? AND COLUMN_NAME = ? LIMIT 1');
        $stmt->bind_param('ss', $table, $column);
        $stmt->execute();
        return (bool)$stmt->get_result()->fetch_row();
    }

    public static function escape(mixed $value): string
    {
        return htmlspecialchars((string)($value ?? ''), ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
    }
}
