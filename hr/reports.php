<?php
require_once '../config/db.php';
require_once '../includes/auth.php';
checkRole(['hr','admin']);

if (!function_exists('sanitizeInput')) {
    function sanitizeInput($input) {
        return trim(htmlspecialchars($input, ENT_QUOTES, 'UTF-8'));
    }
}

// ลบคำขอการลา
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['delete_leave_id'])) {
    $delete_id = (int) $_POST['delete_leave_id'];
    $stmt = $conn->prepare("DELETE FROM leave_requests WHERE id = :id");
    $stmt->execute(['id' => $delete_id]);
    header("Location: " . strtok($_SERVER["REQUEST_URI"], '?'));
    exit;
}

$start_date = sanitizeInput($_GET['start_date'] ?? date('Y-m-01'));
$end_date = sanitizeInput($_GET['end_date'] ?? date('Y-m-t'));

// 1. ดึงสถิติการลาแต่ละประเภท
$leave_stats = [];
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
    $leave_stats[] = $row;
}

// 2. รายงานการลา (ทุกสถานะ) พร้อมจำนวนการลาทั้งหมดของแต่ละคน
$detailed_leaves = [];
$sql = "SELECT lr.*, u.username, u.fullname, u.department, lt.name AS leave_type_name,
               COALESCE(user_totals.total_leaves, 0) AS total_leaves
        FROM leave_requests lr
        JOIN users u ON lr.user_id = u.id
        JOIN leave_types lt ON lr.leave_type_id = lt.id
        LEFT JOIN (
            SELECT user_id, COUNT(*) AS total_leaves
            FROM leave_requests
            GROUP BY user_id
        ) AS user_totals ON user_totals.user_id = u.id
        WHERE lr.start_date >= :start_date AND lr.end_date <= :end_date
        ORDER BY u.department, u.username, lr.start_date";
$stmt = $conn->prepare($sql);
$stmt->execute(['start_date' => $start_date, 'end_date' => $end_date]);
while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
    $detailed_leaves[] = $row;
}

// 3. รายงานสลับวันหยุด
$swap_requests = [];
$sql = "SELECT hs.*, u.username, u.department
        FROM holiday_swaps hs
        JOIN users u ON hs.user_id = u.id
        WHERE hs.old_date >= :start_date AND hs.old_date <= :end_date
        ORDER BY u.department, u.username, hs.old_date";
$stmt = $conn->prepare($sql);
$stmt->execute(['start_date' => $start_date, 'end_date' => $end_date]);
while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
    $swap_requests[] = $row;
}

// 4. Chart data
$chart_labels = [];
$chart_data_approved = [];
$chart_data_rejected = [];
$chart_data_pending = [];
$pie_chart_data = [0, 0, 0];
foreach ($leave_stats as $stat) {
    $chart_labels[] = $stat['leave_type'];
    $chart_data_approved[] = (int)$stat['approved'];
    $chart_data_rejected[] = (int)$stat['rejected'];
    $chart_data_pending[] = (int)$stat['pending'];
    $pie_chart_data[0] += (int)$stat['approved'];
    $pie_chart_data[1] += (int)$stat['rejected'];
    $pie_chart_data[2] += (int)$stat['pending'];
}
$has_chart_data = (count($chart_labels) > 0);

// 5. Chart แผนก
$dept_chart_labels = [];
$dept_chart_counts = [];
$sql = "SELECT u.department, COUNT(lr.id) AS leave_count
        FROM leave_requests lr
        JOIN users u ON lr.user_id = u.id
        WHERE lr.start_date >= :start_date AND lr.end_date <= :end_date
        GROUP BY u.department
        ORDER BY leave_count DESC";
$stmt = $conn->prepare($sql);
$stmt->execute(['start_date' => $start_date, 'end_date' => $end_date]);
while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
    $label = $row['department'] ?: 'ไม่มีแผนก';
    $dept_chart_labels[] = $label;
    $dept_chart_counts[] = (int)$row['leave_count'];
}
$has_dept_chart_data = (count($dept_chart_labels) > 0);

