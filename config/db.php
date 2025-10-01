<?php
// --- BASE_URL กำหนดเอง ---
define('BASE_URL', 'http://localhost/leave_management/'); // แก้ให้ตรงกับที่เข้าจริง!

// --- Composer Autoloader (ไม่ต้องแก้) ---
$autoloader = __DIR__ . '/../vendor/autoload.php';
if (file_exists($autoloader)) {
    require_once $autoloader;
} else {
    require_once __DIR__ . '/../errors/composer_error.php';
    exit();
}

// --- Database Config ---
$db_host = "localhost";
$db_username = "root";
$db_password = "";
$db_database = "leave_management";

try {
    $conn = new PDO("mysql:host=$db_host;dbname=$db_database;charset=utf8", $db_username, $db_password);
    $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch(PDOException $e) {
    die("เชื่อมต่อฐานข้อมูลไม่สำเร็จ กรุณาตรวจสอบการตั้งค่าใน config/db.php<br>" . $e->getMessage());
}
?>
