<?php

namespace TraCuuKetQua;

use mysqli;
use Throwable;

require_once __DIR__ . '/hocSinhModel.php';
require_once __DIR__ . '/baiThiModel.php';
require_once __DIR__ . '/ketQuaXetTuyenModel.php';

/**
 * CtrlTraCuu trong sequence diagram "Tra cứu kết quả thi/trúng tuyển" (Actor: Học sinh).
 */
class CtrlTraCuu
{
    public const TB_KHONG_TIM_THAY = 'Không tìm thấy số báo danh tương ứng';
    /** Giải thích thêm cho người dùng (không tiết lộ SBD đó có tồn tại hay thuộc người khác hay không). */
    public const CT_KHONG_TIM_THAY = [
        'Số báo danh bạn nhập không đúng hoặc không thuộc tài khoản đang đăng nhập.',
        'Vui lòng kiểm tra lại số báo danh được cấp cho bạn rồi nhập lại.',
    ];
    public const TB_CHUA_CONG_BO   = 'Chưa công bố kết quả xét tuyển';
    public const TB_LOI_TAI        = 'Không thể tải dữ liệu tra cứu, vui lòng thử lại';

    private HocSinhModel $hocSinh;
    private BaiThiModel $baiThi;
    private KetQuaXetTuyenModel $ketQua;

    public function __construct(mysqli $conn)
    {
        $this->hocSinh = new HocSinhModel($conn);
        $this->baiThi  = new BaiThiModel($conn);
        $this->ketQua  = new KetQuaXetTuyenModel($conn);
    }

    /**
     * traCuu(soBaoDanh): trangThai =
     *   khong_tim_thay | loi_he_thong | ok
     * Khi ok:
     *   hocSinh {hoten, sobaodanh, ngaysinh}
     *   diemThi {congBo: bool, ds: [...], thongBao?}
     *   xetTuyen {congBo: bool, thongBao?, ketQua?: trung_tuyen|khong_trung_tuyen|khong_dang_ky, truong, diaChi, nv, daXacNhan, tongDiem}
     */
    public function traCuu(int $maNguoiDung, string $soBaoDanh): array
    {
        $soBaoDanh = trim($soBaoDanh);
        // Định dạng không hợp lệ cũng coi như không tìm thấy (luồng 4.1) - không cần chạm CSDL.
        if (!preg_match('/^[A-Za-z0-9]{1,20}$/', $soBaoDanh)) {
            return ['trangThai' => 'khong_tim_thay', 'thongBao' => self::TB_KHONG_TIM_THAY, 'chiTiet' => self::CT_KHONG_TIM_THAY];
        }
        try {
            $hs = $this->hocSinh->kiemTraSBDThuocTaiKhoan($soBaoDanh, $maNguoiDung);
            if ($hs === null) {
                return ['trangThai' => 'khong_tim_thay', 'thongBao' => self::TB_KHONG_TIM_THAY, 'chiTiet' => self::CT_KHONG_TIM_THAY];
            }
            $maHS = (int) $hs['mahocsinh'];
            $maKy = (int) $hs['makythi'];

            $out = [
                'trangThai' => 'ok',
                'hocSinh'   => [
                    'hoten'     => trim($hs['ho'] . ' ' . $hs['ten']),
                    'sobaodanh' => $hs['sobaodanh'],
                    'ngaysinh'  => $hs['ngaysinh'],
                ],
                'diemThi'   => ['congBo' => false, 'ds' => []],
                'xetTuyen'  => ['congBo' => false, 'thongBao' => self::TB_CHUA_CONG_BO],
            ];

            if (!$this->baiThi->daCongBoDiemThi($maKy)) {
                $out['diemThi']['thongBao'] = 'Chưa công bố điểm thi';
                return $out;
            }
            $out['diemThi'] = ['congBo' => true, 'ds' => $this->baiThi->layDiemTheoMon($maHS, $maKy)];

            if ($this->ketQua->daCongBoKetQua($maKy)) {
                $kq = $this->ketQua->layKetQuaXetTuyen($maHS);
                $kq['congBo']   = true;
                $kq['tongDiem'] = $hs['tongdiem'];
                $out['xetTuyen'] = $kq;
            }
            return $out;
        } catch (Throwable $e) {
            error_log('[TraCuuKetQua] ' . $e->getMessage());
            return ['trangThai' => 'loi_he_thong', 'thongBao' => self::TB_LOI_TAI];
        }
    }
}
