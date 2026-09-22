<?php
?>

<!DOCTYPE html>
<html lang="vi">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>Xác thực OTP</title>

    <script src="https://cdn.tailwindcss.com"></script>
</head>

<body class="bg-gray-50 min-h-screen p-8">

    <div class="max-w-2xl mx-auto">

        <!-- HEADER -->

        <div class="mb-8 flex items-center gap-4">
            <div class="w-12 h-12 rounded-xl bg-blue-100 flex items-center justify-center text-blue-600 shrink-0">
                <svg xmlns="http://www.w3.org/2000/svg" class="w-6 h-6" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z" />
                </svg>
            </div>
            <div>
                <h1 class="text-2xl font-bold text-gray-800">Xác thực OTP</h1>
                <p class="text-gray-500 mt-1">Xác thực danh tính người dùng</p>
            </div>
        </div>


        <!-- ========================= -->
        <!-- BƯỚC 1: NHẬP TÀI KHOẢN -->
        <!-- ========================= -->

        <section class="bg-white rounded-2xl shadow-sm border border-gray-100 p-6">

            <!-- STEP INDICATOR -->
            <div class="flex items-center gap-2 mb-6">
                <span class="w-7 h-7 rounded-full bg-blue-600 text-white text-sm font-semibold flex items-center justify-center">1</span>
                <span class="text-sm text-gray-400">Tài khoản</span>

                <span class="flex-1 h-px bg-gray-200 mx-1"></span>

                <span class="w-7 h-7 rounded-full bg-gray-100 text-gray-400 text-sm font-semibold flex items-center justify-center">2</span>
                <span class="text-sm text-gray-300">Phương thức</span>

                <span class="flex-1 h-px bg-gray-200 mx-1"></span>

                <span class="w-7 h-7 rounded-full bg-gray-100 text-gray-400 text-sm font-semibold flex items-center justify-center">3</span>
                <span class="text-sm text-gray-300">Mã OTP</span>
            </div>

            <h2 class="text-xl font-semibold text-gray-800 mb-1">
                Xác thực người dùng
            </h2>

            <p class="text-gray-500 mb-6">
                Nhập thông tin để bắt đầu quá trình xác thực
            </p>


            <div>

                <label class="block text-sm font-medium text-gray-700 mb-1.5">
                    Mã định danh hoặc tên đăng nhập
                </label>

                <div class="relative">
                    <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4 absolute left-3.5 top-1/2 -translate-y-1/2 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z" />
                    </svg>
                    <input
                        type="text"
                        class="w-full border border-gray-200 rounded-xl pl-10 pr-4 py-2.5
                               focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                        placeholder="Nhập mã định danh hoặc tên đăng nhập"
                    >
                </div>

            </div>


            <div class="mt-8 pt-5 border-t border-gray-100">

                <button
                    type="button"
                    class="bg-blue-600 hover:bg-blue-700 text-white
                           px-6 py-2.5 rounded-xl font-medium
                           inline-flex items-center gap-2
                           shadow-sm hover:shadow transition-all"
                >
                    Tiếp tục
                    <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M14 5l7 7m0 0l-7 7m7-7H3" />
                    </svg>
                </button>

            </div>

        </section>

    </div>

</body>

</html>