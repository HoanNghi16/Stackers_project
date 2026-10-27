<?php
session_start();

if (
    ($_SESSION['vaitro'] ?? '') !== 'HOC_SINH'
    || empty($_SESSION['manguoidung'])
) {
    header('Location: /stackers_project/pages/login/');
    exit;
}

require_once __DIR__ . '/../../../class/dang-ky-nguyen-vong-Model.php';

$dangKyNguyenVong = new DangKyNguyenVong($conn);

$manguoidung = (int) $_SESSION['manguoidung'];

$thongBao = '';
$loi = '';

$hocSinh = $dangKyNguyenVong->getById($manguoidung);

if (
    !$hocSinh ||
    (int) $hocSinh['trangthaiduthi'] !== 1
) {
    header('Location: index.php');
    exit;
}

$danhSachTruong = $dangKyNguyenVong->getDanhSachTruong();

$hanDangKy = $dangKyNguyenVong->layHanDangKyNguyenVong($hocSinh['makythi']);
$hetHan = $hanDangKy !== null && date('Y-m-d') > $hanDangKy;

$daLuu = [1 => '', 2 => '', 3 => ''];
for ($thuTu = 1; $thuTu <= 3; $thuTu++) {
    $daCo = $dangKyNguyenVong->kiemTraNguyenVong(
        (int) $hocSinh['mahocsinh'],
        $thuTu
    );
    $daLuu[$thuTu] = (string) ($daCo['matruong'] ?? '');
}
$daDangKy = $daLuu[1] !== '' || $daLuu[2] !== '' || $daLuu[3] !== '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && $hetHan) {
    $loi = 'Đã hết hạn đăng ký nguyện vọng'
        . ($hanDangKy ? ' vào ngày ' . date('d/m/Y', strtotime($hanDangKy)) : '')
        . '. Bạn không thể đăng ký hoặc chỉnh sửa nguyện vọng.';
} elseif ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $nguyenVong1 = filter_input(
        INPUT_POST,
        'nguyenvong1',
        FILTER_VALIDATE_INT
    );

    $nguyenVong2 = filter_input(
        INPUT_POST,
        'nguyenvong2',
        FILTER_VALIDATE_INT
    );

    $nguyenVong3 = filter_input(
        INPUT_POST,
        'nguyenvong3',
        FILTER_VALIDATE_INT
    );

    $nguyenVong2 = ($nguyenVong2 === null || $nguyenVong2 === false)
        ? 0
        : $nguyenVong2;

    $nguyenVong3 = ($nguyenVong3 === null || $nguyenVong3 === false)
        ? 0
        : $nguyenVong3;

    if ($nguyenVong2 <= 0 && $nguyenVong3 > 0) {
        $nguyenVong2 = $nguyenVong3;
        $nguyenVong3 = 0;
    }

    if (
        $nguyenVong1 === false ||
        $nguyenVong1 === null ||
        $nguyenVong1 <= 0
    ) {
        $loi = 'Vui lòng chọn trường cho nguyện vọng 1.';
    } elseif (
        $nguyenVong2 < 0 ||
        $nguyenVong3 < 0
    ) {
        $loi = 'Mã trường không hợp lệ.';
    } else {
        $cacMaTruong = array_values(array_filter(
            [$nguyenVong1, $nguyenVong2, $nguyenVong3],
            fn($maTruong) => $maTruong > 0
        ));

        if (count($cacMaTruong) !== count(array_unique($cacMaTruong))) {
            $loi = 'Bạn không được chọn trùng trường ở các nguyện vọng.';
        } else {
            $maTruongHopLe = array_map(
                'intval',
                array_column($danhSachTruong, 'matruong')
            );

            foreach ($cacMaTruong as $maTruong) {
                if (!in_array($maTruong, $maTruongHopLe, true)) {
                    $loi = 'Có trường được chọn không hợp lệ. Vui lòng chọn lại.';
                    break;
                }
            }

            if ($loi === '') {
                $moi = [
                    1 => $nguyenVong1 > 0 ? (string) $nguyenVong1 : '',
                    2 => $nguyenVong2 > 0 ? (string) $nguyenVong2 : '',
                    3 => $nguyenVong3 > 0 ? (string) $nguyenVong3 : '',
                ];

                if ($moi === [
                    1 => (string) $daLuu[1],
                    2 => (string) $daLuu[2],
                    3 => (string) $daLuu[3],
                ]) {
                    $loi = 'Nguyện vọng chưa có thay đổi.';
                } else {
                $danhSachDangKy = [
                    1 => $nguyenVong1,
                    2 => $nguyenVong2,
                    3 => $nguyenVong3,
                ];
                $maHocSinh = (int) $hocSinh['mahocsinh'];
                $conn->begin_transaction();
                $luuOk = true;

                foreach ($danhSachDangKy as $thuTu => $maTruong) {
                    $daCo = $dangKyNguyenVong->kiemTraNguyenVong($maHocSinh, $thuTu);

                    if ($maTruong > 0) {
                        if ($daCo) {
                            $luuOk = $dangKyNguyenVong->capNhatNguyenVong(
                                (int) $daCo['manguyenvong'],
                                $maTruong
                            );
                        } else {
                            $luuOk = $dangKyNguyenVong->themNguyenVong(
                                $maHocSinh,
                                $thuTu,
                                $maTruong
                            );
                        }
                    } elseif ($daCo) {
                        $luuOk = $dangKyNguyenVong->xoaNguyenVong(
                            (int) $daCo['manguyenvong']
                        );
                    }

                    if (!$luuOk) {
                        break;
                    }
                }

                if ($luuOk) {
                    $conn->commit();
                    $thongBao = $daDangKy
                        ? 'Cập nhật nguyện vọng thành công.'
                        : 'Đăng ký nguyện vọng thành công.';
                    $daDangKy = true;
                    $daLuu = $moi;
                } else {
                    $conn->rollback();
                    $loi = 'Không thể lưu nguyện vọng. Vui lòng thử lại.';
                }
                }
            }
        }
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $giaTriNV1 = $_POST['nguyenvong1'] ?? '';
    $giaTriNV2 = $_POST['nguyenvong2'] ?? '';
    $giaTriNV3 = $_POST['nguyenvong3'] ?? '';
} else {
    $giaTriNV1 = $daLuu[1];
    $giaTriNV2 = $daLuu[2];
    $giaTriNV3 = $daLuu[3];
}

