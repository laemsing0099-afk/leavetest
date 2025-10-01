<?php
require_once '../config/db.php';
require_once '../includes/auth.php';
checkRole(['employee']);

// ฟังก์ชัน sanitizeInput (ถ้าไม่มีในระบบ)
if (!function_exists('sanitizeInput')) {
    function sanitizeInput($input) {
        return trim(htmlspecialchars($input, ENT_QUOTES, 'UTF-8'));
    }
}

if (isset($_GET['id']) && isset($_SESSION['user_id'])) {
    $swap_id = sanitizeInput($_GET['id']);
    $user_id = $_SESSION['user_id'];
    
    // ตรวจสอบว่าคำขอสลับวันนี้ยังไม่ได้รับการตอบรับและเป็นของแผนกเดียวกัน
    $stmt = $conn->prepare("SELECT ss.*, u.department 
                           FROM shift_swaps ss
                           JOIN users u ON ss.requester_id = u.id
                           WHERE ss.id = :swap_id 
                           AND ss.status = 'pending' 
                           AND u.department = (SELECT department FROM users WHERE id = :user_id)");
    $stmt->bindParam(':swap_id', $swap_id);
    $stmt->bindParam(':user_id', $user_id);
    $stmt->execute();
    $swap_request = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($swap_request) {
        // อัปเดตสถานะเป็น rejected
        $stmt = $conn->prepare("UPDATE shift_swaps 
                               SET status = 'rejected' 
                               WHERE id = :swap_id");
        $stmt->bindParam(':swap_id', $swap_id);
        
        if ($stmt->execute()) {
            $_SESSION['success'] = "คุณได้ปฏิเสธการสลับวันทำงานเรียบร้อยแล้ว";
        } else {
            $_SESSION['error'] = "เกิดข้อผิดพลาดในการปฏิเสธ";
        }
    } else {
        $_SESSION['error'] = "ไม่พบคำขอสลับวันทำงานนี้หรือคุณไม่มีสิทธิ์ปฏิเสธ";
    }
} else {
    $_SESSION['error'] = "ไม่ระบุคำขอสลับวันทำงาน";
}

header("Location: " . BASE_URL . "employee/shift_swap.php");
exit();
?>
