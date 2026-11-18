<?php
// Quản lý bài thi (Sở GD&ĐT): cập nhật vắng thi, điểm trừ vi phạm và sinh danh sách phách - 1 FILE DUY NHẤT

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
const MAX_DEDUCTION = 10;     // điểm trừ tối đa
const HOME_URL = '';          // trang quay về khi bấm Hủy (để trống = ở lại trang)

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

function sessionFindKey($data, string $key)
{
    if (is_array($data)) {
        foreach ($data as $k => $v) {
            if ($k === $key && is_scalar($v)) return $v;
            $r = sessionFindKey($v, $key);
            if ($r !== null) return $r;
        }
    }
    return null;
}

$isSoGD = !REQUIRE_SO_GD || sessionHasRole($_SESSION ?? [], 'SO_GD');

/* ===================== TỰ TẠO CỘT CÒN THIẾU ===================== */
// baithi chưa có cột lưu trạng thái vắng thi theo từng môn
try {
    $col = $pdo->query("SHOW COLUMNS FROM baithi LIKE 'vang'")->fetch();
    if (!$col) {
        $pdo->exec("ALTER TABLE baithi ADD COLUMN vang TINYINT(1) NOT NULL DEFAULT 0");
    }
} catch (PDOException $e) {
    http_response_code(500);
    exit('Không tự tạo được cột baithi.vang (kiểm tra quyền của user database): ' . htmlspecialchars($e->getMessage()));
}

/* ===================== HÀM TIỆN ÍCH ===================== */
function fail(string $code, string $message, string $detail = ''): array
{
    return ['ok' => false, 'code' => $code, 'message' => $message, 'detail' => $detail];
}

function newPhach(array &$used): string
{
    for ($i = 0; $i < 2000; $i++) {
        $c = 'P' . str_pad((string)random_int(1, 999999), 6, '0', STR_PAD_LEFT);
        if (!isset($used[$c])) {
            $used[$c] = true;
            return $c;
        }
    }
    throw new RuntimeException('Không sinh được số phách duy nhất.');
}

/* ===================== NGHIỆP VỤ ===================== */
// Kiểm tra điểm thi / môn thi / phòng thi hợp lệ trong kỳ thi hiện tại
function loadContext(PDO $pdo, array $kythi, $venueId, $subjectId, $roomId): array
{
    $kid = (int)$kythi['makythi'];
    $venueId = (int)$venueId;
    $subjectId = (int)$subjectId;
    $roomId = (int)$roomId;

    $st = $pdo->prepare(
        "SELECT t.tentruong FROM truongduthi d JOIN truonghoc t ON t.matruong = d.madiadiemthi
         WHERE d.makythi = ? AND d.madiadiemthi = ? LIMIT 1"
    );
    $st->execute([$kid, $venueId]);
    $venueName = $st->fetchColumn();
    if ($venueName === false) return fail('INVALID', 'Điểm thi không hợp lệ hoặc chưa được cấu hình cho kỳ thi này.');

    $st = $pdo->prepare(
        "SELECT m.tenmonhoc FROM monhoc m JOIN lichthi l ON l.mamonhoc = m.mamonhoc AND l.makythi = ?
         WHERE m.mamonhoc = ? AND m.lamonthi = 1 LIMIT 1"
    );
    $st->execute([$kid, $subjectId]);
    $subjectName = $st->fetchColumn();
    if ($subjectName === false) return fail('INVALID', 'Môn thi không hợp lệ hoặc chưa có trong lịch thi.');

    $roomName = '';
    if ($roomId > 0) {
        $st = $pdo->prepare("SELECT tenphong FROM phong WHERE maphong = ? AND matruong = ?");
        $st->execute([$roomId, $venueId]);
        $roomName = $st->fetchColumn();
        if ($roomName === false) return fail('INVALID', 'Phòng thi không thuộc điểm thi đã chọn.');
    }

    return ['ok' => true, 'venueName' => $venueName, 'subjectName' => $subjectName, 'roomName' => $roomName];
}

