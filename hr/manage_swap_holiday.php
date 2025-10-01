<?php
require_once '../config/db.php';
require_once '../includes/auth.php';
checkRole(['hr', 'manager']);

if (isset($_GET['action']) && isset($_GET['id'])) {
    $action = ($_GET['action'] === 'approve') ? 'approved' : 'rejected';
    $id = intval($_GET['id']);
    $stmt = $conn->prepare("UPDATE holiday_swaps SET status = :status WHERE id = :id");
    $stmt->bindParam(':status', $action);
    $stmt->bindParam(':id', $id);
    $stmt->execute();
    $_SESSION['success'] = ($action === 'approved' ? 'อนุมัติ' : 'ปฏิเสธ') . "คำขอสลับวันหยุดแล้ว";
    header("Location: manage_swap_holiday.php");
    exit();
}

// ดึงคำขอสลับวันหยุดทั้งหมด
$stmt = $conn->query(
    "SELECT hs.*, u.username
     FROM holiday_swaps hs 
     JOIN users u ON hs.user_id = u.id 
     ORDER BY hs.created_at DESC"
);
$requests = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<?php include '../includes/header.php'; ?>
<link href="https://cdn.jsdelivr.net/npm/fullcalendar@6.1.10/index.global.min.css" rel="stylesheet">
<div class="container mt-4">
    <h2><i class="fa-solid fa-retweet"></i> จัดการคำขอสลับวันหยุด</h2>
    <?php if (isset($_SESSION['success'])): ?>
        <div class="alert alert-success"><?= htmlspecialchars($_SESSION['success']); unset($_SESSION['success']); ?></div>
    <?php endif; ?>

    <!-- ปฏิทิน -->
    <div class="card mt-4 mb-4">
        <div class="card-header"><i class="fa-solid fa-calendar-days"></i> ปฏิทินคำขอสลับวันหยุด</div>
        <div class="card-body">
            <div id="calendar"></div>
        </div>
    </div>
    <!-- จบปฏิทิน -->

    <table class="table table-bordered table-hover mt-3">
        <thead>
            <tr>
                <th>พนักงาน</th>
                <th>วันหยุดเดิม</th>
                <th>ต้องการเปลี่ยนเป็นวัน</th>
                <th>เหตุผล</th>
                <th>สถานะ</th>
                <th>วันที่ยื่น</th>
                <th>เอกสาร</th>
                <th>ดำเนินการ</th>
            </tr>
        </thead>
        <tbody>
            <?php if (empty($requests)): ?>
                <tr><td colspan="8" class="text-center text-muted">ยังไม่มีคำขอสลับวันหยุด</td></tr>
            <?php endif; ?>
            <?php foreach ($requests as $row): ?>
                <tr>
                    <td><?= htmlspecialchars($row['username']) ?></td>
                    <td><?= htmlspecialchars($row['old_date']) ?></td>
                    <td><?= htmlspecialchars($row['new_date']) ?></td>
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
                    <td><?= htmlspecialchars($row['created_at']) ?></td>
                    <td>
                        <?php if ($row['document']): ?>
                            <a href="<?= htmlspecialchars($row['document']) ?>" target="_blank">ดาวน์โหลด</a>
                        <?php else: ?>
                            <span class="text-muted">-</span>
                        <?php endif; ?>
                    </td>
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
<script src="https://cdn.jsdelivr.net/npm/fullcalendar@6.1.10/index.global.min.js"></script>
<script>
document.addEventListener('DOMContentLoaded', function() {
    var calendarEl = document.getElementById('calendar');
    if(calendarEl){
        var calendar = new FullCalendar.Calendar(calendarEl, {
            initialView: 'dayGridMonth',
            locale: 'th',
            height: 530,
            events: 'holiday_swaps_events.php',
            eventClick: function(info) {
                if(info.event.url){
                    window.open(info.event.url, "_blank");
                    info.jsEvent.preventDefault();
                }
            },
            headerToolbar: {
                left: 'prev,next today',
                center: 'title',
                right: 'dayGridMonth,timeGridWeek'
            }
        });
        calendar.render();
    }
});
</script>
<?php include '../includes/footer.php'; ?>
