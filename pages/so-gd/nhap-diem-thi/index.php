<?php
use NhapDiemThi\CtrlNhapDiemThi;

if (session_status() !== PHP_SESSION_ACTIVE) {
    session_start();
}

require_once __DIR__ . '/../../../index.php';
require_once __DIR__ . '/../../../class/nhap-diem-thi/ctrlNhapDiemThi.php';
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

function hND($value): string
{
    return htmlspecialchars((string)$value, ENT_QUOTES, 'UTF-8');
}

function diemND($value): string
{
    return $value === null || $value === '' ? '—' : number_format((float)$value, 2, '.', '');
}

if (empty($_SESSION['csrf_nhapdiemthi'])) {
    $_SESSION['csrf_nhapdiemthi'] = bin2hex(random_bytes(32));
}

$ctrl = new CtrlNhapDiemThi($conn);

$thongBao = '';
$popup = null;
$hienXacNhan = false;
$thayDoi = [];
$loiDiem = [];          // [mabaithi => lỗi] (luồng 10.1)
$diemDaGo = [];         // giữ lại điểm người dùng đã gõ khi có lỗi / bấm Hủy
$dsBaiThi = [];
$bt = ['tong' => 0, 'trang' => 1, 'soTrang' => 1, 'loc' => 'chuanhap', 'tim' => ''];

$form = $ctrl->yeuCauForm();
$ky = $form['ky'];
$dsMon = $form['dsMon'];
if ($form['thongBao'] !== '') {
    $thongBao = $form['thongBao'];
}

// Môn đang chọn (bước 5): từ URL, mặc định môn đầu tiên có bài thi
$maMon = (int)($_GET['mon'] ?? 0);
$monHopLe = array_column($dsMon, null, 'mamonhoc');
if (!isset($monHopLe[$maMon])) {
    $maMon = 0;
    foreach ($dsMon as $m) {
        if ($m['tong'] > 0) {
            $maMon = $m['mamonhoc'];
            break;
        }
    }
}
$monDangChon = $monHopLe[$maMon] ?? null;

$loc   = (string)($_GET['loc'] ?? 'chuanhap');
$tim   = (string)($_GET['tim'] ?? '');
$trang = (int)($_GET['trang'] ?? 1);

$maKy = $ky ? (int)$ky['makythi'] : 0;

$khoaNhap = !empty($form['khoaNhap']); // đã xác nhận xét tuyển -> chỉ xem

if ($ky && $monDangChon && !$khoaNhap && $_SERVER['REQUEST_METHOD'] === 'POST') {
    $csrf = (string)($_POST['csrf'] ?? '');
    $nhap = is_array($_POST['diem'] ?? null) ? $_POST['diem'] : [];

    if (!hash_equals($_SESSION['csrf_nhapdiemthi'], $csrf)) {
        $thongBao = 'Phiên làm việc không hợp lệ. Vui lòng tải lại trang.';
    } else {
        $hanhDong = (string)($_POST['hanhDong'] ?? '');

        if ($hanhDong === 'kiemTra') {
            // Bước 9-12
            $diemDaGo = $nhap;
            $kt = $ctrl->kiemTraDiem($maKy, $maMon, $nhap);
            if ($kt['loi']) {
                $loiDiem = $kt['loi'];
                $thongBao = 'Điểm thi không hợp lệ, vui lòng kiểm tra và nhập lại.';
            } elseif (!$kt['thayDoi']) {
                $thongBao = 'Chưa có điểm mới nào được nhập hoặc thay đổi.';
            } else {
                $thayDoi = $kt['thayDoi'];
                $hienXacNhan = true;
            }
        } elseif ($hanhDong === 'luu') {
            // Bước 13-17
            $kq = $ctrl->luuDiem($maKy, $maMon, (string)$monDangChon['tenmonhoc'], $nhap, isset($_SESSION['manguoidung']) ? (int)$_SESSION['manguoidung'] : null);
            $popup = [
                'thanhCong' => $kq['thanhCong'],
                'tieuDe'    => $kq['thanhCong'] ? 'Lưu điểm thành công' : 'Không thể lưu điểm',
                'noiDung'   => $kq['thongBao'],
            ];
            if ($kq['thanhCong']) {
                // Cập nhật lại thống kê từng môn
                $form = $ctrl->yeuCauForm();
                $dsMon = $form['dsMon'];
                $monHopLe = array_column($dsMon, null, 'mamonhoc');
                $monDangChon = $monHopLe[$maMon] ?? $monDangChon;
            } else {
                $diemDaGo = $nhap;
            }
        }
    }
}

