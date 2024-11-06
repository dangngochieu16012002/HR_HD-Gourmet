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

// Lấy thông tin chi tiết của nhân viên từ bảng members và tên phòng ban từ bảng membership_types
$memberQuery = "SELECT members.*, membership_types.type AS department_name 
                FROM members 
                LEFT JOIN membership_types ON members.membership_type = membership_types.id 
                WHERE members.id = ?";
$stmt = $conn->prepare($memberQuery);
$stmt->bind_param("i", $staff_id);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows > 0) {
    $memberData = $result->fetch_assoc();
} else {
    die("Không tìm thấy thông tin nhân viên.");
}

include('includes_staff/header_staff.php');
?>

<!-- HTML content cho trang thông tin nhân viên -->
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
            <h2 class="font-weight-bold">Thông Tin Chi Tiết Nhân Viên</h2>
          </div>
          <div class="row mt-4">
            <div class="col-md-4 text-center">
              <?php
              // Hiển thị ảnh từ cơ sở dữ liệu với đường dẫn cập nhật
              if (!empty($memberData['photo'])) {
                  $photoPath = '../uploads/member_photos/' . htmlspecialchars($memberData['photo']);
                  if (file_exists($photoPath)) {
                      echo '<img src="' . $photoPath . '" alt="Ảnh Nhân Viên" class="profile-img mb-3">';
                  } else {
                      echo '<img src="../dist/img/default-avatar.png" alt="Ảnh Mặc Định" class="profile-img mb-3">';
                  }
              } else {
                  echo '<img src="../dist/img/default-avatar.png" alt="Ảnh Mặc Định" class="profile-img mb-3">';
              }
              ?>
              <h4 class="mt-2 text-primary"><?php echo htmlspecialchars($memberData['fullname']); ?></h4>
              <p class="text-muted"><?php echo htmlspecialchars($memberData['occupation']); ?></p>
            </div>
            <div class="col-md-8">
              <div class="info-box mb-3 bg-info shadow-sm">
                <span class="info-box-icon"><i class="fas fa-id-badge"></i></span>
                <div class="info-box-content">
                  <span class="info-box-text">Mã NV</span>
                  <span class="info-box-number"><?php echo htmlspecialchars($memberData['membership_number']); ?></span>
                </div>
              </div>
              <div class="table-responsive">
                <table class="table table-striped table-hover employee-info-table">
                  <tbody>
                    <tr>
                      <th><i class="fas fa-building"></i> Phòng ban</th>
                      <td><?php echo htmlspecialchars($memberData['department_name'] ? $memberData['department_name'] : 'Chưa có thông tin'); ?></td>
                      <th><i class="fas fa-flag"></i> Quốc tịch</th>
                      <td><?php echo htmlspecialchars($memberData['country']); ?></td>
                    </tr>
                    <tr>
                      <th><i class="fas fa-calendar-alt"></i> Ngày vào làm</th>
                      <td><?php echo date('d/m/Y', strtotime($memberData['created_at'])); ?></td>
                      <th><i class="fas fa-calendar"></i> Ngày sinh</th>
                      <td><?php echo date('d/m/Y', strtotime($memberData['dob'])); ?></td>
                    </tr>
                    <tr>
                      <th><i class="fas fa-mars"></i> Giới tính</th>
                      <td><?php echo htmlspecialchars($memberData['gender']); ?></td>
                      <th><i class="fas fa-phone"></i> Số điện thoại</th>
                      <td><?php echo htmlspecialchars($memberData['contact_number']); ?></td>
                    </tr>
                    <tr>
                      <th><i class="fas fa-envelope"></i> Email</th>
                      <td colspan="3"><?php echo htmlspecialchars($memberData['email']); ?></td>
                    </tr>
                    <tr>
                      <th><i class="fas fa-map-marker-alt"></i> Địa chỉ</th>
                      <td colspan="3"><?php echo htmlspecialchars($memberData['address']); ?></td>
                    </tr>
                    <tr>
                      <th><i class="fas fa-file-contract"></i> Hợp đồng</th>
                      <td colspan="3">
                        <?php
                        if (!empty($memberData['contract'])) {
                            $contractPath = '../uploads/member_photos/' . htmlspecialchars($memberData['contract']);
                            if (file_exists($contractPath)) {
                                echo '<a href="' . $contractPath . '" target="_blank" class="text-info">Xem</a>';
                            } else {
                                echo '<span class="text-danger">Không tìm thấy hợp đồng</span>';
                            }
                        } else {
                            echo '<span class="text-muted">Chưa có hợp đồng</span>';
                        }
                        ?>
                      </td>
                    </tr>
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
    <strong> &copy; <?php echo date('Y'); ?> HD Gourmet</strong> - Bản Quyền Thuộc Về.
  </footer>
</div>
<?php include('includes_staff/footer_staff.php'); ?>
</body>
</html>

<!-- CSS dành riêng cho giao diện thông tin nhân viên -->
<style>
    .card {
        border-radius: 10px;
        border: 1px solid #dee2e6;
        box-shadow: 0 4px 20px rgba(0, 0, 0, 0.1);
        background-color: #ffffff;
    }

    .profile-img {
        width: 160px;
        height: 160px;
        border-radius: 50%;
        border: 3px solid #17a2b8;
        background-color: #f8f9fa;
        object-fit: cover;
        box-shadow: 0 4px 10px rgba(0, 0, 0, 0.1);
    }

    .employee-info-table th {
        width: 30%;
        text-align: left;
        color: #495057;
        font-weight: 600;
        padding: 10px;
        background-color: #e9ecef;
        border-radius: 5px;
    }

    .employee-info-table td {
        width: 70%;
        padding: 10px;
        vertical-align: middle;
    }

    .info-box {
        display: flex;
        align-items: center;
        margin-bottom: 20px;
        border-radius: 10px;
        background-color: #17a2b8;
        color: #ffffff;
    }

    .info-box .info-box-icon {
        border-radius: 10px 0 0 10px;
        padding: 15px;
        font-size: 30px;
        color: #ffffff;
    }

    .info-box .info-box-content {
        padding: 10px 20px;
    }

    .info-box .info-box-text {
        font-size: 18px;
        color: #ffffff;
    }

    .info-box .info-box-number {
        font-size: 24px;
        color: #ffffff;
    }

    .table-hover tbody tr:hover {
        background-color: #f1f1f1;
    }
</style>
