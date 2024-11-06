<?php
// Kết nối cơ sở dữ liệu
include('../includes/config.php');

// Bắt đầu session nếu chưa được bắt đầu
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

// Kiểm tra trạng thái đăng nhập
if (!isset($_SESSION['user_id']) || $_SESSION['user_type'] !== 'staff') {
    header("Location: index.php");
    exit();
}

// Lấy ID nhân viên từ session
$staff_id = $_SESSION['user_id'];

// Lấy tên đầy đủ của nhân viên từ bảng members
$memberQuery = "SELECT fullname FROM members WHERE id = ?";
$stmt = $conn->prepare($memberQuery);
$stmt->bind_param("i", $staff_id);
$stmt->execute();
$result = $stmt->get_result();
if ($result->num_rows > 0) {
    $fullname = $result->fetch_assoc()['fullname'];
} else {
    die("Không tìm thấy thông tin nhân viên.");
}

// Dữ liệu mẫu đánh giá hiệu suất
$performanceData = [
    'tasks_assigned' => 30,
    'tasks_completed' => 28,
    'tasks_pending' => 2,
    'tasks_approved' => 25,
    'rating' => 4.7 // Điểm đánh giá trung bình
];

include('includes_staff/header_staff.php');
?>

<!-- HTML content cho trang đánh giá hiệu suất -->
<body class="hold-transition sidebar-mini layout-fixed layout-navbar-fixed layout-footer-fixed">
<div class="wrapper">
  <?php include('includes_staff/nav_staff.php'); ?>
  <?php include('includes_staff/sidebar_staff.php'); ?>

  <!-- Content Wrapper -->
  <div class="content-wrapper">
    <section class="content">
      <div class="container-fluid">
        <div class="card p-4">
          <div class="card-header text-center bg-primary text-white rounded">
            <h2 class="font-weight-bold">Đánh Giá Hiệu Suất Nhân Viên</h2>
          </div>
          <div class="row mt-4">
            <div class="col-md-3 text-center">
              <div class="card bg-primary text-white shadow-sm mb-3">
                <div class="card-body">
                  <h3><?php echo $performanceData['tasks_assigned']; ?></h3>
                  <p>Tổng số nhiệm vụ</p>
                </div>
              </div>
            </div>
            <div class="col-md-3 text-center">
              <div class="card bg-success text-white shadow-sm mb-3">
                <div class="card-body">
                  <h3><?php echo $performanceData['tasks_completed']; ?></h3>
                  <p>Nhiệm vụ hoàn thành</p>
                </div>
              </div>
            </div>
            <div class="col-md-3 text-center">
              <div class="card bg-warning text-dark shadow-sm mb-3">
                <div class="card-body">
                  <h3><?php echo $performanceData['tasks_pending']; ?></h3>
                  <p>Nhiệm vụ đang chờ</p>
                </div>
              </div>
            </div>
            <div class="col-md-3 text-center">
              <div class="card bg-info text-white shadow-sm mb-3">
                <div class="card-body">
                  <h3><?php echo $performanceData['tasks_approved']; ?></h3>
                  <p>Nhiệm vụ đã duyệt</p>
                </div>
              </div>
            </div>
          </div>
          <div class="row mt-4">
            <div class="col-md-12 text-center">
              <div class="card bg-dark text-white shadow-sm">
                <div class="card-body">
                  <h4>Điểm Đánh Giá Trung Bình</h4>
                  <h2 class="text-warning"><?php echo $performanceData['rating']; ?>/5</h2>
                </div>
              </div>
            </div>
          </div>
        </div>
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

<!-- CSS dành riêng cho giao diện đánh giá hiệu suất -->
<style>
    .card {
        border-radius: 10px;
        box-shadow: 0 4px 20px rgba(0, 0, 0, 0.1);
    }

    .card-body h3 {
        font-size: 2rem;
        margin-bottom: 0;
    }

    .card-body p {
        margin: 0;
        font-size: 1.1rem;
    }
</style>
