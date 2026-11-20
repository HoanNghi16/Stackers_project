<?php
session_start();
require("../../../components/layout/sidebar.php");
require("../../../components/layout/header.php");

$accounts = [
    ['id' => 1, 'name' => 'Nguyễn Văn An', 'phone' => '0901234567', 'email' => 'nguyenvanan@gmail.com', 'cccd' => '079201001234', 'role' => 'Sở GD&ĐT', 'dob' => '01/01/1985', 'address' => '123 Nguyễn Huệ', 'ward' => 'Phường 1'],
    ['id' => 2, 'name' => 'Trần Thị Bình', 'phone' => '0912345678', 'email' => 'tranthibinh@gmail.com', 'cccd' => '079201005678', 'role' => 'Trường THCS', 'dob' => '15/05/1988', 'address' => '45 Lê Lợi', 'ward' => 'Phường 2'],
    ['id' => 3, 'name' => 'Lê Văn Cường', 'phone' => '0923456789', 'email' => 'levancuong@gmail.com', 'cccd' => '079201009999', 'role' => 'Trường THPT', 'dob' => '20/08/1982', 'address' => '78 Trần Hưng Đạo', 'ward' => 'Phường 3'],
    ['id' => 4, 'name' => 'Phạm Minh Dũng', 'phone' => '0934567890', 'email' => '', 'cccd' => '079201001111', 'role' => 'Học sinh', 'dob' => '10/10/2008', 'address' => '25 Hoàng Văn Thụ', 'ward' => 'Phường 4'],
];
$roles = ['Sở GD&ĐT', 'Trường THCS', 'Trường THPT', 'Học sinh'];

