<?php
class KyTuyenSinhModel {
    private mysqli $db;
    public function __construct(mysqli $db) { $this->db = $db; }

    // TODO: Thay ten bang/cot theo CSDL that cua nhom.
    public function danhSachKy(): array {
        // Vi du sau khi xac minh schema:
        // return $this->db->query('SELECT maKy, tenKy FROM ky_tuyen_sinh')->fetch_all(MYSQLI_ASSOC);
        throw new LogicException('Chua cau hinh SQL danh sach ky tuyen sinh.');
    }
}
