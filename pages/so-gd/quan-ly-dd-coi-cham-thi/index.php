<?php
// Quản lý địa điểm thi / chấm thi (Sở GD&ĐT) - 1 FILE DUY NHẤT

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

/* ===================== CẤU HÌNH ===================== */
const DB_HOST = 'localhost';
const DB_NAME = 'leonovar6_ptud';
const DB_USER = 'root';
const DB_PASS = '';

const REQUIRE_SO_GD = false;        // true = bắt buộc tài khoản SO_GD (bật lại khi nhóm xong đăng nhập)
const DEFAULT_ROOM_CAPACITY = 24;   // sức chứa mặc định mỗi phòng thi
const HOME_URL = '';                // trang chính khi bấm "Hủy" (để trống = tải lại trang)

/* ===================== KẾT NỐI DB ===================== */
try {
    $pdo = new PDO(
        "mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=utf8mb4",
        DB_USER,
        DB_PASS,
        [
            PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        ]
    );
} catch (PDOException $e) {
    http_response_code(500);
    exit('Không kết nối được database. Kiểm tra lại DB_NAME / DB_USER / DB_PASS ở đầu file.');
}

/* ===================== PHÂN QUYỀN ===================== */
function sessionHasRole($data, string $role): bool
{
    if (is_array($data)) {
        foreach ($data as $v) {
            if (sessionHasRole($v, $role)) return true;
        }
        return false;
    }
    return is_string($data) && $data === $role;
}

function currentUserId(): ?int
{
    foreach (['manguoidung', 'user_id', 'id'] as $k) {
        if (isset($_SESSION[$k]) && is_numeric($_SESSION[$k])) return (int)$_SESSION[$k];
    }
    if (isset($_SESSION['user']['manguoidung']) && is_numeric($_SESSION['user']['manguoidung'])) {
        return (int)$_SESSION['user']['manguoidung'];
    }
    return null;
}

$isSoGD = !REQUIRE_SO_GD || sessionHasRole($_SESSION ?? [], 'SO_GD');

