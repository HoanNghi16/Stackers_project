<?php
namespace NhapDiemPhucKhao;

use mysqli;
use RuntimeException;
use Throwable;

/**
 * Truy cập dữ liệu cho chức năng Nhập điểm phúc khảo.
 * Bảng: phieuphuckhao   (maphieu, mahocsinh, ngaytao, lydo, trangthai)
 *       chitietphuckhao (machitiet, maphieu, mabaithi, diemsauphuckhao)
 *       minhchungphuckhao (maphieu, tenfilegoc, ...)
 *       baithi          (mabaithi, mahocsinh, mamonhoc, sophach, diem, vang)
 *       nhatkyhethong   - ghi điểm ban đầu, điểm phúc khảo, người + thời gian
 */
class PhucKhaoModel
{
    /**
     * Giá trị trangthai được phép (ràng buộc chk_phieupk_trangthai):
     * CHO_XU_LY, DANG_PHUC_KHAO, HOAN_THANH, TU_CHOI
     */
    public const DA_CAP_NHAT = 'HOAN_THANH';   // "Đã cập nhật kết quả"
    public const TU_CHOI     = 'TU_CHOI';

    /** Phiếu đã xong hoặc bị từ chối -> không nhập điểm được nữa. */
    public static function daKhoaPhieu(string $trangThai): bool
    {
        return in_array($trangThai, [self::DA_CAP_NHAT, self::TU_CHOI], true);
    }

    private mysqli $conn;

    public function __construct(mysqli $conn)
    {
        $this->conn = $conn;
    }

    public function layKyTheoNam(int $nam): ?array
    {
        $stmt = $this->conn->prepare("SELECT makythi, namtuyensinh FROM kythituyensinh WHERE namtuyensinh = ? LIMIT 1");
        $stmt->bind_param('i', $nam);
        $stmt->execute();
        $ky = $stmt->get_result()->fetch_assoc();
        $stmt->close();
        return $ky ?: null;
    }

