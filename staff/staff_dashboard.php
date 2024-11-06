<?php
include('../includes/config.php');

// Kiểm tra xem người dùng có đăng nhập và là nhân viên hay không
if (!isset($_SESSION['user_id']) || $_SESSION['user_type'] != 'staff') {
    header("Location: ../index.php");
    exit();
}

// Hàm để lấy số lượng thành viên do nhân viên này quản lý (tùy thuộc vào vai trò của nhân viên)
function getTotalMembersCountForStaff($staff_id) {
    global $conn;
    // Ví dụ: Nhân viên chỉ thấy các thành viên thuộc khu vực mình quản lý (nếu có logic này)
    $totalMembersQuery = "SELECT COUNT(*) AS totalMembers FROM members WHERE staff_manager_id = ?";
    $stmt = $conn->prepare($totalMembersQuery);
    $stmt->bind_param("i", $staff_id);
    $stmt->execute();
    $totalMembersResult = $stmt->get_result();
    if ($totalMembersResult->num_rows > 0) {
        $totalMembersRow = $totalMembersResult->fetch_assoc();
        return $totalMembersRow['totalMembers'];
    } else {
        return 0;
    }
}

// Lấy thông tin về nhân viên đang đăng nhập
$staff_id = $_SESSION['user_id'];

include('includes_staff/header_staff.php');
?>

<!-- HTML content for staff dashboard -->
<body class="hold-transition sidebar-mini layout-fixed layout-navbar-fixed layout-footer-fixed">
<div class="wrapper">
  <?php include('includes_staff/nav_staff.php');?>
  <?php include('includes_staff/sidebar_staff.php');?>

  <!-- Content Wrapper. Contains page content -->
  <div class="content-wrapper">
    
  <?php include('includes_staff/pagetitle_staff.php');?>

    <!-- Main content -->
    <section class="content">
            <div class="container-fluid">
                <!-- Info boxes -->
                
            </div>
        </section>
    <!-- /.content -->
  </div>
  <!-- /.content-wrapper -->

  <!-- Main Footer -->
  <footer class="main-footer">
    <strong> &copy; <?php echo date('Y');?> HD Gourmet</a> -</strong>
    Bản Quyền Thuộc Về.
  </footer>
</div>
<!-- ./wrapper -->

<?php include('includes_staff/footer_staff.php');?>
</body>
</html>
