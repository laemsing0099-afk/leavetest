<?php
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../vendor/autoload.php';
use PhpOffice\PhpWord\TemplateProcessor;

// ——— 1. ดึงข้อมูลใบลา ———
$id = intval($_GET['id'] ?? 0);
if ($id <= 0) die('ไม่พบข้อมูลใบลา');
$sql = "SELECT lr.*, 
               u.fullname, u.department, u.role,
               lt.name AS leave_type, lt.description AS leave_condition
        FROM leave_requests lr
        JOIN users u ON lr.user_id = u.id
        JOIN leave_types lt ON lr.leave_type_id = lt.id
        WHERE lr.id = ? LIMIT 1";
$stmt = $conn->prepare($sql);
$stmt->execute([$id]);
$data = $stmt->fetch(PDO::FETCH_ASSOC);
if (!$data) die('ไม่พบข้อมูลใบลา');

// แปลง role → ตำแหน่งภาษาไทย
$role_map = [
    'admin'    => 'ผู้ดูแลระบบ',
    'hr'       => 'ฝ่ายบุคคล',
    'manager'  => 'ผู้จัดการ',
    'employee' => 'พนักงาน'
];
$role_th = $role_map[$data['role']] ?? $data['role'];

// หาชื่อหัวหน้าแผนก (manager ใน department เดียวกัน)
$stmt2 = $conn->prepare(
    "SELECT fullname FROM users WHERE role = 'manager' AND department = :dept LIMIT 1"
);
$stmt2->execute(['dept' => $data['department']]);
$manager_name = $stmt2->fetchColumn() ?: '-';

// หาชื่อหัวหน้า HR
$stmt3 = $conn->prepare("SELECT fullname FROM users WHERE role = 'hr' LIMIT 1");
$stmt3->execute();
$hr_head = $stmt3->fetchColumn() ?: '-';

// ฟังก์ชันแปลงวันที่ → ไทย พร้อมปี พ.ศ.
function th_date($date) {
    if (!$date) return '-';
    $t = strtotime($date);
    $d = date('j', $t);
    $m = date('n', $t);
    $y = date('Y', $t) + 543;
    $months = ['', 'มกราคม','กุมภาพันธ์','มีนาคม','เมษายน','พฤษภาคม',
               'มิถุนายน','กรกฎาคม','สิงหาคม','กันยายน',
               'ตุลาคม','พฤศจิกายน','ธันวาคม'];
    return "{$d} {$months[$m]} {$y}";
}

// คำนวณจำนวนวันลา
$total_days = '-';
if (!empty($data['start_date']) && !empty($data['end_date'])) {
    $total_days = intval((strtotime($data['end_date']) - strtotime($data['start_date'])) / 86400) + 1;
}

