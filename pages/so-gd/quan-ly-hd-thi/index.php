<?php

?>

<!DOCTYPE html>

<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

<title>Stackers - Quản lý HĐ thi</title>

<script src="https://cdn.tailwindcss.com"></script>

</head>

<body>

<div class="flex-1 p-8">

<!-- =====================================================
     STEP 1: CHỌN LOẠI HỘI ĐỒNG
====================================================== -->

<div id="step-council-type">

    <h2 class="text-center font-bold text-xl mb-6">
        Vui lòng chọn loại hội đồng thi
    </h2>

    <div class="flex gap-2 justify-center">

        <label class="border rounded-lg p-4 cursor-pointer">
            <input
                type="radio"
                name="council-cate"
                value="examiner"
                onchange="selectCouncilType('examiner')"
            >

            Ra đề
        </label>

        <label class="border rounded-lg p-4 cursor-pointer">
            <input
                type="radio"
                name="council-cate"
                value="proctor"
                onchange="selectCouncilType('proctor')"
            >

            Gác thi
        </label>

        <label class="border rounded-lg p-4 cursor-pointer">
            <input
                type="radio"
                name="council-cate"
                value="grader"
                onchange="selectCouncilType('grader')"
            >

            Chấm thi
        </label>

        <label class="border rounded-lg p-4 cursor-pointer">
            <input
                type="radio"
                name="council-cate"
                value="reviewer"
                onchange="selectCouncilType('reviewer')"
            >

            Phúc khảo
        </label>

    </div>

</div>


<!-- =====================================================
     STEP 2A: CHỌN MÔN THI
     Dùng cho:
     - Ra đề
     - Chấm thi
     - Phúc khảo
====================================================== -->

<div id="step-subject" class="hidden">

    <div class="flex justify-between items-center mb-6">

        <h2 class="font-bold text-xl">
            Chọn môn thi
        </h2>

        <button
            type="button"
            onclick="goBackToCouncilType()"
            class="border px-4 py-2 rounded"
        >
            Quay lại
        </button>

    </div>

    <div class="border rounded-lg p-4">

        <label class="block mb-2">
            Môn thi
        </label>

        <select
            id="subject-select"
            class="border rounded p-2 w-full"
            onchange="selectSubject()"
        >

            <option value="">
                -- Chọn môn thi --
            </option>

            <option value="math">
                Toán
            </option>

            <option value="literature">
                Ngữ Văn
            </option>

            <option value="english">
                Tiếng Anh
            </option>

        </select>

    </div>

</div>


<!-- =====================================================
     STEP 2B: CHỌN GIÁO VIÊN + TRƯỞNG HỘI ĐỒNG

     Dùng cho:
     - Ra đề
     - Chấm thi
     - Phúc khảo
====================================================== -->

<div id="step-teachers" class="hidden mt-8">

    <div class="flex justify-between items-center mb-6">

        <div>

            <h2 class="font-bold text-xl">
                Phân công hội đồng
            </h2>

            <div class="mt-2">
                Môn:
                <span
                    id="selected-subject"
                    class="border rounded px-3 py-1"
                >
                </span>
            </div>

        </div>

        <button
            type="button"
            onclick="goBackToSubject()"
            class="border px-4 py-2 rounded"
        >
            Quay lại
        </button>

    </div>


    <!-- DANH SÁCH GIÁO VIÊN -->

    <div class="border rounded-lg">

        <div class="p-4 border-b font-bold">
            Danh sách giáo viên
        </div>

        <div
            id="teacher-list"
            class="p-4"
        >
            <!-- Javascript render -->
        </div>

    </div>


    <!-- TRƯỞNG HỘI ĐỒNG -->

    <div class="border rounded-lg mt-6">

        <div class="p-4 border-b font-bold">
            Chọn trưởng hội đồng
        </div>

        <div
            id="leader-list"
            class="p-4"
        >

            <p>
                Vui lòng chọn giáo viên tham gia hội đồng trước.
            </p>

        </div>

    </div>


    <div class="mt-4 flex justify-end">

        <button
            type="button"
            onclick="createSubjectCouncil()"
            class="border rounded px-6 py-3"
        >
            Tạo hội đồng
        </button>

    </div>

