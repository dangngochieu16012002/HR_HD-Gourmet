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

// Truy vấn để lấy thông tin số ngày nghỉ và số giờ nghỉ CD
$userInfoQuery = $conn->prepare("SELECT annual_leave_remaining, cd_hours_remaining FROM members WHERE id = ?");
$userInfoQuery->bind_param("i", $staff_id);
$userInfoQuery->execute();
$userInfoResult = $userInfoQuery->get_result();
$userData = $userInfoResult->fetch_assoc();
$annualLeaveRemaining = $userData['annual_leave_remaining'];
$cdHoursRemaining = $userData['cd_hours_remaining'];

// Truy vấn để lấy thông tin các yêu cầu nghỉ phép của nhân viên
$requestQuery = $conn->prepare("SELECT * FROM leave_requests WHERE employee_id = ? ORDER BY created_at DESC");
$requestQuery->bind_param("i", $staff_id);
$requestQuery->execute();
$requestResult = $requestQuery->get_result();

include('includes_staff/header_staff.php');
?>

<!-- HTML content cho trang xem yêu cầu nghỉ phép -->
<body class="hold-transition sidebar-mini layout-fixed layout-navbar-fixed layout-footer-fixed">
<div class="wrapper">
    <?php include('includes_staff/nav_staff.php'); ?>
    <?php include('includes_staff/sidebar_staff.php'); ?>

    <!-- Content Wrapper -->
    <link href="https://cdn.jsdelivr.net/npm/tailwindcss@2.2.19/dist/tailwind.min.css" rel="stylesheet">

