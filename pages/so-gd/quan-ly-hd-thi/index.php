<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Stackers - Quản lý Hội đồng thi</title>
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
.assignment-row {
    display: grid;
    grid-template-columns: minmax(220px, 1fr) auto auto;
    gap: 16px;
    align-items: center;
    padding: 12px 14px;
    border-bottom: 1px solid #e5e7eb;
}
.teacher-info {
    display: flex;
    flex-direction: column;
    gap: 3px;
}
.teacher-name { font-weight: 600; }
.teacher-meta { font-size: 12px; color: #6b7280; }
.role-option {
    display: inline-flex;
    align-items: center;
    gap: 7px;
    white-space: nowrap;
    cursor: pointer;
}
.role-panel {
    margin-top: 18px;
}
.muted {
    color: #6b7280;
    font-size: 14px;
}
@media (max-width: 720px) {
    .assignment-row {
        grid-template-columns: 1fr;
        gap: 8px;
    }
}
</style>
</head>

<body class="bg-gray-50 text-gray-800">
<div class="max-w-6xl mx-auto p-8">
    <div id="step-council-type">
        <h1 class="text-center font-bold text-2xl mb-2">
            Quản lý các Hội đồng thi
        </h1>

        <p class="text-center text-gray-500 mb-8">
            Chọn loại Hội đồng thi cần phân công
        </p>

        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4">
            <button type="button"
                    onclick="selectCouncilType('examiner')"
                    class="border bg-white rounded-xl p-6 text-left hover:shadow-md transition">
                <div class="font-bold text-lg">Ra đề</div>
                <div class="text-sm text-gray-500 mt-1">
                    Phân công giáo viên theo môn thi
                </div>
            </button>

            <button type="button"
                    onclick="selectCouncilType('proctor')"
                    class="border bg-white rounded-xl p-6 text-left hover:shadow-md transition">
                <div class="font-bold text-lg">Gác thi</div>
                <div class="text-sm text-gray-500 mt-1">
                    Phân công giáo viên tại các điểm thi
                </div>
            </button>

            <button type="button"
                    onclick="selectCouncilType('grader')"
                    class="border bg-white rounded-xl p-6 text-left hover:shadow-md transition">
                <div class="font-bold text-lg">Chấm thi</div>
                <div class="text-sm text-gray-500 mt-1">
                    Phân công giáo viên theo môn thi
                </div>
            </button>

            <button type="button"
                    onclick="selectCouncilType('reviewer')"
                    class="border bg-white rounded-xl p-6 text-left hover:shadow-md transition">
                <div class="font-bold text-lg">Phúc khảo</div>
                <div class="text-sm text-gray-500 mt-1">
                    Phân công giáo viên theo môn thi
                </div>
            </button>
        </div>
    </div>


    <!-- =====================================================
         STEP 2A: CHỌN MÔN THI
         Dùng cho:
         - Ra đề
         - Chấm thi
         - Phúc khảo

         Theo Alternative Flow 4.1
    ====================================================== -->
    <div id="step-subject" class="hidden">
        <div class="flex justify-between items-center mb-6">
            <div>
                <h2 class="font-bold text-xl">Chọn môn thi</h2>
                <p class="text-sm text-gray-500 mt-1">
                    Giáo viên phải thuộc môn thi và có tên trong danh sách đề cử từ các trường.
                </p>
            </div>

            <button type="button"
                    onclick="goBackToCouncilType()"
                    class="border bg-white px-4 py-2 rounded-lg hover:bg-gray-100">
                Quay lại
            </button>
        </div>

        <div class="border bg-white rounded-xl p-5">
            <label for="subject-select" class="block font-medium mb-2">
                Môn thi
            </label>

            <select id="subject-select"
                    class="border rounded-lg p-3 w-full"
                    onchange="selectSubject()">
                <option value="">-- Chọn môn thi --</option>
                <option value="math">Toán</option>
                <option value="literature">Ngữ Văn</option>
                <option value="english">Tiếng Anh</option>
            </select>
        </div>
    </div>


    <!-- =====================================================
         STEP 2B: PHÂN CÔNG HỘI ĐỒNG THEO MÔN
         Theo Alternative Flow:
         1. Chọn môn
         2. Hiển thị GV hợp lệ
         3. Chọn GV
         4. Chọn trưởng HĐ
         5. Chọn GV dự phòng
         6. Lưu
    ====================================================== -->
    <div id="step-teachers" class="hidden mt-8">
        <div class="flex justify-between items-center mb-6">
            <div>
                <h2 class="font-bold text-xl">Phân công Hội đồng thi</h2>

                <div class="mt-2 text-sm">
                    Môn:
                    <span id="selected-subject"
                          class="inline-block border rounded px-3 py-1 bg-white font-medium">
                    </span>
                </div>
            </div>

            <button type="button"
                    onclick="goBackToSubject()"
                    class="border bg-white px-4 py-2 rounded-lg hover:bg-gray-100">
                Quay lại
            </button>
        </div>

        <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">

            <!-- Giáo viên hợp lệ -->
            <div class="border bg-white rounded-xl">
                <div class="p-4 border-b">
                    <div class="font-bold">Danh sách giáo viên hợp lệ</div>
                    <div class="text-sm text-gray-500 mt-1">
                        Giáo viên thuộc môn đã chọn và có tên trong danh sách đề cử.
                    </div>
                </div>

                <div id="teacher-list" class="p-4 space-y-3">
                    <!-- Javascript render -->
                </div>
            </div>

            <!-- Trưởng hội đồng & Giáo viên dự phòng -->
            <div class="border bg-white rounded-xl">
                <div class="p-4 border-b">
                    <div class="font-bold">Chọn trưởng Hội đồng &amp; giáo viên dự phòng</div>
                    <div class="text-sm text-gray-500 mt-1">
                        Chỉ giáo viên đã được chọn tham gia mới hiện ở đây. Mỗi giáo viên chỉ được chọn một vai trò.
                    </div>
                </div>

                <div id="subject-assignment-list" class="p-4">
                    <p class="text-gray-500">
                        Vui lòng chọn giáo viên tham gia hội đồng trước.
                    </p>
                </div>
            </div>
        </div>

        <div class="mt-6 flex justify-end">
            <button type="button"
                    onclick="createSubjectCouncil()"
                    class="bg-gray-900 text-white rounded-lg px-6 py-3 hover:bg-gray-800">
                Lưu danh sách
            </button>
        </div>
    </div>


    <!-- =====================================================
         STEP 2C: GÁC THI
         Theo Basic Flow:
         4. Hiển thị điểm thi + GV chưa phân công
         5. Chọn điểm thi
         6. Chọn nhiều GV
         7. Thêm GV vào điểm thi
         8. Chọn trưởng HĐ
         9. Chọn GV dự phòng
         10. Lưu
    ====================================================== -->
    <div id="step-proctor" class="hidden">
        <div class="flex justify-between items-center mb-6">
            <div>
                <h2 class="font-bold text-xl">Phân công Hội đồng Gác thi</h2>
                <p class="text-sm text-gray-500 mt-1">
                    Chọn điểm thi và phân công giáo viên chưa được phân công.
                </p>
            </div>

            <button type="button"
                    onclick="goBackToCouncilType()"
                    class="border bg-white px-4 py-2 rounded-lg hover:bg-gray-100">
                Quay lại
            </button>
        </div>

        <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">

            <!-- Điểm thi -->
            <div class="border bg-white rounded-xl">
                <div class="p-4 border-b font-bold">
                    Danh sách điểm thi
                </div>

                <div class="p-4">
                    <label for="location-select" class="block font-medium mb-2">
                        Chọn điểm thi
                    </label>

                    <select id="location-select"
                            class="border rounded-lg p-3 w-full"
                            onchange="renderSelectedLocationAssignments()">
                        <option value="">-- Chọn điểm thi --</option>
                        <option value="school-a">Trường THPT A</option>
                        <option value="school-b">Trường THPT B</option>
                        <option value="school-c">Trường THPT C</option>
                        <option value="school-d">Trường THPT D</option>
                    </select>
                </div>
            </div>

            <!-- GV chưa phân công -->
            <div class="border bg-white rounded-xl">
                <div class="p-4 border-b">
                    <div class="font-bold">Danh sách giáo viên chưa phân công</div>
                    <div class="text-sm text-gray-500 mt-1">
                        Chọn nhiều giáo viên để thêm vào điểm thi.
                    </div>
                </div>

                <div id="proctor-teacher-list" class="p-4 space-y-3">
                    <!-- Javascript render -->
                </div>
            </div>
        </div>

        <div class="mt-6 flex justify-center">
            <button type="button"
                    onclick="addTeachersToLocation()"
                    class="border bg-white rounded-lg px-6 py-3 hover:bg-gray-100">
                Thêm giáo viên vào điểm thi
            </button>
        </div>

        <!-- Phân công hiện tại -->
        <div class="mt-8 border bg-white rounded-xl">
            <div class="p-4 border-b font-bold">
                Phân công hiện tại
            </div>

            <div id="assignment-list" class="p-4">
                <p class="text-gray-500">
                    Chưa có phân công nào.
                </p>
            </div>
        </div>

        <!-- Trưởng hội đồng & Giáo viên dự phòng -->
        <div class="mt-6 border bg-white rounded-xl">
            <div class="p-4 border-b">
                <div class="font-bold">Chọn trưởng Hội đồng &amp; giáo viên dự phòng</div>
                <div class="text-sm text-gray-500 mt-1">
                    Chỉ giáo viên đã được phân công mới hiện ở đây. Mỗi giáo viên chỉ được chọn một vai trò.
                </div>
            </div>

            <div id="proctor-role-list" class="p-4">
                <p class="text-gray-500">
                    Vui lòng phân công giáo viên trước.
                </p>
            </div>
        </div>

        <div class="mt-6 flex justify-end">
            <button type="button"
                    onclick="createProctorCouncil()"
                    class="bg-gray-900 text-white rounded-lg px-6 py-3 hover:bg-gray-800">
                Lưu danh sách
            </button>
        </div>
    </div>

</div>


<script>
    /*
     * =========================================================
     * DATA DEMO
     * =========================================================
     */

    let councilType = null;

    const subjects = {
        math: "Toán",
        literature: "Ngữ Văn",
        english: "Tiếng Anh"
    };

    /*
     * Danh sách giáo viên được đề cử từ các trường.
     * Trong hệ thống thật, dữ liệu này sẽ lấy từ database/API.
     */
    const teachers = {
        math: [
            { id: 1, name: "Nguyễn Văn A", nominated: true },
            { id: 2, name: "Nguyễn Thị B", nominated: true },
            { id: 3, name: "Trần Văn C", nominated: true },
            { id: 4, name: "Lê Văn D", nominated: true }
        ],
        literature: [
            { id: 5, name: "Lê Thị D", nominated: true },
            { id: 6, name: "Phạm Văn E", nominated: true },
            { id: 7, name: "Hoàng Thị F", nominated: true }
        ],
        english: [
            { id: 8, name: "Nguyễn Văn G", nominated: true },
            { id: 9, name: "Trần Thị H", nominated: true },
            { id: 10, name: "Lê Văn I", nominated: true }
        ]
    };

    /*
     * Giáo viên dùng cho Hội đồng Gác thi.
     * Đây là danh sách GV chưa được phân công trong demo.
     */
    const proctorTeachers = [
        { id: 1, name: "Nguyễn Văn A", nominated: true },
        { id: 2, name: "Nguyễn Thị B", nominated: true },
        { id: 3, name: "Trần Văn C", nominated: true },
        { id: 4, name: "Trần Thị D", nominated: true },
        { id: 5, name: "Lê Văn E", nominated: true }
    ];

    /*
     * Giáo viên đã được phân công vào các điểm thi.
     *
     * {
     *   locationId,
     *   locationName,
     *   teacherId,
     *   teacherName
     * }
     */
    let proctorAssignments = [];


    /*
     * =========================================================
     * CHỌN LOẠI HỘI ĐỒNG
     * =========================================================
     */
    function selectCouncilType(type) {
        councilType = type;

        hideAllSteps();

        if (type === "proctor") {
            document.getElementById("step-proctor")
                .classList.remove("hidden");

            renderProctorTeachers();
            renderSelectedLocationAssignments();
            renderProctorRoles();

            return;
        }

        // Ra đề / Chấm thi / Phúc khảo phải chọn môn.
        document.getElementById("step-subject")
            .classList.remove("hidden");
    }


    /*
     * =========================================================
     * ẨN TẤT CẢ STEP
     * =========================================================
     */
    function hideAllSteps() {
        document.getElementById("step-council-type")
            .classList.add("hidden");

        document.getElementById("step-subject")
            .classList.add("hidden");

        document.getElementById("step-teachers")
            .classList.add("hidden");

        document.getElementById("step-proctor")
            .classList.add("hidden");
    }


    /*
     * =========================================================
     * QUAY LẠI CHỌN LOẠI HỘI ĐỒNG
     * =========================================================
     */
    function goBackToCouncilType() {
        hideAllSteps();

        document.getElementById("step-council-type")
            .classList.remove("hidden");
    }


    /*
     * =========================================================
     * QUAY LẠI CHỌN MÔN
     * =========================================================
     */
    function goBackToSubject() {
        document.getElementById("step-teachers")
            .classList.add("hidden");

        document.getElementById("step-subject")
            .classList.remove("hidden");
    }


    /*
     * =========================================================
     * CHỌN MÔN THI
     * =========================================================
     */
    function selectSubject() {
        const subjectId =
            document.getElementById("subject-select").value;

        if (!subjectId) {
            document.getElementById("step-teachers")
                .classList.add("hidden");
            return;
        }

        document.getElementById("selected-subject")
            .innerText = subjects[subjectId];

        renderSubjectTeachers();

        document.getElementById("step-teachers")
            .classList.remove("hidden");
    }


    /*
     * =========================================================
     * RENDER GIÁO VIÊN HỢP LỆ THEO MÔN
     * =========================================================
     */
    function renderSubjectTeachers() {
        const subjectId =
            document.getElementById("subject-select").value;

        const teacherList =
            document.getElementById("teacher-list");

        teacherList.innerHTML = "";

        const eligibleTeachers =
            (teachers[subjectId] || [])
                .filter(teacher => teacher.nominated);

        if (eligibleTeachers.length === 0) {
            teacherList.innerHTML = `
                <div class="rounded-lg border border-red-200 bg-red-50 p-4 text-red-700">
                    Danh sách giáo viên chưa hoàn chỉnh
                </div>
            `;

            syncSubjectAssignments();
            return;
        }

        eligibleTeachers.forEach(teacher => {
            const label = document.createElement("label");

            label.className =
                "flex items-center gap-3 border rounded-lg p-3 cursor-pointer hover:bg-gray-50";

            label.innerHTML = `
                <input
                    type="checkbox"
                    name="subject-teachers[]"
                    value="${teacher.id}"
                    data-name="${teacher.name}"
                    class="h-4 w-4"
                >

                <span>${teacher.name}</span>
            `;

            teacherList.appendChild(label);
        });

        syncSubjectAssignments();
    }


    /*
     * =========================================================
     * LƯU HỘI ĐỒNG RA ĐỀ / CHẤM / PHÚC KHẢO
     * =========================================================
     */
    
let subjectAssignments = [];

function syncSubjectAssignments() {
    const checked = [...document.querySelectorAll('input[name="subject-teachers[]"]:checked')];
    const ids = new Set(checked.map(el => String(el.value)));

    subjectAssignments = subjectAssignments.filter(x => ids.has(String(x.id)));

    checked.forEach(el => {
        const id = String(el.value);
        if (!subjectAssignments.some(x => String(x.id) === id)) {
            const row = el.closest('[data-teacher-id]');
            const name = row?.dataset?.teacherName || row?.querySelector('.teacher-name')?.textContent?.trim() || el.dataset.name || 'Giáo viên';
            subjectAssignments.push({
                id,
                name,
                isLeader: false,
                isBackup: false
            });
        }
    });

    renderSubjectAssignments();
}

function setSubjectLeader(id) {
    subjectAssignments.forEach(x => x.isLeader = String(x.id) === String(id));
    // Trưởng hội đồng và dự phòng là 2 vai trò riêng.
    const selected = subjectAssignments.find(x => String(x.id) === String(id));
    if (selected) selected.isBackup = false;
    renderSubjectAssignments();
}

function toggleSubjectBackup(id) {
    const item = subjectAssignments.find(x => String(x.id) === String(id));
    if (!item) return;
    item.isBackup = !item.isBackup;
    if (item.isBackup) item.isLeader = false;
    renderSubjectAssignments();
}

function renderSubjectAssignments() {
    const box = document.getElementById('subject-assignment-list');
    if (!box) return;

    if (!subjectAssignments.length) {
        box.innerHTML = '<p class="text-gray-500">Vui lòng chọn giáo viên tham gia hội đồng trước.</p>';
        return;
    }

    box.innerHTML = subjectAssignments.map(t => `
        <div class="assignment-row">
            <div class="teacher-info">
                <span class="teacher-name">${escapeHtml(t.name)}</span>
            </div>
            <label class="role-option">
                <input type="radio"
                       name="subject-leader"
                       value="${t.id}"
                       ${t.isLeader ? 'checked' : ''}
                       onchange="setSubjectLeader('${t.id}')">
                Trưởng hội đồng
            </label>
            <label class="role-option">
                <input type="checkbox"
                       name="subject-backups[]"
                       value="${t.id}"
                       ${t.isBackup ? 'checked' : ''}
                       onchange="toggleSubjectBackup('${t.id}')">
                Dự phòng
            </label>
        </div>
    `).join('');
}

function createSubjectCouncil() {
    const leader = subjectAssignments.find(x => x.isLeader);
    const backups = subjectAssignments.filter(x => x.isBackup);

    if (!subjectAssignments.length) {
        alert('Vui lòng chọn ít nhất một giáo viên.');
        return;
    }

    if (!leader) {
        alert('Vui lòng chọn 1 giáo viên làm trưởng hội đồng.');
        return;
    }

    if (backups.length < 5) {
        alert('Danh sách chưa hợp lệ: cần ít nhất 5 giáo viên dự phòng.');
        return;
    }

    alert('Danh sách hợp lệ và đã sẵn sàng để lưu.');
}
    function renderProctorTeachers() {
        const teacherList =
            document.getElementById("proctor-teacher-list");

        teacherList.innerHTML = "";

        /*
         * GV đã được phân công ở bất kỳ điểm thi nào
         * sẽ không còn xuất hiện trong danh sách chưa phân công.
         */
        const assignedTeacherIds =
            new Set(
                proctorAssignments.map(
                    assignment => assignment.teacherId
                )
            );

        const availableTeachers =
            proctorTeachers.filter(
                teacher => !assignedTeacherIds.has(teacher.id)
            );

        if (availableTeachers.length === 0) {
            teacherList.innerHTML = `
                <div class="rounded-lg border border-yellow-200 bg-yellow-50 p-4 text-yellow-700">
                    Danh sách giáo viên chưa hoàn chỉnh
                </div>
            `;
            return;
        }

        availableTeachers.forEach(teacher => {
            const label = document.createElement("label");

            label.className =
                "flex items-center gap-3 border rounded-lg p-3 cursor-pointer hover:bg-gray-50";

            label.innerHTML = `
                <input
                    type="checkbox"
                    class="proctor-teacher-checkbox h-4 w-4"
                    value="${teacher.id}"
                >

                <span>${teacher.name}</span>
            `;

            teacherList.appendChild(label);
        });
    }


    /*
     * =========================================================
     * THÊM NHIỀU GIÁO VIÊN VÀO ĐIỂM THI
     * =========================================================
     */
    function addTeachersToLocation() {
        const location =
            document.getElementById("location-select");

        if (!location.value) {
            alert("Vui lòng chọn điểm thi.");
            return;
        }

        const selectedTeachers =
            document.querySelectorAll(
                ".proctor-teacher-checkbox:checked"
            );

        if (selectedTeachers.length === 0) {
            alert("Vui lòng chọn ít nhất một giáo viên.");
            return;
        }

        const locationId = location.value;

        const locationName =
            location.options[
                location.selectedIndex
            ].text;

        const teacherMap = Object.fromEntries(
            proctorTeachers.map(
                teacher => [teacher.id, teacher]
            )
        );

        selectedTeachers.forEach(checkbox => {
            const teacherId =
                Number(checkbox.value);

            const teacher =
                teacherMap[teacherId];

            if (!teacher) return;

            /*
             * Không cho thêm cùng một GV nhiều lần
             * và không cho một GV xuất hiện ở nhiều điểm thi
             * trong demo.
             */
            const alreadyAssigned =
                proctorAssignments.some(
                    assignment =>
                        assignment.teacherId === teacherId
                );

            if (alreadyAssigned) return;

            proctorAssignments.push({isLeader: false, isBackup: false,
                locationId,
                locationName,
                teacherId,
                teacherName: teacher.name
            });
        });

        renderSelectedLocationAssignments();
        renderProctorRoles();
        renderProctorTeachers();
    }


    /*
     * =========================================================
     * RENDER PHÂN CÔNG HIỆN TẠI
     * =========================================================
     */
    
function setProctorLeader(id) {
    proctorAssignments.forEach(x => x.isLeader = String(x.teacherId) === String(id));
    const selected = proctorAssignments.find(x => String(x.teacherId) === String(id));
    if (selected) selected.isBackup = false;
    renderProctorRoles();
}

function toggleProctorBackup(id) {
    const item = proctorAssignments.find(x => String(x.teacherId) === String(id));
    if (!item) return;
    item.isBackup = !item.isBackup;
    if (item.isBackup) item.isLeader = false;
    renderProctorRoles();
}

function renderSelectedLocationAssignments() {
        const location =
            document.getElementById("location-select");

        const assignmentList =
            document.getElementById("assignment-list");

        assignmentList.innerHTML = "";

        if (!location.value) {
            assignmentList.innerHTML = `
                <p class="text-gray-500">
                    Vui lòng chọn điểm thi.
                </p>
            `;
            return;
        }

        const assignments =
            proctorAssignments.filter(
                assignment =>
                    assignment.locationId === location.value
            );

        if (assignments.length === 0) {
            assignmentList.innerHTML = `
                <p class="text-gray-500">
                    Chưa có giáo viên nào được phân công tại
                    <strong>${location.options[location.selectedIndex].text}</strong>.
                </p>
            `;
            return;
        }

        const locationTitle =
            document.createElement("div");

        locationTitle.className =
            "font-bold mb-4";

        locationTitle.innerText =
            location.options[location.selectedIndex].text;

        assignmentList.appendChild(locationTitle);

        assignments.forEach(assignment => {
            const teacher =
                document.createElement("div");

            teacher.className =
                "border rounded-lg p-3 mb-2";

            teacher.innerHTML = `
                <div class="font-medium">
                    ${assignment.teacherName}
                </div>
                <div class="text-sm text-gray-500 mt-1">
                    Đã được phân công
                </div>
            `;

            assignmentList.appendChild(teacher);
        });
    }


    /*
     * =========================================================
     * RENDER TRƯỞNG HỘI ĐỒNG & DỰ PHÒNG GÁC THI (BẢNG GỘP CHUNG)
     * =========================================================
     */
    function renderProctorRoles() {
        const box = document.getElementById("proctor-role-list");
        if (!box) return;

        if (proctorAssignments.length === 0) {
            box.innerHTML = `
                <p class="text-gray-500">
                    Vui lòng phân công giáo viên trước.
                </p>
            `;
            return;
        }

        const uniqueTeachers = [];

        proctorAssignments.forEach(assignment => {
            const exists =
                uniqueTeachers.some(
                    teacher =>
                        teacher.id === assignment.teacherId
                );

            if (!exists) {
                uniqueTeachers.push({
                    id: assignment.teacherId,
                    name: assignment.teacherName,
                    isLeader: !!assignment.isLeader,
                    isBackup: !!assignment.isBackup
                });
            }
        });

        box.innerHTML = uniqueTeachers.map(teacher => `
            <div class="assignment-row">
                <div class="teacher-info">
                    <span class="teacher-name">${escapeHtml(teacher.name)}</span>
                </div>
                <label class="role-option">
                    <input type="radio"
                           name="proctor-leader"
                           value="${teacher.id}"
                           ${teacher.isLeader ? 'checked' : ''}
                           onchange="setProctorLeader('${teacher.id}')">
                    Trưởng hội đồng
                </label>
                <label class="role-option">
                    <input type="checkbox"
                           name="proctor-backups[]"
                           value="${teacher.id}"
                           ${teacher.isBackup ? 'checked' : ''}
                           onchange="toggleProctorBackup('${teacher.id}')">
                    Dự phòng
                </label>
            </div>
        `).join('');
    }


    /*
     * =========================================================
     * LƯU HỘI ĐỒNG GÁC THI
     * =========================================================
     */
    function createProctorCouncil() {
        if (proctorAssignments.length === 0) {
            alert("Danh sách giáo viên chưa hoàn chỉnh");
            return;
        }

        const leader =
            document.querySelector(
                'input[name="proctor-leader"]:checked'
            );

        const backups =
            document.querySelectorAll(
                'input[name="proctor-backups[]"]:checked'
            );

        /*
         * Bước 11: kiểm tra giáo viên hợp lệ.
         */
        if (!leader) {
            alert("Vui lòng chọn trưởng Hội đồng thi.");
            return;
        }

        if (backups.length === 0) {
            const confirmWithoutBackup =
                confirm("Bạn chưa chọn giáo viên dự phòng. Vẫn lưu danh sách?");

            if (!confirmWithoutBackup) return;
        }

        alert(
            "Kiểm tra giáo viên hợp lệ thành công.\n" +
            "Thông tin giáo viên và vai trò đã được cập nhật vào Hội đồng Gác thi."
        );

        // TODO: submit API/PHP để lưu database.
    }

document.addEventListener('change', function (e) {
    if (e.target.matches('input[name="subject-teachers[]"]')) {
        syncSubjectAssignments();
    }
});
</script>

</body>
</html>