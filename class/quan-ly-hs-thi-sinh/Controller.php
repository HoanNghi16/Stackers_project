<?php
    class CtrlQuanLyHoSoThiSinh{
        private $conn;

        public function __construct($conn){
            if (!isset($_SESSION['manguoidung'])){
                Header("Location: /stackers_project/pages/login/");
            }
            $this->conn = $conn;
        }

        public function DanhSachHocSinhChuNhiem(){
            if (!isset($_SESSION['manguoidung'])) {
                return false;
            }

            $maNguoiDung = $_SESSION['manguoidung'];
            $sql = "SELECT CONCAT(hs.ho, ' ', hs.ten) AS ten,
                           hs.ngaysinh,
                           nd.sodienthoai,
                           nd.email,
                           hs.socccd
                    FROM hocsinh AS hcs
                    JOIN nguoidung AS nd ON nd.manguoidung = hcs.manguoidung
                    JOIN hosonguoidung AS hs ON hs.manguoidung = nd.manguoidung
                    JOIN giaovien AS gv ON gv.magiaovien = hcs.magiaovienchunhiem
                    WHERE hcs.makythi = 1
                      AND gv.manguoidung = ?
                    ORDER BY hs.ho, hs.ten";

            $stmt = $this->conn->prepare($sql);
            if (!$stmt) {
                return false;
            }

            $stmt->bind_param('s', $maNguoiDung);
            if (!$stmt->execute()) {
                $stmt->close();
                return false;
            }

            $result = $stmt->get_result();
            $danhSach = $result ? $result->fetch_all(MYSQLI_ASSOC) : false;
            $stmt->close();

            return $danhSach;
        }
    }
?>
