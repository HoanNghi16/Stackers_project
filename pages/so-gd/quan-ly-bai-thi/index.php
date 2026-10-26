<?php
// Trang quản lý bài thi (Cập nhật vắng thi, điểm trừ và sinh danh sách phách).
?>
<?php
    require("../../../components/layout/header.php");
    require("../../../components/layout/sidebar.php")
?>
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Stackers - Quản lý bài thi</title>
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
    <style>
        .exam-row {
            display: grid;
            grid-template-columns: 80px minmax(180px, 1fr) 120px 140px minmax(200px, 1fr);
            gap: 16px;
            align-items: center;
            padding: 12px 14px;
            border-bottom: 1px solid #e5e7eb;
        }
        @media (max-width: 900px) {
            .exam-row {
                grid-template-columns: 1fr;
                gap: 8px;
            }
        }
    </style>
</head>
<body class="bg-gray-50 text-gray-800">
    <?php
        renderHeader();
        renderSidebar();
    ?>

<div class="ml-72 pt-24 max-w-7xl mx-auto p-8">

    <!-- TIÊU ĐỀ TRANG -->
    <div class="mb-8">
        <h1 class="font-bold text-2xl mb-1">Quản lý bài thi</h1>
        <p class="text-gray-500 text-sm">Cập nhật trạng thái vắng thi, điểm trừ vi phạm và tổng hợp danh sách bài thi</p>
    </div>

    <!-- BƯỚC 3 & 4: CHỌN ĐIỂM THI VÀ MÔN THI -->
    <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mb-6">
        <div class="border bg-white rounded-xl p-5 shadow-sm">
            <label for="location-select" class="block font-medium mb-2 text-sm text-gray-700">1. Chọn Điểm thi</label>
            <select id="location-select" class="border rounded-lg p-3 w-full bg-white focus:ring-2 focus:ring-gray-900 outline-none" onchange="handleLocationChange()">
                <option value="">-- Chọn điểm thi --</option>
                <option value="loc-a">Trường THPT Chuyên A</option>
                <option value="loc-b">Trường THPT B</option>
                <option value="loc-c">Trường THPT C</option>
            </select>
        </div>

        <div class="border bg-white rounded-xl p-5 shadow-sm">
            <label for="subject-select" class="block font-medium mb-2 text-sm text-gray-700">2. Chọn Môn thi</label>
            <select id="subject-select" class="border rounded-lg p-3 w-full bg-white focus:ring-2 focus:ring-gray-900 outline-none" onchange="handleSubjectChange()">
                <option value="">-- Chọn môn thi --</option>
                <option value="math">Toán</option>
                <option value="literature">Ngữ Văn</option>
                <option value="english">Tiếng Anh</option>
            </select>
        </div>
    </div>

    <!-- BƯỚC 5 & 6: HIỂN THỊ VÀ CHỌN PHÒNG THI -->
    <div id="step-rooms" class="border bg-white rounded-xl p-6 mb-6 shadow-sm hidden">
        <h2 class="font-bold text-lg mb-3">3. Chọn Phòng thi</h2>
        <div id="room-list-container" class="flex flex-wrap gap-3">
            <!-- JavaScript render danh sách phòng thi -->
        </div>
    </div>

    <!-- BƯỚC 7 & 8: DANH SÁCH CHI TIẾT BÀI THI THÍ SINH TRONG PHÒNG -->
    <div id="step-exams" class="border bg-white rounded-xl p-6 shadow-sm hidden">
        <div class="flex flex-col md:flex-row justify-between items-start md:items-center border-b pb-4 mb-4 gap-4">
            <div>
                <h2 class="font-bold text-xl" id="current-room-title">Phòng thi</h2>
                <p class="text-sm text-gray-500 mt-1">Cập nhật vắng thi hoặc nhập điểm trừ (tối đa 10 điểm)</p>
            </div>
            <div class="flex items-center gap-3">
                <button type="button" onclick="confirmCancelChanges()" class="border bg-white px-4 py-2 rounded-lg hover:bg-gray-100 text-sm font-medium">
                    Hủy thay đổi
                </button>
                <button type="button" onclick="saveExamData()" class="bg-gray-900 text-white rounded-lg px-6 py-2 hover:bg-gray-800 text-sm font-medium">
                    Lưu tất cả
                </button>
            </div>
        </div>

        <div class="overflow-x-auto">
            <div class="min-w-[750px]">
                <!-- Header bảng -->
                <div class="grid grid-cols-[80px_minmax(180px,1fr)_120px_140px_minmax(200px,1fr)] gap-4 px-4 py-2 bg-gray-100 rounded-lg font-semibold text-sm text-gray-600 mb-2">
                    <div>Số báo danh</div>
                    <div>Họ tên thí sinh</div>
                    <div>Vắng thi</div>
                    <div>Điểm trừ</div>
                    <div>Ghi chú lỗi vi phạm</div>
                </div>
                <!-- Body danh sách thí sinh -->
                <div id="student-exam-list" class="space-y-2">
                    <!-- JavaScript render -->
                </div>
            </div>
        </div>
    </div>

    <!-- BƯỚC 12 & 13: KHU VỰC HIỂN THỊ KẾT QUẢ DANH SÁCH BÀI THI HOÀN CHỈNH (SAU KHI LƯU) -->
    <div id="step-result" class="border bg-white rounded-xl p-6 mt-8 shadow-sm hidden">
        <div class="bg-green-50 border border-green-200 text-green-700 p-4 rounded-lg mb-6 font-medium">
            ✅ Cập nhật thông tin và tạo danh sách phách thành công!
        </div>
        <h3 class="font-bold text-lg mb-4">Danh sách bài thi hoàn chỉnh (Đã sinh số phách)</h3>
        <div class="overflow-x-auto">
            <table class="w-full text-left border-collapse">
                <thead>
                    <tr class="bg-gray-100 border-b text-sm text-gray-600">
                        <th class="p-3">Số phách</th>
                        <th class="p-3">SBD</th>
                        <th class="p-3">Họ tên thí sinh</th>
                        <th class="p-3">Điểm thi</th>
                        <th class="p-3">Môn thi</th>
                        <th class="p-3">Phòng thi</th>
                        <th class="p-3">Trạng thái vắng</th>
                        <th class="p-3">Điểm trừ</th>
                    </tr>
                </thead>
                <tbody id="completed-exam-table" class="text-sm">
                    <!-- JavaScript render kết quả -->
                </tbody>
            </table>
        </div>
    </div>

