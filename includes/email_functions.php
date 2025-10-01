<?php

// ใช้ PHPMailer library
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

// ตรวจสอบว่าไฟล์ autoload ของ Composer ถูก include แล้วหรือยัง
$composer_autoload = __DIR__ . '/../vendor/autoload.php';
if (file_exists($composer_autoload)) {
    require_once $composer_autoload;
} else {
    trigger_error("PHPMailer is not installed. Please run 'composer require phpmailer/phpmailer'.", E_USER_ERROR);
}

// Include config สำหรับ SMTP/อีเมล
require_once __DIR__ . '/../config/mail.php';

if (!function_exists('sendLeaveStatusUpdateEmail')) {
    /**
     * ส่งอีเมลแจ้งพนักงาน เมื่อใบลาได้รับการอัปเดตสถานะ
     *
     * @param PDO $conn
     * @param int $leave_id
     * @param string $new_status ('approved' หรือ 'rejected')
     * @param string $reason (เหตุผลที่ปฏิเสธ, ส่งเฉพาะตอน rejected)
     * @return bool
     */
    function sendLeaveStatusUpdateEmail($conn, $leave_id, $new_status, $reason = '') {
        try {
            $stmt = $conn->prepare("
                SELECT
                    u.email,
                    u.fullname,
                    lt.name as leave_type_name,
                    lr.start_date,
                    lr.end_date,
                    lr.reason
                FROM leave_requests lr
                JOIN users u ON lr.user_id = u.id
                JOIN leave_types lt ON lr.leave_type_id = lt.id
                WHERE lr.id = :leave_id
            ");
            $stmt->execute(['leave_id' => $leave_id]);
            $leave_data = $stmt->fetch(PDO::FETCH_ASSOC);

            if (!$leave_data || empty($leave_data['email'])) {
                error_log("Could not find user email for leave_id: " . $leave_id);
                return false;
            }

            $status_text_th = [
                'approved' => 'อนุมัติแล้ว',
                'rejected' => 'ถูกปฏิเสธ'
            ];
            $email_subject = "หจก.ศูนย์รถยนต์อุบลเซอร์วิส 1997: " . ($status_text_th[$new_status] ?? $new_status);
            
            $body  = "เรียนคุณ " . htmlspecialchars($leave_data['fullname']) . ",<br><br>";
            $body .= "รายการคำขอลาของคุณได้รับการอัปเดตสถานะเป็น: <strong>" . ($status_text_th[$new_status] ?? $new_status) . "</strong><br><br>";
            $body .= "<strong><u>รายละเอียดการลา:</u></strong><br>";
            $body .= "<strong>ประเภทการลา:</strong> " . htmlspecialchars($leave_data['leave_type_name']) . "<br>";
            $body .= "<strong>วันที่:</strong> " . date('d/m/Y', strtotime($leave_data['start_date'])) . " ถึง " . date('d/m/Y', strtotime($leave_data['end_date'])) . "<br>";
            $body .= "<strong>เหตุผล:</strong> " . nl2br(htmlspecialchars($leave_data['reason'] ?? 'ไม่มี')) . "<br>";
            // เพิ่มตรงนี้ (ถ้าถูกปฏิเสธและมีเหตุผล)
            if ($new_status == 'rejected' && !empty($reason)) {
                $body .= "<strong style='color:red;'>เหตุผลที่ปฏิเสธ:</strong> " . nl2br(htmlspecialchars($reason)) . "<br>";
            }
            $body .= "<br>ขอแสดงความนับถือ,<br>";
            $body .= EMAIL_FROM_NAME;

            $mail = new PHPMailer(true);
            $mail->isSMTP();
            $mail->Host       = SMTP_HOST;
            $mail->SMTPAuth   = true;
            $mail->Username   = SMTP_USERNAME;
            $mail->Password   = SMTP_PASSWORD;
            $mail->SMTPSecure = SMTP_SECURE;
            $mail->Port       = SMTP_PORT;
            $mail->CharSet    = 'UTF-8';

            $mail->setFrom(EMAIL_FROM, EMAIL_FROM_NAME);
            $mail->addAddress($leave_data['email'], $leave_data['fullname']);

            $mail->isHTML(true);
            $mail->Subject = $email_subject;
            $mail->Body    = $body;
            $mail->AltBody = strip_tags(str_replace('<br>', "\n", $body));

            $mail->send();
            return true;

        } catch (Exception $e) {
            error_log("Message could not be sent. Mailer Error: {$mail->ErrorInfo}");
            return false;
        }
    }
}

if (!function_exists('sendLeaveNotificationEmail')) {
    /**
     * ส่งอีเมลแจ้ง HR เมื่อมีคำขอลาใหม่
     * @param int $leave_id
     * @param PDO $conn
     * @return bool
     */
    function sendLeaveNotificationEmail($leave_id, $conn) {
        try {
            $stmt = $conn->prepare("
                SELECT
                    u.fullname,
                    lt.name as leave_type_name,
                    lr.start_date,
                    lr.end_date,
                    lr.reason
                FROM leave_requests lr
                JOIN users u ON lr.user_id = u.id
                JOIN leave_types lt ON lr.leave_type_id = lt.id
                WHERE lr.id = :leave_id
            ");
            $stmt->execute(['leave_id' => $leave_id]);
            $leave_data = $stmt->fetch(PDO::FETCH_ASSOC);

            if (!$leave_data) {
                error_log("Could not find leave request for leave_id: " . $leave_id);
                return false;
            }

            $subject = "แจ้งเตือน: มีคำขอลางานใหม่";
            $body  = "พนักงาน: " . htmlspecialchars($leave_data['fullname']) . "<br>";
            $body .= "ประเภทการลา: " . htmlspecialchars($leave_data['leave_type_name']) . "<br>";
            $body .= "วันที่: " . date('d/m/Y', strtotime($leave_data['start_date'])) . " ถึง " . date('d/m/Y', strtotime($leave_data['end_date'])) . "<br>";
            $body .= "เหตุผล: " . nl2br(htmlspecialchars($leave_data['reason'] ?? 'ไม่มี')) . "<br>";

            $mail = new PHPMailer(true);
            $mail->isSMTP();
            $mail->Host       = SMTP_HOST;
            $mail->SMTPAuth   = true;
            $mail->Username   = SMTP_USERNAME;
            $mail->Password   = SMTP_PASSWORD;
            $mail->SMTPSecure = SMTP_SECURE;
            $mail->Port       = SMTP_PORT;
            $mail->CharSet    = 'UTF-8';

            $mail->setFrom(EMAIL_FROM, EMAIL_FROM_NAME);
            $mail->addAddress(EMAIL_FROM, 'HR'); // ส่งเข้าตัวเอง/หรือเปลี่ยนเป็นอีเมล HR จริง

            $mail->isHTML(true);
            $mail->Subject = $subject;
            $mail->Body    = $body;
            $mail->AltBody = strip_tags(str_replace('<br>', "\n", $body));

            $mail->send();
            return true;

        } catch (Exception $e) {
            error_log("HR Notification Mailer Error: {$mail->ErrorInfo}");
            return false;
        }
    }
}