/* ===================== TỰ TẠO CỘT / BẢNG CÒN THIẾU ===================== */
function ensureSchema(PDO $pdo): void
{
    $col = $pdo->query("SHOW COLUMNS FROM phong LIKE 'succhua'")->fetch();
    if (!$col) {
        $pdo->exec("ALTER TABLE phong ADD COLUMN succhua INT NOT NULL DEFAULT " . (int)DEFAULT_ROOM_CAPACITY);
    }

    $pdo->exec(
        "CREATE TABLE IF NOT EXISTS nhatkyhethong (
            manhatky bigint(20) NOT NULL AUTO_INCREMENT PRIMARY KEY,
            manguoidung bigint(20) NULL,
            hanhdong varchar(50) NOT NULL,
            noidung varchar(500) NOT NULL,
            thoigian datetime NOT NULL DEFAULT current_timestamp()
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci"
    );

    $base = "CREATE TABLE IF NOT EXISTS truongduthi (
            makythi bigint(20) NOT NULL,
            madiadiemthi bigint(20) NOT NULL,
            matruongthcs bigint(20) NOT NULL,
            PRIMARY KEY (makythi, matruongthcs)";
    $tail = ") ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci";
    try {
        $pdo->exec($base . ",
            CONSTRAINT fk_tdt_kythi FOREIGN KEY (makythi) REFERENCES kythituyensinh(makythi),
            CONSTRAINT fk_tdt_diemthi FOREIGN KEY (madiadiemthi) REFERENCES truonghoc(matruong),
            CONSTRAINT fk_tdt_thcs FOREIGN KEY (matruongthcs) REFERENCES truonghoc(matruong)" . $tail);
    } catch (PDOException $e) {
        $pdo->exec($base . $tail); // không tạo được khóa ngoại thì tạo bảng không có
    }
}

try {
    ensureSchema($pdo);
} catch (PDOException $e) {
    http_response_code(500);
    exit('Không tự tạo được bảng/cột cần thiết (kiểm tra quyền của user database): ' . htmlspecialchars($e->getMessage()));
}

/* ===================== HÀM TIỆN ÍCH ===================== */
function fail(string $code, string $message, string $detail = ''): array
{
    return ['ok' => false, 'code' => $code, 'message' => $message, 'detail' => $detail];
}

// Khóa sắp xếp theo bảng chữ cái + dấu thanh tiếng Việt (a ă â b c d đ e ê ...)
function viSortKey(string $s): string
{
    static $order = null, $tones = null;
    if ($order === null) {
        $bases = ['a','ă','â','b','c','d','đ','e','ê','f','g','h','i','j','k','l','m','n','o','ô','ơ','p','q','r','s','t','u','ư','v','w','x','y','z'];
        $order = [];
        foreach ($bases as $i => $b) $order[$b] = chr(65 + $i);

        // thứ tự thanh: không dấu < huyền < sắc < hỏi < ngã < nặng
        $groups = [
            'a' => ['à','á','ả','ã','ạ'], 'ă' => ['ằ','ắ','ẳ','ẵ','ặ'], 'â' => ['ầ','ấ','ẩ','ẫ','ậ'],
            'e' => ['è','é','ẻ','ẽ','ẹ'], 'ê' => ['ề','ế','ể','ễ','ệ'], 'i' => ['ì','í','ỉ','ĩ','ị'],
            'o' => ['ò','ó','ỏ','õ','ọ'], 'ô' => ['ồ','ố','ổ','ỗ','ộ'], 'ơ' => ['ờ','ớ','ở','ỡ','ợ'],
            'u' => ['ù','ú','ủ','ũ','ụ'], 'ư' => ['ừ','ứ','ử','ữ','ự'], 'y' => ['ỳ','ý','ỷ','ỹ','ỵ'],
        ];
        $tones = [];
        foreach ($groups as $base => $list) {
            foreach ($list as $t => $ch) $tones[$ch] = [$base, $t + 1];
        }
    }

    $s = mb_strtolower(trim($s), 'UTF-8');
    if (class_exists('Normalizer')) {
        $s = Normalizer::normalize($s, Normalizer::FORM_C) ?: $s;
    }
    $primary = '';
    $toneKey = '';
    foreach (mb_str_split($s, 1, 'UTF-8') as $ch) {
        if (isset($tones[$ch])) {
            $primary .= $order[$tones[$ch][0]];
            $toneKey .= $tones[$ch][1];
        } elseif (isset($order[$ch])) {
            $primary .= $order[$ch];
            $toneKey .= '0';
        } elseif ($ch === ' ') {
            $primary .= ' ';
            $toneKey .= '0';
        }
    }
    return $primary . "\x01" . $toneKey;
}

function writeLog(PDO $pdo, ?int $uid, string $action, string $content): void
{
    $stmt = $pdo->prepare("INSERT INTO nhatkyhethong (manguoidung, hanhdong, noidung) VALUES (?, ?, ?)");
    $stmt->execute([$uid, $action, mb_substr($content, 0, 500)]);
}

function fetchLogs(PDO $pdo, int $limit = 10): array
{
    $rows = $pdo->query(
        "SELECT hanhdong, noidung, thoigian FROM nhatkyhethong ORDER BY manhatky DESC LIMIT " . (int)$limit
    )->fetchAll();
    return array_map(function ($r) {
        return [
            'time'    => date('H:i:s d/m/Y', strtotime($r['thoigian'])),
            'action'  => $r['hanhdong'],
            'content' => $r['noidung'],
        ];
    }, $rows);
}

/* ===================== NGHIỆP VỤ: SINH PHÒNG & PHÂN BỔ ===================== */
function computeAllocation(PDO $pdo, array $kythi, $venueId, $thcsIds): array
{
    $venueId = filter_var($venueId, FILTER_VALIDATE_INT);
    $thcsIds = array_values(array_unique(array_map('intval', is_array($thcsIds) ? $thcsIds : [])));

    // Kiểm tra điều kiện đầu vào: đúng 1 điểm thi và 2 trường dự thi
    if (!$venueId || count($thcsIds) !== 2) {
        return fail('INVALID', 'Vui lòng chọn đúng 1 điểm thi (THPT) và 2 trường THCS dự thi.');
    }

    $st = $pdo->prepare("SELECT matruong, tentruong FROM truonghoc WHERE matruong = ? AND UPPER(TRIM(loaitruong)) = 'THPT'");
    $st->execute([$venueId]);
    $venue = $st->fetch();
    if (!$venue) return fail('INVALID', 'Điểm thi không hợp lệ (phải là trường THPT).');

    $st = $pdo->prepare(
        "SELECT matruong, tentruong FROM truonghoc
         WHERE UPPER(TRIM(loaitruong)) = 'THCS' AND matruong IN (?, ?)"
    );
    $st->execute($thcsIds);
    $schools = $st->fetchAll();
    if (count($schools) !== 2) return fail('INVALID', 'Trường dự thi không hợp lệ (phải là 2 trường THCS khác nhau).');

    // Lấy toàn bộ học sinh của 2 trường dự thi
    $kid = (int)$kythi['makythi'];
    $st = $pdo->prepare(
        "SELECT h.mahocsinh, h.matruongdanghoc, COALESCE(u.ho, '') AS ho, COALESCE(u.ten, '') AS ten
         FROM hocsinh h
         LEFT JOIN hosonguoidung u ON u.manguoidung = h.manguoidung
         WHERE h.makythi = ? AND h.matruongdanghoc IN (?, ?)"
    );
    $st->execute(array_merge([$kid], $thcsIds));
    $students = $st->fetchAll();

    // 4.1: không có học sinh
    if (count($students) === 0) {
        return fail('EMPTY_STUDENTS', 'Danh sách học sinh của trường dự thi trống. Vui lòng kiểm tra lại dữ liệu thí sinh!');
    }

    // Sắp xếp A-Z theo tên, rồi họ
    foreach ($students as &$s) {
        $s['k_ten'] = viSortKey($s['ten']);
        $s['k_ho']  = viSortKey($s['ho']);
    }
    unset($s);
    usort($students, function ($a, $b) {
        return strcmp($a['k_ten'], $b['k_ten'])
            ?: strcmp($a['k_ho'], $b['k_ho'])
            ?: ($a['mahocsinh'] <=> $b['mahocsinh']);
    });

    // Phòng của điểm thi
    $st = $pdo->prepare("SELECT maphong, tenphong, succhua FROM phong WHERE matruong = ? AND succhua > 0");
    $st->execute([$venueId]);
    $rooms = $st->fetchAll();
    usort($rooms, function ($a, $b) { return strnatcasecmp($a['tenphong'], $b['tenphong']); });

    $total = count($students);
    $totalCap = 0;
    foreach ($rooms as $r) $totalCap += (int)$r['succhua'];

    // 4.2: không đủ sức chứa
    if ($totalCap < $total) {
        return fail(
            'NO_CAPACITY',
            'Điểm thi không đủ sức chứa cho tổng số thí sinh của 2 trường dự thi. Vui lòng chọn thêm phòng hoặc đổi điểm thi khác!',
            "Tổng thí sinh: $total - Tổng sức chứa các phòng tại điểm thi: $totalCap (" . count($rooms) . " phòng)."
        );
    }

    $schoolName = [];
    $schoolCount = [];
    foreach ($schools as $sc) {
        $schoolName[(int)$sc['matruong']] = $sc['tentruong'];
        $schoolCount[(int)$sc['matruong']] = 0;
    }

    $yy = substr((string)$kythi['namtuyensinh'], -2);
    $out = [];
    $i = 0;
    $roomNo = 0;
    foreach ($rooms as $room) {
        if ($i >= $total) break;
        $roomNo++;
        $slice = array_slice($students, $i, (int)$room['succhua']);
        $i += count($slice);

        $list = [];
        $seq = 0;
        foreach ($slice as $stu) {
            $seq++;
            $sid = (int)$stu['matruongdanghoc'];
            $schoolCount[$sid]++;
            $name = trim($stu['ho'] . ' ' . $stu['ten']);
            $list[] = [
                'id'     => (int)$stu['mahocsinh'],
                'sbd'    => sprintf('%s%03d%02d%03d', $yy, $venueId, $roomNo, $seq),
                'name'   => $name !== '' ? $name : '(chưa có hồ sơ)',
                'school' => $schoolName[$sid] ?? '',
            ];
        }
        $out[] = [
            'id'       => (int)$room['maphong'],
            'name'     => $room['tenphong'],
            'capacity' => (int)$room['succhua'],
            'students' => $list,
        ];
    }

    $schoolsOut = [];
    foreach ($schoolName as $id => $name) {
        $schoolsOut[] = ['id' => $id, 'name' => $name, 'count' => $schoolCount[$id]];
    }

    return [
        'ok'        => true,
        'venueId'   => $venueId,
        'venueName' => $venue['tentruong'],
        'schools'   => $schoolsOut,
        'total'     => $total,
        'rooms'     => $out,
    ];
}

/* ===================== NGHIỆP VỤ: LƯU CẤU HÌNH ===================== */
function saveConfig(PDO $pdo, array $kythi, array $in, ?int $uid): array
{
    $alloc = computeAllocation($pdo, $kythi, $in['venue'] ?? null, $in['thcs'] ?? []);
    if (!$alloc['ok']) return $alloc;

    $gradingId = filter_var($in['grading'] ?? null, FILTER_VALIDATE_INT);
    if (!$gradingId) return fail('INVALID', 'Vui lòng chọn điểm chấm thi.');

    $st = $pdo->prepare("SELECT tentruong FROM truonghoc WHERE matruong = ? AND UPPER(TRIM(loaitruong)) = 'THPT'");
    $st->execute([$gradingId]);
    $grading = $st->fetch();
    if (!$grading) return fail('INVALID', 'Điểm chấm thi không hợp lệ (phải là trường THPT).');

    $kid = (int)$kythi['makythi'];
    $venueId = $alloc['venueId'];

    try {
        $pdo->beginTransaction();

        // Đã có điểm thi thì không cho phân bổ lại
        $st = $pdo->prepare(
            "SELECT 1 FROM baithi b JOIN hocsinh h ON h.mahocsinh = b.mahocsinh
             WHERE h.makythi = ? AND b.diem IS NOT NULL LIMIT 1"
        );
        $st->execute([$kid]);
        if ($st->fetchColumn()) {
            throw new RuntimeException('Kỳ thi đã có điểm bài thi nên không thể phân bổ lại phòng thi.');
        }

        // Điểm thi cũ đã có phân công coi thi -> không cho đổi
        $st = $pdo->prepare("SELECT matruong, tentruong FROM truonghoc WHERE ladiemthi = 1 AND matruong <> ?");
        $st->execute([$venueId]);
        $used = $pdo->prepare("SELECT 1 FROM phanconggacthi WHERE madiadiemthi = ? LIMIT 1");
        foreach ($st->fetchAll() as $old) {
            $used->execute([$old['matruong']]);
            if ($used->fetchColumn()) {
                throw new RuntimeException("Điểm thi cũ \"{$old['tentruong']}\" đã có phân công coi thi nên không thể đổi sang điểm thi khác.");
            }
        }

        // Điểm chấm cũ đã có phân công chấm thi -> không cho đổi
        $st = $pdo->prepare("SELECT matruong, tentruong FROM truonghoc WHERE ladiemcham = 1 AND matruong <> ?");
        $st->execute([$gradingId]);
        $used = $pdo->prepare("SELECT 1 FROM phancongchamthi WHERE madiadiemcham = ? LIMIT 1");
        foreach ($st->fetchAll() as $old) {
            $used->execute([$old['matruong']]);
            if ($used->fetchColumn()) {
                throw new RuntimeException("Điểm chấm cũ \"{$old['tentruong']}\" đã có phân công chấm thi nên không thể đổi sang điểm chấm khác.");
            }
        }

        // Cờ điểm thi / điểm chấm thi
        $pdo->exec("UPDATE truonghoc SET ladiemthi = 0 WHERE ladiemthi = 1");
        $pdo->prepare("UPDATE truonghoc SET ladiemthi = 1 WHERE matruong = ?")->execute([$venueId]);
        $pdo->exec("UPDATE truonghoc SET ladiemcham = 0 WHERE ladiemcham = 1");
        $pdo->prepare("UPDATE truonghoc SET ladiemcham = 1 WHERE matruong = ?")->execute([$gradingId]);

        // 2 trường dự thi
        $pdo->prepare("DELETE FROM truongduthi WHERE makythi = ?")->execute([$kid]);
        $ins = $pdo->prepare("INSERT INTO truongduthi (makythi, madiadiemthi, matruongthcs) VALUES (?, ?, ?)");
        foreach ($alloc['schools'] as $sc) {
            $ins->execute([$kid, $venueId, $sc['id']]);
        }

        // Xóa phân bổ & SBD cũ của kỳ thi, ghi phân bổ mới
        $pdo->prepare(
            "DELETE x FROM xepphongthi x JOIN hocsinh h ON h.mahocsinh = x.mahocsinh WHERE h.makythi = ?"
        )->execute([$kid]);
        $pdo->prepare("UPDATE hocsinh SET sobaodanh = NULL WHERE makythi = ?")->execute([$kid]);

        $insRoom = $pdo->prepare("INSERT INTO xepphongthi (mahocsinh, maphong) VALUES (?, ?)");
        $updSbd  = $pdo->prepare("UPDATE hocsinh SET sobaodanh = ? WHERE mahocsinh = ?");
        foreach ($alloc['rooms'] as $room) {
            foreach ($room['students'] as $stu) {
                $insRoom->execute([$stu['id'], $room['id']]);
                $updSbd->execute([$stu['sbd'], $stu['id']]);
            }
        }

        // Audit log
        $names = implode(', ', array_map(function ($s) { return $s['name']; }, $alloc['schools']));
        writeLog(
            $pdo, $uid, 'LUU_DIA_DIEM',
            "Điểm thi: {$alloc['venueName']}; trường dự thi: $names; {$alloc['total']} thí sinh / "
            . count($alloc['rooms']) . " phòng; điểm chấm thi: {$grading['tentruong']}."
        );

        $pdo->commit();
    } catch (Throwable $e) {
        if ($pdo->inTransaction()) $pdo->rollBack();
        return fail('SAVE_ERROR', ($e instanceof RuntimeException) ? $e->getMessage() : 'Lỗi hệ thống, vui lòng thử lại sau.');
    }

    $alloc['gradingName'] = $grading['tentruong'];
    $alloc['time'] = date('H:i:s d/m/Y');
    return [
        'ok'      => true,
        'message' => 'Cập nhật địa điểm thi, chấm thi và phân bổ phòng thi thành công',
        'data'    => $alloc,
        'logs'    => fetchLogs($pdo),
    ];
}

/* ===================== KỲ THI HIỆN TẠI ===================== */
$kythi = $pdo->query("SELECT makythi, namtuyensinh FROM kythituyensinh ORDER BY namtuyensinh DESC LIMIT 1")->fetch();

/* ===================== AJAX (POST JSON) ===================== */
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    header('Content-Type: application/json; charset=utf-8');

    if (!$isSoGD) {
        http_response_code(403);
        echo json_encode(['ok' => false, 'message' => 'Bạn không có quyền thực hiện thao tác này.'], JSON_UNESCAPED_UNICODE);
        exit;
    }
    if (!$kythi) {
        echo json_encode(fail('NO_EXAM', 'Chưa có kỳ thi tuyển sinh nào trong database.'), JSON_UNESCAPED_UNICODE);
        exit;
    }

    $input  = json_decode(file_get_contents('php://input'), true) ?: [];
    $action = $input['action'] ?? '';

    try {
        if ($action === 'preview') {
            $res = computeAllocation($pdo, $kythi, $input['venue'] ?? null, $input['thcs'] ?? []);
        } elseif ($action === 'save') {
            $res = saveConfig($pdo, $kythi, $input, currentUserId());
        } else {
            $res = fail('INVALID', 'Yêu cầu không hợp lệ.');
        }
    } catch (Throwable $e) {
        $res = fail('ERROR', 'Lỗi hệ thống, vui lòng thử lại sau.');
    }
    echo json_encode($res, JSON_UNESCAPED_UNICODE);
    exit;
}

