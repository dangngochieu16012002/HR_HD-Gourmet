<?php
include('includes/config.php');

if (!isset($_SESSION['user_id'])) {
    header("Location: index.php");
    exit();
}

$response = array('success' => false, 'message' => '');

$membershipTypesQuery = "SELECT id, type, amount FROM membership_types";
$membershipTypesResult = $conn->query($membershipTypesQuery);

if (isset($_GET['id'])) {
    $memberId = $_GET['id'];

    $fetchMemberQuery = "SELECT * FROM members WHERE id = $memberId";
    $fetchMemberResult = $conn->query($fetchMemberQuery);

    if ($fetchMemberResult->num_rows > 0) {
        $memberDetails = $fetchMemberResult->fetch_assoc();
    } else {
        header("Location: members_list.php");
        exit();
    }
}

function generateUniqueFileName($filename)
{
    $ext = pathinfo($filename, PATHINFO_EXTENSION);
    $basename = pathinfo($filename, PATHINFO_FILENAME);
    $uniqueName = $basename . '_' . time() . '.' . $ext;
    return $uniqueName;
}

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $fullname = $_POST['fullname'];
    $dob = $_POST['dob'];
    $gender = $_POST['gender'];
    $contactNumber = $_POST['contactNumber'];
    $email = $_POST['email'];
    $address = $_POST['address'];
    $country = $_POST['country'];
    $postcode = $_POST['postcode'];
    $occupation = $_POST['occupation'];
    
    // Thêm các trường mới ở đây
    $membershipType = $_POST['membership_type'];
    $amount = $_POST['amount'];
    $departmentEmployeeCount = $_POST['department_employee_count'];
    $membershipNumber = $_POST['membership_number'];
    $expiryDate = $_POST['expiry_date'];
    $contract = $_POST['contract'];
    $annualLeaveRemaining = $_POST['annual_leave_remaining'];

    $photoUpdate = "";
    $uploadedPhoto = $_FILES['photo'];

    if (!empty($uploadedPhoto['name'])) {
        $uniquePhotoName = generateUniqueFileName($uploadedPhoto['name']);
        move_uploaded_file($uploadedPhoto['tmp_name'], 'uploads/member_photos/' . $uniquePhotoName);
        $photoUpdate = ", photo='$uniquePhotoName'";
    }

    $updateQuery = "UPDATE members SET fullname='$fullname', dob='$dob', gender='$gender', 
                    contact_number='$contactNumber', email='$email', address='$address', country='$country', 
                    postcode='$postcode', occupation='$occupation', membership_type='$membershipType', 
                    amount='$amount', department_employee_count='$departmentEmployeeCount', 
                    membership_number='$membershipNumber', expiry_date='$expiryDate', 
                    contract='$contract', annual_leave_remaining='$annualLeaveRemaining' $photoUpdate
                    WHERE id = $memberId";

    if ($conn->query($updateQuery) === TRUE) {
        $response['success'] = true;
        $response['message'] = 'Member updated successfully!';
        
        header("Location: manage_members.php");
        exit();
    } else {
        $response['message'] = 'Error: ' . $conn->error;
    }
}