<div class="content-wrapper min-h-screen bg-gray-50">
    <section class="content py-10">
        <div class="container mx-auto px-4">
            <h2 class="text-3xl font-bold text-blue-700 mb-8 text-center">Thông Tin Yêu Cầu Nghỉ Phép</h2>
            
            <!-- Nút tạo phép -->
            <div class="text-right mb-6">
                <button onclick="toggleForm()" class="bg-blue-600 text-white p-3 rounded-full shadow-lg hover:bg-blue-700 focus:ring-4 focus:ring-blue-400 transition duration-300 ease-in-out">
                    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor" class="w-6 h-6 inline-block">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
                    </svg>
                    <span class="ml-2 hidden sm:inline">Tạo Phép</span>
                </button>
            </div>

            <!-- Popup Form -->
            <div id="popupForm" class="fixed inset-0 flex items-center justify-center bg-black bg-opacity-50 hidden transition-opacity duration-300">
                <div class="bg-white p-8 rounded-lg shadow-2xl w-full max-w-md relative animate-fadeIn">
                    <button class="absolute top-2 right-2 text-gray-600 hover:text-gray-800" onclick="toggleForm()">&times;</button>
                    <h2 class="text-2xl font-bold text-center text-blue-600 mb-4">Tạo Phép</h2>
                    <p class="text-sm font-semibold text-gray-700 mb-2">Ngày yêu cầu: <span id="requestDate" class="font-normal"></span></p>
                    <form method="post" action="submit_request.php" enctype="multipart/form-data" class="space-y-4">
                        <input type="hidden" id="request_date" name="request_date"> <!-- Trường ẩn để lưu ngày yêu cầu -->
                        
                        <div>
                            <label for="leaveType" class="block text-gray-700 font-semibold mb-1">Chọn loại phép:</label>
                            <select id="leaveType" name="leave_type" onchange="toggleLeaveOptions()" class="w-full p-2 border border-gray-300 rounded focus:outline-none focus:ring-2 focus:ring-blue-500">
                                <option value="">-- Chọn loại phép --</option>
                                <option value="AL_half_morning">Nửa ca trước (AL)</option>
                                <option value="AL_half_afternoon">Nửa ca sau (AL)</option>
                                <option value="AL_full">Cả ngày (AL)</option>
                                <option value="UL_half_morning">Nửa ca trước (UL)</option>
                                <option value="UL_half_afternoon">Nửa ca sau (UL)</option>
                                <option value="UL_full">Cả ngày (UL)</option>
                                <option value="CD_hours">Nghỉ CD theo giờ</option>
                                <option value="UL_hours">Nghỉ UL theo giờ</option>
                            </select>
                        </div>
                        
                        <div id="cdHoursOption" class="hidden">
                            <label for="cdHours" class="block text-gray-700 font-semibold mb-1">Số giờ nghỉ:</label>
                            <input type="number" id="cdHours" name="cd_hours" min="1" max="8" step="1" value="4" class="w-full p-2 border border-gray-300 rounded focus:outline-none focus:ring-2 focus:ring-blue-500" placeholder="Nhập số giờ nghỉ">
                        </div>
                        
                        <div>
                            <label for="reason" class="block text-gray-700 font-semibold mb-1">Giải trình:</label>
                            <textarea id="reason" name="reason" required class="w-full p-2 border border-gray-300 rounded focus:outline-none focus:ring-2 focus:ring-blue-500"></textarea>
                        </div>

                        <div>
                            <label for="proofImage" class="block text-gray-700 font-semibold mb-1">Tải lên bằng chứng (nếu có):</label>
                            <input type="file" id="proofImage" name="proof_image" accept="image/*" class="w-full p-2 border border-gray-300 rounded focus:outline-none focus:ring-2 focus:ring-blue-500">
                        </div>
                        
                        <p class="text-sm font-semibold text-gray-700">Số ngày AL còn lại: <span class="font-normal"><?php echo $annualLeaveRemaining; ?> ngày</span></p>
                        <p class="text-sm font-semibold text-gray-700">Số giờ CD (OT) còn lại: <span class="font-normal"><?php echo $cdHoursRemaining; ?> giờ</span></p>
                        
                        <button type="submit" name="submit_request" class="w-full py-2 px-4 bg-blue-500 text-white font-semibold rounded hover:bg-blue-600 focus:outline-none focus:ring-2 focus:ring-blue-500">
                            Gửi yêu cầu
                        </button>
                    </form>
                </div>
            </div>

            <!-- Danh sách yêu cầu nghỉ phép -->
            <div class="overflow-hidden rounded-lg shadow-lg mt-10">
                <table class="min-w-full divide-y divide-gray-200 bg-white text-sm text-left">
                    <thead class="bg-gradient-to-r from-blue-600 to-blue-500 text-white text-lg">
                        <tr>
                            <th class="px-6 py-4 font-semibold tracking-wide">Ngày Yêu Cầu</th>
                            <th class="px-6 py-4 font-semibold tracking-wide">Loại Phép</th>
                            <th class="px-6 py-4 font-semibold tracking-wide">Số Giờ (CD/UL)</th>
                            <th class="px-6 py-4 font-semibold tracking-wide">Giải Trình</th>
                            <th class="px-6 py-4 font-semibold tracking-wide">Bằng Chứng</th>
                            <th class="px-6 py-4 font-semibold tracking-wide">Trạng Thái</th>
                            <th class="px-6 py-4 font-semibold tracking-wide">Ngày Tạo</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-200">
                        <?php while ($request = $requestResult->fetch_assoc()): ?>
                            <tr class="transition duration-300 ease-in-out hover:bg-gray-100">
                                <td class="px-6 py-4 whitespace-nowrap"><?php echo htmlspecialchars($request['request_date']); ?></td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <?php
                                        switch ($request['leave_type']) {
                                            case 'AL_half_morning': echo 'Nửa ca trước (AL)'; break;
                                            case 'AL_half_afternoon': echo 'Nửa ca sau (AL)'; break;
                                            case 'AL_full': echo 'Cả ngày (AL)'; break;
                                            case 'UL_half_morning': echo 'Nửa ca trước (UL)'; break;
                                            case 'UL_half_afternoon': echo 'Nửa ca sau (UL)'; break;
                                            case 'UL_full': echo 'Cả ngày (UL)'; break;
                                            case 'CD_hours': echo 'Nghỉ CD theo giờ'; break;
                                            case 'UL_hours': echo 'Nghỉ UL theo giờ'; break;
                                            default: echo 'Không xác định';
                                        }
                                    ?>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-center"><?php echo ($request['cd_hours'] !== null) ? htmlspecialchars($request['cd_hours']) : '-'; ?></td>
                                <td class="px-6 py-4"><?php echo htmlspecialchars($request['reason']); ?></td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <?php if ($request['proof_image']): ?>
                                        <a href="<?php echo htmlspecialchars($request['proof_image']); ?>" target="_blank" class="text-blue-500 underline hover:text-blue-700">Xem bằng chứng</a>
                                    <?php else: ?>
                                        <span class="text-gray-400 italic">Không có</span>
                                    <?php endif; ?>
                                </td>
                                <td class="px-6 py-4 text-center">
                                    <?php
                                        if ($request['status'] === 'Pending') {
                                            echo '<span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full bg-yellow-100 text-yellow-800">Đang Chờ</span>';
                                        } elseif ($request['status'] === 'Approved') {
                                            echo '<span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full bg-green-100 text-green-800">Đã Duyệt</span>';
                                        } elseif ($request['status'] === 'Rejected') {
                                            echo '<span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full bg-red-100 text-red-800">Từ Chối</span>';
                                        }
                                    ?>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap"><?php echo htmlspecialchars($request['created_at']); ?></td>
                            </tr>
                        <?php endwhile; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </section>
</div>

<script>
    function toggleForm() {
        const popupForm = document.getElementById('popupForm');
        popupForm.classList.toggle('hidden');
    }

    function toggleLeaveOptions() {
        var leaveType = document.getElementById('leaveType').value;
        var cdHoursOption = document.getElementById('cdHoursOption');
        
        // Hiển thị phần nhập số giờ nếu chọn "CD_hours" hoặc "UL_hours"
        if (leaveType === 'CD_hours' || leaveType === 'UL_hours') {
            cdHoursOption.classList.remove('hidden');
        } else {
            cdHoursOption.classList.add('hidden');
        }
    }
</script>

<footer class="main-footer">
    <strong> &copy; <?php echo date('Y'); ?> HD Gourmet</strong> - Bản Quyền Thuộc Về.
</footer>

<?php include('includes_staff/footer_staff.php'); ?>
</body>
</html>
