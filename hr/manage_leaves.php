<?php
require_once '../config/db.php';
require_once '../includes/auth.php';

// ฟังก์ชัน sanitizeInput (ถ้ายังไม่มีในระบบ)
if (!function_exists('sanitizeInput')) {
    function sanitizeInput($input) {
        return trim(htmlspecialchars($input, ENT_QUOTES, 'UTF-8'));
    }
}

// ตรวจสอบสิทธิ์
checkRole(['hr', 'admin']);

// ==== รับ POST เฉพาะตอนปฏิเสธ ====
if (isset($_POST['reject_submit'])) {
    $id = sanitizeInput($_POST['reject_id']);
    $reason = sanitizeInput($_POST['reject_reason']);
    $approver_id = $_SESSION['user_id'];

    $stmt = $conn->prepare("UPDATE leave_requests 
        SET status = 'rejected', reject_reason = :reason, approved_by = :approved_by 
        WHERE id = :id");
    $stmt->bindParam(':reason', $reason);
    $stmt->bindParam(':approved_by', $approver_id);
    $stmt->bindParam(':id', $id);

    if ($stmt->execute()) {
        $_SESSION['success'] = "อัปเดตสถานะการลาสำเร็จ";
        require_once '../includes/functions.php';
        sendLeaveStatusUpdateEmail($conn, $id, 'rejected', $reason);
    } else {
        $_SESSION['error'] = "เกิดข้อผิดพลาดในการอัปเดต";
    }
    header("Location: " . BASE_URL . "hr/manage_leaves.php");
    exit();
}

// อนุมัติ (GET)
if (isset($_GET['action']) && isset($_GET['id'])) {
    $action = sanitizeInput($_GET['action']);
    $id = sanitizeInput($_GET['id']);
    $approver_id = $_SESSION['user_id'];

    if ($action === 'approved') {
        $stmt = $conn->prepare("UPDATE leave_requests 
                              SET status = :status, approved_by = :approved_by, reject_reason = NULL 
                              WHERE id = :id");
        $stmt->bindParam(':status', $action);
        $stmt->bindParam(':approved_by', $approver_id);
        $stmt->bindParam(':id', $id);

        if ($stmt->execute()) {
            $_SESSION['success'] = "อัปเดตสถานะการลาสำเร็จ";
            require_once '../includes/functions.php';
            sendLeaveStatusUpdateEmail($conn, $id, $action);
        } else {
            $_SESSION['error'] = "เกิดข้อผิดพลาดในการอัปเดต";
        }

        header("Location: " . BASE_URL . "hr/manage_leaves.php");
        exit();
    }
}

// ดึงข้อมูลคำขอลา
$stmt = $conn->query("SELECT lr.*, u.fullname, u.department, lt.name as leave_type_name 
                     FROM leave_requests lr
                     JOIN users u ON lr.user_id = u.id
                     JOIN leave_types lt ON lr.leave_type_id = lt.id
                     ORDER BY lr.created_at DESC");
$leave_requests = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>
<?php include '../includes/header.php'; ?>

<div class="container mt-4">
    <h2>จัดการคำขอลา</h2>

    <?php if (isset($_SESSION['success'])): ?>
        <div class="alert alert-success"><?= htmlspecialchars($_SESSION['success']); unset($_SESSION['success']); ?></div>
    <?php endif; ?>
    <?php if (isset($_SESSION['error'])): ?>
        <div class="alert alert-danger"><?= htmlspecialchars($_SESSION['error']); unset($_SESSION['error']); ?></div>
    <?php endif; ?>

    <table class="table table-bordered">
        <thead class="thead-dark">
            <tr>
                <th>พนักงาน</th>
                <th>แผนก</th>
                <th>ประเภท</th>
                <th>วันที่ลา</th>
                <th>เหตุผล</th>
                <th>สถานะ</th>
                <th>การดำเนินการ</th>
            </tr>
        </thead>
        <tbody>
            <?php if (empty($leave_requests)): ?>
                <tr>
                    <td colspan="7" class="text-center text-muted">ไม่มีข้อมูลคำขอลา</td>
                </tr>
            <?php endif; ?>
            <?php foreach ($leave_requests as $request): ?>
                <tr>
                    <td><?= htmlspecialchars($request['fullname']) ?></td>
                    <td><?= htmlspecialchars($request['department']) ?></td>
                    <td><?= htmlspecialchars($request['leave_type_name']) ?></td>
                    <td><?= date('d/m/Y', strtotime($request['start_date'])) . " ถึง " . date('d/m/Y', strtotime($request['end_date'])) ?></td>
                    <td>
                        <?= htmlspecialchars($request['reason']) ?>
                        <?php if ($request['status'] == 'rejected' && !empty($request['reject_reason'])): ?>
                            <br><span class="badge bg-danger">เหตุผลปฏิเสธ: <?= htmlspecialchars($request['reject_reason']) ?></span>
                        <?php endif; ?>
                    </td>
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
                    <td>
                        <?php if ($request['status'] == 'pending'): ?>
                            <a href="<?= BASE_URL ?>hr/manage_leaves.php?action=approved&id=<?= $request['id'] ?>" class="btn btn-sm btn-success">
                                <i class="fas fa-check"></i> อนุมัติ
                            </a>
                            <!-- ปุ่มเปิด Modal ปฏิเสธ (Bootstrap 5) -->
                            <button type="button" class="btn btn-sm btn-danger" data-bs-toggle="modal" data-bs-target="#rejectModal<?= $request['id'] ?>">
                                <i class="fas fa-times"></i> ปฏิเสธ
                            </button>
                        <?php endif; ?>

                        <?php if ($request['document_path']): ?>
                            <a href="<?= BASE_URL ?>uploads/<?= htmlspecialchars($request['document_path']) ?>" class="btn btn-sm btn-info" target="_blank">
                                <i class="fas fa-file-alt"></i> ดูเอกสาร
                            </a>
                        <?php endif; ?>
                    </td>
                </tr>
            <?php endforeach; ?>
        </tbody>
    </table>

    <!-- Modal ฟอร์มปฏิเสธ (วางนอก table) -->
    <?php foreach ($leave_requests as $request): ?>
    <div class="modal fade" id="rejectModal<?= $request['id'] ?>" tabindex="-1" aria-labelledby="rejectModalLabel<?= $request['id'] ?>" aria-hidden="true">
        <div class="modal-dialog" role="document">
            <form method="POST">
                <input type="hidden" name="reject_id" value="<?= $request['id'] ?>">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title" id="rejectModalLabel<?= $request['id'] ?>">ปฏิเสธคำขอลา</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <div class="modal-body">
                        <div class="form-group">
                            <label for="reject_reason<?= $request['id'] ?>">เหตุผลที่ปฏิเสธ</label>
                            <textarea name="reject_reason" id="reject_reason<?= $request['id'] ?>" class="form-control" required></textarea>
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

</div>
<?php include '../includes/footer.php'; ?>
