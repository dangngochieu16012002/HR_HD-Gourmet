<?php
include('includes/config.php');

// Kiểm tra trạng thái đăng nhập
if (!isset($_SESSION['user_id'])) {
    header("Location: index.php");
    exit();
}

// Lấy tất cả yêu cầu nghỉ phép
$query = "SELECT lr.*, m.fullname, m.annual_leave_remaining, m.cd_hours_remaining 
          FROM leave_requests lr 
          JOIN members m ON lr.employee_id = m.id 
          ORDER BY lr.created_at DESC";
$result = $conn->query($query);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Quản lý yêu cầu nghỉ phép</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/tailwindcss@2.2.19/dist/tailwind.min.css">
</head>
<?php include('includes/header.php'); ?>

<body class="hold-transition sidebar-mini layout-fixed layout-navbar-fixed layout-footer-fixed">
<div class="wrapper">
    <?php include('includes/nav.php'); ?>
    <?php include('includes/sidebar.php'); ?>

    <div class="content-wrapper p-4">
        <section class="content">
            <div class="container mx-auto bg-white rounded-lg shadow-lg p-6">
                <h2 class="text-2xl font-semibold text-center text-blue-600 mb-6">Quản lý Yêu Cầu Nghỉ Phép</h2>

                <?php if ($result->num_rows > 0): ?>
                    <table class="min-w-full border-collapse bg-white rounded-lg shadow overflow-hidden">
                        <thead class="bg-blue-600 text-white">
                            <tr>
                                <th class="px-4 py-2">Tên Nhân Viên</th>
                                <th class="px-4 py-2">Ngày Yêu Cầu</th>
                                <th class="px-4 py-2">Loại Phép</th>
                                <th class="px-4 py-2">Số Giờ (CD)</th>
                                <th class="px-4 py-2">Giải Trình</th>
                                <th class="px-4 py-2">Bằng Chứng</th>
                                <th class="px-4 py-2">Trạng Thái</th>
                                <th class="px-4 py-2">Hành Động</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php while ($row = $result->fetch_assoc()): ?>
                                <tr class="border-b hover:bg-gray-100">
                                    <td class="px-4 py-2"><?php echo htmlspecialchars($row['fullname']); ?></td>
                                    <td class="px-4 py-2"><?php echo htmlspecialchars($row['request_date']); ?></td>
                                    <td class="px-4 py-2"><?php echo htmlspecialchars($row['leave_type']); ?></td>
                                    <td class="px-4 py-2 text-center"><?php echo $row['cd_hours'] ?? '-'; ?></td>
                                    <td class="px-4 py-2"><?php echo htmlspecialchars($row['reason']); ?></td>
                                    <td class="px-4 py-2">
                                        <?php if ($row['proof_image']): ?>
                                            <a href="<?php echo htmlspecialchars($row['proof_image']); ?>" target="_blank" class="text-blue-500 underline hover:text-blue-700">Xem</a>
                                        <?php else: ?>
                                            <span class="text-gray-400 italic">Không có</span>
                                        <?php endif; ?>
                                    </td>
                                    <td class="px-4 py-2">
                                        <?php
                                            if ($row['status'] === 'Pending') {
                                                echo '<span class="px-2 py-1 bg-yellow-100 text-yellow-800 rounded-full">Đang Chờ</span>';
                                            } elseif ($row['status'] === 'Approved') {
                                                echo '<span class="px-2 py-1 bg-green-100 text-green-800 rounded-full">Đã Duyệt</span>';
                                            } elseif ($row['status'] === 'Rejected') {
                                                echo '<span class="px-2 py-1 bg-red-100 text-red-800 rounded-full">Từ Chối</span>';
                                            }
                                        ?>
                                    </td>
                                    <td class="px-4 py-2">
                                        <?php if ($row['status'] === 'Pending'): ?>
                                            <form action="process_leave_request.php" method="post" class="inline-block">
                                                <input type="hidden" name="request_id" value="<?php echo $row['id']; ?>">
                                                <input type="hidden" name="employee_id" value="<?php echo $row['employee_id']; ?>">
                                                <input type="hidden" name="leave_type" value="<?php echo $row['leave_type']; ?>">
                                                <input type="hidden" name="cd_hours" value="<?php echo $row['cd_hours']; ?>">

                                                <button type="submit" name="approve" class="px-3 py-1 bg-green-500 text-white rounded hover:bg-green-600 focus:outline-none">Duyệt</button>
                                                <button type="submit" name="reject" class="px-3 py-1 bg-red-500 text-white rounded hover:bg-red-600 focus:outline-none ml-2">Từ Chối</button>
                                            </form>
                                        <?php else: ?>
                                            <span class="text-gray-400 italic">Đã xử lý</span>
                                        <?php endif; ?>
                                    </td>
                                </tr>
                            <?php endwhile; ?>
                        </tbody>
                    </table>
                <?php else: ?>
                    <p class="text-center text-gray-700 mt-6">Không có yêu cầu nghỉ phép nào.</p>
                <?php endif; ?>
            </div>
        </section>
    </div>

    <footer class="main-footer text-center py-4">
        <strong>&copy; <?php echo date('Y'); ?> HD Gourmet</strong> - Bản Quyền Thuộc Về.
    </footer>
</div>

<?php include('includes/footer.php'); ?>
</body>
</html>