if ($ky && $monDangChon) {
    $bt = $ctrl->taiBaiThi($maKy, $maMon, $loc, $tim, $trang);
    $dsBaiThi = $bt['dsBaiThi'];
    if ($bt['thongBao'] !== '' && $thongBao === '') {
        $thongBao = $bt['thongBao'];
    }
}

/** URL giữ nguyên môn / lọc / tìm kiếm / trang. */
function urlND(array $thayDoi = []): string
{
    global $maMon, $bt;
    $q = array_merge(
        ['mon' => $maMon, 'loc' => $bt['loc'], 'tim' => $bt['tim'], 'trang' => $bt['trang']],
        $thayDoi
    );
    return '?' . http_build_query(array_filter($q, static fn($v) => $v !== '' && $v !== null));
}
?>
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Stackers - Nhập điểm thi</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-slate-50 text-slate-800">

<?php renderHeader(); renderSidebar(); ?>

<main class="pl-72 pt-20">
<div class="mx-auto max-w-7xl px-6 py-8 space-y-6">

    <!-- TIÊU ĐỀ -->
    <div>
        <p class="mb-2 text-sm text-slate-500">Sở GD&amp;ĐT / Quản lý điểm / Nhập điểm thi</p>
        <h1 class="text-2xl font-bold">Nhập điểm thi</h1>
        <p class="mt-1 text-sm text-slate-500">Nhập điểm các môn thi tuyển sinh lớp 10 (thang điểm 10, bước 0.25).</p>
    </div>

    <?php if ($thongBao): ?>
        <div class="rounded-xl whitespace-pre-line border border-amber-300 bg-amber-50 px-5 py-4 text-sm text-amber-900">
            <?= hND($thongBao) ?>
        </div>
    <?php endif; ?>

    <?php if ($ky): ?>
    <!-- KỲ THI (CỐ ĐỊNH 2026) -->
    <section class="rounded-2xl border border-slate-200 bg-white shadow-sm p-5">
        <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
            <label class="text-sm">
                Kỳ tuyển sinh
                <input type="text" value="<?= hND($ky['namtuyensinh']) ?>" readonly
                       class="mt-1 block w-full rounded-lg border border-slate-300 bg-slate-50 px-3 py-2 text-slate-700 cursor-default">
            </label>
        </div>
    </section>

    <!-- BƯỚC 4: MÔN THI + TRẠNG THÁI NHẬP ĐIỂM -->
    <section class="grid grid-cols-1 md:grid-cols-3 gap-4">
        <?php foreach ($dsMon as $m): ?>
            <?php
            $chon = $m['mamonhoc'] === $maMon;
            $soCanNhap = $m['tong'] - $m['vang'];
            $phanTram = $soCanNhap > 0 ? (int)floor($m['danhap'] * 100 / $soCanNhap) : 0;
            if ($m['tong'] === 0) {
                [$nhan, $mau] = ['Không có bài thi', 'bg-slate-100 text-slate-600'];
            } elseif ($m['chuanhap'] === 0) {
                [$nhan, $mau] = ['Đã nhập đủ', 'bg-green-100 text-green-700'];
            } elseif ($m['danhap'] > 0) {
                [$nhan, $mau] = ['Đang nhập', 'bg-blue-100 text-blue-700'];
            } else {
                [$nhan, $mau] = ['Chưa nhập', 'bg-amber-100 text-amber-700'];
            }
            ?>
            <a href="?mon=<?= (int)$m['mamonhoc'] ?>"
               class="block rounded-2xl border bg-white p-5 shadow-sm hover:border-blue-400
                      <?= $chon ? 'border-blue-600 ring-2 ring-blue-100' : 'border-slate-200' ?>">
                <div class="flex items-center justify-between">
                    <h3 class="font-semibold"><?= hND($m['tenmonhoc']) ?></h3>
                    <span class="rounded-full px-3 py-1 text-xs <?= $mau ?>"><?= $nhan ?></span>
                </div>
                <p class="mt-3 text-sm text-slate-600">
                    Đã nhập <b><?= $m['danhap'] ?></b> / <?= $soCanNhap ?> bài
                    <?= $m['vang'] ? ' · vắng ' . $m['vang'] : '' ?>
                </p>
                <div class="mt-2 h-2 w-full rounded-full bg-slate-100">
                    <div class="h-2 rounded-full bg-blue-600" style="width: <?= $phanTram ?>%"></div>
                </div>
            </a>
        <?php endforeach; ?>
    </section>
    <?php endif; ?>

    <?php if ($ky && $monDangChon): ?>
    <!-- BƯỚC 6: DANH SÁCH BÀI THI -->
    <section class="rounded-2xl border border-slate-200 bg-white shadow-sm overflow-hidden">
        <div class="flex flex-wrap items-center justify-between gap-3 border-b border-slate-200 px-5 py-3">
            <h2 class="font-semibold">Bài thi môn <?= hND($monDangChon['tenmonhoc']) ?></h2>

            <!-- Lọc + tìm kiếm -->
            <form method="get" class="flex flex-wrap items-center gap-2 text-sm">
                <input type="hidden" name="mon" value="<?= (int)$maMon ?>">
                <select name="loc" onchange="this.form.submit()" class="rounded-lg border border-slate-300 px-3 py-1.5">
                    <?php foreach (['chuanhap' => 'Chưa có điểm', 'danhap' => 'Đã có điểm', 'vang' => 'Vắng thi', 'tatca' => 'Tất cả'] as $k => $v): ?>
                        <option value="<?= $k ?>" <?= $bt['loc'] === $k ? 'selected' : '' ?>><?= $v ?></option>
                    <?php endforeach; ?>
                </select>
                <input type="text" name="tim" value="<?= hND($bt['tim']) ?>" placeholder="SBD, số phách, họ tên"
                       class="w-52 rounded-lg border border-slate-300 px-3 py-1.5">
                <button class="rounded-lg border border-blue-200 px-3 py-1.5 text-blue-700 hover:bg-blue-50">Tìm</button>
            </form>
        </div>

        <form method="post" action="<?= hND(urlND()) ?>">
            <input type="hidden" name="csrf" value="<?= hND($_SESSION['csrf_nhapdiemthi']) ?>">

            <div class="overflow-x-auto">
                <table class="w-full text-sm text-left">
                    <thead class="bg-slate-50 text-slate-600">
                        <tr>
                            <th class="p-3">SBD</th>
                            <th class="p-3">Họ tên</th>
                            <th class="p-3">Số phách</th>
                            <th class="p-3">Điểm hiện tại</th>
                            <th class="p-3 w-48">Nhập điểm</th>
                            <th class="p-3">Trạng thái</th>
                        </tr>
                    </thead>
                    <tbody>
                    <?php foreach ($dsBaiThi as $r): ?>
                        <?php
                        $ma = (int)$r['mabaithi'];
                        $vang = (int)$r['vang'] === 1;
                        $giaTri = array_key_exists($ma, $diemDaGo)
                            ? (string)$diemDaGo[$ma]
                            : ($r['diem'] === null ? '' : number_format((float)$r['diem'], 2, '.', ''));
                        ?>
                        <tr class="border-t border-slate-100 <?= isset($loiDiem[$ma]) ? 'bg-red-50' : '' ?>">
                            <td class="p-3"><?= hND($r['sobaodanh'] ?? '—') ?></td>
                            <td class="p-3 font-medium"><?= hND(trim(($r['ho'] ?? '') . ' ' . ($r['ten'] ?? ''))) ?></td>
                            <td class="p-3 text-slate-600"><?= hND($r['sophach'] ?? '—') ?></td>
                            <td class="p-3"><?= diemND($r['diem']) ?></td>
                            <td class="p-3">
                                <?php if ($vang): ?>
                                    <span class="text-slate-500">Vắng thi</span>
                                <?php elseif ($khoaNhap): ?>
                                    <span class="text-slate-400">Đã khóa</span>
                                <?php else: ?>
                                    <input type="text" inputmode="decimal" name="diem[<?= $ma ?>]"
                                           value="<?= hND($giaTri) ?>" placeholder="0 – 10"
                                           class="w-28 rounded-lg border px-3 py-1.5
                                                  <?= isset($loiDiem[$ma]) ? 'border-red-500' : 'border-slate-300' ?>">
                                    <?php if (isset($loiDiem[$ma])): ?>
                                        <p class="mt-1 text-xs text-red-600"><?= hND($loiDiem[$ma]) ?></p>
                                    <?php endif; ?>
                                <?php endif; ?>
                            </td>
                            <td class="p-3">
                                <?php if ($vang): ?>
                                    <span class="rounded-full bg-slate-100 px-3 py-1 text-xs text-slate-600">Vắng</span>
                                <?php elseif ($r['diem'] !== null): ?>
                                    <span class="rounded-full bg-green-100 px-3 py-1 text-xs text-green-700">Đã nhập</span>
                                <?php else: ?>
                                    <span class="rounded-full bg-amber-100 px-3 py-1 text-xs text-amber-700">Chưa nhập</span>
                                <?php endif; ?>
                            </td>
                        </tr>
                    <?php endforeach; ?>

                    <?php if (!$dsBaiThi): ?>
                        <tr><td colspan="6" class="p-8 text-center text-slate-500">Không có thí sinh hoặc bài thi phù hợp để nhập điểm.</td></tr>
                    <?php endif; ?>
                    </tbody>
                </table>
            </div>

            <!-- PHÂN TRANG + NÚT LƯU -->
            <div class="flex flex-wrap items-center justify-between gap-3 border-t border-slate-200 px-5 py-4 text-sm">
                <div class="flex items-center gap-2 text-slate-600">
                    <span><?= (int)$bt['tong'] ?> bài thi</span>
                    <?php if ($bt['soTrang'] > 1): ?>
                        <span>·</span>
                        <?php if ($bt['trang'] > 1): ?>
                            <a href="<?= hND(urlND(['trang' => $bt['trang'] - 1])) ?>" class="rounded border px-2 py-1 hover:bg-slate-50">‹ Trước</a>
                        <?php endif; ?>
                        <span>Trang <?= (int)$bt['trang'] ?> / <?= (int)$bt['soTrang'] ?></span>
                        <?php if ($bt['trang'] < $bt['soTrang']): ?>
                            <a href="<?= hND(urlND(['trang' => $bt['trang'] + 1])) ?>" class="rounded border px-2 py-1 hover:bg-slate-50">Sau ›</a>
                        <?php endif; ?>
                    <?php endif; ?>
                </div>

                <?php if ($dsBaiThi && !$khoaNhap): ?>
                    <button type="submit" name="hanhDong" value="kiemTra"
                            class="rounded-lg bg-blue-700 px-6 py-2.5 font-semibold text-white hover:bg-blue-800">
                        Lưu điểm
                    </button>
                <?php endif; ?>
            </div>
            <p class="px-5 pb-4 text-xs text-slate-500">
                Lưu ý: chỉ lưu điểm trên trang đang xem. Hãy bấm "Lưu điểm" trước khi chuyển trang.
            </p>
        </form>
    </section>
    <?php endif; ?>

