<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Thống kê, báo cáo</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-slate-100 min-h-screen text-slate-800">
    <div class="text-white bg-indigo-700 max-w-6xl mx-auto px-4 py-4 mt-3">
        <div class="max-w-6xl mx-auto px-4 py-4">
            <p class="text-xs uppercase tracking-wide text-indigo-200">Cổng Sở GD&ĐT</p>
            <h1 class="text-xl font-bold">Thống kê, báo cáo</h1>
        </div>
    </div>

    <main class="max-w-6xl mx-auto px-4 py-6 space-y-4">
        <section class="bg-white shadow-sm p-5">
            <p class="font-semibold text-slate-800 mb-4 pb-3 border-b border-slate-100">Tiêu chí thống kê</p>
            <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
                <label class="block">
                    <span class="text-sm text-slate-500">Loại thống kê <span class="text-red-500">*</span></span>
                    <select class="mt-1 w-full border border-slate-300 px-3 py-2 bg-white cursor-pointer">
                        <option value="">-- Chọn loại --</option>
                        <option selected>Theo trường THPT</option>
                        <option>Theo khu vực</option>
                        <option>Theo kết quả tuyển sinh</option>
                    </select>
                </label>
                <label class="block">
                    <span class="text-sm text-slate-500">Năm tuyển sinh <span class="text-red-500">*</span></span>
                    <select class="mt-1 w-full border border-slate-300 px-3 py-2 bg-white cursor-pointer">
                        <option value="">-- Chọn năm --</option>
                        <option selected>2026</option>
                        <option>2025</option>
                        <option>2024</option>
                    </select>
                </label>
                <label class="block">
                    <span class="text-sm text-slate-500">Trường THPT</span>
                    <select class="mt-1 w-full border border-slate-300 px-3 py-2 bg-white cursor-pointer">
                        <option value="">-- Tất cả trường --</option>
                        <option>THPT Nguyễn Huệ</option>
                        <option>THPT Lê Quý Đôn</option>
                        <option>THPT Trần Phú</option>
                    </select>
                </label>
            </div>
            <div class="flex gap-2 pt-4">
                <button type="button" class="flex-1 bg-indigo-600 hover:bg-indigo-700 text-white font-medium py-2">Thống kê</button>
                <button type="button" class="px-7 border border-slate-300 hover:bg-slate-50">Hủy</button>
            </div>
        </section>

        <section class="bg-white shadow-sm p-5">
            <div class="flex items-center justify-between mb-4 pb-3 border-b border-slate-100">
                <p class="font-semibold text-slate-800">Kết quả thống kê</p>
                <p class="text-sm text-slate-500">Năm 2026 · Theo trường THPT</p>
            </div>

            <div class="grid grid-cols-2 lg:grid-cols-5 gap-3 mb-5">
                <div class="border border-slate-100 p-3">
                    <p class="text-xs text-slate-500">Tổng thí sinh</p>
                    <p class="text-xl font-semibold">1.248</p>
                </div>
                <div class="border border-slate-100 p-3">
                    <p class="text-xs text-slate-500">Đậu NV1</p>
                    <p class="text-xl font-semibold text-emerald-600">612</p>
                </div>
                <div class="border border-slate-100 p-3">
                    <p class="text-xs text-slate-500">Đậu NV2</p>
                    <p class="text-xl font-semibold text-sky-600">248</p>
                </div>
                <div class="border border-slate-100 p-3">
                    <p class="text-xs text-slate-500">Đậu NV3</p>
                    <p class="text-xl font-semibold text-indigo-600">126</p>
                </div>
                <div class="border border-slate-100 p-3">
                    <p class="text-xs text-slate-500">Không đậu</p>
                    <p class="text-xl font-semibold text-red-500">262</p>
                </div>
            </div>

            <div class="overflow-x-auto">
                <table class="w-full text-sm text-left">
                    <thead class="bg-slate-50 text-slate-500">
                        <tr>
                            <th class="px-3 py-2 font-medium">STT</th>
                            <th class="px-3 py-2 font-medium">Trường THPT</th>
                            <th class="px-3 py-2 font-medium">Đăng ký</th>
                            <th class="px-3 py-2 font-medium">Đậu NV1</th>
                            <th class="px-3 py-2 font-medium">Đậu NV2</th>
                            <th class="px-3 py-2 font-medium">Đậu NV3</th>
                            <th class="px-3 py-2 font-medium">Không đậu</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr class="border-t border-slate-100">
                            <td class="px-3 py-2">1</td>
                            <td class="px-3 py-2 font-medium">THPT Nguyễn Huệ</td>
                            <td class="px-3 py-2">520</td>
                            <td class="px-3 py-2">280</td>
                            <td class="px-3 py-2">90</td>
                            <td class="px-3 py-2">42</td>
                            <td class="px-3 py-2">108</td>
                        </tr>
                        <tr class="border-t border-slate-100">
                            <td class="px-3 py-2">2</td>
                            <td class="px-3 py-2 font-medium">THPT Lê Quý Đôn</td>
                            <td class="px-3 py-2">410</td>
                            <td class="px-3 py-2">200</td>
                            <td class="px-3 py-2">98</td>
                            <td class="px-3 py-2">40</td>
                            <td class="px-3 py-2">72</td>
                        </tr>
                        <tr class="border-t border-slate-100">
                            <td class="px-3 py-2">3</td>
                            <td class="px-3 py-2 font-medium">THPT Trần Phú</td>
                            <td class="px-3 py-2">318</td>
                            <td class="px-3 py-2">132</td>
                            <td class="px-3 py-2">60</td>
                            <td class="px-3 py-2">44</td>
                            <td class="px-3 py-2">82</td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </section>

        <section class="bg-white shadow-sm p-5">
            <p class="font-semibold text-slate-800 mb-2">Xuất báo cáo</p>
            <p class="text-sm text-slate-500 mb-4">Tải kết quả thống kê về máy (Excel / PDF).</p>
            <div class="flex gap-2">
                <button type="button" class="flex-1 bg-indigo-600 hover:bg-indigo-700 text-white font-medium py-2">Xuất báo cáo</button>
                <button type="button" class="px-7 border border-slate-300 hover:bg-slate-50">Hủy</button>
            </div>
        </section>
    </main>
</body>
</html>