if ($daLuu[1] !== '') {
    $giaTriNV1 = $daLuu[1];
}
?>

<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>Đăng ký nguyện vọng tuyển sinh 10</title>

    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-slate-100 text-slate-800">
    <?php
        require "../../../components/layout/header.php";
        renderHeader();
    ?>

    <main class="mx-auto max-w-3xl px-4 pb-12 pt-28">
    <div class="relative mb-7 overflow-hidden rounded-2xl border border-slate-200/80 bg-gradient-to-br from-white via-white to-indigo-50/70 p-5 shadow-sm sm:p-7">

<!-- Decorative background -->
<div class="pointer-events-none absolute -right-10 -top-10 h-36 w-36 rounded-full bg-indigo-100/60 blur-2xl"></div>
<div class="pointer-events-none absolute -bottom-12 right-1/4 h-24 w-24 rounded-full bg-cyan-100/50 blur-2xl"></div>

<div class="relative">
    <!-- Badge -->
    <div class="mb-4 inline-flex items-center gap-2 rounded-full border border-indigo-100 bg-white/90 px-3.5 py-1.5 shadow-sm">
        <span class="relative flex h-2 w-2">
            <span class="absolute inline-flex h-full w-full animate-ping rounded-full bg-emerald-400 opacity-50"></span>
            <span class="relative inline-flex h-2 w-2 rounded-full bg-emerald-500"></span>
        </span>
        <span class="text-[11px] font-bold uppercase tracking-[0.16em] text-indigo-700">
            Cổng thông tin học sinh
        </span>
    </div>

    <!-- Heading -->
    <div class="flex items-start gap-3 sm:gap-4">
        <div class="hidden h-12 w-1 shrink-0 rounded-full bg-gradient-to-b from-indigo-500 to-cyan-400 sm:block"></div>

        <div class="min-w-0">
            <h1 class="text-2xl font-extrabold leading-tight tracking-tight text-slate-900 sm:text-3xl lg:text-4xl">
                Đăng ký nguyện vọng
                <span class="mt-1 block ml-10 bg-gradient-to-r from-indigo-600 to-violet-600 bg-clip-text text-transparent">
                    Tuyển sinh lớp 10
                </span>
            </h1>
        </div>
    </div>