function eAccount($value): string
{
    return htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8');
}
?>
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Stackers - Quản lý tài khoản</title>
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
                    <p class="text-sm font-semibold uppercase tracking-[0.16em] text-blue-200">Quản trị hệ thống</p>
                    <h1 class="mt-3 text-3xl font-extrabold leading-tight tracking-tight md:text-4xl">Quản lý tài khoản</h1>
                    <p class="mt-4 max-w-2xl text-sm leading-7 text-blue-100 md:text-base">Theo dõi thông tin và vai trò của người dùng trong hệ thống tuyển sinh.</p>
                </div>
            </section>

            <section id="account-list-section" aria-labelledby="account-list-heading">
                <div class="mb-6 flex flex-col justify-between gap-4 sm:flex-row sm:items-end">
                    <div>
                        <h2 id="account-list-heading" class="text-xl font-bold tracking-tight text-slate-800">Danh sách tài khoản</h2>
                        <p class="mt-1 text-sm text-slate-500">Tìm kiếm, xem thông tin và quản lý tài khoản người dùng.</p>
                    </div>
                    <span class="inline-flex w-fit items-center rounded-full bg-blue-50 px-3 py-1.5 text-xs font-semibold text-blue-700 ring-1 ring-inset ring-blue-100"><?= count($accounts) ?> tài khoản</span>
                </div>

                <div class="mb-5 rounded-2xl border border-slate-200/80 bg-white p-5 shadow-sm md:p-6">
                    <div class="flex flex-col justify-between gap-4 lg:flex-row lg:items-end">
                        <div class="grid flex-1 grid-cols-1 gap-4 sm:grid-cols-[minmax(0,1fr)_220px]">
                            <div>
                                <label for="search-input" class="mb-1.5 block text-sm font-medium text-slate-700">Tìm kiếm</label>
                                <div class="relative">
                                    <svg class="pointer-events-none absolute left-3.5 top-1/2 h-4 w-4 -translate-y-1/2 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true"><circle cx="11" cy="11" r="7" stroke-width="2"/><path stroke-linecap="round" stroke-width="2" d="m16 16 4 4"/></svg>
                                    <input id="search-input" type="search" placeholder="Tìm theo họ tên, SĐT, CCCD..." oninput="searchAccounts()" class="w-full rounded-xl border border-slate-200 bg-white py-2.5 pl-10 pr-4 text-sm text-slate-700 outline-none placeholder:text-slate-400 focus:border-blue-400 focus:ring-4 focus:ring-blue-100">
                                </div>
                            </div>
                            <div>
                                <label for="role-filter" class="mb-1.5 block text-sm font-medium text-slate-700">Vai trò</label>
                                <select id="role-filter" onchange="searchAccounts()" class="w-full rounded-xl border border-slate-200 bg-white px-3.5 py-2.5 text-sm text-slate-700 outline-none focus:border-blue-400 focus:ring-4 focus:ring-blue-100">
                                    <option value="">Tất cả vai trò</option>
                                    <?php foreach ($roles as $role): ?>
                                        <option value="<?= eAccount($role) ?>"><?= eAccount($role) ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                        </div>
                        <button type="button" onclick="showAddForm()" class="inline-flex shrink-0 items-center justify-center gap-2 rounded-xl bg-blue-600 px-5 py-2.5 text-sm font-semibold text-white shadow-sm transition hover:bg-blue-700 focus:outline-none focus:ring-4 focus:ring-blue-200">
                            <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-width="2" d="M12 5v14m7-7H5"/></svg>
                            Thêm tài khoản
                        </button>
                    </div>
                </div>

                <div class="overflow-hidden rounded-2xl border border-slate-200/80 bg-white shadow-sm">
                    <div id="empty-state" class="hidden px-6 py-14 text-center">
                        <div class="mx-auto flex h-14 w-14 items-center justify-center rounded-2xl bg-slate-100 text-slate-400">
                            <svg class="h-7 w-7" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.7" d="M16 21v-2a4 4 0 00-4-4H8a4 4 0 00-4 4v2m16 0v-2a4 4 0 00-3-3.87M10 11a4 4 0 110-8 4 4 0 010 8zm7-7.87a4 4 0 010 7.75"/></svg>
                        </div>
                        <h3 class="mt-4 font-semibold text-slate-800">Không tìm thấy tài khoản</h3>
                        <p class="mt-1 text-sm text-slate-500">Thử thay đổi từ khóa hoặc bộ lọc vai trò.</p>
                    </div>
                    <div id="account-table-wrapper" class="overflow-x-auto">
                        <table class="w-full min-w-[850px] border-collapse text-left">
                            <thead class="bg-slate-50 text-xs font-semibold uppercase tracking-wider text-slate-500">
                                <tr>
                                    <th scope="col" class="px-5 py-4">Họ và tên</th>
                                    <th scope="col" class="px-5 py-4">Số điện thoại</th>
                                    <th scope="col" class="px-5 py-4">Email</th>
                                    <th scope="col" class="px-5 py-4">Vai trò</th>
                                    <th scope="col" class="px-5 py-4">CCCD</th>
                                    <th scope="col" class="px-5 py-4 text-right">Thao tác</th>
                                </tr>
                            </thead>
                            <tbody id="account-table-body" class="divide-y divide-slate-100 text-sm text-slate-600">
                                <?php foreach ($accounts as $account): ?>
                                    <tr class="account-row transition hover:bg-slate-50/80" data-id="<?= (int)$account['id'] ?>" data-name="<?= eAccount(strtolower($account['name'])) ?>" data-phone="<?= eAccount($account['phone']) ?>" data-cccd="<?= eAccount($account['cccd']) ?>" data-role="<?= eAccount($account['role']) ?>">
                                        <td class="px-5 py-4"><div class="font-semibold text-slate-800"><?= eAccount($account['name']) ?></div><div class="mt-1 text-xs text-slate-400">Mã tài khoản #<?= (int)$account['id'] ?></div></td>
                                        <td class="whitespace-nowrap px-5 py-4"><?= eAccount($account['phone']) ?></td>
                                        <td class="px-5 py-4"><?= $account['email'] ? eAccount($account['email']) : '<span class="text-slate-400">Chưa cập nhật</span>' ?></td>
                                        <td class="px-5 py-4"><span class="inline-flex rounded-full bg-blue-50 px-2.5 py-1 text-xs font-semibold text-blue-700"><?= eAccount($account['role']) ?></span></td>
                                        <td class="whitespace-nowrap px-5 py-4 font-medium text-slate-700"><?= eAccount($account['cccd']) ?></td>
                                        <td class="px-5 py-4 text-right"><button type="button" onclick="viewAccount(<?= (int)$account['id'] ?>)" class="inline-flex items-center rounded-lg px-3 py-1.5 font-semibold text-blue-700 transition hover:bg-blue-50">Xem chi tiết</button></td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                    <div class="flex flex-col gap-2 border-t border-slate-100 px-5 py-4 text-sm text-slate-500 sm:flex-row sm:items-center sm:justify-between">
                        <span>Hiển thị <?= count($accounts) ?> tài khoản</span><span class="text-xs text-slate-400">Danh sách tài khoản hệ thống</span>
                    </div>
                </div>
            </section>

            <section id="account-detail-section" class="hidden">
                <div class="mb-6 flex flex-col justify-between gap-3 sm:flex-row sm:items-end">
                    <div><p class="text-sm font-semibold uppercase tracking-wider text-blue-600">Hồ sơ người dùng</p><h2 class="mt-1 text-2xl font-bold tracking-tight text-slate-800">Chi tiết tài khoản</h2></div>
                    <button type="button" onclick="backToList()" class="inline-flex items-center gap-2 rounded-xl border border-slate-200 bg-white px-4 py-2.5 text-sm font-semibold text-slate-600 shadow-sm transition hover:bg-slate-50"><span aria-hidden="true">←</span> Quay lại danh sách</button>
                </div>
                <div class="rounded-2xl border border-slate-200/80 bg-white p-6 shadow-sm md:p-8">
                    <div class="mb-7 flex items-center gap-4 border-b border-slate-100 pb-6">
                        <div class="flex h-14 w-14 items-center justify-center rounded-2xl bg-blue-50 text-xl font-bold text-blue-700"><span id="detail-initial">?</span></div>
                        <div><h3 id="detail-name" class="text-xl font-bold text-slate-800"></h3><p id="detail-role" class="mt-1 inline-flex rounded-full bg-blue-50 px-2.5 py-1 text-xs font-semibold text-blue-700"></p></div>
                    </div>
                    <div class="grid grid-cols-1 gap-x-8 gap-y-6 md:grid-cols-2">
                        <?php foreach ([['phone','Số điện thoại'],['email','Email'],['cccd','Số CCCD'],['dob','Ngày sinh'],['address','Địa chỉ'],['ward','Phường']] as [$field,$label]): ?>
                            <div><p class="text-sm font-medium text-slate-500"><?= eAccount($label) ?></p><p id="detail-<?= eAccount($field) ?>" class="mt-1.5 font-semibold text-slate-800"></p></div>
                        <?php endforeach; ?>
                    </div>
                    <div class="mt-8 border-t border-slate-100 pt-5"><button type="button" onclick="showEditForm()" class="inline-flex items-center gap-2 rounded-xl bg-blue-600 px-5 py-2.5 text-sm font-semibold text-white shadow-sm transition hover:bg-blue-700"><svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="m16 4 4 4M4 20l4-.8L19 8a2.12 2.12 0 00-3-3L5 16l-1 4z"/></svg>Chỉnh sửa tài khoản</button></div>
                </div>
            </section>

            <section id="account-form-section" class="hidden">
                <div class="mb-6 flex flex-col justify-between gap-3 sm:flex-row sm:items-end">
                    <div><p class="text-sm font-semibold uppercase tracking-wider text-blue-600">Thông tin người dùng</p><h2 id="form-title" class="mt-1 text-2xl font-bold tracking-tight text-slate-800">Chỉnh sửa tài khoản</h2></div>
                    <button type="button" onclick="cancelForm()" class="inline-flex items-center gap-2 rounded-xl border border-slate-200 bg-white px-4 py-2.5 text-sm font-semibold text-slate-600 shadow-sm transition hover:bg-slate-50"><span aria-hidden="true">←</span> Quay lại</button>
                </div>
                <div class="rounded-2xl border border-slate-200/80 bg-white p-6 shadow-sm md:p-8">
                    <p class="mb-6 border-b border-slate-100 pb-5 text-sm text-slate-500">Các trường có dấu <span class="font-semibold text-rose-500">*</span> là bắt buộc.</p>
                    <form id="account-form" onsubmit="saveAccount(event)">
                        <div class="grid grid-cols-1 gap-x-5 gap-y-4 md:grid-cols-2">
                            <?php
                            $formFields = [
                                ['name','Họ và tên','text',true,''],
                                ['phone','Số điện thoại','tel',true,''],
                                ['email','Email','email',false,''],
                                ['cccd','Số CCCD','text',true,'12 chữ số'],
                                ['dob','Ngày sinh','date',true,''],
                                ['address','Địa chỉ','text',true,''],
                                ['ward','Phường','text',true,''],
                            ];
                            foreach ($formFields as [$id,$label,$type,$required,$placeholder]):
                            ?>
                                <div>
                                    <label for="<?= eAccount($id) ?>" class="mb-1.5 block text-sm font-medium text-slate-700"><?= eAccount($label) ?><?= $required ? ' <span class="text-rose-500">*</span>' : '' ?></label>
                                    <input id="<?= eAccount($id) ?>" type="<?= eAccount($type) ?>" <?= $id === 'cccd' ? 'maxlength="12"' : '' ?> <?= $required ? 'required' : '' ?> placeholder="<?= eAccount($placeholder) ?>" class="w-full rounded-xl border border-slate-200 bg-white px-3.5 py-2.5 text-sm text-slate-700 outline-none placeholder:text-slate-400 focus:border-blue-400 focus:ring-4 focus:ring-blue-100">
                                </div>
                            <?php endforeach; ?>
                            <div>
                                <label for="role" class="mb-1.5 block text-sm font-medium text-slate-700">Vai trò <span class="text-rose-500">*</span></label>
                                <select id="role" required class="w-full rounded-xl border border-slate-200 bg-white px-3.5 py-2.5 text-sm text-slate-700 outline-none focus:border-blue-400 focus:ring-4 focus:ring-blue-100">
                                    <option value="">Chọn vai trò</option>
                                    <?php foreach ($roles as $role): ?><option value="<?= eAccount($role) ?>"><?= eAccount($role) ?></option><?php endforeach; ?>
                                </select>
                            </div>
                        </div>
                        <div id="form-error" class="mt-5 hidden rounded-xl border border-rose-200 bg-rose-50 p-4 text-sm text-rose-700" role="alert">Lưu thất bại. Vui lòng kiểm tra lại thông tin.</div>
                        <div class="mt-8 flex flex-wrap gap-3 border-t border-slate-100 pt-5">
                            <button type="submit" class="rounded-xl bg-blue-600 px-6 py-2.5 text-sm font-semibold text-white shadow-sm transition hover:bg-blue-700">Lưu tài khoản</button>
                            <button type="button" onclick="cancelForm()" class="rounded-xl border border-slate-200 bg-white px-6 py-2.5 text-sm font-semibold text-slate-600 transition hover:bg-slate-50">Hủy</button>
                        </div>
                    </form>
                </div>
            </section>

            <section id="history-section" class="mt-6 hidden rounded-2xl border border-slate-200/80 bg-white p-6 shadow-sm">
                <h2 class="font-bold text-slate-800">Lịch sử thao tác</h2>
                <div class="mt-5 border-l-2 border-blue-500 pl-4"><p class="font-semibold text-slate-800">Cập nhật thông tin tài khoản</p><p class="mt-1 text-sm text-slate-500">Nhân viên Sở GD&ĐT · Vừa xong</p></div>
            </section>
        </div>
    </main>

    <div id="success-message" class="fixed right-6 top-24 z-[1000] hidden max-w-sm rounded-2xl bg-emerald-600 px-5 py-4 text-white shadow-xl" role="status">
        <div class="font-semibold">Thành công</div><div id="success-text" class="mt-1 text-sm text-emerald-50">Cập nhật tài khoản thành công.</div>
    </div>

