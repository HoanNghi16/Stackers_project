<?php
require_once __DIR__ . '/../../components/layout/header.php';
require_once __DIR__ . '/../../components/layout/sidebar.php';

$scenario = $_GET['scenario'] ?? 'normal';

$subjects = [
    ['id' => 'van', 'name' => 'Ngữ văn', 'score' => '7.25', 'status' => 'Có thể phúc khảo'],
    ['id' => 'anh', 'name' => 'Ngoại ngữ', 'score' => '8.00', 'status' => 'Có thể phúc khảo'],
    ['id' => 'toan', 'name' => 'Toán', 'score' => '6.75', 'status' => 'Có thể phúc khảo'],
];
?>

<!doctype html>
<html lang="vi">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width,initial-scale=1">
    <title>Đăng ký phúc khảo</title>

    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="../../assets/css/output.css">
    <link rel="stylesheet" href="../../assets/css/usecase-demo.css">
</head>

<body>

    <?php renderHeader(); ?>
    <?php renderSidebar(); ?>

    <div class="min-h-screen bg-slate-50 pl-72 pt-20">

        <main class="container">

            <h1 class="page-title">Đăng ký phúc khảo</h1>
            <p class="subtitle">
                Chọn một hoặc nhiều môn thi cần phúc khảo và gửi phiếu yêu cầu.
            </p>

            <form class="card" method="get">
                <label>Mô phỏng tình huống</label>

                <div class="grid">
                    <div class="col-8">
                        <select name="scenario">
                            <option value="normal" <?= $scenario === 'normal' ? 'selected' : '' ?>>
                                Basic flow - được phép đăng ký
                            </option>

                            <option value="not_started" <?= $scenario === 'not_started' ? 'selected' : '' ?>>
                                Alternative 2.1 - chưa đến thời gian
                            </option>

                            <option value="expired" <?= $scenario === 'expired' ? 'selected' : '' ?>>
                                Alternative 2.2 - hết thời hạn
                            </option>

                            <option value="no_result" <?= $scenario === 'no_result' ? 'selected' : '' ?>>
                                Alternative 2.3 - chưa có kết quả thi
                            </option>

                            <option value="duplicate" <?= $scenario === 'duplicate' ? 'selected' : '' ?>>
                                Alternative 6.2 - môn đã đăng ký
                            </option>

                            <option value="save_error" <?= $scenario === 'save_error' ? 'selected' : '' ?>>
                                Exception 10.1 - lỗi lưu phiếu
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

            <?php if ($scenario === 'not_started'): ?>

                <div class="alert alert-warning">
                    Chưa đến thời gian đăng ký phúc khảo.
                    Thời gian bắt đầu tiếp nhận: 20/06/2027 08:00.
                </div>

            <?php elseif ($scenario === 'expired'): ?>

                <div class="alert alert-danger">
                    Đã hết thời hạn đăng ký phúc khảo.
                    Hệ thống không cho phép tạo phiếu mới.
                </div>

            <?php elseif ($scenario === 'no_result'): ?>

                <div class="alert alert-warning">
                    Chưa có kết quả thi để đăng ký phúc khảo.
                </div>

            <?php else: ?>

                <div class="card">
                    <h2>1. Chọn môn cần phúc khảo</h2>

                    <div style="overflow-x:auto">
                        <table>
                            <thead>
                                <tr>
                                    <th>Chọn</th>
                                    <th>Môn thi</th>
                                    <th>Điểm công bố</th>
                                    <th>Trạng thái</th>
                                </tr>
                            </thead>

                            <tbody>
                                <?php foreach ($subjects as $s): ?>
                                    <tr>
                                        <td>
                                            <input style="width:auto" type="checkbox" class="subject"
                                                value="<?= htmlspecialchars($s['id']) ?>"
                                                data-name="<?= htmlspecialchars($s['name']) ?>"
                                                data-score="<?= htmlspecialchars($s['score']) ?>">
                                        </td>

                                        <td><b><?= htmlspecialchars($s['name']) ?></b></td>
                                        <td><?= htmlspecialchars($s['score']) ?></td>

                                        <td>
                                            <span class="badge badge-green">
                                                <?= htmlspecialchars($s['status']) ?>
                                            </span>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>

                    <?php if ($scenario === 'duplicate'): ?>
                        <div class="alert alert-warning">
                            Môn thi này đã được đăng ký phúc khảo.
                        </div>
                    <?php endif; ?>
                </div>

                <div class="card">
                    <h2>2. Thông tin đăng ký</h2>

                    <div id="formAlert" class="alert hidden"></div>

                    <div class="grid">
                        <div class="col-12">
                            <label>
                                Lý do phúc khảo
                                <span style="color:#dc2626">*</span>
                            </label>

                            <textarea id="reason" placeholder="Nhập lý do đề nghị phúc khảo..."></textarea>
                        </div>

                        <div class="col-6">
                            <label>Minh chứng đính kèm</label>

                            <input id="evidence" type="file" accept=".pdf,.jpg,.jpeg,.png">

                            <small class="muted">
                                Demo giao diện: PDF/JPG/PNG, tối đa 5MB.
                            </small>
                        </div>

                        <div class="col-6">
                            <label>Thông tin liên hệ</label>
                            <input id="contact" value="0901234567">
                        </div>
                    </div>

                    <div class="actions">
                        <button class="btn btn-primary" type="button" onclick="reviewRequest()">
                            Gửi đăng ký
                        </button>

                        <button class="btn btn-secondary" type="button" onclick="clearForm()">
                            Nhập lại
                        </button>
                    </div>
                </div>

            <?php endif; ?>

        </main>

    </div>

    <div id="confirmModal" class="modal">
        <div class="modal-box">
            <div class="modal-header">
                <h2 style="margin:0">Xác nhận phiếu phúc khảo</h2>

                <button class="modal-close" type="button" onclick="closeModal('confirmModal')">
                    ✕
                </button>
            </div>

            <div id="confirmContent"></div>

            <div class="actions">
                <button class="btn btn-secondary" type="button" onclick="closeModal('confirmModal')">
                    Quay lại chỉnh sửa
                </button>

                <button class="btn btn-danger" type="button" onclick="cancelRequest()">
                    Hủy
                </button>

                <button class="btn btn-success" type="button" onclick="submitRequest()">
                    Xác nhận gửi
                </button>
            </div>
        </div>
    </div>

    <div id="resultModal" class="modal">
        <div class="modal-box">
            <div class="modal-header">
                <h2 style="margin:0">Kết quả</h2>

                <button class="modal-close" type="button" onclick="closeModal('resultModal')">
                    ✕
                </button>
            </div>

            <div id="resultContent"></div>

            <div class="actions">
                <button class="btn btn-primary" type="button" onclick="closeModal('resultModal')">
                    Đóng
                </button>
            </div>
        </div>
    </div>

    <script src="../../assets/js/usecase-demo.js"></script>

    <script>
        const scenario = <?= json_encode($scenario) ?>;

        function selectedSubjects() {
            return [...document.querySelectorAll('.subject:checked')].map(x => ({
                name: x.dataset.name,
                score: x.dataset.score
            }));
        }

        function reviewRequest() {
            clearAlert('formAlert');

            const subjects = selectedSubjects();
            const reason = document.getElementById('reason').value.trim();
            const file = document.getElementById('evidence').files[0];
            const contact = document.getElementById('contact').value;

            if (!subjects.length) {
                showAlert(
                    'formAlert',
                    'warning',
                    'Vui lòng chọn ít nhất một môn cần phúc khảo.'
                );
                return;
            }

            if (!reason) {
                showAlert(
                    'formAlert',
                    'danger',
                    'Bạn đang nhập thiếu thông tin bắt buộc: Lý do phúc khảo.'
                );
                return;
            }

            if (file) {
                const allowedTypes = ['application/pdf', 'image/jpeg', 'image/png'];

                if (!allowedTypes.includes(file.type) || file.size > 5 * 1024 * 1024) {
                    showAlert(
                        'formAlert',
                        'danger',
                        'Tệp minh chứng không hợp lệ. Vui lòng chọn lại tệp đúng định dạng và dung lượng.'
                    );
                    return;
                }
            }

            document.getElementById('confirmContent').innerHTML = `
        <p>
            <b>Môn đăng ký:</b>
            ${subjects.map(s => `${s.name} (${s.score})`).join(', ')}
        </p>

        <p><b>Lý do:</b> ${reason}</p>
        <p><b>Liên hệ:</b> ${contact}</p>
        <p><b>Minh chứng:</b> ${file ? file.name : 'Không đính kèm'}</p>

        <div class="alert alert-info">
            Kiểm tra lại thông tin trước khi xác nhận gửi.
        </div>
    `;

            openModal('confirmModal');
        }

        function submitRequest() {
            closeModal('confirmModal');

            document.getElementById('resultContent').innerHTML =
                scenario === 'save_error' ?
                `
            <div class="alert alert-danger">
                Đăng ký phúc khảo không thành công, vui lòng thử lại.
                Hệ thống không tạo phiếu trùng.
            </div>
        ` :
                `
            <div class="alert alert-success">
                Đăng ký phúc khảo thành công.
            </div>

            <p><b>Mã phiếu:</b> PK-2027-000128</p>

            <p>
                <b>Trạng thái:</b>
                <span class="badge badge-yellow">Chờ xử lý</span>
            </p>
        `;

            openModal('resultModal');
        }

        function cancelRequest() {
            closeModal('confirmModal');

            document.getElementById('resultContent').innerHTML = `
        <div class="alert alert-info">
            Đã hủy đăng ký.
            Hệ thống không tạo phiếu phúc khảo.
        </div>
    `;

            openModal('resultModal');
        }

        function clearForm() {
            document.getElementById('reason').value = '';
            document.getElementById('contact').value = '0901234567';
            document.getElementById('evidence').value = '';

            document.querySelectorAll('.subject')
                .forEach(x => x.checked = false);

            clearAlert('formAlert');
        }
    </script>

</body>

</html>