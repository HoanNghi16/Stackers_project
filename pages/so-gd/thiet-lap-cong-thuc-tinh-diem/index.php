<?php
/**
 * Use case: Thiết lập công thức tính điểm (Actor: Sở GD&ĐT).
 * GiaoDienCongThuc trong sequence diagram: trang này chỉ dựng giao diện và chuyển
 * dữ liệu cho CtrlThietLapCongThuc; mọi quy tắc nghiệp vụ nằm ở controller.
 */

use ThietLapCongThuc\CtrlThietLapCongThuc;

session_start();

require __DIR__ . '/../../../index.php';                         // tạo $conn (và $env)
require __DIR__ . '/../../../class/thiet-lap-cong-thuc-tinh-diem/ctrlThietLapCongThuc.php';
require __DIR__ . '/../../../components/layout/header.php';
require __DIR__ . '/../../../components/layout/sidebar.php';

// ---- Tiền điều kiện: đã đăng nhập với vai trò Sở GD&ĐT ----------------------
// Chức năng đăng nhập chưa xong: khi .env có DEV_FAKE_LOGIN=1 thì tự gán vai trò
// SO_GD để chạy thử trang này. KHÔNG bật cờ này trên môi trường thật.
if (($env['DEV_FAKE_LOGIN'] ?? '') === '1' && empty($_SESSION['vaitro'])) {
    $_SESSION['vaitro'] = 'SO_GD';
}
if (($_SESSION['vaitro'] ?? null) !== 'SO_GD') {
    header('Location: /stackers_project/pages/login');
    exit;
}

function h(?string $s): string
{
    return htmlspecialchars((string) $s, ENT_QUOTES, 'UTF-8');
}

/** "2.00" -> "2", "0.50" -> "0.5" */
function fmtSo(?string $v): string
{
    $v = (string) $v;
    return str_contains($v, '.') ? rtrim(rtrim($v, '0'), '.') : $v;
}

if (empty($_SESSION['csrf'])) {
    $_SESSION['csrf'] = bin2hex(random_bytes(32));
}

$ctrl = new CtrlThietLapCongThuc($conn);

// ---- Xử lý yêu cầu ------------------------------------------------------------
$trangThai  = null;      // kết quả lần lưu vừa rồi (null nếu chỉ xem form)
$thongBao   = '';
$dsLoiBanner = [];       // các dòng hiển thị trong banner lỗi
$loiTheoOTruong = [];    // "heSo.3" => [thông báo, ...]
$formDaGui  = null;      // dữ liệu người dùng vừa gửi (để dựng lại form khi có lỗi)

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!hash_equals($_SESSION['csrf'], (string) ($_POST['csrf'] ?? ''))) {
        $trangThai   = 'loi_nghiep_vu';
        $thongBao    = 'Phiên làm việc đã hết hạn. Vui lòng thử lại.';
        $dsLoiBanner = [$thongBao];
    } else {
        $nhap = $ctrl->chuanHoaDuLieuNhap($_POST);
        $kq   = $ctrl->luuCongThuc($nhap['dsHeSo'], $nhap['dsTieuChuan']);

        if ($kq['trangThai'] === 'thanh_cong') {
            $_SESSION['flash_congthuc'] = $kq['thongBao'];
            header('Location: ' . strtok($_SERVER['REQUEST_URI'], '?'));   // PRG: tránh gửi lại form khi F5
            exit;
        }

        $trangThai = $kq['trangThai'];
        $thongBao  = $kq['thongBao'];
        $formDaGui = $nhap;

        if ($trangThai === 'khong_hop_le') {
            foreach ($kq['dsLoi'] as $l) {
                $dsLoiBanner[] = $l['thongBao'];
                $loiTheoOTruong[$l['truong']][] = $l['thongBao'];
            }
        } else {
            $dsLoiBanner = explode("\n", $thongBao);
        }
    }
}

$thongBaoThanhCong = $_SESSION['flash_congthuc'] ?? '';
unset($_SESSION['flash_congthuc']);

