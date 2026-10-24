<?php

$students = [
    [
        'id' => 1,
        'name' => 'Nguyễn Văn An',
        'dob' => '15/03/2008',
        'phone' => '0901234567',
        'email' => 'nguyenvanan@gmail.com',
        'address' => '123 Nguyễn Trãi',
        'ward' => 'Phường 1',
        'cccd' => '079208001234',
        'ethnic' => 'Không'
    ],
    [
        'id' => 2,
        'name' => 'Trần Thị Bình',
        'dob' => '20/07/2008',
        'phone' => '0912345678',
        'email' => '',
        'address' => '45 Lê Lợi',
        'ward' => 'Phường 2',
        'cccd' => '079208005678',
        'ethnic' => 'Có'
    ],
    [
        'id' => 3,
        'name' => 'Lê Minh Khôi',
        'dob' => '02/11/2008',
        'phone' => '0987654321',
        'email' => 'leminhkhoi@gmail.com',
        'address' => '78 Trần Hưng Đạo',
        'ward' => 'Phường 3',
        'cccd' => '079208009999',
        'ethnic' => 'Không'
    ]
];

?>

<!DOCTYPE html>
<html lang="vi">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>Quản lý hồ sơ thí sinh</title>

    <script src="https://cdn.tailwindcss.com"></script>
</head>

