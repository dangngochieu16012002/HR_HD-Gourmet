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
            if (!in_array($shift, ['OFF', 'AL', 'CD'])) { // Bỏ qua các ngày OFF, AL, CD
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
    <link href="https://cdn.jsdelivr.net/npm/tailwindcss@2.2.19/dist/tailwind.min.css" rel="stylesheet">
    <style>
        .error { color: red; }
        .error-cell { background-color: #ffe6e6; }
    </style>
    <script>
        function showEditModal(fullname, date, inTime, outTime, shift) {
            document.getElementById('editFullname').value = fullname;
            document.getElementById('editDate').value = date;
            document.getElementById('editInTime').value = inTime;
            document.getElementById('editOutTime').value = outTime;
            document.getElementById('editShift').value = shift;
            document.getElementById('editModal').classList.remove('hidden');
        }

        function closeEditModal() {
            document.getElementById('editModal').classList.add('hidden');
        }
    </script>
</head>
<?php include('includes/header.php'); ?>

<body class="hold-transition sidebar-mini layout-fixed layout-navbar-fixed layout-footer-fixed">
<div class="wrapper">
    <?php include('includes/nav.php'); ?>
    <?php include('includes/sidebar.php'); ?>

    <div class="content-wrapper">
        <?php include('includes/pagetitle.php'); ?>

        <section class="content">
            <div class="container mx-auto p-4 bg-white rounded-lg shadow-lg">
                <h2 class="text-2xl font-semibold mb-6">Lịch Chấm Công Nhân Viên</h2>
                <table class="table-auto w-full border-collapse border border-gray-200">
                    <thead>
                        <tr class="bg-blue-600 text-white">
                            <th class="px-4 py-2">Thành viên</th>
                            <th class="px-4 py-2">Chức vụ</th>
                            <th class="px-4 py-2">Phòng</th>
                            <?php foreach ($days as $day) { ?>
                                <th class="px-4 py-2"><?php echo ucfirst($day); ?></th>
                            <?php } ?>
                        </tr>
                    </thead>
                    <tbody>
                        <?php 
                        $schedules->data_seek(0);
                        while ($schedule = $schedules->fetch_assoc()) { ?>
                            <tr class="border-b">
                                <td class="px-4 py-2"><?php echo htmlspecialchars($schedule['fullname']); ?></td>
                                <td class="px-4 py-2"><?php echo htmlspecialchars($schedule['occupation']); ?></td>
                                <td class="px-4 py-2"><?php echo htmlspecialchars($schedule['type']); ?></td>
                                <?php foreach ($days as $day) {
                                    $date = date('Y-m-d', strtotime("{$day} this week"));
                                    $attendance_status = isset($attendance_data[$schedule['fullname']][$date]) ? $attendance_data[$schedule['fullname']][$date] : [];
                                    $in_time = '';
                                    $out_time = '';
                                    
                                    foreach ($attendance_status as $status) {
                                        if ($status['type'] == 'in' && empty($in_time)) {
                                            $in_time = $status['time'];
                                        } elseif ($status['type'] == 'out') {
                                            $out_time = $status['time'];
                                        }
                                    }

                                    $shift = $schedule["{$day}_shift"];
                                    $display = $shift;

                                    if (!in_array($shift, ['OFF', 'CD', 'AL'])) {
                                        if ($in_time || $out_time) {
                                            $display = "in: " . htmlspecialchars($in_time) . " - out: " . htmlspecialchars($out_time);
                                        } else {
                                            $display = "<span class='error'>chưa chấm công</span>";
                                        }
                                    }
                                    echo "<td class='px-4 py-2 " . (empty($in_time) && empty($out_time) && !in_array($shift, ['OFF', 'CD', 'AL']) ? "error-cell" : "") . "'>$display <button onclick='showEditModal(\"{$schedule['fullname']}\", \"$date\", \"$in_time\", \"$out_time\", \"$shift\")' class='text-blue-500 hover:text-blue-700'>&#9998;</button></td>";
                                } ?>
                            </tr>
                        <?php } ?>
                    </tbody>
                </table>

                <?php if (!empty($errors)) { ?>
                    <div class="mt-4 p-4 bg-red-100 text-red-800 rounded">
                        <h3 class="font-semibold mb-2">Các lỗi chấm công:</h3>
                        <ul class="list-disc ml-5">
                            <?php foreach ($errors as $error) { ?>
                                <li><?php echo htmlspecialchars($error); ?></li>
                            <?php } ?>
                        </ul>
                    </div>
                <?php } else { ?>
                    <div class="mt-4 p-4 bg-green-100 text-green-800 rounded">
                        <p>Không có lỗi chấm công nào.</p>
                    </div>
                <?php } ?>
            </div>
        </section>
    </div>

    <!-- Edit Modal -->
    <div id="editModal" class="fixed inset-0 flex items-center justify-center bg-black bg-opacity-50 hidden">
        <div class="bg-white p-6 rounded-lg shadow-lg w-full max-w-md relative">
            <button onclick="closeEditModal()" class="absolute top-2 right-2 text-gray-500 hover:text-gray-700">&times;</button>
            <h3 class="text-xl font-bold mb-4 text-center text-blue-600">Chỉnh Sửa Chấm Công</h3>
            <form action="update_attendance.php" method="post">
                <input type="hidden" id="editFullname" name="fullname">
                <input type="hidden" id="editDate" name="date">
                <div class="mb-4">
                    <label for="editShift" class="block text-gray-700 font-semibold">Ca Làm</label>
                    <input type="text" id="editShift" name="shift" class="w-full border rounded px-3 py-2 mt-1 focus:ring-2 focus:ring-blue-500">
                </div>
                <div class="mb-4">
                    <label for="editInTime" class="block text-gray-700 font-semibold">Giờ vào</label>
                    <input type="time" id="editInTime" name="in_time" class="w-full border rounded px-3 py-2 mt-1 focus:ring-2 focus:ring-blue-500">
                </div>
                <div class="mb-4">
                    <label for="editOutTime" class="block text-gray-700 font-semibold">Giờ ra</label>
                    <input type="time" id="editOutTime" name="out_time" class="w-full border rounded px-3 py-2 mt-1 focus:ring-2 focus:ring-blue-500">
                </div>
                <div class="text-right">
                    <button type="submit" class="bg-blue-600 text-white px-4 py-2 rounded shadow hover:bg-blue-700">Lưu Thay Đổi</button>
                </div>
            </form>
        </div>
    </div>

    <footer class="main-footer text-center mt-4 py-4">
        <strong>&copy; <?php echo date('Y'); ?> HD Gourmet</strong> - Bản Quyền Thuộc Về.
    </footer>
</div>

<script src="https://cdn.jsdelivr.net/npm/tailwindcss@2.2.19/dist/tailwind.min.js"></script>
</body>
</html>
