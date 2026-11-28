<?php
/**
 * Use case: Đăng ký chỉ tiêu (Actor: Trường THPT; Sở GD&ĐT phê duyệt ở use case khác).
 * Trang này là GiaoDien: chỉ dựng giao diện, nghiệp vụ nằm ở CtrlDangKyChiTieu.
 */

use DangKyChiTieu\CtrlDangKyChiTieu;

session_start();

require __DIR__ . '/../../../index.php';                         // tạo $conn (và $env)
require __DIR__ . '/../../../class/dang-ky-chi-tieu/ctrlDangKyChiTieu.php';
require __DIR__ . '/../../../components/layout/header.php';
require __DIR__ . '/../../../components/layout/sidebar.php';

// Tiền điều kiện: đã đăng nhập với vai trò Trường THPT.
if (($env['DEV_FAKE_LOGIN'] ?? '') === '1' && empty($_SESSION['vaitro'])) {
    $_SESSION['vaitro'] = 'THPT';   // chỉ để chạy thử, KHÔNG bật trên môi trường thật
}
if (($_SESSION['vaitro'] ?? null) !== 'THPT' || empty($_SESSION['manguoidung'])) {
    header('Location: /stackers_project/pages/login');
    exit;
}
$maNguoiDung = (int) $_SESSION['manguoidung'];

function h(?string $s): string
{
    return htmlspecialchars((string) $s, ENT_QUOTES, 'UTF-8');
}

if (empty($_SESSION['csrf'])) {
    $_SESSION['csrf'] = bin2hex(random_bytes(32));
}

$ctrl  = new CtrlDangKyChiTieu($conn);
$modal = null;          // ['loai' => thanh_cong|loi, 'tieuDe' => ..., 'dong' => [...]]
$giaTriNhap = null;     // giữ lại số người dùng vừa nhập khi lỗi

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $soLuong = (string) ($_POST['chitieu'] ?? '');
    if (!hash_equals($_SESSION['csrf'], (string) ($_POST['csrf'] ?? ''))) {
        $modal = ['loai' => 'loi', 'tieuDe' => 'Không thể đăng ký chỉ tiêu', 'dong' => ['Phiên làm việc đã hết hạn. Vui lòng thử lại.']];
        $giaTriNhap = $soLuong;
    } else {
        $kq = $ctrl->dangKyChiTieu($maNguoiDung, $soLuong);
        if ($kq['trangThai'] === 'thanh_cong') {
            $_SESSION['flash_chitieu'] = $kq['thongBao'];
            header('Location: ' . strtok($_SERVER['REQUEST_URI'], '?'));   // PRG
            exit;
        }
        $modal = ['loai' => 'loi', 'tieuDe' => 'Không thể đăng ký chỉ tiêu', 'dong' => [$kq['thongBao']]];
        $giaTriNhap = $soLuong;
    }
}
if (!empty($_SESSION['flash_chitieu'])) {
    $modal = ['loai' => 'thanh_cong', 'tieuDe' => 'Thành công', 'dong' => [$_SESSION['flash_chitieu']]];
    unset($_SESSION['flash_chitieu']);
}

$d = $ctrl->yeuCauFormDangKy($maNguoiDung);
$nhanTT = [
    'chua_dang_ky' => ['Chưa đăng ký', 'border-slate-200 bg-slate-100 text-slate-600'],
    'cho_duyet'    => ['Chờ duyệt',    'border-amber-200 bg-amber-50 text-amber-700'],
    'da_duyet'     => ['Đã duyệt',     'border-green-200 bg-green-50 text-green-700'],
];
?>
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Stackers - Đăng ký chỉ tiêu</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-slate-50 text-slate-800">
<?php renderHeader(); renderSidebar(); ?>

