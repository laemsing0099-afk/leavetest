<?php
require_once '../config/db.php';
require_once '../includes/auth.php';
checkRole(['hr', 'admin']);

function sanitizeInput($input) {
    return trim(htmlspecialchars($input, ENT_QUOTES, 'UTF-8'));
}

$start_date = sanitizeInput($_GET['start_date'] ?? date('Y-m-01'));
$end_date = sanitizeInput($_GET['end_date'] ?? date('Y-m-t'));
$type = sanitizeInput($_GET['type'] ?? 'excel');
$report_type = sanitizeInput($_GET['report_type'] ?? 'summary');

if ($type === 'excel') {
    $filename = "report_{$report_type}_" . date('Ymd_His') . ".csv";
    header('Content-Type: text/csv; charset=utf-8');
    header('Content-Disposition: attachment; filename="' . $filename . '"');
    $output = fopen('php://output', 'w');
    fputs($output, "\xEF\xBB\xBF"); // UTF-8 BOM

    if ($report_type === 'summary') {
        // รายงานสรุปประเภทการลา
        fputcsv($output, ['ประเภทการลา', 'จำนวนทั้งหมด', 'อนุมัติ', 'ปฏิเสธ', 'รออนุมัติ']);
        $sql = "SELECT lt.name AS leave_type,
                       COUNT(lr.id) AS total_requests,
                       SUM(lr.status = 'approved') AS approved,
                       SUM(lr.status = 'rejected') AS rejected,
                       SUM(lr.status = 'pending') AS pending
                FROM leave_requests lr
                JOIN leave_types lt ON lr.leave_type_id = lt.id
                WHERE lr.start_date >= :start_date AND lr.end_date <= :end_date
                GROUP BY lr.leave_type_id";
        $stmt = $conn->prepare($sql);
        $stmt->execute(['start_date' => $start_date, 'end_date' => $end_date]);
        while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
            fputcsv($output, [
                $row['leave_type'],
                $row['total_requests'],
                $row['approved'],
                $row['rejected'],
                $row['pending']
            ]);
        }

    } elseif ($report_type === 'detailed') {
        // รายงานรายบุคคล (เฉพาะอนุมัติแล้ว)
        fputcsv($output, ['ชื่อพนักงาน', 'ชื่อผู้ใช้', 'แผนก', 'ประเภทการลา', 'วันที่เริ่ม', 'วันที่สิ้นสุด', 'เหตุผลการลา', 'สถานะ']);
        $sql = "SELECT u.fullname, u.username, u.department,
                       lt.name AS leave_type_name,
                       lr.start_date, lr.end_date, lr.reason, lr.status
                FROM leave_requests lr
                JOIN users u ON lr.user_id = u.id
                JOIN leave_types lt ON lr.leave_type_id = lt.id
                WHERE lr.start_date >= :start_date AND lr.end_date <= :end_date
                  AND lr.status = 'approved'
                ORDER BY u.department, u.username, lr.start_date";
        $stmt = $conn->prepare($sql);
        $stmt->execute(['start_date' => $start_date, 'end_date' => $end_date]);
        $status_map = [
            'approved' => 'อนุมัติแล้ว'
        ];
        while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
            fputcsv($output, [
                $row['fullname'],
                $row['username'],
                $row['department'] ?: 'ไม่มีแผนก',
                $row['leave_type_name'],
                $row['start_date'],
                $row['end_date'],
                $row['reason'],
                $status_map[$row['status']] ?? 'อนุมัติแล้ว'
            ]);
        }

    } elseif ($report_type === 'swap') {
        // รายงานสลับวันหยุด
        fputcsv($output, ['แผนก', 'ชื่อพนักงาน', 'วันหยุดเดิม', 'ขอเปลี่ยนเป็นวัน', 'เหตุผล', 'สถานะ', 'วันที่ยื่น']);
        $sql = "SELECT u.department, u.fullname, hs.old_date, hs.new_date, hs.reason, hs.status, hs.created_at
                FROM holiday_swaps hs
                JOIN users u ON hs.user_id = u.id
                WHERE hs.old_date >= :start_date AND hs.old_date <= :end_date
                ORDER BY u.department, u.fullname, hs.old_date";
        $stmt = $conn->prepare($sql);
        $stmt->execute(['start_date' => $start_date, 'end_date' => $end_date]);
        $status_map = [
            'pending' => 'รออนุมัติ',
            'approved' => 'อนุมัติแล้ว',
            'rejected' => 'ปฏิเสธ'
        ];
        while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
            fputcsv($output, [
                $row['department'] ?: 'ไม่มีแผนก',
                $row['fullname'],
                $row['old_date'],
                $row['new_date'],
                $row['reason'],
                $status_map[$row['status']] ?? 'ไม่ทราบสถานะ',
                $row['created_at']
            ]);
        }

    } elseif ($report_type === 'total_by_dept') {
        // รายงานจำนวนการลาทั้งหมด (แยกตามแผนก)
        fputcsv($output, ['แผนก', 'ชื่อพนักงาน', 'ชื่อผู้ใช้', 'จำนวนการลาทั้งหมด']);
        $sql = "
            SELECT u.department, u.fullname, u.username, COUNT(lr.id) AS total_leaves
            FROM users u
            LEFT JOIN leave_requests lr
              ON u.id = lr.user_id
              AND lr.start_date >= :start_date AND lr.end_date <= :end_date
            GROUP BY u.department, u.id
            ORDER BY u.department, u.username
        ";
        $stmt = $conn->prepare($sql);
        $stmt->execute(['start_date' => $start_date, 'end_date' => $end_date]);
        while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
            fputcsv($output, [
                $row['department'] ?: 'ไม่มีแผนก',
                $row['fullname'],
                $row['username'],
                $row['total_leaves']
            ]);
        }

    } else {
        fputcsv($output, ['ไม่พบรูปแบบรายงานที่ต้องการ']);
    }

    fclose($output);
    exit();
}

echo 'ไม่รองรับประเภทการส่งออกนี้';
exit;
