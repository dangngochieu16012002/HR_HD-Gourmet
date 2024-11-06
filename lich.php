<?php
include('includes/config.php');

// Kiểm tra người dùng đăng nhập
if (!isset($_SESSION['user_id'])) {
    header("Location: index.php");
    exit();
}

// Định nghĩa danh sách các ngày trong tuần
$days = ['monday', 'tuesday', 'wednesday', 'thursday', 'friday', 'saturday', 'sunday'];
// Lấy danh sách các thành viên (members) cùng với type
$members = $conn->query("SELECT m.id, m.fullname, mt.type 
                          FROM members m 
                          JOIN membership_types mt ON m.membership_type = mt.id");

// Lưu dữ liệu vào bảng member_schedules
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $fullname = $_POST['fullname'];
    $week_start_date = $_POST['week_start_date'];

    // Kiểm tra lịch đã tồn tại trong tuần chưa
    $existing_schedule = $conn->query("SELECT * FROM member_schedules WHERE fullname = '$fullname' AND week_start_date = '$week_start_date'");
    if ($existing_schedule->num_rows > 0) {
        echo "<script>alert('Nhân viên này đã có lịch làm việc cho tuần này.');</script>";
    } else {
        // Lấy thông tin chức vụ và số ngày phép còn lại
        $member_query = $conn->query("SELECT occupation, annual_leave_remaining FROM members WHERE fullname='$fullname'");
        $member_row = $member_query->fetch_assoc();
        $occupation = $member_row['occupation'];
        $annual_leave_remaining = $member_row['annual_leave_remaining'];

        // Đếm số lần OFF và AL được chọn trong tuần
        $shift_data = [];
        $off_count = 0;
        $al_count = 0;

        foreach ($days as $day) {
            $shift = $_POST[$day . '_shift'];
            $shift_data[$day . '_shift'] = $shift;
            if ($shift === 'OFF') $off_count++;
            if ($shift === 'AL') $al_count++;
        }

        // Kiểm tra số ngày OFF và AL
        if ($off_count > 1) {
            echo "<script>alert('Nhân viên chỉ có thể OFF một ngày trong tuần.');</script>";
        } elseif ($al_count > $annual_leave_remaining) {
            echo "<script>alert('Nhân viên không còn đủ ngày phép AL để chọn.');</script>";
        } else {
            // Cập nhật số ngày phép còn lại
            if ($al_count > 0) {
                $new_annual_leave_remaining = $annual_leave_remaining - $al_count;
                $conn->query("UPDATE members SET annual_leave_remaining = $new_annual_leave_remaining WHERE fullname = '$fullname'");
            }

            // Thêm lịch làm việc vào cơ sở dữ liệu
            $sql = "INSERT INTO member_schedules (fullname, occupation, week_start_date, monday_shift, tuesday_shift, wednesday_shift, thursday_shift, friday_shift, saturday_shift, sunday_shift)
                    VALUES ('$fullname', '$occupation', '$week_start_date', '{$shift_data['monday_shift']}', '{$shift_data['tuesday_shift']}', '{$shift_data['wednesday_shift']}', '{$shift_data['thursday_shift']}', '{$shift_data['friday_shift']}', '{$shift_data['saturday_shift']}', '{$shift_data['sunday_shift']}')";
            $conn->query($sql);
        }
    }
}

// Tính ngày đầu tuần hiện tại
$today = date('Y-m-d');
$start_of_week = date('Y-m-d', strtotime('monday this week', strtotime($today)));

