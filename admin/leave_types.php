<?php
require_once '../config/db.php';
require_once '../includes/auth.php';
checkRole(['admin', 'hr']);

if (!function_exists('sanitizeInput')) {
    function sanitizeInput($input) {
        return trim(htmlspecialchars($input, ENT_QUOTES, 'UTF-8'));
    }
}

// เพิ่มประเภทการลา
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'add') {
    $name = sanitizeInput($_POST['name']);
    $desc = sanitizeInput($_POST['description']);
    if ($name !== '') {
        $stmt = $conn->prepare("INSERT INTO leave_types (name, description) VALUES (?, ?)");
        $stmt->execute([$name, $desc]);
        $_SESSION['success'] = "เพิ่มประเภทการลาสำเร็จ";
    } else {
        $_SESSION['error'] = "กรุณากรอกชื่อประเภทการลา";
    }
    header("Location: ".$_SERVER['PHP_SELF']); exit;
}

// ลบประเภทการลา
if (isset($_GET['delete_id'])) {
    $delete_id = intval($_GET['delete_id']);
    // หมายเหตุ: ควรเช็คก่อนว่ามี leave_requests ใช้งานหรือไม่
    $stmt = $conn->prepare("DELETE FROM leave_types WHERE id = ?");
    $stmt->execute([$delete_id]);
    $_SESSION['success'] = "ลบประเภทการลาสำเร็จ";
    header("Location: ".$_SERVER['PHP_SELF']); exit;
}

// แก้ไขประเภทการลา
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'edit') {
    $edit_id = intval($_POST['edit_id']);
    $edit_name = sanitizeInput($_POST['edit_name']);
    $edit_desc = sanitizeInput($_POST['edit_description']);
    if ($edit_name !== '') {
        $stmt = $conn->prepare("UPDATE leave_types SET name=?, description=? WHERE id=?");
        $stmt->execute([$edit_name, $edit_desc, $edit_id]);
        $_SESSION['success'] = "แก้ไขประเภทการลาสำเร็จ";
    } else {
        $_SESSION['error'] = "กรุณากรอกชื่อประเภทการลา";
    }
    header("Location: ".$_SERVER['PHP_SELF']); exit;
}

// ดึงข้อมูล leave_types ทั้งหมด
$leave_types = $conn->query("SELECT * FROM leave_types ORDER BY id ASC")->fetchAll(PDO::FETCH_ASSOC);

include '../includes/header.php';
?>

<div class="container mt-4">
    <h2>จัดการประเภทการลา</h2>

    <?php if (isset($_SESSION['success'])): ?>
        <div class="alert alert-success"><?= $_SESSION['success']; unset($_SESSION['success']); ?></div>
    <?php endif; ?>
    <?php if (isset($_SESSION['error'])): ?>
        <div class="alert alert-danger"><?= $_SESSION['error']; unset($_SESSION['error']); ?></div>
    <?php endif; ?>

    <div class="card mb-4">
        <div class="card-header"><h5>เพิ่มประเภทการลาใหม่</h5></div>
        <div class="card-body">
            <form method="post">
                <input type="hidden" name="action" value="add">
                <div class="form-group">
                    <label>ชื่อประเภทการลา</label>
                    <input type="text" name="name" class="form-control" required>
                </div>
                <div class="form-group">
                    <label>รายละเอียด/เงื่อนไข</label>
                    <input type="text" name="description" class="form-control">
                </div>
                <button type="submit" class="btn btn-success">เพิ่ม</button>
            </form>
        </div>
    </div>

    <div class="card">
        <div class="card-header"><h5>ประเภทการลาทั้งหมด</h5></div>
        <div class="card-body p-0">
            <table class="table table-bordered table-sm mb-0">
                <thead>
                    <tr>
                        <th style="width:50px;">#</th>
                        <th>ชื่อประเภทการลา</th>
                        <th>รายละเอียด</th>
                        <th style="width:150px;">จัดการ</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($leave_types as $lt): ?>
                        <tr>
                        <?php if (isset($_GET['edit_id']) && $_GET['edit_id'] == $lt['id']): ?>
                            <form method="post" class="form-inline">
                                <input type="hidden" name="action" value="edit">
                                <input type="hidden" name="edit_id" value="<?= $lt['id'] ?>">
                                <td><?= $lt['id'] ?></td>
                                <td>
                                    <input type="text" name="edit_name" value="<?= htmlspecialchars($lt['name']) ?>" class="form-control form-control-sm mr-2" required>
                                </td>
                                <td>
                                    <input type="text" name="edit_description" value="<?= htmlspecialchars($lt['description']) ?>" class="form-control form-control-sm mr-2">
                                </td>
                                <td>
                                    <button type="submit" class="btn btn-primary btn-sm mr-2">บันทึก</button>
                                    <a href="<?= $_SERVER['PHP_SELF'] ?>" class="btn btn-secondary btn-sm">ยกเลิก</a>
                                </td>
                            </form>
                        <?php else: ?>
                            <td><?= $lt['id'] ?></td>
                            <td><?= htmlspecialchars($lt['name']) ?></td>
                            <td><?= htmlspecialchars($lt['description']) ?></td>
                            <td>
                                <a href="?edit_id=<?= $lt['id'] ?>" class="btn btn-warning btn-sm mr-2">แก้ไข</a>
                                <a href="?delete_id=<?= $lt['id'] ?>" class="btn btn-danger btn-sm" onclick="return confirm('ยืนยันการลบ?')">ลบ</a>
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
