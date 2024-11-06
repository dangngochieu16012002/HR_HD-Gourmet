<?php 
include('includes/config.php');

// Kiểm tra người dùng đã đăng nhập
if (!isset($_SESSION['user_id'])) {
    header("Location: index.php");
    exit();
}

// Lấy dữ liệu nhân viên và phòng ban
$salary_data = [];
$salary_results = $conn->query("SELECT m.fullname, m.contact_number, m.email, m.occupation, mt.type AS membership_type, m.amount AS basic_salary 
                                 FROM members m 
                                 JOIN membership_types mt ON m.membership_type = mt.id
                                 ORDER BY mt.type");

while ($row = $salary_results->fetch_assoc()) {
    $fullname = $row['fullname'];
    $contact_number = $row['contact_number'];
    $email = $row['email'];
    $occupation = $row['occupation'];
    $membership_type = $row['membership_type'];
    $basic_salary = $row['basic_salary'];

    // Tính số ngày công hợp lệ
    $today = date('Y-m-d');
    $one_month_ago = date('Y-m-d', strtotime('-1 month'));
    $attendance_count_result = $conn->query("
        SELECT DATE(timestamp) AS date, MIN(timestamp) AS in_time, MAX(timestamp) AS out_time
        FROM attendance_logs 
        WHERE employee_name = '$fullname' 
        AND DATE(timestamp) BETWEEN '$one_month_ago' AND '$today'
        GROUP BY DATE(timestamp)
    ");

    $valid_days = 0;

    while ($attendance_row = $attendance_count_result->fetch_assoc()) {
        $in_time = strtotime($attendance_row['in_time']);
        $out_time = strtotime($attendance_row['out_time']);
        $shift_in_time = strtotime(date('Y-m-d', strtotime($attendance_row['date'])) . ' 08:00:00');
        $shift_out_time = strtotime(date('Y-m-d', strtotime($attendance_row['date'])) . ' 17:00:00');

        // Kiểm tra các điều kiện chấm công hợp lệ
        if ($in_time <= $shift_in_time + 15 * 60 && $out_time >= $shift_out_time) {
            $valid_days++;
        }
    }

    // Lương thực nhận (6240/26 * số ngày công hợp lệ + phụ cấp + thưởng KPI)
    $working_days = $valid_days > 26 ? 26 : $valid_days;
    $daily_salary = 6240 / 26 * $working_days;

    // Các phụ cấp
    $meal_allowance = 730000;
    $clothing_allowance = 410000;
    
    // Nhập thưởng KPI từ input
    $kpi_bonus = 0; // Mặc định bằng 0

    // Tính tổng lương
    $total_salary = $daily_salary + $meal_allowance + $clothing_allowance + $kpi_bonus;

    // Cộng thêm mức lương giám sát nếu chức vụ là Supervisor
    if ($occupation === 'Supervisor') {
        $basic_salary += 2380000; // Thêm lương giám sát
    }

    $salary_data[$membership_type][] = [
        'fullname' => $fullname,
        'contact_number' => $contact_number,
        'email' => $email,
        'occupation' => $occupation,
        'membership_type' => $membership_type,
        'basic_salary' => $basic_salary,
        'working_days' => $working_days,
        'meal_allowance' => $meal_allowance,
        'clothing_allowance' => $clothing_allowance,
        'kpi_bonus' => $kpi_bonus,
        'total_salary' => $total_salary
    ];
}

// Xử lý lưu dữ liệu vào cơ sở dữ liệu khi nhấn nút Lưu
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    foreach ($salary_data as $department => $members) {
        foreach ($members as $data) {
            // Lấy thông tin thưởng KPI từ input
            $kpi_bonus_input = $_POST['kpi_bonus'][$data['fullname']] ?? 0; // Mặc định là 0 nếu không có input

            // Cập nhật lại tổng lương với thưởng KPI
            $total_salary = $daily_salary + $meal_allowance + $clothing_allowance + $kpi_bonus_input;

            $stmt = $conn->prepare("INSERT INTO salaries (fullname, contact_number, email, occupation, membership_type, basic_salary, working_days, meal_allowance, clothing_allowance, kpi_bonus, total_salary) 
                                     VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
            $stmt->bind_param("sssssiddddd", 
                $data['fullname'], 
                $data['contact_number'], 
                $data['email'], 
                $data['occupation'], 
                $data['membership_type'], 
                $data['basic_salary'], 
                $data['working_days'], 
                $data['meal_allowance'], 
                $data['clothing_allowance'], 
                $kpi_bonus_input, // Thưởng KPI từ input
                $total_salary
            );
            $stmt->execute();
        }
    }
    header("Location: salary_report.php"); // Chuyển hướng đến báo cáo lương
    exit();
}
?>

<?php include('includes/header.php');?>
<style>
    @media print {
        form {
            display: none;
        }

        .print-button {
            display: none;
        }
    }
</style>

<body class="hold-transition sidebar-mini layout-fixed layout-navbar-fixed layout-footer-fixed">
<div class="wrapper">
  <?php include('includes/nav.php');?>
  <?php include('includes/sidebar.php');?>
  
  <div class="content-wrapper">
    <?php include('includes/pagetitle.php');?>

    <section class="content">
      <div class="container-fluid">
        <div class="row">
          <div class="col-md-12">
            <div class="card card-primary">
              <div class="card-header">
                <h3 class="card-title"><i class="fas fa-keyboard"></i> Bảng Lương Nhân Viên</h3>
              </div>
              
              <?php foreach ($salary_data as $department => $members): ?>
        <h3>Phòng: <?php echo htmlspecialchars($department); ?></h3>
        <table>
            <tr>
                <th>Tên nhân viên</th>
                <th>Số điện thoại</th>
                <th>Email</th>
                <th>Chức vụ</th>
                <th>Phòng</th>
                <th>Lương cơ bản</th>
                <th>Ngày công</th>
                <th>Phụ cấp</th>
                <th>Thưởng KPI</th>
                <th>Lương thực nhận</th>
            </tr>
            <?php foreach ($members as $data) { ?>
                <tr>
                    <td><?php echo htmlspecialchars($data['fullname']); ?></td>
                    <td><?php echo htmlspecialchars($data['contact_number']); ?></td>
                    <td><?php echo htmlspecialchars($data['email']); ?></td>
                    <td><?php echo htmlspecialchars($data['occupation']); ?></td>
                    <td><?php echo htmlspecialchars($data['membership_type']); ?></td>
                    <td><?php echo number_format($data['basic_salary']); ?></td>
                    <td><?php echo $data['working_days']; ?></td>
                    <td><?php echo number_format($data['meal_allowance'] + $data['clothing_allowance']); ?></td>
                    <td>
                        <input type="text" name="kpi_bonus[<?php echo htmlspecialchars($data['fullname']); ?>]" placeholder="Nhập thưởng KPI" value="<?php echo number_format($data['kpi_bonus']); ?>" />
                    </td>
                    <td><?php echo number_format($data['total_salary']); ?></td>
                </tr>
            <?php } ?>
        </table> 
    <?php endforeach; ?>

    <form method="POST" action="">
        <button type="submit">Lưu</button>
    </form>

    <form method="POST" action="export_to_excel.php">
        <button type="submit">In</button>
    </form>
            </div>
          </div>
        </div>
      </div>
    </section>
  </div>

  <aside class="control-sidebar control-sidebar-dark">
  </aside>

  <footer class="main-footer">
    <strong> &copy; <?php echo date('Y');?> HD Gourmet</strong> - Bản Quyền Thuộc Về.
    <div class="float-right d-none d-sm-inline-block">
      <b>Phát Triển Bởi</b> <a href="#">HD Gourmet</a>
    </div>
  </footer>
</div>

<?php include('includes/footer.php');?>

<script>
function printReport() {
    window.print();
}
</script>

</body>
</html>
