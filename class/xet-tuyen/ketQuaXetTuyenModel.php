<?php
class KetQuaXetTuyenModel {
    private mysqli $db;
    public function __construct(mysqli $db) { $this->db = $db; }

    public function layKetQua(int $maKy): array {
        if ($maKy <= 0) throw new InvalidArgumentException('Ma ky khong hop le.');
        // TODO: SELECT ket qua theo ky, dung prepared statements.
        throw new LogicException('Chua cau hinh SQL lay ket qua xet tuyen.');
    }

    public function luuTam(int $maKy, array $ketQua): void {
        // TODO: Transaction + INSERT/UPDATE ket qua voi trang thai CHO_XAC_NHAN.
        // Khong duoc xoa ho so cu hoac ghi de ket qua chinh thuc.
        throw new LogicException('Chua cau hinh SQL luu tam ket qua.');
    }

    public function xacNhan(int $maKy): void {
        // TODO: Kiem tra quyen So GD&DT, du lieu hop le, trang thai cho xac nhan.
        // Transaction + UPDATE trang thai CHINH_THUC (chi ap dung ket qua ky duoc chon).
        throw new LogicException('Chua cau hinh SQL xac nhan ket qua.');
    }
}
