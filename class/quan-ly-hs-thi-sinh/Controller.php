<?php

class CtrlQuanLyHoSoThiSinh
{
    private mysqli $conn;
    private const MAKYTHI = 1;

    public function __construct(mysqli $conn)
    {
        $this->conn = $conn;
    }

    private function giaoVienDangNhap(): array
    {
        $userId = (int)($_SESSION['manguoidung'] ?? 0);
        if ($userId < 1 || ($_SESSION['vaitro'] ?? '') !== 'THCS') {
            throw new RuntimeException('Bạn không có quyền truy cập chức năng này.');
        }
        $stmt = $this->conn->prepare("SELECT gv.magiaovien, gv.matruong
                                      FROM giaovien gv
                                      JOIN nguoidung nd ON nd.manguoidung = gv.manguoidung
                                      JOIN truonghoc th ON th.matruong = gv.matruong
                                      WHERE gv.manguoidung = ? AND nd.vaitro = 'THCS' AND th.loaitruong = 'THCS'
                                      LIMIT 1");
        $stmt->bind_param('i', $userId);
        $stmt->execute();
        $teacher = $stmt->get_result()->fetch_assoc();
        $stmt->close();
        if (!$teacher) {
            throw new RuntimeException('Tài khoản chưa được liên kết với trường THCS.');
        }
        return $teacher;
    }

    public function DanhSachHocSinhChuNhiem(array $filters = []): array
    {
        $teacher = $this->giaoVienDangNhap();
        $name = trim((string)($filters['name'] ?? ''));
        $cccd = trim((string)($filters['cccd'] ?? ''));
        $phone = trim((string)($filters['phone'] ?? ''));
        $nameLike = '%' . $name . '%';
        $cccdLike = '%' . $cccd . '%';
        $phoneLike = '%' . $phone . '%';
        $sql = "SELECT hcs.mahocsinh, CONCAT(hs.ho, ' ', hs.ten) AS ten,
                       hs.ngaysinh, hs.diachi, ph.tenphuong,
                       nd.sodienthoai, nd.email, hs.socccd, hcs.dantoc
                FROM hocsinh hcs
                JOIN nguoidung nd ON nd.manguoidung = hcs.manguoidung AND nd.vaitro = 'HOC_SINH'
                JOIN hosonguoidung hs ON hs.manguoidung = nd.manguoidung
                LEFT JOIN phuong ph ON ph.maphuong = hs.maphuong
                WHERE hcs.makythi = ? AND hcs.magiaovienchunhiem = ?
                  AND (? = '' OR CONCAT(hs.ho, ' ', hs.ten) LIKE ?)
                  AND (? = '' OR COALESCE(hs.socccd, '') LIKE ?)
                  AND (? = '' OR COALESCE(nd.sodienthoai, '') LIKE ?)
                ORDER BY hs.ho, hs.ten, hcs.mahocsinh";
        $stmt = $this->conn->prepare($sql);
        $examId = self::MAKYTHI;
        $stmt->bind_param('iissssss', $examId, $teacher['magiaovien'], $name, $nameLike, $cccd, $cccdLike, $phone, $phoneLike);
        $stmt->execute();
        $students = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
        $stmt->close();
        return $students;
    }

    public function ChiTietHocSinh(int $studentId): ?array
    {
        $teacher = $this->giaoVienDangNhap();
        $sql = "SELECT hcs.mahocsinh, hcs.dantoc, hcs.matruongdanghoc,
                       CONCAT(hs.ho, ' ', hs.ten) AS ten, hs.ho, hs.ten AS tenten,
                       hs.ngaysinh, hs.diachi, hs.maphuong, ph.tenphuong,
                       nd.sodienthoai, nd.email, hs.socccd
                FROM hocsinh hcs
                JOIN nguoidung nd ON nd.manguoidung = hcs.manguoidung
                JOIN hosonguoidung hs ON hs.manguoidung = nd.manguoidung
                LEFT JOIN phuong ph ON ph.maphuong = hs.maphuong
                WHERE hcs.mahocsinh = ? AND hcs.makythi = ? AND hcs.magiaovienchunhiem = ?
                LIMIT 1";
        $stmt = $this->conn->prepare($sql);
        $examId = self::MAKYTHI;
        $stmt->bind_param('iii', $studentId, $examId, $teacher['magiaovien']);
        $stmt->execute();
        $student = $stmt->get_result()->fetch_assoc() ?: null;
        $stmt->close();
        return $student;
    }

    public function DanhSachPhuong(): array
    {
        $result = $this->conn->query('SELECT maphuong, tenphuong FROM phuong ORDER BY tenphuong');
        return $result->fetch_all(MYSQLI_ASSOC);
    }

    public function SocccdDaTonTai(string $cccd): bool
    {
        if ($cccd === '') return false;
        $stmt = $this->conn->prepare('SELECT 1 FROM hosonguoidung WHERE socccd = ? LIMIT 1');
        $stmt->bind_param('s', $cccd);
        $stmt->execute();
        $exists = (bool)$stmt->get_result()->fetch_row();
        $stmt->close();
        return $exists;
    }

    public function ThemHocSinh(array $input): int
    {
        $profile = $this->chuanHoaHoSo($input);
        $teacher = $this->giaoVienDangNhap();
        $this->conn->begin_transaction();
        try {
            $role = 'HOC_SINH';
            $password = sha1('1');
            $phone = $profile['phone'] === '' ? null : $profile['phone'];
            $stmt = $this->conn->prepare('INSERT INTO nguoidung (vaitro, email, sodienthoai, matkhau) VALUES (?, ?, ?, ?)');
            $stmt->bind_param('ssss', $role, $profile['email'], $phone, $password);
            $stmt->execute();
            $userId = (int)$this->conn->insert_id;
            $stmt->close();

            $cccd = $profile['cccd'] === '' ? null : $profile['cccd'];
            $stmt = $this->conn->prepare('INSERT INTO hosonguoidung (manguoidung, ho, ten, diachi, maphuong, socccd, ngaysinh) VALUES (?, ?, ?, ?, ?, ?, ?)');
            $stmt->bind_param('isssiss', $userId, $profile['ho'], $profile['ten'], $profile['address'], $profile['wardId'], $cccd, $profile['dob']);
            $stmt->execute();
            $stmt->close();

            $dantoc = $profile['ethnic'] === '' ? null : $profile['ethnic'];
            $schoolId = (int)$teacher['matruong'];
            $teacherId = (int)$teacher['magiaovien'];
            $examId = self::MAKYTHI;
            $stmt = $this->conn->prepare('INSERT INTO hocsinh (manguoidung, makythi, matruongdanghoc, dantoc, magiaovienchunhiem) VALUES (?, ?, ?, ?, ?)');
            $stmt->bind_param('iiisi', $userId, $examId, $schoolId, $dantoc, $teacherId);
            $stmt->execute();
            $studentId = (int)$this->conn->insert_id;
            $stmt->close();
            $this->conn->commit();
            return $studentId;
        } catch (Throwable $e) {
            $this->conn->rollback();
            throw $e;
        }
    }

    public function CapNhatHocSinh(int $studentId, array $input): void
    {
        if (!$this->ChiTietHocSinh($studentId)) {
            throw new RuntimeException('Không tìm thấy hồ sơ hoặc bạn không có quyền chỉnh sửa hồ sơ này.');
        }
        $profile = $this->chuanHoaHoSo($input);
        $this->conn->begin_transaction();
        try {
            $stmt = $this->conn->prepare('SELECT manguoidung FROM hocsinh WHERE mahocsinh = ? AND makythi = ?');
            $examId = self::MAKYTHI;
            $stmt->bind_param('ii', $studentId, $examId);
            $stmt->execute();
            $userId = (int)($stmt->get_result()->fetch_assoc()['manguoidung'] ?? 0);
            $stmt->close();
            if ($userId < 1) throw new RuntimeException('Không tìm thấy tài khoản học sinh.');

            $phone = $profile['phone'] === '' ? null : $profile['phone'];
            $stmt = $this->conn->prepare('UPDATE nguoidung SET email = ?, sodienthoai = ? WHERE manguoidung = ?');
            $stmt->bind_param('ssi', $profile['email'], $phone, $userId);
            $stmt->execute();
            $stmt->close();

            $cccd = $profile['cccd'] === '' ? null : $profile['cccd'];
            $stmt = $this->conn->prepare('UPDATE hosonguoidung SET ho = ?, ten = ?, diachi = ?, maphuong = ?, socccd = ?, ngaysinh = ? WHERE manguoidung = ?');
            $stmt->bind_param('sssissi', $profile['ho'], $profile['ten'], $profile['address'], $profile['wardId'], $cccd, $profile['dob'], $userId);
            $stmt->execute();
            $stmt->close();

            $dantoc = $profile['ethnic'] === '' ? null : $profile['ethnic'];
            $stmt = $this->conn->prepare('UPDATE hocsinh SET dantoc = ? WHERE mahocsinh = ? AND makythi = ?');
            $stmt->bind_param('sii', $dantoc, $studentId, $examId);
            $stmt->execute();
            $stmt->close();
            $this->conn->commit();
        } catch (Throwable $e) {
            $this->conn->rollback();
            throw $e;
        }
    }

    public function NhapExcel(array $file): array
    {
        $rows = $this->docTepBangTinh($file);
        $result = ['imported' => 0, 'skipped' => 0, 'errors' => []];
        $seenCccd = [];
        foreach ($rows as $row) {
            $data = $row['data'];
            if (count(array_filter($data, static fn($v) => trim((string)$v) !== '')) === 0) continue;
            try {
                $cccd = trim((string)($data['cccd'] ?? ''));
                if ($cccd !== '' && (isset($seenCccd[$cccd]) || $this->SocccdDaTonTai($cccd))) {
                    $result['skipped']++;
                    $seenCccd[$cccd] = true;
                    continue;
                }
                $phone = trim((string)($data['phone'] ?? ''));
                if (ctype_digit($phone) && strlen($phone) === 9) $data['phone'] = '0' . $phone;
                $wardId = $this->timHoacTaoMaPhuong((string)($data['ward'] ?? ''));
                $this->ThemHocSinh([
                    'name' => $data['name'] ?? '',
                    'dob' => $this->chuanHoaNgay((string)($data['dob'] ?? '')),
                    'phone' => $data['phone'] ?? '',
                    'email' => $data['email'] ?? '',
                    'address' => $data['address'] ?? '',
                    'maphuong' => $wardId,
                    'cccd' => $cccd,
                    'ethnic' => $data['ethnic'] ?? '',
                ]);
                if ($cccd !== '') $seenCccd[$cccd] = true;
                $result['imported']++;
            } catch (Throwable $e) {
                $message = $e instanceof mysqli_sql_exception && (int)$e->getCode() === 1062
                    ? 'Email hoặc số điện thoại đã được sử dụng bởi tài khoản khác.'
                    : $e->getMessage();
                $result['errors'][] = 'Dòng ' . $row['row'] . ': ' . $message;
            }
        }
        return $result;
    }

    private function chuanHoaHoSo(array $input): array
    {
        $name = trim(preg_replace('/\s+/u', ' ', (string)($input['name'] ?? '')));
        $parts = $name === '' ? [] : preg_split('/\s+/u', $name);
        if (count($parts ?: []) < 2) {
            throw new InvalidArgumentException('Vui lòng nhập đầy đủ họ và tên.');
        }
        $ten = array_pop($parts);
        $ho = implode(' ', $parts);
        $email = trim((string)($input['email'] ?? ''));
        $phone = trim((string)($input['phone'] ?? ''));
        $cccd = trim((string)($input['cccd'] ?? ''));
        $address = trim((string)($input['address'] ?? ''));
        $dob = trim((string)($input['dob'] ?? ''));
        $wardId = (int)($input['maphuong'] ?? 0);
        $ethnic = trim((string)($input['ethnic'] ?? ''));
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) throw new InvalidArgumentException('Email bắt buộc và phải đúng định dạng.');
        if ($address === '') throw new InvalidArgumentException('Vui lòng nhập địa chỉ.');
        $parsedDob = DateTimeImmutable::createFromFormat('!Y-m-d', $dob);
        if ($dob === '' || !$parsedDob || $parsedDob->format('Y-m-d') !== $dob) throw new InvalidArgumentException('Vui lòng chọn ngày sinh hợp lệ.');
        if ($phone !== '' && !preg_match('/^\d{9,15}$/', $phone)) throw new InvalidArgumentException('Số điện thoại không hợp lệ.');
        if ($cccd !== '' && !preg_match('/^\d{12}$/', $cccd)) throw new InvalidArgumentException('CCCD phải gồm 12 chữ số.');
        if ($wardId < 1) throw new InvalidArgumentException('Vui lòng chọn phường.');
        $stmt = $this->conn->prepare('SELECT 1 FROM phuong WHERE maphuong = ?');
        $stmt->bind_param('i', $wardId);
        $stmt->execute();
        $validWard = (bool)$stmt->get_result()->fetch_row();
        $stmt->close();
        if (!$validWard) throw new InvalidArgumentException('Phường không hợp lệ.');
        return compact('ho', 'ten', 'email', 'phone', 'cccd', 'address', 'dob', 'wardId', 'ethnic');
    }