<script>
const accounts = <?= json_encode($accounts, JSON_UNESCAPED_UNICODE | JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT) ?>;
let selectedAccount = null;
let editingAccountId = null;
let isAdding = false;

function searchAccounts() {
    const keyword = document.getElementById('search-input').value.toLowerCase().trim();
    const role = document.getElementById('role-filter').value;
    const rows = document.querySelectorAll('.account-row');
    let visibleCount = 0;
    rows.forEach(row => {
        const matchesKeyword = !keyword || row.dataset.name.includes(keyword) || row.dataset.phone.includes(keyword) || row.dataset.cccd.includes(keyword);
        const matchesRole = !role || row.dataset.role === role;
        row.classList.toggle('hidden', !(matchesKeyword && matchesRole));
        if (matchesKeyword && matchesRole) visibleCount++;
    });
    document.getElementById('empty-state').classList.toggle('hidden', visibleCount !== 0);
    document.getElementById('account-table-wrapper').classList.toggle('hidden', visibleCount === 0);
}

function viewAccount(id) {
    const account = accounts.find(item => item.id === id);
    if (!account) return;
    selectedAccount = account;
    document.getElementById('detail-name').textContent = account.name;
    document.getElementById('detail-initial').textContent = account.name.trim().charAt(0).toUpperCase();
    document.getElementById('detail-phone').textContent = account.phone;
    document.getElementById('detail-email').textContent = account.email || 'Chưa cập nhật';
    document.getElementById('detail-cccd').textContent = account.cccd;
    document.getElementById('detail-role').textContent = account.role;
    document.getElementById('detail-dob').textContent = account.dob;
    document.getElementById('detail-address').textContent = account.address;
    document.getElementById('detail-ward').textContent = account.ward;
    document.getElementById('account-list-section').classList.add('hidden');
    document.getElementById('account-detail-section').classList.remove('hidden');
    document.getElementById('history-section').classList.remove('hidden');
}

