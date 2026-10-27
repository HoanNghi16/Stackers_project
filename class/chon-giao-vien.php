<?php
declare(strict_types=1);

final class PhatTeacherRules
{
    /** Validate codes against data loaded ONLY from the authenticated user's school. */
    public static function quotaError(int $selected, int $quota): ?string
    {
        return $quota < 1 || $selected !== $quota
            ? "Số lượng giáo viên không đúng chỉ tiêu: cần {$quota}, hiện có {$selected}."
            : null;
    }

    public static function verify(array $requested, array $teachers): array
    {
        $byCode = [];
        foreach ($teachers as $teacher) {
            $byCode[trim((string)$teacher['code'])] = $teacher;
        }
        $valid = []; $errors = []; $seen = [];
        foreach ($requested as $code) {
            if (!is_string($code) && !is_int($code)) {
                $errors[] = 'Mã giáo viên sai định dạng.'; continue;
            }
            $code = trim((string)$code);
            if ($code === '' || strlen($code) > 128) {
                $errors[] = 'Mã giáo viên trống hoặc quá dài.'; continue;
            }
            if (isset($seen[$code])) {
                $errors[] = "Giáo viên {$code} bị trùng."; continue;
            }
            $seen[$code] = true;
            if (!array_key_exists($code, $byCode)) {
                $errors[] = "Giáo viên {$code} không tồn tại trong danh sách của trường."; continue;
            }
            $teacher = $byCode[$code];
            if (!$teacher['eligible']) {
                $errors[] = "Giáo viên {$code} không đủ điều kiện."; continue;
            }
            $valid[] = $teacher;
        }
        return ['valid' => $valid, 'errors' => $errors];
    }
}

final class PhatTeacherSelection
{
    private string $teacherTable;
    private string $schoolColumn;
    private array $assignmentTables = [];

    public function __construct(
        private mysqli $db,
        private array $cfg,
        private string $schoolId,
        private string $examId,
        private string $userId
    ) {
        if ($schoolId === '' || $examId === '' || $userId === '') {
            throw new RuntimeException('Thiếu trường, kỳ thi hoặc người đăng nhập.');
        }
        $this->teacherTable = $this->ident($cfg['teacher_table']);
        $cols = $this->columnsOf($cfg['teacher_table']);
        foreach (['teacher_id','teacher_user_fk','teacher_subject_fk','nomination_column'] as $name) {
            if (!in_array($cfg[$name], $cols, true)) {
                throw new RuntimeException('Thiếu cột ' . $cfg[$name] . ' trong bảng giaovien.');
            }
        }
        $school = $this->choose($cols, $cfg['teacher_school_candidates']);
        if ($school === null) {
            throw new RuntimeException('Chưa xác định cột trường của bảng giaovien. Xem scripts/phat-inspect-main-schema.php và cấu hình class/chon-giao-vien-config.php.');
        }
        $this->schoolColumn = $this->ident($school);
        foreach (['hosonguoidung','truonghoc'] as $table) {
            if (!$this->tableExists($table)) throw new RuntimeException('Thiếu bảng ' . $table . ' từ schema nhóm.');
        }
        foreach (['manguoidung','ho','ten'] as $col) {
            if (!in_array($col, $this->columnsOf('hosonguoidung'), true)) {
                throw new RuntimeException('Thiếu cột hosonguoidung.' . $col);
            }
        }
        foreach ($cfg['assigned_tables'] as $table) {
            if ($this->tableExists($table) && in_array('magiaovien', $this->columnsOf($table), true)) {
                $this->assignmentTables[] = $this->ident($table);
            }
        }
        if (count($this->assignmentTables) !== count($cfg['assigned_tables'])) {
            throw new RuntimeException('Chưa có đủ các bảng phân công để kiểm tra an toàn điều kiện giáo viên.');
        }
        if (!$this->tableExists('monhoc') || !in_array('tenmonhoc', $this->columnsOf('monhoc'), true)) {
            throw new RuntimeException('Thiếu bảng/cột monhoc.tenmonhoc để hiển thị môn.');
        }
    }