/* ===================== DỮ LIỆU HIỂN THỊ ===================== */
if (!$isSoGD) {
    http_response_code(403);
    exit('Bạn không có quyền truy cập trang này. (Cần đăng nhập bằng tài khoản Sở GD&ĐT)');
}
if (!$kythi) {
    exit('Chưa có kỳ thi tuyển sinh nào trong bảng kythituyensinh.');
}
$kid = (int)$kythi['makythi'];

$thpt = array_map(function ($r) {
    return [
        'id'       => (int)$r['matruong'],
        'code'     => 'THPT' . str_pad((string)$r['matruong'], 2, '0', STR_PAD_LEFT),
        'name'     => $r['tentruong'],
        'ward'     => $r['tenphuong'],
        'rooms'    => (int)$r['rooms'],
        'capacity' => (int)$r['capacity'],
    ];
}, $pdo->query(
    "SELECT t.matruong, t.tentruong, COALESCE(p.tenphuong, '') AS tenphuong,
            (SELECT COUNT(*) FROM phong r WHERE r.matruong = t.matruong AND r.succhua > 0) AS rooms,
            (SELECT COALESCE(SUM(r.succhua), 0) FROM phong r WHERE r.matruong = t.matruong AND r.succhua > 0) AS capacity
     FROM truonghoc t LEFT JOIN phuong p ON p.maphuong = t.maphuong
     WHERE UPPER(TRIM(t.loaitruong)) = 'THPT'
     ORDER BY t.tentruong"
)->fetchAll());

