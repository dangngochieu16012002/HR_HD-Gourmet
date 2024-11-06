<!-- Content Header (Page header) -->
<div class="content-header">
    <div class="container-fluid">
        <div class="row mb-2">
            <div class="col-sm-6">
                <?php
                // Kiểm tra session để xác nhận nhân viên đã đăng nhập
                if (!isset($_SESSION['user_type']) || $_SESSION['user_type'] !== 'staff') {
                    header("Location: index.php");
                    exit();
                }

                // Xác định tiêu đề trang dựa trên URI
                if (strpos($_SERVER['REQUEST_URI'], 'staff_dashboard.php') !== false) {
                    $pageTitle = 'Bảng Điều Khiển';
                } elseif (strpos($_SERVER['REQUEST_URI'], 'lichlamnv.php') !== false) {
                    $pageTitle = 'Lịch - Chấm Công';
                } elseif (strpos($_SERVER['REQUEST_URI'], 'lich.php') !== false) {
                    $pageTitle = 'Quản Lý Lịch Làm';    
                } elseif (strpos($_SERVER['REQUEST_URI'], 'add_members.php') !== false) {
                    $pageTitle = 'Thêm Thành Viên';
                } elseif (strpos($_SERVER['REQUEST_URI'], 'manage_members.php') !== false) {
                    $pageTitle = 'Quản Lý Thành Viên';
                } elseif (strpos($_SERVER['REQUEST_URI'], 'report.php') !== false) {
                    $pageTitle = 'Báo Cáo Thành Viên';
                } elseif (strpos($_SERVER['REQUEST_URI'], 'settings.php') !== false) {
                    $pageTitle = 'Cài Đặt';
                } else {
                    $pageTitle = 'Trang Không Xác Định';
                }

                echo '<h1 class="m-0 text-dark">' . htmlspecialchars($pageTitle) . '</h1>';
                ?>
            </div><!-- /.col -->
            <div class="col-sm-6">
                <ol class="breadcrumb float-sm-right">
                    <li class="breadcrumb-item"><a href="staff_dashboard.php">Trang Chủ</a></li>
                    <li class="breadcrumb-item active"><?php echo htmlspecialchars($pageTitle); ?></li>
                </ol>
            </div><!-- /.col -->
        </div><!-- /.row -->
    </div><!-- /.container-fluid -->
</div>
<!-- /.content-header -->
