<?php
session_start();
header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: no-store');
function passwordResponse(int $status, array $data): void {
    http_response_code($status);
    echo json_encode($data, JSON_UNESCAPED_UNICODE);
    exit;
}
if (empty($_SESSION['manguoidung']) || !in_array($_SESSION['vaitro'] ?? '', ['SO_GD', 'THCS', 'THPT', 'HOC_SINH'], true)) {
    passwordResponse(401, ['errors' => ['general' => 'Phiên đăng nhập đã hết hạn. Vui lòng đăng nhập lại.']]);
}
$_SESSION['doi_mat_khau_csrf'] ??= bin2hex(random_bytes(32));
if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    passwordResponse(200, ['csrf_token' => $_SESSION['doi_mat_khau_csrf']]);
}
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Allow: GET, POST');
    passwordResponse(405, ['errors' => ['general' => 'Phương thức không hợp lệ.']]);
}
$token = $_POST['csrf_token'] ?? '';
if (!is_string($token) || !hash_equals($_SESSION['doi_mat_khau_csrf'], $token)) {
    passwordResponse(403, ['errors' => ['general' => 'Phiên gửi biểu mẫu không hợp lệ. Vui lòng đóng và mở lại biểu mẫu.']]);
}
// Không để cảnh báo kết nối hoặc nội dung bootstrap làm hỏng phản hồi JSON.
ob_start();
try {
    require __DIR__ . '/../../index.php';
    require_once __DIR__ . '/../../class/doi-mat-khau.php';
    $errors = (new CtrlDoiMatKhau($conn))->doiMatKhau((int) $_SESSION['manguoidung'], $_POST);
} catch (Throwable $e) {
    $errors = ['general' => 'Đổi mật khẩu không thành công.'];
}
ob_end_clean();
if ($errors) passwordResponse(422, ['errors' => $errors]);
session_regenerate_id(true);
$_SESSION['doi_mat_khau_csrf'] = bin2hex(random_bytes(32));
passwordResponse(200, ['message' => 'Đổi mật khẩu thành công.', 'csrf_token' => $_SESSION['doi_mat_khau_csrf']]);
