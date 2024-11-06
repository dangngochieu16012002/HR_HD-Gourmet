<?php
// Kết nối cơ sở dữ liệu
include('includes/config.php');

// Kiểm tra xem người dùng đã đăng nhập chưa, nếu chưa chuyển hướng về trang đăng nhập
if (!isset($_SESSION['user_id'])) {
    header("Location: index.php");
    exit();
}

// Biến phản hồi mặc định
$response = array('success' => false, 'message' => '');

// Kiểm tra xem phương thức gửi là POST
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $departmentCode = $_POST['departmentCode'];
    $membershipType = $_POST['membershipType'];
    $membershipAmount = $_POST['membershipAmount'];
    $description = $_POST['description'];
    $departmentHead = $_POST['departmentHead'];
    $employeeCount = $_POST['employeeCount'];
    $status = $_POST['status'];

    // Thêm loại phòng ban mới vào cơ sở dữ liệu
    $insertQuery = "INSERT INTO membership_types (department_code, type, amount, description, department_head, employee_count, status) 
                    VALUES ('$departmentCode', '$membershipType', $membershipAmount, '$description', '$departmentHead', $employeeCount, '$status')";
    
    if ($conn->query($insertQuery) === TRUE) {
        $response['success'] = true;
        $response['message'] = 'Đã thêm phòng ban thành công!';
    } else {
        $response['message'] = 'Lỗi: ' . $conn->error;
    }
}
?>

<!-- Phần đầu trang -->
<?php include('includes/header.php');?>

<body class="hold-transition sidebar-mini layout-fixed layout-navbar-fixed layout-footer-fixed">
<div class="wrapper">
  <?php include('includes/nav.php');?>
  <?php include('includes/sidebar.php');?>

  <!-- Nội dung chính của trang -->
  <div class="content-wrapper">
    
  <?php include('includes/pagetitle.php');?>

    <!-- Nội dung chính -->
    <section class="content">
      <div class="container-fluid">
        <div class="row">
        
        <div class="col-md-12">

        <!-- Thông báo thành công -->
        <?php if ($response['success']): ?>
            <div class="alert alert-success alert-dismissible">
                <button type="button" class="close" data-dismiss="alert" aria-hidden="true">&times;</button>
                <h5><i class="icon fas fa-check"></i> Thành công</h5>
                <?php echo $response['message']; ?>
            </div>
        <?php elseif (!empty($response['message'])): ?>
            <div class="alert alert-danger alert-dismissible">
                <button type="button" class="close" data-dismiss="alert" aria-hidden="true">&times;</button>
                <h5><i class="icon fas fa-ban"></i> Lỗi</h5>
                <?php echo $response['message']; ?>
            </div>
        <?php endif; ?>

            <!-- Form thêm loại thành viên -->
            <div class="card card-primary">
              <div class="card-header">
                <h3 class="card-title"><i class="fas fa-keyboard"></i> Thêm phòng ban mới</h3>
              </div>
              <!-- Form bắt đầu -->
              <form method="post" action="">
                <div class="card-body">
                    <div class="row"> 
                        <div class="col-sm-4">
                            <label for="departmentCode">Mã phòng ban</label>
                            <input type="text" class="form-control" id="departmentCode" name="departmentCode" placeholder="Nhập mã phòng ban" required>
                        </div>
                        <div class="col-sm-4">
                            <label for="membershipType">Loại phòng ban</label>
                            <input type="text" class="form-control" id="membershipType" name="membershipType" placeholder="Nhập tên loại phòng ban" required>
                        </div>
                        <div class="col-sm-4">
                            <label for="membershipAmount">Lương</label>
                            <input type="number" class="form-control" id="membershipAmount" name="membershipAmount" placeholder="Nhập lương" required>
                        </div>
                        <div class="col-sm-12 mt-3">
                            <label for="description">Mô tả phòng ban</label>
                            <textarea class="form-control" id="description" name="description" placeholder="Nhập mô tả phòng ban"></textarea>
                        </div>
                        <div class="col-sm-6 mt-3">
                            <label for="departmentHead">Tên trưởng phòng</label>
                            <input type="text" class="form-control" id="departmentHead" name="departmentHead" placeholder="Nhập tên trưởng phòng">
                        </div>
                        <div class="col-sm-6 mt-3">
                            <label for="employeeCount">Số lượng nhân viên</label>
                            <input type="number" class="form-control" id="employeeCount" name="employeeCount" placeholder="Nhập số lượng nhân viên">
                        </div>
                        <div class="col-sm-12 mt-3">
                            <label for="status">Trạng thái</label>
                            <select class="form-control" id="status" name="status" required>
                                <option value="">Chọn trạng thái</option>
                                <option value="active">Hoạt động</option>
                                <option value="inactive">Ngừng hoạt động</option>
                            </select>
                        </div>
                    </div>
                </div>
                <!-- Kết thúc nội dung form -->

                <div class="card-footer">
                  <button type="submit" class="btn btn-primary">Thêm</button>
                </div>
              </form>
            </div>
            <!-- Kết thúc card -->

          </div>
        </div>
        
      </div>
    </section>
  </div>

  <!-- Footer -->
  <footer class="main-footer">
    <strong> &copy; <?php echo date('Y');?> HD Gourmet -</strong>
    Bản Quyền Thuộc Về.
    <div class="float-right d-none d-sm-inline-block">
      <b>Phát Triển Bởi</b> <a href="#">HD Gourmet</a>
    </div>
  </footer>
</div>

<?php include('includes/footer.php');?>
</body>
</html>
