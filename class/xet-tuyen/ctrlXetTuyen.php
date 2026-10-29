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
                                        $points = [];
                                        $total = 0.0;
                                        if (
            !isset($hs['tongdiem']) ||
            $hs['tongdiem'] === null ||
            $hs['tongdiem'] === '' ||
            !is_numeric($hs['tongdiem'])
        ) {
            $errors[] = "$nhan: chưa có tổng điểm xét tuyển.";
        } else {
            // Tổng điểm đã được tính và lưu trong database
            $total = (float) $hs['tongdiem'];
        
            if ($total < 0) {
                $errors[] = "$nhan: tổng điểm không hợp lệ.";
            }
        }
                // tongdiem đã lưu được dùng làm điểm xét tuyển.
                // Không cộng ưu tiên/khuyến khích thêm lần nữa khi chưa
                // xác minh tongdiem có bao gồm các khoản này hay không.
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
                            ];
                        } catch (NghiepVuException $e) {
                            return ['thanhCong'=>false, 'thongBao'=>$e->getMessage(), 'dsKetQua'=>[]];
                        } catch (Throwable $e) {
                            error_log('[XetTuyen:Xet] ' . $e->getMessage());
                            return ['thanhCong'=>false, 'thongBao'=>'Xảy ra lỗi xử lý xét tuyển. Xem log PHP để biết chi tiết.', 'dsKetQua'=>[]];
                        }
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

        $this->ketQua->xacNhan(
            $maKy,
            $kq['dsKetQua']
        );

        return [
            'thanhCong' => true,
            'thongBao' => 'Xác nhận thành công! Database đã được cập nhật.',
            'dsKetQua' => $kq['dsKetQua']
        ];

    } catch (Throwable $e) {
        error_log('[XetTuyen:XacNhan] ' . $e->getMessage());

        return [
            'thanhCong' => false,
            'thongBao' => $e->getMessage(),
            'dsKetQua' => []
        ];
    }
}


                }