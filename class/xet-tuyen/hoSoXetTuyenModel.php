<?php
namespace XetTuyen;

use mysqli;

class HoSoXetTuyenModel extends \BaseModel
{
    protected string $tableName = 'hocsinh';
    protected string $primaryKey = 'mahocsinh';

    protected array $fields = [
        'mahocsinh'   => 'int',
        'manguoidung' => 'int',
        'makythi'    => 'int',
        'sobaodanh'  => 'string',
        'tongdiem'   => 'decimal'
    ];

    public function __construct(mysqli $conn)
    {
        parent::__construct($conn);
    }

    /**
     * 1. Lấy danh sách kỳ tuyển sinh
     */
    public function danhSachKy(): array
    {
        $sql = "
            SELECT
                makythi,
                namtuyensinh
            FROM kythituyensinh
            ORDER BY namtuyensinh DESC, makythi DESC
        ";

        $result = $this->conn->query($sql);

        return $result->fetch_all(MYSQLI_ASSOC);
    }

    /**
     * 2. Lấy hồ sơ học sinh theo kỳ tuyển sinh
     */
    public function danhSachHoSo(int $maKy): array
    {
        $sql = "
            SELECT
                hs.mahocsinh,
                hs.sobaodanh,
                hs.makythi,
                hs.trangthaiduthi,
                hs.tongdiem,

                hs.matieuchuanuutien,
                hs.matieuchuankhuyenkhich,

                ho.ho,
                ho.ten,
                ho.ngaysinh,

                th.tentruong AS truongthcs

            FROM hocsinh hs

            INNER JOIN hosonguoidung ho
                ON ho.manguoidung = hs.manguoidung

            LEFT JOIN truonghoc th
                ON th.matruong = hs.matruongdanghoc

            WHERE hs.makythi = ?

            ORDER BY
                ho.ho,
                ho.ten,
                hs.mahocsinh
        ";

        $stmt = $this->conn->prepare($sql);
        $stmt->bind_param('i', $maKy);
        $stmt->execute();

        $dsHoSo = $stmt->get_result()
            ->fetch_all(MYSQLI_ASSOC);

        $stmt->close();

        if (empty($dsHoSo)) {
            return [];
        }

        // Lấy danh sách nguyện vọng
        $sqlNV = "
            SELECT
                nv.mahocsinh,
                nv.thutunguyenvong,
                nv.matruong,
                th.tentruong

            FROM nguyenvong nv

            INNER JOIN hocsinh hs
                ON hs.mahocsinh = nv.mahocsinh

            LEFT JOIN truonghoc th
                ON th.matruong = nv.matruong

            WHERE hs.makythi = ?

            ORDER BY
                nv.mahocsinh,
                nv.thutunguyenvong
        ";

        $stmt = $this->conn->prepare($sqlNV);
        $stmt->bind_param('i', $maKy);
        $stmt->execute();

        $dsNV = $stmt->get_result()
            ->fetch_all(MYSQLI_ASSOC);

        $stmt->close();

        // Nhóm nguyện vọng theo học sinh
        $nguyenVong = [];

        foreach ($dsNV as $nv) {
            $maHS = (int) $nv['mahocsinh'];

            $nguyenVong[$maHS][] = $nv;
        }

        // Ghép nguyện vọng vào hồ sơ
        foreach ($dsHoSo as &$hs) {
            $maHS = (int) $hs['mahocsinh'];

            $hs['nguyenvong'] =
                $nguyenVong[$maHS] ?? [];

            $hs['coDangKyNguyenVong'] =
                !empty($hs['nguyenvong']);
        }

        unset($hs);

        return $dsHoSo;
    }

