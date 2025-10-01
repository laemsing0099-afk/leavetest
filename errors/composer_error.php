<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>ข้อผิดพลาดในการตั้งค่าระบบ</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        body {
            background-color: #f8f9fa;
        }
        .error-container {
            max-width: 700px;
            margin-top: 5rem;
        }
        .terminal {
            background-color: #212529;
            color: #f8f9fa;
            padding: 1rem;
            border-radius: 0.25rem;
            font-family: 'Courier New', Courier, monospace;
            font-size: 0.9em;
        }
    </style>
</head>
<body>
    <div class="container error-container">
        <div class="card shadow-sm">
            <div class="card-header bg-danger text-white">
                <h4 class="mb-0"><i class="fas fa-exclamation-triangle me-2"></i> พบข้อผิดพลาดในการตั้งค่าระบบ (System Configuration Error)</h4>
            </div>
            <div class="card-body p-4">
                <p class="lead">ระบบไม่พบไฟล์ <strong>vendor/autoload.php</strong> ซึ่งเป็นส่วนประกอบสำคัญที่จำเป็นสำหรับการทำงานของระบบ (เช่น การส่งอีเมล)</p>
                <p>ปัญหานี้เกิดขึ้นเนื่องจากไลบรารีที่จำเป็น (Dependencies) ยังไม่ได้รับการติดตั้งอย่างสมบูรณ์</p>
                <hr>
                <h5 class="mb-3">ขั้นตอนการแก้ไข:</h5>
                <ol class="list-group list-group-numbered">
                    <li class="list-group-item"><strong>เปิด Command Prompt หรือ Terminal</strong></li>
                    <li class="list-group-item"><strong>เข้าไปที่ไดเรกทอรีของโปรเจกต์:</strong><div class="terminal my-2"><code>cd c:\xampp\htdocs\leave_management</code></div></li>
                    <li class="list-group-item"><strong>รันคำสั่งเพื่อติดตั้งไลบรารี:</strong><div class="terminal my-2"><code>composer require phpmailer/phpmailer</code></div><small class="text-muted">หากคุณยังไม่มี Composer, สามารถดาวน์โหลดและติดตั้งได้จาก <a href="https://getcomposer.org/" target="_blank">getcomposer.org</a></small></li>
                    <li class="list-group-item">เมื่อคำสั่งทำงานเสร็จสิ้น จะมีโฟลเดอร์ชื่อ <code>vendor</code> ปรากฏขึ้นในโปรเจกต์ของคุณ จากนั้นให้ลองรีเฟรชหน้านี้อีกครั้ง</li>
                </ol>
            </div>
        </div>
    </div>
</body>
</html>