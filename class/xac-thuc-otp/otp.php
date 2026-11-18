<?php
declare(strict_types=1);

class CtrlXacThucOTP
{
    private mysqli $conn;
    private string $option;

    public function __construct(mysqli $conn, string $option)
    {
        if (!in_array($option, ['dang-nhap', 'xac-thuc'], true)) {
            throw new InvalidArgumentException('Option xác thực không hợp lệ.');
        }

        $this->conn = $conn;
        $this->option = $option;
    }

    /** Lấy thông tin người dùng theo CCCD hoặc email; chỉ đọc dữ liệu. */
    public function XacThucNguoiDung(string $madinhdanh): ?array
    {
        $sql = "SELECT nd.manguoidung, nd.vaitro, nd.email, CONCAT(hs.ho, ' ', hs.ten) as ten, hs.anhdaidien
                FROM nguoidung AS nd
                LEFT JOIN hosonguoidung AS hs ON hs.manguoidung = nd.manguoidung
                WHERE hs.socccd = ? OR nd.email = ?
                LIMIT 1";

        $stmt = $this->conn->prepare($sql);
        $stmt->bind_param('ss', $madinhdanh, $madinhdanh);
        $stmt->execute();
        $user = $stmt->get_result()->fetch_assoc();
        $stmt->close();

        return $user ?: null;
    }

    /** Ghi session tương ứng sau khi OTP đã được xác minh phía máy chủ. */
    public function HoanTatXacThuc(array $user): void
    {
        if ($this->option === 'dang-nhap') {
            session_regenerate_id(true);
            $_SESSION['manguoidung'] = $user['manguoidung'];
            $_SESSION['vaitro'] = $user['vaitro'];
            $_SESSION['ten'] = $user['ten'];
            $_SESSION['anhdaidien'] = $user['anhdaidien'];
            return;
        }

        $_SESSION['otp'] = true;
    }

    public static function DaXacThuc(): bool
    {
        return ($_SESSION['otp'] ?? false) === true;
    }
}
