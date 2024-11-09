<?php
include('includes/config.php');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $request_id = $_POST['request_id'];
    $employee_id = $_POST['employee_id'];
    $leave_type = $_POST['leave_type'];
    $cd_hours = $_POST['cd_hours'];
    
    // Xử lý duyệt yêu cầu
    if (isset($_POST['approve'])) {
        // Lấy số ngày phép còn lại
        $query = "SELECT annual_leave_remaining, cd_hours_remaining FROM members WHERE id = ?";
        $stmt = $conn->prepare($query);
        $stmt->bind_param("i", $employee_id);
        $stmt->execute();
        $result = $stmt->get_result();
        $member = $result->fetch_assoc();

        $annual_leave = $member['annual_leave_remaining'];
        $cd_hours_remaining = $member['cd_hours_remaining'];
        
        // Kiểm tra loại nghỉ phép và trừ phép hoặc giờ
        if ($leave_type === 'AL_full') {
            $annual_leave--;
        } elseif ($leave_type === 'CD_hours') {
            $cd_hours_remaining -= $cd_hours;
        } elseif (strpos($leave_type, 'AL_half') !== false) {
            $annual_leave -= 0.5;
        }

        // Cập nhật số ngày phép và giờ còn lại
        $updateQuery = "UPDATE members SET annual_leave_remaining = ?, cd_hours_remaining = ? WHERE id = ?";
        $updateStmt = $conn->prepare($updateQuery);
        $updateStmt->bind_param("dii", $annual_leave, $cd_hours_remaining, $employee_id);
        $updateStmt->execute();

        // Cập nhật trạng thái yêu cầu thành "Đã Duyệt"
        $updateRequestQuery = "UPDATE leave_requests SET status = 'Approved' WHERE id = ?";
        $updateRequestStmt = $conn->prepare($updateRequestQuery);
        $updateRequestStmt->bind_param("i", $request_id);
        $updateRequestStmt->execute();

    } elseif (isset($_POST['reject'])) {
        // Từ chối yêu cầu
        $updateRequestQuery = "UPDATE leave_requests SET status = 'Rejected' WHERE id = ?";
        $updateRequestStmt = $conn->prepare($updateRequestQuery);
        $updateRequestStmt->bind_param("i", $request_id);
        $updateRequestStmt->execute();
    }

    header("Location: duyet_yc_nghi.php");
    exit();
}
?>
