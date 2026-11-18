<?php
namespace NhapDiemPhucKhao;

use mysqli;
use Throwable;

require_once __DIR__ . '/phucKhaoModel.php';

/** Lỗi nghiệp vụ: thông báo được phép hiển thị trực tiếp cho người dùng. */
if (!class_exists(__NAMESPACE__ . '\NghiepVuException', false)) {
    class NghiepVuException extends \RuntimeException
    {
    }
}

/**
 * Controller: Nhập điểm phúc khảo (actor: Sở GD&ĐT).
 *
 * Luồng chính: yeuCauForm -> chonPhieu -> kiemTraDiem -> luuDiem
 * 4.1 (exception) Không có hồ sơ phúc khảo đủ điều kiện
 * 8.1 (alt)       Điểm phúc khảo khác điểm ban đầu -> hiện cả 2 để xác nhận
 * 8.1 (exception) Điểm phúc khảo không hợp lệ -> không cho lưu
 * 11.1            Hủy -> không lưu, điểm ban đầu giữ nguyên
 * 12.1            Lỗi lưu -> rollback, báo "không thành công"
 */
class CtrlNhapDiemPhucKhao
{
    public const NAM_TUYEN_SINH = 2026;
    public const DIEM_TOI_DA    = 10.0;
    public const BUOC_DIEM      = 0.25;

    /**
     * true  = kỳ đã xác nhận kết quả xét tuyển thì KHÓA nhập điểm phúc khảo.
     * false = chỉ hiện cảnh báo, vẫn cho nhập.
     */
    public const KHOA_KHI_DA_XET_TUYEN = true;

    public const THONG_BAO_DA_XET = 'Kỳ này đã xác nhận kết quả xét tuyển. Cập nhật điểm phúc khảo lúc này sẽ không làm thay đổi kết quả đã xác nhận. '
        . 'Muốn nhập, hãy đặt lại kết quả xét tuyển (đưa nguyện vọng về "Đang xét") rồi xét tuyển lại.';

    private PhucKhaoModel $model;

    public function __construct(mysqli $conn)
    {
        $this->model = new PhucKhaoModel($conn);
    }

    /** Bước 1-4: kỳ thi + danh sách phiếu phúc khảo. */
    public function yeuCauForm(): array
    {
        try {
            $ky = $this->model->layKyTheoNam(self::NAM_TUYEN_SINH);
            if (!$ky) {
                throw new NghiepVuException('Không tìm thấy kỳ tuyển sinh năm ' . self::NAM_TUYEN_SINH . '.');
            }
            $ds = $this->model->danhSachPhieu((int)$ky['makythi']);
            $canNhap = array_filter($ds, static fn($p) => !PhucKhaoModel::daKhoaPhieu((string)$p['trangthai']));

            $daXetTuyen = $this->model->daXacNhanXetTuyen((int)$ky['makythi']);

            return [
                'thanhCong' => true,
                'thongBao'  => $daXetTuyen
                    ? self::THONG_BAO_DA_XET
                    : ($canNhap ? '' : 'Không có hồ sơ phúc khảo đủ điều kiện để nhập điểm.'), // 4.1
                'ky'        => $ky,
                'dsPhieu'   => $ds,
                'khoaNhap'  => $daXetTuyen && self::KHOA_KHI_DA_XET_TUYEN,
            ];
        } catch (NghiepVuException $e) {
            return ['thanhCong' => false, 'thongBao' => $e->getMessage(), 'ky' => null, 'dsPhieu' => [], 'khoaNhap' => false];
        } catch (Throwable $e) {
            error_log('[PhucKhao:Form] ' . $e->getMessage());
            return ['thanhCong' => false, 'thongBao' => 'Không tải được danh sách phúc khảo.', 'ky' => null, 'dsPhieu' => [], 'khoaNhap' => false];
        }
    }

    /** Bước 5-6: thông tin phiếu + bài thi + điểm ban đầu. */
    public function chonPhieu(int $maKy, int $maPhieu): array
    {
        try {
            $phieu = $this->model->layPhieu($maKy, $maPhieu);
            if (!$phieu) {
                throw new NghiepVuException('Không tìm thấy phiếu phúc khảo.');
            }
            $baiThi = $this->model->baiThiCuaPhieu($maPhieu, (int)$phieu['mahocsinh']);
            return [
                'thanhCong' => true,
                'thongBao'  => $baiThi ? '' : 'Thí sinh chưa có bài thi nào để nhập điểm phúc khảo.',
                'phieu'     => $phieu,
                'baiThi'    => $baiThi,
                'daCapNhat' => PhucKhaoModel::daKhoaPhieu((string)$phieu['trangthai']),
            ];
        } catch (NghiepVuException $e) {
            return ['thanhCong' => false, 'thongBao' => $e->getMessage(), 'phieu' => null, 'baiThi' => [], 'daCapNhat' => false];
        } catch (Throwable $e) {
            error_log('[PhucKhao:Phieu] ' . $e->getMessage());
            return ['thanhCong' => false, 'thongBao' => 'Không tải được hồ sơ phúc khảo.', 'phieu' => null, 'baiThi' => [], 'daCapNhat' => false];
        }
    }