function listRooms(PDO $pdo, array $kythi, $venueId): array
{
    $kid = (int)$kythi['makythi'];
    $venueId = (int)$venueId;

    $st = $pdo->prepare("SELECT 1 FROM truongduthi WHERE makythi = ? AND madiadiemthi = ? LIMIT 1");
    $st->execute([$kid, $venueId]);
    if (!$st->fetchColumn()) return fail('INVALID', 'Điểm thi không hợp lệ hoặc chưa được cấu hình cho kỳ thi này.');

    $st = $pdo->prepare(
        "SELECT p.maphong, p.tenphong, COUNT(x.mahocsinh) AS total
         FROM phong p
         JOIN xepphongthi x ON x.maphong = p.maphong
         JOIN hocsinh h ON h.mahocsinh = x.mahocsinh AND h.makythi = ?
         WHERE p.matruong = ?
         GROUP BY p.maphong, p.tenphong"
    );
    $st->execute([$kid, $venueId]);
    $rows = $st->fetchAll();
    usort($rows, function ($a, $b) { return strnatcasecmp($a['tenphong'], $b['tenphong']); });

    return [
        'ok'    => true,
        'rooms' => array_map(function ($r) {
            return ['id' => (int)$r['maphong'], 'name' => $r['tenphong'], 'total' => (int)$r['total']];
        }, $rows),
    ];
}

function loadStudents(PDO $pdo, array $kythi, int $subjectId, int $roomId): array
{
    $st = $pdo->prepare(
        "SELECT h.mahocsinh, h.sobaodanh, COALESCE(u.ho, '') AS ho, COALESCE(u.ten, '') AS ten,
                b.sophach, b.diem, COALESCE(b.vang, 0) AS vang,
                COALESCE(b.tyletrudiem, 0) AS tru, COALESCE(b.lydotrudiem, '') AS lydo
         FROM xepphongthi x
         JOIN hocsinh h ON h.mahocsinh = x.mahocsinh AND h.makythi = ?
         LEFT JOIN hosonguoidung u ON u.manguoidung = h.manguoidung
         LEFT JOIN baithi b ON b.mahocsinh = h.mahocsinh AND b.mamonhoc = ?
         WHERE x.maphong = ?
         ORDER BY h.sobaodanh, h.mahocsinh"
    );
    $st->execute([(int)$kythi['makythi'], $subjectId, $roomId]);

    return array_map(function ($r) {
        $name = trim($r['ho'] . ' ' . $r['ten']);
        return [
            'id'        => (int)$r['mahocsinh'],
            'sbd'       => (string)($r['sobaodanh'] ?? ''),
            'name'      => $name !== '' ? $name : '(chưa có hồ sơ)',
            'phach'     => (string)($r['sophach'] ?? ''),
            'graded'    => $r['diem'] !== null,
            'absent'    => (int)$r['vang'] === 1,
            'deduction' => (float)$r['tru'],
            'note'      => (string)$r['lydo'],
        ];
    }, $st->fetchAll());
}

