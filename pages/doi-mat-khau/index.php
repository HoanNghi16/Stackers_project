<?php
session_start();
$roles = ['SO_GD' => 'Sở GD&ĐT', 'THCS' => 'Trường THCS', 'THPT' => 'Trường THPT', 'HOC_SINH' => 'Học sinh'];
$role = $_SESSION['vaitro'] ?? '';
if (empty($_SESSION['manguoidung']) || !isset($roles[$role])) {
    header('Location: /stackers_project/pages/login/');
    exit;
}
header('Cache-Control: no-store');
require_once __DIR__ . '/../../class/doi-mat-khau.php';
$_SESSION['doi_mat_khau_csrf'] ??= bin2hex(random_bytes(32));
$errors = [];
$success = $_SESSION['doi_mat_khau_success'] ?? false;
unset($_SESSION['doi_mat_khau_success']);
$account = ['ten' => $_SESSION['ten'] ?? '', 'email' => ''];
try {
    require __DIR__ . '/../../index.php';
    $stmt = $conn->prepare('SELECT nd.email, hs.ten FROM nguoidung nd LEFT JOIN hosonguoidung hs ON hs.manguoidung = nd.manguoidung WHERE nd.manguoidung = ?');
    $stmt->bind_param('i', $_SESSION['manguoidung']);
    $stmt->execute();
    $account = $stmt->get_result()->fetch_assoc();
    $stmt->close();
    if (!$account) {
        header('Location: /stackers_project/pages/logout/');
        exit;
    }
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        $token = $_POST['csrf_token'] ?? '';
        if (!is_string($token) || !hash_equals($_SESSION['doi_mat_khau_csrf'], $token)) {
            $errors['general'] = 'Phiên gửi biểu mẫu không hợp lệ. Vui lòng thử lại.';
        } else {
            $controller = new CtrlDoiMatKhau($conn);
            $errors = $controller->doiMatKhau((int) $_SESSION['manguoidung'], $_POST);
            if (!$errors) {
                session_regenerate_id(true);
                $_SESSION['doi_mat_khau_csrf'] = bin2hex(random_bytes(32));
                $_SESSION['doi_mat_khau_success'] = true;
                header('Location: /stackers_project/pages/doi-mat-khau/', true, 303);
                exit;
            }
        }
    }
} catch (Throwable $e) {
    $errors['general'] = 'Đổi mật khẩu không thành công.';
}
function escapePasswordPage($value): string {
    return htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8');
}
$home = '/stackers_project/pages/' . str_replace('_', '-', strtolower($role)) . '/';
?>
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Đổi mật khẩu</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-slate-100 min-h-screen text-slate-800">
    <main class="max-w-3xl mx-auto px-4 py-8 space-y-5">
        <a href="<?= escapePasswordPage($home) ?>" class="text-indigo-700 hover:underline">← Về trang tổng quan</a>
        <header class="rounded-xl bg-indigo-700 text-white p-6">
            <p class="text-sm text-indigo-200"><?= escapePasswordPage($roles[$role]) ?></p>
            <h1 class="text-2xl font-bold mt-1">Đổi mật khẩu</h1>
        </header>
        <?php if ($success): ?>
            <div role="status" class="rounded-lg border border-green-200 bg-green-50 text-green-800 p-4">Đổi mật khẩu thành công.</div>
        <?php endif; ?>
        <?php if (isset($errors['general'])): ?>
            <div role="alert" class="rounded-lg border border-red-200 bg-red-50 text-red-700 p-4"><?= escapePasswordPage($errors['general']) ?></div>
        <?php endif; ?>
        <section class="bg-white rounded-xl shadow-sm p-5">
            <h2 class="font-semibold mb-4">Tài khoản</h2>
            <dl class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                <div><dt class="text-sm text-slate-500">Họ và tên</dt><dd class="mt-1 break-words"><?= escapePasswordPage($account['ten'] ?? '') ?></dd></div>
                <div><dt class="text-sm text-slate-500">Email đăng nhập</dt><dd class="mt-1 break-words"><?= escapePasswordPage($account['email'] ?? '') ?></dd></div>
            </dl>
        </section>
        <form method="post" action="/stackers_project/pages/doi-mat-khau/" class="bg-white rounded-xl shadow-sm p-5 space-y-4">
            <input type="hidden" name="csrf_token" value="<?= escapePasswordPage($_SESSION['doi_mat_khau_csrf']) ?>">
            <p id="password-help" class="text-sm text-slate-500">Mật khẩu mới tối thiểu 6 ký tự.</p>
            <?php foreach (['current_password' => 'Mật khẩu hiện tại', 'new_password' => 'Mật khẩu mới', 'confirm_password' => 'Xác nhận mật khẩu mới'] as $field => $label): ?>
                <div>
                    <label for="<?= $field ?>" class="block text-sm font-medium"><?= $label ?> <span class="text-red-500">*</span></label>
                    <input id="<?= $field ?>" name="<?= $field ?>" type="password" required autocomplete="<?= $field === 'current_password' ? 'current-password' : 'new-password' ?>"
                        aria-invalid="<?= isset($errors[$field]) ? 'true' : 'false' ?>" aria-describedby="password-help <?= $field ?>-error"
                        class="mt-1 w-full rounded-lg border <?= isset($errors[$field]) ? 'border-red-500' : 'border-slate-300' ?> px-3 py-2 focus:outline-none focus:ring-2 focus:ring-indigo-500">
                    <p id="<?= $field ?>-error" class="text-sm text-red-600 mt-1" role="alert"><?= escapePasswordPage($errors[$field] ?? '') ?></p>
                </div>
            <?php endforeach; ?>
            <label class="flex items-center gap-2 text-sm"><input id="show-passwords" type="checkbox"> Hiển thị mật khẩu</label>
            <div class="flex gap-3 pt-2">
                <button type="submit" class="flex-1 rounded-lg bg-indigo-600 hover:bg-indigo-700 text-white font-medium py-2">Đổi mật khẩu</button>
                <a href="<?= escapePasswordPage($home) ?>" class="rounded-lg px-7 py-2 border border-slate-300 hover:bg-slate-50">Hủy</a>
            </div>
        </form>
    </main>
    <script>
    document.getElementById('show-passwords').addEventListener('change', function () {
        ['current_password', 'new_password', 'confirm_password'].forEach(id => {
            document.getElementById(id).type = this.checked ? 'text' : 'password';
        });
    });
    </script>
</body>
</html>
