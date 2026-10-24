<?php
require_once __DIR__ . '/nghiepVuException.php';
require_once __DIR__ . '/kyTuyenSinhModel.php';
require_once __DIR__ . '/hoSoXetTuyenModel.php';
require_once __DIR__ . '/ketQuaXetTuyenModel.php';

class CtrlXetTuyen {
    private KyTuyenSinhModel $kyModel;
    private HoSoXetTuyenModel $hoSoModel;
    private KetQuaXetTuyenModel $ketQuaModel;

    public function __construct(mysqli $db) {
        $this->kyModel = new KyTuyenSinhModel($db);
        $this->hoSoModel = new HoSoXetTuyenModel($db);
        $this->ketQuaModel = new KetQuaXetTuyenModel($db);
    }

    public function danhSachKy(): array { return $this->kyModel->danhSachKy(); }
    public function danhSachHoSo(int $maKy): array { return $this->hoSoModel->danhSachHoSo($maKy); }
    public function xemKetQua(int $maKy): array { return $this->ketQuaModel->layKetQua($maKy); }

    // $ketQua phai duoc tinh theo quy tac chinh thuc cua nhom va duoc kiem tra truoc khi luu.
    public function luuTam(int $maKy, array $ketQua): void {
        if ($maKy <= 0 || !$ketQua) throw new XetTuyenException('Ky/ket qua khong hop le.');
        $this->ketQuaModel->luuTam($maKy, $ketQua);
    }

    public function xacNhan(int $maKy, bool $duocPhep): void {
        if (!$duocPhep) throw new XetTuyenException('Khong co quyen xac nhan xet tuyen.');
        if ($maKy <= 0) throw new XetTuyenException('Ma ky khong hop le.');
        $this->ketQuaModel->xacNhan($maKy);
    }
}
