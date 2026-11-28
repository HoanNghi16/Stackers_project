<?php

namespace XacNhanNhapHoc;

use mysqli;

/** KetQuaXetTuyen trong sequence: kết quả trúng tuyển của học sinh. */
class KetQuaXetTuyenModel
{
    public function __construct(private mysqli $conn)
    {
    }

    /** Kết quả xét tuyển đã công bố: kỳ có dòng điểm chuẩn và tất cả đều DA_CONG_BO (cùng quy ước use case Tra cứu). */
    public function daCongBoKetQua(int $maKy): bool
    {
        $stmt = $this->conn->prepare(
            "SELECT COUNT(*) AS tong, SUM(trangthaicongbo = 'DA_CONG_BO') AS da FROM diemchuan WHERE makythi = ?"
        );
        $stmt->bind_param('i', $maKy);
        $stmt->execute();
        $row = $stmt->get_result()->fetch_assoc();
        $stmt->close();
        return (int) $row['tong'] > 0 && (int) $row['tong'] === (int) $row['da'];
    }

    /** layKetQuaTrungTuyen(): true nếu học sinh có nguyện vọng trúng tuyển (DAU hoặc đã xác nhận). */
    public function coTrungTuyen(int $maHocSinh): bool
    {
        $stmt = $this->conn->prepare(
            "SELECT 1 FROM nguyenvong WHERE mahocsinh = ? AND trangthaixettuyen IN ('DAU','XAC_NHAN_NHAP_HOC') LIMIT 1"
        );
        $stmt->bind_param('i', $maHocSinh);
        $stmt->execute();
        $co = $stmt->get_result()->num_rows > 0;
        $stmt->close();
        return $co;
    }
}
