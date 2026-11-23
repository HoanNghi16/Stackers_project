<?php

final class ThongKeBaoCao
{
    public const LOAI = ['truong' => 'Theo trường THPT'];
    public const KET_QUA = ['DAU' => 'Trúng tuyển, chưa xác nhận nhập học', 'ROT' => 'Không trúng tuyển', 'DANG_XET' => 'Đang xét', 'XAC_NHAN_NHAP_HOC' => 'Đã xác nhận nhập học'];
    public const COT = ['nhom' => 'Nhóm thống kê', 'thisinh' => 'Số thí sinh', 'luot' => 'Lượt nguyện vọng', 'dau' => 'Trúng tuyển', 'rot' => 'Không trúng tuyển', 'dangxet' => 'Đang xét', 'nhaphoc' => 'Đã xác nhận nhập học'];

    public function __construct(private mysqli $conn) {}

    /** Danh sách trường được liên kết với phường bằng khóa ngoại maphuong. */
    public function tieuChi(): array
    {
        return [
            'ky' => $this->conn->query('SELECT makythi, namtuyensinh FROM kythituyensinh ORDER BY namtuyensinh DESC')->fetch_all(MYSQLI_ASSOC),
            'truong' => $this->conn->query("SELECT t.matruong, t.tentruong, t.maphuong FROM truonghoc t INNER JOIN phuong p ON p.maphuong = t.maphuong WHERE t.loaitruong = 'THPT' ORDER BY t.tentruong")->fetch_all(MYSQLI_ASSOC),
            'phuong' => $this->conn->query("SELECT DISTINCT p.maphuong, p.tenphuong FROM phuong p INNER JOIN truonghoc t ON t.maphuong = p.maphuong WHERE t.loaitruong = 'THPT' ORDER BY p.tenphuong")->fetch_all(MYSQLI_ASSOC),
        ];
    }

    public static function kiemTra(array $f, array $options): array
    {
        $errors = [];
        if (($f['loai'] ?? '') !== 'truong') $errors[] = 'Loại thống kê không hợp lệ.';
        if (!in_array($f['ky'], array_map('strval', array_column($options['ky'], 'makythi')), true)) $errors[] = 'Vui lòng chọn năm tuyển sinh hợp lệ.';
        if ($f['truong'] !== '' && !in_array($f['truong'], array_map('strval', array_column($options['truong'], 'matruong')), true)) $errors[] = 'Trường THPT không hợp lệ.';
        if ($f['phuong'] !== '' && !in_array($f['phuong'], array_map('strval', array_column($options['phuong'], 'maphuong')), true)) $errors[] = 'Phường/Xã không hợp lệ.';
        if ($f['truong'] !== '' && $f['phuong'] !== '') {
            foreach ($options['truong'] as $school) {
                if ((string)$school['matruong'] === $f['truong'] && (string)$school['maphuong'] !== $f['phuong']) {
                    $errors[] = 'Trường THPT đã chọn không thuộc phường/xã đã chọn.';
                    break;
                }
            }
        }
        if (!in_array($f['nv'], ['', '1', '2', '3'], true)) $errors[] = 'Nguyện vọng phải từ 1 đến 3.';
        if ($f['ketqua'] !== '' && !isset(self::KET_QUA[$f['ketqua']])) $errors[] = 'Kết quả tuyển sinh không hợp lệ.';
        return $errors;
    }

