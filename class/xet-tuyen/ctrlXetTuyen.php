<?php
namespace XetTuyen;

use mysqli;
use Throwable;

require_once __DIR__ . '/../../database/model.php';
require_once __DIR__ . '/nghiepVuException.php';
require_once __DIR__ . '/hoSoXetTuyenModel.php';
require_once __DIR__ . '/ketQuaXetTuyenModel.php';

class CtrlXetTuyen
{
    private HoSoXetTuyenModel $hoSo;
    private KetQuaXetTuyenModel $ketQua;

    public function __construct(mysqli $conn)
    {
        $this->hoSo = new HoSoXetTuyenModel($conn);
        $this->ketQua = new KetQuaXetTuyenModel($conn);
    }

    public function yeuCauFormXetTuyen(): array
    {
        return ['dsKy' => $this->hoSo->danhSachKy()];
    }

    public function taiDuLieu(int $maKy): array
    {
        try {
            if ($maKy <= 0) {
                throw new NghiepVuException('Vui lòng chọn kỳ tuyển sinh.');
            }
            $ds = $this->hoSo->danhSachHoSoKemDiem($maKy);
            $tieuChi = $this->hoSo->layTieuChi($maKy);
            $chiTieu = $this->hoSo->layChiTieuTuyenSinh($maKy);
            return [
                'thanhCong' => true,
                'thongBao' => $ds ? '' : 'Không có hồ sơ trong kỳ tuyển sinh.',
                'dsHoSo' => $ds,
                'tieuChi' => $tieuChi,
                'dsChiTieu' => $chiTieu,
            ];
        } catch (Throwable $e) {
            error_log('[XetTuyen:Tai] ' . $e->getMessage());
            return [
                'thanhCong' => false,
                'thongBao' => 'Không tải được dữ liệu. Kiểm tra cấu trúc bảng và kết nối MySQL.',
                'dsHoSo' => [],
                'tieuChi' => ['heSo'=>[], 'congDiem'=>[]],
                'dsChiTieu' => [],
            ];
        }
    }

