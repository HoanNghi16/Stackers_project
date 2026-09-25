<?php
    require("../../../components/layout/sidebar.php");
    require("../../../components/layout/header.php");
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Stackers - Quản lý hđ thi</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-gray-50">
    <?php
        renderHeader();
        renderSidebar();
    ?>
    <div class="ml-64 pt-20 px-8 pb-8">
        <div class="p-12">
            <div class="mb-8">
                <h1 class="text-2xl font-bold text-gray-800">Quản lý hoạt động thi</h1>
                <p class="text-gray-500 mt-1">Chọn một hoạt động bên dưới để bắt đầu</p>
            </div>

            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-6">

                <a href="./cham-thi" class="group bg-white rounded-2xl shadow-sm border border-gray-100 p-6 flex flex-col items-start gap-4 hover:shadow-lg hover:-translate-y-1 transition-all duration-200">
                    <div class="w-12 h-12 rounded-xl bg-green-100 flex items-center justify-center text-green-600 group-hover:bg-green-600 group-hover:text-white transition-colors">
                        <svg xmlns="http://www.w3.org/2000/svg" class="w-6 h-6" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                        </svg>
                    </div>
                    <div>
                        <h2 class="text-lg font-semibold text-gray-800">Chấm thi</h2>
                        <p class="text-sm text-gray-500 mt-1">Hội đồng chấm thi</p>
                    </div>
                </a>

                <a href="./phuc-khao" class="group bg-white rounded-2xl shadow-sm border border-gray-100 p-6 flex flex-col items-start gap-4 hover:shadow-lg hover:-translate-y-1 transition-all duration-200">
                    <div class="w-12 h-12 rounded-xl bg-amber-100 flex items-center justify-center text-amber-600 group-hover:bg-amber-600 group-hover:text-white transition-colors">
                        <svg xmlns="http://www.w3.org/2000/svg" class="w-6 h-6" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15" />
                        </svg>
                    </div>
                    <div>
                        <h2 class="text-lg font-semibold text-gray-800">Phúc khảo</h2>
                        <p class="text-sm text-gray-500 mt-1">Hội đồng phúc khảo</p>
                    </div>
                </a>

                <a href="./gac-thi" class="group bg-white rounded-2xl shadow-sm border border-gray-100 p-6 flex flex-col items-start gap-4 hover:shadow-lg hover:-translate-y-1 transition-all duration-200">
                    <div class="w-12 h-12 rounded-xl bg-purple-100 flex items-center justify-center text-purple-600 group-hover:bg-purple-600 group-hover:text-white transition-colors">
                        <svg xmlns="http://www.w3.org/2000/svg" class="w-6 h-6" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M9 20l-5.447-2.724A1 1 0 013 16.382V5.618a1 1 0 011.447-.894L9 7m0 13l6-3m-6 3V7m6 10l4.553 2.276A1 1 0 0021 18.382V7.618a1 1 0 00-.553-.894L15 4m0 13V4m0 0L9 7" />
                        </svg>
                    </div>
                    <div>
                        <h2 class="text-lg font-semibold text-gray-800">Gác thi</h2>
                        <p class="text-sm text-gray-500 mt-1">Hội đồng gác thi</p>
                    </div>
                </a>

            </div>
        </div>
    </div>
</body>
</html>