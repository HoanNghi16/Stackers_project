<?php
declare(strict_types=1);

final class PhatTeacherRules
{
    /** Validate codes against data loaded ONLY from the authenticated user's school. */
    public static function quotaError(int $selected, int $quota): ?string
    {
        $required = self::requiredCount($quota);
        return $required < 1 || $selected !== $required
            ? "Cần chọn đúng {$required} giáo viên (gồm 3 dự bị) trước khi gửi. Hiện danh sách có {$selected} giáo viên."
            : null;
    }

    public static function requiredCount(int $quota): int
    {
        return $quota > 0 ? $quota + 3 : 0;
    }

    public static function normalizedName(string $name): string
    {
        $name = preg_replace('/\s+/u', ' ', trim($name)) ?? trim($name);
        return function_exists('mb_strtolower') ? mb_strtolower($name, 'UTF-8') : strtolower($name);
    }

    public static function sameName(string $a, string $b): bool
    {
        return self::normalizedName($a) === self::normalizedName($b);
    }

    /**
     * Verify nominations against the school roster. Excel callers may pass
     * physical row numbers so each invalid row gets an actionable explanation.
     */
    public static function verify(array $requested, array $teachers, array $sourceRows = [], array $sourceNames = []): array
    {
        $byCode = [];
        foreach ($teachers as $teacher) {
            $byCode[trim((string)$teacher['code'])] = $teacher;
        }
        $valid = []; $errors = []; $seen = [];
        foreach ($requested as $index => $rawCode) {
            $prefix = isset($sourceRows[$index]) ? 'Dòng ' . (int)$sourceRows[$index] . ': ' : '';
            if (!is_string($rawCode) && !is_int($rawCode)) {
                $errors[] = $prefix . 'Mã giáo viên không hợp lệ. Vui lòng kiểm tra lại.';
                continue;
            }
            $code = trim((string)$rawCode);
            if ($code === '' || strlen($code) > 128) {
                $errors[] = $prefix . 'Mã giáo viên đang để trống hoặc không hợp lệ.';
                continue;
            }
            if (isset($seen[$code])) {
                $errors[] = $prefix . "Giáo viên mã {$code} bị trùng trong danh sách (đã có ở " . $seen[$code] . ').';
                continue;
            }
            $seen[$code] = isset($sourceRows[$index]) ? 'dòng ' . (int)$sourceRows[$index] : 'lựa chọn trước';
            $excelName = trim((string)($sourceNames[$index] ?? ''));
            $display = $excelName !== '' ? " (họ tên trong tệp: {$excelName})" : '';
            if (!isset($byCode[$code])) {
                $errors[] = $prefix . "Không tìm thấy giáo viên mã {$code}{$display} trong danh sách của trường. Vui lòng kiểm tra lại mã và họ tên.";
                continue;
            }
            $teacher = $byCode[$code];
            if ($excelName !== '' && !self::sameName($excelName, (string)$teacher['name'])) {
                $errors[] = $prefix . "Giáo viên mã {$code}: họ tên '{$excelName}' trong tệp không khớp với hồ sơ nhà trường ('{$teacher['name']}').";
                continue;
            }
            if (!$teacher['eligible']) {
                $reason = trim((string)($teacher['eligibility_reason'] ?? $teacher['status_label'] ?? 'Chưa đủ điều kiện để đề cử'));
                $errors[] = $prefix . "Giáo viên mã {$code} ({$teacher['name']}): {$reason}.";
                continue;
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
    /** Metadata gom mot lan moi request, thay cho nhieu truy van information_schema. */
    private array $schemaColumns = [];

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
        $this->loadSchema();
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
        if (!preg_match('/^[A-Za-z_][A-Za-z0-9_]*$/D', $s)) throw new InvalidArgumentException('Thông tin hệ thống chưa hợp lệ. Vui lòng liên hệ quản trị viên.');
        return '`' . $s . '`';
    }
    /** Doc metadata cua cac bang can thiet trong MOT truy van SQL. */
    private function loadSchema(): void
    {
        $tables = array_values(array_unique(array_merge(
            [$this->cfg['teacher_table'], 'hosonguoidung', 'truonghoc', 'monhoc', 'chitieugiaovienthamgiahd'],
            $this->cfg['assigned_tables']
        )));
        foreach ($tables as $table) $this->ident($table); // Validate SQL identifiers.
        $placeholders = implode(',', array_fill(0, count($tables), '?'));
        $sql = "SELECT TABLE_NAME, COLUMN_NAME FROM information_schema.COLUMNS
                WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME IN ({$placeholders})";
        $stmt = $this->db->prepare($sql);
        $stmt->bind_param(str_repeat('s', count($tables)), ...$tables);
        $stmt->execute();
        foreach ($stmt->get_result()->fetch_all(MYSQLI_ASSOC) as $row) {
            $this->schemaColumns[$row['TABLE_NAME']][] = $row['COLUMN_NAME'];
        }
    }

    private function tableExists(string $table): bool
    {
        return isset($this->schemaColumns[$table]);
    }

    private function columnsOf(string $table): array
    {
        return $this->schemaColumns[$table] ?? [];
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
        // duocdecu = 1 means nominated BY SCHOOL, not assigned by SO_GD.
        // The SO_GD process assigns invigilators separately in phanconggacthi.
        // Existing nominations must remain visible, but cannot be submitted again.
        $assignReasons = [
            'phanconggacthi' => 'Đã có phân công gác thi',
            'phancongchamthi' => 'Đã có phân công chấm thi',
            'phancongphuckhao' => 'Đã có phân công phúc khảo',
            'phancongrade' => 'Đã có phân công ra đề',
        ];
        $assignmentChecks = [];
        $statusChecks = [];
        foreach ($this->assignmentTables as $table) {
            $name = trim($table, '`');
            $exists = "EXISTS (SELECT 1 FROM {$table} pc WHERE pc.magiaovien=gv.{$gvId})";
            $assignmentChecks[] = "NOT {$exists}";
            $reason = $assignReasons[$name] ?? ('Đã có phân công trong ' . $name);
            $quotedReason = "'" . str_replace("'", "''", $reason) . "'";
            $statusChecks[] = "WHEN {$exists} THEN {$quotedReason}";
        }
        $isAvailable = implode(' AND ', array_merge(['gv.`duocdecu` = 0'], $assignmentChecks));
        $reasonSql = "CASE
                WHEN gv.`duocdecu` = 1 THEN 'Đã được trường đề cử lên Sở GD&ĐT (không cần chọn lại)'
                " . implode("
                ", $statusChecks) . "
                WHEN gv.`duocdecu` IS NULL THEN 'Chưa xác định được trạng thái đề cử. Vui lòng liên hệ quản trị viên'
                ELSE 'Có thể đề cử' END";
        // No extra teacher eligibility requirements are invented here. Additional
        // qualification rules must be agreed with the team and represented by data.
        $sql = "SELECT gv.{$gvId} AS id, CAST(gv.{$gvId} AS CHAR) AS code,
                   CONCAT_WS(' ', hs.ho, hs.ten) AS name,
                   COALESCE(mh.tenmonhoc, 'Chưa xác định bộ môn') AS subject,
                   gv.`duocdecu` AS nominated,
                   CASE WHEN ({$isAvailable}) THEN 1 ELSE 0 END AS eligible,
                   {$reasonSql} AS eligibility_reason,
                   {$reasonSql} AS status_label
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
    public function quota(bool $forUpdate = false): int
    {
        if (!$this->tableExists('chitieugiaovienthamgiahd')) {
            throw new RuntimeException('Thiếu bảng chitieugiaovienthamgiahd của nhóm.');
        }
        // Khi gui, khoa DUY NHAT dong chi tieu cua truong/ky thi thay vi khoa het GV.
        $sql = 'SELECT soluongyeucau FROM chitieugiaovienthamgiahd WHERE matruong=? AND makythi=? LIMIT 2';
        $stmt = $this->db->prepare($sql . ($forUpdate ? ' FOR UPDATE' : ''));
        $stmt->bind_param('ss', $this->schoolId, $this->examId);
        $stmt->execute();
        $rows = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
        if (count($rows) > 1) {
            throw new DomainException('Thông tin chỉ tiêu của trường đang bị trùng. Vui lòng liên hệ quản trị viên để kiểm tra.');
        }
        return $rows ? max(0, (int)$rows[0]['soluongyeucau']) : 0;
    }

    /** Nháp chỉ lưu những giáo viên CHƯA đề cử, không lưu lặp giáo viên đã có duocdecu=1. */
    private function sessionKey(): string
    {
        return 'phat_teacher_selection:' . $this->userId . ':' . $this->schoolId . ':' . $this->examId;
    }

    private static function countNominated(array $teachers): int
    {
        return count(array_filter($teachers, static fn(array $t): bool => (bool)$t['nominated']));
    }

    public function state(): array
    {
        $teachers = $this->teachers();
        $quota = $this->quota();
        $required = PhatTeacherRules::requiredCount($quota);
        $nominated = self::countNominated($teachers);
        $saved = $_SESSION[$this->sessionKey()] ?? [];
        $draftCodes = is_array($saved['selected'] ?? null) ? $saved['selected'] : [];
        // Một giáo viên vừa được đề cử bởi phiên khác không được tính là nháp nữa.
        $lookup = [];
        foreach ($teachers as $t) $lookup[$t['code']] = $t;
        $pending = [];
        foreach ($draftCodes as $code) {
            $code = (string)$code;
            if (isset($lookup[$code]) && $lookup[$code]['eligible']) $pending[$code] = $code;
        }
        $pending = array_values($pending);
        // Khi DB đã đủ hoặc vượt hạn mức, bỏ nháp cũ khỏi phép đếm để không nhân đôi.
        if ($required > 0 && $nominated >= $required) $pending = [];
        $submitted = $required > 0 && $nominated === $required;
        $status = $submitted ? 'SUBMITTED' : ($pending ? 'DRAFT' : 'NEW');
        $warnings = [];
        if ($required > 0 && $nominated > $required) {
            $warnings[] = "Nhà trường đã gửi {$nominated} giáo viên, vượt số lượng được phép ({$required} giáo viên, gồm 3 dự bị). Vui lòng liên hệ Sở GD&ĐT để kiểm tra lại.";
        }
        $possible = $nominated + count(array_filter($teachers, static fn(array $t): bool => (bool)$t['eligible']));
        if ($required > 0 && $possible < $required) {
            $warnings[] = "Nhà trường hiện có {$possible} giáo viên đã đề cử hoặc đủ điều kiện, trong khi cần {$required} người. Vui lòng kiểm tra và bổ sung giáo viên đủ điều kiện.";
        }
        return [
            'teachers' => $teachers, 'selected' => $pending,
            'quota' => $quota, 'required_count' => $required,
            'nominated_count' => $nominated, 'status' => $status,
            'warnings' => $warnings, 'persistence_ready' => true,
        ];
    }

    /**
     * Luu nhap khong khoa database. Khi gui, khoa dong chi tieu cua truong
     * de tranh hai phien gui dong thoi, kiem tra lai du lieu that trong DB,
     * cap nhat tat ca GV moi chi bang MOT UPDATE atomic.
     */
    public function save(array $codes, bool $submit): array
    {
        if (count($codes) > 1000) throw new InvalidArgumentException('Tối đa 1000 giáo viên.');
        if ($submit) $this->db->begin_transaction();
        try {
            $quota = $this->quota($submit);
            $required = PhatTeacherRules::requiredCount($quota);
            if ($required < 1) throw new DomainException('Trường chưa có chỉ tiêu hợp lệ cho kỳ thi này.');
            $teachers = $this->teachers();
            $nominated = self::countNominated($teachers);
            if ($nominated > $required) {
                throw new DomainException("Nhà trường đã gửi {$nominated} giáo viên, vượt số lượng cho phép ({$required} người). Vui lòng liên hệ Sở GD&ĐT để kiểm tra.");
            }
            $checked = PhatTeacherRules::verify($codes, $teachers);
            if ($checked['errors']) throw new DomainException(implode(' | ', $checked['errors']));
            $pending = count($checked['valid']);
            $total = $nominated + $pending;
            if ($total > $required) {
                throw new DomainException("Danh sách đang có {$nominated} giáo viên đã gửi và {$pending} giáo viên mới chọn (tổng {$total}), vượt mức {$required} người, gồm 3 dự bị. Vui lòng bỏ bớt giáo viên mới chọn.");
            }
            if (!$submit) {
                $_SESSION[$this->sessionKey()] = [
                    'selected' => array_column($checked['valid'], 'code'),
                    'status' => $pending ? 'DRAFT' : 'NEW',
                ];
                return ['status' => $pending ? 'DRAFT' : 'NEW', 'selected_count' => $total,
                        'nominated_count' => $nominated, 'required_count' => $required];
            }
            if (!$pending) throw new DomainException('Chưa có giáo viên mới nào được chọn để gửi.');
            if ($error = PhatTeacherRules::quotaError($total, $quota)) throw new DomainException($error);

            // Duocdecu=1 la DE CU CUA TRUONG, khong ghi vao bang phan cong cua So.
            $ids = array_column($checked['valid'], 'id');
            $gvIdColumn = $this->ident($this->cfg['teacher_id']);
            $placeholders = implode(',', array_fill(0, count($ids), '?'));
            $sql = "UPDATE {$this->teacherTable} SET `duocdecu`=1
                    WHERE {$this->schoolColumn}=? AND `duocdecu`=0
                      AND {$gvIdColumn} IN ({$placeholders})";
            $stmt = $this->db->prepare($sql);
            $params = array_merge([$this->schoolId], array_map('strval', $ids));
            $stmt->bind_param(str_repeat('s', count($params)), ...$params);
            $stmt->execute();
            if ($stmt->affected_rows !== $pending) {
                throw new DomainException('Danh sách giáo viên vừa thay đổi trong lúc gửi. Chưa thể hoàn tất; vui lòng tải lại trang và kiểm tra trước khi gửi lại.');
            }
            $this->db->commit();
            $_SESSION[$this->sessionKey()] = ['selected' => [], 'status' => 'SUBMITTED'];
            // Sau commit, tra trang thai moi trong cung request. Neu doc lai loi,
            // KHONG bao gui that bai vi MySQL da commit thanh cong.
            $fresh = null;
            try {
                $fresh = $this->state();
            } catch (Throwable $reloadError) {
                error_log('[chon-giao-vien-after-commit] ' . $reloadError->getMessage());
            }
            return ['status' => 'SUBMITTED', 'selected_count' => $required,
                    'nominated_count' => $required, 'required_count' => $required,
                    'state' => $fresh];
        } catch (Throwable $e) {
            if ($submit) $this->db->rollback();
            throw $e;
        }
    }

    /** Import cộng dồn vào danh sách đang chọn, không thay thế; các dòng sai KHÔNG được nhập một phần. */
    public function importXlsx(array $file, array $pendingCodes = []): array
    {
        $rows = PhatTeacherXlsx::readTeacherRows($file);
        $teachers = $this->teachers();
        $byCode = [];
        foreach ($teachers as $teacher) $byCode[$teacher['code']] = $teacher;
        $nominated = self::countNominated($teachers);
        $quota = $this->quota();
        $required = PhatTeacherRules::requiredCount($quota);
        if ($required < 1) throw new DomainException('Chưa có chỉ tiêu đề cử hợp lệ.');
        if ($nominated > $required) throw new DomainException("Nhà trường đã gửi {$nominated} giáo viên, vượt số lượng cho phép ({$required} người). Không thể bổ sung thêm giáo viên.");
        $existing = PhatTeacherRules::verify($pendingCodes, $teachers);
        if ($existing['errors']) throw new DomainException(implode(' | ', $existing['errors']));
        $pendingSet = array_fill_keys(array_column($existing['valid'], 'code'), true);
        $codes = []; $errors = []; $skipped = []; $seenRows = [];
        foreach ($rows as $row) {
            $line = (int)$row['row'];
            $code = trim((string)$row['code']);
            $name = trim((string)($row['name'] ?? ''));
            if (isset($seenRows[$code])) {
                $errors[] = "Dòng {$line}: giáo viên mã {$code} bị trùng với dòng {$seenRows[$code]} trong tệp Excel.";
                continue;
            }
            $seenRows[$code] = $line;
            $one = PhatTeacherRules::verify([$code], $teachers, [$line], [$name]);
            if ($one['errors']) {
                if (isset($byCode[$code]) && $byCode[$code]['nominated'] && ($name === '' || PhatTeacherRules::sameName($name, $byCode[$code]['name']))) {
                    $skipped[] = "Dòng {$line}: {$byCode[$code]['name']} (mã {$code}) đã được đề cử trước đó, không thêm trùng.";
                } else {
                    array_push($errors, ...$one['errors']);
                }
                continue;
            }
            if (isset($pendingSet[$code])) {
                $skipped[] = "Dòng {$line}: {$byCode[$code]['name']} (mã {$code}) đã có trong danh sách đang chọn.";
                continue;
            }
            $pendingSet[$code] = true;
            $codes[] = $code;
        }
        $total = $nominated + count($pendingSet);
        if ($total > $required) {
            $errors[] = "Số lượng vượt mức cho phép: {$nominated} giáo viên đã gửi và " . count($pendingSet) . " giáo viên mới chọn, tổng cộng {$total} người. Nhà trường chỉ được đề cử {$required} người (gồm 3 dự bị).";
        }
        return [
            'codes' => $errors ? [] : $codes,
            'errors' => $errors, 'skipped' => $skipped,
            'total_rows' => count($rows), 'valid_count' => $errors ? 0 : count($codes),
            'invalid_count' => count($errors), 'required_count' => $required,
        ];
    }
}

final class PhatTeacherXlsx
{
    /** File must contain a column A header "Ma GV" / "ma_gv" / "teacher_code" and teacher codes below it. */
    public static function readTeacherCodes(array $file): array
    {
        return array_column(self::readTeacherRows($file), 'code');
    }

    /** Keep original XLSX row numbers for accurate per-row validation errors. */
    public static function readTeacherRows(array $file): array
    {
        $uploadError = (int)($file['error'] ?? UPLOAD_ERR_NO_FILE);
        if ($uploadError !== UPLOAD_ERR_OK) {
            $messages = [
                UPLOAD_ERR_INI_SIZE => 'Tệp Excel quá lớn. Vui lòng chọn tệp nhỏ hơn.',
                UPLOAD_ERR_FORM_SIZE => 'Tệp Excel quá lớn. Vui lòng chọn tệp nhỏ hơn.',
                UPLOAD_ERR_PARTIAL => 'Tệp Excel chưa tải lên đầy đủ. Vui lòng chọn lại tệp rồi thử lại.',
                UPLOAD_ERR_NO_FILE => 'Bạn chưa chọn tệp Excel.',
                UPLOAD_ERR_NO_TMP_DIR => 'Không thể nhận tệp Excel lúc này. Vui lòng thử lại sau hoặc liên hệ quản trị viên.',
                UPLOAD_ERR_CANT_WRITE => 'Không thể tải tệp Excel lên hệ thống. Vui lòng thử lại sau.',
                UPLOAD_ERR_EXTENSION => 'Không thể tải tệp Excel lên hệ thống. Vui lòng liên hệ quản trị viên.',
            ];
            throw new InvalidArgumentException($messages[$uploadError] ?? 'Không thể tải tệp Excel lên hệ thống. Vui lòng thử lại.');
        }
        if (($file['size'] ?? 0) > 1048576 || ($file['size'] ?? 0) < 50) {
            throw new InvalidArgumentException('Tệp Excel không được để trống và không được lớn hơn 1 MB.');
        }
        if (strtolower(pathinfo((string)($file['name'] ?? ''), PATHINFO_EXTENSION)) !== 'xlsx') {
            throw new InvalidArgumentException('Vui lòng chọn tệp Excel có định dạng .xlsx.');
        }
        if (!class_exists('ZipArchive') || !function_exists('simplexml_load_string')) {
            throw new RuntimeException('Chưa thể đọc tệp Excel. Vui lòng liên hệ quản trị viên.');
        }
        $zip = new ZipArchive();
        if ($zip->open($file['tmp_name']) !== true) {
            throw new InvalidArgumentException('Tệp Excel không mở được. Vui lòng kiểm tra tệp và thử lại.');
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
            if ($xml === false) throw new InvalidArgumentException('Không tìm thấy trang tính đầu tiên trong tệp Excel.');
            $sx = self::xml($xml);
            $rows = $sx->xpath('//*[local-name()="sheetData"]/*[local-name()="row"]');
            if (!$rows || count($rows) > 1002) {
                throw new InvalidArgumentException('Tệp Excel không có dữ liệu hoặc vượt quá 1.000 dòng cho phép.');
            }
            $values = [];
            $nameHeader = null;
            foreach ($rows as $row) {
                $rowNumber = (int)$row['r'];
                $cells = $row->xpath('./*[local-name()="c"]');
                $fields = [];
                foreach ($cells ?: [] as $cell) {
                    if (!preg_match('/^([A-Z]+)[0-9]+$/', (string)$cell['r'], $match)) continue;
                    $column = $match[1];
                    $valueNodes = $cell->xpath('./*[local-name()="v"]');
                    $inlineNodes = $cell->xpath('./*[local-name()="is"]//*[local-name()="t"]');
                    $value = (string)($valueNodes[0] ?? '');
                    if ((string)$cell['t'] === 's') {
                        $value = $shared[(int)$value] ?? '';
                    } elseif ((string)$cell['t'] === 'inlineStr') {
                        $value = implode('', array_map(static fn($v) => (string)$v, $inlineNodes ?: []));
                    }
                    $fields[$column] = trim($value);
                }
                if ($rowNumber === 1) {
                    foreach ($fields as $column => $headerText) {
                        $normalized = PhatTeacherRules::normalizedName($headerText);
                        if (in_array($normalized, ['họ và tên','họ tên','tên giáo viên','full_name','ho_ten','ho va ten'], true)) {
                            $nameHeader = $column;
                            break;
                        }
                    }
                }
                if ($rowNumber >= 1) $values[$rowNumber] = $fields;
            }
            $header = trim((string)($values[1]['A'] ?? ''));
            unset($values[1]);
            if (!in_array(PhatTeacherRules::normalizedName($header), ['mã gv','ma gv','ma_gv','teacher_code','code'], true)) {
                throw new InvalidArgumentException('Tệp Excel chưa đúng mẫu. Vui lòng đặt tiêu đề "Mã GV" ở ô A1.');
            }
            // Bỏ dòng cuối chỉ có style trống.
            while ($values && count(array_filter(end($values), static fn($v) => $v !== '')) === 0) array_pop($values);
            if (!$values) throw new InvalidArgumentException('Tệp Excel chưa có giáo viên nào để thêm.');
            $records = [];
            foreach ($values as $rowNumber => $fields) {
                $records[] = [
                    'row' => (int)$rowNumber,
                    'code' => (string)($fields['A'] ?? ''),
                    'name' => $nameHeader !== null ? (string)($fields[$nameHeader] ?? '') : '',
                ];
            }
            return $records;
        } finally {
            $zip->close();
        }
    }

    private static function limitPart(ZipArchive $zip, string $path): void
    {
        $stat = $zip->statName($path);
        if ($stat !== false && (int)$stat['size'] > 2_000_000) {
            throw new InvalidArgumentException('Tệp Excel quá lớn hoặc có quá nhiều dữ liệu.');
        }
    }

    private static function xml(string $xml): SimpleXMLElement
    {
        if (strlen($xml) > 2_000_000 || stripos($xml, '<!DOCTYPE') !== false) {
            throw new InvalidArgumentException('Tệp Excel không hợp lệ. Vui lòng kiểm tra và thử lại.');
        }
        $sx = simplexml_load_string($xml, SimpleXMLElement::class, LIBXML_NONET);
        if ($sx === false) throw new InvalidArgumentException('Không đọc được nội dung tệp Excel. Vui lòng kiểm tra và lưu lại tệp.');
        return $sx;
    }
}
