<?php
    $env = parse_ini_file(__DIR__ . '/.env');
  
    $db_host = $env['DB_HOST'] ?? '';
    $db_name = $env['DB_NAME'] ?? '';
    $db_user = $env['DB_USER'] ?? '';
    $db_password = $env['DB_PASSWORD'] ?? '';

    // Kết nối
    $conn = new mysqli($db_host, $db_user, $db_password, $db_name);

    // echo "success";

    if ($conn->connect_error) {
        die("Kết nối MySQL thất bại: " . $conn->connect_error);
    }
    $conn->set_charset('utf8mb4');

    if (parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH) == '/stackers_project/'){
        header('Location: ./pages/');
    }

?>
