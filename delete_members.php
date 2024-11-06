<?php
include('includes/config.php');

if ($_SERVER["REQUEST_METHOD"] == "GET" && isset($_GET['id'])) {
    $memberId = $_GET['id'];

    // 1. Lấy thông tin thành viên trước khi xóa
    $getMemberQuery = "SELECT membership_type, occupation, fullname FROM members WHERE id = $memberId";
    $memberResult = $conn->query($getMemberQuery);

    if ($memberResult->num_rows > 0) {
        $member = $memberResult->fetch_assoc();
        $membershipType = $member['membership_type'];
        $occupation = $member['occupation'];
        $fullname = $member['fullname'];

        // 2. Xóa tất cả bản ghi liên quan trong bảng renew (nếu có)
        $deleteRenewQuery = "DELETE FROM renew WHERE member_id = $memberId";
        if ($conn->query($deleteRenewQuery) === FALSE) {
            echo "Lỗi khi xóa bản ghi liên quan từ bảng renew: " . $conn->error;
            exit();
        }

        // 3. Xóa thành viên khỏi bảng members
        $deleteMemberQuery = "DELETE FROM members WHERE id = $memberId";
        if ($conn->query($deleteMemberQuery) === TRUE) {
            
            // 4. Giảm số lượng nhân viên trong bảng membership_types
            $updateCountQuery = "UPDATE membership_types 
                                 SET employee_count = employee_count - 1 
                                 WHERE id = $membershipType";
            $conn->query($updateCountQuery);

            // 5. Nếu thành viên bị xóa là Supervisor, làm trống cột department_head
            if ($occupation === 'Supervisor') {
                $clearHeadQuery = "UPDATE membership_types 
                                   SET department_head = NULL 
                                   WHERE id = $membershipType";
                $conn->query($clearHeadQuery);
            }

            // 6. Chuyển hướng về trang quản lý thành viên sau khi xóa thành công
            header("Location: manage_members.php");
            exit();
        } else {
            echo "Lỗi khi xóa thành viên: " . $conn->error;
        }
    } else {
        echo "Không tìm thấy thành viên.";
    }
} else {
    // Chuyển hướng nếu truy cập sai cách
    header("Location: manage_members.php");
    exit();
}

$conn->close();
?>
