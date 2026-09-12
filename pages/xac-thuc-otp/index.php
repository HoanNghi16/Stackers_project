<?php
?>

<!DOCTYPE html>
<html lang="vi">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>Xác thực OTP</title>

    <script src="https://cdn.tailwindcss.com"></script>
</head>

<body class="p-8">

    <div class="max-w-2xl mx-auto">

        <!-- HEADER -->

        <div class="border-b pb-4 mb-6">

            <h1 class="text-2xl font-bold">
                Xác thực OTP
            </h1>

            <p class="mt-2">
                Xác thực danh tính người dùng
            </p>

        </div>


        <!-- ========================= -->
        <!-- BƯỚC 1: NHẬP TÀI KHOẢN -->
        <!-- ========================= -->

        <section
            id="step-user"
            class="border p-6"
        >

            <h2 class="text-xl font-bold mb-6">
                Xác thực người dùng
            </h2>


            <div>

                <label class="block mb-2">
                    Mã định danh hoặc tên đăng nhập
                </label>

                <input
                    type="text"
                    id="username"
                    class="border rounded p-2 w-full"
                    placeholder="Nhập mã định danh hoặc tên đăng nhập"
                >

            </div>


            <div class="mt-6">

                <button
                    type="button"
                    onclick="checkUser()"
                    class="border px-5 py-2 rounded"
                >
                    Tiếp tục
                </button>

            </div>

        </section>


        <!-- ========================= -->
        <!-- BƯỚC 3: CHỌN PHƯƠNG THỨC -->
        <!-- ========================= -->

        <section
            id="step-method"
            class="hidden border p-6"
        >

            <h2 class="text-xl font-bold mb-6">
                Chọn phương thức xác thực
            </h2>


            <p class="mb-4">
                Chọn phương thức nhận mã OTP:
            </p>


            <div class="space-y-3">

                <label class="flex items-center gap-3 border p-4 rounded">

                    <input
                        type="radio"
                        name="otp-method"
                        value="email"
                    >

                    <span>
                        Email
                    </span>

                </label>


                <label class="flex items-center gap-3 border p-4 rounded">

                    <input
                        type="radio"
                        name="otp-method"
                        value="phone"
                    >

                    <span>
                        Số điện thoại
                    </span>

                </label>

            </div>


            <div class="mt-6 flex gap-3">

                <button
                    type="button"
                    onclick="backToUser()"
                    class="border px-5 py-2 rounded"
                >
                    Quay lại
                </button>


                <button
                    type="button"
                    onclick="sendOTP()"
                    class="border px-5 py-2 rounded"
                >
                    Gửi mã OTP
                </button>

            </div>

        </section>


        <!-- ========================= -->
        <!-- BƯỚC 6: NHẬP OTP -->
        <!-- ========================= -->

        <section
            id="step-otp"
            class="hidden border p-6"
        >

            <h2 class="text-xl font-bold mb-6">
                Nhập mã OTP
            </h2>


            <p id="otp-message" class="mb-4">
                Mã OTP đã được gửi đến phương thức xác thực của bạn.
            </p>


            <div>

                <label class="block mb-2">
                    Mã OTP
                </label>

                <input
                    type="text"
                    id="otp"
                    maxlength="6"
                    class="border rounded p-2 w-full text-center tracking-widest"
                    placeholder="Nhập mã OTP"
                >

            </div>


            <!-- TIMER -->

            <div class="mt-4">

                Thời gian còn lại:

                <strong id="timer">
                    01:00
                </strong>

            </div>


            <!-- SỐ LẦN NHẬP -->

            <div class="mt-2">

                Số lần nhập:

                <strong id="attempt">
                    0 / 5
                </strong>

            </div>


            <div class="mt-6 flex gap-3">

                <button
                    type="button"
                    onclick="backToMethod()"
                    class="border px-5 py-2 rounded"
                >
                    Quay lại
                </button>


                <button
                    type="button"
                    onclick="verifyOTP()"
                    class="border px-5 py-2 rounded"
                >
                    Xác thực
                </button>

            </div>

        </section>


        <!-- ========================= -->
        <!-- KẾT QUẢ -->
        <!-- ========================= -->

        <section
            id="step-result"
            class="hidden border p-6"
        >

            <h2 class="text-xl font-bold mb-6">
                Kết quả xác thực
            </h2>


            <p id="result-message">
            </p>


            <div class="mt-6">

                <button
                    type="button"
                    onclick="restart()"
                    class="border px-5 py-2 rounded"
                >
                    Thực hiện lại
                </button>

            </div>

        </section>


        <!-- ========================= -->
        <!-- THÔNG BÁO -->
        <!-- ========================= -->

        <div
            id="message"
            class="hidden border p-4 mt-6"
        >
        </div>

    </div>


    <script>

        /*
        |--------------------------------------------------------------------------
        | DEMO DATA
        |--------------------------------------------------------------------------
        */

        const demoUser = {

            username: "HS001",

            email: "student@example.com",

            phone: "0901234567"

        };


        let currentMethod = "";

        let otp = "";

        let attempts = 0;

        let timerInterval = null;

        let remainingSeconds = 60;


        /*
        |--------------------------------------------------------------------------
        | HIỂN THỊ SECTION
        |--------------------------------------------------------------------------
        */

        const sections = [

            "step-user",
            "step-method",
            "step-otp",
            "step-result"

        ];


        function showSection(id) {

            sections.forEach(section => {

                document
                    .getElementById(section)
                    .classList
                    .add("hidden");

            });


            document
                .getElementById(id)
                .classList
                .remove("hidden");

        }


        /*
        |--------------------------------------------------------------------------
        | BƯỚC 1 + 2
        |--------------------------------------------------------------------------
        */

        function checkUser() {

            const username =
                document
                    .getElementById("username")
                    .value
                    .trim();


            if (username === "") {

                showMessage(
                    "Vui lòng nhập mã định danh hoặc tên đăng nhập."
                );

                return;

            }


            /*
             * Demo:
             * HS001 là tài khoản tồn tại.
             */

            if (username !== demoUser.username) {

                showMessage(
                    "Không tìm thấy dữ liệu người dùng."
                );

                return;

            }


            /*
             * Người dùng hợp lệ
             * → sang bước chọn phương thức
             */

            showSection("step-method");

        }


        /*
        |--------------------------------------------------------------------------
        | BƯỚC 3 + 4 + 5
        |--------------------------------------------------------------------------
        */

        function sendOTP() {

            const method =
                document.querySelector(
                    'input[name="otp-method"]:checked'
                );


            if (!method) {

                showMessage(
                    "Vui lòng chọn phương thức xác thực."
                );

                return;

            }


            currentMethod =
                method.value;


            /*
             * Bước 4:
             * Kiểm tra thông tin liên hệ
             */

            if (
                currentMethod === "email" &&
                !demoUser.email
            ) {

                showMessage(
                    "Email không hợp lệ."
                );

                return;

            }


            if (
                currentMethod === "phone" &&
                !demoUser.phone
            ) {

                showMessage(
                    "Số điện thoại không hợp lệ."
                );

                return;

            }


            /*
             * Bước 5:
             * Sinh OTP demo
             */

            otp =
                String(
                    Math.floor(
                        100000 +
                        Math.random() * 900000
                    )
                );


            console.log(
                "OTP DEMO:",
                otp
            );


            let destination =
                currentMethod === "email"
                    ? demoUser.email
                    : demoUser.phone;


            document
                .getElementById("otp-message")
                .innerText =
                `Mã OTP đã được gửi đến ${destination}.`;


            attempts = 0;

            remainingSeconds = 60;


            document
                .getElementById("attempt")
                .innerText =
                "0 / 5";


            showSection("step-otp");


            startTimer();

        }


        /*
        |--------------------------------------------------------------------------
        | BƯỚC 6 + 7
        |--------------------------------------------------------------------------
        */

        function verifyOTP() {

            const inputOTP =
                document
                    .getElementById("otp")
                    .value
                    .trim();


            if (inputOTP === "") {

                showMessage(
                    "Vui lòng nhập mã OTP."
                );

                return;

            }


            /*
             * Tăng số lần thử
             */

            attempts++;


            document
                .getElementById("attempt")
                .innerText =
                `${attempts} / 5`;


            /*
             * Kiểm tra vượt quá 5 lần
             */

            if (attempts > 5) {

                stopTimer();

                showResult(
                    "Nhập quá số lần cho phép."
                );

                return;

            }


            /*
             * Kiểm tra OTP
             */

            if (inputOTP !== otp) {

                if (attempts >= 5) {

                    stopTimer();

                    showResult(
                        "Nhập quá số lần cho phép."
                    );

                    return;

                }


                showMessage(
                    "Xác thực thất bại. Mã OTP không hợp lệ."
                );

                return;

            }


            /*
             * OTP hợp lệ
             */

            stopTimer();


            showResult(
                "Xác thực thành công."
            );

        }


        /*
        |--------------------------------------------------------------------------
        | TIMER 1 PHÚT
        |--------------------------------------------------------------------------
        */

        function startTimer() {

            stopTimer();


            updateTimer();


            timerInterval =
                setInterval(() => {

                    remainingSeconds--;


                    updateTimer();


                    if (remainingSeconds <= 0) {

                        stopTimer();


                        showResult(
                            "Hết thời gian chờ."
                        );

                    }

                }, 1000);

        }


        function updateTimer() {

            const minutes =
                Math.floor(
                    remainingSeconds / 60
                );


            const seconds =
                remainingSeconds % 60;


            document
                .getElementById("timer")
                .innerText =
                `${String(minutes).padStart(2, "0")}:${String(seconds).padStart(2, "0")}`;

        }


        function stopTimer() {

            if (timerInterval) {

                clearInterval(
                    timerInterval
                );

                timerInterval = null;

            }

        }


        /*
        |--------------------------------------------------------------------------
        | KẾT QUẢ
        |--------------------------------------------------------------------------
        */

        function showResult(message) {

            stopTimer();


            document
                .getElementById("result-message")
                .innerText =
                message;


            showSection("step-result");

        }


        /*
        |--------------------------------------------------------------------------
        | QUAY LẠI
        |--------------------------------------------------------------------------
        */

        function backToUser() {

            showSection("step-user");

        }


        function backToMethod() {

            stopTimer();

            showSection("step-method");

        }


        /*
        |--------------------------------------------------------------------------
        | THỰC HIỆN LẠI
        |--------------------------------------------------------------------------
        */

        function restart() {

            stopTimer();


            document
                .getElementById("username")
                .value = "";


            document
                .getElementById("otp")
                .value = "";


            document
                .querySelectorAll(
                    'input[name="otp-method"]'
                )
                .forEach(input => {

                    input.checked = false;

                });


            attempts = 0;

            remainingSeconds = 60;

            otp = "";


            hideMessage();


            showSection("step-user");

        }


        /*
        |--------------------------------------------------------------------------
        | MESSAGE
        |--------------------------------------------------------------------------
        */

        function showMessage(message) {

            const element =
                document.getElementById("message");


            element.innerText =
                message;


            element.classList.remove(
                "hidden"
            );

        }


        function hideMessage() {

            document
                .getElementById("message")
                .classList
                .add("hidden");

        }

    </script>

</body>

</html>