    public function thongKe(array $f): array
    {
        // Nhóm theo trường THPT; phường dùng khóa ngoại, không cắt địa chỉ.
        $from = " FROM nguyenvong nv
            INNER JOIN hocsinh hs ON hs.mahocsinh = nv.mahocsinh
            INNER JOIN truonghoc t ON t.matruong = nv.matruong AND t.loaitruong = 'THPT'
            WHERE hs.makythi = ? AND (? = '' OR t.matruong = ?)
                AND (? = '' OR t.maphuong = ?) AND (? = '' OR nv.thutunguyenvong = ?)
                AND (? = '' OR nv.trangthaixettuyen = ?)";
        $metrics = "COUNT(DISTINCT hs.mahocsinh) AS thisinh, COUNT(*) AS luot,
            SUM(nv.trangthaixettuyen IN ('DAU', 'XAC_NHAN_NHAP_HOC')) AS dau,
            SUM(nv.trangthaixettuyen = 'ROT') AS rot,
            SUM(nv.trangthaixettuyen = 'DANG_XET') AS dangxet,
            SUM(nv.trangthaixettuyen = 'XAC_NHAN_NHAP_HOC') AS nhaphoc";
        $params = [$f['ky'], $f['truong'], $f['truong'], $f['phuong'], $f['phuong'], $f['nv'], $f['nv'], $f['ketqua'], $f['ketqua']];
        $query = function (string $sql) use ($params): array {
            $stmt = $this->conn->prepare($sql);
            $stmt->bind_param('sssssssss', ...$params);
            $stmt->execute();
            $rows = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
            $stmt->close();
            return $rows;
        };
        $rows = $query("SELECT t.tentruong AS nhom, $metrics $from GROUP BY t.matruong, t.tentruong ORDER BY nhom");
        $total = $query("SELECT $metrics $from")[0];
        return ['rows' => $rows, 'total' => $total];
    }