<main class="pl-72 pt-20">
    <div class="mx-auto max-w-7xl px-6 py-8">
        <h1 class="text-2xl font-bold">Đăng ký chỉ tiêu</h1>
        <p class="mt-1 text-sm text-slate-500">Đăng ký tổng chỉ tiêu tuyển sinh lớp 10 của trường cho kỳ tuyển sinh hiện tại. Chỉ tiêu cần được Sở GD&amp;ĐT phê duyệt.</p>

        <?php if ($d['trangThai'] === 'khong_co_truong'): ?>
            <div class="mt-6 rounded-2xl border border-amber-300 bg-amber-50 px-5 py-4 text-sm text-amber-900">Tài khoản chưa được gán với trường THPT nào. Vui lòng liên hệ quản trị viên.</div>
        <?php elseif ($d['trangThai'] === 'loi_he_thong'): ?>
            <div class="mt-6 rounded-2xl border border-red-200 bg-red-50 px-5 py-4 text-sm text-red-700">Không thể tải dữ liệu, vui lòng thử lại sau.</div>
        <?php elseif ($d['trangThai'] === 'chua_co_ky'): ?>
            <div class="mt-6 rounded-2xl border border-amber-300 bg-amber-50 px-5 py-4 text-sm text-amber-900">Hệ thống chưa có kỳ tuyển sinh của năm <?= (int) $d['nam'] ?>.</div>
        <?php else:
            [$nhan, $lop] = $nhanTT[$d['trangThaiChiTieu']];
            $giaTri = $giaTriNhap ?? ($d['chiTieu'] !== null ? (string) $d['chiTieu'] : '');
            $han = DateTime::createFromFormat('Y-m-d', $d['ky']['hanchotdangkychitieu'])->format('d/m/Y');
        ?>
        <section class="mt-6 rounded-2xl border border-slate-200 bg-white p-6 shadow-sm">
            <div class="flex flex-wrap items-start justify-between gap-3">
                <div>
                    <div class="text-xs uppercase tracking-wide text-slate-400">Trường</div>
                    <div class="text-lg font-semibold"><?= h($d['truong']['tentruong']) ?></div>
                    <div class="text-sm text-slate-500"><?= h($d['truong']['diachi']) ?></div>
                </div>
                <div class="text-right text-sm">
                    <div class="text-slate-500">Kỳ tuyển sinh <?= (int) $d['ky']['namtuyensinh'] ?></div>
                    <div class="text-slate-500">Hạn đăng ký: <b><?= h($han) ?></b></div>
                    <span class="mt-2 inline-block rounded-full border px-3 py-1 text-xs font-semibold <?= $lop ?>"><?= h($nhan) ?></span>
                </div>
            </div>

            <?php if (!$d['conHan'] && $d['trangThaiChiTieu'] !== 'da_duyet'): ?>
                <div class="mt-5 rounded-lg border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-700">Đã hết thời gian đăng ký chỉ tiêu.</div>
            <?php elseif ($d['trangThaiChiTieu'] === 'da_duyet'): ?>
                <div class="mt-5 rounded-lg border border-green-200 bg-green-50 px-4 py-3 text-sm text-green-700">Chỉ tiêu đã được Sở GD&amp;ĐT phê duyệt, không thể thay đổi.</div>
            <?php endif; ?>

            <form method="post" id="formChiTieu" class="mt-6" novalidate>
                <input type="hidden" name="csrf" value="<?= h($_SESSION['csrf']) ?>">
                <label for="chitieu" class="block text-sm font-medium">Chỉ tiêu tuyển sinh (số học sinh)</label>
                <input type="text" inputmode="numeric" id="chitieu" name="chitieu" value="<?= h($giaTri) ?>" maxlength="9"
                       <?= $d['choPhepNhap'] ? '' : 'disabled' ?>
                       class="mt-2 w-full rounded-lg border border-slate-300 px-4 py-2.5 text-sm focus:border-blue-500 focus:outline-none focus:ring-2 focus:ring-blue-300 disabled:bg-slate-100 disabled:text-slate-500">
                <?php if ($d['choPhepNhap']): ?>
                <button type="submit" class="mt-4 rounded-lg bg-blue-700 px-5 py-2.5 text-sm font-semibold text-white hover:bg-blue-800">
                    <?= $d['trangThaiChiTieu'] === 'chua_dang_ky' ? 'Đăng ký chỉ tiêu' : 'Cập nhật chỉ tiêu' ?>
                </button>
                <?php endif; ?>
            </form>
        </section>
        <?php endif; ?>
    </div>
</main>

