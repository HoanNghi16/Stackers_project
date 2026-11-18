<?php
use CongBoDiemChuan\CtrlCongBoDiemChuan;
use CongBoDiemChuan\DiemChuanModel;
use CongBoDiemChuan\NghiepVuException;

if (session_status() !== PHP_SESSION_ACTIVE) {
    session_start();
}

require_once __DIR__ . '/../../../index.php';
require_once __DIR__ . '/../../../class/cong-bo-diem-chuan/ctrlCongBoDiemChuan.php';
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

function hCB($value): string
{
    return htmlspecialchars((string)$value, ENT_QUOTES, 'UTF-8');
}

function diemCB($value): string
{
    return $value === null || $value === '' ? '—' : number_format((float)$value, 2, '.', '');
}

function nhanTrangThaiCB(string $tt): array
{
    return match ($tt) {
        DiemChuanModel::DA_CONG_BO  => ['Đã công bố', 'bg-green-100 text-green-700'],
        DiemChuanModel::CHO_CONG_BO => ['Chờ công bố', 'bg-amber-100 text-amber-700'],
        default                     => ['Chưa công bố', 'bg-slate-100 text-slate-700'],
    };
}

if (empty($_SESSION['csrf_congbodiemchuan'])) {
    $_SESSION['csrf_congbodiemchuan'] = bin2hex(random_bytes(32));
}

$ctrl = new CtrlCongBoDiemChuan($conn);

$dsKy = [];
$maKy = 0;
$dl = null;
$thongBao = '';
$popup = null;          // popup kết quả (thành công / lỗi)
$hienXacNhan = false;   // popup xác nhận công bố (bước 7)

try {
    $dsKy = $ctrl->yeuCauFormCongBo()['dsKy'];

    // Kỳ tuyển sinh cố định: năm 2026 (giống trang Xét tuyển). Không lấy mã kỳ từ POST.
    foreach ($dsKy as $ky) {
        if ((int)$ky['namtuyensinh'] === 2026) {
            $maKy = (int)$ky['makythi'];
            break;
        }
    }

    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        $csrf = (string)($_POST['csrf'] ?? '');

        if (!hash_equals($_SESSION['csrf_congbodiemchuan'], $csrf)) {
            $thongBao = 'Phiên làm việc không hợp lệ. Vui lòng tải lại trang.';
        } else {
            $hanhDong = (string)($_POST['hanhDong'] ?? 'tai');

            if ($hanhDong === 'kiemTra') {
                // Bước 6-7: kiểm tra, hợp lệ thì hiện popup xác nhận
                try {
                    $ctrl->kiemTraTruocCongBo($maKy);
                    $hienXacNhan = true;
                } catch (NghiepVuException $e) {
                    $popup = [
                        'thanhCong' => false,
                        'tieuDe'    => 'Chưa thể công bố điểm chuẩn',
                        'noiDung'   => $e->getMessage(),
                        'dsLoi'     => $e->layChiTiet(),
                    ];
                }
            } elseif ($hanhDong === 'congBo') {
                $nguoi = isset($_SESSION['manguoidung']) ? (int)$_SESSION['manguoidung'] : null;
                $kq = $ctrl->congBo($maKy, $nguoi);
                $popup = [
                    'thanhCong' => $kq['thanhCong'],
                    'tieuDe'    => $kq['thanhCong'] ? 'Công bố thành công' : 'Công bố không thành công',
                    'noiDung'   => $kq['thongBao'],
                    'dsLoi'     => $kq['dsLoi'] ?? [],
                ];
            } elseif ($hanhDong === 'luu') {
                $kq = $ctrl->luuChoCongBo($maKy);
                $popup = [
                    'thanhCong' => $kq['thanhCong'],
                    'tieuDe'    => $kq['thanhCong'] ? 'Đã lưu chờ công bố' : 'Không thể lưu',
                    'noiDung'   => $kq['thongBao'],
                    'dsLoi'     => $kq['dsLoi'] ?? [],
                ];
            }
        }
    }

    // Luôn tải lại danh sách sau mọi thao tác để thấy trạng thái mới nhất
    if ($maKy > 0) {
        $dl = $ctrl->taiDanhSach($maKy);
        if (!$dl['thanhCong'] || $dl['thongBao'] !== '') {
            $thongBao = $thongBao ?: $dl['thongBao'];
        }
    } else {
        $thongBao = 'Không tìm thấy kỳ tuyển sinh năm 2026 trong database.';
    }

} catch (Throwable $e) {
    error_log('[CongBoDiemChuanPage] ' . $e->getMessage());
    $thongBao = 'Công bố điểm chuẩn chưa hoàn tất. Không thể hiển thị dữ liệu.';
}

