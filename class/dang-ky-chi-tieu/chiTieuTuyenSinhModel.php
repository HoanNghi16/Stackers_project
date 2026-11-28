<?php

namespace DangKyChiTieu;

use mysqli;

/** Bảng chitieutuyensinh (PK: matruong + makythi). */
class ChiTieuTuyenSinhModel
{
    public function __construct(private mysqli $conn)
    {
    }

    /** layDeXuatChiTieu(): chỉ tiêu đã đăng ký của trường trong kỳ, null nếu chưa có. */
    public function layDeXuatChiTieu(int $maTruong, int $maKy): ?array
    {
        $stmt = $this->conn->prepare("SELECT chitieu, daduyet FROM chitieutuyensinh WHERE matruong = ? AND makythi = ?");
        $stmt->bind_param('ii', $maTruong, $maKy);
        $stmt->execute();
        $row = $stmt->get_result()->fetch_assoc();
        $stmt->close();
        return $row ? ['chitieu' => (int) $row['chitieu'], 'daduyet' => (bool) $row['daduyet']] : null;
    }

    /** Khóa dòng (nếu có) trong transaction rồi trả về trạng thái duyệt. */
    public function khoaVaLayDaDuyet(int $maTruong, int $maKy): ?bool
    {
        $stmt = $this->conn->prepare("SELECT daduyet FROM chitieutuyensinh WHERE matruong = ? AND makythi = ? FOR UPDATE");
        $stmt->bind_param('ii', $maTruong, $maKy);
        $stmt->execute();
        $row = $stmt->get_result()->fetch_assoc();
        $stmt->close();
        return $row ? (bool) $row['daduyet'] : null;
    }

    /** luuChiTieu(): thêm mới hoặc cập nhật, luôn ở trạng thái chờ duyệt. */
    public function luuChiTieu(int $maTruong, int $maKy, int $soLuong): void
    {
        $stmt = $this->conn->prepare(
            "INSERT INTO chitieutuyensinh (matruong, makythi, chitieu, daduyet) VALUES (?, ?, ?, 0)
             ON DUPLICATE KEY UPDATE chitieu = VALUES(chitieu), daduyet = 0"
        );
        $stmt->bind_param('iii', $maTruong, $maKy, $soLuong);
        $stmt->execute();
        $stmt->close();
    }
}
