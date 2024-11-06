<?php
include('../includes/config.php');

// Kiểm tra trạng thái phiên
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

// Kiểm tra xem người dùng có đăng nhập không
if (!isset($_SESSION['user_id'])) {
    header("Location: ../index.php");
    exit();
}

// Lấy thông tin về nhân viên đăng nhập
$staff_id = $_SESSION['user_id'];

// Tính ngày bắt đầu và kết thúc của tuần hiện tại
$weekStart = date('Y-m-d', strtotime('monday this week'));
$weekEnd = date('Y-m-d', strtotime('sunday this week'));

// Kiểm tra kết nối cơ sở dữ liệu
if ($conn) {
    // Truy vấn để kiểm tra occupation của nhân viên và số ngày nghỉ còn lại
    $occupationQuery = "SELECT occupation, fullname, annual_leave_remaining FROM members WHERE id = ?";
    $occupationStmt = $conn->prepare($occupationQuery);
    if ($occupationStmt) {
        $occupationStmt->bind_param("i", $staff_id);
        $occupationStmt->execute();
        $occupationResult = $occupationStmt->get_result();
        $occupationData = $occupationResult->fetch_assoc();
        $occupation = $occupationData['occupation'];
        $fullname = $occupationData['fullname'];
        $annualLeaveRemaining = $occupationData['annual_leave_remaining'];

        $isSupervisor = ($occupation === 'Supervisor');

        // Lấy lịch làm việc từ bảng `member_schedules` cho tuần hiện tại
        $scheduleQuery = $isSupervisor ?
            "SELECT * FROM member_schedules WHERE fullname IN (SELECT fullname FROM members WHERE membership_type = (SELECT membership_type FROM members WHERE id = ?)) AND week_start_date BETWEEN ? AND ?" :
            "SELECT * FROM member_schedules WHERE fullname = (SELECT fullname FROM members WHERE id = ?) AND week_start_date BETWEEN ? AND ?";
        
        $scheduleStmt = $conn->prepare($scheduleQuery);
        if ($scheduleStmt) {
            $scheduleStmt->bind_param("iss", $staff_id, $weekStart, $weekEnd);
            $scheduleStmt->execute();
            $scheduleResult = $scheduleStmt->get_result();
        } else {
            die("Error preparing schedule statement: " . $conn->error);
        }

        // Lấy trạng thái chấm công từ bảng `attendance_logs`
        $attendanceQuery = "SELECT * FROM attendance_logs WHERE employee_name = ? AND week_start_date BETWEEN ? AND ?";
        $attendanceStmt = $conn->prepare($attendanceQuery);
        if ($attendanceStmt) {
            $attendanceStmt->bind_param("sss", $fullname, $weekStart, $weekEnd);
            $attendanceStmt->execute();
            $attendanceResult = $attendanceStmt->get_result();

            $attendanceLogs = [];
            while ($attendance = $attendanceResult->fetch_assoc()) {
                $date = $attendance['week_start_date'];
                $attendanceLogs[$date][] = $attendance['attendance_type'];
            }
        } else {
            die("Error preparing attendance statement: " . $conn->error);
        }
    } else {
        die("Error preparing occupation statement: " . $conn->error);
    }
} else {
    die("Connection to database failed: " . $conn->connect_error);
}

include('includes_staff/header_staff.php');
?>

