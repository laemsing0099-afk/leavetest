<?php
/**
 * ไฟล์ตรวจสอบสิทธิ์และการล็อกอิน
 */
require_once __DIR__ . '/../includes/email_functions.php';

// เริ่ม session ถ้ายังไม่เริ่ม
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

/**
 * ตรวจสอบว่ามีการล็อกอินหรือไม่
 */
function isLoggedIn() {
    return isset($_SESSION['user_id']);
}

/**
 * ตรวจสอบบทบาทผู้ใช้
 */
function checkRole($allowedRoles) {
    // ถ้า BASE_URL ลงท้ายด้วย / แล้ว ให้ลบออกก่อน
    $base = defined('BASE_URL') ? rtrim(BASE_URL, '/') : '';
    if (!isLoggedIn() || !in_array($_SESSION['role'], $allowedRoles)) {
        header("Location: " . $base . "/unauthorized.php");
        exit();
    }
}

/**
 * เข้ารหัสรหัสผ่าน
 */
function hashPassword($password) {
    return password_hash($password, PASSWORD_BCRYPT);
}

/**
 * ตรวจสอบรหัสผ่าน (รองรับทั้ง hash และ plain text)
 */
function verifyPassword($password, $hashOrPlain) {
    // ถ้า $hashOrPlain เป็น hash จริง (Bcrypt ขึ้นต้น $2y$, Argon ขึ้นต้น $argon2i$)
    if (preg_match('/^\$2y\$|\$argon2i\$/', $hashOrPlain)) {
        return password_verify($password, $hashOrPlain);
    }
    // ถ้าไม่ใช่ hash ให้เปรียบเทียบแบบ plain text
    return $password === $hashOrPlain;
}

/**
 * สร้าง Token ป้องกัน CSRF
 */
function generateCSRFToken() {
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

/**
 * ตรวจสอบ CSRF Token
 */
function validateCSRFToken($token) {
    return isset($_SESSION['csrf_token']) && hash_equals($_SESSION['csrf_token'], $token);
}
