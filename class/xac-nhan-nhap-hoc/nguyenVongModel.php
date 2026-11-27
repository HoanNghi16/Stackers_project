<?php

namespace XacNhanNhapHoc;

use mysqli;

/** NguyenVong trong sequence: nguyện vọng trúng tuyển và cập nhật trạng thái nhập học. */
class NguyenVongModel
{
    public function __construct(private mysqli $conn)
    {
    }

    /**
     * layNguyenVongTrungTuyen(): nguyện vọng trúng tuyển hiện tại (ưu tiên đã xác nhận, rồi thứ tự NV nhỏ nhất).
     * $khoa = true để khóa dòng trong transaction.
     */
    public function layNguyenVongTrungTuyen(int $maHocSinh, bool $khoa = false): ?array
    {
        $stmt = $this->conn->prepare(
            "SELECT manguyenvong, thutunguyenvong, matruong, trangthaixettuyen
             FROM nguyenvong
             WHERE mahocsinh = ? AND trangthaixettuyen IN ('DAU','XAC_NHAN_NHAP_HOC')
             ORDER BY (trangthaixettuyen = 'XAC_NHAN_NHAP_HOC') DESC, thutunguyenvong" . ($khoa ? " FOR UPDATE" : "")
        );
        $stmt->bind_param('i', $maHocSinh);
        $stmt->execute();
        $rows = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
        $stmt->close();
        return $rows[0] ?? null;
    }

    /** capNhatTrangThaiNhapHoc(): chuyển DAU -> XAC_NHAN_NHAP_HOC; trả về số dòng đổi (0 nếu trạng thái đã khác). */
    public function capNhatTrangThaiNhapHoc(int $maNguyenVong): int
    {
        $stmt = $this->conn->prepare(
            "UPDATE nguyenvong SET trangthaixettuyen = 'XAC_NHAN_NHAP_HOC' WHERE manguyenvong = ? AND trangthaixettuyen = 'DAU'"
        );
        $stmt->bind_param('i', $maNguyenVong);
        $stmt->execute();
        $n = $stmt->affected_rows;
        $stmt->close();
        return $n;
    }
}
