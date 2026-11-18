<?php
/**
 * QUẢN LÝ ĐỊA ĐIỂM THI - STACKERS
 * Một file PHP duy nhất.
 *
 * - Giữ giao diện Khung 1 và Khung 2.
 * - Điểm thi: chỉ chọn trường THPT có phòng.
 * - Trường dự thi: chỉ chọn THCS, tối đa 2 trường / điểm thi.
 * - Xếp phòng và cấp SBD cho học sinh thuộc các trường THCS đã chọn.
 * - Không xóa dữ liệu học sinh, bài thi, điểm thi hoặc bản ghi xếp phòng cũ.
 */

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

/* ===================== CẤU HÌNH ===================== */
const DB_HOST = 'sv66.dataonline.vn';
const DB_PORT = 3306;
const DB_NAME = 'leonovar6_ptud';
const DB_USER = 'leonovar6_ptud';
const DB_PASS = 'asdlfjlasdjfld';

const REQUIRE_SO_GD = false;
const DEFAULT_ROOM_CAPACITY = 24;
const MAX_SCHOOLS_PER_VENUE = 2;   // Giới hạn đúng 2 trường THCS dự thi tại 1 điểm thi
const HOME_URL = '';

/* ===================== KẾT NỐI DB ===================== */
try {
    $pdo = new PDO(
        "mysql:host=" . DB_HOST . ";port=" . DB_PORT . ";dbname=" . DB_NAME . ";charset=utf8mb4",
        DB_USER,
        DB_PASS,
        [
            PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_TIMEOUT            => 8,
        ]
    );
} catch (PDOException $e) {
    http_response_code(500);
    exit('Không kết nối được database: ' . htmlspecialchars($e->getMessage()));
}

/* ===================== HÀM CHUNG ===================== */
function jsonResponse(array $data, int $status = 200): void
{
    http_response_code($status);
    header('Content-Type: application/json; charset=utf-8');
    header('Cache-Control: no-store');
    echo json_encode($data, JSON_UNESCAPED_UNICODE | JSON_INVALID_UTF8_SUBSTITUTE);
    exit;
}

function failResult(string $code, string $message, string $detail = ''): array
{
    return [
        'ok' => false,
        'code' => $code,
        'message' => $message,
        'detail' => $detail,
    ];
}

function hasRole($value, string $role): bool
{
    if (is_array($value)) {
        foreach ($value as $item) {
            if (hasRole($item, $role)) {
                return true;
            }
        }
        return false;
    }
    return is_string($value) && strtoupper($value) === strtoupper($role);
}

$isSoGD = !REQUIRE_SO_GD || hasRole($_SESSION ?? [], 'SO_GD');

function tableExists(PDO $pdo, string $table): bool
{
    $st = $pdo->prepare('SHOW TABLES LIKE ?');
    $st->execute([$table]);
    return (bool)$st->fetchColumn();
}

function columnExists(PDO $pdo, string $table, string $column): bool
{
    $st = $pdo->prepare("SHOW COLUMNS FROM `$table` LIKE ?");
    $st->execute([$column]);
    return (bool)$st->fetch();
}

/**
 * Chỉ bổ sung cấu trúc còn thiếu; không xóa/sửa dữ liệu hiện có.
 * Bảng truongduthi theo đúng cấu trúc đang dùng trong database gốc.
 */
