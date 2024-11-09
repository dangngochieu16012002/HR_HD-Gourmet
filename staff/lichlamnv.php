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
    $occupationQuery = "SELECT occupation, fullname, annual_leave_remaining, cd_hours_remaining FROM members WHERE id = ?";
    $occupationStmt = $conn->prepare($occupationQuery);
    if ($occupationStmt) {
        $occupationStmt->bind_param("i", $staff_id);
        $occupationStmt->execute();
        $occupationResult = $occupationStmt->get_result();
        $occupationData = $occupationResult->fetch_assoc();
        $occupation = $occupationData['occupation'];
        $fullname = $occupationData['fullname'];
        $annualLeaveRemaining = $occupationData['annual_leave_remaining'];
        $cdHoursRemaining = $occupationData['cd_hours_remaining'];

        // Lấy lịch làm việc và chấm công
        $scheduleQuery = "SELECT * FROM member_schedules WHERE fullname = ? AND week_start_date = ?";
        $scheduleStmt = $conn->prepare($scheduleQuery);
        $scheduleStmt->bind_param("ss", $fullname, $weekStart);
        $scheduleStmt->execute();
        $scheduleResult = $scheduleStmt->get_result();

        $attendanceQuery = "SELECT DATE(timestamp) as attendance_date, TIME(timestamp) as attendance_time, attendance_type FROM attendance_logs WHERE employee_name = ? AND DATE(timestamp) BETWEEN ? AND DATE_ADD(?, INTERVAL 6 DAY)";
        $attendanceStmt = $conn->prepare($attendanceQuery);
        $attendanceStmt->bind_param("sss", $fullname, $weekStart, $weekStart);
        $attendanceStmt->execute();
        $attendanceResult = $attendanceStmt->get_result();

        $attendanceData = [];
        while ($log = $attendanceResult->fetch_assoc()) {
            $attendanceData[$log['attendance_date']][] = [
                'type' => $log['attendance_type'],
                'time' => $log['attendance_time']
            ];
        }

        $errors = [];
    } else {
        die("Error preparing occupation statement: " . $conn->error);
    }
} else {
    die("Connection to database failed: " . $conn->connect_error);
}
?>

<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Trạng Thái Chấm Công Cá Nhân</title>
    <link rel="stylesheet" type="text/css" href="../includes/style.css">
    <script>
        function showRequestForm(date) {
            // Hiển thị form tạo phép và cập nhật ngày chọn
            document.getElementById('requestDate').innerText = date;
            document.getElementById('popupForm').style.display = 'block';
        }

        function closeRequestForm() {
            // Đóng form tạo phép
            document.getElementById('popupForm').style.display = 'none';
        }
    </script>
</head>
<?php include('includes_staff/header_staff.php'); ?>