// Lấy lịch chỉ cho tuần hiện tại cùng với type
$schedules = $conn->query("SELECT ms.*, mt.type 
                            FROM member_schedules ms 
                            JOIN members m ON ms.fullname = m.fullname 
                            JOIN membership_types mt ON m.membership_type = mt.id 
                            WHERE ms.week_start_date = '$start_of_week' 
                            ORDER BY mt.type, ms.week_start_date");

if (!$schedules) {
    die("Lỗi SQL: " . $conn->error);
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Tạo lịch làm việc cho nhân viên</title>
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
                            <h2>Tạo lịch làm việc</h2>
                            <form method="POST">
                                <label for="fullname">Chọn thành viên:</label>
                                <select name="fullname" required>
                                    <?php 
                                    while ($row = $members->fetch_assoc()) { ?>
                                        <option value="<?php echo $row['fullname']; ?>">
                                            <?php echo $row['fullname'] . " (" . $row['type'] . ")"; ?>
                                        </option>
                                    <?php } ?>
                                </select><br><br>

                                <label for="week_start_date">Ngày bắt đầu tuần:</label>
                                <input type="date" name="week_start_date" required><br><br>

                                <?php
                                $shifts = ['9:00-17:00', '7:00-15:00', '14:00-22:00', '10:00-18:00', 'OFF', 'CD', 'AL'];
                                foreach ($days as $day) {
                                    echo "<label>" . ucfirst($day) . ":</label>";
                                    echo "<select name='{$day}_shift'>";
                                    foreach ($shifts as $shift) {
                                        echo "<option value='$shift'>$shift</option>";
                                    }
                                    echo "</select><br><br>";
                                }
                                ?>
                                <input type="submit" value="Tạo lịch">
                            </form>

                            <h2>Lịch đã tạo</h2>
                            <?php if ($schedules->num_rows > 0) { ?>
                                <table class="schedule-table">
                                    <tr>
                                        <th>Thành viên</th>
                                        <th>Phòng Ban</th>
                                        <th>Chức vụ</th>
                                        <th>Ngày bắt đầu tuần</th>
                                        <?php foreach ($days as $day) { ?>
                                            <th><?php echo ucfirst($day); ?></th>
                                        <?php } ?>
                                    </tr>
                                    <?php while ($schedule = $schedules->fetch_assoc()) { ?>
                                        <tr>
                                            <td><?php echo htmlspecialchars($schedule['fullname']); ?></td>
                                            <td><?php echo htmlspecialchars($schedule['type']); ?></td>
                                            <td><?php echo htmlspecialchars($schedule['occupation']); ?></td>
                                            <td><?php echo htmlspecialchars($schedule['week_start_date']); ?></td>
                                            <?php foreach ($days as $day) {
                                                $shift = $schedule["{$day}_shift"];
                                                $class = '';
                                                if ($shift == 'OFF') $class = 'schedule-off';
                                                if ($shift == 'CD') $class = 'schedule-cd';
                                                if ($shift == 'AL') $class = 'schedule-al';
                                                if ($shift == 'UL') $class = 'schedule-ul';
                                                echo "<td class='$class'>" . htmlspecialchars($shift) . "</td>";
                                            } ?>
                                        </tr>
                                    <?php } ?>
                                </table>
                            <?php } else { ?>
                                <p>Không có dữ liệu lịch làm việc nào cho tuần hiện tại.</p>
                            <?php } ?>
                        </div>
                    </div>
                </div>
            </div>
        </section>
    </div>
    <aside class="control-sidebar control-sidebar-dark">
    <!-- Control sidebar content goes here -->
    </aside>
    <!-- /.control-sidebar -->

    <!-- Main Footer -->
    <footer class="main-footer">
        <strong>&copy; <?php echo date('Y'); ?> HD Gourmet</strong> - Bản Quyền Thuộc Về.
        <div class="float-right d-none d-sm-inline-block">
            <b>Phát Triển Bởi</b> <a href="#">HD Gourmet</a>
        </div>
    </footer>
</div>
<!-- ./wrapper -->

<?php include('includes/footer.php'); ?>
</body>
</html>

<style>
table.schedule-table {
    width: 100%;
    border-collapse: collapse;
    font-size: 14px;
}

table.schedule-table th, table.schedule-table td {
    padding: 10px;
    border: 1px solid #ddd;
    text-align: center;
}

table.schedule-table th {
    background-color: #4CAF50;
    color: white;
}

.schedule-off {
    background-color: yellow;
}

.schedule-cd {
    background-color: blue;
    color: white;
}

.schedule-al {
    background-color: orange;
}

.schedule-ul {
    background-color: red;
    color: white;
}
</style>
