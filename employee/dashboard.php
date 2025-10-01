<?php
require_once '../config/db.php';
require_once '../includes/auth.php';
require_once '../includes/functions.php';
checkRole(['employee']);

// ดึงข้อมูลประเภทการลาสำหรับ dropdown
$leave_types_stmt = $conn->query("SELECT * FROM leave_types ORDER BY name");
$leave_types = $leave_types_stmt->fetchAll(PDO::FETCH_ASSOC);

// ดึงข้อมูลคำขอลาของพนักงานคนปัจจุบัน
$my_requests_stmt = $conn->prepare("SELECT lr.*, lt.name as leave_type_name 
                                  FROM leave_requests lr
                                  JOIN leave_types lt ON lr.leave_type_id = lt.id
                                  WHERE lr.user_id = :user_id
                                  ORDER BY lr.created_at DESC");
$my_requests_stmt->bindParam(':user_id', $_SESSION['user_id']);
$my_requests_stmt->execute();
$my_requests = $my_requests_stmt->fetchAll(PDO::FETCH_ASSOC);

// ดึงสถิติการลาของ user นี้ แยกตามประเภท และดึง max_days และ min_notice_days จาก leave_rules
$leave_count_stmt = $conn->prepare("
    SELECT lt.name AS leave_type_name, 
           COUNT(lr.id) AS total,
           ANY_VALUE(lrules.max_days) AS max_days,
           ANY_VALUE(lrules.min_notice_days) AS min_notice_days
    FROM leave_types lt
    LEFT JOIN leave_requests lr ON lt.id = lr.leave_type_id AND lr.user_id = :user_id
    LEFT JOIN leave_rules lrules ON lt.id = lrules.leave_type_id
    GROUP BY lt.id
    ORDER BY lt.name
");
$leave_count_stmt->bindParam(':user_id', $_SESSION['user_id']);
$leave_count_stmt->execute();
$leave_counts = $leave_count_stmt->fetchAll(PDO::FETCH_ASSOC);

// หาผลรวมทั้งหมด
$total_leave_count = 0;
foreach ($leave_counts as $lc) {
    $total_leave_count += (int)$lc['total'];
}

// ======== ดึงวันลาคงเหลือ "ลาพักร้อน" ========
$user_id = $_SESSION['user_id'];
$current_year = date('Y');
$leave_type_vacation = 3; // สมมุติ id = 3 คือ ลาพักร้อน
$vacation_balance = getLeaveBalance($conn, $user_id, $leave_type_vacation, $current_year);

include '../includes/header.php';
?>

<div class="container mt-4">
    <h2>หน้าแรกพนักงาน</h2>

    <div class="row">
        <div class="col-md-6">
            <div class="card mb-4">
                <div class="card-header">
                    <h5>ยื่นคำขอลางาน</h5>
                </div>
                <div class="card-body">
                    <form method="POST" action="<?= BASE_URL ?>employee/request_leave.php" enctype="multipart/form-data">
                        <div class="form-group">
                            <label>ประเภทการลา</label>
                            <select name="leave_type" id="leave_type_select" class="form-control" required>
                                <option value="">-- เลือกประเภท --</option>
                                <?php foreach ($leave_types as $type): ?>
                                    <option value="<?= htmlspecialchars($type['id']) ?>"
                                        data-desc="<?= htmlspecialchars($type['description']) ?>">
                                        <?= htmlspecialchars($type['name']) ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                            <small id="leave_desc" class="form-text text-muted"></small>
                        </div>
                        <div class="form-group">
                            <label>วันที่เริ่มลา</label>
                            <input type="date" name="start_date" class="form-control" required>
                        </div>
                        <div class="form-group">
                            <label>วันที่สิ้นสุดลา</label>
                            <input type="date" name="end_date" class="form-control" required>
                        </div>
                        <div class="form-group">
                            <label>เหตุผล</label>
                            <textarea name="reason" class="form-control" required></textarea>
                        </div>
                        <div class="form-group">
                            <label>เอกสารประกอบ (ถ้ามี)</label>
                            <input type="file" name="document" class="form-control-file">
                        </div>
                        <button type="submit" class="btn btn-primary">ยื่นคำขอ</button>
                    </form>
                </div>
            </div>
        </div>

        <div class="col-md-6">
            <div class="card mb-4">
                <div class="card-header">
                    <h5>สถิติการลาของฉัน (ตามประเภท)</h5>
                </div>
                <div class="card-body">
                    <table class="table table-bordered table-sm mb-0">
                        <thead>
                            <tr>
                                <th>ประเภทการลา</th>
                                <th class="text-center">จำนวนครั้ง</th>
                                <th class="text-center">ลาได้สูงสุดต่อครั้ง (วัน)</th>
                                <th class="text-center">แจ้งล่วงหน้า (วัน)</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($leave_counts as $row): ?>
                                <tr>
                                    <td><?= htmlspecialchars($row['leave_type_name']) ?></td>
                                    <td class="text-center"><?= (int)$row['total'] ?></td>
                                    <td class="text-center"><?= isset($row['max_days']) ? (int)$row['max_days'] . ' วัน' : '-' ?></td>
                                    <td class="text-center"><?= isset($row['min_notice_days']) ? (int)$row['min_notice_days'] . ' วัน' : '-' ?></td>
                                </tr>
                            <?php endforeach; ?>
                            <tr style="font-weight:bold; background:#f2f2f2;">
                                <td>รวมทั้งหมด</td>
                                <td class="text-center"><?= $total_leave_count ?></td>
                                <td colspan="2"></td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>

            <div class="card mb-4">
                <div class="card-header">
                    <h5>คำขอลาของฉัน</h5>
                </div>
                <div class="card-body">
                    <table class="table">
                        <thead>
                            <tr>
                                <th>ประเภท</th>
                                <th>วันที่</th>
                                <th>สถานะ</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (empty($my_requests)): ?>
                                <tr><td colspan="3" class="text-center text-muted">ยังไม่มีคำขอลา</td></tr>
                            <?php endif; ?>
                            <?php foreach ($my_requests as $request): ?>
                                <tr>
                                    <td><?= htmlspecialchars($request['leave_type_name']) ?></td>
                                    <td><?= date('d/m/Y', strtotime($request['start_date'])) ?> ถึง <?= date('d/m/Y', strtotime($request['end_date'])) ?></td>
                                    <td>
                                        <?php 
                                        $status_map = [
                                            'pending' => ['class' => 'bg-warning text-dark', 'text' => 'รออนุมัติ'],
                                            'approved' => ['class' => 'bg-success', 'text' => 'อนุมัติแล้ว'],
                                            'rejected' => ['class' => 'bg-danger', 'text' => 'ปฏิเสธ']
                                        ];
                                        $status_info = $status_map[$request['status']] ?? ['class' => 'bg-secondary', 'text' => 'ไม่ทราบสถานะ'];
                                        ?>
                                        <span class="badge <?= $status_info['class'] ?>"><?= $status_info['text'] ?></span>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<script>
document.addEventListener('DOMContentLoaded', function() {
    <?php if (isset($_SESSION['success'])): ?>
        Swal.fire({
            icon: 'success',
            title: 'สำเร็จ!',
            text: '<?= htmlspecialchars($_SESSION['success']) ?>',
            timer: 1800,
            showConfirmButton: false,
            timerProgressBar: true
        });
        <?php unset($_SESSION['success']); ?>
    <?php elseif (isset($_SESSION['error'])): ?>
        Swal.fire({
            icon: 'error',
            title: 'เกิดข้อผิดพลาด!',
            text: '<?= htmlspecialchars($_SESSION['error']) ?>',
            confirmButtonText: 'ปิด'
        });
        <?php unset($_SESSION['error']); ?>
    <?php endif; ?>

    var select = document.getElementById('leave_type_select');
    var desc = document.getElementById('leave_desc');
    function showDesc() {
        var opt = select.options[select.selectedIndex];
        desc.textContent = opt.getAttribute('data-desc') || '';
    }
    select.addEventListener('change', showDesc);
    showDesc();
});
</script>

<?php include '../includes/footer.php'; ?>
