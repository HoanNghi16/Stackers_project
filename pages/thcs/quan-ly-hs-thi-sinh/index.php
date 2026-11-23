<?php
if (session_status() !== PHP_SESSION_ACTIVE) {
    session_start();
}
require_once __DIR__ . '/../../../index.php';
require_once __DIR__ . '/../../../class/quan-ly-hs-thi-sinh/Controller.php';
require_once __DIR__ . '/../../../components/layout/sidebar.php';
require_once __DIR__ . '/../../../components/layout/header.php';

if (($_SESSION['vaitro'] ?? '') !== 'THCS' || empty($_SESSION['manguoidung'])) {
    header('Location: /stackers_project/pages/login/');
    exit;
}

function eHS($value): string
{
    return htmlspecialchars((string)$value, ENT_QUOTES, 'UTF-8');
}

if (isset($_GET['template']) && $_GET['template'] === 'csv') {
    header('Content-Type: text/csv; charset=UTF-8');
    header('Content-Disposition: attachment; filename="mau-danh-sach-thi-sinh.csv"');
    echo "\xEF\xBB\xBF" . "Họ và tên;Ngày sinh;Số điện thoại;Email;Địa chỉ;Phường;Số CCCD;Dân tộc\r\n";
    echo "Nguyễn Văn An;01/01/2010;0901234567;an@example.com;12 Nguyễn Huệ;Phường 1;079201001234;Kinh\r\n";
    exit;
}

if (empty($_SESSION['csrf_quanlyhocsinh'])) {
    $_SESSION['csrf_quanlyhocsinh'] = bin2hex(random_bytes(32));
}
$ctrl = new CtrlQuanLyHoSoThiSinh($conn);
$message = $_SESSION['flash_quanlyhocsinh'] ?? '';
$importErrors = $_SESSION['flash_import_errors'] ?? [];
unset($_SESSION['flash_quanlyhocsinh']);
unset($_SESSION['flash_import_errors']);
$error = '';
$formValues = [];
$action = (string)($_GET['action'] ?? '');
$studentId = max(0, (int)($_GET['mahocsinh'] ?? 0));

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $formValues = $_POST;
    try {
        if (!hash_equals($_SESSION['csrf_quanlyhocsinh'], (string)($_POST['csrf'] ?? ''))) {
            throw new RuntimeException('Phiên làm việc hết hạn. Hãy tải lại trang.');
        }
        if (($_POST['hanhdong'] ?? '') === 'them') {
            $ctrl->ThemHocSinh($_POST);
            $_SESSION['flash_quanlyhocsinh'] = 'Đã thêm hồ sơ học sinh. Mật khẩu mặc định là 1.';
            header('Location: ?');
            exit;
        }
        if (($_POST['hanhdong'] ?? '') === 'sua') {
            $studentId = (int)($_POST['mahocsinh'] ?? 0);
            $ctrl->CapNhatHocSinh($studentId, $_POST);
            $_SESSION['flash_quanlyhocsinh'] = 'Đã cập nhật hồ sơ học sinh.';
            header('Location: ?mahocsinh=' . $studentId);
            exit;
        }
        if (($_POST['hanhdong'] ?? '') === 'nhap_excel') {
            $report = $ctrl->NhapExcel($_FILES['excel_file'] ?? []);
            $_SESSION['flash_quanlyhocsinh'] = "Đã nhập {$report['imported']} hồ sơ; bỏ qua {$report['skipped']} hồ sơ trùng CCCD.";
            $_SESSION['flash_import_errors'] = $report['errors'];
            header('Location: ?');
            exit;
        } else {
            throw new RuntimeException('Thao tác không hợp lệ.');
        }
    } catch (Throwable $exception) {
        if ($exception instanceof mysqli_sql_exception && (int)$exception->getCode() === 1062) {
            $error = 'Email, số điện thoại hoặc CCCD đã được sử dụng bởi tài khoản khác.';
        } elseif ($exception instanceof InvalidArgumentException || $exception instanceof RuntimeException) {
            $error = $exception->getMessage();
        } else {
            $error = 'Không thể lưu hồ sơ. Vui lòng kiểm tra lại thông tin.';
        }
        $action = ($_POST['hanhdong'] ?? '') === 'sua' ? 'edit' : $action;
        $studentId = (int)($_POST['mahocsinh'] ?? $studentId);
    }
}

