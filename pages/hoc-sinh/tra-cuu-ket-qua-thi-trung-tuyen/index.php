<?php
/**
 * Use case: Tra cứu kết quả thi/trúng tuyển (Actor: Học sinh).
 * GiaoDienTraCuu: chỉ dựng giao diện, nghiệp vụ nằm ở CtrlTraCuu.
 */

use TraCuuKetQua\CtrlTraCuu;

session_start();

require __DIR__ . '/../../../index.php';                         // tạo $conn (và $env)
require __DIR__ . '/../../../class/tra-cuu-ket-qua/ctrlTraCuu.php';
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

/** "8.50" -> "8.5", "9.00" -> "9" */
function fmtDiem(?string $v): string
{
    if ($v === null || $v === '') return '—';
    return str_contains($v, '.') ? rtrim(rtrim($v, '0'), '.') : $v;
}

$ctrl   = new CtrlTraCuu($conn);
$sbd    = trim((string) ($_GET['sbd'] ?? ''));
$daTim  = isset($_GET['sbd']);
$kq     = null;
$modal  = null;

if ($daTim) {
    $kq = $ctrl->traCuu($maNguoiDung, $sbd);
    if ($kq['trangThai'] !== 'ok') {
        $modal = $kq['trangThai'] === 'khong_tim_thay'
            ? ['loai' => 'loi', 'tieuDe' => 'Không tìm thấy số báo danh', 'dong' => $kq['chiTiet']]
            : ['loai' => 'loi', 'tieuDe' => 'Không thể tra cứu', 'dong' => [$kq['thongBao']]];
    }
}
?>
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Stackers - Tra cứu kết quả thi/trúng tuyển</title>
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
                    <h1 class="text-2xl font-extrabold tracking-tight sm:text-3xl">Tra cứu kết quả thi/trúng tuyển</h1>
                    <p class="mt-1 text-sm text-slate-500">Nhập số báo danh của bạn để xem điểm thi từng môn và kết quả xét tuyển lớp 10.</p>
                </div>
            </div>
        </div>

        <form method="get" class="mt-6 flex flex-wrap items-end gap-3 rounded-2xl border border-slate-200 bg-white p-5 shadow-sm">
            <div class="min-w-[16rem] flex-1">
                <label for="sbd" class="block text-sm font-medium">Số báo danh</label>
                <input type="text" id="sbd" name="sbd" value="<?= h($sbd) ?>" maxlength="20" autocomplete="off" required
                       placeholder="Ví dụ: 26010012"
                       class="mt-1 w-full rounded-lg border border-slate-300 px-4 py-2.5 text-sm focus:border-blue-500 focus:outline-none focus:ring-2 focus:ring-blue-300">
            </div>
            <button type="submit" class="rounded-lg bg-blue-700 px-5 py-2.5 text-sm font-semibold text-white hover:bg-blue-800">Tra cứu</button>
        </form>

        <?php if ($kq && $kq['trangThai'] === 'ok'):
            $hs = $kq['hocSinh']; $dt = $kq['diemThi']; $xt = $kq['xetTuyen'];
            $ns = $hs['ngaysinh'] ? DateTime::createFromFormat('Y-m-d', $hs['ngaysinh']) : false;
        ?>
        <section class="mt-6 rounded-2xl border border-slate-200 bg-white p-6 shadow-sm">
            <div class="grid gap-4 sm:grid-cols-3">
                <div><div class="text-xs uppercase tracking-wide text-slate-400">Họ tên</div><div class="mt-1 font-semibold"><?= h($hs['hoten']) ?></div></div>
                <div><div class="text-xs uppercase tracking-wide text-slate-400">Số báo danh</div><div class="mt-1 font-semibold"><?= h($hs['sobaodanh']) ?></div></div>
                <div><div class="text-xs uppercase tracking-wide text-slate-400">Ngày sinh</div><div class="mt-1 font-semibold"><?= $ns ? h($ns->format('d/m/Y')) : '—' ?></div></div>
            </div>
        </section>

        <section class="mt-6 overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm">
            <div class="border-b border-slate-100 px-6 py-4"><h2 class="font-semibold">Điểm thi</h2></div>
            <?php if (!$dt['congBo']): ?>
                <div class="px-6 py-6 text-sm text-amber-800"><?= h($dt['thongBao']) ?></div>
            <?php elseif ($dt['ds'] === []): ?>
                <div class="px-6 py-6 text-sm text-slate-500">Chưa có điểm thi nào của bạn.</div>
            <?php else: ?>
            <table class="w-full text-sm">
                <thead class="bg-slate-50 text-left text-xs uppercase tracking-wide text-slate-500">
                    <tr><th class="px-6 py-3">Môn thi</th><th class="px-6 py-3 text-right">Điểm</th><th class="px-6 py-3 text-right">Hệ số</th><th class="px-6 py-3">Ghi chú</th></tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                <?php foreach ($dt['ds'] as $m): ?>
                    <tr>
                        <td class="px-6 py-3 font-medium"><?= h($m['tenmonhoc']) ?></td>
                        <td class="px-6 py-3 text-right font-semibold"><?= (int) $m['vang'] === 1 ? 'Vắng' : h(fmtDiem($m['diem'])) ?></td>
                        <td class="px-6 py-3 text-right text-slate-500"><?= $m['heso'] === null ? '—' : h(fmtDiem($m['heso'])) ?></td>
                        <td class="px-6 py-3 text-slate-500">
                            <?php if ((float) $m['tyletrudiem'] > 0): ?>Bị trừ điểm<?= $m['lydotrudiem'] ? ': ' . h($m['lydotrudiem']) : '' ?><?php endif; ?>
                        </td>
                    </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
            <?php endif; ?>
        </section>

        <?php if ($dt['congBo']): ?>
        <section class="mt-6 rounded-2xl border border-slate-200 bg-white p-6 shadow-sm">
            <h2 class="font-semibold">Kết quả xét tuyển</h2>
            <?php if (!$xt['congBo']): ?>
                <div class="mt-3 rounded-lg border border-amber-200 bg-amber-50 px-4 py-3 text-sm text-amber-800"><?= h($xt['thongBao']) ?></div>
            <?php elseif ($xt['ketQua'] === 'trung_tuyen'): ?>
                <div class="mt-3 rounded-lg border border-green-200 bg-green-50 px-4 py-4">
                    <div class="text-lg font-bold text-green-700">Trúng tuyển</div>
                    <div class="mt-1 text-sm text-green-900">
                        <?= h($xt['truong']) ?> · Nguyện vọng <?= (int) $xt['nv'] ?>
                        <?php if ($xt['tongDiem'] !== null && (float) $xt['tongDiem'] > 0): ?> · Điểm xét tuyển: <b><?= h(fmtDiem($xt['tongDiem'])) ?></b><?php endif; ?>
                    </div>
                    <?php if ($xt['diaChi']): ?><div class="mt-1 text-sm text-green-800"><?= h($xt['diaChi']) ?></div><?php endif; ?>
                    <?php if ($xt['daXacNhan']): ?><div class="mt-2 inline-block rounded-full border border-green-300 bg-white px-3 py-0.5 text-xs font-semibold text-green-700">Đã xác nhận nhập học</div><?php endif; ?>
                    <?php if (!$xt['daXacNhan']): ?>
                        <a href="/stackers_project/pages/hoc-sinh/xac-nhan-nhap-hoc/" class="mt-3 inline-block rounded-lg bg-blue-700 px-4 py-2 text-sm font-semibold text-white hover:bg-blue-800">Xác nhận nhập học</a>
                    <?php endif; ?>
                </div>
            <?php elseif ($xt['ketQua'] === 'khong_dang_ky'): ?>
                <div class="mt-3 rounded-lg border border-slate-200 bg-slate-50 px-4 py-3 text-sm text-slate-700">Bạn chưa đăng ký nguyện vọng nào trong kỳ tuyển sinh này.</div>
            <?php else: ?>
                <div class="mt-3 rounded-lg border border-red-200 bg-red-50 px-4 py-4">
                    <div class="text-lg font-bold text-red-700">Không trúng tuyển</div>
                    <div class="mt-1 text-sm text-red-800">Bạn không trúng tuyển vào các nguyện vọng đã đăng ký.</div>
                </div>
            <?php endif; ?>
        </section>
        <?php endif; ?>
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

</body>
</html>