// แสดงตารางรวมแบบแยกแผนกและแสดงผู้ใช้ไม่ซ้ำ
$grouped_by_dept = [];
foreach ($detailed_leaves as $row) {
    $dept = $row['department'] ?: 'ไม่มีแผนก';
    $username = $row['username'];
    if (!isset($grouped_by_dept[$dept][$username])) {
        $grouped_by_dept[$dept][$username] = [
            'fullname' => $row['fullname'],
            'username' => $username,
            'total_leaves' => $row['total_leaves']
        ];
    }
}

// Default เพื่อกัน warning
$leave_stats = $leave_stats ?? [];
$detailed_leaves = $detailed_leaves ?? [];
$swap_requests = $swap_requests ?? [];
$chart_labels = $chart_labels ?? [];
$chart_data_approved = $chart_data_approved ?? [];
$chart_data_rejected = $chart_data_rejected ?? [];
$chart_data_pending = $chart_data_pending ?? [];
$pie_chart_data = $pie_chart_data ?? [];
$dept_chart_labels = $dept_chart_labels ?? [];
$dept_chart_counts = $dept_chart_counts ?? [];
$has_chart_data = $has_chart_data ?? false;
$has_dept_chart_data = $has_dept_chart_data ?? false;
?>

<?php include '../includes/header.php'; ?>

<div class="container">
    <h2>รายงานสถิติการลา</h2>
    <div class="card mb-4">
        <div class="card-header"><h5>กรองข้อมูล</h5></div>
        <div class="card-body">
            <form method="GET" class="form-inline">
                <div class="form-group mr-3">
                    <label>ตั้งแต่</label>
                    <input type="date" name="start_date" class="form-control ml-2" value="<?= htmlspecialchars($start_date) ?>">
                </div>
                <div class="form-group mr-3">
                    <label>ถึง</label>
                    <input type="date" name="end_date" class="form-control ml-2" value="<?= htmlspecialchars($end_date) ?>">
                </div>
                <button type="submit" class="btn btn-primary">แสดงรายงาน</button>
            </form>
        </div>
    </div>

    <?php if ($has_chart_data): ?>
    <div class="card mb-4">
        <div class="card-header"><h5>สรุปข้อมูลในรูปแบบกราฟ</h5></div>
        <div class="card-body">
            <div class="row">
                <div class="col-lg-8 mb-4 mb-lg-0">
                    <h6 class="text-center">สถิติการลาแยกตามประเภทและสถานะ</h6>
                    <canvas id="leaveStatsBarChart"></canvas>
                    <button id="downloadBarChart" class="btn btn-outline-primary btn-sm mt-2">ดาวน์โหลดกราฟนี้</button>
                </div>
                <div class="col-lg-4">
                    <h6 class="text-center">ภาพรวมสถานะการลาทั้งหมด</h6>
                    <canvas id="leaveStatusPieChart"></canvas>
                    <button id="downloadPieChart" class="btn btn-outline-primary btn-sm mt-2">ดาวน์โหลดกราฟนี้</button>
                </div>
            </div>
        </div>
    </div>
    <?php endif; ?>

    <?php if ($has_dept_chart_data): ?>
    <div class="card mb-4">
        <div class="card-header"><h5>สถิติการลาแยกตามแผนก</h5></div>
        <div class="card-body">
            <canvas id="departmentLeaveChart"></canvas>
            <button id="downloadDeptChart" class="btn btn-outline-primary btn-sm mt-2">ดาวน์โหลดกราฟนี้</button>
        </div>
    </div>
    <?php endif; ?>

    <div class="card mb-4">
        <div class="card-header"><h5>สถิติการลาแยกตามประเภท</h5>
        </div>
        <a href="<?= BASE_URL ?>hr/export_report.php?start_date=<?= urlencode($start_date) ?>&end_date=<?= urlencode($end_date) ?>&type=excel&report_type=summary"
            class="btn btn-success btn-sm" target="_blank">
                <i class="fas fa-file-excel"></i> ส่งออก Excel
            </a>
        <div class="card-body">
            <table class="table table-bordered">
                <thead>
                    <tr>
                        <th>ประเภทการลา</th>
                        <th>ทั้งหมด</th>
                        <th>อนุมัติ</th>
                        <th>ปฏิเสธ</th>
                        <th>รออนุมัติ</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($leave_stats)): ?>
                        <tr><td colspan="5" class="text-center text-muted">ไม่มีข้อมูล</td></tr>
                    <?php endif; ?>
                    <?php foreach ($leave_stats as $stat): ?>
                        <tr>
                            <td><?= htmlspecialchars($stat['leave_type']) ?></td>
                            <td><?= (int)$stat['total_requests'] ?></td>
                            <td><?= (int)$stat['approved'] ?></td>
                            <td><?= (int)$stat['rejected'] ?></td>
                            <td><?= (int)$stat['pending'] ?></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>

    <!-- รายงานการลาแยกตามแผนก (ลบเฉพาะ pending) -->
