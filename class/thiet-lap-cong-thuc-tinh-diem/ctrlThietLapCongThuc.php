<?php

namespace ThietLapCongThuc;

use mysqli;
use Throwable;

require_once __DIR__ . '/../../database/model.php';
require_once __DIR__ . '/nghiepVuException.php';
require_once __DIR__ . '/kyThiTuyenSinhModel.php';
require_once __DIR__ . '/monThiModel.php';
require_once __DIR__ . '/heSoDiemModel.php';
require_once __DIR__ . '/tieuChuanCongDiemModel.php';

/**
 * Lớp điều khiển "CtrlThietLapCongThuc" của use case "Thiết lập công thức tính điểm".
 * Các phương thức bám theo sequence diagram:
 *   yeuCauFormCongThuc(), kiemTraHopLe(dsHeSo, dsTieuChuan), luuCongThuc(dsHeSo, dsTieuChuan)
 */
class CtrlThietLapCongThuc
{
    /** Các loại tiêu chuẩn hợp lệ: mã lưu trong CSDL => nhãn hiển thị */
    public const LOAI_TIEU_CHUAN = [
        'UU_TIEN'      => 'Ưu tiên',
        'KHUYEN_KHICH' => 'Khuyến khích',
    ];

    /** Giới hạn của cột decimal(4,2) */
    public const GIA_TRI_TOI_DA = 99.99;
    public const DO_DAI_DIEU_KIEN_TOI_DA = 200;

    public const MSG_THANH_CONG = 'Thiết lập công thức tính điểm thành công';
    public const MSG_LOI_HE_THONG = 'Lưu công thức không thành công, vui lòng thử lại';

    private mysqli $conn;
    private KyThiTuyenSinhModel $kyThi;
    private MonThiModel $monThi;
    private HeSoDiemModel $heSoDiem;
    private TieuChuanCongDiemModel $tieuChuan;

    public function __construct(mysqli $conn)
    {
        $this->conn      = $conn;
        $this->kyThi     = new KyThiTuyenSinhModel($conn);
        $this->monThi    = new MonThiModel($conn);
        $this->heSoDiem  = new HeSoDiemModel($conn);
        $this->tieuChuan = new TieuChuanCongDiemModel($conn);
    }

    /**
     * yeuCauFormCongThuc()
     * Gom dữ liệu để dựng form (bước 2 và luồng thay thế 2.1).
     *
     * @return array{
     *   ky: ?array, dsMon: array, dsHeSo: array<int,string>,
     *   dsTieuChuan: array, daCoCongThuc: bool
     * }
     */
    public function yeuCauFormCongThuc(): array
    {
        $ky    = $this->kyThi->layKyTuyenSinhHienTai();
        $dsMon = $this->monThi->layDanhSachMonThi();

        if ($ky === null) {
            return [
                'ky' => null, 'dsMon' => $dsMon, 'dsHeSo' => [],
                'dsTieuChuan' => [], 'daCoCongThuc' => false,
            ];
        }

        $maKy        = (int) $ky['makythi'];
        $dsHeSo      = $this->heSoDiem->layHeSoTheoKy($maKy);
        $dsTieuChuan = $this->tieuChuan->layTieuChuanCongDiemTheoKy($maKy);

        return [
            'ky'           => $ky,
            'dsMon'        => $dsMon,
            'dsHeSo'       => $dsHeSo,
            'dsTieuChuan'  => $dsTieuChuan,
            'daCoCongThuc' => !empty($dsHeSo),
        ];
    }

    /**
     * Chuyển dữ liệu $_POST thành dsHeSo / dsTieuChuan ở dạng "thô" (chuỗi đã trim),
     * chưa kiểm tra tính hợp lệ.
     *
     * Định dạng form:
     *   heSo[<mamonhoc>]                = "2"
     *   tieuChuan[<idx>][ma|loai|dieuKien|diemCong]
     *
     * @return array{dsHeSo: array<string,string>, dsTieuChuan: array<string,array>}
     */
    public function chuanHoaDuLieuNhap(array $post): array
    {
        $dsHeSo = [];
        if (isset($post['heSo']) && is_array($post['heSo'])) {
            foreach ($post['heSo'] as $maMon => $giaTri) {
                if (is_scalar($giaTri)) {
                    $dsHeSo[(string) $maMon] = trim((string) $giaTri);
                }
            }
        }

        $dsTieuChuan = [];
        if (isset($post['tieuChuan']) && is_array($post['tieuChuan'])) {
            foreach ($post['tieuChuan'] as $idx => $tc) {
                if (!is_array($tc)) {
                    continue;
                }
                $lay = static fn(string $k): string =>
                    isset($tc[$k]) && is_scalar($tc[$k]) ? trim((string) $tc[$k]) : '';

                $dsTieuChuan[(string) $idx] = [
                    'ma'       => $lay('ma'),
                    'loai'     => $lay('loai'),
                    'dieuKien' => preg_replace('/\s+/u', ' ', $lay('dieuKien')),
                    'diemCong' => str_replace(',', '.', $lay('diemCong')),
                ];
            }
        }

        // Hệ số cũng chấp nhận dấu phẩy thập phân
        foreach ($dsHeSo as $k => $v) {
            $dsHeSo[$k] = str_replace(',', '.', $v);
        }

        return ['dsHeSo' => $dsHeSo, 'dsTieuChuan' => $dsTieuChuan];
    }

