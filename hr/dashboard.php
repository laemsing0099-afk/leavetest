<?php
require_once '../config/db.php';
require_once '../includes/auth.php';
checkRole(['hr']);

// นับจำนวนคำขอลารออนุมัติ
$stmt = $conn->query("SELECT COUNT(*) as pending FROM leave_requests WHERE status = 'pending'");
$pending_leaves = $stmt->fetch(PDO::FETCH_ASSOC)['pending'];

// นับจำนวนพนักงานทั้งหมด
$stmt = $conn->query("SELECT COUNT(*) as total FROM users WHERE role = 'employee'");
$total_employees = $stmt->fetch(PDO::FETCH_ASSOC)['total'];

// คำขอลาล่าสุด
$stmt = $conn->query("SELECT lr.*, u.fullname, lt.name as leave_type 
                     FROM leave_requests lr
                     JOIN users u ON lr.user_id = u.id
                     JOIN leave_types lt ON lr.leave_type_id = lt.id
                     ORDER BY lr.created_at DESC LIMIT 5");
$recent_leaves = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<?php include '../includes/header.php'; ?>

<div class="container">
    <h2>แดชบอร์ดฝ่ายบุคคล</h2>
    
    <div class="row mb-4">
        <div class="col-md-6">
            <div class="card text-white bg-primary">
                <div class="card-body">
                    <h5 class="card-title">คำขอลารออนุมัติ</h5>
                    <p class="card-text display-4"><?= htmlspecialchars($pending_leaves) ?></p>
                    <a href="<?= BASE_URL ?>hr/manage_leaves.php" class="text-white">จัดการคำขอ <i class="fas fa-arrow-right"></i></a>
                </div>
            </div>
        </div>
        <div class="col-md-6">
            <div class="card text-white bg-success">
                <div class="card-body">
                    <h5 class="card-title">จำนวนพนักงานทั้งหมด</h5>
                    <p class="card-text display-4"><?= htmlspecialchars($total_employees) ?></p>
                    <a href="<?= BASE_URL ?>admin/users.php" class="text-white">ดูรายชื่อ <i class="fas fa-arrow-right"></i></a>
                </div>
            </div>
        </div>
    </div>

    <div class="card">
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
                    <?php if (empty($recent_leaves)): ?>
                        <tr>
                            <td colspan="4" class="text-center text-muted">ไม่มีข้อมูลคำขอลาล่าสุด</td>
                        </tr>
                    <?php endif; ?>
                    <?php foreach ($recent_leaves as $leave): ?>
                        <tr>
                            <td><?= htmlspecialchars($leave['fullname']) ?></td>
                            <td><?= htmlspecialchars($leave['leave_type']) ?></td>
                            <td><?= date('d/m/Y', strtotime($leave['start_date'])) ?> - <?= date('d/m/Y', strtotime($leave['end_date'])) ?></td>
                            <td>
                                <?php
                                $status_map = [
                                    'pending' => ['class' => 'bg-warning text-dark', 'text' => 'รออนุมัติ'],
                                    'approved' => ['class' => 'bg-success', 'text' => 'อนุมัติแล้ว'],
                                    'rejected' => ['class' => 'bg-danger', 'text' => 'ปฏิเสธ']
                                ];
                                $status_info = $status_map[$leave['status']] ?? ['class' => 'bg-secondary', 'text' => 'ไม่ทราบสถานะ'];
                                ?>
                                <span class="badge <?= $status_info['class'] ?>"><?= $status_info['text'] ?></span>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<?php include '../includes/footer.php'; ?>
