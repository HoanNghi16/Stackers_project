<?php
// Trang quản lý địa điểm thi / chấm thi (Dành cho Sở GD&ĐT).
?>
<?php
    require("../../../components/layout/header.php");
    require("../../../components/layout/sidebar.php");
?>
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Stackers - Quản lý địa điểm thi/chấm thi</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script>
        function escapeHtml(value) {
            return String(value ?? '')
                .replace(/&/g, '&amp;')
                .replace(/</g, '&lt;')
                .replace(/>/g, '&gt;')
                .replace(/"/g, '&quot;')
                .replace(/'/g, '&#039;');
        }
    </script>
</head>
<body class="bg-gray-50 text-gray-800">
    <?php
        renderHeader();
        renderSidebar();
    ?>

<div class="ml-72 pt-24 max-w-7xl mx-auto p-8">

    <!-- TIÊU ĐỀ TRANG -->
    <div class="flex justify-between items-center mb-8">
        <div>
            <h1 class="font-bold text-2xl mb-1">Quản lý địa điểm thi / chấm thi</h1>
            <p class="text-gray-500 text-sm">Phân công và thiết lập chức năng địa điểm cho các trường THPT trên địa bàn</p>
        </div>
        <button type="button" onclick="saveLocations()" class="bg-gray-900 text-white rounded-lg px-6 py-3 hover:bg-gray-800 text-sm font-medium shadow-sm">
            Lưu danh sách
        </button>
    </div>

    <!-- KHU VỰC BẢNG DANH SÁCH CÁC TRƯỜNG -->
    <div class="border bg-white rounded-xl shadow-sm overflow-hidden mb-8">
        <div class="p-4 border-b bg-gray-50 font-bold text-sm text-gray-700 flex justify-between items-center">
            <span>Danh sách các trường và phân loại địa điểm</span>
            <span class="text-xs font-normal text-gray-500">* Chọn loại địa điểm phù hợp cho từng trường</span>
        </div>

        <div class="overflow-x-auto">
            <table class="w-full text-left border-collapse">
                <thead>
                    <tr class="bg-gray-100 border-b text-sm text-gray-600">
                        <th class="p-4">Mã trường</th>
                        <th class="p-4">Tên trường THPT</th>
                        <th class="p-4">Quận / Huyện</th>
                        <th class="p-4">Phân loại địa điểm (Thi / Chấm)</th>
                        <th class="p-4 text-center">Thao tác</th>
                    </tr>
                </thead>
                <tbody id="school-table-body" class="text-sm">
                    <!-- JavaScript render danh sách trường -->
                </tbody>
            </table>
        </div>
    </div>

    <!-- KHU VỰC LỊCH SỬ THAY ĐỔI -->
    <div class="border bg-white rounded-xl shadow-sm p-6">
        <h3 class="font-bold text-base mb-3 text-gray-800">Lịch sử thay đổi gần đây (Log)</h3>
        <div id="history-log-container" class="space-y-2 text-sm text-gray-600">
            <p class="text-gray-400 italic">Chưa có thay đổi nào trong phiên làm việc này.</p>
        </div>
    </div>

</div>

<script>
    /* =========================================================
     * DỮ LIỆU DEMO DANH SÁCH CÁC TRƯỜNG THPT (ĐÃ ĐỔI SANG DẠNG A, B, C)
     * ========================================================= */
    let schoolsData = [
        { id: "TH01", name: "Trường THPT A", district: "Quận A", type: "exam" },
        { id: "TH02", name: "Trường THPT B", district: "Quận B", type: "both" },
        { id: "TH03", name: "Trường THPT C", district: "Huyện C", type: "exam" },
        { id: "TH04", name: "Trường THPT D", district: "Quận D", type: "none" },
        { id: "TH05", name: "Trường THPT E", district: "Quận E", type: "grading" }
    ];

    let actionHistory = [];

    // Khởi chạy khi tải trang
    window.onload = function() {
        renderSchoolTable();
    };

    function renderSchoolTable() {
        const tbody = document.getElementById("school-table-body");
        tbody.innerHTML = "";

        schoolsData.forEach((school, index) => {
            let tr = document.createElement("tr");
            tr.className = "border-b hover:bg-gray-50 transition";
            tr.innerHTML = `
                <td class="p-4 font-semibold text-gray-900">${escapeHtml(school.id)}</td>
                <td class="p-4 font-medium">${escapeHtml(school.name)}</td>
                <td class="p-4 text-gray-500">${escapeHtml(school.district)}</td>
                <td class="p-4">
                    <select class="border rounded-lg p-2.5 w-full bg-white text-sm outline-none focus:ring-2 focus:ring-gray-900" onchange="updateSchoolType(${index}, this.value)">
                        <option value="none" ${school.type === 'none' ? 'selected' : ''}>-- Chọn loại địa điểm --</option>
                        <option value="exam" ${school.type === 'exam' ? 'selected' : ''}>Địa điểm thi</option>
                        <option value="grading" ${school.type === 'grading' ? 'selected' : ''}>Địa điểm chấm thi</option>
                        <option value="both" ${school.type === 'both' ? 'selected' : ''}>Cả hai (Thi & Chấm thi)</option>
                    </select>
                </td>
                <td class="p-4 text-center">
                    <button type="button" onclick="clearSchoolType(${index})" class="text-red-600 hover:text-red-800 text-xs font-semibold px-3 py-1.5 rounded border border-red-200 bg-red-50 hover:bg-red-100 transition">
                        Xóa loại
                    </button>
                </td>
            `;
            tbody.appendChild(tr);
        });
    }

    function updateSchoolType(index, value) {
        schoolsData[index].type = value;
    }

    function clearSchoolType(index) {
        const school = schoolsData[index];
        if (school.type === 'none') {
            alert("Trường này hiện chưa được phân loại địa điểm.");
            return;
        }

        const confirmDelete = confirm(`Bạn có chắc chắn muốn xóa loại địa điểm của trường "${school.name}"?`);
        
        if (confirmDelete) {
            school.type = 'none';
            renderSchoolTable();

            const timeNow = new Date().toLocaleTimeString();
            actionHistory.unshift(`[${timeNow}] Đã xóa loại địa điểm của trường <strong>${escapeHtml(school.name)}</strong>.`);
            renderHistoryLog();

            alert("Cập nhật thành công");
        }
    }

    function saveLocations() {
        const hasSelected = schoolsData.some(s => s.type !== 'none');
        if (!hasSelected) {
            alert("Dữ liệu không hợp lệ: Vui lòng chọn ít nhất một địa điểm thi hoặc chấm thi trước khi lưu.");
            return;
        }

        alert("Thêm địa điểm tuyển sinh thành công!");

        const timeNow = new Date().toLocaleTimeString();
        actionHistory.unshift(`[${timeNow}] Hệ thống đã lưu thành công cấu hình danh sách địa điểm thi/chấm thi.`);
        renderHistoryLog();
    }

    function renderHistoryLog() {
        const logContainer = document.getElementById("history-log-container");
        if (actionHistory.length === 0) {
            logContainer.innerHTML = `<p class="text-gray-400 italic">Chưa có thay đổi nào trong phiên làm việc này.</p>`;
            return;
        }
        logContainer.innerHTML = actionHistory.map(log => `<div class="p-2 bg-gray-50 rounded border-l-4 border-gray-900">${log}</div>`).join('');
    }
</script>

</body>
</html>