    /**
     * kiemTraHopLe(dsHeSo, dsTieuChuan)  — bước 5 của basic flow.
     *
     * Quy tắc:
     *  - mỗi môn thi có hệ số là số > 0;
     *  - loại tiêu chuẩn là ưu tiên hoặc khuyến khích;
     *  - điều kiện áp dụng không để trống và không trùng tiêu chuẩn khác CÙNG LOẠI;
     *  - mức điểm cộng là số > 0.
     * (Hệ số và điểm cộng tối đa 2 chữ số thập phân, <= 99.99 do cột decimal(4,2).)
     *
     * @param array $dsMon danh sách môn thi (từ layDanhSachMonThi())
     * @return list<array{truong:string, thongBao:string}> rỗng nếu hợp lệ.
     *         "truong" là khóa để giao diện gắn lỗi vào đúng ô nhập:
     *         "heSo.<mamonhoc>", "tieuChuan.<idx>.<loai|dieuKien|diemCong>", hoặc "chung".
     */
    public function kiemTraHopLe(array $dsMon, array $dsHeSo, array $dsTieuChuan): array
    {
        $loi = [];

        // ---- Hệ số từng môn thi ----
        $maMonHopLe = [];
        foreach ($dsMon as $mon) {
            $ma  = (string) $mon['mamonhoc'];
            $ten = $mon['tenmonhoc'];
            $maMonHopLe[$ma] = true;

            if (!array_key_exists($ma, $dsHeSo) || $dsHeSo[$ma] === '') {
                $loi[] = ['truong' => "heSo.$ma", 'thongBao' => "Hệ số môn $ten không được để trống."];
                continue;
            }
            $thongBao = $this->kiemTraSoDuong($dsHeSo[$ma], "Hệ số môn $ten");
            if ($thongBao !== null) {
                $loi[] = ['truong' => "heSo.$ma", 'thongBao' => $thongBao];
            }
        }
        foreach (array_keys($dsHeSo) as $ma) {
            if (!isset($maMonHopLe[(string) $ma])) {
                $loi[] = ['truong' => 'chung', 'thongBao' => 'Có môn không thuộc danh sách môn thi của kỳ tuyển sinh.'];
                break;
            }
        }

        // ---- Tiêu chuẩn cộng điểm ----
        $daGap = []; // "loai|dieukien-chuan-hoa" => stt dòng đầu tiên
        $stt   = 0;
        foreach ($dsTieuChuan as $idx => $tc) {
            $stt++;
            $nhan = "Tiêu chuẩn #$stt";

            if ($tc['ma'] !== '' && !ctype_digit($tc['ma'])) {
                $loi[] = ['truong' => 'chung', 'thongBao' => "$nhan có mã không hợp lệ. Vui lòng tải lại trang."];
            }

            $loaiHopLe = isset(self::LOAI_TIEU_CHUAN[$tc['loai']]);
            if (!$loaiHopLe) {
                $loi[] = ['truong' => "tieuChuan.$idx.loai",
                          'thongBao' => "$nhan: loại tiêu chuẩn phải là Ưu tiên hoặc Khuyến khích."];
            }

            if ($tc['dieuKien'] === '') {
                $loi[] = ['truong' => "tieuChuan.$idx.dieuKien",
                          'thongBao' => "$nhan: điều kiện áp dụng không được để trống."];
            } elseif (mb_strlen($tc['dieuKien']) > self::DO_DAI_DIEU_KIEN_TOI_DA) {
                $loi[] = ['truong' => "tieuChuan.$idx.dieuKien",
                          'thongBao' => "$nhan: điều kiện áp dụng tối đa " . self::DO_DAI_DIEU_KIEN_TOI_DA . ' ký tự.'];
            } elseif ($loaiHopLe) {
                $khoa = $tc['loai'] . '|' . mb_strtolower($tc['dieuKien']);
                if (isset($daGap[$khoa])) {
                    $loi[] = ['truong' => "tieuChuan.$idx.dieuKien",
                              'thongBao' => sprintf(
                                  '%s: điều kiện "%s" bị trùng với tiêu chuẩn #%d (cùng loại %s).',
                                  $nhan, $tc['dieuKien'], $daGap[$khoa], self::LOAI_TIEU_CHUAN[$tc['loai']]
                              )];
                } else {
                    $daGap[$khoa] = $stt;
                }
            }

            if ($tc['diemCong'] === '') {
                $loi[] = ['truong' => "tieuChuan.$idx.diemCong",
                          'thongBao' => "$nhan: mức điểm cộng không được để trống."];
            } else {
                $thongBao = $this->kiemTraSoDuong($tc['diemCong'], "$nhan: mức điểm cộng");
                if ($thongBao !== null) {
                    $loi[] = ['truong' => "tieuChuan.$idx.diemCong", 'thongBao' => $thongBao];
                }
            }
        }

        return $loi;
    }

