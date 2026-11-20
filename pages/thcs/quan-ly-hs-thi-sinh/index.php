<?php
session_start();
require("../../../index.php");
require_once("../../../class/quan-ly-hs-thi-sinh/Controller.php");
require("../../../components/layout/sidebar.php");
require("../../../components/layout/header.php");

$ctrl = new CtrlQuanLyHoSoThiSinh($conn);
$students = $ctrl->DanhSachHocSinhChuNhiem();
$students = is_array($students) ? $students : [];

function e($value)
{
    return htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8');
}
?>
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Stackers - Quản lý hồ sơ thí sinh</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="min-h-screen bg-slate-50">
    <?php
        renderHeader();
        renderSidebar();
    ?>

    <main class="ml-72 px-6 pb-12 pt-24">
        <div class="mx-auto max-w-7xl">
            <section class="mb-8 overflow-hidden rounded-3xl bg-gradient-to-br from-blue-700 via-blue-600 to-indigo-700 px-8 py-9 text-white shadow-xl shadow-blue-900/10 md:px-10">
                <div class="max-w-3xl">
                    <p class="text-sm font-semibold uppercase tracking-[0.16em] text-blue-200">Cổng quản lý trường THCS</p>
                    <h1 class="mt-3 text-3xl font-extrabold leading-tight tracking-tight md:text-4xl">Quản lý hồ sơ thí sinh</h1>
                    <p class="mt-4 max-w-2xl text-sm leading-7 text-blue-100 md:text-base">
                        Theo dõi thông tin, rà soát hồ sơ và chuẩn bị danh sách thí sinh dự tuyển của trường.
                    </p>
                </div>
            </section>

            <section aria-labelledby="student-list-heading">
                <div class="mb-6 flex flex-col justify-between gap-4 sm:flex-row sm:items-end">
                    <div>
                        <h2 id="student-list-heading" class="text-xl font-bold tracking-tight text-slate-800">Danh sách thí sinh</h2>
                        <p class="mt-1 text-sm text-slate-500">Thông tin hồ sơ thí sinh đăng ký dự tuyển.</p>
                    </div>
                    <span class="inline-flex w-fit items-center rounded-full bg-blue-50 px-3 py-1.5 text-xs font-semibold text-blue-700 ring-1 ring-inset ring-blue-100">
                        <?= count($students) ?> thí sinh
                    </span>
                </div>

                <div class="mb-6 rounded-2xl border border-slate-200/80 bg-white p-5 shadow-sm md:p-6">
                    <div class="mb-4">
                        <h3 class="font-semibold text-slate-800">Tìm kiếm hồ sơ</h3>
                        <p class="mt-1 text-sm text-slate-500">Nhập thông tin để tra cứu thí sinh.</p>
                    </div>
                    <form method="get" class="grid grid-cols-1 items-end gap-4 md:grid-cols-2 xl:grid-cols-4">
                        <div>
                            <label for="search-name" class="mb-1.5 block text-sm font-medium text-slate-700">Họ và tên</label>
                            <input type="text" id="search-name" name="name" placeholder="Nhập họ và tên" class="w-full rounded-xl border border-slate-200 bg-white px-3.5 py-2.5 text-sm text-slate-700 outline-none placeholder:text-slate-400 focus:border-blue-400 focus:ring-4 focus:ring-blue-100">
                        </div>
                        <div>
                            <label for="search-cccd" class="mb-1.5 block text-sm font-medium text-slate-700">Số CCCD</label>
                            <input type="text" id="search-cccd" name="cccd" placeholder="Nhập số CCCD" class="w-full rounded-xl border border-slate-200 bg-white px-3.5 py-2.5 text-sm text-slate-700 outline-none placeholder:text-slate-400 focus:border-blue-400 focus:ring-4 focus:ring-blue-100">
                        </div>
                        <div>
                            <label for="search-phone" class="mb-1.5 block text-sm font-medium text-slate-700">Số điện thoại</label>
                            <input type="tel" id="search-phone" name="phone" placeholder="Nhập số điện thoại" class="w-full rounded-xl border border-slate-200 bg-white px-3.5 py-2.5 text-sm text-slate-700 outline-none placeholder:text-slate-400 focus:border-blue-400 focus:ring-4 focus:ring-blue-100">
                        </div>
                        <button type="submit" class="inline-flex items-center justify-center gap-2 rounded-xl bg-blue-600 px-5 py-2.5 text-sm font-semibold text-white shadow-sm transition hover:bg-blue-700 focus:outline-none focus:ring-4 focus:ring-blue-200">
                            <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true"><circle cx="11" cy="11" r="7" stroke-width="2"/><path stroke-linecap="round" stroke-width="2" d="m16 16 4 4"/></svg>
                            Tìm kiếm
                        </button>
                    </form>
                </div>

                <div class="mb-5 flex flex-col justify-between gap-3 sm:flex-row sm:items-center">
                    <p class="text-sm text-slate-500">Quản lý hồ sơ và nhập danh sách thí sinh.</p>
                    <div class="flex flex-wrap gap-3">
                        <label for="modal-add-student" class="inline-flex cursor-pointer items-center justify-center gap-2 rounded-xl bg-blue-600 px-4 py-2.5 text-sm font-semibold text-white shadow-sm transition hover:bg-blue-700">
                            <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-width="2" d="M12 5v14m7-7H5"/></svg>
                            Thêm thí sinh
                        </label>
                        <label for="modal-excel-upload" class="inline-flex cursor-pointer items-center justify-center gap-2 rounded-xl border border-slate-200 bg-white px-4 py-2.5 text-sm font-semibold text-slate-700 shadow-sm transition hover:border-blue-200 hover:bg-blue-50 hover:text-blue-700">
                            <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 16V4m0 0L7 9m5-5 5 5M5 20h14"/></svg>
                            Tải lên Excel
                        </label>
                    </div>
                </div>

                <div class="overflow-hidden rounded-2xl border border-slate-200/80 bg-white shadow-sm">
                    <div class="overflow-x-auto">
                        <table class="w-full min-w-[850px] border-collapse text-left">
                            <thead class="bg-slate-50 text-xs font-semibold uppercase tracking-wider text-slate-500">
                                <tr>
                                    <th scope="col" class="px-5 py-4">STT</th>
                                    <th scope="col" class="px-5 py-4">Họ và tên</th>
                                    <th scope="col" class="px-5 py-4">Ngày sinh</th>
                                    <th scope="col" class="px-5 py-4">Số điện thoại</th>
                                    <th scope="col" class="px-5 py-4">Số CCCD</th>
                                    <th scope="col" class="px-5 py-4">Thao tác</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-slate-100 text-sm text-slate-600">
                                <?php foreach ($students as $index => $student): ?>
                                    <tr class="transition hover:bg-slate-50/80">
                                        <td class="px-5 py-4 text-slate-400"><?= $index + 1 ?></td>
                                        <td class="px-5 py-4">
                                            <div class="font-semibold text-slate-800"><?= e($student['ten'] ?? '') ?></div>
                                            <div class="mt-1 text-xs text-slate-400"><?= e(($student['email'] ?? '') ?: 'Chưa cập nhật email') ?></div>
                                        </td>
                                        <td class="whitespace-nowrap px-5 py-4"><?= e($student['ngaysinh'] ?? '') ?></td>
                                        <td class="whitespace-nowrap px-5 py-4"><?= e($student['sodienthoai'] ?? '') ?></td>
                                        <td class="whitespace-nowrap px-5 py-4 font-medium text-slate-700"><?= e($student['cccd'] ?? '') ?></td>
                                        <td class="px-5 py-4">
                                            <span class="inline-flex items-center rounded-lg px-3 py-1.5 font-semibold text-slate-400">—</span>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                                <?php if (!$students): ?>
                                    <tr>
                                        <td colspan="6" class="px-5 py-12 text-center">
                                            <p class="font-semibold text-slate-700">Chưa có thí sinh trong danh sách</p>
                                            <p class="mt-1 text-sm text-slate-500">Danh sách sẽ hiển thị học sinh được phân công cho giáo viên chủ nhiệm.</p>
                                        </td>
                                    </tr>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                    <div class="flex flex-col gap-2 border-t border-slate-100 px-5 py-4 text-sm text-slate-500 sm:flex-row sm:items-center sm:justify-between">
                        <span>Hiển thị <?= count($students) ?> thí sinh</span>
                        <span class="text-xs text-slate-400">Dữ liệu hồ sơ của trường</span>
                    </div>
                </div>
            </section>

            <input type="checkbox" id="modal-add-student" class="peer/modal-add sr-only">
            <div class="fixed inset-0 z-[1000] hidden items-center justify-center overflow-y-auto p-4 peer-checked/modal-add:flex" role="dialog" aria-modal="true" aria-labelledby="add-student-title">
                <label for="modal-add-student" class="fixed inset-0 bg-slate-950/50 backdrop-blur-sm" aria-label="Đóng cửa sổ thêm thí sinh"></label>
                <section class="relative z-10 my-auto max-h-[90vh] w-full max-w-3xl overflow-y-auto rounded-2xl border border-slate-200 bg-white p-6 shadow-2xl md:p-8">
                    <div class="mb-6 flex items-start justify-between gap-4 border-b border-slate-100 pb-5">
                        <div>
                            <h2 id="add-student-title" class="text-xl font-bold tracking-tight text-slate-800">Thêm hồ sơ thí sinh</h2>
                            <p class="mt-1 text-sm text-slate-500">Nhập thông tin hồ sơ thí sinh.</p>
                        </div>
                        <label for="modal-add-student" class="cursor-pointer rounded-lg p-2 text-slate-400 hover:bg-slate-100 hover:text-slate-700" aria-label="Đóng">✕</label>
                    </div>
                    <form class="grid grid-cols-1 gap-x-5 gap-y-4 md:grid-cols-2">
                    <?php
                    $fields = [
                        ['name', 'Họ và tên', 'text', 'Nguyễn Văn An'],
                        ['dob', 'Ngày sinh', 'date', ''],
                        ['phone', 'Số điện thoại', 'tel', 'Nhập số điện thoại'],
                        ['email', 'Email', 'email', 'Nhập địa chỉ email'],
                        ['address', 'Địa chỉ', 'text', 'Nhập địa chỉ'],
                        ['ward', 'Phường', 'text', 'Nhập phường'],
                        ['cccd', 'Số CCCD', 'text', 'Nhập số CCCD'],
                    ];
                    foreach ($fields as [$id, $label, $type, $placeholder]):
                    ?>
                        <div>
                            <label for="student-<?= e($id) ?>" class="mb-1.5 block text-sm font-medium text-slate-700"><?= e($label) ?></label>
                            <input id="student-<?= e($id) ?>" name="<?= e($id) ?>" type="<?= e($type) ?>" placeholder="<?= e($placeholder) ?>" class="w-full rounded-xl border border-slate-200 bg-white px-3.5 py-2.5 text-sm text-slate-700 outline-none placeholder:text-slate-400 focus:border-blue-400 focus:ring-4 focus:ring-blue-100">
                        </div>
                    <?php endforeach; ?>
                    <div>
                        <label for="student-ethnic" class="mb-1.5 block text-sm font-medium text-slate-700">Dân tộc thiểu số</label>
                        <select id="student-ethnic" name="ethnic" class="w-full rounded-xl border border-slate-200 bg-white px-3.5 py-2.5 text-sm text-slate-700 outline-none focus:border-blue-400 focus:ring-4 focus:ring-blue-100">
                            <option value="">Chọn thông tin</option>
                            <option value="no">Không</option>
                            <option value="yes">Có</option>
                        </select>
                    </div>
                        <div class="md:col-span-2 flex justify-end gap-3 border-t border-slate-100 pt-5">
                            <label for="modal-add-student" class="cursor-pointer rounded-xl border border-slate-200 px-5 py-2.5 text-sm font-semibold text-slate-600 hover:bg-slate-50">Hủy</label>
                            <button type="button" class="cursor-not-allowed rounded-xl bg-slate-100 px-5 py-2.5 text-sm font-semibold text-slate-400" aria-disabled="true">Lưu hồ sơ</button>
                        </div>
                    </form>
                </section>
            </div>

            <input type="checkbox" id="modal-excel-upload" class="peer/modal-excel sr-only">
            <div class="fixed inset-0 z-[1000] hidden items-center justify-center overflow-y-auto p-4 peer-checked/modal-excel:flex" role="dialog" aria-modal="true" aria-labelledby="excel-upload-title">
                <label for="modal-excel-upload" class="fixed inset-0 bg-slate-950/50 backdrop-blur-sm" aria-label="Đóng cửa sổ tải Excel"></label>
                <section class="relative z-10 my-auto w-full max-w-xl rounded-2xl border border-slate-200 bg-white p-6 shadow-2xl md:p-8">
                    <div class="mb-6 flex items-start justify-between gap-4 border-b border-slate-100 pb-5">
                        <div>
                            <h2 id="excel-upload-title" class="text-xl font-bold tracking-tight text-slate-800">Tải danh sách thí sinh từ Excel</h2>
                            <p class="mt-1 text-sm leading-6 text-slate-500">Bạn có thể nhập danh sách thí sinh bằng cách tải tệp Excel lên.</p>
                            <span class="text-sm text-red-600">Lưu ý: File Excel phải có những cột sau (“Họ và tên”, “Ngày sinh”, “Số điện thoại”, “Email (nếu có)”, “Địa chỉ”, “Phường”, “Số CCCD”)</span>
                        </div>
                        <label for="modal-excel-upload" class="cursor-pointer rounded-lg p-2 text-slate-400 hover:bg-slate-100 hover:text-slate-700" aria-label="Đóng">✕</label>
                    </div>
                    <div class="rounded-xl border border-blue-100 bg-blue-50 p-4 text-sm leading-6 text-blue-800">
                        Chọn tệp Excel có dữ liệu hồ sơ thí sinh. Việc đọc và nhập dữ liệu từ tệp sẽ được bổ sung sau.
                    </div>
                    <div class="mt-5">
                        <label for="excel-file" class="mb-2 block text-sm font-medium text-slate-700">Tệp Excel</label>
                        <input id="excel-file" type="file" accept=".xlsx,.xls" class="block w-full rounded-xl border border-slate-200 bg-white p-3 text-sm text-slate-500 file:mr-4 file:rounded-lg file:border-0 file:bg-blue-50 file:px-4 file:py-2 file:font-semibold file:text-blue-700 hover:file:bg-blue-100">
                        <p class="mt-2 text-xs text-slate-400">Định dạng hỗ trợ: .xlsx, .xls</p>
                    </div>
                    <div class="mt-6 flex justify-end gap-3 border-t border-slate-100 pt-5">
                        <label for="modal-excel-upload" class="cursor-pointer rounded-xl border border-slate-200 px-5 py-2.5 text-sm font-semibold text-slate-600 hover:bg-slate-50">Đóng</label>
                        <button type="button" class="cursor-not-allowed rounded-xl bg-slate-100 px-5 py-2.5 text-sm font-semibold text-slate-400" aria-disabled="true">Tải tệp lên</button>
                    </div>
                </section>
            </div>
        </div>
    </main>
</body>
</html>
