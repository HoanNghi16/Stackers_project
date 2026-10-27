<?php
    session_start();

    require "../../../../components/layout/header.php";
    require "../../../../components/layout/sidebar.php";
    require "../../../../index.php";
    require "../../../../class/quan-ly-hd-thi.php";

    $ctrl = new CtrlQuanLyHoiDongThi($conn);

    $vaiTroGacThi = [
        'CB' => 'Cán bộ',
        'DP' => 'Cán bộ dự phòng'
    ];
    // Dùng để hiển thị danh sách đã phân công (có thể có hiệu trưởng)
    $tenVaiTro = $vaiTroGacThi + ['TRUONG_HD' => 'Trưởng hội đồng'];
    $mauVaiTro = [
        'CB'        => 'bg-slate-100 text-slate-700',
        'DP'        => 'bg-amber-50 text-amber-700',
        'TRUONG_HD' => 'bg-blue-50 text-blue-700',
    ];

    $soDuPhongToiThieu = 5;

    $dsDiaDiem = $ctrl->GetDanhSachDiaDiemThi() ?: [];
    $dsGiaoVienChuaPhanCong = $ctrl->GetGiaoVienDuocDeCu() ?: [];

    // Địa điểm đang chọn (GET khi xem, POST khi lưu) - phải nằm trong danh sách
    $madiadiemthi = (string)($_POST['madiadiemthi'] ?? $_GET['madiadiemthi'] ?? '');
    $diaDiemHienTai = null;
    foreach ($dsDiaDiem as $dd) {
        if ((string)$dd['madiadiemthi'] === $madiadiemthi) {
            $diaDiemHienTai = $dd;
            break;
        }
    }
    if ($diaDiemHienTai === null) {
        $madiadiemthi = '';
    }

    // Danh sách đã phân công của trường đang chọn
    $dsDaPhanCong = [];
    $soDPDaCo = 0;
    $gvChon = $dsGiaoVienChuaPhanCong;

    if ($diaDiemHienTai !== null) {
        $matruonghoc = $diaDiemHienTai['matruonghoc'] ?? $diaDiemHienTai['madiadiemthi'];
        $dsDaPhanCong = $ctrl->GetGiaoVienGacThiDaPhanCong($matruonghoc) ?: [];

        $soDPDaCo = count(array_filter($dsDaPhanCong, fn($g) => ($g['vaitro'] ?? '') === 'DP'));

        // Ẩn giáo viên đã được phân công ở trường này khỏi bảng chọn
        $daPhanCongIds = array_map(fn($g) => (string)$g['magiaovien'], $dsDaPhanCong);
        $gvChon = array_values(array_filter(
            $dsGiaoVienChuaPhanCong,
            fn($g) => !in_array((string)$g['magiaovien'], $daPhanCongIds, true)
        ));
    }
    $idHopLe = array_map(fn($g) => (string)$g['magiaovien'], $gvChon);

    $dsChon = array_map('strval', (array)($_POST['gv_chon'] ?? []));
    $vaiTro = (array)($_POST['vaitro'] ?? []);

    $thongBao  = '';
    $thanhCong = false;

    // Thông báo sau khi lưu thành công (Post/Redirect/Get)
    if (isset($_SESSION['flash'])) {
        $thongBao  = $_SESSION['flash']['msg'];
        $thanhCong = $_SESSION['flash']['ok'];
        unset($_SESSION['flash']);
    }

    if (isset($_POST['luu_danh_sach'])) {
        $dsChon = array_values(array_intersect($dsChon, $idHopLe)); // bỏ mã lạ
        $dsGiaoVien = [];

        foreach ($dsChon as $id) {
            $vt = $vaiTro[$id] ?? 'CB';
            $dsGiaoVien[$id] = isset($vaiTroGacThi[$vt]) ? $vt : 'CB';
        }

        $soDPChon = count(array_filter($dsGiaoVien, fn($vt) => $vt === 'DP'));
        $tongDP   = $soDPDaCo + $soDPChon;

        if ($diaDiemHienTai === null) {
            $thongBao = 'Vui lòng chọn địa điểm thi.';
        } elseif (empty($dsGiaoVien)) {
            $thongBao = 'Vui lòng chọn ít nhất một giáo viên.';
        } elseif ($tongDP < $soDuPhongToiThieu) {
            $thongBao = "Chưa đủ cán bộ dự phòng: hiện có $tongDP/$soDuPhongToiThieu, còn thiếu " . ($soDuPhongToiThieu - $tongDP) . " người.";
        } elseif ($ctrl->LuuHoiDongGacThi($madiadiemthi, $dsGiaoVien)) {
            $_SESSION['flash'] = ['ok' => true, 'msg' => 'Đã lưu danh sách hội đồng gác thi.'];
            header('Location: ?madiadiemthi=' . urlencode($madiadiemthi));
            exit;
        } else {
            $thongBao = 'Không thể lưu danh sách, vui lòng thử lại.';
        }
        // Lỗi: giữ nguyên lựa chọn của người dùng để họ sửa tiếp
    }
