<?php
require_once __DIR__ . '/../index.php';

class DangKyNguyenVong
{
    private $conn;

    public function __construct($conn)
    {
        $this->conn = $conn;
    }

    public function getById($manguoidung)
    {
        $sql = "SELECT
                    hs.mahocsinh,
                    hs.manguoidung,
                    hs.makythi,
                    hs.sobaodanh,
                    hs.trangthaiduthi,
                    ho.mahoso,
                    ho.ho,
                    ho.ten,
                    ho.ngaysinh,
                    th.tentruong AS truong_thcs
                FROM hosonguoidung ho
                INNER JOIN hocsinh hs
                    ON ho.manguoidung = hs.manguoidung
                LEFT JOIN truonghoc th
                    ON th.matruong = hs.matruongdanghoc
                WHERE ho.manguoidung = ?
                LIMIT 1";

        $stmt = $this->conn->prepare($sql);
        $stmt->bind_param("i", $manguoidung);
        $stmt->execute();

        $result = $stmt->get_result();
        $data = $result->fetch_assoc();

        $stmt->close();

        return $data;
    }

    public function updateTrangThaiDuThi($mahocsinh, $trangthai)
    {
        if (!in_array($trangthai, [0, 1], true)) {
            return false;
        }

        $sql = "UPDATE hocsinh
                SET trangthaiduthi = ?
                WHERE mahocsinh = ?";

        $stmt = $this->conn->prepare($sql);
        $stmt->bind_param("ii", $trangthai, $mahocsinh);

        $success = $stmt->execute()
            && $stmt->affected_rows >= 0;

        $stmt->close();

        return $success;
    }

    public function kiemtraDKDuThi($manguoidung){
        $sql = "SELECT * FROM hocsinh WHERE manguoidung = ?";
        $stmt = $this->conn->prepare($sql);
        $stmt->bind_param("i", $manguoidung);
        $stmt->execute();
        $result = $stmt->get_result();
        return $result->fetch_assoc();
    }

    public function layHanDangKyNguyenVong($makythi)
    {
        $makythi = (int) $makythi;

        $stmt = $this->conn->prepare(
            "SELECT hanchotdangkynguyenvong
             FROM kythituyensinh
             WHERE makythi = ?
             LIMIT 1"
        );
        if (!$stmt) {
            return null;
        }

        $stmt->bind_param("i", $makythi);
        $stmt->execute();
        $result = $stmt->get_result();
        $row = $result ? $result->fetch_assoc() : null;
        $stmt->close();

        return $row['hanchotdangkynguyenvong'] ?? null;
    }

    public function getDanhSachTruong()
    {
        $sql = "SELECT matruong, tentruong
                FROM truonghoc
                WHERE loaitruong = 'THPT'
                ORDER BY tentruong ASC";

        $result = $this->conn->query($sql);

        $danhSachTruong = [];

        while ($row = $result->fetch_assoc()) {
            $danhSachTruong[] = $row;
        }

        return $danhSachTruong;
    }

    
    public function kiemTraNguyenVong($maHocSinh, $thuTu)
    {
        $sql = "SELECT manguyenvong, matruong
                FROM nguyenvong
                WHERE mahocsinh = ?
                AND thutunguyenvong = ?
                LIMIT 1";

        $stmt = $this->conn->prepare($sql);
        $stmt->bind_param("ii", $maHocSinh, $thuTu);
        $stmt->execute();

        $result = $stmt->get_result();
        $nguyenVong = $result->fetch_assoc();

        $stmt->close();

        return $nguyenVong;
    }

    
    public function themNguyenVong($maHocSinh, $thuTu, $maTruong)
    {
        $sql = "INSERT INTO nguyenvong
                (mahocsinh, thutunguyenvong, matruong)
                VALUES (?, ?, ?)";

        $stmt = $this->conn->prepare($sql);
        $stmt->bind_param("iii", $maHocSinh, $thuTu, $maTruong);

        $ketQua = $stmt->execute();

        $stmt->close();

        return $ketQua;
    }
    
    public function capNhatNguyenVong($maNguyenVong, $maTruong)
    {
        $sql = "UPDATE nguyenvong
                SET matruong = ?
                WHERE manguyenvong = ?";

        $stmt = $this->conn->prepare($sql);
        $stmt->bind_param("ii", $maTruong, $maNguyenVong);

        $ketQua = $stmt->execute();

        $stmt->close();

        return $ketQua;
    }

    public function xoaNguyenVong($maNguyenVong)
    {
        $sql = "DELETE FROM nguyenvong
                WHERE manguyenvong = ?";

        $stmt = $this->conn->prepare($sql);
        $stmt->bind_param("i", $maNguyenVong);

        $ketQua = $stmt->execute();

        $stmt->close();

        return $ketQua;
    }

}
?>