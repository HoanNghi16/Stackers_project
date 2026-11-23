<?php

function kiemTraMatKhau(string $password, string $hash): bool
{
    // Tương thích các tài khoản SHA-1 được tạo trước đây.
    if (preg_match('/^[a-f0-9]{40}$/i', $hash)) {
        return hash_equals(strtolower($hash), sha1($password));
    }
    return password_verify($password, $hash);
}