<div class="card mb-4">
    <div class="card-header"><h5>รายงานการลาแยกตามแผนก <span class="text-muted small">(รวมทุกสถานะ)</span></h5>
    </div>
            <a href="<?= BASE_URL ?>hr/export_report.php?start_date=<?= urlencode($start_date) ?>&end_date=<?= urlencode($end_date) ?>&type=excel&report_type=detailed"
           class="btn btn-success btn-sm" target="_blank">
            <i class="fas fa-file-excel"></i> ส่งออก Excel
        </a>
    <div class="card-body">
        <table class="table table-bordered">
            <thead>
                <tr>
                    <th>แผนก</th>
                    <th>ชื่อพนักงาน</th>
                    <th>ชื่อผู้ใช้</th>
                    <th>ประเภทการลา</th>
                    <th>วันที่ลา</th>
                    <th>เหตุผล</th>
                    <th>สถานะ</th>
                    <th>ลบ</th>
                </tr>
            </thead>
            <tbody>
            <?php
            $current_department = null;
            if (empty($detailed_leaves)) {
                echo '<tr><td colspan="8" class="text-center text-muted">ไม่มีข้อมูล</td></tr>';
            }
            foreach ($detailed_leaves as $leave):
                if ($leave['department'] !== $current_department) {
                    $current_department = $leave['department'];
                    $department_name = $current_department ? htmlspecialchars($current_department) : 'ไม่มีแผนก';
                    echo '<tr class="table-light"><td colspan="8" class="fw-bold">แผนก: ' . $department_name . '</td></tr>';
                }
            ?>
                <tr>
                    <td><?= $leave['department'] ?: 'ไม่มีแผนก' ?></td>
                    <td><?= htmlspecialchars($leave['fullname']) ?></td>
                    <td><?= htmlspecialchars($leave['username']) ?></td>
                    <td><?= htmlspecialchars($leave['leave_type_name']) ?></td>
                    <td><?= date('d/m/Y', strtotime($leave['start_date'])) ?> ถึง <?= date('d/m/Y', strtotime($leave['end_date'])) ?></td>
                    <td><?= htmlspecialchars($leave['reason']) ?></td>
                    <td>
                        <?php
                        if ($leave['status'] === 'approved') {
                            echo "<span class='badge bg-success'>อนุมัติแล้ว</span>";
                        } elseif ($leave['status'] === 'rejected') {
                            echo "<span class='badge bg-danger'>ปฏิเสธ</span>";
                        } else {
                            echo "<span class='badge bg-warning text-dark'>รออนุมัติ</span>";
                        }
                        ?>
                    </td>
                    <td>
                        <?php if ($leave['status'] === 'pending'): ?>
                            <form method="POST" onsubmit="return confirm('ต้องการลบรายการนี้ใช่หรือไม่?');" style="margin-bottom:0;">
                                <input type="hidden" name="delete_leave_id" value="<?= $leave['id'] ?>">
                                <button type="submit" class="btn btn-sm btn-danger">ลบ</button>
                            </form>
                        <?php else: ?>
                            <span class="text-muted">-</span>
                        <?php endif; ?>
                        <a href="../hr/print_leave_form.php?id=<?= $leave['id'] ?>" target="_blank" class="btn btn-outline-primary btn-sm mt-1">
                            <i class="fas fa-print"></i> พิมพ์ใบลา
                        </a>
                    </td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>

   <!-- ตารางสรุปจำนวนการลาทั้งหมด (แยกตามแผนก) -->