function ensureSchema(PDO $pdo): void
{
    $requiredTables = [
        'truonghoc', 'phong', 'phuong', 'hocsinh',
        'hosonguoidung', 'kythituyensinh', 'baithi', 'xepphongthi'
    ];

    foreach ($requiredTables as $table) {
        if (!tableExists($pdo, $table)) {
            throw new RuntimeException("Database thiếu bảng `$table`.");
        }
    }

    if (!columnExists($pdo, 'phong', 'succhua')) {
        $pdo->exec(
            'ALTER TABLE `phong` ADD COLUMN `succhua` INT NOT NULL DEFAULT ' .
            (int)DEFAULT_ROOM_CAPACITY
        );
    }

    if (!tableExists($pdo, 'truongduthi')) {
        $pdo->exec("
            CREATE TABLE `truongduthi` (
                `makythi` BIGINT(20) NOT NULL,
                `madiadiemthi` BIGINT(20) NOT NULL,
                `matruongthcs` BIGINT(20) NOT NULL
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
        ");
    }

    foreach (['maxepphong', 'mahocsinh', 'maphong'] as $column) {
        if (!columnExists($pdo, 'xepphongthi', $column)) {
            throw new RuntimeException("Bảng xepphongthi thiếu cột `$column`.");
        }
    }
}

try {
    ensureSchema($pdo);
} catch (Throwable $e) {
    http_response_code(500);
    exit('Lỗi kiểm tra cấu trúc database: ' . htmlspecialchars($e->getMessage(), ENT_QUOTES, 'UTF-8'));
}

/* ===================== KỲ THI ĐANG QUẢN LÝ ===================== */
$kythi = $pdo->query(
    'SELECT makythi, namtuyensinh FROM kythituyensinh ORDER BY namtuyensinh DESC LIMIT 1'
)->fetch();

if (!$kythi) {
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        jsonResponse(failResult('NO_EXAM', 'Chưa có kỳ thi tuyển sinh nào.'), 400);
    }
    exit('Chưa có kỳ thi tuyển sinh nào.');
}
$examId = (int)$kythi['makythi'];

/* ===================== THUẬT TOÁN PHÂN BỔ ===================== */
function sortVietnamese(array &$students): void
{
    if (class_exists('Collator')) {
        $collator = new Collator('vi_VN');
        usort($students, static function ($a, $b) use ($collator) {
            $cmp = $collator->compare((string)$a['ten'], (string)$b['ten']);
            if ($cmp !== 0) return $cmp;
            $cmp = $collator->compare((string)$a['ho'], (string)$b['ho']);
            if ($cmp !== 0) return $cmp;
            return (int)$a['mahocsinh'] <=> (int)$b['mahocsinh'];
        });
        return;
    }

    usort($students, static function ($a, $b) {
        $cmp = strcasecmp((string)$a['ten'], (string)$b['ten']);
        if ($cmp !== 0) return $cmp;
        $cmp = strcasecmp((string)$a['ho'], (string)$b['ho']);
        if ($cmp !== 0) return $cmp;
        return (int)$a['mahocsinh'] <=> (int)$b['mahocsinh'];
    });
}

function computeAllocation(PDO $pdo, array $exam, int $venueId, array $schoolIds): array
{
    $schoolIds = array_values(array_unique(array_filter(
        array_map('intval', $schoolIds),
        static fn($id) => $id > 0
    )));
    $n = count($schoolIds);

    if ($venueId <= 0 || $n < 1 || $n > MAX_SCHOOLS_PER_VENUE) {
        return failResult(
            'INVALID',
            'Mỗi điểm thi phải có từ 1 đến ' . MAX_SCHOOLS_PER_VENUE . ' trường THCS dự thi.'
        );
    }

    $st = $pdo->prepare("
        SELECT matruong, tentruong, UPPER(TRIM(loaitruong)) AS loai
        FROM truonghoc
        WHERE matruong = ?
        LIMIT 1
    ");
    $st->execute([$venueId]);
    $venue = $st->fetch();

    if (!$venue || $venue['loai'] !== 'THPT') {
        return failResult('INVALID_VENUE', 'Điểm thi phải là một trường THPT hợp lệ.');
    }

    $marks = implode(',', array_fill(0, $n, '?'));
    $st = $pdo->prepare("
        SELECT matruong, tentruong
        FROM truonghoc
        WHERE matruong IN ($marks)
          AND UPPER(TRIM(loaitruong)) = 'THCS'
    ");
    $st->execute($schoolIds);
    $schools = $st->fetchAll();

    if (count($schools) !== $n) {
        return failResult('INVALID_SCHOOL', 'Danh sách trường dự thi có trường không hợp lệ hoặc không phải THCS.');
    }

    $st = $pdo->prepare("
        SELECT h.mahocsinh, h.matruongdanghoc,
               COALESCE(u.ho, '') AS ho,
               COALESCE(u.ten, '') AS ten,
               COALESCE(h.sobaodanh, '') AS sobaodanh
        FROM hocsinh h
        LEFT JOIN hosonguoidung u ON u.manguoidung = h.manguoidung
        WHERE h.makythi = ?
          AND h.matruongdanghoc IN ($marks)
        ORDER BY h.mahocsinh
    ");
    $st->execute(array_merge([(int)$exam['makythi']], $schoolIds));
    $students = $st->fetchAll();

    if (!$students) {
        $schoolNames = implode(', ', array_column($schools, 'tentruong'));
        return failResult(
            'EMPTY_STUDENTS',
            'Các trường dự thi đã chọn hiện chưa có học sinh đăng ký trong kỳ thi này.',
            'Trường đã chọn: ' . $schoolNames . '. Hãy kiểm tra cột hocsinh.matruongdanghoc và hocsinh.makythi. Chương trình không tự chuyển học sinh từ trường khác sang.'
        );
    }

    sortVietnamese($students);

    $st = $pdo->prepare("
        SELECT maphong, tenphong, succhua
        FROM phong
        WHERE matruong = ? AND succhua > 0
        ORDER BY tenphong
    ");
    $st->execute([$venueId]);
    $rooms = $st->fetchAll();

    if (!$rooms) {
        return failResult('NO_ROOMS', 'Điểm thi "' . $venue['tentruong'] . '" chưa có phòng thi có sức chứa hợp lệ.');
    }

    $total = count($students);
    $capacity = array_sum(array_map(static fn($r) => (int)$r['succhua'], $rooms));

    if ($capacity < $total) {
        return failResult(
            'NO_CAPACITY',
            'Điểm thi "' . $venue['tentruong'] . '" không đủ sức chứa.',
            "Số học sinh: $total. Tổng sức chứa: $capacity (" . count($rooms) . ' phòng).'
        );
    }

    $schoolNames = [];
    $schoolCounts = [];
    foreach ($schools as $school) {
        $id = (int)$school['matruong'];
        $schoolNames[$id] = $school['tentruong'];
        $schoolCounts[$id] = 0;
    }

    $year2 = substr((string)$exam['namtuyensinh'], -2);
    $allocatedRooms = [];
    $offset = 0;

    foreach ($rooms as $roomIndex => $room) {
        if ($offset >= $total) break;

        $roomNo = $roomIndex + 1;
        $chunk = array_slice($students, $offset, (int)$room['succhua']);
        $offset += count($chunk);
        $roomStudents = [];

        foreach ($chunk as $index => $student) {
            $studentSchool = (int)$student['matruongdanghoc'];
            $schoolCounts[$studentSchool] = ($schoolCounts[$studentSchool] ?? 0) + 1;

            // Cấu trúc SBD: YY + mã điểm thi (3 số) + số phòng (2 số) + STT (3 số).
            $sbd = sprintf(
                '%02d%03d%02d%03d',
                (int)$year2,
                $venueId % 1000,
                $roomNo % 100,
                $index + 1
            );

            $fullName = trim((string)$student['ho'] . ' ' . (string)$student['ten']);
            $roomStudents[] = [
                'id' => (int)$student['mahocsinh'],
                'sbd' => $sbd,
                'name' => $fullName !== '' ? $fullName : '(Chưa có hồ sơ)',
                'school' => $schoolNames[$studentSchool] ?? 'Không xác định',
            ];
        }

        $allocatedRooms[] = [
            'id' => (int)$room['maphong'],
            'name' => $room['tenphong'],
            'capacity' => (int)$room['succhua'],
            'students' => $roomStudents,
        ];
    }

    $schoolSummary = [];
    foreach ($schoolNames as $id => $name) {
        $schoolSummary[] = [
            'id' => $id,
            'name' => $name,
            'count' => $schoolCounts[$id] ?? 0,
        ];
    }

    return [
        'ok' => true,
        'venueId' => $venueId,
        'venueName' => $venue['tentruong'],
        'schools' => $schoolSummary,
        'total' => $total,
        'rooms' => $allocatedRooms,
    ];
}

function computeAll(PDO $pdo, array $exam, array $groups): array
{
    if (!$groups) {
        return failResult('INVALID', 'Chưa có điểm thi nào được cấu hình.');
    }

    $seenVenues = [];
    $seenSchools = [];
    $venues = [];
    $totalStudents = 0;
    $totalRooms = 0;

    foreach ($groups as $group) {
        $venueId = (int)($group['venue'] ?? 0);
        $schoolIds = is_array($group['thcs'] ?? null) ? $group['thcs'] : [];

        if (isset($seenVenues[$venueId])) {
            return failResult('DUPLICATE_VENUE', 'Một điểm thi đang bị cấu hình trùng.');
        }
        $seenVenues[$venueId] = true;

        foreach ($schoolIds as $schoolId) {
            $schoolId = (int)$schoolId;
            if (isset($seenSchools[$schoolId])) {
                return failResult('DUPLICATE_SCHOOL', 'Một trường THCS không được xếp vào nhiều điểm thi.');
            }
            $seenSchools[$schoolId] = true;
        }

        $allocation = computeAllocation($pdo, $exam, $venueId, $schoolIds);
        if (!$allocation['ok']) {
            return $allocation;
        }

        $venues[] = $allocation;
        $totalStudents += $allocation['total'];
        $totalRooms += count($allocation['rooms']);
    }

    return [
        'ok' => true,
        'venues' => $venues,
        'total' => $totalStudents,
        'roomCount' => $totalRooms,
    ];
}

/* ===================== LƯU DỮ LIỆU AN TOÀN ===================== */
/**
 * Không DELETE bất kỳ bản ghi nào.
 * - Không sửa/xóa bài thi và điểm.
 * - Nếu học sinh đã có điểm, không cho đổi phòng/SBD của học sinh đó.
 * - Bản ghi xếp phòng có sẵn được UPDATE; chưa có thì INSERT mã maxepphong mới.
 * - truongduthi được UPDATE theo kỳ thi + trường THCS, chưa có thì INSERT.
 */
function saveAllocation(PDO $pdo, array $exam, array $groups): array
{
    $result = computeAll($pdo, $exam, $groups);
    if (!$result['ok']) return $result;

    $examId = (int)$exam['makythi'];

    try {
        $pdo->beginTransaction();

        $checkScore = $pdo->prepare("
            SELECT COUNT(*)
            FROM baithi
            WHERE mahocsinh = ? AND diem IS NOT NULL
        ");

        $findRoom = $pdo->prepare("
            SELECT maxepphong, maphong
            FROM xepphongthi
            WHERE mahocsinh = ?
            ORDER BY maxepphong
            LIMIT 1
        ");

        $findSbdConflict = $pdo->prepare("
            SELECT mahocsinh
            FROM hocsinh
            WHERE sobaodanh = ? AND mahocsinh <> ?
            LIMIT 1
        ");

        $maxId = (int)$pdo->query("
            SELECT COALESCE(MAX(maxepphong), 0)
            FROM xepphongthi
            FOR UPDATE
        ")->fetchColumn();

        $insertRoom = $pdo->prepare("
            INSERT INTO xepphongthi (maxepphong, mahocsinh, maphong)
            VALUES (?, ?, ?)
        ");
        $updateRoom = $pdo->prepare("
            UPDATE xepphongthi
            SET maphong = ?
            WHERE maxepphong = ?
        ");
        $updateSbd = $pdo->prepare("
            UPDATE hocsinh
            SET sobaodanh = ?
            WHERE mahocsinh = ? AND makythi = ?
        ");

        // Kiểm tra tất cả học sinh trước khi ghi để tránh lưu dở dang.
        foreach ($result['venues'] as $venue) {
            foreach ($venue['rooms'] as $room) {
                foreach ($room['students'] as $student) {
                    $checkScore->execute([$student['id']]);
                    $hasScore = (int)$checkScore->fetchColumn() > 0;

                    $findRoom->execute([$student['id']]);
                    $existingRoom = $findRoom->fetch();

                    if ($hasScore && $existingRoom && (int)$existingRoom['maphong'] !== (int)$room['id']) {
                        throw new RuntimeException(
                            'Học sinh ' . $student['name'] . ' đã có điểm bài thi và đang được xếp ở phòng khác. Không thể đổi phòng để bảo vệ kết quả hiện có.'
                        );
                    }
                    if ($hasScore && !$existingRoom) {
                        throw new RuntimeException(
                            'Học sinh ' . $student['name'] . ' đã có điểm bài thi nhưng chưa có bản ghi xếp phòng. Cần kiểm tra thủ công trước khi cấp SBD mới.'
                        );
                    }

                    $findSbdConflict->execute([$student['sbd'], $student['id']]);
                    if ($findSbdConflict->fetch()) {
                        throw new RuntimeException('SBD ' . $student['sbd'] . ' đã được cấp cho học sinh khác. Vui lòng kiểm tra mã điểm thi/phòng.');
                    }
                }
            }
        }

        // Lưu danh sách trường dự thi mà không xóa bản ghi cũ.
        $findSchoolMapping = $pdo->prepare("
            SELECT 1
            FROM truongduthi
            WHERE makythi = ? AND matruongthcs = ?
            LIMIT 1
        ");
        $updateSchoolMapping = $pdo->prepare("
            UPDATE truongduthi
            SET madiadiemthi = ?
            WHERE makythi = ? AND matruongthcs = ?
        ");
        $insertSchoolMapping = $pdo->prepare("
            INSERT INTO truongduthi (makythi, madiadiemthi, matruongthcs)
            VALUES (?, ?, ?)
        ");

        foreach ($result['venues'] as $venue) {
            foreach ($venue['schools'] as $school) {
                $findSchoolMapping->execute([$examId, $school['id']]);
                if ($findSchoolMapping->fetchColumn()) {
                    $updateSchoolMapping->execute([$venue['venueId'], $examId, $school['id']]);
                } else {
                    $insertSchoolMapping->execute([$examId, $venue['venueId'], $school['id']]);
                }
            }

            // Chỉ bật cờ cho điểm thi đã chọn; không tắt cờ của trường khác.
            $pdo->prepare("
                UPDATE truonghoc SET ladiemthi = 1 WHERE matruong = ?
            ")->execute([$venue['venueId']]);
        }

        // Lưu phòng và SBD. Không xóa bản ghi xếp phòng đang có.
        foreach ($result['venues'] as $venue) {
            foreach ($venue['rooms'] as $room) {
                foreach ($room['students'] as $student) {
                    $findRoom->execute([$student['id']]);
                    $existingRoom = $findRoom->fetch();

                    if ($existingRoom) {
                        $updateRoom->execute([$room['id'], $existingRoom['maxepphong']]);
                    } else {
                        $maxId++;
                        $insertRoom->execute([$maxId, $student['id'], $room['id']]);
                    }

                    $updateSbd->execute([$student['sbd'], $student['id'], $examId]);
                }
            }
        }

        $pdo->commit();

        $result['time'] = date('H:i:s d/m/Y');
        return [
            'ok' => true,
            'message' => 'Đã lưu cấu hình điểm thi, phòng thi và số báo danh thành công.',
            'data' => $result,
        ];
    } catch (Throwable $e) {
        if ($pdo->inTransaction()) {
            $pdo->rollBack();
        }
        return failResult('SAVE_ERROR', 'Không thể lưu dữ liệu.', $e->getMessage());
    }
}

/* ===================== AJAX ===================== */
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!$isSoGD) {
        jsonResponse(['ok' => false, 'message' => 'Bạn không có quyền thực hiện chức năng này.'], 403);
    }

    $raw = file_get_contents('php://input');
    $input = json_decode($raw, true);

    if (!is_array($input)) {
        jsonResponse(failResult('BAD_JSON', 'Dữ liệu gửi lên không hợp lệ.'), 400);
    }

    $action = (string)($input['action'] ?? '');
    $groups = is_array($input['groups'] ?? null) ? $input['groups'] : [];

    try {
        if ($action === 'preview') {
            $response = computeAll($pdo, $kythi, $groups);
        } elseif ($action === 'save') {
            $response = saveAllocation($pdo, $kythi, $groups);
        } else {
            $response = failResult('INVALID_ACTION', 'Thao tác không hợp lệ.');
        }
        jsonResponse($response, $response['ok'] ? 200 : 422);
    } catch (Throwable $e) {
        jsonResponse(failResult('SERVER_ERROR', 'Lỗi xử lý máy chủ.', $e->getMessage()), 500);
    }
}

/* ===================== DỮ LIỆU HIỂN THỊ ===================== */
if (!$isSoGD) {
    http_response_code(403);
    exit('Bạn không có quyền truy cập trang này.');
}

$st = $pdo->prepare("
    SELECT t.matruong, t.tentruong,
           UPPER(TRIM(t.loaitruong)) AS loai,
           COALESCE(t.maphuong, 0) AS maphuong,
           COALESCE(p.tenphuong, '(Chưa có khu vực)') AS tenphuong,
           (SELECT COUNT(*) FROM phong r
             WHERE r.matruong = t.matruong AND r.succhua > 0) AS rooms,
           (SELECT COALESCE(SUM(r.succhua), 0) FROM phong r
             WHERE r.matruong = t.matruong AND r.succhua > 0) AS capacity,
           (SELECT COUNT(*) FROM hocsinh h
             WHERE h.matruongdanghoc = t.matruong AND h.makythi = ?) AS students
    FROM truonghoc t
    LEFT JOIN phuong p ON p.maphuong = t.maphuong
    WHERE UPPER(TRIM(t.loaitruong)) IN ('THPT', 'THCS')
    ORDER BY p.tenphuong, t.tentruong
");
$st->execute([$examId]);
$schools = [];

foreach ($st->fetchAll() as $row) {
    $schools[] = [
        'id' => (int)$row['matruong'],
        'code' => $row['loai'] . str_pad((string)$row['matruong'], 2, '0', STR_PAD_LEFT),
        'name' => $row['tentruong'],
        'type' => $row['loai'],
        'wardId' => (int)$row['maphuong'],
        'ward' => $row['tenphuong'],
        'rooms' => (int)$row['rooms'],
        'capacity' => (int)$row['capacity'],
        'students' => (int)$row['students'],
    ];
}

$st = $pdo->prepare("
    SELECT madiadiemthi, matruongthcs
    FROM truongduthi
    WHERE makythi = ?
    ORDER BY madiadiemthi, matruongthcs
");
$st->execute([$examId]);
$grouped = [];
foreach ($st->fetchAll() as $row) {
    $venue = (int)$row['madiadiemthi'];
    $school = (int)$row['matruongthcs'];
    if (!isset($grouped[$venue])) $grouped[$venue] = [];
    if (!in_array($school, $grouped[$venue], true)) $grouped[$venue][] = $school;
}

$savedGroups = [];
foreach ($grouped as $venue => $ids) {
    // Chỉ nạp cấu hình còn đúng loại trường và không vượt giới hạn.
    $savedGroups[] = ['venue' => (int)$venue, 'thcs' => array_slice($ids, 0, MAX_SCHOOLS_PER_VENUE)];
}

$init = [
    'exam' => ['id' => $examId, 'year' => (int)$kythi['namtuyensinh']],
    'schools' => $schools,
    'saved' => $savedGroups,
    'maxPerVenue' => MAX_SCHOOLS_PER_VENUE,
];
$initJson = json_encode(
    $init,
    JSON_UNESCAPED_UNICODE | JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT
);

/* ===================== GIAO DIỆN HEADER/SIDEBAR ===================== */
$headerFile = __DIR__ . '/../../../components/layout/header.php';
$sidebarFile = __DIR__ . '/../../../components/layout/sidebar.php';
if (is_file($headerFile)) require_once $headerFile;
if (is_file($sidebarFile)) require_once $sidebarFile;
?>
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Stackers - Quản lý địa điểm thi</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script>
        function escapeHtml(value) {
            return String(value ?? '')
                .replace(/&/g, '&amp;')
                .replace(/</g, '&lt;')
                .replace(/>/g, '&gt;')
                .replace(/"/g, '&quot;')
                .replace(/'/g, '&#039;');
        }
    </script>
</head>
<body class="bg-gray-50 text-gray-800">
<?php
if (function_exists('renderHeader')) renderHeader();
if (function_exists('renderSidebar')) renderSidebar();
?>

<!-- MODAL THÔNG BÁO -->
<div id="custom-modal" class="fixed inset-0 z-[100] hidden items-center justify-center bg-black/50">
    <div class="bg-white rounded-2xl shadow-2xl p-6 w-full max-w-md mx-4">
        <div id="modal-title" class="text-lg font-bold text-gray-900 mb-2">Thông báo</div>
        <div id="modal-message" class="text-sm text-gray-600 mb-6 whitespace-pre-line"></div>
        <div class="flex justify-end gap-3">
            <button id="modal-cancel-btn" type="button" onclick="closeModal(false)"
                class="hidden border bg-white text-gray-700 rounded-xl px-4 py-2 hover:bg-gray-100 text-sm font-medium">Hủy</button>
            <button id="modal-ok-btn" type="button" onclick="closeModal(true)"
                class="bg-blue-600 text-white rounded-xl px-5 py-2 hover:bg-blue-700 text-sm font-medium">Xác nhận</button>
        </div>
    </div>
</div>

<main class="ml-72 pt-24 max-w-7xl mx-auto p-8">
    <div class="flex flex-wrap justify-between items-center gap-4 mb-6">
        <div>
            <h1 class="font-bold text-2xl mb-1 text-gray-900">Quản lý địa điểm thi</h1>
            <p class="text-gray-500 text-sm">
                Kỳ thi tuyển sinh năm <b><?= (int)$kythi['namtuyensinh'] ?></b>
                — Chọn điểm thi và trường THCS dự thi
            </p>
        </div>
        <div class="flex items-center gap-3">
            <span id="dirty-badge" class="hidden text-xs font-medium text-amber-800 bg-amber-50 border border-amber-200 rounded-full px-3 py-1">Có thay đổi chưa lưu</span>
            <button type="button" onclick="cancelChanges()" class="border bg-white text-gray-700 rounded-xl px-4 py-2.5 hover:bg-gray-50 text-sm font-medium shadow-sm">Hủy</button>
            <button type="button" id="save-btn" onclick="saveAll()" class="bg-gray-900 text-white rounded-xl px-5 py-2.5 hover:bg-gray-800 text-sm font-medium shadow-sm disabled:opacity-50">Lưu danh sách</button>
        </div>
    </div>

    <div id="result-panel" class="hidden border border-emerald-200 bg-emerald-50 rounded-2xl p-6 mb-6">
        <div class="font-bold text-emerald-800 mb-2">Cập nhật thành công</div>
        <div id="result-body" class="text-sm text-gray-700"></div>
    </div>

    <!-- THANH CÔNG CỤ -->
    <div class="border border-gray-200 bg-white rounded-2xl shadow-sm mb-6 p-4 flex flex-wrap items-center gap-3">
        <button type="button" onclick="autoAssign()" class="bg-blue-600 text-white rounded-xl px-4 py-2.5 hover:bg-blue-700 text-sm font-medium shadow-sm">
            ⚡ Tự động chia điểm thi
        </button>
        <div class="ml-auto flex flex-wrap items-center gap-3">
            <select id="ward-filter" onchange="renderAreas()" class="border border-gray-200 rounded-xl px-3 py-2 text-sm bg-white">
                <option value="">Tất cả khu vực</option>
            </select>
            <input id="search-box" type="text" autocomplete="off" placeholder="Tìm tên trường / khu vực..."
                oninput="renderAreas()" class="border border-gray-200 rounded-xl px-3 py-2 text-sm bg-white w-64">
        </div>
    </div>

    <!-- KHUNG 1 -->
    <section class="border border-gray-200 bg-white rounded-2xl shadow-sm mb-6 overflow-hidden">
        <div class="p-4 border-b border-gray-200 bg-gray-50 font-bold text-sm text-gray-700 flex flex-wrap items-center justify-between gap-2">
            <span>🏫 Khung 1: Thêm &amp; Chọn trường làm điểm thi thủ công</span>
            <span class="text-xs font-normal text-gray-500">Chỉ chọn trường THPT có phòng thi</span>
        </div>
        <div class="p-4 flex flex-wrap items-center gap-3">
            <select id="manual-venue-select" class="flex-1 min-w-[280px] border border-gray-300 rounded-xl px-3 py-2.5 text-sm bg-white"></select>
            <button type="button" onclick="confirmManualAdd()" class="bg-emerald-600 text-white rounded-xl px-5 py-2.5 hover:bg-emerald-700 text-sm font-medium">
                ➕ Thêm điểm thi này
            </button>
        </div>
    </section>

    <!-- KHUNG 2 -->
    <section class="border border-gray-200 bg-white rounded-2xl shadow-sm mb-6 overflow-hidden">
        <div class="p-4 border-b border-gray-200 bg-gray-50 font-bold text-sm text-gray-700 flex flex-wrap justify-between items-center gap-2">
            <span>👥 Khung 2: Phân bổ trường dự thi cho điểm thi</span>
            <span id="summary-line" class="font-normal text-xs text-gray-500"></span>
        </div>
        <div id="area-list" class="max-h-[40rem] overflow-y-auto divide-y divide-gray-200"></div>
    </section>

    <div class="flex flex-wrap items-center gap-3 mb-6">
        <button type="button" id="gen-btn" onclick="generate()" class="bg-gray-900 text-white rounded-xl px-6 py-3 hover:bg-gray-800 text-sm font-medium disabled:opacity-50">
            🔍 Sinh phòng thi và phân bổ thí sinh (Xem trước)
        </button>
    </div>

    <section id="preview-card" class="hidden border border-gray-200 bg-white rounded-2xl shadow-sm mb-8 overflow-hidden">
        <div class="p-4 border-b border-gray-200 bg-gray-50 font-bold text-sm text-gray-700">Xem trước phòng thi và danh sách thí sinh</div>
        <div class="p-4">
            <div id="preview-summary" class="text-sm text-gray-700 mb-4 font-medium"></div>
            <div id="preview-rooms"></div>
        </div>
    </section>
</main>

<script>
const INIT = <?= $initJson ?: '{}' ?>;
const MAX_PER_VENUE = Number(INIT.maxPerVenue || 2);
const byId = new Map((INIT.schools || []).map(s => [Number(s.id), s]));

const state = {
    groups: new Map(),
    preview: null,
    previewSaved: false
};

(INIT.saved || []).forEach(g => {
    const venue = Number(g.venue);
    const validThcs = (g.thcs || [])
        .map(Number)
        .filter(id => byId.has(id) && byId.get(id).type === 'THCS')
        .slice(0, MAX_PER_VENUE);
    const venueInfo = byId.get(venue);
    if (venueInfo && venueInfo.type === 'THPT' && venueInfo.rooms > 0) {
        state.groups.set(venue, validThcs);
    }
});

function snapshot() {
    return JSON.stringify(
        [...state.groups]
            .map(([v, ids]) => [Number(v), [...ids].sort((a,b) => a-b)])
            .sort((a,b) => a[0]-b[0])
    );
}
let savedSnap = snapshot();

function isDirty() {
    return snapshot() !== savedSnap || (state.preview !== null && !state.previewSaved);
}

let modalCallback = null;
function showModal(title, message, showCancel = false, callback = null) {
    document.getElementById('modal-title').textContent = title;
    document.getElementById('modal-message').textContent = message;
    document.getElementById('modal-cancel-btn').classList.toggle('hidden', !showCancel);
    const modal = document.getElementById('custom-modal');
    modal.classList.remove('hidden');
    modal.classList.add('flex');
    modalCallback = callback;
}
function closeModal(result) {
    const modal = document.getElementById('custom-modal');
    modal.classList.add('hidden');
    modal.classList.remove('flex');
    const callback = modalCallback;
    modalCallback = null;
    if (callback) callback(result);
}

function normalizeText(value) {
    return String(value ?? '').normalize('NFD')
        .replace(/[\u0300-\u036f]/g, '')
        .replace(/đ/g, 'd').replace(/Đ/g, 'D').toLowerCase();
}
function assignedMap() {
    const map = new Map();
    state.groups.forEach((ids, venue) => ids.forEach(id => map.set(Number(id), Number(venue))));
    return map;
}
function venueCandidates() {
    return INIT.schools.filter(s => s.type === 'THPT' && Number(s.rooms) > 0);
}
function buildGroups() {
    return [...state.groups].map(([venue, thcs]) => ({
        venue: Number(venue),
        thcs: thcs.map(Number)
    }));
}
function invalidatePreview() {
    state.preview = null;
    state.previewSaved = false;
    renderAll();
}

async function callApi(payload) {
    try {
        const response = await fetch(window.location.href, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'Accept': 'application/json',
                'X-Requested-With': 'XMLHttpRequest'
            },
            body: JSON.stringify(payload)
        });
        const raw = await response.text();
        let data;
        try {
            data = JSON.parse(raw);
        } catch (error) {
            console.error('Phản hồi PHP không phải JSON:', raw);
            return {ok:false, message:'Máy chủ trả về dữ liệu không hợp lệ. Kiểm tra lỗi PHP trong log máy chủ.'};
        }
        if (!response.ok && !data.message) {
            data.message = 'Máy chủ trả về HTTP ' + response.status;
        }
        return data;
    } catch (error) {
        console.error(error);
        return {ok:false, message:'Không kết nối được máy chủ hoặc mất kết nối mạng.'};
    }
}
function errorMessage(result) {
    return (result.message || 'Có lỗi xảy ra.') + (result.detail ? '\n\n' + result.detail : '');
}

function addSchool(venueId, schoolId) {
    venueId = Number(venueId);
    schoolId = Number(schoolId);
    if (!schoolId) return;

    const school = byId.get(schoolId);
    if (!school || school.type !== 'THCS') {
        showModal('Thông báo', 'Chỉ được chọn trường THCS làm trường dự thi.');
        return;
    }

    const list = state.groups.get(venueId) || [];
    if (list.length >= MAX_PER_VENUE) {
        showModal('Thông báo', 'Mỗi điểm thi chỉ được chọn tối đa ' + MAX_PER_VENUE + ' trường THCS.');
        return;
    }
    if (assignedMap().has(schoolId)) {
        showModal('Thông báo', 'Trường này đã được phân bổ ở một điểm thi khác.');
        return;
    }

    list.push(schoolId);
    state.groups.set(venueId, list);
    invalidatePreview();
}
function removeSchool(venueId, schoolId) {
    const list = (state.groups.get(Number(venueId)) || []).filter(id => id !== Number(schoolId));
    if (list.length) state.groups.set(Number(venueId), list);
    else state.groups.delete(Number(venueId));
    invalidatePreview();
}
function clearVenue(venueId) {
    state.groups.delete(Number(venueId));
    invalidatePreview();
}

function renderManualSelect() {
    const select = document.getElementById('manual-venue-select');
    const selected = new Set(state.groups.keys());
    const available = venueCandidates().filter(v => !selected.has(Number(v.id)));
    select.innerHTML = available.length
        ? available.map(v => `<option value="${v.id}">${escapeHtml(v.name)} (${v.type} - ${escapeHtml(v.ward)} - ${v.rooms} phòng)</option>`).join('')
        : '<option value="">Tất cả trường THPT có phòng đã được chọn</option>';
}
function confirmManualAdd() {
    const venueId = Number(document.getElementById('manual-venue-select').value);
    const venue = byId.get(venueId);
    if (!venue || venue.type !== 'THPT' || venue.rooms <= 0) {
        showModal('Thông báo', 'Vui lòng chọn trường THPT có phòng thi hợp lệ.');
        return;
    }
    if (!state.groups.has(venueId)) {
        state.groups.set(venueId, []);
        invalidatePreview();
    }
}

function buildWardFilter() {
    const map = new Map();
    INIT.schools.forEach(s => map.set(Number(s.wardId), s.ward));
    const select = document.getElementById('ward-filter');
    select.innerHTML = '<option value="">Tất cả khu vực</option>' +
        [...map].sort((a,b) => String(a[1]).localeCompare(String(b[1]), 'vi'))
            .map(([id,name]) => `<option value="${id}">${escapeHtml(name)}</option>`).join('');
}

function venueCard(venue, assigned) {
    const ids = state.groups.get(Number(venue.id)) || [];
    const load = ids.reduce((sum, id) => sum + Number(byId.get(id)?.students || 0), 0);
    const freeSchools = INIT.schools
        .filter(s => s.type === 'THCS' && !assigned.has(Number(s.id)))
        .sort((a,b) => a.name.localeCompare(b.name, 'vi'));
    const sameWard = freeSchools.filter(s => Number(s.wardId) === Number(venue.wardId));
    const otherWard = freeSchools.filter(s => Number(s.wardId) !== Number(venue.wardId));
    const option = s => `<option value="${s.id}">${escapeHtml(s.name)} — ${escapeHtml(s.ward)} (${s.students} HS)</option>`;

    return `
    <article class="border border-gray-200 rounded-2xl bg-white shadow-sm overflow-hidden">
        <div class="px-5 py-3.5 bg-gray-50 border-b border-gray-100 flex flex-wrap items-center justify-between gap-3">
            <div class="flex flex-wrap items-center gap-3">
                <span class="font-bold text-gray-900 text-sm">${escapeHtml(venue.code)}</span>
                <span class="font-semibold text-gray-900 text-sm">${escapeHtml(venue.name)}</span>
                <span class="text-xs rounded-full px-2.5 py-0.5 font-medium bg-blue-100 text-blue-800">THPT</span>
            </div>
            <div class="flex items-center gap-3">
                <span class="text-xs text-gray-500">${venue.rooms} phòng · Sức chứa: <b>${venue.capacity}</b></span>
                <button type="button" class="text-xs text-red-600 hover:underline font-medium" onclick="clearVenue(${venue.id})">Xóa điểm thi</button>
            </div>
        </div>
        <div class="p-4 grid grid-cols-1 md:grid-cols-2 gap-4">
            <div class="border border-blue-100 bg-blue-50/30 rounded-xl p-4 flex flex-col justify-between">
                <div>
                    <div class="text-xs font-bold text-blue-900 uppercase tracking-wider mb-2">🏫 Điểm thi đã chọn</div>
                    <div class="font-medium text-gray-900 text-sm mb-1">${escapeHtml(venue.name)}</div>
                    <div class="text-xs text-gray-500">Khu vực: ${escapeHtml(venue.ward)}</div>
                </div>
                <div class="mt-4 pt-3 border-t border-blue-100 flex justify-between items-center text-xs text-blue-900 font-medium">
                    <span>Trạng thái: Hoạt động</span>
                    <span class="bg-blue-100 px-2 py-0.5 rounded">${venue.rooms} phòng thi</span>
                </div>
            </div>
            <div class="border border-purple-100 bg-purple-50/30 rounded-xl p-4 flex flex-col justify-between">
                <div>
                    <div class="text-xs font-bold text-purple-900 uppercase tracking-wider mb-2 flex items-center justify-between">
                        <span>👥 Các trường dự thi (Tối đa ${MAX_PER_VENUE})</span>
                        <span class="font-semibold text-purple-700">${ids.length}/${MAX_PER_VENUE} trường</span>
                    </div>
                    <div class="space-y-2 mb-3">
                        ${ids.length === 0 ? '<div class="text-xs italic text-gray-400 py-1">Chưa chọn trường dự thi nào.</div>' :
                            ids.map(id => {
                                const school = byId.get(id);
                                return `<div class="flex items-center justify-between bg-white border border-purple-200/60 px-3 py-2 rounded-lg text-xs">
                                    <div>
                                        <span class="font-medium text-gray-800">${escapeHtml(school?.name || 'Trường không tồn tại')}</span>
                                        <span class="text-gray-400 block">${escapeHtml(school?.ward || '')} · <b>${school?.students || 0}</b> HS</span>
                                    </div>
                                    <button type="button" class="text-red-600 hover:text-red-800 font-medium px-2 py-1 rounded hover:bg-red-50" onclick="removeSchool(${venue.id}, ${id})">Gỡ</button>
                                </div>`;
                            }).join('')
                        }
                    </div>
                </div>
                ${ids.length < MAX_PER_VENUE ? `
                    <div class="pt-2 border-t border-purple-100">
                        <select class="w-full border border-purple-200 rounded-lg px-3 py-2 bg-white text-xs"
                            onchange="addSchool(${venue.id}, this.value); this.value='';">
                            <option value="">+ Thêm trường THCS dự thi...</option>
                            ${sameWard.length ? `<optgroup label="Cùng khu vực">${sameWard.map(option).join('')}</optgroup>` : ''}
                            ${otherWard.length ? `<optgroup label="Khu vực khác">${otherWard.map(option).join('')}</optgroup>` : ''}
                        </select>
                    </div>` : '<div class="text-xs text-amber-700 font-medium pt-2 border-t border-purple-100">Đã đủ giới hạn 2 trường dự thi.</div>'}
            </div>
        </div>
        ${load > venue.capacity ? `<div class="px-5 py-2 bg-red-50 border-t border-red-100 text-red-600 text-xs font-bold">⚠️ Số học sinh đăng ký (${load}) vượt sức chứa (${venue.capacity}).</div>` : ''}
    </article>`;
}

function renderAreas() {
    renderManualSelect();

    const box = document.getElementById('area-list');
    const keyword = normalizeText(document.getElementById('search-box').value.trim());
    const wardFilter = document.getElementById('ward-filter').value;
    const configuredIds = new Set([...state.groups.keys()].map(Number));

    const list = venueCandidates().filter(s =>
        configuredIds.has(Number(s.id)) &&
        (!wardFilter || String(s.wardId) === wardFilter) &&
        (!keyword || normalizeText(s.name).includes(keyword) ||
            normalizeText(s.ward).includes(keyword) || normalizeText(s.code).includes(keyword))
    );

    if (!list.length) {
        box.innerHTML = '<div class="p-8 text-center text-gray-400 italic">Chưa có điểm thi nào trong Khung 2. Vui lòng chọn trường ở Khung 1.</div>';
        return;
    }

    const wards = new Map();
    list.forEach(s => {
        if (!wards.has(s.wardId)) wards.set(s.wardId, {name:s.ward, items:[]});
        wards.get(s.wardId).items.push(s);
    });

    const assigned = assignedMap();
    box.innerHTML = [...wards.values()]
        .sort((a,b) => a.name.localeCompare(b.name, 'vi'))
        .map(ward => `
            <div>
                <div class="bg-gray-100 border-b border-gray-200 px-4 py-2.5 text-xs font-bold uppercase tracking-wider text-gray-600 flex justify-between">
                    <span>Khu vực: ${escapeHtml(ward.name)}</span>
                    <span class="font-normal text-gray-500">${ward.items.length} điểm thi đang hoạt động</span>
                </div>
                <div class="p-4 space-y-4">${ward.items.map(v => venueCard(v, assigned)).join('')}</div>
            </div>
        `).join('');
}

function renderSummary() {
    const assigned = assignedMap();
    const unassigned = INIT.schools.filter(s => s.type === 'THCS' && !assigned.has(Number(s.id)));
    document.getElementById('summary-line').textContent =
        `Đang cấu hình ${state.groups.size} điểm thi · Còn ${unassigned.length} trường THCS chưa phân bổ`;
}

function renderPreview() {
    const card = document.getElementById('preview-card');
    if (!state.preview) {
        card.classList.add('hidden');
        return;
    }
    card.classList.remove('hidden');
    const p = state.preview;
    document.getElementById('preview-summary').innerHTML =
        `Tổng hợp: <b>${p.venues.length}</b> điểm thi — <b>${p.total}</b> thí sinh — <b>${p.roomCount}</b> phòng thi.`;

    document.getElementById('preview-rooms').innerHTML = p.venues.map((venue, venueIndex) => `
        <details class="border border-gray-200 rounded-xl mb-3 overflow-hidden" ${venueIndex === 0 ? 'open' : ''}>
            <summary class="cursor-pointer px-4 py-3 bg-gray-50 font-semibold text-sm flex flex-wrap justify-between gap-2">
                <span>${escapeHtml(venue.venueName)} · ${venue.total} thí sinh · ${venue.rooms.length} phòng</span>
                <span class="text-xs font-normal text-gray-500">${venue.schools.map(s => escapeHtml(s.name) + ' (' + s.count + ')').join(', ')}</span>
            </summary>
            <div class="p-3 bg-white space-y-2">
                ${venue.rooms.map(room => `
                    <details class="border border-gray-100 rounded-lg overflow-hidden bg-gray-50/50">
                        <summary class="cursor-pointer px-4 py-2 font-medium text-xs text-gray-700 flex justify-between">
                            <span>Phòng: <b>${escapeHtml(room.name)}</b></span>
                            <span class="text-gray-500">${room.students.length}/${room.capacity} thí sinh</span>
                        </summary>
                        <div class="overflow-x-auto bg-white border-t border-gray-100">
                            <table class="w-full text-left text-xs">
                                <thead><tr class="bg-gray-50 border-b border-gray-100 text-gray-500">
                                    <th class="p-2 pl-4">STT</th><th class="p-2">SBD</th><th class="p-2">Họ và tên</th><th class="p-2">Trường dự thi</th>
                                </tr></thead>
                                <tbody>${room.students.map((student, i) => `
                                    <tr class="border-b border-gray-50 hover:bg-gray-50">
                                        <td class="p-2 pl-4 text-gray-400">${i+1}</td>
                                        <td class="p-2 font-semibold text-gray-900">${escapeHtml(student.sbd)}</td>
                                        <td class="p-2 font-medium text-gray-800">${escapeHtml(student.name)}</td>
                                        <td class="p-2 text-gray-500">${escapeHtml(student.school)}</td>
                                    </tr>`).join('')}
                                </tbody>
                            </table>
                        </div>
                    </details>
                `).join('')}
            </div>
        </details>
    `).join('');
}

function renderControls() {
    document.getElementById('dirty-badge').classList.toggle('hidden', !isDirty());
}
function renderAll() {
    renderAreas();
    renderSummary();
    renderPreview();
    renderControls();
}

async function generate() {
    const groups = buildGroups();
    if (!groups.length) {
        showModal('Thông báo', 'Chưa có điểm thi nào được cấu hình.');
        return;
    }
    const button = document.getElementById('gen-btn');
    button.disabled = true;
    button.textContent = 'Đang tính toán...';

    const response = await callApi({action:'preview', groups});
    button.disabled = false;
    button.textContent = '🔍 Sinh phòng thi và phân bổ thí sinh (Xem trước)';

    if (!response.ok) {
        showModal('Không thể phân bổ', errorMessage(response));
        return;
    }

    state.preview = response;
    state.previewSaved = false;
    renderAll();
    document.getElementById('preview-card').scrollIntoView({behavior:'smooth', block:'start'});
}

async function saveAll() {
    if (!state.preview) {
        showModal('Thông báo', 'Vui lòng xem trước phân bổ trước khi lưu.');
        return;
    }

    const preview = state.preview;
    showModal(
        'Xác nhận lưu',
        `Bạn muốn lưu cấu hình này?\n\n- Điểm thi: ${preview.venues.length}\n- Thí sinh: ${preview.total}\n- Phòng thi: ${preview.roomCount}\n\nHệ thống không xóa dữ liệu học sinh hoặc điểm bài thi.`,
        true,
        async confirmed => {
            if (!confirmed) return;

            const button = document.getElementById('save-btn');
            button.disabled = true;
            button.textContent = 'Đang lưu...';

            const response = await callApi({action:'save', groups:buildGroups()});

            button.disabled = false;
            button.textContent = 'Lưu danh sách';

            if (!response.ok) {
                showModal('Lỗi lưu dữ liệu', errorMessage(response));
                return;
            }

            state.preview = response.data;
            state.previewSaved = true;
            savedSnap = snapshot();
            renderAll();
            showResult(response.data);
            showModal('Thành công', response.message);
            window.scrollTo({top:0, behavior:'smooth'});
        }
    );
}

function showResult(data) {
    document.getElementById('result-panel').classList.remove('hidden');
    document.getElementById('result-body').innerHTML = `
        <div class="grid grid-cols-2 md:grid-cols-4 gap-4 text-sm mb-4">
            <div class="bg-white p-3 rounded-xl border border-emerald-100">
                <span class="text-gray-500 block text-xs">Số điểm thi</span>
                <b class="text-lg text-emerald-900">${data.venues.length}</b>
            </div>
            <div class="bg-white p-3 rounded-xl border border-emerald-100">
                <span class="text-gray-500 block text-xs">Tổng thí sinh</span>
                <b class="text-lg text-emerald-900">${data.total}</b>
            </div>
            <div class="bg-white p-3 rounded-xl border border-emerald-100">
                <span class="text-gray-500 block text-xs">Tổng phòng thi</span>
                <b class="text-lg text-emerald-900">${data.roomCount}</b>
            </div>
            <div class="bg-white p-3 rounded-xl border border-emerald-100">
                <span class="text-gray-500 block text-xs">Thời gian cập nhật</span>
                <b class="text-sm text-emerald-900">${escapeHtml(data.time || '')}</b>
            </div>
        </div>`;
}

function autoAssign() {
    showModal(
        'Xác nhận tự động',
        'Hệ thống sẽ tự động ghép tối đa 2 trường THCS vào mỗi điểm thi THPT theo khu vực và sức chứa phòng. Tiếp tục?',
        true,
        confirmed => {
            if (!confirmed) return;

            const thcs = INIT.schools
                .filter(s => s.type === 'THCS')
                .sort((a,b) =>
                    a.ward.localeCompare(b.ward, 'vi') ||
                    a.name.localeCompare(b.name, 'vi')
                );
            const venues = venueCandidates();

            if (!thcs.length || !venues.length) {
                showModal('Không thể tự động chia', 'Cần có trường THCS và trường THPT có phòng thi.');
                return;
            }

            const usedVenues = new Set();
            const groups = new Map();
            const unplaced = [];

            function place(schools) {
                const studentCount = schools.reduce((sum, s) => sum + Number(s.students || 0), 0);
                const wardId = Number(schools[0].wardId);
                const candidates = venues
                    .filter(v => !usedVenues.has(Number(v.id)) && Number(v.capacity) >= studentCount)
                    .sort((a,b) =>
                        (Number(a.wardId) === wardId ? 0 : 1) - (Number(b.wardId) === wardId ? 0 : 1) ||
                        Number(a.capacity) - Number(b.capacity)
                    );

                if (!candidates.length) return false;
                const venue = candidates[0];
                usedVenues.add(Number(venue.id));
                groups.set(Number(venue.id), schools.map(s => Number(s.id)));
                return true;
            }

            for (let i = 0; i < thcs.length; i += MAX_PER_VENUE) {
                const pair = thcs.slice(i, i + MAX_PER_VENUE);
                if (!place(pair)) {
                    pair.forEach(s => unplaced.push(s.name));
                }
            }

            state.groups = groups;
            invalidatePreview();

            const message = unplaced.length
                ? `Đã phân bổ ${thcs.length - unplaced.length}/${thcs.length} trường.\n\nChưa bố trí được:\n${unplaced.join('\n')}\n\nHãy thêm điểm thi hoặc kiểm tra sức chứa.`
                : `Đã ghép ${thcs.length} trường THCS vào ${groups.size} điểm thi.`;
            showModal('Kết quả tự động', message);
        }
    );
}

function cancelChanges() {
    if (isDirty()) {
        showModal('Xác nhận hủy', 'Bạn có chắc muốn bỏ các thay đổi chưa lưu?', true, confirmed => {
            if (confirmed) window.location.reload();
        });
    } else {
        window.location.reload();
    }
}

window.addEventListener('beforeunload', event => {
    if (isDirty()) {
        event.preventDefault();
        event.returnValue = '';
    }
});

window.addEventListener('DOMContentLoaded', () => {
    buildWardFilter();
    renderAll();
});
</script>
</body>
</html>
