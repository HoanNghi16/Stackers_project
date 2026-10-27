<?php
    session_start();
    require("../../components/layout/header.php");
    require("../../components/layout/sidebar.php");
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Stackers - Trang chủ</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="../assets/css/output.css">
</head>
<body>
    <?php
        renderHeader();
        renderSidebar();
    ?>
    <div class="ml-72 w-auto text-center flex">
        <?php require "../danh-sach-truong.php"?>
    </div>
</body>
</html>