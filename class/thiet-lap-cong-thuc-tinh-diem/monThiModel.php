<?php

namespace ThietLapCongThuc;

use mysqli;

/**
 * Bảng monhoc (chỉ lấy các môn là môn thi: lamonthi = 1).
 * Lớp "MonTN" trong sequence của use case "Thiết lập công thức tính điểm".
 */
class MonThiModel extends \BaseModel
{
    protected string $tableName = 'monhoc';
    protected string $primaryKey = 'mamonhoc';
    protected array $fields = [
        'mamonhoc'  => 'int',
        'tenmonhoc' => 'string',
        'lamonthi'  => 'int',
    ];

    public function __construct(mysqli $conn)
    {
        parent::__construct($conn);
    }

    /**
     * layDanhSachMonThi()
     * @return array<int, array{mamonhoc:int|string, tenmonhoc:string}>
     */
    public function layDanhSachMonThi(): array
    {
        $sql = "SELECT mamonhoc, tenmonhoc
                FROM monhoc
                WHERE lamonthi = 1
                ORDER BY mamonhoc";

        $result = $this->conn->query($sql);

        return $result->fetch_all(MYSQLI_ASSOC);
    }
}
