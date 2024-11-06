<?php
// Bắt đầu session nếu chưa được bắt đầu
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

// Hủy tất cả các biến session
session_unset();

// Hủy session
session_destroy();

// Chuyển hướng người dùng đến trang chính sau khi đăng xuất
header("Location: /HR_HD-Gourmet"); // Cập nhật đường dẫn đến trang chính
exit();
?>