    public static function xuatExcel(array $report): string
    {
        $rows = [['BÁO CÁO TUYỂN SINH LỚP 10']];
        foreach ($report['criteria'] as $key => $value) $rows[] = [$key, $value];
        $rows[] = ['Thời điểm thống kê', $report['time']];
        $rows[] = ['Ghi chú', 'Kết quả tính theo lượt nguyện vọng. Trúng tuyển bao gồm đã xác nhận nhập học. Tổng thí sinh được đếm không trùng.'];
        $rows[] = [];
        $rows[] = array_values(self::COT);
        $headerRow = count($rows);
        foreach ($report['rows'] as $row) {
            $rows[] = array_merge([(string)$row['nhom']], array_map(
                static fn($key) => (int)($row[$key] ?? 0), array_slice(array_keys(self::COT), 1)
            ));
        }
        $rows[] = array_merge(['Tổng (thí sinh không trùng)'], array_map(
            static fn($key) => (int)($report['total'][$key] ?? 0), array_slice(array_keys(self::COT), 1)
        ));
        $escape = static fn($value) => htmlspecialchars(
            preg_replace('/[^\x{0009}\x{000A}\x{000D}\x{0020}-\x{D7FF}\x{E000}-\x{FFFD}\x{10000}-\x{10FFFF}]/u', '', (string)$value) ?? '',
            ENT_XML1 | ENT_QUOTES, 'UTF-8'
        );
        $sheet = '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'
            . '<worksheet xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main">'
            . '<sheetViews><sheetView workbookViewId="0"><pane ySplit="' . $headerRow . '" topLeftCell="A' . ($headerRow + 1) . '" activePane="bottomLeft" state="frozen"/></sheetView></sheetViews>'
            . '<cols><col min="1" max="1" width="42" customWidth="1"/><col min="2" max="7" width="22" customWidth="1"/></cols><sheetData>';
        foreach ($rows as $index => $cells) {
            $number = $index + 1;
            $style = ($number === 1 || $number === $headerRow || $number === count($rows)) ? 1 : 0;
            $height = $number === $headerRow ? 42 : ($number === $headerRow - 2 ? 48 : 30);
            $sheet .= '<row r="' . $number . '" ht="' . $height . '" customHeight="1">';
            foreach ($cells as $column => $value) {
                $ref = chr(65 + $column) . $number;
                // Explicit string cells keep database labels from becoming formulas.
                $sheet .= is_int($value)
                    ? '<c r="' . $ref . '" s="' . $style . '"><v>' . $value . '</v></c>'
                    : '<c r="' . $ref . '" s="' . $style . '" t="inlineStr"><is><t xml:space="preserve">' . $escape($value) . '</t></is></c>';
            }
            $sheet .= '</row>';
        }
        $sheet .= '</sheetData><autoFilter ref="A' . $headerRow . ':G' . (count($rows) - 1) . '"/><mergeCells count="' . ($headerRow - 2) . '"><mergeCell ref="A1:G1"/>';
        for ($r = 2; $r <= $headerRow - 2; $r++) $sheet .= '<mergeCell ref="B' . $r . ':G' . $r . '"/>';
        $sheet .= '</mergeCells></worksheet>';
        $parts = [
            '[Content_Types].xml' => '<Types xmlns="http://schemas.openxmlformats.org/package/2006/content-types"><Default Extension="rels" ContentType="application/vnd.openxmlformats-package.relationships+xml"/><Default Extension="xml" ContentType="application/xml"/><Override PartName="/xl/workbook.xml" ContentType="application/vnd.openxmlformats-officedocument.spreadsheetml.sheet.main+xml"/><Override PartName="/xl/worksheets/sheet1.xml" ContentType="application/vnd.openxmlformats-officedocument.spreadsheetml.worksheet+xml"/><Override PartName="/xl/styles.xml" ContentType="application/vnd.openxmlformats-officedocument.spreadsheetml.styles+xml"/></Types>',
            '_rels/.rels' => '<Relationships xmlns="http://schemas.openxmlformats.org/package/2006/relationships"><Relationship Id="rId1" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/officeDocument" Target="xl/workbook.xml"/></Relationships>',
            'xl/workbook.xml' => '<workbook xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main" xmlns:r="http://schemas.openxmlformats.org/officeDocument/2006/relationships"><sheets><sheet name="Báo cáo tuyển sinh" sheetId="1" r:id="rId1"/></sheets></workbook>',
            'xl/_rels/workbook.xml.rels' => '<Relationships xmlns="http://schemas.openxmlformats.org/package/2006/relationships"><Relationship Id="rId1" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/worksheet" Target="worksheets/sheet1.xml"/><Relationship Id="rId2" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/styles" Target="styles.xml"/></Relationships>',
            'xl/styles.xml' => '<styleSheet xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main"><fonts count="2"><font><sz val="11"/><name val="Calibri"/></font><font><b/><sz val="11"/><name val="Calibri"/></font></fonts><fills count="2"><fill><patternFill patternType="none"/></fill><fill><patternFill patternType="gray125"/></fill></fills><borders count="1"><border/></borders><cellStyleXfs count="1"><xf numFmtId="0" fontId="0" fillId="0" borderId="0"/></cellStyleXfs><cellXfs count="2"><xf numFmtId="0" fontId="0" fillId="0" borderId="0" xfId="0" applyAlignment="1"><alignment vertical="center" wrapText="1"/></xf><xf numFmtId="0" fontId="1" fillId="0" borderId="0" xfId="0" applyFont="1" applyAlignment="1"><alignment vertical="center" wrapText="1"/></xf></cellXfs><cellStyles count="1"><cellStyle name="Normal" xfId="0" builtinId="0"/></cellStyles></styleSheet>',
            'xl/worksheets/sheet1.xml' => $sheet,
        ];
        $path = tempnam(sys_get_temp_dir(), 'stackers-xlsx-');
        if ($path === false) throw new RuntimeException('Không tạo được file Excel tạm.');
        try {
            $zip = new ZipArchive();
            if ($zip->open($path, ZipArchive::OVERWRITE) !== true) throw new RuntimeException('Không tạo được file Excel.');
            foreach ($parts as $name => $xml) {
                if (!$zip->addFromString($name, $xml)) throw new RuntimeException('Không ghi được dữ liệu Excel.');
            }
            if (!$zip->close()) throw new RuntimeException('Không hoàn tất được file Excel.');
            $data = file_get_contents($path);
            if ($data === false) throw new RuntimeException('Không đọc được file Excel.');
            return $data;
        } finally {
            unlink($path);
        }
    }
}
