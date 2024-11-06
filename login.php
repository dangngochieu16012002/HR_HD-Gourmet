<?php
// Kết nối cơ sở dữ liệu
include('includes/config.php');

// Biến thông báo lỗi
$error_message = "";

// Xử lý đăng nhập khi form được gửi
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['login'])) {
    $email = $_POST['email'];
    $password = $_POST['password'];
    $user_type = $_POST['user_type'];

    // Kiểm tra email và password không được để trống
    if (empty($email) || empty($password)) {
        $error_message = "Email và mật khẩu không được để trống!";
    } else {
        // Tạo câu truy vấn dựa vào loại người dùng
        $sql = ($user_type == 'staff') ? 
            "SELECT * FROM member_accounts WHERE email = ?" : 
            "SELECT * FROM users WHERE email = ?";

        // Chuẩn bị và thực thi câu truy vấn
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("s", $email);
        $stmt->execute();
        $result = $stmt->get_result();

        // Kiểm tra thông tin đăng nhập
        if ($result->num_rows == 1) {
            $row = $result->fetch_assoc();
            if (md5($password) == $row['password']) {
                $_SESSION['user_id'] = $user_type == 'staff' ? $row['member_id'] : $row['id'];
                $_SESSION['email'] = $row['email'];
                $_SESSION['user_type'] = $user_type;

                // Cập nhật last_login cho staff và chuyển hướng
                if ($user_type == 'staff') {
                    $update_login = "UPDATE member_accounts SET last_login = CURRENT_TIMESTAMP WHERE email = ?";
                    $stmt = $conn->prepare($update_login);
                    $stmt->bind_param("s", $email);
                    $stmt->execute();
                    header("Location: staff/staff_dashboard.php");
                } else {
                    header("Location: dashboard.php");
                }
                exit();
            } else {
                $error_message = "Mật khẩu không chính xác!";
            }
        } else {
            $error_message = "Email hoặc mật khẩu không chính xác!";
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta http-equiv="X-UA-Compatible" content="IE=edge">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>HD-Gourmet Login</title>
  <link rel="stylesheet" type="text/css" href="style2.css">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css"> <!-- Font Awesome -->
</head>
<body>
  <div class="wrapper">
    <!-- Form đăng nhập cho nhân viên -->
    <div class="form-wrapper sign-in">
      <form action="" method="POST">
        <h2>HD-Gourmet</h2>
        <h2>Nhân Viên <a href="index.html" class="see-more-btn"><i class="fas fa-undo"></i></a></h2>
        <input type="hidden" name="user_type" value="staff">
        <?php if (!empty($error_message) && isset($_POST['user_type']) && $_POST['user_type'] == 'staff') : ?>
            <div class="alert alert-danger"><?php echo $error_message; ?></div>
        <?php endif; ?>
        <div class="input-group">
          <input type="email" name="email" required>
          <label>Email</label>
        </div>
        <div class="input-group">
          <input type="password" name="password" required>
          <label>Password</label>
        </div>
        <div class="remember">
          <label><input type="checkbox" name="remember"> Remember me</label>
        </div>
        <button type="submit" name="login">Login</button>
        <div class="signUp-link">
          <p>Bạn là User? <a href="#" class="signUpBtn-link">User</a></p>
        </div>
      </form>
    </div>

    <!-- Form đăng nhập cho user -->
    <div class="form-wrapper sign-up">
      <form action="" method="POST">
        <h2>HD-Gourmet</h2>
        <h2>User <a href="index.html" class="see-more-btn"><i class="fas fa-undo"></i></a></h2>
        <input type="hidden" name="user_type" value="user">
        <?php if (!empty($error_message) && isset($_POST['user_type']) && $_POST['user_type'] == 'user') : ?>
            <div class="alert alert-danger"><?php echo $error_message; ?></div>
        <?php endif; ?>
        <div class="input-group">
          <input type="email" name="email" required>
          <label>Email</label>
        </div>
        <div class="input-group">
          <input type="password" name="password" required>
          <label>Password</label>
        </div>
        <div class="remember">
          <label><input type="checkbox" name="remember"> Remember me</label>
        </div>
        <button type="submit" name="login">Login</button>
        <div class="signUp-link">
          <p>Bạn là Nhân Viên? <a href="#" class="signInBtn-link">Nhân Viên</a></p>
        </div>
      </form>
    </div>
  </div>

  <script>
    const signInBtnLink = document.querySelector('.signInBtn-link');
    const signUpBtnLink = document.querySelector('.signUpBtn-link');
    const wrapper = document.querySelector('.wrapper');
    
    signUpBtnLink.addEventListener('click', () => {
        wrapper.classList.toggle('active');
    });
    
    signInBtnLink.addEventListener('click', () => {
        wrapper.classList.toggle('active');
    });
  </script>
</body>
</html>

