<?php
require_once __DIR__ . '/../../../class/chon-giao-vien-bootstrap.php';
if (session_status() !== PHP_SESSION_ACTIVE) session_start();
if (!isset($_SESSION['phat_teacher_csrf'])) $_SESSION['phat_teacher_csrf'] = bin2hex(random_bytes(32));

if (isset($_GET['ajax'])) {
    header('Content-Type: application/json; charset=utf-8');
    header('Cache-Control: no-store');
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        http_response_code(405);
        echo json_encode(['ok' => false, 'error' => 'Chỉ hỗ trợ POST.']); exit;
    }
    $sentCsrf = $_SERVER['HTTP_X_CSRF_TOKEN'] ?? '';
    if (!is_string($sentCsrf) || !hash_equals($_SESSION['phat_teacher_csrf'], $sentCsrf)) {
        http_response_code(403);
        echo json_encode(['ok' => false, 'error' => 'Phiên làm việc hết hạn (CSRF).']); exit;
    }
    try {
        $service = phatCreateTeacherService();
        $action = (string)($_POST['action'] ?? '');
        if ($action === 'import') {
            $data = $service->importXlsx($_FILES['excel'] ?? []);
        } elseif ($action === 'save' || $action === 'submit') {
            $raw = (string)($_POST['codes'] ?? '[]');
            $codes = json_decode($raw, true, 512, JSON_THROW_ON_ERROR);
            if (!is_array($codes)) throw new InvalidArgumentException('Danh sách giáo viên sai định dạng.');
            $data = $service->save($codes, $action === 'submit');
        } elseif ($action === 'reload') {
            $data = $service->state();
        } else {
            throw new InvalidArgumentException('Thao tác không hỗ trợ.');
        }
        echo json_encode(['ok' => true, 'data' => $data], JSON_UNESCAPED_UNICODE | JSON_INVALID_UTF8_SUBSTITUTE);
    } catch (DomainException | InvalidArgumentException | JsonException $e) {
        http_response_code(422);
        echo json_encode(['ok' => false, 'error' => $e->getMessage()], JSON_UNESCAPED_UNICODE);
    } catch (Throwable $e) {
        error_log('[chon-giao-vien] ' . $e->getMessage());
        http_response_code(500);
        echo json_encode(['ok' => false, 'error' => 'Không thể xử lý yêu cầu. Kiểm tra kết nối DB/schema và nhật ký PHP.'], JSON_UNESCAPED_UNICODE);
    }
    exit;
}

require_once __DIR__ . '/../../../components/layout/header.php';
require_once __DIR__ . '/../../../components/layout/sidebar.php';
$teachers = []; $selectedCodes = []; $quota = 0; $currentStatus = 'NEW'; $pageError = null;
try {
    $state = phatCreateTeacherService()->state();
    $teachers = $state['teachers'];
    $selectedCodes = $state['selected'];
    $quota = $state['quota'];
    $currentStatus = $state['status'];
} catch (Throwable $e) {
    error_log('[chon-giao-vien-page] ' . $e->getMessage());
    $pageError = $e instanceof DomainException ? $e->getMessage() : 'Không tải được dữ liệu giáo viên từ database. Kiểm tra cấu hình và PHP error log.';
}
$subjects = array_unique(array_column($teachers, 'subject'));
sort($subjects);
?>

<!doctype html>
<html lang="vi">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width,initial-scale=1">
    <title>Chọn giáo viên tham gia hội đồng thi</title>

    <link rel="stylesheet" href="../../../assets/css/phat-tailwind.css">
</head>

