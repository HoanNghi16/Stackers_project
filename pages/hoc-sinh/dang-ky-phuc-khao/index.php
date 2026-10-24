<?php
require_once __DIR__ . '/../../../components/layout/header.php';
require_once __DIR__ . '/../../../components/layout/sidebar.php';

$scenario = $_GET['scenario'] ?? 'normal';

$subjects = [
    ['id' => 'van', 'name' => 'Ngữ văn', 'score' => '7.25', 'status' => 'Có thể phúc khảo'],
    ['id' => 'anh', 'name' => 'Ngoại ngữ', 'score' => '8.00', 'status' => 'Có thể phúc khảo'],
    ['id' => 'toan', 'name' => 'Toán', 'score' => '6.75', 'status' => 'Có thể phúc khảo'],
];
?>

<!doctype html>
<html lang="vi">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width,initial-scale=1">
    <title>Đăng ký phúc khảo</title>

    <link rel="stylesheet" href="../../../assets/css/phat-tailwind.css">
</head>

<body class="m-0 bg-[#f5f7fb] font-sans text-[#1f2937]">

    <?php renderHeader(); ?>
    <?php renderSidebar(); ?>

    <div class="min-h-screen bg-slate-50 pl-72 pt-20">

        <main class="mx-auto mt-7 max-w-[1180px] px-[18px] pb-12">

            <h1 class="mb-1.5 text-[30px] font-bold leading-tight">Đăng ký phúc khảo</h1>
            <p class="mb-6 text-gray-500">
                Chọn một hoặc nhiều môn thi cần phúc khảo và gửi phiếu yêu cầu.
            </p>

            <form class="mb-[18px] rounded-[14px] border border-gray-200 bg-white p-5 shadow-[0_10px_25px_rgba(15,23,42,0.08)]" method="get">
                <label class="mb-[7px] block font-[650]">Mô phỏng tình huống</label>

                <div class="grid grid-cols-12 gap-4">
                    <div class="col-span-12 min-[801px]:col-span-8">
                        <select class="w-full rounded-[9px] border border-slate-300 bg-white px-3 py-2.5 text-[#1f2937] outline-none focus:border-blue-600 focus:ring-[3px] focus:ring-blue-600/10" name="scenario">
                            <option value="normal" <?= $scenario === 'normal' ? 'selected' : '' ?>>
                                Basic flow - được phép đăng ký
                            </option>

                            <option value="not_started" <?= $scenario === 'not_started' ? 'selected' : '' ?>>
                                Alternative 2.1 - chưa đến thời gian
                            </option>

                            <option value="expired" <?= $scenario === 'expired' ? 'selected' : '' ?>>
                                Alternative 2.2 - hết thời hạn
                            </option>

                            <option value="no_result" <?= $scenario === 'no_result' ? 'selected' : '' ?>>
                                Alternative 2.3 - chưa có kết quả thi
                            </option>

                            <option value="duplicate" <?= $scenario === 'duplicate' ? 'selected' : '' ?>>
                                Alternative 6.2 - môn đã đăng ký
                            </option>

                            <option value="save_error" <?= $scenario === 'save_error' ? 'selected' : '' ?>>
                                Exception 10.1 - lỗi lưu phiếu
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

            <?php if ($scenario === 'not_started'): ?>

                <div class="my-3 rounded-[10px] border px-[15px] py-[13px] font-semibold border-amber-200 bg-amber-50 text-amber-800">
                    Chưa đến thời gian đăng ký phúc khảo.
                    Thời gian bắt đầu tiếp nhận: 20/06/2027 08:00.
                </div>

            <?php elseif ($scenario === 'expired'): ?>

                <div class="my-3 rounded-[10px] border px-[15px] py-[13px] font-semibold border-red-200 bg-red-50 text-red-800">
                    Đã hết thời hạn đăng ký phúc khảo.
                    Hệ thống không cho phép tạo phiếu mới.
                </div>

            <?php elseif ($scenario === 'no_result'): ?>

                <div class="my-3 rounded-[10px] border px-[15px] py-[13px] font-semibold border-amber-200 bg-amber-50 text-amber-800">
                    Chưa có kết quả thi để đăng ký phúc khảo.
                </div>

            <?php else: ?>

                <div class="mb-[18px] rounded-[14px] border border-gray-200 bg-white p-5 shadow-[0_10px_25px_rgba(15,23,42,0.08)]">
                    <h2 class="mb-4 text-2xl font-bold">1. Chọn môn cần phúc khảo</h2>

                    <div class="overflow-x-auto">
                        <table class="w-full border-collapse">
                            <thead>
                                <tr>
                                    <th class="border-b border-gray-200 px-2.5 py-3 text-left align-top bg-slate-50 text-[13px] font-bold uppercase text-slate-500">Chọn</th>
                                    <th class="border-b border-gray-200 px-2.5 py-3 text-left align-top bg-slate-50 text-[13px] font-bold uppercase text-slate-500">Môn thi</th>
                                    <th class="border-b border-gray-200 px-2.5 py-3 text-left align-top bg-slate-50 text-[13px] font-bold uppercase text-slate-500">Điểm công bố</th>
                                    <th class="border-b border-gray-200 px-2.5 py-3 text-left align-top bg-slate-50 text-[13px] font-bold uppercase text-slate-500">Trạng thái</th>
                                </tr>
                            </thead>

                            <tbody>
                                <?php foreach ($subjects as $s): ?>
                                    <tr>
                                        <td class="border-b border-gray-200 px-2.5 py-3 text-left align-top">
                                            <input class="subject h-4 w-4 accent-blue-600" type="checkbox"
                                                value="<?= htmlspecialchars($s['id']) ?>"
                                                data-name="<?= htmlspecialchars($s['name']) ?>"
                                                data-score="<?= htmlspecialchars($s['score']) ?>">
                                        </td>

                                        <td class="border-b border-gray-200 px-2.5 py-3 text-left align-top"><b><?= htmlspecialchars($s['name']) ?></b></td>
                                        <td class="border-b border-gray-200 px-2.5 py-3 text-left align-top"><?= htmlspecialchars($s['score']) ?></td>

                                        <td class="border-b border-gray-200 px-2.5 py-3 text-left align-top">
                                            <span class="inline-block rounded-full px-[9px] py-[5px] text-xs font-extrabold bg-green-100 text-green-800">
                                                <?= htmlspecialchars($s['status']) ?>
                                            </span>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>

                    <?php if ($scenario === 'duplicate'): ?>
                        <div class="my-3 rounded-[10px] border px-[15px] py-[13px] font-semibold border-amber-200 bg-amber-50 text-amber-800">
                            Môn thi này đã được đăng ký phúc khảo.
                        </div>
                    <?php endif; ?>
                </div>

                <div class="mb-[18px] rounded-[14px] border border-gray-200 bg-white p-5 shadow-[0_10px_25px_rgba(15,23,42,0.08)]">
                    <h2 class="mb-4 text-2xl font-bold">2. Thông tin đăng ký</h2>

                    <div id="formAlert" class="my-3 rounded-[10px] border px-[15px] py-[13px] font-semibold hidden"></div>

                    <div class="grid grid-cols-12 gap-4">
                        <div class="col-span-12">
                            <label class="mb-[7px] block font-[650]">
                                Lý do phúc khảo
                                <span class="text-red-600">*</span>
                            </label>

                            <textarea class="min-h-[110px] resize-y w-full rounded-[9px] border border-slate-300 bg-white px-3 py-2.5 text-[#1f2937] outline-none focus:border-blue-600 focus:ring-[3px] focus:ring-blue-600/10" id="reason" placeholder="Nhập lý do đề nghị phúc khảo..."></textarea>
                        </div>

                        <div class="col-span-12 min-[801px]:col-span-6">
                            <label class="mb-[7px] block font-[650]">Minh chứng đính kèm</label>

                            <input class="w-full rounded-[9px] border border-slate-300 bg-white px-3 py-2.5 text-[#1f2937] outline-none focus:border-blue-600 focus:ring-[3px] focus:ring-blue-600/10" id="evidence" type="file" accept=".pdf,.jpg,.jpeg,.png">

                            <small class="text-gray-500">
                                Demo giao diện: PDF/JPG/PNG, tối đa 5MB.
                            </small>
                        </div>

                        <div class="col-span-12 min-[801px]:col-span-6">
                            <label class="mb-[7px] block font-[650]">Thông tin liên hệ</label>
                            <input class="w-full rounded-[9px] border border-slate-300 bg-white px-3 py-2.5 text-[#1f2937] outline-none focus:border-blue-600 focus:ring-[3px] focus:ring-blue-600/10" id="contact" value="0901234567">
                        </div>
                    </div>

                    <div class="mt-3.5 flex flex-wrap gap-2.5">
                        <button class="inline-flex cursor-pointer items-center justify-center gap-2 rounded-[9px] px-[15px] py-2.5 font-bold bg-blue-600 text-white hover:bg-blue-700" type="button" onclick="reviewRequest()">
                            Gửi đăng ký
                        </button>

                        <button class="inline-flex cursor-pointer items-center justify-center gap-2 rounded-[9px] px-[15px] py-2.5 font-bold bg-slate-200 text-slate-900" type="button" onclick="clearForm()">
                            Nhập lại
                        </button>
                    </div>
                </div>

            <?php endif; ?>

        </main>

    </div>

    <div id="confirmModal" class="modal fixed inset-0 z-[1000] hidden items-center justify-center bg-slate-900/55 p-[18px]">
        <div class="w-full max-w-[620px] rounded-[14px] bg-white p-5 shadow-[0_20px_60px_rgba(0,0,0,0.2)]">
            <div class="mb-3 flex items-center justify-between gap-4">
                <h2 class="m-0 text-2xl font-bold">Xác nhận phiếu phúc khảo</h2>

                <button class="cursor-pointer rounded-lg border-0 bg-slate-200 px-2.5 py-[7px]" type="button" onclick="closeModal('confirmModal')">
                    ✕
                </button>
            </div>

            <div id="confirmContent"></div>

            <div class="mt-3.5 flex flex-wrap gap-2.5">
                <button class="inline-flex cursor-pointer items-center justify-center gap-2 rounded-[9px] px-[15px] py-2.5 font-bold bg-slate-200 text-slate-900" type="button" onclick="closeModal('confirmModal')">
                    Quay lại chỉnh sửa
                </button>

                <button class="inline-flex cursor-pointer items-center justify-center gap-2 rounded-[9px] px-[15px] py-2.5 font-bold bg-red-600 text-white" type="button" onclick="cancelRequest()">
                    Hủy
                </button>

                <button class="inline-flex cursor-pointer items-center justify-center gap-2 rounded-[9px] px-[15px] py-2.5 font-bold bg-green-600 text-white" type="button" onclick="submitRequest()">
                    Xác nhận gửi
                </button>
            </div>
        </div>
    </div>

    <div id="resultModal" class="modal fixed inset-0 z-[1000] hidden items-center justify-center bg-slate-900/55 p-[18px]">
        <div class="w-full max-w-[620px] rounded-[14px] bg-white p-5 shadow-[0_20px_60px_rgba(0,0,0,0.2)]">
            <div class="mb-3 flex items-center justify-between gap-4">
                <h2 class="m-0 text-2xl font-bold">Kết quả</h2>

                <button class="cursor-pointer rounded-lg border-0 bg-slate-200 px-2.5 py-[7px]" type="button" onclick="closeModal('resultModal')">
                    ✕
                </button>
            </div>

            <div id="resultContent"></div>

            <div class="mt-3.5 flex flex-wrap gap-2.5">
                <button class="inline-flex cursor-pointer items-center justify-center gap-2 rounded-[9px] px-[15px] py-2.5 font-bold bg-blue-600 text-white hover:bg-blue-700" type="button" onclick="closeModal('resultModal')">
                    Đóng
                </button>
            </div>
        </div>
    </div>

    <script src="../../../assets/js/usecase-tailwind.js"></script>

    <script>
        const scenario = <?= json_encode($scenario) ?>;

        function selectedSubjects() {
            return [...document.querySelectorAll('.subject:checked')].map(x => ({
                name: x.dataset.name,
                score: x.dataset.score
            }));
        }

        function reviewRequest() {
            clearAlert('formAlert');

            const subjects = selectedSubjects();
            const reason = document.getElementById('reason').value.trim();
            const file = document.getElementById('evidence').files[0];
            const contact = document.getElementById('contact').value;

            if (!subjects.length) {
                showAlert(
                    'formAlert',
                    'warning',
                    'Vui lòng chọn ít nhất một môn cần phúc khảo.'
                );
                return;
            }

            if (!reason) {
                showAlert(
                    'formAlert',
                    'danger',
                    'Bạn đang nhập thiếu thông tin bắt buộc: Lý do phúc khảo.'
                );
                return;
            }

            if (file) {
                const allowedTypes = ['application/pdf', 'image/jpeg', 'image/png'];

                if (!allowedTypes.includes(file.type) || file.size > 5 * 1024 * 1024) {
                    showAlert(
                        'formAlert',
                        'danger',
                        'Tệp minh chứng không hợp lệ. Vui lòng chọn lại tệp đúng định dạng và dung lượng.'
                    );
                    return;
                }
            }

            document.getElementById('confirmContent').innerHTML = `
        <p>
            <b>Môn đăng ký:</b>
            ${subjects.map(s => `${s.name} (${s.score})`).join(', ')}
        </p>

        <p class="my-2"><b>Lý do:</b> ${reason}</p>
        <p class="my-2"><b>Liên hệ:</b> ${contact}</p>
        <p class="my-2"><b>Minh chứng:</b> ${file ? file.name : 'Không đính kèm'}</p>

        <div class="my-3 rounded-[10px] border px-[15px] py-[13px] font-semibold border-blue-200 bg-blue-50 text-blue-800">
            Kiểm tra lại thông tin trước khi xác nhận gửi.
        </div>
    `;

            openModal('confirmModal');
        }

        function submitRequest() {
            closeModal('confirmModal');

            document.getElementById('resultContent').innerHTML =
                scenario === 'save_error' ?
                `
            <div class="my-3 rounded-[10px] border px-[15px] py-[13px] font-semibold border-red-200 bg-red-50 text-red-800">
                Đăng ký phúc khảo không thành công, vui lòng thử lại.
                Hệ thống không tạo phiếu trùng.
            </div>
        ` :
                `
            <div class="my-3 rounded-[10px] border px-[15px] py-[13px] font-semibold border-green-200 bg-green-50 text-green-800">
                Đăng ký phúc khảo thành công.
            </div>

            <p class="my-2"><b>Mã phiếu:</b> PK-2027-000128</p>

            <p>
                <b>Trạng thái:</b>
                <span class="inline-block rounded-full px-[9px] py-[5px] text-xs font-extrabold bg-amber-100 text-amber-800">Chờ xử lý</span>
            </p>
        `;

            openModal('resultModal');
        }

        function cancelRequest() {
            closeModal('confirmModal');

            document.getElementById('resultContent').innerHTML = `
        <div class="my-3 rounded-[10px] border px-[15px] py-[13px] font-semibold border-blue-200 bg-blue-50 text-blue-800">
            Đã hủy đăng ký.
            Hệ thống không tạo phiếu phúc khảo.
        </div>
    `;

            openModal('resultModal');
        }

        function clearForm() {
            document.getElementById('reason').value = '';
            document.getElementById('contact').value = '0901234567';
            document.getElementById('evidence').value = '';

            document.querySelectorAll('.subject')
                .forEach(x => x.checked = false);

            clearAlert('formAlert');
        }
    </script>

</body>

</html>