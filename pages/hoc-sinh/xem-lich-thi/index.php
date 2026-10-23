<?php
require_once __DIR__ . '/../../components/layout/header.php';
require_once __DIR__ . '/../../components/layout/sidebar.php';

$scenario = $_GET['scenario'] ?? 'normal';

$schedules = [
    ['mon' => 'Ngữ văn', 'ngay' => '06/06/2027', 'gio' => '07:30', 'dia_diem' => 'THPT Nguyễn Thượng Hiền', 'phong' => 'A203', 'thoi_luong' => '120 phút'],
    ['mon' => 'Ngoại ngữ', 'ngay' => '06/06/2027', 'gio' => '13:30', 'dia_diem' => 'THPT Nguyễn Thượng Hiền', 'phong' => 'A203', 'thoi_luong' => '90 phút'],
    ['mon' => 'Toán', 'ngay' => '07/06/2027', 'gio' => '07:30', 'dia_diem' => 'THPT Nguyễn Thượng Hiền', 'phong' => 'A203', 'thoi_luong' => '120 phút'],
];
?>

<!doctype html>
<html lang="vi">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width,initial-scale=1">
    <title>Xem lịch thi</title>

    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="../../assets/css/output.css">
    <link rel="stylesheet" href="../../assets/css/usecase-demo.css">
</head>

<body>

    <?php renderHeader(); ?>
    <?php renderSidebar(); ?>

    <div class="min-h-screen bg-slate-50 pl-72 pt-20">

        <main class="container">

            <h1 class="page-title">Xem lịch thi</h1>
            <p class="subtitle">Tra cứu lịch thi đã được Sở GD&amp;ĐT công bố.</p>

            <form class="card" method="get">
                <label>Mô phỏng tình huống để chụp báo cáo</label>

                <div class="grid">
                    <div class="col-8">
                        <select name="scenario">
                            <option value="normal" <?= $scenario === 'normal' ? 'selected' : '' ?>>
                                Basic flow - tải lịch thi thành công
                            </option>
                            <option value="not_published" <?= $scenario === 'not_published' ? 'selected' : '' ?>>
                                Alternative 3.1 - lịch thi chưa công bố
                            </option>
                            <option value="no_profile" <?= $scenario === 'no_profile' ? 'selected' : '' ?>>
                                Exception 2.1 - không tìm thấy hồ sơ thí sinh
                            </option>
                            <option value="load_error" <?= $scenario === 'load_error' ? 'selected' : '' ?>>
                                Exception 3.2 - không thể tải lịch thi
                            </option>
                        </select>
                    </div>

                    <div class="col-4">
                        <button class="btn btn-primary" type="submit">
                            Hiển thị tình huống
                        </button>
                    </div>
                </div>
            </form>

            <?php if ($scenario === 'not_published'): ?>

                <div class="alert alert-warning">
                    Lịch thi chưa được công bố.
                </div>

            <?php elseif ($scenario === 'no_profile'): ?>

                <div class="alert alert-danger">
                    Không tìm thấy hồ sơ dự thi được liên kết với tài khoản.
                </div>

            <?php elseif ($scenario === 'load_error'): ?>

                <div class="alert alert-danger">
                    Không thể tải lịch thi, vui lòng thử lại sau.
                </div>

            <?php else: ?>

                <div class="grid">
                    <div class="col-4">
                        <div class="kpi">
                            <div class="muted">Thí sinh</div>
                            <div class="value" style="font-size:20px">Nguyễn Văn An</div>
                            <div class="muted">SBD: 102345</div>
                        </div>
                    </div>

                    <div class="col-4">
                        <div class="kpi">
                            <div class="muted">Số buổi thi</div>
                            <div class="value"><?= count($schedules) ?></div>
                        </div>
                    </div>

                    <div class="col-4">
                        <div class="kpi">
                            <div class="muted">Điểm thi</div>
                            <div class="value" style="font-size:20px">
                                THPT Nguyễn Thượng Hiền
                            </div>
                        </div>
                    </div>
                </div>

                <div class="card">
                    <h2>Lịch thi của bạn</h2>

                    <div style="overflow:auto">
                        <table>
                            <thead>
                                <tr>
                                    <th>Môn thi</th>
                                    <th>Ngày thi</th>
                                    <th>Giờ</th>
                                    <th>Địa điểm</th>
                                    <th>Phòng</th>
                                    <th></th>
                                </tr>
                            </thead>

                            <tbody>
                                <?php foreach ($schedules as $s): ?>
                                    <tr>
                                        <td><b><?= htmlspecialchars($s['mon']) ?></b></td>
                                        <td><?= htmlspecialchars($s['ngay']) ?></td>
                                        <td><?= htmlspecialchars($s['gio']) ?></td>
                                        <td><?= htmlspecialchars($s['dia_diem']) ?></td>

                                        <td>
                                            <span class="badge badge-blue">
                                                <?= htmlspecialchars($s['phong']) ?>
                                            </span>
                                        </td>

                                        <td>
                                            <button class="btn btn-outline" type="button" onclick='showDetail(<?= json_encode(
                                                                                                                    $s,
                                                                                                                    JSON_UNESCAPED_UNICODE |
                                                                                                                        JSON_HEX_APOS |
                                                                                                                        JSON_HEX_QUOT
                                                                                                                ) ?>)'>
                                                Xem chi tiết
                                            </button>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>

                    <p class="muted">
                        Alternative 5.1: nếu người dùng không chọn “Xem chi tiết”
                        thì chỉ dừng ở danh sách lịch thi tổng quát.
                    </p>
                </div>

            <?php endif; ?>

        </main>

    </div>

    <div id="detailModal" class="modal">
        <div class="modal-box">

            <div class="modal-header">
                <h2 style="margin:0">Chi tiết buổi thi</h2>

                <button class="modal-close" type="button" onclick="closeModal('detailModal')">
                    ✕
                </button>
            </div>

            <div id="detailContent"></div>

            <div class="actions">
                <button class="btn btn-primary" type="button" onclick="closeModal('detailModal')">
                    Đã hiểu
                </button>
            </div>

        </div>
    </div>

    <script src="../../assets/js/usecase-demo.js"></script>

    <script>
        function showDetail(s) {
            document.getElementById('detailContent').innerHTML = `
        <table>
            <tr><th>Môn thi</th><td><b>${s.mon}</b></td></tr>
            <tr><th>Ngày thi</th><td>${s.ngay}</td></tr>
            <tr><th>Giờ bắt đầu</th><td>${s.gio}</td></tr>
            <tr><th>Thời lượng</th><td>${s.thoi_luong}</td></tr>
            <tr><th>Địa điểm</th><td>${s.dia_diem}</td></tr>
            <tr><th>Phòng thi</th><td>${s.phong}</td></tr>
        </table>
    `;

            openModal('detailModal');
        }
    </script>

</body>

</html>