<body class="m-0 bg-[#f5f7fb] font-sans text-[#1f2937]">

    <?php renderHeader(); ?>
    <?php renderSidebar(); ?>

    <div class="min-h-screen bg-slate-50 pl-72 pt-20">

        <main class="mx-auto mt-7 max-w-[1180px] px-[18px] pb-12">

            <h1 class="mb-1.5 text-[30px] font-bold leading-tight">Chọn giáo viên tham gia hội đồng thi</h1>
            <p class="mb-6 text-gray-500">Chọn giáo viên thủ công hoặc nhập danh sách từ Excel.</p>

            <?php if ($pageError): ?>
                <div class="mb-[18px] rounded-[10px] border border-red-200 bg-red-50 px-[15px] py-[13px] font-semibold text-red-800"><?= htmlspecialchars($pageError, ENT_QUOTES, 'UTF-8') ?></div>
            <?php endif; ?>

            <div class="grid grid-cols-12 gap-4">
                <div class="col-span-12 min-[801px]:col-span-4">
                    <div class="rounded-xl border border-gray-200 bg-slate-50 p-[15px]">
                        <div class="text-gray-500">Chỉ tiêu cần đăng ký</div>
                        <div class="mt-1 text-[28px] font-extrabold"><?= $quota > 0 ? $quota : 'Chưa có' ?></div>
                        <div class="text-gray-500">giáo viên</div>
                    </div>
                </div>

                <div class="col-span-12 min-[801px]:col-span-4">
                    <div class="rounded-xl border border-gray-200 bg-slate-50 p-[15px]">
                        <div class="text-gray-500">Đã chọn hợp lệ</div>
                        <div id="selectedCount" class="mt-1 text-[28px] font-extrabold"><?= count($selectedCodes) ?></div>
                        <div class="text-gray-500">giáo viên</div>
                    </div>
                </div>

                <div class="col-span-12 min-[801px]:col-span-4">
                    <div class="rounded-xl border border-gray-200 bg-slate-50 p-[15px]">
                        <div class="text-gray-500">Trạng thái</div>
                        <div class="mt-2.5">
                            <span id="statusBadge" class="inline-block rounded-full px-[9px] py-[5px] text-xs font-extrabold bg-gray-200 text-gray-700"><?= htmlspecialchars(['NEW'=>'Chưa lưu','DRAFT'=>'Bản nháp','SUBMITTED'=>'Đã gửi / Chờ phân công'][$currentStatus] ?? $currentStatus, ENT_QUOTES, 'UTF-8') ?></span>
                        </div>
                    </div>
                </div>
            </div>

            <div class="mb-[18px] rounded-[14px] border border-gray-200 bg-white p-5 shadow-[0_10px_25px_rgba(15,23,42,0.08)]">
                <h2 class="mb-4 text-2xl font-bold">1. Chọn phương thức</h2>

                <div class="mb-4 flex flex-wrap gap-2">
                    <button type="button" class="inline-flex cursor-pointer items-center justify-center gap-2 rounded-[9px] px-[15px] py-2.5 font-bold tab-btn bg-slate-200 text-slate-700 [&.active]:bg-blue-600 [&.active]:text-white active" id="manualTab"
                        onclick="switchMethod('manual')">
                        Chọn thủ công
                    </button>

                    <button type="button" class="inline-flex cursor-pointer items-center justify-center gap-2 rounded-[9px] px-[15px] py-2.5 font-bold tab-btn bg-slate-200 text-slate-700 [&.active]:bg-blue-600 [&.active]:text-white" id="excelTab" onclick="switchMethod('excel')">
                        Nhập từ Excel
                    </button>
                </div>

                <div id="manualPanel">
                    <div class="my-3 rounded-[10px] border px-[15px] py-[13px] font-semibold border-blue-200 bg-blue-50 text-blue-800">
                        Danh sách giáo viên chưa được phân công vào các hội đồng được hệ thống kiểm tra. Các điều kiện nghiệp vụ khác cần đối chiếu quy định và dữ liệu nhóm.
                    </div>

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

                    <div class="overflow-x-auto">
                        <table class="mt-3.5 w-full border-collapse">
                            <thead>
                                <tr>
                                    <th class="border-b border-gray-200 px-2.5 py-3 text-left align-top bg-slate-50 text-[13px] font-bold uppercase text-slate-500">Chọn</th>
                                    <th class="border-b border-gray-200 px-2.5 py-3 text-left align-top bg-slate-50 text-[13px] font-bold uppercase text-slate-500">Mã GV</th>
                                    <th class="border-b border-gray-200 px-2.5 py-3 text-left align-top bg-slate-50 text-[13px] font-bold uppercase text-slate-500">Họ tên</th>
                                    <th class="border-b border-gray-200 px-2.5 py-3 text-left align-top bg-slate-50 text-[13px] font-bold uppercase text-slate-500">Bộ môn</th>
                                    <th class="border-b border-gray-200 px-2.5 py-3 text-left align-top bg-slate-50 text-[13px] font-bold uppercase text-slate-500">Điều kiện</th>
                                </tr>
                            </thead>

                            <tbody id="teacherBody">
                                <?php foreach ($teachers as $t): ?>
                                    <tr data-name="<?= htmlspecialchars($t['name'] . ' ' . $t['code'], ENT_QUOTES, 'UTF-8') ?>"
                                        data-subject="<?= htmlspecialchars($t['subject']) ?>">

                                        <td class="border-b border-gray-200 px-2.5 py-3 text-left align-top">
                                            <input class="teacher-check h-4 w-4 accent-blue-600" type="checkbox"
                                                <?= $currentStatus === 'SUBMITTED' || !$t['eligible'] ? 'disabled' : '' ?>
                                                <?= in_array($t['code'], $selectedCodes, true) ? 'checked' : '' ?>
                                                data-code="<?= htmlspecialchars($t['code']) ?>"
                                                data-name="<?= htmlspecialchars($t['name']) ?>"
                                                data-subject="<?= htmlspecialchars($t['subject']) ?>"
                                                onchange="syncManual()">
                                        </td>

                                        <td class="border-b border-gray-200 px-2.5 py-3 text-left align-top"><?= htmlspecialchars($t['code']) ?></td>
                                        <td class="border-b border-gray-200 px-2.5 py-3 text-left align-top"><b><?= htmlspecialchars($t['name']) ?></b></td>
                                        <td class="border-b border-gray-200 px-2.5 py-3 text-left align-top"><?= htmlspecialchars($t['subject']) ?></td>

                                        <td class="border-b border-gray-200 px-2.5 py-3 text-left align-top">
                                            <?php if ($t['eligible']): ?>
                                            <span class="inline-block rounded-full px-[9px] py-[5px] text-xs font-extrabold bg-green-100 text-green-800">Đủ điều kiện</span>
                                            <?php else: ?>
                                            <span class="inline-block rounded-full px-[9px] py-[5px] text-xs font-extrabold bg-red-100 text-red-800"><?= htmlspecialchars((string)($t['status_label'] ?? 'Không đủ điều kiện'), ENT_QUOTES, 'UTF-8') ?></span>
                                            <?php endif; ?>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>

                <div id="excelPanel" class="hidden">
                    <label class="mb-[7px] block font-[650]">Chọn file Excel</label>

                    <input class="w-full rounded-[9px] border border-slate-300 bg-white px-3 py-2.5 text-[#1f2937] outline-none focus:border-blue-600 focus:ring-[3px] focus:ring-blue-600/10" id="excelFile" type="file" accept=".xlsx">

                    <p class="text-gray-500">
                        File .xlsx cần có cột A là <strong>Mã GV</strong>, từ dòng 2 trở đi ghi một mã giáo viên mỗi dòng.
                    </p>

                    <div class="mt-3.5 flex flex-wrap gap-2.5">
                        <button class="inline-flex cursor-pointer items-center justify-center gap-2 rounded-[9px] px-[15px] py-2.5 font-bold bg-blue-600 text-white hover:bg-blue-700" type="button" onclick="simulateExcel()" <?= $pageError || $currentStatus === 'SUBMITTED' ? 'disabled' : '' ?>>
                            Đọc và kiểm tra file
                        </button>
                    </div>

                    <div id="excelAlert" class="my-3 rounded-[10px] border px-[15px] py-[13px] font-semibold hidden"></div>
                    <div id="excelResult"></div>
                </div>
            </div>

            <div class="mb-[18px] rounded-[14px] border border-gray-200 bg-white p-5 shadow-[0_10px_25px_rgba(15,23,42,0.08)]">
                <h2 class="mb-4 text-2xl font-bold">2. Danh sách giáo viên đã chọn/nhập</h2>

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
                    <button class="inline-flex cursor-pointer items-center justify-center gap-2 rounded-[9px] px-[15px] py-2.5 font-bold bg-slate-200 text-slate-900" type="button" onclick="saveDraft()" <?= $pageError || $currentStatus === 'SUBMITTED' ? 'disabled' : '' ?>>
                        Lưu danh sách
                    </button>

                    <button class="inline-flex cursor-pointer items-center justify-center gap-2 rounded-[9px] px-[15px] py-2.5 font-bold bg-green-600 text-white" type="button" onclick="sendList()" <?= $pageError || $quota < 1 || $currentStatus === 'SUBMITTED' ? 'disabled' : '' ?>>
                        Gửi danh sách
                    </button>
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
                Khi xác nhận, hệ thống sẽ đánh dấu giáo viên được chọn là đã đề cử trong database.
                Thao tác này không thể hoàn tác trong phiên hiện tại.
            </p>

            <div class="mt-3.5 flex flex-wrap gap-2.5">
                <button class="inline-flex cursor-pointer items-center justify-center gap-2 rounded-[9px] px-[15px] py-2.5 font-bold bg-slate-200 text-slate-900" type="button" onclick="closeModal('sendModal')">
                    Quay lại
                </button>

                <button class="inline-flex cursor-pointer items-center justify-center gap-2 rounded-[9px] px-[15px] py-2.5 font-bold bg-green-600 text-white" type="button" onclick="confirmSend()">
                    Xác nhận gửi
                </button>
            </div>
        </div>
    </div>

    <script src="../../../assets/js/usecase-tailwind.js"></script>

    <script>
        const csrf = <?= json_encode($_SESSION['phat_teacher_csrf']) ?>;
        const quota = <?= (int)$quota ?>;
        const teacherCatalog = <?= json_encode($teachers, JSON_UNESCAPED_UNICODE | JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_AMP | JSON_HEX_QUOT) ?>;
        let selected = teacherCatalog.filter(t => <?= json_encode($selectedCodes) ?>.includes(t.code));
        let status = <?= json_encode($currentStatus) ?>;
        const byCode = new Map(teacherCatalog.map(t => [t.code, t]));
        const esc = v => String(v ?? '').replace(/[&<>"']/g, c => ({'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#39;'}[c]));
        const tdClass = 'border-b border-gray-200 px-2.5 py-3 text-left align-top';

        async function request(action, fields = {}, file = null) {
            const body = new FormData();
            body.set('action', action);
            Object.entries(fields).forEach(([key, value]) => body.set(key, value));
            if (file) body.set('excel', file);
            const response = await fetch('?ajax=1', { method: 'POST', credentials: 'same-origin', headers: {'X-CSRF-Token': csrf}, body });
            let result;
            try { result = await response.json(); } catch { throw new Error('Máy chủ không trả về JSON hợp lệ.'); }
            if (!response.ok || !result.ok) throw new Error(result.error || 'Có lỗi khi xử lý.');
            return result.data;
        }

        function switchMethod(method) {
            const manual = method === 'manual';
            document.getElementById('manualPanel').classList.toggle('hidden', !manual);
            document.getElementById('excelPanel').classList.toggle('hidden', manual);
            document.getElementById('manualTab').classList.toggle('active', manual);
            document.getElementById('excelTab').classList.toggle('active', !manual);
        }

        function filterTeachers() {
            const keyword = document.getElementById('teacherSearch').value.toLocaleLowerCase('vi');
            const subject = document.getElementById('subjectFilter').value;
            document.querySelectorAll('#teacherBody tr').forEach(row => {
                row.classList.toggle('hidden', !(row.dataset.name.toLocaleLowerCase('vi').includes(keyword) && (!subject || row.dataset.subject === subject)));
            });
        }

        function syncManual() {
            if (status === 'SUBMITTED') return;
            // Giáo viên vừa nhập Excel có thể chưa có checkbox nếu trang được mở
            // trước khi DB bổ sung giáo viên. Không đánh mất họ khi chọn thủ công.
            const displayedCodes = new Set([...document.querySelectorAll('.teacher-check')]
                .map(check => String(check.dataset.code)));
            const importedNotDisplayed = selected.filter(t => !displayedCodes.has(String(t.code)));
            const checkedOnPage = [...document.querySelectorAll('.teacher-check:checked')]
                .map(check => byCode.get(String(check.dataset.code))).filter(Boolean);
            selected = [...importedNotDisplayed, ...checkedOnPage];
            renderSelected();
        }

        function syncCheckboxes() {
            const codes = new Set(selected.map(t => t.code));
            document.querySelectorAll('.teacher-check').forEach(box => box.checked = codes.has(box.dataset.code));
        }

        async function simulateExcel() {
            if (status === 'SUBMITTED') { showAlert('excelAlert','warning','Danh sách đã gửi, không được sửa.'); return; }
            clearAlert('excelAlert');
            const file = document.getElementById('excelFile').files[0];
            if (!file) { showAlert('excelAlert','warning','Vui lòng chọn file .xlsx.'); return; }
            try {
                const result = await request('import', {}, file);
                // Lấy lại catalog mới nhất từ DB thay vì dựa vào dữ liệu lúc tải trang.
                // Tránh lỗi "Excel hợp lệ nhưng danh sách đã chọn vẫn là 0".
                const liveState = await request('reload');
                const liveTeachers = Array.isArray(liveState.teachers) ? liveState.teachers : [];
                const liveByCode = new Map(liveTeachers.map(t => [String(t.code), t]));
                const acceptedCodes = (Array.isArray(result.codes) ? result.codes : []).map(String);
                const matched = acceptedCodes.map(code => liveByCode.get(code));
                if (matched.some(t => !t || !t.eligible)) {
                    throw new Error('Danh sách giáo viên đã thay đổi khi nhập Excel. Hãy tải lại trang và thử lại.');
                }
                // Cập nhật map cho cả lựa chọn thủ công và danh sách nhập Excel.
                liveTeachers.forEach(t => byCode.set(String(t.code), t));
                selected = matched;
                syncCheckboxes(); renderSelected();
                const errors = result.errors || [];
                const resultHtml = errors.length
                    ? errors.map(error => `<tr><td class="${tdClass}">${esc(error)}</td></tr>`).join('')
                    : `<tr><td class="${tdClass}">Đã đối chiếu ${result.total_rows} dòng hợp lệ.</td></tr>`;
                document.getElementById('excelResult').innerHTML = `<table class="w-full border-collapse"><tbody>${resultHtml}</tbody></table>`;
                const message = errors.length
                    ? `Đã chọn ${selected.length} GV hợp lệ; ${errors.length} dòng cần sửa.`
                    : `Đã đọc và chọn ${selected.length} giáo viên hợp lệ từ Excel.`;
                showAlert('excelAlert', errors.length ? 'warning' : 'success', message);
            } catch (e) { showAlert('excelAlert','danger',e.message); }
        }

        function renderSelected() {
            const body = document.getElementById('selectedBody');
            body.innerHTML = selected.length ? selected.map((t, index) => `
                <tr>
                    <td class="${tdClass}">${esc(t.code)}</td>
                    <td class="${tdClass}"><b>${esc(t.name)}</b></td>
                    <td class="${tdClass}">${esc(t.subject)}</td>
                    <td class="${tdClass}"><span class="inline-block rounded-full bg-green-100 px-2 py-1 text-xs font-extrabold text-green-800">Hợp lệ</span></td>
                    <td class="${tdClass}">${status === 'SUBMITTED' ? '' : `<button type="button" class="rounded-[9px] border border-slate-300 bg-white px-[15px] py-2.5 font-bold" onclick="removeTeacher(${index})">Loại</button>`}</td>
                </tr>`).join('') : `<tr><td colspan="5" class="${tdClass} py-8 text-center text-gray-500">Chưa chọn giáo viên nào.</td></tr>`;
            document.getElementById('selectedCount').textContent = selected.length;
        }

        function removeTeacher(index) {
            if (status === 'SUBMITTED') return;
            selected.splice(index, 1);
            syncCheckboxes(); renderSelected();
        }

        function setStatus(newStatus) {
            status = newStatus;
            const badge = document.getElementById('statusBadge');
            badge.textContent = newStatus === 'DRAFT' ? 'Bản nháp' : newStatus === 'SUBMITTED' ? 'Đã gửi / Chờ phân công' : 'Chưa lưu';
            badge.className = 'inline-block rounded-full px-[9px] py-[5px] text-xs font-extrabold ' +
                (newStatus === 'SUBMITTED' ? 'bg-blue-100 text-blue-700' : newStatus === 'DRAFT' ? 'bg-amber-100 text-amber-800' : 'bg-gray-200 text-gray-700');
            if (newStatus === 'SUBMITTED') {
                document.querySelectorAll('.teacher-check, button[onclick="saveDraft()"], button[onclick="sendList()"], button[onclick="simulateExcel()"]').forEach(el => el.disabled = true);
            }
            renderSelected();
        }

        function checkQuota() {
            if (quota < 1 || selected.length !== quota) {
                showAlert('quotaAlert','danger',`Số lượng không hợp lệ. Chỉ tiêu: ${quota || 'chưa cấu hình'}, hiện đã chọn: ${selected.length}.`);
                return false;
            }
            clearAlert('quotaAlert'); return true;
        }

        async function saveDraft() {
            if (!selected.length) { showAlert('quotaAlert','warning','Chưa chọn giáo viên để lưu.'); return; }
            try {
                const r = await request('save', {codes: JSON.stringify(selected.map(t => t.code))});
                setStatus(r.status);
                showAlert('quotaAlert','success','Đã lưu bản nháp trong Session (chưa ghi database).');
            } catch (e) { showAlert('quotaAlert','danger',e.message); }
        }

        function sendList() { if (checkQuota()) openModal('sendModal'); }

        async function confirmSend() {
            try {
                const r = await request('submit', {codes: JSON.stringify(selected.map(t => t.code))});
                closeModal('sendModal');
                setStatus(r.status);
                showAlert('quotaAlert','success','Đã đánh dấu giáo viên được chọn trong database. Lưu ý: database hiện chưa lưu thông tin phiếu gửi theo kỳ thi.');
            } catch (e) { closeModal('sendModal'); showAlert('quotaAlert','danger',e.message); }
        }
        renderSelected();
    </script>

</body>

</html>