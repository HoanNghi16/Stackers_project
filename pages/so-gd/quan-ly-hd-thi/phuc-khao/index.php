<?php
    session_start();
    require "../../../../index.php";
    require "../../../../class/quan-ly-hd-thi.php";

    $ctrl = new CtrlQuanLyHoiDongThi($conn);
    $loaihoidong = "phuckhao";
    const MIN_DP = 5;     // số cán bộ dự phòng tối thiểu
    const MIN_TRUONG = 1; // số trưởng hội đồng tối thiểu

    $tenVaiTro = [
        'CB'        => 'Cán bộ',
        'DP'        => 'Cán bộ dự phòng',
        'TRUONG_HD' => 'Trưởng hội đồng'
    ];

    $dsMonThi = $ctrl->GetMonThi() ?: [];
    $mamon = isset($_GET['mamon']) ? (string)$_GET['mamon'] : null;
    $monHienTai = null;

    if ($mamon !== null) {
        foreach ($dsMonThi as $m) {
            if ((string)($m['mamonhoc'] ?? '') === $mamon) {
                $monHienTai = $m;
                break;
            }
        }
        // Mã môn không hợp lệ -> về trang chọn môn
        if ($monHienTai === null) {
            header('Location: ?');
            exit;
        }
    }

    if ($monHienTai !== null) {
        $dsPhanCong = $ctrl->GetGiaoVienDaPhanCong($loaihoidong, $mamon) ?: [];
        $gvdecuAll  = $ctrl->GetGiaoVienDuocDeCuTheoMaMon($mamon) ?: [];

        $daPhanCongIds = array_map(fn($g) => (string)$g['magiaovien'], $dsPhanCong);
        // Chỉ hiển thị GV được đề cử mà chưa được phân công
        $gvdecu = array_values(array_filter(
            $gvdecuAll,
            fn($g) => !in_array((string)$g['magiaovien'], $daPhanCongIds, true)
        ));
        $idHopLe = array_map(fn($g) => (string)$g['magiaovien'], $gvdecu);

        $soDPDaCo     = count(array_filter($dsPhanCong, fn($g) => $g['vaitro'] === 'DP'));
        $soTruongDaCo = count(array_filter($dsPhanCong, fn($g) => $g['vaitro'] === 'TRUONG_HD'));

        // Xử lý POST (Post/Redirect/Get)
        if (isset($_POST['them_hoi_dong'])) {
            $dsChon  = array_map('strval', (array)($_POST['gv_chon'] ?? []));
            $dsChon  = array_values(array_intersect($dsChon, $idHopLe)); // bỏ mã lạ
            $vaiTro  = (array)($_POST['vaitro'] ?? []);

            $chonVaiTro = [];
            foreach ($dsChon as $id) {
                $vt = $vaiTro[$id] ?? 'CB';
                $chonVaiTro[$id] = isset($tenVaiTro[$vt]) ? $vt : 'CB';
            }

            $soDPChon     = count(array_filter($chonVaiTro, fn($v) => $v === 'DP'));
            $soTruongChon = count(array_filter($chonVaiTro, fn($v) => $v === 'TRUONG_HD'));
            $tongDP       = $soDPDaCo + $soDPChon;
            $tongTruong   = $soTruongDaCo + $soTruongChon;

            $loi = [];
            if ($tongDP < MIN_DP) {
                $loi[] = "cán bộ dự phòng $tongDP/" . MIN_DP . " (còn thiếu " . (MIN_DP - $tongDP) . ")";
            }
            if ($tongTruong < MIN_TRUONG) {
                $loi[] = "trưởng hội đồng $tongTruong/" . MIN_TRUONG;
            }

            if (empty($chonVaiTro)) {
                $_SESSION['flash'] = ['ok' => false, 'msg' => 'Vui lòng chọn ít nhất một giáo viên.'];
            } elseif (!empty($loi)) {
                $_SESSION['flash'] = ['ok' => false, 'msg' => 'Chưa đủ điều kiện: ' . implode('; ', $loi) . '.'];
            } else {
                $soLuong = 0;
                foreach ($chonVaiTro as $id => $vt) {
                    if ($ctrl->ThemGiaoVienVaoHoiDong($loaihoidong, $mamon, $id, $vt, null)) {
                        $soLuong++;
                    }
                }
                $_SESSION['flash'] = $soLuong > 0
                    ? ['ok' => true,  'msg' => "Đã thêm $soLuong giáo viên vào hội đồng."]
                    : ['ok' => false, 'msg' => 'Không thể thêm giáo viên vào hội đồng, vui lòng thử lại.'];
            }

            header('Location: ?mamon=' . urlencode($mamon));
            exit;
        }
    }

    $flash = $_SESSION['flash'] ?? null;
    unset($_SESSION['flash']);
