<?php

namespace TraCuuKetQua;

use mysqli;

/** KetQuaXetTuyen trong sequence: kết quả xét tuyển (bảng nguyenvong + điểm chuẩn đã công bố). */
class KetQuaXetTuyenModel
{
    public function __construct(private mysqli $conn)
    {
    }

    /**
     * Kết quả xét tuyển của kỳ được xem là đã công bố khi kỳ có dòng điểm chuẩn
     * và TẤT CẢ đều ở trạng thái DA_CONG_BO (cùng quy ước với use case Công bố điểm chuẩn).
     */
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

    /** layKetQuaXetTuyen(): nguyện vọng trúng tuyển (DAU hoặc đã xác nhận nhập học) nếu có. */
    public function layKetQuaXetTuyen(int $maHocSinh): array
    {
        $stmt = $this->conn->prepare(
            "SELECT nv.thutunguyenvong, nv.trangthaixettuyen, t.tentruong, t.diachi
             FROM nguyenvong nv JOIN truonghoc t ON t.matruong = nv.matruong
             WHERE nv.mahocsinh = ? AND nv.trangthaixettuyen IN ('DAU', 'XAC_NHAN_NHAP_HOC')
             ORDER BY nv.thutunguyenvong LIMIT 1"
        );
        $stmt->bind_param('i', $maHocSinh);
        $stmt->execute();
        $trung = $stmt->get_result()->fetch_assoc();
        $stmt->close();

        $stmt = $this->conn->prepare("SELECT COUNT(*) AS n FROM nguyenvong WHERE mahocsinh = ?");
        $stmt->bind_param('i', $maHocSinh);
        $stmt->execute();
        $soNV = (int) $stmt->get_result()->fetch_assoc()['n'];
        $stmt->close();

        if ($trung) {
            return [
                'ketQua'   => 'trung_tuyen',
                'truong'   => $trung['tentruong'],
                'diaChi'   => $trung['diachi'],
                'nv'       => (int) $trung['thutunguyenvong'],
                'daXacNhan' => $trung['trangthaixettuyen'] === 'XAC_NHAN_NHAP_HOC',
            ];
        }
        return ['ketQua' => $soNV === 0 ? 'khong_dang_ky' : 'khong_trung_tuyen'];
    }
}
