-- Chạy trước khi sử dụng chức năng đổi mật khẩu để lưu được hash bcrypt.
ALTER TABLE nguoidung MODIFY COLUMN matkhau VARCHAR(255) NOT NULL;