    /**
     * Bước 7-8: kiểm tra điểm phúc khảo vừa nhập.
     * Ô để trống = môn đó không phúc khảo, bỏ qua.
     * @return array{loi: array<int,string>, hopLe: array<int,string>, doiChieu: array<int,array>}
     */
    public function kiemTraDiem(int $maKy, int $maPhieu, array $nhap): array
    {
        $loi = [];
        $hopLe = [];
        $doiChieu = [];

        $phieu = $this->model->layPhieu($maKy, $maPhieu);
        if (!$phieu) {
            return ['loi' => [0 => 'Không tìm thấy phiếu phúc khảo.'], 'hopLe' => [], 'doiChieu' => []];
        }
        $baiThi = $this->model->baiThiCuaPhieu($maPhieu, (int)$phieu['mahocsinh']);

        foreach ($nhap as $ma => $chuoi) {
            $ma = (int)$ma;
            if (trim((string)$chuoi) === '') {
                continue;
            }
            if (!isset($baiThi[$ma])) {
                $loi[$ma] = 'Bài thi không thuộc phiếu phúc khảo.';
                continue;
            }
            $bt = $baiThi[$ma];
            if ((int)$bt['vang'] === 1) {
                $loi[$ma] = 'Thí sinh vắng thi.';
                continue;
            }
            $diem = $this->chuanHoaDiem((string)$chuoi);
            if ($diem === null) {
                $loi[$ma] = 'Điểm phải từ 0 đến 10, bước 0.25.';
                continue;
            }

            $cu = $bt['diem'] === null ? null : number_format((float)$bt['diem'], 2, '.', '');
            $hopLe[$ma] = $diem;
            $doiChieu[$ma] = [
                'mabaithi'  => $ma,
                'tenmonhoc' => $bt['tenmonhoc'],
                'sophach'   => $bt['sophach'],
                'diemcu'    => $cu,
                'diemmoi'   => $diem,
                'khac'      => $cu !== $diem,   // 8.1: khác điểm ban đầu
            ];
        }

        return ['loi' => $loi, 'hopLe' => $hopLe, 'doiChieu' => $doiChieu];
    }

    /** Bước 11-16: xác nhận và lưu. */
    public function luuDiem(int $maKy, int $maPhieu, array $nhap, ?int $nguoiNhap): array
    {
        try {
            if (self::KHOA_KHI_DA_XET_TUYEN && $this->model->daXacNhanXetTuyen($maKy)) {
                throw new NghiepVuException(self::THONG_BAO_DA_XET);
            }
            $kt = $this->kiemTraDiem($maKy, $maPhieu, $nhap);
            if ($kt['loi']) {
                throw new NghiepVuException('Điểm phúc khảo không hợp lệ, vui lòng kiểm tra lại.');
            }
            if (!$kt['hopLe']) {
                throw new NghiepVuException('Chưa nhập điểm phúc khảo cho môn nào.');
            }
            $so = $this->model->luuPhucKhao($maKy, $maPhieu, $kt['hopLe'], $nguoiNhap);
            return ['thanhCong' => true, 'thongBao' => "Nhập điểm phúc khảo thành công ($so môn)."];
        } catch (NghiepVuException $e) {
            return ['thanhCong' => false, 'thongBao' => $e->getMessage()];
        } catch (Throwable $e) {
            error_log('[PhucKhao:Luu] ' . $e->getMessage());
            return ['thanhCong' => false, 'thongBao' => 'Lưu điểm phúc khảo không thành công, vui lòng thử lại.'];
        }
    }

    /** Chuỗi -> 'x.xx' nếu hợp lệ (0..10, bội số 0.25), ngược lại null. Chấp nhận dấu phẩy. */
    public function chuanHoaDiem(string $chuoi): ?string
    {
        $chuoi = str_replace(',', '.', trim($chuoi));
        if (!preg_match('/^\d{1,2}(\.\d{1,2})?$/', $chuoi)) {
            return null;
        }
        $diem = (float)$chuoi;
        if ($diem < 0 || $diem > self::DIEM_TOI_DA) {
            return null;
        }
        $soBuoc = $diem / self::BUOC_DIEM;
        if (abs($soBuoc - round($soBuoc)) > 1e-9) {
            return null;
        }
        return number_format($diem, 2, '.', '');
    }
}