<body class="hold-transition sidebar-mini layout-fixed layout-navbar-fixed layout-footer-fixed">
<div class="wrapper">
    <?php include('includes_staff/nav_staff.php'); ?>
    <?php include('includes_staff/sidebar_staff.php'); ?>

    <div class="content-wrapper">
        <?php include('includes_staff/pagetitle_staff.php'); ?>

        <section class="content">
            <div class="container-fluid">
                <h2 class="schedule-title">Lịch Chấm Công - Tuần từ <?php echo date('d/m/Y', strtotime($weekStart)); ?> đến <?php echo date('d/m/Y', strtotime($weekEnd)); ?></h2>
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
                            <?php
                            $days = ['monday', 'tuesday', 'wednesday', 'thursday', 'friday', 'saturday', 'sunday'];
                            $schedule = $scheduleResult->fetch_assoc();
                            foreach ($days as $day) {
                                $date = date('Y-m-d', strtotime("{$day} this week", strtotime($weekStart)));
                                $dayLabel = ucfirst($day);
                                $shift = $schedule["{$day}_shift"];
                                $attendanceStatus = isset($attendanceData[$date]) ? $attendanceData[$date] : [];
                                $in_time = '';
                                $out_time = '';
                                $errorClass = '';

                                // Bỏ qua kiểm tra chấm công cho các ngày OFF, AL, CD
                                if ($shift === 'OFF' || $shift === 'AL' || $shift === 'CD') {
                                    $attendance_display = $shift;
                                } else {
                                    // Thực hiện kiểm tra chấm công cho các ngày làm việc
                                    foreach ($attendanceStatus as $status) {
                                        if ($status['type'] == 'in') {
                                            $in_time = $status['time'];
                                        } elseif ($status['type'] == 'out') {
                                            $out_time = $status['time'];
                                        }
                                    }

                                    $shift_start_time = !empty(explode('-', $shift)[0]) ? date('H:i:s', strtotime(explode('-', $shift)[0])) : null;
                                    $shift_end_time = !empty(explode('-', $shift)[1]) ? date('H:i:s', strtotime(explode('-', $shift)[1])) : null;

                                    if (empty($in_time) && empty($out_time)) {
                                        $errors[] = "Chưa chấm công vào và ra cho ngày $date.";
                                        $errorClass = 'error-cell';
                                    } elseif (empty($in_time)) {
                                        $errors[] = "Chưa chấm công vào cho ngày $date.";
                                        $errorClass = 'error-cell';
                                    } elseif (empty($out_time)) {
                                        $errors[] = "Chưa chấm công ra cho ngày $date.";
                                        $errorClass = 'error-cell';
                                    } elseif ($shift_start_time && $in_time > date('H:i:s', strtotime($shift_start_time) + 15 * 60)) {
                                        $errors[] = "Chấm công vào trễ hơn 15 phút cho ngày $date.";
                                        $errorClass = 'error-cell';
                                    } elseif ($shift_end_time && $out_time < $shift_end_time) {
                                        $errors[] = "Chấm công ra sớm hơn giờ quy định cho ngày $date.";
                                        $errorClass = 'error-cell';
                                    }

                                    $attendance_display = ($in_time || $out_time) ? "in: $in_time - out: $out_time" : "<span class='error'>Chưa chấm công</span>";
                                }
                                
                                echo "<tr>
                                    <td>$dayLabel</td>
                                    <td>$date</td>
                                    <td>$shift</td>
                                    <td class='$errorClass'>$attendance_display <span class='edit-icon' onclick='showRequestForm(\"$date\")'>&#9998;</span></td>
                                </tr>";
                            }
                            ?>
                            <tr>
                                <td colspan="4" style="text-align: right; font-weight: bold; padding: 15px; background-color: #f1f1f1;">
                                    Số ngày nghỉ còn lại trong năm: <?php echo $annualLeaveRemaining; ?> ngày | Giờ CD còn lại: <?php echo $cdHoursRemaining; ?> giờ
                                </td>
                            </tr>
                        </tbody>
                    </table>

                    <?php if (!empty($errors)) { ?>
                        <div class="error-list">
                            <h3>Các lỗi chấm công:</h3>
                            <ul>
                                <?php foreach ($errors as $error) { ?>
                                    <li><?php echo htmlspecialchars($error); ?></li>
                                <?php } ?>
                            </ul>
                        </div>
                    <?php } else { ?>
                        <div class="success-message">
                            <p>Không có lỗi chấm công nào.</p>
                        </div>
                    <?php } ?>
                <?php else: ?>
                    <p>Không tìm thấy lịch làm việc cho tuần này.</p>
                <?php endif; ?>
            </div>
        </section>
    </div>

    <footer class="main-footer">
        <strong>&copy; <?php echo date('Y'); ?> HD Gourmet</strong> - Bản Quyền Thuộc Về.
    </footer>
</div>

<!-- Popup form for request -->
<link href="https://cdn.jsdelivr.net/npm/tailwindcss@2.2.19/dist/tailwind.min.css" rel="stylesheet">

