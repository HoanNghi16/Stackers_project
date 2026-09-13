<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Đăng ký nguyện vọng tuyển sinh 10</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-slate-100 min-h-screen text-slate-800">
    <!-- <header class="bg-indigo-700 text-white">
        <div class="max-w-3xl mx-auto px-4 py-4">
            <p class="text-xs uppercase tracking-wide text-indigo-200">Cổng học sinh</p>
            <h1 class="text-xl font-bold">Đăng ký nguyện vọng tuyển sinh 10</h1>
        </div>
    </header> -->
    <div class="text-white bg-indigo-700 max-w-3xl mx-auto px-4 py-4 mt-3">
        <div class="max-w-3xl mx-auto px-4 py-4">
            <p class="text-xs uppercase tracking-wide text-indigo-200">Cổng học sinh</p>
            <h1 class="text-xl font-bold">Đăng ký nguyện vọng tuyển  10</h1>
        </div>
    </div>

    <main class="max-w-3xl mx-auto px-4 py-6 space-y-4">
        <section class="bg-white shadow-sm p-5">
            <p class="font-semibold text-slate-800 mb-4 pb-3 border-b border-slate-100">Thông tin học sinh</p>
            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                <label class="block sm:col-span-2">
                    <span class="text-sm text-slate-500">Họ và tên</span>
                    <input type="text" class="mt-1 w-full border border-slate-300 px-3 py-2 bg-slate-50 text-slate-700 focus:outline-none focus:border-indigo-500 focus:bg-white" value="Nguyễn Văn An" readonly>
                </label>
                <label class="block">
                    <span class="text-sm text-slate-500">Lớp</span>
                    <input type="text" class="mt-1 w-full border border-slate-300 px-3 py-2 bg-slate-50 text-slate-700 focus:outline-none" value="09A1" readonly>
                </label>
                <label class="block">
                    <span class="text-sm text-slate-500">Số báo danh</span>
                    <input type="text" class="mt-1 w-full border border-slate-300 px-3 py-2 bg-slate-50 text-slate-700 focus:outline-none" value="26010012" readonly>
                </label>
                <label class="block sm:col-span-2">
                    <span class="text-sm text-slate-500">Trường THCS</span>
                    <input type="text" class="mt-1 w-full border border-slate-300 px-3 py-2 bg-slate-50 text-slate-700 focus:outline-none" value="THCS Lê Lợi" readonly>
                </label>
            </div>
        </section>

        <form class="bg-white  shadow-sm p-4 space-y-4">
            <h2 class="font-semibold">Chọn nguyện vọng</h2>

            <label class="block">
                <span class="text-sm font-medium">Nguyện vọng 1 <span class="text-red-500">*</span></span>
                <select class="mt-1 w-full cursor-pointer  border-slate-300 border px-3 py-2 bg-white">
                    <option value="">-- Chọn trường --</option>
                    <option>THPT Nguyễn Huệ</option>
                    <option>THPT Lê Quý Đôn</option>
                    <option>THPT Trần Phú</option>
                </select>
            </label>

            <label class="block">
                <span class="text-sm font-medium">Nguyện vọng 2</span>
                <select class="mt-1 w-full cursor-pointer  border-slate-300 border px-3 py-2 bg-white">
                    <option value="">-- Không đăng ký --</option>
                    <option>THPT Nguyễn Huệ</option>
                    <option>THPT Lê Quý Đôn</option>
                    <option>THPT Trần Phú</option>
                </select>
            </label>

            <label class="block">
                <span class="text-sm font-medium">Nguyện vọng 3</span>
                <select class="mt-1 w-full cursor-pointer  border-slate-300 border px-3 py-2 bg-white">
                    <option value="">-- Không đăng ký --</option>
                    <option>THPT Nguyễn Huệ</option>
                    <option>THPT Lê Quý Đôn</option>
                    <option>THPT Trần Phú</option>
                </select>
            </label>

            <div class="flex gap-2 pt-2">
                <button type="button" class="flex-1 bg-indigo-600 hover:bg-indigo-700 text-white font-medium py-2 ">Xác nhận đăng ký </button>
                <button type="button" class="px-7 border border-slate-300  hover:bg-slate-50">Hủy</button>
            </div>
        </form>
    </main>
</body>
</html>
