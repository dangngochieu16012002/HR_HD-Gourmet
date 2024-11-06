<?php 
// Kết nối đến cơ sở dữ liệu và kiểm tra phiên đăng nhập
include('includes/config.php');

if (!isset($_SESSION['user_id'])) {
    header("Location: index.php");
    exit();
}

$response = array('success' => false, 'message' => '');

// Lấy danh sách các loại thành viên/phòng ban
$membershipTypesQuery = "SELECT id, type, amount, employee_count, total_employees FROM membership_types";
$membershipTypesResult = $conn->query($membershipTypesQuery);

// Hàm tạo tên tệp tin duy nhất
function generateUniqueFileName($originalName) {
    $timestamp = time();
    $extension = pathinfo($originalName, PATHINFO_EXTENSION);
    return $timestamp . '_' . uniqid() . '.' . $extension;
}

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // Lấy dữ liệu từ biểu mẫu
    $fullname = $_POST['fullname'];
    $dob = $_POST['dob'];
    $gender = $_POST['gender'];
    $contactNumber = $_POST['contactNumber'];
    $email = $_POST['email'];
    $address = $_POST['address'];
    $country = $_POST['country'];
    $postcode = $_POST['postcode'];
    $occupation = $_POST['occupation'];
    $membershipType = $_POST['membershipType'];

    // Sinh số thẻ thành viên
    $membershipNumber = 'CA-' . str_pad(mt_rand(1, 999999), 6, '0', STR_PAD_LEFT);

    // Tạo ngày hết hạn thẻ (ví dụ: 1 năm kể từ ngày tạo)
    $expiryDate = date('Y-m-d', strtotime('+1 year'));

    // Xử lý ảnh nhân viên
    $uniquePhotoName = !empty($_FILES['photo']['name']) 
        ? generateUniqueFileName($_FILES['photo']['name']) 
        : 'default.jpg';
    if (!empty($_FILES['photo']['name'])) {
        move_uploaded_file($_FILES['photo']['tmp_name'], 'uploads/member_photos/' . $uniquePhotoName);
    }

    // Xử lý tệp hợp đồng
    $uniqueContractName = !empty($_FILES['contract']['name']) 
        ? generateUniqueFileName($_FILES['contract']['name']) 
        : 'default_contract.pdf';
    if (!empty($_FILES['contract']['name'])) {
        move_uploaded_file($_FILES['contract']['tmp_name'], 'uploads/contracts/' . $uniqueContractName);
    }

    // Kiểm tra thông tin phòng ban
    $checkQuery = "SELECT amount, employee_count, total_employees FROM membership_types WHERE id = $membershipType";
    $checkResult = $conn->query($checkQuery);
    $room = $checkResult->fetch_assoc();

    $amount = $room['amount'];
    $currentEmployeeCount = $room['employee_count'];

    // Kiểm tra giới hạn nhân viên
    if ($currentEmployeeCount >= $room['total_employees']) {
        $response['message'] = 'Không thể thêm nhân viên vì phòng ban đã đạt giới hạn.';
    } else {
        // Thêm nhân viên vào bảng members, bao gồm `expiry_date`
        $insertQuery = "INSERT INTO members (fullname, dob, gender, contact_number, email, address, country, postcode, occupation, 
                        membership_type, membership_number, amount, department_employee_count, photo, contract, created_at, expiry_date) 
                        VALUES ('$fullname', '$dob', '$gender', '$contactNumber', '$email', '$address', '$country', '$postcode', 
                                '$occupation', '$membershipType', '$membershipNumber', $amount, $currentEmployeeCount, 
                                '$uniquePhotoName', '$uniqueContractName', NOW(), '$expiryDate')";

                        if ($conn->query($insertQuery) === TRUE) {
                            // Nếu nghề nghiệp là Supervisor, cập nhật department_head với tên người đó
                            if ($occupation === 'Supervisor') {
                                $updateHeadQuery = "UPDATE membership_types 
                                                    SET department_head = '$fullname' 
                                                    WHERE id = $membershipType";
                                $conn->query($updateHeadQuery);
                            }

                            // Cập nhật số lượng nhân viên trong phòng ban
                            $updateCountQuery = "UPDATE membership_types 
                                                SET employee_count = employee_count + 1 
                                                WHERE id = $membershipType";
                            $conn->query($updateCountQuery);

                            $response['success'] = true;
                            $response['message'] = 'Thêm nhân viên thành công! Số thẻ thành viên: ' . $membershipNumber;
                        } else {
                            $response['message'] = 'Lỗi: ' . $conn->error;
                        }
    }
}
?>


<!-- Giao diện thêm nhân viên -->
<?php include('includes/header.php');?>

