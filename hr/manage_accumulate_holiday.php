<?php
require_once '../config/db.php';
require_once '../includes/auth.php';
checkRole(['hr', 'manager']);

// อนุมัติ/ปฏิเสธ
if (isset($_GET['action']) && isset($_GET['id'])) {
    $action = ($_GET['action'] === 'approve') ? 'approved' : 'rejected';
    $id = intval($_GET['id']);
    $stmt = $conn->prepare("UPDATE holiday_balance SET status = :status WHERE id = :id");
    $stmt->bindParam(':status', $action);
    $stmt->bindParam(':id', $id);
    $stmt->execute();
    $_SESSION['success'] = ($action === 'approved' ? 'อนุมัติ' : 'ปฏิเสธ') . "คำขอสะสมวันหยุดแล้ว";
    header("Location: manage_accumulate_holiday.php");
    exit();
}

// ดึงคำขอสะสมวันหยุด (ที่รออนุมัติและทั้งหมด)
$stmt = $conn->query(
    "SELECT hb.*, u.username
     FROM holiday_balance hb 
     JOIN users u ON hb.user_id = u.id 
     ORDER BY hb.updated_at DESC"
);
$requests = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<?php include '../includes/header.php'; ?>
<div class="container mt-4">
    <h2><i class="fa-solid fa-calendar-plus"></i> จัดการคำขอสะสมวันหยุด</h2>
    <?php if (isset($_SESSION['success'])): ?>
        <div class="alert alert-success"><?= htmlspecialchars($_SESSION['success']); unset($_SESSION['success']); ?></div>
    <?php endif; ?>

    <table class="table table-bordered table-hover mt-3">
        <thead>
            <tr>
                <th>พนักงาน</th>
                <th>ปี</th>
                <th>จำนวนวัน</th>
                <th>เหตุผล</th>
                <th>สถานะ</th>
                <th>วันที่ยื่น</th>
                <th>ดำเนินการ</th>
            </tr>
        </thead>
        <tbody>
            <?php if (empty($requests)): ?>
                <tr><td colspan="7" class="text-center text-muted">ยังไม่มีคำขอสะสมวันหยุด</td></tr>
            <?php endif; ?>
            <?php foreach ($requests as $row): ?>
                <tr>
                    <td><?= htmlspecialchars($row['username']) ?></td>
                    <td><?= htmlspecialchars($row['year']) ?></td>
                    <td><?= htmlspecialchars($row['days']) ?></td>
                    <td><?= htmlspecialchars($row['reason']) ?></td>
                    <td>
                        <?php
                        if ($row['status'] === 'approved') {
                            echo "<span class='badge bg-success'>อนุมัติแล้ว</span>";
                        } elseif ($row['status'] === 'rejected') {
                            echo "<span class='badge bg-danger'>ปฏิเสธ</span>";
                        } else {
                            echo "<span class='badge bg-warning text-dark'>รออนุมัติ</span>";
                        }
                        ?>
                    </td>
                    <td><?= htmlspecialchars($row['updated_at']) ?></td>
                    <td>
                        <?php if (!in_array($row['status'], ['approved', 'rejected'])): ?>
                            <a href="?action=approve&id=<?= $row['id'] ?>" class="btn btn-success btn-sm"
                               onclick="return confirm('ยืนยันอนุมัติ?')"><i class="fa fa-check"></i> อนุมัติ</a>
                            <a href="?action=reject&id=<?= $row['id'] ?>" class="btn btn-danger btn-sm"
                               onclick="return confirm('ยืนยันปฏิเสธ?')"><i class="fa fa-times"></i> ปฏิเสธ</a>
                        <?php else: ?>
                            <span class="text-muted">-</span>
                        <?php endif; ?>
                    </td>
                </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
</div>
<?php include '../includes/footer.php'; ?>
