<?php
require_once 'config/db.php';
require_once 'includes/auth.php';

// ป้องกันกรณี session ยังไม่เริ่ม
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

$message = "ขออภัย! คุณไม่มีสิทธิ์เข้าถึงหน้านี้";
if (isset($_SESSION['user_id'])) {
    $message .= " ในบทบาท " . htmlspecialchars($_SESSION['role']);
} else {
    $message .= " กรุณาล็อกอินก่อน";
}
?>

<?php include 'includes/header.php'; ?>

<div class="container mt-5">
    <div class="row justify-content-center">
        <div class="col-md-8 text-center">
            <div class="alert alert-danger">
                <h1><i class="fas fa-exclamation-triangle"></i> 403 Forbidden</h1>
                <p class="lead"><?= htmlspecialchars($message) ?></p>
            </div>
            <a href="<?= BASE_URL ?>/index.php" class="btn btn-primary">
                <i class="fas fa-home"></i> กลับสู่หน้าหลัก
            </a>
            <?php if (!isset($_SESSION['user_id'])): ?>
                <a href="<?= BASE_URL ?>/index.php" class="btn btn-success ml-2">
                    <i class="fas fa-sign-in-alt"></i> เข้าสู่ระบบ
                </a>
            <?php endif; ?>
        </div>
    </div>
</div>

<?php include 'includes/footer.php'; ?>
