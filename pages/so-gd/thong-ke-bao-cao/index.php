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
        header('Content-Type: text/csv; charset=UTF-8');
        header('Content-Disposition: attachment; filename="bao-cao-tuyen-sinh-' . date('Ymd-His') . '.csv"');
        $out = fopen('php://output', 'w');
        ThongKeBaoCao::xuatCsv($saved, $out);
        fclose($out);
        exit;
    }
}
$f = array_fill_keys(['loai', 'ky', 'truong', 'khuvuc', 'nv', 'ketqua'], '');
foreach ($f as $key => $_) $f[$key] = getTK($key);
$options = ['ky' => [], 'truong' => [], 'khuvuc' => []];
$requested = getTK('action') === 'thongke';
try {
    require __DIR__ . '/../../../index.php';
    $model = new ThongKeBaoCao($conn);
    $options = $model->tieuChi();
    if (!$requested && $_SERVER['REQUEST_METHOD'] !== 'POST') {
        $f['loai'] = $f['loai'] ?: 'truong';
        $f['ky'] = $f['ky'] ?: (string)($options['ky'][0]['makythi'] ?? '');
    }
    if ($_SERVER['REQUEST_METHOD'] === 'GET' && !$errors) {
        $errors = ThongKeBaoCao::kiemTra($f, $options);
        if (!$errors) {
            $report = $model->thongKe($f);
            $years = array_column($options['ky'], 'namtuyensinh', 'makythi');
            $schools = array_column($options['truong'], 'tentruong', 'matruong');
            $report['criteria'] = [
                'Loại thống kê' => ThongKeBaoCao::LOAI[$f['loai']],
                'Năm tuyển sinh' => $years[$f['ky']],
                'Trường THPT' => $schools[$f['truong']] ?? 'Tất cả trường THPT',
                'Khu vực trường THPT' => $f['khuvuc'] ?: 'Tất cả khu vực',
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
            <label>Loại thống kê *<select name="loai" id="report-type" required><option value="">Chọn loại thống kê</option><?php foreach (ThongKeBaoCao::LOAI as $value => $label): ?><option value="<?= hTK($value) ?>" <?= $f['loai'] === $value ? 'selected' : '' ?>><?= hTK($label) ?></option><?php endforeach; ?></select></label>
            <label>Năm tuyển sinh *<select name="ky" required><option value="">Chọn năm tuyển sinh</option><?php foreach ($options['ky'] as $ky): ?><option value="<?= hTK($ky['makythi']) ?>" <?= $f['ky'] === (string)$ky['makythi'] ? 'selected' : '' ?>><?= hTK($ky['namtuyensinh']) ?></option><?php endforeach; ?></select></label>
            <label data-filter="truong">Trường THPT<select name="truong"><option value="">Tất cả trường THPT</option><?php foreach ($options['truong'] as $school): ?><option value="<?= hTK($school['matruong']) ?>" <?= $f['truong'] === (string)$school['matruong'] ? 'selected' : '' ?>><?= hTK($school['tentruong']) ?></option><?php endforeach; ?></select></label>
            <label data-filter="khuvuc">Khu vực trường THPT<select name="khuvuc"><option value="">Tất cả khu vực</option><?php foreach ($options['khuvuc'] as $area): ?><option value="<?= hTK($area) ?>" <?= $f['khuvuc'] === $area ? 'selected' : '' ?>><?= hTK($area) ?></option><?php endforeach; ?></select></label>
            <label>Nguyện vọng<select name="nv"><option value="">Tất cả nguyện vọng</option><?php foreach (['1','2','3'] as $nv): ?><option value="<?= $nv ?>" <?= $f['nv'] === $nv ? 'selected' : '' ?>>Nguyện vọng <?= $nv ?></option><?php endforeach; ?></select></label>
            <label data-filter="ketqua">Kết quả nguyện vọng<select name="ketqua"><option value="">Tất cả kết quả</option><?php foreach (ThongKeBaoCao::KET_QUA as $value => $label): ?><option value="<?= hTK($value) ?>" <?= $f['ketqua'] === $value ? 'selected' : '' ?>><?= hTK($label) ?></option><?php endforeach; ?></select></label>
            <p class="report-help" id="type-help" aria-live="polite">Chọn loại thống kê để nhóm kết quả theo trường THPT, khu vực hoặc kết quả nguyện vọng. Các bộ lọc có thể kết hợp.</p>
            <div class="candidate-actions"><button class="primary" type="submit">Thống kê</button><a class="button" href="./">Đặt lại</a></div>
        </form>
    </section>
    <?php if ($report): ?>
    <section aria-labelledby="result-title">
        <div class="report-result-heading"><div><h2 id="result-title">Kết quả thống kê</h2><p><?= hTK($report['criteria']['Loại thống kê']) ?> · Năm <?= hTK($report['criteria']['Năm tuyển sinh']) ?> · <?= hTK($report['time']) ?></p></div>
        <?php if ($report['rows']): ?><details class="report-export" id="export-confirm"><summary class="button">Xuất báo cáo</summary><div class="candidate-card"><h3>Xuất báo cáo CSV</h3><p>Tải kết quả đang hiển thị, kèm tiêu chí thống kê. Có thể mở bằng Excel.</p><form method="post" action="./"><input type="hidden" name="report" value="<?= hTK($token) ?>"><div class="candidate-actions"><button class="primary" type="submit">Tải báo cáo</button><button type="button" id="cancel-export">Hủy</button></div></form></div></details><?php endif; ?></div>
        <div class="report-applied"><?php foreach (array_slice($report['criteria'], 2) as $label => $value): ?><span><?= hTK($label) ?>: <strong><?= hTK($value) ?></strong></span><?php endforeach; ?></div>
        <?php if (!$report['rows']): ?><div class="candidate-card candidate-empty" role="status"><strong>Không có dữ liệu thống kê.</strong><p>Vui lòng thay đổi tiêu chí thống kê và thử lại.</p></div>
        <?php else: ?>
        <div class="report-metrics"><?php foreach (['thisinh' => 'Thí sinh không trùng', 'luot' => 'Lượt nguyện vọng', 'dau' => 'Lượt trúng tuyển', 'nhaphoc' => 'Đã xác nhận nhập học'] as $key => $label): ?><div class="candidate-card"><p><?= hTK($label) ?></p><strong><?= number_format((int)$report['total'][$key], 0, ',', '.') ?></strong></div><?php endforeach; ?></div>
        <?php renderBieuDoThongKe($report['rows']); ?>
        <div class="candidate-card candidate-results"><div class="candidate-table-wrap"><table><caption class="report-caption">Kết quả theo lượt nguyện vọng đã đăng ký</caption><thead><tr><?php foreach (ThongKeBaoCao::COT as $label): ?><th scope="col"><?= hTK($label) ?></th><?php endforeach; ?></tr></thead><tbody><?php foreach ($report['rows'] as $row): ?><tr><?php foreach (ThongKeBaoCao::COT as $key => $_): ?><td><?= $key === 'nhom' ? hTK($row[$key]) : number_format((int)$row[$key], 0, ',', '.') ?></td><?php endforeach; ?></tr><?php endforeach; ?></tbody><tfoot><tr><th scope="row">Tổng (thí sinh không trùng)</th><?php foreach (array_slice(array_keys(ThongKeBaoCao::COT), 1) as $key): ?><td><?= number_format((int)$report['total'][$key], 0, ',', '.') ?></td><?php endforeach; ?></tr></tfoot></table></div></div>
        <?php endif; ?>
        <p class="report-help">Chỉ tính hồ sơ đã đăng ký nguyện vọng. Mỗi thí sinh có thể thuộc nhiều nhóm; tổng thí sinh được đếm không trùng. Các cột kết quả tính theo lượt nguyện vọng; “Trúng tuyển” bao gồm “Đã xác nhận nhập học”. Một nguyện vọng không trúng tuyển không có nghĩa thí sinh không trúng tuyển ở nguyện vọng khác. Khu vực lấy theo phần đầu địa chỉ trường THPT.</p>
    </section>
    <?php endif; ?>
    <footer class="candidate-footer">Stackers · Hệ thống quản lý tuyển sinh</footer>
</main>
<script src="/stackers_project/assets/js/danh-sach-thi-sinh.js" defer></script>
<script src="/stackers_project/assets/js/thong-ke-bao-cao.js" defer></script>
</body>
</html>