    private function ident(string $s): string
    {
        if (!preg_match('/^[A-Za-z_][A-Za-z0-9_]*$/D', $s)) throw new InvalidArgumentException('Tên bảng/cột không hợp lệ.');
        return '`' . $s . '`';
    }
    private function tableExists(string $table): bool
    {
        $s = $this->db->prepare('SELECT COUNT(*) AS n FROM information_schema.TABLES WHERE TABLE_SCHEMA=DATABASE() AND TABLE_NAME=?');
        $s->bind_param('s', $table); $s->execute();
        return (int)$s->get_result()->fetch_assoc()['n'] > 0;
    }
    private function columnsOf(string $table): array
    {
        $s = $this->db->prepare('SELECT COLUMN_NAME FROM information_schema.COLUMNS WHERE TABLE_SCHEMA=DATABASE() AND TABLE_NAME=?');
        $s->bind_param('s', $table); $s->execute();
        return array_column($s->get_result()->fetch_all(MYSQLI_ASSOC), 'COLUMN_NAME');
    }
    private function choose(array $cols, array $candidates): ?string
    {
        foreach ($candidates as $candidate) if (in_array($candidate, $cols, true)) return $candidate;
        return null;
    }

    public function teachers(): array
    {
        $gvId = $this->ident($this->cfg['teacher_id']);
        $userFk = $this->ident($this->cfg['teacher_user_fk']);
        $subjectFk = $this->ident($this->cfg['teacher_subject_fk']);
        // Main dùng giaovien/hosonguoidung và các bảng phân công; không có bảng teachers.
        $exclusions = ["gv.`duocdecu` = 0"];
        foreach ($this->assignmentTables as $table) {
            $exclusions[] = "NOT EXISTS (SELECT 1 FROM {$table} pc WHERE pc.magiaovien=gv.{$gvId})";
        }
        $isFree = implode(' AND ', $exclusions);
        // Schema đã xác nhận có monhoc(mamonhoc,tenmonhoc).
        // LEFT JOIN để không mất GV nếu môn học chưa được khai báo.
        $sql = "SELECT gv.{$gvId} AS id, CAST(gv.{$gvId} AS CHAR) AS code,
                   CONCAT_WS(' ', hs.ho, hs.ten) AS name,
                   COALESCE(mh.tenmonhoc, CONCAT('Môn #', gv.{$subjectFk})) AS subject,
                   gv.`duocdecu` AS nominated,
                   CASE WHEN ({$isFree}) THEN 1 ELSE 0 END AS eligible,
                   CASE WHEN gv.`duocdecu` = 1 THEN 'Đã đề cử'
                        WHEN NOT ({$isFree}) THEN 'Đã phân công'
                        ELSE 'Đủ điều kiện' END AS status_label
                FROM {$this->teacherTable} gv
                JOIN hosonguoidung hs ON gv.{$userFk}=hs.manguoidung
                LEFT JOIN monhoc mh ON mh.mamonhoc = gv.{$subjectFk}
                WHERE gv.{$this->schoolColumn}=?
                ORDER BY hs.ho, hs.ten, gv.{$gvId}";
        $s = $this->db->prepare($sql);
        $s->bind_param('s', $this->schoolId); $s->execute();
        $result = $s->get_result()->fetch_all(MYSQLI_ASSOC);
        foreach ($result as &$row) {
            $row['id'] = (string)$row['id']; $row['code'] = (string)$row['code'];
            $row['name'] = trim((string)$row['name']); $row['subject'] = (string)$row['subject'];
            $row['eligible'] = (int)$row['eligible'] === 1;
            $row['nominated'] = (int)$row['nominated'] === 1;
        }
        unset($row);
        return $result;
    }

    /** Required teacher count is recorded in the group's existing table. */
    public function quota(): int
    {
        if (!$this->tableExists('chitieugiaovienthamgiahd')) {
            throw new RuntimeException('Thiếu bảng chitieugiaovienthamgiahd của nhóm.');
        }
        $stmt = $this->db->prepare('SELECT soluongyeucau FROM chitieugiaovienthamgiahd WHERE matruong=? AND makythi=? LIMIT 2');
        $stmt->bind_param('ss', $this->schoolId, $this->examId);
        $stmt->execute();
        $rows = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
        if (count($rows) > 1) {
            throw new DomainException('Có nhiều chỉ tiêu cùng trường và kỳ thi; cần nhóm kiểm tra dữ liệu trùng.');
        }
        return $rows ? max(0, (int)$rows[0]['soluongyeucau']) : 0;
    }

