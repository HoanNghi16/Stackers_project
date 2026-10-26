<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Đổi mật khẩu</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-slate-100 min-h-screen text-slate-800">
    <div class="text-white bg-indigo-700 max-w-3xl mx-auto px-4 py-4 mt-3">
        <div class="max-w-3xl mx-auto px-4 py-4">   
            <p class="text-xs uppercase tracking-wide text-indigo-200">Cổng học sinh</p>
            <h1 class="text-xl font-bold">Đổi mật khẩu</h1>
        </div>
    </div>

    <main class="max-w-3xl mx-auto px-4 py-6 space-y-4">
        <section class="bg-white shadow-sm p-5">
            <p class="font-semibold text-slate-800 mb-4 pb-3 border-b border-slate-100">Tài khoản</p>
            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                <label class="block">
                    <span class="text-sm text-slate-500">Họ và tên</span>
                    <input type="text" class="mt-1 w-full border border-slate-300 px-3 py-2 bg-slate-50 text-slate-700 focus:outline-none" value="Nguyễn Văn An" readonly>
                </label>
                <label class="block">
                    <span class="text-sm text-slate-500">Tên đăng nhập</span>
                    <input type="text" class="mt-1 w-full border border-slate-300 px-3 py-2 bg-slate-50 text-slate-700 focus:outline-none" value="26010012" readonly>
                </label>
            </div>
        </section>

        <form class="bg-white shadow-sm p-4 space-y-4">
            <h2 class="font-semibold pb-3 border-b border-slate-100">Đổi mật khẩu</h2>
            <p class="text-sm text-slate-500">Mật khẩu mới tối thiểu 8 ký tự, gồm chữ và số.</p>

            <label class="block">
                <span class="text-sm font-medium">Mật khẩu hiện tại <span class="text-red-500">*</span></span>
                <input type="password" class="mt-1 w-full border border-slate-300 px-3 py-2 bg-white focus:outline-none focus:border-indigo-500" placeholder="Nhập mật khẩu hiện tại">
            </label>

            <label class="block">
                <span class="text-sm font-medium">Mật khẩu mới <span class="text-red-500">*</span></span>
                <input type="password" class="mt-1 w-full border border-slate-300 px-3 py-2 bg-white focus:outline-none focus:border-indigo-500" placeholder="Nhập mật khẩu mới">
            </label>

            <label class="block">
                <span class="text-sm font-medium">Xác nhận mật khẩu mới <span class="text-red-500">*</span></span>
                <input type="password" class="mt-1 w-full border border-slate-300 px-3 py-2 bg-white focus:outline-none focus:border-indigo-500" placeholder="Nhập lại mật khẩu mới">
            </label>

            <div class="flex gap-2 pt-2">
                <button type="button" class="flex-1 bg-indigo-600 hover:bg-indigo-700 text-white font-medium py-2">Cập nhật mật khẩu</button>
                <button type="button" class="px-7 border border-slate-300 hover:bg-slate-50">Hủy</button>
            </div>
        </form>
    </main>
</body>
</html>
