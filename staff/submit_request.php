<?php
// Kết nối đến cơ sở dữ liệu
include('../includes/config.php');
session_start();

if (isset($_POST['submit_request'])) {
    // Nhận dữ liệu từ form
    $employee_id = $_SESSION['user_id']; // ID của nhân viên đang đăng nhập
    $request_date = $_POST['request_date'];
    $leave_type = $_POST['leave_type'];
    $cd_hours = isset($_POST['cd_hours']) ? $_POST['cd_hours'] : null; // Số giờ nghỉ CD hoặc UL nếu có
    $reason = $_POST['reason'];
    $proof_image = null;

    // Kiểm tra và xử lý tải lên hình ảnh
    if (!empty($_FILES['proof_image']['name'])) {
        $target_dir = "../uploads/proofs/"; // Thư mục lưu hình ảnh
        if (!is_dir($target_dir)) {
            mkdir($target_dir, 0777, true);
        }

        $target_file = $target_dir . basename($_FILES["proof_image"]["name"]);
        $imageFileType = strtolower(pathinfo($target_file, PATHINFO_EXTENSION));

        // Đổi tên tệp ảnh để tránh trùng lặp
        $new_filename = $target_dir . uniqid() . '.' . $imageFileType;

        // Chỉ cho phép các loại tệp JPG, JPEG, PNG
        if (in_array($imageFileType, ['jpg', 'jpeg', 'png'])) {
            if (move_uploaded_file($_FILES["proof_image"]["tmp_name"], $new_filename)) {
                $proof_image = $new_filename;
            } else {
                echo "<script>alert('Lỗi khi tải lên hình ảnh.'); window.location.href = 'lichlamnv.php';</script>";
                exit;
            }
        } else {
            echo "<script>alert('Chỉ chấp nhận các tệp JPG, JPEG, PNG.'); window.location.href = 'lichlamnv.php';</script>";
            exit;
        }
    }

    // Chuẩn bị truy vấn để chèn dữ liệu vào bảng `leave_requests`
    $stmt = $conn->prepare("INSERT INTO leave_requests (employee_id, request_date, leave_type, cd_hours, reason, proof_image, status) VALUES (?, ?, ?, ?, ?, ?, 'Pending')");
    $stmt->bind_param("ississ", $employee_id, $request_date, $leave_type, $cd_hours, $reason, $proof_image);

    if ($stmt->execute()) {
        // Thông báo yêu cầu gửi thành công
        echo "<script>alert('Yêu cầu nghỉ phép đã được gửi thành công và đang chờ phê duyệt.'); window.location.href = 'lichlamnv.php';</script>";
    } else {
        // Thông báo lỗi nếu không gửi được yêu cầu
        echo "<script>alert('Có lỗi xảy ra khi gửi yêu cầu nghỉ phép.'); window.location.href = 'lichlamnv.php';</script>";
    }

    // Đóng kết nối và truy vấn
    $stmt->close();
    $conn->close();
}
?>
