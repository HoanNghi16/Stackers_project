<?php
// Trang tra cứu danh sách điểm thi - 1 FILE DUY NHẤT

/* ===================== CẤU HÌNH ===================== */
const DB_HOST = 'localhost';
const DB_NAME = 'leonovar6_ptud';
const DB_USER = 'root';
const DB_PASS = '';

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

/* ===================== LẤY DỮ LIỆU (bước 2: hệ thống kiểm tra danh sách điểm thi) ===================== */
$hasCap = (bool)$pdo->query("SHOW COLUMNS FROM phong LIKE 'succhua'")->fetch();
$hasTdt = (bool)$pdo->query("SHOW TABLES LIKE 'truongduthi'")->fetch();

$venues = $pdo->query(
    "SELECT t.matruong, t.tentruong, t.diachi, COALESCE(p.tenphuong, '') AS tenphuong
     FROM truonghoc t
     LEFT JOIN phuong p ON p.maphuong = t.maphuong
     WHERE t.ladiemthi = 1
     ORDER BY t.tentruong"
)->fetchAll();

$ids = array_map(function ($v) { return (int)$v['matruong']; }, $venues);
$roomsByVenue = [];
$schoolsByVenue = [];

if ($ids) {
    $in = implode(',', $ids); // toàn số nguyên đã ép kiểu nên an toàn

    $capCol = $hasCap ? 'r.succhua' : 'NULL';
    $rows = $pdo->query(
        "SELECT r.matruong, r.tenphong, $capCol AS succhua,
                (SELECT COUNT(*) FROM xepphongthi x WHERE x.maphong = r.maphong) AS dacos
         FROM phong r
         WHERE r.matruong IN ($in)"
    )->fetchAll();
    foreach ($rows as $r) {
        $roomsByVenue[(int)$r['matruong']][] = [
            'name'     => $r['tenphong'],
            'capacity' => $r['succhua'] === null ? null : (int)$r['succhua'],
            'students' => (int)$r['dacos'],
        ];
    }
    foreach ($roomsByVenue as &$list) {
        usort($list, function ($a, $b) { return strnatcasecmp($a['name'], $b['name']); });
    }
    unset($list);

    if ($hasTdt) {
        $rows = $pdo->query(
            "SELECT d.madiadiemthi, t.tentruong,
                    (SELECT COUNT(*) FROM hocsinh h
                     WHERE h.matruongdanghoc = d.matruongthcs AND h.makythi = d.makythi) AS soHS
             FROM truongduthi d
             JOIN truonghoc t ON t.matruong = d.matruongthcs
             WHERE d.madiadiemthi IN ($in)
             ORDER BY t.tentruong"
        )->fetchAll();
        foreach ($rows as $r) {
            $schoolsByVenue[(int)$r['madiadiemthi']][] = [
                'name'     => $r['tentruong'],
                'students' => (int)$r['soHS'],
            ];
        }
    }
}

$locations = [];
foreach ($venues as $v) {
    $id = (int)$v['matruong'];
    $rooms = $roomsByVenue[$id] ?? [];
    $schools = $schoolsByVenue[$id] ?? [];

    $capacity = 0;
    $assigned = 0;
    $usedRooms = 0;
    foreach ($rooms as $r) {
        $capacity += (int)($r['capacity'] ?? 0);
        $assigned += $r['students'];
        if ($r['students'] > 0) $usedRooms++;
    }

    if ($assigned > 0) {
        $note = "Đã phân bổ $assigned thí sinh vào $usedRooms phòng thi";
    } elseif (count($rooms) === 0) {
        $note = "Chưa thiết lập phòng thi";
    } else {
        $note = "Chưa phân bổ thí sinh";
    }

    $locations[] = [
        'id'       => $id,
        'code'     => 'DT' . str_pad((string)$id, 2, '0', STR_PAD_LEFT),
        'name'     => $v['tentruong'],
        'address'  => $v['diachi'],
        'district' => $v['tenphuong'],
        'rooms'    => count($rooms),
        'capacity' => $capacity,
        'assigned' => $assigned,
        'note'     => $note,
        'roomList' => $rooms,
        'schools'  => $schools,
    ];
}

