<?php
$accounts = [
    [
        'id' => 1,
        'name' => 'Nguyễn Văn An',
        'phone' => '0901234567',
        'email' => 'nguyenvanan@gmail.com',
        'cccd' => '079201001234',
        'role' => 'Sở GD&ĐT',
        'dob' => '01/01/1985',
        'address' => '123 Nguyễn Huệ',
        'ward' => 'Phường 1',
    ],
    [
        'id' => 2,
        'name' => 'Trần Thị Bình',
        'phone' => '0912345678',
        'email' => 'tranthibinh@gmail.com',
        'cccd' => '079201005678',
        'role' => 'Trường THCS',
        'dob' => '15/05/1988',
        'address' => '45 Lê Lợi',
        'ward' => 'Phường 2',
    ],
    [
        'id' => 3,
        'name' => 'Lê Văn Cường',
        'phone' => '0923456789',
        'email' => 'levancuong@gmail.com',
        'cccd' => '079201009999',
        'role' => 'Trường THPT',
        'dob' => '20/08/1982',
        'address' => '78 Trần Hưng Đạo',
        'ward' => 'Phường 3',
    ],
    [
        'id' => 4,
        'name' => 'Phạm Minh Dũng',
        'phone' => '0934567890',
        'email' => '',
        'cccd' => '079201001111',
        'role' => 'Học sinh',
        'dob' => '10/10/2008',
        'address' => '25 Hoàng Văn Thụ',
        'ward' => 'Phường 4',
    ],
];

$roles = [
    'Sở GD&ĐT',
    'Trường THCS',
    'Trường THPT',
    'Học sinh'
];
?>

<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>Quản lý tài khoản</title>

    <script src="https://cdn.tailwindcss.com"></script>
</head>

<body class="bg-gray-100 min-h-screen">

