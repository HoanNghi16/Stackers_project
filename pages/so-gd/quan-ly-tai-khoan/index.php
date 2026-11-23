<?php
if (session_status() !== PHP_SESSION_ACTIVE) {
    session_start();
}
require_once __DIR__ . '/../../../index.php';
require_once __DIR__ . '/../../../class/quan-ly-tai-khoan/Controller.php';
require_once __DIR__ . '/../../../components/layout/header.php';
require_once __DIR__ . '/../../../components/layout/sidebar.php';

if (($env['DEV_FAKE_LOGIN'] ?? '') === '1' && empty($_SESSION['vaitro'])) {
    $_SESSION['vaitro'] = 'SO_GD';
}
if (($_SESSION['vaitro'] ?? '') !== 'SO_GD') {
    header('Location: /stackers_project/pages/login');
    exit;
}

function eAccount($value): string
{
    return htmlspecialchars((string)$value, ENT_QUOTES, 'UTF-8');
}

if (empty($_SESSION['csrf_quanlytaikhoan'])) {
    $_SESSION['csrf_quanlytaikhoan'] = bin2hex(random_bytes(32));
}
$ctrl = new CtrlQuanLyTaiKhoan($conn);
$roles = [
    'SO_GD' => 'Sở GD',
    'THPT' => 'Trường THPT',
    'THCS' => 'Trường THCS',
    'HOC_SINH' => 'Học sinh',
];
$page = max(1, (int)($_GET['page'] ?? 1));
$accountId = max(0, (int)($_GET['mataikhoan'] ?? 0));
$action = (string)($_GET['action'] ?? '');
$searchName = trim((string)($_GET['q'] ?? ''));
$filterRole = (string)($_GET['vaitro'] ?? '');
$filterRole = array_key_exists($filterRole, $roles) ? $filterRole : '';
$filterQuery = http_build_query(array_filter(['q' => $searchName, 'vaitro' => $filterRole], static fn($value) => $value !== ''));
$filterSuffix = $filterQuery !== '' ? '&' . $filterQuery : '';
$notice = $_SESSION['flash_quanlytaikhoan'] ?? '';
unset($_SESSION['flash_quanlytaikhoan']);
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        if (!hash_equals($_SESSION['csrf_quanlytaikhoan'], (string)($_POST['csrf'] ?? ''))) {
            throw new RuntimeException('Phiên làm việc hết hạn. Vui lòng tải lại trang.');
        }
        if (($_POST['hanhdong'] ?? '') === 'them') {
            $newId = $ctrl->ThemTaiKhoan($_POST);
            $_SESSION['flash_quanlytaikhoan'] = 'Thêm tài khoản thành công. Mật khẩu mặc định: 1.';
            header('Location: ?mataikhoan=' . $newId);
            exit;
        }
        if (($_POST['hanhdong'] ?? '') === 'sua') {
            $id = (int)($_POST['mataikhoan'] ?? 0);
            $ctrl->CapNhatTaiKhoan($id, $_POST);
            $_SESSION['flash_quanlytaikhoan'] = 'Cập nhật thông tin tài khoản thành công.';
            header('Location: ?mataikhoan=' . $id);
            exit;
        }
        throw new RuntimeException('Thao tác không hợp lệ.');
    } catch (Throwable $exception) {
        $error = !($exception instanceof mysqli_sql_exception) && ($exception instanceof InvalidArgumentException || $exception instanceof RuntimeException)
            ? $exception->getMessage()
            : 'Không thể lưu tài khoản. Hãy kiểm tra dữ liệu trùng lặp và cập nhật schema cơ sở dữ liệu theo migration.';
        $action = ($_POST['hanhdong'] ?? '') === 'sua' ? 'edit' : 'add';
        $accountId = (int)($_POST['mataikhoan'] ?? 0);
    }
}

