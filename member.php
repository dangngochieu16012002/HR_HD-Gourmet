<?php
include('includes/config.php');

if (!isset($_SESSION['user_id'])) {
    header("Location: index.php");
    exit();
}
// Thêm tài khoản mới
if (isset($_POST['add_account'])) {
    $member_id = $_POST['member_id'];
    $email = $_POST['email'];
    $password = md5($_POST['password']);  // Mã hóa bằng MD5

    $check = mysqli_query($conn, "SELECT * FROM member_accounts WHERE member_id = '$member_id'");
    if (mysqli_num_rows($check) > 0) {
        $message = "Nhân viên này đã có tài khoản!";
    } else {
        $sql = "INSERT INTO member_accounts (member_id, email, password) VALUES ('$member_id', '$email', '$password')";
        if (mysqli_query($conn, $sql)) {
            $message = "Tài khoản đã được thêm thành công!";
        } else {
            $message = "Lỗi: " . mysqli_error($conn);
        }
    }
}

if (isset($_POST['edit_account'])) {
    $account_id = $_POST['account_id'];
    $email = $_POST['email'];
    ?>
    <form action="" method="POST">
        <input type="hidden" name="account_id" value="<?= $account_id ?>">
        <div class="form-group">
            <label for="email">Email Mới</label>
            <input type="email" name="new_email" class="form-control" value="<?= $email ?>" required>
        </div>
        <div class="form-group">
            <label for="password">Mật Khẩu Mới</label>
            <input type="password" name="new_password" class="form-control" required>
        </div>
        <button type="submit" name="update_account" class="btn btn-primary">Cập Nhật</button>
    </form>
    <?php
}

//   Cập nhật tài khoản
if (isset($_POST['update_account'])) {
    $account_id = $_POST['account_id'];
    $new_email = $_POST['new_email'];
    $new_password = md5($_POST['new_password']);  // Mã hóa bằng MD5

    $sql = "UPDATE member_accounts SET email='$new_email', password='$new_password' WHERE id='$account_id'";
    if (mysqli_query($conn, $sql)) {
        $message = "Tài khoản đã được cập nhật thành công!";
    } else {
        $message = "Lỗi: " . mysqli_error($conn);
    }
}

// Xóa tài khoản
if (isset($_POST['delete_account'])) {
    $account_id = $_POST['account_id'];

    $sql = "DELETE FROM member_accounts WHERE id='$account_id'";
    if (mysqli_query($conn, $sql)) {
        $message = "Tài khoản đã được xóa thành công!";
    } else {
        $message = "Lỗi: " . mysqli_error($conn);
    }
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
                <h3 class="card-title"><i class="fas fa-keyboard"></i>Thêm Nhân Viên </h3>
              </div>
                    <div class="card-body">
                        <form action="" method="POST">
                            <div class="form-group">
                                <label for="member_id">Chọn Nhân Viên</label>
                                <select name="member_id" class="form-control" required>
                                    <option value="">-- Chọn Nhân Viên --</option>
                                    <?php
                                    $result = mysqli_query($conn, "SELECT id, fullname FROM members");
                                    while ($row = mysqli_fetch_assoc($result)) {
                                        echo "<option value='{$row['id']}'>{$row['fullname']}</option>";
                                    }
                                    ?>
                                </select>
                            </div>

                            <div class="form-group">
                                <label for="email">Email</label>
                                <input type="email" name="email" class="form-control" required>
                            </div>

                            <div class="form-group">
                                <label for="password">Mật Khẩu</label>
                                <input type="password" name="password" class="form-control" required>
                            </div>

                            <button type="submit" name="add_account" class="btn btn-success">Thêm Tài Khoản</button>
                        </form>

                        <!-- Hiển thị thông báo -->
                        <?php if (isset($message)) : ?>
                            <div class="alert alert-info mt-3"><?= $message; ?></div>
                        <?php endif; ?>

                        <!-- Hiển thị danh sách tài khoản -->
                        <h3 class="mt-4">Danh Sách Tài Khoản</h3>
                        <table class="table table-bordered">
                            <thead>
                                <tr>
                                    <th>ID</th>
                                    <th>Tên Nhân Viên</th>
                                    <th>Email</th>
                                    <th>Lần Đăng Nhập Cuối</th>
                                    <th>Hành Động</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php
                                $accounts = mysqli_query($conn, "SELECT a.id, m.fullname, a.email, a.last_login 
                                                                FROM member_accounts a 
                                                                JOIN members m ON a.member_id = m.id");
                                while ($account = mysqli_fetch_assoc($accounts)) {
                                    echo "<tr>
                                            <td>{$account['id']}</td>
                                            <td>{$account['fullname']}</td>
                                            <td>{$account['email']}</td>
                                            <td>{$account['last_login']}</td>
                                            <td>
                                                <form action='' method='POST' style='display:inline-block;'>
                                                    <input type='hidden' name='account_id' value='{$account['id']}'>
                                                    <button type='submit' name='delete_account' class='btn btn-danger btn-sm'>Xóa</button>
                                                </form>
                                            </td>
                                        </tr>";
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

  <aside class="control-sidebar control-sidebar-dark">
  </aside>

  <footer class="main-footer">
    <strong> &copy; <?php echo date('Y');?> HD Gourmet</a> -</strong>
    Bản Quyền Thuộc Về.
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