$filters = [
    'name' => trim((string)($_GET['name'] ?? '')),
    'cccd' => trim((string)($_GET['cccd'] ?? '')),
    'phone' => trim((string)($_GET['phone'] ?? '')),
];
$wards = $ctrl->DanhSachPhuong();
$students = [];
$loadError = '';
try {
    $students = $ctrl->DanhSachHocSinhChuNhiem($filters);
} catch (Throwable $exception) {
    $loadError = $exception->getMessage();
}
$selectedStudent = $studentId > 0 ? $ctrl->ChiTietHocSinh($studentId) : null;
$editValues = $formValues ?: ($selectedStudent ? [
    'name' => $selectedStudent['ten'],
    'dob' => $selectedStudent['ngaysinh'],
    'phone' => $selectedStudent['sodienthoai'],
    'email' => $selectedStudent['email'],
    'address' => $selectedStudent['diachi'],
    'maphuong' => $selectedStudent['maphuong'],
    'cccd' => $selectedStudent['socccd'],
    'ethnic' => $selectedStudent['dantoc'],
] : []);
$filterQuery = http_build_query(array_filter($filters, static fn($value) => $value !== ''));
?>
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Quản lý hồ sơ thí sinh</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="min-h-screen bg-slate-50">
<?php renderHeader(); renderSidebar(); ?>
<main class="ml-72 px-6 pb-12 pt-24">
    <div class="mx-auto max-w-7xl">
        <section class="mb-8 overflow-hidden rounded-3xl bg-gradient-to-br from-blue-700 via-blue-600 to-indigo-700 px-8 py-9 text-white shadow-xl shadow-blue-900/10 md:px-10">
            <div class="max-w-3xl">
                <p class="text-sm font-semibold uppercase tracking-[0.16em] text-blue-200">Cổng quản lý trường THCS</p>
                <h1 class="mt-3 text-3xl font-extrabold leading-tight tracking-tight md:text-4xl">Quản lý hồ sơ thí sinh</h1>
                <p class="mt-4 max-w-2xl text-sm leading-7 text-blue-100 md:text-base">Theo dõi hồ sơ học sinh dự tuyển được phân công cho giáo viên chủ nhiệm.</p>
            </div>
        </section>

        <?php if ($message !== ''): ?><div class="mb-5 rounded-xl border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm text-emerald-800" role="status"><?= eHS($message) ?></div><?php endif; ?>
        <?php if ($error !== ''): ?><div class="mb-5 rounded-xl border border-rose-200 bg-rose-50 px-4 py-3 text-sm text-rose-700" role="alert"><?= eHS($error) ?></div><?php endif; ?>
        <?php if ($loadError !== ''): ?><div class="mb-5 rounded-xl border border-rose-200 bg-rose-50 px-4 py-3 text-sm text-rose-700" role="alert"><?= eHS($loadError) ?></div><?php endif; ?>
        <?php if ($importErrors): ?>
            <section class="mb-5 rounded-xl border border-amber-200 bg-amber-50 p-4 text-sm text-amber-900"><h2 class="font-semibold">Một số dòng chưa nhập được</h2><ul class="mt-2 list-inside list-disc space-y-1"><?php foreach (array_slice($importErrors, 0, 20) as $rowError): ?><li><?= eHS($rowError) ?></li><?php endforeach; ?></ul><?php if (count($importErrors) > 20): ?><p class="mt-2">Còn <?= count($importErrors) - 20 ?> lỗi khác.</p><?php endif; ?></section>
        <?php endif; ?>

        <?php if ($selectedStudent && $action === 'edit'): ?>
            <div class="mb-6 flex items-end justify-between gap-4"><div><p class="text-sm font-semibold uppercase tracking-wider text-blue-600">Thông tin thí sinh</p><h2 class="mt-1 text-2xl font-bold text-slate-800">Chỉnh sửa hồ sơ</h2></div><a href="?mahocsinh=<?= (int)$studentId ?>" class="rounded-xl border border-slate-200 bg-white px-4 py-2.5 text-sm font-semibold text-slate-600">Quay lại chi tiết</a></div>
            <form method="post" class="rounded-2xl border border-slate-200 bg-white p-6 shadow-sm md:p-8"><input type="hidden" name="csrf" value="<?= eHS($_SESSION['csrf_quanlyhocsinh']) ?>"><input type="hidden" name="hanhdong" value="sua"><input type="hidden" name="mahocsinh" value="<?= (int)$studentId ?>">
                <p class="mb-6 border-b border-slate-100 pb-5 text-sm text-slate-500">Vai trò, trường và giáo viên chủ nhiệm được giữ nguyên.</p>
                <div class="grid grid-cols-1 gap-x-5 gap-y-4 md:grid-cols-2">
                    <?php foreach ([['name', 'Họ và tên', 'text', true], ['dob', 'Ngày sinh', 'date', true], ['phone', 'Số điện thoại', 'tel', false], ['email', 'Email', 'email', true], ['address', 'Địa chỉ', 'text', true], ['cccd', 'Số CCCD', 'text', false], ['ethnic', 'Dân tộc', 'text', false]] as [$key, $label, $type, $required]): ?><div><label for="edit-student-<?= eHS($key) ?>" class="mb-1.5 block text-sm font-medium text-slate-700"><?= eHS($label) ?><?= $required ? ' <span class="text-rose-500">*</span>' : '' ?></label><input id="edit-student-<?= eHS($key) ?>" name="<?= eHS($key) ?>" type="<?= eHS($type) ?>" value="<?= eHS($editValues[$key] ?? '') ?>" <?= $required ? 'required' : '' ?> <?= $key === 'cccd' ? 'maxlength="12" pattern="[0-9]{12}"' : '' ?> class="w-full rounded-xl border border-slate-200 bg-white px-3.5 py-2.5 text-sm outline-none focus:border-blue-400 focus:ring-4 focus:ring-blue-100"></div><?php endforeach; ?>
                    <div><label for="edit-student-maphuong" class="mb-1.5 block text-sm font-medium text-slate-700">Phường <span class="text-rose-500">*</span></label><select id="edit-student-maphuong" name="maphuong" required class="w-full rounded-xl border border-slate-200 bg-white px-3.5 py-2.5 text-sm"><option value="">Chọn phường</option><?php foreach ($wards as $ward): ?><option value="<?= (int)$ward['maphuong'] ?>" <?= (string)($editValues['maphuong'] ?? '') === (string)$ward['maphuong'] ? 'selected' : '' ?>><?= eHS($ward['tenphuong']) ?></option><?php endforeach; ?></select></div>
                </div>
                <div class="mt-8 flex gap-3 border-t border-slate-100 pt-5"><button type="submit" class="rounded-xl bg-blue-600 px-6 py-2.5 text-sm font-semibold text-white hover:bg-blue-700">Lưu thay đổi</button><a href="?mahocsinh=<?= (int)$studentId ?>" class="rounded-xl border border-slate-200 px-6 py-2.5 text-sm font-semibold text-slate-600">Hủy</a></div>
            </form>
        <?php elseif ($selectedStudent): ?>
            <div class="mb-6 flex items-end justify-between gap-4"><div><p class="text-sm font-semibold uppercase tracking-wider text-blue-600">Hồ sơ thí sinh</p><h2 class="mt-1 text-2xl font-bold text-slate-800"><?= eHS($selectedStudent['ten']) ?></h2></div><a href="?<?= eHS($filterQuery) ?>" class="rounded-xl border border-slate-200 bg-white px-4 py-2.5 text-sm font-semibold text-slate-600">Quay lại danh sách</a></div>
            <section class="rounded-2xl border border-slate-200 bg-white p-6 shadow-sm md:p-8"><dl class="grid grid-cols-1 gap-5 md:grid-cols-2">
                <?php foreach (['ngaysinh' => 'Ngày sinh', 'sodienthoai' => 'Số điện thoại', 'email' => 'Email', 'socccd' => 'CCCD', 'diachi' => 'Địa chỉ', 'tenphuong' => 'Phường', 'dantoc' => 'Dân tộc'] as $key => $label): ?><div><dt class="text-sm font-medium text-slate-500"><?= eHS($label) ?></dt><dd class="mt-1 font-semibold text-slate-800"><?= eHS($selectedStudent[$key] ?: 'Chưa cập nhật') ?></dd></div><?php endforeach; ?>
            </dl><div class="mt-8 border-t border-slate-100 pt-5"><a href="?mahocsinh=<?= (int)$studentId ?>&action=edit" class="inline-flex rounded-xl bg-blue-600 px-5 py-2.5 text-sm font-semibold text-white hover:bg-blue-700">Chỉnh sửa hồ sơ</a></div></section>
        <?php else: ?>
            <section aria-labelledby="student-list-heading">
                <div class="mb-6 flex flex-col justify-between gap-4 sm:flex-row sm:items-end"><div><h2 id="student-list-heading" class="text-xl font-bold tracking-tight text-slate-800">Danh sách thí sinh</h2><p class="mt-1 text-sm text-slate-500">Hồ sơ được phân công cho bạn trong kỳ tuyển sinh hiện tại.</p></div><span class="inline-flex w-fit items-center rounded-full bg-blue-50 px-3 py-1.5 text-xs font-semibold text-blue-700 ring-1 ring-inset ring-blue-100"><?= count($students) ?> thí sinh</span></div>

                <div class="mb-6 rounded-2xl border border-slate-200/80 bg-white p-5 shadow-sm md:p-6">
                    <div class="mb-4"><h3 class="font-semibold text-slate-800">Tìm kiếm hồ sơ</h3><p class="mt-1 text-sm text-slate-500">Có thể kết hợp tìm theo tên, CCCD và số điện thoại.</p></div>
                    <form method="get" class="grid grid-cols-1 items-end gap-4 md:grid-cols-2 xl:grid-cols-4">
                        <?php foreach ([['name', 'Họ và tên', 'Nhập họ và tên'], ['cccd', 'Số CCCD', 'Nhập số CCCD'], ['phone', 'Số điện thoại', 'Nhập số điện thoại']] as [$key, $label, $placeholder]): ?>
                            <div><label for="search-<?= eHS($key) ?>" class="mb-1.5 block text-sm font-medium text-slate-700"><?= eHS($label) ?></label><input type="search" id="search-<?= eHS($key) ?>" name="<?= eHS($key) ?>" value="<?= eHS($filters[$key]) ?>" placeholder="<?= eHS($placeholder) ?>" class="w-full rounded-xl border border-slate-200 bg-white px-3.5 py-2.5 text-sm outline-none placeholder:text-slate-400 focus:border-blue-400 focus:ring-4 focus:ring-blue-100"></div>
                        <?php endforeach; ?>
                        <div class="flex gap-2"><button type="submit" class="inline-flex flex-1 items-center justify-center gap-2 rounded-xl bg-blue-600 px-5 py-2.5 text-sm font-semibold text-white shadow-sm hover:bg-blue-700">Tìm kiếm</button><a href="?" class="rounded-xl border border-slate-200 px-4 py-2.5 text-sm font-semibold text-slate-600 hover:bg-slate-50">Xóa lọc</a></div>
                    </form>
                </div>

                <div class="mb-5 flex flex-col justify-between gap-3 sm:flex-row sm:items-center"><p class="text-sm text-slate-500">Thêm hồ sơ trực tiếp hoặc nhập nhiều hồ sơ từ tệp.</p><div class="flex flex-wrap gap-3"><label for="modal-add-student" class="inline-flex cursor-pointer items-center justify-center gap-2 rounded-xl bg-blue-600 px-4 py-2.5 text-sm font-semibold text-white shadow-sm hover:bg-blue-700">+ Thêm thí sinh</label><label for="modal-excel-upload" class="inline-flex cursor-pointer items-center justify-center gap-2 rounded-xl border border-slate-200 bg-white px-4 py-2.5 text-sm font-semibold text-slate-700 shadow-sm hover:border-blue-200 hover:bg-blue-50 hover:text-blue-700">Tải lên từ file Excel</label></div></div>

                <div class="overflow-hidden rounded-2xl border border-slate-200/80 bg-white shadow-sm"><div class="max-h-[60vh] overflow-auto"><table class="w-full min-w-[900px] border-collapse text-left">
                    <thead class="sticky top-0 z-10 bg-slate-50 text-xs font-semibold uppercase tracking-wider text-slate-500"><tr><th class="px-5 py-4">STT</th><th class="px-5 py-4">Họ và tên</th><th class="px-5 py-4">Ngày sinh</th><th class="px-5 py-4">Số điện thoại</th><th class="px-5 py-4">Số CCCD</th><th class="px-5 py-4">Thao tác</th></tr></thead>
                    <tbody class="divide-y divide-slate-100 text-sm text-slate-600">
                    <?php foreach ($students as $index => $student): ?><tr class="transition hover:bg-slate-50/80"><td class="px-5 py-4 text-slate-400"><?= $index + 1 ?></td><td class="px-5 py-4"><div class="font-semibold text-slate-800"><?= eHS($student['ten']) ?></div><div class="mt-1 text-xs text-slate-400"><?= eHS($student['email']) ?></div></td><td class="whitespace-nowrap px-5 py-4"><?= eHS($student['ngaysinh']) ?></td><td class="whitespace-nowrap px-5 py-4"><?= eHS($student['sodienthoai'] ?: '—') ?></td><td class="whitespace-nowrap px-5 py-4 font-medium text-slate-700"><?= eHS($student['socccd'] ?: '—') ?></td><td class="px-5 py-4"><a class="font-semibold text-blue-700 hover:underline" href="?mahocsinh=<?= (int)$student['mahocsinh'] ?><?= $filterQuery !== '' ? '&' . eHS($filterQuery) : '' ?>">Xem hồ sơ</a></td></tr><?php endforeach; ?>
                    <?php if (!$students): ?><tr><td colspan="6" class="px-5 py-12 text-center"><p class="font-semibold text-slate-700">Không tìm thấy thí sinh</p><p class="mt-1 text-sm text-slate-500">Thử thay đổi từ khóa hoặc thêm hồ sơ mới.</p></td></tr><?php endif; ?>
                    </tbody>
                </table></div><div class="flex flex-col gap-2 border-t border-slate-100 px-5 py-4 text-sm text-slate-500 sm:flex-row sm:items-center sm:justify-between"><span>Hiển thị <?= count($students) ?> thí sinh</span><span class="text-xs text-slate-400">Hồ sơ kỳ tuyển sinh #1</span></div></div>
            </section>

            <input type="checkbox" id="modal-add-student" class="peer/modal-add sr-only" <?= ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['hanhdong'] ?? '') === 'them' && $error !== '') ? 'checked' : '' ?>>
            <div class="fixed inset-0 z-[1000] hidden items-center justify-center overflow-y-auto p-4 peer-checked/modal-add:flex" role="dialog" aria-modal="true" aria-labelledby="add-student-title"><label for="modal-add-student" class="fixed inset-0 bg-slate-950/50 backdrop-blur-sm" aria-label="Đóng cửa sổ"></label>
                <section class="relative z-10 my-auto max-h-[90vh] w-full max-w-3xl overflow-y-auto rounded-2xl border border-slate-200 bg-white p-6 shadow-2xl md:p-8"><div class="mb-6 flex items-start justify-between gap-4 border-b border-slate-100 pb-5"><div><h2 id="add-student-title" class="text-xl font-bold text-slate-800">Thêm hồ sơ thí sinh</h2><p class="mt-1 text-sm text-slate-500">Tài khoản học sinh sẽ có mật khẩu mặc định là 1.</p></div><label for="modal-add-student" class="cursor-pointer rounded-lg p-2 text-slate-400 hover:bg-slate-100" aria-label="Đóng">✕</label></div>
                    <form method="post" class="grid grid-cols-1 gap-x-5 gap-y-4 md:grid-cols-2"><input type="hidden" name="csrf" value="<?= eHS($_SESSION['csrf_quanlyhocsinh']) ?>"><input type="hidden" name="hanhdong" value="them">
                        <?php foreach ([['name', 'Họ và tên', 'text', true], ['dob', 'Ngày sinh', 'date', true], ['phone', 'Số điện thoại', 'tel', false], ['email', 'Email', 'email', true], ['address', 'Địa chỉ', 'text', true], ['cccd', 'Số CCCD', 'text', false], ['ethnic', 'Dân tộc', 'text', false]] as [$key, $label, $type, $required]): ?><div><label for="student-<?= eHS($key) ?>" class="mb-1.5 block text-sm font-medium text-slate-700"><?= eHS($label) ?><?= $required ? ' <span class="text-rose-500">*</span>' : '' ?></label><input id="student-<?= eHS($key) ?>" name="<?= eHS($key) ?>" type="<?= eHS($type) ?>" value="<?= eHS($formValues[$key] ?? '') ?>" <?= $required ? 'required' : '' ?> <?= $key === 'cccd' ? 'maxlength="12" pattern="[0-9]{12}"' : '' ?> class="w-full rounded-xl border border-slate-200 bg-white px-3.5 py-2.5 text-sm outline-none focus:border-blue-400 focus:ring-4 focus:ring-blue-100"></div><?php endforeach; ?>
                        <div><label for="student-maphuong" class="mb-1.5 block text-sm font-medium text-slate-700">Phường <span class="text-rose-500">*</span></label><select id="student-maphuong" name="maphuong" required class="w-full rounded-xl border border-slate-200 bg-white px-3.5 py-2.5 text-sm"><option value="">Chọn phường</option><?php foreach ($wards as $ward): ?><option value="<?= (int)$ward['maphuong'] ?>" <?= (string)($formValues['maphuong'] ?? '') === (string)$ward['maphuong'] ? 'selected' : '' ?>><?= eHS($ward['tenphuong']) ?></option><?php endforeach; ?></select></div>
                        <div class="md:col-span-2 flex justify-end gap-3 border-t border-slate-100 pt-5"><label for="modal-add-student" class="cursor-pointer rounded-xl border border-slate-200 px-5 py-2.5 text-sm font-semibold text-slate-600">Hủy</label><button type="submit" class="rounded-xl bg-blue-600 px-5 py-2.5 text-sm font-semibold text-white hover:bg-blue-700">Lưu hồ sơ</button></div>
                    </form>
                </section>
            </div>

            <input type="checkbox" id="modal-excel-upload" class="peer/modal-excel sr-only" <?= ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['hanhdong'] ?? '') === 'nhap_excel' && $error !== '') ? 'checked' : '' ?>>
            <div class="fixed inset-0 z-[1000] hidden items-center justify-center overflow-y-auto p-4 peer-checked/modal-excel:flex" role="dialog" aria-modal="true" aria-labelledby="excel-upload-title"><label for="modal-excel-upload" class="fixed inset-0 bg-slate-950/50 backdrop-blur-sm" aria-label="Đóng cửa sổ"></label>
                <section class="relative z-10 my-auto w-full max-w-xl rounded-2xl border border-slate-200 bg-white p-6 shadow-2xl md:p-8"><div class="mb-6 flex items-start justify-between gap-4 border-b border-slate-100 pb-5"><div><h2 id="excel-upload-title" class="text-xl font-bold text-slate-800">Tải danh sách thí sinh</h2><p class="mt-1 text-sm leading-6 text-slate-500">Tải mẫu, điền dữ liệu rồi nhập tệp .xlsx hoặc .csv (tối đa 1.000 dòng).</p><a class="mt-2 inline-block text-sm font-semibold text-blue-700 hover:underline" href="?template=csv">Tải mẫu CSV</a></div><label for="modal-excel-upload" class="cursor-pointer rounded-lg p-2 text-slate-400 hover:bg-slate-100" aria-label="Đóng">✕</label></div>
                    <form method="post" enctype="multipart/form-data"><input type="hidden" name="csrf" value="<?= eHS($_SESSION['csrf_quanlyhocsinh']) ?>"><input type="hidden" name="hanhdong" value="nhap_excel"><label for="excel-file" class="mb-2 block text-sm font-medium text-slate-700">Tệp Excel / CSV</label><input id="excel-file" name="excel_file" type="file" accept=".xlsx,.csv" required class="block w-full rounded-xl border border-slate-200 bg-white p-3 text-sm text-slate-500 file:mr-4 file:rounded-lg file:border-0 file:bg-blue-50 file:px-4 file:py-2 file:font-semibold file:text-blue-700"><p class="mt-3 text-xs leading-5 text-red-600">Cột cần có: Họ và tên, Ngày sinh, Email, Địa chỉ, Phường. Số điện thoại, CCCD và Dân tộc có thể để trống. Có thể ghi “Phường ABC” hoặc “ABC”; nếu chưa có, hệ thống tự thêm phường. Dòng có CCCD đã tồn tại sẽ tự động bỏ qua.</p><div class="mt-6 flex justify-end gap-3 border-t border-slate-100 pt-5"><label for="modal-excel-upload" class="cursor-pointer rounded-xl border border-slate-200 px-5 py-2.5 text-sm font-semibold text-slate-600">Hủy</label><button type="submit" class="rounded-xl bg-blue-600 px-5 py-2.5 text-sm font-semibold text-white hover:bg-blue-700">Nhập danh sách</button></div></form>
                </section>
            </div>
        <?php endif; ?>
    </div>
</main>
</body>
</html>
