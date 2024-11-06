<?php
include('includes/config.php');

if (!isset($_SESSION['user_id'])) {
    header("Location: index.php");
    exit();
}

$response = array('success' => false, 'message' => '');

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $membershipType = $_POST['membershipType'];
    $membershipAmount = $_POST['membershipAmount'];
    $departmentCode = $_POST['departmentCode']; // Thêm mã phòng ban
    $description = $_POST['description']; // Thêm mô tả phòng ban
    $departmentHead = $_POST['departmentHead']; // Thêm tên trưởng phòng
    $employeeCount = $_POST['employeeCount']; // Thêm số lượng nhân viên

    $id = $_POST['edit_id'];

    $updateQuery = "UPDATE membership_types SET 
        type = '$membershipType', 
        amount = $membershipAmount, 
        department_code = '$departmentCode',
        description = '$description',
        department_head = '$departmentHead',
        employee_count = $employeeCount 
        WHERE id = $id";

    if ($conn->query($updateQuery) === TRUE) {
        $response['success'] = true;
        $response['message'] = 'Đã cập nhật loại thành viên thành công!';
    } else {
        $response['message'] = 'Lỗi: ' . $conn->error;
    }
}

$edit_id = $_GET['id'] ?? null;
$editQuery = "SELECT * FROM membership_types WHERE id = $edit_id";
$result = $conn->query($editQuery);
$editData = $result->fetch_assoc();
?>

<?php include('includes/header.php');?>

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

            <?php if ($response['success']): ?>
                <div class="alert alert-success alert-dismissible">
                    <button type="button" class="close" data-dismiss="alert" aria-hidden="true">&times;</button>
                    <h5><i class="icon fas fa-check"></i> Thành Công</h5>
                    <?php echo $response['message']; ?>
                </div>
            <?php elseif (!empty($response['message'])): ?>
                <div class="alert alert-danger alert-dismissible">
                    <button type="button" class="close" data-dismiss="alert" aria-hidden="true">&times;</button>
                    <h5><i class="icon fas fa-ban"></i> Lỗi</h5>
                    <?php echo $response['message']; ?>
                </div>
            <?php endif; ?>

            <div class="card card-primary">
              <div class="card-header">
                <h3 class="card-title"><i class="fas fa-keyboard"></i> Chỉnh Sửa Phòng</h3>
              </div>

              <form method="post" action="">
                <input type="hidden" name="edit_id" value="<?php echo $edit_id; ?>">

                <div class="card-body">
                  <div class="row">
                    <div class="col-sm-6">
                      <label for="membershipType">Phòng</label>
                      <input type="text" class="form-control" id="membershipType" name="membershipType" placeholder="Nhập loại thành viên" value="<?php echo $editData['type']; ?>" required>
                    </div>
                    <div class="col-sm-6">
                      <label for="membershipAmount">Lương</label>
                      <input type="number" class="form-control" id="membershipAmount" name="membershipAmount" placeholder="Nhập số tiền thành viên" value="<?php echo $editData['amount']; ?>" required>
                    </div>
                  </div>

                  <div class="row mt-2">
                    <div class="col-sm-6">
                      <label for="departmentCode">Mã Phòng Ban</label>
                      <input type="text" class="form-control" id="departmentCode" name="departmentCode" placeholder="Nhập mã phòng ban" value="<?php echo $editData['department_code']; ?>" required>
                    </div>
                    <div class="col-sm-6">
                      <label for="description">Mô Tả</label>
                      <input type="text" class="form-control" id="description" name="description" placeholder="Nhập mô tả" value="<?php echo $editData['description']; ?>" required>
                    </div>
                  </div>

                  <div class="row mt-2">
                    <div class="col-sm-6">
                      <label for="departmentHead">Tên Trưởng Phòng</label>
                      <input type="text" class="form-control" id="departmentHead" name="departmentHead" placeholder="Nhập tên trưởng phòng" value="<?php echo $editData['department_head']; ?>" required>
                    </div>
                    <div class="col-sm-6">
                      <label for="employeeCount">Số Lượng Nhân Viên</label>
                      <input type="number" class="form-control" id="employeeCount" name="employeeCount" placeholder="Nhập số lượng nhân viên" value="<?php echo $editData['employee_count']; ?>" required>
                    </div>
                  </div>
                </div>

                <div class="card-footer">
                  <button type="submit" class="btn btn-primary">Cập Nhật</button>
                  <a href="view_type.php" class="btn btn-secondary">Quay Về</a> 
                </div>
              </form>
            </div>
          </div>
        </div>
      </div>
    </section>
  </div>

  <footer class="main-footer">
    <strong> &copy; <?php echo date('Y');?> HD Gourmet</strong> - Bản Quyền Thuộc Về.
    <div class="float-right d-none d-sm-inline-block">
      <b>Phát Triển Bởi</b> <a href="#">HD Gourmet</a>
    </div>
  </footer>
</div>

<?php include('includes/footer.php');?>
</body>
</html>
