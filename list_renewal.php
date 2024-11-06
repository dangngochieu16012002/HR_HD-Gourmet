<?php
include('includes/config.php');

$selectQuery = "SELECT * FROM members";
$result = $conn->query($selectQuery);

if (!isset($_SESSION['user_id'])) {
    header("Location: index.php");
    exit();
}
?>

<?php include('includes/header.php');?>

<body class="hold-transition sidebar-mini layout-fixed layout-navbar-fixed layout-footer-fixed">
<div class="wrapper">
  <?php include('includes/nav.php');?>
  <?php include('includes/sidebar.php');?>

  <!-- Nội dung trang chính -->
  <div class="content-wrapper">
    
  <?php include('includes/pagetitle.php');?>

    <!-- Nội dung chính -->
    <section class="content">
      <div class="container-fluid">
        <div class="row">
          <div class="col-12">
            <div class="card">
              <div class="card-header">
                  <h3 class="card-title">Gia Hạn Thành Viên</h3>
              </div>
              <!-- Nội dung thẻ -->
              <div class="card-body">
                <table id="example1" class="table table-bordered table-striped">
                    <thead>
                        <tr>
                            <th>STT</th>
                            <th>Họ Tên</th>
                            <th>Liên Hệ</th>
                            <th>Email</th>
                            <th>Loại Thành Viên</th>
                            <th>Hết Hạn</th>
                            <th>Trạng Thái</th>
                            <th>Hành Động</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php
                        $counter = 1;
                        while ($row = $result->fetch_assoc()) {
                            $expiryDate = strtotime($row['expiry_date']);
                            $currentDate = time();
                            $daysDifference = floor(($expiryDate - $currentDate) / (60 * 60 * 24));

                            $membershipStatus = ($daysDifference < 0) ? 'Hết Hạn' : 'Hoạt Động';
                            $badgeClass = ($membershipStatus === 'Hết Hạn') ? 'badge-danger' : 'badge-success';

                            $membershipTypeId = $row['membership_type'];
                            $membershipTypeQuery = "SELECT type FROM membership_types WHERE id = $membershipTypeId";
                            $membershipTypeResult = $conn->query($membershipTypeQuery);
                            $membershipTypeRow = $membershipTypeResult->fetch_assoc();
                            $membershipTypeName = ($membershipTypeRow) ? $membershipTypeRow['type'] : 'Không Xác Định';

                            echo "<tr>";
                            echo "<td>{$row['membership_number']}</td>";
                            echo "<td>{$row['fullname']}</td>";
                            echo "<td>{$row['contact_number']}</td>";
                            echo "<td>{$row['email']}</td>";
                            echo "<td>{$membershipTypeName}</td>";

                            if ($row['expiry_date'] === NULL) {
                                echo "<td>KHÔNG CÓ</td>";
                            } else {
                                $expiryDate = new DateTime($row['expiry_date']);
                                $currentDate = new DateTime();
                                $daysRemaining = $currentDate->diff($expiryDate)->days;
                                echo "<td>{$row['expiry_date']}<br><small>Còn {$daysRemaining} ngày</small></td>";
                            }
                            echo "<td><span class='badge $badgeClass'>$membershipStatus</span></td>";
                            echo "<td>
                                <a href='renew.php?id={$row['id']}' class='btn btn-success'>Gia Hạn</a>
                                </td>";
                            echo "</tr>";
                            $counter++;
                        }
                        ?>
                    </tbody>
                </table>
              </div>
              <!-- Kết thúc nội dung thẻ -->
            </div>
          </div>
        </div>
      </div>
    </section>
  </div>
  <!-- Kết thúc phần nội dung -->

  <!-- Sidebar điều khiển -->
  <aside class="control-sidebar control-sidebar-dark"></aside>

  <!-- Footer -->
  <footer class="main-footer">
    <strong> &copy; <?php echo date('Y');?> HD Gourmet</strong> - Bản Quyền Thuộc Về.
    <div class="float-right d-none d-sm-inline-block">
      <b>Phát Triển Bởi</b> <a href="#">HD Gourmet</a>
    </div>
  </footer>
</div>

<?php include('includes/footer.php');?>
<script>
  $(function () {
    $("#example1").DataTable({
      "responsive": true,
      "autoWidth": false,
    });
    $('#example2').DataTable({
      "paging": true,
      "lengthChange": false,
      "searching": false,
      "ordering": true,
      "info": true,
      "autoWidth": false,
      "responsive": true,
    });
  });
</script>

</body>
</html>
