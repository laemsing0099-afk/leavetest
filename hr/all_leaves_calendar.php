<?php
require_once '../config/db.php';
require_once '../includes/auth.php';
checkRole(['hr', 'admin']);

// กำหนดสีประจำแต่ละแผนก (ชื่อแผนกต้องตรงกับใน DB)
$department_colors = [
    'HR'         => '#007bff',
    'บัญชี'      => '#28a745',
    'ไอที'       => '#17a2b8',
    'ฝ่ายขาย'    => '#fd7e14',
    'ฝ่ายผลิต'   => '#6610f2',
    'คลังสินค้า' => '#e83e8c',
    'ช่าง'      => '#6c757d',
    // เพิ่มแผนกใหม่ใส่สีเพิ่มได้เลย
];

// ดึงข้อมูลใบลาทั้งหมดทุกแผนก
$stmt = $conn->query("SELECT lr.*, u.fullname, u.department, lt.name AS leave_type_name
                      FROM leave_requests lr
                      JOIN users u ON lr.user_id = u.id
                      JOIN leave_types lt ON lr.leave_type_id = lt.id
                      ORDER BY lr.start_date DESC");
$leaves = $stmt->fetchAll(PDO::FETCH_ASSOC);

// รวบรวมชื่อแผนกที่พบจริง เผื่อแผนกใหม่ในอนาคต
$all_departments = [];
foreach ($leaves as $leave) {
    $all_departments[$leave['department']] = true;
}
// สำหรับแผนกที่ไม่มีใน $department_colors จะสุ่มสี
function getDepartmentColor($department, $department_colors) {
    if (isset($department_colors[$department])) return $department_colors[$department];
    // สุ่มสีถ้าไม่มีใน list
    return '#' . substr(md5($department), 0, 6);
}
?>
<?php include '../includes/header.php'; ?>

<div class="container mt-4">
    <h2>ปฏิทินวันลาพนักงานทั้งหมด (แยกสีตามแผนก)</h2>
    <div class="mb-3">
        <b>แสดงสีประจำแผนก:</b>
        <?php foreach (array_keys($all_departments) as $dep): ?>
            <span class="badge" style="background:<?= getDepartmentColor($dep, $department_colors) ?>;color:#fff;"><?= htmlspecialchars($dep) ?></span>
        <?php endforeach; ?>
    </div>
    <div class="card">
        <div class="card-body">
            <div id="allLeavesCalendar"></div>
        </div>
    </div>
</div>

<!-- FullCalendar CSS/JS -->
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/fullcalendar@5.10.1/main.min.css">
<script src="https://cdn.jsdelivr.net/npm/fullcalendar@5.10.1/main.min.js"></script>
<script>
document.addEventListener('DOMContentLoaded', function() {
    var calendarEl = document.getElementById('allLeavesCalendar');
    var calendar = new FullCalendar.Calendar(calendarEl, {
        initialView: 'dayGridMonth',
        locale: 'th',
        headerToolbar: {
            left: 'prev,next today',
            center: 'title',
            right: 'dayGridMonth,listMonth'
        },
        events: [
            <?php foreach ($leaves as $leave): 
                $color = getDepartmentColor($leave['department'], $department_colors);
            ?>
            {
                title: '<?= addslashes($leave['fullname']) ?> (<?= addslashes($leave['department']) ?>) - <?= addslashes($leave['leave_type_name']) ?>',
                start: '<?= $leave['start_date'] ?>',
                end: '<?= date('Y-m-d', strtotime($leave['end_date'] . ' +1 day')) ?>',
                color: '<?= $color ?>',
                textColor: '#fff'
            },
            <?php endforeach; ?>
        ]
    });
    calendar.render();
});
</script>
<?php include '../includes/footer.php'; ?>
