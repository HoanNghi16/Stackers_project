<?php
use NhapDiemPhucKhao\CtrlNhapDiemPhucKhao;

if (session_status() !== PHP_SESSION_ACTIVE) {
    session_start();
}

require_once __DIR__ . '/../../../index.php';
require_once __DIR__ . '/../../../class/nhap-diem-phuc-khao/ctrlNhapDiemPhucKhao.php';
require_once __DIR__ . '/../../../components/layout/header.php';
require_once __DIR__ . '/../../../components/layout/sidebar.php';

// Kiểm tra quyền Sở GD&ĐT (giống trang Xét tuyển)
if (($env['DEV_FAKE_LOGIN'] ?? '') === '1' && empty($_SESSION['vaitro'])) {
    $_SESSION['vaitro'] = 'SO_GD';
}
if (($_SESSION['vaitro'] ?? '') !== 'SO_GD') {
    header('Location: /stackers_project/pages/login');
    exit;
}

function hPK($value): string
{
    return htmlspecialchars((string)$value, ENT_QUOTES, 'UTF-8');
}

function diemPK($value): string
{
    return $value === null || $value === '' ? '—' : number_format((float)$value, 2, '.', '');
}

function trangThaiPK(string $tt): array
{
    return match ($tt) {
        'HOAN_THANH'     => ['Đã cập nhật kết quả', 'bg-green-100 text-green-700'],
        'DANG_PHUC_KHAO' => ['Đang phúc khảo', 'bg-blue-100 text-blue-700'],
        'TU_CHOI'        => ['Từ chối', 'bg-red-100 text-red-700'],
        'CHO_XU_LY'   => ['Chờ xử lý', 'bg-amber-100 text-amber-700'],
        default       => [$tt, 'bg-slate-100 text-slate-700'],
    };
}

if (empty($_SESSION['csrf_phuckhao'])) {
    $_SESSION['csrf_phuckhao'] = bin2hex(random_bytes(32));
}

$ctrl = new CtrlNhapDiemPhucKhao($conn);

$thongBao = '';
$popup = null;
$hienXacNhan = false;
$doiChieu = [];
$loiDiem = [];
$diemDaGo = [];

$form = $ctrl->yeuCauForm();
$ky = $form['ky'];
$dsPhieu = $form['dsPhieu'];
$thongBao = $form['thongBao'];
$maKy = $ky ? (int)$ky['makythi'] : 0;

// Phiếu đang chọn (bước 5)
$maPhieu = (int)($_GET['phieu'] ?? 0);
$ct = $maPhieu > 0 && $ky ? $ctrl->chonPhieu($maKy, $maPhieu) : null;
if ($ct && !$ct['thanhCong']) {
    $thongBao = $ct['thongBao'];
    $ct = null;
}

$khoaNhap = !empty($form['khoaNhap']); // đã xác nhận xét tuyển -> chỉ xem

if ($ct && !$ct['daCapNhat'] && !$khoaNhap && $_SERVER['REQUEST_METHOD'] === 'POST') {
    $csrf = (string)($_POST['csrf'] ?? '');
    $nhap = is_array($_POST['diem'] ?? null) ? $_POST['diem'] : [];

    if (!hash_equals($_SESSION['csrf_phuckhao'], $csrf)) {
        $thongBao = 'Phiên làm việc không hợp lệ. Vui lòng tải lại trang.';
    } else {
        $hanhDong = (string)($_POST['hanhDong'] ?? '');

        if ($hanhDong === 'kiemTra') {
            // Bước 7-10
            $diemDaGo = $nhap;
            $kt = $ctrl->kiemTraDiem($maKy, $maPhieu, $nhap);
            if ($kt['loi']) {
                $loiDiem = $kt['loi'];
                $thongBao = 'Điểm phúc khảo không hợp lệ, vui lòng kiểm tra lại.';
            } elseif (!$kt['doiChieu']) {
                $thongBao = 'Chưa nhập điểm phúc khảo cho môn nào.';
            } else {
                $doiChieu = $kt['doiChieu'];
                $hienXacNhan = true;
            }
        } elseif ($hanhDong === 'luu') {
            // Bước 11-16
            $kq = $ctrl->luuDiem($maKy, $maPhieu, $nhap, isset($_SESSION['manguoidung']) ? (int)$_SESSION['manguoidung'] : null);
            $popup = [
                'thanhCong' => $kq['thanhCong'],
                'tieuDe'    => $kq['thanhCong'] ? 'Lưu điểm phúc khảo thành công' : 'Không thể lưu điểm phúc khảo',
                'noiDung'   => $kq['thongBao'],
            ];
            if ($kq['thanhCong']) {
                // Tải lại để thấy điểm + trạng thái mới
                $form = $ctrl->yeuCauForm();
                $dsPhieu = $form['dsPhieu'];
                $thongBao = $form['thongBao'];
                $ct = $ctrl->chonPhieu($maKy, $maPhieu);
            } else {
                $diemDaGo = $nhap;
            }
        }
    }
}
?>
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Stackers - Nhập điểm phúc khảo</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-slate-50 text-slate-800">

