<?php
/** Danh sách thí sinh: so khớp theo manguoidung, thứ tự mã học sinh tăng dần. */
if (session_status() !== PHP_SESSION_ACTIVE) session_start();
if (($_SESSION['vaitro'] ?? '') !== 'SO_GD') {
    header('Location: /stackers_project/pages/login/');
    exit;
}

require_once __DIR__ . '/../../../components/layout/header.php';
require_once __DIR__ . '/../../../components/layout/sidebar.php';
require_once __DIR__ . '/../../../class/xem-danh-sach-thi-sinh.php';

function hTS($value): string
{
    return htmlspecialchars((string) ($value ?? ''), ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
}
function paramTS(string $key): string
{
    return is_string($_GET[$key] ?? null) ? trim($_GET[$key]) : '';
}
function ngayTS($value): string
{
    if (!is_string($value) || trim($value) === '') return 'Chưa cập nhật';
    $ngay = DateTimeImmutable::createFromFormat('!Y-m-d', $value);
    $loi = DateTimeImmutable::getLastErrors();
    if ($ngay === false || $loi !== false || $ngay->format('Y-m-d') !== $value) {
        return 'Ngày không hợp lệ';
    }
    return $ngay->format('d/m/Y');
}
function trangThaiTS($value): string
{
    if ($value === null || $value === '') return 'Chưa xác nhận dự thi';
    return match ((string) $value) {
        '1' => 'Đã đăng ký dự thi',
        '0' => 'Đã chọn không dự thi',
        default => 'Trạng thái không hợp lệ',
    };
}
function giaTriTS(string $key, $value): string
{
    if ($key === 'trangthaiduthi') return trangThaiTS($value);
    if ($key === 'ngaysinh') return ngayTS($value);
    if ($value === null || $value === '') return $key === 'sobaodanh' ? 'Chưa cấp' : 'Chưa cập nhật';
    if ($key === 'tongdiem') return number_format((float) $value, 2, ',', '.');
    return (string) $value;
}
function urlTS(array $params): string
{
    return '?' . http_build_query($params, '', '&', PHP_QUERY_RFC3986);
}
$tim = paramTS('tim');
$truong = paramTS('truong');
$ma = paramTS('chitiet');
$ds = $dsTruong = [];
$chiTiet = null;
$loi = '';
$tong = $batDau = 0;
$soTrang = $trang = 1;

try {
    require __DIR__ . '/../../../index.php';
    if (!isset($conn) || !($conn instanceof mysqli)) {
        throw new RuntimeException('Không tìm thấy kết nối mysqli $conn.');
    }
    $conn->set_charset('utf8mb4');
    $model = new DanhSachThiSinhModel($conn);
    $dsTruong = $model->danhSachTruong();
    $trangYeuCau = filter_var(paramTS('trang'), FILTER_VALIDATE_INT, [
        'options' => ['min_range' => 1],
    ]);
    $ketQua = $model->timKiem($tim, $truong, $trangYeuCau ?: 1);
    $ds = $ketQua['rows'];
    $tong = (int) $ketQua['tong'];
    $soTrang = (int) $ketQua['soTrang'];
    $trang = (int) $ketQua['trang'];
    $batDau = (int) $ketQua['batDau'];
    if ($ma !== '' && ctype_digit($ma) && (int) $ma > 0) {
        $chiTiet = $model->chiTiet((int) $ma);
    }
} catch (Throwable $e) {
    error_log('Danh sach thi sinh: ' . $e->getMessage());
    $loi = 'Không thể tải dữ liệu thí sinh. Kiểm tra PHP error log để xem nguyên nhân.';
}

$boLoc = ['tim' => $tim, 'truong' => $truong, 'trang' => $trang];
$dongUrl = urlTS($boLoc);
?>
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Danh sách thí sinh | Sở GD&amp;ĐT · Stackers</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="/stackers_project/assets/css/danh-sach-thi-sinh.css">
</head>
<body class="candidate-page">
<?php renderHeader(); renderSidebar(); ?>
<main class="candidate-main">
    <div class="candidate-heading">
        <div>
            <p class="eyebrow">SỞ GIÁO DỤC VÀ ĐÀO TẠO</p>
            <h1>Danh sách thí sinh</h1>
            <p>Tra cứu thông tin và nguyện vọng đăng ký của thí sinh.</p>
        </div>
        <button type="button" id="toggle-sidebar" aria-expanded="false" aria-controls="candidate-sidebar">Menu chức năng</button>
    </div>

    <section class="candidate-card" aria-labelledby="search-title">
        <h2 id="search-title">Tìm kiếm thí sinh</h2>
        <form method="get" class="candidate-search">
            <label>Họ tên, mã thí sinh hoặc số báo danh
                <input type="search" name="tim" value="<?= hTS($tim) ?>" placeholder="Nhập họ tên, mã học sinh hoặc số báo danh" aria-describedby="search-hint">
            </label>
            <label>Trường THCS
                <select name="truong">
                    <option value="">Tất cả trường THCS</option>
                    <?php foreach ($dsTruong as $t): ?>
                        <option value="<?= hTS($t['matruong']) ?>" <?= $truong === (string) $t['matruong'] ? 'selected' : '' ?>><?= hTS($t['tentruong']) ?></option>
                    <?php endforeach; ?>
                </select>
            </label>
            <div class="candidate-actions">
                <button class="primary" type="submit">Tìm kiếm</button>
                <a class="button" href="./">Xóa bộ lọc</a>
            </div>
        </form>
        <p id="search-hint" class="candidate-search-hint">Họ tên có thể nhập một phần; mã học sinh và số báo danh cần nhập chính xác.</p>
    </section>

    <?php if ($loi !== ''): ?>
        <p class="candidate-alert" role="alert"><?= hTS($loi) ?></p>
    <?php endif; ?>
    <?php if ($ma !== '' && !$chiTiet && $loi === ''): ?>
        <p class="candidate-alert" role="alert">Không tìm thấy học sinh được chọn. <a href="<?= hTS($dongUrl) ?>">Quay lại danh sách</a></p>
    <?php endif; ?>

    <section class="candidate-card candidate-results" aria-labelledby="list-title">
        <div class="candidate-list-heading">
            <div>
                <h2 id="list-title">Danh sách thí sinh</h2>
                <p>Sắp xếp theo mã học sinh tăng dần.</p>
            </div>
            <span class="candidate-count"><?= number_format($tong, 0, ',', '.') ?> dòng kết quả</span>
        </div>
        <div class="candidate-table-wrap">
            <table>
                <thead>
                    <tr>
                        <th scope="col">STT</th>
                        <th scope="col">Mã học sinh</th>
                        <th scope="col">Họ và tên</th>
                        <th scope="col">Ngày sinh</th>
                        <th scope="col">Số báo danh</th>
                        <th scope="col">Trường THCS</th>
                        <th scope="col">Năm tuyển sinh</th>
                        <th scope="col">Trạng thái dự thi</th>
                        <th scope="col">Thao tác</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($ds as $i => $ts): ?>
                        <?php
                            $tenThiSinh = trim((string) ($ts['hoten'] ?? ''));
                        ?>
                        <tr>
                            <td><?= $batDau + $i + 1 ?></td>
                            <td class="candidate-code"><?= hTS($ts['mahocsinh'] ?? '') ?></td>
                            <td class="candidate-name"><?= hTS($tenThiSinh !== '' ? $tenThiSinh : 'Chưa có hồ sơ/họ tên') ?></td>
                            <td>
                                <?= hTS(ngayTS($ts['ngaysinh'] ?? null)) ?>
                            </td>
                            <td><?= hTS(giaTriTS('sobaodanh', $ts['sobaodanh'] ?? null)) ?></td>
                            <td><?= hTS(giaTriTS('tentruong', $ts['tentruong'] ?? null)) ?></td>
                            <td><?= hTS($ts['namtuyensinh'] ?? '—') ?></td>
                            <td><?= hTS(trangThaiTS($ts['trangthaiduthi'] ?? null)) ?></td>
                            <td>
                                <a class="detail-link"
                                   href="<?= hTS(urlTS(array_merge($boLoc, ['chitiet' => $ts['mahocsinh']]))) ?>"
                                   aria-label="Xem chi tiết học sinh <?= hTS($ts['mahocsinh']) ?>">Xem chi tiết</a>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                    <?php if (!$ds): ?>
                        <tr>
                            <td colspan="9" class="candidate-empty">
                                <strong><?= $loi ? 'Chưa tải được danh sách.' : 'Không có học sinh phù hợp.' ?></strong>
                                <p><?= $loi ? 'Kiểm tra lỗi kết nối hoặc truy vấn.' : 'Thử xóa bộ lọc hoặc nhập từ khóa khác.' ?></p>
                            </td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </section>

    <?php if ($soTrang > 1): ?>
        <nav class="candidate-pagination" aria-label="Phân trang danh sách thí sinh">
            <?php if ($trang > 1): ?>
                <a class="button" href="<?= hTS(urlTS(array_merge($boLoc, ['trang' => $trang - 1]))) ?>">Trang trước</a>
            <?php endif; ?>
            <span>Trang <?= $trang ?> / <?= $soTrang ?> · <?= $tong > 0 ? $batDau + 1 : 0 ?>–<?= $batDau + count($ds) ?> / <?= $tong ?> thí sinh</span>
            <?php if ($trang < $soTrang): ?>
                <a class="button" href="<?= hTS(urlTS(array_merge($boLoc, ['trang' => $trang + 1]))) ?>">Trang sau</a>
            <?php endif; ?>
        </nav>
    <?php endif; ?>
    <footer class="candidate-footer">Stackers · Hệ thống quản lý tuyển sinh</footer>
</main>

<?php if ($chiTiet !== null): ?>
    <dialog id="candidate-detail" open aria-labelledby="detail-title">
        <div class="detail-heading">
            <div>
                <p class="eyebrow">HỒ SƠ THÍ SINH</p>
                <h2 id="detail-title"><?= hTS(($chiTiet['hoten'] ?? '') ?: 'Học sinh chưa có tên') ?></h2>
            </div>
            <a class="button" href="<?= hTS($dongUrl) ?>" aria-label="Đóng chi tiết">Đóng</a>
        </div>
        <div class="candidate-detail-body" tabindex="0" role="region" aria-label="Thông tin chi tiết thí sinh">
        <dl class="candidate-details">
            <?php
            $cacTruongChiTiet = [
                'mahocsinh' => 'Mã học sinh',
                'ngaysinh' => 'Ngày sinh',
                'sobaodanh' => 'Số báo danh',
                'diachi' => 'Địa chỉ',
                'tentruong' => 'Trường THCS',
                'namtuyensinh' => 'Năm tuyển sinh',
                'dantoc' => 'Dân tộc',
                'trangthaiduthi' => 'Trạng thái dự thi',
                'tongdiem' => 'Tổng điểm',
            ];
            ?>
            <?php foreach ($cacTruongChiTiet as $key => $label): ?>
                <div>
                    <dt><?= hTS($label) ?></dt>
                    <dd><?= hTS(giaTriTS($key, $chiTiet[$key] ?? null)) ?></dd>
                </div>
            <?php endforeach; ?>
        </dl>
        <div class="candidate-preferences">
            <h3>Nguyện vọng đăng ký</h3>
            <?php foreach (($chiTiet['nguyenvong'] ?? []) as $nv): ?>
                <p>
                    <span>Nguyện vọng <?= hTS($nv['thutunguyenvong'] ?? '') ?></span>
                    <strong><?= hTS(giaTriTS('tentruong', $nv['tentruong'] ?? null)) ?></strong>
                </p>
            <?php endforeach; ?>
            <?php if (empty($chiTiet['nguyenvong'])): ?>
                <p>Chưa đăng ký nguyện vọng.</p>
            <?php endif; ?>
        </div>
        </div>
    </dialog>
<?php endif; ?>
<script src="/stackers_project/assets/js/danh-sach-thi-sinh.js" defer></script>
</body>
</html>