</div>
</main>

<!-- BƯỚC 12-13: POPUP XÁC NHẬN LƯU ĐIỂM -->
<?php if ($hienXacNhan): ?>
<div id="popupXacNhanND" class="fixed inset-0 z-50 flex items-center justify-center bg-black/40" role="dialog" aria-modal="true">
    <div class="mx-4 w-full max-w-2xl rounded-2xl bg-white p-6 shadow-xl">
        <h3 class="mb-1 text-lg font-bold">Xác nhận lưu điểm môn <?= hND($monDangChon['tenmonhoc']) ?></h3>
        <p class="mb-4 text-sm text-slate-600">Kiểm tra lại <?= count($thayDoi) ?> điểm trước khi lưu.</p>
        <div class="max-h-80 overflow-y-auto rounded-lg border border-slate-200">
            <table class="w-full text-sm text-left">
                <thead class="sticky top-0 bg-slate-50">
                    <tr><th class="p-2">SBD</th><th class="p-2">Họ tên</th><th class="p-2">Số phách</th>
                        <th class="p-2">Điểm cũ</th><th class="p-2">Điểm mới</th></tr>
                </thead>
                <tbody>
                <?php foreach ($thayDoi as $t): ?>
                    <tr class="border-t border-slate-100">
                        <td class="p-2"><?= hND($t['sobaodanh'] ?? '—') ?></td>
                        <td class="p-2"><?= hND($t['hoten']) ?></td>
                        <td class="p-2"><?= hND($t['sophach'] ?? '—') ?></td>
                        <td class="p-2 text-slate-500"><?= diemND($t['diemcu']) ?></td>
                        <td class="p-2 font-semibold text-blue-700"><?= diemND($t['diemmoi']) ?></td>
                    </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        </div>

        <form method="post" action="<?= hND(urlND()) ?>" class="mt-5 flex gap-3">
            <input type="hidden" name="csrf" value="<?= hND($_SESSION['csrf_nhapdiemthi']) ?>">
            <?php foreach ($thayDoi as $t): ?>
                <input type="hidden" name="diem[<?= (int)$t['mabaithi'] ?>]" value="<?= hND($t['diemmoi']) ?>">
            <?php endforeach; ?>
            <!-- 13.1: Hủy -> không lưu, quay lại bảng để sửa (12.1) -->
            <button type="button" onclick="document.getElementById('popupXacNhanND').remove()"
                    class="flex-1 rounded-lg border border-slate-300 px-5 py-2.5 font-semibold text-slate-700 hover:bg-slate-50">Hủy</button>
            <button type="submit" name="hanhDong" value="luu"
                    class="flex-1 rounded-lg bg-blue-700 px-5 py-2.5 font-semibold text-white hover:bg-blue-800">Xác nhận lưu</button>
        </form>
    </div>
