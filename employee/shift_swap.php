<?php
require_once '../config/db.php';
require_once '../includes/auth.php';
checkRole(['employee']);

if (!function_exists('sanitizeInput')) {
    function sanitizeInput($input) {
        return trim(htmlspecialchars($input, ENT_QUOTES, 'UTF-8'));
    }
}

// ค้นหา id ของ leave_types ที่ชื่อ "สะสมวันหยุด" หรือ "สลับวันหยุด"
$stmt = $conn->prepare("SELECT id FROM leave_types WHERE name LIKE :name LIMIT 1");
$type_name = '%สะสมวันหยุด%'; // หรือจะใส่ชื่อที่ตั้งไว้
$stmt->bindParam(':name', $type_name);
$stmt->execute();
$type_row = $stmt->fetch(PDO::FETCH_ASSOC);
$leave_type_id = $type_row ? $type_row['id'] : null;

// ส่งฟอร์มขอสะสมวันหยุด
if ($_SERVER['REQUEST_METHOD'] === 'POST' && $leave_type_id) {
    $user_id = $_SESSION['user_id'];
    $date_to_save = sanitizeInput($_POST['date_to_save']);
    $reason = sanitizeInput($_POST['reason']);

    // สามารถแนบไฟล์ได้ถ้าต้องการ
    $document_path = null;
    if (isset($_FILES['document']) && $_FILES['document']['error'] === UPLOAD_ERR_OK) {
        $target_dir = '../uploads/';
        if (!is_dir($target_dir)) mkdir($target_dir, 0777, true);
        $target_file = $target_dir . uniqid() . "_" . basename($_FILES['document']['name']);
        if (move_uploaded_file($_FILES['document']['tmp_name'], $target_file)) {
            $document_path = $target_file;
        }
    }

    $stmt = $conn->prepare("INSERT INTO leave_requests 
        (user_id, leave_type_id, start_date, end_date, reason, status, document) 
        VALUES 
        (:user_id, :leave_type_id, :start_date, :end_date, :reason, 'pending', :document)");
    $stmt->bindParam(':user_id', $user_id);
    $stmt->bindParam(':leave_type_id', $leave_type_id);
    $stmt->bindParam(':start_date', $date_to_save);
    $stmt->bindParam(':end_date', $date_to_save);
    $stmt->bindParam(':reason', $reason);
    $stmt->bindParam(':document', $document_path);

    if ($stmt->execute()) {
        $_SESSION['success'] = "ยื่นคำขอสะสมวันหยุดสำเร็จ รอการอนุมัติ";
    } else {
        $_SESSION['error'] = "เกิดข้อผิดพลาดในการยื่นคำขอ";
    }
    header("Location: " . BASE_URL . "employee/shift_swap.php");
    exit();
}
?>

<?php include '../includes/header.php'; ?>
<div class="container mt-4">
    <h2>ขอยื่นสะสมวันหยุด/สลับวันหยุด</h2>
    <?php if (isset($_SESSION['success'])): ?>
        <div class="alert alert-success"><?= htmlspecialchars($_SESSION['success']) ?><?php unset($_SESSION['success']); ?></div>
    <?php endif; ?>
    <?php if (isset($_SESSION['error'])): ?>
        <div class="alert alert-danger"><?= htmlspecialchars($_SESSION['error']) ?><?php unset($_SESSION['error']); ?></div>
    <?php endif; ?>

    <div class="row">
        <div class="col-md-6">
            <div class="card mb-4">
                <div class="card-header"><h5>ฟอร์มขอสะสมวันหยุด</h5></div>
                <div class="card-body">
                    <form method="POST" enctype="multipart/form-data">
                        <div class="form-group">
                            <label>วันที่ต้องการสะสม/สลับ</label>
                            <input type="date" name="date_to_save" class="form-control" required>
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
        <!-- แสดงคำขอสะสมวันหยุดย้อนหลัง -->
        <?php
        $stmt = $conn->prepare("SELECT * FROM leave_requests WHERE user_id = :user_id AND leave_type_id = :leave_type_id ORDER BY created_at DESC");
        $stmt->bindParam(':user_id', $_SESSION['user_id']);
        $stmt->bindParam(':leave_type_id', $leave_type_id);
        $stmt->execute();
        $my_requests = $stmt->fetchAll(PDO::FETCH_ASSOC);
        ?>
            <div class="card mb-4">
                <div class="card-header"><h5>รายการขอสะสมวันหยุดของฉัน</h5></div>
                <div class="card-body">
                    <table class="table">
                        <thead>
                            <tr>
                                <th>วันที่ขอ</th>
                                <th>สถานะ</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (empty($my_requests)): ?>
                                <tr><td colspan="2" class="text-center text-muted">ยังไม่มีรายการขอสะสมวันหยุด</td></tr>
                            <?php endif; ?>
                            <?php foreach ($my_requests as $request): ?>
                                <tr>
                                    <td><?= htmlspecialchars($request['start_date']) ?></td>
                                    <td>
                                        <?php
                                        switch ($request['status']) {
                                            case 'approved':
                                                echo "<span class='badge badge-success'>อนุมัติแล้ว</span>";
                                                break;
                                            case 'rejected':
                                                echo "<span class='badge badge-danger'>ปฏิเสธ</span>";
                                                break;
                                            default:
                                                echo "<span class='badge badge-warning'>รออนุมัติ</span>";
                                        }
                                        ?>
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
<?php include '../includes/footer.php'; ?>