$st = $pdo->prepare(
    "SELECT t.matruong, t.tentruong, COALESCE(p.tenphuong, '') AS tenphuong,
            (SELECT COUNT(*) FROM hocsinh h WHERE h.matruongdanghoc = t.matruong AND h.makythi = ?) AS students
     FROM truonghoc t LEFT JOIN phuong p ON p.maphuong = t.maphuong
     WHERE UPPER(TRIM(t.loaitruong)) = 'THCS'
     ORDER BY t.tentruong"
);
$st->execute([$kid]);
$thcs = array_map(function ($r) {
    return [
        'id'       => (int)$r['matruong'],
        'code'     => 'THCS' . str_pad((string)$r['matruong'], 2, '0', STR_PAD_LEFT),
        'name'     => $r['tentruong'],
        'ward'     => $r['tenphuong'],
        'students' => (int)$r['students'],
    ];
}, $st->fetchAll());

// Cấu hình đã lưu
$st = $pdo->prepare("SELECT madiadiemthi, matruongthcs FROM truongduthi WHERE makythi = ?");
$st->execute([$kid]);
$savedRows = $st->fetchAll();
$savedVenue = $savedRows ? (int)$savedRows[0]['madiadiemthi'] : null;
$savedThcs  = array_map(function ($r) { return (int)$r['matruongthcs']; }, $savedRows);
$g = $pdo->query("SELECT matruong FROM truonghoc WHERE ladiemcham = 1 AND UPPER(TRIM(loaitruong)) = 'THPT' LIMIT 1")->fetchColumn();

$initJson = json_encode([
    'kythi' => ['id' => $kid, 'year' => (int)$kythi['namtuyensinh']],
    'thpt'  => $thpt,
    'thcs'  => $thcs,
    'saved' => ['venue' => $savedVenue, 'thcs' => $savedThcs, 'grading' => $g ? (int)$g : null],
    'logs'  => fetchLogs($pdo),
], JSON_UNESCAPED_UNICODE | JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT);
?>
<?php
    require(__DIR__ . "/../../../components/layout/header.php");
    require(__DIR__ . "/../../../components/layout/sidebar.php");
?>
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Stackers - Quản lý địa điểm thi/chấm thi</title>
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
        renderHeader();
        renderSidebar();
    ?>

