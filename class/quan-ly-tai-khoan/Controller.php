<?php

class CtrlQuanLyTaiKhoan
{
    private mysqli $conn;
    private const PER_PAGE = 10;

    public function __construct(mysqli $conn)
    {
        $this->conn = $conn;
    }

    public function DanhSachTaiKhoan(int $page, string $keyword = '', string $role = ''): array
    {
        $page = max(1, $page);
        $offset = ($page - 1) * self::PER_PAGE;
        $limit = self::PER_PAGE;
        $keywordPattern = '%' . $keyword . '%';
        $sql = "SELECT nd.manguoidung, CONCAT(hs.ho, ' ', hs.ten) AS ten,
                       nd.email, nd.sodienthoai, nd.vaitro, hs.socccd,
                       hs.ngaysinh, hs.diachi, p.tenphuong,
                       CASE nd.vaitro
                         WHEN 'HOC_SINH' THEN ths.matruong
                         WHEN 'THCS' THEN thg.matruong
                         WHEN 'THPT' THEN thg.matruong
                         ELSE NULL
                       END AS matruong,
                       CASE nd.vaitro
                         WHEN 'HOC_SINH' THEN ths.tentruong
                         WHEN 'THCS' THEN thg.tentruong
                         WHEN 'THPT' THEN thg.tentruong
                         ELSE NULL
                       END AS tentruong
                FROM nguoidung nd
                JOIN hosonguoidung hs ON hs.manguoidung = nd.manguoidung
                LEFT JOIN phuong p ON p.maphuong = hs.maphuong
                LEFT JOIN hocsinh h ON h.manguoidung = nd.manguoidung AND h.makythi = 1
                LEFT JOIN truonghoc ths ON ths.matruong = h.matruongdanghoc
                LEFT JOIN giaovien gv ON gv.manguoidung = nd.manguoidung
                LEFT JOIN truonghoc thg ON thg.matruong = gv.matruong
                WHERE (? = '' OR CONCAT(hs.ho, ' ', hs.ten) LIKE ?)
                  AND (? = '' OR nd.vaitro = ?)
                ORDER BY nd.manguoidung DESC
                LIMIT ? OFFSET ?";
        $stmt = $this->conn->prepare($sql);
        $stmt->bind_param('ssssii', $keyword, $keywordPattern, $role, $role, $limit, $offset);
        $stmt->execute();
        $rows = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
        $stmt->close();
        return $rows;
    }

    public function TongSoTaiKhoan(string $keyword = '', string $role = ''): int
    {
        $keywordPattern = '%' . $keyword . '%';
        $sql = "SELECT COUNT(*) AS total
                FROM nguoidung nd
                JOIN hosonguoidung hs ON hs.manguoidung = nd.manguoidung
                WHERE (? = '' OR CONCAT(hs.ho, ' ', hs.ten) LIKE ?)
                  AND (? = '' OR nd.vaitro = ?)";
        $stmt = $this->conn->prepare($sql);
        $stmt->bind_param('ssss', $keyword, $keywordPattern, $role, $role);
        $stmt->execute();
        $total = (int)($stmt->get_result()->fetch_assoc()['total'] ?? 0);
        $stmt->close();
        return $total;
    }

    public function ChiTietTaiKhoan(int $id): ?array
    {
        $sql = "SELECT nd.manguoidung, nd.vaitro, nd.email, nd.sodienthoai,
                       hs.ho, hs.ten, hs.socccd, hs.ngaysinh, hs.diachi,
                       p.maphuong, p.tenphuong,
                       CASE WHEN nd.vaitro = 'HOC_SINH' THEN ths.matruong ELSE thg.matruong END AS matruong,
                       CASE WHEN nd.vaitro = 'HOC_SINH' THEN ths.tentruong ELSE thg.tentruong END AS tentruong
                FROM nguoidung nd
                JOIN hosonguoidung hs ON hs.manguoidung = nd.manguoidung
                LEFT JOIN phuong p ON p.maphuong = hs.maphuong
                LEFT JOIN hocsinh h ON h.manguoidung = nd.manguoidung AND h.makythi = 1
                LEFT JOIN truonghoc ths ON ths.matruong = h.matruongdanghoc
                LEFT JOIN giaovien gv ON gv.manguoidung = nd.manguoidung
                LEFT JOIN truonghoc thg ON thg.matruong = gv.matruong
                WHERE nd.manguoidung = ? LIMIT 1";
        $stmt = $this->conn->prepare($sql);
        $stmt->bind_param('i', $id);
        $stmt->execute();
        $row = $stmt->get_result()->fetch_assoc() ?: null;
        $stmt->close();
        return $row;
    }