$dsDiemChuan = $dl['dsDiemChuan'] ?? [];
$trangThaiKy = $dl['trangThaiKy'] ?? DiemChuanModel::CHUA_CONG_BO;
$xetTuyen    = $dl['xetTuyen'] ?? ['tong' => 0, 'dangXet' => 0, 'hoanTat' => false];
$daCongBo    = $trangThaiKy === DiemChuanModel::DA_CONG_BO;
[$nhanKy, $mauKy] = nhanTrangThaiCB($trangThaiKy);

$namKy = '';
foreach ($dsKy as $ky) {
    if ((int)$ky['makythi'] === $maKy) {
        $namKy = $ky['namtuyensinh'];
    }
}
?>
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Stackers - Công bố điểm chuẩn</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-slate-50 text-slate-800">

<?php renderHeader(); renderSidebar(); ?>

<main class="pl-72 pt-20">
<div class="mx-auto max-w-7xl px-6 py-8 space-y-6">

    <!-- TIÊU ĐỀ -->
    <div>
        <p class="mb-2 text-sm text-slate-500">Sở GD&amp;ĐT / Tuyển sinh / Công bố điểm chuẩn</p>
        <h1 class="text-2xl font-bold">Công bố điểm chuẩn</h1>
        <p class="mt-1 text-sm text-slate-500">Kiểm tra và công bố điểm chuẩn vào lớp 10 của các trường.</p>
    </div>

    <?php if ($thongBao): ?>
        <div class="rounded-xl whitespace-pre-line border border-amber-300 bg-amber-50 px-5 py-4 text-sm text-amber-900">
            <?= hCB($thongBao) ?>
        </div>
    <?php endif; ?>

    <form method="post" id="formCongBo" class="space-y-6">
        <input type="hidden" name="csrf" value="<?= hCB($_SESSION['csrf_congbodiemchuan']) ?>">

        <!-- KỲ TUYỂN SINH (CỐ ĐỊNH 2026) + TRẠNG THÁI -->
        <section class="rounded-2xl border border-slate-200 bg-white shadow-sm p-5">
            <div class="grid grid-cols-1 md:grid-cols-3 gap-4 items-end">
                <label class="text-sm">
                    Kỳ tuyển sinh
                    <input type="text" value="2026" readonly
                           class="mt-1 block w-full rounded-lg border border-slate-300
                                  bg-slate-50 px-3 py-2 text-slate-700 cursor-default">
                </label>

                <div class="text-sm">
                    <p class="text-slate-500">Trạng thái xét tuyển</p>
                    <?php if ($xetTuyen['hoanTat']): ?>
                        <span class="mt-1 inline-block rounded-full bg-green-100 px-3 py-1 text-xs text-green-700">Đã hoàn tất</span>
                    <?php else: ?>
                        <span class="mt-1 inline-block rounded-full bg-red-100 px-3 py-1 text-xs text-red-700">
                            Chưa hoàn tất<?= $xetTuyen['dangXet'] ? ' (' . (int)$xetTuyen['dangXet'] . ' nguyện vọng đang xét)' : '' ?>
                        </span>
                    <?php endif; ?>
                </div>

                <div class="text-sm">
                    <p class="text-slate-500">Trạng thái công bố</p>
                    <span class="mt-1 inline-block rounded-full px-3 py-1 text-xs <?= $mauKy ?>"><?= $nhanKy ?></span>
                    <?php if ($daCongBo && !empty($dsDiemChuan[0]['thoigiancongbo'])): ?>
                        <span class="ml-2 text-xs text-slate-500">
                            lúc <?= hCB(date('H:i d/m/Y', strtotime($dsDiemChuan[0]['thoigiancongbo']))) ?>
                        </span>
                    <?php endif; ?>
                </div>
            </div>
        </section>

        <!-- DANH SÁCH ĐIỂM CHUẨN -->
        <section class="rounded-2xl border border-slate-200 bg-white shadow-sm overflow-hidden">
            <div class="flex items-center justify-between border-b border-slate-200 px-5 py-3">
                <h2 class="font-semibold">Điểm chuẩn các trường<?= $namKy ? ' – năm ' . hCB($namKy) : '' ?></h2>
                <span class="text-xs text-slate-500"><?= count($dsDiemChuan) ?> trường</span>
            </div>

            <div class="overflow-x-auto">
                <table class="w-full text-sm text-left">
                    <thead class="bg-slate-50 text-slate-600">
                        <tr>
                            <th class="p-4">Trường</th>
                            <th class="p-4">Chỉ tiêu</th>
                            <th class="p-4">Trúng tuyển</th>
                            <th class="p-4">Điểm chuẩn NV1</th>
                            <th class="p-4">Điểm chuẩn NV2</th>
                            <th class="p-4">Điểm chuẩn NV3</th>
                            <th class="p-4">Trạng thái</th>
                            <th class="p-4">Chi tiết</th>
                        </tr>
                    </thead>
                    <tbody>
                    <?php foreach ($dsDiemChuan as $dc): ?>
                        <?php [$nhan, $mau] = nhanTrangThaiCB($dc['trangthaicongbo']); ?>
                        <tr class="border-t border-slate-100 hover:bg-slate-50">
                            <td class="p-4 font-medium"><?= hCB($dc['tentruong']) ?></td>
                            <td class="p-4"><?= (int)$dc['chitieu'] ?></td>
                            <td class="p-4"><?= (int)$dc['sotrungtuyen'] ?></td>
                            <?php if (!$dc['codiemchuan']): ?>
                                <td class="p-4 text-red-600" colspan="3">Chưa có điểm chuẩn</td>
                            <?php else: ?>
                                <td class="p-4 font-semibold"><?= diemCB($dc['diemnv1']) ?></td>
                                <td class="p-4 font-semibold"><?= diemCB($dc['diemnv2']) ?></td>
                                <td class="p-4 font-semibold"><?= diemCB($dc['diemnv3']) ?></td>
                            <?php endif; ?>
                            <td class="p-4"><span class="rounded-full px-3 py-1 text-xs <?= $mau ?>"><?= $nhan ?></span></td>
                            <td class="p-4">
                                <button type="button"
                                        onclick="document.getElementById('dc<?= (int)$dc['matruong'] ?>').showModal()"
                                        class="rounded-lg border border-blue-200 px-3 py-1.5 text-blue-700 hover:bg-blue-50">
                                    Xem
                                </button>

                                <!-- 4.1 CHI TIẾT ĐIỂM CHUẨN MỘT TRƯỜNG -->
                                <dialog id="dc<?= (int)$dc['matruong'] ?>"
                                        class="w-full max-w-lg rounded-2xl p-6 shadow-2xl backdrop:bg-black/40">
                                    <h3 class="mb-5 text-lg font-bold"><?= hCB($dc['tentruong']) ?></h3>
                                    <div class="space-y-2 text-sm">
                                        <p><b>Chỉ tiêu:</b> <?= (int)$dc['chitieu'] ?></p>
                                        <p><b>Tổng trúng tuyển:</b> <?= (int)$dc['sotrungtuyen'] ?>
                                           (còn <?= max(0, (int)$dc['chitieu'] - (int)$dc['sotrungtuyen']) ?> chỉ tiêu)</p>
                                        <table class="mt-3 w-full border border-slate-200 text-left">
                                            <thead class="bg-slate-50">
                                                <tr><th class="p-2">Nguyện vọng</th><th class="p-2">Số trúng tuyển</th><th class="p-2">Điểm chuẩn</th></tr>
                                            </thead>
                                            <tbody>
                                            <?php for ($i = 1; $i <= 3; $i++): ?>
                                                <tr class="border-t border-slate-100">
                                                    <td class="p-2">NV<?= $i ?></td>
                                                    <td class="p-2"><?= (int)$dc['trungtuyennv' . $i] ?></td>
                                                    <td class="p-2 font-semibold"><?= $dc['codiemchuan'] ? diemCB($dc['diemnv' . $i]) : '—' ?></td>
                                                </tr>
                                            <?php endfor; ?>
                                            </tbody>
                                        </table>
                                        <p class="pt-2"><b>Trạng thái:</b> <?= $nhan ?></p>
                                        <?php if (!empty($dc['thoigiancongbo'])): ?>
                                            <p><b>Thời gian công bố:</b> <?= hCB(date('H:i d/m/Y', strtotime($dc['thoigiancongbo']))) ?></p>
                                        <?php endif; ?>
                                        <?php if ($dc['codiemchuan'] && $dc['diemnv1'] === null && $dc['diemnv2'] === null && $dc['diemnv3'] === null): ?>
                                            <p class="text-amber-700">Trường không có thí sinh trúng tuyển ở cả 3 nguyện vọng.</p>
                                        <?php endif; ?>
                                    </div>
                                    <div class="mt-6 text-right">
                                        <button type="button" onclick="this.closest('dialog').close()"
                                                class="rounded-lg bg-blue-700 px-5 py-2 text-white hover:bg-blue-800">Đóng</button>
                                    </div>
                                </dialog>
                            </td>
                        </tr>
                    <?php endforeach; ?>

                    <?php if (!$dsDiemChuan): ?>
                        <tr><td colspan="8" class="p-8 text-center text-slate-500">Không có dữ liệu điểm chuẩn.</td></tr>
                    <?php endif; ?>
                    </tbody>
                </table>
            </div>

            <?php if (!empty($dl['dsKhongTrungTuyen'])): ?>
                <p class="border-t border-slate-100 px-5 py-3 text-xs text-amber-700">
                    Không có thí sinh trúng tuyển: <?= hCB(implode(', ', $dl['dsKhongTrungTuyen'])) ?>.
                </p>
            <?php endif; ?>
        </section>

        <!-- NÚT THAO TÁC -->
        <?php if ($dsDiemChuan): ?>
        <div class="flex justify-end gap-3">
            <button type="submit" name="hanhDong" value="luu"
                    <?= $daCongBo ? 'disabled' : '' ?>
                    class="rounded-lg border border-blue-600 px-5 py-2.5 text-sm font-semibold text-blue-700 hover:bg-blue-50
                           disabled:cursor-not-allowed disabled:opacity-50">
                Lưu (chờ công bố)
            </button>
            <button type="submit" name="hanhDong" value="kiemTra"
                    <?= $daCongBo ? 'disabled' : '' ?>
                    class="rounded-lg bg-blue-700 px-6 py-2.5 text-sm font-semibold text-white hover:bg-blue-800
                           disabled:cursor-not-allowed disabled:opacity-50">
                Công bố điểm chuẩn
            </button>
            <!-- Nút ẩn, được bấm khi người dùng chọn OK ở popup xác nhận -->
            <button id="guiCongBo" type="submit" name="hanhDong" value="congBo" class="hidden"></button>
        </div>
        <?php endif; ?>
    </form>

