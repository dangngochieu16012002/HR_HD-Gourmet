<?php 
include('includes/config.php');

// Kiểm tra người dùng đã đăng nhập
if (!isset($_SESSION['user_id'])) {
    header("Location: index.php");
    exit();
}

// Định nghĩa danh sách các ngày trong tuần
$days = ['monday', 'tuesday', 'wednesday', 'thursday', 'friday', 'saturday', 'sunday'];

// Tính ngày đầu tuần hiện tại
$today = date('Y-m-d');
$start_of_week = date('Y-m-d', strtotime('monday this week', strtotime($today)));

// Lấy lịch làm việc cho tuần hiện tại
$schedules = $conn->query("SELECT ms.*, m.occupation, mt.type 
                            FROM member_schedules ms 
                            JOIN members m ON ms.fullname = m.fullname 
                            JOIN membership_types mt ON m.membership_type = mt.id
                            WHERE ms.week_start_date = '$start_of_week' 
                            ORDER BY ms.fullname");

// Lấy dữ liệu chấm công từ bảng attendance_logs
$attendance_logs = $conn->query("SELECT employee_name, DATE(timestamp) as attendance_date, 
                                  TIME(timestamp) as attendance_time, attendance_type 
                                  FROM attendance_logs 
                                  WHERE DATE(timestamp) BETWEEN '$start_of_week' AND DATE_ADD('$start_of_week', INTERVAL 6 DAY) 
                                  ORDER BY timestamp");

// Tạo một mảng để lưu trạng thái chấm công
$attendance_data = [];
while ($log = $attendance_logs->fetch_assoc()) {
    $attendance_data[$log['employee_name']][$log['attendance_date']][] = [
        'type' => $log['attendance_type'],
        'time' => $log['attendance_time']
    ];
}

// Tạo mảng để lưu thông báo lỗi
$errors = [];

// Kiểm tra các trường hợp không hợp lệ cho các ngày đã qua
while ($schedule = $schedules->fetch_assoc()) {
    $fullname = $schedule['fullname'];
    foreach ($days as $day) {
        $date = date('Y-m-d', strtotime("{$day} this week"));
        
        // Chỉ kiểm tra các ngày đã qua
        if ($date < $today) {
            $attendance_status = isset($attendance_data[$fullname][$date]) ? $attendance_data[$fullname][$date] : [];
            
            $in_time = '';
            $out_time = '';
            $shift = $schedule["{$day}_shift"];
            $shift_times = explode('-', $shift);

            // Xác định giờ bắt đầu và kết thúc ca làm việc
            $shift_start_time = !empty($shift_times[0]) ? date('H:i:s', strtotime($shift_times[0])) : null;
            $shift_end_time = !empty($shift_times[1]) ? date('H:i:s', strtotime($shift_times[1])) : null;

            // Lấy giờ vào và giờ ra
            foreach ($attendance_status as $status) {
                if ($status['type'] == 'in') {
                    if (empty($in_time)) {
                        $in_time = $status['time']; // Lấy giờ vào lần đầu tiên
                    }
                } elseif ($status['type'] == 'out') {
                    $out_time = $status['time']; // Cập nhật giờ ra lần cuối cùng
                }
            }

            // Kiểm tra các điều kiện không hợp lệ
            if (empty($in_time) && empty($out_time)) {
                $errors[] = "$fullname chưa chấm công vào và ra trong ngày $date.";
            } elseif (empty($in_time)) {
                $errors[] = "$fullname chưa chấm công vào trong ngày $date.";
            } elseif (empty($out_time)) {
                $errors[] = "$fullname chưa chấm công ra trong ngày $date.";
            } else {
                // Kiểm tra trễ vào ca
                if ($shift_start_time && $in_time > date('H:i:s', strtotime($shift_start_time) + 15 * 60)) {
                    $errors[] = "$fullname chấm công trễ hơn 15 phút so với giờ vào ca trong ngày $date.";
                }
                
                // Kiểm tra ra sớm hơn giờ ra
                if ($shift_end_time && $out_time < $shift_end_time) {
                    $errors[] = "$fullname về sớm hơn giờ ra ca trong ngày $date.";
                }

                // Kiểm tra giờ chấm công so với lịch làm việc
                if ($shift !== 'OFF' && $shift !== 'CD' && $shift !== 'AL') {
                    if ($in_time < $shift_start_time) {
                        $errors[] = "$fullname vào ca sớm hơn giờ quy định trong ngày $date.";
                    } elseif ($in_time > $shift_end_time) {
                        $errors[] = "$fullname vào ca muộn hơn giờ quy định trong ngày $date.";
                    }
                }
            }
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Lịch Chấm Công</title>
    <link rel="stylesheet" type="text/css" href="includes/style.css">
</head>
<?php include('includes/header.php'); ?>

<body class="hold-transition sidebar-mini layout-fixed layout-navbar-fixed layout-footer-fixed">
<div class="wrapper">
    <?php include('includes/nav.php'); ?>
    <?php include('includes/sidebar.php'); ?>

    <div class="content-wrapper">
        <?php include('includes/pagetitle.php'); ?>

        <section class="content">
            <div class="container-fluid">
                <div class="row">
                    <div class="col-md-12">
                        <div class="container">
                            <h2>Lịch Chấm Công Nhân Viên</h2>
                            <table class="attendance-table">
                                <tr>
                                    <th>Thành viên</th>
                                    <th>Chức vụ</th>
                                    <th>Phòng</th>
                                    <?php foreach ($days as $day) { ?>
                                        <th><?php echo ucfirst($day); ?></th>
                                    <?php } ?>
                                </tr>
                                <?php 
                                // Reset cursor of schedules
                                $schedules->data_seek(0);
                                
                                while ($schedule = $schedules->fetch_assoc()) { ?>
                                    <tr>
                                        <td><?php echo htmlspecialchars($schedule['fullname']); ?></td>
                                        <td><?php echo htmlspecialchars($schedule['occupation']); ?></td>
                                        <td><?php echo htmlspecialchars($schedule['type']); ?></td> <!-- Hiển thị cột Phòng -->
                                        <?php foreach ($days as $day) {
                                            $date = date('Y-m-d', strtotime("{$day} this week"));
                                            $attendance_status = isset($attendance_data[$schedule['fullname']][$date]) ? $attendance_data[$schedule['fullname']][$date] : [];

                                            $in_time = '';
                                            $out_time = '';

                                            // Lấy giờ vào và giờ ra
                                            foreach ($attendance_status as $status) {
                                                if ($status['type'] == 'in') {
                                                    if (empty($in_time)) {
                                                        $in_time = $status['time']; // Lấy giờ vào lần đầu tiên
                                                    }
                                                } elseif ($status['type'] == 'out') {
                                                    $out_time = $status['time']; // Cập nhật giờ ra lần cuối cùng
                                                }
                                            }

                                            // Lấy lịch làm cho ngày hiện tại
                                            $shift = $schedule["{$day}_shift"];
                                            $display = '';

                                            // Kiểm tra và hiển thị trạng thái
                                            if ($shift == 'OFF' || $shift == 'CD' || $shift == 'AL') {
                                                $display = htmlspecialchars($shift);
                                            } elseif ($in_time || $out_time) {
                                                $display = "in: " . htmlspecialchars($in_time) . " - out: " . htmlspecialchars($out_time);
                                            } else {
                                                $display = "<span class='error'>chưa chấm công</span>";
                                            }

                                            echo "<td class='" . ($shift == 'OFF' ? 'schedule-off' : ($shift == 'CD' ? 'schedule-cd' : ($shift == 'AL' ? 'schedule-al' : ''))) . "'>$display</td>";
                                        } ?>
                                    </tr>
                                <?php } ?>
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
                        </div>
                    </div>
                </div>
            </div>
        </section>
    </div>
    <footer class="main-footer">
        <strong>&copy; <?php echo date('Y'); ?> HD Gourmet</strong> - Bản Quyền Thuộc Về.
    </footer>
</div>

<style>
table.attendance-table {
    width: 100%;
    border-collapse: collapse;
    font-size: 14px;
}

table.attendance-table th, table.attendance-table td {
    padding: 10px;
    border: 1px solid #ddd;
    text-align: center;
}

table.attendance-table th {
    background-color: #4CAF50;
    color: white;
}

.error {
    color: red;
}

.error-list {
    margin-top: 20px;
    color: red;
}

.error-list ul {
    list-style-type: none;
    padding: 0;
}

.error-list li {
    margin: 5px 0;
}
</style>

<?php include('includes/footer.php'); ?>
</body>
</html>
