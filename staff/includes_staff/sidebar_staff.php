<?php
// Kết nối cơ sở dữ liệu
include('../includes/config.php');

// Lấy trang hiện tại
$current_page = basename($_SERVER['PHP_SELF']);

// Lấy thông tin nhân viên từ session
if (!isset($_SESSION['user_type']) || $_SESSION['user_type'] !== 'staff') {
    header("Location: index.php");
    exit();
}

// Lấy ID nhân viên từ session
$staff_id = $_SESSION['user_id'];

// Lấy tên đầy đủ từ bảng members
$memberQuery = "SELECT fullname FROM members WHERE id = ?";
$stmt = $conn->prepare($memberQuery);
$stmt->bind_param("i", $staff_id);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows > 0) {
    $fullname = $result->fetch_assoc()['fullname'];
} else {
    $fullname = 'Staff Member';
}

// Lấy số loại thành viên
$countQuery = "SELECT COUNT(*) as total_types FROM membership_types";
$countResult = $conn->query($countQuery);

if ($countResult && $countResult->num_rows > 0) {
    $totalCount = $countResult->fetch_assoc()['total_types'];
} else {
    $totalCount = 0;
}
?>

<!-- Main Sidebar Container -->
<aside class="main-sidebar sidebar-dark-primary elevation-4">
    <!-- Brand Logo -->
    <a href="" class="brand-link">
        <img src="<?php echo getLogoUrl(); ?>" alt="Logo" class="brand-image img-circle elevation-3" style="opacity: .8">
        <span class="brand-text font-weight-light"><?php echo getSystemName(); ?></span>
    </a>

    <!-- Lấy tên hệ thống và logo -->
    <?php
    function getSystemName() {
        global $conn;
        $systemNameQuery = "SELECT system_name FROM settings";
        $systemNameResult = $conn->query($systemNameQuery);

        if ($systemNameResult->num_rows > 0) {
            return $systemNameResult->fetch_assoc()['system_name'];
        } else {
            return 'HD-Gourmet System';
        }
    }

    function getLogoUrl() {
        global $conn;
        $logoQuery = "SELECT logo FROM settings";
        $logoResult = $conn->query($logoQuery);

        if ($logoResult->num_rows > 0) {
            return $logoResult->fetch_assoc()['logo'];
        } else {
            return '../dist/img/AdminLTELogo.png';
        }
    }
    ?>

    <!-- Sidebar -->
    <div class="sidebar">
        <!-- Sidebar user panel -->
        <div class="user-panel mt-3 pb-3 mb-3 d-flex">
            <div class="image">
                <img src="../dist/img/2382414.png" class="img-circle elevation-2" alt="User Image">
            </div>
            <div class="info">
                <a href="#" class="d-block"><?php echo htmlspecialchars($fullname); ?></a>
            </div>
        </div>

        <!-- Sidebar Menu -->
        <nav class="mt-2">
            <ul class="nav nav-pills nav-sidebar flex-column" data-widget="treeview" role="menu" data-accordion="false">
                <!-- Trang Dashboard -->
                <li class="nav-item">
                    <a href="staff_dashboard.php" class="nav-link <?php echo ($current_page == 'staff_dashboard.php') ? 'active' : ''; ?>">
                        <i class="nav-icon fas fa-tachometer-alt"></i>
                        <p>Bảng Điều Khiển</p>
                    </a>
                </li>

                <!-- Lịch - Chấm Công -->
                <li class="nav-item">
                    <a href="lichlamnv.php" class="nav-link <?php echo ($current_page == 'lichlamnv.php') ? 'active' : ''; ?>">
                        <i class="nav-icon fas fa-clock"></i>
                        <p>Lịch - Chấm Công</p>
                    </a>
                </li>

                <!-- Thông tin cá nhân -->
                <li class="nav-item">
                    <a href="employee_information.php" class="nav-link <?php echo ($current_page == 'employee_information.php') ? 'active' : ''; ?>">
                        <i class="nav-icon fas fa-calendar-alt"></i>
                        <p>Thông tin cá nhân</p>
                    </a>
                </li>

                <!-- Bảng lương -->
                <li class="nav-item">
                    <a href="payroll.php" class="nav-link <?php echo ($current_page == 'payroll.php') ? 'active' : ''; ?>">
                        <i class="nav-icon fas fa-user-plus"></i>
                        <p>Bảng lương</p>
                    </a>
                </li>

                <!-- Đánh giá hiệu suất -->
                <li class="nav-item">
                    <a href="performance_evaluation.php" class="nav-link <?php echo ($current_page == 'performance_evaluation.php') ? 'active' : ''; ?>">
                        <i class="nav-icon fas fa-users-cog"></i>
                        <p>Đánh giá hiệu suất</p>
                    </a>
                </li>
                <!-- Thông tin bảo hiểm -->
                <li class="nav-item">
                    <a href="social_insurance.php" class="nav-link <?php echo ($current_page == 'social_insurance.php') ? 'active' : ''; ?>">
                        <i class="nav-icon fas fa-users-cog"></i>
                        <p>Thông tin BHXH</p>
                    </a>
                </li>
                <!-- Cài Đặt -->
                <li class="nav-item">
                    <a href="settings_staff.php" class="nav-link <?php echo ($current_page == 'settings_staff.php') ? 'active' : ''; ?>">
                        <i class="nav-icon fas fa-cogs"></i>
                        <p>Cài Đặt</p>
                    </a>
                </li>

                <!-- Đăng Xuất -->
                <li class="nav-item">
    <a href="logout_staff.php" class="nav-link <?php echo ($current_page == 'logout_staff.php') ? 'active' : ''; ?>" onclick="return confirmLogout();">
        <i class="nav-icon fas fa-power-off"></i>
        <p>Đăng Xuất</p>
    </a>
</li>

<script>
function confirmLogout() {
    return confirm("Bạn có chắc chắn muốn đăng xuất không?");
}
</script>

            </ul>
        </nav>
    </div>
</aside>
