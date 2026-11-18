<?php
namespace XetTuyen;

use mysqli;
use RuntimeException;
use Throwable;

class KetQuaXetTuyenModel
{
    private mysqli $conn;

    public function __construct(mysqli $conn)
    {
        $this->conn = $conn;
    }

    // LƯU TẠM
    // Chưa có nơi lưu bản nháp trong database hiện tại.
    public function luuTam(int $maKy, array $ketQua): void
    {
        if ($maKy <= 0 || empty($ketQua)) {
            throw new RuntimeException(
                'Không có kết quả để lưu tạm.'
            );
        }

        throw new RuntimeException(
            'Chưa có nơi lưu bản nháp. Chưa lưu tạm.'
        );
    }

    // XÁC NHẬN KẾT QUẢ XÉT TUYỂN
    
public function xacNhan(int $maKy, array $ketQua, array $diemChuan = []): void
{
    if ($maKy <= 0 || empty($ketQua)) {
        throw new RuntimeException('Không có kết quả xét tuyển.');
    }

    $this->conn->begin_transaction();

    try {
        $kiemTra = $this->conn->prepare("
            SELECT nv.thutunguyenvong,
                   nv.matruong,
                   nv.trangthaixettuyen
            FROM nguyenvong nv
            INNER JOIN hocsinh hs
                ON hs.mahocsinh = nv.mahocsinh
            WHERE hs.makythi = ?
              AND nv.mahocsinh = ?
            FOR UPDATE
        ");

        $capNhat = $this->conn->prepare("
            UPDATE nguyenvong
            SET trangthaixettuyen = ?
            WHERE mahocsinh = ?
              AND thutunguyenvong = ?
              AND trangthaixettuyen = 'DANG_XET'
        ");

        $soCapNhat = 0;
        $soTongDiem = 0;

        // Lưu tổng điểm xét tuyển (tính từ điểm thi) vào hocsinh.tongdiem
        $luuTong = $this->conn->prepare("
            UPDATE hocsinh SET tongdiem = ?
            WHERE mahocsinh = ? AND makythi = ?
              AND (tongdiem IS NULL OR tongdiem <> ?)
        ");
        $daXuLy = [];

        foreach ($ketQua as $hs) {
            $maHS = (int)($hs['mahocsinh'] ?? 0);
            $nvDau = $hs['nvtrungtuyen'] ?? null;
            $nvDau = $nvDau === null ? null : (int)$nvDau;

            if ($maHS <= 0 || isset($daXuLy[$maHS])) {
                throw new RuntimeException('Mã học sinh không hợp lệ hoặc bị trùng.');
            }

            $daXuLy[$maHS] = true;

            $kiemTra->bind_param('ii', $maKy, $maHS);
            $kiemTra->execute();
            $dsNV = $kiemTra->get_result()->fetch_all(MYSQLI_ASSOC);

            if (!$dsNV) {
                throw new RuntimeException("Không tìm thấy nguyện vọng học sinh $maHS.");
            }

            $duKien = [];
            $hienTai = [];
            $nvs = $hs['nguyenvong'] ?? [];

            foreach ($dsNV as $nv) {
                $stt = (int)$nv['thutunguyenvong'];

                if (!isset($nvs[$stt]) ||
                    (int)$nvs[$stt]['matruong'] !== (int)$nv['matruong']) {
                    throw new RuntimeException(
                        "Dữ liệu nguyện vọng học sinh $maHS không khớp."
                    );
                }

                $duKien[$stt] =
                    ($nvDau !== null && $stt === $nvDau)
                    ? 'DAU'
                    : 'ROT';

                $hienTai[$stt] = $nv['trangthaixettuyen'];
            }

            if (count($nvs) !== count($dsNV) ||
                ($nvDau !== null && !isset($duKien[$nvDau]))) {
                throw new RuntimeException(
                    "Danh sách nguyện vọng học sinh $maHS không hợp lệ."
                );
            }

            if (isset($hs['tongdiem']) && is_numeric($hs['tongdiem'])) {
                $tong = number_format((float)$hs['tongdiem'], 2, '.', '');
                $luuTong->bind_param('siis', $tong, $maHS, $maKy, $tong);
                $luuTong->execute();
                $soTongDiem += max(0, $luuTong->affected_rows);
            }

            // Đã xác nhận đúng thì không cập nhật lại.
            if ($hienTai === $duKien) {
                continue;
            }

            // Không ghi đè kết quả đã lưu trước đó.
            foreach ($hienTai as $trangThai) {
                if ($trangThai !== 'DANG_XET') {
                    throw new RuntimeException(
                        "Học sinh $maHS có trạng thái cũ không khớp."
                    );
                }
            }

            foreach ($duKien as $stt => $trangThai) {
                $capNhat->bind_param(
                    'sii',
                    $trangThai,
                    $maHS,
                    $stt
                );
                $capNhat->execute();

                if ($capNhat->affected_rows !== 1) {
                    throw new RuntimeException(
                        "Không cập nhật được học sinh $maHS, NV$stt."
                    );
                }

                $soCapNhat++;
            }
        }

        $kiemTra->close();
        $capNhat->close();

        // Lưu điểm chuẩn trong cùng transaction với kết quả nguyện vọng.
        $soDiemChuan = $this->luuDiemChuan($maKy, $diemChuan);

        // Chỉ báo "đã xác nhận" khi cả kết quả lẫn điểm chuẩn đều không đổi.
        // (Kỳ đã xác nhận từ trước nhưng chưa có điểm chuẩn -> bấm Xác nhận
        //  lại sẽ bổ sung điểm chuẩn.)
        $luuTong->close();

        if ($soCapNhat === 0 && $soDiemChuan === 0 && $soTongDiem === 0) {
            throw new RuntimeException(
                'Kết quả đã được xác nhận trước đó.'
            );
        }

        $this->conn->commit();

    } catch (Throwable $e) {
        $this->conn->rollback();
        throw $e;
    }
}

    /**
     * Ghi điểm chuẩn từng trường vào bảng diemchuan (thêm mới hoặc cập nhật).
     * Không ghi đè điểm chuẩn đã công bố. Phải gọi bên trong transaction.
     *
     * @return int số dòng thực sự thêm/sửa
     */
    private function luuDiemChuan(int $maKy, array $diemChuan): int
    {
        if (!$diemChuan) {
            throw new RuntimeException('Không có dữ liệu điểm chuẩn để lưu.');
        }

        $fmt = static fn($v) => ($v === null || $v === '')
            ? null
            : number_format((float)$v, 2, '.', '');

        $tim = $this->conn->prepare("
            SELECT *
            FROM diemchuan
            WHERE makythi = ? AND matruong = ?
            FOR UPDATE
        ");
        $them = $this->conn->prepare("
            INSERT INTO diemchuan (makythi, matruong, diemnv1, diemnv2, diemnv3)
            VALUES (?, ?, ?, ?, ?)
        ");
        $sua = $this->conn->prepare("
            UPDATE diemchuan
            SET diemnv1 = ?, diemnv2 = ?, diemnv3 = ?
            WHERE makythi = ? AND matruong = ?
        ");

        $soThayDoi = 0;
        $daXuLy = [];

        try {
            foreach ($diemChuan as $dc) {
                $maTruong = (int)($dc['matruong'] ?? 0);
                if ($maTruong <= 0 || isset($daXuLy[$maTruong])) {
                    throw new RuntimeException('Mã trường trong điểm chuẩn không hợp lệ hoặc bị trùng.');
                }
                $daXuLy[$maTruong] = true;
                $ten = $dc['tentruong'] ?? ('Mã ' . $maTruong);

                $nv1 = $fmt($dc['diemnv1'] ?? null);
                $nv2 = $fmt($dc['diemnv2'] ?? null);
                $nv3 = $fmt($dc['diemnv3'] ?? null);

                // Cột diemnv1..3 là DECIMAL(4,2): chỉ chứa 0.00 -> 99.99
                foreach ([$nv1, $nv2, $nv3] as $d) {
                    if ($d !== null && ((float)$d < 0 || (float)$d > 99.99)) {
                        throw new RuntimeException("Điểm chuẩn trường $ten ($d) vượt giới hạn cột DECIMAL(4,2).");
                    }
                }

                $tim->bind_param('ii', $maKy, $maTruong);
                $tim->execute();
                $cu = $tim->get_result()->fetch_all(MYSQLI_ASSOC);

                if (count($cu) > 1) {
                    throw new RuntimeException("Trường $ten có nhiều dòng điểm chuẩn trong cùng kỳ.");
                }

                if (!$cu) {
                    $them->bind_param('iisss', $maKy, $maTruong, $nv1, $nv2, $nv3);
                    $them->execute();
                    if ($them->affected_rows !== 1) {
                        throw new RuntimeException("Không thêm được điểm chuẩn trường $ten.");
                    }
                    $soThayDoi++;
                    continue;
                }

                $cu = $cu[0];
                if ($fmt($cu['diemnv1']) === $nv1
                    && $fmt($cu['diemnv2']) === $nv2
                    && $fmt($cu['diemnv3']) === $nv3) {
                    continue; // không đổi
                }

                // Cột trangthaicongbo là tùy chọn (xem file SQL kèm theo).
                if (($cu['trangthaicongbo'] ?? '') === 'DA_CONG_BO') {
                    throw new RuntimeException("Điểm chuẩn trường $ten đã công bố, không thể ghi đè.");
                }

                $sua->bind_param('sssii', $nv1, $nv2, $nv3, $maKy, $maTruong);
                $sua->execute();
                $soThayDoi++;

                // Điểm thay đổi -> bảng "Chờ công bố" cũ không còn đúng,
                // đưa về "Chưa công bố" để Sở kiểm tra lại.
                if (array_key_exists('trangthaicongbo', $cu)
                    && $cu['trangthaicongbo'] !== 'CHUA_CONG_BO') {
                    $datLai = $this->conn->prepare("
                        UPDATE diemchuan SET trangthaicongbo = 'CHUA_CONG_BO'
                        WHERE makythi = ? AND matruong = ?
                    ");
                    $datLai->bind_param('ii', $maKy, $maTruong);
                    $datLai->execute();
                    $datLai->close();
                }
            }
        } finally {
            $tim->close();
            $them->close();
            $sua->close();
        }

        return $soThayDoi;
    }

}
