-- ============================================================
-- DỮ LIỆU THỬ cho use case "Xem danh sách học sinh nhập học"
-- CHỈ DÙNG ĐỂ TEST. Dùng 6 học sinh CÓ SẴN trong DB của nhóm (số báo danh bên dưới),
-- KHÔNG tạo tài khoản mới. Việc làm:
--   * gán điểm xét tuyển (hocsinh.tongdiem) cho 6 em (chỉ khi đang là 0/NULL);
--   * thêm nguyện vọng vào trường THPT mã 1: 5 em ở trạng thái XAC_NHAN_NHAP_HOC,
--     1 em ở trạng thái DAU (chưa xác nhận) để kiểm tra danh sách chỉ lấy em đã xác nhận.
-- Chạy lại nhiều lần được (không thêm trùng nguyện vọng).
-- Đăng nhập xem kết quả: tài khoản THPT của trường 1 (thpt@gmail.com).
-- Xóa dữ liệu thử:
--   DELETE FROM nguyenvong WHERE matruong = 1 AND mahocsinh IN
--     (SELECT mahocsinh FROM hocsinh WHERE sobaodanh IN
--      ('2602201007','2602201016','2602201020','2602202005','2602202007','2602202016'));
--   (điểm xét tuyển đã gán có thể đặt lại về 0 nếu muốn)
-- ============================================================
SET NAMES utf8mb4;

-- Điểm xét tuyển mẫu (không ghi đè em đã có điểm).
UPDATE hocsinh hs
JOIN (
  SELECT '2602201007' sbd, 34.50 diem UNION ALL
  SELECT '2602201016', 32.00 UNION ALL
  SELECT '2602201020', 29.50 UNION ALL
  SELECT '2602202005', 37.00 UNION ALL
  SELECT '2602202007', 27.00 UNION ALL
  SELECT '2602202016', 36.00
) t ON t.sbd = hs.sobaodanh
SET hs.tongdiem = t.diem
WHERE hs.tongdiem IS NULL OR hs.tongdiem = 0;

-- Nguyện vọng trúng tuyển vào trường 1 (thứ tự NV khác nhau).
INSERT INTO nguyenvong (mahocsinh, thutunguyenvong, matruong, trangthaixettuyen)
SELECT hs.mahocsinh, t.nv, 1, t.tt
FROM hocsinh hs JOIN (
  SELECT '2602201007' sbd, 1 nv, 'XAC_NHAN_NHAP_HOC' tt UNION ALL
  SELECT '2602201016', 1, 'XAC_NHAN_NHAP_HOC' UNION ALL
  SELECT '2602201020', 2, 'XAC_NHAN_NHAP_HOC' UNION ALL
  SELECT '2602202005', 1, 'XAC_NHAN_NHAP_HOC' UNION ALL
  SELECT '2602202007', 3, 'XAC_NHAN_NHAP_HOC' UNION ALL
  SELECT '2602202016', 1, 'DAU'
) t ON t.sbd = hs.sobaodanh
WHERE NOT EXISTS (SELECT 1 FROM nguyenvong x WHERE x.mahocsinh = hs.mahocsinh AND x.thutunguyenvong = t.nv);