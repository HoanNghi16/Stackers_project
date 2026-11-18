<?php
namespace NhapDiemThi;

use mysqli;
use Throwable;

require_once __DIR__ . '/nhapDiemThiModel.php';

/** Lỗi nghiệp vụ: thông báo được phép hiển thị trực tiếp cho người dùng. */
if (!class_exists(__NAMESPACE__ . '\NghiepVuException', false)) {
    class NghiepVuException extends \RuntimeException
    {
    }
}

/**
 * Controller: Nhập điểm thi (actor: Sở GD&ĐT).
 *
 * Luồng chính: yeuCauForm -> taiBaiThi -> kiemTraDiem -> luuDiem
 * 4.1  Không có môn thi được phép nhập điểm
 * 6.1  Không có thí sinh / bài thi phù hợp
 * 10.1 Điểm không hợp lệ -> không cho lưu
 * 14.1 Lỗi lưu -> rollback, báo "không thành công"
 */
class CtrlNhapDiemThi
{
    public const NAM_TUYEN_SINH = 2026;
    public const DIEM_TOI_DA    = 10.0;
    public const BUOC_DIEM      = 0.25;   // thang 10, làm tròn đến 0.25
    public const SO_DONG_TRANG  = 3;

    /**
     * true  = kỳ đã xác nhận kết quả xét tuyển thì KHÓA nhập/sửa điểm.
     * false = chỉ hiện cảnh báo, vẫn cho sửa.
     */
    public const KHOA_KHI_DA_XET_TUYEN = true;

    public const THONG_BAO_DA_XET = 'Kỳ này đã xác nhận kết quả xét tuyển. Sửa điểm lúc này sẽ không làm thay đổi kết quả đã xác nhận. '
        . 'Muốn sửa điểm, hãy đặt lại kết quả xét tuyển (đưa nguyện vọng về "Đang xét") rồi xét tuyển lại.';

    private NhapDiemThiModel $model;

    public function __construct(mysqli $conn)
    {
        $this->model = new NhapDiemThiModel($conn);
    }

    /** Bước 1-4: kỳ thi + danh sách môn thi và trạng thái nhập điểm. */
    public function yeuCauForm(): array
    {
        try {
            $ky = $this->model->layKyTheoNam(self::NAM_TUYEN_SINH);
            if (!$ky) {
                throw new NghiepVuException('Không tìm thấy kỳ tuyển sinh năm ' . self::NAM_TUYEN_SINH . '.');
            }

            $dsMon = $this->model->danhSachMonThi((int)$ky['makythi']);
            $coBaiThi = array_filter($dsMon, static fn($m) => $m['tong'] > 0);
            if (!$coBaiThi) {
                throw new NghiepVuException('Không có môn thi được phép nhập điểm.'); // 4.1
            }

            $daXetTuyen = $this->model->daXacNhanXetTuyen((int)$ky['makythi']);

            return [
                'thanhCong'  => true,
                'thongBao'   => $daXetTuyen ? self::THONG_BAO_DA_XET : '',
                'ky'         => $ky,
                'dsMon'      => $dsMon,
                'khoaNhap'   => $daXetTuyen && self::KHOA_KHI_DA_XET_TUYEN,
            ];
        } catch (NghiepVuException $e) {
            return ['thanhCong' => false, 'thongBao' => $e->getMessage(), 'ky' => null, 'dsMon' => [], 'khoaNhap' => false];
        } catch (Throwable $e) {
            error_log('[NhapDiemThi:Form] ' . $e->getMessage());
            return ['thanhCong' => false, 'thongBao' => 'Không tải được dữ liệu nhập điểm.', 'ky' => null, 'dsMon' => [], 'khoaNhap' => false];
        }
    }