<?php renderHeader(); renderSidebar(); ?>

<main class="pl-72 pt-20">
<div class="mx-auto max-w-7xl px-6 py-8 space-y-6">

    <!-- TIÊU ĐỀ -->
    <div>
        <p class="mb-2 text-sm text-slate-500">Sở GD&amp;ĐT / Quản lý điểm / Nhập điểm phúc khảo</p>
        <h1 class="text-2xl font-bold">Nhập điểm phúc khảo</h1>
        <p class="mt-1 text-sm text-slate-500">Cập nhật điểm thi của thí sinh theo kết quả chấm phúc khảo.</p>
    </div>

    <?php if ($thongBao): ?>
        <div class="rounded-xl whitespace-pre-line border border-amber-300 bg-amber-50 px-5 py-4 text-sm text-amber-900">
            <?= hPK($thongBao) ?>
        </div>
    <?php endif; ?>

    <?php if ($ky): ?>
    <!-- KỲ THI (CỐ ĐỊNH 2026) -->
    <section class="rounded-2xl border border-slate-200 bg-white shadow-sm p-5">
        <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
            <label class="text-sm">
                Kỳ tuyển sinh
                <input type="text" value="<?= hPK($ky['namtuyensinh']) ?>" readonly
                       class="mt-1 block w-full rounded-lg border border-slate-300 bg-slate-50 px-3 py-2 text-slate-700 cursor-default">
            </label>
        </div>
    </section>

    <!-- BƯỚC 4: DANH SÁCH HỒ SƠ PHÚC KHẢO -->
    <section class="rounded-2xl border border-slate-200 bg-white shadow-sm overflow-hidden">
        <div class="flex items-center justify-between border-b border-slate-200 px-5 py-3">
            <h2 class="font-semibold">Hồ sơ phúc khảo</h2>
            <span class="text-xs text-slate-500"><?= count($dsPhieu) ?> phiếu</span>
        </div>
        <div class="overflow-x-auto">
            <table class="w-full text-sm text-left">
                <thead class="bg-slate-50 text-slate-600">
                    <tr>
                        <th class="p-3">Mã phiếu</th>
                        <th class="p-3">SBD</th>
                        <th class="p-3">Họ tên</th>
                        <th class="p-3">Ngày gửi</th>
                        <th class="p-3">Lý do</th>
                        <th class="p-3">Trạng thái</th>
                        <th class="p-3"></th>
                    </tr>
                </thead>
                <tbody>
                <?php foreach ($dsPhieu as $p): ?>
                    <?php [$nhan, $mau] = trangThaiPK((string)$p['trangthai']); ?>
                    <tr class="border-t border-slate-100 <?= (int)$p['maphieu'] === $maPhieu ? 'bg-blue-50' : 'hover:bg-slate-50' ?>">
                        <td class="p-3 font-medium">#<?= (int)$p['maphieu'] ?></td>
                        <td class="p-3"><?= hPK($p['sobaodanh'] ?? '—') ?></td>
                        <td class="p-3 font-medium"><?= hPK(trim(($p['ho'] ?? '') . ' ' . ($p['ten'] ?? ''))) ?></td>
                        <td class="p-3"><?= hPK(date('d/m/Y H:i', strtotime($p['ngaytao']))) ?></td>
                        <td class="p-3 max-w-xs truncate" title="<?= hPK($p['lydo']) ?>"><?= hPK($p['lydo']) ?></td>
                        <td class="p-3"><span class="rounded-full px-3 py-1 text-xs <?= $mau ?>"><?= hPK($nhan) ?></span></td>
                        <td class="p-3 text-right">
                            <a href="?phieu=<?= (int)$p['maphieu'] ?>"
                               class="rounded-lg border border-blue-200 px-3 py-1.5 text-blue-700 hover:bg-blue-50">
                                <?= in_array($p['trangthai'], ['HOAN_THANH', 'TU_CHOI'], true) ? 'Xem' : 'Nhập điểm' ?>
                            </a>
                        </td>
                    </tr>
                <?php endforeach; ?>
                <?php if (!$dsPhieu): ?>
                    <tr><td colspan="7" class="p-8 text-center text-slate-500">Không có hồ sơ phúc khảo đủ điều kiện để nhập điểm.</td></tr>
                <?php endif; ?>
                </tbody>
            </table>
        </div>
    </section>
    <?php endif; ?>

    <?php if ($ct): ?>
    <?php $phieu = $ct['phieu']; ?>
    <!-- BƯỚC 6-7: CHI TIẾT HỒ SƠ + NHẬP ĐIỂM -->
    <section class="rounded-2xl border border-slate-200 bg-white shadow-sm overflow-hidden">
        <div class="border-b border-slate-200 px-5 py-3">
            <h2 class="font-semibold">Phiếu #<?= (int)$phieu['maphieu'] ?> –
                <?= hPK(trim(($phieu['ho'] ?? '') . ' ' . ($phieu['ten'] ?? ''))) ?>
                (SBD <?= hPK($phieu['sobaodanh'] ?? '—') ?>)</h2>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-2 gap-4 px-5 py-4 text-sm">
            <p><b>Lý do phúc khảo:</b> <?= hPK($phieu['lydo']) ?></p>
            <p><b>Minh chứng:</b> <?= $phieu['tenfilegoc'] ? hPK($phieu['tenfilegoc']) : '<span class="text-slate-400">Không có</span>' ?></p>
            <p><b>Ngày gửi:</b> <?= hPK(date('d/m/Y H:i', strtotime($phieu['ngaytao']))) ?></p>
            <p><b>Trạng thái:</b> <?= hPK(trangThaiPK((string)$phieu['trangthai'])[0]) ?></p>
        </div>

        <form method="post" action="?phieu=<?= (int)$maPhieu ?>">
            <input type="hidden" name="csrf" value="<?= hPK($_SESSION['csrf_phuckhao']) ?>">
            <div class="overflow-x-auto">
                <table class="w-full text-sm text-left">
                    <thead class="bg-slate-50 text-slate-600">
                        <tr>
                            <th class="p-3">Môn thi</th>
                            <th class="p-3">Số phách</th>
                            <th class="p-3">Điểm ban đầu</th>
                            <th class="p-3">Điểm phúc khảo đã lưu</th>
                            <th class="p-3 w-52">Nhập điểm phúc khảo</th>
                        </tr>
                    </thead>
                    <tbody>
                    <?php foreach ($ct['baiThi'] as $bt): ?>
                        <?php
                        $ma = (int)$bt['mabaithi'];
                        $giaTri = array_key_exists($ma, $diemDaGo)
                            ? (string)$diemDaGo[$ma]
                            : ($bt['diemsauphuckhao'] === null ? '' : number_format((float)$bt['diemsauphuckhao'], 2, '.', ''));
                        ?>
                        <tr class="border-t border-slate-100 <?= isset($loiDiem[$ma]) ? 'bg-red-50' : '' ?>">
                            <td class="p-3 font-medium"><?= hPK($bt['tenmonhoc']) ?></td>
                            <td class="p-3 text-slate-600"><?= hPK($bt['sophach'] ?? '—') ?></td>
                            <td class="p-3"><?= diemPK($bt['diem']) ?></td>
                            <td class="p-3"><?= diemPK($bt['diemsauphuckhao']) ?></td>
                            <td class="p-3">
                                <?php if ((int)$bt['vang'] === 1): ?>
                                    <span class="text-slate-500">Vắng thi</span>
                                <?php elseif ($ct['daCapNhat']): ?>
                                    <span class="text-slate-400">Đã cập nhật</span>
                                <?php elseif ($khoaNhap): ?>
                                    <span class="text-slate-400">Đã khóa</span>
                                <?php else: ?>
                                    <input type="text" inputmode="decimal" name="diem[<?= $ma ?>]"
                                           value="<?= hPK($giaTri) ?>" placeholder="Để trống nếu không phúc khảo"
                                           class="w-48 rounded-lg border px-3 py-1.5 <?= isset($loiDiem[$ma]) ? 'border-red-500' : 'border-slate-300' ?>">
                                    <?php if (isset($loiDiem[$ma])): ?>
                                        <p class="mt-1 text-xs text-red-600"><?= hPK($loiDiem[$ma]) ?></p>
                                    <?php endif; ?>
                                <?php endif; ?>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                    <?php if (!$ct['baiThi']): ?>
                        <tr><td colspan="5" class="p-8 text-center text-slate-500">Thí sinh chưa có bài thi nào để nhập điểm phúc khảo.</td></tr>
                    <?php endif; ?>
                    </tbody>
                </table>
            </div>

            <?php if ($ct['baiThi'] && !$ct['daCapNhat'] && !$khoaNhap): ?>
            <div class="flex justify-end gap-3 border-t border-slate-200 px-5 py-4">
                <a href="?" class="rounded-lg border border-slate-300 px-5 py-2.5 text-sm font-semibold text-slate-700 hover:bg-slate-50">Đóng</a>
                <button type="submit" name="hanhDong" value="kiemTra"
                        class="rounded-lg bg-blue-700 px-6 py-2.5 text-sm font-semibold text-white hover:bg-blue-800">Lưu</button>
            </div>
            <?php endif; ?>
        </form>
    </section>
    <?php endif; ?>