    private function timMaPhuong(string $name): int
    {
        $name = trim($name);
        if ($name === '') throw new InvalidArgumentException('Thiếu tên phường.');
        if (ctype_digit($name)) {
            $id = (int)$name;
            $stmt = $this->conn->prepare('SELECT 1 FROM phuong WHERE maphuong = ?');
            $stmt->bind_param('i', $id);
        } else {
            $stmt = $this->conn->prepare('SELECT maphuong FROM phuong WHERE LOWER(tenphuong) = LOWER(?) LIMIT 1');
            $stmt->bind_param('s', $name);
        }
        $stmt->execute();
        $row = $stmt->get_result()->fetch_row();
        $stmt->close();
        if (!$row) throw new InvalidArgumentException('Không tìm thấy phường: ' . $name);
        return ctype_digit($name) ? (int)$name : (int)$row[0];
    }

    private function timHoacTaoMaPhuong(string $name): int
    {
        $name = trim(preg_replace('/\s+/u', ' ', $name));
        $name = preg_replace('/^phường\s*/iu', '', $name);
        $name = trim(preg_replace('/\s+/u', ' ', (string)$name));
        if ($name === '') throw new InvalidArgumentException('Thiếu tên phường.');

        if (function_exists('mb_substr') && function_exists('mb_strtoupper') && function_exists('mb_strlen')) {
            $name = mb_strtoupper(mb_substr($name, 0, 1, 'UTF-8'), 'UTF-8') . mb_substr($name, 1, mb_strlen($name, 'UTF-8'), 'UTF-8');
        } else {
            $first = substr($name, 0, 1);
            $firstUpper = [
                'á' => 'Á', 'à' => 'À', 'ả' => 'Ả', 'ã' => 'Ã', 'ạ' => 'Ạ', 'ă' => 'Ă', 'ắ' => 'Ắ', 'ằ' => 'Ằ', 'ẳ' => 'Ẳ', 'ẵ' => 'Ẵ', 'ặ' => 'Ặ', 'â' => 'Â', 'ấ' => 'Ấ', 'ầ' => 'Ầ', 'ẩ' => 'Ẩ', 'ẫ' => 'Ẫ', 'ậ' => 'Ậ',
                'é' => 'É', 'è' => 'È', 'ẻ' => 'Ẻ', 'ẽ' => 'Ẽ', 'ẹ' => 'Ẹ', 'ê' => 'Ê', 'ế' => 'Ế', 'ề' => 'Ề', 'ể' => 'Ể', 'ễ' => 'Ễ', 'ệ' => 'Ệ',
                'í' => 'Í', 'ì' => 'Ì', 'ỉ' => 'Ỉ', 'ĩ' => 'Ĩ', 'ị' => 'Ị', 'ó' => 'Ó', 'ò' => 'Ò', 'ỏ' => 'Ỏ', 'õ' => 'Õ', 'ọ' => 'Ọ', 'ô' => 'Ô', 'ố' => 'Ố', 'ồ' => 'Ồ', 'ổ' => 'Ổ', 'ỗ' => 'Ỗ', 'ộ' => 'Ộ', 'ơ' => 'Ơ', 'ớ' => 'Ớ', 'ờ' => 'Ờ', 'ở' => 'Ở', 'ỡ' => 'Ỡ', 'ợ' => 'Ợ',
                'ú' => 'Ú', 'ù' => 'Ù', 'ủ' => 'Ủ', 'ũ' => 'Ũ', 'ụ' => 'Ụ', 'ư' => 'Ư', 'ứ' => 'Ứ', 'ừ' => 'Ừ', 'ử' => 'Ử', 'ữ' => 'Ữ', 'ự' => 'Ự', 'ý' => 'Ý', 'ỳ' => 'Ỳ', 'ỷ' => 'Ỷ', 'ỹ' => 'Ỹ', 'ỵ' => 'Ỵ', 'đ' => 'Đ',
            ];
            if (isset($firstUpper[$first])) {
                $name = $firstUpper[$first] . substr($name, strlen($first));
            } else {
                $name = ucfirst($name);
            }
        }

        if (strlen($name) > 100) throw new InvalidArgumentException('Tên phường không được dài quá 100 ký tự.');
        $stmt = $this->conn->prepare('SELECT maphuong FROM phuong WHERE tenphuong = ? LIMIT 1');
        $stmt->bind_param('s', $name);
        $stmt->execute();
        $row = $stmt->get_result()->fetch_assoc();
        $stmt->close();
        if ($row) return (int)$row['maphuong'];

        try {
            $stmt = $this->conn->prepare('INSERT INTO phuong (tenphuong) VALUES (?)');
            $stmt->bind_param('s', $name);
            $stmt->execute();
            $wardId = (int)$this->conn->insert_id;
            $stmt->close();
            return $wardId;
        } catch (mysqli_sql_exception $exception) {
            if ((int)$exception->getCode() !== 1062) throw $exception;
            $stmt = $this->conn->prepare('SELECT maphuong FROM phuong WHERE tenphuong = ? LIMIT 1');
            $stmt->bind_param('s', $name);
            $stmt->execute();
            $wardId = (int)($stmt->get_result()->fetch_assoc()['maphuong'] ?? 0);
            $stmt->close();
            if ($wardId < 1) throw $exception;
            return $wardId;
        }
    }

