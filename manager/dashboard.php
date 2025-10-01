<?php
require_once '../config/db.php';
require_once '../includes/auth.php';
checkRole(['manager']);

$department = $_SESSION['department'];

// นับจำนวนคำขอลาของทีม
$stmt = $conn->prepare("SELECT COUNT(*) as team_leaves 
                       FROM leave_requests lr
                       JOIN users u ON lr.user_id = u.id
                       WHERE u.department = :department");
$stmt->bindParam(':department', $department);
$stmt->execute();
$team_leaves = $stmt->fetch(PDO::FETCH_ASSOC)['team_leaves'];

// นับจำนวนพนักงานในทีม
$stmt = $conn->prepare("SELECT COUNT(*) as team_members 
                       FROM users 
                       WHERE department = :department AND role = 'employee'");
$stmt->bindParam(':department', $department);
$stmt->execute();
$team_members = $stmt->fetch(PDO::FETCH_ASSOC)['team_members'];

// คำขอลาล่าสุดของทีม
$stmt = $conn->prepare("SELECT lr.*, u.fullname, lt.name as leave_type 
                       FROM leave_requests lr
                       JOIN users u ON lr.user_id = u.id
                       JOIN leave_types lt ON lr.leave_type_id = lt.id
                       WHERE u.department = :department
                       ORDER BY lr.created_at DESC LIMIT 5");
$stmt->bindParam(':department', $department);
$stmt->execute();
$recent_team_leaves = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<?php include '../includes/header.php'; ?>

<div class="container">
    <h2>แดชบอร์ดหัวหน้าแผนก (<?= htmlspecialchars($department) ?>)</h2>
    
    <div class="row mb-4">
        <div class="col-md-6">
            <div class="card text-white bg-info">
                <div class="card-body">
                    <h5 class="card-title">คำขอลาของทีม</h5>
                    <p class="card-text display-4"><?= $team_leaves ?></p>
                    <a href="<?= BASE_URL ?>manager/team_leaves.php" class="text-white">จัดการคำขอ <i class="fas fa-arrow-right"></i></a>
                </div>
            </div>
        </div>
        <div class="col-md-6">
            <div class="card text-white bg-secondary">
                <div class="card-body">
                    <h5 class="card-title">สมาชิกในทีม</h5>
                    <p class="card-text display-4"><?= $team_members ?></p>
                </div>
            </div>
        </div>
    </div>

    <div class="card">
        <div class="card-header">
            <h5>คำขอลาล่าสุดของทีม</h5>
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
                    <?php foreach ($recent_team_leaves as $leave): ?>
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
