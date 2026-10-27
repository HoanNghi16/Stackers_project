<?php
    session_start();
    require("../../components/layout/header.php");
?>
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Stackers - Học sinh</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="../assets/css/output.css">
</head>
<body class="bg-slate-50 text-slate-800">
    <?php renderHeader(); ?>

    <main class="pt-24 pb-12 px-4 sm:px-6 lg:px-10">
        <div class="max-w-7xl mx-auto">

            <!-- Tiêu đề trang -->
            <div class="mb-10">
                <div class="inline-flex items-center gap-2 px-3 py-1.5
                            rounded-full bg-blue-50 text-blue-700
                            text-sm font-semibold mb-4">
                    <span class="w-2 h-2 rounded-full bg-blue-500"></span>
                    CỔNG THÔNG TIN HỌC SINH
                </div>

                <h1 class="text-3xl sm:text-4xl font-extrabold
                           tracking-tight text-slate-900">
                    Xin chào, học sinh!
                </h1>

                <p class="mt-3 text-slate-500 text-base sm:text-lg">
                    Chào mừng bạn đến với hệ thống tuyển sinh Stackers.
                    Hãy chọn chức năng bạn muốn sử dụng.
                </p>
            </div>

            <!-- Thông báo -->
            <div class="overflow-hidden rounded-2xl
                        bg-gradient-to-r from-blue-700 to-indigo-600
                        p-6 sm:p-8 mb-10 text-white shadow-lg shadow-blue-100">

                <div class="absolute -right-10 -top-16 w-56 h-56
                            rounded-full bg-white/10"></div>
                <div class="absolute right-28 -bottom-24 w-48 h-48
                            rounded-full bg-white/10"></div>

                <div class="flex flex-col sm:flex-row
                            sm:items-center sm:justify-between gap-5">
                    <div>
                        <div class="flex items-center gap-2 mb-3">
                            <svg class="w-6 h-6" fill="none"
                                 stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round"
                                      stroke-linejoin="round"
                                      stroke-width="2"
                                      d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/>
                            </svg>
                            <span class="text-sm font-semibold text-blue-100">
                                KHU VỰC HỌC SINH
                            </span>
                        </div>

                        <h2 class="text-xl sm:text-2xl font-bold mb-2">
                            Chủ động theo dõi kỳ thi của bạn
                        </h2>

                        <p class="text-blue-100 text-sm sm:text-base
                                  max-w-2xl leading-relaxed">
                            Theo dõi lịch thi, tra cứu kết quả, đăng ký phúc khảo
                            và kiểm tra địa điểm thi ngay tại một nơi.
                        </p>
                    </div>

                    <div class="hidden sm:flex items-center justify-center
                                w-20 h-20 rounded-2xl bg-white/15 shrink-0">
                        <svg class="w-10 h-10" fill="none"
                             stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round"
                                  stroke-linejoin="round"
                                  stroke-width="1.5"
                                  d="M12 3l8 4v5c0 5-3.5 8-8 9-4.5-1-8-4-8-9V7l8-4z"/>
                            <path stroke-linecap="round"
                                  stroke-linejoin="round"
                                  stroke-width="2"
                                  d="M9 12l2 2 4-4"/>
                        </svg>
                    </div>
                </div>
            </div>

            <!-- Danh sách chức năng -->
            <div class="mb-6 flex items-end justify-between gap-4">
                <div>
                    <h2 class="text-xl font-bold text-slate-900">
                        Chức năng dành cho bạn
                    </h2>
                    <p class="text-sm text-slate-500 mt-1">
                        Chọn một chức năng để tiếp tục
                    </p>
                </div>

                <span class="text-sm text-slate-400 hidden sm:block">
                    04 chức năng
                </span>
            </div>

            <div class="grid grid-cols-1 sm:grid-cols-2
                        xl:grid-cols-4 gap-5">


                <!-- Xem lịch thi -->
                <a href="lich-thi/"
                   class="group bg-white border border-slate-200
                          rounded-2xl p-6 shadow-sm
                          hover:shadow-xl hover:shadow-blue-100/70
                          hover:-translate-y-1 hover:border-blue-300
                          transition-all duration-300">

                    <div class="w-14 h-14 flex items-center justify-center
                                rounded-2xl bg-blue-50 text-blue-600
                                group-hover:bg-blue-600 group-hover:text-white
                                transition-colors duration-300">
                        <svg class="w-7 h-7" fill="none"
                             stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round"
                                  stroke-linejoin="round"
                                  stroke-width="1.8"
                                  d="M8 2v4m8-4v4M3 10h18M5 4h14a2 2 0 012 2v14a2 2 0 01-2 2H5a2 2 0 01-2-2V6a2 2 0 012-2z"/>
                            <path stroke-linecap="round"
                                  stroke-linejoin="round"
                                  stroke-width="2"
                                  d="M8 14h3m-3 4h3m3-4h2"/>
                        </svg>
                    </div>

                    <h3 class="mt-5 text-lg font-bold text-slate-900
                               group-hover:text-blue-700 transition-colors">
                        Xem lịch thi
                    </h3>

                    <p class="mt-2 text-sm text-slate-500 leading-relaxed">
                        Theo dõi ngày thi, giờ thi và thời gian của từng môn thi.
                    </p>

                    <div class="mt-6 flex items-center justify-between">
                        <span class="text-xs font-semibold text-blue-600
                                     bg-blue-50 px-3 py-1.5 rounded-lg">
                            LỊCH THI
                        </span>
                        <svg class="w-5 h-5 text-slate-300
                                    group-hover:text-blue-600
                                    group-hover:translate-x-1
                                    transition-all"
                             fill="none" stroke="currentColor"
                             viewBox="0 0 24 24">
                            <path stroke-linecap="round"
                                  stroke-linejoin="round"
                                  stroke-width="2"
                                  d="M5 12h14m-6-6l6 6-6 6"/>
                        </svg>
                    </div>
                </a>



                <!-- Đăng ký nguyện vọng -->
            <a href="dang-ky-nguyen-vong/"
            class="group bg-white border border-slate-200
                    rounded-2xl p-6 shadow-sm
                    hover:shadow-xl hover:shadow-pink-100/70
                    hover:-translate-y-1 hover:border-pink-300
                    transition-all duration-300">

                <div class="w-14 h-14 flex items-center justify-center
                            rounded-2xl bg-pink-50 text-pink-600
                            group-hover:bg-pink-600 group-hover:text-white
                            transition-colors duration-300">

                    <svg class="w-7 h-7" fill="none"
                        stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round"
                            stroke-width="1.8"
                            d="M9 5H5a2 2 0 00-2 2v12a2 2 0 002 2h14a2 2 0 002-2V7a2 2 0 00-2-2h-4"/>
                        <rect x="9" y="3" width="6" height="4" rx="1"
                            stroke-width="1.8"/>
                        <path stroke-linecap="round" stroke-linejoin="round"
                            stroke-width="1.8"
                            d="M8 12h8m-8 4h5"/>
                    </svg>
                </div>

                <h3 class="mt-5 text-lg font-bold text-slate-900
                        group-hover:text-pink-700 transition-colors">
                    Đăng ký nguyện vọng
                </h3>

                <p class="mt-2 text-sm text-slate-500 leading-relaxed">
                    Đăng ký và sắp xếp nguyện vọng xét tuyển vào các trường
                    theo mong muốn của bạn.
                </p>

                <div class="mt-6 flex items-center justify-between">
                    <span class="text-xs font-semibold text-pink-600
                                bg-pink-50 px-3 py-1.5 rounded-lg">
                        NGUYỆN VỌNG
                    </span>

                    <svg class="w-5 h-5 text-slate-300
                                group-hover:text-pink-600
                                group-hover:translate-x-1 transition-all"
                        fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round"
                            stroke-width="2"
                            d="M5 12h14m-6-6l6 6-6 6"/>
                    </svg>
                </div>
            </a>



                <!-- Tra cứu kết quả thi -->
                <a href="ket-qua-thi/"
                   class="group bg-white border border-slate-200
                          rounded-2xl p-6 shadow-sm
                          hover:shadow-xl hover:shadow-emerald-100/70
                          hover:-translate-y-1 hover:border-emerald-300
                          transition-all duration-300">

                    <div class="w-14 h-14 flex items-center justify-center
                                rounded-2xl bg-emerald-50 text-emerald-600
                                group-hover:bg-emerald-600 group-hover:text-white
                                transition-colors duration-300">
                        <svg class="w-7 h-7" fill="none"
                             stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round"
                                  stroke-linejoin="round"
                                  stroke-width="1.8"
                                  d="M4 19V5m0 14h17M7 15l4-4 3 2 6-7"/>
                            <path stroke-linecap="round"
                                  stroke-linejoin="round"
                                  stroke-width="1.8"
                                  d="M16 6h4v4"/>
                        </svg>
                    </div>

                    <h3 class="mt-5 text-lg font-bold text-slate-900
                               group-hover:text-emerald-700 transition-colors">
                        Tra cứu kết quả thi
                    </h3>

                    <p class="mt-2 text-sm text-slate-500 leading-relaxed">
                        Xem điểm thi, kết quả từng môn và thông tin xét tuyển.
                    </p>

                    <div class="mt-6 flex items-center justify-between">
                        <span class="text-xs font-semibold text-emerald-600
                                     bg-emerald-50 px-3 py-1.5 rounded-lg">
                            KẾT QUẢ
                        </span>
                        <svg class="w-5 h-5 text-slate-300
                                    group-hover:text-emerald-600
                                    group-hover:translate-x-1
                                    transition-all"
                             fill="none" stroke="currentColor"
                             viewBox="0 0 24 24">
                            <path stroke-linecap="round"
                                  stroke-linejoin="round"
                                  stroke-width="2"
                                  d="M5 12h14m-6-6l6 6-6 6"/>
                        </svg>
                    </div>
                </a>

                <!-- Đăng ký phúc khảo -->
                <a href="phuc-khao/"
                   class="group bg-white border border-slate-200
                          rounded-2xl p-6 shadow-sm
                          hover:shadow-xl hover:shadow-amber-100/70
                          hover:-translate-y-1 hover:border-amber-300
                          transition-all duration-300">

                    <div class="w-14 h-14 flex items-center justify-center
                                rounded-2xl bg-amber-50 text-amber-600
                                group-hover:bg-amber-500 group-hover:text-white
                                transition-colors duration-300">
                        <svg class="w-7 h-7" fill="none"
                             stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round"
                                  stroke-linejoin="round"
                                  stroke-width="1.8"
                                  d="M12 20h9"/>
                            <path stroke-linecap="round"
                                  stroke-linejoin="round"
                                  stroke-width="1.8"
                                  d="M16.5 3.5a2.12 2.12 0 013 3L8 18l-4 1 1-4L16.5 3.5z"/>
                            <path stroke-linecap="round"
                                  stroke-linejoin="round"
                                  stroke-width="1.8"
                                  d="M13.5 6.5l4 4"/>
                        </svg>
                    </div>

                    <h3 class="mt-5 text-lg font-bold text-slate-900
                               group-hover:text-amber-700 transition-colors">
                        Đăng ký phúc khảo
                    </h3>

                    <p class="mt-2 text-sm text-slate-500 leading-relaxed">
                        Gửi yêu cầu phúc khảo bài thi khi cần kiểm tra lại điểm số.
                    </p>

                    <div class="mt-6 flex items-center justify-between">
                        <span class="text-xs font-semibold text-amber-600
                                     bg-amber-50 px-3 py-1.5 rounded-lg">
                            PHÚC KHẢO
                        </span>
                        <svg class="w-5 h-5 text-slate-300
                                    group-hover:text-amber-600
                                    group-hover:translate-x-1
                                    transition-all"
                             fill="none" stroke="currentColor"
                             viewBox="0 0 24 24">
                            <path stroke-linecap="round"
                                  stroke-linejoin="round"
                                  stroke-width="2"
                                  d="M5 12h14m-6-6l6 6-6 6"/>
                        </svg>
                    </div>
                </a>

                <!-- Xem địa điểm thi -->
                <a href="dia-diem-thi/"
                   class="group bg-white border border-slate-200
                          rounded-2xl p-6 shadow-sm
                          hover:shadow-xl hover:shadow-violet-100/70
                          hover:-translate-y-1 hover:border-violet-300
                          transition-all duration-300">

                    <div class="w-14 h-14 flex items-center justify-center
                                rounded-2xl bg-violet-50 text-violet-600
                                group-hover:bg-violet-600 group-hover:text-white
                                transition-colors duration-300">
                        <svg class="w-7 h-7" fill="none"
                             stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round"
                                  stroke-linejoin="round"
                                  stroke-width="1.8"
                                  d="M20 10c0 5-8 11-8 11S4 15 4 10a8 8 0 1116 0z"/>
                            <circle cx="12" cy="10" r="2.5"
                                    stroke="currentColor"
                                    stroke-width="1.8"/>
                        </svg>
                    </div>

                    <h3 class="mt-5 text-lg font-bold text-slate-900
                               group-hover:text-violet-700 transition-colors">
                        Xem địa điểm thi
                    </h3>

                    <p class="mt-2 text-sm text-slate-500 leading-relaxed">
                        Tra cứu hội đồng thi, địa chỉ và thông tin điểm thi của bạn.
                    </p>

                    <div class="mt-6 flex items-center justify-between">
                        <span class="text-xs font-semibold text-violet-600
                                     bg-violet-50 px-3 py-1.5 rounded-lg">
                            ĐỊA ĐIỂM
                        </span>
                        <svg class="w-5 h-5 text-slate-300
                                    group-hover:text-violet-600
                                    group-hover:translate-x-1
                                    transition-all"
                             fill="none" stroke="currentColor"
                             viewBox="0 0 24 24">
                            <path stroke-linecap="round"
                                  stroke-linejoin="round"
                                  stroke-width="2"
                                  d="M5 12h14m-6-6l6 6-6 6"/>
                        </svg>
                    </div>
                </a>

            </div>

            <!-- Chân trang -->
            <div class="mt-10 border-t border-slate-200 pt-6
                        flex flex-col sm:flex-row
                        sm:items-center sm:justify-between gap-3">
                <p class="text-sm text-slate-500">
                    Cần hỗ trợ? Vui lòng liên hệ bộ phận tuyển sinh.
                </p>
                <p class="text-xs text-slate-400">
                    © <?= date('Y') ?> Stackers. All rights reserved.
                </p>
            </div>

        </div>
    </main>
</body>
</html>