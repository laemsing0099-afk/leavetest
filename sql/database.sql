-- สร้างฐานข้อมูล
CREATE DATABASE IF NOT EXISTS leave_management;
USE leave_management;

-- ตารางผู้ใช้งาน
CREATE TABLE users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    username VARCHAR(50) NOT NULL UNIQUE,
    password VARCHAR(255) NOT NULL,
    fullname VARCHAR(100) NOT NULL,
    email VARCHAR(100) NOT NULL,
    role ENUM('admin', 'hr', 'manager', 'employee') NOT NULL,
    department VARCHAR(50),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- ตารางประเภทการลา
CREATE TABLE leave_types (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(50) NOT NULL,
    description TEXT,
    requires_document BOOLEAN DEFAULT FALSE
);

-- ตารางการลางาน
CREATE TABLE leave_requests (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    leave_type_id INT NOT NULL,
    start_date DATE NOT NULL,
    end_date DATE NOT NULL,
    reason TEXT NOT NULL,
    document_path VARCHAR(255),
    status ENUM('pending', 'approved', 'rejected') DEFAULT 'pending',
    approved_by INT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id),
    FOREIGN KEY (leave_type_id) REFERENCES leave_types(id),
    FOREIGN KEY (approved_by) REFERENCES users(id)
);

-- ตารางสลับวันทำงาน
CREATE TABLE shift_swaps (
    id INT AUTO_INCREMENT PRIMARY KEY,
    requester_id INT NOT NULL,
    acceptor_id INT,
    original_date DATE NOT NULL,
    new_date DATE NOT NULL,
    reason TEXT NOT NULL,
    status ENUM('pending', 'approved', 'rejected') DEFAULT 'pending',
    approved_by INT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (requester_id) REFERENCES users(id),
    FOREIGN KEY (acceptor_id) REFERENCES users(id),
    FOREIGN KEY (approved_by) REFERENCES users(id)
);

-- ตารางเงื่อนไขการลา
CREATE TABLE leave_rules (
    id INT AUTO_INCREMENT PRIMARY KEY,
    rule_name VARCHAR(100) NOT NULL,
    description TEXT,
    leave_type_id INT,
    max_days INT,
    min_notice_days INT,
    max_requests_per_month INT,
    blackout_start_date DATE,
    blackout_end_date DATE,
    department VARCHAR(50),
    created_by INT NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (leave_type_id) REFERENCES leave_types(id),
    FOREIGN KEY (created_by) REFERENCES users(id)
);

-- ข้อมูลเริ่มต้น
INSERT INTO users (username, password, fullname, email, role, department) VALUES 
('admin', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'Admin User', 'admin@example.com', 'admin', NULL),
('hr', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'HR Staff', 'hr@example.com', 'hr', 'HR'),
('manager1', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'Manager One', 'manager1@example.com', 'manager', 'Sales'),
('employee1', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'Employee One', 'employee1@example.com', 'employee', 'Sales');

INSERT INTO leave_types (name, description, requires_document) VALUES 
('ลาป่วย', 'การลาด้วยเหตุผลสุขภาพ', TRUE),
('ลากิจ', 'การลาด้วยเหตุผลส่วนตัว', FALSE),
('ลาพักร้อน', 'การลาพักผ่อนประจำปี', FALSE),
('ลาคลอด', 'การลาตามกฎหมายสำหรับคุณแม่', TRUE);

INSERT INTO leave_rules (rule_name, description, leave_type_id, max_days, min_notice_days, max_requests_per_month, created_by) VALUES
('ลาพักร้อนพื้นฐาน', 'ลาพักร้อนไม่เกิน 3 วันต่อครั้ง', 3, 3, 3, 1, 1),
('ลาป่วยฉุกเฉิน', 'สามารถลาป่วยได้โดยไม่ต้องแจ้งล่วงหน้า', 1, 5, 0, 2, 1);