// ======== ส่วนนี้สำหรับการดาวน์โหลด .docx =========
if (isset($_GET['format']) && $_GET['format'] === 'docx') {
    // กำหนด path ไฟล์ template docx
   $templatePath = __DIR__ . '/templates/leave_template.docx';


    if (!file_exists($templatePath)) die('ไม่พบไฟล์แม่แบบ');
    // ... ที่เหลือต่อปกติ ...
    $template = new TemplateProcessor($templatePath);

    // ส่งค่าไปใน template
    $template->setValue('fullname', $data['fullname']);
    $template->setValue('role', $role_th);
    $template->setValue('department', $data['department']);
    $template->setValue('leave_type', $data['leave_type']);
    $template->setValue('leave_condition', $data['leave_condition']);
    $template->setValue('reason', $data['reason']);
    $template->setValue('start_date', th_date($data['start_date']));
    $template->setValue('end_date', th_date($data['end_date']));
    $template->setValue('total_days', $total_days);
    $template->setValue('manager_name', $manager_name);
    $template->setValue('hr_head', $hr_head);
    $template->setValue('status', 
        $data['status'] === 'approved' ? 'อนุมัติ' :
        ($data['status'] === 'pending' ? 'รออนุมัติ' : 'ไม่อนุมัติ')
    );

    // ตั้งชื่อไฟล์สำหรับดาวน์โหลด
    $filename = "ใบลา-" . preg_replace('/[^ก-๙a-z0-9_]+/iu', '-', $data['fullname']) . ".docx";
    header("Content-Type: application/vnd.openxmlformats-officedocument.wordprocessingml.document");
    header("Content-Disposition: attachment; filename=\"" . $filename . "\"");
    $template->saveAs('php://output');
    exit;
}
?>
<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <title>เอกสารใบลา</title>
    <link rel="icon" type="image/png" href="<?= BASE_URL ?>assets/images/icon1.png">
    <style>
        @media print {
            @page { size: A4 portrait; margin: 10mm 14mm; }
            .noprint { display: none !important; }
        }
        body {
            font-family:"TH SarabunPSK","Sarabun",Arial,sans-serif;
            font-size:15.5pt;
            margin:0;
            background:#fff;
            line-height:1.17;
        }
        .mainbox {
            width: 685px; /* เหมาะกับ A4 (210mm - margin) */
            max-width:100vw;
            margin: 0 auto;
            padding: 6px 0 0 0;
            box-sizing: border-box;
            background: #fff;
        }
        .logo { text-align:center; }
        .logo img { height:40px; }
        .doc-title { text-align:center; font-size:19pt; font-weight:bold; margin-bottom:6px;}
        .row { margin-bottom:5px;}
        .label { width:78px; min-width:76px; display:inline-block;}
        .input-inline {
            display:inline-block;
            border-bottom:1px dotted #888;
            min-width:60px;
            margin-left:2px;
            margin-right:2px;
        }
        .signature-block {width:100%; max-width:335px; margin: 16px auto 0 auto; text-align:center;}
        .signature-line {text-align:center; letter-spacing:1px; font-size:15pt;}
        .signature-name {text-align:center;}
        .signature-title {text-align:center; font-size:14pt;}
        .center {text-align:center;}
        .mb-6 {margin-bottom:6px;}
        .mb-8 {margin-bottom:8px;}
        .mb-10 {margin-bottom:10px;}
        .mb-12 {margin-bottom:12px;}
        .mb-16 {margin-bottom:16px;}

        /* -------- ปุ่มใหม่ -------- */
        .button-bar {
            display: flex;
            justify-content: center;
            align-items: center;
            gap: 16px;
            margin-top: 14px;
            margin-bottom: 10px;
        }
        .btn-custom {
            padding: 6px 22px;
            font-size: 1rem;
            border: none;
            border-radius: 6px;
            background: #2866C7;
            color: #fff;
            transition: background 0.15s, box-shadow 0.15s;
            cursor: pointer;
            font-family: "TH SarabunPSK", "Sarabun", Arial, sans-serif;
            box-shadow: 0 2px 8px 0 rgba(40,102,199,0.08);
        }
        .btn-custom:hover, .btn-custom:focus {
            background: #19498c;
        }
        .btn-outline {
            background: #fff;
            color: #2866C7;
            border: 1.3px solid #2866C7;
            transition: background 0.2s, color 0.2s;
        }
        .btn-outline:hover, .btn-outline:focus {
            background: #e8f1ff;
            color: #19498c;
        }
        .link-custom {
            color: #1d4ed8;
            text-decoration: underline;
            background: none;
            border: none;
            font-size: 1rem;
            cursor: pointer;
            font-family: inherit;
            transition: color 0.2s;
            margin-left: 8px;
        }
        .link-custom:hover, .link-custom:focus {
            color: #2563eb;
        }
    </style>
