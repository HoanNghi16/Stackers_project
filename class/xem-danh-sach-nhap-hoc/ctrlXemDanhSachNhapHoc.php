<?php

namespace XemDanhSachNhapHoc;

use mysqli;
use Throwable;

require_once __DIR__ . '/nghiepVuException.php';
require_once __DIR__ . '/kyThiTuyenSinhModel.php';
require_once __DIR__ . '/truongTHPTModel.php';
require_once __DIR__ . '/ketQuaXetTuyenModel.php';

/**
 * CtrlXemDanhSachNhapHoc trong sequence diagram "Xem danh sách học sinh nhập học" (Actor: Trường THPT).
 */
class CtrlXemDanhSachNhapHoc
{
    private KyThiTuyenSinhModel $kyThi;
    private TruongTHPTModel $truong;
    private KetQuaXetTuyenModel $ketQua;

    public function __construct(mysqli $conn)
    {
        $this->kyThi  = new KyThiTuyenSinhModel($conn);
        $this->truong = new TruongTHPTModel($conn);
        $this->ketQua = new KetQuaXetTuyenModel($conn);
    }

    /** Chuẩn hóa điều kiện từ query string: nv (1-3 hoặc rỗng) và q (từ khóa). */
    public function chuanHoaDieuKien(array $in): array
    {
        $nv = (string) ($in['nv'] ?? '');
        $q  = trim((string) ($in['q'] ?? ''));
        return [
            'nv' => in_array($nv, ['1', '2', '3'], true) ? (int) $nv : null,
            'q'  => mb_substr($q, 0, 100),
        ];
    }

    /**
     * yeuCauDanhSach() + locDanhSach(dieuKien) + timKiemDanhSach(tuKhoa) (điều kiện rỗng = xem toàn bộ).
     * trangThai: ok | khong_co_truong | chua_co_ky | loi_he_thong
     * Khi ok: 'tongSo' (tổng học sinh đã nhập học, bỏ qua lọc), 'ds', 'thongKeNV', 'trangThaiDs':
     *   chua_co (chưa có học sinh nào xác nhận) | khong_tim_thay (có nhưng lọc/tìm không ra) | co_du_lieu
     */
    public function yeuCauDanhSach(int $maNguoiDung, array $dieuKien = ['nv' => null, 'q' => '']): array
    {
        try {
            $truong = $this->truong->layTheoNguoiDung($maNguoiDung);
            if ($truong === null) {
                return ['trangThai' => 'khong_co_truong'];
            }
            $ky = $this->kyThi->layKyTuyenSinhHienTai();
            if ($ky === null) {
                return ['trangThai' => 'chua_co_ky', 'truong' => $truong, 'nam' => $this->kyThi->layNamHienTai()];
            }
            $maTruong = (int) $truong['matruong'];
            $maKy     = (int) $ky['makythi'];

            $tatCa = $this->ketQua->layDanhSach($maTruong, $maKy);
            $thongKe = [1 => 0, 2 => 0, 3 => 0];
            foreach ($tatCa as $r) {
                $thongKe[(int) $r['thutunguyenvong']] = ($thongKe[(int) $r['thutunguyenvong']] ?? 0) + 1;
            }

            $coLoc = $dieuKien['nv'] !== null || $dieuKien['q'] !== '';
            $ds    = $coLoc
                ? $this->ketQua->locTheoDieuKien($maTruong, $maKy, $dieuKien['nv'], $dieuKien['q'])
                : $tatCa;

            return [
                'trangThai'   => 'ok',
                'truong'      => $truong,
                'ky'          => $ky,
                'tongSo'      => count($tatCa),
                'thongKeNV'   => $thongKe,
                'ds'          => $ds,
                'coLoc'       => $coLoc,
                'trangThaiDs' => $tatCa === [] ? 'chua_co' : ($ds === [] ? 'khong_tim_thay' : 'co_du_lieu'),
            ];
        } catch (Throwable $e) {
            error_log('[XemDanhSachNhapHoc] ' . $e->getMessage());
            return ['trangThai' => 'loi_he_thong'];
        }
    }

    /**
     * xuatDanhSach(): lấy toàn bộ danh sách (không áp bộ lọc) và dựng nội dung CSV (UTF-8 BOM để Excel đọc đúng tiếng Việt).
     * trangThai: ok | trong | khong_co_truong | chua_co_ky | loi_he_thong
     */
    public function xuatDanhSach(int $maNguoiDung): array
    {
        $d = $this->yeuCauDanhSach($maNguoiDung);
        if ($d['trangThai'] !== 'ok') {
            return ['trangThai' => $d['trangThai']];
        }
        if ($d['tongSo'] === 0) {
            return ['trangThai' => 'trong'];
        }
        $f = fopen('php://temp', 'w+');
        fwrite($f, "\xEF\xBB\xBF");
        fputcsv($f, ['STT', 'Họ tên', 'Số báo danh', 'Điểm xét tuyển', 'Nguyện vọng trúng tuyển'], ',', '"', '\\');
        foreach ($d['ds'] as $i => $r) {
            fputcsv($f, [
                $i + 1,
                $this->chongCsvInjection($r['hoten']),
                $this->chongCsvInjection((string) $r['sobaodanh']),
                $r['diemxettuyen'] === null ? '' : rtrim(rtrim((string) $r['diemxettuyen'], '0'), '.'),
                'NV' . (int) $r['thutunguyenvong'],
            ], ',', '"', '\\');
        }
        rewind($f);
        $noiDung = stream_get_contents($f);
        fclose($f);
        return [
            'trangThai' => 'ok',
            'tenFile'   => 'danh-sach-nhap-hoc-' . (int) $d['ky']['namtuyensinh'] . '-truong-' . (int) $d['truong']['matruong'] . '.csv',
            'noiDung'   => $noiDung,
        ];
    }

    /** Ô bắt đầu bằng = + - @ có thể bị Excel hiểu là công thức. */
    private function chongCsvInjection(string $v): string
    {
        return preg_match('/^[=+\-@\t\r]/', $v) ? "'" . $v : $v;
    }
}