</div>


<!-- =====================================================
     STEP 2C: GÁC THI

     - Chọn điểm thi
     - Chọn nhiều giáo viên
     - Có thể đánh dấu dự phòng
     - Sau đó thêm vào điểm thi
====================================================== -->

<div id="step-proctor" class="hidden">

    <div class="flex justify-between items-center mb-6">

        <h2 class="font-bold text-xl">
            Phân công gác thi
        </h2>

        <button
            type="button"
            onclick="goBackToCouncilType()"
            class="border px-4 py-2 rounded"
        >
            Quay lại
        </button>

    </div>


    <div class="grid grid-cols-2 gap-8">

        <!-- =================================================
             DANH SÁCH ĐIỂM THI
        ================================================== -->

        <div class="border rounded-lg">

            <div class="p-4 border-b font-bold">
                Danh sách điểm thi
            </div>

            <div class="p-4">

                <label class="block mb-2">
                    Chọn điểm thi
                </label>

                <select
                    id="location-select"
                    class="border rounded p-2 w-full"
                    onchange="renderSelectedLocationAssignments()"
                >

                    <option value="">
                        -- Chọn điểm thi --
                    </option>

                    <option value="school-a">
                        Trường THPT A
                    </option>

                    <option value="school-b">
                        Trường THPT B
                    </option>

                    <option value="school-c">
                        Trường THPT C
                    </option>

                    <option value="school-d">
                        Trường THPT D
                    </option>

                </select>

            </div>

        </div>


        <!-- =================================================
             DANH SÁCH GIÁO VIÊN
        ================================================== -->

        <div class="border rounded-lg">

            <div class="p-4 border-b font-bold">
                Danh sách giáo viên
            </div>

            <div class="p-4">

                <div class="mb-4">
                    Chọn giáo viên tham gia gác thi
                </div>

                <div
                    id="proctor-teacher-list"
                    class="space-y-4"
                >

                    <!-- Javascript render -->

                </div>

            </div>

        </div>

    </div>


    <!-- =================================================
         THÊM GIÁO VIÊN VÀO ĐIỂM THI
    ================================================== -->

    <div class="mt-6 flex justify-center">

        <button
            type="button"
            onclick="addTeachersToLocation()"
            class="border rounded px-6 py-3"
        >
            Thêm giáo viên vào trường đã chọn
        </button>

    </div>


    <!-- =================================================
         PHÂN CÔNG HIỆN TẠI
    ================================================== -->

    <div class="mt-8 border rounded-lg">

        <div class="p-4 border-b font-bold">
            Phân công hiện tại
        </div>

        <div
            id="assignment-list"
            class="p-4"
        >

            <p id="empty-assignment">
                Chưa có phân công nào.
            </p>

        </div>

    </div>


    <!-- =================================================
         TRƯỞNG HỘI ĐỒNG
    ================================================== -->

    <div class="mt-6 border rounded-lg">

        <div class="p-4 border-b font-bold">
            Chọn trưởng hội đồng
        </div>

        <div
            id="proctor-leader-list"
            class="p-4"
        >

            <p>
                Vui lòng phân công giáo viên trước.
            </p>

        </div>

    </div>


    <div class="mt-4 flex justify-end">

        <button
            type="button"
            onclick="createProctorCouncil()"
            class="border rounded px-6 py-3"
        >
            Tạo hội đồng
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

        english: "Tiếng Anh",

        programming: "Lập trình"

    };


    const teachers = {

        math: [
            {
                id: 1,
                name: "Nguyễn Văn A"
            },
            {
                id: 2,
                name: "Nguyễn Thị B"
            },
            {
                id: 3,
                name: "Trần Văn C"
            },
            {
                id: 4,
                name: "Lê Văn D"
            }
        ],

        literature: [
            {
                id: 5,
                name: "Lê Thị D"
            },
            {
                id: 6,
                name: "Phạm Văn E"
            },
            {
                id: 7,
                name: "Hoàng Thị F"
            }
        ],

        english: [
            {
                id: 8,
                name: "Nguyễn Văn G"
            },
            {
                id: 9,
                name: "Trần Thị H"
            },
            {
                id: 10,
                name: "Lê Văn I"
            }
        ],

        programming: [
            {
                id: 11,
                name: "Phạm Văn K"
            },
            {
                id: 12,
                name: "Nguyễn Văn L"
            },
            {
                id: 13,
                name: "Trần Thị M"
            }
        ]

    };


    /*
     * Giáo viên đã được phân công vào các điểm thi
     *
     * {
     *     locationId,
     *     locationName,
     *     teacherId,
     *     teacherName,
     *     isBackup
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


        /*
         * Gác thi có flow riêng
         */

        if (type === "proctor") {

            document
                .getElementById("step-proctor")
                .classList.remove("hidden");

            renderProctorTeachers();

            return;
        }


        /*
         * Ra đề / Chấm thi / Phúc khảo
         * đều phải chọn môn.
         */

        document
            .getElementById("step-subject")
            .classList.remove("hidden");

    }


    /*
     * =========================================================
     * ẨN TẤT CẢ STEP
     * =========================================================
     */

    function hideAllSteps() {

        document
            .getElementById("step-council-type")
            .classList.add("hidden");

        document
            .getElementById("step-subject")
            .classList.add("hidden");

        document
            .getElementById("step-teachers")
            .classList.add("hidden");

        document
            .getElementById("step-proctor")
            .classList.add("hidden");

    }


    /*
     * =========================================================
     * QUAY LẠI CHỌN LOẠI HỘI ĐỒNG
     * =========================================================
     */

    function goBackToCouncilType() {

        hideAllSteps();

        document
            .getElementById("step-council-type")
            .classList.remove("hidden");

    }


    /*
     * =========================================================
     * QUAY LẠI CHỌN MÔN
     * =========================================================
     */

    function goBackToSubject() {

        document
            .getElementById("step-teachers")
            .classList.add("hidden");

        document
            .getElementById("step-subject")
            .classList.remove("hidden");

    }


    /*
     * =========================================================
     * CHỌN MÔN THI
     * =========================================================
     */

    function selectSubject() {

        const subjectId =
            document
                .getElementById("subject-select")
                .value;


        if (!subjectId) {

            document
                .getElementById("step-teachers")
                .classList.add("hidden");

            return;

        }


        document
            .getElementById("selected-subject")
            .innerText =
                subjects[subjectId];


        renderSubjectTeachers();


        document
            .getElementById("step-teachers")
            .classList.remove("hidden");

    }


    /*
     * =========================================================
     * RENDER GIÁO VIÊN THEO MÔN
     * =========================================================
     */

    function renderSubjectTeachers() {

        const subjectId =
            document
                .getElementById("subject-select")
                .value;


        const teacherList =
            document
                .getElementById("teacher-list");


        teacherList.innerHTML = "";


        teachers[subjectId].forEach(teacher => {

            const label =
                document.createElement("label");


            label.className =
                "block mb-4";


            label.innerHTML = `

                <input
                    type="checkbox"
                    name="subject-teachers[]"
                    value="${teacher.id}"
                    onchange="renderSubjectLeaders()"
                >

                ${teacher.name}

            `;


            teacherList.appendChild(label);

        });


        renderSubjectLeaders();

    }


    /*
     * =========================================================
     * RENDER TRƯỞNG HỘI ĐỒNG
     *
     * Chỉ những giáo viên đã được tick tham gia
     * mới xuất hiện ở đây.
     * =========================================================
     */

    function renderSubjectLeaders() {

        const leaderList =
            document
                .getElementById("leader-list");


        const selectedTeachers =
            document.querySelectorAll(
                'input[name="subject-teachers[]"]:checked'
            );


        leaderList.innerHTML = "";


        if (selectedTeachers.length === 0) {

            leaderList.innerHTML = `
                <p>
                    Vui lòng chọn giáo viên tham gia hội đồng trước.
                </p>
            `;

            return;
        }


        selectedTeachers.forEach(checkbox => {

            const teacher =
                teachers[
                    document
                        .getElementById("subject-select")
                        .value
                ].find(
                    item =>
                        item.id == checkbox.value
                );


            const label =
                document.createElement("label");


            label.className =
                "block mb-3";


            label.innerHTML = `

                <input
                    type="radio"
                    name="subject-leader"
                    value="${teacher.id}"
                >

                ${teacher.name}

            `;


            leaderList.appendChild(label);

        });

    }


    /*
     * =========================================================
     * TẠO HỘI ĐỒNG THEO MÔN
     * =========================================================
     */

    function createSubjectCouncil() {

        const selectedTeachers =
            document.querySelectorAll(
                'input[name="subject-teachers[]"]:checked'
            );


        const leader =
            document.querySelector(
                'input[name="subject-leader"]:checked'
            );


        if (selectedTeachers.length === 0) {

            alert(
                "Vui lòng chọn ít nhất một giáo viên."
            );

            return;
        }


        if (!leader) {

            alert(
                "Vui lòng chọn trưởng hội đồng."
            );

            return;
        }


        alert(
            "Đã đủ thông tin để tạo hội đồng."
        );


        /*
         * Sau này submit API/PHP tại đây.
         */

    }


    /*
     * =========================================================
     * RENDER DANH SÁCH GIÁO VIÊN GÁC THI
     * =========================================================
     */

    function renderProctorTeachers() {

        const teacherList =
            document
                .getElementById("proctor-teacher-list");


        teacherList.innerHTML = "";


        const proctorTeachers = [

            {
                id: 1,
                name: "Nguyễn Văn A"
            },

            {
                id: 2,
                name: "Nguyễn Thị B"
            },

            {
                id: 3,
                name: "Trần Văn C"
            },

            {
                id: 4,
                name: "Trần Thị D"
            },

            {
                id: 5,
                name: "Lê Văn E"
            }

        ];


        proctorTeachers.forEach(teacher => {

            const container =
                document.createElement("div");


            container.className =
                "border rounded p-3";


            container.innerHTML = `

                <label class="block">

                    <input
                        type="checkbox"
                        class="proctor-teacher-checkbox"
                        value="${teacher.id}"
                    >

                    ${teacher.name}

                </label>


                <label class="block mt-2 ml-6">

                    <input
                        type="checkbox"
                        class="proctor-backup-checkbox"
                        data-teacher-id="${teacher.id}"
                    >

                    Dự phòng

                </label>

            `;


            teacherList.appendChild(container);

        });

    }


    /*
     * =========================================================
     * THÊM GIÁO VIÊN VÀO ĐIỂM THI
     * =========================================================
     */

    function addTeachersToLocation() {

        const location =
            document
                .getElementById("location-select");


        if (!location.value) {

            alert(
                "Vui lòng chọn điểm thi."
            );

            return;
        }


        const locationId =
            location.value;


        const locationName =
            location.options[
                location.selectedIndex
            ].text;


        const selectedTeachers =
            document.querySelectorAll(
                ".proctor-teacher-checkbox:checked"
            );


        if (selectedTeachers.length === 0) {

            alert(
                "Vui lòng chọn ít nhất một giáo viên."
            );

            return;
        }


        selectedTeachers.forEach(checkbox => {

            const teacherId =
                Number(checkbox.value);


            /*
             * Tìm checkbox dự phòng tương ứng
             */

            const backupCheckbox =
                document.querySelector(
                    `.proctor-backup-checkbox[data-teacher-id="${teacherId}"]`
                );


            const teacherNames = {

                1: "Nguyễn Văn A",

                2: "Nguyễn Thị B",

                3: "Trần Văn C",

                4: "Trần Thị D",

                5: "Lê Văn E"

            };


            /*
             * Không cho thêm cùng một giáo viên
             * nhiều lần vào cùng một điểm thi.
             */

            const alreadyAssigned =
                proctorAssignments.some(
                    assignment =>
                        assignment.locationId === locationId
                        &&
                        assignment.teacherId === teacherId
                );


            if (alreadyAssigned) {

                return;

            }


            proctorAssignments.push({

                locationId:
                    locationId,

                locationName:
                    locationName,

                teacherId:
                    teacherId,

                teacherName:
                    teacherNames[teacherId],

                isBackup:
                    backupCheckbox
                        ? backupCheckbox.checked
                        : false

            });

        });


        renderSelectedLocationAssignments();

        renderProctorLeaders();


        /*
         * Reset checkbox sau khi thêm
         */

        document
            .querySelectorAll(
                ".proctor-teacher-checkbox"
            )
            .forEach(
                checkbox =>
                    checkbox.checked = false
            );


        document
            .querySelectorAll(
                ".proctor-backup-checkbox"
            )
            .forEach(
                checkbox =>
                    checkbox.checked = false
            );

    }


    /*
     * =========================================================
     * RENDER PHÂN CÔNG
     * =========================================================
     */

    function renderSelectedLocationAssignments() {

        const location =
            document.getElementById("location-select");

        const assignmentList =
            document.getElementById("assignment-list");

        assignmentList.innerHTML = "";


        /*
        * Chưa chọn điểm thi
        */

        if (!location.value) {

            assignmentList.innerHTML = `
                <p>
                    Vui lòng chọn điểm thi.
                </p>
            `;

            return;
        }


        /*
        * Lấy những giáo viên thuộc điểm thi đang chọn
        */

        const assignments =
            proctorAssignments.filter(
                assignment =>
                    assignment.locationId === location.value
            );


        /*
        * Điểm thi chưa có giáo viên
        */

        if (assignments.length === 0) {

            assignmentList.innerHTML = `
                <p>
                    Chưa có giáo viên nào được phân công tại
                    <strong>${location.options[location.selectedIndex].text}</strong>.
                </p>
            `;

            return;
        }


        /*
        * Hiển thị tên điểm thi
        */

        const locationTitle =
            document.createElement("div");

        locationTitle.className =
            "font-bold mb-4";

        locationTitle.innerText =
            location.options[location.selectedIndex].text;


        assignmentList.appendChild(locationTitle);


        /*
        * Hiển thị giáo viên
        */

        assignments.forEach(assignment => {

            const teacher =
                document.createElement("div");

            teacher.className =
                "border rounded p-3 mb-2";


            teacher.innerHTML = `

                <div>
                    ${assignment.teacherName}
                </div>

                <div class="mt-1">

                    ${
                        assignment.isBackup
                            ? "Dự phòng"
                            : "Chính thức"
                    }

                </div>

            `;


            assignmentList.appendChild(teacher);

        });

    }


    /*
     * =========================================================
     * RENDER DANH SÁCH TRƯỞNG HỘI ĐỒNG GÁC THI
     *
     * Chỉ những giáo viên đã được phân công
     * mới được chọn làm trưởng.
     * =========================================================
     */

    function renderProctorLeaders() {

        const leaderList =
            document
                .getElementById("proctor-leader-list");


        leaderList.innerHTML = "";


        if (proctorAssignments.length === 0) {

            leaderList.innerHTML = `

                <p>
                    Vui lòng phân công giáo viên trước.
                </p>

            `;

            return;
        }


        /*
         * Lấy danh sách giáo viên duy nhất.
         */

        const uniqueTeachers = [];


        proctorAssignments.forEach(assignment => {

            const exists =
                uniqueTeachers.some(
                    teacher =>
                        teacher.id === assignment.teacherId
                );


            if (!exists) {

                uniqueTeachers.push({

                    id:
                        assignment.teacherId,

                    name:
                        assignment.teacherName

                });

            }

        });


        uniqueTeachers.forEach(teacher => {

            const label =
                document.createElement("label");


            label.className =
                "block mb-3";


            label.innerHTML = `

                <input
                    type="radio"
                    name="proctor-leader"
                    value="${teacher.id}"
                >

                ${teacher.name}

            `;


            leaderList.appendChild(label);

        });

    }


    /*
     * =========================================================
     * TẠO HỘI ĐỒNG GÁC THI
     * =========================================================
     */

    function createProctorCouncil() {

        if (proctorAssignments.length === 0) {

            alert(
                "Vui lòng phân công ít nhất một giáo viên."
            );

            return;
        }


        const leader =
            document.querySelector(
                'input[name="proctor-leader"]:checked'
            );


        if (!leader) {

            alert(
                "Vui lòng chọn trưởng hội đồng."
            );

            return;
        }


        alert(
            "Đã đủ thông tin để tạo hội đồng."
        );


        /*
         * Sau này submit API/PHP tại đây.
         */

    }

</script>

</body>

</html>
