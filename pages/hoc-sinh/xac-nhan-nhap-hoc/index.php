<?php
/**
 * Use case: Xác nhận nhập học (Actor: Học sinh).
 * GiaoDienXacNhanNhapHoc: chỉ dựng giao diện, nghiệp vụ nằm ở CtrlXacNhanNhapHoc.
 */

use XacNhanNhapHoc\CtrlXacNhanNhapHoc;

session_start();

require __DIR__ . '/../../../index.php';                         // tạo $conn (và $env)
require __DIR__ . '/../../../class/xac-nhan-nhap-hoc/ctrlXacNhanNhapHoc.php';
require __DIR__ . '/../../../components/layout/header.php';

// Tiền điều kiện: học sinh đã đăng nhập.
if (($env['DEV_FAKE_LOGIN'] ?? '') === '1' && empty($_SESSION['vaitro'])) {
    $_SESSION['vaitro'] = 'HOC_SINH';   // chỉ để chạy thử, KHÔNG bật trên môi trường thật
}
if (($_SESSION['vaitro'] ?? null) !== 'HOC_SINH' || empty($_SESSION['manguoidung'])) {
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

$ctrl  = new CtrlXacNhanNhapHoc($conn);
$modal = null;   // ['loai' => thanh_cong|loi, 'tieuDe' => ..., 'dong' => [...]]

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!hash_equals($_SESSION['csrf'], (string) ($_POST['csrf'] ?? ''))) {
        $_SESSION['flash_xnnh'] = ['loai' => 'loi', 'tieuDe' => 'Không thể xác nhận nhập học', 'dong' => ['Phiên làm việc đã hết hạn. Vui lòng thử lại.']];
    } else {
        $kq = $ctrl->xacNhanNhapHoc($maNguoiDung);
        $_SESSION['flash_xnnh'] = $kq['trangThai'] === 'thanh_cong'
            ? ['loai' => 'thanh_cong', 'tieuDe' => 'Thành công', 'dong' => [$kq['thongBao']]]
            : ['loai' => 'loi', 'tieuDe' => 'Không thể xác nhận nhập học', 'dong' => [$kq['thongBao']]];
    }
    header('Location: ' . strtok($_SERVER['REQUEST_URI'], '?'));   // PRG
    exit;
}
if (!empty($_SESSION['flash_xnnh'])) {
    $modal = $_SESSION['flash_xnnh'];
    unset($_SESSION['flash_xnnh']);
}

$d = $ctrl->yeuCauThongTinXacNhan($maNguoiDung);
?>
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Stackers - Xác nhận nhập học</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="min-h-screen bg-slate-100 text-slate-800">
<?php renderHeader(); ?>

<main class="mx-auto max-w-3xl px-4 pb-12 pt-28">
    <div>
        <a href="/stackers_project/pages/hoc-sinh/" class="mb-4 inline-block text-sm font-medium text-indigo-700 hover:underline">&larr; Về trang chức năng</a>
        <div class="mb-6 overflow-hidden rounded-2xl border border-slate-200/80 bg-gradient-to-br from-white via-white to-indigo-50/70 p-5 shadow-sm sm:p-7">
            <div class="mb-3 inline-flex items-center gap-2 rounded-full border border-indigo-100 bg-white/90 px-3.5 py-1.5 shadow-sm">
                <span class="h-2 w-2 rounded-full bg-emerald-500"></span>
                <span class="text-[11px] font-bold uppercase tracking-[0.16em] text-indigo-700">Cổng thông tin học sinh</span>
            </div>
            <div class="flex items-start gap-3">
                <div class="hidden h-12 w-1 shrink-0 rounded-full bg-gradient-to-b from-indigo-500 to-cyan-400 sm:block"></div>
                <div>
                    <h1 class="text-2xl font-extrabold tracking-tight sm:text-3xl">Xác nhận nhập học</h1>
                    <p class="mt-1 text-sm text-slate-500">Xác nhận đồng ý nhập học chính thức tại trường THPT bạn đã trúng tuyển.</p>
                </div>
            </div>
        </div>

        <?php if ($d['trangThai'] === 'khong_trung_tuyen'): ?>
            <div class="mt-6 rounded-2xl border border-amber-300 bg-amber-50 px-5 py-4 text-sm text-amber-900"><?= h($d['thongBao']) ?></div>
        <?php elseif ($d['trangThai'] === 'loi_he_thong'): ?>
            <div class="mt-6 rounded-2xl border border-red-200 bg-red-50 px-5 py-4 text-sm text-red-700"><?= h($d['thongBao']) ?></div>
        <?php else:
            $han = DateTime::createFromFormat('Y-m-d', $d['han']);
            $t = $d['truong'];
        ?>
        <section class="mt-6 rounded-2xl border border-slate-200 bg-white p-6 shadow-sm">
            <div class="flex flex-wrap items-start justify-between gap-3">
                <div>
                    <div class="text-xs uppercase tracking-wide text-slate-400">Trường trúng tuyển</div>
                    <div class="text-xl font-bold"><?= h($t['tentruong']) ?></div>
                    <div class="mt-1 text-sm text-slate-500">Nguyện vọng <?= (int) $d['nguyenVong'] ?> · Thí sinh <?= h($d['hocSinh']['hoten']) ?> (SBD <?= h($d['hocSinh']['sobaodanh']) ?>)</div>
                </div>
                <?php if ($d['trangThai'] === 'da_xac_nhan'): ?>
                    <span class="rounded-full border border-green-200 bg-green-50 px-3 py-1 text-xs font-semibold text-green-700">Đã xác nhận nhập học</span>
                <?php elseif ($d['trangThai'] === 'het_han'): ?>
                    <span class="rounded-full border border-red-200 bg-red-50 px-3 py-1 text-xs font-semibold text-red-700">Đã hết hạn xác nhận</span>
                <?php else: ?>
                    <span class="rounded-full border border-amber-200 bg-amber-50 px-3 py-1 text-xs font-semibold text-amber-700">Chưa xác nhận · hạn <?= $han ? h($han->format('d/m/Y')) : '' ?></span>
                <?php endif; ?>
            </div>

            <!-- Luồng 3.1: Xem chi tiết trường -->
            <button type="button" id="btnChiTiet" aria-expanded="false" aria-controls="chiTietTruong"
                    class="mt-5 text-sm font-semibold text-blue-700 hover:underline">Xem chi tiết trường</button>
            <dl id="chiTietTruong" class="mt-3 hidden grid-cols-1 gap-3 rounded-lg border border-slate-200 bg-slate-50 p-4 text-sm sm:grid-cols-2">
                <div><dt class="text-xs uppercase tracking-wide text-slate-400">Địa chỉ</dt><dd class="mt-1 font-medium"><?= h($t['diachi']) ?></dd></div>
                <div><dt class="text-xs uppercase tracking-wide text-slate-400">Phường</dt><dd class="mt-1 font-medium"><?= h($t['tenphuong'] ?? '—') ?></dd></div>
            </dl>

            <?php if ($d['trangThai'] === 'san_sang'): ?>
            <form method="post" id="formXacNhan" class="mt-6">
                <input type="hidden" name="csrf" value="<?= h($_SESSION['csrf']) ?>">
                <button type="submit" id="btnXacNhan" class="rounded-lg bg-blue-700 px-6 py-2.5 text-sm font-semibold text-white hover:bg-blue-800 disabled:opacity-60">Xác nhận nhập học</button>
                <p class="mt-2 text-xs text-slate-500">Sau khi xác nhận, bạn sẽ được đưa vào danh sách nhập học của trường và không thể xác nhận ở trường khác.</p>
            </form>
            <?php elseif ($d['trangThai'] === 'het_han'): ?>
                <div class="mt-6 rounded-lg border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-700">Đã hết thời hạn xác nhận nhập học.</div>
            <?php else: ?>
                <div class="mt-6 rounded-lg border border-green-200 bg-green-50 px-4 py-3 text-sm text-green-700">Bạn đã xác nhận nhập học tại trường này.</div>
            <?php endif; ?>
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

