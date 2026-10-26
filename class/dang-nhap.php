<?php
    class CtrlDangNhap{ 
        private $conn;

        public function __construct($conn) {
            global $conn;
            $this->conn = $conn;
        }


        public function DangNhap($email, $password) {
            $hashedPassword = sha1($password);

            $stmt = $this->conn->prepare(
                "SELECT * FROM nguoidung WHERE email = ?"
            );

            $stmt->bind_param("s", $email);
            $stmt->execute();

            $result = $stmt->get_result();
            $user = $result->fetch_assoc();

            $stmt->close();

            if ($user) {
                if ($user['matkhau'] != $hashedPassword){
                    return "Sai mật khẩu";
                }
                $_SESSION['manguoidung'] = $user['manguoidung'];
                $_SESSION['vaitro'] = $user['vaitro'];
                return true;
            }

            return "Không tìm thấy người dùng";
        }

    }
?>