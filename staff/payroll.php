<?php
include('../includes/config.php');

// Bắt đầu session nếu chưa được bắt đầu
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

// Kiểm tra xem người dùng có đăng nhập không
if (!isset($_SESSION['user_id'])) {
    header("Location: ../index.php");
    exit();
}

// Lấy ID nhân viên từ session
$staff_id = $_SESSION['user_id'];

// Lấy tên đầy đủ của nhân viên từ bảng `members`
$memberQuery = "SELECT fullname FROM members WHERE id = ?";
$memberStmt = $conn->prepare($memberQuery);
if (!$memberStmt) {
    die("Error preparing statement for member query: " . $conn->error);
}
$memberStmt->bind_param("i", $staff_id);
$memberStmt->execute();
$memberResult = $memberStmt->get_result();
if ($memberResult->num_rows > 0) {
    $fullname = $memberResult->fetch_assoc()['fullname'];
} else {
    die("Không tìm thấy thông tin nhân viên.");
}

// Lấy thông tin lương từ bảng `salaries`
$salaryQuery = "SELECT * FROM salaries WHERE id = ? ORDER BY created_at DESC";
$stmt = $conn->prepare($salaryQuery);
if (!$stmt) {
    die("Error preparing statement for salary query: " . $conn->error);
}
$stmt->bind_param("i", $staff_id);
$stmt->execute();
$salaryResult = $stmt->get_result();

include('includes_staff/header_staff.php');
?>

<!-- HTML content cho trang bảng lương -->
<body class="hold-transition sidebar-mini layout-fixed layout-navbar-fixed layout-footer-fixed">
<div class="wrapper">
  <?php include('includes_staff/nav_staff.php'); ?>
  <?php include('includes_staff/sidebar_staff.php'); ?>

  <!-- Content Wrapper -->
  <div class="content-wrapper">
    <section class="content">
      <div class="container-fluid">
        <h2 class="salary-title">Bảng Lương Cá Nhân</h2>
        <?php if ($salaryResult->num_rows > 0): ?>
            <table class="salary-table">
                <thead>
                    <tr>
                        <th>Họ Tên</th>
                        <th>Lương Cơ Bản (VND)</th>
                        <th>Trợ Cấp Ăn Uống (VND)</th>
                        <th>Trợ Cấp Quần Áo (VND)</th>
                        <th>Thưởng KPI (VND)</th>
                        <th>Tổng Lương (VND)</th>
                        <th>Ngày Cập Nhật</th>
                    </tr>
                </thead>
                <tbody>
                    <?php while ($salary = $salaryResult->fetch_assoc()): ?>
                        <tr>
                            <td><?php echo htmlspecialchars($fullname); ?></td>
                            <td><?php echo number_format($salary['basic_salary'], 0, ',', '.'); ?></td>
                            <td><?php echo number_format($salary['meal_allowance'], 0, ',', '.'); ?></td>
                            <td><?php echo number_format($salary['clothing_allowance'], 0, ',', '.'); ?></td>
                            <td><?php echo number_format($salary['kpi_bonus'], 0, ',', '.'); ?></td>
                            <td><?php echo number_format($salary['total_salary'], 0, ',', '.'); ?></td>
                            <td><?php echo date('d/m/Y', strtotime($salary['created_at'])); ?></td>
                        </tr>
                    <?php endwhile; ?>
                </tbody>
            </table>
        <?php else: ?>
            <p>Không tìm thấy thông tin lương.</p>
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

<!-- CSS dành riêng cho giao diện bảng lương -->
<style>
    .salary-title {
        text-align: center;
        margin-bottom: 20px;
        color: #333;
        font-weight: bold;
        font-size: 24px;
        padding: 10px;
        background-color: #f4f6f9;
        border-radius: 8px;
    }

    .salary-table {
        width: 100%;
        border-collapse: collapse;
        margin: 20px 0;
        font-size: 16px;
        text-align: left;
        box-shadow: 0 2px 15px rgba(0, 0, 0, 0.1);
        border-radius: 8px;
        overflow: hidden;
    }

    .salary-table thead {
        background-color: #007bff;
    }

    .salary-table th {
        color: #ffffff;
        font-weight: bold;
        padding: 12px 15px;
        text-transform: uppercase;
    }

    .salary-table th, .salary-table td {
        border: 1px solid #ddd;
        padding: 12px 15px;
        text-align: center;
    }

    .salary-table tr {
        background-color: #f9f9f9;
        transition: background-color 0.3s;
    }

    .salary-table tr:nth-child(even) {
        background-color: #f1f1f1;
    }

    .salary-table tr:hover {
        background-color: #e7f1ff;
    }

    .salary-table td {
        font-size: 14px;
        color: #333;
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
