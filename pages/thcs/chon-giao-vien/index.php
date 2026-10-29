<?php
require_once __DIR__ . '/../../../class/chon-giao-vien-bootstrap.php';
if (session_status() !== PHP_SESSION_ACTIVE) session_start();
if (!isset($_SESSION['phat_teacher_csrf'])) $_SESSION['phat_teacher_csrf'] = bin2hex(random_bytes(32));

if (isset($_GET['ajax'])) {
    header('Content-Type: application/json; charset=utf-8');
    header('Cache-Control: no-store');
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        http_response_code(405);
        echo json_encode(['ok' => false, 'error' => 'Yêu cầu không hợp lệ. Vui lòng tải lại trang rồi thử lại.']); exit;
    }
    $sentCsrf = $_SERVER['HTTP_X_CSRF_TOKEN'] ?? '';
    if (!is_string($sentCsrf) || !hash_equals($_SESSION['phat_teacher_csrf'], $sentCsrf)) {
        http_response_code(403);
        echo json_encode(['ok' => false, 'error' => 'Phiên làm việc đã hết hạn. Vui lòng tải lại trang rồi thử lại.']); exit;
    }
    try {
        $service = phatCreateTeacherService();
        $action = (string)($_POST['action'] ?? '');
        if ($action === 'import') {
            $pendingCodes = json_decode((string)($_POST['codes'] ?? '[]'), true, 512, JSON_THROW_ON_ERROR);
            if (!is_array($pendingCodes)) throw new InvalidArgumentException('Danh sách đang chọn không hợp lệ. Vui lòng tải lại trang rồi thử lại.');
            $data = $service->importXlsx($_FILES['excel'] ?? [], $pendingCodes);
        } elseif ($action === 'save' || $action === 'submit') {
            $raw = (string)($_POST['codes'] ?? '[]');
            $codes = json_decode($raw, true, 512, JSON_THROW_ON_ERROR);
            if (!is_array($codes)) throw new InvalidArgumentException('Danh sách giáo viên không hợp lệ. Vui lòng tải lại trang rồi thử lại.');
            $data = $service->save($codes, $action === 'submit');
        } elseif ($action === 'reload') {
            $data = $service->state();
        } else {
            throw new InvalidArgumentException('Không thực hiện được thao tác này. Vui lòng tải lại trang rồi thử lại.');
        }
        echo json_encode(['ok' => true, 'data' => $data], JSON_UNESCAPED_UNICODE | JSON_INVALID_UTF8_SUBSTITUTE);
    } catch (DomainException | InvalidArgumentException | JsonException $e) {
        http_response_code(422);
        echo json_encode(['ok' => false, 'error' => $e->getMessage()], JSON_UNESCAPED_UNICODE);
    } catch (Throwable $e) {
        error_log('[chon-giao-vien] ' . $e->getMessage());
        http_response_code(500);
        echo json_encode(['ok' => false, 'error' => 'Chưa thể thực hiện thao tác này. Vui lòng thử lại sau hoặc liên hệ quản trị viên.'], JSON_UNESCAPED_UNICODE);
    }
    exit;
}

require_once __DIR__ . '/../../../components/layout/header.php';
require_once __DIR__ . '/../../../components/layout/sidebar.php';
$teachers = []; $selectedCodes = []; $quota = 0; $requiredCount = 0; $nominatedCount = 0; $stateWarnings = []; $currentStatus = 'NEW'; $pageError = null;
try {
    $state = phatCreateTeacherService()->state();
    $teachers = $state['teachers'];
    $selectedCodes = $state['selected'];
    $quota = $state['quota'];
    $requiredCount = $state['required_count'];
    $nominatedCount = $state['nominated_count'];
    $stateWarnings = $state['warnings'];
    $currentStatus = $state['status'];
} catch (Throwable $e) {
    error_log('[chon-giao-vien-page] ' . $e->getMessage());
    $pageError = $e instanceof DomainException ? $e->getMessage() : 'Không tải được danh sách giáo viên. Vui lòng tải lại trang hoặc liên hệ quản trị viên.';
}
$subjects = array_unique(array_column($teachers, 'subject'));
sort($subjects);
?>

