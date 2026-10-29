<?php
declare(strict_types=1);
require_once __DIR__ . '/phat-db.php';

/** Danh sách trường công khai. Đọc thông tin trực tiếp từ MySQL, không suy đoán phường. */
final class PhatDanhSachTruong
{
    public function __construct(private readonly mysqli $db) {}

    public function exam(): ?array
    {
        $res = $this->db->query('SELECT makythi, namtuyensinh FROM kythituyensinh ORDER BY namtuyensinh DESC, makythi DESC LIMIT 1');
        return $res->fetch_assoc() ?: null;
    }

    /**
     * Dữ liệu diachi hiện tại có dạng "Quận Gò Vấp, TP. Hồ Chí Minh".
     * Chỉ trích phần đầu để lọc QUẬN/KHU VỰC, TUYỆT ĐỐI không gọi là phường.
     * Nếu sau này nhóm lưu địa chỉ chi tiết hơn, cần nâng cấp bộ tách khu vực.
     */
    public function areas(): array
    {
        $sql = 'SELECT TRIM(SUBSTRING_INDEX(t.diachi, ",", 1)) AS khuvuc,
                       COUNT(*) AS so_truong,
                       SUM(CASE WHEN t.loaitruong = "THCS" THEN 1 ELSE 0 END) AS so_thcs,
                       SUM(CASE WHEN t.loaitruong = "THPT" THEN 1 ELSE 0 END) AS so_thpt
                FROM truonghoc t
                WHERE t.diachi IS NOT NULL AND TRIM(t.diachi) <> ""
                GROUP BY khuvuc ORDER BY khuvuc';
        return $this->db->query($sql)->fetch_all(MYSQLI_ASSOC);
    }

    public function schools(string $keyword, string $type, string $area, int $examId): array
    {
        $keyword = trim($keyword);
        if (function_exists('mb_strlen') && mb_strlen($keyword, 'UTF-8') > 100) {
            $keyword = mb_substr($keyword, 0, 100, 'UTF-8');
        } elseif (strlen($keyword) > 300) {
            $keyword = substr($keyword, 0, 300);
        }
        $type = in_array(strtoupper($type), ['THCS', 'THPT'], true) ? strtoupper($type) : '';
        $area = trim($area);
        if (function_exists('mb_strlen') && mb_strlen($area, 'UTF-8') > 100) {
            $area = mb_substr($area, 0, 100, 'UTF-8');
        } elseif (strlen($area) > 300) {
            $area = substr($area, 0, 300);
        }

        $hasQuota = PhatDb::hasTable($this->db, 'chitieutuyensinh');
        $quotaSelect = $hasQuota ? 'CASE WHEN ct.daduyet = 1 THEN ct.chitieu ELSE NULL END' : 'NULL';
        $quotaJoin = $hasQuota ? 'LEFT JOIN chitieutuyensinh ct ON ct.matruong = t.matruong AND ct.makythi = ?' : '';
        $sql = 'SELECT t.matruong, t.tentruong, t.loaitruong, t.diachi, t.maphuong,
                       t.ladiemthi, TRIM(SUBSTRING_INDEX(t.diachi, ",", 1)) AS khuvuc,
                       ' . $quotaSelect . ' AS chitieu
                FROM truonghoc t ' . $quotaJoin . '
                WHERE (? = "" OR t.tentruong LIKE CONCAT("%", ?, "%"))
                  AND (? = "" OR t.loaitruong = ?)
                  AND (? = "" OR TRIM(SUBSTRING_INDEX(t.diachi, ",", 1)) = ?)
                ORDER BY t.loaitruong DESC, t.tentruong ASC, t.matruong ASC
                LIMIT 500';
        $stmt = $this->db->prepare($sql);
        if ($hasQuota) {
            $stmt->bind_param('issssss', $examId, $keyword, $keyword, $type, $type, $area, $area);
        } else {
            $stmt->bind_param('ssssss', $keyword, $keyword, $type, $type, $area, $area);
        }
        $stmt->execute();
        return $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    }
}
