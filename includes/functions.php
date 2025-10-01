<?php
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

// PHPMailer autoload
if (!class_exists('PHPMailer\PHPMailer\PHPMailer')) {
    require_once __DIR__ . '/../vendor/autoload.php';
}

// --- ฟังก์ชันที่ระบบจำเป็นต้องมี ---
if (!function_exists('sanitizeInput')) {
    function sanitizeInput($data) {
        if ($data === null) return null;
        return htmlspecialchars(stripslashes(trim($data)), ENT_QUOTES, 'UTF-8');
    }
}

if (!function_exists('displayAlert')) {
    function displayAlert() {
        if (isset($_SESSION['success'])) {
            echo '<div class="alert alert-success">'.htmlspecialchars($_SESSION['success']).'</div>';
            unset($_SESSION['success']);
        }
        if (isset($_SESSION['error'])) {
            echo '<div class="alert alert-danger">'.htmlspecialchars($_SESSION['error']).'</div>';
            unset($_SESSION['error']);
        }
    }
}

// ====== ฟังก์ชันคำนวณวันลาคงเหลือ (พักร้อน) ======
if (!function_exists('getLeaveBalance')) {
    function getLeaveBalance($conn, $user_id, $leave_type_id, $year) {
        // ดึงวันสะสม holiday_balance (เฉพาะ status approved)
        $stmt = $conn->prepare("SELECT COALESCE(SUM(days), 0) AS total_balance
                                FROM holiday_balance 
                                WHERE user_id = :user_id AND year = :year AND status = 'approved'");
        $stmt->execute([':user_id' => $user_id, ':year' => $year]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        $total_balance = $row ? $row['total_balance'] : 0;

        // ดึงจำนวนวันที่ใช้ลาในปีนั้น
        $stmt2 = $conn->prepare("SELECT COALESCE(SUM(DATEDIFF(end_date, start_date) + 1), 0) AS days_used
                                 FROM leave_requests
                                 WHERE user_id = :user_id AND leave_type_id = :leave_type_id
                                 AND status IN ('pending', 'approved')
                                 AND YEAR(start_date) = :year");
        $stmt2->execute([':user_id' => $user_id, ':leave_type_id' => $leave_type_id, ':year' => $year]);
        $row2 = $stmt2->fetch(PDO::FETCH_ASSOC);
        $days_used = $row2 ? $row2['days_used'] : 0;

        return $total_balance - $days_used;
    }
}
?>