<div class="card mb-4">
  <div class="card-header d-flex justify-content-between align-items-center">
      <h5 class="mb-0">รายงานจำนวนการลาทั้งหมด (แยกตามแผนก)</h5>
  </div>
         <a href="<?= BASE_URL ?>hr/export_report.php?start_date=<?= urlencode($start_date) ?>&end_date=<?= urlencode($end_date) ?>&type=excel&report_type=total_by_dept"
         class="btn btn-success btn-sm"
         target="_blank">
         <i class="fas fa-file-excel"></i> ส่งออก Excel
      </a>
  <div class="card-body">
    <?php foreach ($grouped_by_dept as $dept => $users): ?>
      <h6 class="mt-3">แผนก: <?= htmlspecialchars($dept) ?></h6>
      <table class="table table-bordered">
        <thead>
          <tr>
            <th>ชื่อพนักงาน</th>
            <th>ชื่อผู้ใช้</th>
            <th class="text-center">จำนวนการลาทั้งหมด</th>
          </tr>
        </thead>
        <tbody>
          <?php foreach ($users as $user): ?>
            <tr>
              <td><?= htmlspecialchars($user['fullname']) ?></td>
              <td><?= htmlspecialchars($user['username']) ?></td>
              <td class="text-center"><?= (int)$user['total_leaves'] ?> ครั้ง</td>
            </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
    <?php endforeach; ?>
  </div>
</div>

    <!-- รายงานสลับวันหยุด -->
    <div class="card mb-4">
        <div class="card-header"><h5>รายงานสลับวันหยุด</h5>
        </div>
            <a href="<?= BASE_URL ?>hr/export_report.php?start_date=<?= urlencode($start_date) ?>&end_date=<?= urlencode($end_date) ?>&type=excel&report_type=swap"
           class="btn btn-success btn-sm" target="_blank">
            <i class="fas fa-file-excel"></i> ส่งออก Excel
            </a>
        <div class="card-body">
            <table class="table table-bordered">
                <thead>
                    <tr>
                        <th>พนักงาน</th>
                        <th>แผนก</th>
                        <th>วันหยุดเดิม</th>
                        <th>ขอเปลี่ยนเป็นวัน</th>
                        <th>เหตุผล</th>
                        <th>สถานะ</th>
                        <th>วันที่ยื่น</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($swap_requests)): ?>
                        <tr><td colspan="7" class="text-center text-muted">ไม่มีข้อมูล</td></tr>
                    <?php endif; ?>
                    <?php foreach ($swap_requests as $req): ?>
                        <tr>
                            <td><?= htmlspecialchars($req['username']) ?></td>
                            <td><?= htmlspecialchars($req['department']) ?></td>
                            <td><?= htmlspecialchars($req['old_date']) ?></td>
                            <td><?= htmlspecialchars($req['new_date']) ?></td>
                            <td><?= htmlspecialchars($req['reason']) ?></td>
                            <td>
                                <?php
                                if ($req['status'] === 'approved') {
                                    echo "<span class='badge bg-success'>อนุมัติแล้ว</span>";
                                } elseif ($req['status'] === 'rejected') {
                                    echo "<span class='badge bg-danger'>ปฏิเสธ</span>";
                                } else {
                                    echo "<span class='badge bg-warning text-dark'>รออนุมัติ</span>";
                                }
                                ?>
                            
                            </td>
                            <td><?= htmlspecialchars($req['created_at']) ?></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>