    /**
     * luuCongThuc(dsHeSo, dsTieuChuan)  — bước 4-7 và các nhánh lỗi 5.1, 6.1.
     *
     * @return array{
     *   trangThai: 'thanh_cong'|'khong_hop_le'|'loi_nghiep_vu'|'loi_he_thong'|'chua_co_ky',
     *   thongBao: string,
     *   dsLoi: list<array{truong:string, thongBao:string}>
     * }
     */
    public function luuCongThuc(array $dsHeSo, array $dsTieuChuan): array
    {
        $ky = $this->kyThi->layKyTuyenSinhHienTai();
        if ($ky === null) {
            return $this->ketQua('chua_co_ky',
                'Chưa có kỳ tuyển sinh của năm ' . $this->kyThi->layNamHienTai() . '.');
        }
        $maKy  = (int) $ky['makythi'];
        $dsMon = $this->monThi->layDanhSachMonThi();

        // 5. Kiểm tra tính hợp lệ  -> nhánh 5.1
        $dsLoi = $this->kiemTraHopLe($dsMon, $dsHeSo, $dsTieuChuan);
        if (!empty($dsLoi)) {
            return $this->ketQua('khong_hop_le', 'Dữ liệu nhập vào không hợp lệ.', $dsLoi);
        }

        // Đổi sang kiểu số để ghi vào model
        $heSoLuu = [];
        foreach ($dsMon as $mon) {
            $heSoLuu[(int) $mon['mamonhoc']] = (float) $dsHeSo[(string) $mon['mamonhoc']];
        }
        $tieuChuanLuu = [];
        foreach ($dsTieuChuan as $tc) {
            $tieuChuanLuu[] = [
                'ma'       => $tc['ma'] === '' ? null : (int) $tc['ma'],
                'loai'     => $tc['loai'],
                'dieuKien' => $tc['dieuKien'],
                'diemCong' => (float) $tc['diemCong'],
            ];
        }

        // 6. Lưu (một transaction: hệ số và tiêu chuẩn cùng thành công hoặc cùng hủy)
        try {
            $this->conn->begin_transaction();

            // Khóa dòng kỳ thi để hai người lưu cùng lúc không ghi chồng lên nhau
            $stmt = $this->conn->prepare("SELECT makythi FROM kythituyensinh WHERE makythi = ? FOR UPDATE");
            $stmt->bind_param('i', $maKy);
            $stmt->execute();
            $stmt->get_result()->free();   // đọc hết kết quả, nếu không các câu lệnh sau sẽ báo "out of sync"
            $stmt->close();

            $this->heSoDiem->capNhatHeSo($maKy, $heSoLuu);
            $this->tieuChuan->capNhatTieuChuanCongDiem($maKy, $tieuChuanLuu);

            $this->conn->commit();
        } catch (NghiepVuException $e) {
            $this->conn->rollback();
            return $this->ketQua('loi_nghiep_vu', $e->getMessage());
        } catch (Throwable $e) {
            // 6.1 Lỗi CSDL / hệ thống: không lộ chi tiết cho người dùng, chỉ ghi log
            try { $this->conn->rollback(); } catch (Throwable) {}
            error_log('[ThietLapCongThuc] ' . $e->getMessage());
            return $this->ketQua('loi_he_thong', self::MSG_LOI_HE_THONG);
        }

        // 7. Thông báo thành công
        return $this->ketQua('thanh_cong', self::MSG_THANH_CONG);
    }

    // ------------------------------------------------------------------

    /**
     * Kiểm tra chuỗi là số thập phân dương, tối đa 2 chữ số thập phân, <= 99.99.
     * Trả về thông báo lỗi hoặc null nếu hợp lệ. Dùng regex thay vì ép kiểu float
     * để tránh các giá trị như "1e3", "0x1A", " 5" lọt qua.
     */
    private function kiemTraSoDuong(string $giaTri, string $nhan): ?string
    {
        if (!preg_match('/^-?\d+(\.\d+)?$/', $giaTri)) {
            return "$nhan phải là một số.";
        }
        if ($giaTri[0] === '-' || (float) $giaTri <= 0) {
            return "$nhan phải lớn hơn 0.";
        }
        if (!preg_match('/^\d+(\.\d{1,2})?$/', $giaTri)) {
            return "$nhan chỉ được có tối đa 2 chữ số thập phân.";
        }
        if ((float) $giaTri > self::GIA_TRI_TOI_DA) {
            return "$nhan không được lớn hơn " . self::GIA_TRI_TOI_DA . '.';
        }
        return null;
    }

    private function ketQua(string $trangThai, string $thongBao, array $dsLoi = []): array
    {
        return ['trangThai' => $trangThai, 'thongBao' => $thongBao, 'dsLoi' => $dsLoi];
    }
}