</div>
</main>

<!-- BƯỚC 7: POPUP XÁC NHẬN CÔNG BỐ -->
<?php if ($hienXacNhan): ?>
<div id="popupXacNhanCB" class="fixed inset-0 z-50 flex items-center justify-center bg-black/40" role="dialog" aria-modal="true">
    <div class="mx-4 w-full max-w-2xl rounded-2xl bg-white p-6 shadow-xl">
        <h3 class="mb-1 text-lg font-bold">Xác nhận công bố điểm chuẩn<?= $namKy ? ' năm ' . hCB($namKy) : '' ?></h3>
        <p class="mb-4 text-sm text-slate-600">Sau khi công bố, người dùng sẽ tra cứu được điểm chuẩn và không thể chỉnh sửa lại.</p>
        <div class="max-h-80 overflow-y-auto rounded-lg border border-slate-200">
            <table class="w-full text-sm text-left">
                <thead class="sticky top-0 bg-slate-50">
                    <tr><th class="p-2">Trường</th><th class="p-2">NV1</th><th class="p-2">NV2</th><th class="p-2">NV3</th></tr>
                </thead>
                <tbody>
                <?php foreach ($dsDiemChuan as $dc): ?>
                    <tr class="border-t border-slate-100">
                        <td class="p-2"><?= hCB($dc['tentruong']) ?></td>
                        <td class="p-2"><?= diemCB($dc['diemnv1']) ?></td>
                        <td class="p-2"><?= diemCB($dc['diemnv2']) ?></td>
                        <td class="p-2"><?= diemCB($dc['diemnv3']) ?></td>
                    </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        </div>
        <div class="mt-5 flex gap-3">
            <!-- 9.1: Hủy -> giữ nguyên trạng thái -->
            <button type="button" onclick="document.getElementById('popupXacNhanCB').remove()"
                    class="flex-1 rounded-lg border border-slate-300 px-5 py-2.5 font-semibold text-slate-700 hover:bg-slate-50">Hủy</button>
            <button type="button" onclick="document.getElementById('guiCongBo').click()"
                    class="flex-1 rounded-lg bg-blue-700 px-5 py-2.5 font-semibold text-white hover:bg-blue-800">Xác nhận công bố</button>
        </div>
    </div>