<!-- HTML content for staff dashboard -->
<body class="hold-transition sidebar-mini layout-fixed layout-navbar-fixed layout-footer-fixed">
<div class="wrapper">
  <?php include('includes_staff/nav_staff.php'); ?>
  <?php include('includes_staff/sidebar_staff.php'); ?>

  <!-- Content Wrapper -->
  <div class="content-wrapper">
    <?php include('includes_staff/pagetitle_staff.php'); ?>

    <!-- Main content -->
    <section class="content">
    <div class="container-fluid">
        <h2 class="schedule-title">Lịch Làm Việc - Tuần từ <?php echo date('d/m/Y', strtotime($weekStart)); ?> đến <?php echo date('d/m/Y', strtotime($weekEnd)); ?></h2>
        <p><strong>Tên nhân viên: </strong><?php echo $fullname; ?></p>
        <?php if ($scheduleResult->num_rows > 0): ?>
            <table class="schedule-table">
                <thead>
                    <tr>
                        <th>Thứ</th>
                        <th>Ngày</th>
                        <th>Ca Làm</th>
                        <th>Trạng Thái Chấm Công</th>
                    </tr>
                </thead>
                <tbody>
                    <?php while ($schedule = $scheduleResult->fetch_assoc()): ?>
                        <?php for ($day = 0; $day < 7; $day++): // Lặp từ thứ 2 đến Chủ nhật ?>
                            <?php
                            $currentDate = date('Y-m-d', strtotime("monday this week +{$day} days"));
                            $formattedDate = date('d/m/Y', strtotime($currentDate));
                            $dayName = strtolower(date('l', strtotime($currentDate)));
                            $shiftKey = $dayName . '_shift';
                            $shiftValue = isset($schedule[$shiftKey]) ? $schedule[$shiftKey] : 'N/A';

                            // Xác định màu cho ô ca làm
                            $shiftColor = '';
                            if ($shiftValue === 'OFF') {
                                $shiftColor = 'background-color: yellow;';
                            } elseif ($shiftValue === 'AL') {
                                $shiftColor = 'background-color: orange;';
                            }

                            // Xác định màu cho trạng thái chấm công
                            $attendanceStatus = isset($attendanceLogs[$currentDate]) ? implode(", ", $attendanceLogs[$currentDate]) : 'Chưa chấm công';
                            $attendanceColor = ($attendanceStatus === 'Chưa chấm công') ? 'color: red;' : 'color: green;';

                            // Xác định tên thứ
                            $dayOfWeek = date('l', strtotime($currentDate));
                            $dayOfWeekVietnamese = [
                                'Monday' => 'Thứ Hai',
                                'Tuesday' => 'Thứ Ba',
                                'Wednesday' => 'Thứ Tư',
                                'Thursday' => 'Thứ Năm',
                                'Friday' => 'Thứ Sáu',
                                'Saturday' => 'Thứ Bảy',
                                'Sunday' => 'Chủ Nhật'
                            ];
                            $dayLabel = $dayOfWeekVietnamese[$dayOfWeek];
                            ?>
                            <tr>
                                <td><?php echo $dayLabel; ?></td>
                                <td><?php echo $formattedDate; ?></td>
                                <td style="<?php echo $shiftColor; ?>"><?php echo $shiftValue; ?></td>
                                <td style="<?php echo $attendanceColor; ?>"><?php echo $attendanceStatus; ?></td>
                            </tr>
                        <?php endfor; ?>
                    <?php endwhile; ?>
                    <!-- Thêm dòng hiển thị số ngày nghỉ còn lại -->
                    <tr>
                        <td colspan="4" style="text-align: right; font-weight: bold; padding: 15px; background-color: #f1f1f1;">
                            Số ngày nghỉ còn lại trong năm: <?php echo $annualLeaveRemaining; ?> ngày
                        </td>
                    </tr>
                </tbody>
            </table>
        <?php else: ?>
            <p>Không tìm thấy lịch làm việc cho tuần này.</p>
        <?php endif; ?>
    </div>
    </section>
  </div>
  <footer class="main-footer">
    <strong> &copy; <?php echo date('Y'); ?> HD Gourmet</strong> - Bản Quyền Thuộc Về.
  </footer>
</div>
<?php include('includes_staff/footer_staff.php'); ?>
</body>
</html>

<!-- CSS dành riêng cho giao diện lịch làm việc -->
<style>
    .schedule-title {
        text-align: center;
        margin-bottom: 20px;
        color: #333;
        font-weight: bold;
        font-size: 24px;
        padding: 10px;
        background-color: #f4f6f9;
        border-radius: 8px;
    }

    .schedule-table {
        width: 100%;
        border-collapse: collapse;
        margin: 20px 0;
        font-size: 16px;
        text-align: left;
        box-shadow: 0 2px 15px rgba(0, 0, 0, 0.1);
        border-radius: 8px;
        overflow: hidden;
    }

    .schedule-table thead {
        background-color: #e7f1ff; /* Màu nền xanh nhạt */
    }

    .schedule-table th {
        color: #007bff; /* Màu chữ xanh dương */
        font-weight: bold;
        padding: 12px 15px;
        text-transform: uppercase;
        border-bottom: 2px solid #0056b3; /* Đường viền màu xanh đậm */
    }

    .schedule-table th, .schedule-table td {
        border: 1px solid #ddd;
        padding: 12px 15px;
        text-align: center;
    }

    .schedule-table tr {
        background-color: #f9f9f9;
        transition: background-color 0.3s;
    }

    .schedule-table tr:nth-child(even) {
        background-color: #f1f1f1;
    }

    .schedule-table tr:hover {
        background-color: #e7f1ff; /* Màu xanh nhạt khi hover */
    }

    .schedule-table td {
        font-size: 14px;
        color: #333;
    }

    .schedule-table td:first-child {
        font-weight: bold;
        color: #007bff; /* Màu xanh dương cho cột đầu tiên */
    }

    .main-footer {
        text-align: center;
        padding: 10px 20px;
        background-color: #f4f6f9;
        border-top: 1px solid #ddd;
    }

    .container-fluid {
        padding: 20px;
        background-color: #ffffff;
        border-radius: 8px;
        box-shadow: 0 4px 20px rgba(0, 0, 0, 0.1);
    }
</style>
