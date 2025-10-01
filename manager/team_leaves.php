<?php
require_once '../config/db.php';
require_once '../includes/auth.php';
require_once '../includes/functions.php';
checkRole(['manager']);

$department = $_SESSION['department'];

// ==== รับ POST เฉพาะตอนปฏิเสธ ====
if (isset($_POST['reject_submit'])) {
    $id = sanitizeInput($_POST['reject_id']);
    $reason = sanitizeInput($_POST['reject_reason']);

    $stmt = $conn->prepare("UPDATE leave_requests 
        SET status = 'rejected', reject_reason = :reason 
        WHERE id = :id AND EXISTS (SELECT 1 FROM users u WHERE u.id = leave_requests.user_id AND u.department = :department)");
    $stmt->bindParam(':reason', $reason);
    $stmt->bindParam(':id', $id);
    $stmt->bindParam(':department', $department);

    if ($stmt->execute()) {
        $_SESSION['success'] = "อัปเดตสถานะการลาสำเร็จ";
        require_once '../includes/email_functions.php';
        sendLeaveStatusUpdateEmail($conn, $id, 'rejected', $reason);
    } else {
        $_SESSION['error'] = "เกิดข้อผิดพลาดในการอัปเดต";
    }
    header("Location: " . BASE_URL . "manager/team_leaves.php");
    exit();
}

// อนุมัติ (GET)
if (isset($_GET['action']) && isset($_GET['id'])) {
    $action = sanitizeInput($_GET['action']);
    $id = sanitizeInput($_GET['id']);

    if ($action === 'approved') {
        $stmt = $conn->prepare("UPDATE leave_requests 
                              SET status = :status, reject_reason = NULL 
                              WHERE id = :id AND 
                              EXISTS (SELECT 1 FROM users u 
                                      WHERE u.id = leave_requests.user_id 
                                      AND u.department = :department)");
        $stmt->bindParam(':status', $action);
        $stmt->bindParam(':id', $id);
        $stmt->bindParam(':department', $department);

        if ($stmt->execute()) {
            $_SESSION['success'] = "อัปเดตสถานะการลาสำเร็จ";
            require_once '../includes/email_functions.php';
            sendLeaveStatusUpdateEmail($conn, $id, $action);
        } else {
            $_SESSION['error'] = "เกิดข้อผิดพลาดในการอัปเดต";
        }
        header("Location: " . BASE_URL . "manager/team_leaves.php");
        exit();
    }
}

// ดึงข้อมูลการลาของทีม
$stmt = $conn->prepare("SELECT lr.*, u.fullname, lt.name as leave_type 
                       FROM leave_requests lr
                       JOIN users u ON lr.user_id = u.id
                       JOIN leave_types lt ON lr.leave_type_id = lt.id
                       WHERE u.department = :department
                       ORDER BY lr.status, lr.start_date DESC");
$stmt->bindParam(':department', $department);
$stmt->execute();
$team_leaves = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<?php include '../includes/header.php'; ?>

<div class="container">
    <h2>จัดการการลาของทีม (<?= htmlspecialchars($department) ?>)</h2>
    <?php displayAlert(); ?>

    <div class="card mb-4">
        <div class="card-header">
            <h5>ปฏิทินการลาของทีม</h5>
        </div>
        <div class="card-body">
            <div id="teamLeaveCalendar"></div>
        </div>
    </div>

    <div class="card">
        <div class="card-header">
            <h5>รายการคำขอลาทั้งหมด</h5>
        </div>
        <div class="card-body">
            <div class="table-responsive">
            <table class="table table-bordered align-middle">
                <thead class="table-light">
                    <tr>
                        <th>พนักงาน</th>
                        <th>ประเภท</th>
                        <th>วันที่ลา</th>
                        <th>เหตุผล</th>
                        <th>สถานะ</th>
                        <th>การดำเนินการ</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($team_leaves)): ?>
                        <tr><td colspan="6" class="text-center text-muted">ไม่มีข้อมูล</td></tr>
                    <?php endif; ?>
                    <?php foreach ($team_leaves as $leave): ?>
                        <tr>
                            <td><?= htmlspecialchars($leave['fullname']) ?></td>
                            <td><?= htmlspecialchars($leave['leave_type']) ?></td>
                            <td><?= date('d/m/Y', strtotime($leave['start_date'])) ?> - <?= date('d/m/Y', strtotime($leave['end_date'])) ?></td>
                            <td>
                                <?= htmlspecialchars($leave['reason']) ?>
                                <?php if ($leave['status'] == 'rejected' && !empty($leave['reject_reason'])): ?>
                                    <br><span class="badge bg-danger">เหตุผลปฏิเสธ: <?= htmlspecialchars($leave['reject_reason']) ?></span>
                                <?php endif; ?>
                            </td>
                            <td>
                                <?php
                                $status_map = [
                                    'pending' => ['class' => 'bg-warning text-dark', 'text' => 'รออนุมัติ'],
                                    'approved' => ['class' => 'bg-success', 'text' => 'อนุมัติแล้ว'],
                                    'rejected' => ['class' => 'bg-danger', 'text' => 'ปฏิเสธ']
                                ];
                                $status_info = $status_map[$leave['status']] ?? ['class' => 'bg-secondary', 'text' => 'ไม่ทราบสถานะ'];
                                ?>
                                <span class="badge <?= $status_info['class'] ?>"><?= $status_info['text'] ?></span>
                            </td>
                            <td>
                                <?php if ($leave['status'] == 'pending'): ?>
                                    <a href="<?= BASE_URL ?>manager/team_leaves.php?action=approved&id=<?= $leave['id'] ?>" 
                                       class="btn btn-sm btn-success" onclick="return confirm('ยืนยันการอนุมัติ?');">
                                        <i class="fas fa-check"></i> อนุมัติ
                                    </a>
                                    <!-- ปุ่มเปิด Modal ปฏิเสธ -->
                                    <button type="button" class="btn btn-sm btn-danger"
                                        data-bs-toggle="modal" data-bs-target="#rejectModal<?= $leave['id'] ?>">
                                        <i class="fas fa-times"></i> ปฏิเสธ
                                    </button>
                                <?php endif; ?>
                                <a href="../manager/print_leave_form.php?id=<?= $leave['id'] ?>" target="_blank" class="btn btn-outline-primary btn-sm mt-1">
                                    <i class="fas fa-print"></i> พิมพ์ใบลา
                                </a>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
            </div>
        </div>
    </div>
</div>

<!-- Modal ฟอร์มปฏิเสธ (วางนอกตาราง) -->
<?php foreach ($team_leaves as $leave): ?>
<div class="modal fade" id="rejectModal<?= $leave['id'] ?>" tabindex="-1" aria-labelledby="rejectModalLabel<?= $leave['id'] ?>" aria-hidden="true">
    <div class="modal-dialog" role="document">
        <form method="POST">
            <input type="hidden" name="reject_id" value="<?= $leave['id'] ?>">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="rejectModalLabel<?= $leave['id'] ?>">ปฏิเสธคำขอลา</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <div class="form-group">
                        <label for="reject_reason<?= $leave['id'] ?>">เหตุผลที่ปฏิเสธ</label>
                        <textarea name="reject_reason" id="reject_reason<?= $leave['id'] ?>" class="form-control" required></textarea>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">ยกเลิก</button>
                    <button type="submit" name="reject_submit" class="btn btn-danger">ยืนยันปฏิเสธ</button>
                </div>
            </div>
        </form>
    </div>
</div>
<?php endforeach; ?>

<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/fullcalendar@5.10.1/main.min.css">
<script src="https://cdn.jsdelivr.net/npm/fullcalendar@5.10.1/main.min.js"></script>
<script>
document.addEventListener('DOMContentLoaded', function() {
    var calendarEl = document.getElementById('teamLeaveCalendar');
    var calendar = new FullCalendar.Calendar(calendarEl, {
        initialView: 'dayGridMonth',
        events: [
            <?php foreach ($team_leaves as $leave): ?>
            {
                title: '<?= addslashes($leave['fullname']) ?> - <?= addslashes($leave['leave_type']) ?>',
                start: '<?= $leave['start_date'] ?>',
                end: '<?= date('Y-m-d', strtotime($leave['end_date'] . ' +1 day')) ?>',
                color: '<?= $leave['status'] == 'approved' ? '#28a745' : ($leave['status'] == 'rejected' ? '#dc3545' : '#ffc107') ?>'
            },
            <?php endforeach; ?>
        ]
    });
    calendar.render();
});
</script>

<?php include '../includes/footer.php'; ?>
