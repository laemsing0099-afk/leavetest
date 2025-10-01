<?php
// Email Configuration
// แนะนำให้ใช้ App Password หากใช้ Gmail เพื่อความปลอดภัย

define('SMTP_HOST', 'smtp.gmail.com');      // Ex: smtp.gmail.com, smtp.office365.com
define('SMTP_PORT', 587);                   // 587 for TLS, 465 for SSL
define('SMTP_USERNAME', 'hrubonservice@gmail.com'); // อีเมลของคุณ
define('SMTP_PASSWORD', 'rccf cvza ewvz upwd');     // App Password (16 หลัก)
define('SMTP_SECURE', 'tls');               // 'tls' or 'ssl'

define('EMAIL_FROM', 'hrubonservice@gmail.com');    // อีเมลผู้ส่ง
define('EMAIL_FROM_NAME', 'ระบบเเจ้งลาอัตโนมัติ');  // ชื่อผู้ส่ง
?>
