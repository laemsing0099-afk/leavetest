<?php
require_once '../config/db.php';
require_once '../includes/auth.php';
checkRole(['employee']);

$user_id = $_SESSION['user_id'];

// ================== ฟิลเตอร์ปีและสถานะ ==================
$filter_year = isset($_GET['filter_year']) ? intval($_GET['filter_year']) : null;
$filter_status = isset($_GET['filter_status']) ? $_GET['filter_status'] : '';

// ดึงปีที่เคยยื่นขอไว้
$years_stmt = $conn->prepare("SELECT DISTINCT year FROM holiday_balance WHERE user_id = :user_id ORDER BY year DESC");
$years_stmt->bindParam(':user_id', $user_id);
$years_stmt->execute();
$all_years = $years_stmt->fetchAll(PDO::FETCH_COLUMN);

// ================== ฟอร์มยื่นสะสมวันหยุด ==================
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $year = intval($_POST['year']);
    $days = intval($_POST['days']);
    $reason = trim($_POST['reason']);

    $stmt = $conn->prepare("INSERT INTO holiday_balance (user_id, year, days, updated_at, reason) VALUES (:user_id, :year, :days, NOW(), :reason)");
    $stmt->bindParam(':user_id', $user_id);
    $stmt->bindParam(':year', $year);
    $stmt->bindParam(':days', $days);
    $stmt->bindParam(':reason', $reason);

    if ($stmt->execute()) {
        $_SESSION['success'] = "ยื่นขอสะสมวันหยุดสำเร็จ รอการอนุมัติ";
    } else {
        $_SESSION['error'] = "เกิดข้อผิดพลาด";
    }
    header("Location: accumulate_holiday.php");
    exit();
}

// ================== กรองข้อมูลประวัติ ==================
$where = "user_id = :user_id";
$params = [':user_id' => $user_id];
if ($filter_year) {
    $where .= " AND year = :year";
    $params[':year'] = $filter_year;
}
if ($filter_status) {
    $where .= " AND status = :status";
    $params[':status'] = $filter_status;
}
$sql = "SELECT * FROM holiday_balance WHERE $where ORDER BY updated_at DESC";
$stmt = $conn->prepare($sql);
$stmt->execute($params);
$my_requests = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<?php include '../includes/header.php'; ?>
<div class="container mt-4">
    <h2>ขอยื่นสะสมวันหยุด</h2>
    <?php if (isset($_SESSION['success'])): ?>
        <div class="alert alert-success"><?= htmlspecialchars($_SESSION['success']); unset($_SESSION['success']); ?></div>
    <?php endif; ?>
    <?php if (isset($_SESSION['error'])): ?>
        <div class="alert alert-danger"><?= htmlspecialchars($_SESSION['error']); unset($_SESSION['error']); ?></div>
    <?php endif; ?>

    <form method="POST" class="mb-4">
        <div class="form-group">
            <label>ปี (เช่น 2025)</label>
            <input type="number" name="year" min="2024" max="2099" class="form-control" required>
        </div>
        <div class="form-group">
            <label>จำนวนวัน</label>
            <input type="number" name="days" min="1" max="30" class="form-control" required>
        </div>
        <div class="form-group">
            <label>เหตุผล</label>
            <textarea name="reason" class="form-control" required></textarea>
        </div>
        <button type="submit" class="btn btn-primary">ยื่นขอสะสมวันหยุด</button>
    </form>

    <!-- ฟอร์ม filter ปี+สถานะ -->
    <form method="get" class="form-inline mb-2">
        <label class="mr-2">ดูประวัติปี: </label>
        <select name="filter_year" class="form-control mr-2" onchange="this.form.submit()">
            <option value="">ทั้งหมด</option>
            <?php foreach ($all_years as $y): ?>
                <option value="<?= $y ?>" <?= ($filter_year == $y) ? 'selected' : '' ?>><?= $y ?></option>
            <?php endforeach; ?>
        </select>
        <label class="mr-2">สถานะ:</label>
        <select name="filter_status" class="form-control mr-2" onchange="this.form.submit()">
            <option value="">ทั้งหมด</option>
            <option value="pending" <?= ($filter_status == 'pending') ? 'selected' : '' ?>>รออนุมัติ</option>
            <option value="approved" <?= ($filter_status == 'approved') ? 'selected' : '' ?>>อนุมัติ</option>
            <option value="rejected" <?= ($filter_status == 'rejected') ? 'selected' : '' ?>>ไม่อนุมัติ</option>
        </select>
    </form>

    <h5>ประวัติขอสะสมวันหยุด</h5>
    <table class="table table-bordered">
        <thead>
            <tr>
                <th>ปี</th>
                <th>จำนวนวัน</th>
                <th>เหตุผล</th>
                <th>วันที่ยื่น</th>
                <th>สถานะ</th>
            </tr>
        </thead>
        <tbody>
            <?php if (empty($my_requests)): ?>
                <tr><td colspan="5" class="text-center">ยังไม่มีประวัติ</td></tr>
            <?php endif; ?>
            <?php foreach ($my_requests as $row): ?>
                <tr>
                    <td><?= htmlspecialchars($row['year']) ?></td>
                    <td><?= htmlspecialchars($row['days']) ?></td>
                    <td><?= htmlspecialchars($row['reason']) ?></td>
                    <td><?= htmlspecialchars($row['updated_at']) ?></td>
                    <td>
                        <?php
                        $status_map = [
                            'pending' => '<span class="badge badge-warning text-dark">รออนุมัติ</span>',
                            'approved' => '<span class="badge badge-success">อนุมัติ</span>',
                            'rejected' => '<span class="badge badge-danger">ไม่อนุมัติ</span>',
                        ];
                        echo $status_map[$row['status']] ?? '<span class="badge badge-secondary">-</span>';
                        ?>
                    </td>
                </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
</div>
<?php include '../includes/footer.php'; ?>
