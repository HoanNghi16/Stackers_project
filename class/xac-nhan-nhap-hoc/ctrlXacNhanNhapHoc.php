<?php

namespace XacNhanNhapHoc;

use mysqli;
use Throwable;

require_once __DIR__ . '/nghiepVuException.php';
require_once __DIR__ . '/hocSinhKyModel.php';
require_once __DIR__ . '/ketQuaXetTuyenModel.php';
require_once __DIR__ . '/nguyenVongModel.php';
require_once __DIR__ . '/truongTHPTModel.php';

/**
 * CtrlXacNhanNhapHoc trong sequence diagram "Xác nhận nhập học" (Actor: Học sinh).
 */
class CtrlXacNhanNhapHoc
{
    public const TB_THANH_CONG = 'Xác nhận nhập học thành công';
    public const TB_LOI_HE_THONG = 'Xác nhận nhập học không thành công, vui lòng thử lại';

    private HocSinhKyModel $hocSinh;
    private KetQuaXetTuyenModel $ketQua;
    private NguyenVongModel $nguyenVong;
    private TruongTHPTModel $truong;

    public function __construct(private mysqli $conn)
    {
        $this->hocSinh    = new HocSinhKyModel($conn);
        $this->ketQua     = new KetQuaXetTuyenModel($conn);
        $this->nguyenVong = new NguyenVongModel($conn);
        $this->truong     = new TruongTHPTModel($conn);
    }

    /**
     * yeuCauThongTinXacNhan(): thông tin màn hình xác nhận (trường, nguyện vọng trúng tuyển, chi tiết trường).
     * trangThai:
     *   san_sang          - có thể bấm xác nhận
     *   da_xac_nhan       - đã xác nhận nhập học (hiển thị trường đã xác nhận)
     *   het_han           - quá hạn xác nhận
     *   khong_trung_tuyen - chưa công bố kết quả / không có nguyện vọng trúng tuyển / không có hồ sơ kỳ này
     *   loi_he_thong
     */
    public function yeuCauThongTinXacNhan(int $maNguoiDung): array
    {
        try {
            $hs = $this->hocSinh->layHocSinhTrongKy($maNguoiDung);
            if ($hs === null) {
                return ['trangThai' => 'khong_trung_tuyen', 'thongBao' => 'Bạn chưa có hồ sơ dự tuyển trong kỳ tuyển sinh hiện tại.'];
            }
            $maKy = (int) $hs['makythi'];
            if (!$this->ketQua->daCongBoKetQua($maKy)) {
                return ['trangThai' => 'khong_trung_tuyen', 'thongBao' => 'Chưa công bố kết quả xét tuyển.'];
            }
            $nv = $this->nguyenVong->layNguyenVongTrungTuyen((int) $hs['mahocsinh']);
            if ($nv === null) {
                return ['trangThai' => 'khong_trung_tuyen', 'thongBao' => 'Bạn không có kết quả trúng tuyển hợp lệ để xác nhận nhập học.'];
            }
            $truong = $this->truong->layThongTinTruong((int) $nv['matruong']);
            if ($truong === null) {
                throw new \RuntimeException('Không tìm thấy trường mã ' . $nv['matruong']);
            }
            $daXacNhan = $nv['trangthaixettuyen'] === 'XAC_NHAN_NHAP_HOC';
            $conHan    = $this->hocSinh->conHanXacNhan($hs);
            return [
                'trangThai' => $daXacNhan ? 'da_xac_nhan' : ($conHan ? 'san_sang' : 'het_han'),
                'hocSinh'   => ['hoten' => trim($hs['ho'] . ' ' . $hs['ten']), 'sobaodanh' => $hs['sobaodanh']],
                'nguyenVong' => (int) $nv['thutunguyenvong'],
                'truong'    => $truong,                     // layChiTietTruong(): địa chỉ, phường
                'han'       => $hs['hanchotxacnhannhaphoc'],
            ];
        } catch (Throwable $e) {
            error_log('[XacNhanNhapHoc] ' . $e->getMessage());
            return ['trangThai' => 'loi_he_thong', 'thongBao' => 'Không thể tải thông tin xác nhận nhập học, vui lòng thử lại.'];
        }
    }

    /** xacNhanNhapHoc(): trangThai = thanh_cong | loi_nghiep_vu | loi_he_thong */
    public function xacNhanNhapHoc(int $maNguoiDung): array
    {
        try {
            $this->conn->begin_transaction();
            try {
                $hs = $this->hocSinh->layHocSinhTrongKy($maNguoiDung, true);     // khóa hồ sơ: chống bấm trùng/2 tab
                if ($hs === null) {
                    throw new NghiepVuException('Bạn chưa có hồ sơ dự tuyển trong kỳ tuyển sinh hiện tại.');
                }
                if (!$this->ketQua->daCongBoKetQua((int) $hs['makythi'])) {
                    throw new NghiepVuException('Chưa công bố kết quả xét tuyển.');
                }
                if (!$this->hocSinh->conHanXacNhan($hs)) {
                    throw new NghiepVuException('Đã hết thời hạn xác nhận nhập học.');
                }
                // kiemTraDieuKien(): còn hạn, chưa xác nhận ở trường khác
                $nv = $this->nguyenVong->layNguyenVongTrungTuyen((int) $hs['mahocsinh'], true);
                if ($nv === null) {
                    throw new NghiepVuException('Bạn không có kết quả trúng tuyển hợp lệ để xác nhận nhập học.');
                }
                if ($nv['trangthaixettuyen'] === 'XAC_NHAN_NHAP_HOC') {
                    throw new NghiepVuException('Bạn đã xác nhận nhập học rồi.');
                }
                if ($this->nguyenVong->capNhatTrangThaiNhapHoc((int) $nv['manguyenvong']) !== 1) {
                    throw new NghiepVuException('Trạng thái nguyện vọng đã thay đổi, vui lòng tải lại trang.');
                }
                $this->conn->commit();
            } catch (Throwable $e) {
                $this->conn->rollback();
                throw $e;
            }
            return ['trangThai' => 'thanh_cong', 'thongBao' => self::TB_THANH_CONG];
        } catch (NghiepVuException $e) {
            return ['trangThai' => 'loi_nghiep_vu', 'thongBao' => $e->getMessage()];
        } catch (Throwable $e) {
            error_log('[XacNhanNhapHoc] ' . $e->getMessage());
            return ['trangThai' => 'loi_he_thong', 'thongBao' => self::TB_LOI_HE_THONG];
        }
    }
}
