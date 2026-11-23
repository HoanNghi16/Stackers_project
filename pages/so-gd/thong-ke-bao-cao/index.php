<?php
if (session_status() !== PHP_SESSION_ACTIVE) session_start();
if (($_SESSION['vaitro'] ?? '') !== 'SO_GD') {
    header('Location: /stackers_project/pages/login/');
    exit;
}
require_once __DIR__ . '/../../../class/thong-ke-bao-cao.php';
require_once __DIR__ . '/../../../components/thong-ke-bieu-do.php';
header('Cache-Control: no-store');
function hTK($v): string { return htmlspecialchars((string)$v, ENT_QUOTES, 'UTF-8'); }
function getTK(string $key): string { return is_string($_GET[$key] ?? null) ? trim($_GET[$key]) : ''; }
$errors = [];
$report = null;
$token = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $token = is_string($_POST['report'] ?? null) ? $_POST['report'] : '';
    $saved = $_SESSION['reports'][$token] ?? null;
    if (!$saved || time() - $saved['created'] > 3600) {
        http_response_code(400);
        $errors[] = 'Báo cáo đã hết hạn hoặc không hợp lệ. Vui lòng thống kê lại trước khi xuất.';
    } else {
        try {
            $excel = ThongKeBaoCao::xuatExcel($saved);
            header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
            header('Content-Disposition: attachment; filename="bao-cao-tuyen-sinh-' . date('Ymd-His') . '.xlsx"');
            header('Content-Length: ' . strlen($excel));
            echo $excel;
            exit;
        } catch (Throwable $e) {
            error_log('Xuat Excel: ' . $e->getMessage());
            $errors[] = 'Không thể xuất file Excel. Vui lòng thử lại.';
        }
    }
}
// Không yêu cầu người dùng chọn loại: thống kê mặc định theo trường THPT.
// Giữ 'loai' trong mảng để tương thích với ThongKeBaoCao::kiemTra()/thongKe().
$f = ['loai' => 'truong', 'ky' => '', 'truong' => '', 'phuong' => '', 'nv' => '', 'ketqua' => ''];
foreach (['ky', 'truong', 'phuong', 'nv', 'ketqua'] as $key) {
    $f[$key] = getTK($key);
}
$options = ['ky' => [], 'truong' => [], 'phuong' => []];
$requested = getTK('action') === 'thongke';
try {
    require __DIR__ . '/../../../index.php';
    $model = new ThongKeBaoCao($conn);
    $options = $model->tieuChi();
    if (!$requested && $_SERVER['REQUEST_METHOD'] !== 'POST') {
        $f['ky'] = $f['ky'] ?: (string)($options['ky'][0]['makythi'] ?? '');
    }
    if ($_SERVER['REQUEST_METHOD'] === 'GET' && !$errors) {
        $errors = ThongKeBaoCao::kiemTra($f, $options);
        if (!$errors) {
            $report = $model->thongKe($f);
            $years = array_column($options['ky'], 'namtuyensinh', 'makythi');
            $schools = array_column($options['truong'], 'tentruong', 'matruong');
            $wards = array_column($options['phuong'], 'tenphuong', 'maphuong');
            $report['criteria'] = [
                'Năm tuyển sinh' => $years[$f['ky']],
                'Trường THPT' => $schools[$f['truong']] ?? 'Tất cả trường THPT',
                'Phường/Xã của trường' => $wards[$f['phuong']] ?? 'Tất cả phường/xã',
                'Nguyện vọng' => $f['nv'] ?: 'Tất cả nguyện vọng',
                'Kết quả nguyện vọng' => ThongKeBaoCao::KET_QUA[$f['ketqua']] ?? 'Tất cả kết quả',
            ];
            $report['created'] = time();
            $report['time'] = (new DateTimeImmutable('now', new DateTimeZone('Asia/Ho_Chi_Minh')))->format('d/m/Y H:i:s');
            if ($report['rows']) {
                $token = bin2hex(random_bytes(24));
                $_SESSION['reports'] = array_slice($_SESSION['reports'] ?? [], -4, null, true);
                $_SESSION['reports'][$token] = $report;
            }
        }
    }
} catch (Throwable $e) {
    error_log('Thong ke bao cao: ' . $e->getMessage());
    $errors[] = 'Không thể tải dữ liệu thống kê. Vui lòng thử lại sau.';
}
require_once __DIR__ . '/../../../components/layout/header.php';
require_once __DIR__ . '/../../../components/layout/sidebar.php';
?>
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8"><meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Thống kê, báo cáo | Sở GD&ĐT · Stackers</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="/stackers_project/assets/css/danh-sach-thi-sinh.css">
    <link rel="stylesheet" href="/stackers_project/assets/css/thong-ke-bao-cao.css">
