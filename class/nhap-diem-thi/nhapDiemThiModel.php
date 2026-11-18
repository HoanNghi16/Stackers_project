<?php
namespace NhapDiemThi;

use mysqli;
use RuntimeException;
use Throwable;

/**
 * Truy cập dữ liệu cho chức năng Nhập điểm thi.
 * Bảng: baithi (mabaithi, mahocsinh, mamonhoc, sophach, diem, vang)
 *       nhatkyhethong (manguoidung, hanhdong, noidung, thoigian) - ghi người nhập + thời gian
 * Trạng thái điểm suy ra từ baithi.diem: NULL = chưa nhập.
 */
class NhapDiemThiModel
{
    private mysqli $conn;

    public function __construct(mysqli $conn)
    {
        $this->conn = $conn;
    }

    /** Kỳ tuyển sinh theo năm (trang dùng cố định năm 2026). */
    public function layKyTheoNam(int $nam): ?array
    {
        $stmt = $this->conn->prepare("
            SELECT makythi, namtuyensinh
            FROM kythituyensinh
            WHERE namtuyensinh = ?
            LIMIT 1
        ");
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

    /**
     * Bước 4: môn thi của kỳ + thống kê nhập điểm từng môn.
     */
    public function danhSachMonThi(int $maKy): array
    {
        $stmt = $this->conn->prepare("
            SELECT
                m.mamonhoc,
                m.tenmonhoc,
                COUNT(bt.mabaithi) AS tong,
                COALESCE(SUM(bt.vang = 1), 0) AS vang,
                COALESCE(SUM(bt.vang = 0 AND bt.diem IS NOT NULL), 0) AS danhap
            FROM monhoc m
            LEFT JOIN (
                baithi bt
                INNER JOIN hocsinh hs
                    ON hs.mahocsinh = bt.mahocsinh
                   AND hs.makythi = ?
            ) ON bt.mamonhoc = m.mamonhoc
            WHERE m.lamonthi = 1
            GROUP BY m.mamonhoc, m.tenmonhoc
            ORDER BY m.mamonhoc
        ");
        $stmt->bind_param('i', $maKy);
        $stmt->execute();
        $ds = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
        $stmt->close();

        foreach ($ds as &$m) {
            $m['mamonhoc'] = (int)$m['mamonhoc'];
            $m['tong']     = (int)$m['tong'];
            $m['vang']     = (int)$m['vang'];
            $m['danhap']   = (int)$m['danhap'];
            $m['chuanhap'] = max(0, $m['tong'] - $m['vang'] - $m['danhap']);
        }
        unset($m);

        return $ds;
    }

    /** Điều kiện lọc dùng chung cho danh sách bài thi. */
    private function dieuKienLoc(string $loc, string $tim, string &$types, array &$params): string
    {
        $sql = '';
        if ($loc === 'chuanhap') {
            $sql .= ' AND bt.vang = 0 AND bt.diem IS NULL';
        } elseif ($loc === 'danhap') {
            $sql .= ' AND bt.diem IS NOT NULL';
        } elseif ($loc === 'vang') {
            $sql .= ' AND bt.vang = 1';
        }
        if ($tim !== '') {
            $sql .= " AND (hs.sobaodanh LIKE ? OR bt.sophach LIKE ?
                           OR CONCAT(ho.ho, ' ', ho.ten) LIKE ?)";
            $like = '%' . $tim . '%';
            $types .= 'sss';
            array_push($params, $like, $like, $like);
        }
        return $sql;
    }

    /**
     * Bước 6: danh sách bài thi của 1 môn (có lọc, tìm kiếm, phân trang).
     * @return array{tong:int, ds:array}
     */
    public function danhSachBaiThi(int $maKy, int $maMon, string $loc, string $tim, int $offset, int $limit): array
    {
        $types = 'ii';
        $params = [$maKy, $maMon];
        $where = $this->dieuKienLoc($loc, $tim, $types, $params);

        $from = "
            FROM baithi bt
            INNER JOIN hocsinh hs       ON hs.mahocsinh = bt.mahocsinh
            INNER JOIN hosonguoidung ho ON ho.manguoidung = hs.manguoidung
            WHERE hs.makythi = ? AND bt.mamonhoc = ?
              $where
        ";

        $stmt = $this->conn->prepare("SELECT COUNT(*) AS so $from");
        $stmt->bind_param($types, ...$params);
        $stmt->execute();
        $tong = (int)$stmt->get_result()->fetch_assoc()['so'];
        $stmt->close();

        $stmt = $this->conn->prepare("
            SELECT bt.mabaithi, bt.sophach, bt.diem, bt.vang,
                   hs.mahocsinh, hs.sobaodanh, ho.ho, ho.ten
            $from
            ORDER BY (hs.sobaodanh IS NULL), hs.sobaodanh, bt.sophach, bt.mabaithi
            LIMIT ? OFFSET ?
        ");
        $types2 = $types . 'ii';
        $params2 = array_merge($params, [$limit, $offset]);
        $stmt->bind_param($types2, ...$params2);
        $stmt->execute();
        $ds = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
        $stmt->close();

        return ['tong' => $tong, 'ds' => $ds];
    }

    /** Lấy các bài thi theo mã (chỉ những bài thuộc đúng kỳ + môn). */
    public function layBaiThiTheoMa(int $maKy, int $maMon, array $dsMa): array
    {
        $dsMa = array_values(array_unique(array_map('intval', $dsMa)));
        if (!$dsMa) {
            return [];
        }
        $dauHoi = implode(',', array_fill(0, count($dsMa), '?'));
        $stmt = $this->conn->prepare("
            SELECT bt.mabaithi, bt.sophach, bt.diem, bt.vang,
                   hs.sobaodanh, ho.ho, ho.ten
            FROM baithi bt
            INNER JOIN hocsinh hs       ON hs.mahocsinh = bt.mahocsinh
            INNER JOIN hosonguoidung ho ON ho.manguoidung = hs.manguoidung
            WHERE hs.makythi = ? AND bt.mamonhoc = ?
              AND bt.mabaithi IN ($dauHoi)
        ");
        $stmt->bind_param('ii' . str_repeat('i', count($dsMa)), $maKy, $maMon, ...$dsMa);
        $stmt->execute();
        $ds = [];
        foreach ($stmt->get_result()->fetch_all(MYSQLI_ASSOC) as $r) {
            $ds[(int)$r['mabaithi']] = $r;
        }
        $stmt->close();
        return $ds;
    }

    /**
     * Bước 14-16: lưu điểm + ghi nhật ký (người nhập, thời gian) trong 1 transaction.
     * @param array $diemMoi [mabaithi => 'x.xx']
     * Lỗi bất kỳ -> rollback, dữ liệu điểm hiện tại không đổi (luồng 14.1).
     */
    public function luuDiem(int $maKy, int $maMon, string $tenMon, array $diemMoi, ?int $nguoiNhap): int
    {
        if (!$diemMoi) {
            throw new RuntimeException('Không có điểm để lưu.');
        }

        $this->conn->begin_transaction();

        try {
            $dsMa = array_map('intval', array_keys($diemMoi));
            $dauHoi = implode(',', array_fill(0, count($dsMa), '?'));

            // Khóa các dòng sẽ sửa + xác nhận đúng kỳ, đúng môn, không vắng
            $stmt = $this->conn->prepare("
                SELECT bt.mabaithi, bt.vang, bt.diem, bt.sophach, hs.sobaodanh
                FROM baithi bt
                INNER JOIN hocsinh hs ON hs.mahocsinh = bt.mahocsinh
                WHERE hs.makythi = ? AND bt.mamonhoc = ?
                      AND bt.mabaithi IN ($dauHoi)
                FOR UPDATE
            ");
            $stmt->bind_param('ii' . str_repeat('i', count($dsMa)), $maKy, $maMon, ...$dsMa);
            $stmt->execute();
            $hienCo = [];
            foreach ($stmt->get_result()->fetch_all(MYSQLI_ASSOC) as $r) {
                $hienCo[(int)$r['mabaithi']] = $r;
            }
            $stmt->close();

            foreach ($dsMa as $ma) {
                if (!isset($hienCo[$ma])) {
                    throw new RuntimeException("Bài thi $ma không thuộc môn/kỳ đang nhập.");
                }
                if ((int)$hienCo[$ma]['vang'] === 1) {
                    throw new RuntimeException("Bài thi $ma: thí sinh vắng thi, không nhập điểm.");
                }
            }

            $capNhat = $this->conn->prepare("
                UPDATE baithi SET diem = ?
                WHERE mabaithi = ? AND mamonhoc = ? AND vang = 0
            ");
            $ghiLog = $this->conn->prepare("
                INSERT INTO nhatkyhethong (manguoidung, hanhdong, noidung)
                VALUES (?, 'NHAP_DIEM_THI', ?)
            ");

            $so = 0;
            foreach ($diemMoi as $ma => $diem) {
                $ma = (int)$ma;
                $diem = (string)$diem;

                $capNhat->bind_param('sii', $diem, $ma, $maMon);
                $capNhat->execute();
                if ($capNhat->errno) {
                    throw new RuntimeException('Lỗi cập nhật bài thi ' . $ma . ': ' . $capNhat->error);
                }

                $cu = $hienCo[$ma]['diem'] === null
                    ? 'chưa có'
                    : number_format((float)$hienCo[$ma]['diem'], 2, '.', '');
                $noiDung = mb_substr(sprintf(
                    'Kỳ %d, môn %s, bài thi %d (SBD %s, phách %s): %s -> %s',
                    $maKy, $tenMon, $ma,
                    $hienCo[$ma]['sobaodanh'] ?? '-', $hienCo[$ma]['sophach'] ?? '-',
                    $cu, $diem
                ), 0, 500);
                $ghiLog->bind_param('is', $nguoiNhap, $noiDung);
                $ghiLog->execute();
                if ($ghiLog->errno) {
                    throw new RuntimeException('Lỗi ghi nhật ký: ' . $ghiLog->error);
                }

                $so++;
            }
            $capNhat->close();
            $ghiLog->close();

            $this->conn->commit();
            return $so;

        } catch (Throwable $e) {
            $this->conn->rollback();
            throw $e;
        }
    }
}