$locationsJson = json_encode(
    $locations,
    JSON_UNESCAPED_UNICODE | JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT
);
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
    <title>Stackers - Xem danh sách điểm thi</title>
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

<div class="ml-72 pt-24 max-w-6xl mx-auto p-8">

    <!-- TIÊU ĐỀ TRANG -->
    <div class="mb-8">
        <h1 class="font-bold text-2xl mb-1">Tra cứu danh sách Điểm thi</h1>
        <p class="text-gray-500 text-sm">Hệ thống thông tin điểm thi tuyển sinh lớp 10 </p>
    </div>

    <!-- KHU VỰC TÌM KIẾM -->
    <div id="search-section" class="border bg-white rounded-xl p-6 mb-6 shadow-sm">
        <div class="flex flex-col md:flex-row gap-4 items-center justify-between">
            <div class="w-full md:w-2/3">
                <label for="search-input" class="block font-medium mb-2 text-sm text-gray-700">Tìm kiếm điểm thi</label>
                <div class="flex gap-2">
                    <div class="relative flex-1">
                        <input type="text" id="search-input" autocomplete="off"
                               placeholder="Nhập tên trường, phường, mã điểm thi hoặc trường dự thi..."
                               class="border rounded-lg p-3 pl-10 w-full bg-white focus:ring-2 focus:ring-gray-900 outline-none text-sm"
                               oninput="handleSearch()" onkeydown="if(event.key==='Enter'){event.preventDefault();handleSearch();}">
                        <span class="absolute left-3 top-3.5 text-gray-400">🔍</span>
                    </div>
                    <button type="button" onclick="handleSearch()" class="bg-gray-900 text-white px-5 rounded-lg text-sm font-medium hover:bg-gray-800">Tìm kiếm</button>
                    <button type="button" id="clear-btn" onclick="clearSearch()" class="hidden border bg-white text-gray-700 px-4 rounded-lg text-sm font-medium hover:bg-gray-100">Xóa</button>
                </div>
            </div>
            <div class="text-sm text-gray-500 self-end md:self-center" id="count-box">
                Tổng số điểm thi: <span id="total-count" class="font-bold text-gray-900">0</span>
            </div>
        </div>
    </div>

    <!-- KHU VỰC HIỂN THỊ DANH SÁCH -->
    <div class="border bg-white rounded-xl shadow-sm overflow-hidden">
        <div class="p-4 border-b bg-gray-50 font-bold text-sm text-gray-700">
            Danh sách các điểm thi chính thức <span class="font-normal text-gray-500">(Nhấn vào một điểm thi để xem chi tiết)</span>
        </div>

        <div id="location-container" class="p-6">
            <!-- JavaScript render danh sách -->
        </div>
    </div>

</div>