<?php if ($d['trangThai'] === 'san_sang'): ?>
<div id="modalXacNhan" class="fixed inset-0 z-[1000] hidden items-center justify-center bg-slate-900/50 px-4"
     role="dialog" aria-modal="true" aria-labelledby="xnTieuDe">
    <div class="w-full max-w-md rounded-2xl bg-white shadow-2xl">
        <div class="px-6 pb-2 pt-7 text-center">
            <div class="mx-auto flex h-14 w-14 items-center justify-center rounded-full bg-blue-100 text-blue-600">
                <svg class="h-7 w-7" fill="none" stroke="currentColor" stroke-width="2.2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M12 9v4m0 4h.01M10.3 3.9L2.4 17.5A2 2 0 004.1 20.5h15.8a2 2 0 001.7-3L13.7 3.9a2 2 0 00-3.4 0z"/></svg>
            </div>
            <h2 id="xnTieuDe" class="mt-4 text-lg font-bold text-slate-900">Xác nhận nhập học?</h2>
            <p class="mt-2 text-sm text-slate-600">Bạn sẽ nhập học chính thức tại <b><?= h($d['truong']['tentruong']) ?></b>.</p>
            <p class="mt-1 text-sm text-slate-500">Sau khi xác nhận, bạn không thể xác nhận ở trường khác.</p>
        </div>
        <div class="flex gap-3 px-6 pb-6 pt-5">
            <button type="button" id="btnHuy" class="flex-1 rounded-lg border border-slate-300 px-4 py-2.5 text-sm font-semibold text-slate-700 hover:bg-slate-50">Hủy</button>
            <button type="button" id="btnDongY" class="flex-1 rounded-lg bg-blue-700 px-4 py-2.5 text-sm font-semibold text-white hover:bg-blue-800 disabled:opacity-60">Xác nhận nhập học</button>
        </div>
    </div>
</div>
<?php endif; ?>

<script>
(() => {
    const btn = document.getElementById('btnChiTiet'), box = document.getElementById('chiTietTruong');
    if (btn && box) btn.addEventListener('click', () => {
        const mo = box.classList.toggle('hidden') === false;
        box.classList.toggle('grid', mo);
        btn.setAttribute('aria-expanded', mo ? 'true' : 'false');
        btn.textContent = mo ? 'Ẩn chi tiết trường' : 'Xem chi tiết trường';
    });
    // Hộp xác nhận tự dựng (thay cho confirm() mặc định của trình duyệt)
    const form = document.getElementById('formXacNhan'), hop = document.getElementById('modalXacNhan');
    if (form && hop) {
        const btnOk = document.getElementById('btnDongY'), btnHuy = document.getElementById('btnHuy');
        const dong = () => { hop.classList.add('hidden'); hop.classList.remove('flex'); };
        form.addEventListener('submit', e => { e.preventDefault(); hop.classList.remove('hidden'); hop.classList.add('flex'); btnHuy.focus(); });
        btnHuy.addEventListener('click', dong);
        hop.addEventListener('click', e => { if (e.target === hop) dong(); });
        document.addEventListener('keydown', e => { if (e.key === 'Escape' && !hop.classList.contains('hidden')) dong(); });
        btnOk.addEventListener('click', () => { btnOk.disabled = true; btnHuy.disabled = true; form.submit(); });   // chống bấm đúp
    }
})();
</script>
</body>
</html>
