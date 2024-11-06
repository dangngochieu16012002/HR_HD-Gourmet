<?php
include('includes/config.php');

$selectQuery = "SELECT * FROM membership_types"; // Truy vấn để lấy tất cả loại thành viên
$result = $conn->query($selectQuery);

if (!isset($_SESSION['user_id'])) {
    header("Location: index.php"); // Chuyển hướng nếu chưa đăng nhập
    exit();
}

$fetchCurrencyQuery = "SELECT currency FROM settings WHERE id = 1"; // Truy vấn để lấy thông tin tiền tệ
$fetchCurrencyResult = $conn->query($fetchCurrencyQuery);

if ($fetchCurrencyResult->num_rows > 0) {
    $currencyDetails = $fetchCurrencyResult->fetch_assoc();
    $currencySymbol = $currencyDetails['currency']; // Lấy ký hiệu tiền tệ
} else {
    $currencySymbol = '$'; // Mặc định là ký hiệu đô la
}

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $membershipType = $_POST['membershipType']; // Lấy loại thành viên từ biểu mẫu
    $membershipAmount = $_POST['membershipAmount']; // Lấy số tiền từ biểu mẫu

    $insertQuery = "INSERT INTO membership_types (type, amount) VALUES ('$membershipType', $membershipAmount)"; // Truy vấn để thêm loại thành viên
    
    if ($conn->query($insertQuery) === TRUE) {
        $successMessage = 'Đã thêm loại thành viên thành công!'; // Thông báo thành công
    } else {
        echo "Lỗi: " . $insertQuery . "<br>" . $conn->error; // Thông báo lỗi
    }
}
?>

<!DOCTYPE html>
<html lang="vi"> <!-- Đặt ngôn ngữ là tiếng Việt -->
<?php include('includes/header.php');?> <!-- Bao gồm phần đầu trang -->

<body class="hold-transition sidebar-mini layout-fixed layout-navbar-fixed layout-footer-fixed">
<div class="wrapper">
  <?php include('includes/nav.php');?> <!-- Bao gồm thanh điều hướng -->
  <?php include('includes/sidebar.php');?> <!-- Bao gồm thanh bên -->

  <div class="content-wrapper">
    <?php include('includes/pagetitle.php');?> <!-- Bao gồm tiêu đề trang -->

    <section class="content">
      <div class="container-fluid">
        <div class="row">
          <div class="col-12">
            <div class="card">
              <div class="card-header">
                <h3 class="card-title">Thành Viên</h3> <!-- Tiêu đề bảng -->
              </div>
              <div class="card-body">
                <table id="example1" class="table table-bordered table-striped" style="padding-bottom: 50px;"> <!-- Thêm padding dưới bảng -->
                  <thead>
                    <tr>
                      <th>STT</th>
                      <th>Loại Thành Viên</th>
                      <th>Mã phòng ban</th>
                      <th>Mô tả phòng ban</th>
                      <th>Tên trưởng phòng</th>
                      <th>Số lượng nhân viên</th>
                      <th>Trạng thái</th>
                      <th>Lương</th>
                      <th>Hành Động</th>
                    </tr>
                  </thead>
                  <tbody>
                    <?php
                    $counter = 1;
                    while ($row = $result->fetch_assoc()) {
                      echo "<tr>";
                      echo "<td>{$counter}</td>";
                      echo "<td>{$row['type']}</td>"; // Loại thành viên
                      echo "<td>{$row['department_code']}</td>"; // Mã phòng ban
                      echo "<td>{$row['description']}</td>"; // Mô tả phòng ban
                      echo "<td>{$row['department_head']}</td>"; // Tên trưởng phòng
                      echo "<td>{$row['employee_count']}</td>"; // Số lượng nhân viên
                      echo "<td>{$row['status']}</td>"; // Trạng thái
                      echo "<td>{$currencySymbol} {$row['amount']}</td>"; // Số tiền
                      echo "<td>
                              <div class='dropdown'>
                                <button class='btn btn-primary dropdown-toggle' type='button' id='dropdownMenuButton{$row['id']}' onclick='toggleDropdown(this, {$row['id']})' aria-haspopup='true' aria-expanded='false'>
                                  Hành Động
                                </button>
                                <div class='dropdown-menu' aria-labelledby='dropdownMenuButton{$row['id']}' style='z-index: 1050;'> <!-- Thêm z-index cho menu -->
                                  <a class='dropdown-item' href='edit_type.php?id={$row['id']}'>Sửa</a>
                                  <button class='dropdown-item' onclick='deleteMembership({$row['id']})'>Xóa</button>
                                </div>
                              </div>
                            </td>";
                      echo "</tr>";
                      $counter++;
                    }
                    ?>
                  </tbody>
                </table>
              </div>
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

<?php include('includes/footer.php');?> <!-- Bao gồm phần chân trang -->
<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script>
  $(function () {
    $("#example1").DataTable({
      "responsive": true,
      "autoWidth": false,
    });
  });

  function deleteMembership(id) {
    if (confirm("Bạn có chắc chắn muốn xóa loại thành viên này không?")) { // Thông báo xác nhận
      window.location.href = 'delete_membership.php?id=' + id; // Chuyển hướng đến trang xóa
    }
  }

  // Hàm để xử lý việc hiển thị menu dropdown
  function toggleDropdown(button, id) {
    const dropdownMenu = $(button).siblings('.dropdown-menu');
    const otherMenus = $('.dropdown-menu').not(dropdownMenu); // Lấy tất cả các menu khác
    
    // Ẩn tất cả các menu khác
    otherMenus.removeClass('show'); 
    
    // Chuyển trạng thái hiển thị cho menu hiện tại
    dropdownMenu.toggleClass('show'); 
  }

  // Ngăn chặn việc đóng menu khi nhấn bên ngoài
  $(document).on('click', function(e) {
    if (!$(e.target).closest('.dropdown').length) {
      $('.dropdown-menu').removeClass('show'); // Ẩn tất cả menu dropdown nếu nhấn bên ngoài
    }
  });
</script>

<style>
.dropdown-menu {
    display: none; /* Ẩn menu dropdown */
}

.dropdown-menu.show {
    display: block; /* Hiển thị menu khi có lớp 'show' */
}

.dropdown-item {
    cursor: pointer; /* Con trỏ chuột dạng tay */
}

.dropdown-item:hover {
    background-color: #007bff; /* Thay đổi nền khi di chuột qua */
    color: white; /* Thay đổi màu chữ khi di chuột qua */
}
</style>

</body>
</html>