<div class="max-w-7xl mx-auto p-6">

    <!-- HEADER -->
    <div class="mb-6">
        <h1 class="text-2xl font-bold text-gray-800">
            Quản lý tài khoản
        </h1>

        <p class="text-gray-500 mt-1">
            Quản lý thông tin tài khoản người dùng trong hệ thống
        </p>
    </div>


    <!-- ========================= -->
    <!-- DANH SÁCH TÀI KHOẢN -->
    <!-- ========================= -->

    <div id="account-list-section"
         class="bg-white rounded-xl shadow-sm border border-gray-200">

        <!-- TOOLBAR -->
        <div class="p-5 border-b border-gray-200">

            <div class="flex flex-col md:flex-row gap-3 justify-between">

                <div class="flex flex-col sm:flex-row gap-3 flex-1">

                    <input
                        id="search-input"
                        type="text"
                        placeholder="Tìm theo họ tên, SĐT, CCCD..."
                        class="border border-gray-300 rounded-lg px-4 py-2.5
                               focus:outline-none focus:ring-2 focus:ring-blue-500
                               w-full sm:max-w-md"
                        oninput="searchAccounts()"
                    >

                    <select
                        id="role-filter"
                        onchange="searchAccounts()"
                        class="border border-gray-300 rounded-lg px-4 py-2.5
                               focus:outline-none focus:ring-2 focus:ring-blue-500"
                    >
                        <option value="">Tất cả vai trò</option>

                        <?php foreach ($roles as $role): ?>
                            <option value="<?= htmlspecialchars($role) ?>">
                                <?= htmlspecialchars($role) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>

                </div>

                <button
                    onclick="showAddForm()"
                    class="bg-blue-600 hover:bg-blue-700 text-white
                           px-5 py-2.5 rounded-lg font-medium"
                >
                    + Thêm tài khoản
                </button>

            </div>
        </div>


        <!-- EMPTY STATE -->
        <div id="empty-state"
             class="hidden p-12 text-center">

            <div class="text-5xl mb-4">
                📭
            </div>

            <h2 class="text-lg font-semibold text-gray-700">
                Chưa có tài khoản người dùng
            </h2>

            <p class="text-gray-500 mt-2">
                Hệ thống chưa tìm thấy tài khoản phù hợp.
            </p>

        </div>


        <!-- TABLE -->
        <div id="account-table-wrapper"
             class="overflow-x-auto">

            <table class="w-full">

                <thead class="bg-gray-50 border-b border-gray-200">

                    <tr class="text-left text-sm text-gray-600">

                        <th class="px-5 py-3">
                            Họ và tên
                        </th>

                        <th class="px-5 py-3">
                            Số điện thoại
                        </th>

                        <th class="px-5 py-3">
                            Email
                        </th>

                        <th class="px-5 py-3">
                            Vai trò
                        </th>

                        <th class="px-5 py-3">
                            CCCD
                        </th>

                        <th class="px-5 py-3 text-center">
                            Thao tác
                        </th>

                    </tr>

                </thead>

                <tbody id="account-table-body"
                       class="divide-y divide-gray-100">

                    <?php foreach ($accounts as $account): ?>

                        <tr
                            class="account-row hover:bg-gray-50"
                            data-id="<?= $account['id'] ?>"
                            data-name="<?= htmlspecialchars(strtolower($account['name'])) ?>"
                            data-phone="<?= htmlspecialchars($account['phone']) ?>"
                            data-cccd="<?= htmlspecialchars($account['cccd']) ?>"
                            data-role="<?= htmlspecialchars($account['role']) ?>"
                        >

                            <td class="px-5 py-4 font-medium text-gray-800">
                                <?= htmlspecialchars($account['name']) ?>
                            </td>

                            <td class="px-5 py-4 text-gray-600">
                                <?= htmlspecialchars($account['phone']) ?>
                            </td>

                            <td class="px-5 py-4 text-gray-600">
                                <?= $account['email']
                                    ? htmlspecialchars($account['email'])
                                    : '<span class="text-gray-400">Không có</span>' ?>
                            </td>

                            <td class="px-5 py-4">

                                <span class="inline-flex px-3 py-1
                                             rounded-full bg-blue-50
                                             text-blue-700 text-sm">

                                    <?= htmlspecialchars($account['role']) ?>

                                </span>

                            </td>

                            <td class="px-5 py-4 text-gray-600">
                                <?= htmlspecialchars($account['cccd']) ?>
                            </td>

                            <td class="px-5 py-4 text-center">

                                <button
                                    onclick="viewAccount(<?= $account['id'] ?>)"
                                    class="text-blue-600 hover:text-blue-800
                                           font-medium"
                                >
                                    Xem
                                </button>

                            </td>

                        </tr>

                    <?php endforeach; ?>

                </tbody>

            </table>

        </div>

    </div>


    <!-- ========================= -->
    <!-- CHI TIẾT TÀI KHOẢN -->
    <!-- ========================= -->

    <div id="account-detail-section"
         class="hidden">

        <div class="bg-white rounded-xl shadow-sm border border-gray-200">

            <!-- HEADER -->
            <div class="p-5 border-b border-gray-200
                        flex justify-between items-center">

                <div>

                    <h2 class="text-xl font-semibold text-gray-800">
                        Chi tiết tài khoản
                    </h2>

                    <p class="text-gray-500 mt-1">
                        Thông tin tài khoản người dùng
                    </p>

                </div>

                <button
                    onclick="backToList()"
                    class="text-gray-600 hover:text-gray-900"
                >
                    ← Quay lại
                </button>

            </div>


            <!-- DETAIL -->
            <div class="p-6">

                <div class="grid grid-cols-1 md:grid-cols-2 gap-5">

                    <div>
                        <label class="text-sm text-gray-500">
                            Họ và tên
                        </label>

                        <p id="detail-name"
                           class="font-medium text-gray-800 mt-1">
                        </p>
                    </div>


                    <div>
                        <label class="text-sm text-gray-500">
                            Số điện thoại
                        </label>

                        <p id="detail-phone"
                           class="font-medium text-gray-800 mt-1">
                        </p>
                    </div>


                    <div>
                        <label class="text-sm text-gray-500">
                            Email
                        </label>

                        <p id="detail-email"
                           class="font-medium text-gray-800 mt-1">
                        </p>
                    </div>


                    <div>
                        <label class="text-sm text-gray-500">
                            Số CCCD
                        </label>

                        <p id="detail-cccd"
                           class="font-medium text-gray-800 mt-1">
                        </p>
                    </div>


                    <div>
                        <label class="text-sm text-gray-500">
                            Vai trò
                        </label>

                        <p id="detail-role"
                           class="font-medium text-gray-800 mt-1">
                        </p>
                    </div>


                    <div>
                        <label class="text-sm text-gray-500">
                            Ngày sinh
                        </label>

                        <p id="detail-dob"
                           class="font-medium text-gray-800 mt-1">
                        </p>
                    </div>


                    <div>
                        <label class="text-sm text-gray-500">
                            Địa chỉ
                        </label>

                        <p id="detail-address"
                           class="font-medium text-gray-800 mt-1">
                        </p>
                    </div>


                    <div>
                        <label class="text-sm text-gray-500">
                            Phường
                        </label>

                        <p id="detail-ward"
                           class="font-medium text-gray-800 mt-1">
                        </p>
                    </div>

                </div>


                <!-- ACTION -->
                <div class="mt-8 pt-5 border-t border-gray-200">

                    <button
                        onclick="showEditForm()"
                        class="bg-blue-600 hover:bg-blue-700
                               text-white px-5 py-2.5 rounded-lg
                               font-medium"
                    >
                        Chỉnh sửa
                    </button>

                </div>

            </div>

        </div>

    </div>


    <!-- ========================= -->
    <!-- FORM THÊM / CHỈNH SỬA -->
    <!-- ========================= -->

    <div id="account-form-section"
         class="hidden">

        <div class="bg-white rounded-xl shadow-sm border border-gray-200">

            <!-- HEADER -->
            <div class="p-5 border-b border-gray-200
                        flex justify-between items-center">

                <div>

                    <h2 id="form-title"
                        class="text-xl font-semibold text-gray-800">
                        Chỉnh sửa tài khoản
                    </h2>

                    <p class="text-gray-500 mt-1">
                        Nhập thông tin tài khoản
                    </p>

                </div>

                <button
                    onclick="cancelForm()"
                    class="text-gray-600 hover:text-gray-900"
                >
                    ← Quay lại
                </button>

            </div>


            <!-- FORM -->
            <form id="account-form"
                  class="p-6"
                  onsubmit="saveAccount(event)">

                <div class="grid grid-cols-1 md:grid-cols-2 gap-5">


                    <!-- HỌ TÊN -->
                    <div>

                        <label class="block text-sm font-medium
                                      text-gray-700 mb-1">

                            Họ và tên
                            <span class="text-red-500">*</span>

                        </label>

                        <input
                            id="name"
                            type="text"
                            class="w-full border border-gray-300
                                   rounded-lg px-4 py-2.5
                                   focus:outline-none
                                   focus:ring-2 focus:ring-blue-500"
                            required
                        >

                    </div>


                    <!-- SĐT -->
                    <div>

                        <label class="block text-sm font-medium
                                      text-gray-700 mb-1">

                            Số điện thoại
                            <span class="text-red-500">*</span>

                        </label>

                        <input
                            id="phone"
                            type="tel"
                            class="w-full border border-gray-300
                                   rounded-lg px-4 py-2.5
                                   focus:outline-none
                                   focus:ring-2 focus:ring-blue-500"
                            required
                        >

                    </div>


                    <!-- EMAIL -->
                    <div>

                        <label class="block text-sm font-medium
                                      text-gray-700 mb-1">

                            Email

                        </label>

                        <input
                            id="email"
                            type="email"
                            class="w-full border border-gray-300
                                   rounded-lg px-4 py-2.5
                                   focus:outline-none
                                   focus:ring-2 focus:ring-blue-500"
                        >

                    </div>


                    <!-- CCCD -->
                    <div>

                        <label class="block text-sm font-medium
                                      text-gray-700 mb-1">

                            Số CCCD
                            <span class="text-red-500">*</span>

                        </label>

                        <input
                            id="cccd"
                            type="text"
                            maxlength="12"
                            class="w-full border border-gray-300
                                   rounded-lg px-4 py-2.5
                                   focus:outline-none
                                   focus:ring-2 focus:ring-blue-500"
                            required
                        >

                    </div>


                    <!-- VAI TRÒ -->
                    <div>

                        <label class="block text-sm font-medium
                                      text-gray-700 mb-1">

                            Vai trò
                            <span class="text-red-500">*</span>

                        </label>

                        <select
                            id="role"
                            class="w-full border border-gray-300
                                   rounded-lg px-4 py-2.5
                                   focus:outline-none
                                   focus:ring-2 focus:ring-blue-500"
                            required
                        >

                            <option value="">
                                -- Chọn vai trò --
                            </option>

                            <?php foreach ($roles as $role): ?>

                                <option value="<?= htmlspecialchars($role) ?>">
                                    <?= htmlspecialchars($role) ?>
                                </option>

                            <?php endforeach; ?>

                        </select>

                    </div>


                    <!-- NGÀY SINH -->
                    <div>

                        <label class="block text-sm font-medium
                                      text-gray-700 mb-1">

                            Ngày sinh
                            <span class="text-red-500">*</span>

                        </label>

                        <input
                            id="dob"
                            type="date"
                            class="w-full border border-gray-300
                                   rounded-lg px-4 py-2.5
                                   focus:outline-none
                                   focus:ring-2 focus:ring-blue-500"
                            required
                        >

                    </div>


                    <!-- ĐỊA CHỈ -->
                    <div>

                        <label class="block text-sm font-medium
                                      text-gray-700 mb-1">

                            Địa chỉ
                            <span class="text-red-500">*</span>

                        </label>

                        <input
                            id="address"
                            type="text"
                            class="w-full border border-gray-300
                                   rounded-lg px-4 py-2.5
                                   focus:outline-none
                                   focus:ring-2 focus:ring-blue-500"
                            required
                        >

                    </div>


                    <!-- PHƯỜNG -->
                    <div>

                        <label class="block text-sm font-medium
                                      text-gray-700 mb-1">

                            Phường
                            <span class="text-red-500">*</span>

                        </label>

                        <input
                            id="ward"
                            type="text"
                            class="w-full border border-gray-300
                                   rounded-lg px-4 py-2.5
                                   focus:outline-none
                                   focus:ring-2 focus:ring-blue-500"
                            required
                        >

                    </div>

                </div>


                <!-- ERROR -->
                <div id="form-error"
                     class="hidden mt-5 p-4 rounded-lg
                            bg-red-50 border border-red-200
                            text-red-700">

                    Lưu thất bại. Vui lòng kiểm tra lại thông tin.

                </div>


                <!-- BUTTON -->
                <div class="mt-8 pt-5 border-t border-gray-200
                            flex gap-3">

                    <button
                        type="submit"
                        class="bg-blue-600 hover:bg-blue-700
                               text-white px-6 py-2.5
                               rounded-lg font-medium"
                    >
                        Lưu
                    </button>

                    <button
                        type="button"
                        onclick="cancelForm()"
                        class="border border-gray-300
                               hover:bg-gray-50
                               px-6 py-2.5 rounded-lg"
                    >
                        Hủy
                    </button>

                </div>

            </form>

        </div>

    </div>


    <!-- ========================= -->
    <!-- LỊCH SỬ THAY ĐỔI -->
    <!-- ========================= -->

    <div id="history-section"
         class="hidden mt-6">

        <div class="bg-white rounded-xl shadow-sm
                    border border-gray-200">

            <div class="p-5 border-b border-gray-200">

                <h2 class="font-semibold text-gray-800">
                    Lịch sử thao tác
                </h2>

            </div>

            <div class="p-5">

                <div class="border-l-2 border-blue-500 pl-4">

                    <p class="font-medium text-gray-800">
                        Cập nhật thông tin tài khoản
                    </p>

                    <p class="text-sm text-gray-500 mt-1">
                        Nhân viên Sở GD&ĐT
                        · Vừa xong
                    </p>

                </div>

            </div>

        </div>

    </div>


    <!-- ========================= -->
    <!-- SUCCESS MESSAGE -->
    <!-- ========================= -->

    <div id="success-message"
         class="hidden fixed top-6 right-6
                bg-green-600 text-white
                px-5 py-4 rounded-lg shadow-lg">

        <div class="font-semibold">
            Thành công
        </div>

        <div id="success-text"
             class="text-sm mt-1">
            Cập nhật tài khoản thành công.
        </div>

    </div>

