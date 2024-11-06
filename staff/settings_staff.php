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

// Truy vấn để lấy thông tin nhân viên
$userQuery = $conn->prepare("SELECT * FROM member_accounts WHERE member_id = ?");
$userQuery->bind_param("i", $staff_id);
$userQuery->execute();
$userResult = $userQuery->get_result();

if ($userResult->num_rows > 0) {
    $memberData = $userResult->fetch_assoc();
} else {
    die("Không tìm thấy thông tin nhân viên.");
}

// Xử lý thay đổi mật khẩu
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['changePassword'])) {
    $currentPassword = $_POST['currentPassword'];
    $newPassword = $_POST['newPassword'];
    $confirmPassword = $_POST['confirmPassword'];

    // Kiểm tra mật khẩu hiện tại
    if (md5($currentPassword) === $memberData['password']) {
        // Kiểm tra nếu mật khẩu mới trùng với mật khẩu hiện tại
        if (md5($newPassword) === $memberData['password']) {
            $errorMessage = "Mật khẩu mới không được trùng với mật khẩu hiện tại.";
        } else {
            if ($newPassword === $confirmPassword) {
                $hashedNewPassword = md5($newPassword);
                $updatePasswordQuery = $conn->prepare("UPDATE member_accounts SET password = ? WHERE member_id = ?");
                $updatePasswordQuery->bind_param("si", $hashedNewPassword, $staff_id);

                if ($updatePasswordQuery->execute()) {
                    $successMessage = "Đổi mật khẩu thành công!";
                } else {
                    $errorMessage = "Lỗi khi đổi mật khẩu: " . $conn->error;
                }
            } else {
                $errorMessage = "Mật khẩu mới và xác nhận mật khẩu không khớp.";
            }
        }
    } else {
        $errorMessage = "Mật khẩu hiện tại không đúng.";
    }
}

include('includes_staff/header_staff.php');
?>

<!-- HTML content cho trang đổi mật khẩu -->
<body class="hold-transition sidebar-mini layout-fixed layout-navbar-fixed layout-footer-fixed">
<div class="wrapper">
  <?php include('includes_staff/nav_staff.php'); ?>
  <?php include('includes_staff/sidebar_staff.php'); ?>

  <!-- Content Wrapper -->
  <div class="content-wrapper">
    <section class="content">
      <div class="container-fluid">
        <div class="card mx-auto mt-5 p-4 shadow-lg" style="max-width: 600px; border-radius: 15px;">
          <div class="card-header bg-gradient-primary text-white text-center rounded-top" style="border-radius: 15px 15px 0 0;">
            <h3 class="mb-0"><i class="fas fa-lock"></i> Đổi Mật Khẩu</h3>
          </div>
          <div class="card-body">
            <?php
            if (!empty($successMessage)) {
                echo '<div class="alert alert-success alert-dismissible fade show" role="alert">
                        <strong>Thành công!</strong> ' . $successMessage . '
                        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                      </div>';
            } elseif (!empty($errorMessage)) {
                echo '<div class="alert alert-danger alert-dismissible fade show" role="alert">
                        <strong>Lỗi!</strong> ' . $errorMessage . '
                        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                      </div>';
            }
            ?>

            <!-- Form thay đổi mật khẩu -->
            <form method="post" action="">
              <div class="form-group mb-3 position-relative">
                <label for="currentPassword" class="form-label"><i class="fas fa-key"></i> Mật Khẩu Hiện Tại</label>
                <div class="input-group">
                  <input type="password" id="currentPassword" name="currentPassword" class="form-control" required>
                  <div class="input-group-append">
                    <span class="input-group-text toggle-password" onclick="togglePassword('currentPassword')">
                      <i class="fas fa-eye"></i> <span class="toggle-text">Ẩn/Hiện</span>
                    </span>
                  </div>
                </div>
              </div>
              <div class="form-group mb-3 position-relative">
                <label for="newPassword" class="form-label"><i class="fas fa-lock"></i> Mật Khẩu Mới</label>
                <div class="input-group">
                  <input type="password" id="newPassword" name="newPassword" class="form-control" required>
                  <div class="input-group-append">
                    <span class="input-group-text toggle-password" onclick="togglePassword('newPassword')">
                      <i class="fas fa-eye"></i> <span class="toggle-text">Ẩn/Hiện</span>
                    </span>
                  </div>
                </div>
              </div>
              <div class="form-group mb-4 position-relative">
                <label for="confirmPassword" class="form-label"><i class="fas fa-lock"></i> Xác Nhận Mật Khẩu Mới</label>
                <div class="input-group">
                  <input type="password" id="confirmPassword" name="confirmPassword" class="form-control" required>
                  <div class="input-group-append">
                    <span class="input-group-text toggle-password" onclick="togglePassword('confirmPassword')">
                      <i class="fas fa-eye"></i> <span class="toggle-text">Ẩn/Hiện</span>
                    </span>
                  </div>
                </div>
              </div>
              <button type="submit" name="changePassword" class="btn btn-primary w-100 py-2 shadow-sm">
                <i class="fas fa-save"></i> Thay Đổi Mật Khẩu
              </button>
            </form>
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

<!-- JavaScript để ẩn/hiện mật khẩu -->
<script>
function togglePassword(id) {
    var input = document.getElementById(id);
    var icon = input.nextElementSibling.querySelector('i');
    var toggleText = input.nextElementSibling.querySelector('.toggle-text');
    if (input.type === "password") {
        input.type = "text";
        icon.classList.remove('fa-eye');
        icon.classList.add('fa-eye-slash');
        toggleText.textContent = 'Ẩn';
    } else {
        input.type = "password";
        icon.classList.remove('fa-eye-slash');
        icon.classList.add('fa-eye');
        toggleText.textContent = 'Hiện';
    }
}
</script>

<!-- CSS dành riêng cho giao diện đổi mật khẩu -->
<style>
    .card {
        border-radius: 15px;
        box-shadow: 0 10px 30px rgba(0, 0, 0, 0.1);
        background-color: #f8f9fa;
    }

    .card-header {
        background: linear-gradient(135deg, #007bff, #6610f2);
        border-radius: 15px 15px 0 0;
    }

    .form-label {
        font-weight: 600;
        color: #495057;
    }

    .btn-primary {
        background: linear-gradient(135deg, #007bff, #6610f2);
        border: none;
        border-radius: 5px;
        transition: background 0.3s ease;
    }

    .btn-primary:hover {
        background: linear-gradient(135deg, #0056b3, #520e9b);
    }

    .alert {
        border-radius: 10px;
    }

    .input-group-text {
        cursor: pointer;
        background-color: #ffffff;
        border-left: none;
        display: flex;
        align-items: center;
    }

    .toggle-password i {
        margin-right: 5px;
    }

    .toggle-text {
        font-size: 14px;
        color: #007bff;
    }

    .position-relative {
        position: relative;
    }
</style>
