<?php
require_once '../config/db.php';
require_once '../includes/auth.php';
checkRole(['employee']);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $user_id = $_SESSION['user_id'];
    $old_date = $_POST['old_date'];
    $new_date = $_POST['new_date'];
    $reason = trim($_POST['reason']);

    $document_path = null;
    if (isset($_FILES['document']) && $_FILES['document']['error'] === UPLOAD_ERR_OK) {
        $target_dir = '../uploads/';
        if (!is_dir($target_dir)) mkdir($target_dir, 0777, true);
        $target_file = $target_dir . uniqid() . "_" . basename($_FILES['document']['name']);
        if (move_uploaded_file($_FILES['document']['tmp_name'], $target_file)) {
            $document_path = $target_file;
        }
    }

    $stmt = $conn->prepare("INSERT INTO holiday_swaps (user_id, old_date, new_date, reason, status, document, created_at)
                            VALUES (:user_id, :old_date, :new_date, :reason, 'pending', :document, NOW())");
    $stmt->bindParam(':user_id', $user_id);
    $stmt->bindParam(':old_date', $old_date);
    $stmt->bindParam(':new_date', $new_date);
    $stmt->bindParam(':reason', $reason);
    $stmt->bindParam(':document', $document_path);

    if ($stmt->execute()) {
        // ==== ส่งอีเมลแจ้ง HR ====
        // 1. ดึงอีเมล HR ทั้งหมด
        $hr_stmt = $conn->query("SELECT email FROM users WHERE role = 'hr'");
        $hr_emails = $hr_stmt->fetchAll(PDO::FETCH_COLUMN);

        // 2. ดึงชื่อและอีเมลของผู้ขอ
        $user_stmt = $conn->prepare("SELECT username, email FROM users WHERE id = :id");
        $user_stmt->bindParam(':id', $user_id);
        $user_stmt->execute();
        $user = $user_stmt->fetch(PDO::FETCH_ASSOC);

        $subject = "แจ้งขอสลับวันหยุดใหม่จาก {$user['username']}";
        $message = "มีการขอสลับวันหยุดใหม่จาก {$user['username']}\n"
                 . "วันหยุดเดิม: $old_date\n"
                 . "เปลี่ยนเป็น: $new_date\n"
                 . "เหตุผล: $reason\n"
                 . "กรุณาตรวจสอบและอนุมัติผ่านระบบลางาน";

        foreach ($hr_emails as $to) {
            // หาก host ของคุณรองรับฟังก์ชัน mail() จะส่งถึง hr ทันที
            @mail($to, $subject, $message, "From: noreply@yourdomain.com");
        }

        $_SESSION['success'] = "ยื่นขอสลับวันหยุดสำเร็จ รอการอนุมัติ (แจ้ง HR แล้ว)";
    } else {
        $_SESSION['error'] = "เกิดข้อผิดพลาด";
    }
    header("Location: swap_holiday.php");
    exit();
}

// ดึงคำขอสลับวันหยุดของฉัน
$stmt = $conn->prepare("SELECT * FROM holiday_swaps WHERE user_id = :user_id ORDER BY created_at DESC");
$stmt->bindParam(':user_id', $_SESSION['user_id']);
$stmt->execute();
$my_swaps = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<?php include '../includes/header.php'; ?>
<div class="container mt-4">
    <h2>ขอสลับวันหยุด</h2>
    <?php if (isset($_SESSION['success'])): ?>
        <div class="alert alert-success"><?= htmlspecialchars($_SESSION['success']); unset($_SESSION['success']); ?></div>
    <?php endif; ?>
    <?php if (isset($_SESSION['error'])): ?>
        <div class="alert alert-danger"><?= htmlspecialchars($_SESSION['error']); unset($_SESSION['error']); ?></div>
    <?php endif; ?>

    <form method="POST" enctype="multipart/form-data" class="mb-4">
        <div class="form-group">
            <label>วันหยุดเดิม</label>
            <input type="date" name="old_date" class="form-control" required>
        </div>
        <div class="form-group">
            <label>ต้องการเปลี่ยนเป็นวัน</label>
            <input type="date" name="new_date" class="form-control" required>
        </div>
        <div class="form-group">
            <label>เหตุผล</label>
            <textarea name="reason" class="form-control" required></textarea>
        </div>
        <div class="form-group">
            <label>เอกสารประกอบ (ถ้ามี)</label>
            <input type="file" name="document" class="form-control-file">
        </div>
        <button type="submit" class="btn btn-primary">ยื่นขอสลับวันหยุด</button>
    </form>

    <h5>ประวัติขอสลับวันหยุด</h5>
    <table class="table table-bordered">
        <thead>
            <tr>
                <th>วันหยุดเดิม</th>
                <th>เปลี่ยนเป็นวัน</th>
                <th>เหตุผล</th>
                <th>สถานะ</th>
                <th>วันที่ยื่น</th>
            </tr>
        </thead>
        <tbody>
            <?php if (empty($my_swaps)): ?>
                <tr><td colspan="5" class="text-center">ยังไม่มีประวัติ</td></tr>
            <?php endif; ?>
            <?php foreach ($my_swaps as $row): ?>
                <tr>
                    <td><?= htmlspecialchars($row['old_date']) ?></td>
                    <td><?= htmlspecialchars($row['new_date']) ?></td>
                    <td><?= htmlspecialchars($row['reason']) ?></td>
                    <td>
                        <?php
                        switch ($row['status']) {
                            case 'approved': echo "<span class='badge badge-success'>อนุมัติแล้ว</span>"; break;
                            case 'rejected': echo "<span class='badge badge-danger'>ปฏิเสธ</span>"; break;
                            default: echo "<span class='badge badge-warning'>รออนุมัติ</span>";
                        }
                        ?>
                    </td>
                    <td><?= htmlspecialchars($row['created_at']) ?></td>
                </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
</div>
<?php include '../includes/footer.php'; ?>
