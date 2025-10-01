<?php
require_once 'config/db.php';
require_once 'includes/auth.php';
require_once 'includes/functions.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: index.php");
    exit();
}

// ฟีเจอร์: อัปเดตข้อมูลโปรไฟล์
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // อัปเดตโปรไฟล์ส่วนตัว
    if (isset($_POST['update_profile'])) {
        $fullname   = sanitizeInput($_POST['fullname']);
        $email      = sanitizeInput($_POST['email']);
        $department = sanitizeInput($_POST['department']);

        $stmt = $conn->prepare("UPDATE users SET fullname = :fullname, email = :email, department = :department WHERE id = :id");
        $stmt->bindParam(':fullname', $fullname);
        $stmt->bindParam(':email', $email);
        $stmt->bindParam(':department', $department);
        $stmt->bindParam(':id', $_SESSION['user_id']);

        if ($stmt->execute()) {
            $_SESSION['success'] = "อัปเดตโปรไฟล์สำเร็จ";
            $_SESSION['fullname'] = $fullname;
            $_SESSION['email'] = $email;
            $_SESSION['department'] = $department;
        } else {
            $_SESSION['error'] = "เกิดข้อผิดพลาดในการอัปเดตโปรไฟล์";
        }

        header("Location: " . BASE_URL . "profile.php");
        exit();
    }

    // เปลี่ยนรหัสผ่าน
    if (isset($_POST['change_password'])) {
        $current_password = $_POST['current_password'];
        $new_password = $_POST['new_password'];
        $confirm_password = $_POST['confirm_password'];

        // ตรวจสอบความถูกต้อง
        if (empty($current_password) || empty($new_password) || empty($confirm_password)) {
            $_SESSION['error'] = "กรุณากรอกข้อมูลให้ครบถ้วน";
        } else if ($new_password !== $confirm_password) {
            $_SESSION['error'] = "รหัสผ่านใหม่กับการยืนยันรหัสผ่านไม่ตรงกัน";
        } else {
            // ตรวจสอบรหัสผ่านเดิม (รองรับทั้ง hash และ plain text)
            $stmt = $conn->prepare("SELECT password, password_plain FROM users WHERE id = :id");
            $stmt->bindParam(':id', $_SESSION['user_id']);
            $stmt->execute();
            $user = $stmt->fetch(PDO::FETCH_ASSOC);

            $isValidPassword = false;
            if ($user) {
                // ตรวจสอบด้วย verifyPassword (รองรับ hash และ plain text)
                if (
                    (isset($user['password']) && verifyPassword($current_password, $user['password'])) ||
                    (isset($user['password_plain']) && $current_password === $user['password_plain'])
                ) {
                    $isValidPassword = true;
                }
            }

            if (!$isValidPassword) {
                $_SESSION['error'] = "รหัสผ่านปัจจุบันไม่ถูกต้อง";
            } else {
                // อัปเดตรหัสผ่านใหม่ (ทั้ง hash และ plain text)
                $hashed_new_password = password_hash($new_password, PASSWORD_BCRYPT);
                $stmt = $conn->prepare("UPDATE users SET password = :password, password_plain = :password_plain WHERE id = :id");
                $stmt->bindParam(':password', $hashed_new_password);
                $stmt->bindParam(':password_plain', $new_password);
                $stmt->bindParam(':id', $_SESSION['user_id']);
                if ($stmt->execute()) {
                    $_SESSION['success'] = "เปลี่ยนรหัสผ่านสำเร็จ";
                } else {
                    $_SESSION['error'] = "เกิดข้อผิดพลาดในการเปลี่ยนรหัสผ่าน";
                }
            }
        }
        header("Location: " . BASE_URL . "profile.php");
        exit();
    }
}

// ดึงข้อมูลผู้ใช้ปัจจุบัน
$stmt = $conn->prepare("SELECT * FROM users WHERE id = :id");
$stmt->bindParam(':id', $_SESSION['user_id']);
$stmt->execute();
$user = $stmt->fetch(PDO::FETCH_ASSOC);

include 'includes/header.php';
?>

<div class="container mt-4">
    <h2>โปรไฟล์ของฉัน</h2>
    <?php if (isset($_SESSION['success'])): ?>
        <div class="alert alert-success"><?= htmlspecialchars($_SESSION['success']) ?><?php unset($_SESSION['success']); ?></div>
    <?php endif; ?>
    <?php if (isset($_SESSION['error'])): ?>
        <div class="alert alert-danger"><?= htmlspecialchars($_SESSION['error']) ?><?php unset($_SESSION['error']); ?></div>
    <?php endif; ?>
    <div class="row">
        <div class="col-md-6">
            <div class="card mb-4">
                <div class="card-header">
                    <h5>ข้อมูลส่วนตัว</h5>
                </div>
                <div class="card-body">
                    <form method="POST">
                        <div class="form-group mb-2">
                            <label>ชื่อผู้ใช้</label>
                            <input type="text" class="form-control" value="<?= htmlspecialchars($user['username']) ?>" readonly>
                        </div>
                        <div class="form-group mb-2">
                            <label>ชื่อ-สกุล</label>
                            <input type="text" name="fullname" class="form-control" value="<?= htmlspecialchars($user['fullname']) ?>" required>
                        </div>
                        <div class="form-group mb-2">
                            <label>อีเมล</label>
                            <input type="email" name="email" class="form-control" value="<?= htmlspecialchars($user['email']) ?>" required>
                        </div>
                        <div class="form-group mb-3">
                            <label>แผนก</label>
                            <input type="text" name="department" class="form-control" value="<?= htmlspecialchars($user['department'] ?? '') ?>">
                        </div>
                        <button type="submit" name="update_profile" class="btn btn-primary">บันทึกการเปลี่ยนแปลง</button>
                    </form>
                </div>
            </div>  
        </div>
        
        <div class="col-md-6">
            <div class="card mb-4">
                <div class="card-header">
                    <h5>เปลี่ยนรหัสผ่าน</h5>
                </div>
                <div class="card-body">
                    <form method="POST">
                        <div class="form-group mb-2">
                            <label>รหัสผ่านปัจจุบัน</label>
                            <input type="password" name="current_password" class="form-control">
                        </div>
                        <div class="form-group mb-2">
                            <label>รหัสผ่านใหม่</label>
                            <input type="password" name="new_password" class="form-control">
                        </div>
                        <div class="form-group mb-3">
                            <label>ยืนยันรหัสผ่านใหม่</label>
                            <input type="password" name="confirm_password" class="form-control">
                        </div>
                        <button type="submit" name="change_password" class="btn btn-warning">เปลี่ยนรหัสผ่าน</button>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>

<?php include 'includes/footer.php'; ?>
