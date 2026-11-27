<?php

namespace XemDanhSachNhapHoc;

use mysqli;
use DateTime;
use DateTimeZone;

/** Bảng kythituyensinh: kỳ tuyển sinh hiện tại = năm hiện tại. */
class KyThiTuyenSinhModel
{
    public function __construct(private mysqli $conn)
    {
    }

    public function layNamHienTai(): int
    {
        return (int) (new DateTime('now', new DateTimeZone('Asia/Ho_Chi_Minh')))->format('Y');
    }

    public function layKyTuyenSinhHienTai(): ?array
    {
        $nam  = $this->layNamHienTai();
        $stmt = $this->conn->prepare("SELECT makythi, namtuyensinh FROM kythituyensinh WHERE namtuyensinh = ?");
        $stmt->bind_param('i', $nam);
        $stmt->execute();
        $row = $stmt->get_result()->fetch_assoc();
        $stmt->close();
        return $row ?: null;
    }
}
