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
    
public function xacNhan(int $maKy, array $ketQua): void
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

        if ($soCapNhat === 0) {
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

}