<?php if ($has_chart_data || $has_dept_chart_data): ?>
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
document.addEventListener('DOMContentLoaded', function () {
    // Bar Chart
    const ctxBar = document.getElementById('leaveStatsBarChart').getContext('2d');
    window.barChart = new Chart(ctxBar, {
        type: 'bar',
        data: {
            labels: <?= json_encode($chart_labels) ?>,
            datasets: [
                {
                    label: 'อนุมัติ',
                    data: <?= json_encode($chart_data_approved) ?>,
                    backgroundColor: 'rgba(35, 244, 20, 0.92)',
                },
                {
                    label: 'ปฏิเสธ',
                    data: <?= json_encode($chart_data_rejected) ?>,
                    backgroundColor: 'rgba(220, 26, 45, 0.92)',
                },
                {
                    label: 'รออนุมัติ',
                    data: <?= json_encode($chart_data_pending) ?>,
                    backgroundColor: 'rgba(242, 185, 15, 0.95)',
                }
            ]
        },
        options: {
            responsive: true,
            plugins: {
                title: { display: false },
            },
            scales: {
                x: { stacked: true },
                y: {
                    stacked: true,
                    beginAtZero: true,
                    ticks: { precision: 0 }
                }
            }
        }
    });

    // Pie Chart
    const ctxPie = document.getElementById('leaveStatusPieChart').getContext('2d');
    window.pieChart = new Chart(ctxPie, {
        type: 'pie',
        data: {
            labels: ['อนุมัติแล้ว', 'ปฏิเสธ', 'รออนุมัติ'],
            datasets: [{
                label: 'สถานะการลา',
                data: <?= json_encode($pie_chart_data) ?>,
                backgroundColor: [
                    'rgba(35, 244, 20, 0.92)',
                    'rgba(255, 0, 25, 1)',
                    'rgba(241, 183, 8, 0.98)'
                ],
                borderColor: ['#ffffff', '#ffffff', '#ffffff'],
                borderWidth: 2
            }]
        },
        options: {
            responsive: true,
            plugins: { legend: { position: 'top' } }
        }
    });

    <?php if ($has_dept_chart_data): ?>
    // Department Chart
    const ctxDept = document.getElementById('departmentLeaveChart').getContext('2d');
    window.deptChart = new Chart(ctxDept, {
        type: 'bar',
        data: {
            labels: <?= json_encode($dept_chart_labels) ?>,
            datasets: [{
                label: 'จำนวนใบลา',
                data: <?= json_encode($dept_chart_counts) ?>,
                backgroundColor: [
                  'rgba(0, 254, 59, 0.95)',
                  'rgba(220, 53, 69, 0.8)',
                  'rgba(255, 193, 7, 0.8)',
                  'rgba(54, 162, 235, 0.8)',
                  'rgba(153, 102, 255, 0.8)',
                  'rgba(255, 99, 132, 0.8)',
                  'rgba(255, 206, 86, 0.8)',
                  'rgba(75, 192, 192, 0.8)',
                  'rgba(201, 203, 207, 0.8)'
                ]
            }]
        },
        options: {
            responsive: true,
            plugins: {
                legend: { display: false },
                title: { display: false }
            },
            scales: {
                x: { beginAtZero: true },
                y: { beginAtZero: true, ticks: { precision: 0 } }
            }
        }
    });
    <?php endif; ?>

    // ====== ปุ่มดาวน์โหลดกราฟ ======
    document.getElementById('downloadBarChart').addEventListener('click', function() {
        const url = window.barChart.toBase64Image();
        const link = document.createElement('a');
        link.href = url;
        link.download = 'leaveStatsBarChart.png';
        link.click();
    });
    document.getElementById('downloadPieChart').addEventListener('click', function() {
        const url = window.pieChart.toBase64Image();
        const link = document.createElement('a');
        link.href = url;
        link.download = 'leaveStatusPieChart.png';
        link.click();
    });
    <?php if ($has_dept_chart_data): ?>
    document.getElementById('downloadDeptChart').addEventListener('click', function() {
        const url = window.deptChart.toBase64Image();
        const link = document.createElement('a');
        link.href = url;
        link.download = 'departmentLeaveChart.png';
        link.click();
    });
    <?php endif; ?>
});
</script>
<?php endif; ?>

<?php include '../includes/footer.php'; ?>
