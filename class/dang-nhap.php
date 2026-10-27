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
                "SELECT email, nd.manguoidung, vaitro, hs.ten, hs.anhdaidien, matkhau FROM nguoidung nd LEFT JOIN hosonguoidung hs ON nd.manguoidung = hs.manguoidung WHERE nd.email = ? OR hs.socccd=?"
            );

            $stmt->bind_param("ss", $email, $email);
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
                $_SESSION['ten'] = $user['ten'];
                $_SESSION['anhdaidien'] = $user['anhdaidien'];
                return true;
            }

            return "Không tìm thấy người dùng";
        }

    }
?>