<!-- MODAL XEM CHI TIẾT ĐIỂM THI -->
<div id="detail-modal" class="fixed inset-0 bg-black bg-opacity-50 hidden flex items-center justify-center z-50 p-4" onclick="if(event.target===this) closeModal()">
    <div class="bg-white rounded-2xl max-w-lg w-full p-6 shadow-xl relative max-h-[90vh] overflow-y-auto">
        <div class="flex justify-between items-center mb-4 border-b pb-3">
            <h3 class="font-bold text-lg text-gray-900" id="modal-title">Chi tiết điểm thi</h3>
            <button onclick="closeModal()" class="text-gray-400 hover:text-gray-600 font-bold text-xl">&times;</button>
        </div>
        <div class="space-y-3 text-sm text-gray-700 mb-6">
            <p><strong>Mã điểm thi:</strong> <span id="modal-id" class="text-gray-900 font-semibold"></span></p>
            <p><strong>Tên trường:</strong> <span id="modal-name" class="text-gray-900 font-semibold"></span></p>
            <p><strong>Địa chỉ:</strong> <span id="modal-address"></span></p>
            <p><strong>Khu vực:</strong> <span id="modal-district"></span></p>
            <p><strong>Số phòng thi:</strong> <span id="modal-rooms" class="bg-blue-50 text-blue-700 px-2 py-1 rounded font-medium"></span></p>
            <p><strong>Tổng sức chứa:</strong> <span id="modal-capacity"></span></p>
            <p><strong>Trường dự thi:</strong> <span id="modal-schools"></span></p>
            <p><strong>Ghi chú:</strong> <span id="modal-note" class="italic text-gray-500"></span></p>

            <div id="modal-room-wrap" class="hidden">
                <p class="font-semibold mb-2">Danh sách phòng thi:</p>
                <div class="border rounded-lg overflow-hidden max-h-48 overflow-y-auto">
                    <table class="w-full text-left text-xs">
                        <thead class="bg-gray-100 text-gray-600 sticky top-0">
                            <tr><th class="p-2">Phòng</th><th class="p-2">Sức chứa</th><th class="p-2">Thí sinh</th></tr>
                        </thead>
                        <tbody id="modal-room-body"></tbody>
                    </table>
                </div>
            </div>
        </div>
        <div class="flex justify-end">
            <button type="button" onclick="closeModal()" class="bg-gray-900 text-white px-5 py-2 rounded-lg text-sm font-medium hover:bg-gray-800">Đóng</button>
        </div>
    </div>
</div>

