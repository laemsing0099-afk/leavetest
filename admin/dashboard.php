<?php
require_once '../config/db.php';
require_once '../includes/auth.php';
checkRole(['admin']);

// สถิติผู้ใช้
$users_count = $conn->query("SELECT COUNT(*) as total FROM users")->fetch(PDO::FETCH_ASSOC)['total'];
$active_leaves = $conn->query("SELECT COUNT(*) as total FROM leave_requests WHERE status = 'approved' AND end_date >= CURDATE()")->fetch(PDO::FETCH_ASSOC)['total'];

// คำขอลาล่าสุด
$recent_leaves = $conn->query("SELECT lr.*, u.fullname, lt.name as leave_type 
                             FROM leave_requests lr
                             JOIN users u ON lr.user_id = u.id
                             JOIN leave_types lt ON lr.leave_type_id = lt.id
                             ORDER BY lr.created_at DESC LIMIT 5")->fetchAll(PDO::FETCH_ASSOC);

// กิจกรรมล่าสุด
$recent_activities = $conn->query("SELECT * FROM (
                                  SELECT id, 'leave' as type, created_at FROM leave_requests 
                                  UNION 
                                  SELECT id, 'swap' as type, created_at FROM shift_swaps
                                  ) as activities 
                                  ORDER BY created_at DESC LIMIT 5")->fetchAll(PDO::FETCH_ASSOC);

// เงื่อนไขการลา (ใช้ fetchColumn เพื่อความแม่นยำ)
$leave_rules_count = $conn->query("SELECT COUNT(*) FROM leave_rules")->fetchColumn();
?>

<?php include '../includes/header.php'; ?>

<div class="container">
    <h2>แดชบอร์ดผู้ดูแลระบบ</h2>
    
    <div class="row mb-4">
        <div class="col-md-4">
            <div class="card text-white bg-primary">
                <div class="card-body">
                    <h5 class="card-title">ผู้ใช้ทั้งหมด</h5>
                    <p class="card-text display-4"><?= htmlspecialchars($users_count) ?></p>
                    <a href="<?= BASE_URL ?>admin/users.php" class="text-white">จัดการผู้ใช้ <i class="fas fa-arrow-right"></i></a>
                </div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="card text-white bg-success">
                <div class="card-body">
                    <h5 class="card-title">การลาปัจจุบัน</h5>
                    <p class="card-text display-4"><?= htmlspecialchars($active_leaves) ?></p>
                    <a href="<?= BASE_URL ?>hr/manage_leaves.php" class="text-white">ดูทั้งหมด <i class="fas fa-arrow-right"></i></a>
                </div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="card text-white bg-info">
                <div class="card-body">
                    <h5 class="card-title">เงื่อนไขการลา</h5>
                    <p class="card-text display-4">
                        <?= htmlspecialchars($leave_rules_count) ?>
                    </p>
                    <a href="<?= BASE_URL ?>admin/leave_rules.php" class="text-white">จัดการเงื่อนไข <i class="fas fa-arrow-right"></i></a>
                </div>
            </div>
        </div>
    </div>

    <div class="row">
        <div class="col-md-6">
            <div class="card mb-4">
                <div class="card-header">
                    <h5>คำขอลาล่าสุด</h5>
                </div>
                <div class="card-body">
                    <table class="table">
                        <thead>
                            <tr>
                                <th>พนักงาน</th>
                                <th>ประเภท</th>
                                <th>วันที่ลา</th>
                                <th>สถานะ</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($recent_leaves as $leave): ?>
                                <tr>
                                    <td><?= htmlspecialchars($leave['fullname']) ?></td>
                                    <td><?= htmlspecialchars($leave['leave_type']) ?></td>
                                    <td><?= date('d/m/Y', strtotime($leave['start_date'])) ?></td>
                                    <td>
                                        <?php if ($leave['status'] == 'pending'): ?>
                                            <span class="badge bg-warning">รออนุมัติ</span>
                                        <?php elseif ($leave['status'] == 'approved'): ?>
                                            <span class="badge bg-success">อนุมัติแล้ว</span>
                                        <?php else: ?>
                                            <span class="badge bg-danger">ปฏิเสธ</span>
                                        <?php endif; ?>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
        
        <div class="col-md-6">
            <div class="card">
                <div class="card-header">
                    <h5>กิจกรรมล่าสุด</h5>
                </div>
                <div class="card-body">
                    <ul class="list-group">
                        <?php foreach ($recent_activities as $activity): ?>
                            <li class="list-group-item">
                                <?php if ($activity['type'] == 'leave'): ?>
                                    <i class="fas fa-calendar-alt text-primary"></i> มีคำขอลาใหม่
                                <?php else: ?>
                                    <i class="fas fa-exchange-alt text-info"></i> มีคำขอสลับวันทำงาน
                                <?php endif; ?>
                                <span class="float-end text-muted small">
                                    <?= date('d/m/Y H:i', strtotime($activity['created_at'])) ?>
                                </span>
                            </li>
                        <?php endforeach; ?>
                    </ul>
                </div>
            </div>
        </div>
    </div>
</div>

<?php include '../includes/footer.php'; ?>
