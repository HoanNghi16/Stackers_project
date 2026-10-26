<?php

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
    <title>Stackers - Xem danh sách điểm thi</title>
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

<div class="ml-72 pt-24 max-w-6xl mx-auto p-8">

    <!-- TIÊU ĐỀ TRANG -->
    <div class="mb-8">
        <h1 class="font-bold text-2xl mb-1">Tra cứu danh sách Điểm thi</h1>
        <p class="text-gray-500 text-sm">Hệ thống thông tin điểm thi tuyển sinh lớp 10 </p>
    </div>

    <!-- KHU VỰC TÌM KIẾM -->
    <div class="border bg-white rounded-xl p-6 mb-6 shadow-sm">
        <div class="flex flex-col md:flex-row gap-4 items-center justify-between">
            <div class="w-full md:w-1/2">
                <label for="search-input" class="block font-medium mb-2 text-sm text-gray-700">Tìm kiếm điểm thi</label>
                <div class="relative">
                    <input type="text" id="search-input" 
                           placeholder="Nhập tên trường, quận/huyện hoặc mã điểm thi..." 
                           class="border rounded-lg p-3 pl-10 w-full bg-white focus:ring-2 focus:ring-gray-900 outline-none text-sm"
                           oninput="handleSearch()">
                    <span class="absolute left-3 top-3.5 text-gray-400">
                        🔍
                    </span>
                </div>
            </div>
            <div class="text-sm text-gray-500 self-end md:self-center">
                Tổng số điểm thi: <span id="total-count" class="font-bold text-gray-900">0</span>
            </div>
        </div>
    </div>

    <!-- KHU VỰC HIỂN THỊ DANH SÁCH -->
    <div class="border bg-white rounded-xl shadow-sm overflow-hidden">
        <div class="p-4 border-b bg-gray-50 font-bold text-sm text-gray-700">
            Danh sách các điểm thi chính thức *(Nhấn vào một điểm thi để xem chi tiết)*
        </div>

        <div id="location-container" class="p-6">
            <!-- JavaScript render danh sách -->
        </div>
    </div>

</div>

<!-- MODAL XEM CHI TIẾT ĐIỂM THI -->
<div id="detail-modal" class="fixed inset-0 bg-black bg-opacity-50 hidden flex items-center justify-center z-50 p-4">
    <div class="bg-white rounded-2xl max-w-lg w-full p-6 shadow-xl relative">
        <div class="flex justify-between items-center mb-4 border-b pb-3">
            <h3 class="font-bold text-lg text-gray-900" id="modal-title">Chi tiết điểm thi</h3>
            <button onclick="closeModal()" class="text-gray-400 hover:text-gray-600 font-bold text-xl">&times;</button>
        </div>
        <div class="space-y-3 text-sm text-gray-700 mb-6">
            <p><strong>Mã điểm thi:</strong> <span id="modal-id" class="text-gray-900 font-semibold"></span></p>
            <p><strong>Tên trường:</strong> <span id="modal-name" class="text-gray-900 font-semibold"></span></p>
            <p><strong>Địa chỉ:</strong> <span id="modal-address"></span></p>
            <p><strong>Khu vực:</strong> <span id="modal-district"></span></p>
            <p><strong>Số phòng thi:</strong> <span id="modal-rooms" class="bg-blue-50 text-blue-700 px-2 py-1 rounded font-medium"></span></p>
            <p><strong>Ghi chú:</strong> <span id="modal-note" class="italic text-gray-500"></span></p>
        </div>
        <div class="flex justify-end">
            <button type="button" onclick="closeModal()" class="bg-gray-900 text-white px-5 py-2 rounded-lg text-sm font-medium hover:bg-gray-800">Đóng</button>
        </div>
    </div>
</div>

