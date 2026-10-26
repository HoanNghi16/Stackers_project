<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Xem danh sách thí sinh</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-slate-100 min-h-screen text-slate-800">
    <div class="text-white bg-indigo-700 max-w-6xl mx-auto px-4 py-4 mt-3">
        <div class="max-w-6xl mx-auto px-4 py-4">
            <p class="text-xs uppercase tracking-wide text-indigo-200">Cổng Sở GD&ĐT</p>
            <h1 class="text-xl font-bold">Xem danh sách thí sinh</h1>
        </div>
    </div>

    <main class="max-w-6xl mx-auto px-4 py-6 space-y-4">
        <section class="bg-white shadow-sm p-5">
            <p class="font-semibold text-slate-800 mb-4 pb-3 border-b border-slate-100">Tìm kiếm thí sinh</p>
            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4 items-end">
                <label class="block lg:col-span-2">
                    <span class="text-sm text-slate-500">Họ tên hoặc mã thí sinh</span>
                    <input type="text" class="mt-1 w-full border border-slate-300 px-3 py-2 bg-white" placeholder="VD: Nguyễn Văn An / 26010012">
                </label>
                <label class="block">
                    <span class="text-sm text-slate-500">Trường THCS</span>
                    <select class="mt-1 w-full border border-slate-300 px-3 py-2 bg-white cursor-pointer">
                        <option value="">-- Tất cả trường --</option>
                        <option>THCS Lê Lợi</option>
                        <option>THCS Trần Phú</option>
                        <option>THCS Nguyễn Trãi</option>
                    </select>
                </label>
                <div class="flex gap-2">
                    <button type="button" class="flex-1 bg-indigo-600 hover:bg-indigo-700 text-white font-medium py-2">Tìm kiếm</button>
                    <button type="button" class="px-4 border border-slate-300 hover:bg-slate-50">Xóa</button>
                </div>
            </div>
        </section>

        <section class="bg-white shadow-sm p-5">
            <div class="flex items-center justify-between mb-4 pb-3 border-b border-slate-100">
                <p class="font-semibold text-slate-800">Danh sách thí sinh</p>
                <p class="text-sm text-slate-500">5 thí sinh</p>
            </div>
            <div class="overflow-x-auto">
                <table class="w-full text-sm text-left">
                    <thead class="bg-slate-50 text-slate-500">
                        <tr>
                            <th class="px-3 py-2 font-medium">STT</th>
                            <th class="px-3 py-2 font-medium">Mã thí sinh</th>
                            <th class="px-3 py-2 font-medium">Họ và tên</th>
                            <th class="px-3 py-2 font-medium">Lớp</th>
                            <th class="px-3 py-2 font-medium">Trường THCS</th>
                            <th class="px-3 py-2 font-medium">Thao tác</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr class="border-t border-slate-100 hover:bg-indigo-50">
                            <td class="px-3 py-2">1</td>
                            <td class="px-3 py-2 font-medium">26010012</td>
                            <td class="px-3 py-2">Nguyễn Văn An</td>
                            <td class="px-3 py-2">09A1</td>
                            <td class="px-3 py-2">THCS Lê Lợi</td>
                            <td class="px-3 py-2">
                                <button type="button" class="text-indigo-600 hover:underline font-medium" onclick="document.getElementById('modal').showModal()">Xem chi tiết</button>
                            </td>
                        </tr>
                        <tr class="border-t border-slate-100 hover:bg-indigo-50">
                            <td class="px-3 py-2">2</td>
                            <td class="px-3 py-2 font-medium">26010013</td>
                            <td class="px-3 py-2">Trần Thị Bình</td>
                            <td class="px-3 py-2">09A2</td>
                            <td class="px-3 py-2">THCS Trần Phú</td>
                            <td class="px-3 py-2">
                                <button type="button" class="text-indigo-600 hover:underline font-medium" onclick="document.getElementById('modal').showModal()">Xem chi tiết</button>
                            </td>
                        </tr>
                        <tr class="border-t border-slate-100 hover:bg-indigo-50">
                            <td class="px-3 py-2">3</td>
                            <td class="px-3 py-2 font-medium">26010014</td>
                            <td class="px-3 py-2">Lê Minh Châu</td>
                            <td class="px-3 py-2">09B1</td>
                            <td class="px-3 py-2">THCS Lê Lợi</td>
                            <td class="px-3 py-2">
                                <button type="button" class="text-indigo-600 hover:underline font-medium" onclick="document.getElementById('modal').showModal()">Xem chi tiết</button>
                            </td>
                        </tr>
                        <tr class="border-t border-slate-100 hover:bg-indigo-50">
                            <td class="px-3 py-2">4</td>
                            <td class="px-3 py-2 font-medium">26010015</td>
                            <td class="px-3 py-2">Phạm Quốc Dũng</td>
                            <td class="px-3 py-2">09A1</td>
                            <td class="px-3 py-2">THCS Nguyễn Trãi</td>
                            <td class="px-3 py-2">
                                <button type="button" class="text-indigo-600 hover:underline font-medium" onclick="document.getElementById('modal').showModal()">Xem chi tiết</button>
                            </td>
                        </tr>
                        <tr class="border-t border-slate-100 hover:bg-indigo-50">
                            <td class="px-3 py-2">5</td>
                            <td class="px-3 py-2 font-medium">26010016</td>
                            <td class="px-3 py-2">Hoàng Thị Em</td>
                            <td class="px-3 py-2">09C1</td>
                            <td class="px-3 py-2">THCS Trần Phú</td>
                            <td class="px-3 py-2">
                                <button type="button" class="text-indigo-600 hover:underline font-medium" onclick="document.getElementById('modal').showModal()">Xem chi tiết</button>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </section>
    </main>

    <dialog id="modal" class="w-full max-w-lg p-0 bg-white shadow-lg backdrop:bg-black/40">
        <div class="p-5">
            <div class="flex justify-between items-center mb-4 pb-3 border-b border-slate-100">
                <h2 class="font-semibold">Chi tiết thí sinh</h2>
                <button type="button" class="text-slate-500 hover:text-slate-800 text-2xl leading-none" onclick="document.getElementById('modal').close()">&times;</button>
            </div>
            <div class="grid grid-cols-2 gap-4 text-sm">
                <div>
                    <p class="text-slate-500">Mã thí sinh</p>
                    <p class="font-medium">26010012</p>
                </div>
                <div>
                    <p class="text-slate-500">Họ và tên</p>
                    <p class="font-medium">Nguyễn Văn An</p>
                </div>
                <div>
                    <p class="text-slate-500">Ngày sinh</p>
                    <p class="font-medium">12/03/2010</p>
                </div>
                <div>
                    <p class="text-slate-500">Giới tính</p>
                    <p class="font-medium">Nam</p>
                </div>
                <div>
                    <p class="text-slate-500">Lớp</p>
                    <p class="font-medium">09A1</p>
                </div>
                <div>
                    <p class="text-slate-500">Trường THCS</p>
                    <p class="font-medium">THCS Lê Lợi</p>
                </div>
                <div>
                    <p class="text-slate-500">Số điện thoại</p>
                    <p class="font-medium">0901 234 567</p>
                </div>
                <div>
                    <p class="text-slate-500">Nguyện vọng 1</p>
                    <p class="font-medium">THPT Nguyễn Huệ</p>
                </div>
                <div>
                    <p class="text-slate-500">Nguyện vọng 2</p>
                    <p class="font-medium">THPT Lê Quý Đôn</p>
                </div>
                <div>
                    <p class="text-slate-500">Nguyện vọng 3</p>
                    <p class="font-medium">THPT Trần Phú</p>
                </div>
            </div>
            <div class="mt-5 text-right">
                <button type="button" class="px-7 border border-slate-300 hover:bg-slate-50 py-2" onclick="document.getElementById('modal').close()">Đóng</button>
            </div>
        </div>
    </dialog>
</body>
</html>