    /** Session draft scoped to both account, school and exam (not shared across schools). */
    private function sessionKey(): string
    {
        return 'phat_teacher_selection:' . $this->userId . ':' . $this->schoolId . ':' . $this->examId;
    }

    public function state(): array
    {
        $teachers = $this->teachers();
        $saved = $_SESSION[$this->sessionKey()] ?? [];
        $selected = is_array($saved['selected'] ?? null) ? $saved['selected'] : [];
        $status = in_array($saved['status'] ?? null, ['NEW', 'DRAFT', 'SUBMITTED'], true)
            ? $saved['status'] : 'NEW';
        // Never call nomination flags a recorded submission: the DB stores no
        // submitter/batch history. Session 'SUBMITTED' is local to the current login.
        return ['teachers' => $teachers, 'selected' => $selected,
                'quota' => $this->quota(), 'status' => $status, 'persistence_ready' => true];
    }

    public function save(array $codes, bool $submit): array
    {
        if (count($codes) > 1000) throw new InvalidArgumentException('Tối đa 1000 giáo viên.');
        if (($_SESSION[$this->sessionKey()]['status'] ?? null) === 'SUBMITTED') {
            throw new DomainException('Danh sách đã gửi trong phiên này, không thể sửa hoặc gửi lại.');
        }
        if (!$submit) {
            $result = PhatTeacherRules::verify($codes, $this->teachers());
            if ($result['errors']) throw new DomainException(implode(' ', $result['errors']));
            if (!$result['valid']) throw new DomainException('Chưa chọn giáo viên để lưu nháp.');
            $quota = $this->quota();
            if ($quota > 0 && count($result['valid']) > $quota) {
                throw new DomainException('Danh sách nháp vượt chỉ tiêu được giao.');
            }
            $_SESSION[$this->sessionKey()] = [
                'selected' => array_column($result['valid'], 'code'),
                'status' => 'DRAFT',
            ];
            return ['status' => 'DRAFT', 'selected_count' => count($result['valid']),
                    'quota' => $quota];
        }

        // From this point on, submitting writes to the shared team's DB.
        // Lock rows belonging to the school to prevent concurrent submissions
        // through this feature from selecting already-nominated teachers.
        $this->db->begin_transaction();
        try {
            $lock = $this->db->prepare('SELECT magiaovien FROM giaovien WHERE matruong=? ORDER BY magiaovien FOR UPDATE');
            $lock->bind_param('s', $this->schoolId);
            $lock->execute();
            $lock->get_result()->fetch_all(MYSQLI_ASSOC);

            $quota = $this->quota();
            if ($quota < 1) throw new DomainException('Chưa có chỉ tiêu hợp lệ cho trường và kỳ thi.');
            $result = PhatTeacherRules::verify($codes, $this->teachers());
            if ($result['errors']) throw new DomainException(implode(' ', $result['errors']));
            if ($error = PhatTeacherRules::quotaError(count($result['valid']), $quota)) {
                throw new DomainException($error);
            }
            $stmt = $this->db->prepare(
                'UPDATE giaovien SET duocdecu=1 WHERE magiaovien=? AND matruong=? AND duocdecu=0'
            );
            foreach ($result['valid'] as $teacher) {
                $id = (string)$teacher['id'];
                $stmt->bind_param('ss', $id, $this->schoolId);
                $stmt->execute();
                if ($stmt->affected_rows !== 1) {
                    throw new DomainException('Giáo viên ' . $id . ' đã được đề cử hoặc không còn hợp lệ.');
                }
            }
            $this->db->commit();
            $_SESSION[$this->sessionKey()] = [
                'selected' => array_column($result['valid'], 'code'),
                'status' => 'SUBMITTED',
            ];
            return ['status' => 'SUBMITTED', 'selected_count' => count($result['valid']),
                    'quota' => $quota];
        } catch (Throwable $e) {
            $this->db->rollback();
            throw $e;
        }
    }
    public function importXlsx(array $file): array
    {
        $codes=PhatTeacherXlsx::readTeacherCodes($file);
        $checked=PhatTeacherRules::verify($codes,$this->teachers());
        return ['codes'=>array_column($checked['valid'],'code'),'errors'=>$checked['errors'],'total_rows'=>count($codes)];
    }
}