</div>


<script>

const accounts = <?= json_encode($accounts, JSON_UNESCAPED_UNICODE) ?>;

let selectedAccount = null;
let editingAccountId = null;
let isAdding = false;


/* =========================
   TÌM KIẾM TÀI KHOẢN
========================= */

function searchAccounts() {

    const keyword =
        document
            .getElementById('search-input')
            .value
            .toLowerCase()
            .trim();

    const role =
        document
            .getElementById('role-filter')
            .value;

    const rows =
        document.querySelectorAll('.account-row');

    let visibleCount = 0;

    rows.forEach(row => {

        const name = row.dataset.name;
        const phone = row.dataset.phone;
        const cccd = row.dataset.cccd;
        const rowRole = row.dataset.role;

        const matchKeyword =
            !keyword ||
            name.includes(keyword) ||
            phone.includes(keyword) ||
            cccd.includes(keyword);

        const matchRole =
            !role || rowRole === role;

        if (matchKeyword && matchRole) {

            row.classList.remove('hidden');

            visibleCount++;

        } else {

            row.classList.add('hidden');

        }

    });


    const emptyState =
        document.getElementById('empty-state');

    const table =
        document.getElementById('account-table-wrapper');


    if (visibleCount === 0) {

        emptyState.classList.remove('hidden');
        table.classList.add('hidden');

    } else {

        emptyState.classList.add('hidden');
        table.classList.remove('hidden');

    }
}


