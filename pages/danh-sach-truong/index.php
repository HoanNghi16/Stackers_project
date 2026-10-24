<?php
require_once __DIR__ . '/../components/layout/header.php';
require_once __DIR__ . '/../components/layout/sidebar.php';

$scenario = $_GET['scenario'] ?? 'normal';
?>

<!doctype html>
<html lang="vi">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width,initial-scale=1">
    <title>Xem danh sách trường</title>

    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="../assets/css/output.css">
    <link rel="stylesheet" href="../assets/css/usecase-demo.css">
</head>

<body>

    <?php renderHeader(); ?>
    <?php renderSidebar(); ?>

    <div class="min-h-screen bg-slate-50 pl-72 pt-20">

        <main class="container">

            <h1 class="page-title">Danh sách trường tuyển sinh lớp 10</h1>
            <p class="subtitle">
                Tra cứu các trường tham gia tuyển sinh và thông tin tuyển sinh liên quan.
            </p>

            <form class="card" method="get">
                <label>Mô phỏng tình huống</label>

                <div class="grid">
                    <div class="col-8">
                        <select name="scenario">
                            <option value="normal" <?= $scenario === 'normal' ? 'selected' : '' ?>>
                                Basic flow - tải danh sách thành công
                            </option>

                            <option value="load_error" <?= $scenario === 'load_error' ? 'selected' : '' ?>>
                                Exception 2.1 - lỗi tải danh sách
                            </option>

                            <option value="detail_error" <?= $scenario === 'detail_error' ? 'selected' : '' ?>>
                                Exception 7.1 - lỗi tải chi tiết
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

            <?php if ($scenario === 'load_error'): ?>

                <div class="alert alert-danger">
                    Không thể tải danh sách trường, vui lòng thử lại sau.
                </div>

            <?php else: ?>

                <div class="card">
                    <div class="grid">

                        <div class="col-6">
                            <label>Tìm kiếm theo tên trường</label>
                            <input id="schoolSearch" placeholder="VD: Nguyễn Thượng Hiền..." oninput="filterSchools()">
                        </div>

                        <div class="col-3">
                            <label>Khu vực</label>

                            <select id="areaFilter" onchange="filterSchools()">
                                <option value="">Tất cả khu vực</option>
                                <option value="Tân Bình">Tân Bình</option>
                                <option value="Quận 1">Quận 1</option>
                                <option value="Quận 3">Quận 3</option>
                            </select>
                        </div>

                        <div class="col-3">
                            <label>Loại trường</label>

                            <select id="typeFilter" onchange="filterSchools()">
                                <option value="">Tất cả loại trường</option>
                                <option value="Công lập">Công lập</option>
                                <option value="Chuyên">Chuyên</option>
                            </select>
                        </div>

                    </div>
                </div>

                <div id="noResult" class="alert alert-warning hidden">
                    Không tìm thấy trường phù hợp.
                </div>

                <div id="schoolGrid" class="grid"></div>

            <?php endif; ?>

        </main>

    </div>

    <div id="schoolModal" class="modal">
        <div class="modal-box">

            <div class="modal-header">
                <h2 id="schoolTitle" style="margin:0">Chi tiết trường</h2>

                <button class="modal-close" type="button" onclick="closeModal('schoolModal')">
                    ✕
                </button>
            </div>

            <div id="schoolDetail"></div>

            <div class="actions">
                <button class="btn btn-primary" type="button" onclick="closeModal('schoolModal')">
                    Quay lại danh sách
                </button>
            </div>

        </div>
    </div>

    <script src="../assets/js/usecase-demo.js"></script>

    <script>
        const scenario = <?= json_encode($scenario) ?>;

        const schools = [{
                name: 'THPT Nguyễn Thượng Hiền',
                type: 'Công lập',
                area: 'Tân Bình',
                address: '649 Hoàng Văn Thụ, Tân Bình, TP.HCM',
                quota: 730,
                method: 'Thi tuyển',
                note: 'Tuyển sinh lớp 10 theo kế hoạch của Sở GD&ĐT.'
            },
            {
                name: 'THPT Bùi Thị Xuân',
                type: 'Công lập',
                area: 'Quận 1',
                address: '73-75 Bùi Thị Xuân, Quận 1, TP.HCM',
                quota: 700,
                method: 'Thi tuyển',
                note: 'Thông tin minh họa cho giao diện.'
            },
            {
                name: 'THPT Chuyên Lê Hồng Phong',
                type: 'Chuyên',
                area: 'Quận 3',
                address: '235 Nguyễn Văn Cừ, Quận 5, TP.HCM',
                quota: 500,
                method: 'Thi tuyển + môn chuyên',
                note: 'Thông tin minh họa cho giao diện.'
            }
        ];

        function renderSchools(list) {
            const grid = document.getElementById('schoolGrid');
            if (!grid) return;

            grid.innerHTML = list.map((school, index) => `
        <div class="col-4 school-item">
            <div class="school-card">

                <span class="badge ${
                    school.type === 'Chuyên'
                    ? 'badge-yellow'
                    : 'badge-blue'
                }">
                    ${school.type}
                </span>

                <h3>${school.name}</h3>

                <p><b>Khu vực:</b> ${school.area}</p>
                <p><b>Địa chỉ:</b> ${school.address}</p>

                <div class="actions">
                    <button class="btn btn-primary"
                        type="button"
                        onclick="viewSchool(${index})">
                        Xem chi tiết
                    </button>
                </div>

            </div>
        </div>
    `).join('');

            document.getElementById('noResult')
                .classList.toggle('hidden', list.length > 0);
        }

        function filterSchools() {
            const keyword = document.getElementById('schoolSearch').value.toLowerCase();
            const area = document.getElementById('areaFilter').value;
            const type = document.getElementById('typeFilter').value;

            const filtered = schools.filter(school =>
                school.name.toLowerCase().includes(keyword) &&
                (!area || school.area === area) &&
                (!type || school.type === type)
            );

            renderSchools(filtered);
        }

        function viewSchool(index) {
            if (scenario === 'detail_error') {
                document.getElementById('schoolTitle').textContent = 'Không thể tải thông tin';
                document.getElementById('schoolDetail').innerHTML =
                    '<div class="alert alert-danger">Không thể tải thông tin chi tiết của trường.</div>';

                openModal('schoolModal');
                return;
            }

            const school = schools[index];

            document.getElementById('schoolTitle').textContent = school.name;

            document.getElementById('schoolDetail').innerHTML = `
        <table>
            <tr><th>Loại trường</th><td>${school.type}</td></tr>
            <tr><th>Khu vực</th><td>${school.area}</td></tr>
            <tr><th>Địa chỉ</th><td>${school.address}</td></tr>
            <tr><th>Chỉ tiêu</th><td>${school.quota} học sinh</td></tr>
            <tr><th>Phương thức tuyển sinh</th><td>${school.method}</td></tr>
            <tr><th>Thông tin liên quan</th><td>${school.note}</td></tr>
        </table>
    `;

            openModal('schoolModal');
        }

        renderSchools(schools);
    </script>

</body>

</html>