<!doctype html>
<html lang="vi">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width,initial-scale=1">
    <title>Đề cử giáo viên tham gia hội đồng thi</title>

    <link rel="stylesheet" href="../../../assets/css/phat-tailwind.css">
    <style>
        /* Luon giu cac thong ke gon, ke ca khi viewport hep ben canh sidebar. */
        .gv-summary {display:grid; grid-template-columns:repeat(2,minmax(0,1fr)); gap:10px}
        .gv-summary > div {min-width:0}
        @media (min-width:1200px) {.gv-summary {grid-template-columns:repeat(4,minmax(0,1fr))}}
        .gv-summary .gv-number {font-size:24px; font-weight:800; line-height:1.25}
        .gv-summary .gv-label {font-size:13px; line-height:1.3}
        .gv-summary .gv-extra {font-size:12px; line-height:1.35}
        .gv-action:disabled {opacity:.55;cursor:not-allowed}
    </style>
</head>

<body class="m-0 bg-[#f5f7fb] font-sans text-[#1f2937]">

    <?php renderHeader(); ?>
    <?php renderSidebar(); ?>

    <div class="min-h-screen bg-slate-50 pl-72 pt-20">

        <main class="mx-auto mt-7 max-w-[1180px] px-[18px] pb-12">

            <h1 class="mb-1.5 text-[30px] font-bold leading-tight">Đề cử giáo viên tham gia hội đồng thi</h1>
            <p class="mb-6 text-gray-500">Nhà trường lựa chọn giáo viên để gửi đề cử lên Sở GD&amp;ĐT. Việc phân công gác thi sẽ do Sở quyết định.</p>

            <?php if ($pageError): ?>
                <div class="mb-[18px] rounded-[10px] border border-red-200 bg-red-50 px-[15px] py-[13px] font-semibold text-red-800"><?= htmlspecialchars($pageError, ENT_QUOTES, 'UTF-8') ?></div>
            <?php endif; ?>

            <!-- Mot hang thong ke gon: 2 cot tren man hinh hep, 4 cot tren desktop. -->
            <div class="gv-summary mb-4">
                <div class="rounded-xl border border-gray-200 bg-white px-3 py-2.5">
                    <div class="gv-label text-slate-500">Chỉ tiêu chính thức</div>
                    <div class="gv-number mt-1 text-slate-900"><?= $quota > 0 ? $quota : '—' ?></div>
                </div>
                <div class="rounded-xl border border-blue-200 bg-blue-50 px-3 py-2.5">
                    <div class="gv-label text-blue-800">Số giáo viên cần đề cử</div>
                    <div class="gv-number mt-1 text-blue-900"><?= $requiredCount ?: '—' ?></div>
                </div>
                <div class="rounded-xl border border-gray-200 bg-white px-3 py-2.5">
                    <div class="gv-label text-slate-500">Số giáo viên đã chọn</div>
                    <div class="gv-number mt-1 text-slate-900"><span id="selectedCount"><?= $nominatedCount + count($selectedCodes) ?></span><span class="text-base font-normal text-gray-500"> / <?= $requiredCount ?: '—' ?></span></div>
                    <div id="quotaProgress" class="gv-extra text-slate-500"></div>
                </div>
                <div class="rounded-xl border border-gray-200 bg-white px-3 py-2.5">
                    <div class="gv-label text-slate-500">Tình trạng danh sách</div>
                    <div class="mt-2"><span id="statusBadge" class="inline-block rounded-full bg-gray-200 px-[9px] py-[5px] text-xs font-extrabold text-gray-700">Chưa lưu</span></div>
                </div>
            </div>

            <div class="mb-[18px] rounded-[14px] border border-gray-200 bg-white p-5 shadow-[0_10px_25px_rgba(15,23,42,0.08)]">
                <h2 class="mb-2 text-2xl font-bold">1. Danh sách giáo viên đề cử lên Sở GD&amp;ĐT</h2>
                <p class="mb-3 text-sm text-slate-500">Đã gửi lên Sở: <b id="nominatedCount">0</b> giáo viên · Chờ gửi: <b id="newCount">0</b> giáo viên. Danh sách gồm chỉ tiêu chính thức và 3 giáo viên dự bị.</p>

                <div id="quotaAlert" class="my-3 rounded-[10px] border px-[15px] py-[13px] font-semibold hidden"></div>

                <div class="overflow-x-auto">
                    <table class="w-full border-collapse">
                        <thead>
                            <tr>
                                <th class="border-b border-gray-200 px-2.5 py-3 text-left align-top bg-slate-50 text-[13px] font-bold uppercase text-slate-500">Mã GV</th>
                                <th class="border-b border-gray-200 px-2.5 py-3 text-left align-top bg-slate-50 text-[13px] font-bold uppercase text-slate-500">Họ tên</th>
                                <th class="border-b border-gray-200 px-2.5 py-3 text-left align-top bg-slate-50 text-[13px] font-bold uppercase text-slate-500">Bộ môn</th>
                                <th class="border-b border-gray-200 px-2.5 py-3 text-left align-top bg-slate-50 text-[13px] font-bold uppercase text-slate-500">Trạng thái</th>
                                <th class="border-b border-gray-200 px-2.5 py-3 text-left align-top bg-slate-50 text-[13px] font-bold uppercase text-slate-500"></th>
                            </tr>
                        </thead>

                        <tbody id="selectedBody">
                            <tr>
                                <td colspan="5" class="border-b border-gray-200 px-2.5 py-3 text-left align-top px-8 py-8 text-center text-gray-500">
                                    Chưa có giáo viên nào được chọn.
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>

                <div class="mt-3.5 flex flex-wrap gap-2.5">
                    <button class="inline-flex cursor-pointer items-center justify-center gap-2 rounded-[9px] px-[15px] py-2.5 font-bold bg-slate-200 text-slate-900" type="button" id="saveButton" onclick="saveDraft()" <?= $pageError ? 'disabled' : '' ?>>
                        Lưu bản nháp
                    </button>

                    <button class="inline-flex cursor-pointer items-center justify-center gap-2 rounded-[9px] px-[15px] py-2.5 font-bold bg-green-600 text-white" type="button" id="sendButton" onclick="sendList()" <?= $pageError || $quota < 1 ? 'disabled' : '' ?>>
                        Gửi danh sách
                    </button>
                </div>
            </div>

            <div class="mb-[18px] rounded-[14px] border border-gray-200 bg-white p-5 shadow-[0_10px_25px_rgba(15,23,42,0.08)]">
                <h2 class="mb-4 text-2xl font-bold">2. Bổ sung giáo viên vào danh sách</h2>

                <div class="mb-4 flex flex-wrap gap-2">
                    <button type="button" class="inline-flex cursor-pointer items-center justify-center gap-2 rounded-[9px] px-[15px] py-2.5 font-bold tab-btn bg-slate-200 text-slate-700 [&.active]:bg-blue-600 [&.active]:text-white active" id="manualTab"
                        onclick="switchMethod('manual')">
                        Chọn từ danh sách
                    </button>

                    <button type="button" class="inline-flex cursor-pointer items-center justify-center gap-2 rounded-[9px] px-[15px] py-2.5 font-bold tab-btn bg-slate-200 text-slate-700 [&.active]:bg-blue-600 [&.active]:text-white" id="excelTab" onclick="switchMethod('excel')">
                        Nhập danh sách từ Excel
                    </button>
                </div>

                <div id="manualPanel">
                    <p class="mb-3 text-sm text-blue-800">Bạn có thể chọn giáo viên của trường ở danh sách bên dưới. Giáo viên mới chọn sẽ chỉ được gửi lên Sở khi bạn nhấn <b>Gửi danh sách</b>.</p>

                    <div class="grid grid-cols-12 gap-4">
                        <div class="col-span-12 min-[801px]:col-span-6">
                            <input class="w-full rounded-[9px] border border-slate-300 bg-white px-3 py-2.5 text-[#1f2937] outline-none focus:border-blue-600 focus:ring-[3px] focus:ring-blue-600/10" id="teacherSearch" placeholder="Tìm theo tên/mã giáo viên..."
                                oninput="filterTeachers()">
                        </div>

                        <div class="col-span-12 min-[801px]:col-span-6">
                            <select class="w-full rounded-[9px] border border-slate-300 bg-white px-3 py-2.5 text-[#1f2937] outline-none focus:border-blue-600 focus:ring-[3px] focus:ring-blue-600/10" id="subjectFilter" onchange="filterTeachers()">
                                <option value="">Tất cả bộ môn</option>
                                <?php foreach ($subjects as $subject): ?>
                                    <option value="<?= htmlspecialchars($subject, ENT_QUOTES, 'UTF-8') ?>"><?= htmlspecialchars($subject, ENT_QUOTES, 'UTF-8') ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                    </div>

                </div>

                <div id="excelPanel" class="hidden">
                    <label class="mb-[7px] block font-[650]">Chọn tệp Excel</label>

                    <input class="w-full rounded-[9px] border border-slate-300 bg-white px-3 py-2.5 text-[#1f2937] outline-none focus:border-blue-600 focus:ring-[3px] focus:ring-blue-600/10" id="excelFile" type="file" accept=".xlsx">

                    <p class="text-gray-500">
                        Tệp Excel cần có tiêu đề <strong>Mã GV</strong> tại ô A1 và mã giáo viên từ dòng 2. Nếu có cột <strong>Họ và tên</strong>, thông tin phải khớp với hồ sơ của trường. Giáo viên hợp lệ sẽ được bổ sung vào danh sách đã chọn; các lựa chọn trước đó vẫn được giữ nguyên.
                    </p>

                    <div class="mt-3.5 flex flex-wrap gap-2.5">
                        <button class="inline-flex cursor-pointer items-center justify-center gap-2 rounded-[9px] px-[15px] py-2.5 font-bold bg-blue-600 text-white hover:bg-blue-700" type="button" onclick="simulateExcel()" <?= $pageError ? 'disabled' : '' ?>>
                            Kiểm tra và thêm giáo viên
                        </button>
                    </div>

                    <div id="excelAlert" class="my-3 rounded-[10px] border px-[15px] py-[13px] font-semibold hidden"></div>
                    <div id="excelResult"></div>
                </div>
            </div>

            <div class="mb-[18px] rounded-[14px] border border-gray-200 bg-white p-5 shadow-[0_10px_25px_rgba(15,23,42,0.08)]">
                <h2 class="mb-2 text-2xl font-bold">3. Danh sách giáo viên còn lại (<span id="availableCount">0</span>)</h2>
                <p class="mb-3 text-sm text-slate-500">Đây là các giáo viên còn lại của trường. Bạn có thể chọn những người đủ điều kiện để bổ sung vào danh sách đề cử.</p>
                <div class="overflow-x-auto">
                    <table class="w-full border-collapse">
                        <thead><tr>
                            <th class="bg-slate-50 px-3 py-3 text-left text-xs font-bold uppercase text-slate-500">Thao tác</th>
                            <th class="bg-slate-50 px-3 py-3 text-left text-xs font-bold uppercase text-slate-500">Mã GV</th>
                            <th class="bg-slate-50 px-3 py-3 text-left text-xs font-bold uppercase text-slate-500">Họ tên</th>
                            <th class="bg-slate-50 px-3 py-3 text-left text-xs font-bold uppercase text-slate-500">Bộ môn</th>
                            <th class="bg-slate-50 px-3 py-3 text-left text-xs font-bold uppercase text-slate-500">Điều kiện</th>
                        </tr></thead>
                        <tbody id="teacherBody"></tbody>
                    </table>
                </div>
            </div>

        </main>

    </div>

    <div id="sendModal" class="modal fixed inset-0 z-[1000] hidden items-center justify-center bg-slate-900/55 p-[18px]">
        <div class="w-full max-w-[620px] rounded-[14px] bg-white p-5 shadow-[0_20px_60px_rgba(0,0,0,0.2)]">
            <div class="mb-3 flex items-center justify-between gap-4">
                <h2 class="m-0 text-2xl font-bold">Xác nhận gửi danh sách</h2>

                <button class="cursor-pointer rounded-lg border-0 bg-slate-200 px-2.5 py-[7px]" type="button" onclick="closeModal('sendModal')">✕</button>
            </div>

            <p>
                Bạn có chắc chắn muốn gửi danh sách giáo viên đã chọn lên <b>Sở GD&amp;ĐT</b>?
                Sau khi gửi thành công, các giáo viên này sẽ được ghi nhận là <b>đã đề cử</b>.
                Sở GD&amp;ĐT sẽ xem xét và quyết định phân công gác thi.
                Bạn có thể chọn <b>Quay lại</b> nếu cần kiểm tra danh sách trước khi gửi.
            </p>

            <div class="mt-3.5 flex flex-wrap gap-2.5">
                <button class="inline-flex cursor-pointer items-center justify-center gap-2 rounded-[9px] px-[15px] py-2.5 font-bold bg-slate-200 text-slate-900" type="button" onclick="closeModal('sendModal')">
                    Quay lại
                </button>

                <button class="gv-action inline-flex cursor-pointer items-center justify-center gap-2 rounded-[9px] px-[15px] py-2.5 font-bold bg-green-600 text-white" type="button" id="confirmSendButton" onclick="confirmSend()">
                    <span id="confirmSendLabel">Xác nhận gửi</span>
                </button>
            </div>
            <p id="sendingHint" class="mt-3 hidden text-sm font-medium text-blue-700" role="status" aria-live="polite">Đang gửi danh sách lên Sở GD&amp;ĐT. Vui lòng không nhấn nút Gửi nhiều lần.</p>
        </div>
    </div>

    <script src="../../../assets/js/usecase-tailwind.js"></script>

    <script>
        const csrf = <?= json_encode($_SESSION['phat_teacher_csrf']) ?>;
        const quota = <?= (int)$quota ?>;
        const required = quota > 0 ? quota + 3 : 0;
        let teacherCatalog = <?= json_encode($teachers, JSON_UNESCAPED_UNICODE | JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_AMP | JSON_HEX_QUOT) ?>;
        let pending = [];
        const draftCodes = <?= json_encode($selectedCodes, JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_AMP | JSON_HEX_QUOT) ?>;
        let status = <?= json_encode($currentStatus) ?>;
        let sending = false;
        const initWarnings = <?= json_encode($stateWarnings, JSON_UNESCAPED_UNICODE | JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_AMP | JSON_HEX_QUOT) ?>;
        let byCode = new Map();
        const esc = v => String(v ?? '').replace(/[&<>"']/g, c => ({'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#39;'}[c]));
        const tdClass = 'border-b border-gray-200 px-2.5 py-3 text-left align-top';

        function refreshMap() {
            byCode = new Map(teacherCatalog.map(t => [String(t.code), t]));
            pending = pending.filter(t => byCode.get(String(t.code))?.eligible)
                .map(t => byCode.get(String(t.code)));
        }
        refreshMap();
        pending = draftCodes.map(code => byCode.get(String(code))).filter(t => t?.eligible);

        const nominatedTeachers = () => teacherCatalog.filter(t => t.nominated);
        const selectedTeachers = () => {
            const present = nominatedTeachers();
            const nominatedCodes = new Set(present.map(t => String(t.code)));
            return [...present, ...pending.filter(t => !nominatedCodes.has(String(t.code)))];
        };
        const totalSelected = () => selectedTeachers().length;

        async function request(action, fields = {}, file = null) {
            const body = new FormData();
            body.set('action', action);
            Object.entries(fields).forEach(([key, value]) => body.set(key, value));
            if (file) body.set('excel', file);
            const controller = new AbortController();
            // Khong de giao dien cho vo han khi host/database mat ket noi.
            // Rieng submit co the tiep tuc xu ly tren server du client het han.
            const timer = setTimeout(() => controller.abort(), action === 'submit' ? 45000 : 25000);
            try {
                const response = await fetch('?ajax=1', {
                    method: 'POST', credentials: 'same-origin', signal: controller.signal,
                    headers: {'X-CSRF-Token': csrf}, body
                });
                let result;
                try { result = await response.json(); }
                catch { throw new Error('Không nhận được phản hồi từ hệ thống. Vui lòng thử lại hoặc liên hệ quản trị viên.'); }
                if (!response.ok || !result.ok) throw new Error(result.error || 'Có sự cố xảy ra. Vui lòng thử lại.');
                return result.data;
            } catch (error) {
                if (error.name === 'AbortError') throw new Error('Kết nối bị gián đoạn. Vui lòng tải lại trang để kiểm tra kết quả trước khi thực hiện lại.');
                throw error;
            } finally {
                clearTimeout(timer);
            }
        }

        function switchMethod(method) {
            const manual = method === 'manual';
            document.getElementById('manualPanel').classList.toggle('hidden', !manual);
            document.getElementById('excelPanel').classList.toggle('hidden', manual);
            document.getElementById('manualTab').classList.toggle('active', manual);
            document.getElementById('excelTab').classList.toggle('active', !manual);
        }

        function renderAvailable() {
            const selectedCodes = new Set(selectedTeachers().map(t => String(t.code)));
            const keyword = document.getElementById('teacherSearch').value.trim().toLocaleLowerCase('vi');
            const subject = document.getElementById('subjectFilter').value;
            const remaining = teacherCatalog.filter(t => !selectedCodes.has(String(t.code)));
            document.getElementById('availableCount').textContent = String(remaining.length);
            const shown = remaining.filter(t =>
                (`${t.name} ${t.code}`).toLocaleLowerCase('vi').includes(keyword)
                && (!subject || t.subject === subject)
            );
            const full = required > 0 && totalSelected() >= required;
            const body = document.getElementById('teacherBody');
            body.innerHTML = shown.length ? shown.map(t => `
                <tr>
                    <td class="${tdClass}">
                        <button type="button" class="rounded-lg px-3 py-1.5 text-sm font-bold ${t.eligible && !full ? 'bg-blue-600 text-white hover:bg-blue-700' : 'bg-slate-200 text-slate-500'}"
                            data-code="${esc(t.code)}" onclick="addTeacher(this.dataset.code)"
                            ${!t.eligible || full || required < 1 ? 'disabled' : ''}>Chọn</button>
                    </td>
                    <td class="${tdClass}">${esc(t.code)}</td>
                    <td class="${tdClass}"><b>${esc(t.name)}</b></td>
                    <td class="${tdClass}">${esc(t.subject)}</td>
                    <td class="${tdClass}">${t.eligible
                        ? '<span class="rounded-full bg-green-100 px-2 py-1 text-xs font-bold text-green-800">Đủ điều kiện</span>'
                        : `<span class="rounded-full bg-red-100 px-2 py-1 text-xs font-bold text-red-800">${esc(t.eligibility_reason || 'Chưa đủ điều kiện')}</span>`}</td>
                </tr>`).join('') : `<tr><td colspan="5" class="${tdClass} py-7 text-center text-slate-500">Không tìm thấy giáo viên phù hợp với từ khóa hoặc bộ môn đã chọn.</td></tr>`;
        }

        function renderSelected() {
            const chosen = selectedTeachers();
            const body = document.getElementById('selectedBody');
            body.innerHTML = chosen.length ? chosen.map(t => `
                <tr>
                    <td class="${tdClass}">${esc(t.code)}</td>
                    <td class="${tdClass}"><b>${esc(t.name)}</b></td>
                    <td class="${tdClass}">${esc(t.subject)}</td>
                    <td class="${tdClass}">${t.nominated
                        ? '<span class="rounded-full bg-blue-100 px-2 py-1 text-xs font-bold text-blue-800">Đã gửi lên Sở</span>'
                        : '<span class="rounded-full bg-amber-100 px-2 py-1 text-xs font-bold text-amber-800">Chờ gửi</span>'}</td>
                    <td class="${tdClass}">${t.nominated ? '<span class="text-xs text-slate-500">Đã gửi</span>' :
                        `<button type="button" class="rounded-lg border border-slate-300 bg-white px-3 py-1.5 font-bold" data-code="${esc(t.code)}" onclick="removeTeacher(this.dataset.code)">Bỏ chọn</button>`}</td>
                </tr>`).join('') : `<tr><td colspan="5" class="${tdClass} py-8 text-center text-gray-500">Chưa có giáo viên nào được đề cử.</td></tr>`;
            document.getElementById('selectedCount').textContent = String(chosen.length);
            document.getElementById('newCount').textContent = String(pending.length);
            document.getElementById('nominatedCount').textContent = String(nominatedTeachers().length);
            const progress = document.getElementById('quotaProgress');
            progress.textContent = required > 0 ? (chosen.length >= required ? 'Đã đủ số lượng' : `Còn thiếu ${required - chosen.length} giáo viên`) : 'Chưa có chỉ tiêu đề cử';
            if (required > 0 && chosen.length > required) {
                showAlert('quotaAlert','danger',`Nhà trường đã đề cử ${nominatedTeachers().length} giáo viên; tổng danh sách hiện là ${chosen.length}, vượt số lượng cho phép (${required} giáo viên, gồm 3 dự bị). Vui lòng kiểm tra lại danh sách đã gửi.`);
            }
            document.getElementById('sendButton').disabled = sending || !pending.length || !required || chosen.length !== required;
            document.getElementById('saveButton').disabled = sending || !required || chosen.length > required;
            setStatusBadge();
        }

        function renderAll() { renderSelected(); renderAvailable(); }
        function filterTeachers() { renderAvailable(); }

        function addTeacher(code) {
            if (sending) return;
            const teacher = byCode.get(String(code));
            if (!teacher) return showAlert('quotaAlert','danger',`Không tìm thấy giáo viên mã ${code} trong danh sách của trường.`);
            if (!teacher.eligible) return showAlert('quotaAlert','danger',`${teacher.name}: ${teacher.eligibility_reason}.`);
            if (required < 1) return showAlert('quotaAlert','danger','Nhà trường chưa được giao chỉ tiêu đề cử cho kỳ thi này.');
            if (totalSelected() >= required) return showAlert('quotaAlert','danger',`Danh sách đã đủ ${required} giáo viên (bao gồm 3 giáo viên dự bị). Vui lòng bỏ chọn một người trước khi thêm giáo viên khác.`);
            if (!pending.some(t => String(t.code) === String(code))) pending.push(teacher);
            clearAlert('quotaAlert');
            if (status === 'DRAFT') status = 'NEW';
            renderAll();
        }

        function removeTeacher(code) {
            if (sending) return;
            pending = pending.filter(t => String(t.code) !== String(code));
            if (status === 'DRAFT') status = 'NEW';
            clearAlert('quotaAlert');
            renderAll();
        }

        async function simulateExcel() {
            if (sending) return;
            clearAlert('excelAlert');
            const file = document.getElementById('excelFile').files[0];
            if (!file) return showAlert('excelAlert', 'warning', 'Vui lòng chọn một tệp Excel (.xlsx).');
            try {
                const r = await request('import', {codes: JSON.stringify(pending.map(t => String(t.code)))}, file);
                const errors = Array.isArray(r.errors) ? r.errors : [];
                const skipped = Array.isArray(r.skipped) ? r.skipped : [];
                const renderNotices = () => `<ul class="mt-2 list-disc pl-5">${[...errors,...skipped].map(e=>`<li class="mb-1">${esc(e)}</li>`).join('')}</ul>`;
                if (errors.length) {
                    document.getElementById('excelResult').innerHTML = `<div class="rounded-lg border border-red-200 bg-red-50 p-3 text-red-900"><b>${errors.length} vấn đề cần sửa – chưa thêm giáo viên nào:</b>${renderNotices()}</div>`;
                    return showAlert('excelAlert','danger','Vui lòng chỉnh sửa tệp Excel rồi thử lại. Danh sách bạn đã chọn vẫn được giữ nguyên.');
                }
                const live = await request('reload');
                teacherCatalog = Array.isArray(live.teachers) ? live.teachers : teacherCatalog;
                refreshMap();
                const newOnes = (r.codes || []).map(code => byCode.get(String(code)));
                if (newOnes.some(t => !t || !t.eligible)) throw new Error('Danh sách giáo viên vừa có thay đổi. Vui lòng tải lại trang rồi thực hiện lại.');
                const pendingCodes = new Set(pending.map(t => String(t.code)));
                for (const t of newOnes) if (!pendingCodes.has(String(t.code))) { pending.push(t); pendingCodes.add(String(t.code)); }
                if (status === 'DRAFT') status = 'NEW';
                renderAll();
                document.getElementById('excelResult').innerHTML = skipped.length
                    ? `<div class="rounded-lg border border-amber-200 bg-amber-50 p-3">${esc(r.valid_count)} giáo viên mới; ${skipped.length} giáo viên đã có trong danh sách.${renderNotices()}</div>`
                    : `<p class="py-2 text-green-800">Đã kiểm tra ${r.total_rows} dòng và thêm ${r.valid_count} giáo viên vào danh sách chờ gửi.</p>`;
                showAlert('excelAlert','success',`Đã thêm ${r.valid_count} giáo viên. Hiện có ${totalSelected()}/${required} giáo viên trong danh sách đề cử.`);
            } catch (e) { showAlert('excelAlert','danger',e.message); }
        }

        function setStatusBadge() {
            const badge = document.getElementById('statusBadge');
            const completed = required > 0 && nominatedTeachers().length === required;
            const displayed = completed ? 'Đã gửi đủ' : pending.length ? (status === 'DRAFT' ? 'Bản nháp' : 'Đang lập danh sách') : (nominatedTeachers().length ? 'Đã gửi một phần' : 'Chưa lưu');
            badge.textContent = displayed;
            badge.className = 'inline-block rounded-full px-[9px] py-[5px] text-xs font-extrabold ' +
                (completed ? 'bg-blue-100 text-blue-800' : pending.length ? 'bg-amber-100 text-amber-800' : 'bg-gray-200 text-gray-700');
        }

        function checkQuota() {
            if (required < 1 || totalSelected() !== required) {
                showAlert('quotaAlert','danger',`Cần chọn đúng ${required} giáo viên (gồm 3 dự bị) trước khi gửi. Hiện danh sách có ${totalSelected()} giáo viên.`);
                return false;
            }
            if (!pending.length) {
                showAlert('quotaAlert','warning','Chưa có giáo viên mới được chọn để gửi lên Sở.');
                return false;
            }
            clearAlert('quotaAlert'); return true;
        }

        async function saveDraft() {
            if (sending) return;
            try {
                const r = await request('save', {codes: JSON.stringify(pending.map(t => String(t.code)))});
                status = r.status;
                renderAll();
                showAlert('quotaAlert','success',`Đã lưu danh sách tạm gồm ${pending.length} giáo viên mới. Tổng cộng ${totalSelected()}/${required} giáo viên. Danh sách mới chọn chưa được gửi lên Sở.`);
            } catch (e) { showAlert('quotaAlert','danger',e.message); }
        }

        function sendList() { if (!sending && checkQuota()) openModal('sendModal'); }

        function setSending(value) {
            sending = value;
            document.getElementById('sendingHint').classList.toggle('hidden', !value);
            document.getElementById('confirmSendLabel').textContent = value ? 'Đang gửi...' : 'Xác nhận gửi';
            document.querySelectorAll('#sendModal button').forEach(button => { button.disabled = value; });
            renderSelected();
        }

        async function confirmSend() {
            if (sending || !checkQuota()) return; // Chan click lap va gui trung.
            setSending(true);
            let saved = false;
            try {
                const r = await request('submit', {codes: JSON.stringify(pending.map(t => String(t.code)))});
                saved = true;
                // Backend tra lai danh sach sau commit, khong phai goi AJAX reload rieng.
                let fresh = r.state;
                if (!fresh || !Array.isArray(fresh.teachers)) {
                    try { fresh = await request('reload'); }
                    catch (refreshError) {
                        closeModal('sendModal');
                        showAlert('quotaAlert', 'warning', 'Danh sách đã được gửi thành công, nhưng trang chưa hiển thị thông tin mới. Vui lòng tải lại trang, không gửi lại.');
                        return;
                    }
                }
                teacherCatalog = fresh.teachers;
                pending = [];
                refreshMap();
                status = fresh.status;
                closeModal('sendModal');
                renderAll();
                showAlert('quotaAlert', 'success', `Đã gửi thành công danh sách ${r.selected_count} giáo viên lên Sở GD&ĐT (bao gồm 3 giáo viên dự bị). Sở sẽ xem xét và phân công theo kế hoạch.`);
            } catch (e) {
                closeModal('sendModal');
                showAlert('quotaAlert','danger', saved
                    ? 'Danh sách đã được gửi, nhưng trang chưa hiển thị kết quả mới. Vui lòng tải lại trang trước khi thực hiện thao tác khác.'
                    : `Chưa xác nhận được kết quả gửi: ${e.message}. Vui lòng tải lại trang và kiểm tra danh sách trước khi gửi lại.`);
            } finally {
                setSending(false);
            }
        }

        renderAll();
        if (initWarnings.length) showAlert('quotaAlert','warning',initWarnings.join(' '));
    </script>

</body>

</html>