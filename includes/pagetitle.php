<!-- Content Header (Page header) -->
<div class="content-header">
    <div class="container-fluid">
        <div class="row mb-2">
            <div class="col-sm-6">
                <?php
                
                if (strpos($_SERVER['REQUEST_URI'], 'add_members.php') !== false) {
                    $pageTitle = 'Thêm Nhân Viên';
                } elseif (strpos($_SERVER['REQUEST_URI'], 'view_type.php') !== false) {
                    $pageTitle = 'Quản Lý Phòng';
                } elseif (strpos($_SERVER['REQUEST_URI'], 'lich.php') !== false) {
                    $pageTitle = 'Quản Lý Lịch Làm';    
                }
                elseif (strpos($_SERVER['REQUEST_URI'], 'chamcong.php') !== false) {
                  $pageTitle = 'Dữ liệu chấm công';    
                } elseif (strpos($_SERVER['REQUEST_URI'], 'renew.php') !== false) {
                    $pageTitle = 'Gia Hạn Hợp Đồng ';
                } elseif (strpos($_SERVER['REQUEST_URI'], 'edit_member.php') !== false) {
                  $pageTitle = 'Chỉnh Sửa Nhân Viên';
                } elseif (strpos($_SERVER['REQUEST_URI'], 'edit_type.php') !== false) {
                  $pageTitle = 'Chỉnh Sửa Phòng';
                } elseif (strpos($_SERVER['REQUEST_URI'], 'list_renewal.php') !== false) {
                  $pageTitle = 'Renewal';
                } elseif (strpos($_SERVER['REQUEST_URI'], 'manage_members.php') !== false) {
                  $pageTitle = 'Quản lý nhân viên';
                } elseif (strpos($_SERVER['REQUEST_URI'], 'memberProfile.php') !== false) {
                  $pageTitle = 'Hồ sơ nhân viên';
                } elseif (strpos($_SERVER['REQUEST_URI'], 'print_membership_card.php') !== false) {
                  $pageTitle = 'Thẻ Nhân Viên';
                } elseif (strpos($_SERVER['REQUEST_URI'], 'revenue_report.php') !== false) {
                  $pageTitle = 'KPI';
                } elseif (strpos($_SERVER['REQUEST_URI'], 'report.php') !== false) {
                  $pageTitle = 'Bảng Lương';
                } elseif (strpos($_SERVER['REQUEST_URI'], 'settings.php') !== false) {
                  $pageTitle = 'Cài Đặt';
                } elseif (strpos($_SERVER['REQUEST_URI'], 'add_type.php') !== false) {
                  $pageTitle = 'Thêm Phòng';
                } elseif (strpos($_SERVER['REQUEST_URI'], 'member.php') !== false) {
                  $pageTitle = 'Thêm Tài Khoản';
                } elseif (strpos($_SERVER['REQUEST_URI'], 'dashboard.php') !== false) {
                  $pageTitle = 'Bảng Điều Khiển';
                }
                
                echo '<h1 class="m-0 text-dark">' . $pageTitle . '</h1>';
                ?>
            </div><!-- /.col -->
            <div class="col-sm-6">
                <ol class="breadcrumb float-sm-right">
                    <li class="breadcrumb-item"><a href="#">Trang Chủ</a></li>
                    <li class="breadcrumb-item active"><?php echo $pageTitle; ?></li>
                </ol>
            </div><!-- /.col -->
        </div><!-- /.row -->
    </div><!-- /.container-fluid -->
</div>
<!-- /.content-header -->
