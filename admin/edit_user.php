<?php
require_once '../config/db.php';
require_once '../includes/auth.php';
checkRole(['admin', 'hr']);

if (!function_exists('sanitizeInput')) {
    function sanitizeInput($input) {
        return trim(htmlspecialchars($input, ENT_QUOTES, 'UTF-8'));
    }
}

// รับ id ผู้ใช้จาก GET
$id = isset($_GET['id']) ? intval($_GET['id']) : 0;

// ดึงข้อมูลผู้ใช้
$stmt = $conn->prepare("SELECT * FROM users WHERE id = :id");
$stmt->bindParam(':id', $id, PDO::PARAM_INT);
$stmt->execute();
$user = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$user) {
    echo '<div class="container mt-5"><div class="alert alert-danger">ไม่พบข้อมูลผู้ใช้</div></div>';
    exit();
}

// ลบผู้ใช้ (admin/hr เท่านั้น)
if (isset($_POST['delete_user']) && $id != $_SESSION['user_id']) {
    try {
        // ลบ leave_requests ที่เกี่ยวกับ user นี้ออกก่อน
        $stmt = $conn->prepare("DELETE FROM leave_requests WHERE user_id = :id");
        $stmt->bindParam(':id', $id, PDO::PARAM_INT);
        $stmt->execute();

        // ค่อยลบ users
        $stmt = $conn->prepare("DELETE FROM users WHERE id = :id");
        $stmt->bindParam(':id', $id, PDO::PARAM_INT);
        $stmt->execute();

        $_SESSION['success'] = "ลบผู้ใช้สำเร็จ";
        header("Location: " . BASE_URL . "admin/users.php");
        exit();
    } catch (PDOException $e) {
        $_SESSION['error'] = "เกิดข้อผิดพลาด: " . $e->getMessage();
    }
}

// อัปเดตข้อมูลผู้ใช้
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['save'])) {
    $fullname   = sanitizeInput($_POST['fullname']);
    $email      = sanitizeInput($_POST['email']);
    $role       = sanitizeInput($_POST['role']);
    $department = sanitizeInput($_POST['department']);

    $set = "fullname = :fullname, email = :email, role = :role, department = :department";
    $params = [
        ':fullname' => $fullname,
        ':email'    => $email,
        ':role'     => $role,
        ':department' => $department,
        ':id'       => $id
    ];

    // ถ้ามีการเปลี่ยนรหัสผ่านใหม่ (ไม่ว่าง)
    if (!empty($_POST['password'])) {
        $new_password_plain = $_POST['password'];
        $new_password_hash  = password_hash($new_password_plain, PASSWORD_DEFAULT);
        $set .= ", password = :password, password_plain = :password_plain";
        $params[':password'] = $new_password_hash;
        $params[':password_plain'] = $new_password_plain;
    }

    $sql = "UPDATE users SET $set WHERE id = :id";
    $stmt = $conn->prepare($sql);

    if ($stmt->execute($params)) {
        $_SESSION['success'] = "อัปเดตข้อมูลผู้ใช้เรียบร้อยแล้ว";
        // รีเฟรชข้อมูลล่าสุด
        $stmt = $conn->prepare("SELECT * FROM users WHERE id = :id");
        $stmt->bindParam(':id', $id, PDO::PARAM_INT);
        $stmt->execute();
        $user = $stmt->fetch(PDO::FETCH_ASSOC);
    } else {
        $_SESSION['error'] = "เกิดข้อผิดพลาดในการบันทึกข้อมูล";
    }
}

include '../includes/header.php';
?>

<div class="container mt-4" style="max-width: 600px;">
    <h2 class="mb-3">แก้ไขข้อมูลผู้ใช้</h2>

    <?php if (isset($_SESSION['success'])): ?>
        <div class="alert alert-success"><?= htmlspecialchars($_SESSION['success']) ?><?php unset($_SESSION['success']); ?></div>
    <?php endif; ?>
    <?php if (isset($_SESSION['error'])): ?>
        <div class="alert alert-danger"><?= htmlspecialchars($_SESSION['error']) ?><?php unset($_SESSION['error']); ?></div>
    <?php endif; ?>

    <form method="POST" autocomplete="off">
        <div class="mb-3">
            <label for="username" class="form-label">ชื่อผู้ใช้</label>
            <input type="text" class="form-control" id="username" value="<?= htmlspecialchars($user['username']) ?>" readonly>
        </div>
        <div class="mb-3">
            <label for="fullname" class="form-label">ชื่อ-สกุล</label>
            <input type="text" name="fullname" class="form-control" id="fullname" value="<?= htmlspecialchars($user['fullname']) ?>" required>
        </div>
        <div class="mb-3">
            <label for="email" class="form-label">อีเมล</label>
            <input type="email" name="email" class="form-control" id="email" value="<?= htmlspecialchars($user['email']) ?>" required>
        </div>
        <div class="mb-3">
            <label for="department" class="form-label">แผนก</label>
            <input type="text" name="department" class="form-control" id="department" value="<?= htmlspecialchars($user['department'] ?? '') ?>">
        </div>
        <div class="mb-3">
            <label for="role" class="form-label">บทบาท</label>
            <select name="role" id="role" class="form-select" required>
                <option value="employee" <?= $user['role']=='employee'?'selected':'' ?>>พนักงาน</option>
                <option value="manager" <?= $user['role']=='manager'?'selected':'' ?>>หัวหน้าแผนก</option>
                <option value="hr"      <?= $user['role']=='hr'?'selected':'' ?>>ฝ่ายบุคคล</option>
                <option value="admin"   <?= $user['role']=='admin'?'selected':'' ?>>ผู้ดูแลระบบ</option>
            </select>
        </div>
        <div class="mb-3">
            <label for="password" class="form-label">
                รหัสผ่านใหม่ <small class="text-muted">(ไม่ต้องกรอกถ้าไม่เปลี่ยน)</small>
            </label>
            <input type="text" name="password" class="form-control" id="password" placeholder="กรอกรหัสผ่านใหม่ถ้าต้องการเปลี่ยน">
            <?php if (!empty($user['password_plain'])): ?>
                <div class="form-text mt-2">
                    <span>รหัสผ่านปัจจุบันในระบบ: </span>
                    <input type="text" class="form-control form-control-sm d-inline w-auto" style="width:auto; display:inline-block;" value="<?= htmlspecialchars($user['password_plain']) ?>" readonly>
                </div>
            <?php else: ?>
                <div class="form-text text-muted">ไม่พบรหัสผ่านเดิม (หรือเป็น hash-only)</div>
            <?php endif; ?>
        </div>
        <button type="submit" name="save" class="btn btn-primary">บันทึกการเปลี่ยนแปลง</button>
        <?php if ($id != $_SESSION['user_id']): ?>
            <button type="submit" name="delete_user" class="btn btn-danger ms-2" onclick="return confirm('ต้องการลบผู้ใช้นี้จริงหรือไม่?');">ลบผู้ใช้นี้</button>
        <?php endif; ?>
        <a href="users.php" class="btn btn-secondary ms-2">กลับ</a>
    </form>
</div>

<?php include '../includes/footer.php'; ?>