</head>
<body class="candidate-page">
<?php renderHeader(); renderSidebar(); ?>
<main class="candidate-main">
    <div class="candidate-heading"><div><p class="eyebrow">SỞ GIÁO DỤC VÀ ĐÀO TẠO</p><h1>Thống kê, báo cáo</h1><p>Theo dõi đăng ký nguyện vọng và kết quả tuyển sinh lớp 10.</p></div><button type="button" id="toggle-sidebar" aria-expanded="false" aria-controls="candidate-sidebar">Menu chức năng</button></div>
    <?php if ($errors): ?><div class="candidate-alert" role="alert"><ul><?php foreach ($errors as $error): ?><li><?= hTK($error) ?></li><?php endforeach; ?></ul></div><?php endif; ?>
    <section class="candidate-card" aria-labelledby="criteria-title">
        <h2 id="criteria-title">Tiêu chí thống kê</h2>
        <form method="get" class="candidate-search report-filters" id="report-filters">
            <input type="hidden" name="action" value="thongke">
            <!-- Gửi giá trị cố định để tương thích hệ thống hiện tại; không hiện ô chọn. -->
            <input type="hidden" name="loai" value="truong">
            <label>Năm tuyển sinh *<select name="ky" required><option value="">Chọn năm tuyển sinh</option><?php foreach ($options['ky'] as $ky): ?><option value="<?= hTK($ky['makythi']) ?>" <?= $f['ky'] === (string)$ky['makythi'] ? 'selected' : '' ?>><?= hTK($ky['namtuyensinh']) ?></option><?php endforeach; ?></select></label>
            <label>Phường/Xã của trường THPT<select name="phuong" id="filter-phuong"><option value="">Tất cả phường/xã</option><?php foreach ($options['phuong'] as $ward): ?><option value="<?= hTK($ward['maphuong']) ?>" <?= $f['phuong'] === (string)$ward['maphuong'] ? 'selected' : '' ?>><?= hTK($ward['tenphuong']) ?></option><?php endforeach; ?></select></label>
            <label>Trường THPT<select name="truong" id="filter-truong"><option value="">Tất cả trường THPT</option><?php foreach ($options['truong'] as $school): ?><option value="<?= hTK($school['matruong']) ?>" data-maphuong="<?= hTK($school['maphuong']) ?>" <?= $f['truong'] === (string)$school['matruong'] ? 'selected' : '' ?>><?= hTK($school['tentruong']) ?></option><?php endforeach; ?></select></label>
            <label>Nguyện vọng<select name="nv"><option value="">Tất cả nguyện vọng</option><?php foreach (['1','2','3'] as $nv): ?><option value="<?= $nv ?>" <?= $f['nv'] === $nv ? 'selected' : '' ?>>Nguyện vọng <?= $nv ?></option><?php endforeach; ?></select></label>
            <label>Kết quả nguyện vọng<select name="ketqua"><option value="">Tất cả kết quả</option><?php foreach (ThongKeBaoCao::KET_QUA as $value => $label): ?><option value="<?= hTK($value) ?>" <?= $f['ketqua'] === $value ? 'selected' : '' ?>><?= hTK($label) ?></option><?php endforeach; ?></select></label>
            <p class="report-help">Kết quả được tổng hợp theo trường THPT. Chọn phường/xã để lọc trường THPT tương ứng; khi chọn trường, phường/xã sẽ tự cập nhật. Có thể kết hợp với các tiêu chí khác.</p>
            <div class="candidate-actions"><button class="primary" type="submit">Thống kê</button><a class="button" href="./">Đặt lại</a></div>
        </form>
    </section>
    <?php if ($report): ?>
    <section aria-labelledby="result-title">
        <div class="report-result-heading"><div><h2 id="result-title">Kết quả thống kê</h2><p>Theo trường THPT · Năm <?= hTK($report['criteria']['Năm tuyển sinh']) ?> · <?= hTK($report['time']) ?></p></div>
        <?php if ($report['rows']): ?><details class="report-export" id="export-confirm"><summary class="button">Xuất báo cáo</summary><div class="candidate-card"><h3>Xuất báo cáo Excel (.xlsx)</h3><p>Tải kết quả đang hiển thị, kèm tiêu chí thống kê. Định dạng Excel (.xlsx).</p><form method="post" action="./"><input type="hidden" name="report" value="<?= hTK($token) ?>"><div class="candidate-actions"><button class="primary" type="submit">Tải báo cáo</button><button type="button" id="cancel-export">Hủy</button></div></form></div></details><?php endif; ?></div>
        <div class="report-applied"><?php foreach (array_slice($report['criteria'], 1) as $label => $value): ?><span><?= hTK($label) ?>: <strong><?= hTK($value) ?></strong></span><?php endforeach; ?></div>
        <?php if (!$report['rows']): ?><div class="candidate-card candidate-empty" role="status"><strong>Không có dữ liệu thống kê.</strong><p>Vui lòng thay đổi tiêu chí thống kê và thử lại.</p></div>
        <?php else: ?>
        <div class="report-metrics"><?php foreach (['thisinh' => 'Thí sinh không trùng', 'luot' => 'Lượt nguyện vọng', 'dau' => 'Lượt trúng tuyển', 'nhaphoc' => 'Đã xác nhận nhập học'] as $key => $label): ?><div class="candidate-card"><p><?= hTK($label) ?></p><strong><?= number_format((int)$report['total'][$key], 0, ',', '.') ?></strong></div><?php endforeach; ?></div>
        <?php renderBieuDoThongKe($report['rows']); ?>
        <div class="candidate-card candidate-results"><div class="candidate-table-wrap"><table><caption class="report-caption">Kết quả theo lượt nguyện vọng đã đăng ký</caption><thead><tr><?php foreach (ThongKeBaoCao::COT as $label): ?><th scope="col"><?= hTK($label) ?></th><?php endforeach; ?></tr></thead><tbody><?php foreach ($report['rows'] as $row): ?><tr><?php foreach (ThongKeBaoCao::COT as $key => $_): ?><td><?= $key === 'nhom' ? hTK($row[$key]) : number_format((int)$row[$key], 0, ',', '.') ?></td><?php endforeach; ?></tr><?php endforeach; ?></tbody><tfoot><tr><th scope="row">Tổng (thí sinh không trùng)</th><?php foreach (array_slice(array_keys(ThongKeBaoCao::COT), 1) as $key): ?><td><?= number_format((int)$report['total'][$key], 0, ',', '.') ?></td><?php endforeach; ?></tr></tfoot></table></div></div>
        <?php endif; ?>
        <p class="report-help">Chỉ tính hồ sơ đã đăng ký nguyện vọng. Mỗi thí sinh có thể thuộc nhiều nhóm; tổng thí sinh được đếm không trùng. Các cột kết quả tính theo lượt nguyện vọng; “Trúng tuyển” bao gồm “Đã xác nhận nhập học”. Một nguyện vọng không trúng tuyển không có nghĩa thí sinh không trúng tuyển ở nguyện vọng khác. Phường/xã của trường lấy từ bảng phuong liên kết với truonghoc qua maphuong.</p>
    </section>
    <?php endif; ?>
    <footer class="candidate-footer">Stackers · Hệ thống quản lý tuyển sinh</footer>