    private function chuanHoaNgay(string $value): string
    {
        $value = trim($value);
        if ($value === '') throw new InvalidArgumentException('Thiếu ngày sinh.');
        if (is_numeric($value) && (float)$value > 20000 && (float)$value < 80000) {
            return gmdate('Y-m-d', (int)(($value - 25569) * 86400));
        }
        foreach (['Y-m-d', 'd/m/Y', 'd-m-Y', 'm/d/Y'] as $format) {
            $date = DateTimeImmutable::createFromFormat('!' . $format, $value);
            if ($date && $date->format($format) === $value) return $date->format('Y-m-d');
        }
        throw new InvalidArgumentException('Ngày sinh không đúng định dạng.');
    }

    private function docTepBangTinh(array $file): array
    {
        if (($file['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_OK) {
            throw new InvalidArgumentException('Vui lòng chọn tệp .xlsx hoặc .csv hợp lệ.');
        }
        if (($file['size'] ?? 0) < 1 || ($file['size'] ?? 0) > 5 * 1024 * 1024) {
            throw new InvalidArgumentException('Tệp phải có dữ liệu và dung lượng không quá 5 MB.');
        }
        $extension = strtolower(pathinfo((string)$file['name'], PATHINFO_EXTENSION));
        if ($extension === 'csv') {
            $rawRows = $this->docCsv((string)$file['tmp_name']);
        } elseif ($extension === 'xlsx') {
            $rawRows = $this->docXlsx((string)$file['tmp_name']);
        } else {
            throw new InvalidArgumentException('Định dạng hỗ trợ là .xlsx và .csv.');
        }
        if (count($rawRows) < 2 || count($rawRows) > 1001) {
            throw new InvalidArgumentException('Tệp cần có dòng tiêu đề và tối đa 1.000 hồ sơ.');
        }
        $header = array_map([$this, 'chuanHoaTieuDe'], array_shift($rawRows));
        $map = [];
        $aliases = [
            'name' => ['ho va ten', 'ho ten', 'ten hoc sinh', 'full name'],
            'dob' => ['ngay sinh', 'ngay thang nam sinh', 'date of birth'],
            'phone' => ['so dien thoai', 'dien thoai', 'phone'],
            'email' => ['email', 'email neu co', 'dia chi email'],
            'address' => ['dia chi', 'address'],
            'ward' => ['phuong', 'ten phuong', 'ma phuong', 'ward'],
            'cccd' => ['so cccd', 'cccd', 'can cuoc cong dan'],
            'ethnic' => ['dan toc', 'ethnicity'],
        ];
        foreach ($header as $index => $columnName) {
            foreach ($aliases as $field => $accepted) {
                if (in_array($columnName, $accepted, true)) $map[$field] = $index;
            }
        }
        foreach (['name', 'dob', 'email', 'address', 'ward'] as $required) {
            if (!array_key_exists($required, $map)) throw new InvalidArgumentException('Thiếu cột bắt buộc trong Excel: ' . $required . '.');
        }
        $records = [];
        foreach ($rawRows as $rowIndex => $values) {
            $data = [];
            foreach ($aliases as $field => $_) $data[$field] = trim((string)($values[$map[$field] ?? -1] ?? ''));
            if ($data['cccd'] !== '' && ctype_digit($data['cccd']) && strlen($data['cccd']) < 12) $data['cccd'] = str_pad($data['cccd'], 12, '0', STR_PAD_LEFT);
            $records[] = ['row' => $rowIndex + 2, 'data' => $data];
        }
        return $records;
    }

    private function chuanHoaTieuDe(string $value): string
    {
        $value = trim($value);
        $vietnamese = [
            'à'=>'a','á'=>'a','ạ'=>'a','ả'=>'a','ã'=>'a','â'=>'a','ầ'=>'a','ấ'=>'a','ậ'=>'a','ẩ'=>'a','ẫ'=>'a','ă'=>'a','ằ'=>'a','ắ'=>'a','ặ'=>'a','ẳ'=>'a','ẵ'=>'a',
            'è'=>'e','é'=>'e','ẹ'=>'e','ẻ'=>'e','ẽ'=>'e','ê'=>'e','ề'=>'e','ế'=>'e','ệ'=>'e','ể'=>'e','ễ'=>'e',
            'ì'=>'i','í'=>'i','ị'=>'i','ỉ'=>'i','ĩ'=>'i',
            'ò'=>'o','ó'=>'o','ọ'=>'o','ỏ'=>'o','õ'=>'o','ô'=>'o','ồ'=>'o','ố'=>'o','ộ'=>'o','ổ'=>'o','ỗ'=>'o','ơ'=>'o','ờ'=>'o','ớ'=>'o','ợ'=>'o','ở'=>'o','ỡ'=>'o',
            'ù'=>'u','ú'=>'u','ụ'=>'u','ủ'=>'u','ũ'=>'u','ư'=>'u','ừ'=>'u','ứ'=>'u','ự'=>'u','ử'=>'u','ữ'=>'u',
            'ỳ'=>'y','ý'=>'y','ỵ'=>'y','ỷ'=>'y','ỹ'=>'y','đ'=>'d',
            'À'=>'a','Á'=>'a','Ạ'=>'a','Ả'=>'a','Ã'=>'a','Â'=>'a','Ầ'=>'a','Ấ'=>'a','Ậ'=>'a','Ẩ'=>'a','Ẫ'=>'a','Ă'=>'a','Ằ'=>'a','Ắ'=>'a','Ặ'=>'a','Ẳ'=>'a','Ẵ'=>'a',
            'È'=>'e','É'=>'e','Ẹ'=>'e','Ẻ'=>'e','Ẽ'=>'e','Ê'=>'e','Ề'=>'e','Ế'=>'e','Ệ'=>'e','Ể'=>'e','Ễ'=>'e',
            'Ì'=>'i','Í'=>'i','Ị'=>'i','Ỉ'=>'i','Ĩ'=>'i',
            'Ò'=>'o','Ó'=>'o','Ọ'=>'o','Ỏ'=>'o','Õ'=>'o','Ô'=>'o','Ồ'=>'o','Ố'=>'o','Ộ'=>'o','Ổ'=>'o','Ỗ'=>'o','Ơ'=>'o','Ờ'=>'o','Ớ'=>'o','Ợ'=>'o','Ở'=>'o','Ỡ'=>'o',
            'Ù'=>'u','Ú'=>'u','Ụ'=>'u','Ủ'=>'u','Ũ'=>'u','Ư'=>'u','Ừ'=>'u','Ứ'=>'u','Ự'=>'u','Ử'=>'u','Ữ'=>'u',
            'Ỳ'=>'y','Ý'=>'y','Ỵ'=>'y','Ỷ'=>'y','Ỹ'=>'y','Đ'=>'d',
        ];
        $value = strtr($value, $vietnamese);
        if (function_exists('iconv')) {
            $ascii = iconv('UTF-8', 'ASCII//TRANSLIT//IGNORE', $value);
            if ($ascii !== false) $value = $ascii;
        }
        $value = strtolower($value);
        return trim(preg_replace('/[^a-z0-9]+/', ' ', $value));
    }

    private function docCsv(string $path): array
    {
        $handle = fopen($path, 'rb');
        if (!$handle) throw new InvalidArgumentException('Không thể đọc tệp CSV.');
        $rows = [];
        $firstLine = fgets($handle);
        if ($firstLine === false) {
            fclose($handle);
            return [];
        }
        $delimiter = substr_count($firstLine, ';') > substr_count($firstLine, ',') ? ';' : ',';
        rewind($handle);
        while (($row = fgetcsv($handle, 0, $delimiter)) !== false) {
            if (isset($row[0])) $row[0] = preg_replace('/^\xEF\xBB\xBF/', '', (string)$row[0]);
            $rows[] = array_map(static fn($value) => trim((string)$value), $row);
            if (count($rows) > 1001) break;
        }
        fclose($handle);
        return $rows;
    }

    private function docXlsx(string $path): array
    {
        if (!class_exists('ZipArchive') || !function_exists('simplexml_load_string')) {
            throw new RuntimeException('Máy chủ cần bật ZipArchive và SimpleXML để đọc .xlsx.');
        }
        $zip = new ZipArchive();
        if ($zip->open($path) !== true) throw new InvalidArgumentException('Tệp Excel không mở được.');
        try {
            $shared = [];
            $stringsStat = $zip->statName('xl/sharedStrings.xml');
            if ($stringsStat !== false && (int)$stringsStat['size'] > 5_000_000) {
                throw new InvalidArgumentException('Bảng chuỗi của tệp Excel vượt quá giới hạn cho phép.');
            }
            $stringsXml = $zip->getFromName('xl/sharedStrings.xml');
            if ($stringsXml !== false) {
                $xml = simplexml_load_string($stringsXml, SimpleXMLElement::class, LIBXML_NONET);
                foreach ($xml->children('http://schemas.openxmlformats.org/spreadsheetml/2006/main')->si as $item) {
                    $parts = $item->xpath('.//*[local-name()="t"]');
                    $shared[] = implode('', array_map(static fn($part) => (string)$part, $parts ?: []));
                }
            }
            $sheetXml = $zip->getFromName('xl/worksheets/sheet1.xml');
            if ($sheetXml === false || strlen($sheetXml) > 5_000_000 || stripos($sheetXml, '<!DOCTYPE') !== false) {
                throw new InvalidArgumentException('Không đọc được trang tính đầu tiên trong tệp Excel.');
            }
            $sheet = simplexml_load_string($sheetXml, SimpleXMLElement::class, LIBXML_NONET);
            if ($sheet === false) throw new InvalidArgumentException('Nội dung Excel không hợp lệ.');
            $xmlRows = $sheet->xpath('//*[local-name()="sheetData"]/*[local-name()="row"]') ?: [];
            if (count($xmlRows) > 1001) throw new InvalidArgumentException('Tệp Excel vượt quá 1.000 hồ sơ.');
            $rows = [];
            foreach ($xmlRows as $xmlRow) {
                $row = [];
                foreach (($xmlRow->xpath('./*[local-name()="c"]') ?: []) as $cell) {
                    if (!preg_match('/^([A-Z]+)[0-9]+$/', (string)$cell['r'], $match)) continue;
                    $col = 0;
                    foreach (str_split($match[1]) as $letter) $col = $col * 26 + ord($letter) - 64;
                    $col--;
                    $value = (string)($cell->xpath('./*[local-name()="v"]')[0] ?? '');
                    if ((string)$cell['t'] === 's') $value = $shared[(int)$value] ?? '';
                    elseif ((string)$cell['t'] === 'inlineStr') $value = implode('', array_map(static fn($part) => (string)$part, $cell->xpath('./*[local-name()="is"]//*[local-name()="t"]') ?: []));
                    $row[$col] = trim($value);
                }
                if ($row) {
                    $max = max(array_keys($row));
                    $rows[] = array_map(static fn($index) => $row[$index] ?? '', range(0, $max));
                } else $rows[] = [];
            }
            return $rows;
        } finally {
            $zip->close();
        }
    }
}