function showEditForm() {
    if (!selectedAccount) return;
    isAdding = false;
    editingAccountId = selectedAccount.id;
    document.getElementById('form-title').textContent = 'Chỉnh sửa tài khoản';
    fillForm(selectedAccount);
    document.getElementById('form-error').classList.add('hidden');
    document.getElementById('account-detail-section').classList.add('hidden');
    document.getElementById('account-form-section').classList.remove('hidden');
}

function showAddForm() {
    isAdding = true;
    editingAccountId = null;
    document.getElementById('form-title').textContent = 'Thêm tài khoản';
    document.getElementById('account-form').reset();
    document.getElementById('form-error').classList.add('hidden');
    document.getElementById('account-list-section').classList.add('hidden');
    document.getElementById('account-form-section').classList.remove('hidden');
    document.getElementById('history-section').classList.add('hidden');
}

function fillForm(account) {
    ['name', 'phone', 'email', 'cccd', 'role', 'address', 'ward'].forEach(key => document.getElementById(key).value = account[key] || '');
    document.getElementById('dob').value = convertDate(account.dob);
}

function convertDate(date) {
    const parts = date.split('/');
    return parts.length === 3 ? `${parts[2]}-${parts[1]}-${parts[0]}` : '';
}

function saveAccount(event) {
    event.preventDefault();
    const values = Object.fromEntries(['name','phone','email','cccd','role','dob','address','ward'].map(key => [key, document.getElementById(key).value.trim()]));
    const error = document.getElementById('form-error');
    if (Object.entries(values).some(([key, value]) => key !== 'email' && !value)) {
        error.textContent = 'Vui lòng nhập đầy đủ thông tin bắt buộc.';
        error.classList.remove('hidden');
        return;
    }
    if (!/^\d{10,11}$/.test(values.phone)) {
        error.textContent = 'Số điện thoại cần gồm 10 hoặc 11 chữ số.';
        error.classList.remove('hidden');
        return;
    }
    if (!/^\d{12}$/.test(values.cccd)) {
        error.textContent = 'Số CCCD cần gồm 12 chữ số.';
        error.classList.remove('hidden');
        return;
    }
    error.classList.add('hidden');
    showSuccess(isAdding ? 'Thêm tài khoản thành công.' : 'Cập nhật tài khoản thành công.');
}

function showSuccess(message) {
    const success = document.getElementById('success-message');
    document.getElementById('success-text').textContent = message;
    success.classList.remove('hidden');
    window.setTimeout(() => {
        success.classList.add('hidden');
        if (isAdding) backToList(); else cancelForm();
    }, 1800);
}

function backToList() {
    ['account-detail-section','account-form-section','history-section'].forEach(id => document.getElementById(id).classList.add('hidden'));
    document.getElementById('account-list-section').classList.remove('hidden');
}

function cancelForm() {
    document.getElementById('account-form-section').classList.add('hidden');
    if (selectedAccount && !isAdding) {
        document.getElementById('account-detail-section').classList.remove('hidden');
        document.getElementById('history-section').classList.remove('hidden');
    } else {
        backToList();
    }
}
</script>
</body>
</html>