    public function DanhSachPhuong(): array
    {
        $result = $this->conn->query('SELECT maphuong, tenphuong FROM phuong ORDER BY tenphuong');
        return $result->fetch_all(MYSQLI_ASSOC);
    }

    public function DanhSachTruongTHCS(): array
    {
        return $this->danhSachTruongTheoLoai('THCS');
    }

    public function DanhSachTruongTHPT(): array
    {
        return $this->danhSachTruongTheoLoai('THPT');
    }

    private function danhSachTruongTheoLoai(string $loai): array
    {
        $stmt = $this->conn->prepare('SELECT matruong, tentruong FROM truonghoc WHERE loaitruong = ? ORDER BY tentruong');
        $stmt->bind_param('s', $loai);
        $stmt->execute();
        $rows = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
        $stmt->close();
        return $rows;
    }

    public function ThemTaiKhoan(array $input): int
    {
        $this->validate($input, true);
        $role = $input['vaitro'];
        $schoolId = $this->validateSchool($input['matruong'] ?? '', $role);
        $email = trim((string)($input['email'] ?? ''));
        $phone = trim((string)($input['sodienthoai'] ?? ''));
        $phoneValue = $phone === '' ? null : $phone;

        $this->conn->begin_transaction();
        try {
            $sql = "INSERT INTO nguoidung (vaitro, email, sodienthoai, matkhau)
                    VALUES (?, ?, ?, ?)";
            $hash = sha1('1');
            $stmt = $this->conn->prepare($sql);
            $stmt->bind_param('ssss', $role, $email, $phoneValue, $hash);
            $stmt->execute();
            $userId = (int)$this->conn->insert_id;
            $stmt->close();

            $this->insertProfile($userId, $input);
            if ($role === 'HOC_SINH') {
                $stmt = $this->conn->prepare('INSERT INTO hocsinh (manguoidung, makythi, matruongdanghoc) VALUES (?, 1, ?)');
                $stmt->bind_param('ii', $userId, $schoolId);
                $stmt->execute();
                $stmt->close();
            } elseif ($role === 'THCS' || $role === 'THPT') {
                $stmt = $this->conn->prepare('INSERT INTO giaovien (matruong, manguoidung, mamonhoc) VALUES (?, ?, NULL)');
                $stmt->bind_param('ii', $schoolId, $userId);
                $stmt->execute();
                $stmt->close();
            }
            $this->conn->commit();
            return $userId;
        } catch (Throwable $e) {
            $this->conn->rollback();
            throw $e;
        }
    }

    public function CapNhatTaiKhoan(int $id, array $input): void
    {
        $this->validate($input, false);
        $email = trim((string)($input['email'] ?? ''));
        $phone = trim((string)($input['sodienthoai'] ?? ''));
        $phoneValue = $phone === '' ? null : $phone;
        $this->conn->begin_transaction();
        try {
            $stmt = $this->conn->prepare('UPDATE nguoidung SET email = ?, sodienthoai = ? WHERE manguoidung = ?');
            $stmt->bind_param('ssi', $email, $phoneValue, $id);
            $stmt->execute();
            if ($stmt->affected_rows === 0 && !$this->ChiTietTaiKhoan($id)) {
                throw new RuntimeException('Không tìm thấy tài khoản.');
            }
            $stmt->close();

            $stmt = $this->conn->prepare('UPDATE hosonguoidung SET ho = ?, ten = ?, diachi = ?, maphuong = ?, socccd = ?, ngaysinh = ? WHERE manguoidung = ?');
            $stmt->bind_param('sssissi', $input['ho'], $input['ten'], $input['diachi'], $input['maphuong'], $input['socccd'], $input['ngaysinh'], $id);
            $stmt->execute();
            if ($stmt->affected_rows === 0 && !$this->ChiTietTaiKhoan($id)) {
                throw new RuntimeException('Không tìm thấy hồ sơ tài khoản.');
            }
            $stmt->close();
            $this->conn->commit();
        } catch (Throwable $e) {
            $this->conn->rollback();
            throw $e;
        }
    }