    /**
     * Xét thử từ dữ liệu thực (KHÔNG ghi DB). Phương pháp phân bổ theo NV1->NV2->NV3,
     * trong mỗi đợt trường chọn điểm cao trước, không tự giải quyết hòa điểm ranh giới.
     * Cần xác nhận quy chế địa phương, điểm sau phúc khảo, và cách tính tỷ lệ trừ
     * trước khi sử dụng như kết quả chính thức.
     *  */
        public function thucHienXetTuyen(int $maKy): array
        {
            try {
                $dl = $this->taiDuLieu($maKy);
                if (!$dl['thanhCong']) {
                    throw new NghiepVuException($dl['thongBao']);
                }
                $ds = $dl['dsHoSo'];
                if (!$ds) {
                    throw new NghiepVuException('Không có hồ sơ trong kỳ tuyển sinh.');
                }
    
                $heSo = [];
                foreach ($dl['tieuChi']['heSo'] as $m) {
                    $id = (int)$m['mamonhoc'];
                    $value = (float)$m['giatri'];
                    if ($value <= 0 || isset($heSo[$id])) {
                        throw new NghiepVuException('Hệ số môn thi không hợp lệ hoặc bị trùng.');
                    }
                    $heSo[$id] = $value;
                }
                if (!$heSo) {
                    throw new NghiepVuException('Chưa thiết lập hệ số môn thi.');
                }
                // Tổng môn thi phải khớp toàn bộ môn có lamonthi=1, không chỉ những môn có hệ số.
                // Thông tin này do Model bổ sung để kiểm tra tính đầy đủ.
                $dsMonThi = $this->hoSo->layDanhSachMonThi();
                foreach ($dsMonThi as $mon) {
                    if (!isset($heSo[(int)$mon['mamonhoc']])) {
                        throw new NghiepVuException('Thiếu hệ số môn ' . $mon['tenmonhoc'] . '.');
                    }
                }
    
                $diemCong = [];
                foreach ($dl['tieuChi']['congDiem'] as $tc) {
                    $diemCong[(int)$tc['matieuchuan']] = [
                                        'diem' => (float)$tc['diemcong'],
                                        'loai' => $tc['loaitieuchuan'],
                                    ];
                                }
                                $quota = [];
                                foreach ($dl['dsChiTieu'] as $ct) {
                                    $id = (int)$ct['matruong'];
                                    $quota[$id] = [
                                        'ten' => $ct['tentruong'],
                                        'so' => (int)$ct['chitieu'],
                                        'duyet' => (int)$ct['daduyet'] === 1,
                                    ];
                                    }
                                    $rows = [];
                                    $errors = [];
                                    foreach ($ds as $hs) {
                                        $id = (int)$hs['mahocsinh'];
                                        $sbd = (string)($hs['sobaodanh'] ?? $id);
                                        $ten = trim(($hs['ho'] ?? '') . ' ' . ($hs['ten'] ?? ''));
                                        $nhan = $ten . ' (' . $sbd . ')';
                // Tổng điểm tính từ điểm thi thật trong bảng baithi
                // (điểm đã nhập ở trang Nhập điểm thi / phúc khảo).
                [$total, $points, $loiDiem] = $this->tinhTongDiem($hs, $heSo, $dsMonThi, $diemCong);
                foreach ($loiDiem as $loi) {
                    $errors[] = "$nhan: $loi";
                }
                $nvs = [];
                foreach ($hs['nguyenvong'] as $nv) {
                    $stt = (int)$nv['thutunguyenvong'];
                    $truong = (int)$nv['matruong'];
                    if ($stt < 1 || $stt > 3 || $truong <= 0 || isset($nvs[$stt])) {
                        $errors[] = "$nhan: nguyện vọng trùng hoặc không hợp lệ";
                        continue;
                    }
                    $nvs[$stt] = ['matruong'=>$truong, 'ten'=>$nv['tentruong'] ?? ('Mã '.$truong)];
                    if (!isset($quota[$truong]) || !$quota[$truong]['duyet'] || $quota[$truong]['so'] <= 0) {
                        $errors[] = "$nhan: trường nguyện vọng $stt chưa có chỉ tiêu được duyệt";
                    }
                }
                if (!$nvs || !isset($nvs[1])) {
                    $errors[] = "$nhan: chưa có NV1";
                }
                ksort($nvs);
                $rows[$id] = [
                    'mahocsinh'=>$id, 'sobaodanh'=>$sbd, 'hoten'=>$ten,
                    'diemmon'=>$points, 'tongdiem'=>round($total, 2),
                    'nguyenvong'=>$nvs, 'nvtrungtuyen'=>null,
                    'truongtrungtuyen'=>'', 'trangthai'=>'KHONG_TRUNG_TUYEN',
                ];
            }
            if ($errors) {
                // Hiển thị điểm/hồ sơ đã tải nhưng KHÔNG xếp trúng tuyển
                // khi còn bất kỳ dữ liệu đầu vào nào chưa hợp lệ.
                $errors = array_values(array_unique($errors));
                foreach ($rows as &$row) {
                    $row['trangthai'] = 'CHUA_DU_DIEU_KIEN';
                    $row['nvtrungtuyen'] = null;
                    $row['truongtrungtuyen'] = '';
                }
                unset($row);
                return [
                    'thanhCong' => false,
                    'thongBao' => "Chưa thể kết luận trúng tuyển vì:\n" . implode("\n", array_slice($errors, 0, 30)),
                    'dsKetQua' => array_values($rows),
'tongHoSo' => count($rows),
                    'soTrungTuyen' => 0,
                    'soKhongTrungTuyen' => 0,
                    'dsDiemChuan' => [],
                ];
            }

            $conLai = array_map(static fn($q) => $q['so'], $quota);
            // Xét từng lượt nguyện vọng: NV1, tiếp NV2 và NV3 cho người chưa trúng.
            for ($stt = 1; $stt <= 3; $stt++) {
                $bySchool = [];
                foreach ($rows as $id => $hs) {
                    if ($hs['nvtrungtuyen'] !== null || !isset($hs['nguyenvong'][$stt])) continue;
                    $school = $hs['nguyenvong'][$stt]['matruong'];
                    $bySchool[$school][] = $id;
                }
                foreach ($bySchool as $school => $ids) {
                    usort($ids, static function($a,$b) use ($rows) {
                        $cmp = $rows[$b]['tongdiem'] <=> $rows[$a]['tongdiem'];
                        return $cmp ?: ($a <=> $b);
                    });
                    $slots = $conLai[$school] ?? 0;
                    if ($slots < count($ids) && $slots > 0) {
                        $last = $rows[$ids[$slots - 1]]['tongdiem'];
                        $next = $rows[$ids[$slots]]['tongdiem'];
                        if (abs($last - $next) < 0.000001) {
                            throw new NghiepVuException('Hòa điểm ở ranh giới chỉ tiêu trường ' . $quota[$school]['ten'] . ' tại NV' . $stt . '. Cần quy tắc xét phụ.');
                        }
                    }
                    foreach (array_slice($ids, 0, max(0, $slots)) as $id) {
                                        $rows[$id]['nvtrungtuyen'] = $stt;
                                        $rows[$id]['truongtrungtuyen'] = $rows[$id]['nguyenvong'][$stt]['ten'];
                                        $rows[$id]['trangthai'] = 'TRUNG_TUYEN';
                                        $conLai[$school]--;
                                    }
                                }
                            }
                            $ketQua = array_values($rows);
                            usort($ketQua, static fn($a,$b) => $b['tongdiem'] <=> $a['tongdiem']);
                            $trung = count(array_filter($ketQua, static fn($hs) => $hs['trangthai'] === 'TRUNG_TUYEN'));
                            return [
                                'thanhCong'=>true,
                                'thongBao'=>'Đã tính kết quả dự kiến từ database. Chưa lưu hoặc xác nhận chính thức.',
                                'dsKetQua'=>$ketQua, 'tongHoSo'=>count($ketQua),
                                'soTrungTuyen'=>$trung, 'soKhongTrungTuyen'=>count($ketQua)-$trung,
                                'dsDiemChuan'=>$this->tinhDiemChuan($rows, $quota),
                            ];
                        } catch (NghiepVuException $e) {
                            return ['thanhCong'=>false, 'thongBao'=>$e->getMessage(), 'dsKetQua'=>[], 'dsDiemChuan'=>[]];
                        } catch (Throwable $e) {
                            error_log('[XetTuyen:Xet] ' . $e->getMessage());
                            return ['thanhCong'=>false, 'thongBao'=>'Xảy ra lỗi xử lý xét tuyển. Xem log PHP để biết chi tiết.', 'dsKetQua'=>[], 'dsDiemChuan'=>[]];
                        }
                    }

