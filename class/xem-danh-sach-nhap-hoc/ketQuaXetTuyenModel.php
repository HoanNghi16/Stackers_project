<?php

namespace XemDanhSachNhapHoc;

use mysqli;

/**
 * KetQuaXetTuyen trong sequence: học sinh đã xác nhận nhập học vào trường.
 * Gộp thông tin HocSinh (họ tên, số báo danh, điểm xét tuyển) và NguyenVong (thứ tự nguyện vọng trúng tuyển)
 * trong một truy vấn để tránh gọi lặp theo từng học sinh.
 */
class KetQuaXetTuyenModel
{
    private const SELECT = "SELECT hs.mahocsinh, CONCAT(hn.ho, ' ', hn.ten) AS hoten, hs.sobaodanh,
                                   hs.tongdiem AS diemxettuyen, nv.thutunguyenvong
                            FROM nguyenvong nv
                            JOIN hocsinh hs        ON hs.mahocsinh = nv.mahocsinh
                            JOIN hosonguoidung hn  ON hn.manguoidung = hs.manguoidung
                            WHERE nv.matruong = ? AND hs.makythi = ? AND nv.trangthaixettuyen = 'XAC_NHAN_NHAP_HOC'";
    private const ORDER = " ORDER BY hs.tongdiem DESC, hn.ten, hn.ho, hs.mahocsinh";

    public function __construct(private mysqli $conn)
    {
    }

    /** layDanhSach() / layToanBoDanhSach(): toàn bộ học sinh đã xác nhận nhập học. */
    public function layDanhSach(int $maTruong, int $maKy): array
    {
        return $this->truyVan($maTruong, $maKy, null, '');
    }

    /** locTheoDieuKien() + timTheoTuKhoa(): lọc theo thứ tự nguyện vọng và/hoặc từ khóa (họ tên, số báo danh). */
    public function locTheoDieuKien(int $maTruong, int $maKy, ?int $thuTuNguyenVong, string $tuKhoa): array
    {
        return $this->truyVan($maTruong, $maKy, $thuTuNguyenVong, $tuKhoa);
    }

    private function truyVan(int $maTruong, int $maKy, ?int $thuTu, string $tuKhoa): array
    {
        $sql    = self::SELECT;
        $kieu   = 'ii';
        $thamSo = [$maTruong, $maKy];
        if ($thuTu !== null) {
            $sql    .= " AND nv.thutunguyenvong = ?";
            $kieu   .= 'i';
            $thamSo[] = $thuTu;
        }
        if ($tuKhoa !== '') {
            $mau     = '%' . addcslashes($tuKhoa, '\\%_') . '%';
            $sql    .= " AND (CONCAT(hn.ho, ' ', hn.ten) LIKE ? OR hs.sobaodanh LIKE ?)";
            $kieu   .= 'ss';
            array_push($thamSo, $mau, $mau);
        }
        $stmt = $this->conn->prepare($sql . self::ORDER);
        $stmt->bind_param($kieu, ...$thamSo);
        $stmt->execute();
        $rows = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
        $stmt->close();
        return $rows;
    }
}