?>

<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Stackers - Chọn môn thi</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>

<body class="bg-slate-50 text-[13px] text-slate-700">

    <?php
        require "../../../../components/layout/header.php";
        require "../../../../components/layout/sidebar.php";

        renderHeader();
        renderSidebar();
    ?>

    <main class="ml-72 min-h-screen px-6 pb-10 pt-20">

        <div class="mb-4 pt-3">
            <a href="<?= $monHienTai ? '?' : '../' ?>"
               class="inline-flex items-center gap-2 rounded-lg border border-slate-200 bg-white px-3 py-2 text-xs font-medium text-slate-600 shadow-sm transition hover:border-blue-200 hover:bg-blue-50 hover:text-blue-600">
                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.8" stroke="currentColor" class="h-4 w-4">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M10.5 19.5 3 12m0 0 7.5-7.5M3 12h18"/>
                </svg>
                <span>Quay lại</span>
            </a>
        </div>

<?php if ($monHienTai === null): ?>
        <?php
            $monThiConfig = [
                'Toán'      => ['icon' => 'M3 3v18h18 M19 9l-5 5-4-4-3 3', 'color' => 'blue'],
                'Ngữ văn'   => ['icon' => 'M4 19.5A2.5 2.5 0 016.5 17H20 M6.5 2H20v20H6.5A2.5 2.5 0 014 19.5v-15A2.5 2.5 0 016.5 2z', 'color' => 'amber'],
                'Tiếng Anh' => ['icon' => 'M5 8l6 6 6-6 M4 4h16 M4 20h16', 'color' => 'violet'],
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
                ],
            ];
        ?>

        <!-- Hero -->
        <section class="mb-6 rounded-2xl bg-gradient-to-br from-blue-700 via-blue-600 to-indigo-700 px-6 py-6 text-white shadow-lg shadow-blue-900/10">
            <h1 class="text-xl font-extrabold tracking-tight md:text-2xl">Hội đồng phúc khảo</h1>
            <p class="mt-2 text-xs leading-6 text-blue-100 md:text-sm">Chọn môn thi để phân công hội đồng phúc khảo.</p>
        </section>

        <div class="mb-4">
            <h2 class="text-base font-bold text-slate-800">Danh sách môn thi</h2>
            <p class="mt-0.5 text-xs text-slate-500">Chọn một môn để tiếp tục.</p>
        </div>

        <?php if (empty($dsMonThi)): ?>
            <div class="rounded-xl border border-dashed border-slate-300 bg-white px-6 py-12 text-center">
                <h3 class="text-base font-bold text-slate-800">Chưa có môn thi</h3>
                <p class="mt-1 text-xs text-slate-500">Hiện chưa có môn học nào được cấu hình làm môn thi.</p>
            </div>
        <?php else: ?>
            <div class="grid grid-cols-1 gap-4 sm:grid-cols-2 xl:grid-cols-3">
                <?php foreach ($dsMonThi as $mon): ?>
                    <?php
                        $tenMon = $mon['tenmonhoc'] ?? 'Môn học';
                        $config = $monThiConfig[$tenMon] ?? ['icon' => 'M12 6v12m-6-6h12', 'color' => 'blue'];
                        $style  = $colors[$config['color']];
                    ?>
                    <a href="?mamon=<?= urlencode((string)($mon['mamonhoc'] ?? '')) ?>"
                       class="group flex flex-col rounded-xl border border-slate-200/80 bg-white p-4 shadow-sm transition duration-300 hover:-translate-y-1 hover:shadow-lg <?= $style['border'] ?>">

                        <div class="flex items-start justify-between">
                            <div class="flex h-10 w-10 items-center justify-center rounded-xl transition duration-300 group-hover:text-white <?= $style['icon'] ?>">
                                <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8"
                                          d="<?= htmlspecialchars($config['icon'], ENT_QUOTES, 'UTF-8') ?>"/>
                                </svg>
                            </div>
                            <span class="rounded-full px-2.5 py-0.5 text-[11px] font-semibold <?= $style['badge'] ?>">Môn thi</span>
                        </div>

                        <h3 class="mt-4 text-sm font-bold text-slate-800 transition <?= $style['hover'] ?>">
                            <?= htmlspecialchars($tenMon, ENT_QUOTES, 'UTF-8') ?>
                        </h3>
                        <p class="mt-1 flex-1 text-xs leading-5 text-slate-500">
                            Phân công hội đồng phúc khảo môn <?= htmlspecialchars($tenMon, ENT_QUOTES, 'UTF-8') ?>.
                        </p>

                        <div class="mt-4 flex items-center justify-between border-t border-slate-100 pt-3">
                            <span class="text-xs font-semibold text-slate-600 <?= $style['hover'] ?>">Chọn môn này</span>
                            <span class="flex h-7 w-7 items-center justify-center rounded-lg bg-slate-50 text-slate-400 transition duration-300 group-hover:translate-x-1 <?= $style['arrow'] ?>">
                                <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 12h14m-7-7 7 7-7 7"/>
                                </svg>
                            </span>
                        </div>
                    </a>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>

        <div class="mt-6 flex items-start gap-3 rounded-xl border border-blue-100 bg-blue-50/70 p-3">
            <svg class="mt-0.5 h-4 w-4 shrink-0 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
            </svg>
            <p class="text-xs leading-5 text-blue-800/80">
                <span class="font-semibold text-blue-900">Lưu ý:</span>
                Danh sách chỉ hiển thị các môn được đánh dấu là môn thi.
            </p>
        </div>

