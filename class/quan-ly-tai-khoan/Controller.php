<?php
    class CtrlQuanLyTaiKhoan{
        private $conn;

        public function __construct($conn){
            if(!isset($_SESSION['vaitro'])){
                Header("Location: /stackers_project/pages/login");
                return;
            }else{
                if ($_SESSION['vaitro'] != "SO_GD"){
                    Header("Location: /stackers_project/pages");
                    return;
                }
            }
            $this->conn = $conn;
        }



        public function DanhSachTaiKhoan($page){
            $sql = "SELECT * FROM nguoidung 
                            
            ";
        }

    }


?>