/* =========================
   XEM CHI TIẾT
========================= */

function viewAccount(id) {

    const account =
        accounts.find(item => item.id === id);

    if (!account) {

        alert('Không tìm thấy tài khoản');

        return;

    }

    selectedAccount = account;

    document.getElementById('detail-name').textContent =
        account.name;

    document.getElementById('detail-phone').textContent =
        account.phone;

    document.getElementById('detail-email').textContent =
        account.email || 'Không có';

    document.getElementById('detail-cccd').textContent =
        account.cccd;

    document.getElementById('detail-role').textContent =
        account.role;

    document.getElementById('detail-dob').textContent =
        account.dob;

    document.getElementById('detail-address').textContent =
        account.address;

    document.getElementById('detail-ward').textContent =
        account.ward;


    document
        .getElementById('account-list-section')
        .classList.add('hidden');

    document
        .getElementById('account-detail-section')
        .classList.remove('hidden');

    document
        .getElementById('history-section')
        .classList.remove('hidden');
}


/* =========================
   HIỂN THỊ FORM CHỈNH SỬA
========================= */

function showEditForm() {

    if (!selectedAccount) return;

    isAdding = false;

    editingAccountId =
        selectedAccount.id;

    document.getElementById('form-title').textContent =
        'Chỉnh sửa tài khoản';


    fillForm(selectedAccount);


    document
        .getElementById('account-detail-section')
        .classList.add('hidden');

    document
        .getElementById('account-form-section')
        .classList.remove('hidden');
}