</main>
<script src="/stackers_project/assets/js/danh-sach-thi-sinh.js" defer></script>
<script>

// Đồng bộ Phường/Xã <-> Trường THPT hoàn toàn ở phía trình duyệt.
(function () {
    const wardSelect = document.getElementById('filter-phuong');
    const schoolSelect = document.getElementById('filter-truong');
    if (!wardSelect || !schoolSelect) return;

    const schoolOptions = Array.from(schoolSelect.options).filter(option => option.value !== '');

    function applyWardFilter() {
        const wardId = wardSelect.value;
        for (const option of schoolOptions) {
            option.hidden = Boolean(wardId) && option.dataset.maphuong !== wardId;
            option.disabled = option.hidden;
        }
        const chosen = schoolSelect.selectedOptions[0];
        if (chosen && chosen.value && chosen.dataset.maphuong !== wardId && wardId !== '') {
            schoolSelect.value = '';
        }
    }

    wardSelect.addEventListener('change', function () {
        // Phường thay đổi: bỏ trường cũ nếu không thuộc phường mới.
        applyWardFilter();
    });

    schoolSelect.addEventListener('change', function () {
        // Trường thay đổi: cập nhật lại phường rồi giới hạn danh sách trường.
        const chosen = schoolSelect.selectedOptions[0];
        if (chosen && chosen.value) {
            wardSelect.value = chosen.dataset.maphuong || '';
        }
        applyWardFilter();
    });

    // Đồng bộ các lựa chọn khi trang mở lại sau khi bấm Thống kê.
    if (schoolSelect.value) {
        const chosen = schoolSelect.selectedOptions[0];
        if (chosen && chosen.dataset.maphuong) wardSelect.value = chosen.dataset.maphuong;
    }
    applyWardFilter();
})();

// Đóng hộp xác nhận xuất Excel mà không phụ thuộc script điều khiển "Loại thống kê".
document.getElementById('cancel-export')?.addEventListener('click', function () {
    const panel = document.getElementById('export-confirm');
    if (panel) panel.open = false;
});
</script>
</body>
</html>