// ---- Nội dung modal kết quả (mọi thông báo sau khi bấm "Lưu công thức") -------------
$modal = null;   // ['loai' => 'thanh_cong'|'loi', 'tieuDe' => string, 'dong' => string[]]
if ($thongBaoThanhCong !== '') {
    $modal = [
        'loai'   => 'thanh_cong',
        'tieuDe' => $thongBaoThanhCong,
        'dong'   => ['Công thức đã được lưu và áp dụng cho kỳ tuyển sinh hiện tại.'],
    ];
} elseif ($trangThai !== null) {
    $modal = match ($trangThai) {
        'khong_hop_le' => ['loai' => 'loi', 'tieuDe' => 'Không thể lưu công thức. Vui lòng kiểm tra lại:', 'dong' => $dsLoiBanner],
        'loi_he_thong' => ['loai' => 'loi', 'tieuDe' => $thongBao, 'dong' => []],
        default        => ['loai' => 'loi', 'tieuDe' => 'Không thể lưu công thức', 'dong' => $dsLoiBanner],
    };
}

// ---- Dữ liệu cho form (bước 2 / luồng 2.1) --------------------------------------
$duLieu      = $ctrl->yeuCauFormCongThuc();
$ky          = $duLieu['ky'];
$dsMon       = $duLieu['dsMon'];
$daCoCongThuc = $duLieu['daCoCongThuc'];
$namHienTai  = (int) (new DateTime('now', new DateTimeZone('Asia/Ho_Chi_Minh')))->format('Y');
$choPhepNhap = $ky !== null && !empty($dsMon);

// Giá trị hiển thị trong form: ưu tiên dữ liệu vừa gửi (khi có lỗi), nếu không thì dữ liệu đã lưu
$giaTriHeSo = [];
foreach ($dsMon as $mon) {
    $ma = (string) $mon['mamonhoc'];
    $giaTriHeSo[$ma] = $formDaGui !== null
        ? ($formDaGui['dsHeSo'][$ma] ?? '')
        : fmtSo($duLieu['dsHeSo'][(int) $ma] ?? '');
}

$dongTieuChuan = [];   // idx => [ma, loai, dieuKien, diemCong]
if ($formDaGui !== null) {
    $dongTieuChuan = $formDaGui['dsTieuChuan'];
} else {
    foreach ($duLieu['dsTieuChuan'] as $i => $tc) {
        $dongTieuChuan[(string) $i] = [
            'ma'       => (string) $tc['matieuchuan'],
            'loai'     => $tc['loaitieuchuan'],
            'dieuKien' => $tc['dieukienapdung'],
            'diemCong' => fmtSo($tc['diemcong']),
        ];
    }
}
$idxTiepTheo = 0;
foreach (array_keys($dongTieuChuan) as $k) {
    if (ctype_digit((string) $k)) {
        $idxTiepTheo = max($idxTiepTheo, (int) $k + 1);
    }
}

