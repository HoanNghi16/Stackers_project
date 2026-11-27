<?php
/**
 * Use case: Xem danh sách học sinh nhập học (Actor: Trường THPT).
 * GiaoDienDanhSachNhapHoc: chỉ dựng giao diện, nghiệp vụ nằm ở CtrlXemDanhSachNhapHoc.
 */

use XemDanhSachNhapHoc\CtrlXemDanhSachNhapHoc;

session_start();

require __DIR__ . '/../../../index.php';                         // tạo $conn (và $env)
require __DIR__ . '/../../../class/xem-danh-sach-nhap-hoc/ctrlXemDanhSachNhapHoc.php';
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

/** "34.50" -> "34.5", "30.00" -> "30" */
function fmtDiem(?string $v): string
{
    if ($v === null || $v === '') return '—';
    return str_contains($v, '.') ? rtrim(rtrim($v, '0'), '.') : $v;
}

$ctrl = new CtrlXemDanhSachNhapHoc($conn);

// ---- Xuất danh sách (tải file CSV) --------------------------------------------
$modal = null;   // ['loai' => thanh_cong|loi, 'tieuDe' => ..., 'dong' => [...]]
if (($_GET['xuat'] ?? '') === '1') {
    $kq = $ctrl->xuatDanhSach($maNguoiDung);
    if ($kq['trangThai'] === 'ok') {
        header('Content-Type: text/csv; charset=UTF-8');
        header('Content-Disposition: attachment; filename="' . $kq['tenFile'] . '"');
        header('Content-Length: ' . strlen($kq['noiDung']));
        echo $kq['noiDung'];
        exit;
    }
    $modal = $kq['trangThai'] === 'trong'
        ? ['loai' => 'loi', 'tieuDe' => 'Không thể xuất danh sách', 'dong' => ['Chưa có học sinh nào xác nhận nhập học.']]
        : ['loai' => 'loi', 'tieuDe' => 'Không thể xuất danh sách', 'dong' => ['Lỗi hệ thống']];
}

// ---- Xem / lọc / tìm kiếm -----------------------------------------------------
$dk = $ctrl->chuanHoaDieuKien($_GET);
$d  = $ctrl->yeuCauDanhSach($maNguoiDung, $dk);

if ($modal === null) {
    if ($d['trangThai'] === 'loi_he_thong') {
        $modal = ['loai' => 'loi', 'tieuDe' => 'Lỗi hệ thống', 'dong' => ['Lỗi hệ thống']];
    } elseif ($d['trangThai'] === 'ok' && $d['trangThaiDs'] === 'khong_tim_thay') {
        $modal = ['loai' => 'loi', 'tieuDe' => 'Không có kết quả', 'dong' => ['Không tìm thấy học sinh tương ứng']];
    }
}
?>
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Stackers - Danh sách học sinh nhập học</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-slate-50 text-slate-800">
<?php renderHeader(); renderSidebar(); ?>