$account = $accountId > 0 ? $ctrl->ChiTietTaiKhoan($accountId) : null;
if ($accountId > 0 && !$account && $action !== 'add') {
    $error = 'Không tìm thấy tài khoản.';
    $accountId = 0;
    $action = '';
}
$isForm = in_array($action, ['add', 'edit'], true);
$formAccount = $action === 'edit' && $account ? $account : [];
$totalAccounts = $ctrl->TongSoTaiKhoan($searchName, $filterRole);
$totalPages = max(1, (int)ceil($totalAccounts / 10));
$page = min($page, $totalPages);
$accounts = $ctrl->DanhSachTaiKhoan($page, $searchName, $filterRole);
$wards = $ctrl->DanhSachPhuong();
$schoolsTHCS = $ctrl->DanhSachTruongTHCS();
$schoolsTHPT = $ctrl->DanhSachTruongTHPT();
$formValues = $_SERVER['REQUEST_METHOD'] === 'POST' ? $_POST : $formAccount;

?>
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Quản lý tài khoản</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="min-h-screen bg-slate-50">
<?php renderHeader(); renderSidebar(); ?>
<main class="ml-72 px-6 pb-12 pt-24">
    <div class="mx-auto max-w-7xl">
        <section class="mb-8 rounded-3xl bg-gradient-to-br from-blue-700 to-indigo-700 px-8 py-9 text-white shadow-xl">
            <p class="text-sm font-semibold uppercase tracking-widest text-blue-200">Quản trị hệ thống</p>
            <h1 class="mt-3 text-3xl font-extrabold">Quản lý tài khoản</h1>
            <p class="mt-3 text-blue-100">Danh sách, hồ sơ và vai trò người dùng trong hệ thống tuyển sinh.</p>
        </section>

        <?php if ($notice !== ''): ?><div class="mb-5 rounded-xl border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm text-emerald-800" role="status"><?= eAccount($notice) ?></div><?php endif; ?>
        <?php if ($error !== ''): ?><div class="mb-5 rounded-xl border border-rose-200 bg-rose-50 px-4 py-3 text-sm text-rose-700" role="alert"><?= eAccount($error) ?></div><?php endif; ?>

        <?php if ($isForm): ?>
            <?php $isEdit = $action === 'edit'; ?>
            <div class="mb-6 flex items-end justify-between gap-4">
                <div><p class="text-sm font-semibold uppercase tracking-wider text-blue-600">Thông tin người dùng</p><h2 class="mt-1 text-2xl font-bold text-slate-800"><?= $isEdit ? 'Chỉnh sửa tài khoản' : 'Thêm tài khoản' ?></h2></div>
                <a href="<?= $isEdit ? '?mataikhoan=' . (int)$accountId : '?page=' . $page ?>" class="rounded-xl border border-slate-200 bg-white px-4 py-2.5 text-sm font-semibold text-slate-600">Quay lại</a>
            </div>
            <form method="post" class="rounded-2xl border border-slate-200 bg-white p-6 shadow-sm md:p-8">
                <input type="hidden" name="csrf" value="<?= eAccount($_SESSION['csrf_quanlytaikhoan']) ?>">
                <input type="hidden" name="hanhdong" value="<?= $isEdit ? 'sua' : 'them' ?>">
                <?php if ($isEdit): ?><input type="hidden" name="mataikhoan" value="<?= (int)$accountId ?>"><?php endif; ?>
                <?php if (!$isEdit): ?>
                    <div class="mb-5 rounded-xl bg-blue-50 p-4 text-sm text-blue-800">Mật khẩu mặc định của tài khoản mới là <strong>1</strong>.</div>
                <?php else: ?>
                    <div class="mb-5 rounded-xl bg-slate-50 p-4 text-sm text-slate-600">Vai trò và trường liên kết không thay đổi khi chỉnh sửa thông tin cơ bản.</div>
                <?php endif; ?>
                <div class="grid grid-cols-1 gap-4 md:grid-cols-2">
                    <?php
                    $fields = [
                        ['ho', 'Họ', 'text', true], ['ten', 'Tên', 'text', true],
                        ['sodienthoai', 'Số điện thoại', 'tel', true], ['email', 'Email', 'email', true],
                        ['socccd', 'Số CCCD', 'text', true], ['ngaysinh', 'Ngày sinh', 'date', true],
                        ['diachi', 'Địa chỉ', 'text', true],
                    ];
                    foreach ($fields as [$field, $label, $type, $required]):
                        $value = $formValues[$field] ?? '';
                    ?>
                        <div>
                            <label for="<?= eAccount($field) ?>" class="mb-1.5 block text-sm font-medium text-slate-700"><?= eAccount($label) ?><?= $required ? ' <span class="text-rose-500">*</span>' : '' ?></label>
                            <input id="<?= eAccount($field) ?>" name="<?= eAccount($field) ?>" type="<?= eAccount($type) ?>" value="<?= eAccount($value) ?>" <?= $required ? 'required' : '' ?> <?= $field === 'socccd' ? 'maxlength="12" pattern="[0-9]{12}"' : '' ?> class="w-full rounded-xl border border-slate-200 px-3.5 py-2.5 text-sm outline-none focus:border-blue-400 focus:ring-4 focus:ring-blue-100">
                        </div>
                    <?php endforeach; ?>
                    <div>
                        <label for="maphuong" class="mb-1.5 block text-sm font-medium text-slate-700">Phường <span class="text-rose-500">*</span></label>
                        <select id="maphuong" name="maphuong" required class="w-full rounded-xl border border-slate-200 bg-white px-3.5 py-2.5 text-sm">
                            <option value="">Chọn phường</option>
                            <?php foreach ($wards as $ward): ?><option value="<?= (int)$ward['maphuong'] ?>" <?= (string)($formValues['maphuong'] ?? '') === (string)$ward['maphuong'] ? 'selected' : '' ?>><?= eAccount($ward['tenphuong']) ?></option><?php endforeach; ?>
                        </select>
                    </div>
                    <?php if (!$isEdit): ?>
                        <div>
                            <label for="vaitro" class="mb-1.5 block text-sm font-medium text-slate-700">Vai trò <span class="text-rose-500">*</span></label>
                            <select id="vaitro" name="vaitro" required onchange="updateSchoolOptions()" class="w-full rounded-xl border border-slate-200 bg-white px-3.5 py-2.5 text-sm">
                                <option value="">Chọn vai trò</option>
                                <?php foreach ($roles as $role => $label): ?><option value="<?= eAccount($role) ?>" <?= ($formValues['vaitro'] ?? '') === $role ? 'selected' : '' ?>><?= eAccount($label) ?></option><?php endforeach; ?>
                            </select>
                        </div>
                        <div id="school-field" class="hidden">
                            <label for="matruong" class="mb-1.5 block text-sm font-medium text-slate-700">Trường học <span class="text-rose-500">*</span></label>
                            <select id="matruong" name="matruong" class="w-full rounded-xl border border-slate-200 bg-white px-3.5 py-2.5 text-sm">
                                <option value="">Chọn trường</option>
                                <?php foreach ($schoolsTHCS as $school): ?><option data-role="HOC_SINH,THCS" data-kind="THCS" value="<?= (int)$school['matruong'] ?>" <?= (string)($formValues['matruong'] ?? '') === (string)$school['matruong'] ? 'selected' : '' ?>><?= eAccount($school['tentruong']) ?> (THCS)</option><?php endforeach; ?>
                                <?php foreach ($schoolsTHPT as $school): ?><option data-role="THPT" data-kind="THPT" value="<?= (int)$school['matruong'] ?>" <?= (string)($formValues['matruong'] ?? '') === (string)$school['matruong'] ? 'selected' : '' ?>><?= eAccount($school['tentruong']) ?> (THPT)</option><?php endforeach; ?>
                            </select>
                        </div>
                    <?php else: ?>
                        <div>
                            <label class="mb-1.5 block text-sm font-medium text-slate-700">Vai trò</label>
                            <div class="rounded-xl border border-slate-200 bg-slate-50 px-3.5 py-2.5 text-sm text-slate-600"><?= eAccount($roles[$account['vaitro']] ?? $account['vaitro']) ?></div>
                        </div>
                    <?php endif; ?>
                </div>
                <div class="mt-8 flex gap-3 border-t border-slate-100 pt-5">
                    <button class="rounded-xl bg-blue-600 px-6 py-2.5 text-sm font-semibold text-white hover:bg-blue-700" type="submit">Lưu tài khoản</button>
                    <a href="<?= $isEdit ? '?mataikhoan=' . (int)$accountId : '?page=' . $page ?>" class="rounded-xl border border-slate-200 px-6 py-2.5 text-sm font-semibold text-slate-600">Hủy</a>
                </div>
            </form>
            <script>
            function updateSchoolOptions() {
                const role = document.getElementById('vaitro').value;
                const field = document.getElementById('school-field');
                const select = document.getElementById('matruong');
                const needsSchool = ['HOC_SINH', 'THCS', 'THPT'].includes(role);
                field.classList.toggle('hidden', !needsSchool);
                select.required = needsSchool;
                [...select.options].forEach(option => {
                    if (!option.value) return;
                    option.hidden = !option.dataset.role.split(',').includes(role);
                    if (option.hidden && option.selected) select.value = '';
                });
            }
            updateSchoolOptions();
            </script>
        <?php elseif ($account): ?>
            <div class="mb-6 flex items-end justify-between gap-4">
                <div><p class="text-sm font-semibold uppercase tracking-wider text-blue-600">Hồ sơ người dùng · #<?= (int)$account['manguoidung'] ?></p><h2 class="mt-1 text-2xl font-bold text-slate-800">Chi tiết tài khoản</h2></div>
                <a href="?page=<?= $page ?>" class="rounded-xl border border-slate-200 bg-white px-4 py-2.5 text-sm font-semibold text-slate-600">Quay lại danh sách</a>
            </div>
            <div class="rounded-2xl border border-slate-200 bg-white p-6 shadow-sm md:p-8">
                <div class="mb-6 border-b border-slate-100 pb-5"><h3 class="text-xl font-bold text-slate-800"><?= eAccount(trim($account['ho'] . ' ' . $account['ten'])) ?></h3><span class="mt-2 inline-flex rounded-full bg-blue-50 px-2.5 py-1 text-xs font-semibold text-blue-700"><?= eAccount($roles[$account['vaitro']] ?? $account['vaitro']) ?></span></div>
                <dl class="grid grid-cols-1 gap-5 md:grid-cols-2">
                    <?php foreach ([
                        'sodienthoai' => 'Số điện thoại', 'email' => 'Email', 'socccd' => 'Số CCCD',
                        'ngaysinh' => 'Ngày sinh', 'diachi' => 'Địa chỉ', 'tenphuong' => 'Phường', 'tentruong' => 'Trường',
                    ] as $field => $label): ?>
                        <div><dt class="text-sm font-medium text-slate-500"><?= eAccount($label) ?></dt><dd class="mt-1 font-semibold text-slate-800"><?= eAccount($account[$field] ?: 'Chưa cập nhật') ?></dd></div>
                    <?php endforeach; ?>
                </dl>
                <div class="mt-8 border-t border-slate-100 pt-5"><a href="?mataikhoan=<?= (int)$accountId ?>&action=edit" class="inline-flex rounded-xl bg-blue-600 px-5 py-2.5 text-sm font-semibold text-white hover:bg-blue-700">Chỉnh sửa thông tin cơ bản</a></div>
            </div>
        <?php else: ?>
            <div class="mb-6 flex flex-col justify-between gap-4 sm:flex-row sm:items-end">
                <div><h2 class="text-xl font-bold text-slate-800">Danh sách tài khoản</h2><p class="mt-1 text-sm text-slate-500">Tổng cộng <?= $totalAccounts ?> tài khoản</p></div>
                <a href="?action=add" class="rounded-xl bg-blue-600 px-5 py-2.5 text-sm font-semibold text-white hover:bg-blue-700">+ Thêm tài khoản</a>
            </div>
            <form method="get" class="mb-5 grid grid-cols-1 gap-4 rounded-2xl border border-slate-200 bg-white p-5 shadow-sm sm:grid-cols-[minmax(0,1fr)_240px_auto_auto] sm:items-end">
                <div>
                    <label for="search-name" class="mb-1.5 block text-sm font-medium text-slate-700">Tìm theo tên người dùng</label>
                    <input id="search-name" name="q" type="search" value="<?= eAccount($searchName) ?>" placeholder="Nhập họ hoặc tên..." class="w-full rounded-xl border border-slate-200 px-3.5 py-2.5 text-sm outline-none placeholder:text-slate-400 focus:border-blue-400 focus:ring-4 focus:ring-blue-100">
                </div>
                <div>
                    <label for="filter-role" class="mb-1.5 block text-sm font-medium text-slate-700">Vai trò</label>
                    <select id="filter-role" name="vaitro" class="w-full rounded-xl border border-slate-200 bg-white px-3.5 py-2.5 text-sm outline-none focus:border-blue-400 focus:ring-4 focus:ring-blue-100">
                        <option value="">Tất cả vai trò</option>
                        <?php foreach ($roles as $roleValue => $roleLabel): ?><option value="<?= eAccount($roleValue) ?>" <?= $filterRole === $roleValue ? 'selected' : '' ?>><?= eAccount($roleLabel) ?></option><?php endforeach; ?>
                    </select>
                </div>
                <button type="submit" class="rounded-xl bg-blue-600 px-5 py-2.5 text-sm font-semibold text-white hover:bg-blue-700">Tìm kiếm</button>
                <a href="?" class="rounded-xl border border-slate-200 px-5 py-2.5 text-center text-sm font-semibold text-slate-600 hover:bg-slate-50">Xóa lọc</a>
            </form>
            <div class="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm">
                <div class="max-h-[60vh] overflow-auto">
                    <table class="w-full min-w-[850px] text-left text-sm">
                        <thead class="sticky top-0 z-10 bg-slate-50 text-xs font-semibold uppercase tracking-wider text-slate-500"><tr><th class="px-5 py-4">Họ và tên</th><th class="px-5 py-4">Số điện thoại</th><th class="px-5 py-4">Email</th><th class="px-5 py-4">Vai trò</th><th class="px-5 py-4">Trường</th><th class="px-5 py-4">CCCD</th><th class="px-5 py-4 text-right">Thao tác</th></tr></thead>
                        <tbody class="divide-y divide-slate-100 text-slate-600">
                        <?php if (!$accounts): ?><tr><td colspan="7" class="px-5 py-12 text-center text-slate-500">Chưa có tài khoản trong danh sách.</td></tr><?php endif; ?>
                        <?php foreach ($accounts as $item): ?>
                            <tr class="hover:bg-slate-50"><td class="px-5 py-4"><div class="font-semibold text-slate-800"><?= eAccount($item['ten']) ?></div><div class="mt-1 text-xs text-slate-400">Mã người dùng #<?= (int)$item['manguoidung'] ?></div></td><td class="px-5 py-4"><?= eAccount($item['sodienthoai'] ?: '—') ?></td><td class="px-5 py-4"><?= eAccount($item['email'] ?: '—') ?></td><td class="px-5 py-4"><?= eAccount($roles[$item['vaitro']] ?? $item['vaitro']) ?></td><td class="px-5 py-4"><?= eAccount($item['tentruong'] ?: '—') ?></td><td class="px-5 py-4"><?= eAccount($item['socccd'] ?: '—') ?></td><td class="px-5 py-4 text-right"><a class="font-semibold text-blue-700 hover:underline" href="?mataikhoan=<?= (int)$item['manguoidung'] ?>&page=<?= $page ?><?= eAccount($filterSuffix) ?>">Xem chi tiết</a></td></tr>
                        <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
                <div class="flex flex-col gap-3 border-t border-slate-100 px-5 py-4 text-sm text-slate-500 sm:flex-row sm:items-center sm:justify-between">
                    <span>Trang <?= $page ?> / <?= $totalPages ?></span>
                    <nav class="flex items-center gap-2" aria-label="Phân trang">
                        <?php if ($page > 1): ?><a class="rounded-lg border border-slate-200 px-3 py-1.5 hover:bg-slate-50" href="?page=<?= $page - 1 ?><?= eAccount($filterSuffix) ?>">Trước</a><?php endif; ?>
                        <?php for ($p = max(1, $page - 2); $p <= min($totalPages, $page + 2); $p++): ?><a class="rounded-lg px-3 py-1.5 <?= $p === $page ? 'bg-blue-600 text-white' : 'border border-slate-200 hover:bg-slate-50' ?>" href="?page=<?= $p ?><?= eAccount($filterSuffix) ?>" aria-current="<?= $p === $page ? 'page' : 'false' ?>"><?= $p ?></a><?php endfor; ?>
                        <?php if ($page < $totalPages): ?><a class="rounded-lg border border-slate-200 px-3 py-1.5 hover:bg-slate-50" href="?page=<?= $page + 1 ?><?= eAccount($filterSuffix) ?>">Tiếp</a><?php endif; ?>
                    </nav>
                </div>
            </div>
        <?php endif; ?>
    </div>
</main>
</body>
</html>