?>

<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Stackers - Hội đồng gác thi</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>

<body class="min-h-screen bg-slate-50 text-[13px] text-slate-700">
    <?php
        renderHeader();
        renderSidebar();
    ?>

    <main class="ml-72 min-h-screen px-6 pb-10 pt-24">

        <!-- TIÊU ĐỀ + TRẠNG THÁI DỰ PHÒNG -->
        <section class="mb-4 flex flex-col gap-4 lg:flex-row lg:items-end lg:justify-between">
            <div>
                <p class="text-xs font-medium uppercase tracking-wide text-blue-600">Phân công gác thi</p>
                <h1 class="mt-1 text-xl font-bold tracking-tight text-slate-900">Hội đồng gác thi</h1>
                <p class="mt-1 max-w-2xl text-xs leading-5 text-slate-500">
                    Phân công giáo viên gác thi theo địa điểm. Trưởng hội đồng mặc định là hiệu trưởng của điểm thi.
                </p>
            </div>

            <?php if ($diaDiemHienTai !== null): ?>
                <div class="w-full rounded-xl border border-slate-200 bg-white px-4 py-3 shadow-sm lg:w-80">
                    <div class="flex items-center justify-between text-xs">
                        <span class="font-semibold text-slate-700">Cán bộ dự phòng (tối thiểu <?= $soDuPhongToiThieu ?>)</span>
                        <span id="dp-count" class="font-bold text-slate-900">0/<?= $soDuPhongToiThieu ?></span>
                    </div>
                    <div class="mt-1.5 h-1.5 overflow-hidden rounded-full bg-slate-100">
                        <div id="dp-bar" class="h-full w-0 rounded-full bg-amber-500 transition-all duration-300"></div>
                    </div>
                    <p id="dp-msg" class="mt-1.5 text-xs text-slate-500"></p>
                </div>
            <?php endif; ?>
        </section>

        <!-- THÔNG BÁO -->
        <?php if ($thongBao): ?>
            <div class="mb-4 flex items-start gap-2 rounded-lg border px-4 py-2.5 text-xs
                <?= $thanhCong ? 'border-emerald-200 bg-emerald-50 text-emerald-800' : 'border-rose-200 bg-rose-50 text-rose-800' ?>">
                <svg class="mt-0.5 h-4 w-4 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                    <?php if ($thanhCong): ?>
                        <path stroke-linecap="round" stroke-linejoin="round" d="m5 12 4 4L19 6"/>
                    <?php else: ?>
                        <path stroke-linecap="round" stroke-linejoin="round" d="M12 9v4m0 4h.01M10.3 3.9 1.8 18.6A1.6 1.6 0 0 0 3.2 21h17.6a1.6 1.6 0 0 0 1.4-2.4L13.7 3.9a2 2 0 0 0-3.4 0Z"/>
                    <?php endif; ?>
                </svg>
                <p class="leading-5"><?= htmlspecialchars($thongBao) ?></p>
            </div>
        <?php endif; ?>

        <!-- CHỌN ĐỊA ĐIỂM (GET, tự tải lại khi đổi) -->
        <section class="mb-4 rounded-xl border border-slate-200 bg-white p-4 shadow-sm">
            <form method="GET" id="formDiaDiem" class="flex flex-col gap-2 sm:flex-row sm:items-center sm:gap-4">
                <label for="madiadiemthi" class="shrink-0 text-xs font-semibold text-slate-700">Địa điểm thi</label>
                <select id="madiadiemthi" name="madiadiemthi"
                        class="w-full rounded-lg border border-slate-300 bg-white px-3 py-2 text-xs outline-none transition focus:border-blue-500 focus:ring-2 focus:ring-blue-100 sm:max-w-md">
                    <option value="">-- Chọn địa điểm thi --</option>
                    <?php foreach ($dsDiaDiem as $dd): ?>
                        <option value="<?= htmlspecialchars($dd['madiadiemthi']) ?>"
                            <?= $madiadiemthi === (string)$dd['madiadiemthi'] ? 'selected' : '' ?>>
                            <?= htmlspecialchars($dd['tendiadiemthi']) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </form>
        </section>

<?php if ($diaDiemHienTai === null): ?>

        <div class="rounded-xl border border-dashed border-slate-300 bg-white px-6 py-12 text-center">
            <h3 class="text-sm font-bold text-slate-800">Chưa chọn địa điểm thi</h3>
            <p class="mt-1 text-xs text-slate-500">Chọn một địa điểm ở trên để xem và phân công hội đồng gác thi.</p>
        </div>