<body class="hold-transition sidebar-mini layout-fixed layout-navbar-fixed layout-footer-fixed">
<div class="wrapper">
    <?php include('includes/nav.php'); ?>
    <?php include('includes/sidebar.php'); ?>

    <div class="content-wrapper">
        <?php include('includes/pagetitle.php'); ?>

        <section class="content">
            <div class="container-fluid">
                <div class="row">
                    <div class="col-md-12">
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

                        <div class="card card-primary">
                            <div class="card-header">
                                <h3 class="card-title"><i class="fas fa-keyboard"></i> Thêm Nhân Viên</h3>
                            </div>

                            <form method="post" action="" enctype="multipart/form-data">
                                <div class="card-body">
                                    <div class="row">
                                        <div class="col-sm-6">
                                            <label for="fullname">Họ Tên</label>
                                            <input type="text" class="form-control" id="fullname" name="fullname" placeholder="Nhập họ tên" required>
                                        </div>
                                        <div class="col-sm-3">
                                            <label for="dob">Ngày Sinh</label>
                                            <input type="date" class="form-control" id="dob" name="dob" required>
                                        </div>
                                        <div class="col-sm-3">
                                            <label for="gender">Giới Tính</label>
                                            <select class="form-control" id="gender" name="gender" required>
                                                <option value="Male">Nam</option>
                                                <option value="Female">Nữ</option>
                                                <option value="Other">Khác</option>
                                            </select>
                                        </div>
                                    </div>

                                    <div class="row mt-3">
                                        <div class="col-sm-6">
                                            <label for="contactNumber">Số Điện Thoại</label>
                                            <input type="tel" class="form-control" id="contactNumber" name="contactNumber" placeholder="Nhập số điện thoại" required>
                                        </div>
                                        <div class="col-sm-6">
                                            <label for="email">Email</label>
                                            <input type="email" class="form-control" id="email" name="email" placeholder="Nhập email" required>
                                        </div>
                                    </div>

                                    <div class="row mt-3">
                                        <div class="col-sm-6">
                                            <label for="address">Địa Chỉ</label>
                                            <input type="text" class="form-control" id="address" name="address" placeholder="Nhập địa chỉ" required>
                                        </div>
                                        <div class="col-sm-6">
                                            <label for="country">Quốc Gia</label>
                                            <input type="text" class="form-control" id="country" name="country" placeholder="Nhập quốc gia" required>
                                        </div>
                                    </div>

                                    <div class="row mt-3">
                                        <div class="col-sm-6">
                                            <label for="postcode">Mã Bưu Chính</label>
                                            <input type="text" class="form-control" id="postcode" name="postcode" placeholder="Nhập mã bưu chính" required>
                                        </div>
                                        <div class="col-sm-6">
                                            <label for="occupation">Nghề Nghiệp</label>
                                            <select class="form-control" id="occupation" name="occupation" required>
                                                <option value="Staff">Nhân Viên</option>
                                                <option value="Supervisor">Trưởng Phòng</option>
                                            </select>
                                        </div>

                                    </div>
                                    
                                    <div class="row mt-3">
                                        <div class="col-sm-6">
                                            <label for="membershipType">Phòng Ban</label>
                                            <select class="form-control" id="membershipType" name="membershipType" required>
                                                <?php
                                                if ($membershipTypesResult) {
                                                    while ($row = $membershipTypesResult->fetch_assoc()) {
                                                        echo "<option value='{$row['id']}' data-amount='{$row['amount']}'>{$row['type']}</option>";
                                                    }
                                                } else {
                                                    echo "Lỗi: " . $conn->error;
                                                }
                                                ?>
                                            </select>
                                        </div>

                                        <div class="col-sm-6">
                                            <label for="amount">Lương</label>
                                            <input type="text" class="form-control" id="amount" name="amount" readonly>
                                        </div>

                                        <div class="col-sm-6">
                                            <label for="photo">Ảnh Nhân Viên</label>
                                            <input type="file" class="form-control-file" id="photo" name="photo">
                                        </div>
                                    </div>

                                    <div class="row mt-3">
                                        <div class="col-sm-6">
                                            <label for="contract">Tải Hợp Đồng</label>
                                            <input type="file" class="form-control-file" id="contract" name="contract">
                                        </div>
                                        <div class="col-sm-6">
                                            <label for="expiry_date">Ngày Hết Hạn</label>
                                            <input type="date" class="form-control" id="expiry_date" name="expiry_date" required>
                                        </div>
                                    </div>
                                    
                                </div>

                                <div class="card-footer">
                                    <button type="submit" class="btn btn-primary">Hoàn Thành</button>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>
            </div>
        </section>
    </div>

    <?php include('includes/footer.php'); ?>
</div>
</body>
</html>
<script>
document.getElementById('membershipType').addEventListener('change', function () {
    const selectedOption = this.options[this.selectedIndex];
    const amount = selectedOption.getAttribute('data-amount');
    document.getElementById('amount').value = amount;
});
</script>