function saveExam(PDO $pdo, array $kythi, array $in): array
{
    $subjectId = (int)($in['subject'] ?? 0);
    $roomId    = (int)($in['room'] ?? 0);
    $ctx = loadContext($pdo, $kythi, $in['venue'] ?? 0, $subjectId, $roomId);
    if (!$ctx['ok']) return $ctx;

    $current = loadStudents($pdo, $kythi, $subjectId, $roomId);
    if (!$current) return fail('EMPTY', 'Phòng thi này chưa có thí sinh.');
    $byId = [];
    foreach ($current as $s) $byId[$s['id']] = $s;

    $items = is_array($in['items'] ?? null) ? $in['items'] : [];
    $clean = [];
    foreach ($items as $it) {
        $id = (int)($it['id'] ?? 0);
        if (!isset($byId[$id]) || isset($clean[$id])) {
            return fail('INVALID', 'Danh sách thí sinh gửi lên không hợp lệ. Vui lòng tải lại trang.');
        }
        $name = $byId[$id]['name'];
        $absent = !empty($it['absent']);
        $ded = $it['deduction'] ?? 0;
        if (!is_numeric($ded) || (float)$ded < 0 || (float)$ded > MAX_DEDUCTION) {
            return fail('INVALID', "Thí sinh $name: điểm trừ phải là số từ 0 đến " . MAX_DEDUCTION . ".");
        }
        $ded = $absent ? 0.0 : round((float)$ded, 2);
        if ($absent && $byId[$id]['graded']) {
            return fail('INVALID', "Thí sinh $name đã có điểm bài thi nên không thể đánh dấu vắng thi.");
        }
        $note = mb_substr(trim((string)($it['note'] ?? '')), 0, 200, 'UTF-8');
        $clean[$id] = ['absent' => $absent, 'ded' => $ded, 'note' => $note];
    }
    if (!$clean) return fail('INVALID', 'Không có dữ liệu thí sinh để lưu.');

    $uid = (int)(sessionFindKey($_SESSION ?? [], 'manguoidung') ?? 0);
    $newPhach = 0;

    try {
        $pdo->beginTransaction();

        $up = $pdo->prepare(
            "INSERT INTO baithi (mahocsinh, mamonhoc, vang, tyletrudiem, lydotrudiem) VALUES (?, ?, ?, ?, ?)
             ON DUPLICATE KEY UPDATE vang = VALUES(vang), tyletrudiem = VALUES(tyletrudiem),
                 lydotrudiem = VALUES(lydotrudiem), sophach = IF(VALUES(vang) = 1, NULL, sophach)"
        );
        foreach ($clean as $id => $c) {
            $up->execute([$id, $subjectId, $c['absent'] ? 1 : 0, $c['ded'], $c['note'] !== '' ? $c['note'] : null]);
        }

        // Sinh số phách ngẫu nhiên, duy nhất cho bài thi chưa có phách (thí sinh có mặt)
        $used = array_flip($pdo->query("SELECT sophach FROM baithi WHERE sophach IS NOT NULL")->fetchAll(PDO::FETCH_COLUMN));
        $setPhach = $pdo->prepare("UPDATE baithi SET sophach = ? WHERE mahocsinh = ? AND mamonhoc = ? AND (sophach IS NULL OR sophach = '')");
        $need = [];
        foreach ($clean as $id => $c) {
            if (!$c['absent'] && $byId[$id]['phach'] === '') $need[] = $id;
        }
        shuffle($need); // thứ tự phách không trùng thứ tự SBD
        foreach ($need as $id) {
            $setPhach->execute([newPhach($used), $id, $subjectId]);
            $newPhach++;
        }

        $absentN = count(array_filter($clean, function ($c) { return $c['absent']; }));
        $dedN    = count(array_filter($clean, function ($c) { return $c['ded'] > 0; }));
        $log = mb_substr(
            "Kỳ thi {$kythi['namtuyensinh']} - {$ctx['venueName']} - {$ctx['roomName']} - môn {$ctx['subjectName']}: "
            . count($clean) . " thí sinh, $absentN vắng, $dedN bị trừ điểm, sinh $newPhach số phách.",
            0, 500, 'UTF-8'
        );
        $pdo->prepare("INSERT INTO nhatkyhethong (manguoidung, hanhdong, noidung) VALUES (?, 'CAP_NHAT_BAI_THI', ?)")
            ->execute([$uid > 0 ? $uid : null, $log]);

        $pdo->commit();
    } catch (Throwable $e) {
        if ($pdo->inTransaction()) $pdo->rollBack();
        return fail('SAVE_ERROR', 'Lỗi hệ thống, không lưu được dữ liệu. Vui lòng thử lại sau.');
    }

    return [
        'ok'      => true,
        'message' => 'Cập nhật thông tin và tạo danh sách phách thành công',
        'data'    => [
            'venueName'   => $ctx['venueName'],
            'subjectName' => $ctx['subjectName'],
            'roomName'    => $ctx['roomName'],
            'students'    => loadStudents($pdo, $kythi, $subjectId, $roomId),
            'newPhach'    => $newPhach,
            'time'        => date('H:i:s d/m/Y'),
        ],
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
        if ($action === 'rooms') {
            $res = listRooms($pdo, $kythi, $input['venue'] ?? 0);
        } elseif ($action === 'students') {
            $res = loadContext($pdo, $kythi, $input['venue'] ?? 0, $input['subject'] ?? 0, $input['room'] ?? 0);
            if ($res['ok']) {
                $res['students'] = loadStudents($pdo, $kythi, (int)$input['subject'], (int)$input['room']);
            }
        } elseif ($action === 'save') {
            $res = saveExam($pdo, $kythi, $input);
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

// Điểm thi đã được cấu hình cho kỳ thi
$st = $pdo->prepare(
    "SELECT DISTINCT t.matruong, t.tentruong
     FROM truongduthi d JOIN truonghoc t ON t.matruong = d.madiadiemthi
     WHERE d.makythi = ? ORDER BY t.tentruong"
);
$st->execute([$kid]);
$venues = array_map(function ($r) {
    return ['id' => (int)$r['matruong'], 'name' => $r['tentruong']];
}, $st->fetchAll());

// Môn thi có trong lịch thi
$st = $pdo->prepare(
    "SELECT m.mamonhoc, m.tenmonhoc, l.ngaythi, l.buoithi
     FROM monhoc m JOIN lichthi l ON l.mamonhoc = m.mamonhoc AND l.makythi = ?
     WHERE m.lamonthi = 1 ORDER BY l.ngaythi, l.buoithi"
);
$st->execute([$kid]);
$subjects = array_map(function ($r) {
    return [
        'id'   => (int)$r['mamonhoc'],
        'name' => $r['tenmonhoc'],
        'date' => date('d/m/Y', strtotime($r['ngaythi'])),
    ];
}, $st->fetchAll());

$initJson = json_encode([
    'kythi'    => ['id' => $kid, 'year' => (int)$kythi['namtuyensinh']],
    'venues'   => $venues,
    'subjects' => $subjects,
    'maxDeduction' => MAX_DEDUCTION,
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
    <title>Stackers - Quản lý bài thi</title>
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

    <!-- 1. THANH TIÊU ĐỀ & NÚT CHỐT DỮ LIỆU -->
    <div class="flex justify-between items-center mb-6">
        <div>
            <h1 class="font-bold text-2xl mb-1">Quản lý bài thi</h1>
            <p class="text-gray-500 text-sm">
                Kỳ thi tuyển sinh năm <b><?= (int)$kythi['namtuyensinh'] ?></b> - Cập nhật trạng thái dự thi, điểm trừ vi phạm và sinh danh sách phách
            </p>
        </div>
        <div class="flex items-center gap-3">
            <span id="dirty-badge" class="hidden text-xs font-medium text-yellow-800 bg-yellow-100 border border-yellow-300 rounded-full px-3 py-1">Có thay đổi chưa lưu</span>
            <button type="button" onclick="cancelAll()" class="border bg-white text-gray-700 rounded-lg px-5 py-3 hover:bg-gray-100 text-sm font-medium">Hủy</button>
            <button type="button" id="save-btn" onclick="saveAll()" disabled class="bg-gray-900 text-white rounded-lg px-6 py-3 hover:bg-gray-800 text-sm font-medium shadow-sm disabled:opacity-50 disabled:cursor-not-allowed">Lưu tất cả</button>
        </div>
    </div>

    <!-- 2. KHUNG 1: BỘ LỌC PHẠM VI QUẢN LÝ -->
    <div class="border bg-white rounded-xl p-5 shadow-sm mb-6">
        <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
            <div>
                <label for="venue-select" class="block font-medium mb-2 text-sm text-gray-700">Điểm thi</label>
                <select id="venue-select" onchange="onVenueChange(this)" class="border rounded-lg p-3 w-full bg-white focus:ring-2 focus:ring-gray-900 outline-none text-sm">
                    <option value="">-- Chọn điểm thi --</option>
                </select>
            </div>
            <div>
                <label for="subject-select" class="block font-medium mb-2 text-sm text-gray-700">Môn thi</label>
                <select id="subject-select" onchange="onSubjectChange(this)" class="border rounded-lg p-3 w-full bg-white focus:ring-2 focus:ring-gray-900 outline-none text-sm">
                    <option value="">-- Chọn môn thi --</option>
                </select>
            </div>
            <div>
                <label for="room-select" class="block font-medium mb-2 text-sm text-gray-700">Phòng thi</label>
                <select id="room-select" disabled onchange="onRoomChange(this)" class="border rounded-lg p-3 w-full bg-white focus:ring-2 focus:ring-gray-900 outline-none text-sm disabled:bg-gray-100">
                    <option value="">-- Chọn phòng thi --</option>
                </select>
            </div>
            <div>
                <label for="search-box" class="block font-medium mb-2 text-sm text-gray-700">Tìm nhanh</label>
                <input id="search-box" type="text" autocomplete="off" disabled placeholder="SBD hoặc tên thí sinh..."
                       oninput="onSearch(this.value)"
                       class="border rounded-lg p-3 w-full bg-white focus:ring-2 focus:ring-gray-900 outline-none text-sm disabled:bg-gray-100">
            </div>
        </div>
    </div>

    <!-- 3. KHUNG 2: BẢNG CHI TIẾT BÀI THI TRONG PHÒNG -->
    <div id="empty-hint" class="border border-dashed bg-white rounded-xl p-10 text-center text-gray-400 italic mb-6">
        Vui lòng chọn Điểm thi, Môn thi và Phòng thi để xem danh sách bài thi.
    </div>

    <div id="exam-card" class="hidden border bg-white rounded-xl shadow-sm mb-6">
        <div class="p-4 border-b bg-gray-50 rounded-t-xl flex flex-wrap items-center justify-between gap-2">
            <div>
                <div class="font-bold text-gray-800" id="exam-title"></div>
                <div class="text-xs text-gray-500 mt-0.5">Tích "Vắng" thì điểm trừ được khóa về 0. Điểm trừ hợp lệ từ 0 đến <?= (int)MAX_DEDUCTION ?>.</div>
            </div>
            <div id="exam-counter" class="text-xs text-gray-500"></div>
        </div>
        <div class="overflow-x-auto">
            <table class="w-full text-left border-collapse">
                <thead>
                    <tr class="bg-gray-100 border-b text-sm text-gray-600">
                        <th class="p-3 w-14">STT</th>
                        <th class="p-3">Số báo danh</th>
                        <th class="p-3">Họ và tên thí sinh</th>
                        <th class="p-3">Số phách</th>
                        <th class="p-3">Vắng thi</th>
                        <th class="p-3 w-44">Điểm trừ vi phạm</th>
                        <th class="p-3">Ghi chú lỗi</th>
                    </tr>
                </thead>
                <tbody id="exam-body" class="text-sm"></tbody>
            </table>
        </div>
    </div>

    <!-- 4. KHUNG 3: KẾT QUẢ DANH SÁCH PHÁCH SAU KHI LƯU -->
    <div id="result-card" class="hidden border bg-white rounded-xl p-6 shadow-sm mb-8">
        <div class="bg-green-50 border border-green-200 text-green-700 p-4 rounded-lg mb-6 font-medium">
            ✅ Cập nhật thông tin và tạo danh sách phách thành công!
            <span id="result-meta" class="block text-sm font-normal text-green-700 mt-1"></span>
        </div>
        <div class="flex flex-wrap justify-between items-center gap-3 mb-4">
            <h3 class="font-bold text-lg">Danh sách bài thi hoàn chỉnh (đã sinh số phách)</h3>
            <button type="button" onclick="exportPhach()" class="bg-gray-900 text-white rounded-lg px-5 py-2.5 hover:bg-gray-800 text-sm font-medium shadow-sm">
                Xuất danh sách phách (Excel)
            </button>
        </div>
        <div class="overflow-x-auto">
            <table class="w-full text-left border-collapse">
                <thead>
                    <tr class="bg-gray-100 border-b text-sm text-gray-600">
                        <th class="p-3">Số phách</th><th class="p-3">SBD</th><th class="p-3">Họ tên thí sinh</th>
                        <th class="p-3">Điểm thi</th><th class="p-3">Môn thi</th><th class="p-3">Phòng thi</th>
                        <th class="p-3">Trạng thái vắng</th><th class="p-3">Điểm trừ</th>
                    </tr>
                </thead>
                <tbody id="result-body" class="text-sm"></tbody>
            </table>
        </div>
    </div>

</div>

<script>
    const INIT = <?= $initJson ?>;
    const HOME_URL = <?= json_encode(HOME_URL) ?>;
    const MAX_DED = INIT.maxDeduction;

    const S = {
        venue: '', subject: '', room: '',
        prev: { venue: '', subject: '', room: '' },
        students: [],      // dữ liệu đang chỉnh sửa
        original: [],      // bản đã tải từ server (để hủy)
        origSnap: '',
        loaded: false,
        query: '',
        result: null       // kết quả sau khi lưu (để xuất file)
    };

    /* ===================== TIỆN ÍCH ===================== */
    function removeAccents(str) {
        return String(str ?? '').normalize('NFC').split('')
            .map(c => c === 'đ' ? 'd' : c === 'Đ' ? 'D' : c.normalize('NFD').replace(/[\u0300-\u036f]/g, ''))
            .join('').toLowerCase();
    }
    async function callApi(payload) {
        try {
            const res = await fetch(window.location.href, {
                method: 'POST', headers: { 'Content-Type': 'application/json' }, body: JSON.stringify(payload)
            });
            return await res.json();
        } catch (e) {
            return { ok: false, message: 'Không kết nối được máy chủ hoặc phản hồi không hợp lệ.' };
        }
    }
    function snap() { return JSON.stringify(S.students.map(s => [s.id, s.absent, String(s.deduction), s.note])); }
    function isDirty() { return S.loaded && snap() !== S.origSnap; }
    function parseDed(v) {
        const t = String(v).trim().replace(',', '.');
        if (!/^\d+(\.\d{1,2})?$/.test(t)) return NaN;
        return Number(t);
    }
    function dedError(v) {
        const n = parseDed(v);
        if (isNaN(n) || n < 0 || n > MAX_DED) return 'Nhập số từ 0 đến ' + MAX_DED;
        return '';
    }
    const CONFIRM_MSG = 'Dữ liệu vừa thay đổi chưa được lưu. Bạn có chắc chắn muốn thoát?';

    /* ===================== KHỞI TẠO DROPDOWN ===================== */
    function initSelects() {
        document.getElementById('venue-select').innerHTML =
            '<option value="">-- Chọn điểm thi --</option>' +
            INIT.venues.map(v => `<option value="${v.id}">${escapeHtml(v.name)}</option>`).join('');
        document.getElementById('subject-select').innerHTML =
            '<option value="">-- Chọn môn thi --</option>' +
            INIT.subjects.map(s => `<option value="${s.id}">${escapeHtml(s.name)} (${escapeHtml(s.date)})</option>`).join('');
        if (INIT.venues.length === 0) {
            alert('Chưa có điểm thi nào được cấu hình cho kỳ thi này. Vui lòng thiết lập điểm thi và phân bổ phòng thi trước.');
        } else if (INIT.subjects.length === 0) {
            alert('Chưa có lịch thi cho kỳ thi này. Vui lòng kiểm tra lại cấu hình kỳ thi.');
        }
    }

    // Chặn đổi phạm vi khi còn dữ liệu chưa lưu (luồng 8.1)
    function guard(key, el) {
        if (isDirty() && !confirm(CONFIRM_MSG)) { el.value = S.prev[key]; return false; }
        S.prev[key] = el.value;
        S[key] = el.value;
        return true;
    }

    function clearExam() {
        S.students = []; S.original = []; S.origSnap = ''; S.loaded = false; S.query = '';
        document.getElementById('search-box').value = '';
        document.getElementById('search-box').disabled = true;
        document.getElementById('exam-card').classList.add('hidden');
        document.getElementById('empty-hint').classList.remove('hidden');
        document.getElementById('result-card').classList.add('hidden');
        updateControls();
    }

    async function onVenueChange(el) {
        if (!guard('venue', el)) return;
        const roomSel = document.getElementById('room-select');
        S.room = ''; S.prev.room = '';
        roomSel.innerHTML = '<option value="">-- Chọn phòng thi --</option>';
        roomSel.disabled = true;
        clearExam();
        if (!S.venue) return;

        const res = await callApi({ action: 'rooms', venue: Number(S.venue) });
        if (!res.ok) { alert(res.message); return; }
        if (res.rooms.length === 0) {
            alert('Chưa có dữ liệu bài thi cho điểm thi này (chưa phân bổ phòng thi). Vui lòng kiểm tra lại cấu hình kỳ thi.');
            return;
        }
        roomSel.innerHTML = '<option value="">-- Chọn phòng thi --</option>' +
            res.rooms.map(r => `<option value="${r.id}">${escapeHtml(r.name)} (${r.total} thí sinh)</option>`).join('');
        roomSel.disabled = false;
    }

    function onSubjectChange(el) {
        if (!guard('subject', el)) return;
        loadExam();
    }
    function onRoomChange(el) {
        if (!guard('room', el)) return;
        loadExam();
    }

    /* ===================== TẢI DANH SÁCH BÀI THI ===================== */
    async function loadExam() {
        clearExam();
        if (!S.venue || !S.subject || !S.room) return;

        const res = await callApi({ action: 'students', venue: Number(S.venue), subject: Number(S.subject), room: Number(S.room) });
        if (!res.ok) { alert(res.message); return; }
        if (res.students.length === 0) {
            alert('Chưa có dữ liệu bài thi cho phòng này. Vui lòng kiểm tra lại cấu hình kỳ thi.');
            return;
        }
        setStudents(res.students);
        S.meta = { venueName: res.venueName, subjectName: res.subjectName, roomName: res.roomName };
        document.getElementById('exam-title').textContent =
            `${res.roomName} - ${res.subjectName} - ${res.venueName}`;
        document.getElementById('empty-hint').classList.add('hidden');
        document.getElementById('exam-card').classList.remove('hidden');
        document.getElementById('search-box').disabled = false;
        renderTable();
        updateControls();
    }

    function setStudents(list) {
        S.students = list.map(s => Object.assign({}, s, { deduction: String(s.deduction), errMsg: '' }));
        S.original = JSON.parse(JSON.stringify(S.students));
        S.origSnap = snap();
        S.loaded = true;
    }

    /* ===================== BẢNG CHI TIẾT ===================== */
    function renderTable() {
        const kw = removeAccents(S.query.trim());
        const body = document.getElementById('exam-body');
        const rows = [];
        S.students.forEach((s, i) => {
            if (kw && !removeAccents(s.name).includes(kw) && !removeAccents(s.sbd).includes(kw)) return;
            rows.push(rowHtml(s, i));
        });

        body.innerHTML = rows.length
            ? rows.join('')
            : `<tr><td colspan="7" class="p-8 text-center text-gray-400 italic">Không tìm thấy bài thi phù hợp.</td></tr>`;

        const absent = S.students.filter(s => s.absent).length;
        document.getElementById('exam-counter').textContent =
            `Hiển thị ${rows.length}/${S.students.length} thí sinh · ${absent} vắng thi`;
    }

    function rowHtml(s, i) {
        const bad = !!s.errMsg;
        return `<tr class="border-b ${s.absent ? 'bg-gray-50' : ''}">
            <td class="p-3 text-gray-500">${i + 1}</td>
            <td class="p-3 font-semibold">${escapeHtml(s.sbd)}</td>
            <td class="p-3 font-medium">${escapeHtml(s.name)}</td>
            <td class="p-3 ${s.phach ? 'font-semibold text-gray-900' : 'text-gray-400 italic'}">${s.phach ? escapeHtml(s.phach) : 'Tự động sinh sau khi lưu'}</td>
            <td class="p-3">
                <label class="inline-flex items-center gap-2 cursor-pointer">
                    <input type="checkbox" class="w-4 h-4" ${s.absent ? 'checked' : ''} ${s.graded ? 'disabled title="Đã có điểm bài thi"' : ''} onchange="setAbsent(${i}, this.checked)"> Vắng
                </label>
            </td>
            <td class="p-3 align-top">
                <input id="ded-${i}" type="text" inputmode="decimal" value="${escapeHtml(s.deduction)}" ${s.absent ? 'disabled' : ''}
                    oninput="setDeduction(${i}, this.value)"
                    class="border rounded-lg p-2 w-28 text-sm outline-none focus:ring-1 focus:ring-gray-900 disabled:bg-gray-100 ${bad ? 'border-red-500 bg-red-50' : ''}">
                <div id="err-${i}" class="text-xs text-red-600 mt-1 ${bad ? '' : 'hidden'}">${escapeHtml(s.errMsg)}</div>
            </td>
            <td class="p-3">
                <input type="text" maxlength="200" value="${escapeHtml(s.note)}" placeholder="Nhập lỗi nếu có..."
                    oninput="setNote(${i}, this.value)"
                    class="border rounded-lg p-2 w-full text-sm outline-none focus:ring-1 focus:ring-gray-900">
            </td>
        </tr>`;
    }

    function showDedError(i) {
        const s = S.students[i];
        const input = document.getElementById('ded-' + i);
        const err = document.getElementById('err-' + i);
        if (!input || !err) return;
        const bad = !!s.errMsg;
        input.classList.toggle('border-red-500', bad);
        input.classList.toggle('bg-red-50', bad);
        err.textContent = s.errMsg;
        err.classList.toggle('hidden', !bad);
    }

    function setAbsent(i, checked) {
        const s = S.students[i];
        s.absent = checked;
        if (checked) { s.deduction = '0'; s.errMsg = ''; }
        const input = document.getElementById('ded-' + i);
        if (input) { input.disabled = checked; if (checked) input.value = '0'; }
        showDedError(i);
        updateControls();
        document.getElementById('exam-counter').textContent =
            `Hiển thị ${document.querySelectorAll('#exam-body tr').length}/${S.students.length} thí sinh · ${S.students.filter(x => x.absent).length} vắng thi`;
    }
    function setDeduction(i, v) {
        const s = S.students[i];
        s.deduction = v;
        s.errMsg = dedError(v);
        showDedError(i);
        updateControls();
    }
    function setNote(i, v) { S.students[i].note = v; updateControls(); }
    function onSearch(v) { S.query = v; renderTable(); }

    function updateControls() {
        document.getElementById('save-btn').disabled = !S.loaded;
        document.getElementById('dirty-badge').classList.toggle('hidden', !isDirty());
    }

    /* ===================== LƯU TẤT CẢ ===================== */
    async function saveAll() {
        if (!S.loaded) return;

        // Luồng 10.1: kiểm tra điểm trừ
        let firstBad = -1;
        S.students.forEach((s, i) => {
            s.errMsg = s.absent ? '' : dedError(s.deduction);
            if (s.errMsg && firstBad < 0) firstBad = i;
        });
        if (firstBad >= 0) {
            S.query = ''; document.getElementById('search-box').value = '';
            renderTable();
            const el = document.getElementById('ded-' + firstBad);
            if (el) { el.scrollIntoView({ behavior: 'smooth', block: 'center' }); el.focus(); }
            return;
        }

        const btn = document.getElementById('save-btn');
        btn.disabled = true;
        const res = await callApi({
            action: 'save',
            venue: Number(S.venue), subject: Number(S.subject), room: Number(S.room),
            items: S.students.map(s => ({
                id: s.id, absent: s.absent, deduction: s.absent ? 0 : parseDed(s.deduction), note: s.note.trim()
            }))
        });
        btn.disabled = false;

        if (!res.ok) { alert(res.message); return; }

        setStudents(res.data.students);
        renderTable();
        updateControls();
        showResult(res.data);
    }

    /* ===================== KẾT QUẢ SAU KHI LƯU ===================== */
    function showResult(d) {
        S.result = d;
        document.getElementById('result-meta').textContent =
            `Lưu lúc ${d.time} · Sinh mới ${d.newPhach} số phách.`;
        document.getElementById('result-body').innerHTML = d.students.map(s => `<tr class="border-b hover:bg-gray-50">
            <td class="p-3 font-bold text-gray-900">${s.phach ? escapeHtml(s.phach) : '<span class="text-gray-400 font-normal">—</span>'}</td>
            <td class="p-3">${escapeHtml(s.sbd)}</td>
            <td class="p-3 font-medium">${escapeHtml(s.name)}</td>
            <td class="p-3">${escapeHtml(d.venueName)}</td>
            <td class="p-3">${escapeHtml(d.subjectName)}</td>
            <td class="p-3">${escapeHtml(d.roomName)}</td>
            <td class="p-3">${s.absent ? '<span class="text-red-600 font-semibold">Vắng thi</span>' : 'Có mặt'}</td>
            <td class="p-3">${s.deduction > 0 ? `<span class="text-orange-600 font-semibold">-${s.deduction}đ</span>` : '0'}</td>
        </tr>`).join('');
        const card = document.getElementById('result-card');
        card.classList.remove('hidden');
        card.scrollIntoView({ behavior: 'smooth' });
    }

    // Xuất CSV (mở được bằng Excel, có BOM UTF-8 để không lỗi tiếng Việt)
    function exportPhach() {
        const d = S.result;
        if (!d) return;
        const cell = v => {
            let t = String(v ?? '');
            if (/^[=+\-@]/.test(t)) t = "'" + t;
            return '"' + t.replace(/"/g, '""') + '"';
        };
        const lines = [['Số phách', 'SBD', 'Họ tên thí sinh', 'Điểm thi', 'Môn thi', 'Phòng thi', 'Trạng thái vắng', 'Điểm trừ']];
        d.students.forEach(s => lines.push([
            s.phach, s.sbd, s.name, d.venueName, d.subjectName, d.roomName, s.absent ? 'Vắng thi' : 'Có mặt', s.deduction
        ]));
        const csv = '\uFEFF' + lines.map(r => r.map(cell).join(',')).join('\r\n');
        const slug = t => removeAccents(t).replace(/[^a-z0-9]+/g, '-').replace(/^-|-$/g, '');
        const a = document.createElement('a');
        a.href = URL.createObjectURL(new Blob([csv], { type: 'text/csv;charset=utf-8' }));
        a.download = `danh-sach-phach_${slug(d.venueName)}_${slug(d.subjectName)}_${slug(d.roomName)}.csv`;
        document.body.appendChild(a); a.click(); a.remove();
        setTimeout(() => URL.revokeObjectURL(a.href), 1000);
    }

    /* ===================== HỦY (8.1) ===================== */
    function cancelAll() {
        if (isDirty() && !confirm(CONFIRM_MSG)) return;
        if (S.loaded) {
            S.students = JSON.parse(JSON.stringify(S.original));
            S.students.forEach(s => s.errMsg = '');
            renderTable();
            updateControls();
        }
        if (HOME_URL) window.location.href = HOME_URL;
    }

    window.addEventListener('beforeunload', function (e) {
        if (isDirty()) { e.preventDefault(); e.returnValue = ''; }
    });

    window.onload = initSelects;
</script>

</body>
</html>