<body class="p-8">

    <div class="max-w-6xl mx-auto">

        <!-- HEADER -->
        <div class="border-b pb-4 mb-6">

            <h1 class="text-2xl font-bold">
                Quản lý hồ sơ thí sinh
            </h1>

            <p class="mt-2">
                Trường THCS
            </p>

        </div>


        <!-- ========================= -->
        <!-- DANH SÁCH THÍ SINH -->
        <!-- ========================= -->

        <section id="student-list-section">

            <div class="flex justify-between items-center mb-4">

                <h2 class="text-xl font-bold">
                    Danh sách thí sinh
                </h2>

                <button
                    type="button"
                    onclick="showAddStudent()"
                    class="border px-4 py-2 rounded"
                >
                    + Thêm thí sinh
                </button>

            </div>


            <!-- SEARCH -->

            <div class="border p-4 mb-6">

                <div class="grid grid-cols-3 gap-4">

                    <div>
                        <label class="block mb-1">
                            Họ và tên
                        </label>

                        <input
                            type="text"
                            id="search-name"
                            class="border rounded p-2 w-full"
                            placeholder="Nhập họ và tên"
                        >
                    </div>


                    <div>
                        <label class="block mb-1">
                            Số CCCD
                        </label>

                        <input
                            type="text"
                            id="search-cccd"
                            class="border rounded p-2 w-full"
                            placeholder="Nhập số CCCD"
                        >
                    </div>


                    <div>
                        <label class="block mb-1">
                            Số điện thoại
                        </label>

                        <input
                            type="text"
                            id="search-phone"
                            class="border rounded p-2 w-full"
                            placeholder="Nhập số điện thoại"
                        >
                    </div>

                </div>


                <div class="mt-4">

                    <button
                        type="button"
                        onclick="searchStudents()"
                        class="border px-4 py-2 rounded"
                    >
                        Tìm kiếm
                    </button>

                </div>

            </div>


            <!-- ACTIONS -->

            <div class="border p-4 mb-6">

                <div class="flex gap-4">

                    <button
                        type="button"
                        onclick="showAddStudent()"
                        class="border px-4 py-2 rounded"
                    >
                        Thêm thí sinh
                    </button>

                    <button
                        type="button"
                        onclick="showExcelUpload()"
                        class="border px-4 py-2 rounded"
                    >
                        Tải lên từ file Excel
                    </button>

                </div>

            </div>


            <!-- STUDENT TABLE -->

            <div class="border">

                <table class="w-full border-collapse">

                    <thead>

                        <tr class="border-b">

                            <th class="text-left p-3">
                                STT
                            </th>

                            <th class="text-left p-3">
                                Họ và tên
                            </th>

                            <th class="text-left p-3">
                                Ngày sinh
                            </th>

                            <th class="text-left p-3">
                                Số điện thoại
                            </th>

                            <th class="text-left p-3">
                                Số CCCD
                            </th>

                            <th class="text-left p-3">
                                Thao tác
                            </th>

                        </tr>

                    </thead>


                    <tbody id="student-table-body">

                        <?php foreach ($students as $index => $student): ?>

                            <tr
                                class="border-b student-row"
                                data-name="<?= strtolower($student['name']) ?>"
                                data-cccd="<?= $student['cccd'] ?>"
                                data-phone="<?= $student['phone'] ?>"
                            >

                                <td class="p-3">
                                    <?= $index + 1 ?>
                                </td>

                                <td class="p-3">
                                    <?= $student['name'] ?>
                                </td>

                                <td class="p-3">
                                    <?= $student['dob'] ?>
                                </td>

                                <td class="p-3">
                                    <?= $student['phone'] ?>
                                </td>

                                <td class="p-3">
                                    <?= $student['cccd'] ?>
                                </td>

                                <td class="p-3">

                                    <button
                                        type="button"
                                        onclick='editStudent(<?= json_encode($student) ?>)'
                                        class="border px-3 py-1 rounded"
                                    >
                                        Chỉnh sửa
                                    </button>

                                </td>

                            </tr>

                        <?php endforeach; ?>

                    </tbody>

                </table>

            </div>

        </section>


        <!-- ========================= -->
        <!-- FORM HỒ SƠ THÍ SINH -->
        <!-- ========================= -->

        <section
            id="student-form-section"
            class="hidden"
        >

            <div class="flex justify-between items-center mb-4">

                <h2
                    id="student-form-title"
                    class="text-xl font-bold"
                >
                    Chỉnh sửa hồ sơ thí sinh
                </h2>

                <button
                    type="button"
                    onclick="backToStudentList()"
                    class="border px-4 py-2 rounded"
                >
                    Quay lại
                </button>

            </div>


            <form
                id="student-form"
                onsubmit="saveStudent(event)"
                class="border p-6"
            >

                <input
                    type="hidden"
                    id="student-id"
                >


                <!-- THÔNG TIN CÁ NHÂN -->

                <h3 class="font-bold mb-4">
                    Thông tin thí sinh
                </h3>


                <div class="grid grid-cols-2 gap-4">


                    <div>

                        <label class="block mb-1">
                            Họ và tên
                        </label>

                        <input
                            type="text"
                            id="student-name"
                            class="border rounded p-2 w-full"
                            required
                        >

                    </div>


                    <div>

                        <label class="block mb-1">
                            Ngày sinh
                        </label>

                        <input
                            type="date"
                            id="student-dob"
                            class="border rounded p-2 w-full"
                            required
                        >

                    </div>


                    <div>

                        <label class="block mb-1">
                            Số điện thoại
                        </label>

                        <input
                            type="text"
                            id="student-phone"
                            class="border rounded p-2 w-full"
                            required
                        >

                    </div>


                    <div>

                        <label class="block mb-1">
                            Email (nếu có)
                        </label>

                        <input
                            type="email"
                            id="student-email"
                            class="border rounded p-2 w-full"
                        >

                    </div>


                    <div>

                        <label class="block mb-1">
                            Địa chỉ
                        </label>

                        <input
                            type="text"
                            id="student-address"
                            class="border rounded p-2 w-full"
                            required
                        >

                    </div>


                    <div>

                        <label class="block mb-1">
                            Phường
                        </label>

                        <input
                            type="text"
                            id="student-ward"
                            class="border rounded p-2 w-full"
                            required
                        >

                    </div>


                    <div>

                        <label class="block mb-1">
                            Số CCCD
                        </label>

                        <input
                            type="text"
                            id="student-cccd"
                            class="border rounded p-2 w-full"
                            required
                        >

                    </div>


                    <div>

                        <label class="block mb-1">
                            Dân tộc thiểu số
                        </label>

                        <select
                            id="student-ethnic"
                            class="border rounded p-2 w-full"
                        >

                            <option value="Không">
                                Không
                            </option>

                            <option value="Có">
                                Có
                            </option>

                        </select>

                    </div>

                </div>


                <!-- BUTTON -->

                <div class="mt-6 flex gap-3">

                    <button
                        type="submit"
                        class="border px-5 py-2 rounded"
                    >
                        Lưu
                    </button>

                    <button
                        type="button"
                        onclick="backToStudentList()"
                        class="border px-5 py-2 rounded"
                    >
                        Hủy
                    </button>

                </div>

            </form>

        </section>


        <!-- ========================= -->
        <!-- THÊM THÍ SINH -->
        <!-- ========================= -->

        <section
            id="add-student-section"
            class="hidden"
        >

            <div class="flex justify-between items-center mb-4">

                <h2 class="text-xl font-bold">
                    Thêm thí sinh
                </h2>

                <button
                    type="button"
                    onclick="backToStudentList()"
                    class="border px-4 py-2 rounded"
                >
                    Quay lại
                </button>

            </div>


            <div class="border p-6">

                <p class="mb-6">
                    Chọn phương thức thêm thí sinh:
                </p>


                <div class="flex gap-4">

                    <button
                        type="button"
                        onclick="showManualForm()"
                        class="border px-5 py-3 rounded"
                    >
                        Nhập thủ công
                    </button>


                    <button
                        type="button"
                        onclick="showExcelUpload()"
                        class="border px-5 py-3 rounded"
                    >
                        Tải lên từ file Excel
                    </button>

                </div>

            </div>

        </section>


        <!-- ========================= -->
        <!-- UPLOAD EXCEL -->
        <!-- ========================= -->

        <section
            id="excel-section"
            class="hidden"
        >

            <div class="flex justify-between items-center mb-4">

                <h2 class="text-xl font-bold">
                    Tải lên từ file Excel
                </h2>

                <button
                    type="button"
                    onclick="backToStudentList()"
                    class="border px-4 py-2 rounded"
                >
                    Quay lại
                </button>

            </div>


            <!-- QUY CÁCH FILE -->

            <div class="border p-6 mb-6">

                <h3 class="font-bold mb-4">
                    Quy cách file Excel
                </h3>

                <p class="mb-3">
                    File Excel phải có các cột sau:
                </p>


                <table class="border-collapse border w-full">

                    <thead>

                        <tr>

                            <th class="border p-2">
                                STT
                            </th>

                            <th class="border p-2">
                                Tên cột
                            </th>

                            <th class="border p-2">
                                Bắt buộc
                            </th>

                        </tr>

                    </thead>


                    <tbody>

                        <tr>
                            <td class="border p-2">1</td>
                            <td class="border p-2">Họ và tên</td>
                            <td class="border p-2">Có</td>
                        </tr>

                        <tr>
                            <td class="border p-2">2</td>
                            <td class="border p-2">Ngày sinh</td>
                            <td class="border p-2">Có</td>
                        </tr>

                        <tr>
                            <td class="border p-2">3</td>
                            <td class="border p-2">Số điện thoại</td>
                            <td class="border p-2">Có</td>
                        </tr>

                        <tr>
                            <td class="border p-2">4</td>
                            <td class="border p-2">Email</td>
                            <td class="border p-2">Không</td>
                        </tr>

                        <tr>
                            <td class="border p-2">5</td>
                            <td class="border p-2">Địa chỉ</td>
                            <td class="border p-2">Có</td>
                        </tr>

                        <tr>
                            <td class="border p-2">6</td>
                            <td class="border p-2">Phường</td>
                            <td class="border p-2">Có</td>
                        </tr>

                        <tr>
                            <td class="border p-2">7</td>
                            <td class="border p-2">Số CCCD</td>
                            <td class="border p-2">Có</td>
                        </tr>

                        <tr>
                            <td class="border p-2">8</td>
                            <td class="border p-2">Dân tộc thiểu số</td>
                            <td class="border p-2">Không</td>
                        </tr>

                    </tbody>

                </table>

            </div>


            <!-- FILE UPLOAD -->

            <div class="border p-6">

                <label class="block mb-2">
                    Chọn file Excel
                </label>

                <input
                    type="file"
                    accept=".xlsx,.xls"
                    id="excel-file"
                    class="border p-2 w-full"
                >


                <div class="mt-4">

                    <button
                        type="button"
                        onclick="uploadExcel()"
                        class="border px-5 py-2 rounded"
                    >
                        Upload file
                    </button>

                </div>

            </div>

        </section>


        <!-- ========================= -->
        <!-- THÔNG BÁO -->
        <!-- ========================= -->

        <div
            id="message"
            class="hidden border p-4 mt-6"
        ></div>

    </div>


    <script>

        const sections = [
            "student-list-section",
            "student-form-section",
            "add-student-section",
            "excel-section"
        ];


        function hideAllSections() {

            sections.forEach(id => {

                document
                    .getElementById(id)
                    .classList
                    .add("hidden");

            });

        }


        function showSection(id) {

            hideAllSections();

            document
                .getElementById(id)
                .classList
                .remove("hidden");

        }


        // =========================
        // DANH SÁCH
        // =========================

        function backToStudentList() {

            showSection("student-list-section");

        }


        function searchStudents() {

            const name =
                document
                    .getElementById("search-name")
                    .value
                    .toLowerCase()
                    .trim();

            const cccd =
                document
                    .getElementById("search-cccd")
                    .value
                    .trim();

            const phone =
                document
                    .getElementById("search-phone")
                    .value
                    .trim();


            const rows =
                document.querySelectorAll(".student-row");


            let found = false;


            rows.forEach(row => {

                const rowName =
                    row.dataset.name;

                const rowCccd =
                    row.dataset.cccd;

                const rowPhone =
                    row.dataset.phone;


                const matchName =
                    !name ||
                    rowName.includes(name);

                const matchCccd =
                    !cccd ||
                    rowCccd.includes(cccd);

                const matchPhone =
                    !phone ||
                    rowPhone.includes(phone);


                if (
                    matchName &&
                    matchCccd &&
                    matchPhone
                ) {

                    row.classList.remove("hidden");

                    found = true;

                } else {

                    row.classList.add("hidden");

                }

            });


            if (!found) {

                showMessage(
                    "Chưa có thí sinh nào được tải lên."
                );

            }

        }


        // =========================
        // CHỈNH SỬA
        // =========================

        function editStudent(student) {

            showSection("student-form-section");


            document
                .getElementById("student-form-title")
                .innerText =
                "Chỉnh sửa hồ sơ thí sinh";


            document
                .getElementById("student-id")
                .value =
                student.id;


            document
                .getElementById("student-name")
                .value =
                student.name;


            document
                .getElementById("student-dob")
                .value =
                convertDate(student.dob);


            document
                .getElementById("student-phone")
                .value =
                student.phone;


            document
                .getElementById("student-email")
                .value =
                student.email;


            document
                .getElementById("student-address")
                .value =
                student.address;


            document
                .getElementById("student-ward")
                .value =
                student.ward;


            document
                .getElementById("student-cccd")
                .value =
                student.cccd;


            document
                .getElementById("student-ethnic")
                .value =
                student.ethnic;

        }


        function convertDate(date) {

            const parts =
                date.split("/");

            if (parts.length !== 3) {
                return "";
            }

            return `${parts[2]}-${parts[1]}-${parts[0]}`;

        }


        // =========================
        // THÊM THÍ SINH
        // =========================

        function showAddStudent() {

            showSection("add-student-section");

        }


        function showManualForm() {

            showSection("student-form-section");


            document
                .getElementById("student-form-title")
                .innerText =
                "Thêm thí sinh";


            document
                .getElementById("student-form")
                .reset();


            document
                .getElementById("student-id")
                .value = "";

        }


        function saveStudent(event) {

            event.preventDefault();


            const name =
                document
                    .getElementById("student-name")
                    .value
                    .trim();

            const phone =
                document
                    .getElementById("student-phone")
                    .value
                    .trim();

            const cccd =
                document
                    .getElementById("student-cccd")
                    .value
                    .trim();


            if (
                name === "" ||
                phone === "" ||
                cccd === ""
            ) {

                showMessage(
                    "Dữ liệu không hợp lệ, lưu không thành công."
                );

                return;

            }


            showMessage(
                "Lưu hồ sơ thí sinh thành công."
            );


            setTimeout(() => {

                backToStudentList();

            }, 1000);

        }


        // =========================
        // EXCEL
        // =========================

        function showExcelUpload() {

            showSection("excel-section");

        }


        function uploadExcel() {

            const file =
                document
                    .getElementById("excel-file")
                    .files[0];


            if (!file) {

                showMessage(
                    "Vui lòng chọn file Excel."
                );

                return;

            }


            const validExtensions = [
                ".xlsx",
                ".xls"
            ];


            const extension =
                file.name
                    .substring(
                        file.name.lastIndexOf(".")
                    )
                    .toLowerCase();


            if (
                !validExtensions.includes(extension)
            ) {

                showMessage(
                    "File không đúng quy cách."
                );

                return;

            }


            showMessage(
                "Tải lên thành công."
            );

        }


        // =========================
        // MESSAGE
        // =========================

        function showMessage(message) {

            const element =
                document.getElementById("message");


            element.innerText =
                message;


            element.classList.remove("hidden");

        }

    </script>

</body>

</html>