<?php else: ?>

        <div class="grid grid-cols-1 gap-4 xl:grid-cols-5">

            <!-- ĐÃ PHÂN CÔNG -->
            <section class="flex flex-col overflow-hidden rounded-xl border border-slate-200 bg-white shadow-sm xl:col-span-2">
                <div class="flex items-center justify-between border-b border-slate-100 px-4 py-3">
                    <h2 class="text-sm font-semibold text-slate-900">Đã phân công</h2>
                    <span class="rounded-full bg-slate-100 px-2 py-0.5 text-[11px] font-medium text-slate-600"><?= count($dsDaPhanCong) ?></span>
                </div>

                <?php if (!empty($dsDaPhanCong)): ?>
                    <div class="max-h-[60vh] overflow-auto">
                        <table class="min-w-full divide-y divide-slate-200 text-[13px]">
                            <thead class="sticky top-0 z-10 bg-slate-50 text-left text-[11px] font-semibold uppercase tracking-wide text-slate-500">
                                <tr>
                                    <th class="px-4 py-2">Mã GV</th>
                                    <th class="px-4 py-2">Tên giáo viên</th>
                                    <th class="px-4 py-2">Vai trò</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-slate-100">
                                <?php foreach ($dsDaPhanCong as $gv): ?>
                                    <?php $vt = $gv['vaitro'] ?? 'CB'; ?>
                                    <tr class="hover:bg-slate-50">
                                        <td class="whitespace-nowrap px-4 py-2 font-mono text-xs"><?= htmlspecialchars($gv['magiaovien']) ?></td>
                                        <td class="px-4 py-2 font-medium text-slate-800"><?= htmlspecialchars($gv['tengiaovien']) ?></td>
                                        <td class="whitespace-nowrap px-4 py-2">
                                            <span class="rounded-full px-2 py-0.5 text-[11px] font-semibold <?= $mauVaiTro[$vt] ?? $mauVaiTro['CB'] ?>">
                                                <?= htmlspecialchars($tenVaiTro[$vt] ?? $vt) ?>
                                            </span>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                <?php else: ?>
                    <p class="px-4 py-10 text-center text-xs text-slate-500">Địa điểm này chưa có giáo viên nào được phân công.</p>
                <?php endif; ?>
            </section>

            <!-- DANH SÁCH GIÁO VIÊN ĐỂ CHỌN -->
            <section class="flex flex-col overflow-hidden rounded-xl border border-slate-200 bg-white shadow-sm xl:col-span-3">
                <div class="flex items-center justify-between border-b border-slate-100 px-4 py-3">
                    <div>
                        <h2 class="text-sm font-semibold text-slate-900">Danh sách giáo viên</h2>
                        <p class="mt-0.5 text-xs text-slate-500">Chọn giáo viên và phân loại vai trò gác thi.</p>
                    </div>
                    <span class="rounded-full bg-slate-100 px-2 py-0.5 text-[11px] font-medium text-slate-600"><?= count($gvChon) ?></span>
                </div>

                <?php if (!empty($gvChon)): ?>
                    <form method="POST" id="form-gac-thi" class="flex min-h-0 flex-1 flex-col">
                        <input type="hidden" name="madiadiemthi" value="<?= htmlspecialchars($madiadiemthi) ?>">

                        <div class="max-h-[60vh] overflow-auto">
                            <table class="min-w-full divide-y divide-slate-200 text-[13px]">
                                <thead class="sticky top-0 z-10 bg-slate-50 text-left text-[11px] font-semibold uppercase tracking-wide text-slate-500">
                                    <tr>
                                        <th class="w-12 px-4 py-2 text-center">Chọn</th>
                                        <th class="px-4 py-2">Mã GV</th>
                                        <th class="px-4 py-2">Tên giáo viên</th>
                                        <th class="px-4 py-2">Cấp</th>
                                        <th class="px-4 py-2">Môn</th>
                                        <th class="px-4 py-2">Vai trò</th>
                                    </tr>
                                </thead>
                                <tbody class="divide-y divide-slate-100">
                                    <?php foreach ($gvChon as $gv): ?>
                                        <?php $id = (string)$gv['magiaovien']; ?>
                                        <tr data-row class="transition hover:bg-blue-50/40">
                                            <td class="px-4 py-2 text-center">
                                                <input type="checkbox" name="gv_chon[]"
                                                       value="<?= htmlspecialchars($id) ?>"
                                                       <?= in_array($id, $dsChon, true) ? 'checked' : '' ?>
                                                       class="h-4 w-4 cursor-pointer rounded border-slate-300 text-blue-600 focus:ring-2 focus:ring-blue-500 focus:ring-offset-1">
                                            </td>
                                            <td class="whitespace-nowrap px-4 py-2 font-mono text-xs"><?= htmlspecialchars($id) ?></td>
                                            <td class="px-4 py-2 font-medium text-slate-800"><?= htmlspecialchars($gv['tengiaovien']) ?></td>
                                            <td class="whitespace-nowrap px-4 py-2 text-slate-600"><?= htmlspecialchars($gv['capgiangday'] ?? '') ?></td>
                                            <td class="whitespace-nowrap px-4 py-2 text-slate-600"><?= htmlspecialchars($gv['tenmonhoc'] ?? '') ?></td>
                                            <td class="px-4 py-2">
                                                <select name="vaitro[<?= htmlspecialchars($id) ?>]"
                                                        class="w-full min-w-40 rounded-md border border-slate-300 bg-white px-2 py-1.5 text-xs outline-none focus:border-blue-500 focus:ring-2 focus:ring-blue-100">
                                                    <?php foreach ($vaiTroGacThi as $value => $label): ?>
                                                        <option value="<?= htmlspecialchars($value) ?>"
                                                            <?= ($vaiTro[$id] ?? 'CB') === $value ? 'selected' : '' ?>>
                                                            <?= htmlspecialchars($label) ?>
                                                        </option>
                                                    <?php endforeach; ?>
                                                </select>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>

                        <div class="mt-auto flex items-center justify-between gap-3 border-t border-slate-100 bg-slate-50 px-4 py-3">
                            <span id="chon-count" class="text-xs text-slate-500">Đã chọn 0 giáo viên</span>
                            <button type="submit" name="luu_danh_sach" id="btn-luu" disabled
                                    class="rounded-lg bg-blue-600 px-4 py-2 text-xs font-bold text-white shadow-sm transition hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-blue-300 disabled:cursor-not-allowed disabled:opacity-50 disabled:hover:bg-blue-600">
                                Lưu danh sách
                            </button>
                        </div>
                    </form>
                <?php else: ?>
                    <div class="flex flex-col items-center px-6 py-12 text-center">
                        <div class="flex h-12 w-12 items-center justify-center rounded-xl bg-emerald-50 text-emerald-600">
                            <svg class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8">
                                <path stroke-linecap="round" stroke-linejoin="round" d="m5 12 4 4L19 6"/>
                            </svg>
                        </div>
                        <h3 class="mt-3 text-sm font-bold text-slate-900">Không còn giáo viên để phân công</h3>
                        <p class="mt-1 max-w-md text-xs leading-5 text-slate-500">
                            Tất cả giáo viên đã được phân công hoặc chưa có giáo viên nào trong danh sách đề cử.
                        </p>
                    </div>
                <?php endif; ?>
            </section>
        </div>