<script>
    const mockExamLocations = [
        { id: "DT01", name: "Trường A", address: "Địa chỉ A", district: "Địa điểm A", rooms: 30, note: "Điểm thi chuyên" },
        { id: "DT02", name: "Trường B", address: "Địa chỉ B", district: "Địa điểm B", rooms: 35, note: "Điểm thi thường" },
        { id: "DT03", name: "Trường C", address: "Địa chỉ C", district: "Địa điểm C", rooms: 20, note: "Điểm thi khu vực" },
        { id: "DT04", name: "Trường D", address: "Địa chỉ D", district: "Địa điểm D", rooms: 24, note: "Điểm thi thường" },
        { id: "DT05", name: "Trường E", address: "Địa chỉ E", district: "Địa điểm E", rooms: 28, note: "Điểm thi thường" }
    ];

    let currentData = [...mockExamLocations];

    window.onload = function() {
        checkAndRenderInitialData();
    };

    function checkAndRenderInitialData() {
        const container = document.getElementById("location-container");
        const totalCountEl = document.getElementById("total-count");

        if (mockExamLocations.length === 0) {
            totalCountEl.innerText = "0";
            container.innerHTML = `
                <div class="rounded-xl border border-yellow-200 bg-yellow-50 p-6 text-center text-yellow-800 font-medium">
                    ⚠️ Hiện tại sở GD chưa thiết lập điểm thi nào.
                </div>
            `;
            document.getElementById("search-input").disabled = true;
            return;
        }

        renderLocations(currentData);
    }

    function renderLocations(data) {
        const container = document.getElementById("location-container");
        const totalCountEl = document.getElementById("total-count");
        
        totalCountEl.innerText = data.length;

        if (data.length === 0) {
            container.innerHTML = `
                <div class="rounded-xl border border-gray-200 bg-gray-50 p-8 text-center text-gray-500">
                    <p class="font-medium text-lg text-gray-700">Không tìm thấy dữ liệu</p>
                    <p class="text-sm mt-1">Không có điểm thi nào phù hợp với từ khóa bạn vừa nhập. Vui lòng thử lại.</p>
                </div>
            `;
            return;
        }

        let html = '<div class="grid grid-cols-1 md:grid-cols-2 gap-4">';
        data.forEach(item => {
            html += `
                <div onclick="viewDetail('${item.id}')" class="border rounded-xl p-5 hover:shadow-md transition bg-white flex flex-col justify-between cursor-pointer hover:border-gray-400">
                    <div>
                        <div class="flex justify-between items-start mb-2">
                            <span class="font-bold text-lg text-gray-900">${escapeHtml(item.name)}</span>
                            <span class="bg-gray-100 text-gray-700 text-xs px-2.5 py-1 rounded-full font-semibold">${escapeHtml(item.id)}</span>
                        </div>
                        <p class="text-sm text-gray-600 mb-3 flex items-center gap-2">
                            <span>📍</span> ${escapeHtml(item.address)}
                        </p>
                    </div>
                    <div class="border-t pt-3 flex justify-between items-center text-xs text-gray-500">
                        <span>Khu vực: <strong>${escapeHtml(item.district)}</strong></span>
                        <div class="flex items-center gap-2">
                            <span class="bg-blue-50 text-blue-700 px-2.5 py-1 rounded font-medium">${item.rooms} Phòng thi</span>
                            <span class="text-gray-900 font-semibold underline">Xem chi tiết &rarr;</span>
                        </div>
                    </div>
                </div>
            `;
        });
        html += '</div>';

        container.innerHTML = html;
    }

    // Hàm mở Modal xem chi tiết
    function viewDetail(id) {
        const item = mockExamLocations.find(s => s.id === id);
        if (!item) return;

        document.getElementById("modal-id").innerText = item.id;
        document.getElementById("modal-name").innerText = item.name;
        document.getElementById("modal-address").innerText = item.address;
        document.getElementById("modal-district").innerText = item.district;
        document.getElementById("modal-rooms").innerText = item.rooms + " Phòng thi";
        document.getElementById("modal-note").innerText = item.note || "Không có ghi chú";

        document.getElementById("detail-modal").classList.remove("hidden");
    }

    // Hàm đóng Modal
    function closeModal() {
        document.getElementById("detail-modal").classList.add("hidden");
    }

    function handleSearch() {
        const keyword = document.getElementById("search-input").value.toLowerCase().trim();

        if (!keyword) {
            currentData = [...mockExamLocations];
        } else {
            currentData = mockExamLocations.filter(item => 
                item.name.toLowerCase().includes(keyword) || 
                item.address.toLowerCase().includes(keyword) || 
                item.district.toLowerCase().includes(keyword) ||
                item.id.toLowerCase().includes(keyword)
            );
        }

        renderLocations(currentData);
    }
</script>

</body>
</html>