    /**
     * 3. Lấy hệ số môn thi và tiêu chuẩn cộng điểm
     */
    public function layTieuChi(int $maKy): array
    {
        $sqlHeSo = "
            SELECT
                m.mamonhoc,
                m.tenmonhoc,
                h.giatri

            FROM hesodiem h

            INNER JOIN monhoc m
                ON m.mamonhoc = h.mamonhoc

            WHERE h.makythi = ?
              AND m.lamonthi = 1

            ORDER BY m.mamonhoc
        ";

        $stmt = $this->conn->prepare($sqlHeSo);
        $stmt->bind_param('i', $maKy);
        $stmt->execute();

        $heSo = $stmt->get_result()
            ->fetch_all(MYSQLI_ASSOC);

        $stmt->close();

        // Tiêu chuẩn cộng điểm
        $sqlCongDiem = "
            SELECT
                matieuchuan,
                loaitieuchuan,
                dieukienapdung,
                diemcong

            FROM tieuchuancongdiem

            WHERE makythi = ?

            ORDER BY
                (loaitieuchuan = 'UU_TIEN') DESC,
                matieuchuan
        ";

        $stmt = $this->conn->prepare($sqlCongDiem);
        $stmt->bind_param('i', $maKy);
        $stmt->execute();

        $congDiem = $stmt->get_result()
            ->fetch_all(MYSQLI_ASSOC);

        $stmt->close();

        return [
            'heSo' => $heSo,
            'congDiem' => $congDiem
        ];
    }

    /**
     * 4. Lấy điểm thi từng môn từ bảng baithi
     *
     * Trả về:
     * [
     *   mahocsinh => [
     *      [mamonhoc, tenmonhoc, diem, ...],
     *      ...
     *   ]
     * ]
     */
    public function layDiemThi(int $maKy): array
    {
        $sql = "
            SELECT
                bt.mahocsinh,
                bt.mamonhoc,
                m.tenmonhoc,
                bt.diem,
                bt.tyletrudiem,
                bt.lydotrudiem,
                heso.giatri AS heso

            FROM baithi bt

            INNER JOIN hocsinh hs
                ON hs.mahocsinh = bt.mahocsinh

            INNER JOIN monhoc m
                ON m.mamonhoc = bt.mamonhoc

            LEFT JOIN hesodiem heso
                ON heso.makythi = hs.makythi
               AND heso.mamonhoc = bt.mamonhoc

            WHERE hs.makythi = ?
              AND m.lamonthi = 1

            ORDER BY
                bt.mahocsinh,
                bt.mamonhoc
        ";

        $stmt = $this->conn->prepare($sql);
        $stmt->bind_param('i', $maKy);
        $stmt->execute();

        $dsDiem = $stmt->get_result()
            ->fetch_all(MYSQLI_ASSOC);

        $stmt->close();

        $ketQua = [];

        foreach ($dsDiem as $row) {
            $maHS = (int) $row['mahocsinh'];

            $ketQua[$maHS][] = $row;
        }

        return $ketQua;
    }

    /**
     * 5. Lấy danh sách hồ sơ kèm điểm thi
     */
    public function danhSachHoSoKemDiem(int $maKy): array
    {
        $dsHoSo = $this->danhSachHoSo($maKy);
        $dsDiem = $this->layDiemThi($maKy);

        foreach ($dsHoSo as &$hs) {
            $maHS = (int) $hs['mahocsinh'];

            $hs['diemthi'] = $dsDiem[$maHS] ?? [];

            // Chỉ sử dụng để hiển thị dữ liệu hiện có.
            // Chưa tự tính tổng điểm vì cần xác minh
            // quy tắc trừ điểm và điểm phúc khảo.
            $hs['tongDiemDaLuu'] = $hs['tongdiem'];
        }

        unset($hs);

        return $dsHoSo;
    }