<?php endif; ?>
    </main>

    <script>
        // Đổi địa điểm -> tải lại để hiện danh sách đã phân công
        document.getElementById('madiadiemthi').addEventListener('change', function () {
            document.getElementById('formDiaDiem').submit();
        });

        (function () {
            const form = document.getElementById('form-gac-thi'); // null khi không còn giáo viên để chọn

            const MIN = <?= (int)$soDuPhongToiThieu ?>;
            const daCo = <?= (int)$soDPDaCo ?>;

            const rows = form ? form.querySelectorAll('tr[data-row]') : [];
            const elCount = document.getElementById('dp-count');
            const elBar = document.getElementById('dp-bar');
            const elMsg = document.getElementById('dp-msg');
            const elChon = document.getElementById('chon-count');
            const btn = document.getElementById('btn-luu');
            if (!elCount) return; // chưa chọn địa điểm thì không có thanh trạng thái

            function update() {
                let chon = 0, dpChon = 0;
                rows.forEach(tr => {
                    const cb = tr.querySelector('input[type=checkbox]');
                    const sel = tr.querySelector('select');
                    if (cb.checked) {
                        chon++;
                        if (sel.value === 'DP') dpChon++;
                    }
                });

                const tong = daCo + dpChon;
                const du = tong >= MIN;

                elCount.textContent = tong + '/' + MIN;
                elBar.style.width = Math.min(100, tong / MIN * 100) + '%';
                elBar.className = 'h-full rounded-full transition-all duration-300 ' + (du ? 'bg-green-500' : 'bg-amber-500');
                elMsg.textContent = du
                    ? 'Đã đủ số cán bộ dự phòng tối thiểu.'
                    : 'Còn thiếu ' + (MIN - tong) + ' cán bộ dự phòng.';
                elMsg.className = 'mt-1.5 text-xs ' + (du ? 'text-green-600' : 'text-amber-600');

                if (form) {
                    elChon.textContent = 'Đã chọn ' + chon + ' giáo viên';
                    btn.disabled = !(chon > 0 && du);
                }
            }

            rows.forEach(tr => {
                tr.querySelector('input[type=checkbox]').addEventListener('change', update);
                tr.querySelector('select').addEventListener('change', update);
            });
            update();
        })();
    </script>
</body>
</html>