/** In một dòng tiêu chuẩn (dùng cho cả dòng có sẵn và <template> thêm mới). */
function renderDongTieuChuan(string $idx, array $tc, array $loiTheoOTruong): void
{
    $loiLoai = $loiTheoOTruong["tieuChuan.$idx.loai"] ?? [];
    $loiDK   = $loiTheoOTruong["tieuChuan.$idx.dieuKien"] ?? [];
    $loiDiem = $loiTheoOTruong["tieuChuan.$idx.diemCong"] ?? [];
    $vienLoi = 'border-red-400 bg-red-50';
    $vienThuong = 'border-slate-300 bg-white';
    ?>
    <tr class="js-dong-tieu-chuan border-b border-slate-100 align-top last:border-b-0">
        <td class="px-4 py-3 sm:px-5">
            <input type="hidden" name="tieuChuan[<?= h($idx) ?>][ma]" value="<?= h($tc['ma']) ?>">
            <select name="tieuChuan[<?= h($idx) ?>][loai]"
                    class="js-loai w-full rounded-lg border px-3 py-2 text-sm <?= $loiLoai ? $vienLoi : $vienThuong ?>">
                <?php foreach (CtrlThietLapCongThuc::LOAI_TIEU_CHUAN as $maLoai => $nhan): ?>
                    <option value="<?= h($maLoai) ?>" <?= $tc['loai'] === $maLoai ? 'selected' : '' ?>><?= h($nhan) ?></option>
                <?php endforeach; ?>
            </select>
            <p class="js-loi mt-1 text-xs text-red-600 <?= $loiLoai ? '' : 'hidden' ?>"><?= h($loiLoai[0] ?? '') ?></p>
        </td>
        <td class="px-4 py-3 sm:px-5">
            <input type="text" name="tieuChuan[<?= h($idx) ?>][dieuKien]" value="<?= h($tc['dieuKien']) ?>"
                   maxlength="200" placeholder="Ví dụ: Con liệt sĩ"
                   class="js-dieu-kien w-full rounded-lg border px-3 py-2 text-sm <?= $loiDK ? $vienLoi : $vienThuong ?>">
            <p class="js-loi mt-1 text-xs text-red-600 <?= $loiDK ? '' : 'hidden' ?>"><?= h($loiDK[0] ?? '') ?></p>
        </td>
        <td class="px-4 py-3 sm:px-5">
            <input type="text" inputmode="decimal" name="tieuChuan[<?= h($idx) ?>][diemCong]" value="<?= h($tc['diemCong']) ?>"
                   class="js-diem-cong w-24 rounded-lg border px-3 py-2 text-sm <?= $loiDiem ? $vienLoi : $vienThuong ?>">
            <p class="js-loi mt-1 text-xs text-red-600 <?= $loiDiem ? '' : 'hidden' ?>"><?= h($loiDiem[0] ?? '') ?></p>
        </td>
        <td class="px-4 py-3 text-right sm:px-5">
            <button type="button" class="js-xoa rounded-lg px-2 py-2 text-sm text-slate-400 transition hover:bg-red-50 hover:text-red-600">
                Xóa
            </button>
        </td>
    </tr>
    <?php
}
?>
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Stackers - Thiết lập công thức tính điểm</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script>
        tailwind.config = {
            theme: { extend: { colors: { primary: '#1d4ed8' }, fontFamily: { roboto: ['Roboto', 'sans-serif'] } } }
        };
    </script>
</head>
<body class="bg-slate-50 text-slate-800">
<?php
renderHeader();
renderSidebar();
?>