/* =========================
   HIỂN THỊ FORM THÊM
========================= */

function showAddForm() {

    isAdding = true;

    editingAccountId = null;

    document.getElementById('form-title').textContent =
        'Thêm tài khoản';


    document
        .getElementById('account-form')
        .reset();

    document
        .getElementById('form-error')
        .classList.add('hidden');


    document
        .getElementById('account-list-section')
        .classList.add('hidden');

    document
        .getElementById('account-form-section')
        .classList.remove('hidden');

    document
        .getElementById('history-section')
        .classList.add('hidden');
}


/* =========================
   ĐIỀN FORM
========================= */

function fillForm(account) {

    document.getElementById('name').value =
        account.name;

    document.getElementById('phone').value =
        account.phone;

    document.getElementById('email').value =
        account.email;

    document.getElementById('cccd').value =
        account.cccd;

    document.getElementById('role').value =
        account.role;

    document.getElementById('dob').value =
        convertDate(account.dob);

    document.getElementById('address').value =
        account.address;

    document.getElementById('ward').value =
        account.ward;

}


/* =========================
   ĐỔI DD/MM/YYYY → YYYY-MM-DD
========================= */

function convertDate(date) {

    const parts = date.split('/');

    if (parts.length !== 3) return '';

    return `${parts[2]}-${parts[1]}-${parts[0]}`;
}