</head>
<body>
<div class="mainbox">
    <div class="logo">
        <img src="<?= BASE_URL ?>includes/logo/logo1.png" alt="Logo">
    </div>
    <div class="doc-title">เอกสารใบลา</div>
    <div class="mb-8" style="margin-left:2px;">
        <span>เรียน หัวหน้างาน คุณ</span>
        <span class="input-inline" style="min-width:160px;"><?= htmlspecialchars($manager_name) ?></span>
    </div>
    <div class="mb-6" style="margin-left:2px;">ข้าพเจ้า</div>
    <div class="row mb-6">
        <span class="label">ชื่อ</span>
        <span class="input-inline"><?= htmlspecialchars($data['fullname']) ?></span>
        <span class="label" style="margin-left:12px;">ตำแหน่ง</span>
        <span class="input-inline"><?= htmlspecialchars($role_th) ?></span>
        <span class="label" style="margin-left:12px;">แผนก</span>
        <span class="input-inline"><?= htmlspecialchars($data['department']) ?></span>
    </div>
    <div class="row mb-6">
        <span class="label">ประเภทการลา</span>
        <span class="input-inline" style="min-width:140px;"><?= htmlspecialchars($data['leave_type']) ?></span>
    </div>
    <div class="row mb-6">
        <span class="label">เงื่อนไขการลา</span>
        <span class="input-inline" style="min-width:170px;"><?= htmlspecialchars($data['leave_condition']) ?></span>
    </div>
    <div class="mb-8">
        <span>เหตุผลการลา</span>
        <span class="input-inline" style="min-width:230px;"><?= htmlspecialchars($data['reason']) ?></span>
    </div>
    <div class="mb-8">
        <span class="input-inline" style="min-width:330px;"></span>
    </div>
    <!-- ลายเซ็นผู้ขอลา -->
    <div class="signature-block mb-10">
        <div class="signature-line">..............................</div>
        <div class="signature-name">(<?= htmlspecialchars($data['fullname']) ?>)</div>
    </div>
    <div class="signature-block mb-10">
        <div class="signature-name" style="margin-top:2px;">
            ตั้งแต่วันที่: <span class="input-inline"><?= th_date($data['start_date']) ?></span><br>
            ถึงวันที่: <span class="input-inline"><?= th_date($data['end_date']) ?></span><br>
            ทั้งหมด: <span class="input-inline" style="min-width:20px;"><?= $total_days ?> วัน</span>
        </div>
    </div>
    <div class="rhihg mb-8" style="font-weight:bold; margin-top:10px;">จึงเรียนมาเพื่อโปรดพิจารณา</div><br>
    <div class="mb-6" style="margin-left:3px;">
        เนื่องจาก คุณ <span class="input-inline"><?= htmlspecialchars($data['fullname']) ?></span>
        ตำแหน่ง <span class="input-inline"><?= htmlspecialchars($role_th) ?></span>
        ได้ทำการยื่นคำร้องการลางานประเภทลา <span class="input-inline"><?= htmlspecialchars($data['leave_type']) ?></span>
    </div>
    <div class="mb-10" style="margin-left:3px;">
        จึงได้ทำการ
        <span class="input-inline" style="min-width:46px;">
            <?= ($data['status'] === 'approved' ? 'อนุมัติ' : ($data['status'] === 'pending' ? 'รออนุมัติ' : 'ไม่อนุมัติ')) ?>
        </span>
    </div>
    <!-- ลายเซ็น HR -->
    <div class="signature-block mb-10">
        <div class="signature-line">..............................</div>
        <div class="signature-name">(<?= htmlspecialchars($hr_head) ?>)</div>
        <div class="signature-title">ฝ่ายจัดการบริหารทรัพยากรบุคคล</div>
    </div>

    <!-- ลายเซ็นหัวหน้างาน -->
    <div class="signature-block mb-10">
        <div class="signature-line">..............................</div>
        <div class="signature-name">(<?= htmlspecialchars($manager_name) ?>)</div>
        <div class="signature-title">หัวหน้างานผู้รับผิดชอบ</div>
    </div>

    <!-- ลายเซ็นหุ้นส่วนผู้จัดการ -->
    <div class="signature-block" style="margin-top:10px;">
        <div class="signature-line">………………………………</div>
        <div class="signature-name">(นายไพบูลย์ คำศรี)</div>
        <div class="signature-title">หุ้นส่วนผู้จัดการ</div>
    </div>
    <div class="button-bar noprint">
        <a href="?id=<?= $id ?>&format=docx">
            <button class="btn-custom">ดาวน์โหลด .docx (Word)</button>
        </a>
        <button onclick="window.print()" class="btn-outline btn-custom">พิมพ์เอกสาร (HTML)</button>
        <button onclick="window.close()" class="link-custom">กลับไป</button>
    </div>
</div>
</body>
</html>
