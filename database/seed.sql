-- ============================================================
-- Dữ liệu khởi tạo cho use case "Thiết lập công thức tính điểm"
-- Chạy SAU khi đã tạo bảng (tuyensinh10_tables_only.sql).
-- Có thể chạy lại nhiều lần: INSERT IGNORE bỏ qua dòng trùng khóa unique
-- (kythituyensinh.namtuyensinh, monhoc.tenmonhoc).
-- ============================================================

SET NAMES utf8mb4;

-- Kỳ tuyển sinh hiện tại = năm hiện tại (2026).
-- Các hạn chót bên dưới chỉ là giá trị mẫu, chỉnh lại theo lịch thực tế.
INSERT IGNORE INTO kythituyensinh
    (namtuyensinh, hanchotdangkychitieu, hanchotdangkynguyenvong,
     hanchotcongbodiemthi, hanchotdangkyphuckhao,
     hanchotcongbodiemchuan, hanchotxacnhannhaphoc)
VALUES
    (2026, '2026-03-31', '2026-05-15', '2026-06-30', '2026-07-05', '2026-07-15', '2026-07-25');

-- Môn học: 3 môn thi (lamonthi = 1) + 1 môn không thi để kiểm tra bộ lọc.
INSERT IGNORE INTO monhoc (tenmonhoc, lamonthi) VALUES
    ('Toán',                 1),
    ('Ngữ văn',              1),
    ('Tiếng Anh',            1),
    ('Giáo dục công dân',    0);

-- ------------------------------------------------------------
-- (Tùy chọn) Công thức mẫu, để thử luồng thay thế 2.1
-- "Kỳ tuyển sinh hiện tại đã có công thức tính điểm".
-- Bỏ dấu comment nếu muốn dùng. Hệ số Toán/Văn = 2, Anh = 1.
-- ------------------------------------------------------------
-- INSERT IGNORE INTO hesodiem (makythi, mamonhoc, giatri)
-- SELECT k.makythi, m.mamonhoc, IF(m.tenmonhoc = 'Tiếng Anh', 1, 2)
-- FROM kythituyensinh k
-- JOIN monhoc m ON m.lamonthi = 1
-- WHERE k.namtuyensinh = 2026;
--
-- INSERT INTO tieuchuancongdiem (makythi, loaitieuchuan, diemcong, dieukienapdung)
-- SELECT makythi, 'UU_TIEN', 2.00, 'Con liệt sĩ / thương binh'
-- FROM kythituyensinh WHERE namtuyensinh = 2026;
-- INSERT INTO tieuchuancongdiem (makythi, loaitieuchuan, diemcong, dieukienapdung)
-- SELECT makythi, 'UU_TIEN', 1.00, 'Người dân tộc thiểu số'
-- FROM kythituyensinh WHERE namtuyensinh = 2026;
-- INSERT INTO tieuchuancongdiem (makythi, loaitieuchuan, diemcong, dieukienapdung)
-- SELECT makythi, 'KHUYEN_KHICH', 0.50, 'Có chứng chỉ nghề / năng khiếu'
-- FROM kythituyensinh WHERE namtuyensinh = 2026;