/* =========================
   LƯU TÀI KHOẢN
========================= */

function saveAccount(event) {

    event.preventDefault();


    const name =
        document.getElementById('name').value.trim();

    const phone =
        document.getElementById('phone').value.trim();

    const email =
        document.getElementById('email').value.trim();

    const cccd =
        document.getElementById('cccd').value.trim();

    const role =
        document.getElementById('role').value;

    const dob =
        document.getElementById('dob').value;

    const address =
        document.getElementById('address').value.trim();

    const ward =
        document.getElementById('ward').value.trim();


    /* =========================
       VALIDATE
    ========================= */

    const error =
        document.getElementById('form-error');


    if (
        !name ||
        !phone ||
        !cccd ||
        !role ||
        !dob ||
        !address ||
        !ward
    ) {

        error.textContent =
            'Lưu thất bại. Vui lòng nhập đầy đủ thông tin bắt buộc.';

        error.classList.remove('hidden');

        return;

    }


    if (!/^\d{10,11}$/.test(phone)) {

        error.textContent =
            'Lưu thất bại. Số điện thoại không hợp lệ.';

        error.classList.remove('hidden');

        return;

    }


    if (!/^\d{12}$/.test(cccd)) {

        error.textContent =
            'Lưu thất bại. Số CCCD phải gồm 12 chữ số.';

        error.classList.remove('hidden');

        return;

    }


    error.classList.add('hidden');


    /* =========================
       CẬP NHẬT / THÊM
    ========================= */

    if (isAdding) {

        showSuccess(
            'Thêm tài khoản thành công.'
        );

        // Demo: giả lập hệ thống lưu lịch sử
        console.log('Lưu lịch sử thao tác:', {
            action: 'CREATE_ACCOUNT',
            name,
            role
        });

    } else {

        showSuccess(
            'Cập nhật tài khoản thành công.'
        );

        // Demo: giả lập hệ thống lưu lịch sử
        console.log('Lưu lịch sử thay đổi:', {
            action: 'UPDATE_ACCOUNT',
            accountId: editingAccountId,
            name,
            role
        });

    }

}


/* =========================
   THÔNG BÁO THÀNH CÔNG
========================= */

function showSuccess(message) {

    const success =
        document.getElementById('success-message');

    document.getElementById('success-text')
        .textContent = message;

    success.classList.remove('hidden');


    setTimeout(() => {

        success.classList.add('hidden');

        if (isAdding) {

            backToList();

        } else {

            cancelForm();

        }

    }, 1800);

}


/* =========================
   QUAY LẠI DANH SÁCH
========================= */

function backToList() {

    document
        .getElementById('account-detail-section')
        .classList.add('hidden');

    document
        .getElementById('account-form-section')
        .classList.add('hidden');

    document
        .getElementById('history-section')
        .classList.add('hidden');

    document
        .getElementById('account-list-section')
        .classList.remove('hidden');

}


/* =========================
   HỦY FORM
========================= */

function cancelForm() {

    document
        .getElementById('account-form-section')
        .classList.add('hidden');


    if (selectedAccount && !isAdding) {

        document
            .getElementById('account-detail-section')
            .classList.remove('hidden');

        document
            .getElementById('history-section')
            .classList.remove('hidden');

    } else {

        backToList();

    }

}


/* =========================
   DEMO KHÔNG CÓ TÀI KHOẢN
========================= */

// Có thể dùng đoạn này để test Alternative Flow 2.1:
//
// accounts.length = 0;
// searchAccounts();

</script>

</body>
</html>

