<?php

namespace XacNhanNhapHoc;

use mysqli;

/** TruongTHPT trong sequence: layThongTinTruong(), layChiTietTruong(). */
class TruongTHPTModel
{
    public function __construct(private mysqli $conn)
    {
    }

    public function layThongTinTruong(int $maTruong): ?array
    {
        $stmt = $this->conn->prepare(
            "SELECT t.matruong, t.tentruong, t.diachi, p.tenphuong
             FROM truonghoc t LEFT JOIN phuong p ON p.maphuong = t.maphuong
             WHERE t.matruong = ?"
        );
        $stmt->bind_param('i', $maTruong);
        $stmt->execute();
        $row = $stmt->get_result()->fetch_assoc();
        $stmt->close();
        return $row ?: null;
    }
}
