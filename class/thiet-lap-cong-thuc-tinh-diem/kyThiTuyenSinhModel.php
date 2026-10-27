<?php

namespace ThietLapCongThuc;

use mysqli;
use DateTime;
use DateTimeZone;

/**
 * Bảng kythituyensinh.
 * Lớp "KyThiTuyenSinh" trong sequence của use case "Thiết lập công thức tính điểm".
 */
class KyThiTuyenSinhModel extends \BaseModel
{
    protected string $tableName = 'kythituyensinh';
    protected string $primaryKey = 'makythi';
    protected array $fields = [
        'makythi'                 => 'int',
        'namtuyensinh'            => 'int',
        'hanchotdangkychitieu'    => 'string',
        'hanchotdangkynguyenvong' => 'string',
        'hanchotcongbodiemthi'    => 'string',
        'hanchotdangkyphuckhao'   => 'string',
        'hanchotcongbodiemchuan'  => 'string',
        'hanchotxacnhannhaphoc'   => 'string',
    ];

    public function __construct(mysqli $conn)
    {
        parent::__construct($conn);
    }

    /**
     * Năm hiện tại (theo múi giờ Việt Nam). Kỳ tuyển sinh hiện tại là kỳ có
     * namtuyensinh trùng với năm này.
     */
    public function layNamHienTai(): int
    {
        return (int) (new DateTime('now', new DateTimeZone('Asia/Ho_Chi_Minh')))->format('Y');
    }

    /**
     * layKyTuyenSinhHienTai()
     * Trả về dòng kythituyensinh của năm hiện tại, hoặc null nếu chưa có.
     */
    public function layKyTuyenSinhHienTai(): ?array
    {
        $rows = $this->filter(['namtuyensinh' => $this->layNamHienTai()]);
        return $rows[0] ?? null;
    }
}