</div>
<?php endif; ?>

<!-- POPUP KẾT QUẢ -->
<?php if ($popup !== null): ?>
<div id="popupKetQuaCB" class="fixed inset-0 z-50 flex items-center justify-center bg-black/40" role="dialog" aria-modal="true">
    <div class="mx-4 w-full max-w-md rounded-2xl bg-white p-6 text-center shadow-xl">
        <div class="mx-auto mb-4 flex h-12 w-12 items-center justify-center rounded-full <?= $popup['thanhCong'] ? 'bg-green-100' : 'bg-red-100' ?>">
            <span class="text-2xl <?= $popup['thanhCong'] ? 'text-green-600' : 'text-red-600' ?>"><?= $popup['thanhCong'] ? '✓' : '!' ?></span>
        </div>
        <h3 class="mb-2 text-lg font-bold text-slate-800"><?= hCB($popup['tieuDe']) ?></h3>
        <p class="mb-3 text-sm text-slate-600"><?= hCB($popup['noiDung']) ?></p>
        <?php if (!empty($popup['dsLoi'])): ?>
            <!-- 6.2: danh sách trường chưa có điểm chuẩn -->
            <ul class="mb-4 max-h-48 overflow-y-auto rounded-lg bg-red-50 p-3 text-left text-sm text-red-700 list-disc list-inside">
                <?php foreach ($popup['dsLoi'] as $loi): ?>
                    <li><?= hCB($loi) ?></li>
                <?php endforeach; ?>
            </ul>
        <?php endif; ?>
        <button type="button" onclick="document.getElementById('popupKetQuaCB').remove()"
                class="w-full rounded-lg bg-blue-700 px-5 py-2.5 font-semibold text-white hover:bg-blue-800">Đóng</button>
    </div>
</div>
<?php endif; ?>

</body>
</html>