</div>
</div>

        <?php if ($thongBao !== ''): ?>
            <div class="mb-4 rounded-xl border border-green-200 bg-green-50 px-4 py-3 text-sm text-green-800">
                <?= htmlspecialchars($thongBao) ?>
            </div>
        <?php endif; ?>

        <?php if ($hanDangKy): ?>
            <?php if ($hetHan): ?>
                <div class="mb-4 rounded-xl border border-amber-200 bg-amber-50 px-4 py-3 text-sm text-amber-800">
                    Đã hết hạn đăng ký nguyện vọng vào ngày
                    <?= htmlspecialchars(date('d/m/Y', strtotime($hanDangKy))) ?>.
                    Bạn không thể đăng ký hoặc chỉnh sửa nguyện vọng.
                </div>
            <?php else: ?>
                <div class="mb-4 rounded-xl border border-blue-200 bg-blue-50 px-4 py-3 text-sm text-blue-800">
                    Thời gian đăng ký nguyện vọng đến hết ngày
                    <?= htmlspecialchars(date('d/m/Y', strtotime($hanDangKy))) ?>.
                </div>
            <?php endif; ?>
        <?php endif; ?>

        <?php if ($loi !== '' && !$hetHan): ?>
            <div class="mb-4 rounded-xl border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-700">
                <?= htmlspecialchars($loi) ?>
            </div>
        <?php endif; ?>

        <section class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm">
            <h2 class="border-b border-slate-100 pb-3 text-base font-semibold text-slate-900">
                Thông tin học sinh
            </h2>

            <dl class="mt-4 grid grid-cols-1 gap-4 sm:grid-cols-2">
                <div class="sm:col-span-2">
                    <dt class="text-xs font-medium uppercase tracking-wide text-slate-400">Họ và tên</dt>
                    <dd class="mt-1 text-sm font-medium text-slate-800">
                        <?= htmlspecialchars($hocSinh['ho'] . ' ' . $hocSinh['ten']) ?>
                    </dd>
                </div>
                <div>
                    <dt class="text-xs font-medium uppercase tracking-wide text-slate-400">Ngày sinh</dt>
                    <dd class="mt-1 text-sm text-slate-800">
                        <?= !empty($hocSinh['ngaysinh'])
                            ? htmlspecialchars(date('d/m/Y', strtotime($hocSinh['ngaysinh'])))
                            : 'Chưa có' ?>
                    </dd>
                </div>
                <div>
                    <dt class="text-xs font-medium uppercase tracking-wide text-slate-400">Số báo danh</dt>
                    <dd class="mt-1 text-sm text-slate-800">
                        <?= htmlspecialchars($hocSinh['sobaodanh'] ?: 'Chưa có') ?>
                    </dd>
                </div>
                <div class="sm:col-span-2">
                    <dt class="text-xs font-medium uppercase tracking-wide text-slate-400">Trường THCS</dt>
                    <dd class="mt-1 text-sm text-slate-800">
                        <?= htmlspecialchars($hocSinh['truong_thcs'] ?: 'Chưa có') ?>
                    </dd>
                </div>
            </dl>
        </section>

        <form
            action=""
            method="POST"
            id="formNguyenVong"
            class="mt-4 space-y-4 rounded-2xl border border-slate-200 bg-white p-5 shadow-sm"
        >
            <div class="border-b border-slate-100 pb-3">
                <h2 class="text-base font-semibold text-slate-900">
                    <?php if ($hetHan): ?>
                        Nguyện vọng đã đăng ký
                    <?php else: ?>
                        <?= $daDangKy ? 'Cập nhật nguyện vọng' : 'Chọn nguyện vọng' ?>
                    <?php endif; ?>
                </h2>
            </div>

            <?php
            $cacNguyenVong = [
                'nguyenvong1' => [
                    'label' => 'Nguyện vọng 1',
                    // 'hint' => 'Bắt buộc',
                    'required' => true,
                    'placeholder' => '-- Chọn trường --',
                    'value' => $giaTriNV1,
                    'showPlaceholder' => $daLuu[1] === ''
                ],
                'nguyenvong2' => [
                    'label' => 'Nguyện vọng 2',
                    // 'hint' => 'Không bắt buộc',
                    'required' => false,
                    'placeholder' => '-- Không đăng ký --',
                    'value' => $giaTriNV2,
                    'disabled' => $giaTriNV1 === ''
                ],
                'nguyenvong3' => [
                    'label' => 'Nguyện vọng 3',
                    // 'hint' => 'Không bắt buộc',
                    'required' => false,
                    'placeholder' => '-- Không đăng ký --',
                    'value' => $giaTriNV3,
                    'disabled' => $giaTriNV2 === ''
                ]
            ];

            $soThuTu = 1;
            foreach ($cacNguyenVong as $name => $config):
            ?>
                <label class="block rounded-xl border border-slate-200 bg-slate-50 p-4">
                    <span class="flex items-center justify-between gap-3">
                        <span class="flex items-center gap-2 text-sm font-semibold text-slate-800">
                            <!-- <span class="flex h-6 w-6 items-center justify-center rounded-full bg-blue-700 text-xs text-white">
                                <?= $soThuTu ?>
                            </span> -->
                            <?= $config['label'] ?>
                            <?php if ($config['required']): ?>
                                <span class="text-red-500">*</span>
                            <?php endif; ?>
                        </span>
                        <!-- <span class="text-xs text-slate-400"><?= $config['hint'] ?></span> -->
                    </span>

                    <select
                        name="<?= $name ?>"
                        id="<?= $name ?>"
                        <?= $config['required'] && !$hetHan ? 'required' : '' ?>
                        <?= ($hetHan || !empty($config['disabled'])) ? 'disabled' : '' ?>
                        class="mt-3 w-full rounded-lg border border-slate-300 bg-white px-3 py-2.5 text-sm focus:border-blue-600 focus:outline-none focus:ring-2 focus:ring-blue-600/20 disabled:cursor-not-allowed disabled:bg-slate-100 disabled:text-slate-400"
                    >
                        <?php if (!isset($config['showPlaceholder']) || $config['showPlaceholder']): ?>
                            <option value="">
                                <?= $config['placeholder'] ?>
                            </option>
                        <?php endif; ?>

                        <?php foreach ($danhSachTruong as $truong): ?>
                            <option
                                value="<?= (int) $truong['matruong'] ?>"
                                data-ten="<?= htmlspecialchars($truong['tentruong']) ?>"
                                <?= (string) $config['value'] === (string) $truong['matruong']
                                    ? 'selected'
                                    : '' ?>
                            >
                                <?= htmlspecialchars($truong['tentruong']) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </label>
            <?php
                $soThuTu++;
            endforeach;
            ?>

            <div class="flex flex-col gap-3 pt-1 sm:flex-row">
                <?php if (!$hetHan): ?>
                <button
                    type="submit"
                    class="flex-1 rounded-xl bg-blue-700 py-2.5 text-sm font-semibold text-white hover:bg-blue-800"
                >
                    <?= $daDangKy ? 'Cập nhật nguyện vọng' : 'Xác nhận đăng ký' ?>
                </button>
                <?php endif; ?>

                <button
                    type="button"
                    onclick="window.location.href='../index.php'"
                    class="rounded-xl border border-slate-300 px-7 py-2.5 text-sm font-medium text-slate-700 hover:bg-slate-50"
                >
                    Hủy
                </button>
            </div>
        </form>
    </main>

    <script>
        const cacOChon = [
            document.getElementById('nguyenvong1'),
            document.getElementById('nguyenvong2'),
            document.getElementById('nguyenvong3')
        ];

        const nguyenVongBanDau = <?= json_encode([
            (string) $daLuu[1],
            (string) $daLuu[2],
            (string) $daLuu[3],
        ], JSON_UNESCAPED_UNICODE) ?>;
        const daDangKy = <?= $daDangKy ? 'true' : 'false' ?>;
        const hetHan = <?= $hetHan ? 'true' : 'false' ?>;

        function capNhatDanhSachTruong() {
            const thuTuDaChon = {};
            cacOChon.forEach((select, index) => {
                if (select.value !== '') {
                    thuTuDaChon[select.value] = index + 1;
                }

                select.style.color = '';
                select.style.backgroundColor = '#ffffff';
                select.style.borderColor = '';
                select.style.fontWeight = '';
            });

            cacOChon.forEach(select => {
                Array.from(select.options).forEach(option => {
                    if (option.value === '') {
                        option.disabled = false;
                        option.style.color = '#0f172a';
                        option.style.backgroundColor = '#ffffff';
                        option.style.fontWeight = '';
                        return;
                    }

                    const ten = option.dataset.ten;
                    const thuTu = thuTuDaChon[option.value];
                    const dangChonONay = select.value === option.value;
                    const daDuocChon = Boolean(thuTu);

                    option.textContent = daDuocChon && !dangChonONay
                        ? 'NV' + thuTu + ' · ' + ten
                        : ten;
                    option.style.color = daDuocChon ? '#dc2626' : '#0f172a';
                    option.style.backgroundColor = daDuocChon ? '#fee2e2' : '#ffffff';
                    option.style.fontWeight = daDuocChon ? '700' : '';
                    option.disabled = daDuocChon && !dangChonONay;
                });
            });
        }

        function capNhatThuTuChon() {
            const [nv1, nv2, nv3] = cacOChon;

            if (nv2.value === '' && nv3.value !== '') {
                nv2.value = nv3.value;
                nv3.value = '';
            }

            nv2.disabled = nv1.value === '';
            nv3.disabled = nv2.value === '';

            capNhatDanhSachTruong();
        }

        if (hetHan) {
            cacOChon.forEach(select => {
                select.disabled = true;
            });
            capNhatDanhSachTruong();
        } else {
            cacOChon.forEach(select => {
                select.addEventListener('change', capNhatThuTuChon);
            });

            capNhatThuTuChon();
        }

        document.getElementById('formNguyenVong')
            .addEventListener('submit', function (event) {
                if (hetHan) {
                    event.preventDefault();
                    alert('Đã hết hạn đăng ký nguyện vọng.');
                    return;
                }

                if (cacOChon[0].dataset.khoa === '1') {
                    cacOChon[0].value = nguyenVongBanDau[0];
                }

                const hienTai = cacOChon.map(select => select.value);
                const khongDoi = nguyenVongBanDau.every(
                    (giaTri, viTri) => String(giaTri) === String(hienTai[viTri])
                );

                if (khongDoi) {
                    event.preventDefault();
                    alert('Nguyện vọng chưa có thay đổi.');
                    return;
                }

                const cauHoi = daDangKy
                    ? 'Bạn có chắc chắn muốn cập nhật nguyện vọng không?'
                    : 'Bạn có chắc chắn muốn xác nhận đăng ký nguyện vọng không?';

                if (!confirm(cauHoi)) {
                    event.preventDefault();
                }
            });
    </script>

</body>
</html>