</div>
</main>

<!-- BƯỚC 10-11: POPUP ĐỐI CHIẾU ĐIỂM BAN ĐẦU / ĐIỂM PHÚC KHẢO -->
<?php if ($hienXacNhan && $ct): ?>
<div id="popupXacNhanPK" class="fixed inset-0 z-50 flex items-center justify-center bg-black/40" role="dialog" aria-modal="true">
    <div class="mx-4 w-full max-w-xl rounded-2xl bg-white p-6 shadow-xl">
        <h3 class="mb-1 text-lg font-bold">Xác nhận kết quả phúc khảo</h3>
        <p class="mb-4 text-sm text-slate-600">
            Phiếu #<?= (int)$maPhieu ?> – <?= hPK(trim(($ct['phieu']['ho'] ?? '') . ' ' . ($ct['phieu']['ten'] ?? ''))) ?>.
            Điểm thi của thí sinh sẽ được cập nhật theo điểm phúc khảo.
        </p>
        <table class="w-full rounded-lg border border-slate-200 text-sm text-left">
            <thead class="bg-slate-50">
                <tr><th class="p-2">Môn</th><th class="p-2">Điểm ban đầu</th><th class="p-2">Điểm phúc khảo</th><th class="p-2"></th></tr>
            </thead>
            <tbody>
            <?php foreach ($doiChieu as $d): ?>
                <tr class="border-t border-slate-100">
                    <td class="p-2"><?= hPK($d['tenmonhoc']) ?></td>
                    <td class="p-2 text-slate-500"><?= diemPK($d['diemcu']) ?></td>
                    <td class="p-2 font-semibold text-blue-700"><?= diemPK($d['diemmoi']) ?></td>
                    <td class="p-2">
                        <?php if ($d['khac']): ?>
                            <span class="rounded-full bg-amber-100 px-2 py-0.5 text-xs text-amber-700">Thay đổi</span>
                        <?php else: ?>
                            <span class="rounded-full bg-slate-100 px-2 py-0.5 text-xs text-slate-600">Giữ nguyên</span>
                        <?php endif; ?>
                    </td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>

        <form method="post" action="?phieu=<?= (int)$maPhieu ?>" class="mt-5 flex gap-3">
            <input type="hidden" name="csrf" value="<?= hPK($_SESSION['csrf_phuckhao']) ?>">
            <?php foreach ($doiChieu as $d): ?>
                <input type="hidden" name="diem[<?= (int)$d['mabaithi'] ?>]" value="<?= hPK($d['diemmoi']) ?>">
            <?php endforeach; ?>
            <!-- 11.1: Hủy -> không lưu, điểm ban đầu giữ nguyên -->
            <button type="button" onclick="document.getElementById('popupXacNhanPK').remove()"
                    class="flex-1 rounded-lg border border-slate-300 px-5 py-2.5 font-semibold text-slate-700 hover:bg-slate-50">Hủy</button>
            <button type="submit" name="hanhDong" value="luu"
                    class="flex-1 rounded-lg bg-blue-700 px-5 py-2.5 font-semibold text-white hover:bg-blue-800">Xác nhận</button>
        </form>
    </div>
