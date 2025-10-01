<?php
require_once '../config/db.php';
require_once '../includes/auth.php';
checkRole(['admin', 'hr']);

// --- ฟังก์ชัน sanitize (ถ้าไม่มี) ---
if (!function_exists('sanitizeInput')) {
    function sanitizeInput($input) {
        return trim(htmlspecialchars($input, ENT_QUOTES, 'UTF-8'));
    }
}

// ลบผู้ใช้ (รองรับ AJAX)
if (
    (isset($_POST['action']) && $_POST['action'] === 'delete' && isset($_POST['id']) && isset($_POST['ajax'])) ||
    (isset($_GET['action']) && $_GET['action'] === 'delete' && isset($_GET['id']))
) {
    $id_to_delete = sanitizeInput($_POST['id'] ?? $_GET['id']);

    if ($id_to_delete == $_SESSION['user_id']) {
        $msg = "ไม่สามารถลบบัญชีผู้ใช้ของตัวเองได้";
        if (isset($_POST['ajax'])) {
            http_response_code(400);
            echo $msg;
            exit();
        } else {
            $_SESSION['error'] = $msg;
        }
    } else {
        try {
            // ลบ leave_requests ที่เกี่ยวกับ user นี้ออกก่อน
            $stmt = $conn->prepare("DELETE FROM leave_requests WHERE user_id = :id");
            $stmt->bindParam(':id', $id_to_delete, PDO::PARAM_INT);
            $stmt->execute();

            // ค่อยลบ users
            $stmt = $conn->prepare("DELETE FROM users WHERE id = :id");
            $stmt->bindParam(':id', $id_to_delete, PDO::PARAM_INT);

            if ($stmt->execute()) {
                if (isset($_POST['ajax'])) {
                    echo 'success';
                    exit();
                } else {
                    $_SESSION['success'] = "ลบผู้ใช้สำเร็จ";
                }
            } else {
                $msg = "เกิดข้อผิดพลาดในการลบผู้ใช้";
                if (isset($_POST['ajax'])) {
                    http_response_code(500);
                    echo $msg;
                    exit();
                } else {
                    $_SESSION['error'] = $msg;
                }
            }
        } catch (PDOException $e) {
            $msg = "เกิดข้อผิดพลาด: " . $e->getMessage();
            if (isset($_POST['ajax'])) {
                http_response_code(500);
                echo $msg;
                exit();
            } else {
                $_SESSION['error'] = $msg;
            }
        }
    }
    if (!isset($_POST['ajax'])) {
        header("Location: " . BASE_URL . "admin/users.php");
        exit();
    }
}

// เพิ่มผู้ใช้ใหม่
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_user'])) {
    $username = sanitizeInput($_POST['username']);
    $password_plain = sanitizeInput($_POST['password']); // << เก็บ plain text
    $password = password_hash($password_plain, PASSWORD_DEFAULT);
    $fullname = sanitizeInput($_POST['fullname']);
    $email = sanitizeInput($_POST['email']);
    $role = sanitizeInput($_POST['role']);
    $department = sanitizeInput($_POST['department']);

    // ป้องกันการซ้ำ username/email (optional)
    $check = $conn->prepare("SELECT id FROM users WHERE username = :username OR email = :email");
    $check->bindParam(':username', $username);
    $check->bindParam(':email', $email);
    $check->execute();
    if ($check->rowCount() > 0) {
        $_SESSION['error'] = "Username หรือ Email นี้มีในระบบแล้ว";
        header("Location: " . BASE_URL . "admin/users.php");
        exit();
    }

    // เพิ่ม plain text password ด้วย
    $stmt = $conn->prepare("INSERT INTO users (username, password, password_plain, fullname, email, role, department, created_at) 
                          VALUES (:username, :password, :password_plain, :fullname, :email, :role, :department, NOW())");
    $stmt->bindParam(':username', $username);
    $stmt->bindParam(':password', $password);
    $stmt->bindParam(':password_plain', $password_plain);
    $stmt->bindParam(':fullname', $fullname);
    $stmt->bindParam(':email', $email);
    $stmt->bindParam(':role', $role);
    $stmt->bindParam(':department', $department);

    if ($stmt->execute()) {
        $_SESSION['success'] = "เพิ่มผู้ใช้ใหม่สำเร็จ";
    } else {
        $_SESSION['error'] = "เกิดข้อผิดพลาดในการเพิ่มผู้ใช้";
    }
    header("Location: " . BASE_URL . "admin/users.php");
    exit();
}

// ดึงข้อมูลผู้ใช้ทั้งหมด
$stmt = $conn->query("SELECT * FROM users ORDER BY department, fullname ASC");
$users = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<?php include '../includes/header.php'; ?>

