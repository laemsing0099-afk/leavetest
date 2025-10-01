<?php
if (session_status() === PHP_SESSION_NONE) session_start();
if (!isset($_SESSION['user_id']) || !isset($_SESSION['role']) || !isset($_SESSION['username'])) {
    header("Location: " . BASE_URL . "index.php"); exit();
}
$allowed_roles = ['admin', 'hr', 'manager', 'employee'];
if (!in_array($_SESSION['role'], $allowed_roles)) {
    header("Location: " . BASE_URL . "index.php"); exit();
}
$dashboard_link = BASE_URL . 'index.php';
switch ($_SESSION['role']) {
    case 'admin':    $dashboard_link = BASE_URL . 'admin/dashboard.php';    break;
    case 'hr':       $dashboard_link = BASE_URL . 'hr/dashboard.php';       break;
    case 'manager':  $dashboard_link = BASE_URL . 'manager/dashboard.php';  break;
    case 'employee': $dashboard_link = BASE_URL . 'employee/dashboard.php'; break;
}
$role_icon_map = [
    'admin' => 'fa-user-shield',
    'hr' => 'fa-user-tie',
    'manager' => 'fa-user-check',
    'employee' => 'fa-user'
];
$user_role_icon = $role_icon_map[$_SESSION['role']] ?? 'fa-user';