<div class="ml-72 pt-24 max-w-7xl mx-auto p-8">

    <!-- TIÊU ĐỀ TRANG -->
    <div class="flex justify-between items-center mb-6">
        <div>
            <h1 class="font-bold text-2xl mb-1">Quản lý địa điểm thi / chấm thi</h1>
            <p class="text-gray-500 text-sm">
                Kỳ thi tuyển sinh năm <b><?= (int)$kythi['namtuyensinh'] ?></b> - Thiết lập điểm thi, trường dự thi, phòng thi và điểm chấm thi
            </p>
        </div>
        <div class="flex items-center gap-3">
            <span id="dirty-badge" class="hidden text-xs font-medium text-yellow-800 bg-yellow-100 border border-yellow-300 rounded-full px-3 py-1">Có thay đổi chưa lưu</span>
            <button type="button" onclick="cancelChanges()" class="border bg-white text-gray-700 rounded-lg px-5 py-3 hover:bg-gray-100 text-sm font-medium">Hủy</button>
            <button type="button" id="save-btn" onclick="saveAll()" class="bg-gray-900 text-white rounded-lg px-6 py-3 hover:bg-gray-800 text-sm font-medium shadow-sm disabled:opacity-50">Lưu danh sách</button>
        </div>
    </div>

    <!-- KẾT QUẢ SAU KHI LƯU -->
    <div id="result-panel" class="hidden border border-green-300 bg-green-50 rounded-xl p-6 mb-6">
        <div class="font-bold text-green-800 mb-3">Cập nhật địa điểm thi, chấm thi và phân bổ phòng thi thành công</div>
        <div id="result-body" class="text-sm text-gray-700"></div>
    </div>

    <!-- TABS -->
    <div class="flex gap-2 mb-4">
        <button type="button" id="tab-exam" onclick="switchTab('exam')" class="px-5 py-2.5 rounded-lg text-sm font-semibold border">Địa điểm thi</button>
        <button type="button" id="tab-grading" onclick="switchTab('grading')" class="px-5 py-2.5 rounded-lg text-sm font-semibold border">Địa điểm chấm thi</button>
    </div>

    <!-- ============ TAB: ĐỊA ĐIỂM THI ============ -->
    <div id="panel-exam">

        <!-- Bước 1: chọn điểm thi -->
        <div class="border bg-white rounded-xl shadow-sm mb-6">
            <div class="p-4 border-b bg-gray-50 rounded-t-xl font-bold text-sm text-gray-700 flex flex-wrap gap-3 justify-between items-center">
                <span>Bước 1: Chọn điểm thi (đúng 1 trường THPT)</span>
                <div class="relative">
                    <input id="search-venue" type="text" autocomplete="off" placeholder="Tìm tên trường THPT..."
                           oninput="onSearch('venue')" onfocus="onSearch('venue')" onkeydown="onSearchKey(event,'venue')"
                           class="border rounded-lg px-3 py-2 text-sm font-normal bg-white outline-none focus:ring-2 focus:ring-gray-900 w-64">
                    <div id="suggest-venue" class="hidden absolute left-0 right-0 mt-1 bg-white border rounded-lg shadow-lg z-30 max-h-72 overflow-y-auto font-normal"></div>
                </div>
            </div>
            <div class="overflow-x-auto max-h-80 overflow-y-auto">
                <table class="w-full text-left border-collapse">
                    <thead class="sticky top-0">
                        <tr class="bg-gray-100 border-b text-sm text-gray-600">
                            <th class="p-3 w-12"></th><th class="p-3">Mã trường</th><th class="p-3">Tên trường THPT</th>
                            <th class="p-3">Phường</th><th class="p-3">Số phòng</th><th class="p-3">Tổng sức chứa</th>
                        </tr>
                    </thead>
                    <tbody id="body-venue" class="text-sm"></tbody>
                </table>
            </div>
            <div id="sel-venue" class="px-4 py-3 border-t text-sm text-gray-600"></div>
        </div>

        <!-- Bước 2: chọn 2 trường THCS -->
        <div class="border bg-white rounded-xl shadow-sm mb-6">
            <div class="p-4 border-b bg-gray-50 rounded-t-xl font-bold text-sm text-gray-700 flex flex-wrap gap-3 justify-between items-center">
                <span>Bước 2: Chọn 2 trường THCS dự thi <span id="thcs-counter" class="ml-2 text-xs font-normal text-gray-500"></span></span>
                <div class="relative">
                    <input id="search-thcs" type="text" autocomplete="off" placeholder="Tìm tên trường THCS..."
                           oninput="onSearch('thcs')" onfocus="onSearch('thcs')" onkeydown="onSearchKey(event,'thcs')"
                           class="border rounded-lg px-3 py-2 text-sm font-normal bg-white outline-none focus:ring-2 focus:ring-gray-900 w-64">
                    <div id="suggest-thcs" class="hidden absolute left-0 right-0 mt-1 bg-white border rounded-lg shadow-lg z-30 max-h-72 overflow-y-auto font-normal"></div>
                </div>
            </div>
            <div class="overflow-x-auto max-h-80 overflow-y-auto">
                <table class="w-full text-left border-collapse">
                    <thead class="sticky top-0">
                        <tr class="bg-gray-100 border-b text-sm text-gray-600">
                            <th class="p-3 w-12"></th><th class="p-3">Mã trường</th><th class="p-3">Tên trường THCS</th>
                            <th class="p-3">Phường</th><th class="p-3">Số học sinh đăng ký thi</th>
                        </tr>
                    </thead>
                    <tbody id="body-thcs" class="text-sm"></tbody>
                </table>
            </div>
            <div id="sel-thcs" class="px-4 py-3 border-t text-sm text-gray-600"></div>
        </div>

        <!-- Nút sinh phòng -->
        <div class="flex flex-wrap items-center gap-3 mb-6">
            <button type="button" id="gen-btn" onclick="generate()" class="bg-gray-900 text-white rounded-lg px-6 py-3 hover:bg-gray-800 text-sm font-medium shadow-sm disabled:opacity-50">
                Tự động sinh phòng thi và phân bổ thí sinh
            </button>
            <button type="button" id="edit-btn" onclick="unlockEdit()" class="hidden border bg-white text-gray-700 rounded-lg px-5 py-3 hover:bg-gray-100 text-sm font-medium">
                Chỉnh sửa lại
            </button>
            <button type="button" id="next-btn" onclick="switchTab('grading')" class="hidden text-sm font-semibold text-gray-900 underline">
                Tiếp tục: chọn điểm chấm thi →
            </button>
        </div>

        <!-- Preview -->
        <div id="preview-card" class="hidden border bg-white rounded-xl shadow-sm mb-8">
            <div class="p-4 border-b bg-gray-50 rounded-t-xl font-bold text-sm text-gray-700">Xem trước phòng thi và thí sinh (chưa lưu)</div>
            <div class="p-4">
                <div id="preview-summary" class="text-sm text-gray-700 mb-4"></div>
                <div id="preview-rooms"></div>
            </div>
        </div>
    </div>

    <!-- ============ TAB: ĐỊA ĐIỂM CHẤM THI ============ -->
    <div id="panel-grading" class="hidden">
        <div class="border bg-white rounded-xl shadow-sm mb-8">
            <div class="p-4 border-b bg-gray-50 rounded-t-xl font-bold text-sm text-gray-700 flex flex-wrap gap-3 justify-between items-center">
                <span>Chọn điểm chấm thi (đúng 1 trường THPT)</span>
                <div class="relative">
                    <input id="search-grading" type="text" autocomplete="off" placeholder="Tìm tên trường THPT..."
                           oninput="onSearch('grading')" onfocus="onSearch('grading')" onkeydown="onSearchKey(event,'grading')"
                           class="border rounded-lg px-3 py-2 text-sm font-normal bg-white outline-none focus:ring-2 focus:ring-gray-900 w-64">
                    <div id="suggest-grading" class="hidden absolute left-0 right-0 mt-1 bg-white border rounded-lg shadow-lg z-30 max-h-72 overflow-y-auto font-normal"></div>
                </div>
            </div>
            <div class="overflow-x-auto max-h-96 overflow-y-auto">
                <table class="w-full text-left border-collapse">
                    <thead class="sticky top-0">
                        <tr class="bg-gray-100 border-b text-sm text-gray-600">
                            <th class="p-3 w-12"></th><th class="p-3">Mã trường</th><th class="p-3">Tên trường THPT</th><th class="p-3">Phường</th>
                        </tr>
                    </thead>
                    <tbody id="body-grading" class="text-sm"></tbody>
                </table>
            </div>
            <div id="sel-grading" class="px-4 py-3 border-t text-sm text-gray-600"></div>
        </div>
    </div>

    <!-- LỊCH SỬ THAY ĐỔI -->
    <div class="border bg-white rounded-xl shadow-sm p-6">
        <h3 class="font-bold text-base mb-3 text-gray-800">Lịch sử thay đổi gần đây (Log)</h3>
        <div id="history-log-container" class="space-y-2 text-sm text-gray-600"></div>
    </div>

