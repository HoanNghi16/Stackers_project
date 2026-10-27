<?php
require_once __DIR__ . '/../../../components/layout/header.php';
require_once __DIR__ . '/../../../components/layout/sidebar.php';

$scenario = $_GET['scenario'] ?? 'normal';

$schedules = [
    ['mon' => 'Ngữ văn', 'ngay' => '06/06/2027', 'gio' => '07:30', 'dia_diem' => 'THPT Nguyễn Thượng Hiền', 'phong' => 'A203', 'thoi_luong' => '120 phút'],
    ['mon' => 'Ngoại ngữ', 'ngay' => '06/06/2027', 'gio' => '13:30', 'dia_diem' => 'THPT Nguyễn Thượng Hiền', 'phong' => 'A203', 'thoi_luong' => '90 phút'],
    ['mon' => 'Toán', 'ngay' => '07/06/2027', 'gio' => '07:30', 'dia_diem' => 'THPT Nguyễn Thượng Hiền', 'phong' => 'A203', 'thoi_luong' => '120 phút'],
];
?>

<!doctype html>
<html lang="vi">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width,initial-scale=1">
    <title>Xem lịch thi</title>

    <link rel="stylesheet" href="../../../assets/css/phat-tailwind.css">
</head>

<body class="m-0 bg-[#f5f7fb] font-sans text-[#1f2937]">

    <?php renderHeader(); ?>
    <?php renderSidebar(); ?>

    <div class="min-h-screen bg-slate-50 pl-72 pt-20">

        <main class="mx-auto mt-7 max-w-[1180px] px-[18px] pb-12">

            <h1 class="mb-1.5 text-[30px] font-bold leading-tight">Xem lịch thi</h1>
            <p class="mb-6 text-gray-500">Tra cứu lịch thi đã được Sở GD&amp;ĐT công bố.</p>

            <form class="mb-[18px] rounded-[14px] border border-gray-200 bg-white p-5 shadow-[0_10px_25px_rgba(15,23,42,0.08)]" method="get">
                <label class="mb-[7px] block font-[650]">Mô phỏng tình huống để chụp báo cáo</label>

                <div class="grid grid-cols-12 gap-4">
                    <div class="col-span-12 min-[801px]:col-span-8">
                        <select class="w-full rounded-[9px] border border-slate-300 bg-white px-3 py-2.5 text-[#1f2937] outline-none focus:border-blue-600 focus:ring-[3px] focus:ring-blue-600/10" name="scenario">
                            <option value="normal" <?= $scenario === 'normal' ? 'selected' : '' ?>>
                                Basic flow - tải lịch thi thành công
                            </option>
                            <option value="not_published" <?= $scenario === 'not_published' ? 'selected' : '' ?>>
                                Alternative 3.1 - lịch thi chưa công bố
                            </option>
                            <option value="no_profile" <?= $scenario === 'no_profile' ? 'selected' : '' ?>>
                                Exception 2.1 - không tìm thấy hồ sơ thí sinh
                            </option>
                            <option value="load_error" <?= $scenario === 'load_error' ? 'selected' : '' ?>>
                                Exception 3.2 - không thể tải lịch thi
                            </option>
                        </select>
                    </div>

                    <div class="col-span-12 min-[801px]:col-span-4">
                        <button class="inline-flex cursor-pointer items-center justify-center gap-2 rounded-[9px] px-[15px] py-2.5 font-bold bg-blue-600 text-white hover:bg-blue-700" type="submit">
                            Hiển thị tình huống
                        </button>
                    </div>
                </div>
            </form>

            <?php if ($scenario === 'not_published'): ?>

                <div class="my-3 rounded-[10px] border px-[15px] py-[13px] font-semibold border-amber-200 bg-amber-50 text-amber-800">
                    Lịch thi chưa được công bố.
                </div>

            <?php elseif ($scenario === 'no_profile'): ?>

                <div class="my-3 rounded-[10px] border px-[15px] py-[13px] font-semibold border-red-200 bg-red-50 text-red-800">
                    Không tìm thấy hồ sơ dự thi được liên kết với tài khoản.
                </div>

            <?php elseif ($scenario === 'load_error'): ?>

                <div class="my-3 rounded-[10px] border px-[15px] py-[13px] font-semibold border-red-200 bg-red-50 text-red-800">
                    Không thể tải lịch thi, vui lòng thử lại sau.
                </div>

            <?php else: ?>

                <div class="grid grid-cols-12 gap-4">
                    <div class="col-span-12 min-[801px]:col-span-4">
                        <div class="rounded-xl border border-gray-200 bg-slate-50 p-[15px]">
                            <div class="text-gray-500">Thí sinh</div>
                            <div class="mt-1 text-xl font-extrabold">Nguyễn Văn An</div>
                            <div class="text-gray-500">SBD: 102345</div>
                        </div>
                    </div>

                    <div class="col-span-12 min-[801px]:col-span-4">
                        <div class="rounded-xl border border-gray-200 bg-slate-50 p-[15px]">
                            <div class="text-gray-500">Số buổi thi</div>
                            <div class="mt-1 text-[28px] font-extrabold"><?= count($schedules) ?></div>
                        </div>
                    </div>

                    <div class="col-span-12 min-[801px]:col-span-4">
                        <div class="rounded-xl border border-gray-200 bg-slate-50 p-[15px]">
                            <div class="text-gray-500">Điểm thi</div>
                            <div class="mt-1 text-xl font-extrabold">
                                THPT Nguyễn Thượng Hiền
                            </div>
                        </div>
                    </div>
                </div>

                <div class="mb-[18px] rounded-[14px] border border-gray-200 bg-white p-5 shadow-[0_10px_25px_rgba(15,23,42,0.08)]">
                    <h2 class="mb-4 text-2xl font-bold">Lịch thi của bạn</h2>

                    <div class="overflow-auto">
                        <table class="w-full border-collapse">
                            <thead>
                                <tr>
                                    <th class="border-b border-gray-200 px-2.5 py-3 text-left align-top bg-slate-50 text-[13px] font-bold uppercase text-slate-500">Môn thi</th>
                                    <th class="border-b border-gray-200 px-2.5 py-3 text-left align-top bg-slate-50 text-[13px] font-bold uppercase text-slate-500">Ngày thi</th>
                                    <th class="border-b border-gray-200 px-2.5 py-3 text-left align-top bg-slate-50 text-[13px] font-bold uppercase text-slate-500">Giờ</th>
                                    <th class="border-b border-gray-200 px-2.5 py-3 text-left align-top bg-slate-50 text-[13px] font-bold uppercase text-slate-500">Địa điểm</th>
                                    <th class="border-b border-gray-200 px-2.5 py-3 text-left align-top bg-slate-50 text-[13px] font-bold uppercase text-slate-500">Phòng</th>
                                    <th class="border-b border-gray-200 px-2.5 py-3 text-left align-top bg-slate-50 text-[13px] font-bold uppercase text-slate-500"></th>
                                </tr>
                            </thead>

                            <tbody>
                                <?php foreach ($schedules as $s): ?>
                                    <tr>
                                        <td class="border-b border-gray-200 px-2.5 py-3 text-left align-top"><b><?= htmlspecialchars($s['mon']) ?></b></td>
                                        <td class="border-b border-gray-200 px-2.5 py-3 text-left align-top"><?= htmlspecialchars($s['ngay']) ?></td>
                                        <td class="border-b border-gray-200 px-2.5 py-3 text-left align-top"><?= htmlspecialchars($s['gio']) ?></td>
                                        <td class="border-b border-gray-200 px-2.5 py-3 text-left align-top"><?= htmlspecialchars($s['dia_diem']) ?></td>

                                        <td class="border-b border-gray-200 px-2.5 py-3 text-left align-top">
                                            <span class="inline-block rounded-full px-[9px] py-[5px] text-xs font-extrabold bg-blue-100 text-blue-700">
                                                <?= htmlspecialchars($s['phong']) ?>
                                            </span>
                                        </td>

                                        <td class="border-b border-gray-200 px-2.5 py-3 text-left align-top">
                                            <button class="inline-flex cursor-pointer items-center justify-center gap-2 rounded-[9px] px-[15px] py-2.5 font-bold border border-slate-300 bg-white text-[#1f2937]" type="button" onclick='showDetail(<?= json_encode(
                                                                                                                    $s,
                                                                                                                    JSON_UNESCAPED_UNICODE |
                                                                                                                        JSON_HEX_APOS |
                                                                                                                        JSON_HEX_QUOT
                                                                                                                ) ?>)'>
                                                Xem chi tiết
                                            </button>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>

                    <p class="text-gray-500">
                        Alternative 5.1: nếu người dùng không chọn “Xem chi tiết”
                        thì chỉ dừng ở danh sách lịch thi tổng quát.
                    </p>
                </div>

            <?php endif; ?>

        </main>

    </div>

    <div id="detailModal" class="modal fixed inset-0 z-[1000] hidden items-center justify-center bg-slate-900/55 p-[18px]">
        <div class="w-full max-w-[620px] rounded-[14px] bg-white p-5 shadow-[0_20px_60px_rgba(0,0,0,0.2)]">

            <div class="mb-3 flex items-center justify-between gap-4">
                <h2 class="m-0 text-2xl font-bold">Chi tiết buổi thi</h2>

                <button class="cursor-pointer rounded-lg border-0 bg-slate-200 px-2.5 py-[7px]" type="button" onclick="closeModal('detailModal')">
                    ✕
                </button>
            </div>

            <div id="detailContent"></div>

            <div class="mt-3.5 flex flex-wrap gap-2.5">
                <button class="inline-flex cursor-pointer items-center justify-center gap-2 rounded-[9px] px-[15px] py-2.5 font-bold bg-blue-600 text-white hover:bg-blue-700" type="button" onclick="closeModal('detailModal')">
                    Đã hiểu
                </button>
            </div>

        </div>
    </div>

    <script src="../../../assets/js/usecase-tailwind.js"></script>

    <script>
        function showDetail(s) {
            document.getElementById('detailContent').innerHTML = `
        <table class="w-full border-collapse">
            <tr><th class="border-b border-gray-200 px-2.5 py-3 text-left align-top bg-slate-50 text-[13px] font-bold uppercase text-slate-500">Môn thi</th><td class="border-b border-gray-200 px-2.5 py-3 text-left align-top"><b>${s.mon}</b></td></tr>
            <tr><th class="border-b border-gray-200 px-2.5 py-3 text-left align-top bg-slate-50 text-[13px] font-bold uppercase text-slate-500">Ngày thi</th><td class="border-b border-gray-200 px-2.5 py-3 text-left align-top">${s.ngay}</td></tr>
            <tr><th class="border-b border-gray-200 px-2.5 py-3 text-left align-top bg-slate-50 text-[13px] font-bold uppercase text-slate-500">Giờ bắt đầu</th><td class="border-b border-gray-200 px-2.5 py-3 text-left align-top">${s.gio}</td></tr>
            <tr><th class="border-b border-gray-200 px-2.5 py-3 text-left align-top bg-slate-50 text-[13px] font-bold uppercase text-slate-500">Thời lượng</th><td class="border-b border-gray-200 px-2.5 py-3 text-left align-top">${s.thoi_luong}</td></tr>
            <tr><th class="border-b border-gray-200 px-2.5 py-3 text-left align-top bg-slate-50 text-[13px] font-bold uppercase text-slate-500">Địa điểm</th><td class="border-b border-gray-200 px-2.5 py-3 text-left align-top">${s.dia_diem}</td></tr>
            <tr><th class="border-b border-gray-200 px-2.5 py-3 text-left align-top bg-slate-50 text-[13px] font-bold uppercase text-slate-500">Phòng thi</th><td class="border-b border-gray-200 px-2.5 py-3 text-left align-top">${s.phong}</td></tr>
        </table>
    `;

            openModal('detailModal');
        }
    </script>

</body>

</html>