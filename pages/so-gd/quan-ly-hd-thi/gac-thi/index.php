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

    $soDuPhongToiThieu = 5;

    $madiadiemthi = $_POST['madiadiemthi'] ?? '';
    $dsChon       = $_POST['gv_chon'] ?? [];
    $vaiTro       = $_POST['vaitro'] ?? [];

    $thongBao  = '';
    $thanhCong = false;

    if (isset($_POST['luu_danh_sach'])) {
        $dsGiaoVien = [];

        foreach ($dsChon as $id) {
            $vt = $vaiTro[$id] ?? 'CB';
            $dsGiaoVien[$id] = isset($vaiTroGacThi[$vt]) ? $vt : 'CB';
        }

        $soDuPhong = count(
            array_filter($dsGiaoVien, fn($vt) => $vt === 'DP')
        );

        if ($madiadiemthi === '') {
            $thongBao = 'Vui lòng chọn địa điểm thi.';
        } elseif (empty($dsGiaoVien)) {
            $thongBao = 'Vui lòng chọn ít nhất một giáo viên.';
        } elseif ($soDuPhong < $soDuPhongToiThieu) {
            $thongBao = "Danh sách chưa hợp lệ: cần ít nhất $soDuPhongToiThieu giáo viên dự phòng (hiện có $soDuPhong).";
        } elseif ($ctrl->LuuHoiDongGacThi($madiadiemthi, $dsGiaoVien)) {
            $thanhCong = true;
            $thongBao  = 'Đã lưu danh sách hội đồng gác thi.';
            $dsChon = [];
            $vaiTro = [];
        } else {
            $thongBao = 'Không thể lưu danh sách, vui lòng thử lại.';
        }
    }

    $dsDiaDiem = $ctrl->GetDanhSachDiaDiemThi();
    $dsGiaoVienChuaPhanCong = $ctrl->GetGiaoVienDuocDeCu();

    // Đổi đường dẫn này thành trang xem hội đồng thực tế của mày.
    $trangXemHoiDong = 'xem-hoi-dong.php';
?>

<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>Stackers - Hội đồng gác thi</title>

    <script src="https://cdn.tailwindcss.com"></script>
</head>

