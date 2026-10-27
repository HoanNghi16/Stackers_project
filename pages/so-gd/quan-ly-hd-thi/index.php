<?php
    session_start();
    require("../../../components/layout/sidebar.php");
    require("../../../components/layout/header.php");
?>

<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Stackers - Quản lý hội đồng thi</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>

<body class="bg-slate-50">
    <?php
        renderHeader();
        renderSidebar();
    ?>

    <main class="ml-72 pb-12 px-6 pt-24">
        <div class="">
            <!-- Hero -->
            <section class="mb-10 overflow-hidden rounded-3xl bg-gradient-to-br from-blue-700 via-blue-600 to-indigo-700 px-8 py-10 text-white shadow-xl shadow-blue-900/10 md:px-10">
                <div class="z-10 max-w-2xl">
                    <h1 class="text-3xl font-extrabold leading-tight tracking-tight md:text-4xl">
                        Quản lý hội đồng thi
                    </h1>

                    <p class="mt-4 max-w-xl text-sm leading-7 text-blue-100 md:text-base">
                        Quản lý các hội đồng thi thuộc kỳ thi tuyển sinh 10. Chọn 1 loại hội đồng để quản lý các hội đồng tương ứng.
                    </p>
                </div>
            </section>

            <!-- Section heading -->
            <div class="mb-6 flex flex-col justify-between gap-3 sm:flex-row sm:items-end">
                <div>
                    <h2 class="text-xl font-bold tracking-tight text-slate-800">
                        Các loại hội đồng thi
                    </h2>
                    <p class="mt-1 text-sm text-slate-500">
                        Chọn một loại hội đồng thi để quản lý các hội đồng tương ứng.
                    </p>
                </div>

                <span class="inline-flex w-fit items-center rounded-full bg-blue-50 px-3 py-1.5 text-xs font-semibold text-blue-700 ring-1 ring-inset ring-blue-100">
                    03 hội đồng
                </span>
            </div>

            <!-- Cards -->
            <div class="grid grid-cols-1 gap-6 sm:grid-cols-2 xl:grid-cols-4">
                <!-- Ra đề -->
                <a href="./ra-de"
                    class="group flex flex-col rounded-2xl border border-slate-200/80 bg-white p-6 shadow-sm transition duration-300 hover:-translate-y-1.5 hover:border-violet-200 hover:shadow-xl hover:shadow-violet-900/5">

                    <div class="flex items-start justify-between">
                        <div class="flex h-14 w-14 items-center justify-center rounded-2xl bg-violet-50 text-violet-600 transition duration-300 group-hover:scale-110 group-hover:bg-violet-600 group-hover:text-white">
                            <svg class="h-7 w-7" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M9 20l-5.447-2.724A1 1 0 013 16.382V5.618a1 1 0 011.447-.894L9 7m0 13l6-3m-6 3V7m6 10l4.553 2.276A1 1 0 0021 18.382V7.618a1 1 0 00-.553-.894L15 4m0 13V4m0 0L9 7"/>
                            </svg>
                        </div>
                    </div>

                    <h3 class="mt-6 text-lg font-bold text-slate-800 transition group-hover:text-violet-700">
                        Ra đề
                    </h3>

                    <p class="mt-2 flex-1 text-sm leading-6 text-slate-500">
                        Hội đồng ra đề, thực hiện ra các đề thi theo chuyên môn giảng dạy.
                    </p>

                    <div class="mt-6 flex items-center justify-between border-t border-slate-100 pt-4">
                        <span class="text-sm font-semibold text-slate-600 transition group-hover:text-violet-700">
                            Quản lý
                        </span>

                        <span class="flex h-9 w-9 items-center justify-center rounded-xl bg-slate-50 text-slate-400 transition duration-300 group-hover:translate-x-1 group-hover:bg-violet-50 group-hover:text-violet-600">
                            <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 12h14m-7-7 7 7-7 7"/>
                            </svg>
                        </span>
                    </div>
                </a>

                <!-- Chấm thi -->
                <a href="./cham-thi"
                    class="group flex flex-col rounded-2xl border border-slate-200/80 bg-white p-6 shadow-sm transition duration-300 hover:-translate-y-1.5 hover:border-emerald-200 hover:shadow-xl hover:shadow-emerald-900/5">

                    <div class="flex items-start justify-between">
                        <div class="flex h-14 w-14 items-center justify-center rounded-2xl bg-emerald-50 text-emerald-600 transition duration-300 group-hover:scale-110 group-hover:bg-emerald-600 group-hover:text-white">
                            <svg class="h-7 w-7" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                            </svg>
                        </div>

                        <span class="rounded-full bg-emerald-50 px-2.5 py-1 text-xs font-semibold text-emerald-700">
                            Đánh giá
                        </span>
                    </div>

                    <h3 class="mt-6 text-lg font-bold text-slate-800 transition group-hover:text-emerald-700">
                        Chấm thi
                    </h3>

                    <p class="mt-2 flex-1 text-sm leading-6 text-slate-500">
                        Quản lý hội đồng chấm thi, hỗ trợ tổ chức và thực hiện
                        công tác đánh giá bài thi của thí sinh.
                    </p>

                    <div class="mt-6 flex items-center justify-between border-t border-slate-100 pt-4">
                        <span class="text-sm font-semibold text-slate-600 transition group-hover:text-emerald-700">
                            Quản lý
                        </span>

                        <span class="flex h-9 w-9 items-center justify-center rounded-xl bg-slate-50 text-slate-400 transition duration-300 group-hover:translate-x-1 group-hover:bg-emerald-50 group-hover:text-emerald-600">
                            <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 12h14m-7-7 7 7-7 7"/>
                            </svg>
                        </span>
                    </div>
                </a>

                <!-- Phúc khảo -->
                <a href="./phuc-khao"
                    class="group flex flex-col rounded-2xl border border-slate-200/80 bg-white p-6 shadow-sm transition duration-300 hover:-translate-y-1.5 hover:border-amber-200 hover:shadow-xl hover:shadow-amber-900/5">

                    <div class="flex items-start justify-between">
                        <div class="flex h-14 w-14 items-center justify-center rounded-2xl bg-amber-50 text-amber-600 transition duration-300 group-hover:scale-110 group-hover:bg-amber-500 group-hover:text-white">
                            <svg class="h-7 w-7" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/>
                            </svg>
                        </div>
                    </div>

                    <h3 class="mt-6 text-lg font-bold text-slate-800 transition group-hover:text-amber-600">
                        Phúc khảo
                    </h3>

                    <p class="mt-2 flex-1 text-sm leading-6 text-slate-500">
                        Hội đồng chấm phúc khảo và xử lý các yêu cầu phúc khảo của thí sinh sau kỳ thi.
                    </p>

                    <div class="mt-6 flex items-center justify-between border-t border-slate-100 pt-4">
                        <span class="text-sm font-semibold text-slate-600 transition group-hover:text-amber-600">
                            Quản lý
                        </span>

                        <span class="flex h-9 w-9 items-center justify-center rounded-xl bg-slate-50 text-slate-400 transition duration-300 group-hover:translate-x-1 group-hover:bg-amber-50 group-hover:text-amber-600">
                            <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 12h14m-7-7 7 7-7 7"/>
                            </svg>
                        </span>
                    </div>
                </a>

                <!-- Gác thi -->
                <a href="./gac-thi"
                    class="group flex flex-col rounded-2xl border border-slate-200/80 bg-white p-6 shadow-sm transition duration-300 hover:-translate-y-1.5 hover:border-violet-200 hover:shadow-xl hover:shadow-violet-900/5">

                    <div class="flex items-start justify-between">
                        <div class="flex h-14 w-14 items-center justify-center rounded-2xl bg-violet-50 text-violet-600 transition duration-300 group-hover:scale-110 group-hover:bg-violet-600 group-hover:text-white">
                            <svg class="h-7 w-7" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M9 20l-5.447-2.724A1 1 0 013 16.382V5.618a1 1 0 011.447-.894L9 7m0 13l6-3m-6 3V7m6 10l4.553 2.276A1 1 0 0021 18.382V7.618a1 1 0 00-.553-.894L15 4m0 13V4m0 0L9 7"/>
                            </svg>
                        </div>

                        <span class="rounded-full bg-violet-50 px-2.5 py-1 text-xs font-semibold text-violet-700">
                            Tổ chức thi
                        </span>
                    </div>

                    <h3 class="mt-6 text-lg font-bold text-slate-800 transition group-hover:text-violet-700">
                        Gác thi
                    </h3>

                    <p class="mt-2 flex-1 text-sm leading-6 text-slate-500">
                        Quản lý hội đồng gác thi, hỗ trợ công tác coi thi
                        và tổ chức kỳ thi theo kế hoạch.
                    </p>

                    <div class="mt-6 flex items-center justify-between border-t border-slate-100 pt-4">
                        <span class="text-sm font-semibold text-slate-600 transition group-hover:text-violet-700">
                            Quản lý
                        </span>

                        <span class="flex h-9 w-9 items-center justify-center rounded-xl bg-slate-50 text-slate-400 transition duration-300 group-hover:translate-x-1 group-hover:bg-violet-50 group-hover:text-violet-600">
                            <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 12h14m-7-7 7 7-7 7"/>
                            </svg>
                        </span>
                    </div>
                </a>

            </div>

            <!-- Footer note -->

        </div>
    </main>
</body>
</html>