<div id="popupForm" class="fixed inset-0 flex items-center justify-center bg-black bg-opacity-50 hidden">
    <div class="bg-white p-8 rounded-lg shadow-lg w-96 relative">
        <button class="absolute top-2 right-2 text-gray-600 hover:text-gray-800" onclick="closeRequestForm()">
            &times;
        </button>
        <h2 class="text-2xl font-bold text-center text-blue-600 mb-4">Tạo Phép</h2>
        <p class="text-sm font-semibold text-gray-700 mb-2">Ngày yêu cầu: <span id="requestDate" class="font-normal"></span></p>
        <form method="post" action="submit_request.php" enctype="multipart/form-data" class="space-y-4">
            <input type="hidden" id="request_date" name="request_date"> <!-- Trường ẩn để lưu ngày yêu cầu -->
            
            <div>
                <label for="leaveType" class="block text-gray-700 font-semibold mb-1">Chọn loại phép:</label>
                <select id="leaveType" name="leave_type" onchange="toggleLeaveOptions()" class="w-full p-2 border border-gray-300 rounded focus:outline-none focus:ring-2 focus:ring-blue-500">
                    <option value="">-- Chọn loại phép --</option>
                    <option value="AL_half_morning">Nửa ca trước (AL)</option>
                    <option value="AL_half_afternoon">Nửa ca sau (AL)</option>
                    <option value="AL_full">Cả ngày (AL)</option>
                    <option value="UL_half_morning">Nửa ca trước (UL)</option>
                    <option value="UL_half_afternoon">Nửa ca sau (UL)</option>
                    <option value="UL_full">Cả ngày (UL)</option>
                    <option value="CD_hours">Nghỉ CD theo giờ</option>
                    <option value="UL_hours">Nghỉ UL theo giờ</option>
                </select>
            </div>
            
            <div id="cdHoursOption" class="hidden">
                <label for="cdHours" class="block text-gray-700 font-semibold mb-1">Số giờ nghỉ:</label>
                <input type="number" id="cdHours" name="cd_hours" min="1" max="8" step="1" value="4" class="w-full p-2 border border-gray-300 rounded focus:outline-none focus:ring-2 focus:ring-blue-500" placeholder="Nhập số giờ nghỉ">
                <p class="text-sm text-gray-500">Thời gian nghỉ tối đa: 8 giờ (1 ca) hoặc 4 giờ (nửa ca).</p>
            </div>
            
            <div>
                <label for="reason" class="block text-gray-700 font-semibold mb-1">Giải trình:</label>
                <textarea id="reason" name="reason" required class="w-full p-2 border border-gray-300 rounded focus:outline-none focus:ring-2 focus:ring-blue-500"></textarea>
            </div>

            <!-- Thêm trường tải lên hình ảnh bằng chứng -->
            <div>
                <label for="proofImage" class="block text-gray-700 font-semibold mb-1">Tải lên bằng chứng (nếu có):</label>
                <input type="file" id="proofImage" name="proof_image" accept="image/*" class="w-full p-2 border border-gray-300 rounded focus:outline-none focus:ring-2 focus:ring-blue-500">
            </div>
            
            <p class="text-sm font-semibold text-gray-700">Số ngày AL còn lại: <span class="font-normal"><?php echo $annualLeaveRemaining; ?> ngày</span></p>
            <p class="text-sm font-semibold text-gray-700">Số giờ CD (OT) còn lại: <span class="font-normal"><?php echo $cdHoursRemaining; ?> giờ</span></p>
            
            <button type="submit" name="submit_request" class="w-full py-2 px-4 bg-blue-500 text-white font-semibold rounded hover:bg-blue-600 focus:outline-none focus:ring-2 focus:ring-blue-500">
                Gửi yêu cầu
            </button>
        </form>
    </div>
</div>


<script>
    function showRequestForm(date) {
        document.getElementById('requestDate').innerText = date;
        document.getElementById('request_date').value = date; // Gán ngày yêu cầu vào trường hidden
        document.getElementById('popupForm').classList.remove('hidden');

        // Gọi API hoặc lấy lịch làm việc của ngày đó từ cơ sở dữ liệu (đoạn này chỉ mô phỏng)
        loadWorkSchedule(date);

        // Kiểm tra số ngày AL và giờ CD còn lại để vô hiệu hóa tùy chọn
        checkLeaveOptions();
    }

    function closeRequestForm() {
        document.getElementById('popupForm').classList.add('hidden');
    }

    function toggleLeaveOptions() {
        var leaveType = document.getElementById('leaveType').value;
        var cdHoursOption = document.getElementById('cdHoursOption');
        
        // Hiển thị phần nhập số giờ nếu chọn "CD_hours" hoặc "UL_hours"
        if (leaveType === 'CD_hours' || leaveType === 'UL_hours') {
            cdHoursOption.classList.remove('hidden');
        } else {
            cdHoursOption.classList.add('hidden');
        }
    }

    function loadWorkSchedule(date) {
        // Mô phỏng việc tải lịch làm việc từ cơ sở dữ liệu
        var workSchedule = "Ca làm việc từ 9:00 đến 17:00"; // Thay thế với dữ liệu thực tế từ cơ sở dữ liệu
        document.getElementById('workSchedule').innerText = workSchedule;
    }

    function checkLeaveOptions() {
        // Lấy số ngày AL và giờ CD còn lại
        var remainingAL = parseFloat(document.getElementById('remainingAL').innerText);
        var remainingCD = parseFloat(document.getElementById('remainingCD').innerText);

        // Vô hiệu hóa các tùy chọn nếu không đủ AL hoặc CD
        document.getElementById('AL_half_morning_option').disabled = remainingAL < 0.5;
        document.getElementById('AL_half_afternoon_option').disabled = remainingAL < 0.5;
        document.getElementById('AL_full_option').disabled = remainingAL < 1;
        document.getElementById('CD_hours_option').disabled = remainingCD < 1;
    }
