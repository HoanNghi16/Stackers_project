<?php

namespace ThietLapCongThuc;

use mysqli;

/**
 * Bảng hesodiem (hệ số từng môn thi theo kỳ tuyển sinh).
 * Lớp "HeSoDiem" trong sequence của use case "Thiết lập công thức tính điểm".
 */
class HeSoDiemModel extends \BaseModel
{
    protected string $tableName = 'hesodiem';
    protected string $primaryKey = 'maheso';
    protected array $fields = [
        'maheso'   => 'int',
        'makythi'  => 'int',
        'mamonhoc' => 'int',
        'giatri'   => 'decimal',
    ];

    public function __construct(mysqli $conn)
    {
        parent::__construct($conn);
    }

    /**
     * layHeSoTheoKy(kyHienTai)
     * @return array<int, string> [mamonhoc => giatri]
     */
    public function layHeSoTheoKy(int $maKyThi): array
    {
        $stmt = $this->conn->prepare(
            "SELECT mamonhoc, giatri FROM hesodiem WHERE makythi = ?"
        );
        $stmt->bind_param('i', $maKyThi);
        $stmt->execute();

        $heSo = [];
        foreach ($stmt->get_result()->fetch_all(MYSQLI_ASSOC) as $row) {
            $heSo[(int) $row['mamonhoc']] = $row['giatri'];
        }

        return $heSo;
    }

    /**
     * capNhatHeSo(kyHienTai, dsHeSo)
     * Ghi hệ số từng môn cho kỳ: môn chưa có thì thêm, đã có thì cập nhật
     * (dựa trên ràng buộc unique (makythi, mamonhoc)).
     * Không tự mở transaction: controller chịu trách nhiệm commit/rollback.
     *
     * @param array<int, float> $dsHeSo [mamonhoc => giatri]
     */
    public function capNhatHeSo(int $maKyThi, array $dsHeSo): void
    {
        $stmt = $this->conn->prepare(
            "INSERT INTO hesodiem (makythi, mamonhoc, giatri)
             VALUES (?, ?, ?)
             ON DUPLICATE KEY UPDATE giatri = VALUES(giatri)"
        );

        foreach ($dsHeSo as $maMon => $giaTri) {
            $maMon = (int) $maMon;
            $giaTriChuoi = number_format((float) $giaTri, 2, '.', '');
            $stmt->bind_param('iis', $maKyThi, $maMon, $giaTriChuoi);
            $stmt->execute();
        }
    }
}
