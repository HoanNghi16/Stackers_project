<?php

namespace TraCuuKetQua;

use mysqli;

/** HocSinh trong sequence: kiểm tra số báo danh có thuộc tài khoản đang đăng nhập. */
class HocSinhModel
{
    public function __construct(private mysqli $conn)
    {
    }

    /** kiemTraSBDThuocTaiKhoan(): trả về thông tin học sinh nếu SBD đúng và thuộc tài khoản, ngược lại null. */
    public function kiemTraSBDThuocTaiKhoan(string $soBaoDanh, int $maNguoiDung): ?array
    {
        $stmt = $this->conn->prepare(
            "SELECT hs.mahocsinh, hs.makythi, hs.sobaodanh, hs.tongdiem, hn.ho, hn.ten, hn.ngaysinh
             FROM hocsinh hs
             JOIN hosonguoidung hn ON hn.manguoidung = hs.manguoidung
             WHERE hs.sobaodanh = ? AND hs.manguoidung = ?"
        );
        $stmt->bind_param('si', $soBaoDanh, $maNguoiDung);
        $stmt->execute();
        $row = $stmt->get_result()->fetch_assoc();
        $stmt->close();
        return $row ?: null;
    }
}
