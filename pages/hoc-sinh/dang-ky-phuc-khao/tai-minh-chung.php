<?php
declare(strict_types=1);
if (session_status() === PHP_SESSION_NONE) session_start();
if (empty($_SESSION['manguoidung']) || strtoupper((string)($_SESSION['vaitro'] ?? '')) !== 'HOC_SINH') {
    http_response_code(403);
    exit('Bạn không có quyền tải minh chứng.');
}
$id = (string)($_GET['maphieu'] ?? '');
if (!preg_match('/^[1-9][0-9]*$/D', $id)) {
    http_response_code(400);
    exit('Mã phiếu không hợp lệ.');
}
require_once __DIR__ . '/../../../class/dang-ky-phuc-khao.php';
try {
    $db = PhatDb::connect();
    $service = new PhatDangKyPhucKhao($db);
    // Chỉ cho chủ phiếu xem file, luôn kiểm tra với tài khoản đang đăng nhập.
    $file = $service->evidenceForDownload((int)$_SESSION['manguoidung'], (int)$id);
    $storage = PhatDangKyPhucKhao::evidenceStorageDir(false);
    $db->close();
    $stored = (string)($file['tenfileluu'] ?? '');
    if ($file === null || $storage === null || !preg_match('/^[a-f0-9]{32}\.(?:pdf|jpg|png)$/D', $stored)) {
        http_response_code(404);
        exit('Không tìm thấy file minh chứng.');
    }
    $path = $storage . DIRECTORY_SEPARATOR . $stored;
    if (!is_file($path) || !is_readable($path)) {
        http_response_code(404);
        exit('File minh chứng không còn khả dụng.');
    }
    $types = ['application/pdf', 'image/jpeg', 'image/png'];
    $mime = in_array((string)$file['loaimime'], $types, true) ? (string)$file['loaimime'] : 'application/octet-stream';
    $name = basename(str_replace('\\', '/', (string)$file['tenfilegoc']));
    $name = str_replace(["\r", "\n", '"'], '', $name);
    $extension = pathinfo($stored, PATHINFO_EXTENSION);
    header('Content-Type: ' . $mime);
    header('Content-Disposition: attachment; filename="minh-chung.' . $extension . '"; filename*=UTF-8\'\'' . rawurlencode($name));
    header('Content-Length: ' . (string)filesize($path));
    header('Cache-Control: no-store, private');
    header('Pragma: no-cache');
    header('X-Content-Type-Options: nosniff');
    header("Content-Security-Policy: default-src 'none'");
    readfile($path);
    exit;
} catch (Throwable $e) {
    error_log('Tai minh chung PK: ' . get_class($e) . ' - ' . $e->getMessage());
    http_response_code(500);
    exit('Không tải được file minh chứng. Vui lòng thử lại sau.');
}
