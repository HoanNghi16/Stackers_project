<?php
use XetTuyen\CtrlXetTuyen;

if (session_status() !== PHP_SESSION_ACTIVE) {
    session_start();
}

require_once __DIR__ . '/../../../index.php';
require_once __DIR__ . '/../../../class/xet-tuyen/ctrlXetTuyen.php';
require_once __DIR__ . '/../../../components/layout/header.php';
require_once __DIR__ . '/../../../components/layout/sidebar.php';

// Kiểm tra quyền Sở GD&ĐT
if (
    ($env['DEV_FAKE_LOGIN'] ?? '') === '1'
    && empty($_SESSION['vaitro'])
) {
    $_SESSION['vaitro'] = 'SO_GD';
}

if (($_SESSION['vaitro'] ?? '') !== 'SO_GD') {
    header('Location: /stackers_project/pages/login');
    exit;
}

function hXT($value): string
{
    return htmlspecialchars(
        (string) $value,
        ENT_QUOTES,
        'UTF-8'
    );
}

function ngaySinhXT($value): string
{
    if (empty($value)) {
        return 'Chưa cập nhật';
    }

    $timestamp = strtotime((string) $value);

    return $timestamp !== false
        ? date('d/m/Y', $timestamp)
        : (string) $value;
}

if (empty($_SESSION['csrf_xettuyen'])) {
    $_SESSION['csrf_xettuyen'] = bin2hex(random_bytes(32));
}

/**
 * Trường hợp báo "đã xác nhận trước đó": kiểm tra chính xác kết quả đã nằm
 * trong MySQL trước khi thông báo thành công. Không ghi hay thay đổi DB.
 */
