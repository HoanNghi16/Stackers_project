<?php
    session_start();
    require "../../../../index.php";
    require "../../../../class/quan-ly-hd-thi.php";
    $ctrl = new CtrlQuanLyHoiDongThi($conn);
    $loaihoidong = "phuckhao";
?>

<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Stackers - Chọn môn thi</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>

<body class="bg-slate-50">

    <?php
        require "../../../../components/layout/header.php";
        require "../../../../components/layout/sidebar.php";

        renderHeader();
        renderSidebar();
    ?>

    <main class="ml-72 min-h-screen px-6 pb-12 pt-24">
        <div class="w-full pt-4 mb-4">
            <a href="../"
            class="inline-flex items-center gap-2 rounded-xl border border-slate-200 bg-white px-4 py-2.5 text-sm font-medium text-slate-600 shadow-sm transition-all duration-200 hover:-translate-x-1 hover:border-blue-200 hover:bg-blue-50 hover:text-blue-600">
                
                <svg xmlns="http://www.w3.org/2000/svg"
                    fill="none"
                    viewBox="0 0 24 24"
                    stroke-width="1.8"
                    stroke="currentColor"
                    class="h-4 w-4">
                    <path stroke-linecap="round"
                        stroke-linejoin="round"
                        d="M10.5 19.5 3 12m0 0 7.5-7.5M3 12h18"/>
                </svg>

                <span>Quay lại</span>
            </a>
        </div>
        <?php if (! isset($_GET['mamon'])): ?>
            <?php
                $dsMonThi = $ctrl->GetMonThi();    
                $monThiConfig = [
                    'Toán' => [
                        'icon' => 'M3 3v18h18 M19 9l-5 5-4-4-3 3',
                        'color' => 'blue'
                    ],
                    'Ngữ văn' => [
                        'icon' => 'M4 19.5A2.5 2.5 0 016.5 17H20 M6.5 2H20v20H6.5A2.5 2.5 0 014 19.5v-15A2.5 2.5 0 016.5 2z',
                        'color' => 'amber'
                    ],
                    'Tiếng Anh' => [
                        'icon' => 'M5 8l6 6 6-6 M4 4h16 M4 20h16',
                        'color' => 'violet'
                    ]
                ];
            ?>
            <div class="">

                <!-- Hero -->
                <section class="mb-10 overflow-hidden rounded-3xl bg-gradient-to-br from-blue-700 via-blue-600 to-indigo-700 px-8 py-10 text-white shadow-xl shadow-blue-900/10">

                    <div class="z-10 max-w-2xl">
                        <h1 class="text-3xl font-extrabold tracking-tight md:text-4xl">
                            Hội đồng phúc khảo
                        </h1>

                        <p class="mt-4 text-sm leading-7 text-blue-100 md:text-base">
                            Chọn môn thi để phân công hội đồng phúc khảo.
                        </p>

                        <!-- Thêm cái hạn chót vô -->
                        <div class="mt-6 inline-flex items-center gap-2 rounded-xl bg-white/10 px-4 py-2 text-sm text-white ring-1 ring-inset ring-white/10">
                            <svg class="h-5 w-5" fill="none" stroke="currentColor"
                                viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round"
                                    stroke-width="2" d="M12 6v6l4 2m5-4a9 9 0 11-18 0 9 9 0 0118 0z"/>
                            </svg>
                        </div>
                    </div>
                </section>

                <!-- Heading -->
                <div class="mb-6">
                    <h2 class="text-xl font-bold tracking-tight text-slate-800">
                        Danh sách môn thi
                    </h2>
                    <p class="mt-1 text-sm text-slate-500">
                        Chọn một môn để tiếp tục.
                    </p>
                </div>

                <!-- Subject cards -->
                <?php if (empty($dsMonThi)): ?>

                    <div class="rounded-2xl border border-dashed border-slate-300 bg-white px-6 py-16 text-center">
                        <div class="mx-auto flex h-16 w-16 items-center justify-center rounded-2xl bg-slate-100 text-slate-400">
                            <svg class="h-8 w-8" fill="none" stroke="currentColor"
                                viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round"
                                    stroke-width="1.5" d="M12 6.5v11m-5.5-5.5h11M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                            </svg>
                        </div>

                        <h3 class="mt-5 text-lg font-bold text-slate-800">
                            Chưa có môn thi
                        </h3>

                        <p class="mt-2 text-sm text-slate-500">
                            Hiện chưa có môn học nào được cấu hình làm môn thi.
                        </p>
                    </div>

                <?php else: ?>

                    <div class="grid grid-cols-1 gap-6 sm:grid-cols-2 xl:grid-cols-3">

                        <?php foreach ($dsMonThi as $mon): ?>
                            <?php
                                $tenMon = $mon['tenmonhoc'] ?? 'Môn học';
                                $config = $monThiConfig[$tenMon] ?? [
                                    'icon' => 'M12 6v12m-6-6h12',
                                    'color' => 'blue'
                                ];

                                $colors = [
                                    'blue' => [
                                        'icon' => 'bg-blue-50 text-blue-600 group-hover:bg-blue-600',
                                        'badge' => 'bg-blue-50 text-blue-700',
                                        'hover' => 'group-hover:text-blue-700',
                                        'border' => 'hover:border-blue-200',
                                        'arrow' => 'group-hover:bg-blue-50 group-hover:text-blue-600'
                                    ],
                                    'amber' => [
                                        'icon' => 'bg-amber-50 text-amber-600 group-hover:bg-amber-500',
                                        'badge' => 'bg-amber-50 text-amber-700',
                                        'hover' => 'group-hover:text-amber-700',
                                        'border' => 'hover:border-amber-200',
                                        'arrow' => 'group-hover:bg-amber-50 group-hover:text-amber-600'
                                    ],
                                    'violet' => [
                                        'icon' => 'bg-violet-50 text-violet-600 group-hover:bg-violet-600',
                                        'badge' => 'bg-violet-50 text-violet-700',
                                        'hover' => 'group-hover:text-violet-700',
                                        'border' => 'hover:border-violet-200',
                                        'arrow' => 'group-hover:bg-violet-50 group-hover:text-violet-600'
                                    ]
                                ];

                                $style = $colors[$config['color']];
                            ?>

                            <a href="?mamon=<?= urlencode((string)($mon['mamonhoc'] ?? '')) ?>"
                                class="group flex flex-col rounded-2xl border border-slate-200/80 bg-white p-6 shadow-sm transition duration-300 hover:-translate-y-1.5 hover:shadow-xl <?= $style['border'] ?>">

                                <div class="flex items-start justify-between">
                                    <div class="flex h-14 w-14 items-center justify-center rounded-2xl transition duration-300 group-hover:scale-110 group-hover:text-white <?= $style['icon'] ?>">
                                        <svg class="h-7 w-7" fill="none"
                                            stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round"
                                                stroke-linejoin="round" stroke-width="1.8"
                                                d="<?= htmlspecialchars($config['icon'], ENT_QUOTES, 'UTF-8') ?>"/>
                                        </svg>
                                    </div>

                                    <span class="rounded-full px-3 py-1 text-xs font-semibold <?= $style['badge'] ?>">
                                        Môn thi
                                    </span>
                                </div>

                                <h3 class="mt-6 text-lg font-bold text-slate-800 transition <?= $style['hover'] ?>">
                                    <?= htmlspecialchars($tenMon, ENT_QUOTES, 'UTF-8') ?>
                                </h3>

                                <p class="mt-2 flex-1 text-sm leading-6 text-slate-500">
                                    Quản lý và biên soạn đề thi môn
                                    <?= htmlspecialchars($tenMon, ENT_QUOTES, 'UTF-8') ?>.
                                </p>

                                <div class="mt-6 flex items-center justify-between border-t border-slate-100 pt-4">
                                    <span class="text-sm font-semibold text-slate-600 <?= $style['hover'] ?>">
                                        Chọn môn này
                                    </span>

                                    <span class="flex h-9 w-9 items-center justify-center rounded-xl bg-slate-50 text-slate-400 transition duration-300 group-hover:translate-x-1 <?= $style['arrow'] ?>">
                                        <svg class="h-5 w-5" fill="none"
                                            stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round"
                                                stroke-linejoin="round" stroke-width="2"
                                                d="M5 12h14m-7-7 7 7-7 7"/>
                                        </svg>
                                    </span>
                                </div>
                            </a>

                        <?php endforeach; ?>

                    </div>
                <?php endif; ?>

                <!-- Note -->
                <div class="mt-8 flex items-start gap-3 rounded-2xl border border-blue-100 bg-blue-50/70 p-4">
                    <svg class="mt-0.5 h-5 w-5 shrink-0 text-blue-600"
                        fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round"
                            stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                    </svg>

                    <div>
                        <p class="text-sm font-semibold text-blue-900">
                            Lưu ý
                        </p>
                        <p class="mt-1 text-sm leading-6 text-blue-800/80">
                            Danh sách chỉ hiển thị các môn được đánh dấu là môn thi.
                        </p>
                    </div>
                </div>

            </div>
        <?php endif;?>
        <?php if (isset($_GET['mamon'])): ?>

            <?php
                $mamon = $_GET['mamon'];

                $tenVaiTro = [
                    'CB'        => 'Cán bộ',
                    'DP'        => 'Cán bộ dự phòng',
                    'TRUONG_HD' => 'Trưởng hội đồng'
                ];

                $thongBao = '';
                $thanhCong = false;

                if (isset($_POST['them_hoi_dong'])) {
                    $dsChon = $_POST['gv_chon'] ?? [];
                    $vaiTro = $_POST['vaitro'] ?? [];

                    if (empty($dsChon)) {
                        $thongBao = 'Vui lòng chọn ít nhất một giáo viên.';
                    } else {
                        $soLuong = 0;

                        foreach ($dsChon as $id) {
                            $vt = $vaiTro[$id] ?? 'CB';
                            if (!isset($tenVaiTro[$vt])) {
                                $vt = 'CB';
                            }

                            // Đổi tên hàm cho đúng với controller của bạn
                            if ($ctrl->ThemGiaoVienVaoHoiDong($loaihoidong, $mamon, $id, $vt, null)) {
                                $soLuong++;
                            }
                        }

                        $thanhCong = $soLuong > 0;
                        $thongBao = $thanhCong
                            ? "Đã thêm $soLuong giáo viên vào hội đồng."
                            : 'Không thể thêm giáo viên vào hội đồng, vui lòng thử lại.';
                    }
                }

                $dsPhanCong = $ctrl->GetGiaoVienDaPhanCong($loaihoidong, $mamon);
                $gvdecu     = $ctrl->GetGiaoVienDuocDeCuTheoMaMon($mamon);
            ?>

            <div class="mx-auto max-w-7xl space-y-8 px-4 py-8 sm:px-6 lg:px-8">

                <div>
                    <h2 class="text-2xl font-bold text-gray-900">Hội đồng phúc khảo</h2>
                    <p class="mt-2 text-sm text-gray-500">Quản lý danh sách giáo viên tham gia hội đồng phúc khảo.</p>
                </div>

                <?php if ($thongBao): ?>
                    <div class="rounded-lg border px-4 py-3 text-sm <?= $thanhCong ? 'border-green-200 bg-green-50 text-green-700' : 'border-red-200 bg-red-50 text-red-700' ?>">
                        <?= htmlspecialchars($thongBao) ?>
                    </div>
                <?php endif; ?>

                <!-- ĐÃ PHÂN CÔNG -->
                <section class="overflow-hidden rounded-xl border border-gray-200 bg-white shadow-sm">
                    <h3 class="border-b border-gray-200 px-6 py-5 text-lg font-semibold text-gray-900">
                        Danh sách đã phân công
                    </h3>

                    <?php if (!empty($dsPhanCong)): ?>
                        <div class="overflow-x-auto">
                            <table class="min-w-full divide-y divide-gray-200 text-sm">
                                <thead class="bg-gray-50 text-left text-xs font-semibold text-gray-500">
                                    <tr>
                                        <th class="px-6 py-3">Mã giáo viên</th>
                                        <th class="px-6 py-3">Tên giáo viên</th>
                                        <th class="px-6 py-3">Cấp giảng dạy</th>
                                        <th class="px-6 py-3">Phân công</th>
                                    </tr>
                                </thead>
                                <tbody class="divide-y divide-gray-100">
                                    <?php foreach ($dsPhanCong as $gv): ?>
                                        <tr class="hover:bg-gray-50">
                                            <td class="px-6 py-4"><?= htmlspecialchars($gv['magiaovien']) ?></td>
                                            <td class="px-6 py-4 font-medium"><?= htmlspecialchars($gv['tengiaovien']) ?></td>
                                            <td class="px-6 py-4"><?= htmlspecialchars($gv['capgiangday']) ?></td>
                                            <td class="px-6 py-4"><?= htmlspecialchars($tenVaiTro[$gv['vaitro']] ?? $gv['vaitro']) ?></td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    <?php else: ?>
                        <p class="px-6 py-8 text-center text-sm text-gray-500">Chưa có giáo viên nào được phân công.</p>
                    <?php endif; ?>
                </section>

                <!-- ĐƯỢC ĐỀ CỬ -->
                <section class="overflow-hidden rounded-xl border border-gray-200 bg-white shadow-sm">
                    <div class="border-b border-gray-200 px-6 py-5">
                        <h3 class="text-lg font-semibold text-gray-900">Giáo viên được đề cử</h3>
                        <p class="mt-1 text-sm text-gray-500">Chọn giáo viên, phân công vai trò rồi thêm vào hội đồng.</p>
                    </div>

                    <form method="POST">
                        <div class="overflow-x-auto">
                            <table class="min-w-full divide-y divide-gray-200 text-sm">
                                <thead class="bg-gray-50 text-left text-xs font-semibold text-gray-500">
                                    <tr>
                                        <th class="px-6 py-3 text-center">Chọn</th>
                                        <th class="px-6 py-3">Mã giáo viên</th>
                                        <th class="px-6 py-3">Tên giáo viên</th>
                                        <th class="px-6 py-3">Cấp giảng dạy</th>
                                        <th class="px-6 py-3">Phân công</th>
                                    </tr>
                                </thead>
                                <tbody class="divide-y divide-gray-100">
                                    <?php foreach ($gvdecu as $gv): ?>
                                        <tr class="hover:bg-blue-50/50">
                                            <td class="px-6 py-4 text-center">
                                                <input type="checkbox" name="gv_chon[]" value="<?= htmlspecialchars($gv['magiaovien']) ?>"
                                                    class="h-4 w-4 rounded border-gray-300 text-blue-600 focus:ring-blue-500">
                                            </td>
                                            <td class="px-6 py-4"><?= htmlspecialchars($gv['magiaovien']) ?></td>
                                            <td class="px-6 py-4 font-medium"><?= htmlspecialchars($gv['tengiaovien']) ?></td>
                                            <td class="px-6 py-4"><?= htmlspecialchars($gv['capgiangday']) ?></td>
                                            <td class="px-6 py-4">
                                                <select name="vaitro[<?= htmlspecialchars($gv['magiaovien']) ?>]"
                                                    class="w-full min-w-48 rounded-lg border border-gray-300 bg-white px-3 py-2 focus:border-blue-500 focus:outline-none focus:ring-2 focus:ring-blue-100">
                                                    <?php foreach ($tenVaiTro as $value => $label): ?>
                                                        <option value="<?= $value ?>"><?= $label ?></option>
                                                    <?php endforeach; ?>
                                                </select>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>

                        <div class="flex justify-end border-t border-gray-200 bg-gray-50 px-6 py-5">
                            <button type="submit" name="them_hoi_dong"
                                class="rounded-lg bg-blue-600 px-5 py-3 text-sm font-semibold text-white transition hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-offset-2">
                                Thêm vào hội đồng
                            </button>
                        </div>
                    </form>
                </section>

            </div>

        <?php endif; ?>
    </main>
</body>
</html>