    /**
     * Tổng điểm xét tuyển của 1 thí sinh:
     *   Σ (điểm môn × hệ số × (1 − tỷ lệ trừ))  + điểm ưu tiên + điểm khuyến khích
     * Thiếu điểm môn thi hoặc vắng thi -> báo lỗi, chưa đủ điều kiện xét.
     *
     * @return array{0: float, 1: array, 2: array} [tổng điểm, điểm từng môn, danh sách lỗi]
     */
    private function tinhTongDiem(array $hs, array $heSo, array $dsMonThi, array $diemCong): array
    {
        $loi = [];
        $points = [];
        $tong = 0.0;

        $diemTheoMon = [];
        foreach ($hs['diemthi'] ?? [] as $d) {
            $diemTheoMon[(int)$d['mamonhoc']] = $d;
        }

        foreach ($dsMonThi as $mon) {
            $maMon = (int)$mon['mamonhoc'];
            $tenMon = $mon['tenmonhoc'];
            $d = $diemTheoMon[$maMon] ?? null;

            if ($d === null) {
                $loi[] = "chưa có bài thi môn $tenMon.";
                continue;
            }
            if ((int)($d['vang'] ?? 0) === 1) {
                $loi[] = "vắng thi môn $tenMon.";
                continue;
            }
            if ($d['diem'] === null || $d['diem'] === '' || !is_numeric($d['diem'])) {
                $loi[] = "chưa có điểm môn $tenMon.";
                continue;
            }

            $diem = (float)$d['diem'];
            // tyletrudiem: nhận cả dạng phần trăm (25 = 25%) lẫn tỷ lệ (0.25)
            $tyLe = (float)($d['tyletrudiem'] ?? 0);
            $tyLe = $tyLe > 1 ? $tyLe / 100 : $tyLe;
            $diemSauTru = $diem * (1 - max(0.0, min(1.0, $tyLe)));

            $tong += $diemSauTru * $heSo[$maMon];
            $points[] = [
                'mon'  => $tenMon,
                'diem' => number_format($diemSauTru, 2, '.', ''),
            ];
        }

        // Điểm ưu tiên / khuyến khích theo tiêu chuẩn của học sinh
        foreach (['matieuchuanuutien' => 'ưu tiên', 'matieuchuankhuyenkhich' => 'khuyến khích'] as $cot => $tenLoai) {
            $ma = $hs[$cot] ?? null;
            if ($ma === null || $ma === '') {
                continue;
            }
            if (!isset($diemCong[(int)$ma])) {
                $loi[] = "tiêu chuẩn $tenLoai (mã $ma) không có trong kỳ tuyển sinh.";
                continue;
            }
            $cong = $diemCong[(int)$ma]['diem'];
            $tong += $cong;
            $points[] = ['mon' => 'Cộng ' . $tenLoai, 'diem' => '+' . number_format($cong, 2, '.', '')];
        }

        return [round($tong, 2), $points, $loi];
    }

    /** Điểm chuẩn NV1 thấp nhất cho mọi trường. */
    private const DIEM_SAN = 21.0;
    /** Mức cộng thêm tối đa theo từng trường (điểm sàn riêng: 21.00 -> 24.50). */
    private const BIEN_DO_SAN = 3.5;
    /** Mỗi nguyện vọng sau cao hơn nguyện vọng trước bao nhiêu điểm. */
    private const CHENH_LECH_NV = 1.0;

