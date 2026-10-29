<?php

namespace ThietLapCongThuc;

use mysqli;

require_once __DIR__ . '/nghiepVuException.php';

/**
 * Bảng tieuchuancongdiem (tiêu chuẩn cộng điểm ưu tiên / khuyến khích theo kỳ).
 * Lớp "TieuChuanCongDiem" trong sequence của use case "Thiết lập công thức tính điểm".
 */
class TieuChuanCongDiemModel extends \BaseModel
{
    protected string $tableName = 'tieuchuancongdiem';
    protected string $primaryKey = 'matieuchuan';
    protected array $fields = [
        'matieuchuan'    => 'int',
        'makythi'        => 'int',
        'loaitieuchuan'  => 'string',
        'diemcong'       => 'decimal',
        'dieukienapdung' => 'string',
    ];

    public function __construct(mysqli $conn)
    {
        parent::__construct($conn);
    }

    /**
     * layTieuChuanCongDiemTheoKy(kyHienTai)
     * Ưu tiên xếp trước, khuyến khích xếp sau; trong mỗi loại theo thứ tự tạo.
     *
     * @return array<int, array{matieuchuan:int|string, loaitieuchuan:string, dieukienapdung:string, diemcong:string}>
     */
    public function layTieuChuanCongDiemTheoKy(int $maKyThi): array
    {
        $stmt = $this->conn->prepare(
            "SELECT matieuchuan, loaitieuchuan, dieukienapdung, diemcong
             FROM tieuchuancongdiem
             WHERE makythi = ?
             ORDER BY (loaitieuchuan = 'UU_TIEN') DESC, matieuchuan"
        );
        $stmt->bind_param('i', $maKyThi);
        $stmt->execute();

        return $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    }

    /**
     * capNhatTieuChuanCongDiem(kyHienTai, dsTieuChuanCongDiem)
     *
     * Đồng bộ danh sách tiêu chuẩn của kỳ với danh sách gửi lên:
     *  - có "ma"  -> cập nhật (loại, điều kiện áp dụng, điểm cộng)
     *  - không có -> thêm mới
     *  - tiêu chuẩn của kỳ không còn trong danh sách -> xóa
     *
     * Vì hocsinh.matieuchuanuutien / matieuchuankhuyenkhich tham chiếu tới bảng này,
     * tiêu chuẩn đang có học sinh áp dụng sẽ KHÔNG bị xóa và cũng không được đổi
     * loại (tránh làm sai dữ liệu điểm đã gắn với học sinh): ném NghiepVuException
     * để controller rollback và báo lỗi rõ ràng cho người dùng.
     *
     * Không tự mở transaction: controller chịu trách nhiệm commit/rollback.
     *
     * @param array<int, array{ma:?int, loai:string, dieuKien:string, diemCong:float}> $dsTieuChuan
     * @throws NghiepVuException
     */
    public function capNhatTieuChuanCongDiem(int $maKyThi, array $dsTieuChuan): void
    {
        // Trạng thái hiện có của kỳ: [matieuchuan => dòng]
        $hienCo = [];
        foreach ($this->layTieuChuanCongDiemTheoKy($maKyThi) as $row) {
            $hienCo[(int) $row['matieuchuan']] = $row;
        }

        $loi = [];
        $canCapNhat = [];
        $canThem = [];
        $giuLai = [];

        foreach ($dsTieuChuan as $tc) {
            if ($tc['ma'] === null) {
                $canThem[] = $tc;
                continue;
            }

            $ma = (int) $tc['ma'];
            if (!isset($hienCo[$ma])) {
                throw new NghiepVuException(
                    'Có tiêu chuẩn không còn tồn tại (có thể đã bị thay đổi ở nơi khác). '
                    . 'Vui lòng tải lại trang rồi thiết lập lại.'
                );
            }

            $giuLai[$ma] = true;

            if ($hienCo[$ma]['loaitieuchuan'] !== $tc['loai']) {
                $soHS = $this->demHocSinhDangDung($ma);
                if ($soHS > 0) {
                    $loi[] = sprintf(
                        'Không thể đổi loại của tiêu chuẩn "%s" vì đang có %d học sinh áp dụng.',
                        $hienCo[$ma]['dieukienapdung'],
                        $soHS
                    );
                    continue;
                }
            }

            $canCapNhat[$ma] = $tc;
        }

        // Tiêu chuẩn bị xóa (có trong CSDL, không còn trong danh sách gửi lên)
        $canXoa = [];
        foreach ($hienCo as $ma => $row) {
            if (isset($giuLai[$ma])) {
                continue;
            }
            $soHS = $this->demHocSinhDangDung($ma);
            if ($soHS > 0) {
                $loi[] = sprintf(
                    'Không thể xóa tiêu chuẩn "%s" vì đang có %d học sinh áp dụng.',
                    $row['dieukienapdung'],
                    $soHS
                );
                continue;
            }
            $canXoa[] = $ma;
        }

        if (!empty($loi)) {
            throw new NghiepVuException(implode("\n", $loi));
        }

        // --- Ghi dữ liệu ---
        $update = $this->conn->prepare(
            "UPDATE tieuchuancongdiem
             SET loaitieuchuan = ?, dieukienapdung = ?, diemcong = ?
             WHERE matieuchuan = ? AND makythi = ?"
        );
        foreach ($canCapNhat as $ma => $tc) {
            $diem = number_format($tc['diemCong'], 2, '.', '');
            $update->bind_param('sssii', $tc['loai'], $tc['dieuKien'], $diem, $ma, $maKyThi);
            $update->execute();
        }

        $insert = $this->conn->prepare(
            "INSERT INTO tieuchuancongdiem (makythi, loaitieuchuan, diemcong, dieukienapdung)
             VALUES (?, ?, ?, ?)"
        );
        foreach ($canThem as $tc) {
            $diem = number_format($tc['diemCong'], 2, '.', '');
            $insert->bind_param('isss', $maKyThi, $tc['loai'], $diem, $tc['dieuKien']);
            $insert->execute();
        }

        $delete = $this->conn->prepare(
            "DELETE FROM tieuchuancongdiem WHERE matieuchuan = ? AND makythi = ?"
        );
        foreach ($canXoa as $ma) {
            $delete->bind_param('ii', $ma, $maKyThi);
            $delete->execute();
        }
    }

    /**
     * Số học sinh đang áp dụng tiêu chuẩn này (làm tiêu chuẩn ưu tiên hoặc khuyến khích).
     */
    private function demHocSinhDangDung(int $maTieuChuan): int
    {
        $stmt = $this->conn->prepare(
            "SELECT COUNT(*) AS so
             FROM hocsinh
             WHERE matieuchuanuutien = ? OR matieuchuankhuyenkhich = ?"
        );
        $stmt->bind_param('ii', $maTieuChuan, $maTieuChuan);
        $stmt->execute();

        return (int) $stmt->get_result()->fetch_assoc()['so'];
    }
}
