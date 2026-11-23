<?php
require_once __DIR__ . '/mat-khau.php';

class CtrlDoiMatKhau
{
    private mysqli $conn;

    public function __construct(mysqli $conn)
    {
        $this->conn = $conn;
    }

    public function doiMatKhau(int $userId, array $input): array
    {
        $labels = ['current_password' => 'mật khẩu hiện tại', 'new_password' => 'mật khẩu mới', 'confirm_password' => 'xác nhận mật khẩu mới'];
        $errors = [];
        foreach ($labels as $field => $label) {
            if (!isset($input[$field]) || !is_string($input[$field]) || $input[$field] === '') {
                $errors[$field] = 'Vui lòng nhập ' . $label . '.';
            }
        }
        if ($errors) return $errors;
        if ($input['new_password'] !== $input['confirm_password']) {
            return ['confirm_password' => 'Mật khẩu xác nhận không khớp.'];
        }
        if (mb_strlen($input['new_password'], 'UTF-8') < 6) {
            return ['new_password' => 'Mật khẩu mới tối thiểu 6 ký tự.'];
        }

        $transactionStarted = false;
        try {
            $stmt = $this->conn->prepare('SELECT matkhau FROM nguoidung WHERE manguoidung = ?');
            $stmt->bind_param('i', $userId);
            if (!$stmt->execute()) throw new RuntimeException();
            $user = $stmt->get_result()->fetch_assoc();
            $stmt->close();
            if (!$user || !kiemTraMatKhau($input['current_password'], $user['matkhau'])) {
                return ['current_password' => 'Mật khẩu hiện tại không chính xác.'];
            }
            $hash = password_hash($input['new_password'], PASSWORD_ARGON2ID);
            if (!$this->conn->begin_transaction()) throw new RuntimeException();
            $transactionStarted = true;
            // Chỉ cập nhật nếu mật khẩu chưa bị thay đổi bởi yêu cầu khác.
            $stmt = $this->conn->prepare('UPDATE nguoidung SET matkhau = ? WHERE manguoidung = ? AND matkhau = ?');
            $stmt->bind_param('sis', $hash, $userId, $user['matkhau']);
            $ok = $stmt->execute() && $stmt->affected_rows === 1;
            $stmt->close();
            if (!$ok) throw new RuntimeException();
            $stmt = $this->conn->prepare('SELECT matkhau FROM nguoidung WHERE manguoidung = ?');
            $stmt->bind_param('i', $userId);
            if (!$stmt->execute()) throw new RuntimeException();
            $saved = $stmt->get_result()->fetch_assoc();
            $stmt->close();
            if (!$saved || !hash_equals($hash, $saved['matkhau'])) throw new RuntimeException();
            if (!$this->conn->commit()) throw new RuntimeException();
            $transactionStarted = false;
            return [];
        } catch (Throwable $e) {
            if ($transactionStarted) {
                try { $this->conn->rollback(); } catch (Throwable $ignored) {}
            }
            return ['general' => 'Đổi mật khẩu không thành công.'];
        }
    }
}
