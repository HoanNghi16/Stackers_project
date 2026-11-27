<?php

namespace XacNhanNhapHoc;

use mysqli;
use DateTime;
use DateTimeZone;

/** Hồ sơ học sinh của kỳ tuyển sinh hiện tại (năm hiện tại) + hạn xác nhận nhập học. */
class HocSinhKyModel
{
    public function __construct(private mysqli $conn)
    {
    }

    public function layNamHienTai(): int
    {
        return (int) (new DateTime('now', new DateTimeZone('Asia/Ho_Chi_Minh')))->format('Y');
    }

    public function homNay(): string
    {
        return (new DateTime('now', new DateTimeZone('Asia/Ho_Chi_Minh')))->format('Y-m-d');
    }

    /** Học sinh (của tài khoản) trong kỳ hiện tại + hạn xác nhận; $khoa = true để khóa dòng trong transaction. */
    public function layHocSinhTrongKy(int $maNguoiDung, bool $khoa = false): ?array
    {
        $nam = $this->layNamHienTai();
        $stmt = $this->conn->prepare(
            "SELECT hs.mahocsinh, hs.makythi, hs.sobaodanh, hn.ho, hn.ten, k.hanchotxacnhannhaphoc
             FROM hocsinh hs
             JOIN kythituyensinh k ON k.makythi = hs.makythi AND k.namtuyensinh = ?
             JOIN hosonguoidung hn ON hn.manguoidung = hs.manguoidung
             WHERE hs.manguoidung = ?
             ORDER BY hs.mahocsinh LIMIT 1" . ($khoa ? " FOR UPDATE" : "")
        );
        $stmt->bind_param('ii', $nam, $maNguoiDung);
        $stmt->execute();
        $row = $stmt->get_result()->fetch_assoc();
        $stmt->close();
        return $row ?: null;
    }

    public function conHanXacNhan(array $hs): bool
    {
        return $hs['hanchotxacnhannhaphoc'] !== null && $this->homNay() <= $hs['hanchotxacnhannhaphoc'];
    }
}
