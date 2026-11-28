<?php

namespace TraCuuKetQua;

use mysqli;

/** Bảng baithi: điểm thi từng môn của học sinh. */
class BaiThiModel
{
    public function __construct(private mysqli $conn)
    {
    }

    /** Kỳ đã công bố điểm thi chưa (bảng congbodiemthi). */
    public function daCongBoDiemThi(int $maKy): bool
    {
        $stmt = $this->conn->prepare("SELECT dacongbo FROM congbodiemthi WHERE makythi = ?");
        $stmt->bind_param('i', $maKy);
        $stmt->execute();
        $row = $stmt->get_result()->fetch_assoc();
        $stmt->close();
        return $row !== null && (int) $row['dacongbo'] === 1;
    }

    /** layDiemTheoMon(): chỉ các môn thi (lamonthi = 1), kèm hệ số của kỳ nếu đã thiết lập. */
    public function layDiemTheoMon(int $maHocSinh, int $maKy): array
    {
        $stmt = $this->conn->prepare(
            "SELECT m.tenmonhoc, b.diem, b.vang, b.tyletrudiem, b.lydotrudiem, h.giatri AS heso
             FROM baithi b
             JOIN monhoc m ON m.mamonhoc = b.mamonhoc AND m.lamonthi = 1
             LEFT JOIN hesodiem h ON h.mamonhoc = b.mamonhoc AND h.makythi = ?
             WHERE b.mahocsinh = ?
             ORDER BY m.mamonhoc"
        );
        $stmt->bind_param('ii', $maKy, $maHocSinh);
        $stmt->execute();
        $rows = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
        $stmt->close();
        return $rows;
    }
}
