<?php
require_once __DIR__ . '/../../../components/layout/header.php';
require_once __DIR__ . '/../../../components/layout/sidebar.php';

$scenario = $_GET['scenario'] ?? 'normal';

$teachers = [
    ['name' => 'Nguyễn Thị Lan', 'subject' => 'Ngữ văn', 'code' => 'GV001'],
    ['name' => 'Trần Minh Quân', 'subject' => 'Toán', 'code' => 'GV002'],
    ['name' => 'Lê Hoàng Mai', 'subject' => 'Tiếng Anh', 'code' => 'GV003'],
    ['name' => 'Phạm Quốc Huy', 'subject' => 'Vật lý', 'code' => 'GV004'],
];
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

            <form class="mb-[18px] rounded-[14px] border border-gray-200 bg-white p-5 shadow-[0_10px_25px_rgba(15,23,42,0.08)]" method="get">
                <label class="mb-[7px] block font-[650]">Mô phỏng tình huống</label>

                <div class="grid grid-cols-12 gap-4">
                    <div class="col-span-12 min-[801px]:col-span-8">
                        <select class="w-full rounded-[9px] border border-slate-300 bg-white px-3 py-2.5 text-[#1f2937] outline-none focus:border-blue-600 focus:ring-[3px] focus:ring-blue-600/10" name="scenario">
                            <option value="normal" <?= $scenario === 'normal' ? 'selected' : '' ?>>
                                Basic flow - dữ liệu hợp lệ
                            </option>

                            <option value="invalid_excel" <?= $scenario === 'invalid_excel' ? 'selected' : '' ?>>
                                Exception 3.2.1 - file Excel không hợp lệ
                            </option>

                            <option value="teacher_missing"
                                <?= $scenario === 'teacher_missing' ? 'selected' : '' ?>>
                                Exception 3.2.2 - GV không tồn tại
                            </option>

                            <option value="teacher_ineligible"
                                <?= $scenario === 'teacher_ineligible' ? 'selected' : '' ?>>
                                Exception 3.2.3 - GV không đủ điều kiện
                            </option>

                            <option value="wrong_quota" <?= $scenario === 'wrong_quota' ? 'selected' : '' ?>>
                                Exception 6.1 - số lượng không đúng chỉ tiêu
                            </option>
                        </select>
                    </div>

                    <div class="col-span-12 min-[801px]:col-span-4">
                        <button class="inline-flex cursor-pointer items-center justify-center gap-2 rounded-[9px] px-[15px] py-2.5 font-bold bg-blue-600 text-white hover:bg-blue-700" type="submit">
                            Hiển thị tình huống
                        </button>
                    </div>
                </div>
            </form>

            <div class="grid grid-cols-12 gap-4">
                <div class="col-span-12 min-[801px]:col-span-4">
                    <div class="rounded-xl border border-gray-200 bg-slate-50 p-[15px]">
                        <div class="text-gray-500">Chỉ tiêu cần đăng ký</div>
                        <div class="mt-1 text-[28px] font-extrabold">3</div>
                        <div class="text-gray-500">giáo viên</div>
                    </div>
                </div>

                <div class="col-span-12 min-[801px]:col-span-4">
                    <div class="rounded-xl border border-gray-200 bg-slate-50 p-[15px]">
                        <div class="text-gray-500">Đã chọn hợp lệ</div>
                        <div id="selectedCount" class="mt-1 text-[28px] font-extrabold">0</div>
                        <div class="text-gray-500">giáo viên</div>
                    </div>
                </div>

                <div class="col-span-12 min-[801px]:col-span-4">
                    <div class="rounded-xl border border-gray-200 bg-slate-50 p-[15px]">
                        <div class="text-gray-500">Trạng thái</div>
                        <div class="mt-2.5">
                            <span id="statusBadge" class="inline-block rounded-full px-[9px] py-[5px] text-xs font-extrabold bg-gray-200 text-gray-700">Chưa lưu</span>
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
                        Danh sách bên dưới chỉ hiển thị giáo viên đáp ứng điều kiện tham gia hội đồng thi.
                    </div>

                    <div class="grid grid-cols-12 gap-4">
                        <div class="col-span-12 min-[801px]:col-span-6">
                            <input class="w-full rounded-[9px] border border-slate-300 bg-white px-3 py-2.5 text-[#1f2937] outline-none focus:border-blue-600 focus:ring-[3px] focus:ring-blue-600/10" id="teacherSearch" placeholder="Tìm theo tên/mã giáo viên..."
                                oninput="filterTeachers()">
                        </div>

                        <div class="col-span-12 min-[801px]:col-span-6">
                            <select class="w-full rounded-[9px] border border-slate-300 bg-white px-3 py-2.5 text-[#1f2937] outline-none focus:border-blue-600 focus:ring-[3px] focus:ring-blue-600/10" id="subjectFilter" onchange="filterTeachers()">
                                <option value="">Tất cả bộ môn</option>
                                <option>Ngữ văn</option>
                                <option>Toán</option>
                                <option>Tiếng Anh</option>
                                <option>Vật lý</option>
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
                                    <tr data-name="<?= htmlspecialchars(strtolower($t['name'] . ' ' . $t['code'])) ?>"
                                        data-subject="<?= htmlspecialchars($t['subject']) ?>">

                                        <td class="border-b border-gray-200 px-2.5 py-3 text-left align-top">
                                            <input class="teacher-check h-4 w-4 accent-blue-600" type="checkbox"
                                                data-code="<?= htmlspecialchars($t['code']) ?>"
                                                data-name="<?= htmlspecialchars($t['name']) ?>"
                                                data-subject="<?= htmlspecialchars($t['subject']) ?>"
                                                onchange="syncManual()">
                                        </td>

                                        <td class="border-b border-gray-200 px-2.5 py-3 text-left align-top"><?= htmlspecialchars($t['code']) ?></td>
                                        <td class="border-b border-gray-200 px-2.5 py-3 text-left align-top"><b><?= htmlspecialchars($t['name']) ?></b></td>
                                        <td class="border-b border-gray-200 px-2.5 py-3 text-left align-top"><?= htmlspecialchars($t['subject']) ?></td>

                                        <td class="border-b border-gray-200 px-2.5 py-3 text-left align-top">
                                            <span class="inline-block rounded-full px-[9px] py-[5px] text-xs font-extrabold bg-green-100 text-green-800">
                                                Đủ điều kiện
                                            </span>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>

                <div id="excelPanel" class="hidden">
                    <label class="mb-[7px] block font-[650]">Chọn file Excel</label>

                    <input class="w-full rounded-[9px] border border-slate-300 bg-white px-3 py-2.5 text-[#1f2937] outline-none focus:border-blue-600 focus:ring-[3px] focus:ring-blue-600/10" id="excelFile" type="file" accept=".xlsx,.xls">

                    <p class="text-gray-500">
                        File cần đúng mẫu hệ thống hỗ trợ.
                        Demo này mô phỏng kết quả đối chiếu, chưa đọc Excel thật.
                    </p>

                    <div class="mt-3.5 flex flex-wrap gap-2.5">
                        <button class="inline-flex cursor-pointer items-center justify-center gap-2 rounded-[9px] px-[15px] py-2.5 font-bold bg-blue-600 text-white hover:bg-blue-700" type="button" onclick="simulateExcel()">
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
                    <button class="inline-flex cursor-pointer items-center justify-center gap-2 rounded-[9px] px-[15px] py-2.5 font-bold bg-slate-200 text-slate-900" type="button" onclick="saveDraft()">
                        Lưu danh sách
                    </button>

                    <button class="inline-flex cursor-pointer items-center justify-center gap-2 rounded-[9px] px-[15px] py-2.5 font-bold bg-green-600 text-white" type="button" onclick="sendList()">
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
                Danh sách sẽ được lưu chính thức và chuyển sang trạng thái
                “Đã gửi/Chờ phân công”.
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
        const scenario = <?= json_encode($scenario) ?>;
        const quota = 3;
        let selected = [];

        function switchMethod(method) {
            const manual = method === 'manual';

            document.getElementById('manualPanel').classList.toggle('hidden', !manual);
            document.getElementById('excelPanel').classList.toggle('hidden', manual);
            document.getElementById('manualTab').classList.toggle('active', manual);
            document.getElementById('excelTab').classList.toggle('active', !manual);
        }

        function filterTeachers() {
            const keyword = document.getElementById('teacherSearch').value.toLowerCase();
            const subject = document.getElementById('subjectFilter').value;

            document.querySelectorAll('#teacherBody tr').forEach(row => {
                row.classList.toggle(
                    'hidden',
                    !(row.dataset.name.includes(keyword) && (!subject || row.dataset.subject === subject))
                );
            });
        }

        function syncManual() {
            selected = [...document.querySelectorAll('.teacher-check:checked')].map(check => ({
                code: check.dataset.code,
                name: check.dataset.name,
                subject: check.dataset.subject,
                status: 'Hợp lệ'
            }));

            renderSelected();
        }

        function simulateExcel() {
            clearAlert('excelAlert');

            const file = document.getElementById('excelFile').files[0];

            if (!file) {
                showAlert('excelAlert', 'warning', 'Vui lòng chọn file Excel trước.');
                return;
            }

            if (scenario === 'invalid_excel') {
                showAlert(
                    'excelAlert',
                    'danger',
                    'File Excel không hợp lệ, vui lòng kiểm tra và tải lại.'
                );
                return;
            }

            selected = [{
                    code: 'GV001',
                    name: 'Nguyễn Thị Lan',
                    subject: 'Ngữ văn',
                    status: 'Hợp lệ'
                },
                {
                    code: 'GV002',
                    name: 'Trần Minh Quân',
                    subject: 'Toán',
                    status: 'Hợp lệ'
                }
            ];

            let rows = `
        <tr>
            <td class="border-b border-gray-200 px-2.5 py-3 text-left align-top">2</td>
            <td class="border-b border-gray-200 px-2.5 py-3 text-left align-top">Nguyễn Thị Lan</td>
            <td class="border-b border-gray-200 px-2.5 py-3 text-left align-top"><span class="inline-block rounded-full px-[9px] py-[5px] text-xs font-extrabold bg-green-100 text-green-800">Hợp lệ</span></td>
        </tr>

        <tr>
            <td class="border-b border-gray-200 px-2.5 py-3 text-left align-top">3</td>
            <td class="border-b border-gray-200 px-2.5 py-3 text-left align-top">Trần Minh Quân</td>
            <td class="border-b border-gray-200 px-2.5 py-3 text-left align-top"><span class="inline-block rounded-full px-[9px] py-[5px] text-xs font-extrabold bg-green-100 text-green-800">Hợp lệ</span></td>
        </tr>
    `;

            if (scenario === 'teacher_missing') {
                rows += `
            <tr>
                <td class="border-b border-gray-200 px-2.5 py-3 text-left align-top">4</td>
                <td class="border-b border-gray-200 px-2.5 py-3 text-left align-top">Nguyễn Văn Không Có</td>
                <td class="border-b border-gray-200 px-2.5 py-3 text-left align-top">
                    <span class="inline-block rounded-full px-[9px] py-[5px] text-xs font-extrabold bg-red-100 text-red-800">
                        Không tồn tại trong danh sách giáo viên của trường
                    </span>
                </td>
            </tr>
        `;

                showAlert(
                    'excelAlert',
                    'warning',
                    'Giáo viên Nguyễn Văn Không Có không tồn tại trong danh sách giáo viên của trường. Giáo viên này không được thêm.'
                );
            } else if (scenario === 'teacher_ineligible') {
                rows += `
            <tr>
                <td class="border-b border-gray-200 px-2.5 py-3 text-left align-top">4</td>
                <td class="border-b border-gray-200 px-2.5 py-3 text-left align-top">Phạm Thị B</td>
                <td class="border-b border-gray-200 px-2.5 py-3 text-left align-top">
                    <span class="inline-block rounded-full px-[9px] py-[5px] text-xs font-extrabold bg-red-100 text-red-800">Không đủ điều kiện</span>
                    <br>
                    <small>Chưa đáp ứng điều kiện theo quy định hội đồng thi.</small>
                </td>
            </tr>
        `;

                showAlert(
                    'excelAlert',
                    'warning',
                    'Giáo viên Phạm Thị B không đủ điều kiện tham gia hội đồng thi. Giáo viên này không được thêm.'
                );
            } else {
                selected.push({
                    code: 'GV003',
                    name: 'Lê Hoàng Mai',
                    subject: 'Tiếng Anh',
                    status: 'Hợp lệ'
                });

                rows += `
            <tr>
                <td class="border-b border-gray-200 px-2.5 py-3 text-left align-top">4</td>
                <td class="border-b border-gray-200 px-2.5 py-3 text-left align-top">Lê Hoàng Mai</td>
                <td class="border-b border-gray-200 px-2.5 py-3 text-left align-top"><span class="inline-block rounded-full px-[9px] py-[5px] text-xs font-extrabold bg-green-100 text-green-800">Hợp lệ</span></td>
            </tr>
        `;

                showAlert(
                    'excelAlert',
                    'success',
                    'Đã đọc file và đối chiếu danh sách giáo viên thành công.'
                );
            }

            document.getElementById('excelResult').innerHTML = `
        <table class="w-full border-collapse">
            <thead>
                <tr>
                    <th class="border-b border-gray-200 px-2.5 py-3 text-left align-top bg-slate-50 text-[13px] font-bold uppercase text-slate-500">Dòng</th>
                    <th class="border-b border-gray-200 px-2.5 py-3 text-left align-top bg-slate-50 text-[13px] font-bold uppercase text-slate-500">Giáo viên</th>
                    <th class="border-b border-gray-200 px-2.5 py-3 text-left align-top bg-slate-50 text-[13px] font-bold uppercase text-slate-500">Kết quả đối chiếu</th>
                </tr>
            </thead>
            <tbody>${rows}</tbody>
        </table>
    `;

            renderSelected();
        }

        function renderSelected() {
            const body = document.getElementById('selectedBody');

            body.innerHTML = selected.length ?
                selected.map((teacher, index) => `
            <tr>
                <td class="border-b border-gray-200 px-2.5 py-3 text-left align-top">${teacher.code}</td>
                <td class="border-b border-gray-200 px-2.5 py-3 text-left align-top"><b>${teacher.name}</b></td>
                <td class="border-b border-gray-200 px-2.5 py-3 text-left align-top">${teacher.subject}</td>
                <td class="border-b border-gray-200 px-2.5 py-3 text-left align-top"><span class="inline-block rounded-full px-[9px] py-[5px] text-xs font-extrabold bg-green-100 text-green-800">${teacher.status}</span></td>
                <td class="border-b border-gray-200 px-2.5 py-3 text-left align-top">
                    <button class="inline-flex cursor-pointer items-center justify-center gap-2 rounded-[9px] px-[15px] py-2.5 font-bold border border-slate-300 bg-white text-[#1f2937]" type="button"
                        onclick="removeTeacher(${index})">
                        Loại
                    </button>
                </td>
            </tr>
        `).join('') :
                `
            <tr>
                <td colspan="5" class="border-b border-gray-200 px-2.5 py-3 text-left align-top px-8 py-8 text-center text-gray-500">
                    Chưa có giáo viên nào được chọn.
                </td>
            </tr>
        `;

            document.getElementById('selectedCount').textContent = selected.length;
        }

        function removeTeacher(index) {
            selected.splice(index, 1);
            renderSelected();
        }

        function checkQuota() {
            if (scenario === 'wrong_quota' || selected.length !== quota) {
                showAlert(
                    'quotaAlert',
                    'danger',
                    `Số lượng giáo viên không hợp lệ. Chỉ tiêu là ${quota}, hiện có ${selected.length}. Vui lòng bổ sung hoặc loại bớt giáo viên.`
                );

                return false;
            }

            clearAlert('quotaAlert');
            return true;
        }

        function saveDraft() {
            if (!selected.length) {
                showAlert(
                    'quotaAlert',
                    'warning',
                    'Chưa có giáo viên trong danh sách để lưu.'
                );
                return;
            }

            const badge = document.getElementById('statusBadge');
            badge.className = 'inline-block rounded-full px-[9px] py-[5px] text-xs font-extrabold bg-amber-100 text-amber-800';
            badge.textContent = 'Bản nháp';

            showAlert(
                'quotaAlert',
                'success',
                'Đã lưu danh sách ở trạng thái “Bản nháp”.'
            );
        }

        function sendList() {
            if (checkQuota()) openModal('sendModal');
        }

        function confirmSend() {
            closeModal('sendModal');

            const badge = document.getElementById('statusBadge');
            badge.className = 'inline-block rounded-full px-[9px] py-[5px] text-xs font-extrabold bg-blue-100 text-blue-700';
            badge.textContent = 'Đã gửi / Chờ phân công';

            showAlert(
                'quotaAlert',
                'success',
                'Danh sách hợp lệ đã được gửi thành công cho Sở GD&ĐT.'
            );
        }
    </script>

</body>

</html>