final class PhatTeacherXlsx
{
    /** File must contain a column A header "Ma GV" / "ma_gv" / "teacher_code" and teacher codes below it. */
    public static function readTeacherCodes(array $file): array
    {
        if (($file['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_OK) {
            throw new InvalidArgumentException('Không tải lên được file Excel.');
        }
        if (($file['size'] ?? 0) > 1048576 || ($file['size'] ?? 0) < 50) {
            throw new InvalidArgumentException('File Excel phải lớn hơn 0 và tối đa 1 MB.');
        }
        if (strtolower(pathinfo((string)($file['name'] ?? ''), PATHINFO_EXTENSION)) !== 'xlsx') {
            throw new InvalidArgumentException('Chỉ hỗ trợ Excel .xlsx. Hãy lưu file .xls dưới dạng .xlsx.');
        }
        if (!class_exists('ZipArchive') || !function_exists('simplexml_load_string')) {
            throw new RuntimeException('PHP thiếu extension zip hoặc simplexml để đọc .xlsx.');
        }
        $zip = new ZipArchive();
        if ($zip->open($file['tmp_name']) !== true) {
            throw new InvalidArgumentException('File Excel không hợp lệ hoặc bị hỏng.');
        }
        try {
            $shared = [];
            self::limitPart($zip, 'xl/sharedStrings.xml');
            $sharedXml = $zip->getFromName('xl/sharedStrings.xml');
            if ($sharedXml !== false) {
                $sx = self::xml($sharedXml);
                foreach ($sx->children('http://schemas.openxmlformats.org/spreadsheetml/2006/main')->si as $si) {
                    $parts = $si->xpath('.//*[local-name()="t"]');
                    $shared[] = implode('', array_map(static fn($x) => (string)$x, $parts ?: []));
                }
            }
            self::limitPart($zip, 'xl/worksheets/sheet1.xml');
            $xml = $zip->getFromName('xl/worksheets/sheet1.xml');
            if ($xml === false) throw new InvalidArgumentException('File thiếu Sheet 1.');
            $sx = self::xml($xml);
            $rows = $sx->xpath('//*[local-name()="sheetData"]/*[local-name()="row"]');
            if (!$rows || count($rows) > 1002) {
                throw new InvalidArgumentException('Excel trống hoặc quá 1000 dòng.');
            }
            $values = [];
            foreach ($rows as $row) {
                $cells = $row->xpath('./*[local-name()="c" and starts-with(@r,"A")]');
                $cell = $cells[0] ?? null;
                if ($cell === null) { $values[] = ''; continue; }
                $valueNodes = $cell->xpath('./*[local-name()="v"]');
                $inlineNodes = $cell->xpath('./*[local-name()="is"]/*[local-name()="t"]');
                $value = (string)($valueNodes[0] ?? $inlineNodes[0] ?? '');
                if ((string)$cell['t'] === 's') $value = $shared[(int)$value] ?? '';
                $values[] = trim($value);
            }
            $header = trim(array_shift($values));
            if (!in_array(function_exists('mb_strtolower') ? mb_strtolower($header, 'UTF-8') : strtolower($header), ['mã gv','ma gv','ma_gv','teacher_code','code'], true)) {
                throw new InvalidArgumentException('Sai mẫu Excel: ô A1 phải là "Mã GV" (hoặc ma_gv).');
            }
            $values = array_values(array_filter($values, static fn($v) => $v !== ''));
            if (!$values) throw new InvalidArgumentException('File Excel không có giáo viên.');
            return $values;
        } finally {
            $zip->close();
        }
    }

    private static function limitPart(ZipArchive $zip, string $path): void
    {
        $stat = $zip->statName($path);
        if ($stat !== false && (int)$stat['size'] > 2_000_000) {
            throw new InvalidArgumentException('File Excel vượt giới hạn dữ liệu cho phép.');
        }
    }

    private static function xml(string $xml): SimpleXMLElement
    {
        if (strlen($xml) > 2_000_000 || stripos($xml, '<!DOCTYPE') !== false) {
            throw new InvalidArgumentException('File Excel không hợp lệ.');
        }
        $sx = simplexml_load_string($xml, SimpleXMLElement::class, LIBXML_NONET);
        if ($sx === false) throw new InvalidArgumentException('File Excel chứa XML lỗi.');
        return $sx;
    }
}
