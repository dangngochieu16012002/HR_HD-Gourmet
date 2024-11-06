<?php
include('includes/config.php');

$selectQuery = "SELECT * FROM members ORDER BY created_at DESC";
$result = $conn->query($selectQuery);

if (!isset($_SESSION['user_id'])) {
    header("Location: index.php");
    exit();
}
?>

<?php include('includes/header.php'); ?>

<style>
.dropdown-item {
    transition: background-color 0.3s, color 0.3s; /* Hiệu ứng chuyển màu mượt mà */
}

.dropdown-item:hover {
    background-color: #007bff; /* Màu nền khi di chuột qua */
    color: white; /* Màu chữ khi di chuột qua */
}
</style>

<body class="hold-transition sidebar-mini layout-fixed layout-navbar-fixed layout-footer-fixed">
<div class="wrapper">
  <?php include('includes/nav.php'); ?>
  <?php include('includes/sidebar.php'); ?>

  <!-- Content Wrapper. Chứa nội dung trang -->
  <div class="content-wrapper">
    <?php include('includes/pagetitle.php'); ?>

    <!-- Nội dung chính -->
    <section class="content">
      <div class="container-fluid">
        <div class="row">
          <div class="col-12">
            <div class="card">
                <div class="card-header">
                    <h3 class="card-title">Nhân Viên</h3>
                </div>
                <div class="card-body">
                  <table id="example1" class="table table-bordered table-striped">
                    <thead>
                        <tr>
                            <th>STT</th>
                            <th>Họ Tên</th>
                            <th>Liên Hệ</th>
                            <th>Email</th>
                            <th>Địa Chỉ</th>
                            <th>Phòng</th>
                            <th>Trạng Thái</th>
                            <th>Hợp Đồng</th>
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
                            $membershipStatus = ($daysDifference < 0) ? 'Hết hạn' : 'Đang hoạt động';

                            $membershipTypeId = $row['membership_type'];
                            $membershipTypeQuery = "SELECT type FROM membership_types WHERE id = $membershipTypeId";
                            $membershipTypeResult = $conn->query($membershipTypeQuery);
                            $membershipTypeRow = $membershipTypeResult->fetch_assoc();
                            $membershipTypeName = ($membershipTypeRow) ? $membershipTypeRow['type'] : 'Không rõ';

                            echo "<tr>";
                            echo "<td>{$counter}</td>";
                            echo "<td>{$row['fullname']}</td>";
                            echo "<td>{$row['contact_number']}</td>";
                            echo "<td>{$row['email']}</td>";
                            echo "<td>{$row['address']}</td>";
                            echo "<td>{$membershipTypeName}</td>";
                            echo "<td>{$membershipStatus}</td>";

                            // Thêm cột hợp đồng với liên kết mở modal
                            if (!empty($row['contract'])) {
                                $contractPath = '../HR_HD-Gourmet/uploads/contracts/' . $row['contract'];
                                echo "<td><a href='#' class='contract-link' data-contract='$contractPath' data-toggle='modal' data-target='#contractModal'>Xem Hợp Đồng</a></td>";
                            } else {
                                echo "<td>Chưa có hợp đồng</td>";
                            }

                            // Menu thả xuống cho các hành động
                            echo "<td>";
                            echo "<div class='btn-group'>";
                            echo "<button type='button' class='btn btn-primary dropdown-toggle' data-toggle='dropdown' aria-haspopup='true' aria-expanded='false'>Hành Động</button>";
                            echo "<div class='dropdown-menu'>";

                            // Thêm liên kết hồ sơ
                            echo "<a class='dropdown-item' href='memberProfile.php?id={$row['id']}'><i class='fas fa-id-card'></i> Hồ Sơ</a>";
                            echo "<a class='dropdown-item' href='edit_member.php?id={$row['id']}'><i class='fas fa-edit'></i> Chỉnh Sửa</a>";
                            echo "<a class='dropdown-item text-danger' href='#' onclick='deleteMember({$row['id']})'><i class='fas fa-trash'></i> Xóa</a>";

                            echo "</div></div></td>";
                            echo "</tr>";

                            $counter++;
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

  <footer class="main-footer">
    <strong>&copy; <?php echo date('Y'); ?> HD Gourmet</strong>
    <div class="float-right d-none d-sm-inline-block">
      <b>Phát Triển Bởi</b> <a href="#">HD Gourmet</a>
    </div>
  </footer>
</div>

<!-- Modal hợp đồng -->
<div class="modal fade" id="contractModal" tabindex="-1" role="dialog" aria-labelledby="contractModalLabel" aria-hidden="true">
  <div class="modal-dialog modal-lg" role="document">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title" id="contractModalLabel">Hợp Đồng</h5>
        <button type="button" class="close" data-dismiss="modal" aria-label="Đóng">
          <span aria-hidden="true">&times;</span>
        </button>
      </div>
      <div class="modal-body">
        <div id="contractError" class="alert alert-danger" style="display: none;">Chỉ chấp nhận file PDF!</div> <!-- Thông báo lỗi -->
        <iframe id="contractIframe" src="" style="width: 100%; height: 600px; border: none;"></iframe>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-secondary" data-dismiss="modal">Đóng</button>
      </div>
    </div>
  </div>
</div>

<?php include('includes/footer.php'); ?>
<script>
  $(function () {
    $("#example1").DataTable({
      "responsive": true,
      "autoWidth": false,
    });

    // Khi nhấp vào liên kết hợp đồng
    $('.contract-link').on('click', function() {
      var contractUrl = $(this).data('contract'); // Lấy URL hợp đồng từ thuộc tính data
      var extension = contractUrl.split('.').pop().toLowerCase(); // Lấy phần mở rộng file
      $('#contractError').hide(); // Ẩn thông báo lỗi ban đầu
      if (extension !== 'pdf') {
        $('#contractError').show(); // Hiển thị thông báo lỗi nếu không phải file PDF
        $('#contractIframe').attr('src', ''); // Xóa src của iframe
      } else {
        $('#contractIframe').attr('src', contractUrl); // Đặt URL vào iframe trong modal
      }
    });
  });

  function deleteMember(id) {
      if (confirm("Bạn có chắc chắn muốn xóa nhân viên này?")) {
          window.location.href = 'delete_members.php?id=' + id;
      }
  }
</script>

</body>
</html>