<?php else: ?>
        <!-- Tiêu đề + trạng thái DP -->
        <div class="mb-4 flex flex-col gap-4 lg:flex-row lg:items-end lg:justify-between">
            <div>
                <p class="text-xs font-medium uppercase tracking-wide text-blue-600">Hội đồng phúc khảo</p>
                <h2 class="mt-1 text-xl font-bold text-slate-900">
                    Phân công giáo viên — <?= htmlspecialchars($monHienTai['tenmonhoc'] ?? '', ENT_QUOTES, 'UTF-8') ?>
                </h2>
                <p class="mt-1 text-xs text-slate-500">Chọn giáo viên được đề cử, phân vai trò rồi thêm vào hội đồng.</p>
            </div>

            <div class="w-full space-y-3 rounded-xl border border-slate-200 bg-white px-4 py-3 shadow-sm lg:w-80">
                <div>
                    <div class="flex items-center justify-between text-xs">
                        <span class="font-semibold text-slate-700">Cán bộ dự phòng (tối thiểu <?= MIN_DP ?>)</span>
                        <span id="dp-count" class="font-bold text-slate-900">0/<?= MIN_DP ?></span>
                    </div>
                    <div class="mt-1.5 h-1.5 overflow-hidden rounded-full bg-slate-100">
                        <div id="dp-bar" class="h-full w-0 rounded-full bg-amber-500 transition-all duration-300"></div>
                    </div>
                    <p id="dp-msg" class="mt-1.5 text-xs text-slate-500"></p>
                </div>
                <div>
                    <div class="flex items-center justify-between text-xs">
                        <span class="font-semibold text-slate-700">Trưởng hội đồng (tối thiểu <?= MIN_TRUONG ?>)</span>
                        <span id="tr-count" class="font-bold text-slate-900">0/<?= MIN_TRUONG ?></span>
                    </div>
                    <div class="mt-1.5 h-1.5 overflow-hidden rounded-full bg-slate-100">
                        <div id="tr-bar" class="h-full w-0 rounded-full bg-amber-500 transition-all duration-300"></div>
                    </div>
                    <p id="tr-msg" class="mt-1.5 text-xs text-slate-500"></p>
                </div>
            </div>
        </div>

        <?php if ($flash): ?>
            <div class="mb-4 rounded-lg border px-4 py-2.5 text-xs <?= $flash['ok'] ? 'border-green-200 bg-green-50 text-green-700' : 'border-red-200 bg-red-50 text-red-700' ?>">
                <?= htmlspecialchars($flash['msg']) ?>
            </div>
        <?php endif; ?>

        <div class="grid grid-cols-1 gap-4 xl:grid-cols-5">

            <!-- ĐÃ PHÂN CÔNG -->
            <section class="flex flex-col overflow-hidden rounded-xl border border-gray-200 bg-white shadow-sm xl:col-span-2">
                <div class="flex items-center justify-between border-b border-gray-200 px-4 py-3">
                    <h3 class="text-sm font-semibold text-gray-900">Đã phân công</h3>
                    <span class="rounded-full bg-slate-100 px-2 py-0.5 text-[11px] font-medium text-slate-600"><?= count($dsPhanCong) ?></span>
                </div>

                <?php if (!empty($dsPhanCong)): ?>
                    <div class="max-h-[60vh] overflow-auto">
                        <table class="min-w-full divide-y divide-gray-200 text-[13px]">
                            <thead class="sticky top-0 z-10 bg-gray-50 text-left text-[11px] font-semibold uppercase tracking-wide text-gray-500">
                                <tr>
                                    <th class="px-4 py-2">Mã GV</th>
                                    <th class="px-4 py-2">Tên giáo viên</th>
                                    <th class="px-4 py-2">Cấp</th>
                                    <th class="px-4 py-2">Vai trò</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-gray-100">
                                <?php foreach ($dsPhanCong as $gv): ?>
                                    <tr class="hover:bg-gray-50">
                                        <td class="whitespace-nowrap px-4 py-2"><?= htmlspecialchars($gv['magiaovien']) ?></td>
                                        <td class="px-4 py-2 font-medium"><?= htmlspecialchars($gv['tengiaovien']) ?></td>
                                        <td class="whitespace-nowrap px-4 py-2"><?= htmlspecialchars($gv['capgiangday']) ?></td>
                                        <td class="whitespace-nowrap px-4 py-2"><?= htmlspecialchars($tenVaiTro[$gv['vaitro']] ?? $gv['vaitro']) ?></td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                <?php else: ?>
                    <p class="px-4 py-8 text-center text-xs text-gray-500">Chưa có giáo viên nào được phân công.</p>
                <?php endif; ?>
            </section>

            <!-- ĐƯỢC ĐỀ CỬ -->
            <section class="flex flex-col overflow-hidden rounded-xl border border-gray-200 bg-white shadow-sm xl:col-span-3">
                <div class="flex items-center justify-between border-b border-gray-200 px-4 py-3">
                    <h3 class="text-sm font-semibold text-gray-900">Giáo viên được đề cử</h3>
                    <span class="rounded-full bg-slate-100 px-2 py-0.5 text-[11px] font-medium text-slate-600"><?= count($gvdecu) ?></span>
                </div>

                <form method="POST" id="form-hoi-dong" class="flex min-h-0 flex-1 flex-col">
                    <?php if (!empty($gvdecu)): ?>
                        <div class="max-h-[60vh] overflow-auto">
                            <table class="min-w-full divide-y divide-gray-200 text-[13px]">
                                <thead class="sticky top-0 z-10 bg-gray-50 text-left text-[11px] font-semibold uppercase tracking-wide text-gray-500">
                                    <tr>
                                        <th class="w-12 px-4 py-2 text-center">Chọn</th>
                                        <th class="px-4 py-2">Mã GV</th>
                                        <th class="px-4 py-2">Tên giáo viên</th>
                                        <th class="px-4 py-2">Cấp</th>
                                        <th class="px-4 py-2">Vai trò</th>
                                    </tr>
                                </thead>
                                <tbody class="divide-y divide-gray-100">
                                    <?php foreach ($gvdecu as $gv): ?>
                                        <tr data-row class="hover:bg-blue-50/50">
                                            <td class="px-4 py-2 text-center">
                                                <input type="checkbox" name="gv_chon[]"
                                                       value="<?= htmlspecialchars($gv['magiaovien']) ?>"
                                                       class="h-4 w-4 rounded border-gray-300 text-blue-600 focus:ring-blue-500">
                                            </td>
                                            <td class="whitespace-nowrap px-4 py-2"><?= htmlspecialchars($gv['magiaovien']) ?></td>
                                            <td class="px-4 py-2 font-medium"><?= htmlspecialchars($gv['tengiaovien']) ?></td>
                                            <td class="whitespace-nowrap px-4 py-2"><?= htmlspecialchars($gv['capgiangday']) ?></td>
                                            <td class="px-4 py-2">
                                                <select name="vaitro[<?= htmlspecialchars($gv['magiaovien']) ?>]"
                                                        class="w-full min-w-40 rounded-md border border-gray-300 bg-white px-2 py-1.5 text-xs focus:border-blue-500 focus:outline-none focus:ring-2 focus:ring-blue-100">
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
                    <?php else: ?>
                        <p class="px-4 py-8 text-center text-xs text-gray-500">Không còn giáo viên nào để thêm.</p>
                    <?php endif; ?>

                    <div class="mt-auto flex items-center justify-between gap-3 border-t border-gray-200 bg-gray-50 px-4 py-3">
                        <span id="chon-count" class="text-xs text-slate-500">Đã chọn 0 giáo viên</span>
                        <button type="submit" name="them_hoi_dong" id="btn-submit" disabled
                                class="rounded-lg bg-blue-600 px-4 py-2 text-xs font-semibold text-white transition hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-offset-2 disabled:cursor-not-allowed disabled:opacity-50 disabled:hover:bg-blue-600">
                            Thêm vào hội đồng
                        </button>
                    </div>
                </form>
            </section>
        </div>

        <script>
            (function () {
                const MIN = <?= MIN_DP ?>;
                const MIN_TR = <?= MIN_TRUONG ?>;
                const daCo = <?= (int)$soDPDaCo ?>;
                const daCoTr = <?= (int)$soTruongDaCo ?>;

                const rows = document.querySelectorAll('tr[data-row]');
                const elChon = document.getElementById('chon-count');
                const btn = document.getElementById('btn-submit');

                function render(prefix, tong, min, label) {
                    const du = tong >= min;
                    document.getElementById(prefix + '-count').textContent = tong + '/' + min;
                    const bar = document.getElementById(prefix + '-bar');
                    bar.style.width = Math.min(100, tong / min * 100) + '%';
                    bar.className = 'h-full rounded-full transition-all duration-300 ' + (du ? 'bg-green-500' : 'bg-amber-500');
                    const msg = document.getElementById(prefix + '-msg');
                    msg.textContent = du ? 'Đã đủ ' + label + ' tối thiểu.' : 'Còn thiếu ' + (min - tong) + ' ' + label + '.';
                    msg.className = 'mt-1.5 text-xs ' + (du ? 'text-green-600' : 'text-amber-600');
                    return du;
                }

                function update() {
                    let chon = 0, dpChon = 0, trChon = 0;
                    rows.forEach(tr => {
                        const cb = tr.querySelector('input[type=checkbox]');
                        const sel = tr.querySelector('select');
                        if (cb.checked) {
                            chon++;
                            if (sel.value === 'DP') dpChon++;
                            if (sel.value === 'TRUONG_HD') trChon++;
                        }
                    });

                    const duDP = render('dp', daCo + dpChon, MIN, 'cán bộ dự phòng');
                    const duTr = render('tr', daCoTr + trChon, MIN_TR, 'trưởng hội đồng');

                    elChon.textContent = 'Đã chọn ' + chon + ' giáo viên';
                    btn.disabled = !(chon > 0 && duDP && duTr);
                }

                rows.forEach(tr => {
                    tr.querySelector('input[type=checkbox]').addEventListener('change', update);
                    tr.querySelector('select').addEventListener('change', update);
                });
                update();
            })();
        </script>
<?php endif; ?>
    </main>
</body>
</html>