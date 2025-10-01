<?php
require_once 'config/db.php';
require_once 'includes/auth.php';
require_once 'includes/functions.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = sanitizeInput($_POST['username']);
    $password = sanitizeInput($_POST['password']);

    try {
        $stmt = $conn->prepare("SELECT * FROM users WHERE username = :username");
        $stmt->bindParam(':username', $username);
        $stmt->execute();
        $user = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($user && verifyPassword($password, $user['password'])) {
            session_regenerate_id(true);
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['username'] = $user['username'];
            $_SESSION['role'] = $user['role'];
            $_SESSION['department'] = $user['department'];
            $_SESSION['last_login'] = time();

            generateCSRFToken();

            $redirectMap = [
                'admin'    => 'admin/dashboard.php',
                'hr'       => 'hr/dashboard.php',
                'manager'  => 'manager/dashboard.php',
                'employee' => 'employee/dashboard.php'
            ];
            if (isset($redirectMap[$user['role']])) {
                header("Location: " . BASE_URL . $redirectMap[$user['role']]);
                exit();
            }
        }
        $error = "ชื่อผู้ใช้หรือรหัสผ่านไม่ถูกต้อง";
    } catch (PDOException $e) {
        error_log("Login error: " . $e->getMessage());
        $error = "เกิดข้อผิดพลาดในการเข้าสู่ระบบ";
    }
}
$csrfToken = generateCSRFToken();
?>
<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>ระบบลางาน - เข้าสู่ระบบ</title>
    <link rel="icon" type="image/png" href="./includes/logo/logo1.png">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="bg-light d-flex justify-content-center align-items-center" style="min-height:100vh;">
    <form method="POST" class="p-4 bg-white rounded shadow" style="width: 100%; max-width: 400px;">
        <div class="text-center mb-3">
            <img src="./includes/logo/logo1.png" alt="logo" style="width:100px; height:100px;">
            <h3 class="mt-2">ระบบลางาน</h3>
        </div>
        <?php if (isset($error)): ?>
            <div class="alert alert-danger"><?= htmlspecialchars($error) ?></div>
        <?php endif; ?>
        <input type="hidden" name="csrf_token" value="<?= $csrfToken ?>">
        <div class="mb-2">
            <label>ชื่อผู้ใช้</label>
            <input type="text" name="username" class="form-control" required autofocus>
        </div>
        <div class="mb-2">
            <label>รหัสผ่าน</label>
            <input type="password" name="password" class="form-control" required>
        </div>
        <button type="submit" class="btn btn-primary w-100">เข้าสู่ระบบ</button>
    </form>
</body>
</html>
