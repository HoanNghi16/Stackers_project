<?php
class HoSoXetTuyenModel {
    private mysqli $db;
    public function __construct(mysqli $db) { $this->db = $db; }

    public function danhSachHoSo(int $maKy): array {
        if ($maKy <= 0) throw new InvalidArgumentException('Ma ky khong hop le.');
        // TODO: Viet SELECT + JOIN ho so, nguyen vong, diem theo schema that.
        // Dung prepared statement: $stmt = $this->db->prepare('... WHERE maKy = ?');
        throw new LogicException('Chua cau hinh SQL lay ho so theo ky.');
    }
}
