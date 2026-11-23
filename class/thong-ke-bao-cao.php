<?php

final class ThongKeBaoCao
{
    public const LOAI = ['truong' => 'Theo trường THPT', 'khuvuc' => 'Theo khu vực', 'ketqua' => 'Theo kết quả tuyển sinh'];
    public const KET_QUA = ['DAU' => 'Trúng tuyển, chưa xác nhận nhập học', 'ROT' => 'Không trúng tuyển', 'DANG_XET' => 'Đang xét', 'XAC_NHAN_NHAP_HOC' => 'Đã xác nhận nhập học'];
    public const COT = ['nhom' => 'Nhóm thống kê', 'thisinh' => 'Số thí sinh', 'luot' => 'Lượt nguyện vọng', 'dau' => 'Trúng tuyển', 'rot' => 'Không trúng tuyển', 'dangxet' => 'Đang xét', 'nhaphoc' => 'Đã xác nhận nhập học'];

    public function __construct(private mysqli $conn) {}

    public function tieuChi(): array
    {
        return [
            'ky' => $this->conn->query('SELECT makythi, namtuyensinh FROM kythituyensinh ORDER BY namtuyensinh DESC')->fetch_all(MYSQLI_ASSOC),
            'truong' => $this->conn->query("SELECT matruong, tentruong FROM truonghoc WHERE loaitruong = 'THPT' ORDER BY tentruong")->fetch_all(MYSQLI_ASSOC),
            'khuvuc' => array_column($this->conn->query("SELECT DISTINCT TRIM(SUBSTRING_INDEX(diachi, ',', 1)) AS khuvuc FROM truonghoc WHERE loaitruong = 'THPT' AND diachi IS NOT NULL AND TRIM(diachi) <> '' ORDER BY khuvuc")->fetch_all(MYSQLI_ASSOC), 'khuvuc'),
        ];
    }

    public static function kiemTra(array $f, array $options): array
    {
        $errors = [];
        if (!isset(self::LOAI[$f['loai']])) $errors[] = 'Vui lòng chọn loại thống kê hợp lệ.';
        if (!in_array($f['ky'], array_map('strval', array_column($options['ky'], 'makythi')), true)) $errors[] = 'Vui lòng chọn năm tuyển sinh hợp lệ.';
        if ($f['truong'] !== '' && !in_array($f['truong'], array_map('strval', array_column($options['truong'], 'matruong')), true)) $errors[] = 'Trường THPT không hợp lệ.';
        if ($f['khuvuc'] !== '' && !in_array($f['khuvuc'], $options['khuvuc'], true)) $errors[] = 'Khu vực không hợp lệ.';
        if (!in_array($f['nv'], ['', '1', '2', '3'], true)) $errors[] = 'Nguyện vọng phải từ 1 đến 3.';
        if ($f['ketqua'] !== '' && !isset(self::KET_QUA[$f['ketqua']])) $errors[] = 'Kết quả tuyển sinh không hợp lệ.';
        return $errors;
    }

    public function thongKe(array $f): array
    {
        $area = "COALESCE(NULLIF(TRIM(SUBSTRING_INDEX(t.diachi, ',', 1)), ''), 'Chưa cập nhật khu vực')";
        // Only fixed, allowlisted expressions are interpolated; filter values are bound.
        [$group, $label] = match ($f['loai']) {
            'truong' => ['t.matruong, t.tentruong', 't.tentruong'],
            'khuvuc' => [$area, $area],
            'ketqua' => ['nv.trangthaixettuyen', 'nv.trangthaixettuyen'],
            default => throw new InvalidArgumentException('Loại thống kê không hợp lệ.'),
        };
        $from = " FROM nguyenvong nv
            INNER JOIN hocsinh hs ON hs.mahocsinh = nv.mahocsinh
            INNER JOIN truonghoc t ON t.matruong = nv.matruong AND t.loaitruong = 'THPT'
            WHERE hs.makythi = ? AND (? = '' OR t.matruong = ?)
                AND (? = '' OR $area = ?) AND (? = '' OR nv.thutunguyenvong = ?)
                AND (? = '' OR nv.trangthaixettuyen = ?)";
        $metrics = "COUNT(DISTINCT hs.mahocsinh) AS thisinh, COUNT(*) AS luot,
            SUM(nv.trangthaixettuyen IN ('DAU', 'XAC_NHAN_NHAP_HOC')) AS dau,
            SUM(nv.trangthaixettuyen = 'ROT') AS rot,
            SUM(nv.trangthaixettuyen = 'DANG_XET') AS dangxet,
            SUM(nv.trangthaixettuyen = 'XAC_NHAN_NHAP_HOC') AS nhaphoc";
        $params = [$f['ky'], $f['truong'], $f['truong'], $f['khuvuc'], $f['khuvuc'], $f['nv'], $f['nv'], $f['ketqua'], $f['ketqua']];
        $query = function (string $sql) use ($params): array {
            $stmt = $this->conn->prepare($sql);
            $stmt->bind_param('sssssssss', ...$params);
            $stmt->execute();
            $rows = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
            $stmt->close();
            return $rows;
        };
        $rows = $query("SELECT $label AS nhom, $metrics $from GROUP BY $group ORDER BY nhom");
        if ($f['loai'] === 'ketqua') {
            foreach ($rows as &$row) $row['nhom'] = self::KET_QUA[$row['nhom']] ?? 'Chưa xác định';
            unset($row);
        }
        // Distinct candidates must be counted across the filtered set, never summed across schools.
        $total = $query("SELECT $metrics $from")[0];
        return ['rows' => $rows, 'total' => $total];
    }

    public static function xuatCsv(array $report, $stream): void
    {
        // Prevent spreadsheet formula evaluation in labels sourced from the database.
        $write = static function (array $cells) use ($stream): void {
            $cells = array_map(static function ($v) {
                $v = (string)$v;
                return preg_match('/^[\s]*[=+@-]/u', $v) ? "'" . $v : $v;
            }, $cells);
            fputcsv($stream, $cells, ',', '"', '');
        };
        fwrite($stream, "\xEF\xBB\xBF");
        $write(['BÁO CÁO TUYỂN SINH LỚP 10']);
        foreach ($report['criteria'] as $key => $value) $write([$key, $value]);
        $write(['Thời điểm thống kê', $report['time']]);
        $write(['Ghi chú', 'Kết quả tính theo lượt nguyện vọng. Trúng tuyển bao gồm đã xác nhận nhập học. Số thí sinh tổng được đếm không trùng.']);
        $write(array_values(self::COT));
        foreach ($report['rows'] as $row) $write(array_map(static fn($key) => $row[$key] ?? 0, array_keys(self::COT)));
        $write(array_merge(['Tổng (thí sinh không trùng)'], array_map(static fn($key) => $report['total'][$key] ?? 0, array_slice(array_keys(self::COT), 1))));
    }
}