<main class="pl-72 pt-20">
    <div class="mx-auto max-w-4xl px-6 py-8">

        <!-- Breadcrumb + tiêu đề -->
        <p class="mb-2 text-sm text-slate-500">
            Sở GD&amp;ĐT &nbsp;/&nbsp; Điểm số &nbsp;/&nbsp;
            <span class="text-slate-800">Thiết lập công thức tính điểm</span>
        </p>
        <div class="mb-6 flex flex-wrap items-start justify-between gap-3">
            <div>
                <h1 class="text-2xl font-bold">Thiết lập công thức tính điểm</h1>
                <p class="mt-1 text-sm text-slate-500">
                    Cấu hình hệ số các môn thi và các tiêu chuẩn cộng điểm ưu tiên / khuyến khích
                    áp dụng cho kỳ tuyển sinh hiện tại.
                </p>
            </div>
            <span class="rounded-full border border-blue-200 bg-blue-50 px-3 py-1 text-xs font-semibold text-blue-700">
                Kỳ tuyển sinh: <?= $ky !== null ? h((string) $ky['namtuyensinh']) : $namHienTai ?>
            </span>
        </div>

        <!-- Thông báo kết quả lưu hiển thị ở modal #modalKetQua (cuối trang) -->

        <?php if ($ky === null): ?>
            <div class="rounded-2xl border border-amber-300 bg-amber-50 px-5 py-4 text-sm text-amber-900">
                Hệ thống chưa có kỳ tuyển sinh của năm <?= $namHienTai ?>. Vui lòng tạo kỳ tuyển sinh trước khi thiết lập công thức.
            </div>
        <?php elseif (empty($dsMon)): ?>
            <div class="rounded-2xl border border-amber-300 bg-amber-50 px-5 py-4 text-sm text-amber-900">
                Chưa có môn thi nào được khai báo cho kỳ tuyển sinh này.
            </div>
        <?php else: ?>

        <?php if ($daCoCongThuc && $trangThai === null && $thongBaoThanhCong === ''): ?>
            <p class="mb-4 rounded-xl border border-slate-200 bg-white px-4 py-2.5 text-sm text-slate-600">
                Kỳ tuyển sinh này đã có công thức tính điểm. Bạn có thể chỉnh sửa rồi chọn "Lưu công thức".
            </p>
        <?php endif; ?>

        <form id="formCongThuc" method="post" action="" class="space-y-6" novalidate>
            <input type="hidden" name="csrf" value="<?= h($_SESSION['csrf']) ?>">

            <!-- Khối 1: hệ số môn thi -->
            <section class="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm">
                <div class="border-b border-slate-200 px-5 py-3">
                    <h2 class="font-semibold">Hệ số môn thi</h2>
                    <p class="mt-0.5 text-xs text-slate-500">Hệ số phải lớn hơn 0 (tối đa 2 chữ số thập phân).</p>
                </div>
                <div class="overflow-x-auto">
                <table class="w-full text-sm">
                    <thead>
                    <tr class="border-b border-slate-100 text-left text-slate-500">
                        <th class="px-5 py-2 font-medium">Môn thi</th>
                        <th class="w-40 px-5 py-2 font-medium">Hệ số</th>
                        <th class="px-5 py-2 font-medium">Ghi chú</th>
                    </tr>
                    </thead>
                    <tbody>
                    <?php foreach ($dsMon as $mon):
                        $ma  = (string) $mon['mamonhoc'];
                        $loi = $loiTheoOTruong["heSo.$ma"] ?? [];
                        ?>
                        <tr class="border-b border-slate-100 align-top last:border-b-0">
                            <td class="px-5 py-3 font-medium"><?= h($mon['tenmonhoc']) ?></td>
                            <td class="px-5 py-3">
                                <input type="text" inputmode="decimal" name="heSo[<?= h($ma) ?>]"
                                       value="<?= h($giaTriHeSo[$ma]) ?>"
                                       data-ten-mon="<?= h($mon['tenmonhoc']) ?>"
                                       class="js-he-so w-24 rounded-lg border px-3 py-2 <?= $loi ? 'border-red-400 bg-red-50' : 'border-slate-300 bg-white' ?>">
                                <p class="js-loi mt-1 text-xs text-red-600 <?= $loi ? '' : 'hidden' ?>"><?= h($loi[0] ?? '') ?></p>
                            </td>
                            <td class="px-5 py-3 text-slate-400">Điểm thi môn <?= h($mon['tenmonhoc']) ?> nhân với hệ số</td>
                        </tr>
                    <?php endforeach; ?>
                    </tbody>
                </table>
                </div>
                <div class="border-t border-slate-100 bg-slate-50 px-5 py-3 text-xs text-slate-600">
                    Công thức: <span id="congThucXemTruoc" class="font-semibold text-slate-800"></span>
                </div>
            </section>

            <!-- Khối 2: tiêu chuẩn cộng điểm -->
            <section class="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm">
                <div class="flex items-center justify-between border-b border-slate-200 px-5 py-3">
                    <div>
                        <h2 class="font-semibold">Tiêu chuẩn cộng điểm ưu tiên / khuyến khích</h2>
                        <p class="mt-0.5 text-xs text-slate-500">
                            Điều kiện áp dụng không được trống và không trùng tiêu chuẩn khác cùng loại; mức điểm cộng phải lớn hơn 0.
                        </p>
                    </div>
                    <button type="button" id="btnThemTieuChuan"
                            class="shrink-0 rounded-lg border border-blue-600 px-3 py-1.5 text-sm font-medium text-blue-700 transition hover:bg-blue-50">
                        + Thêm tiêu chuẩn
                    </button>
                </div>
                <div class="overflow-x-auto">
                <table class="w-full text-sm">
                    <thead>
                    <tr class="border-b border-slate-100 text-left text-slate-500">
                        <th class="w-44 px-4 py-2 font-medium sm:px-5">Loại</th>
                        <th class="px-4 py-2 font-medium sm:px-5">Điều kiện áp dụng</th>
                        <th class="w-32 px-4 py-2 font-medium sm:px-5">Điểm cộng</th>
                        <th class="w-20 px-4 py-2 sm:px-5"></th>
                    </tr>
                    </thead>
                    <tbody id="dsTieuChuan">
                    <?php foreach ($dongTieuChuan as $idx => $tc) { renderDongTieuChuan((string) $idx, $tc, $loiTheoOTruong); } ?>
                    </tbody>
                </table>
                </div>
                <p id="khongCoTieuChuan"
                   class="px-5 py-6 text-center text-sm text-slate-400 <?= $dongTieuChuan ? 'hidden' : '' ?>">
                    Chưa có tiêu chuẩn nào. Chọn "+ Thêm tiêu chuẩn" để thêm.
                </p>
            </section>

            <div class="flex items-center justify-end gap-3">
                <a href="<?= h(strtok($_SERVER['REQUEST_URI'], '?')) ?>"
                   class="rounded-lg border border-slate-300 bg-white px-4 py-2 text-sm font-medium text-slate-700 transition hover:bg-slate-50">
                    Hủy thay đổi
                </a>
                <button type="submit" id="btnLuu"
                        class="rounded-lg bg-blue-700 px-5 py-2 text-sm font-semibold text-white shadow-sm transition hover:bg-blue-800 disabled:opacity-60">
                    Lưu công thức
                </button>
            </div>
        </form>

        <!-- Mẫu dòng tiêu chuẩn mới -->
        <template id="tplTieuChuan">
            <?php renderDongTieuChuan('__IDX__', ['ma' => '', 'loai' => 'UU_TIEN', 'dieuKien' => '', 'diemCong' => ''], []); ?>
        </template>

        <?php endif; ?>
    </div>