if (isset($_POST['add_account'])) {
    $member_id = $_POST['member_id'];
    $email = $_POST['email'];
    $password = password_hash($_POST['password'], PASSWORD_BCRYPT);

    // Kiểm tra nếu nhân viên đã có tài khoản
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

if (isset($_POST['update_account'])) {
    $member_id = $_POST['member_id'];
    $email = $_POST['email'];
    $password = password_hash($_POST['password'], PASSWORD_BCRYPT);

    $sql = "UPDATE member_accounts SET email='$email', password='$password' WHERE member_id='$member_id'";
    if (mysqli_query($conn, $sql)) {
        $message = "Tài khoản đã được cập nhật thành công!";
    } else {
        $message = "Lỗi: " . mysqli_error($conn);
    }
}

if (isset($_POST['delete_account'])) {
    $member_id = $_POST['member_id'];

    $sql = "DELETE FROM member_accounts WHERE member_id='$member_id'";
    if (mysqli_query($conn, $sql)) {
        $message = "Tài khoản đã được xóa thành công!";
    } else {
        $message = "Lỗi: " . mysqli_error($conn);
    }
}
?>

<?php include('includes/header.php'); ?>

<body class="hold-transition sidebar-mini layout-fixed layout-navbar-fixed layout-footer-fixed">
<div class="wrapper">
    <?php include('includes/nav.php'); ?>

    <?php include('includes/sidebar.php'); ?>

    <!-- Content Wrapper. Contains page content -->
    <div class="content-wrapper">
        <?php include('includes/pagetitle.php'); ?>

        <!-- Main content -->
        <section class="content">
            <div class="container-fluid">
                <!-- Info boxes -->
                <div class="row">
                    <!-- left column -->
                    <div class="col-md-12">

                        <?php if ($response['success']): ?>
                            <div class="alert alert-success alert-dismissible">
                                <button type="button" class="close" data-dismiss="alert" aria-hidden="true">&times;</button>
                                <h5><i class="icon fas fa-check"></i> Success</h5>
                                <?php echo $response['message']; ?>
                            </div>
                        <?php elseif (!empty($response['message'])): ?>
                            <div class="alert alert-danger alert-dismissible">
                                <button type="button" class="close" data-dismiss="alert" aria-hidden="true">&times;</button>
                                <h5><i class="icon fas fa-ban"></i> Error</h5>
                                <?php echo $response['message']; ?>
                            </div>
                        <?php endif; ?>

                        <!-- general form elements -->
                        <div class="card card-primary">
                            <div class="card-header">
                                <h3 class="card-title"><i class="fas fa-keyboard"></i> Chỉnh Sửa Nhân Viên</h3>
                            </div>
                            <!-- /.card-header -->
                            <!-- form start -->
                            <form method="post" action="" enctype="multipart/form-data">
                            <input type="hidden" name="member_id" value="<?php echo $memberId; ?>">
                                <div class="card-body">
                                    <div class="row">
                                        <div class="col-sm-6">
                                            <label for="fullname">Họ Tên</label>
                                            <input type="text" class="form-control" id="fullname" name="fullname"
                                                placeholder="Enter full name" required value="<?php echo $memberDetails['fullname']; ?>">
                                        </div>
                                        <div class="col-sm-3">
                                            <label for="dob">Ngày Sinh</label>
                                            <input type="date" class="form-control" id="dob" name="dob" value="<?php echo $memberDetails['dob']; ?>" required>
                                        </div>
                                        
                                        <div class="col-sm-3">
                                            <label for="gender">Giới Tính</label>
                                            <select class="form-control" id="gender" name="gender" required>
                                                <option value="Male" <?php echo ($memberDetails['gender'] == 'Male') ? 'selected' : ''; ?>>Nam</option>
                                                <option value="Female" <?php echo ($memberDetails['gender'] == 'Female') ? 'selected' : ''; ?>>Nữ</option>
                                                <option value="Other" <?php echo ($memberDetails['gender'] == 'Other') ? 'selected' : ''; ?>>Khác</option>
                                            </select>
                                        </div>
                                    </div>

                                    <div class="row mt-3">
                                        <div class="col-sm-6">
                                            <label for="contactNumber">Phone</label>
                                            <input type="tel" class="form-control" id="contactNumber"
                                                   name="contactNumber" placeholder="Enter contact number" value="<?php echo $memberDetails['contact_number']; ?>" required>
                                        </div>
                                        <div class="col-sm-6">
                                            <label for="email">Email</label>
                                            <input type="email" class="form-control" id="email" name="email"
                                                   placeholder="Enter email" value="<?php echo $memberDetails['email']; ?>" required>
                                        </div>
                                    </div>

                                    <div class="row mt-3">
                                        <div class="col-sm-6">
                                            <label for="address">Địa Chỉ</label>
                                            <input type="text" class="form-control" id="address" name="address"
                                                   placeholder="Enter address" value="<?php echo $memberDetails['address']; ?>" required>
                                        </div>
                                        <div class="col-sm-6">
                                            <label for="country">Quốc Gia</label>
                                            <input type="text" class="form-control" id="country" name="country"
                                                   placeholder="Enter country" value="<?php echo $memberDetails['country']; ?>" required>
                                        </div>
                                    </div>

                                    <div class="row mt-3">
                                        <div class="col-sm-6">
                                            <label for="postcode">Mã Bưu Chính</label>
                                            <input type="text" class="form-control" id="postcode" name="postcode"
                                                   placeholder="Enter postcode" value="<?php echo $memberDetails['postcode']; ?>" required>
                                        </div>
                                        <div class="col-sm-6">
                                            <label for="occupation">Nghề Nghiệp</label>
                                            <input type="text" class="form-control" id="occupation" name="occupation"
                                                   placeholder="Enter occupation" value="<?php echo $memberDetails['occupation']; ?>" required>
                                        </div>
                                    </div>

                                    <div class="row mt-3">
                                        <div class="col-sm-6">
                                            <label for="membership_type">Loại Thẻ</label>
                                            <select class="form-control" id="membership_type" name="membership_type" required>
                                                <?php while ($membershipType = $membershipTypesResult->fetch_assoc()): ?>
                                                    <option value="<?php echo $membershipType['id']; ?>" <?php echo ($membershipType['id'] == $memberDetails['membership_type']) ? 'selected' : ''; ?>>
                                                        <?php echo $membershipType['type']; ?>
                                                    </option>
                                                <?php endwhile; ?>
                                            </select>
                                        </div>
                                        <div class="col-sm-6">
                                            <label for="amount">Lương cơ bản/ Tháng</label>
                                            <input type="number" class="form-control" id="amount" name="amount"
                                                   placeholder="Enter amount" value="<?php echo $memberDetails['amount']; ?>" required>
                                        </div>
                                    </div>

                                    <div class="row mt-3">
                                        <div class="col-sm-6">
                                            <label for="membership_number">Số Thẻ</label>
                                            <input type="text" class="form-control" id="membership_number" name="membership_number"
                                                   placeholder="Enter membership number" value="<?php echo $memberDetails['membership_number']; ?>" required>
                                        </div>
                                    </div>


                                    <div class="row mt-3">
                                        <div class="col-sm-6">
                                            <label for="photo">Hình Ảnh</label>
                                            <input type="file" class="form-control" id="photo" name="photo" accept="image/*">
                                        </div>
                                    </div>
                                </div>
                                <!-- /.card-body -->

                                <div class="card-footer">
                                    <button type="submit" class="btn btn-primary">Cập Nhật</button>
                                    <a href="manage_members.php" class="btn btn-danger">Hủy Bỏ</a>
                                </div>
                            </form>
                        </div>
                        <!-- /.card -->
                    </div>
                    <!--/.col (left) -->
                </div>
                <!-- /.row -->
            </div><!-- /.container-fluid -->
        </section>
        <!-- /.content -->
    </div>
    <!-- /.content-wrapper -->

    <?php include('includes/footer.php'); ?>
</div>
<!-- ./wrapper -->

<!-- jQuery -->
<script src="plugins/jquery/jquery.min.js"></script>
<!-- Bootstrap 4 -->
<script src="plugins/bootstrap/js/bootstrap.bundle.min.js"></script>
<!-- AdminLTE App -->
<script src="dist/js/adminlte.min.js"></script>
</body>
</html>
