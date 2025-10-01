<?php
require_once '../config/db.php';
$stmt = $conn->query("SELECT hs.*, u.username
    FROM holiday_swaps hs 
    JOIN users u ON hs.user_id = u.id");
$events = [];
while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
    $events[] = [
        "title" => $row['username'] . " (" . 
            ($row['status'] == 'approved' ? 'อนุมัติ' : ($row['status'] == 'rejected' ? 'ปฏิเสธ' : 'รออนุมัติ')) . ")",
        "start" => $row['old_date'],
        // แนะนำ: แสดง 1 วันเฉย ๆ ถ้า new_date เว้นไว้ หรือใส่ 'end' => $row['new_date'],
        "color" => ($row['status'] == 'approved' ? '#38c776' : ($row['status'] == 'rejected' ? '#e05b5b' : '#ffe580')),
        "url"   => "manage_swap_holiday.php?id=" . $row['id']
    ];
}
header('Content-Type: application/json');
echo json_encode($events);
