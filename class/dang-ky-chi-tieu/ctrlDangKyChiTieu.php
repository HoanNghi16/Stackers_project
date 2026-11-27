<?php

namespace DangKyChiTieu;

use mysqli;
use Throwable;

require_once __DIR__ . '/nghiepVuException.php';
require_once __DIR__ . '/kyThiTuyenSinhModel.php';
require_once __DIR__ . '/truongTHPTModel.php';
require_once __DIR__ . '/chiTieuTuyenSinhModel.php';

/**
 * CtrlDangKyChiTieu trong sequence diagram "Đăng ký chỉ tiêu" (Actor: Trường THPT).
 */
class CtrlDangKyChiTieu
{
    private KyThiTuyenSinhModel $kyThi;
    private TruongTHPTModel $truong;
    private ChiTieuTuyenSinhModel $chiTieu;

    public function __construct(private mysqli $conn)
    {
        $this->kyThi   = new KyThiTuyenSinhModel($conn);
        $this->truong  = new TruongTHPTModel($conn);
        $this->chiTieu = new ChiTieuTuyenSinhModel($conn);
    }

    /**
     * yeuCauFormDangKy(): dữ liệu dựng form.
     * trangThai: ok | khong_co_truong | chua_co_ky
     * trangThaiChiTieu: chua_dang_ky | cho_duyet | da_duyet
     */
    public function yeuCauFormDangKy(int $maNguoiDung): array
    {
        try {
            return $this->dungForm($maNguoiDung);
        } catch (Throwable $e) {
            error_log('[DangKyChiTieu] ' . $e->getMessage());
            return ['trangThai' => 'loi_he_thong'];
        }
    }

    private function dungForm(int $maNguoiDung): array
    {
        $truong = $this->truong->layTheoNguoiDung($maNguoiDung);
        if ($truong === null) {
            return ['trangThai' => 'khong_co_truong'];
        }
        $ky = $this->kyThi->layKyTuyenSinhHienTai();
        if ($ky === null) {
            return ['trangThai' => 'chua_co_ky', 'truong' => $truong, 'nam' => $this->kyThi->layNamHienTai()];
        }
        $dx   = $this->chiTieu->layDeXuatChiTieu((int) $truong['matruong'], (int) $ky['makythi']);
        $tt   = $dx === null ? 'chua_dang_ky' : ($dx['daduyet'] ? 'da_duyet' : 'cho_duyet');
        $han  = $this->kyThi->conHanDangKy($ky);
        return [
            'trangThai'        => 'ok',
            'truong'           => $truong,
            'ky'               => $ky,
            'chiTieu'          => $dx['chitieu'] ?? null,
            'trangThaiChiTieu' => $tt,
            'conHan'           => $han,
            'choPhepNhap'      => $han && $tt !== 'da_duyet',
        ];
    }

    /** kiemTraHopLe(): số nguyên dương (không dùng ép kiểu để bắt "12abc", "1.5", "-3"). */
    public function kiemTraHopLe(string $soLuong): ?string
    {
        $soLuong = trim($soLuong);
        if ($soLuong === '') {
            return 'Vui lòng nhập chỉ tiêu tuyển sinh.';
        }
        if (!preg_match('/^[1-9]\d{0,8}$/', $soLuong)) {
            return 'Chỉ tiêu tuyển sinh phải là số nguyên dương.';
        }
        return null;
    }

    /**
     * dangKyChiTieu(): trangThai = thanh_cong | khong_hop_le | loi_nghiep_vu | loi_he_thong
     */
    public function dangKyChiTieu(int $maNguoiDung, string $soLuong): array
    {
        $loi = $this->kiemTraHopLe($soLuong);
        if ($loi !== null) {
            return ['trangThai' => 'khong_hop_le', 'thongBao' => $loi];
        }
        try {
            $truong = $this->truong->layTheoNguoiDung($maNguoiDung);
            if ($truong === null) {
                throw new NghiepVuException('Tài khoản chưa được gán với trường THPT nào.');
            }
            $ky = $this->kyThi->layKyTuyenSinhHienTai();
            if ($ky === null) {
                throw new NghiepVuException('Hệ thống chưa có kỳ tuyển sinh của năm hiện tại.');
            }
            $maTruong = (int) $truong['matruong'];
            $maKy     = (int) $ky['makythi'];

            $this->conn->begin_transaction();
            try {
                if (!$this->kyThi->conHanDangKy($ky)) {
                    throw new NghiepVuException('Đã hết thời gian đăng ký chỉ tiêu.');
                }
                if ($this->chiTieu->khoaVaLayDaDuyet($maTruong, $maKy) === true) {
                    throw new NghiepVuException('Chỉ tiêu đã được Sở GD&ĐT phê duyệt, không thể thay đổi.');
                }
                $this->chiTieu->luuChiTieu($maTruong, $maKy, (int) trim($soLuong));
                $this->conn->commit();
            } catch (Throwable $e) {
                $this->conn->rollback();
                throw $e;
            }
            return ['trangThai' => 'thanh_cong', 'thongBao' => 'Đăng ký chỉ tiêu thành công'];
        } catch (NghiepVuException $e) {
            return ['trangThai' => 'loi_nghiep_vu', 'thongBao' => $e->getMessage()];
        } catch (Throwable $e) {
            error_log('[DangKyChiTieu] ' . $e->getMessage());
            return ['trangThai' => 'loi_he_thong', 'thongBao' => 'Đăng ký chỉ tiêu không thành công, vui lòng thử lại'];
        }
    }
}