<div id="modalKetQua"
     class="fixed inset-0 z-[1000] flex items-center justify-center bg-slate-900/50 px-4 <?= $modal ? '' : 'hidden' ?>"
     role="dialog" aria-modal="true" aria-labelledby="modalTieuDe">
    <div class="w-full max-w-md rounded-2xl bg-white shadow-2xl">
        <div class="px-6 pb-2 pt-7 text-center">
            <div id="modalIconThanhCong"
                 class="mx-auto flex h-14 w-14 items-center justify-center rounded-full bg-green-100 text-green-600 <?= ($modal['loai'] ?? '') === 'thanh_cong' ? '' : 'hidden' ?>">
                <svg class="h-7 w-7" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7"/></svg>
            </div>
            <div id="modalIconLoi"
                 class="mx-auto flex h-14 w-14 items-center justify-center rounded-full bg-red-100 text-red-600 <?= ($modal['loai'] ?? 'loi') === 'loi' ? '' : 'hidden' ?>">
                <svg class="h-7 w-7" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M12 8v5m0 3.5h.01M10.3 3.9L2.4 17.6A2 2 0 004.1 20.5h15.8a2 2 0 001.7-2.9L13.7 3.9a2 2 0 00-3.4 0z"/></svg>
            </div>
            <h3 id="modalTieuDe" class="mt-4 text-lg font-bold text-slate-800"><?= h($modal['tieuDe'] ?? '') ?></h3>
            <div id="modalNoiDung" class="mt-2 max-h-60 overflow-y-auto text-sm text-slate-600">
                <?php if ($modal && $modal['dong']): ?>
                    <?php if ($modal['loai'] === 'thanh_cong'): ?>
                        <p><?= h($modal['dong'][0]) ?></p>
                    <?php else: ?>
                        <ul class="list-disc space-y-1 pl-5 text-left">
                            <?php foreach ($modal['dong'] as $dong): ?><li><?= h($dong) ?></li><?php endforeach; ?>
                        </ul>
                    <?php endif; ?>
                <?php endif; ?>
            </div>
        </div>
        <div class="px-6 pb-6 pt-4">
            <button type="button" id="modalDong"
                    class="w-full rounded-lg bg-blue-700 px-4 py-2.5 text-sm font-semibold text-white transition hover:bg-blue-800 focus:outline-none focus:ring-2 focus:ring-blue-400">
                Đóng
            </button>
        </div>
    </div>
</div>

<script>
// Modal kết quả dùng chung: server mở sẵn khi có thông báo, JS gọi hienModal() khi kiểm tra phía client thất bại.
(() => {
    const modal = document.getElementById('modalKetQua');
    const btnDong = document.getElementById('modalDong');
    let focusTruoc = null;

    window.hienModal = function (loai, tieuDe, dong) {
        const thanhCong = loai === 'thanh_cong';
        document.getElementById('modalIconThanhCong').classList.toggle('hidden', !thanhCong);
        document.getElementById('modalIconLoi').classList.toggle('hidden', thanhCong);
        document.getElementById('modalTieuDe').textContent = tieuDe;

        const noiDung = document.getElementById('modalNoiDung');
        noiDung.innerHTML = '';
        if (dong && dong.length) {
            if (thanhCong) {
                const p = document.createElement('p'); p.textContent = dong[0]; noiDung.appendChild(p);
            } else {
                const ul = document.createElement('ul');
                ul.className = 'list-disc space-y-1 pl-5 text-left';
                dong.forEach(m => { const li = document.createElement('li'); li.textContent = m; ul.appendChild(li); });
                noiDung.appendChild(ul);
            }
        }
        focusTruoc = document.activeElement;
        modal.classList.remove('hidden');
        btnDong.focus();
    };

    function dongModal() {
        modal.classList.add('hidden');
        if (focusTruoc && focusTruoc.focus) focusTruoc.focus();
    }

    btnDong.addEventListener('click', dongModal);
    modal.addEventListener('click', e => { if (e.target === modal) dongModal(); });   // bấm ra ngoài để đóng
    document.addEventListener('keydown', e => { if (e.key === 'Escape' && !modal.classList.contains('hidden')) dongModal(); });

    if (!modal.classList.contains('hidden')) btnDong.focus();
})();
</script>

<script>
(() => {
    const form = document.getElementById('formChiTieu');
    if (!form) return;
    form.addEventListener('submit', e => {
        const v = document.getElementById('chitieu').value.trim();
        let loi = null;
        if (v === '') loi = 'Vui lòng nhập chỉ tiêu tuyển sinh.';
        else if (!/^[1-9]\d{0,8}$/.test(v)) loi = 'Chỉ tiêu tuyển sinh phải là số nguyên dương.';
        if (loi) { e.preventDefault(); hienModal('loi', 'Không thể đăng ký chỉ tiêu', [loi]); }
    });
})();
</script>
</body>
</html>