</main>

<!-- Modal kết quả: thành công (bước 7) / dữ liệu không hợp lệ (5.1) / lỗi hệ thống (6.1) -->
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

<?php if ($choPhepNhap): ?>
<script>
(() => {
    const MAX = 99.99;
    const form = document.getElementById('formCongThuc');
    const tbody = document.getElementById('dsTieuChuan');
    const tpl = document.getElementById('tplTieuChuan');
    const trong = document.getElementById('khongCoTieuChuan');
    let idxTiepTheo = <?= (int) $idxTiepTheo ?>;

    // ---- Quy tắc kiểm tra: phản chiếu CtrlThietLapCongThuc::kiemTraHopLe (server vẫn kiểm tra lại) ----
    function kiemTraSo(raw, nhan) {
        const v = raw.trim().replace(',', '.');
        if (v === '') return nhan + ' không được để trống.';
        if (!/^-?\d+(\.\d+)?$/.test(v)) return nhan + ' phải là một số.';
        if (v[0] === '-' || parseFloat(v) <= 0) return nhan + ' phải lớn hơn 0.';
        if (!/^\d+(\.\d{1,2})?$/.test(v)) return nhan + ' chỉ được có tối đa 2 chữ số thập phân.';
        if (parseFloat(v) > MAX) return nhan + ' không được lớn hơn ' + MAX + '.';
        return null;
    }
    const chuanHoaChu = s => s.replace(/\s+/g, ' ').trim();

    function datLoi(input, msg) {
        const p = input.parentElement.querySelector('.js-loi');
        const co = !!msg;
        if (p) { p.textContent = msg || ''; p.classList.toggle('hidden', !co); }
        input.classList.toggle('border-red-400', co);
        input.classList.toggle('bg-red-50', co);
        input.classList.toggle('border-slate-300', !co);
        input.classList.toggle('bg-white', !co);
    }

    function kiemTraHeSo(input) {
        const msg = kiemTraSo(input.value, 'Hệ số môn ' + input.dataset.tenMon);
        datLoi(input, msg);
        return msg;
    }

    // Kiểm tra toàn bộ tiêu chuẩn, trả về danh sách thông báo (đồng thời gắn lỗi vào từng ô)
    function kiemTraTatCaTieuChuan() {
        const loi = [];
        const daGap = new Map();
        [...tbody.querySelectorAll('.js-dong-tieu-chuan')].forEach((tr, i) => {
            const nhan = 'Tiêu chuẩn #' + (i + 1);
            const loai = tr.querySelector('.js-loai');
            const dk = tr.querySelector('.js-dieu-kien');
            const diem = tr.querySelector('.js-diem-cong');

            let m = null;
            const ten = chuanHoaChu(dk.value);
            if (ten === '') m = nhan + ': điều kiện áp dụng không được để trống.';
            else {
                const khoa = loai.value + '|' + ten.toLocaleLowerCase('vi');
                if (daGap.has(khoa)) m = nhan + ': điều kiện "' + ten + '" bị trùng với tiêu chuẩn #' + daGap.get(khoa) + ' (cùng loại).';
                else daGap.set(khoa, i + 1);
            }
            datLoi(dk, m); if (m) loi.push(m);

            m = kiemTraSo(diem.value, nhan + ': mức điểm cộng');
            datLoi(diem, m); if (m) loi.push(m);
        });
        return loi;
    }

    // ---- Công thức xem trước ----
    function capNhatCongThuc() {
        const phan = [...form.querySelectorAll('.js-he-so')].map(inp => {
            const v = inp.value.trim().replace(',', '.');
            return inp.dataset.tenMon + ' × ' + (/^\d+(\.\d+)?$/.test(v) ? parseFloat(v) : '?');
        });
        document.getElementById('congThucXemTruoc').textContent =
            'Tổng điểm = ' + phan.join(' + ') + ' + điểm cộng ưu tiên / khuyến khích';
    }

    function capNhatTrong() {
        trong.classList.toggle('hidden', tbody.querySelector('.js-dong-tieu-chuan') !== null);
    }

    // ---- Sự kiện ----
    form.querySelectorAll('.js-he-so').forEach(inp =>
        inp.addEventListener('input', () => { kiemTraHeSo(inp); capNhatCongThuc(); }));

    tbody.addEventListener('input', e => {
        if (e.target.matches('.js-dieu-kien, .js-diem-cong, .js-loai')) kiemTraTatCaTieuChuan();
    });
    tbody.addEventListener('change', e => {
        if (e.target.matches('.js-loai')) kiemTraTatCaTieuChuan();
    });
    tbody.addEventListener('click', e => {
        const btn = e.target.closest('.js-xoa');
        if (!btn) return;
        btn.closest('tr').remove();
        capNhatTrong();
        kiemTraTatCaTieuChuan();
    });

    document.getElementById('btnThemTieuChuan').addEventListener('click', () => {
        const html = tpl.innerHTML.replaceAll('__IDX__', 'n' + (idxTiepTheo++));
        tbody.insertAdjacentHTML('beforeend', html);
        capNhatTrong();
        tbody.lastElementChild.querySelector('.js-dieu-kien').focus();
    });

    form.addEventListener('submit', e => {
        const loi = [];
        form.querySelectorAll('.js-he-so').forEach(inp => { const m = kiemTraHeSo(inp); if (m) loi.push(m); });
        loi.push(...kiemTraTatCaTieuChuan());

        if (loi.length > 0) {
            e.preventDefault();
            hienModal('loi', 'Không thể lưu công thức. Vui lòng kiểm tra lại:', loi);
            return;
        }
        document.getElementById('btnLuu').disabled = true;   // tránh bấm lưu hai lần
    });

    capNhatCongThuc();
})();
</script>
<?php endif; ?>

</body>
</html>
