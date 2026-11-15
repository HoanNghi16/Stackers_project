<?php
namespace CongBoDiemChuan;

use mysqli;
use RuntimeException;
use Throwable;

/**
 * Truy cập dữ liệu cho chức năng Công bố điểm chuẩn.
 * Bảng chính: diemchuan (matruong, makythi, diemnv1..3,
 *             trangthaicongbo, thoigiancongbo, nguoicongbo)
 */
class DiemChuanModel
{
    public const CHUA_CONG_BO = 'CHUA_CONG_BO';
    public const CHO_CONG_BO  = 'CHO_CONG_BO';
    public const DA_CONG_BO   = 'DA_CONG_BO';

    private mysqli $conn;

    public function __construct(mysqli $conn)
    {
        $this->conn = $conn;
    }

    /** Danh sách kỳ tuyển sinh, mới nhất trước. */
    public function danhSachKy(): array
    {
        $result = $this->conn->query("
            SELECT makythi, namtuyensinh
            FROM kythituyensinh
            ORDER BY namtuyensinh DESC, makythi DESC
        ");
        return $result->fetch_all(MYSQLI_ASSOC);
    }

    /** Bảng diemchuan đã có các cột phục vụ công bố chưa (đã chạy file SQL chưa). */
    public function daCoCotCongBo(): bool
    {
        $result = $this->conn->query("SHOW COLUMNS FROM diemchuan LIKE 'trangthaicongbo'");
        return $result && $result->num_rows > 0;
    }

    /**
     * Danh sách các trường có chỉ tiêu đã duyệt trong kỳ, kèm điểm chuẩn (nếu có)
     * và số thí sinh trúng tuyển theo từng NV. Trường chưa có dòng trong
     * diemchuan vẫn được trả về với codiemchuan = 0.
     */
    public function danhSachDiemChuan(int $maKy): array
    {
        $stmt = $this->conn->prepare("
            SELECT
                ct.matruong,
                th.tentruong,
                ct.chitieu,
                (dc.matruong IS NOT NULL) AS codiemchuan,
                dc.diemnv1,
                dc.diemnv2,
                dc.diemnv3,
                COALESCE(dc.trangthaicongbo, 'CHUA_CONG_BO') AS trangthaicongbo,
                dc.thoigiancongbo,
                dc.nguoicongbo
            FROM chitieutuyensinh ct
            INNER JOIN truonghoc th
                ON th.matruong = ct.matruong
            LEFT JOIN diemchuan dc
                ON dc.makythi = ct.makythi
               AND dc.matruong = ct.matruong
            WHERE ct.makythi = ?
              AND ct.daduyet = 1
            ORDER BY th.tentruong
        ");
        $stmt->bind_param('i', $maKy);
        $stmt->execute();
        $ds = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
        $stmt->close();

        // Số trúng tuyển theo trường + thứ tự NV
        $stmt = $this->conn->prepare("
            SELECT nv.matruong, nv.thutunguyenvong, COUNT(*) AS so
            FROM nguyenvong nv
            INNER JOIN hocsinh hs ON hs.mahocsinh = nv.mahocsinh
            WHERE hs.makythi = ?
              AND nv.trangthaixettuyen IN ('DAU', 'XAC_NHAN_NHAP_HOC')
            GROUP BY nv.matruong, nv.thutunguyenvong
        ");
        $stmt->bind_param('i', $maKy);
        $stmt->execute();
        $dem = [];
        foreach ($stmt->get_result()->fetch_all(MYSQLI_ASSOC) as $r) {
            $dem[(int)$r['matruong']][(int)$r['thutunguyenvong']] = (int)$r['so'];
        }
        $stmt->close();

        foreach ($ds as &$r) {
            $mt = (int)$r['matruong'];
            $r['matruong']    = $mt;
            $r['chitieu']     = (int)$r['chitieu'];
            $r['codiemchuan'] = (int)$r['codiemchuan'] === 1;
            for ($i = 1; $i <= 3; $i++) {
                $r['trungtuyennv' . $i] = $dem[$mt][$i] ?? 0;
            }
            $r['sotrungtuyen'] = $r['trungtuyennv1'] + $r['trungtuyennv2'] + $r['trungtuyennv3'];
        }
        unset($r);

        return $ds;
    }

    /**
     * Trạng thái xét tuyển của kỳ, dựa trên bảng nguyenvong.
     * Hoàn tất = có nguyện vọng và không còn nguyện vọng nào 'DANG_XET'.
     */
    public function trangThaiXetTuyen(int $maKy): array
    {
        $stmt = $this->conn->prepare("
            SELECT
                COUNT(*) AS tong,
                COALESCE(SUM(nv.trangthaixettuyen = 'DANG_XET'), 0) AS dangxet
            FROM nguyenvong nv
            INNER JOIN hocsinh hs ON hs.mahocsinh = nv.mahocsinh
            WHERE hs.makythi = ?
        ");
        $stmt->bind_param('i', $maKy);
        $stmt->execute();
        $r = $stmt->get_result()->fetch_assoc();
        $stmt->close();

        $tong = (int)($r['tong'] ?? 0);
        $dangXet = (int)($r['dangxet'] ?? 0);

        return [
            'tong'    => $tong,
            'dangXet' => $dangXet,
            'hoanTat' => $tong > 0 && $dangXet === 0,
        ];
    }

    /**
     * Chuyển toàn bộ điểm chuẩn của kỳ sang trạng thái mới
     * (CHO_CONG_BO hoặc DA_CONG_BO) trong 1 transaction.
     * Lỗi bất kỳ -> rollback, trạng thái giữ nguyên.
     */
    public function capNhatTrangThai(int $maKy, string $trangThaiMoi, ?int $nguoiThucHien): int
    {
        if (!in_array($trangThaiMoi, [self::CHO_CONG_BO, self::DA_CONG_BO], true)) {
            throw new RuntimeException('Trạng thái công bố không hợp lệ.');
        }

        $this->conn->begin_transaction();

        try {
            $stmt = $this->conn->prepare("
                SELECT matruong, trangthaicongbo
                FROM diemchuan
                WHERE makythi = ?
                FOR UPDATE
            ");
            $stmt->bind_param('i', $maKy);
            $stmt->execute();
            $dsCu = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
            $stmt->close();

            if (!$dsCu) {
                throw new RuntimeException('Kỳ tuyển sinh chưa có dữ liệu điểm chuẩn.');
            }
            foreach ($dsCu as $r) {
                if ($r['trangthaicongbo'] === self::DA_CONG_BO) {
                    throw new RuntimeException('Điểm chuẩn của kỳ này đã được công bố trước đó.');
                }
            }

            if ($trangThaiMoi === self::DA_CONG_BO) {
                $stmt = $this->conn->prepare("
                    UPDATE diemchuan
                    SET trangthaicongbo = 'DA_CONG_BO',
                        thoigiancongbo  = NOW(),
                        nguoicongbo     = ?
                    WHERE makythi = ?
                ");
                $stmt->bind_param('ii', $nguoiThucHien, $maKy);
            } else {
                $stmt = $this->conn->prepare("
                    UPDATE diemchuan
                    SET trangthaicongbo = 'CHO_CONG_BO',
                        thoigiancongbo  = NULL,
                        nguoicongbo     = NULL
                    WHERE makythi = ?
                ");
                $stmt->bind_param('i', $maKy);
            }
            $stmt->execute();
            $stmt->close();

            $this->conn->commit();
            return count($dsCu);

        } catch (Throwable $e) {
            $this->conn->rollback();
            throw $e;
        }
    }

    /**
     * Dùng cho trang tra cứu của người dùng: CHỈ trả về điểm chuẩn đã công bố.
     * (Bảng ở trạng thái Chờ công bố không hiển thị - luồng 7.1)
     */
    public function diemChuanDaCongBo(int $maKy): array
    {
        $stmt = $this->conn->prepare("
            SELECT dc.matruong, th.tentruong,
                   dc.diemnv1, dc.diemnv2, dc.diemnv3, dc.thoigiancongbo
            FROM diemchuan dc
            INNER JOIN truonghoc th ON th.matruong = dc.matruong
            WHERE dc.makythi = ?
              AND dc.trangthaicongbo = 'DA_CONG_BO'
            ORDER BY th.tentruong
        ");
        $stmt->bind_param('i', $maKy);
        $stmt->execute();
        $ds = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
        $stmt->close();
        return $ds;
    }
}
