<?php
    session_start();
    require("../../components/layout/header.php");
    require("../../components/layout/sidebar.php");
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Stackers - Trang chủ</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="../assets/css/output.css">
</head>
<body>
    <?php
        renderHeader();
        renderSidebar();
    ?>

    <div class="ml-72 pt-24 max-w-screen min-h-screen bg-slate-50 flex-1 p-10 text-left">

        <!-- Header trang -->
        <div class="flex flex-col md:flex-row md:items-center
                    md:justify-between gap-4 mb-8">
            <div>
                <p class="text-sm font-medium text-blue-600 mb-2">
                    HỆ THỐNG QUẢN LÝ TUYỂN SINH
                </p>
                <h1 class="text-3xl font-bold text-slate-800">
                    Tổng quan hệ thống
                </h1>
                <p class="text-slate-500 mt-2">
                    Theo dõi tình hình tổ chức kỳ thi và công tác tuyển sinh.
                </p>
            </div>

            <div class="bg-white border border-slate-200 rounded-xl
                        px-4 py-3 shadow-sm">
                <p class="text-xs text-slate-400 mb-1">Hôm nay</p>
                <p class="font-semibold text-slate-700">
                    <?= date('d/m/Y') ?>
                </p>
            </div>
        </div>

        <!-- Banner -->
        <div class="overflow-hidden rounded-2xl
                    bg-gradient-to-r from-blue-700 via-blue-600 to-indigo-600
                    p-7 md:p-9 text-white mb-8 shadow-lg shadow-blue-100">

            <div class="max-w-2xl">
                <span class="inline-flex items-center gap-2
                            bg-white/15 rounded-full px-3 py-1
                            text-xs font-semibold mb-4">
                    <span class="w-2 h-2 bg-green-300 rounded-full"></span>
                    CỔNG QUẢN LÝ SỞ GIÁO DỤC
                </span>

                <h2 class="text-2xl md:text-3xl font-bold mb-3">
                    Chào mừng trở lại!
                </h2>

                <p class="text-blue-100 leading-relaxed">
                    Đây là trung tâm tổng quan dành cho cán bộ Sở Giáo dục.
                    Theo dõi nhanh số liệu, tiến độ tổ chức thi và các hoạt động
                    đang diễn ra trong hệ thống.
                </p>
            </div>
        </div>

        <!-- Chân trang -->
        <div class="border-t border-slate-200 pt-5
                    flex flex-col sm:flex-row sm:justify-between gap-2">
            <p class="text-sm text-slate-400">
                Stackers · Hệ thống quản lý tuyển sinh
            </p>
            <p class="text-xs text-slate-400">
                © <?= date('Y') ?> Stackers. All rights reserved.
            </p>
        </div>

    </div>

</body>
</html>