<?php
    $db_host = getenv('DB_HOST') ?: 'localhost';
    $db_name = getenv('DB_NAME') ?: 'stackers';
    $db_user = getenv('DB_USER') ?: 'root';
    $db_password = getenv('DB_PASSWORD') ?: '';

    // Kết nối
    $conn = new mysqli($db_host, $db_user, $db_password);

    if ($conn->connect_error) {
        die("Kết nối MySQL thất bại: " . $conn->connect_error);
    }

    $conn->set_charset("utf8mb4");

    // Kiểm tra database có tồn tại chưa
    $db_check = $conn->query(
        "SELECT SCHEMA_NAME 
        FROM INFORMATION_SCHEMA.SCHEMATA 
        WHERE SCHEMA_NAME = '" . $conn->real_escape_string($db_name) . "'"
    );

    // Nếu không thể kiểm tra database
    if ($db_check === false) {
        die("Không thể kiểm tra database: " . $conn->error);
    }


    // Khởi tạo database
    if ($db_check->num_rows === 0) {
        $db_name_escaped = str_replace('`', '``', $db_name);

        $create_db = $conn->query(
            "CREATE DATABASE `$db_name_escaped`
            CHARACTER SET utf8mb4
            COLLATE utf8mb4_unicode_ci"
        );

        if (!$create_db) {
            die("Không thể tạo database `$db_name`: " . $conn->error);
        }

        echo "Database `$db_name` đã được tạo thành công.<br>";
    }


    // Chọn database sau khi đã tạo
    if (!$conn->select_db($db_name)) {
        die("Không thể chọn database `$db_name`: " . $conn->error);
    }

    $conn->set_charset("utf8mb4");
?>