</div>

<script>
    /* =========================================================
     * DỮ LIỆU DEMO BÀI THI THEO ĐIỂM THI & MÔN THI
     * ========================================================= */
    const mockRooms = {
        "loc-a_math": [
            { roomId: "room-01", roomName: "Phòng 01", students: [
                { sbd: "A0101", name: "Nguyễn Văn An", absent: false, deduction: 0, note: "" },
                { sbd: "A0102", name: "Trần Thị Bình", absent: false, deduction: 0, note: "" },
                { sbd: "A0103", name: "Lê Hoàng Cường", absent: true, deduction: 0, note: "Vắng có phép" }
            ]},
            { roomId: "room-02", roomName: "Phòng 02", students: [
                { sbd: "A0201", name: "Phạm Minh Đức", absent: false, deduction: 1.5, note: "Mang tài liệu trái phép" },
                { sbd: "A0202", name: "Hoàng Thị Mai", absent: false, deduction: 0, note: "" }
            ]}
        ],
        "loc-a_literature": [
            { roomId: "room-01", roomName: "Phòng 01", students: [
                { sbd: "A0101", name: "Nguyễn Văn An", absent: false, deduction: 0, note: "" },
                { sbd: "A0102", name: "Trần Thị Bình", absent: false, deduction: 0, note: "" }
            ]}
        ]
    };

    let selectedLocation = "";
    let selectedSubject = "";
    let currentRoomId = "";
    let currentStudentsData = []; // Lưu state tạm thời cho phòng đang chỉnh sửa
    let hasUnsavedChanges = false;

    // Theo dõi thay đổi dữ liệu để kích hoạt Alt Flow 8.1 (Cảnh báo chưa lưu)
    document.addEventListener('input', () => { hasUnsavedChanges = true; });
    document.addEventListener('change', () => { hasUnsavedChanges = true; });

    function handleLocationChange() {
        selectedLocation = document.getElementById("location-select").value;
        checkAndShowRooms();
    }

    function handleSubjectChange() {
        selectedSubject = document.getElementById("subject-select").value;
        checkAndShowRooms();
    }

    function checkAndShowRooms() {
        const roomStep = document.getElementById("step-rooms");
        const examStep = document.getElementById("step-exams");
        examStep.classList.add("hidden");

        if (!selectedLocation || !selectedSubject) {
            roomStep.classList.add("hidden");
            return;
        }

        const key = `${selectedLocation}_${selectedSubject}`;
        const rooms = mockRooms[key];

        // Alternative Flow 3.1: Kiểm tra chưa có dữ liệu bài thi
        if (!rooms || rooms.length === 0) {
            alert("Chưa có dữ liệu bài thi cho kỳ thi này. Vui lòng kiểm tra lại cấu hình kỳ thi.");
            roomStep.classList.add("hidden");
            return;
        }

        roomStep.classList.remove("hidden");
        renderRooms(rooms);
    }

    function renderRooms(rooms) {
        const container = document.getElementById("room-list-container");
        container.innerHTML = "";

        rooms.forEach(room => {
            const btn = document.createElement("button");
            btn.type = "button";
            btn.className = `border px-5 py-3 rounded-xl font-medium transition hover:bg-gray-900 hover:text-white ${currentRoomId === room.roomId ? 'bg-gray-900 text-white' : 'bg-white'}`;
            btn.innerText = room.roomName;
            btn.onclick = () => selectRoom(room.roomId);
            container.appendChild(btn);
        });
    }

    function selectRoom(roomId) {
        // Alt Flow 8.1: Kiểm tra dữ liệu chưa lưu nếu chuyển phòng khác
        if (hasUnsavedChanges) {
            const confirmLeave = confirm("Dữ liệu vừa thay đổi chưa được lưu. Bạn có chắc chắn muốn chuyển phòng?");
            if (!confirmLeave) return;
        }

        currentRoomId = roomId;
        hasUnsavedChanges = false;

        const key = `${selectedLocation}_${selectedSubject}`;
        const room = mockRooms[key].find(r => r.roomId === roomId);
        if (!room) return;

        // Clone dữ liệu để thao tác tạm thời
        currentStudentsData = JSON.parse(JSON.stringify(room.students));

        document.getElementById("current-room-title").innerText = `Phòng thi: ${room.roomName}`;
        document.getElementById("step-exams").classList.remove("hidden");
        
        renderStudents();
    }

    function renderStudents() {
        const container = document.getElementById("student-exam-list");
        container.innerHTML = "";

        currentStudentsData.forEach((st, index) => {
            const row = document.createElement("div");
            row.className = "exam-row bg-white border rounded-lg items-center";
            row.innerHTML = `
                <div class="font-semibold text-sm">${escapeHtml(st.sbd)}</div>
                <div class="font-medium">${escapeHtml(st.name)}</div>
                <div>
                    <label class="inline-flex items-center gap-2 cursor-pointer text-sm">
                        <input type="checkbox" class="w-4 h-4 rounded text-gray-900" ${st.absent ? 'checked' : ''} onchange="updateAbsent(${index}, this.checked)">
                        Vắng
                    </label>
                </div>
                <div>
                    <input type="number" step="0.25" min="0" max="10" value="${st.deduction}" class="border rounded-lg p-2 w-24 text-sm outline-none focus:ring-1 focus:ring-gray-900 deduction-input-${index}" oninput="updateDeduction(${index}, this.value)">
                </div>
                <div>
                    <input type="text" value="${escapeHtml(st.note)}" placeholder="Nhập lỗi nếu có..." class="border rounded-lg p-2 w-full text-sm outline-none focus:ring-1 focus:ring-gray-900" oninput="updateNote(${index}, this.value)">
                </div>
            `;
            container.appendChild(row);
        });
    }

    function updateAbsent(index, val) {
        currentStudentsData[index].absent = val;
    }

    function updateDeduction(index, val) {
        currentStudentsData[index].deduction = val;
    }

    function updateNote(index, val) {
        currentStudentsData[index].note = val;
    }

    function confirmCancelChanges() {
        // Alt Flow 8.1
        const confirmExit = confirm("Dữ liệu vừa thay đổi chưa được lưu. Bạn có chắc chắn muốn thoát?");
        if (confirmExit) {
            hasUnsavedChanges = false;
            document.getElementById("step-exams").classList.add("hidden");
            currentRoomId = "";
        }
    }

    function saveExamData() {
        // Bước 10: Kiểm tra tính hợp lệ dữ liệu (Alternative Flow 10.1)
        for (let i = 0; i < currentStudentsData.length; i++) {
            let deductionVal = parseFloat(currentStudentsData[i].deduction);
            if (isNaN(deductionVal) || deductionVal < 0 || deductionVal > 10) {
                alert(`Lỗi ở thí sinh ${currentStudentsData[i].name}: Điểm trừ phải là số hợp lệ từ 0 đến 10.`);
                const inputEl = document.querySelector(`.deduction-input-${i}`);
                if (inputEl) {
                    inputEl.classList.add("border-red-500", "bg-red-50");
                    inputEl.focus();
                }
                return;
            }
        }

        hasUnsavedChanges = false;

        // Bước 11 & 12 & 13: Lưu thành công, tạo danh sách phách hoàn chỉnh
        const locationText = document.getElementById("location-select").options[document.getElementById("location-select").selectedIndex].text;
        const subjectText = document.getElementById("subject-select").options[document.getElementById("subject-select").selectedIndex].text;
        
        const resultTable = document.getElementById("completed-exam-table");
        resultTable.innerHTML = "";

        currentStudentsData.forEach((st, idx) => {
            let randomPhach = 1000 + Math.floor(Math.random() * 9000); // Giả lập sinh số phách ngẫu nhiên
            let tr = document.createElement("tr");
            tr.className = "border-b hover:bg-gray-50";
            tr.innerHTML = `
                <td class="p-3 font-bold text-gray-900">P-${randomPhach}</td>
                <td class="p-3">${escapeHtml(st.sbd)}</td>
                <td class="p-3 font-medium">${escapeHtml(st.name)}</td>
                <td class="p-3">${locationText}</td>
                <td class="p-3">${subjectText}</td>
                <td class="p-3">Phòng ${currentRoomId.replace('room-', '')}</td>
                <td class="p-3">${st.absent ? '<span class="text-red-600 font-semibold">Vắng thi</span>' : 'Có mặt'}</td>
                <td class="p-3">${st.deduction > 0 ? `<span class="text-orange-600 font-semibold">-${st.deduction}đ</span>` : '0'}</td>
            `;
            resultTable.appendChild(tr);
        });

        document.getElementById("step-result").classList.remove("hidden");
        window.scrollTo({ top: document.body.scrollHeight, behavior: 'smooth' });
    }
</script>

</body>
</html>