<script>
    // Dữ liệu lấy từ database
    const examLocations = <?= $locationsJson ?>;

    let currentData = [...examLocations];

    window.onload = function () {
        checkAndRenderInitialData();
    };

    // Bỏ dấu tiếng Việt + chữ thường để tìm kiếm không phân biệt dấu
    function removeAccents(str) {
        return String(str ?? '')
            .normalize('NFC')
            .split('')
            .map(c => c === 'đ' ? 'd' : c === 'Đ' ? 'D' : c.normalize('NFD').replace(/[\u0300-\u036f]/g, ''))
            .join('')
            .toLowerCase();
    }

    // Bước 2: kiểm tra danh sách điểm thi
    function checkAndRenderInitialData() {
        const container = document.getElementById("location-container");

        // 2.1 Không có điểm thi nào -> thông báo và thoát use case
        if (examLocations.length === 0) {
            document.getElementById("search-section").classList.add("hidden");
            container.innerHTML = `
                <div class="rounded-xl border border-yellow-200 bg-yellow-50 p-6 text-center text-yellow-800 font-medium">
                    Hiện tại sở GD chưa thiết lập điểm thi nào.
                </div>
            `;
            return;
        }

        // Bước 3: hiển thị danh sách điểm thi
        renderLocations(currentData, "");
    }

    function renderLocations(data, keyword) {
        const container = document.getElementById("location-container");
        const countBox = document.getElementById("count-box");

        countBox.innerHTML = keyword
            ? `Tìm thấy <span class="font-bold text-gray-900">${data.length}</span> / ${examLocations.length} điểm thi`
            : `Tổng số điểm thi: <span id="total-count" class="font-bold text-gray-900">${data.length}</span>`;

        // 5.1 Không có kết quả khớp -> báo "Không tìm thấy dữ liệu", quay lại bước 4
        if (data.length === 0) {
            container.innerHTML = `
                <div class="rounded-xl border border-gray-200 bg-gray-50 p-8 text-center text-gray-500">
                    <p class="font-medium text-lg text-gray-700">Không tìm thấy dữ liệu</p>
                    <p class="text-sm mt-1">Không có điểm thi nào phù hợp với từ khóa bạn vừa nhập. Vui lòng thử lại.</p>
                    <button type="button" onclick="clearSearch()" class="mt-4 border bg-white text-gray-700 px-4 py-2 rounded-lg text-sm font-medium hover:bg-gray-100">Xóa tìm kiếm</button>
                </div>
            `;
            return;
        }

        let html = '<div class="grid grid-cols-1 md:grid-cols-2 gap-4">';
        data.forEach(item => {
            html += `
                <div onclick="viewDetail(${item.id})" class="border rounded-xl p-5 hover:shadow-md transition bg-white flex flex-col justify-between cursor-pointer hover:border-gray-400">
                    <div>
                        <div class="flex justify-between items-start mb-2">
                            <span class="font-bold text-lg text-gray-900">${escapeHtml(item.name)}</span>
                            <span class="bg-gray-100 text-gray-700 text-xs px-2.5 py-1 rounded-full font-semibold">${escapeHtml(item.code)}</span>
                        </div>
                        <p class="text-sm text-gray-600 mb-3 flex items-center gap-2">
                            <span>📍</span> ${escapeHtml(item.address)}
                        </p>
                    </div>
                    <div class="border-t pt-3 flex justify-between items-center text-xs text-gray-500">
                        <span>Khu vực: <strong>${escapeHtml(item.district)}</strong></span>
                        <div class="flex items-center gap-2">
                            <span class="bg-blue-50 text-blue-700 px-2.5 py-1 rounded font-medium">${item.rooms} Phòng thi</span>
                            <span class="text-gray-900 font-semibold underline">Xem chi tiết &rarr;</span>
                        </div>
                    </div>
                </div>
            `;
        });
        html += '</div>';

        container.innerHTML = html;
    }

    // Mở modal xem chi tiết
    function viewDetail(id) {
        const item = examLocations.find(s => s.id === id);
        if (!item) return;

        document.getElementById("modal-id").innerText = item.code;
        document.getElementById("modal-name").innerText = item.name;
        document.getElementById("modal-address").innerText = item.address;
        document.getElementById("modal-district").innerText = item.district || "Chưa cập nhật";
        document.getElementById("modal-rooms").innerText = item.rooms + " Phòng thi";
        document.getElementById("modal-capacity").innerText = item.capacity > 0 ? item.capacity + " thí sinh" : "Chưa cập nhật";
        document.getElementById("modal-schools").innerText = item.schools.length
            ? item.schools.map(s => s.name + " (" + s.students + " HS)").join(", ")
            : "Chưa có trường dự thi";
        document.getElementById("modal-note").innerText = item.note || "Không có ghi chú";

        const wrap = document.getElementById("modal-room-wrap");
        if (item.roomList.length > 0) {
            document.getElementById("modal-room-body").innerHTML = item.roomList.map(r => `
                <tr class="border-b">
                    <td class="p-2 font-medium">${escapeHtml(r.name)}</td>
                    <td class="p-2">${r.capacity === null ? '-' : r.capacity}</td>
                    <td class="p-2">${r.students}</td>
                </tr>`).join('');
            wrap.classList.remove("hidden");
        } else {
            wrap.classList.add("hidden");
        }

        document.getElementById("detail-modal").classList.remove("hidden");
    }

    function closeModal() {
        document.getElementById("detail-modal").classList.add("hidden");
    }

    document.addEventListener("keydown", function (e) {
        if (e.key === "Escape") closeModal();
    });

    // Bước 5 - 6: tìm kiếm theo thông tin nhập, khớp tất cả các từ, không phân biệt dấu
    function handleSearch() {
        const raw = document.getElementById("search-input").value;
        const keyword = removeAccents(raw.trim());
        document.getElementById("clear-btn").classList.toggle("hidden", !keyword);

        if (!keyword) {
            currentData = [...examLocations];
            renderLocations(currentData, "");
            return;
        }

        const tokens = keyword.split(/\s+/).filter(Boolean);
        currentData = examLocations.filter(item => {
            const haystack = removeAccents([
                item.name, item.address, item.district, item.code,
                item.schools.map(s => s.name).join(' ')
            ].join(' '));
            return tokens.every(t => haystack.includes(t));
        });

        renderLocations(currentData, keyword);
    }

    // Quay lại bước 4: xóa từ khóa và đưa con trỏ về ô nhập
    function clearSearch() {
        const input = document.getElementById("search-input");
        input.value = "";
        handleSearch();
        input.focus();
    }
</script>

</body>
</html>