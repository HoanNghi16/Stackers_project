<?php
    session_start();
    require __DIR__."../../../class/dang-nhap.php";
    require "../../index.php";
?>
<?php
    function chuyenHuongTheoVaiTro() {
        if (!isset($_SESSION['vaitro'])) return;

        switch ($_SESSION['vaitro']) {
            case 'HOC_SINH': header("Location: ../hoc-sinh"); exit;
            case 'THPT':     header("Location: ../thpt");     exit;
            case 'THCS':     header("Location: ../thcs");     exit;
            case 'SO_GD':    header("Location: ../so-gd");    exit;
        }
    }

    // Đã đăng nhập từ trước thì chuyển luôn
    chuyenHuongTheoVaiTro();
    $result = true;
    if (isset($_POST['dang-nhap'])){
        $email = $_POST['email'];
        $password = $_POST['password'];
        $ctrl = new CtrlDangNhap($conn);
        $result = $ctrl->DangNhap($email, $password);
        chuyenHuongTheoVaiTro();
    }

?>
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Đăng nhập hệ thống</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-gray-100 min-h-screen flex items-center justify-center">

<div class="max-w-md w-full mx-4">
    <!-- CARD CONTAINER -->
    <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-8">
        
        <!-- HEADER -->
        <div class="text-center mb-8">
            <h1 class="text-2xl font-bold text-gray-800">Đăng nhập</h1>
            <p class="text-gray-500 mt-1 text-sm">Hệ thống quản lý giáo dục</p>
        </div>

        <!-- LOGIN FORM -->
        <form id="login-form" method="POST">
            
            <!-- TÊN ĐĂNG NHẬP -->
            <div class="mb-5">
                <label class="block text-sm font-medium text-gray-700 mb-1">
                    Tên đăng nhập <span class="text-red-500">*</span>
                </label>
                <input 
                    id="email"
                    type="text" 
                    name="email"
                    placeholder="Nhập email hoặc số CCCD..."
                    class="w-full border border-gray-300 rounded-lg px-4 py-2.5 focus:outline-none focus:ring-2 focus:ring-blue-500"
                    required
                >
            </div>

            <!-- MẬT KHẨU -->
            <div class="mb-5">
                <label class="block text-sm font-medium text-gray-700 mb-1">
                    Mật khẩu <span class="text-red-500">*</span>
                </label>
                <input 
                    id="password"
                    name="password"
                    type="password" 
                    placeholder="••••••••"
                    class="w-full border border-gray-300 rounded-lg px-4 py-2.5 focus:outline-none focus:ring-2 focus:ring-blue-500"
                    required
                >
            </div>

            <!-- HIỂN THỊ LỖI (Alternative Flows) -->
            <?php 
                if (is_string($result)){
                    echo '
                        <div id="login-error" class="mb-5 p-4 rounded-lg bg-red-50 border border-red-200 text-red-700 text-sm">
                            '.$result.'
                        </div>
                    ';
                }
            ?>


            <!-- BUTTON ĐĂNG NHẬP -->
            <button 
                id="dang-nhap"
                name="dang-nhap"
                type="submit"
                class="w-full bg-blue-600 hover:bg-blue-700 text-white py-2.5 rounded-lg font-medium transition duration-200"
            >
                Đăng nhập
            </button>

            <!-- ĐĂNG NHẬP BẰNG OTP (Alternative Flow 3.1) -->
            <div class="mt-4 text-center">
                <button 
                    type="button" 
                    onclick="loginWithOTP()"
                    class="text-sm text-blue-600 hover:text-blue-800 font-medium"
                >
                    Đăng nhập bằng Xác thực OTP
                </button>
            </div>

        </form>

    </div>

</div>

<!-- SUCCESS TOAST MESSAGE -->
<div id="success-message" class="hidden fixed top-6 right-6 bg-green-600 text-white px-5 py-4 rounded-lg shadow-lg">
    <div class="font-semibold">Thành công</div>
    <div id="success-text" class="text-sm mt-1">Đăng nhập thành công. Đang chuyển hướng...</div>
</div>

<script>
// Danh sách tài khoản mẫu kết nối tương ứng với cây thư mục dự án
// const mockUsers = [
//     { 
//         username: 'nguyenvanan@gmail.com', 
//         password: '123', 
//         role: 'Sở GD&ĐT', 
//         redirectUrl: '../so-gd/quan-ly-tai-khoan/index.php',
//         status: 'active' 
//     },
//     { 
//         username: 'tranthibinh@gmail.com', 
//         password: '123', 
//         role: 'Trường THCS', 
//         redirectUrl: '../thcs/quan-ly-hs-thi-sinh/index.php',
//         status: 'active' 
//     },
//     { 
//         username: 'levancuong@gmail.com', 
//         password: '123', 
//         role: 'Trường THPT', 
//         redirectUrl: '../thpt/dang-ky-chi-tieu/index.php', 
//         status: 'active' 
//     },
//     { 
//         username: 'phamminhdung', 
//         password: '123', 
//         role: 'Học sinh', 
//         redirectUrl: '../hoc-sinh/xem-ds-diem-thi/index.php',
//         status: 'active' 
//     },
//     { 
//         username: 'locked_user', 
//         password: '123', 
//         role: 'Học sinh', 
//         status: 'locked' // Tài khoản mẫu để test lỗi khóa tài khoản (Alternative 4.3)
//     }
// ];

// function handleLogin(event) {
//     event.preventDefault();

//     const usernameInput = document.getElementById('username').value.trim();
//     const passwordInput = document.getElementById('password').value.trim();
//     const errorDiv = document.getElementById('login-error');

//     // Ẩn thông báo lỗi cũ
//     errorDiv.classList.add('hidden');

//     // Tìm kiếm thông tin người dùng
//     const user = mockUsers.find(u => u.username === usernameInput);

//     // Alternative 4.1: Không tìm thấy thông tin người dùng
//     if (!user) {
//         errorDiv.textContent = 'Tên đăng nhập không tồn tại.';
//         errorDiv.classList.remove('hidden');
//         return;
//     }

//     // Alternative 4.3: Tài khoản bị khóa hoặc vô hiệu hóa
//     if (user.status === 'locked') {
//         errorDiv.textContent = 'Tài khoản đã bị khóa hoặc vô hiệu hóa. Vui lòng liên hệ Quản trị viên.';
//         errorDiv.classList.remove('hidden');
//         return;
//     }

//     // Alternative 4.2: Sai mật khẩu
//     if (user.password !== passwordInput) {
//         errorDiv.textContent = 'Sai mật khẩu.';
//         errorDiv.classList.remove('hidden');
//         return;
//     }

//     // Basic Flow (Bước 5, 6, 7): Đăng nhập thành công
//     console.log('Lưu lịch sử đăng nhập thành công cho:', user.username);

//     // Hiển thị toast thông báo thành công
//     const successToast = document.getElementById('success-message');
//     document.getElementById('success-text').textContent = `Đăng nhập thành công (${user.role}). Đang chuyển hướng...`;
//     successToast.classList.remove('hidden');

//     // Điều hướng đến đúng trang chủ của từng vai trò sau 1.5 giây
//     setTimeout(() => {
//         window.location.href = user.redirectUrl;
//     }, 1500);
// }

// Xử lý Alternative Flow 3.1: Đăng nhập bằng OTP
function loginWithOTP() {

    window.location.href = '../xac-thuc-otp/';
}
</script>

</body>
</html>