</div>

<script>
    const INIT = <?= $initJson ?>;
    const HOME_URL = <?= json_encode(HOME_URL) ?>;

    const state = {
        tab: 'exam',
        venue: INIT.saved.venue,
        thcs: [...INIT.saved.thcs],
        grading: INIT.saved.grading,
        preview: null,
        previewSaved: false,
        locked: false
    };

    function snapshot() {
        return JSON.stringify({ v: state.venue, t: [...state.thcs].sort(), g: state.grading });
    }
    let savedSnap = snapshot();

    function isDirty() { return snapshot() !== savedSnap || (state.preview !== null && !state.previewSaved); }

    /* ===================== TIỆN ÍCH ===================== */
    function removeAccents(str) {
        return String(str ?? '')
            .normalize('NFC')
            .split('')
            .map(c => c === 'đ' ? 'd' : c === 'Đ' ? 'D' : c.normalize('NFD').replace(/[\u0300-\u036f]/g, ''))
            .join('')
            .toLowerCase();
    }
    function highlightMatch(name, kw) {
        const text = String(name).normalize('NFC');
        const idx = removeAccents(text).indexOf(kw);
        if (idx < 0) return escapeHtml(text);
        return escapeHtml(text.slice(0, idx))
            + '<mark class="bg-yellow-200 rounded px-0.5">' + escapeHtml(text.slice(idx, idx + kw.length)) + '</mark>'
            + escapeHtml(text.slice(idx + kw.length));
    }
    const SRC = { venue: () => INIT.thpt, thcs: () => INIT.thcs, grading: () => INIT.thpt };
    function nameOf(list, id) { const s = list.find(x => x.id === id); return s ? s.name : ''; }

    async function callApi(payload) {
        try {
            const res = await fetch(window.location.href, {
                method: "POST",
                headers: { "Content-Type": "application/json" },
                body: JSON.stringify(payload)
            });
            return await res.json();
        } catch (e) {
            return { ok: false, message: "Không kết nối được máy chủ hoặc phản hồi không hợp lệ." };
        }
    }
    function errText(r) { return r.message + (r.detail ? "\n\n" + r.detail : ""); }

    /* ===================== GỢI Ý TÊN TRƯỜNG ===================== */
    const sug = { venue: { items: [], active: -1 }, thcs: { items: [], active: -1 }, grading: { items: [], active: -1 } };

    function hideSuggest(key) {
        document.getElementById('suggest-' + key).classList.add('hidden');
        sug[key].active = -1;
    }

    function onSearch(key) {
        renderTable(key);
        const box = document.getElementById('suggest-' + key);
        const kw = removeAccents(document.getElementById('search-' + key).value.trim());
        if (!kw) { hideSuggest(key); return; }

        sug[key].items = SRC[key]().filter(s => removeAccents(s.name).includes(kw)).slice(0, 8);
        sug[key].active = -1;

        if (sug[key].items.length === 0) {
            box.innerHTML = `<div class="px-4 py-3 text-sm text-gray-400 italic">Không tìm thấy trường phù hợp</div>`;
        } else {
            box.innerHTML = sug[key].items.map((s, i) => `
                <div class="suggest-item px-4 py-2.5 cursor-pointer text-sm text-gray-800 hover:bg-gray-100" onmousedown="pickSuggest('${key}', ${i})">
                    <div class="font-medium">${highlightMatch(s.name, kw)}</div>
                    <div class="text-xs text-gray-400">${escapeHtml(s.code)} · ${escapeHtml(s.ward)}</div>
                </div>`).join('');
        }
        box.classList.remove('hidden');
    }

    function pickSuggest(key, i) {
        const s = sug[key].items[i];
        if (!s) return;
        document.getElementById('search-' + key).value = s.name;
        hideSuggest(key);
        if (key === 'venue') selectVenue(s.id);
        else if (key === 'thcs') addThcs(s.id);
        else selectGrading(s.id);
        renderTable(key);
    }

    function setActive(key, i) {
        const items = document.querySelectorAll('#suggest-' + key + ' .suggest-item');
        items.forEach(el => el.classList.remove('bg-gray-200'));
        sug[key].active = i;
        if (items[i]) { items[i].classList.add('bg-gray-200'); items[i].scrollIntoView({ block: 'nearest' }); }
    }

    function onSearchKey(e, key) {
        const box = document.getElementById('suggest-' + key);
        const n = sug[key].items.length;
        if (box.classList.contains('hidden') || n === 0) return;
        if (e.key === 'ArrowDown') { e.preventDefault(); setActive(key, (sug[key].active + 1) % n); }
        else if (e.key === 'ArrowUp') { e.preventDefault(); setActive(key, (sug[key].active - 1 + n) % n); }
        else if (e.key === 'Enter') {
            if (sug[key].active >= 0) { e.preventDefault(); pickSuggest(key, sug[key].active); } else hideSuggest(key);
        } else if (e.key === 'Escape') hideSuggest(key);
    }

    document.addEventListener('click', function (e) {
        ['venue', 'thcs', 'grading'].forEach(k => {
            if (!e.target.closest('#search-' + k) && !e.target.closest('#suggest-' + k)) hideSuggest(k);
        });
    });

    /* ===================== CHỌN TRƯỜNG ===================== */
    function selectVenue(id) {
        if (state.locked) return;
        state.venue = id;
        state.preview = null;
        renderAll();
    }
    function addThcs(id) {
        if (state.locked || state.thcs.includes(id)) return;
        if (state.thcs.length >= 2) { alert("Chỉ được chọn tối đa 2 trường THCS dự thi."); renderAll(); return; }
        state.thcs.push(id);
        state.preview = null;
        renderAll();
    }
    function toggleThcs(id, checked) {
        if (state.locked) return;
        if (checked) { addThcs(id); return; }
        state.thcs = state.thcs.filter(x => x !== id);
        state.preview = null;
        renderAll();
    }
    function selectGrading(id) {
        state.grading = id;
        renderAll();
    }

    /* ===================== RENDER ===================== */
    function renderTable(key) {
        const body = document.getElementById('body-' + key);
        const kw = removeAccents(document.getElementById('search-' + key).value.trim());
        const list = SRC[key]().filter(s => !kw ||
            removeAccents(s.name).includes(kw) || removeAccents(s.ward).includes(kw) || removeAccents(s.code).includes(kw));

        if (SRC[key]().length === 0) {
            body.innerHTML = `<tr><td colspan="6" class="p-6 text-center text-gray-400 italic">Chưa có trường ${key === 'thcs' ? 'THCS' : 'THPT'} nào trong database (bảng truonghoc).</td></tr>`;
            return;
        }
        if (list.length === 0) {
            body.innerHTML = `<tr><td colspan="6" class="p-6 text-center text-gray-400 italic">Không có trường phù hợp.</td></tr>`;
            return;
        }

        const dis = state.locked && key !== 'grading' ? 'disabled' : '';
        body.innerHTML = list.map(s => {
            let sel, ctl, extra = '';
            if (key === 'venue') {
                sel = state.venue === s.id;
                ctl = `<input type="radio" name="venue" ${sel ? 'checked' : ''} ${dis} onchange="selectVenue(${s.id})">`;
                extra = `<td class="p-3">${s.rooms > 0 ? s.rooms + ' phòng' : '<span class="text-red-500">Chưa có phòng</span>'}</td><td class="p-3">${s.capacity}</td>`;
            } else if (key === 'thcs') {
                sel = state.thcs.includes(s.id);
                ctl = `<input type="checkbox" ${sel ? 'checked' : ''} ${dis} onchange="toggleThcs(${s.id}, this.checked)">`;
                extra = `<td class="p-3">${s.students > 0 ? s.students : '<span class="text-red-500">0</span>'}</td>`;
            } else {
                sel = state.grading === s.id;
                ctl = `<input type="radio" name="grading" ${sel ? 'checked' : ''} onchange="selectGrading(${s.id})">`;
            }
            return `<tr class="border-b ${sel ? 'bg-yellow-50' : 'hover:bg-gray-50'}">
                <td class="p-3 text-center">${ctl}</td>
                <td class="p-3 font-semibold text-gray-900">${escapeHtml(s.code)}</td>
                <td class="p-3 font-medium">${escapeHtml(s.name)}</td>
                <td class="p-3 text-gray-500">${escapeHtml(s.ward)}</td>
                ${extra}
            </tr>`;
        }).join('');
    }

    function renderSelected() {
        const v = document.getElementById('sel-venue');
        v.innerHTML = state.venue ? `Điểm thi đã chọn: <b>${escapeHtml(nameOf(INIT.thpt, state.venue))}</b>` : `<span class="italic text-gray-400">Chưa chọn điểm thi.</span>`;

        const t = document.getElementById('sel-thcs');
        t.innerHTML = state.thcs.length
            ? 'Trường dự thi đã chọn: ' + state.thcs.map(id => `<b>${escapeHtml(nameOf(INIT.thcs, id))}</b>`).join(', ')
            : `<span class="italic text-gray-400">Chưa chọn trường dự thi.</span>`;
        document.getElementById('thcs-counter').textContent = `(đã chọn ${state.thcs.length}/2)`;

        const g = document.getElementById('sel-grading');
        g.innerHTML = state.grading ? `Điểm chấm thi đã chọn: <b>${escapeHtml(nameOf(INIT.thpt, state.grading))}</b>` : `<span class="italic text-gray-400">Chưa chọn điểm chấm thi.</span>`;
    }

    function renderPreview() {
        const card = document.getElementById('preview-card');
        const p = state.preview;
        if (!p) { card.classList.add('hidden'); return; }
        card.classList.remove('hidden');

        document.getElementById('preview-summary').innerHTML = `
            <div>Điểm thi: <b>${escapeHtml(p.venueName)}</b></div>
            <div>Trường dự thi: ${p.schools.map(s => `<b>${escapeHtml(s.name)}</b> (${s.count} thí sinh)`).join(', ')}</div>
            <div>Tổng: <b>${p.total}</b> thí sinh - <b>${p.rooms.length}</b> phòng thi (sắp xếp theo tên A-Z)</div>`;

        document.getElementById('preview-rooms').innerHTML = p.rooms.map((r, i) => {
            const first = r.students[0].sbd, last = r.students[r.students.length - 1].sbd;
            return `<details class="border rounded-lg mb-2" ${i === 0 ? 'open' : ''}>
                <summary class="cursor-pointer px-4 py-3 bg-gray-50 font-medium text-sm">
                    ${escapeHtml(r.name)} - ${r.students.length}/${r.capacity} thí sinh · SBD ${escapeHtml(first)} → ${escapeHtml(last)}
                </summary>
                <div class="overflow-x-auto">
                    <table class="w-full text-left text-sm">
                        <thead><tr class="bg-gray-100 border-b text-gray-600">
                            <th class="p-2 pl-4 w-16">STT</th><th class="p-2">SBD</th><th class="p-2">Họ và tên</th><th class="p-2">Trường THCS</th>
                        </tr></thead>
                        <tbody>${r.students.map((s, j) => `
                            <tr class="border-b">
                                <td class="p-2 pl-4">${j + 1}</td>
                                <td class="p-2 font-semibold">${escapeHtml(s.sbd)}</td>
                                <td class="p-2">${escapeHtml(s.name)}</td>
                                <td class="p-2 text-gray-500">${escapeHtml(s.school)}</td>
                            </tr>`).join('')}
                        </tbody>
                    </table>
                </div>
            </details>`;
        }).join('');
    }

    function renderControls() {
        document.getElementById('gen-btn').disabled = state.locked;
        document.getElementById('edit-btn').classList.toggle('hidden', !state.locked);
        document.getElementById('next-btn').classList.toggle('hidden', !state.preview);
        document.getElementById('dirty-badge').classList.toggle('hidden', !isDirty());

        const active = 'bg-gray-900 text-white border-gray-900', idle = 'bg-white text-gray-700 hover:bg-gray-100';
        document.getElementById('tab-exam').className = 'px-5 py-2.5 rounded-lg text-sm font-semibold border ' + (state.tab === 'exam' ? active : idle);
        document.getElementById('tab-grading').className = 'px-5 py-2.5 rounded-lg text-sm font-semibold border ' + (state.tab === 'grading' ? active : idle);
        document.getElementById('panel-exam').classList.toggle('hidden', state.tab !== 'exam');
        document.getElementById('panel-grading').classList.toggle('hidden', state.tab !== 'grading');
    }

    function renderLogs(logs) {
        const c = document.getElementById('history-log-container');
        if (!logs || logs.length === 0) {
            c.innerHTML = `<p class="text-gray-400 italic">Chưa có thao tác nào được ghi nhận.</p>`;
            return;
        }
        c.innerHTML = logs.map(l => `<div class="p-2 bg-gray-50 rounded border-l-4 border-gray-900">[${escapeHtml(l.time)}] ${escapeHtml(l.content)}</div>`).join('');
    }

    function renderAll() {
        renderTable('venue'); renderTable('thcs'); renderTable('grading');
        renderSelected(); renderPreview(); renderControls();
    }

    function switchTab(tab) { state.tab = tab; renderControls(); window.scrollTo({ top: 0, behavior: 'smooth' }); }

    /* ===================== SINH PHÒNG & PHÂN BỔ ===================== */
    async function generate() {
        if (!state.venue || state.thcs.length !== 2) {
            alert("Vui lòng chọn đúng 1 điểm thi (THPT) và 2 trường THCS dự thi.");
            return;
        }
        const btn = document.getElementById('gen-btn');
        btn.disabled = true;
        const res = await callApi({ action: 'preview', venue: state.venue, thcs: state.thcs });
        btn.disabled = false;

        if (!res.ok) { alert(errText(res)); return; }   // 4.1 / 4.2

        state.preview = res;
        state.previewSaved = false;
        state.locked = true;
        renderAll();
        document.getElementById('preview-card').scrollIntoView({ behavior: 'smooth' });
    }

    // 7.1 Chỉnh sửa lại sau khi xem trước
    function unlockEdit() {
        state.locked = false;
        state.preview = null;
        state.previewSaved = false;
        renderAll();
    }

    /* ===================== LƯU ===================== */
    async function saveAll() {
        if (!state.preview) {
            alert("Vui lòng chọn điểm thi, 2 trường dự thi và nhấn \"Tự động sinh phòng thi và phân bổ thí sinh\" để xem trước trước khi lưu.");
            switchTab('exam');
            return;
        }
        if (!state.grading) {
            alert("Vui lòng chọn điểm chấm thi ở tab \"Địa điểm chấm thi\" trước khi lưu.");
            switchTab('grading');
            return;
        }

        const p = state.preview;
        const ok = confirm(
            `Xác nhận lưu cấu hình?\n\n` +
            `Điểm thi: ${p.venueName}\n` +
            `Trường dự thi: ${p.schools.map(s => s.name).join(', ')}\n` +
            `Số thí sinh: ${p.total} - Số phòng: ${p.rooms.length}\n` +
            `Điểm chấm thi: ${nameOf(INIT.thpt, state.grading)}`
        );
        if (!ok) return;

        const btn = document.getElementById('save-btn');
        btn.disabled = true;
        const res = await callApi({ action: 'save', venue: state.venue, thcs: state.thcs, grading: state.grading });
        btn.disabled = false;

        if (!res.ok) { alert(errText(res)); return; }

        state.preview = Object.assign({ ok: true }, res.data);
        state.previewSaved = true;
        savedSnap = snapshot();
        renderAll();
        renderLogs(res.logs);
        showResult(res.data);
        alert(res.message);
        window.scrollTo({ top: 0, behavior: 'smooth' });
    }

    function showResult(d) {
        document.getElementById('result-panel').classList.remove('hidden');
        document.getElementById('result-body').innerHTML = `
            <table class="text-sm mb-4">
                <tr><td class="pr-6 py-1 text-gray-500">Điểm thi</td><td class="font-semibold">${escapeHtml(d.venueName)}</td></tr>
                <tr><td class="pr-6 py-1 text-gray-500">Trường dự thi</td><td class="font-semibold">${d.schools.map(s => escapeHtml(s.name) + ' (' + s.count + ')').join(', ')}</td></tr>
                <tr><td class="pr-6 py-1 text-gray-500">Tổng thí sinh</td><td class="font-semibold">${d.total}</td></tr>
                <tr><td class="pr-6 py-1 text-gray-500">Số phòng thi</td><td class="font-semibold">${d.rooms.length}</td></tr>
                <tr><td class="pr-6 py-1 text-gray-500">Điểm chấm thi</td><td class="font-semibold">${escapeHtml(d.gradingName)}</td></tr>
                <tr><td class="pr-6 py-1 text-gray-500">Thời gian lưu</td><td class="font-semibold">${escapeHtml(d.time)}</td></tr>
            </table>
            <table class="w-full text-left text-sm bg-white border rounded-lg">
                <thead><tr class="bg-gray-100 border-b text-gray-600"><th class="p-2">Phòng thi</th><th class="p-2">Số thí sinh</th><th class="p-2">SBD từ</th><th class="p-2">SBD đến</th></tr></thead>
                <tbody>${d.rooms.map(r => `<tr class="border-b">
                    <td class="p-2 font-medium">${escapeHtml(r.name)}</td><td class="p-2">${r.students.length}</td>
                    <td class="p-2">${escapeHtml(r.students[0].sbd)}</td><td class="p-2">${escapeHtml(r.students[r.students.length - 1].sbd)}</td>
                </tr>`).join('')}</tbody>
            </table>`;
    }

    /* ===================== HỦY (7.2) ===================== */
    function cancelChanges() {
        if (isDirty() && !confirm("Bạn có muốn hủy các thay đổi chưa lưu?")) return;
        // bỏ thay đổi tạm thời để không bị cảnh báo khi rời trang
        const s = JSON.parse(savedSnap);
        state.venue = s.v; state.thcs = s.t; state.grading = s.g;
        state.preview = null; state.previewSaved = false; state.locked = false;
        if (HOME_URL) window.location.href = HOME_URL; else window.location.reload();
    }

    window.addEventListener('beforeunload', function (e) {
        if (isDirty()) { e.preventDefault(); e.returnValue = ''; }
    });

    window.onload = function () {
        renderAll();
        renderLogs(INIT.logs);
    };
</script>

</body>
</html>