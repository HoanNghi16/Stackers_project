<?php
declare(strict_types=1);

require_once __DIR__ . '/chon-giao-vien.php';

/** Only common DB credentials are read from .env; no account/school/exam overrides. */
function phatConnectDb(): mysqli
{
    $file = dirname(__DIR__) . '/.env';
    $env = is_file($file) ? parse_ini_file($file, false, INI_SCANNER_RAW) : [];
    if (!is_array($env)) {
        throw new RuntimeException('Không đọc được cấu hình database.');
    }
    $setting = static function (string $key, string $default = '') use ($env): string {
        $fromSystem = getenv($key);
        $raw = $fromSystem !== false ? (string)$fromSystem : (string)($env[$key] ?? $default);
        return trim($raw, " \t\r\n\0\x0B\"'");
    };
    $host = $setting('DB_HOST');
    $user = $setting('DB_USER');
    $password = $setting('DB_PASSWORD');
    $database = $setting('DB_NAME');
    $port = $setting('DB_PORT', '3306');
    if ($host === '' || str_contains($host, '://') || $user === '' || $database === '' || !ctype_digit($port)) {
        throw new DomainException('Thiếu hoặc sai DB_HOST/DB_USER/DB_NAME/DB_PORT trong cấu hình database.');
    }
    mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);
    $db = new mysqli($host, $user, $password, $database, (int)$port);
    $db->set_charset('utf8mb4');
    return $db;
}

function phatSessionValue(array $session, array $paths): ?string
{
    foreach ($paths as $path) {
        $val = $session;
        foreach (explode('.', $path) as $part) {
            if (!is_array($val) || !array_key_exists($part, $val)) {
                $val = null;
                break;
            }
            $val = $val[$part];
        }
        if (is_scalar($val) && trim((string)$val) !== '') {
            return trim((string)$val);
        }
    }
    return null;
}

/**
 * IMPORTANT: Neither nguoidung nor truonghoc in the shared schema has an
 * account -> school relationship. A vetted mapping table is therefore needed;
 * do not infer school from numeric IDs or from a browser parameter.
 */
function phatResolveManagedSchool(mysqli $db, string $userId, string $role): string
{
    $exists = $db->query("SELECT COUNT(*) AS n FROM information_schema.TABLES
        WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'qlgv_quanlytruong'")->fetch_assoc();
    if ((int)($exists['n'] ?? 0) !== 1) {
        throw new DomainException('Database chưa có bảng ánh xạ qlgv_quanlytruong. Nhờ nhóm trưởng duyệt file SQL đi kèm trước khi dùng chức năng.');
    }
    $stmt = $db->prepare(
        'SELECT th.matruong AS school_id
           FROM qlgv_quanlytruong AS q
           JOIN nguoidung AS nd ON nd.manguoidung = q.manguoidung
           JOIN truonghoc AS th ON th.matruong = q.matruong
          WHERE q.manguoidung = ? AND nd.vaitro = ? AND th.loaitruong = ?
          LIMIT 2'
    );
    $stmt->bind_param('sss', $userId, $role, $role);
    $stmt->execute();
    $rows = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    if (count($rows) !== 1) {
        throw new DomainException('Tài khoản chưa được liên kết với đúng một trường ' . $role . ' trong qlgv_quanlytruong.');
    }
    $schoolId = (string)$rows[0]['school_id'];
    // If the official login flow later stores matruong, require it to agree.
    $sessionSchool = phatSessionValue($_SESSION, ['matruong', 'user.matruong']);
    if ($sessionSchool !== null && $sessionSchool !== $schoolId) {
        throw new DomainException('Trường trong Session không khớp với quyền quản lý đã lưu ở database.');
    }
    return $schoolId;
}

/** Select by actual enrollment year, not by local .env or arbitrary client input. */
function phatCurrentExamId(mysqli $db): string
{
    $result = $db->query('SELECT makythi FROM kythituyensinh
                           WHERE namtuyensinh = YEAR(CURDATE()) LIMIT 2');
    $rows = $result->fetch_all(MYSQLI_ASSOC);
    if (count($rows) !== 1) {
        throw new DomainException('Không xác định được duy nhất kỳ thi tuyển sinh của năm hiện tại từ kythituyensinh.');
    }
    return (string)$rows[0]['makythi'];
}

function phatCreateTeacherSelectionForRole(string $requiredRole): PhatTeacherSelection
{
    if (session_status() !== PHP_SESSION_ACTIVE) {
        session_start();
    }
    $role = phatSessionValue($_SESSION, ['vaitro']);
    if ($role !== $requiredRole) {
        throw new DomainException('Cần đăng nhập bằng tài khoản ' . $requiredRole . ' để chọn giáo viên.');
    }
    $userId = phatSessionValue($_SESSION, ['manguoidung']);
    if ($userId === null || !ctype_digit($userId) || (int)$userId < 1) {
        throw new DomainException('Phiên đăng nhập thiếu manguoidung hợp lệ.');
    }
    $db = phatConnectDb();
    $schoolId = phatResolveManagedSchool($db, $userId, $requiredRole);
    $examId = phatCurrentExamId($db);
    $config = require __DIR__ . '/chon-giao-vien-config.php';
    return new PhatTeacherSelection($db, $config, $schoolId, $examId, $userId);
}

function phatCreateTeacherService(): PhatTeacherSelection
{
    return phatCreateTeacherSelectionForRole('THCS');
}
