<?php
require_once '../config/db.php';
require_once '../includes/auth.php';
require_once '../includes/functions.php'; // <--- เพิ่ม
checkRole(['employee']);

// ฟังก์ชัน sanitizeInput (ถ้ายังไม่มี)
if (!function_exists('sanitizeInput')) {
    function sanitizeInput($input) {
        return trim(htmlspecialchars($input, ENT_QUOTES, 'UTF-8'));
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_SESSION['user_id'])) {
    $user_id = $_SESSION['user_id'];
    $leave_type_id = sanitizeInput($_POST['leave_type']);
    $start_date = sanitizeInput($_POST['start_date']);
    $end_date = sanitizeInput($_POST['end_date']);
    $reason = sanitizeInput($_POST['reason']);
    $document_path = null;

    // ตรวจสอบข้อมูลเบื้องต้น
    if (empty($leave_type_id) || empty($start_date) || empty($end_date) || empty($reason)) {
        $_SESSION['error'] = "กรุณากรอกข้อมูลให้ครบถ้วน";
        header("Location: " . BASE_URL . "employee/dashboard.php");
        exit();
    }
    if (strtotime($start_date) > strtotime($end_date)) {
        $_SESSION['error'] = "วันที่เริ่มลาต้องไม่เกินวันที่สิ้นสุดการลา";
        header("Location: " . BASE_URL . "employee/dashboard.php");
        exit();
    }

    // จัดการการอัปโหลดไฟล์
    if (isset($_FILES['document']) && $_FILES['document']['error'] == 0) {
        $target_dir = "../uploads/";
        if (!is_dir($target_dir)) {
            mkdir($target_dir, 0755, true);
        }
        $file_extension = strtolower(pathinfo($_FILES["document"]["name"], PATHINFO_EXTENSION));
        $document_path = "doc_" . $user_id . "_" . time() . "." . $file_extension;
        $target_file = $target_dir . $document_path;
        $allowed_types = ['pdf', 'png', 'jpg', 'jpeg'];
        if (!in_array($file_extension, $allowed_types)) {
            $_SESSION['error'] = "อนุญาตเฉพาะไฟล์ PDF, PNG, JPG, JPEG เท่านั้น";
            header("Location: " . BASE_URL . "employee/dashboard.php");
            exit();
        }
        if ($_FILES["document"]["size"] > 5 * 1024 * 1024) {
            $_SESSION['error'] = "ขนาดไฟล์ต้องไม่เกิน 5MB";
            header("Location: " . BASE_URL . "employee/dashboard.php");
            exit();
        }
        if (!move_uploaded_file($_FILES["document"]["tmp_name"], $target_file)) {
            $_SESSION['error'] = "เกิดข้อผิดพลาดในการอัปโหลดไฟล์";
            header("Location: " . BASE_URL . "employee/dashboard.php");
            exit();
        }
    }

    // ====== ดึง rule ของประเภทการลานี้ ======
    $rule_stmt = $conn->prepare("SELECT * FROM leave_rules WHERE leave_type_id = :leave_type_id ORDER BY id DESC LIMIT 1");
    $rule_stmt->bindParam(':leave_type_id', $leave_type_id);
    $rule_stmt->execute();
    $rule = $rule_stmt->fetch(PDO::FETCH_ASSOC);

    $error = "";

    // ====== 1. เช็คจำนวนวันลา (max_days) ======
    if ($rule && $rule['max_days'] > 0) {
        $days_requested = (strtotime($end_date) - strtotime($start_date)) / (60*60*24) + 1;
        if ($days_requested > $rule['max_days']) {
            $error = "จำนวนวันที่ลามากกว่าที่อนุญาต ({$rule['max_days']} วันต่อครั้ง)";
        }
    }

    // ====== 2. เช็คจำนวนครั้งที่ลาได้ในเดือนนี้ (max_requests_per_month) ======
    if (!$error && $rule && $rule['max_requests_per_month'] > 0) {
        $month = date('m', strtotime($start_date));
        $year = date('Y', strtotime($start_date));
        $stmt = $conn->prepare("SELECT COUNT(*) FROM leave_requests 
            WHERE user_id = :user_id AND leave_type_id = :leave_type_id
            AND MONTH(start_date) = :month AND YEAR(start_date) = :year
            AND status IN ('pending', 'approved')");
        $stmt->bindParam(':user_id', $user_id);
        $stmt->bindParam(':leave_type_id', $leave_type_id);
        $stmt->bindParam(':month', $month);
        $stmt->bindParam(':year', $year);
        $stmt->execute();
        $count = $stmt->fetchColumn();
        if ($count >= $rule['max_requests_per_month']) {
            $error = "จำนวนครั้งที่ลามากกว่าที่กำหนดในเดือนนี้ ({$rule['max_requests_per_month']} ครั้ง)";
        }
    }

    // ====== 3. เช็คแจ้งลาล่วงหน้า (min_notice_days) ======
    if (!$error && $rule && $rule['min_notice_days'] > 0) {
        $notice_days = (strtotime($start_date) - strtotime(date('Y-m-d'))) / (60*60*24);
        if ($notice_days < $rule['min_notice_days']) {
            $error = "ต้องแจ้งลาล่วงหน้าอย่างน้อย {$rule['min_notice_days']} วัน";
        }
    }

    // ====== เพิ่ม future: เช็ค blackout period (ไม่ให้ลาในช่วงนี้) ======
    if (!$error && $rule && $rule['blackout_start_date'] && $rule['blackout_end_date']) {
        if (
            ($start_date >= $rule['blackout_start_date'] && $start_date <= $rule['blackout_end_date']) ||
            ($end_date   >= $rule['blackout_start_date'] && $end_date   <= $rule['blackout_end_date'])
        ) {
            $error = "ไม่สามารถลาช่วงวันที่กำหนดไว้ได้";
        }
    }

    // ====== เช็คแผนก ======
    if (!$error && $rule && $rule['department'] && strtolower($rule['department']) !== "ทุกแผนก") {
        $user_dept = $_SESSION['department'] ?? '';
        if (strcasecmp($user_dept, $rule['department']) !== 0) {
            $error = "ประเภทการลานี้อนุญาตเฉพาะแผนก " . $rule['department'];
        }
    }

    // ====== เช็ควันลาคงเหลือ (พักร้อน) ======
    // ใส่ id ประเภทลาพักร้อนตามที่ใช้ใน leave_types จริง (สมมติเป็น 3)
    if (!$error && $leave_type_id == 3) {
        $current_year = date('Y', strtotime($start_date));
        $balance = getLeaveBalance($conn, $user_id, $leave_type_id, $current_year);
        $days_requested = (strtotime($end_date) - strtotime($start_date)) / (60*60*24) + 1;
        if ($balance < $days_requested) {
            $error = "วันลาพักร้อนคงเหลือของคุณไม่เพียงพอสำหรับการลาครั้งนี้ (คงเหลือ $balance วัน)";
        }
    }

    // ====== ถ้ามี error ให้แจ้งและไม่บันทึก ======
    if ($error) {
        $_SESSION['error'] = $error;
        header("Location: " . BASE_URL . "employee/dashboard.php");
        exit();
    }

    // บันทึกข้อมูลลงฐานข้อมูล
    try {
        $stmt = $conn->prepare("INSERT INTO leave_requests (user_id, leave_type_id, start_date, end_date, reason, document_path) 
                              VALUES (:user_id, :leave_type_id, :start_date, :end_date, :reason, :document_path)");
        $stmt->bindParam(':user_id', $user_id);
        $stmt->bindParam(':leave_type_id', $leave_type_id);
        $stmt->bindParam(':start_date', $start_date);
        $stmt->bindParam(':end_date', $end_date);
        $stmt->bindParam(':reason', $reason);
        $stmt->bindParam(':document_path', $document_path);

        if ($stmt->execute()) {
            $_SESSION['success'] = "ยื่นคำขอลาสำเร็จ";
            $last_leave_id = $conn->lastInsertId();

            // ส่งอีเมลแจ้งเตือน
            require_once '../includes/functions.php';
            sendLeaveNotificationEmail($last_leave_id, $conn);
        } else {
            $_SESSION['error'] = "เกิดข้อผิดพลาดในการยื่นคำขอ";
        }
    } catch (Exception $e) {
        $_SESSION['error'] = "เกิดข้อผิดพลาด: " . $e->getMessage();
    }

    header("Location: " . BASE_URL . "employee/dashboard.php");
    exit();
}
?>
