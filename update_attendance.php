<?php
include('includes/config.php');

// Kiểm tra phiên và trạng thái đăng nhập
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

if (!isset($_SESSION['user_id'])) {
    header("Location: index.php");
    exit();
}

// Kiểm tra xem các dữ liệu yêu cầu có được gửi qua POST hay không
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    // Lấy dữ liệu từ form
    $fullname = $_POST['fullname'];
    $date = $_POST['date'];
    $shift = $_POST['shift'];
    $in_time = $_POST['in_time'];
    $out_time = $_POST['out_time'];

    // Chuẩn bị câu truy vấn để cập nhật lịch làm việc
    $updateScheduleQuery = "UPDATE member_schedules SET {$day}_shift = ? WHERE fullname = ? AND week_start_date = ?";
    $updateScheduleStmt = $conn->prepare($updateScheduleQuery);
    $updateScheduleStmt->bind_param("sss", $shift, $fullname, $start_of_week);
    
    // Kiểm tra và thực hiện cập nhật lịch làm việc
    if ($updateScheduleStmt->execute()) {
        // Xóa chấm công hiện có cho ngày cụ thể để tránh xung đột trước khi chèn dữ liệu mới
        $deleteAttendanceQuery = "DELETE FROM attendance_logs WHERE employee_name = ? AND DATE(timestamp) = ?";
        $deleteAttendanceStmt = $conn->prepare($deleteAttendanceQuery);
        $deleteAttendanceStmt->bind_param("ss", $fullname, $date);
        $deleteAttendanceStmt->execute();

        // Chèn giờ vào và giờ ra mới nếu có
        if (!empty($in_time)) {
            $inTimestamp = "{$date} {$in_time}";
            $insertInQuery = "INSERT INTO attendance_logs (employee_name, timestamp, attendance_type) VALUES (?, ?, 'in')";
            $insertInStmt = $conn->prepare($insertInQuery);
            $insertInStmt->bind_param("ss", $fullname, $inTimestamp);
            $insertInStmt->execute();
        }
        
        if (!empty($out_time)) {
            $outTimestamp = "{$date} {$out_time}";
            $insertOutQuery = "INSERT INTO attendance_logs (employee_name, timestamp, attendance_type) VALUES (?, ?, 'out')";
            $insertOutStmt = $conn->prepare($insertOutQuery);
            $insertOutStmt->bind_param("ss", $fullname, $outTimestamp);
            $insertOutStmt->execute();
        }

        // Chuyển hướng về trang chấm công với thông báo thành công
        $_SESSION['message'] = "Dữ liệu chấm công đã được cập nhật thành công!";
        header("Location: chamcong.php");
        exit();
    } else {
        // Hiển thị lỗi nếu cập nhật thất bại
        $_SESSION['error'] = "Lỗi khi cập nhật dữ liệu. Vui lòng thử lại!";
        header("Location: chamcong.php");
        exit();
    }
} else {
    // Chuyển hướng về trang chấm công nếu không có dữ liệu POST
    header("Location: chamcong.php");
    exit();
}
?>