// เมนูสำหรับทุก role (เพื่อ reuse ทั้ง sidebar/topbar)
function get_menu_items($role) {
    $base = BASE_URL;
    $items = [];
    if ($role == 'admin') {
        $items = [
            ['href' => "{$base}admin/dashboard.php",         'icon' => 'fa-chart-line',     'text' => 'แดชบอร์ด', 'uri' => 'dashboard'],
            ['href' => "{$base}admin/leave_rules.php",       'icon' => 'fa-cogs',           'text' => 'ตั้งค่าเงื่อนไข', 'uri' => 'leave_rules'],
            ['href' => "{$base}admin/leave_types.php",       'icon' => 'fa-calendar-plus',  'text' => 'จัดการประเภทการลา', 'uri' => 'manage_accumulate_holiday'],
            ['href' => "{$base}admin/users.php",             'icon' => 'fa-users-cog',      'text' => 'จัดการผู้ใช้', 'uri' => 'users'],
            ['href' => "{$base}hr/all_leaves_calendar.php",  'icon' => 'fa-calendar-days',  'text' => 'ปฏิทินการลา', 'uri' => 'all_leaves_calendar'],
            ['href' => "{$base}hr/reports.php",              'icon' => 'fa-file-lines',     'text' => 'รายงาน', 'uri' => 'reports'],
        ];
    } elseif ($role == 'hr') {
        $items = [
            ['href' => "{$base}hr/dashboard.php",            'icon' => 'fa-house-user',     'text' => 'แดชบอร์ด', 'uri' => 'dashboard'],
            ['href' => "{$base}hr/manage_leaves.php",        'icon' => 'fa-calendar-check', 'text' => 'จัดการการลา', 'uri' => 'manage_leaves'],
            ['href' => "{$base}admin/leave_types.php",       'icon' => 'fa-calendar-plus',  'text' => 'จัดการประเภทการลา', 'uri' => 'manage_accumulate_holiday'],
            ['href' => "{$base}hr/manage_swap_holiday.php",  'icon' => 'fa-retweet',        'text' => 'สลับวันหยุด', 'uri' => 'manage_swap_holiday'],
            ['href' => "{$base}admin/users.php",             'icon' => 'fa-users',          'text' => 'จัดการผู้ใช้', 'uri' => 'users'],
            ['href' => "{$base}admin/leave_rules.php",       'icon' => 'fa-cogs',           'text' => 'ตั้งค่าเงื่อนไข', 'uri' => 'leave_rules'],
            ['href' => "{$base}hr/all_leaves_calendar.php",  'icon' => 'fa-calendar-days',  'text' => 'ปฏิทินการลา', 'uri' => 'all_leaves_calendar'],
            ['href' => "{$base}hr/reports.php",              'icon' => 'fa-file-lines',     'text' => 'รายงาน', 'uri' => 'reports'],
        ];
    } elseif ($role == 'manager') {
        $items = [
            ['href' => "{$base}manager/dashboard.php",       'icon' => 'fa-house-user',        'text' => 'แดชบอร์ด', 'uri' => 'dashboard'],
            ['href' => "{$base}manager/team_leaves.php",     'icon' => 'fa-users-viewfinder',  'text' => 'การลาของทีม', 'uri' => 'team_leaves'],
            ['href' => "{$base}hr/manage_swap_holiday.php",  'icon' => 'fa-retweet',           'text' => 'สลับวันหยุด', 'uri' => 'manage_swap_holiday'],
        ];
    } else {
        $items = [
            ['href' => "{$base}employee/dashboard.php",      'icon' => 'fa-house-user',    'text' => 'แดชบอร์ด', 'uri' => 'dashboard'],
            ['href' => "{$base}employee/swap_holiday.php",   'icon' => 'fa-retweet',       'text' => 'ขอสลับวันหยุด', 'uri' => 'swap_holiday'],
        ];
    }
    return $items;
}
$menu_items = get_menu_items($_SESSION['role']);
?>
<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>ระบบจัดการลางาน</title>
    <link rel="icon" type="image/png" href="<?= BASE_URL ?>assets/images/icon1.png">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.2/css/all.min.css">
    <link href="<?= BASE_URL ?>assets/css/style.css" rel="stylesheet">
    <style>
        body { background: #f6f8fa; }
        /* Hide default navbar on md+ and show sidebar */
        @media (min-width: 992px) {
            .navbar-top { display: none !important; }
            .sidebar-left { display: flex !important; }
            body { padding-left: 210px !important; }
        }
        @media (max-width: 991px) {
            .sidebar-left { display: none !important; }
            .navbar-top { display: flex !important; }
            body { padding-left: 0 !important; }
        }
        .sidebar-left {
            display: none;
            position: fixed;
            top: 0; left: 0;
            height: 100vh;
            width: 210px;
            background: linear-gradient(180deg, #e53935 60%, #d32f2f 100%);
            box-shadow: 4px 0 18px 0 rgba(180, 15, 15, 0.12);
            z-index: 1090;
            flex-direction: column;
            align-items: stretch;
        }
        .sidebar-left .logo-area {
            padding: 1.2rem 1.2rem .4rem 1.2rem;
            display: flex;
            align-items: center;
            gap: 0.6em;
        }
        .sidebar-left .logo-area img {
            height: 38px;
            border-radius: 0.8em;
            box-shadow: 0 3px 12px #c43a3488;
        }
        .sidebar-left .logo-area span {
            font-size: 1.19rem;
            color: #fff;
            font-weight: 700;
            letter-spacing: 0.03em;
        }
        .sidebar-left .sidebar-menu {
            flex: 1;
            margin-top: .6em;
            display: flex;
            flex-direction: column;
            gap: 2px;
        }
        .sidebar-left .sidebar-menu .nav-link {
            color: #fff !important;
            padding: .8em 1.1em;
            border-radius: 1.8em 0 0 1.8em;
            font-size: 1.03rem;
            font-weight: 500;
            display: flex;
            align-items: center;
            gap: 0.64em;
            margin-bottom: 3px;
            transition: background .18s, color .13s;
        }
        .sidebar-left .sidebar-menu .nav-link.active,
        .sidebar-left .sidebar-menu .nav-link:hover {
            background: rgba(255,255,255,0.19);
            color: #ffe27c !important;
        }
        .sidebar-left .sidebar-bottom {
            padding: 1.3em 1.1em;
            border-top: 1.5px solid #fff1;
        }
        .sidebar-left .profile-btn,
        .sidebar-left .logout-link {
            color: #fff !important;
            display: flex; align-items: center; gap: 8px;
            padding: 0.28em 0;
            text-decoration: none;
            font-size: 1.04rem;
        }
        .sidebar-left .profile-btn:hover,
        .sidebar-left .logout-link:hover {
            color: #ffe27c !important;
            text-decoration: underline;
        }

        /* Topbar style */
        .navbar-top {
            display: flex;
            background: linear-gradient(90deg, #e53935 60%, #d32f2f 100%);
            box-shadow: 0 4px 18px 0 rgba(180, 15, 15, 0.15);
            border-radius: 0 0 1.6rem 1.6rem;
            min-height: 62px;
            margin-bottom: 1.4rem;
        }
        .navbar-top .navbar-brand span {
            font-weight: bold;
            font-size: 1.2rem;
            color: #fff;
            text-shadow: 0 2px 12px rgba(90,0,0,.08);
        }
        .navbar-top .navbar-nav .nav-link {
            color: #fff !important;
            font-size: 1.02rem;
            font-weight: 500;
            padding: .48em 1em .44em 1em;
            margin: 0 .08em;
            border-radius: 2em;
            display: flex;
            align-items: center;
            gap: 0.5em;
            white-space: nowrap;
        }
        .navbar-top .navbar-nav .nav-link.active,
        .navbar-top .navbar-nav .nav-link:hover {
            background: rgba(255,255,255,0.15);
            color: #ffe27c !important;
        }
        .navbar-top .navbar-profile {
            background: rgba(0,0,0,0.16);
            border-radius: 2em;
            padding: .28em 1em;
            display: flex;
            align-items: center;
            font-weight: 600;
            gap: 8px;
            color: #fff !important;
            font-size: 1.03rem;
            text-shadow: 0 1px 8px #c43a34aa;
        }
        .navbar-top .navbar-profile i { color: #fff !important; }
        .navbar-top .logout-link { color: #ffe0e0 !important; font-weight: 600; margin-left: 1.1em;}
        .navbar-top .logout-link:hover { text-decoration: underline; color: #fff4f4 !important; }
    </style>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
</head>
<body>
<!-- Sidebar (ซ้าย) บนคอม -->
<div class="sidebar-left flex-column" style="display:none;">
    <div class="logo-area">
        <img src="<?= BASE_URL ?>assets/images/logo1.png" alt="Logo">
        <span>ระบบลางาน</span>
    </div>
    <nav class="sidebar-menu nav flex-column">
        <?php
        $current_uri = $_SERVER['REQUEST_URI'];
        foreach($menu_items as $item): 
            $is_active = strpos($current_uri, $item['uri']) !== false ? ' active' : '';
        ?>
            <a class="nav-link<?= $is_active ?>" href="<?= $item['href'] ?>">
                <i class="fa-solid <?= $item['icon'] ?>"></i>
                <?= $item['text'] ?>
            </a>
        <?php endforeach; ?>
    </nav>
    <div class="sidebar-bottom mt-auto">
        <a href="<?= BASE_URL ?>profile.php" class="profile-btn mb-2">
            <i class="fas <?= $user_role_icon ?>"></i>
            <?= htmlspecialchars($_SESSION['username'], ENT_QUOTES, 'UTF-8') ?>
        </a>
        <a class="logout-link" href="<?= BASE_URL ?>logout.php">
            <i class="fas fa-sign-out-alt me-1"></i> ออกจากระบบ
        </a>
    </div>
</div>

<!-- Topbar (บน) มือถือ/จอเล็ก -->
<nav class="navbar navbar-expand-lg navbar-top" style="display:flex;">
    <div class="container">
        <a class="navbar-brand d-flex align-items-center" href="<?= $dashboard_link ?>">
            <img src="<?= BASE_URL ?>assets/images/logo1.png" alt="Logo" style="height: 32px;" class="me-2 rounded-3 shadow">
            <span>ระบบลางาน</span>
        </a>
        <button class="navbar-toggler border-0 shadow-none" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNavMobile">
            <span class="navbar-toggler-icon"></span>
        </button>
        <div class="collapse navbar-collapse flex-grow-1" id="navbarNavMobile">
            <ul class="navbar-nav underline-menu mx-auto">
                <?php
                foreach($menu_items as $item):
                    $is_active = strpos($_SERVER['REQUEST_URI'], $item['uri']) !== false ? ' active' : '';
                ?>
                    <li class="nav-item"><a class="nav-link<?= $is_active ?>" href="<?= $item['href'] ?>"><i class="fa-solid <?= $item['icon'] ?>"></i><?= $item['text'] ?></a></li>
                <?php endforeach; ?>
            </ul>
            <div class="d-flex align-items-center ms-lg-4 mt-3 mt-lg-0">
                <a href="<?= BASE_URL ?>profile.php" class="navbar-profile me-2 text-decoration-none">
                    <i class="fas <?= $user_role_icon ?>"></i>
                    <?= htmlspecialchars($_SESSION['username'], ENT_QUOTES, 'UTF-8') ?>
                </a>
                <a class="nav-link logout-link d-flex align-items-center" href="<?= BASE_URL ?>logout.php">
                    <i class="fas fa-sign-out-alt me-1"></i> ออกจากระบบ
                </a>
            </div>
        </div>
    </div>
</nav>

<div class="container mt-4">
<?php if (isset($_SESSION['success'])): ?>
    <script>
    document.addEventListener('DOMContentLoaded', function() {
        Swal.fire({
            icon: 'success',
            title: 'สำเร็จ!',
            text: '<?= htmlspecialchars($_SESSION['success']) ?>',
            confirmButtonText: 'ปิด',
            timer: 1800,
            timerProgressBar: true,
            showConfirmButton: false
        });
    });
    </script>
    <?php unset($_SESSION['success']); ?>
<?php endif; ?>
<?php if (isset($_SESSION['error'])): ?>
    <script>
    document.addEventListener('DOMContentLoaded', function() {
        Swal.fire({
            icon: 'error',
            title: 'เกิดข้อผิดพลาด!',
            text: '<?= htmlspecialchars($_SESSION['error']) ?>',
            confirmButtonText: 'ปิด'
        });
    });
    </script>
    <?php unset($_SESSION['error']); ?>
<?php endif; ?>
</div>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