    /**
     * 6. Lấy chỉ tiêu tuyển sinh
     */
    public function layChiTieuTuyenSinh(int $maKy): array
    {
        $sql = "
            SELECT
                ct.matruong,
                th.tentruong,
                ct.chitieu,
                ct.daduyet

            FROM chitieutuyensinh ct

            INNER JOIN truonghoc th
                ON th.matruong = ct.matruong

            WHERE ct.makythi = ?

            ORDER BY th.tentruong
        ";

        $stmt = $this->conn->prepare($sql);
        $stmt->bind_param('i', $maKy);
        $stmt->execute();

        $dsChiTieu = $stmt->get_result()
            ->fetch_all(MYSQLI_ASSOC);

        $stmt->close();

        return $dsChiTieu;
    }

    /**
     * 7. Lấy điểm chuẩn theo nguyện vọng
     */
    public function layDiemChuan(int $maKy): array
    {
        $sql = "
            SELECT
                dc.matruong,
                th.tentruong,
                dc.diemnv1,
                dc.diemnv2,
                dc.diemnv3

            FROM diemchuan dc

            INNER JOIN truonghoc th
                ON th.matruong = dc.matruong

            WHERE dc.makythi = ?

            ORDER BY th.tentruong
        ";

        $stmt = $this->conn->prepare($sql);
        $stmt->bind_param('i', $maKy);
        $stmt->execute();

        $dsDiemChuan = $stmt->get_result()
            ->fetch_all(MYSQLI_ASSOC);

        $stmt->close();

        return $dsDiemChuan;
    }

    /**
     * 8. Kiểm tra chỉ tiêu các trường
     * được học sinh đăng ký nguyện vọng
     */
    public function kiemTraChiTieu(int $maKy): array
    {
        $dsChiTieu = $this->layChiTieuTuyenSinh($maKy);

        $chiTieuTheoTruong = [];

        foreach ($dsChiTieu as $ct) {
            $maTruong = (int) $ct['matruong'];

            $chiTieuTheoTruong[$maTruong] = $ct;
        }

        $sql = "
            SELECT DISTINCT
                nv.matruong,
                th.tentruong

            FROM nguyenvong nv

            INNER JOIN hocsinh hs
                ON hs.mahocsinh = nv.mahocsinh

            LEFT JOIN truonghoc th
                ON th.matruong = nv.matruong

            WHERE hs.makythi = ?

            ORDER BY th.tentruong
        ";

        $stmt = $this->conn->prepare($sql);
        $stmt->bind_param('i', $maKy);
        $stmt->execute();

        $dsTruong = $stmt->get_result()
            ->fetch_all(MYSQLI_ASSOC);

        $stmt->close();

        $dsLoi = [];

        foreach ($dsTruong as $truong) {
            $maTruong = (int) $truong['matruong'];

            $tenTruong = $truong['tentruong']
                ?: 'Mã trường ' . $maTruong;

            if (!isset($chiTieuTheoTruong[$maTruong])) {
                $dsLoi[] = [
                    'matruong' => $maTruong,
                    'tentruong' => $tenTruong,
                    'lydo' => 'Chưa thiết lập chỉ tiêu'
                ];

                continue;
            }

            $ct = $chiTieuTheoTruong[$maTruong];

            if ((int) $ct['daduyet'] !== 1) {
                $dsLoi[] = [
                    'matruong' => $maTruong,
                    'tentruong' => $tenTruong,
                    'lydo' => 'Chỉ tiêu chưa được duyệt'
                ];

                continue;
            }

            if ((int) $ct['chitieu'] <= 0) {
                $dsLoi[] = [
                    'matruong' => $maTruong,
                    'tentruong' => $tenTruong,
                    'lydo' => 'Chỉ tiêu không hợp lệ'
                ];
            }
        }

        return $dsLoi;
    }

    /** Danh sách môn thi đang được sử dụng để xét tuyển. */
    public function layDanhSachMonThi(): array
    {
        $result = $this->conn->query("
            SELECT mamonhoc, tenmonhoc
            FROM monhoc
            WHERE lamonthi = 1
            ORDER BY mamonhoc
        ");
        return $result->fetch_all(MYSQLI_ASSOC);
    }
}
