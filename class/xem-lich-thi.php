<?php
declare(strict_types=1);

/**
 * Xem lịch thi: chỉ đọc dữ liệu của tài khoản HOC_SINH đang đăng nhập.
 * Không dùng dữ liệu giả, không ghi database, không cho chọn mã học sinh từ URL.
 */
final class PhatXemLichThi
{
    private mysqli $db;
    /** @var array<string, array<string,string>> */
    private array $schema = [];
    /** @var array<string,string|null> */
    private array $tables = [];

    public function __construct(mysqli $db)
    {
        $this->db = $db;
        $this->db->set_charset('utf8mb4');
    }

    public static function connect(): mysqli
    {
        $file = dirname(__DIR__) . '/.env';
        $data = is_file($file) ? parse_ini_file($file, false, INI_SCANNER_RAW) : false;
        if (!is_array($data)) {
            throw new RuntimeException('Không đọc được cấu hình kết nối.');
        }
        $config = static function (string $name, string $default = '') use ($data): string {
            $value = getenv($name);
            return trim((string)($value !== false ? $value : ($data[$name] ?? $default)), " \t\n\r\0\x0B\"'");
        };
        $host = $config('DB_HOST');
        $username = $config('DB_USER');
        $database = $config('DB_NAME');
        $password = $config('DB_PASSWORD');
        $port = $config('DB_PORT', '3306');
        if ($host === '' || str_contains($host, '://') || $username === '' || $database === '' || !ctype_digit($port)) {
            throw new RuntimeException('Cấu hình kết nối chưa hợp lệ.');
        }
        mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);
        return new mysqli($host, $username, $password, $database, (int)$port);
    }

    /** @return string|null actual table name, without backticks */
    private function table(string $table): ?string
    {
        if (array_key_exists($table, $this->tables)) {
            return $this->tables[$table];
        }
        $stmt = $this->db->prepare('SELECT TABLE_NAME FROM information_schema.TABLES WHERE TABLE_SCHEMA = DATABASE() AND LOWER(TABLE_NAME) = ? LIMIT 1');
        $stmt->bind_param('s', $table);
        $stmt->execute();
        $actual = $stmt->get_result()->fetch_column();
        $this->tables[$table] = is_string($actual) ? $actual : null;
        return $this->tables[$table];
    }

    /** @return array<string,string> lowercase => actual column */
    private function cols(string $table): array
    {
        if (isset($this->schema[$table])) {
            return $this->schema[$table];
        }
        $actual = $this->table($table);
        if ($actual === null) {
            return $this->schema[$table] = [];
        }
        $stmt = $this->db->prepare('SELECT COLUMN_NAME FROM information_schema.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = ?');
        $stmt->bind_param('s', $actual);
        $stmt->execute();
        $cols = [];
        foreach ($stmt->get_result()->fetch_all(MYSQLI_ASSOC) as $row) {
            $cols[strtolower((string)$row['COLUMN_NAME'])] = (string)$row['COLUMN_NAME'];
        }
        return $this->schema[$table] = $cols;
    }

    private function ident(string $name): string
    {
        if (!preg_match('/^[a-zA-Z0-9_]+$/D', $name)) {
            throw new RuntimeException('Cấu trúc dữ liệu chưa phù hợp.');
        }
        return '`' . $name . '`';
    }

    /** Return an SQL identifier or throw if required column is absent. */
    private function col(string $table, string $column): string
    {
        $name = $this->cols($table)[strtolower($column)] ?? null;
        if ($name === null) {
            throw new RuntimeException('Thiếu thông tin ' . $table . '.' . $column . ' trong dữ liệu hiện tại.');
        }
        return $this->ident($name);
    }

    private function optional(string $table, string $column, string $alias, string $fallback = 'NULL'): string
    {
        $name = $this->cols($table)[strtolower($column)] ?? null;
        return $name !== null ? $alias . '.' . $this->ident($name) : $fallback;
    }

    public static function isPublished(mixed $value): bool
    {
        if (is_bool($value)) return $value;
        if (is_int($value) || is_float($value)) return $value === 1 || $value === 1.0;
        return in_array(strtoupper(trim((string)$value)), ['1', 'TRUE', 'DA_CONG_BO', 'DA_CONGBO', 'CONG_BO', 'PUBLISHED'], true);
    }

    /**
     * Result keys: state, candidate, schedules, publication_verified.
     * state is one of: no_profile, not_published, no_schedule, ready.
     * Throw on query/schema/connection failures (handled in UI without database details).
     */
    public function getScheduleForUser(int $userId): array
    {
        if ($userId <= 0) return ['state' => 'no_profile'];
        foreach (['hocsinh', 'kythituyensinh', 'lichthi', 'monhoc'] as $needed) {
            if ($this->table($needed) === null) {
                throw new RuntimeException('Thiếu bảng dữ liệu cần thiết: ' . $needed);
            }
        }

        $nameSelect = "'' AS hoten";
        $nameJoin = '';
        if ($this->table('hosonguoidung') !== null
            && isset($this->cols('hosonguoidung')['manguoidung'], $this->cols('hosonguoidung')['ho'], $this->cols('hosonguoidung')['ten'])) {
            $nameSelect = "TRIM(CONCAT_WS(' ', hsn." . $this->col('hosonguoidung', 'ho') . ", hsn." . $this->col('hosonguoidung', 'ten') . ")) AS hoten";
            $nameJoin = ' LEFT JOIN ' . $this->ident($this->table('hosonguoidung')) . ' hsn ON hsn.' . $this->col('hosonguoidung', 'manguoidung') . ' = hs.' . $this->col('hocsinh', 'manguoidung');
        }
        $sql = "SELECT hs." . $this->col('hocsinh', 'mahocsinh') . " AS mahocsinh,
                  hs." . $this->col('hocsinh', 'makythi') . " AS makythi,
                  " . $this->optional('hocsinh', 'sobaodanh', 'hs') . " AS sobaodanh,
                  ky." . $this->col('kythituyensinh', 'namtuyensinh') . " AS namtuyensinh,
                  $nameSelect
                FROM " . $this->ident($this->table('hocsinh')) . " hs
                JOIN " . $this->ident($this->table('kythituyensinh')) . " ky
                    ON ky." . $this->col('kythituyensinh', 'makythi') . " = hs." . $this->col('hocsinh', 'makythi') . "
                $nameJoin
                WHERE hs." . $this->col('hocsinh', 'manguoidung') . " = ?
                ORDER BY ky." . $this->col('kythituyensinh', 'namtuyensinh') . " DESC, hs." . $this->col('hocsinh', 'mahocsinh') . " DESC LIMIT 1";
        $stmt = $this->db->prepare($sql);
        $stmt->bind_param('i', $userId);
        $stmt->execute();
        $candidate = $stmt->get_result()->fetch_assoc();
        if (!$candidate) return ['state' => 'no_profile'];
        $examId = (int)$candidate['makythi'];

        // Where is publication authority stored? Do not expose unpublished rows
        // if the deployed database contains no verified publication field/table.
        $publicationMode = 'none';
        $examPublishColumn = null;
        $schedulePublishColumn = null;
        foreach (['lichthidacongbo', 'trangthaicongbolichthi', 'dacongbolichthi'] as $column) {
            if (isset($this->cols('kythituyensinh')[$column])) {
                $examPublishColumn = $column;
                $publicationMode = 'exam';
                break;
            }
        }
        if ($publicationMode === 'none' && $this->table('congbolichthi') !== null
            && isset($this->cols('congbolichthi')['makythi'], $this->cols('congbolichthi')['dacongbo'])) {
            $publicationMode = 'publish_table';
        }
        if ($publicationMode === 'none') {
            foreach (['dacongbo', 'trangthaicongbo', 'dadang'] as $column) {
                if (isset($this->cols('lichthi')[$column])) {
                    $publicationMode = 'row';
                    $schedulePublishColumn = $column;
                    break;
                }
            }
        }
        if ($publicationMode === 'none') {
            return ['state' => 'not_published', 'candidate' => $candidate, 'publication_verified' => false];
        }
        if ($publicationMode === 'exam') {
            $query = 'SELECT ' . $this->col('kythituyensinh', $examPublishColumn) . ' AS published FROM ' . $this->ident($this->table('kythituyensinh'))
                . ' WHERE ' . $this->col('kythituyensinh', 'makythi') . ' = ? LIMIT 1';
            $stmt = $this->db->prepare($query);
            $stmt->bind_param('i', $examId);
            $stmt->execute();
            if (!self::isPublished($stmt->get_result()->fetch_column())) {
                return ['state' => 'not_published', 'candidate' => $candidate, 'publication_verified' => true];
            }
        } elseif ($publicationMode === 'publish_table') {
            $query = 'SELECT ' . $this->col('congbolichthi', 'dacongbo') . ' AS published FROM ' . $this->ident($this->table('congbolichthi'))
                . ' WHERE ' . $this->col('congbolichthi', 'makythi') . ' = ? LIMIT 1';
            $stmt = $this->db->prepare($query);
            $stmt->bind_param('i', $examId);
            $stmt->execute();
            if (!self::isPublished($stmt->get_result()->fetch_column())) {
                return ['state' => 'not_published', 'candidate' => $candidate, 'publication_verified' => true];
            }
        }

        $publishedSelect = $publicationMode === 'row'
            ? ', lt.' . $this->col('lichthi', $schedulePublishColumn) . ' AS published'
            : '';
        $scheduleSql = 'SELECT lt.' . $this->col('lichthi', 'malichthi') . ' AS malichthi,
                    lt.' . $this->col('lichthi', 'ngaythi') . ' AS ngaythi,
                    lt.' . $this->col('lichthi', 'buoithi') . ' AS buoithi,
                    mh.' . $this->col('monhoc', 'tenmonhoc') . ' AS mon,
                    ' . $this->optional('lichthi', 'giobatdau', 'lt') . ' AS giobatdau,
                    ' . $this->optional('lichthi', 'gioketthuc', 'lt') . ' AS gioketthuc
                    ' . $publishedSelect . '
                FROM ' . $this->ident($this->table('lichthi')) . ' lt
                JOIN ' . $this->ident($this->table('monhoc')) . ' mh
                    ON mh.' . $this->col('monhoc', 'mamonhoc') . ' = lt.' . $this->col('lichthi', 'mamonhoc') . '
                WHERE lt.' . $this->col('lichthi', 'makythi') . ' = ?
                ORDER BY lt.' . $this->col('lichthi', 'ngaythi') . ', lt.' . $this->col('lichthi', 'buoithi') . ', lt.' . $this->col('lichthi', 'malichthi');
        $stmt = $this->db->prepare($scheduleSql);
        $stmt->bind_param('i', $examId);
        $stmt->execute();
        $schedules = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
        if ($publicationMode === 'row') {
            $schedules = array_values(array_filter($schedules, static fn(array $s): bool => self::isPublished($s['published'] ?? null)));
        }
        if (!$schedules) {
            return ['state' => $publicationMode === 'row' ? 'not_published' : 'no_schedule', 'candidate' => $candidate, 'publication_verified' => true];
        }

        // Student's room is shared across exam sessions per XepPhongThi schema.
        $location = ['tenphong' => '', 'tentruong' => '', 'diachi' => ''];
        if ($this->table('xepphongthi') && $this->table('phong') && $this->table('truonghoc')) {
            $locSql = 'SELECT p.' . $this->col('phong', 'tenphong') . ' AS tenphong,
                    th.' . $this->col('truonghoc', 'tentruong') . ' AS tentruong,
                    ' . $this->optional('truonghoc', 'diachi', 'th', "''") . ' AS diachi
                FROM ' . $this->ident($this->table('xepphongthi')) . ' xp
                JOIN ' . $this->ident($this->table('phong')) . ' p
                    ON p.' . $this->col('phong', 'maphong') . ' = xp.' . $this->col('xepphongthi', 'maphong') . '
                JOIN ' . $this->ident($this->table('truonghoc')) . ' th
                    ON th.' . $this->col('truonghoc', 'matruong') . ' = p.' . $this->col('phong', 'matruong') . '
                WHERE xp.' . $this->col('xepphongthi', 'mahocsinh') . ' = ? LIMIT 1';
            $studentId = (int)$candidate['mahocsinh'];
            $stmt = $this->db->prepare($locSql);
            $stmt->bind_param('i', $studentId);
            $stmt->execute();
            $loc = $stmt->get_result()->fetch_assoc();
            if ($loc) $location = $loc;
        }
        foreach ($schedules as &$schedule) {
            $schedule['tenphong'] = (string)($location['tenphong'] ?? '');
            $schedule['tentruong'] = (string)($location['tentruong'] ?? '');
            $schedule['diachi'] = (string)($location['diachi'] ?? '');
        }
        unset($schedule);
        return ['state' => 'ready', 'candidate' => $candidate, 'schedules' => $schedules, 'publication_verified' => true];
    }
}
