<?php
namespace CongBoDiemChuan;

use mysqli;
use Throwable;

require_once __DIR__ . '/diemChuanModel.php';

/** Lỗi nghiệp vụ: thông báo được phép hiển thị trực tiếp cho người dùng. */
if (!class_exists(__NAMESPACE__ . '\NghiepVuException', false)) {
    class NghiepVuException extends \RuntimeException
    {
        /** Dữ liệu kèm theo (vd: danh sách trường chưa có điểm chuẩn). */
        private array $chiTiet;

        public function __construct(string $message, array $chiTiet = [])
        {
            parent::__construct($message);
            $this->chiTiet = $chiTiet;
        }

        public function layChiTiet(): array
        {
            return $this->chiTiet;
        }
    }
}

/**
 * Controller: Công bố điểm chuẩn (actor: Sở GD&ĐT).
 *
 * Luồng chính:  taiDanhSach -> kiemTraTruocCongBo -> congBo
 * Luồng 7.1:    luuChoCongBo (trạng thái "Chờ công bố")
 * Luồng 6.1:    xét tuyển chưa hoàn tất  -> chặn
 * Luồng 6.2:    trường chưa có điểm chuẩn -> chặn + liệt kê
 * Luồng 10.1:   lỗi cập nhật -> rollback, báo "không thành công"
 */
class CtrlCongBoDiemChuan
{
    /**
     * true  = trường có dòng điểm chuẩn nhưng cả NV1..NV3 đều NULL
     *         (không có thí sinh trúng tuyển) cũng bị coi là "chưa có điểm chuẩn".
     * false = chỉ cảnh báo, vẫn cho công bố.
     */
    private const CHAN_TRUONG_KHONG_CO_TRUNG_TUYEN = false;

    private DiemChuanModel $model;

    public function __construct(mysqli $conn)
    {
        $this->model = new DiemChuanModel($conn);
    }

    /** Bước 1-2: danh sách kỳ tuyển sinh. */
    public function yeuCauFormCongBo(): array
    {
        return ['dsKy' => $this->model->danhSachKy()];
    }

    /** Bước 3-5: danh sách trường + điểm chuẩn + kết quả kiểm tra. */
    public function taiDanhSach(int $maKy): array
    {
        try {
            if ($maKy <= 0) {
                throw new NghiepVuException('Vui lòng chọn kỳ tuyển sinh.');
            }
            if (!$this->model->daCoCotCongBo()) {
                throw new NghiepVuException(
                    'Bảng diemchuan chưa có cột trạng thái công bố. '
                    . 'Hãy chạy file congbo_diemchuan.sql trước.'
                );
            }

            $ds = $this->model->danhSachDiemChuan($maKy);
            $xetTuyen = $this->model->trangThaiXetTuyen($maKy);
            $kiemTra = $this->phanLoaiTruong($ds);

            return [
                'thanhCong'        => true,
                'thongBao'         => $ds ? '' : 'Kỳ tuyển sinh chưa có trường nào được duyệt chỉ tiêu.',
                'dsDiemChuan'      => $ds,
                'trangThaiKy'      => $this->trangThaiKy($ds),
                'xetTuyen'         => $xetTuyen,
                'dsChuaCoDiem'     => $kiemTra['chuaCo'],
                'dsKhongTrungTuyen'=> $kiemTra['khongTrung'],
            ];
        } catch (NghiepVuException $e) {
            return $this->ketQuaRong($e->getMessage());
        } catch (Throwable $e) {
            // Luồng 12.1
            error_log('[CongBoDiemChuan:Tai] ' . $e->getMessage());
            return $this->ketQuaRong('Không tải được dữ liệu điểm chuẩn.');
        }
    }

    /**
     * Bước 6: kiểm tra trước khi công bố / lưu.
     * Ném NghiepVuException nếu không hợp lệ (luồng 6.1, 6.2).
     */
    public function kiemTraTruocCongBo(int $maKy): array
    {
        $dl = $this->taiDanhSach($maKy);
        if (!$dl['thanhCong']) {
            throw new NghiepVuException($dl['thongBao']);
        }
        if (!$dl['dsDiemChuan']) {
            throw new NghiepVuException('Không có điểm chuẩn để công bố.');
        }
        if ($dl['trangThaiKy'] === DiemChuanModel::DA_CONG_BO) {
            throw new NghiepVuException('Điểm chuẩn của kỳ này đã được công bố.');
        }

        // 6.1
        if (!$dl['xetTuyen']['hoanTat']) {
            throw new NghiepVuException(
                'Chưa thể công bố điểm chuẩn khi quá trình xét tuyển chưa hoàn tất.'
            );
        }

        // 6.2
        $thieu = $dl['dsChuaCoDiem'];
        if (self::CHAN_TRUONG_KHONG_CO_TRUNG_TUYEN) {
            $thieu = array_merge($thieu, $dl['dsKhongTrungTuyen']);
        }
        if ($thieu) {
            throw new NghiepVuException(
                'Một số trường chưa có điểm chuẩn. Vui lòng hoàn tất dữ liệu điểm chuẩn '
                . '(thực hiện và xác nhận xét tuyển) trước khi công bố.',
                $thieu
            );
        }

        return $dl;
    }

