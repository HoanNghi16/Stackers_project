<?php
require_once __DIR__ . '/../../../class/dang-ky-nguyen-vong-Model.php';

$dangKyNguyenVong = new DangKyNguyenVong($conn);

$manguoidung = 4;

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

$daLuu = [1 => '', 2 => '', 3 => ''];
for ($thuTu = 1; $thuTu <= 3; $thuTu++) {
    $daCo = $dangKyNguyenVong->kiemTraNguyenVong(
        (int) $hocSinh['mahocsinh'],
        $thuTu
    );
    $daLuu[$thuTu] = (string) ($daCo['matruong'] ?? '');
}
$daDangKy = $daLuu[1] !== '' || $daLuu[2] !== '' || $daLuu[3] !== '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
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

<body class="bg-slate-100 min-h-screen text-slate-800">

    <header class="bg-indigo-700 text-white">
        <div class="max-w-3xl mx-auto px-4 py-5">
            <p class="text-xs uppercase tracking-wide text-indigo-200">
                Cổng học sinh
            </p>

            <h1 class="text-xl font-bold">
                Đăng ký nguyện vọng tuyển sinh 10
            </h1>
        </div>
    </header>

    <main class="max-w-3xl mx-auto px-4 py-6 space-y-4">

        <?php if ($thongBao !== ''): ?>
            <div class="bg-green-100 text-green-800 p-4 rounded">
                <?= $thongBao ?>
            </div>
        <?php endif; ?>

        <?php if ($loi !== ''): ?>
            <div class="bg-red-100 text-red-700 p-4 rounded">
                <?= $loi ?>
            </div>
        <?php endif; ?>

        <!-- Thông tin học sinh -->
        <section class="bg-white shadow-sm p-5">
            <h2 class="font-semibold text-slate-800 mb-4 pb-3 border-b border-slate-100">
                Thông tin học sinh
            </h2>

            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">

                <label class="block sm:col-span-2">
                    <span class="text-sm text-slate-500">Họ và tên</span>

                    <input
                        type="text"
                        value="<?= $hocSinh['ho'] . ' ' . $hocSinh['ten'] ?>"
                        readonly
                        class="mt-1 w-full border border-slate-300 px-3 py-2 bg-slate-50"
                    >
                </label>

                <label class="block">
                    <span class="text-sm text-slate-500">Ngày sinh</span>

                    <input
                        type="text"
                        value="<?= !empty($hocSinh['ngaysinh'])
                            ? date('d/m/Y', strtotime($hocSinh['ngaysinh']))
                            : 'Chưa có' ?>"
                        readonly
                        class="mt-1 w-full border border-slate-300 px-3 py-2 bg-slate-50"
                    >
                </label>

                <label class="block">
                    <span class="text-sm text-slate-500">Số báo danh</span>

                    <input
                        type="text"
                        value="<?= $hocSinh['sobaodanh'] ?: 'Chưa có' ?>"
                        readonly
                        class="mt-1 w-full border border-slate-300 px-3 py-2 bg-slate-50"
                    >
                </label>

                <label class="block sm:col-span-2">
                    <span class="text-sm text-slate-500">Trường THCS</span>

                    <input
                        type="text"
                        value="<?= $hocSinh['truong_thcs'] ?: 'Chưa có' ?>"
                        readonly
                        class="mt-1 w-full border border-slate-300 px-3 py-2 bg-slate-50"
                    >
                </label>

            </div>
        </section>

        <!-- Form đăng ký nguyện vọng -->
        <form
            action=""
            method="POST"
            id="formNguyenVong"
            class="bg-white shadow-sm p-5 space-y-4"
        >

            <h2 class="font-semibold text-slate-800  border-slate-100">
                <?= $daDangKy ? 'Cập nhật nguyện vọng' : 'Chọn nguyện vọng' ?>
            </h2>

            <?php
            $cacNguyenVong = [
                'nguyenvong1' => [
                    'label' => 'Nguyện vọng 1',
                    'required' => true,
                    'placeholder' => '-- Chọn trường --',
                    'value' => $giaTriNV1,
                    'showPlaceholder' => $daLuu[1] === ''
                ],
                'nguyenvong2' => [
                    'label' => 'Nguyện vọng 2',
                    'required' => false,
                    'placeholder' => '-- Không đăng ký --',
                    'value' => $giaTriNV2,
                    'disabled' => $giaTriNV1 === ''
                ],
                'nguyenvong3' => [
                    'label' => 'Nguyện vọng 3',
                    'required' => false,
                    'placeholder' => '-- Không đăng ký --',
                    'value' => $giaTriNV3,
                    'disabled' => $giaTriNV2 === ''
                ]
            ];

            foreach ($cacNguyenVong as $name => $config):
            ?>

                <label class="block">
                    <span class="text-sm font-medium">
                        <?= $config['label'] ?>

                        <?php if ($config['required']): ?>
                            <span class="text-red-500">*</span>
                        <?php endif; ?>
                    </span>

                    <select
                        name="<?= $name ?>"
                        id="<?= $name ?>"
                        <?= $config['required'] ? 'required' : '' ?>
                        <?= !empty($config['disabled']) ? 'disabled' : '' ?>
                        class="mt-1 w-full border border-slate-300 px-3 py-2 bg-white disabled:bg-slate-100 disabled:text-slate-400 disabled:cursor-not-allowed"
                    >
                        <?php if (!isset($config['showPlaceholder']) || $config['showPlaceholder']): ?>
                            <option value="">
                                <?= $config['placeholder'] ?>
                            </option>
                        <?php endif; ?>

                        <?php foreach ($danhSachTruong as $truong): ?>
                            <option
                                value="<?= (int) $truong['matruong'] ?>"
                                data-ten="<?= $truong['tentruong'] ?>"
                                <?= (string) $config['value'] === (string) $truong['matruong']
                                    ? 'selected'
                                    : '' ?>
                            >
                                <?= $truong['tentruong'] ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </label>

            <?php endforeach; ?>

            <div class="flex flex-col sm:flex-row gap-3 pt-2">
                <button
                    type="submit"
                    class="flex-1 bg-indigo-600 hover:bg-indigo-700 text-white font-medium py-2.5 rounded"
                >
                    <?= $daDangKy ? 'Cập nhật nguyện vọng' : 'Xác nhận đăng ký' ?>
                </button>

                <button
                    type="button"
                    onclick="window.location.href='index.php'"
                    class="px-7 py-2.5 border border-slate-300 hover:bg-slate-50 rounded"
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

        cacOChon.forEach(select => {
            select.addEventListener('change', capNhatThuTuChon);
        });

        capNhatThuTuChon();

        document.getElementById('formNguyenVong')
            .addEventListener('submit', function (event) {
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