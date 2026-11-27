<?php

namespace XemDanhSachNhapHoc;

use mysqli;

/** Xác định trường THPT của tài khoản đang đăng nhập (qua qlgv_quanlytruong). */
class TruongTHPTModel
{
    public function __construct(private mysqli $conn)
    {
    }

    public function layTheoNguoiDung(int $maNguoiDung): ?array
    {
        $stmt = $this->conn->prepare(
            "SELECT t.matruong, t.tentruong, t.diachi
             FROM qlgv_quanlytruong q JOIN truonghoc t ON t.matruong = q.matruong
             WHERE q.manguoidung = ? AND t.loaitruong = 'THPT'"
        );
        $stmt->bind_param('i', $maNguoiDung);
        $stmt->execute();
        $row = $stmt->get_result()->fetch_assoc();
        $stmt->close();
        return $row ?: null;
    }
}
