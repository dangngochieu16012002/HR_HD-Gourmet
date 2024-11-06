<?php
include('includes/config.php');

header("Content-Type: application/vnd.ms-excel");
header("Content-Disposition: attachment; filename=bang_luong.xls");

$salary_results = $conn->query("SELECT * FROM salaries");

echo '<table border="1">';
echo '<tr>';
echo '<th>Tên nhân viên</th>';
echo '<th>Số điện thoại</th>';
echo '<th>Email</th>';
echo '<th>Chức vụ</th>';
echo '<th>Phòng</th>';
echo '<th>Lương cơ bản</th>';
echo '<th>Ngày công</th>';
echo '<th>Phụ cấp</th>';
echo '<th>Thưởng KPI</th>';
echo '<th>Lương thực nhận</th>';
echo '</tr>';

while ($row = $salary_results->fetch_assoc()) {
    echo '<tr>';
    echo '<td>' . htmlspecialchars($row['fullname']) . '</td>';
    echo '<td>' . htmlspecialchars($row['contact_number']) . '</td>';
    echo '<td>' . htmlspecialchars($row['email']) . '</td>';
    echo '<td>' . htmlspecialchars($row['occupation']) . '</td>';
    echo '<td>' . htmlspecialchars($row['membership_type']) . '</td>';
    echo '<td>' . number_format($row['basic_salary']) . '</td>';
    echo '<td>' . $row['working_days'] . '</td>';
    echo '<td>' . number_format($row['meal_allowance'] + $row['clothing_allowance']) . '</td>';
    echo '<td>' . number_format($row['kpi_bonus']) . '</td>';
    echo '<td>' . number_format($row['total_salary']) . '</td>';
    echo '</tr>';
}

echo '</table>';
exit;
?>
