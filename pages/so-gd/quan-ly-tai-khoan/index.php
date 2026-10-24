<?php
require("../../../components/layout/header.php");
require("../../../components/layout/sidebar.php");
$accounts = [
    [
        'id' => 1,
        'name' => 'Nguyễn Văn An',
        'phone' => '0901234567',
        'email' => 'nguyenvanan@gmail.com',
        'cccd' => '079201001234',
        'role' => 'Sở GD&ĐT',
    ],
    [
        'id' => 2,
        'name' => 'Trần Thị Bình',
        'phone' => '0912345678',
        'email' => 'tranthibinh@gmail.com',
        'cccd' => '079201005678',
        'role' => 'Trường THCS',
    ],
    [
        'id' => 3,
        'name' => 'Lê Văn Cường',
        'phone' => '0923456789',
        'email' => 'levancuong@gmail.com',
        'cccd' => '079201009999',
        'role' => 'Trường THPT',
    ],
    [
        'id' => 4,
        'name' => 'Phạm Minh Dũng',
        'phone' => '0934567890',
        'email' => '',
        'cccd' => '079201001111',
        'role' => 'Học sinh',
    ],
];

$roles = [
    'Sở GD&ĐT',
    'Trường THCS',
    'Trường THPT',
    'Học sinh'
];

// Màu badge theo vai trò
$roleStyles = [
    'Sở GD&ĐT'     => ['bg' => 'bg-blue-50',   'text' => 'text-blue-700',   'dot' => 'bg-blue-500'],
    'Trường THCS'  => ['bg' => 'bg-green-50',  'text' => 'text-green-700',  'dot' => 'bg-green-500'],
    'Trường THPT'  => ['bg' => 'bg-amber-50',  'text' => 'text-amber-700',  'dot' => 'bg-amber-500'],
    'Học sinh'     => ['bg' => 'bg-purple-50', 'text' => 'text-purple-700', 'dot' => 'bg-purple-500'],
];

function roleBadge($role, $roleStyles) {
    $style = $roleStyles[$role] ?? ['bg' => 'bg-gray-50', 'text' => 'text-gray-700', 'dot' => 'bg-gray-400'];
    return sprintf(
        '<span class="inline-flex items-center gap-1.5 px-3 py-1 rounded-full %s %s text-sm font-medium"><span class="w-1.5 h-1.5 rounded-full %s"></span>%s</span>',
        $style['bg'], $style['text'], $style['dot'], htmlspecialchars($role)
    );
}

function initials($name) {
    $parts = explode(' ', trim($name));
    $last = end($parts);
    return mb_strtoupper(mb_substr($last, 0, 1));
}
?>

<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>Quản lý tài khoản</title>

    <script src="https://cdn.tailwindcss.com"></script>
</head>

<body class="bg-gray-50 min-h-screen">

<?php
    renderHeader();
    renderSidebar();
?>

<div class="ml-60 pt-20 px-8 pb-8">
    <div class="p-12">

        <!-- HEADER -->
        <div class="mb-8 flex items-center gap-4">
            <div class="w-12 h-12 rounded-xl bg-blue-100 flex items-center justify-center text-blue-600 shrink-0">
                <svg xmlns="http://www.w3.org/2000/svg" class="w-6 h-6" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z" />
                </svg>
            </div>
            <div>
                <h1 class="text-2xl font-bold text-gray-800">Quản lý tài khoản</h1>
                <p class="text-gray-500 mt-1">Quản lý thông tin tài khoản người dùng trong hệ thống</p>
            </div>
        </div>


        <!-- ========================= -->
        <!-- DANH SÁCH TÀI KHOẢN -->
        <!-- ========================= -->

        <div class="bg-white rounded-2xl shadow-sm border border-gray-100">

            <!-- TOOLBAR -->
            <div class="p-5 border-b border-gray-100">

                <div class="flex flex-col md:flex-row gap-3 justify-between">

                    <div class="flex flex-col sm:flex-row gap-3 flex-1">

                        <div class="relative w-full sm:max-w-md">
                            <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4 absolute left-3.5 top-1/2 -translate-y-1/2 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M21 21l-4.35-4.35M17 11a6 6 0 11-12 0 6 6 0 0112 0z" />
                            </svg>
                            <input
                                type="text"
                                placeholder="Tìm theo họ tên, SĐT, CCCD..."
                                class="border border-gray-200 rounded-xl pl-10 pr-4 py-2.5
                                    focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent
                                    w-full transition-shadow"
                            >
                        </div>

                        <select
                            class="border border-gray-200 rounded-xl px-4 py-2.5
                                focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent
                                text-gray-700 bg-white"
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
                        class="bg-blue-600 hover:bg-blue-700 text-white
                            px-5 py-2.5 rounded-xl font-medium
                            inline-flex items-center justify-center gap-2
                            shadow-sm hover:shadow transition-all"
                    >
                        <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M12 4v16m8-8H4" />
                        </svg>
                        Thêm tài khoản
                    </button>

                </div>
            </div>


            <!-- TABLE -->
            <div class="overflow-x-auto">

                <table class="w-full">

                    <thead class="bg-gray-50/70 border-b border-gray-100">

                        <tr class="text-left text-xs font-semibold uppercase tracking-wide text-gray-500">

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

                    <tbody class="divide-y divide-gray-100">

                        <?php foreach ($accounts as $account): ?>

                            <tr class="hover:bg-gray-50/80 transition-colors">

                                <td class="px-5 py-4">
                                    <div class="flex items-center gap-3">
                                        <div class="w-9 h-9 rounded-full bg-blue-100 text-blue-600 flex items-center justify-center text-sm font-semibold shrink-0">
                                            <?= htmlspecialchars(initials($account['name'])) ?>
                                        </div>
                                        <span class="font-medium text-gray-800">
                                            <?= htmlspecialchars($account['name']) ?>
                                        </span>
                                    </div>
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
                                    <?= roleBadge($account['role'], $roleStyles) ?>
                                </td>

                                <td class="px-5 py-4 text-gray-600 font-mono text-sm">
                                    <?= htmlspecialchars($account['cccd']) ?>
                                </td>

                                <td class="px-5 py-4 text-center">

                                    <button
                                        class="text-blue-600 hover:text-blue-800
                                            hover:bg-blue-50 px-3 py-1.5 rounded-lg
                                            font-medium transition-colors"
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

    </div>
</div>

</body>
</html>