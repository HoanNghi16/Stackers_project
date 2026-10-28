<?php
declare(strict_types=1);
require_once __DIR__ . '/phat-db.php';

/** Chỉ làm việc với hồ sơ HOC_SINH trong Session. */
final class PhatDangKyPhucKhao
{
    public function __construct(private readonly mysqli $db) {}

    public static function statusName(string $status): string
    {
        return match (strtoupper($status)) {
            'CHO_XU_LY' => 'Chờ xử lý',
            'DANG_PHUC_KHAO' => 'Đang phúc khảo',
            'HOAN_THANH' => 'Đã hoàn thành',
            'TU_CHOI' => 'Đã từ chối',
            default => 'Đang cập nhật',
        };
    }

    /** Hồ sơ thuộc tài khoản hiện tại, không nhận ID học sinh từ URL/form. */
    public function student(int $userId): ?array
    {
        $stmt = $this->db->prepare('SELECT h.mahocsinh, h.makythi, h.sobaodanh, k.namtuyensinh,
                k.hanchotdangkyphuckhao, k.hanchotcongbodiemthi,
                TRIM(CONCAT_WS(" ", hs.ho, hs.ten)) AS hoten
            FROM hocsinh h
            JOIN kythituyensinh k ON k.makythi = h.makythi
            LEFT JOIN hosonguoidung hs ON hs.manguoidung = h.manguoidung
            WHERE h.manguoidung = ?
            ORDER BY k.namtuyensinh DESC, h.mahocsinh DESC LIMIT 1');
        $stmt->bind_param('i', $userId);
        $stmt->execute();
        return $stmt->get_result()->fetch_assoc() ?: null;
    }

    /** Không lấy mốc hạn chót làm bằng chứng Sở đã công bố điểm. */
    public function scoreIsPublished(int $examId): ?bool
    {
        foreach (['dacongbodiemthi', 'dacongboketquathi', 'diemthidacongbo'] as $col) {
            if (PhatDb::hasColumn($this->db, 'kythituyensinh', $col)) {
                $sql = 'SELECT `' . $col . '` AS flag FROM kythituyensinh WHERE makythi = ?';
                $s = $this->db->prepare($sql);
                $s->bind_param('i', $examId);
                $s->execute();
                return (string)$s->get_result()->fetch_column() === '1';
            }
        }
        if (PhatDb::hasTable($this->db, 'congbodiemthi') &&
            PhatDb::hasColumn($this->db, 'congbodiemthi', 'makythi') &&
            PhatDb::hasColumn($this->db, 'congbodiemthi', 'dacongbo')) {
            $s = $this->db->prepare('SELECT dacongbo FROM congbodiemthi WHERE makythi = ? LIMIT 1');
            $s->bind_param('i', $examId);
            $s->execute();
            return (string)$s->get_result()->fetch_column() === '1';
        }
        // NULL = database chưa có dấu hiệu công bố được xác thực.
        return null;
    }

    public static function isWithinDeadline(?string $deadline, ?DateTimeImmutable $now = null): bool
    {
        if (!$deadline) return false;
        $now ??= new DateTimeImmutable('now', new DateTimeZone('Asia/Ho_Chi_Minh'));
        return $now->format('Y-m-d') <= substr($deadline, 0, 10);
    }

    public function examPapers(int $studentId): array
    {
        $s = $this->db->prepare('SELECT b.mabaithi, m.tenmonhoc, b.diem,
                    b.tyletrudiem, b.lydotrudiem
                FROM baithi b
                JOIN monhoc m ON m.mamonhoc = b.mamonhoc
                WHERE b.mahocsinh = ?
                ORDER BY m.tenmonhoc');
        $s->bind_param('i', $studentId);
        $s->execute();
        return $s->get_result()->fetch_all(MYSQLI_ASSOC);
    }

    /** Các bài đã gửi thuộc hồ sơ này, kể cả đơn đang được xử lý. */
    public function existingPaperIds(int $studentId): array
    {
        $s = $this->db->prepare('SELECT DISTINCT ct.mabaithi
            FROM chitietphuckhao ct
            JOIN phieuphuckhao p ON p.maphieu = ct.maphieu
            WHERE p.mahocsinh = ?');
        $s->bind_param('i', $studentId);
        $s->execute();
        return array_map('intval', array_column($s->get_result()->fetch_all(MYSQLI_ASSOC), 'mabaithi'));
    }

    public function requests(int $studentId): array
    {
        $s = $this->db->prepare('SELECT p.maphieu, p.ngaytao, p.lydo, p.trangthai,
                     GROUP_CONCAT(m.tenmonhoc ORDER BY m.tenmonhoc SEPARATOR ", ") AS monhoc
            FROM phieuphuckhao p
            JOIN chitietphuckhao ct ON ct.maphieu = p.maphieu
            JOIN baithi b ON b.mabaithi = ct.mabaithi
            JOIN monhoc m ON m.mamonhoc = b.mamonhoc
            WHERE p.mahocsinh = ?
            GROUP BY p.maphieu, p.ngaytao, p.lydo, p.trangthai
            ORDER BY p.ngaytao DESC, p.maphieu DESC');
        $s->bind_param('i', $studentId);
        $s->execute();
        return $s->get_result()->fetch_all(MYSQLI_ASSOC);
    }

    /**
     * Truy cap storage phai duoc cau hinh rieng ngoai webroot.
     * .env: PHAT_PHUC_KHAO_STORAGE="C:/xampp/stackers_private/phuc_khao"
     */
    public static function evidenceStorageDir(bool $writable = true): ?string
    {
        $project = dirname(__DIR__);
        $configured = getenv('PHAT_PHUC_KHAO_STORAGE');
        if ($configured === false || trim($configured) === '') {
            $envPath = $project . '/.env';
            $env = is_file($envPath) ? parse_ini_file($envPath, false, INI_SCANNER_RAW) : false;
            $configured = is_array($env) ? ($env['PHAT_PHUC_KHAO_STORAGE'] ?? '') : '';
        }
        $given = trim((string)$configured, " \t\r\n\"'");
        if ($given === '' || !(str_starts_with($given, '/') || preg_match('~^[a-zA-Z]:[\\\\/]~', $given))) {
            return null;
        }
        $resolved = realpath($given);
        if ($resolved === false || !is_dir($resolved) || !is_readable($resolved) || ($writable && !is_writable($resolved))) {
            return null;
        }
        $normal = static fn(string $path): string => strtolower(rtrim(str_replace('\\', '/', $path), '/'));
        $dir = $normal($resolved);
        $docRoot = trim((string)($_SERVER['DOCUMENT_ROOT'] ?? ''));
        $protectedRoots = [realpath($project)];
        if ($docRoot !== '') $protectedRoots[] = realpath($docRoot);
        foreach ($protectedRoots as $root) {
            if ($root !== false && $root !== '') {
                $r = $normal($root);
                if ($dir === $r || str_starts_with($dir, $r . '/')) return null;
            }
        }
        return $resolved;
    }

    public function evidenceReady(): bool
    {
        return PhatDb::hasTable($this->db, 'minhchungphuckhao')
            && self::evidenceStorageDir() !== null;
    }

    /** File dinh kem la tuy chon; khong co file thi van gui phieu binh thuong. */
    private function inspectEvidence(?array $upload): ?array
    {
        if (!$upload || (int)($upload['error'] ?? UPLOAD_ERR_NO_FILE) === UPLOAD_ERR_NO_FILE) return null;
        if (is_array($upload['name'] ?? null) || is_array($upload['tmp_name'] ?? null)) {
            throw new DomainException('Chỉ được đính kèm tối đa một file minh chứng.');
        }
        $error = (int)($upload['error'] ?? -1);
        if ($error !== UPLOAD_ERR_OK) {
            throw new DomainException('File chưa tải lên được. Kiểm tra dung lượng và cấu hình PHP (upload_max_filesize, post_max_size).');
        }
        if (!$this->evidenceReady()) {
            throw new DomainException('Chức năng lưu minh chứng chưa được thiết lập. Vui lòng liên hệ quản trị viên.');
        }
        $tmp = (string)($upload['tmp_name'] ?? '');
        $size = (int)($upload['size'] ?? 0);
        if ($tmp === '' || !is_uploaded_file($tmp) || $size < 1 || $size > 5 * 1024 * 1024) {
            throw new DomainException('File minh chứng phải có dung lượng từ 1 byte đến 5 MB.');
        }
        $origin = basename(str_replace('\\', '/', (string)($upload['name'] ?? '')));
        $origin = trim((string)preg_replace('/[\x00-\x1F\x7F]/', '', $origin));
        if ($origin === '') throw new DomainException('Tên file minh chứng không hợp lệ.');
        if (strlen($origin) > 240) {
            $origin = function_exists('mb_strcut') ? mb_strcut($origin, 0, 240, 'UTF-8') : substr($origin, 0, 240);
        }
        $extension = strtolower((string)pathinfo($origin, PATHINFO_EXTENSION));
        $types = ['pdf' => 'application/pdf', 'jpg' => 'image/jpeg', 'jpeg' => 'image/jpeg', 'png' => 'image/png'];
        if (!isset($types[$extension])) throw new DomainException('Chỉ hỗ trợ PDF, JPG, JPEG hoặc PNG.');
        if (!class_exists('finfo')) throw new DomainException('Máy chủ chưa bật chức năng kiểm tra loại file.');
        $finfo = new finfo(FILEINFO_MIME_TYPE);
        $mime = (string)$finfo->file($tmp);
        if ($mime !== $types[$extension]) throw new DomainException('Nội dung file không khớp định dạng PDF/JPG/PNG.');
        if ($mime === 'application/pdf') {
            $head = file_get_contents($tmp, false, null, 0, 5);
            if ($head !== '%PDF-') throw new DomainException('File PDF không hợp lệ.');
        } elseif (@getimagesize($tmp) === false) {
            throw new DomainException('File ảnh minh chứng không hợp lệ.');
        }
        return [
            'tmp' => $tmp, 'origin' => $origin,
            'extension' => $extension === 'jpeg' ? 'jpg' : $extension,
            'mime' => $mime, 'size' => $size,
        ];
    }

    /** Ten minh chung gan voi phieu cua DUY NHAT hoc sinh dang dang nhap. */
    public function evidenceNames(int $studentId): array
    {
        if (!PhatDb::hasTable($this->db, 'minhchungphuckhao')) return [];
        $s = $this->db->prepare('SELECT mc.maphieu, mc.tenfilegoc FROM minhchungphuckhao mc
            JOIN phieuphuckhao p ON p.maphieu = mc.maphieu WHERE p.mahocsinh = ?');
        $s->bind_param('i', $studentId);
        $s->execute();
        $result = [];
        foreach ($s->get_result()->fetch_all(MYSQLI_ASSOC) as $row) $result[(int)$row['maphieu']] = $row['tenfilegoc'];
        return $result;
    }

    public function evidenceForDownload(int $userId, int $requestId): ?array
    {
        if (!PhatDb::hasTable($this->db, 'minhchungphuckhao')) return null;
        $student = $this->student($userId);
        if (!$student) return null;
        $studentId = (int)$student['mahocsinh'];
        $s = $this->db->prepare('SELECT mc.tenfilegoc, mc.tenfileluu, mc.loaimime, mc.kichthuoc
            FROM minhchungphuckhao mc
            JOIN phieuphuckhao p ON p.maphieu = mc.maphieu
            WHERE mc.maphieu = ? AND p.mahocsinh = ? LIMIT 1');
        $s->bind_param('ii', $requestId, $studentId);
        $s->execute();
        return $s->get_result()->fetch_assoc() ?: null;
    }

    public function eligibility(array $student): array
    {
        $pub = $this->scoreIsPublished((int)$student['makythi']);
        if ($pub === null) return [false, 'Hệ thống chưa có thông tin xác nhận công bố điểm thi. Vui lòng liên hệ nhà trường.'];
        if (!$pub) return [false, 'Điểm thi chưa được công bố. Bạn chưa thể đăng ký phúc khảo.'];
        if (!self::isWithinDeadline($student['hanchotdangkyphuckhao'])) {
            return [false, 'Đã hết hạn đăng ký phúc khảo hoặc kỳ thi chưa cập nhật thời hạn tiếp nhận.'];
        }
        return [true, ''];
    }

    /** Ghi 1 phiếu, nhiều bài thi; revalidate quyền, điểm, hạn, trùng trong transaction. */
    public function submit(int $userId, array $paperIds, string $reason, ?array $evidence = null): int
    {
        $file = $this->inspectEvidence($evidence);
        $storedFile = null;
        $reason = trim($reason);
        $reasonLength = function_exists('mb_strlen') ? mb_strlen($reason, 'UTF-8') : preg_match_all('/./us', $reason);
        if ($reasonLength === false || $reasonLength < 10 || $reasonLength > 500) {
            throw new DomainException('Lý do cần từ 10 đến 500 ký tự.');
        }
        if (!$paperIds || count($paperIds) > 20) throw new DomainException('Vui lòng chọn ít nhất một môn thi.');
        $ids = [];
        foreach ($paperIds as $value) {
            if (!is_scalar($value) || !preg_match('/^[1-9][0-9]*$/D', (string)$value)) {
                throw new DomainException('Danh sách môn thi không hợp lệ.');
            }
            $ids[] = (int)$value;
        }
        if (count(array_unique($ids)) !== count($ids)) throw new DomainException('Danh sách có môn thi bị chọn trùng.');
        $this->db->begin_transaction();
        try {
            $student = $this->student($userId);
            if (!$student) throw new DomainException('Không tìm thấy hồ sơ dự thi của bạn.');
            [$allowed, $reasonNotAllowed] = $this->eligibility($student);
            if (!$allowed) throw new DomainException($reasonNotAllowed);
            $studentId = (int)$student['mahocsinh'];
            // Khóa các bài thi thuộc học sinh để tránh hai lượt gửi cùng lúc trên cùng một môn.
            $lock = $this->db->prepare('SELECT mabaithi, diem FROM baithi WHERE mahocsinh = ? FOR UPDATE');
            $lock->bind_param('i', $studentId);
            $lock->execute();
            $valid = [];
            foreach ($lock->get_result()->fetch_all(MYSQLI_ASSOC) as $paper) {
                if ($paper['diem'] !== null) $valid[(int)$paper['mabaithi']] = true;
            }
            foreach ($ids as $id) {
                if (!isset($valid[$id])) throw new DomainException('Có môn chưa có điểm hoặc không thuộc hồ sơ của bạn.');
            }
            $existing = $this->existingPaperIds($studentId);
            foreach ($ids as $id) {
                if (in_array($id, $existing, true)) throw new DomainException('Bạn đã đăng ký phúc khảo một trong các môn được chọn.');
            }
            $stmt = $this->db->prepare('INSERT INTO phieuphuckhao (mahocsinh, ngaytao, lydo, trangthai)
                VALUES (?, NOW(), ?, "CHO_XU_LY")');
            $stmt->bind_param('is', $studentId, $reason);
            $stmt->execute();
            $requestId = (int)$this->db->insert_id;
            $item = $this->db->prepare('INSERT INTO chitietphuckhao (maphieu, mabaithi) VALUES (?, ?)');
            foreach ($ids as $id) {
                $item->bind_param('ii', $requestId, $id);
                $item->execute();
            }
            // Ghi file truoc COMMIT; neu bat ky buoc nao loi se ROLLBACK va xoa file.
            if ($file !== null) {
                $dir = self::evidenceStorageDir();
                if ($dir === null) throw new DomainException('Thư mục lưu minh chứng chưa sẵn sàng.');
                $randomName = bin2hex(random_bytes(16)) . '.' . $file['extension'];
                $storedFile = $dir . DIRECTORY_SEPARATOR . $randomName;
                if (!move_uploaded_file($file['tmp'], $storedFile)) {
                    throw new DomainException('Không lưu được file minh chứng. Vui lòng thử lại.');
                }
                $attachment = $this->db->prepare('INSERT INTO minhchungphuckhao
                    (maphieu, tenfilegoc, tenfileluu, loaimime, kichthuoc)
                    VALUES (?, ?, ?, ?, ?)');
                $attachment->bind_param('isssi', $requestId, $file['origin'], $randomName, $file['mime'], $file['size']);
                $attachment->execute();
            }
            $this->db->commit();
            return $requestId;
        } catch (Throwable $e) {
            $this->db->rollback();
            if ($storedFile !== null && is_file($storedFile)) @unlink($storedFile);
            throw $e;
        }
    }
}
