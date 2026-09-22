<?php
$scenario = $_GET['scenario'] ?? 'normal';
$teachers = [
    ['id' => 1, 'name' => 'Nguyễn Thị Lan', 'subject' => 'Ngữ văn', 'code' => 'GV001', 'eligible' => true],
    ['id' => 2, 'name' => 'Trần Minh Quân', 'subject' => 'Toán', 'code' => 'GV002', 'eligible' => true],
    ['id' => 3, 'name' => 'Lê Hoàng Mai', 'subject' => 'Tiếng Anh', 'code' => 'GV003', 'eligible' => true],
    ['id' => 4, 'name' => 'Phạm Quốc Huy', 'subject' => 'Vật lý', 'code' => 'GV004', 'eligible' => true],
];
?>
<!doctype html>
<html lang="vi">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width,initial-scale=1">
    <title>Chọn giáo viên tham gia hội đồng thi</title>
    <link rel="stylesheet" href="../../assets/css/usecase-demo.css">
</head>

<body>
    <header class="topbar">
        <div class="brand">STACKERS · Trường THCS/THPT</div>
        <a href="../../index.php"><small>← Danh sách Use Case</small></a>
    </header>
    <main class="container">
        <h1 class="page-title">Chọn giáo viên tham gia hội đồng thi</h1>
        <p class="subtitle">Chọn giáo viên thủ công hoặc nhập danh sách từ Excel.</p>

        <form class="card" method="get">
            <label>Mô phỏng tình huống</label>
            <div class="grid">
                <div class="col-8">
                    <select name="scenario">
                        <option value="normal" <?= $scenario === 'normal' ? 'selected' : '' ?>>Basic flow - dữ liệu hợp lệ
                        </option>
                        <option value="invalid_excel" <?= $scenario === 'invalid_excel' ? 'selected' : '' ?>>Exception 3.2.1
                            - file Excel không hợp lệ</option>
                        <option value="teacher_missing" <?= $scenario === 'teacher_missing' ? 'selected' : '' ?>>Exception
                            3.2.2 - GV không tồn tại</option>
                        <option value="teacher_ineligible" <?= $scenario === 'teacher_ineligible' ? 'selected' : '' ?>>
                            Exception 3.2.3 - GV không đủ điều kiện</option>
                        <option value="wrong_quota" <?= $scenario === 'wrong_quota' ? 'selected' : '' ?>>Exception 6.1 - số
                            lượng không đúng chỉ tiêu</option>
                    </select>
                </div>
                <div class="col-4"><button class="btn btn-primary" type="submit">Hiển thị tình huống</button></div>
            </div>
        </form>

        <div class="grid">
            <div class="col-4">
                <div class="kpi">
                    <div class="muted">Chỉ tiêu cần đăng ký</div>
                    <div class="value">3</div>
                    <div class="muted">giáo viên</div>
                </div>
            </div>
            <div class="col-4">
                <div class="kpi">
                    <div class="muted">Đã chọn hợp lệ</div>
                    <div id="selectedCount" class="value">0</div>
                    <div class="muted">giáo viên</div>
                </div>
            </div>
            <div class="col-4">
                <div class="kpi">
                    <div class="muted">Trạng thái</div>
                    <div style="margin-top:10px"><span id="statusBadge" class="badge badge-gray">Chưa lưu</span>
                    </div>
                </div>
            </div>
        </div>

        <div class="card">
            <h2>1. Chọn phương thức</h2>
            <div class="tabs">
                <button type="button" class="btn tab-btn active" id="manualTab"
                    onclick="switchMethod('manual')">Chọn thủ công</button>
                <button type="button" class="btn tab-btn" id="excelTab" onclick="switchMethod('excel')">Nhập từ
                    Excel</button>
            </div>

            <div id="manualPanel">
                <div class="alert alert-info">Danh sách bên dưới chỉ hiển thị giáo viên đáp ứng điều kiện tham gia
                    hội đồng thi.</div>
                <div class="grid">
                    <div class="col-6"><input id="teacherSearch" placeholder="Tìm theo tên/mã giáo viên..."
                            oninput="filterTeachers()"></div>
                    <div class="col-6"><select id="subjectFilter" onchange="filterTeachers()">
                            <option value="">Tất cả bộ môn</option>
                            <option>Ngữ văn</option>
                            <option>Toán</option>
                            <option>Tiếng Anh</option>
                            <option>Vật lý</option>
                        </select></div>
                </div>
                <table style="margin-top:14px">
                    <thead>
                        <tr>
                            <th>Chọn</th>
                            <th>Mã GV</th>
                            <th>Họ tên</th>
                            <th>Bộ môn</th>
                            <th>Điều kiện</th>
                        </tr>
                    </thead>
                    <tbody id="teacherBody">
                        <?php foreach ($teachers as $t): ?>
                            <tr data-name="<?= mb_strtolower($t['name'] . ' ' . $t['code']) ?>"
                                data-subject="<?= $t['subject'] ?>">
                                <td><input style="width:auto" type="checkbox" class="teacher-check"
                                        data-code="<?= $t['code'] ?>" data-name="<?= $t['name'] ?>"
                                        data-subject="<?= $t['subject'] ?>" onchange="syncManual()"></td>
                                <td><?= $t['code'] ?></td>
                                <td><b><?= $t['name'] ?></b></td>
                                <td><?= $t['subject'] ?></td>
                                <td><span class="badge badge-green">Đủ điều kiện</span></td>
                            </tr>
                        <?php endforeach ?>
                    </tbody>
                </table>
            </div>

            <div id="excelPanel" class="hidden">
                <label>Chọn file Excel</label>
                <input id="excelFile" type="file" accept=".xlsx,.xls">
                <p class="muted">File cần đúng mẫu hệ thống hỗ trợ. Demo này mô phỏng kết quả đối chiếu, chưa đọc
                    Excel thật.</p>
                <div class="actions"><button class="btn btn-primary" type="button" onclick="simulateExcel()">Đọc và
                        kiểm tra file</button></div>
                <div id="excelAlert" class="alert hidden"></div>
                <div id="excelResult"></div>
            </div>
        </div>

        <div class="card">
            <h2>2. Danh sách giáo viên đã chọn/nhập</h2>
            <div id="quotaAlert" class="alert hidden"></div>
            <table>
                <thead>
                    <tr>
                        <th>Mã GV</th>
                        <th>Họ tên</th>
                        <th>Bộ môn</th>
                        <th>Trạng thái</th>
                        <th></th>
                    </tr>
                </thead>
                <tbody id="selectedBody">
                    <tr id="emptyRow">
                        <td colspan="5" class="empty">Chưa có giáo viên nào được chọn.</td>
                    </tr>
                </tbody>
            </table>
            <div class="actions">
                <button class="btn btn-secondary" type="button" onclick="saveDraft()">Lưu danh sách</button>
                <button class="btn btn-success" type="button" onclick="sendList()">Gửi danh sách</button>
            </div>
        </div>
    </main>

    <div id="sendModal" class="modal">
        <div class="modal-box">
            <div class="modal-header">
                <h2 style="margin:0">Xác nhận gửi danh sách</h2><button class="modal-close"
                    onclick="closeModal('sendModal')">✕</button>
            </div>
            <p>Danh sách sẽ được lưu chính thức và chuyển sang trạng thái “Đã gửi/Chờ phân công”.</p>
            <div class="actions"><button class="btn btn-secondary" onclick="closeModal('sendModal')">Quay
                    lại</button><button class="btn btn-success" onclick="confirmSend()">Xác nhận gửi</button></div>
        </div>
    </div>

    <script src="../../assets/js/usecase-demo.js"></script>
    <script>
        const scenario = <?= json_encode($scenario) ?>;
        const quota = 3;
        let selected = [];

        function switchMethod(method) {
            document.getElementById('manualPanel').classList.toggle('hidden', method !== 'manual');
            document.getElementById('excelPanel').classList.toggle('hidden', method !== 'excel');
            document.getElementById('manualTab').classList.toggle('active', method === 'manual');
            document.getElementById('excelTab').classList.toggle('active', method === 'excel');
        }

        function filterTeachers() {
            const q = document.getElementById('teacherSearch').value.toLowerCase();
            const subject = document.getElementById('subjectFilter').value;
            document.querySelectorAll('#teacherBody tr').forEach(tr => {
                const ok = tr.dataset.name.includes(q) && (!subject || tr.dataset.subject === subject);
                tr.style.display = ok ? '' : 'none';
            });
        }

        function syncManual() {
            selected = [...document.querySelectorAll('.teacher-check:checked')].map(c => ({
                code: c.dataset.code,
                name: c.dataset.name,
                subject: c.dataset.subject,
                status: 'Hợp lệ'
            }));
            renderSelected();
        }

        function simulateExcel() {
            clearAlert('excelAlert');
            const file = document.getElementById('excelFile').files[0];
            if (!file) {
                showAlert('excelAlert', 'warning', 'Vui lòng chọn file Excel trước.');
                return;
            }
            if (scenario === 'invalid_excel') {
                showAlert('excelAlert', 'danger', 'File Excel không hợp lệ, vui lòng kiểm tra và tải lại.');
                return;
            }
            let html =
                '<table><thead><tr><th>Dòng</th><th>Giáo viên</th><th>Kết quả đối chiếu</th></tr></thead><tbody>';
            selected = [{
                    code: 'GV001',
                    name: 'Nguyễn Thị Lan',
                    subject: 'Ngữ văn',
                    status: 'Hợp lệ'
                },
                {
                    code: 'GV002',
                    name: 'Trần Minh Quân',
                    subject: 'Toán',
                    status: 'Hợp lệ'
                }
            ];
            html += '<tr><td>2</td><td>Nguyễn Thị Lan</td><td><span class="badge badge-green">Hợp lệ</span></td></tr>';
            html += '<tr><td>3</td><td>Trần Minh Quân</td><td><span class="badge badge-green">Hợp lệ</span></td></tr>';

            if (scenario === 'teacher_missing') {
                html +=
                    '<tr><td>4</td><td>Nguyễn Văn Không Có</td><td><span class="badge badge-red">Không tồn tại trong danh sách giáo viên của trường</span></td></tr>';
                showAlert('excelAlert', 'warning',
                    'Giáo viên Nguyễn Văn Không Có không tồn tại trong danh sách giáo viên của trường. Giáo viên này không được thêm.'
                );
            } else if (scenario === 'teacher_ineligible') {
                html +=
                    '<tr><td>4</td><td>Phạm Thị B</td><td><span class="badge badge-red">Không đủ điều kiện</span><br><small>Chưa đáp ứng điều kiện theo quy định hội đồng thi.</small></td></tr>';
                showAlert('excelAlert', 'warning',
                    'Giáo viên Phạm Thị B không đủ điều kiện tham gia hội đồng thi. Giáo viên này không được thêm.');
            } else {
                selected.push({
                    code: 'GV003',
                    name: 'Lê Hoàng Mai',
                    subject: 'Tiếng Anh',
                    status: 'Hợp lệ'
                });
                html +=
                    '<tr><td>4</td><td>Lê Hoàng Mai</td><td><span class="badge badge-green">Hợp lệ</span></td></tr>';
                showAlert('excelAlert', 'success', 'Đã đọc file và đối chiếu danh sách giáo viên thành công.');
            }
            html += '</tbody></table>';
            document.getElementById('excelResult').innerHTML = html;
            renderSelected();
        }

        function renderSelected() {
            const body = document.getElementById('selectedBody');
            if (!selected.length) {
                body.innerHTML = '<tr><td colspan="5" class="empty">Chưa có giáo viên nào được chọn.</td></tr>';
            } else {
                body.innerHTML = selected.map((t, i) => `
      <tr><td>${t.code}</td><td><b>${t.name}</b></td><td>${t.subject}</td>
      <td><span class="badge badge-green">${t.status}</span></td>
      <td><button class="btn btn-outline" type="button" onclick="removeTeacher(${i})">Loại</button></td></tr>`).join(
                    '');
            }
            document.getElementById('selectedCount').textContent = selected.length;
        }

        function removeTeacher(i) {
            selected.splice(i, 1);
            renderSelected();
        }

        function checkQuota() {
            const box = document.getElementById('quotaAlert');
            if (scenario === 'wrong_quota' || selected.length !== quota) {
                showAlert('quotaAlert', 'danger',
                    `Số lượng giáo viên không hợp lệ. Chỉ tiêu là ${quota}, hiện có ${selected.length}. Vui lòng bổ sung hoặc loại bớt giáo viên.`
                );
                return false;
            }
            clearAlert('quotaAlert');
            return true;
        }

        function saveDraft() {
            if (!selected.length) {
                showAlert('quotaAlert', 'warning', 'Chưa có giáo viên trong danh sách để lưu.');
                return;
            }
            document.getElementById('statusBadge').className = 'badge badge-yellow';
            document.getElementById('statusBadge').textContent = 'Bản nháp';
            showAlert('quotaAlert', 'success', 'Đã lưu danh sách ở trạng thái “Bản nháp”.');
        }

        function sendList() {
            if (!checkQuota()) return;
            openModal('sendModal');
        }

        function confirmSend() {
            closeModal('sendModal');
            document.getElementById('statusBadge').className = 'badge badge-blue';
            document.getElementById('statusBadge').textContent = 'Đã gửi / Chờ phân công';
            showAlert('quotaAlert', 'success', 'Danh sách hợp lệ đã được gửi thành công cho Sở GD&ĐT.');
        }
    </script>
</body>

</html>