<?php
    $db_host = getenv('DB_HOST') ?: 'sv66.dataonline.vn';
    $db_name = getenv('DB_NAME') ?: 'leonovar6_ptud';
    $db_user = getenv('DB_USER') ?: 'leonovar6_ptud';
    $db_password = getenv('DB_PASSWORD') ?: 'asdlfjlasdjfld';



    // Kết nối
    $conn = new mysqli($db_host, $db_user, $db_password, $db_name);

    if ($conn->connect_error) {
        die("Kết nối MySQL thất bại: " . $conn->connect_error);
    }

?>