    /** Kỳ đã xác nhận kết quả xét tuyển chưa (có nguyện vọng khác DANG_XET). */
    public function daXacNhanXetTuyen(int $maKy): bool
    {
        $stmt = $this->conn->prepare("
            SELECT COUNT(*) AS so
            FROM nguyenvong nv
            INNER JOIN hocsinh hs ON hs.mahocsinh = nv.mahocsinh
            WHERE hs.makythi = ? AND nv.trangthaixettuyen <> 'DANG_XET'
        ");
        $stmt->bind_param('i', $maKy);
        $stmt->execute();
        $so = (int)$stmt->get_result()->fetch_assoc()['so'];
        $stmt->close();
        return $so > 0;
    }

    /** Bước 2-4: danh sách phiếu phúc khảo của kỳ. */
    public function danhSachPhieu(int $maKy): array
    {
        $stmt = $this->conn->prepare("
            SELECT p.maphieu, p.mahocsinh, p.ngaytao, p.lydo, p.trangthai,
                   hs.sobaodanh, ho.ho, ho.ten,
                   (SELECT COUNT(*) FROM chitietphuckhao c WHERE c.maphieu = p.maphieu) AS somon,
                   (SELECT COUNT(*) FROM baithi b WHERE b.mahocsinh = p.mahocsinh) AS sobaithi,
                   mc.tenfilegoc
            FROM phieuphuckhao p
            INNER JOIN hocsinh hs       ON hs.mahocsinh = p.mahocsinh
            INNER JOIN hosonguoidung ho ON ho.manguoidung = hs.manguoidung
            LEFT JOIN minhchungphuckhao mc ON mc.maphieu = p.maphieu
            WHERE hs.makythi = ?
            ORDER BY (p.trangthai IN ('HOAN_THANH', 'TU_CHOI')), p.ngaytao, p.maphieu
        ");
        $stmt->bind_param('i', $maKy);
        $stmt->execute();
        $ds = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
        $stmt->close();
        return $ds;
    }

    /** Một phiếu (chỉ khi thuộc đúng kỳ). */
    public function layPhieu(int $maKy, int $maPhieu): ?array
    {
        foreach ($this->danhSachPhieu($maKy) as $p) {
            if ((int)$p['maphieu'] === $maPhieu) {
                return $p;
            }
        }
        return null;
    }

    /**
     * Bước 6: bài thi của phiếu.
     * - Phiếu đã có chi tiết (học sinh chọn môn phúc khảo) -> chỉ các bài thi đó.
     * - Phiếu chưa có chi tiết -> mọi bài thi của học sinh.
     */
    public function baiThiCuaPhieu(int $maPhieu, int $maHocSinh, bool $khoaDong = false): array
    {
        $sql = "
            SELECT bt.mabaithi, bt.mamonhoc, m.tenmonhoc, bt.sophach, bt.diem, bt.vang,
                   c.machitiet, c.diemsauphuckhao
            FROM baithi bt
            INNER JOIN monhoc m ON m.mamonhoc = bt.mamonhoc
            LEFT JOIN chitietphuckhao c
                   ON c.mabaithi = bt.mabaithi AND c.maphieu = ?
            WHERE bt.mahocsinh = ?
              AND m.lamonthi = 1
              AND (
                    NOT EXISTS (SELECT 1 FROM chitietphuckhao c2 WHERE c2.maphieu = ?)
                    OR c.machitiet IS NOT NULL
              )
            ORDER BY bt.mamonhoc
        " . ($khoaDong ? ' FOR UPDATE' : '');

        $stmt = $this->conn->prepare($sql);
        $stmt->bind_param('iii', $maPhieu, $maHocSinh, $maPhieu);
        $stmt->execute();
        $ds = [];
        foreach ($stmt->get_result()->fetch_all(MYSQLI_ASSOC) as $r) {
            $ds[(int)$r['mabaithi']] = $r;
        }
        $stmt->close();
        return $ds;
    }

    /**
     * Bước 12-15: lưu điểm phúc khảo trong 1 transaction:
     *  - ghi chitietphuckhao.diemsauphuckhao
     *  - cập nhật baithi.diem theo kết quả phúc khảo
     *  - ghi nhatkyhethong (điểm ban đầu, điểm phúc khảo, người, thời gian)
     *  - phiếu -> 'HOAN_THANH' (Đã cập nhật kết quả)
     * Lỗi bất kỳ -> rollback: điểm ban đầu không đổi (luồng 12.1).
     *
     * @param array $diemMoi [mabaithi => 'x.xx']
     */
    public function luuPhucKhao(int $maKy, int $maPhieu, array $diemMoi, ?int $nguoiNhap): int
    {
        if (!$diemMoi) {
            throw new RuntimeException('Không có điểm phúc khảo để lưu.');
        }

        $this->conn->begin_transaction();

        try {
            // Khóa phiếu
            $stmt = $this->conn->prepare("
                SELECT p.maphieu, p.mahocsinh, p.trangthai, hs.sobaodanh
                FROM phieuphuckhao p
                INNER JOIN hocsinh hs ON hs.mahocsinh = p.mahocsinh
                WHERE p.maphieu = ? AND hs.makythi = ?
                FOR UPDATE
            ");
            $stmt->bind_param('ii', $maPhieu, $maKy);
            $stmt->execute();
            $phieu = $stmt->get_result()->fetch_assoc();
            $stmt->close();

            if (!$phieu) {
                throw new RuntimeException('Không tìm thấy phiếu phúc khảo.');
            }
            if ($phieu['trangthai'] === self::DA_CAP_NHAT) {
                throw new RuntimeException('Phiếu phúc khảo đã được cập nhật kết quả trước đó.');
            }
            if ($phieu['trangthai'] === self::TU_CHOI) {
                throw new RuntimeException('Phiếu phúc khảo đã bị từ chối, không nhập điểm.');
            }

            $baiThi = $this->baiThiCuaPhieu($maPhieu, (int)$phieu['mahocsinh'], true);

            $themCT = $this->conn->prepare("
                INSERT INTO chitietphuckhao (maphieu, mabaithi, diemsauphuckhao) VALUES (?, ?, ?)
            ");
            $suaCT = $this->conn->prepare("
                UPDATE chitietphuckhao SET diemsauphuckhao = ? WHERE machitiet = ?
            ");
            $suaDiem = $this->conn->prepare("
                UPDATE baithi SET diem = ? WHERE mabaithi = ?
            ");
            $ghiLog = $this->conn->prepare("
                INSERT INTO nhatkyhethong (manguoidung, hanhdong, noidung)
                VALUES (?, 'NHAP_DIEM_PHUC_KHAO', ?)
            ");

            $so = 0;
            foreach ($diemMoi as $ma => $diem) {
                $ma = (int)$ma;
                $diem = (string)$diem;

                if (!isset($baiThi[$ma])) {
                    throw new RuntimeException("Bài thi $ma không thuộc phiếu phúc khảo $maPhieu.");
                }
                $bt = $baiThi[$ma];
                if ((int)$bt['vang'] === 1) {
                    throw new RuntimeException("Bài thi môn {$bt['tenmonhoc']}: thí sinh vắng thi.");
                }

                // 1. Chi tiết phúc khảo
                if ($bt['machitiet'] === null) {
                    $themCT->bind_param('iis', $maPhieu, $ma, $diem);
                    $themCT->execute();
                    if ($themCT->errno) {
                        throw new RuntimeException('Lỗi ghi chi tiết phúc khảo: ' . $themCT->error);
                    }
                } else {
                    $maCT = (int)$bt['machitiet'];
                    $suaCT->bind_param('si', $diem, $maCT);
                    $suaCT->execute();
                    if ($suaCT->errno) {
                        throw new RuntimeException('Lỗi cập nhật chi tiết phúc khảo: ' . $suaCT->error);
                    }
                }

                // 2. Điểm của thí sinh theo kết quả phúc khảo
                $suaDiem->bind_param('si', $diem, $ma);
                $suaDiem->execute();
                if ($suaDiem->errno) {
                    throw new RuntimeException('Lỗi cập nhật điểm bài thi: ' . $suaDiem->error);
                }

                // 3. Nhật ký: điểm ban đầu, điểm phúc khảo, người, thời gian
                $cu = $bt['diem'] === null ? 'chưa có' : number_format((float)$bt['diem'], 2, '.', '');
                $noiDung = mb_substr(sprintf(
                    'Phiếu %d, SBD %s, môn %s, bài thi %d: điểm ban đầu %s -> điểm phúc khảo %s',
                    $maPhieu, $phieu['sobaodanh'] ?? '-', $bt['tenmonhoc'], $ma, $cu, $diem
                ), 0, 500);
                $ghiLog->bind_param('is', $nguoiNhap, $noiDung);
                $ghiLog->execute();
                if ($ghiLog->errno) {
                    throw new RuntimeException('Lỗi ghi nhật ký: ' . $ghiLog->error);
                }

                $so++;
            }

            $themCT->close();
            $suaCT->close();
            $suaDiem->close();
            $ghiLog->close();

            // 4. Trạng thái phiếu
            $stmt = $this->conn->prepare("UPDATE phieuphuckhao SET trangthai = 'HOAN_THANH' WHERE maphieu = ?");
            $stmt->bind_param('i', $maPhieu);
            $stmt->execute();
            $stmt->close();

            $this->conn->commit();
            return $so;

        } catch (Throwable $e) {
            $this->conn->rollback();
            throw $e;
        }
    }
}