    /** Luồng 7.1: lưu ở trạng thái "Chờ công bố". */
    public function luuChoCongBo(int $maKy): array
    {
        try {
            $this->kiemTraTruocCongBo($maKy);
            $this->model->capNhatTrangThai($maKy, DiemChuanModel::CHO_CONG_BO, null);
            return [
                'thanhCong' => true,
                'thongBao'  => 'Đã lưu bảng điểm chuẩn ở trạng thái "Chờ công bố". '
                             . 'Người dùng chưa xem được cho đến khi công bố.',
            ];
        } catch (NghiepVuException $e) {
            return ['thanhCong' => false, 'thongBao' => $e->getMessage(), 'dsLoi' => $e->layChiTiet()];
        } catch (Throwable $e) {
            error_log('[CongBoDiemChuan:Luu] ' . $e->getMessage());
            return ['thanhCong' => false, 'thongBao' => 'Lưu điểm chuẩn không thành công, vui lòng thử lại.', 'dsLoi' => []];
        }
    }

    /** Bước 8-11: công bố chính thức. */
    public function congBo(int $maKy, ?int $nguoiThucHien): array
    {
        try {
            $this->kiemTraTruocCongBo($maKy);
            $this->model->capNhatTrangThai($maKy, DiemChuanModel::DA_CONG_BO, $nguoiThucHien);
            return ['thanhCong' => true, 'thongBao' => 'Công bố điểm chuẩn thành công.'];
        } catch (NghiepVuException $e) {
            return ['thanhCong' => false, 'thongBao' => $e->getMessage(), 'dsLoi' => $e->layChiTiet()];
        } catch (Throwable $e) {
            // Luồng 10.1
            error_log('[CongBoDiemChuan:CongBo] ' . $e->getMessage());
            return ['thanhCong' => false, 'thongBao' => 'Công bố điểm chuẩn không thành công, vui lòng thử lại.', 'dsLoi' => []];
        }
    }

    /** Dành cho trang tra cứu của người dùng (chỉ điểm chuẩn đã công bố). */
    public function traCuuDiemChuan(int $maKy): array
    {
        try {
            return $this->model->diemChuanDaCongBo($maKy);
        } catch (Throwable $e) {
            error_log('[CongBoDiemChuan:TraCuu] ' . $e->getMessage());
            return [];
        }
    }

    // ------------------------------------------------------------------

    /** Trạng thái chung của kỳ: chỉ "Đã/Chờ" khi TẤT CẢ trường cùng trạng thái. */
    private function trangThaiKy(array $ds): string
    {
        if (!$ds) {
            return DiemChuanModel::CHUA_CONG_BO;
        }
        $cacTrangThai = array_unique(array_column($ds, 'trangthaicongbo'));
        return count($cacTrangThai) === 1 ? $cacTrangThai[0] : DiemChuanModel::CHUA_CONG_BO;
    }

    /** Tách trường chưa có dòng điểm chuẩn và trường có dòng nhưng toàn NULL. */
    private function phanLoaiTruong(array $ds): array
    {
        $chuaCo = [];
        $khongTrung = [];
        foreach ($ds as $r) {
            if (!$r['codiemchuan']) {
                $chuaCo[] = $r['tentruong'];
            } elseif ($r['diemnv1'] === null && $r['diemnv2'] === null && $r['diemnv3'] === null) {
                $khongTrung[] = $r['tentruong'];
            }
        }
        return ['chuaCo' => $chuaCo, 'khongTrung' => $khongTrung];
    }

    private function ketQuaRong(string $thongBao): array
    {
        return [
            'thanhCong'         => false,
            'thongBao'          => $thongBao,
            'dsDiemChuan'       => [],
            'trangThaiKy'       => DiemChuanModel::CHUA_CONG_BO,
            'xetTuyen'          => ['tong' => 0, 'dangXet' => 0, 'hoanTat' => false],
            'dsChuaCoDiem'      => [],
            'dsKhongTrungTuyen' => [],
        ];
    }
}