</div>
<?php endif; ?>

<!-- POPUP KẾT QUẢ -->
<?php if ($popup !== null): ?>
<div id="popupKetQuaPK" class="fixed inset-0 z-50 flex items-center justify-center bg-black/40" role="dialog" aria-modal="true">
    <div class="mx-4 w-full max-w-md rounded-2xl bg-white p-6 text-center shadow-xl">
        <div class="mx-auto mb-4 flex h-12 w-12 items-center justify-center rounded-full <?= $popup['thanhCong'] ? 'bg-green-100' : 'bg-red-100' ?>">
            <span class="text-2xl <?= $popup['thanhCong'] ? 'text-green-600' : 'text-red-600' ?>"><?= $popup['thanhCong'] ? '✓' : '!' ?></span>
        </div>
        <h3 class="mb-2 text-lg font-bold text-slate-800"><?= hPK($popup['tieuDe']) ?></h3>
        <p class="mb-5 text-sm text-slate-600"><?= hPK($popup['noiDung']) ?></p>
        <button type="button" onclick="document.getElementById('popupKetQuaPK').remove()"
                class="w-full rounded-lg bg-blue-700 px-5 py-2.5 font-semibold text-white hover:bg-blue-800">Đóng</button>
    </div>
</div>
<?php endif; ?>

</body>
</html>
