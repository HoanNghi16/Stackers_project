<?php
require("../../../components/layout/header.php");
require("../../../components/layout/sidebar.php");
$students = [
    [
        'id' => 1,
        'name' => 'Nguyễn Văn An',
        'dob' => '15/03/2008',
        'phone' => '0901234567',
        'email' => 'nguyenvanan@gmail.com',
        'address' => '123 Nguyễn Trãi',
        'ward' => 'Phường 1',
        'cccd' => '079208001234',
        'ethnic' => 'Không'
    ],
    [
        'id' => 2,
        'name' => 'Trần Thị Bình',
        'dob' => '20/07/2008',
        'phone' => '0912345678',
        'email' => '',
        'address' => '45 Lê Lợi',
        'ward' => 'Phường 2',
        'cccd' => '079208005678',
        'ethnic' => 'Có'
    ],
    [
        'id' => 3,
        'name' => 'Lê Minh Khôi',
        'dob' => '02/11/2008',
        'phone' => '0987654321',
        'email' => 'leminhkhoi@gmail.com',
        'address' => '78 Trần Hưng Đạo',
        'ward' => 'Phường 3',
        'cccd' => '079208009999',
        'ethnic' => 'Không'
    ]
];

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

    <title>Quản lý hồ sơ thí sinh</title>

    <script src="https://cdn.tailwindcss.com"></script>
</head>

<body class="bg-gray-50">
    <?php
        renderHeader();
        renderSidebar();
    ?>
    <div class="ml-60 pt-20">
        <div class="p-20 pt-8">
            <!-- HEADER -->
            <div class="mb-8 flex items-center justify-between">

                <div class="flex items-center gap-4">
                    <div class="w-12 h-12 rounded-xl bg-blue-100 flex items-center justify-center text-blue-600 shrink-0">
                        <svg xmlns="http://www.w3.org/2000/svg" class="w-6 h-6" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M12 14l9-5-9-5-9 5 9 5zm0 0l6.16-3.422a12.083 12.083 0 01.665 6.479A11.952 11.952 0 0012 20.055a11.952 11.952 0 00-6.824-2.998 12.078 12.078 0 01.665-6.479L12 14z" />
                        </svg>
                    </div>
                    <div>
                        <h1 class="text-2xl font-bold text-gray-800">Quản lý hồ sơ thí sinh</h1>
                        <p class="text-gray-500 mt-1">Trường THCS</p>
                    </div>
                </div>

            </div>


            <!-- ========================= -->
            <!-- DANH SÁCH THÍ SINH -->
            <!-- ========================= -->

            <!-- SEARCH -->

            <div class="bg-white rounded-2xl shadow-sm border border-gray-100 p-5 mb-6">

                <div class="grid grid-cols-1 md:grid-cols-3 gap-4">

                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1.5">
                            Họ và tên
                        </label>

                        <input
                            type="text"
                            class="w-full border border-gray-200 rounded-xl px-4 py-2.5
                                focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                            placeholder="Nhập họ và tên"
                        >
                    </div>


                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1.5">
                            Số CCCD
                        </label>

                        <input
                            type="text"
                            class="w-full border border-gray-200 rounded-xl px-4 py-2.5
                                focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                            placeholder="Nhập số CCCD"
                        >
                    </div>


                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1.5">
                            Số điện thoại
                        </label>

                        <input
                            type="text"
                            class="w-full border border-gray-200 rounded-xl px-4 py-2.5
                                focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                            placeholder="Nhập số điện thoại"
                        >
                    </div>

                </div>


                <div class="mt-4">

                    <button
                        type="button"
                        class="bg-blue-600 hover:bg-blue-700 text-white
                            px-5 py-2.5 rounded-xl font-medium
                            inline-flex items-center gap-2
                            shadow-sm hover:shadow transition-all"
                    >
                        <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M21 21l-4.35-4.35M17 11a6 6 0 11-12 0 6 6 0 0112 0z" />
                        </svg>
                        Tìm kiếm
                    </button>

                </div>

            </div>


            <!-- ACTIONS -->

            <div class="bg-white rounded-2xl shadow-sm border border-gray-100 p-5 mb-6">

                <div class="flex flex-col sm:flex-row gap-4">

                    <button
                        type="button"
                        class="border border-gray-200 hover:bg-gray-50
                            px-5 py-2.5 rounded-xl font-medium text-gray-700
                            inline-flex items-center justify-center gap-2 transition-colors"
                    >
                        <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M12 4v16m8-8H4" />
                        </svg>
                        Thêm thí sinh
                    </button>

                    <button
                        type="button"
                        class="border border-gray-200 hover:bg-gray-50
                            px-5 py-2.5 rounded-xl font-medium text-gray-700
                            inline-flex items-center justify-center gap-2 transition-colors"
                    >
                        <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M7 16a4 4 0 01-.88-7.903A5 5 0 1115.9 6L16 6a5 5 0 011 9.9M12 12v9m0-9l-3 3m3-3l3 3" />
                        </svg>
                        Tải lên từ file Excel
                    </button>

                </div>

            </div>


            <!-- STUDENT TABLE -->

            <div class="bg-white rounded-2xl shadow-sm border border-gray-100 overflow-hidden">

                <div class="overflow-x-auto">

                    <table class="w-full">

                        <thead class="bg-gray-50/70 border-b border-gray-100">

                            <tr class="text-left text-xs font-semibold uppercase tracking-wide text-gray-500">

                                <th class="px-5 py-3">
                                    STT
                                </th>

                                <th class="px-5 py-3">
                                    Họ và tên
                                </th>

                                <th class="px-5 py-3">
                                    Ngày sinh
                                </th>

                                <th class="px-5 py-3">
                                    Số điện thoại
                                </th>

                                <th class="px-5 py-3">
                                    Số CCCD
                                </th>

                                <th class="px-5 py-3 text-center">
                                    Thao tác
                                </th>

                            </tr>

                        </thead>


                        <tbody class="divide-y divide-gray-100">

                            <?php foreach ($students as $index => $student): ?>

                                <tr class="hover:bg-gray-50/80 transition-colors">

                                    <td class="px-5 py-4 text-gray-500">
                                        <?= $index + 1 ?>
                                    </td>

                                    <td class="px-5 py-4">
                                        <div class="flex items-center gap-3">
                                            <div class="w-9 h-9 rounded-full bg-blue-100 text-blue-600 flex items-center justify-center text-sm font-semibold shrink-0">
                                                <?= htmlspecialchars(initials($student['name'])) ?>
                                            </div>
                                            <span class="font-medium text-gray-800">
                                                <?= htmlspecialchars($student['name']) ?>
                                            </span>
                                        </div>
                                    </td>

                                    <td class="px-5 py-4 text-gray-600">
                                        <?= htmlspecialchars($student['dob']) ?>
                                    </td>

                                    <td class="px-5 py-4 text-gray-600">
                                        <?= htmlspecialchars($student['phone']) ?>
                                    </td>

                                    <td class="px-5 py-4 text-gray-600 font-mono text-sm">
                                        <?= htmlspecialchars($student['cccd']) ?>
                                    </td>

                                    <td class="px-5 py-4 text-center">

                                        <button
                                            type="button"
                                            class="text-blue-600 hover:text-blue-800
                                                hover:bg-blue-50 px-3 py-1.5 rounded-lg
                                                font-medium transition-colors"
                                        >
                                            Chỉnh sửa
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