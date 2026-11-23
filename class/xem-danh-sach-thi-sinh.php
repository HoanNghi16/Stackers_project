<?php
/**
 * Model danh sach thi sinh - dong bo voi cau SQL da kiem tra tren phpMyAdmin.
 * Quan he: hocsinh.manguoidung = hosonguoidung.manguoidung.
 * Thu tu: hs.mahocsinh ASC, ho.mahoso ASC (GIỐNG phpMyAdmin).
 */
class DanhSachThiSinhModel
{
    public function __construct(private mysqli $conn) {}

    public function danhSachTruong(): array
    {
        $sql = "SELECT matruong, tentruong FROM truonghoc WHERE loaitruong = 'THCS' ORDER BY tentruong";
        return $this->conn->query($sql)->fetch_all(MYSQLI_ASSOC);
    }

    // Khong dung MAX(mahoso): JOIN TRUC TIEP nhu cau SQL da doi chieu.
    private function bangLienKet(): string
    {
        return "
            FROM hocsinh hs
            LEFT JOIN hosonguoidung ho
                ON ho.manguoidung = hs.manguoidung
            LEFT JOIN truonghoc th
                ON th.matruong = hs.matruongdanghoc
            LEFT JOIN kythituyensinh ky
                ON ky.makythi = hs.makythi
        ";
    }

    // TRIM tung phan de tranh 2 khoang trang khi cot ho co dau cach cuoi.
    private function hoTenSql(): string
    {
        return "NULLIF(TRIM(CONCAT_WS(' ',
            NULLIF(TRIM(ho.ho), ''),
            NULLIF(TRIM(ho.ten), '')
        )), '')";
    }

    public function timKiem(string $tuKhoa, string $maTruong, int $trang = 1): array
    {
        $tuKhoa = trim($tuKhoa);
        $maTruong = trim($maTruong);
        $tim = '%' . strtr($tuKhoa, ['!' => '!!', '%' => '!%', '_' => '!_']) . '%';
        $from = $this->bangLienKet();
        $hoTen = $this->hoTenSql();
        $where = "
            WHERE (
                ? = ''
                OR {$hoTen} LIKE ? ESCAPE '!'
                OR CAST(hs.mahocsinh AS CHAR) = ?
                OR hs.sobaodanh = ?
            )
              AND (? = '' OR hs.matruongdanghoc = ?)
        ";

        // COUNT(*) giong so dong tra ve tu JOIN truc tiep. Neu trung ho so,
        // mot hoc sinh co the co nhieu dong; xem cot sohoso de doi chieu.
        $stmt = $this->conn->prepare("SELECT COUNT(*) AS tong {$from} {$where}");
        $stmt->bind_param('ssssss', $tuKhoa, $tim, $tuKhoa, $tuKhoa, $maTruong, $maTruong);
        $stmt->execute();
        $tong = (int) ($stmt->get_result()->fetch_assoc()['tong'] ?? 0);
        $stmt->close();

        $soTrang = max(1, (int) ceil($tong / 25));
        $trang = min($soTrang, max(1, $trang));
        $batDau = ($trang - 1) * 25;

        $sql = "
            SELECT
                hs.mahocsinh,
                hs.manguoidung,
                ho.mahoso,
                ho.ho,
                ho.ten,
                {$hoTen} AS hoten,
                ho.ngaysinh,
                hs.sobaodanh,
                hs.trangthaiduthi,
                th.tentruong,
                ky.namtuyensinh,
                (SELECT COUNT(*) FROM hosonguoidung ho2
                 WHERE ho2.manguoidung = hs.manguoidung) AS sohoso
            {$from}
            {$where}
            ORDER BY hs.mahocsinh ASC, ho.mahoso ASC
            LIMIT 25 OFFSET ?
        ";
        $stmt = $this->conn->prepare($sql);
        $stmt->bind_param('ssssssi', $tuKhoa, $tim, $tuKhoa, $tuKhoa, $maTruong, $maTruong, $batDau);
        $stmt->execute();
        $rows = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
        $stmt->close();

        return compact('rows', 'tong', 'soTrang', 'trang', 'batDau');
    }

    public function chiTiet(int $ma): ?array
    {
        $from = $this->bangLienKet();
        $hoTen = $this->hoTenSql();
        $sql = "
            SELECT
                hs.mahocsinh,
                hs.manguoidung,
                ho.mahoso,
                ho.ho,
                ho.ten,
                {$hoTen} AS hoten,
                ho.ngaysinh,
                ho.diachi,
                hs.sobaodanh,
                hs.dantoc,
                hs.tongdiem,
                hs.trangthaiduthi,
                th.tentruong,
                ky.namtuyensinh,
                (SELECT COUNT(*) FROM hosonguoidung ho2
                 WHERE ho2.manguoidung = hs.manguoidung) AS sohoso
            {$from}
            WHERE hs.mahocsinh = ?
            ORDER BY ho.mahoso ASC
            LIMIT 1
        ";
        $stmt = $this->conn->prepare($sql);
        $stmt->bind_param('i', $ma);
        $stmt->execute();
        $row = $stmt->get_result()->fetch_assoc();
        $stmt->close();
        if (!$row) return null;

        $stmt = $this->conn->prepare("
            SELECT nv.thutunguyenvong, th.tentruong
            FROM nguyenvong nv
            LEFT JOIN truonghoc th ON th.matruong = nv.matruong
            WHERE nv.mahocsinh = ?
            ORDER BY nv.thutunguyenvong ASC
        ");
        $stmt->bind_param('i', $ma);
        $stmt->execute();
        $row['nguyenvong'] = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
        $stmt->close();
        return $row;
    }
}
