<?php
require_once 'config/db.php'; // ดึง BASE_URL

session_start();
session_unset();
session_destroy();

// แก้ path redirect ให้เหมาะกับ BASE_URL ของคุณ
header("Location: " . BASE_URL . "index.php");
exit();
?>