function daXacNhanDungTrongDbXT(mysqli $conn, int $maKy, array $dsKetQua): bool
{
    if ($maKy <= 0 || !$dsKetQua) {
        return false;
    }

    $stmt = $conn->prepare("
        SELECT nv.thutunguyenvong, nv.matruong, nv.trangthaixettuyen
        FROM nguyenvong nv
        JOIN hocsinh hs ON hs.mahocsinh = nv.mahocsinh
        WHERE hs.makythi = ? AND nv.mahocsinh = ?
        ORDER BY nv.thutunguyenvong
    ");
    try {
        $daXet = [];
        foreach ($dsKetQua as $hs) {
            $maHS = (int)($hs['mahocsinh'] ?? 0);
            if ($maHS <= 0 || isset($daXet[$maHS])) {
                return false;
            }
            $daXet[$maHS] = true;
            $nvDau = $hs['nvtrungtuyen'] ?? null;
            $dsNV = $hs['nguyenvong'] ?? [];
            if (!$dsNV) {
                return false;
            }
            $stmt->bind_param('ii', $maKy, $maHS);
            $stmt->execute();
            $rows = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
            if (count($rows) !== count($dsNV)) {
                return false;
            }
            foreach ($rows as $row) {
                $stt = (int)$row['thutunguyenvong'];
                if (!isset($dsNV[$stt]) || (int)$row['matruong'] !== (int)$dsNV[$stt]['matruong']) {
                    return false;
                }
                $mongDoi = ($nvDau !== null && $stt === (int)$nvDau) ? 'DAU' : 'ROT';
                if ((string)$row['trangthaixettuyen'] !== $mongDoi) {
                    return false;
                }
            }
        }
        return true;
    } finally {
        $stmt->close();
    }
}

$ctrl = new CtrlXetTuyen($conn);

$dsKy = [];
$dl = null;
$thongBao = '';
$popupPhanHoi = null; // Chỉ dùng cho Lưu tạm và Xác nhận kết quả

$hienDuLieu = false;
$hienKetQua = false;
$dsKetQua = [];

// Kỳ tuyển sinh cố định: năm 2026. Không lấy mã kỳ từ POST.
$maKy = 0;

try {
    $dsKy = $ctrl->yeuCauFormXetTuyen()['dsKy'];

    foreach ($dsKy as $ky) {
        if ((int)($ky['namtuyensinh'] ?? 0) === 2026) {
            $maKy = (int)$ky['makythi'];
            break;
        }
    }

    if ($maKy <= 0) {
        throw new RuntimeException('Không tìm thấy kỳ tuyển sinh năm 2026 trong database.');
    }

    if ($_SERVER['REQUEST_METHOD'] === 'POST') {

        $csrf = (string) ($_POST['csrf'] ?? '');

        if (!hash_equals($_SESSION['csrf_xettuyen'], $csrf)) {

            $thongBao = 'Phiên làm việc không hợp lệ.';

        } else {

            $hanhDong = (string) ($_POST['hanhDong'] ?? '');

            if ($hanhDong === 'tai') {

                $dl = $ctrl->taiDuLieu($maKy);

                $hienDuLieu = !empty($dl['thanhCong']);

            } elseif ($hanhDong === 'xet') {

                $dl = $ctrl->taiDuLieu($maKy);

                $hienDuLieu = !empty($dl['thanhCong']);

                if ($hienDuLieu) {
                    $kq = $ctrl->thucHienXetTuyen($maKy);
                    $hienKetQua = true;
                    $dsKetQua = $kq['dsKetQua'] ?? [];
                    // Thành công: chỉ hiển thị bảng kết quả, không hiện banner phía trên.
                    // Nếu xét tuyển thất bại, vẫn giữ thông báo lỗi.
                    $thongBao = !empty($kq['thanhCong'])
                        ? ''
                        : ($kq['thongBao'] ?? 'Không thể thực hiện xét tuyển.');
                }

            } elseif ($hanhDong === 'luu' || $hanhDong === 'xacNhan') {

                $dl = $ctrl->taiDuLieu($maKy);
                $hienDuLieu = !empty($dl['thanhCong']);

                if ($hienDuLieu) {
                    // Giữ bảng kết quả phía dưới sau khi gửi form.
                    $xet = $ctrl->thucHienXetTuyen($maKy);
                    $dsKetQua = $xet['dsKetQua'] ?? [];
                    $hienKetQua = true;

                    if (empty($xet['thanhCong'])) {
                        $kq = [
                            'thanhCong' => false,
                            'thongBao' => $xet['thongBao'] ?? 'Không thể tính kết quả xét tuyển.'
                        ];
                    } elseif ($hanhDong === 'luu') {
                        // Lưu bản nháp trong phiên PHP, không ghi vào MySQL.
                        // Chỉ thông báo thành công khi dữ liệu thực sự được đưa vào session.
                        $_SESSION['xettuyen_ban_nhap'][$maKy] = [
                            'dsKetQua' => $dsKetQua,
                            'luuLuc' => date('Y-m-d H:i:s'),
                        ];
                        $daLuu = isset($_SESSION['xettuyen_ban_nhap'][$maKy]['dsKetQua'])
                            && $_SESSION['xettuyen_ban_nhap'][$maKy]['dsKetQua'] === $dsKetQua;
                        $kq = [
                            'thanhCong' => $daLuu,
                            'thongBao' => $daLuu
                                ? 'Kết quả xét tuyển đã lưu tạm trong phiên làm việc (chưa ghi vào MySQL).'
                                : 'Không thể lưu bản nháp vào phiên làm việc.',
                        ];
                    } else {
                        // Xác nhận vẫn dùng nghiệp vụ ghi database ở Controller.
                        $kq = $ctrl->xacNhan($maKy);
                        // Nếu kết quả đã có trong MySQL và khớp toàn bộ
                        // thì xác nhận lặp lại vẫn được xem là thành công.
                        if (empty($kq['thanhCong'])
                            && str_contains((string)($kq['thongBao'] ?? ''), 'Kết quả đã được xác nhận trước đó.')) {
                            try {
                                if (daXacNhanDungTrongDbXT($conn, $maKy, $dsKetQua)) {
                                    $kq = [
                                        'thanhCong' => true,
                                        'thongBao' => 'Kết quả xét tuyển đã được xác nhận thành công.'
                                    ];
                                }
                            } catch (Throwable $kiemTraLoi) {
                                error_log('[XetTuyen:KiemTraDaXacNhan] ' . $kiemTraLoi->getMessage());
                            }
                        }
                    }

                    $popupPhanHoi = [
                        'thanhCong' => !empty($kq['thanhCong']),
                        'tieuDe' => !empty($kq['thanhCong'])
                            ? ($hanhDong === 'luu' ? 'Lưu tạm thành công' : 'Xác nhận thành công')
                            : ($hanhDong === 'luu' ? 'Không thể lưu tạm' : 'Không thể xác nhận kết quả'),
                        'noiDung' => $kq['thongBao'] ?? 'Thao tác không thành công.'
                    ];
                    // Chỉ hiển thị phản hồi qua popup, không hiện banner phía trên.
                    $thongBao = '';
                } else {
                    $popupPhanHoi = [
                        'thanhCong' => false,
                        'tieuDe' => 'Không thể xử lý kết quả',
                        'noiDung' => $dl['thongBao'] ?? 'Không tải được dữ liệu xét tuyển.'
                    ];
                    $thongBao = '';
                }
            }
        }
    }

} catch (Throwable $e) {

    error_log('[XetTuyenPage] ' . $e->getMessage());

    $thongBao = 'Không thể tải dữ liệu xét tuyển.';
    $hienDuLieu = false;
    $hienKetQua = false;
}

if (
    $dl !== null
    && !empty($dl['thongBao'])
    && $thongBao === ''
    && $popupPhanHoi === null
) {
    $thongBao = $dl['thongBao'];
}

$hoSo = $dl['dsHoSo'] ?? [];

$tieuChi = $dl['tieuChi'] ?? [
    'heSo' => [],
    'congDiem' => []
];
?>

<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport"
          content="width=device-width, initial-scale=1">

    <title>Stackers - Xét tuyển</title>

    <script src="https://cdn.tailwindcss.com"></script>
</head>

<!-- HỘP THOẠI XÁC NHẬN (trước khi ghi database) -->
<div id="popupHoiXacNhan" class="fixed inset-0 z-50 hidden items-center justify-center bg-black/40"
     role="dialog" aria-modal="true" aria-labelledby="tieuDeHoiXacNhan">
    <div class="w-full max-w-md rounded-2xl bg-white p-6 text-center shadow-xl mx-4">
        <div class="mx-auto mb-4 flex h-12 w-12 items-center justify-center rounded-full bg-blue-100">
            <span class="text-2xl text-blue-700">?</span>
        </div>
        <h3 id="tieuDeHoiXacNhan" class="mb-2 text-lg font-bold text-slate-800">Xác nhận kết quả xét tuyển</h3>
        <p class="mb-5 text-sm text-slate-600">Bạn muốn xác nhận kết quả xét tuyển?</p>
        <div class="flex gap-3">
            <button type="button" onclick="dongHoiXacNhan()"
                class="flex-1 rounded-lg border border-slate-300 px-5 py-2.5 font-semibold text-slate-700 hover:bg-slate-50">Hủy</button>
            <button type="button" onclick="guiDongYXacNhan()"
                class="flex-1 rounded-lg bg-blue-700 px-5 py-2.5 font-semibold text-white hover:bg-blue-800">OK</button>
        </div>
    </div>
</div>

<!-- POPUP KẾT QUẢ -->
<div id="popupXetTuyen"
     class="fixed inset-0 z-50 hidden
            items-center justify-center bg-black/40">

    <div class="w-full max-w-md rounded-2xl
                bg-white p-6 text-center shadow-xl">

        <div class="mx-auto mb-4 flex h-12 w-12
                    items-center justify-center
                    rounded-full" id="bieuTuongNenPopup">

            <span class="text-2xl" id="bieuTuongPopup">✓</span>

        </div>

        <h3 id="tieuDePopup"
            class="mb-2 text-lg font-bold text-slate-800">
        </h3>

        <p id="noiDungPopup"
           class="mb-5 text-sm text-slate-500">
        </p>

        <button type="button"
                onclick="dongPopupXetTuyen()"
                class="w-full rounded-lg bg-blue-700
                       px-5 py-2.5 font-semibold text-white
                       hover:bg-blue-800">
            Đóng
        </button>

    </div>
</div>

<script>
function moXacNhanXetTuyen() {
    const popup = document.getElementById('popupHoiXacNhan');
    popup.classList.remove('hidden');
    popup.classList.add('flex');
}
function dongHoiXacNhan() {
    const popup = document.getElementById('popupHoiXacNhan');
    popup.classList.add('hidden');
    popup.classList.remove('flex');
}
function guiDongYXacNhan() {
    dongHoiXacNhan();
    const nutGui = document.getElementById('guiXacNhanXetTuyen');
    if (nutGui) nutGui.click();
}
</script>

<script>
function hienPopupXetTuyen(tieuDe, noiDung, thanhCong = true) {
    document.getElementById('tieuDePopup').textContent = tieuDe;
    document.getElementById('noiDungPopup').textContent = noiDung;

    const nen = document.getElementById('bieuTuongNenPopup');
    const icon = document.getElementById('bieuTuongPopup');
    nen.classList.toggle('bg-green-100', thanhCong);
    nen.classList.toggle('bg-red-100', !thanhCong);
    icon.classList.toggle('text-green-600', thanhCong);
    icon.classList.toggle('text-red-600', !thanhCong);
    icon.textContent = thanhCong ? '✓' : '!';

    const popup = document.getElementById('popupXetTuyen');

    popup.classList.remove('hidden');
    popup.classList.add('flex');
}

function dongPopupXetTuyen() {
    const popup = document.getElementById('popupXetTuyen');

    popup.classList.add('hidden');
    popup.classList.remove('flex');
}

</script>

<?php if ($popupPhanHoi !== null): ?>
<script>
document.addEventListener('DOMContentLoaded', function () {
    hienPopupXetTuyen(
        <?= json_encode($popupPhanHoi['tieuDe'], JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT) ?>,
        <?= json_encode($popupPhanHoi['noiDung'], JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT) ?>,
        <?= !empty($popupPhanHoi['thanhCong']) ? 'true' : 'false' ?>
    );
});
</script>
<?php endif; ?>

<body class="bg-slate-50 text-slate-800">

<?php
renderHeader();
renderSidebar();
?>

<main class="pl-72 pt-20">

<div class="mx-auto max-w-7xl px-6 py-8 space-y-6">

    <!-- TIÊU ĐỀ -->
    <div>
        <p class="mb-2 text-sm text-slate-500">
            Sở GD&amp;ĐT / Tuyển sinh / Xét tuyển
        </p>

        <h1 class="text-2xl font-bold">
            Xét tuyển
        </h1>

        <p class="mt-1 text-sm text-slate-500">
            Quản lý hồ sơ, tiêu chí và kết quả
            xét tuyển vào lớp 10.
        </p>
    </div>

    <!-- THÔNG BÁO -->
    <?php if ($thongBao): ?>

        <div class="rounded-xl whitespace-pre-line border border-amber-300
                    bg-amber-50 px-5 py-4
                    text-sm text-amber-900">

            <?= hXT($thongBao) ?>

        </div>

    <?php endif; ?>

    <form method="post" class="space-y-6">

        <input
            type="hidden"
            name="csrf"
            value="<?= hXT($_SESSION['csrf_xettuyen']) ?>"
        >

        <!-- PHẦN 1: THÔNG TIN XÉT TUYỂN -->
        <section class="rounded-2xl border border-slate-200
                        bg-white shadow-sm p-5">

            <h2 class="font-semibold mb-4">
                Thông tin xét tuyển
            </h2>

            <div class="grid grid-cols-1 md:grid-cols-3 gap-4">

                <!-- KỲ TUYỂN SINH CỐ ĐỊNH -->
                <label class="text-sm">
                    Kỳ tuyển sinh
                    <input type="text" value="Năm 2026" readonly
                           class="mt-1 block w-full rounded-lg border border-slate-300
                                  bg-slate-50 px-3 py-2 text-slate-700 cursor-default">
                </label>

                <!-- PHƯƠNG THỨC CỐ ĐỊNH -->
                <label class="text-sm">
                    Phương thức xét tuyển
                    <input type="text" value="Thi tuyển" readonly
                           class="mt-1 block w-full rounded-lg border border-slate-300
                                  bg-slate-50 px-3 py-2 text-slate-700 cursor-default">
                </label>

                <!-- ĐỢT XÉT TUYỂN CỐ ĐỊNH -->
                <label class="text-sm">
                    Đợt xét tuyển
                    <input type="text" value="Đợt 1" readonly
                           class="mt-1 block w-full rounded-lg border border-slate-300
                                  bg-slate-50 px-3 py-2 text-slate-700 cursor-default">
                </label>

            </div>

            <!-- Mã kỳ để form tiếp tục xử lý; giá trị được kiểm tra trên máy chủ -->
            <input type="hidden" name="maKy" value="<?= (int)$maKy ?>">
            <input type="hidden" name="phuongThuc" value="THI_TUYEN">
            <input type="hidden" name="dot" value="1">

            <!-- NÚT TẢI DỮ LIỆU -->
            <div class="mt-5">

                <button
                    type="submit"
                    name="hanhDong"
                    value="tai"
                    class="rounded-lg bg-blue-700
                           px-5 py-2.5 text-sm
                           font-semibold text-white
                           hover:bg-blue-800"
                >
                    Tải dữ liệu xét tuyển
                </button>

            </div>

        </section>


        <!-- CHỈ HIỆN KHI NHẤN TẢI DỮ LIỆU -->
        <?php if ($hienDuLieu): ?>


        <!-- PHẦN 2: TIÊU CHÍ XÉT TUYỂN -->
        <section class="rounded-2xl border border-slate-200
                        bg-white shadow-sm overflow-hidden">

            <div class="border-b border-slate-200 px-5 py-3">

                <h2 class="font-semibold">
                    Tiêu chí xét tuyển
                </h2>

            </div>

            <div class="p-5 space-y-4 text-sm">

                <!-- HỆ SỐ MÔN THI -->
                <div>

                    <h3 class="font-medium mb-3">
                        Hệ số môn thi
                    </h3>

                    <?php if (!empty($tieuChi['heSo'])): ?>

                        <div class="flex flex-wrap gap-2">

                            <?php foreach (
                                $tieuChi['heSo'] as $hs
                            ): ?>

                                <span class="rounded-lg bg-blue-50
                                             px-3 py-2 text-blue-800">

                                    <?= hXT($hs['tenmonhoc']) ?>

                                    ×

                                    <?= hXT($hs['giatri']) ?>

                                </span>

                            <?php endforeach; ?>

                        </div>

                    <?php else: ?>

                        <p class="text-amber-700">
                            Chưa thiết lập hệ số môn thi.
                        </p>

                    <?php endif; ?>

                </div>

                <!-- ĐIỂM CỘNG -->
                <div>

                    <h3 class="font-medium mb-2">
                        Điểm ưu tiên / khuyến khích
                    </h3>

                    <?php if (!empty($tieuChi['congDiem'])): ?>

                        <?php foreach (
                            $tieuChi['congDiem'] as $tc
                        ): ?>

                            <p class="mb-1 text-slate-600">

                                <?= hXT($tc['loaitieuchuan']) ?>

                                –

                                <?= hXT($tc['dieukienapdung']) ?>

                                :

                                +<?= hXT($tc['diemcong']) ?>

                            </p>

                        <?php endforeach; ?>

                    <?php else: ?>

                        <p class="text-slate-500">
                            Chưa có tiêu chuẩn cộng điểm.
                        </p>

                    <?php endif; ?>

                </div>

                <div class="border-t pt-4">
                    <h3 class="font-medium mb-2">Chỉ tiêu tuyển sinh từ database</h3>
                    <?php if (!empty($dl['dsChiTieu'])): ?>
                        <div class="grid md:grid-cols-2 gap-2">
                        <?php foreach ($dl['dsChiTieu'] as $ct): ?>
                            <p class="rounded-lg bg-slate-50 p-2">
                                <?= hXT($ct['tentruong']) ?>:
                                <b><?= (int)$ct['chitieu'] ?></b> chỉ tiêu —
                                <?= (int)$ct['daduyet'] === 1 ? 'Đã duyệt' : 'Chưa duyệt' ?>
                            </p>
                        <?php endforeach; ?>
                        </div>
                    <?php else: ?>
                        <p class="text-amber-700">Chưa có chỉ tiêu tuyển sinh.</p>
                    <?php endif; ?>
                </div>

            </div>

        </section>


        <!-- PHẦN 3: DANH SÁCH HỒ SƠ THÍ SINH -->
        <section class="rounded-2xl border border-slate-200
                        bg-white shadow-sm overflow-hidden">

            <div class="flex items-center justify-between
                        border-b border-slate-200 px-5 py-3">

                <h2 class="font-semibold">
                    Danh sách hồ sơ thí sinh
                </h2>

                <span class="text-xs text-slate-500">
                    <?= count($hoSo) ?> hồ sơ
                </span>

            </div>

            <div class="overflow-x-auto">

                <table class="w-full text-sm">

                    <thead class="bg-slate-50 text-left
                                  text-slate-600">

                        <tr>
                            <th class="p-4">SBD</th>
                            <th class="p-4">Họ tên</th>
                            <th class="p-4">Trường đang học</th>
                            <th class="p-4">Nguyện vọng</th>
                            <th class="p-4">Trạng thái</th>
                            <th class="p-4">Chi tiết</th>
                        </tr>

                    </thead>

                    <tbody>

                    <?php foreach ($hoSo as $hs): ?>

                        <tr class="border-t border-slate-100
                                   hover:bg-slate-50">

                            <!-- SỐ BÁO DANH -->
                            <td class="p-4">

                                <?= hXT($hs['sobaodanh'] ?? '') ?>

                            </td>

                            <!-- HỌ TÊN -->
                            <td class="p-4 font-medium">

                                <?= hXT(trim(
                                    ($hs['ho'] ?? '')
                                    . ' '
                                    . ($hs['ten'] ?? '')
                                )) ?>

                            </td>

                            <!-- TRƯỜNG ĐANG HỌC -->
                            <td class="p-4">

                                <?= hXT(
                                    $hs['truongthcs'] ?? ''
                                ) ?>

                            </td>

                            <!-- NGUYỆN VỌNG -->
                            <td class="p-4">

                                <?php foreach (
                                    ($hs['nguyenvong'] ?? []) as $nv
                                ): ?>

                                    <div class="mb-1">

                                        <span class="font-medium">

                                            NV<?= hXT(
                                                $nv['thutunguyenvong']
                                            ) ?>:

                                        </span>

                                        <?= hXT(
                                            $nv['tentruong']
                                            ?? $nv['matruong']
                                        ) ?>

                                    </div>

                                <?php endforeach; ?>

                                <?php if (empty($hs['nguyenvong'])): ?>

                                    <span class="text-amber-700">
                                        Chưa có nguyện vọng
                                    </span>

                                <?php endif; ?>

                            </td>

                            <!-- TRẠNG THÁI -->
                            <td class="p-4">

                                
<?php
$dsNV = $hs['nguyenvong'] ?? [];

$daXet = false;
$trungTuyen = false;

foreach ($dsNV as $nv) {
    $trangThai = $nv['trangthaixettuyen'] ?? 'DANG_XET';

    if ($trangThai !== 'DANG_XET') {
        $daXet = true;
    }

    if ($trangThai === 'DAU') {
        $trungTuyen = true;
    }
}
?>

<?php if ($trungTuyen): ?>

    <span class="rounded-full bg-green-100
                 px-3 py-1 text-xs text-green-700">
        Đã xét - Trúng tuyển
    </span>

<?php elseif ($daXet): ?>

    <span class="rounded-full bg-slate-100
                 px-3 py-1 text-xs text-slate-700">
        Đã xét tuyển
    </span>

<?php else: ?>

    <span class="rounded-full bg-blue-50
                 px-3 py-1 text-xs text-blue-700">
        Đang xét tuyển
    </span>

<?php endif; ?>


                            </td>

                            <!-- NÚT XEM CHI TIẾT -->
                            <td class="p-4">

                                <button
                                    type="button"
                                    onclick="document.getElementById(
                                        'hs<?= (int) $hs['mahocsinh'] ?>'
                                    ).showModal()"
                                    class="rounded-lg border border-blue-200
                                           px-3 py-1.5 text-blue-700
                                           hover:bg-blue-50"
                                >
                                    Xem
                                </button>

                                <!-- HỘP THOẠI CHI TIẾT -->
                                <dialog
                                    id="hs<?= (int) $hs['mahocsinh'] ?>"
                                    class="w-full max-w-lg rounded-2xl
                                           p-6 shadow-2xl
                                           backdrop:bg-black/40"
                                >

                                    <h3 class="mb-5 text-lg font-bold">
                                        Chi tiết hồ sơ thí sinh
                                    </h3>

                                    <div class="space-y-3 text-sm">

                                        <!-- HỌ TÊN -->
                                        <p>
                                            <b>Họ tên:</b>

                                            <?= hXT(trim(
                                                ($hs['ho'] ?? '')
                                                . ' '
                                                . ($hs['ten'] ?? '')
                                            )) ?>
                                        </p>

                                        <!-- SỐ BÁO DANH -->
                                        <p>
                                            <b>Số báo danh:</b>

                                            <?= hXT(
                                                $hs['sobaodanh'] ?? ''
                                            ) ?>
                                        </p>

                                        <!-- NGÀY THÁNG NĂM SINH -->
                                        <p>
                                            <b>Ngày tháng năm sinh:</b>

                                            <?= hXT(
                                                ngaySinhXT(
                                                    $hs['ngaysinh'] ?? null
                                                )
                                            ) ?>
                                        </p>

                                        <!-- ĐIỂM THI TỪ DATABASE -->
                                        <div>
                                            <b>Điểm các môn thi:</b>
                                            <?php if (!empty($hs['diemthi'])): ?>
                                                <?php foreach ($hs['diemthi'] as $diem): ?>
                                                    <p class="mt-1">
                                                        <?= hXT($diem['tenmonhoc']) ?>:
                                                        <?= $diem['diem'] === null ? 'Chưa nhập' : hXT($diem['diem']) ?>
                                                    </p>
                                                <?php endforeach; ?>
                                            <?php else: ?>
                                                <p class="text-amber-700 mt-1">Chưa có điểm thi.</p>
                                            <?php endif; ?>
                                        </div>

                                        <!-- TRƯỜNG -->
                                        <p>
                                            <b>Trường đang học:</b>

                                            <?= hXT(
                                                $hs['truongthcs'] ?? ''
                                            ) ?>
                                        </p>

                                        <!-- NGUYỆN VỌNG -->
                                        <div>

                                            <b>Nguyện vọng đăng ký:</b>

                                            <?php if (
                                                !empty($hs['nguyenvong'])
                                            ): ?>

                                                <?php foreach (
                                                    $hs['nguyenvong'] as $nv
                                                ): ?>

                                                    <p class="mt-2">

                                                        NV<?= hXT(
                                                            $nv['thutunguyenvong']
                                                        ) ?>:

                                                        <?= hXT(
                                                            $nv['tentruong']
                                                            ?? $nv['matruong']
                                                        ) ?>

                                                    </p>

                                                <?php endforeach; ?>

                                            <?php else: ?>

                                                <p class="mt-2 text-amber-700">
                                                    Chưa đăng ký nguyện vọng.
                                                </p>

                                            <?php endif; ?>

                                        </div>

                                    </div>

                                    <!-- NÚT ĐÓNG -->
                                    <div class="mt-6 text-right">

                                        <button
                                            type="button"
                                            onclick="this.closest(
                                                'dialog'
                                            ).close()"
                                            class="rounded-lg bg-blue-700
                                                   px-5 py-2 text-white
                                                   hover:bg-blue-800"
                                        >
                                            Đóng
                                        </button>

                                    </div>

                                </dialog>

                            </td>

                        </tr>

                    <?php endforeach; ?>

                    <?php if (empty($hoSo)): ?>

                        <tr>
                            <td colspan="6"
                                class="p-8 text-center text-slate-500">

                                Không có hồ sơ trong kỳ tuyển sinh.

                            </td>
                        </tr>

                    <?php endif; ?>

                    </tbody>
                </table>

            </div>

        </section>


        <!-- NÚT THỰC HIỆN XÉT TUYỂN -->
        <div class="flex justify-end">

            <button
                type="submit"
                name="hanhDong"
                value="xet"
                <?= (empty($hoSo) || empty($tieuChi['heSo']))
                    ? 'disabled' : '' ?>
                class="rounded-lg bg-blue-700
                       px-6 py-3 text-sm
                       font-semibold text-white
                       shadow-sm hover:bg-blue-800
                       disabled:cursor-not-allowed
                       disabled:opacity-50"
            >
                Thực hiện xét tuyển
            </button>

        </div>

        <?php endif; ?>


        <!-- PHẦN 4: KẾT QUẢ XÉT TUYỂN -->
        <?php if ($hienKetQua): ?>
        <section class="rounded-2xl border border-slate-200 bg-white shadow-sm overflow-hidden">
            <div class="border-b border-slate-200 px-5 py-3">
                <h2 class="font-semibold">Kết quả xét tuyển dự kiến</h2>
                <p class="text-xs text-slate-500">Tính từ điểm, hệ số, nguyện vọng và chỉ tiêu trong database; chưa xác nhận chính thức.</p>
            </div>
            <?php if ($dsKetQua): ?>
                <div class="grid grid-cols-2 lg:grid-cols-4 gap-3 p-5 text-sm">
                    <div class="rounded-xl bg-slate-50 p-3">Tổng hồ sơ<br><strong class="text-xl"><?= count($dsKetQua) ?></strong></div>
                    <div class="rounded-xl bg-green-50 p-3">Trúng tuyển dự kiến<br><strong class="text-xl"><?= count(array_filter($dsKetQua, static fn($r) => $r['trangthai'] === 'TRUNG_TUYEN')) ?></strong></div>
                    <div class="rounded-xl bg-red-50 p-3">Không trúng tuyển dự kiến<br><strong class="text-xl"><?= count(array_filter($dsKetQua, static fn($r) => $r['trangthai'] === 'KHONG_TRUNG_TUYEN')) ?></strong></div>
                    <div class="rounded-xl bg-amber-50 p-3">Chưa đủ điều kiện xét<br><strong class="text-xl"><?= count(array_filter($dsKetQua, static fn($r) => $r['trangthai'] === 'CHUA_DU_DIEU_KIEN')) ?></strong></div>
                </div>
                <div class="overflow-x-auto">
                    <table class="w-full text-sm text-left">
                        <thead class="bg-slate-50"><tr>
                            <th class="p-3">SBD</th><th class="p-3">Họ tên</th>
                            <th class="p-3">Điểm môn thi</th><th class="p-3">Tổng điểm</th>
                            <th class="p-3">Nguyện vọng</th><th class="p-3">Trường trúng tuyển</th>
                            <th class="p-3">Trạng thái</th>
                        </tr></thead><tbody>
                        <?php foreach ($dsKetQua as $r): ?>
                            <tr class="border-t border-slate-100">
                                <td class="p-3"><?= hXT($r['sobaodanh']) ?></td>
                                <td class="p-3 font-medium"><?= hXT($r['hoten']) ?></td>
                                <td class="p-3">
                                    <?php foreach ($r['diemmon'] as $m): ?>
                                        <div><?= hXT($m['mon']) ?>: <?= hXT($m['diem']) ?></div>
                                    <?php endforeach; ?>
                                </td>
                                <td class="p-3 font-semibold"><?= number_format((float)$r['tongdiem'], 2, '.', '') ?></td>
                                <td class="p-3"><?= $r['nvtrungtuyen'] === null ? '—' : 'NV' . (int)$r['nvtrungtuyen'] ?></td>
                                <td class="p-3"><?= hXT($r['truongtrungtuyen'] ?: '—') ?></td>
                                <td class="p-3"><?= $r['trangthai'] === 'TRUNG_TUYEN' ? 'Trúng tuyển dự kiến' : ($r['trangthai'] === 'CHUA_DU_DIEU_KIEN' ? 'Chưa đủ điều kiện xét' : 'Không trúng tuyển dự kiến') ?></td>
                            </tr>
                        <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php else: ?>
                <p class="p-5 text-sm text-amber-700">Chưa tạo được kết quả. Xem thông báo lỗi phía trên để biết dữ liệu cần bổ sung.</p>
            <?php endif; ?>
            <div class="mt-5 flex justify-end gap-3">

    <button
        type="submit"
        name="hanhDong"
        value="luu"
        class="rounded-lg border border-blue-600
               px-5 py-2 text-sm font-semibold
               text-blue-700 hover:bg-blue-50">
        Lưu tạm
    </button>

    <button
        type="button"
        onclick="moXacNhanXetTuyen()"
        class="rounded-lg bg-blue-700
               px-5 py-2 text-sm font-semibold
               text-white hover:bg-blue-800">
        Xác nhận kết quả
    </button>
    <button id="guiXacNhanXetTuyen" type="submit"
            name="hanhDong" value="xacNhan" class="hidden">
    </button>

</div>
        </section>
        <?php endif; ?>

    </form>

</div>
</main>

</body>