</script>





<!-- CSS cho lịch làm việc và biểu tượng chỉnh sửa -->
<style>
    /* General Reset */
    * {
        margin: 0;
        padding: 0;
        box-sizing: border-box;
    }

    /* Body Styling */
    body {
        font-family: 'Arial', sans-serif;
        background-color: #f5f5f5;
        color: #333;
        line-height: 1.6;
    }

    .schedule-title {
        text-align: center;
        margin-bottom: 20px;
        color: #1f3c88;
        font-weight: bold;
        font-size: 24px;
        padding: 15px;
        background-color: #ebf5ff;
        border-radius: 10px;
        box-shadow: 0px 4px 10px rgba(0, 0, 0, 0.1);
        transition: transform 0.3s;
    }

    /* Hover effect for title */
    .schedule-title:hover {
        transform: scale(1.05);
    }

    /* Table Styling */
    .schedule-table {
        width: 100%;
        border-collapse: collapse;
        margin: 20px 0;
        font-size: 16px;
        text-align: left;
        box-shadow: 0px 4px 20px rgba(0, 0, 0, 0.05);
        border-radius: 10px;
        overflow: hidden;
    }

    /* Header Styling */
    .schedule-table thead {
        background-color: #007bff;
    }

    .schedule-table th {
        color: #fff;
        font-weight: 700;
        padding: 12px 15px;
        text-transform: uppercase;
        font-size: 14px;
    }

    .schedule-table th, .schedule-table td {
        border: 1px solid #ddd;
        padding: 16px;
        text-align: center;
        transition: background-color 0.3s;
    }

    /* Row Hover Effect */
    .schedule-table tr:hover {
        background-color: #d3e4ff;
    }

    /* Alternating Row Colors */
    .schedule-table tr:nth-child(even) {
        background-color: #f9f9f9;
    }

    .schedule-table td {
        font-size: 14px;
        color: #333;
    }

    /* Special Error Cell Styling - Only for Invalid Attendance */
    .schedule-table td.error-cell {
        background-color: #ffe6e6; /* Light red background only for invalid cells */
        color: #d9534f;
        font-weight: bold;
        border-left: 4px solid #d9534f;
    }

    /* Edit Icon Styling */
    .edit-icon {
        cursor: pointer;
        color: #007bff;
        font-size: 16px;
        margin-left: 8px;
        transition: color 0.2s;
    }

    /* Hover Effect for Edit Icon */
    .edit-icon:hover {
        color: #0056b3;
    }

    /* Error List Styling */
    .error-list {
        color: #d9534f;
        font-weight: bold;
        margin-top: 20px;
        font-size: 15px;
    }

    .error-list ul {
        list-style-type: none;
        padding: 0;
        margin-top: 10px;
    }

    .error-list li {
        margin: 5px 0;
        padding: 10px;
        background-color: #ffe6e6;
        border-radius: 5px;
        border-left: 4px solid #d9534f;
    }

    /* Container Styling */
    .container-fluid {
        padding: 20px;
        background-color: #ffffff;
        border-radius: 10px;
        box-shadow: 0px 4px 20px rgba(0, 0, 0, 0.1);
    }

    /* Responsive Design for Mobile */
    @media (max-width: 768px) {
        .schedule-table, .schedule-table thead, .schedule-table tbody, .schedule-table th, .schedule-table td, .schedule-table tr {
            display: block;
        }

        .schedule-table tr {
            margin-bottom: 15px;
            box-shadow: 0px 4px 10px rgba(0, 0, 0, 0.1);
            border-radius: 8px;
            padding: 10px;
        }

        .schedule-table td {
            font-size: 14px;
            text-align: left;
            padding: 10px 15px;
            position: relative;
        }

        .schedule-table td::before {
            content: attr(data-label);
            font-weight: bold;
            text-transform: uppercase;
            margin-right: 10px;
            display: inline-block;
            color: #007bff;
        }
    }
</style>