<div class="container">
    <h2>จัดการผู้ใช้</h2>

    <?php if (isset($_SESSION['success'])): ?>
        <div class="alert alert-success"><?= htmlspecialchars($_SESSION['success']) ?><?php unset($_SESSION['success']); ?></div>
    <?php endif; ?>
    <?php if (isset($_SESSION['error'])): ?>
        <div class="alert alert-danger"><?= htmlspecialchars($_SESSION['error']) ?><?php unset($_SESSION['error']); ?></div>
    <?php endif; ?>

    <div class="card mb-4">
        <div class="card-header">
            <h5>เพิ่มผู้ใช้ใหม่</h5>
        </div>
        <div class="card-body">
            <form method="POST" action="<?= BASE_URL ?>admin/users.php">
                <div class="row g-3">
                    <div class="col-md-6">
                        <label for="username" class="form-label">ชื่อผู้ใช้</label>
                        <input type="text" id="username" name="username" class="form-control" required>
                    </div>
                    <div class="col-md-6">
                        <label for="password" class="form-label">รหัสผ่าน</label>
                        <input type="password" id="password" name="password" class="form-control" required>
                    </div>
                    <div class="col-md-6">
                        <label for="fullname" class="form-label">ชื่อ-สกุล</label>
                        <input type="text" id="fullname" name="fullname" class="form-control" required>
                    </div>
                    <div class="col-md-6">
                        <label for="email" class="form-label">อีเมล</label>
                        <input type="email" id="email" name="email" class="form-control" required>
                    </div>
                    <div class="col-md-6">
                        <label for="department" class="form-label">แผนก</label>
                        <input type="text" id="department" name="department" class="form-control">
                    </div>
                    <div class="col-md-6">
                        <label for="role" class="form-label">บทบาท</label>
                        <select id="role" name="role" class="form-select" required>
                            <option value="" disabled selected>-- เลือกบทบาท --</option>
                            <option value="employee">พนักงาน</option>
                            <option value="manager">หัวหน้าแผนก</option>
                            <option value="hr">ฝ่ายบุคคล</option>
                            <option value="admin">ผู้ดูแลระบบ</option>
                        </select>
                    </div>
                </div>
                <button type="submit" name="add_user" class="btn btn-primary mt-4"><i class="fas fa-plus me-2"></i>เพิ่มผู้ใช้</button>
            </form>
        </div>
    </div>

    <div class="card">
        <div class="card-header">
            <h5>รายชื่อผู้ใช้ทั้งหมด</h5>
        </div>
        <div class="card-body">
            <table class="table table-bordered" id="users-table">
                <thead>
                    <tr>
                        <th>ชื่อผู้ใช้</th>
                        <th>ชื่อ-สกุล</th>
                        <th>อีเมล</th>
                        <th>บทบาท</th>
                        <th>แผนก</th>
                        <th>วันที่สร้าง</th>
                        <th>รหัสผ่าน</th>
                        <th>การดำเนินการ</th>
                    </tr>
                </thead>
                <tbody>
                    <?php 
                    $current_department = null;
                    foreach ($users as $user): 
                        if ($user['department'] !== $current_department) {
                            $current_department = $user['department'];
                            $department_name = $current_department ? htmlspecialchars($current_department) : 'ไม่มีแผนก';
                            echo '<tr class="table-light"><td colspan="8" class="fw-bold">แผนก: ' . $department_name . '</td></tr>';
                        }
                    ?>
                        <tr id="user-row-<?= $user['id'] ?>">
                            <td><?= htmlspecialchars($user['username']) ?></td>
                            <td><?= htmlspecialchars($user['fullname']) ?></td>
                            <td><?= htmlspecialchars($user['email']) ?></td>
                            <td><?= htmlspecialchars($user['role']) ?></td>
                            <td><?= htmlspecialchars($user['department'] ?? 'N/A') ?></td>
                            <td><?= isset($user['created_at']) ? date('d/m/Y', strtotime($user['created_at'])) : '-' ?></td>
                            <td>
                                <?php if (!empty($user['password_plain'])): ?>
                                    <input type="text" class="form-control form-control-sm" value="<?= htmlspecialchars($user['password_plain']) ?>" readonly>
                                <?php else: ?>
                                    <span class="text-muted">-</span>
                                <?php endif; ?>
                            </td>
                            <td>
                                <a href="edit_user.php?id=<?= $user['id'] ?>" class="btn btn-warning btn-sm me-1">
                                    <i class="fas fa-edit"></i> แก้ไข
                                </a>
                                <?php if ($user['id'] != $_SESSION['user_id']): ?>
                                    <button 
                                        class="btn btn-danger btn-sm delete-user-btn" 
                                        data-id="<?= $user['id'] ?>">
                                        <i class="fas fa-trash"></i> ลบ
                                    </button>
                                <?php endif; ?>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<!-- jQuery (ต้องมี) -->
<script src="https://code.jquery.com/jquery-3.7.0.min.js"></script>
<script>
$(function(){
    $('.delete-user-btn').on('click', function(){
        var id = $(this).data('id');
        var $row = $('#user-row-' + id);
        if(confirm('คุณแน่ใจหรือไม่ว่าต้องการลบผู้ใช้นี้? การกระทำนี้ไม่สามารถย้อนกลับได้')){
            $.ajax({
                url: 'users.php',
                type: 'POST',
                data: { action: 'delete', id: id, ajax: 1 },
                success: function(resp){
                    $row.fadeOut(300, function(){ $(this).remove(); });
                },
                error: function(xhr){
                    var msg = xhr.responseText ? xhr.responseText : 'เกิดข้อผิดพลาดในการลบ';
                    alert(msg);
                }
            });
        }
    });
});
</script>

<?php include '../includes/footer.php'; ?>