<body class="min-h-screen bg-slate-50 text-slate-800">
    <?php
        renderHeader();
        renderSidebar();
    ?>

    <main class="mx-auto ml-72 pt-24 max-w-7xl space-y-7 px-10 py-8">

        <!-- TIÊU ĐỀ -->
        <section class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
            <div>
                <div class="mb-2 flex items-center gap-2 text-sm font-semibold text-blue-600">
                    <span class="h-2 w-2 rounded-full bg-blue-600"></span>
                    QUẢN LÝ KỲ THI
                </div>

                <h1 class="text-3xl font-bold tracking-tight text-slate-900">
                    Hội đồng gác thi
                </h1>

                <p class="mt-2 max-w-2xl text-sm leading-6 text-slate-500">
                    Phân công giáo viên gác thi theo địa điểm.
                    Trưởng hội đồng mặc định là hiệu trưởng của điểm thi.
                </p>
            </div>

            <div class="inline-flex w-fit items-center gap-2 rounded-xl border border-blue-100 bg-blue-50 px-4 py-3 text-sm font-medium text-blue-700">
                <svg xmlns="http://www.w3.org/2000/svg"
                    class="h-5 w-5"
                    fill="none"
                    viewBox="0 0 24 24"
                    stroke="currentColor"
                    stroke-width="1.8">
                    <path stroke-linecap="round" stroke-linejoin="round"
                        d="M12 6v6l4 2m5-2a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z" />
                </svg>
                Phân công gác thi
            </div>
        </section>

        <!-- THÔNG BÁO -->
        <?php if ($thongBao): ?>
            <div class="flex items-start gap-3 rounded-xl border px-4 py-4 text-sm
                <?= $thanhCong
                    ? 'border-emerald-200 bg-emerald-50 text-emerald-800'
                    : 'border-rose-200 bg-rose-50 text-rose-800' ?>">

                <?php if ($thanhCong): ?>
                    <svg xmlns="http://www.w3.org/2000/svg"
                        class="mt-0.5 h-5 w-5 shrink-0"
                        fill="none"
                        viewBox="0 0 24 24"
                        stroke="currentColor"
                        stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round"
                            d="m5 12 4 4L19 6" />
                    </svg>
                <?php else: ?>
                    <svg xmlns="http://www.w3.org/2000/svg"
                        class="mt-0.5 h-5 w-5 shrink-0"
                        fill="none"
                        viewBox="0 0 24 24"
                        stroke="currentColor"
                        stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round"
                            d="M12 9v4m0 4h.01M10.3 3.9 1.8 18.6A1.6 1.6 0 0 0 3.2 21h17.6a1.6 1.6 0 0 0 1.4-2.4L13.7 3.9a2 2 0 0 0-3.4 0Z" />
                    </svg>
                <?php endif; ?>

                <p class="leading-6">
                    <?= htmlspecialchars($thongBao) ?>
                </p>
            </div>
        <?php endif; ?>

        <!-- FORM PHÂN CÔNG -->
        <form method="POST" class="space-y-6">

            <!-- CHỌN ĐỊA ĐIỂM -->
            <section class="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm">
                <div class="border-b border-slate-100 px-6 py-5">
                    <h2 class="text-lg font-bold text-slate-900">
                        Địa điểm thi
                    </h2>
                    <p class="mt-1 text-sm text-slate-500">
                        Chọn địa điểm cần phân công hoặc xem hội đồng đã lưu.
                    </p>
                </div>

                <div class="flex flex-col gap-4 p-6 sm:flex-row sm:items-end">
                    <div class="w-full sm:max-w-md">
                        <label for="madiadiemthi"
                            class="mb-2 block text-sm font-semibold text-slate-700">
                            Chọn địa điểm
                        </label>

                        <select
                            id="madiadiemthi"
                            name="madiadiemthi"
                            class="w-full rounded-xl border border-slate-300 bg-white px-4 py-3 text-sm outline-none transition focus:border-blue-500 focus:ring-4 focus:ring-blue-100">

                            <option value="">-- Chọn địa điểm thi --</option>

                            <?php foreach ($dsDiaDiem as $dd): ?>
                                <option
                                    value="<?= htmlspecialchars($dd['madiadiemthi']) ?>"
                                    <?= (string)$madiadiemthi === (string)$dd['madiadiemthi']
                                        ? 'selected'
                                        : '' ?>>

                                    <?= htmlspecialchars($dd['tendiadiemthi']) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <button
                        type="button"
                        id="btnXemHoiDong"
                        disabled
                        class="inline-flex items-center justify-center gap-2 rounded-xl border border-blue-200 bg-blue-100 px-5 py-3 text-sm font-semibold text-slate-400 transition hover:border-blue-200 hover:bg-blue-50 hover:text-blue-700 disabled:cursor-not-allowed disabled:opacity-50">

                        <svg xmlns="http://www.w3.org/2000/svg"
                            class="h-5 w-5"
                            fill="none"
                            viewBox="0 0 24 24"
                            stroke="currentColor"
                            stroke-width="1.8">
                            <path stroke-linecap="round" stroke-linejoin="round"
                                d="M2.25 12s3.5-6.75 9.75-6.75S21.75 12 21.75 12 18.25 18.75 12 18.75 2.25 12 2.25 12Z" />
                            <circle cx="12" cy="12" r="3" />
                        </svg>

                        Xem hội đồng thi
                    </button>
                </div>
            </section>

            <!-- DANH SÁCH GIÁO VIÊN -->
            <section class="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm">

                <div class="flex flex-col gap-3 border-b border-slate-100 px-6 py-5 sm:flex-row sm:items-center sm:justify-between">
                    <div>
                        <h2 class="text-lg font-bold text-slate-900">
                            Danh sách giáo viên
                        </h2>
                        <p class="mt-1 text-sm text-slate-500">
                            Chọn giáo viên và phân loại vai trò trong hội đồng gác thi.
                        </p>
                    </div>

                    <span class="inline-flex w-fit items-center rounded-full bg-slate-100 px-3 py-1.5 text-xs font-semibold text-slate-600">
                        <?= count($dsGiaoVienChuaPhanCong) ?> giáo viên
                    </span>
                </div>

                <?php if (!empty($dsGiaoVienChuaPhanCong)): ?>

                    <div class="overflow-x-auto">
                        <table class="min-w-full divide-y divide-slate-200">
                            <thead class="bg-slate-50">
                                <tr class="text-left text-xs font-bold uppercase tracking-wider text-slate-500">
                                    <th class="w-20 px-6 py-4 text-center">Chọn</th>
                                    <th class="px-6 py-4">Mã giáo viên</th>
                                    <th class="px-6 py-4">Tên giáo viên</th>
                                    <th class="px-6 py-4">Cấp giảng dạy</th>
                                    <th class="px-6 py-4">Môn giảng dạy</th>
                                    <th class="min-w-56 px-6 py-4">Phân công</th>
                                </tr>
                            </thead>

                            <tbody class="divide-y divide-slate-100">
                                <?php foreach ($dsGiaoVienChuaPhanCong as $gv): ?>
                                    <?php $id = $gv['magiaovien']; ?>

                                    <tr class="transition hover:bg-blue-50/40">
                                        <td class="px-6 py-4 text-center">
                                            <input
                                                type="checkbox"
                                                name="gv_chon[]"
                                                value="<?= htmlspecialchars($id) ?>"
                                                <?= in_array($id, $dsChon) ? 'checked' : '' ?>
                                                class="h-4 w-4 cursor-pointer rounded border-slate-300 text-blue-600 focus:ring-2 focus:ring-blue-500 focus:ring-offset-1">
                                        </td>

                                        <td class="whitespace-nowrap px-6 py-4">
                                            <span class="rounded-md bg-slate-100 px-2.5 py-1 font-mono text-xs font-semibold text-slate-700">
                                                <?= htmlspecialchars($id) ?>
                                            </span>
                                        </td>

                                        <td class="whitespace-nowrap px-6 py-4">
                                            <div class="font-semibold text-slate-800">
                                                <?= htmlspecialchars($gv['tengiaovien']) ?>
                                            </div>
                                        </td>

                                        <td class="whitespace-nowrap px-6 py-4 text-sm text-slate-600">
                                            <?= htmlspecialchars($gv['capgiangday']) ?>
                                        </td>
                                        <td class="whitespace-nowrap px-6 py-4 text-sm text-slate-600">
                                            <?= htmlspecialchars($gv['tenmonhoc']) ?>
                                        </td>

                                        <td class="px-6 py-4">
                                            <select
                                                name="vaitro[<?= htmlspecialchars($id) ?>]"
                                                class="w-full rounded-lg border border-slate-200 bg-white px-3 py-2.5 text-sm text-slate-700 outline-none transition focus:border-blue-500 focus:ring-4 focus:ring-blue-100">

                                                <?php foreach ($vaiTroGacThi as $value => $label): ?>
                                                    <option
                                                        value="<?= htmlspecialchars($value) ?>"
                                                        <?= ($vaiTro[$id] ?? 'CB') === $value
                                                            ? 'selected'
                                                            : '' ?>>

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

                    <!-- CHÂN BẢNG -->
                    <div class="flex flex-col gap-4 border-t border-slate-100 bg-slate-50 px-6 py-5 sm:flex-row sm:items-center sm:justify-between">
                        <div class="flex items-start gap-3">
                            <div class="flex h-10 w-10 shrink-0 items-center justify-center rounded-xl bg-amber-100 text-amber-700">
                                <svg xmlns="http://www.w3.org/2000/svg"
                                    class="h-5 w-5"
                                    fill="none"
                                    viewBox="0 0 24 24"
                                    stroke="currentColor"
                                    stroke-width="1.8">
                                    <path stroke-linecap="round" stroke-linejoin="round"
                                        d="M12 9v3m0 4h.01M10.3 3.9 1.8 18.6A1.6 1.6 0 0 0 3.2 21h17.6a1.6 1.6 0 0 0 1.4-2.4L13.7 3.9a2 2 0 0 0-3.4 0Z" />
                                </svg>
                            </div>

                            <div>
                                <p class="text-sm font-semibold text-slate-800">
                                    Yêu cầu phân công
                                </p>
                                <p class="mt-1 text-sm leading-5 text-slate-500">
                                    Cần tối thiểu
                                    <span class="font-bold text-amber-700">
                                        <?= $soDuPhongToiThieu ?> giáo viên dự phòng
                                    </span>
                                    trong danh sách được chọn.
                                </p>
                            </div>
                        </div>

                        <button
                            type="submit"
                            name="luu_danh_sach"
                            class="inline-flex items-center justify-center gap-2 rounded-xl bg-blue-600 px-6 py-3 text-sm font-bold text-white shadow-sm transition hover:bg-blue-700 focus:outline-none focus:ring-4 focus:ring-blue-200">

                            <svg xmlns="http://www.w3.org/2000/svg"
                                class="h-5 w-5"
                                fill="none"
                                viewBox="0 0 24 24"
                                stroke="currentColor"
                                stroke-width="1.8">
                                <path stroke-linecap="round" stroke-linejoin="round"
                                    d="M5 13l4 4L19 7" />
                            </svg>

                            Lưu danh sách
                        </button>
                    </div>

                <?php else: ?>

                    <div class="flex flex-col items-center px-6 py-16 text-center">
                        <div class="flex h-16 w-16 items-center justify-center rounded-2xl bg-emerald-50 text-emerald-600">
                            <svg xmlns="http://www.w3.org/2000/svg"
                                class="h-8 w-8"
                                fill="none"
                                viewBox="0 0 24 24"
                                stroke="currentColor"
                                stroke-width="1.8">
                                <path stroke-linecap="round" stroke-linejoin="round"
                                    d="m5 12 4 4L19 6" />
                            </svg>
                        </div>

                        <h3 class="mt-4 text-base font-bold text-slate-900">
                            Không còn giáo viên để phân công
                        </h3>

                        <p class="mt-2 max-w-md text-sm leading-6 text-slate-500">
                            Tất cả giáo viên hiện đã được phân công hoặc chưa có giáo viên nào trong danh sách đề cử.
                        </p>
                    </div>

                <?php endif; ?>
            </section>
        </form>
    </main>

    <script>
        const selectDiaDiem = document.getElementById('madiadiemthi');
        const btnXemHoiDong = document.getElementById('btnXemHoiDong');

        function capNhatNutXem() {
            btnXemHoiDong.disabled = !selectDiaDiem.value;
        }

        selectDiaDiem.addEventListener('change', capNhatNutXem);

        btnXemHoiDong.addEventListener('click', function () {
            const maDiaDiem = selectDiaDiem.value;

            if (!maDiaDiem) return;

            const url = new URL(
                <?= json_encode($trangXemHoiDong) ?>,
                window.location.href
            );

            url.searchParams.set('madiadiemthi', maDiaDiem);
            window.location.href = url.toString();
        });

        capNhatNutXem();
    </script>
</body>
</html>