    /** Bước 5-6 (và 6.1 nhập nhiều thí sinh với bộ lọc "chưa nhập"). */
    public function taiBaiThi(int $maKy, int $maMon, string $loc, string $tim, int $trang): array
    {
        try {
            $loc = in_array($loc, ['tatca', 'chuanhap', 'danhap', 'vang'], true) ? $loc : 'chuanhap';
            $tim = mb_substr(trim($tim), 0, 50);
            $trang = max(1, $trang);

            $kq = $this->model->danhSachBaiThi(
                $maKy, $maMon, $loc, $tim,
                ($trang - 1) * self::SO_DONG_TRANG, self::SO_DONG_TRANG
            );
            $soTrang = max(1, (int)ceil($kq['tong'] / self::SO_DONG_TRANG));

            return [
                'thanhCong' => true,
                'thongBao'  => $kq['tong'] === 0
                    ? 'Không có thí sinh hoặc bài thi phù hợp để nhập điểm.' // 6.1
                    : '',
                'dsBaiThi'  => $kq['ds'],
                'tong'      => $kq['tong'],
                'trang'     => min($trang, $soTrang),
                'soTrang'   => $soTrang,
                'loc'       => $loc,
                'tim'       => $tim,
            ];
        } catch (Throwable $e) {
            error_log('[NhapDiemThi:BaiThi] ' . $e->getMessage());
            return ['thanhCong' => false, 'thongBao' => 'Không tải được danh sách bài thi.',
                    'dsBaiThi' => [], 'tong' => 0, 'trang' => 1, 'soTrang' => 1, 'loc' => $loc, 'tim' => $tim];
        }
    }

    /**
     * Bước 9-10 (+10.1): kiểm tra điểm vừa nhập.
     * @param array $nhap [mabaithi => chuỗi người dùng gõ]
     * @return array{loi: array<int,string>, thayDoi: array<int,array>, hopLe: array<int,string>}
     *   hopLe: chỉ các bài có điểm MỚI khác điểm hiện tại, dạng 'x.xx'
     */
    public function kiemTraDiem(int $maKy, int $maMon, array $nhap): array
    {
        $loi = [];
        $hopLe = [];
        $thayDoi = [];

        $nhap = array_filter($nhap, static fn($v) => trim((string)$v) !== '');
        $baiThi = $this->model->layBaiThiTheoMa($maKy, $maMon, array_keys($nhap));

        foreach ($nhap as $ma => $chuoi) {
            $ma = (int)$ma;
            if (!isset($baiThi[$ma])) {
                $loi[$ma] = 'Bài thi không thuộc môn đang nhập.';
                continue;
            }
            $bt = $baiThi[$ma];
            if ((int)$bt['vang'] === 1) {
                $loi[$ma] = 'Thí sinh vắng thi.';
                continue;
            }

            $diem = $this->chuanHoaDiem((string)$chuoi);
            if ($diem === null) {
                $loi[$ma] = 'Điểm phải từ 0 đến ' . self::DIEM_TOI_DA . ', bước ' . self::BUOC_DIEM . '.';
                continue;
            }

            $cu = $bt['diem'] === null ? null : number_format((float)$bt['diem'], 2, '.', '');
            if ($cu === $diem) {
                continue; // không đổi
            }

            $hopLe[$ma] = $diem;
            $thayDoi[$ma] = [
                'mabaithi'  => $ma,
                'sobaodanh' => $bt['sobaodanh'],
                'sophach'   => $bt['sophach'],
                'hoten'     => trim(($bt['ho'] ?? '') . ' ' . ($bt['ten'] ?? '')),
                'diemcu'    => $cu,
                'diemmoi'   => $diem,
            ];
        }

        return ['loi' => $loi, 'hopLe' => $hopLe, 'thayDoi' => $thayDoi];
    }

    /** Bước 13-17: xác nhận lưu. Kiểm tra lại toàn bộ trước khi ghi. */
    public function luuDiem(int $maKy, int $maMon, string $tenMon, array $nhap, ?int $nguoiNhap): array
    {
        try {
            if (self::KHOA_KHI_DA_XET_TUYEN && $this->model->daXacNhanXetTuyen($maKy)) {
                throw new NghiepVuException(self::THONG_BAO_DA_XET);
            }
            $kt = $this->kiemTraDiem($maKy, $maMon, $nhap);
            if ($kt['loi']) {
                throw new NghiepVuException('Điểm thi không hợp lệ, vui lòng kiểm tra và nhập lại.');
            }
            if (!$kt['hopLe']) {
                throw new NghiepVuException('Không có điểm nào thay đổi để lưu.');
            }
            $so = $this->model->luuDiem($maKy, $maMon, $tenMon, $kt['hopLe'], $nguoiNhap);
            return ['thanhCong' => true, 'thongBao' => "Nhập điểm thi thành công ($so bài thi)."];
        } catch (NghiepVuException $e) {
            return ['thanhCong' => false, 'thongBao' => $e->getMessage()];
        } catch (Throwable $e) {
            error_log('[NhapDiemThi:Luu] ' . $e->getMessage());
            return ['thanhCong' => false, 'thongBao' => 'Lưu điểm thi không thành công, vui lòng thử lại.'];
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
