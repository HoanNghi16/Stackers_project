<?php
require_once __DIR__ . '/../../../class/dang-ky-nguyen-vong-Model.php';

$hocSinhModel = new DangKyNguyenVong($conn);

$manguoidung = 4; // Tạm thời dùng để kiểm thử

$thongBao = '';
$loi = '';


$hocSinh = $hocSinhModel->getById($manguoidung);

if (!$hocSinh) {
    $loi = 'Không tìm thấy hồ sơ học sinh.';
} elseif (
    $hocSinh['trangthaiduthi'] !== null
    && (int) $hocSinh['trangthaiduthi'] === 1
) {
    header('Location: dang-ky-nguyen-vong.php');
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && $hocSinh) {
    $trangthai = isset($_POST['trangthaiduthi'])
        ? filter_var($_POST['trangthaiduthi'], FILTER_VALIDATE_INT)
        : false;

    if ($trangthai === 0 || $trangthai === 1) {
        if ($hocSinhModel->updateTrangThaiDuThi(
            (int) $hocSinh['mahocsinh'],
            $trangthai
        )) {
            if ($trangthai === 1) {
                header('Location: dang-ky-nguyen-vong.php');
                exit;
            }

            $thongBao = 'Đã xác nhận không tham gia kỳ thi.';
        } else {
            $loi = 'Không thể cập nhật trạng thái. Vui lòng thử lại.';
        }
    } else {
        $loi = 'Vui lòng chọn Dự thi hoặc Không dự thi.';
    }
}

$hocSinh = $hocSinhModel->getById($manguoidung);
?>

<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Đăng ký dự thi tuyển sinh 10</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>

<body class="bg-slate-100 min-h-screen text-slate-800">

    <header class="bg-indigo-700 text-white">
        <div class="max-w-3xl mx-auto px-4 py-5">
            <p class="text-xs uppercase tracking-wide text-indigo-200">
                Cổng học sinh
            </p>
            <h1 class="text-xl font-bold">
                Đăng ký dự thi tuyển sinh 10
            </h1>
        </div>
    </header>

    <main class="max-w-3xl mx-auto px-4 py-6 space-y-4">

        <?php if (!$hocSinh): ?>

            <section class="bg-white p-5 shadow-sm">
                <p class="text-red-600">
                    Không tìm thấy hồ sơ hoặc thông tin học sinh tương ứng.
                </p>
            </section>

        <?php else: ?>

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

            <section class="bg-white shadow-sm p-5">
                <h2 class="font-semibold mb-4 pb-3 border-b">
                    Thông tin học sinh
                </h2>

                <div class="space-y-3">
                    <p>
                        <span class="text-slate-500">Mã hồ sơ:</span>
                        <?= $hocSinh['mahoso'] ?>
                    </p>

                    <p>
                        <span class="text-slate-500">Họ và tên:</span>
                        <?= $hocSinh['ho'] . ' ' . $hocSinh['ten'] ?>
                    </p>

                    <p>
                        <span class="text-slate-500">Ngày sinh:</span>
                        <?= date('d/m/Y', strtotime($hocSinh['ngaysinh'])) ?>
                    </p>

                    <p>
                        <span class="text-slate-500">Số báo danh:</span>
                        <?= $hocSinh['sobaodanh'] ?: 'Chưa có' ?>
                    </p>

                    <p>
                        <span class="text-slate-500">Trạng thái:</span>

                        <?php if ($hocSinh['trangthaiduthi'] === null): ?>
                            <span class="text-amber-600 font-medium">
                                Chưa đăng ký
                            </span>
                        <?php elseif ((int)$hocSinh['trangthaiduthi'] === 1): ?>
                            <span class="text-green-600 font-medium">
                                Dự thi
                            </span>
                        <?php else: ?>
                            <span class="text-red-600 font-medium">
                                Không dự thi
                            </span>
                        <?php endif; ?>
                    </p>
                </div>
            </section>

            <form
                method="POST"
                id="formDangKy"
                class="bg-white shadow-sm p-5 space-y-4"
            >
                <h2 class="font-semibold">
                    Bạn có đăng ký dự thi tuyển sinh 10 không?
                </h2>

                <label class="flex items-center gap-3 border p-4 rounded cursor-pointer hover:bg-indigo-50">
                    <input
                        type="radio"
                        name="trangthaiduthi"
                        value="1"
                        required
                        class="w-4 h-4"
                        <?= $hocSinh['trangthaiduthi'] !== null
                            && (int)$hocSinh['trangthaiduthi'] === 1
                            ? 'checked' : '' ?>
                    >
                    <span>
                        <span class="font-medium">Dự thi</span>
                        <span class="block text-sm text-slate-500">
                            Đăng ký tham gia kỳ thi tuyển sinh lớp 10.
                        </span>
                    </span>
                </label>

                <label class="flex items-center gap-3 border p-4 rounded cursor-pointer hover:bg-indigo-50">
                    <input
                        type="radio"
                        name="trangthaiduthi"
                        value="0"
                        required
                        class="w-4 h-4"
                        <?= $hocSinh['trangthaiduthi'] !== null
                            && (int)$hocSinh['trangthaiduthi'] === 0
                            ? 'checked' : '' ?>
                    >
                    <span>
                        <span class="font-medium">Không dự thi</span>
                        <span class="block text-sm text-slate-500">
                            Không đăng ký tham gia kỳ thi.
                        </span>
                    </span>
                </label>

                <div class="flex gap-3 pt-2">
                    <button
                        type="submit"
                        class="flex-1 bg-indigo-600 hover:bg-indigo-700 text-white font-medium py-2 rounded"
                    >
                        Xác nhận lựa chọn
                    </button>

                    <button
                        type="button"
                        onclick="window.location.reload()"
                        class="px-6 border rounded hover:bg-slate-50"
                    >
                        Hủy
                    </button>
                </div>
            </form>

            <script>
                document.getElementById('formDangKy')
                    .addEventListener('submit', function (event) {
                        const selected = document.querySelector(
                            'input[name="trangthaiduthi"]:checked'
                        );

                        if (!selected) {
                            return;
                        }

                        const message = selected.value === '1'
                            ? 'Bạn có chắc chắn muốn ĐĂNG KÝ DỰ THI không?'
                            : 'Bạn có chắc chắn muốn chọn KHÔNG DỰ THI không?';

                        if (!confirm(message)) {
                            event.preventDefault();
                        }
                    });
            </script>

        <?php endif; ?>

    </main>
</body>
</html>