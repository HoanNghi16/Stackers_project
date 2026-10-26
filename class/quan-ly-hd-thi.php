<?php
    class CtrlQuanLyHoiDongThi{
        private $conn;

        public function __construct($conn){
            $this->conn = $conn;


        }


        // Lấy môn thi để thực hiện phân công
        public function GetMonThi() {
            $sql = "SELECT * FROM monhoc WHERE lamonthi = 1";

            $result = $this->conn->query($sql);

            if (!$result) {
                return false;
            }

            $monThi = [];

            while ($row = $result->fetch_assoc()) {
                $monThi[] = $row;
            }

            return $monThi;
        }

        public function GetLichThi(){
            $sql = "SELECT * FROM lichthi";

            $result = $this->conn->query($sql);

            if (!$result) {
                return false;
            }

            $lichThi = [];

            while ($row = $result->fetch_assoc()) {
                $lichThi[] = $row;
            }

            return $lichThi;
        }

        public function GetGiaoVienDuocDeCuTheoMaMon($mamon) {
            $sql = "SELECT gv.magiaovien, CONCAT(hs.ho, ' ',hs.ten) as tengiaovien, nd.vaitro as capgiangday  FROM giaovien gv JOIN hosonguoidung hs
                        ON gv.manguoidung = hs.manguoidung
                    JOIN nguoidung nd
                        ON gv.manguoidung = nd.manguoidung
                    WHERE duocdecu = 1
                    AND magiaovien NOT IN (
                        SELECT magiaovien FROM phancongchamthi
                        UNION
                        SELECT magiaovien FROM phancongphuckhao
                        UNION
                        SELECT magiaovien FROM phanconggacthi
                        UNION
                        SELECT magiaovien FROM phancongrade
                    )
                    AND mamonhoc = ?";

            $stmt = $this->conn->prepare($sql);
            $stmt->bind_param("i", $mamon);
            $stmt->execute();

            $result = $stmt->get_result();
            return $result->fetch_all(MYSQLI_ASSOC);
        }

        public function GetGiaoVienDuocDeCu(){
            $sql = "SELECT gv.magiaovien, CONCAT(hs.ho, ' ',hs.ten) as tengiaovien, nd.vaitro as capgiangday, mh.tenmonhoc FROM giaovien gv JOIN hosonguoidung hs
                        ON gv.manguoidung = hs.manguoidung
                    JOIN nguoidung nd
                        ON gv.manguoidung = nd.manguoidung
                    JOIN monhoc mh
                        ON gv.mamonhoc = mh.mamonhoc
                    WHERE duocdecu = 1
                    AND magiaovien NOT IN (
                        SELECT magiaovien FROM phancongchamthi
                        UNION
                        SELECT magiaovien FROM phancongphuckhao
                        UNION
                        SELECT magiaovien FROM phanconggacthi
                        UNION
                        SELECT magiaovien FROM phancongrade
                    )
                ";

            $stmt = $this->conn->prepare($sql);
            $stmt->execute();

            $result = $stmt->get_result();
            return $result->fetch_all(MYSQLI_ASSOC);
        }


        public function GetDanhSachDiaDiemThi() {
            $sql = "SELECT tentruong as tendiadiemthi, matruong as madiadiemthi FROM truonghoc WHERE ladiemthi = 1";
            $result = $this->conn->query($sql);

            if (!$result) {
                return [];
            }

            $dsDiaDiem = [];

            while ($row = $result->fetch_assoc()) {
                $dsDiaDiem[] = $row;
            }

            return $dsDiaDiem;
        }


        public function GetGiaoVienDaPhanCong($loaihoidong, $mamon) {
            if (
                $loaihoidong != "chamthi" &&
                $loaihoidong != "gacthi" &&
                $loaihoidong != "rade" &&
                $loaihoidong != "phuckhao"
            ) {
                return false;
            }

            $table = "phancong" . $loaihoidong;

            $sql = "SELECT gv.magiaovien, CONCAT(hs.ho, ' ',hs.ten) as tengiaovien, nd.vaitro as capgiangday, hd.vaitro
                    FROM " . $table . " hd
                    JOIN giaovien gv
                        ON hd.magiaovien = gv.magiaovien
                    JOIN hosonguoidung hs
                        ON gv.manguoidung = hs.manguoidung
                    JOIN nguoidung nd
                        ON nd.manguoidung = gv.manguoidung
                    WHERE gv.mamonhoc = ?";

            $stmt = $this->conn->prepare($sql);
            $stmt->bind_param("s", $mamon);
            $stmt->execute();

            $result = $stmt->get_result();
            return $result->fetch_all(MYSQLI_ASSOC);
        }

        public function ThemGiaoVienVaoHoiDong(
            $loaihoidong,
            $mamon,
            $magiaovien,
            $vaitro,
            $madiemthi
        ) {
            if (
                $loaihoidong != "chamthi" &&
                $loaihoidong != "gacthi" &&
                $loaihoidong != "rade" &&
                $loaihoidong != "phuckhao"
            ) {
                return false;
            }

            if (
                $vaitro != "CB" &&
                $vaitro != "DP" &&
                $vaitro != "TRUONG_HD"
            ) {
                return false;
            }

            $table = "phancong" . $loaihoidong;

            if ($loaihoidong == "gacthi") {
                // Chưa xử lý vì cần xác định cấu trúc bảng phân công gác thi.
                return false;
            }

            $makythi = 1;

            $sql = "INSERT INTO $table (makythi, magiaovien, vaitro)
                    VALUES (?, ?, ?)";

            $stmt = $this->conn->prepare($sql);

            if (!$stmt) {
                return false;
            }

            $stmt->bind_param("iis", $makythi, $magiaovien, $vaitro);

            $result = $stmt->execute();

            $stmt->close();

            return $result;
        }

        public function LuuHoiDongGacThi($madiadiemthi, $dsGiaoVien)
        {
            $dsLichThi   = [1, 2, 3];
            $vaiTroHopLe = ['CB', 'DP'];
        
            if (empty($dsGiaoVien)) {
                return false;
            }
        
            $this->conn->begin_transaction();
        
            try {
                $stmt = $this->conn->prepare(
                    "INSERT INTO phanconggacthi (malichthi, magiaovien, madiadiemthi, vaitro, maphong)
                    VALUES (?, ?, ?, ?, NULL)"
                );
        
                if (!$stmt) {
                    throw new Exception($this->conn->error);
                }
        
                foreach ($dsGiaoVien as $magiaovien => $vaitro) {
                    if (!in_array($vaitro, $vaiTroHopLe, true)) {
                        throw new InvalidArgumentException("Vai trò không hợp lệ: $vaitro");
                    }
        
                    foreach ($dsLichThi as $malichthi) {
                        $magv = (string)$magiaovien;
                        $madd = (string)$madiadiemthi;
        
                        $stmt->bind_param('isss', $malichthi, $magv, $madd, $vaitro);
        
                        if (!$stmt->execute()) {
                            throw new Exception($stmt->error);
                        }
                    }
                }
        
                $stmt->close();
                $this->conn->commit();
                return true;
            } catch (Throwable $e) {
                $this->conn->rollback();
                error_log('LuuHoiDongGacThi: ' . $e->getMessage());
                echo $e->getMessage();
                return false;
            }
        }

    }
?>