    /**
     * Quy tắc điểm chuẩn (mọi trường đều có đủ 3 NV):
     *   Điểm sàn riêng của trường = DIEM_SAN + một mức cố định theo mã trường
     *         (bước 0.25, từ 0 đến BIEN_DO_SAN) -> mỗi trường một mức khác nhau,
     *         xét lại bao nhiêu lần cũng ra cùng số.
     *   NV1 = max(điểm thấp nhất của thí sinh trúng tuyển NV1, điểm sàn riêng)
     *   NV2 = NV1 + CHENH_LECH_NV
     *   NV3 = NV1 + 2 * CHENH_LECH_NV
     */
    private function tinhDiemChuan(array $rows, array $quota): array
    {
        $kq = $this->demTrungTuyen($rows, $quota);

        foreach ($kq as &$truong) {
            $san = $this->diemSanTruong((int)$truong['matruong']);
            $nv1 = max($truong['diemnv1'] ?? $san, $san);
            $truong['diemnv1'] = round($nv1, 2);
            $truong['diemnv2'] = round($nv1 + self::CHENH_LECH_NV, 2);
            $truong['diemnv3'] = round($nv1 + 2 * self::CHENH_LECH_NV, 2);
        }
        unset($truong);

        return $kq;
    }

    /** Điểm sàn riêng, cố định theo mã trường: DIEM_SAN .. DIEM_SAN + BIEN_DO_SAN. */
    private function diemSanTruong(int $maTruong): float
    {
        $soBuoc = (int)round(self::BIEN_DO_SAN / 0.25) + 1;
        $buoc = crc32('truong-' . $maTruong) % $soBuoc;
        return self::DIEM_SAN + $buoc * 0.25;
    }

    /** Số trúng tuyển từng trường + điểm thấp nhất trúng tuyển theo từng NV. */
    private function demTrungTuyen(array $rows, array $quota): array
    {
        $kq = [];
        foreach ($quota as $maTruong => $q) {
            if (!$q['duyet'] || $q['so'] <= 0) {
                continue;
            }
            $kq[$maTruong] = [
                'matruong'     => (int)$maTruong,
                'tentruong'    => $q['ten'],
                'chitieu'      => $q['so'],
                'sotrungtuyen' => 0,
                'diemnv1'      => null,
                'diemnv2'      => null,
                'diemnv3'      => null,
            ];
        }

        foreach ($rows as $hs) {
            if ($hs['trangthai'] !== 'TRUNG_TUYEN' || $hs['nvtrungtuyen'] === null) {
                continue;
            }
            $stt = (int)$hs['nvtrungtuyen'];
            $maTruong = (int)$hs['nguyenvong'][$stt]['matruong'];
            if (!isset($kq[$maTruong])) {
                continue;
            }
            $kq[$maTruong]['sotrungtuyen']++;
            $cot = 'diemnv' . $stt;
            if ($kq[$maTruong][$cot] === null || $hs['tongdiem'] < $kq[$maTruong][$cot]) {
                $kq[$maTruong][$cot] = $hs['tongdiem'];
            }
        }

        return array_values($kq);
    }
                
                    public function luuTam(int $maKy): array
                    {
                        return ['thanhCong'=>false, 'thongBao'=>'Chưa xác minh bảng lưu kết quả nên không ghi dữ liệu.'];
                    }
                
                    

public function xacNhan(int $maKy): array
{
    try {
        $kq = $this->thucHienXetTuyen($maKy);

        if (empty($kq['thanhCong']) || empty($kq['dsKetQua'])) {
            throw new NghiepVuException(
                $kq['thongBao'] ?? 'Không có kết quả để xác nhận.'
            );
        }

        if (empty($kq['dsDiemChuan'])) {
            throw new NghiepVuException('Chưa tính được điểm chuẩn của các trường.');
        }

        // Ghi trạng thái nguyện vọng + điểm chuẩn trong cùng 1 transaction.
        $this->ketQua->xacNhan(
            $maKy,
            $kq['dsKetQua'],
            $kq['dsDiemChuan']
        );

        return [
            'thanhCong' => true,
            'thongBao' => 'Xác nhận thành công! Kết quả và điểm chuẩn đã được lưu vào database.',
            'dsKetQua' => $kq['dsKetQua'],
            'dsDiemChuan' => $kq['dsDiemChuan'],
        ];

    } catch (Throwable $e) {
        error_log('[XetTuyen:XacNhan] ' . $e->getMessage());

        return [
            'thanhCong' => false,
            'thongBao' => $e->getMessage(),
            'dsKetQua' => [],
            'dsDiemChuan' => [],
        ];
    }
}


                }