    private function insertProfile(int $userId, array $input): void
    {
        $stmt = $this->conn->prepare('INSERT INTO hosonguoidung (manguoidung, ho, ten, diachi, maphuong, socccd, ngaysinh) VALUES (?, ?, ?, ?, ?, ?, ?)');
        $stmt->bind_param('isssiss', $userId, $input['ho'], $input['ten'], $input['diachi'], $input['maphuong'], $input['socccd'], $input['ngaysinh']);
        $stmt->execute();
        $stmt->close();
    }

    private function validate(array $input, bool $newAccount): void
    {
        foreach (['ho', 'ten', 'socccd', 'diachi', 'maphuong', 'ngaysinh'] as $field) {
            if (trim((string)($input[$field] ?? '')) === '') {
                throw new InvalidArgumentException('Vui lòng nhập đầy đủ thông tin bắt buộc.');
            }
        }
        if (!preg_match('/^\d{12}$/', (string)$input['socccd'])) {
            throw new InvalidArgumentException('CCCD phải gồm đúng 12 chữ số.');
        }
        if (trim((string)($input['sodienthoai'] ?? '')) === '') {
            throw new InvalidArgumentException('Vui lòng nhập số điện thoại.');
        }
        if (!empty($input['sodienthoai']) && !preg_match('/^\d{10,15}$/', (string)$input['sodienthoai'])) {
            throw new InvalidArgumentException('Số điện thoại không hợp lệ.');
        }
        if (trim((string)($input['email'] ?? '')) === '') {
            throw new InvalidArgumentException('Vui lòng nhập email.');
        }
        if (!filter_var($input['email'], FILTER_VALIDATE_EMAIL)) {
            throw new InvalidArgumentException('Email không hợp lệ.');
        }
        if ($newAccount && !in_array($input['vaitro'] ?? '', ['SO_GD', 'THPT', 'THCS', 'HOC_SINH'], true)) {
            throw new InvalidArgumentException('Vai trò không hợp lệ.');
        }
        $stmt = $this->conn->prepare('SELECT COUNT(*) FROM phuong WHERE maphuong = ?');
        $stmt->bind_param('i', $input['maphuong']);
        $stmt->execute();
        $validWard = (int)$stmt->get_result()->fetch_row()[0] > 0;
        $stmt->close();
        if (!$validWard) {
            throw new InvalidArgumentException('Phường đã chọn không hợp lệ.');
        }
    }

    private function validateSchool($schoolId, string $role): ?int
    {
        if ($role === 'SO_GD') {
            return null;
        }
        $schoolType = $role === 'HOC_SINH' ? 'THCS' : $role;
        if (!in_array($schoolType, ['THCS', 'THPT'], true) || !ctype_digit((string)$schoolId)) {
            throw new InvalidArgumentException('Vui lòng chọn trường phù hợp với vai trò.');
        }
        $stmt = $this->conn->prepare('SELECT COUNT(*) FROM truonghoc WHERE matruong = ? AND loaitruong = ?');
        $stmt->bind_param('is', $schoolId, $schoolType);
        $stmt->execute();
        $valid = (int)$stmt->get_result()->fetch_row()[0] > 0;
        $stmt->close();
        if (!$valid) {
            throw new InvalidArgumentException('Trường đã chọn không hợp lệ với vai trò.');
        }
        return (int)$schoolId;
    }
}