<main class="pl-72 pt-20">
    <div class="mx-auto max-w-7xl px-6 py-8">
        <div class="mb-6 flex flex-wrap items-start justify-between gap-3">
            <div>
                <h1 class="text-2xl font-bold">Danh sách học sinh nhập học</h1>
                <p class="mt-1 text-sm text-slate-500">Học sinh đã xác nhận nhập học vào trường trong kỳ tuyển sinh hiện tại.</p>
            </div>
            <?php if ($d['trangThai'] === 'ok'): ?>
                <span class="rounded-full border border-blue-200 bg-blue-50 px-3 py-1 text-xs font-semibold text-blue-700">
                    <?= h($d['truong']['tentruong']) ?> · Kỳ tuyển sinh <?= (int) $d['ky']['namtuyensinh'] ?>
                </span>
            <?php endif; ?>
        </div>

        <?php if ($d['trangThai'] === 'khong_co_truong'): ?>
            <div class="rounded-2xl border border-amber-300 bg-amber-50 px-5 py-4 text-sm text-amber-900">Tài khoản chưa được gán với trường THPT nào. Vui lòng liên hệ quản trị viên.</div>
        <?php elseif ($d['trangThai'] === 'chua_co_ky'): ?>
            <div class="rounded-2xl border border-amber-300 bg-amber-50 px-5 py-4 text-sm text-amber-900">Hệ thống chưa có kỳ tuyển sinh của năm <?= (int) $d['nam'] ?>.</div>
        <?php elseif ($d['trangThai'] === 'loi_he_thong'): ?>
            <div class="rounded-2xl border border-red-200 bg-red-50 px-5 py-4 text-sm text-red-700">Lỗi hệ thống</div>
        <?php elseif ($d['trangThaiDs'] === 'chua_co'): ?>
            <div class="rounded-2xl border border-slate-200 bg-white px-6 py-10 text-center shadow-sm">
                <p class="text-base font-semibold">Chưa có học sinh nào xác nhận nhập học</p>
                <a href="/stackers_project/pages/thpt" class="mt-4 inline-block rounded-lg bg-blue-700 px-5 py-2.5 text-sm font-semibold text-white hover:bg-blue-800">Quay lại trang chủ</a>
            </div>
        <?php else: ?>
            <!-- Thống kê -->
            <div class="mb-6 grid grid-cols-2 gap-4 md:grid-cols-4">
                <div class="rounded-2xl border border-slate-200 bg-white p-4 shadow-sm">
                    <div class="text-xs uppercase tracking-wide text-slate-400">Tổng nhập học</div>
                    <div class="mt-1 text-2xl font-bold"><?= (int) $d['tongSo'] ?></div>
                </div>
                <?php foreach ([1, 2, 3] as $n): ?>
                <div class="rounded-2xl border border-slate-200 bg-white p-4 shadow-sm">
                    <div class="text-xs uppercase tracking-wide text-slate-400">Nguyện vọng <?= $n ?></div>
                    <div class="mt-1 text-2xl font-bold"><?= (int) ($d['thongKeNV'][$n] ?? 0) ?></div>
                </div>
                <?php endforeach; ?>
            </div>

            <!-- Lọc / tìm kiếm / xuất -->
            <form method="get" class="mb-4 flex flex-wrap items-end gap-3 rounded-2xl border border-slate-200 bg-white p-4 shadow-sm">
                <div class="min-w-[16rem] flex-1">
                    <label for="q" class="block text-xs font-medium text-slate-500">Tìm kiếm (họ tên hoặc số báo danh)</label>
                    <input type="text" id="q" name="q" value="<?= h($dk['q']) ?>" maxlength="100" placeholder="Nhập từ khóa..."
                           class="mt-1 w-full rounded-lg border border-slate-300 px-3 py-2 text-sm focus:border-blue-500 focus:outline-none focus:ring-2 focus:ring-blue-300">
                </div>
                <div>
                    <label for="nv" class="block text-xs font-medium text-slate-500">Nguyện vọng trúng tuyển</label>
                    <select id="nv" name="nv" class="mt-1 rounded-lg border border-slate-300 px-3 py-2 text-sm focus:border-blue-500 focus:outline-none focus:ring-2 focus:ring-blue-300">
                        <option value="">Tất cả</option>
                        <?php foreach ([1, 2, 3] as $n): ?>
                            <option value="<?= $n ?>" <?= $dk['nv'] === $n ? 'selected' : '' ?>>Nguyện vọng <?= $n ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <button type="submit" class="rounded-lg bg-blue-700 px-4 py-2 text-sm font-semibold text-white hover:bg-blue-800">Lọc / Tìm</button>
                <?php if ($d['coLoc']): ?>
                    <a href="?" class="rounded-lg border border-slate-300 px-4 py-2 text-sm font-medium text-slate-600 hover:bg-slate-50">Xóa lọc</a>
                <?php endif; ?>
                <a href="?xuat=1" class="ml-auto rounded-lg border border-green-600 px-4 py-2 text-sm font-semibold text-green-700 hover:bg-green-50">Xuất danh sách (CSV)</a>
            </form>

            <!-- Bảng -->
            <div class="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm">
                <table class="w-full text-sm">
                    <thead class="bg-slate-50 text-left text-xs uppercase tracking-wide text-slate-500">
                        <tr>
                            <th class="px-5 py-3">STT</th>
                            <th class="px-5 py-3">Họ tên</th>
                            <th class="px-5 py-3">Số báo danh</th>
                            <th class="px-5 py-3 text-right">Điểm xét tuyển</th>
                            <th class="px-5 py-3">Nguyện vọng trúng tuyển</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100">
                        <?php if ($d['ds'] === []): ?>
                            <tr><td colspan="5" class="px-5 py-8 text-center text-slate-500">Không tìm thấy học sinh tương ứng</td></tr>
                        <?php endif; ?>
                        <?php foreach ($d['ds'] as $i => $r): ?>
                        <tr class="hover:bg-slate-50">
                            <td class="px-5 py-3 text-slate-500"><?= $i + 1 ?></td>
                            <td class="px-5 py-3 font-medium"><?= h($r['hoten']) ?></td>
                            <td class="px-5 py-3"><?= h($r['sobaodanh']) ?></td>
                            <td class="px-5 py-3 text-right font-semibold"><?= h(fmtDiem($r['diemxettuyen'])) ?></td>
                            <td class="px-5 py-3"><span class="rounded-full border border-blue-200 bg-blue-50 px-2.5 py-0.5 text-xs font-semibold text-blue-700">NV<?= (int) $r['thutunguyenvong'] ?></span></td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
                <div class="border-t border-slate-100 px-5 py-3 text-xs text-slate-500">
                    Hiển thị <?= count($d['ds']) ?> / <?= (int) $d['tongSo'] ?> học sinh
                </div>
            </div>
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
