<?php
require_once '../config/db.php';
require_once '../includes/auth.php';
checkRole(['admin', 'hr']);

if (!function_exists('sanitizeInput')) {
    function sanitizeInput($input) {
        return trim(htmlspecialchars($input, ENT_QUOTES, 'UTF-8'));
    }
}

// ========== เพิ่มเงื่อนไขใหม่ ==========
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'add') {
    $description = sanitizeInput($_POST['description']);
    $leave_type_id = !empty($_POST['leave_type_id']) ? intval($_POST['leave_type_id']) : null;
    $max_days = !empty($_POST['max_days']) ? intval($_POST['max_days']) : null;
    $min_notice_days = !empty($_POST['min_notice_days']) ? intval($_POST['min_notice_days']) : null;
    $max_requests_per_month = !empty($_POST['max_requests_per_month']) ? intval($_POST['max_requests_per_month']) : null;
    $blackout_start_date = !empty($_POST['blackout_start_date']) ? sanitizeInput($_POST['blackout_start_date']) : null;
    $blackout_end_date = !empty($_POST['blackout_end_date']) ? sanitizeInput($_POST['blackout_end_date']) : null;
    $department = sanitizeInput($_POST['department']);
    $created_by = $_SESSION['user_id'];
    $rule_name = null; // ตัดชื่อเงื่อนไขออก

    $stmt = $conn->prepare("INSERT INTO leave_rules 
        (rule_name, description, leave_type_id, max_days, min_notice_days, max_requests_per_month, blackout_start_date, blackout_end_date, department, created_by) 
        VALUES (:rule_name, :description, :leave_type_id, :max_days, :min_notice_days, :max_requests_per_month, :blackout_start_date, :blackout_end_date, :department, :created_by)");
    $stmt->bindParam(':rule_name', $rule_name);
    $stmt->bindParam(':description', $description);
    $stmt->bindParam(':leave_type_id', $leave_type_id, PDO::PARAM_INT);
    $stmt->bindParam(':max_days', $max_days, PDO::PARAM_INT);
    $stmt->bindParam(':min_notice_days', $min_notice_days, PDO::PARAM_INT);
    $stmt->bindParam(':max_requests_per_month', $max_requests_per_month, PDO::PARAM_INT);
    $stmt->bindParam(':blackout_start_date', $blackout_start_date);
    $stmt->bindParam(':blackout_end_date', $blackout_end_date);
    $stmt->bindParam(':department', $department);
    $stmt->bindParam(':created_by', $created_by, PDO::PARAM_INT);

    if ($stmt->execute()) {
        $_SESSION['success'] = "เพิ่มเงื่อนไขการลาสำเร็จ";
    } else {
        $_SESSION['error'] = "เกิดข้อผิดพลาดในการเพิ่มเงื่อนไข";
    }
    header("Location: " . $_SERVER['PHP_SELF']); exit();
}

// ========== ลบ ==========
if (isset($_GET['delete_id'])) {
    $id = intval($_GET['delete_id']);
    $stmt = $conn->prepare("DELETE FROM leave_rules WHERE id = ?");
    $stmt->execute([$id]);
    $_SESSION['success'] = "ลบเงื่อนไขเรียบร้อยแล้ว";
    header("Location: " . $_SERVER['PHP_SELF']); exit();
}

// ========== แก้ไข ==========
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'edit') {
    $id = intval($_POST['edit_id']);
    $description = sanitizeInput($_POST['edit_description']);
    $leave_type_id = !empty($_POST['edit_leave_type_id']) ? intval($_POST['edit_leave_type_id']) : null;
    $max_days = !empty($_POST['edit_max_days']) ? intval($_POST['edit_max_days']) : null;
    $min_notice_days = !empty($_POST['edit_min_notice_days']) ? intval($_POST['edit_min_notice_days']) : null;
    $max_requests_per_month = !empty($_POST['edit_max_requests_per_month']) ? intval($_POST['edit_max_requests_per_month']) : null;
    $blackout_start_date = !empty($_POST['edit_blackout_start_date']) ? sanitizeInput($_POST['edit_blackout_start_date']) : null;
    $blackout_end_date = !empty($_POST['edit_blackout_end_date']) ? sanitizeInput($_POST['edit_blackout_end_date']) : null;
    $department = sanitizeInput($_POST['edit_department']);

    $stmt = $conn->prepare("UPDATE leave_rules SET 
        description = :description, leave_type_id = :leave_type_id,
        max_days = :max_days, min_notice_days = :min_notice_days, max_requests_per_month = :max_requests_per_month,
        blackout_start_date = :blackout_start_date, blackout_end_date = :blackout_end_date,
        department = :department
        WHERE id = :id");
    $stmt->bindParam(':description', $description);
    $stmt->bindParam(':leave_type_id', $leave_type_id, PDO::PARAM_INT);
    $stmt->bindParam(':max_days', $max_days, PDO::PARAM_INT);
    $stmt->bindParam(':min_notice_days', $min_notice_days, PDO::PARAM_INT);
    $stmt->bindParam(':max_requests_per_month', $max_requests_per_month, PDO::PARAM_INT);
    $stmt->bindParam(':blackout_start_date', $blackout_start_date);
    $stmt->bindParam(':blackout_end_date', $blackout_end_date);
    $stmt->bindParam(':department', $department);
    $stmt->bindParam(':id', $id, PDO::PARAM_INT);

    if ($stmt->execute()) {
        $_SESSION['success'] = "แก้ไขเงื่อนไขสำเร็จ";
    } else {
        $_SESSION['error'] = "เกิดข้อผิดพลาดในการแก้ไข";
    }
    header("Location: " . $_SERVER['PHP_SELF']); exit();
}

// ========== ดึงข้อมูล ==========
$stmt = $conn->query("SELECT lr.*, lt.name as leave_type_name 
                     FROM leave_rules lr
                     LEFT JOIN leave_types lt ON lr.leave_type_id = lt.id
                     ORDER BY lr.created_at DESC");
$rules = $stmt->fetchAll(PDO::FETCH_ASSOC);

$leave_types = $conn->query("SELECT * FROM leave_types")->fetchAll(PDO::FETCH_ASSOC);

include '../includes/header.php';
?>

<div class="container mt-4">
    <h2>ตั้งค่าเงื่อนไขการลา</h2>

    <?php if (isset($_SESSION['success'])): ?>
        <div class="alert alert-success"><?= htmlspecialchars($_SESSION['success']) ?><?php unset($_SESSION['success']); ?></div>
    <?php endif; ?>
    <?php if (isset($_SESSION['error'])): ?>
        <div class="alert alert-danger"><?= htmlspecialchars($_SESSION['error']) ?><?php unset($_SESSION['error']); ?></div>
    <?php endif; ?>

    <div class="card mb-4">
        <div class="card-header"><h5>เพิ่มเงื่อนไขใหม่</h5></div>
        <div class="card-body">
            <form method="POST">
                <input type="hidden" name="action" value="add">

                <div class="form-group">
                    <label>ประเภทการลา</label>
                    <select name="leave_type_id" class="form-control" required>
                        <option value="">-- เลือกประเภทการลา --</option>
                        <?php foreach ($leave_types as $type): ?>
                            <option value="<?= htmlspecialchars($type['id']) ?>"><?= htmlspecialchars($type['name']) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="form-group">
                    <label>คำอธิบาย</label>
                    <textarea name="description" class="form-control"></textarea>
                </div>
                <div class="form-row">
                    <div class="form-group col-md-4">
                        <label>จำนวนวันลาสูงสุดต่อครั้ง</label>
                        <input type="number" name="max_days" class="form-control" min="0">
                    </div>
                    <div class="form-group col-md-4">
                        <label>แจ้งล่วงหน้าอย่างน้อย (วัน)</label>
                        <input type="number" name="min_notice_days" class="form-control" min="0">
                    </div>
                    <div class="form-group col-md-4">
                        <label>ลาสูงสุด (ครั้ง/เดือน)</label>
                        <input type="number" name="max_requests_per_month" class="form-control" min="0">
                    </div>
                </div>
                <div class="form-row">
                    <div class="form-group col-md-6">
                        <label>ช่วงเวลาห้ามลา (เริ่มต้น)</label>
                        <input type="date" name="blackout_start_date" class="form-control">
                    </div>
                    <div class="form-group col-md-6">
                        <label>ช่วงเวลาห้ามลา (สิ้นสุด)</label>
                        <input type="date" name="blackout_end_date" class="form-control">
                    </div>
                </div>
                <div class="form-group">
                    <label>แผนก (เว้นว่างหากใช้กับทุกแผนก)</label>
                    <input type="text" name="department" class="form-control">
                </div>
                <button type="submit" class="btn btn-primary">บันทึกเงื่อนไข</button>
            </form>
        </div>
    </div>

    <div class="card">
        <div class="card-header"><h5>เงื่อนไขการลาทั้งหมด</h5></div>
        <div class="card-body">
            <table class="table table-bordered table-sm mb-0">
                <thead class="thead-dark">
                    <tr>
                        <th>ประเภทการลา</th>
                        <th>วันลาสูงสุด</th>
                        <th>แจ้งล่วงหน้า</th>
                        <th>ลาสูงสุด/เดือน</th>
                        <th>ช่วงห้ามลา</th>
                        <th>แผนก</th>
                        <th>คำอธิบาย</th>
                        <th>จัดการ</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($rules as $rule): ?>
                        <tr>
                        <?php if (isset($_GET['edit_id']) && $_GET['edit_id'] == $rule['id']): ?>
                            <form method="POST">
                                <input type="hidden" name="action" value="edit">
                                <input type="hidden" name="edit_id" value="<?= $rule['id'] ?>">
                                <td>
                                    <select name="edit_leave_type_id" class="form-control form-control-sm" required>
                                        <option value="">-- เลือกประเภท --</option>
                                        <?php foreach ($leave_types as $type): ?>
                                            <option value="<?= $type['id'] ?>" <?= $rule['leave_type_id'] == $type['id'] ? 'selected' : '' ?>><?= htmlspecialchars($type['name']) ?></option>
                                        <?php endforeach; ?>
                                    </select>
                                </td>
                                <td><input type="number" name="edit_max_days" class="form-control form-control-sm" value="<?= htmlspecialchars($rule['max_days']) ?>"></td>
                                <td><input type="number" name="edit_min_notice_days" class="form-control form-control-sm" value="<?= htmlspecialchars($rule['min_notice_days']) ?>"></td>
                                <td><input type="number" name="edit_max_requests_per_month" class="form-control form-control-sm" value="<?= htmlspecialchars($rule['max_requests_per_month']) ?>"></td>
                                <td>
                                    <input type="date" name="edit_blackout_start_date" class="form-control form-control-sm mb-1" value="<?= htmlspecialchars($rule['blackout_start_date']) ?>">
                                    <input type="date" name="edit_blackout_end_date" class="form-control form-control-sm" value="<?= htmlspecialchars($rule['blackout_end_date']) ?>">
                                </td>
                                <td><input type="text" name="edit_department" class="form-control form-control-sm" value="<?= htmlspecialchars($rule['department']) ?>"></td>
                                <td><input type="text" name="edit_description" class="form-control form-control-sm" value="<?= htmlspecialchars($rule['description']) ?>"></td>
                                <td>
                                    <button type="submit" class="btn btn-sm btn-primary">บันทึก</button>
                                    <a href="<?= $_SERVER['PHP_SELF'] ?>" class="btn btn-sm btn-secondary">ยกเลิก</a>
                                </td>
                            </form>
                        <?php else: ?>
                            <td><?= htmlspecialchars($rule['leave_type_name'] ?? '-') ?></td>
                            <td><?= $rule['max_days'] ? htmlspecialchars($rule['max_days']) . ' วัน' : '-' ?></td>
                            <td><?= $rule['min_notice_days'] ? htmlspecialchars($rule['min_notice_days']) . ' วัน' : '-' ?></td>
                            <td><?= $rule['max_requests_per_month'] ? htmlspecialchars($rule['max_requests_per_month']) : '-' ?></td>
                            <td>
                                <?php if ($rule['blackout_start_date'] && $rule['blackout_end_date']): ?>
                                    <?= htmlspecialchars($rule['blackout_start_date']) . ' ถึง ' . htmlspecialchars($rule['blackout_end_date']) ?>
                                <?php else: ?>
                                    -
                                <?php endif; ?>
                            </td>
                            <td><?= htmlspecialchars($rule['department'] ?? 'ทุกแผนก') ?></td>
                            <td><?= htmlspecialchars($rule['description']) ?></td>
                            <td>
                                <a href="?edit_id=<?= $rule['id'] ?>" class="btn btn-sm btn-warning">แก้ไข</a>
                                <a href="?delete_id=<?= $rule['id'] ?>" class="btn btn-sm btn-danger" onclick="return confirm('ยืนยันการลบ?')">ลบ</a>
                            </td>
                        <?php endif; ?>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<?php include '../includes/footer.php'; ?>
