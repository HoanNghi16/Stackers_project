<?php

namespace ThietLapCongThuc;

/**
 * Lỗi nghiệp vụ (vi phạm quy tắc của use case, ví dụ xóa tiêu chuẩn đang được dùng).
 * Khác với lỗi hệ thống (lỗi CSDL, ...): message của exception này được phép
 * hiển thị trực tiếp cho người dùng.
 */
class NghiepVuException extends \Exception
{
}
