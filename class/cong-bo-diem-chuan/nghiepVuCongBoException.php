<?php
namespace CongBoDiemChuan;
 
/** Lỗi nghiệp vụ: thông báo được phép hiển thị trực tiếp cho người dùng. */
class NghiepVuException extends \RuntimeException
{
    /** Dữ liệu kèm theo (vd: danh sách trường chưa có điểm chuẩn). */
    private array $chiTiet;
 
    public function __construct(string $message, array $chiTiet = [])
    {
        parent::__construct($message);
        $this->chiTiet = $chiTiet;
    }
 
    public function layChiTiet(): array
    {
        return $this->chiTiet;
    }
}