</div>
<?php endif; ?>

<!-- POPUP KẾT QUẢ -->
<?php if ($popup !== null): ?>
<div id="popupKetQuaND" class="fixed inset-0 z-50 flex items-center justify-center bg-black/40" role="dialog" aria-modal="true">
    <div class="mx-4 w-full max-w-md rounded-2xl bg-white p-6 text-center shadow-xl">
        <div class="mx-auto mb-4 flex h-12 w-12 items-center justify-center rounded-full <?= $popup['thanhCong'] ? 'bg-green-100' : 'bg-red-100' ?>">
            <span class="text-2xl <?= $popup['thanhCong'] ? 'text-green-600' : 'text-red-600' ?>"><?= $popup['thanhCong'] ? '✓' : '!' ?></span>
        </div>
        <h3 class="mb-2 text-lg font-bold text-slate-800"><?= hND($popup['tieuDe']) ?></h3>
        <p class="mb-5 text-sm text-slate-600"><?= hND($popup['noiDung']) ?></p>
        <button type="button" onclick="document.getElementById('popupKetQuaND').remove()"
                class="w-full rounded-lg bg-blue-700 px-5 py-2.5 font-semibold text-white hover:bg-blue-800">Đóng</button>
    </div>
</div>
<?php endif; ?>

</body>
</html>
