-- phpMyAdmin SQL Dump
-- version 5.2.2
-- https://www.phpmyadmin.net/
--
-- Host: localhost:3306
-- Generation Time: Sep 30, 2025 at 05:20 AM
-- Server version: 8.4.3
-- PHP Version: 8.3.16

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Database: `leave_management`
--

-- --------------------------------------------------------

--
-- Table structure for table `departments`
--

CREATE TABLE `departments` (
  `id` int NOT NULL,
  `name` varchar(255) COLLATE utf8mb4_general_ci NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `departments`
--

INSERT INTO `departments` (`id`, `name`) VALUES
(1, 'บัญชี'),
(2, 'ไอที'),
(3, 'ฝ่ายผลิต'),
(4, 'บุคคล');

-- --------------------------------------------------------

--
-- Table structure for table `holiday_balance`
--

CREATE TABLE `holiday_balance` (
  `id` int NOT NULL,
  `user_id` int DEFAULT NULL,
  `year` int DEFAULT NULL,
  `days` int DEFAULT NULL,
  `reason` varchar(255) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `updated_at` datetime DEFAULT NULL,
  `status` varchar(20) COLLATE utf8mb4_general_ci DEFAULT 'pending'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `holiday_swaps`
--

CREATE TABLE `holiday_swaps` (
  `id` int NOT NULL,
  `user_id` int DEFAULT NULL,
  `old_date` date DEFAULT NULL,
  `new_date` date DEFAULT NULL,
  `reason` varchar(255) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `status` varchar(20) COLLATE utf8mb4_general_ci DEFAULT 'pending',
  `document` varchar(255) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `created_at` datetime DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `holiday_swaps`
--

INSERT INTO `holiday_swaps` (`id`, `user_id`, `old_date`, `new_date`, `reason`, `status`, `document`, `created_at`) VALUES
(2, 5, '2025-07-25', '2025-07-26', '12', 'approved', NULL, '2025-07-20 14:39:55');

-- --------------------------------------------------------

--
-- Table structure for table `leave_requests`
--

CREATE TABLE `leave_requests` (
  `id` int NOT NULL,
  `user_id` int NOT NULL,
  `leave_type_id` int NOT NULL,
  `start_date` date NOT NULL,
  `end_date` date NOT NULL,
  `reason` text COLLATE utf8mb4_general_ci NOT NULL,
  `document_path` varchar(255) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `status` enum('pending','approved','rejected') COLLATE utf8mb4_general_ci DEFAULT 'pending',
  `reject_reason` varchar(255) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `approved_by` int DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `leave_requests`
--

INSERT INTO `leave_requests` (`id`, `user_id`, `leave_type_id`, `start_date`, `end_date`, `reason`, `document_path`, `status`, `reject_reason`, `approved_by`, `created_at`) VALUES
(36, 12, 7, '2025-08-01', '2025-08-01', 'เทส', NULL, 'approved', NULL, 2, '2025-07-31 09:55:26'),
(37, 54, 6, '2025-08-07', '2025-08-07', 'ลาเนื่องจากเป็นวันหยุดค่ะ', 'doc_54_1754290287.jpg', 'approved', NULL, 2, '2025-08-04 06:51:27'),
(38, 56, 6, '2025-08-25', '2025-08-31', 'ลาออก', NULL, 'approved', NULL, NULL, '2025-08-20 09:59:41'),
(39, 56, 6, '2025-09-01', '2025-09-02', 'ทดสอบระบบ', NULL, 'rejected', 'ok', 2, '2025-08-25 11:33:13'),
(40, 55, 2, '2025-10-03', '2025-10-04', 'ป่วย', NULL, 'approved', NULL, 1, '2025-09-30 03:33:30'),
(41, 55, 2, '2025-10-04', '2025-10-04', 'เที่ยว', 'doc_55_1759203611.png', 'approved', NULL, 1, '2025-09-30 03:40:11');

-- --------------------------------------------------------

--
-- Table structure for table `leave_rules`
--

CREATE TABLE `leave_rules` (
  `id` int NOT NULL,
  `rule_name` varchar(255) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `description` text COLLATE utf8mb4_general_ci,
  `leave_type_id` int DEFAULT NULL,
  `max_days` int DEFAULT NULL,
  `min_notice_days` int DEFAULT NULL,
  `max_requests_per_month` int DEFAULT NULL,
  `blackout_start_date` date DEFAULT NULL,
  `blackout_end_date` date DEFAULT NULL,
  `department` varchar(50) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `created_by` int NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `leave_rules`
--

INSERT INTO `leave_rules` (`id`, `rule_name`, `description`, `leave_type_id`, `max_days`, `min_notice_days`, `max_requests_per_month`, `blackout_start_date`, `blackout_end_date`, `department`, `created_by`, `created_at`) VALUES
(8, NULL, 'การลานี้ หากท่านลาท่านจะไม่ได้รับค่าจ้างในวันที่ท่านลา', 2, 3, 2, 2, NULL, NULL, '', 2, '2025-07-26 03:39:34');

-- --------------------------------------------------------

--
-- Table structure for table `leave_types`
--

CREATE TABLE `leave_types` (
  `id` int NOT NULL,
  `name` varchar(50) COLLATE utf8mb4_general_ci NOT NULL,
  `description` text COLLATE utf8mb4_general_ci,
  `requires_document` tinyint(1) DEFAULT '0'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `leave_types`
--

INSERT INTO `leave_types` (`id`, `name`, `description`, `requires_document`) VALUES
(2, 'ลากิจ', 'การลาด้วยเหตุผลส่วนตัว ตามเงื่อนไขจะไม่ได้รับค่าจ้าง', 0),
(6, 'ลากิจด่วน', 'การลาเพราะเหตุด่วนเหตุร้าย หากท่านมีความจำเป็น ต้องลาโปรดเเจ้งรายล่ะเอียดให้ครบถ้วน', 0),
(7, 'ลาป่วย', 'การลานี้ท่านสามารถลาได้ หากท่านเจ็บป่วยร้ายเเรงสามารถลาได้เเละโปรดเเจ้งรายล่ะเอียดกับฝ่ายบุคคลเพิ่มเป็นการรักษาสิทธิของตัวท่างเอง', 0);

-- --------------------------------------------------------

--
-- Table structure for table `shift_swaps`
--

CREATE TABLE `shift_swaps` (
  `id` int NOT NULL,
  `requester_id` int NOT NULL,
  `acceptor_id` int DEFAULT NULL,
  `original_date` date NOT NULL,
  `new_date` date NOT NULL,
  `reason` text COLLATE utf8mb4_general_ci NOT NULL,
  `status` enum('pending','approved','rejected') COLLATE utf8mb4_general_ci DEFAULT 'pending',
  `approved_by` int DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `users`
--

CREATE TABLE `users` (
  `id` int NOT NULL,
  `username` varchar(50) COLLATE utf8mb4_general_ci NOT NULL,
  `password` varchar(255) COLLATE utf8mb4_general_ci NOT NULL,
  `password_plain` varchar(255) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `fullname` varchar(100) COLLATE utf8mb4_general_ci NOT NULL,
  `email` varchar(100) COLLATE utf8mb4_general_ci NOT NULL,
  `role` enum('admin','hr','manager','employee') COLLATE utf8mb4_general_ci NOT NULL,
  `department` varchar(50) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `users`
--

INSERT INTO `users` (`id`, `username`, `password`, `password_plain`, `fullname`, `email`, `role`, `department`, `created_at`) VALUES
(1, 'admin', '$2y$10$4FqOhXnAlbQj2aROeApZ7OY002rmMjqYjHxTEdvboA8tBtR2YtVAy', 'ub19971997', 'หจก.ศูนย์รถยนต์อุบลเซอร์วิส 1997', 'hrubonservice@gmail.com', 'admin', 'IT SUBPORT', '2025-07-12 08:50:12'),
(2, 'hr', '$2y$10$yOhOsG6sEAy9hxG89p66ZObPzQGk3UzrV0/pLxTL.luMC60M4gGka', '1234', 'นางศศิวรรณ วงค์ล้อม', 'hrubonservice@gmail.com', 'hr', 'HR', '2025-07-12 08:50:12'),
(11, 'ผจก', '$2y$10$tuErCfIbMMGVLNK7e.2V5eJpBuCoaMZWv8n2a8L.uV4JS69hNDWxy', '088-7199978', 'นางสาวเจนจิรา ตาแสง', 'admin@email.com', 'admin', 'บริหาร', '2025-07-24 09:37:42'),
(12, '14', '$2y$10$at77jAowQ3RAdJdkSm0BMe0uB7tkCE9v0BbVkNAZa8fN9P0LKxrUS', '085-4942745', 'นางศศิวรรณ วงค์ล้อม', 'big.sasiwan@gmail.com', 'employee', 'HR', '2025-07-24 09:40:09'),
(13, 'IT', '$2y$10$QsuOqPHyHPqH.jBdzIJPf.dP2FzmwYQ6yHV..iBFRSMgv/wl9Wg6q', '088-7199979', 'นางสาวเจนจิรา ตาแสง', 'S@email.com', 'manager', 'IT', '2025-07-24 09:42:08'),
(14, '19', '$2y$10$Y6pQAbucZnY89NMmH9.Wu.7NJX0S9n84/Meq3z7tKp17.fSVyvSpO', '088-7199978', 'นางสาวเจนจิรา ตาแสง', 'n@email.com', 'employee', 'บริหาร', '2025-07-24 09:44:04'),
(15, '11', '$2y$10$5X90gQQZgGWVmdD7NFEEb.TYDuc55fuvk3qBPiyytPKcsB71M4ykG', '089-1234012', 'นายพจน์สุพล ยืนมั่น', 'm@email.com', 'employee', 'หัวหน้าศูนย์บริการ', '2025-07-24 09:46:45'),
(16, '32', '$2y$10$xWUXefqR/L1gunzreINvS.yTBxwteGMPpRWAu9Ore0CvdS6PJeBIu', '099-0980315', 'นายศรราม รางสถิต', 'gf@email.com', 'employee', 'ช่างโรงA', '2025-07-24 09:48:55'),
(17, 'UB-A', '$2y$10$CGrqFjjrSQ2VrLxGz6CrQOd4xUuiFqGbRe2TGJ/3mXmkA55rUa4bK', '099-0980315', 'นายศรราม รางสถิต', 'g@email.com', 'manager', 'ช่างโรงA', '2025-07-24 09:49:44'),
(18, '66011', '$2y$10$CcUsovNlvv9rqtnYNJm8AOT8iiB8.K/rfiJf775Siu8IXl96TOksu', '066-1507015', 'นายวีระพล พูลไชย', 'po@gmail.com', 'employee', 'ช่างโรงA', '2025-07-24 09:52:06'),
(19, '66010', '$2y$10$3fKAEEBfit5YHIiMtjwtKOPFKDj33SzlqD728DhWNcI.EEgLT1nMm', '082-8803277', 'นายวัชรพล สุวรรณกูฏ', 'd@gmail.com', 'employee', 'ช่างโรงA', '2025-07-24 09:53:18'),
(20, '90', '$2y$10$sw3VDsEXTUcJkZDun.tSCuOV4/Q2mm5vdXdLdqSWwv0OVMoRvt0zW', '096-1529795', 'นายภานุวัฒน์  กุตรัตน์', 'l@gmail.com', 'employee', 'ช่างโรงA', '2025-07-24 09:54:50'),
(21, '54', '$2y$10$0/ZElWOmn8EFW0xGO8qANe2sNVAT3Iv9r2Phm2vIpaLx63Z6o.RUe', '099-6189740', 'นายอุดมศักดิ์ หลักบุญ', 'AD@gmail.com', 'employee', 'ช่างโรงA', '2025-07-26 01:46:45'),
(22, '6601', '$2y$10$fqudVQsWVFbWUi0btj9gteIoWuSViKAg3/TkiCD40LwEmZGRxFWKK', '099-2845582', 'นายพรหมมินทร์ วงศ์จอม', 'wr@gmail.com', 'employee', 'ช่างโรงA', '2025-07-26 01:47:22'),
(23, '67019', '$2y$10$FSfdB5UVoC00BaO4ENyJEOeKJaYR0YmuPjYoZ/nVE6gK8rBI81mBm', '098-0961944', 'นายนภัส รูปใหญ่', 'Np@gmail.com', 'employee', 'ช่างโรงA', '2025-07-26 01:48:14'),
(24, '92', '$2y$10$7vPj6FN5nRUkLWdlA5jVqu59VhJUblcrAbFFN6WF5wuw68oqTNFDe', '082-0458239', 'นยสุพจน์ สมสมัย', 'SU@gmail.com', 'employee', 'ช่างโรงA', '2025-07-26 01:49:00'),
(25, 'UB-B', '$2y$10$5O.ghLYRXIqEcoJ0WPDKQeCrByu/Tn5PBODfucQHRVdTwGPnLLs26', '062-6720992', 'นายวุฒิไกร โทรพันธ์', 'VW@gmail.com', 'manager', 'ช่างโรงB', '2025-07-26 01:49:58'),
(26, '13', '$2y$10$99.MIghJZsvNz83cm.ugvuXFDu1KsY83ObFhUP4ELr2EaYP9x4iie', '062-6720992', 'นายวุฒิไกร โทรพันธ์', 'WC@gmail.com', 'employee', 'ช่างโรงB', '2025-07-26 01:50:43'),
(27, '37', '$2y$10$/VzZxCzZW5K8pWPAZVkLe.Xwv4EsCOSp9rQazq2rpSbxd3s3ANT4m', '092-7654283', 'นายสมบัติ จอมหงษ์', 'SM@gmail.com', 'employee', 'ช่างโรงB', '2025-07-26 01:58:03'),
(28, '663', '$2y$10$7wBmvu9Rh4gDqRSTVt3eNOt0jjbdA0tsYv4MWan4pxvP5edJufZMu', '090-2373820', 'นายรังสิมันต์ ปั้นทอง', 'RM@gmail.com', 'employee', 'ช่างโรงB', '2025-07-26 01:58:51'),
(29, '88', '$2y$10$XVucrdZA.JklxcNuOwy0VeycPEBpz4Hop.1nmH4YBrsgcrcCC0pTG', '099-1533996', 'นายศุธิวัฒน์ วงค์มั่น', 'SS@gmail.com', 'employee', 'ช่างโรงB', '2025-07-26 01:59:55'),
(30, '57', '$2y$10$tSDOQ3.yfsKbxvK.FxEa.OiuTAfe6v/lEQ47ahHOqm4V57Btn1KkG', '095-5614598', 'นายรณกร พุทธวงศ์', 'Rn@gmail.com', 'employee', 'ช่างโรงB', '2025-07-26 02:00:30'),
(31, '67015', '$2y$10$V30Y4lEuQ1YF6w7KeMcQtu58uLliLu6txTUi.EJYVCAJOY2xer22u', '093-0853962', 'นายอนุชิต สร้อยสิงห์', 'AU@gmail.com', 'employee', 'ช่างโรงB', '2025-07-26 02:01:09'),
(32, '67013', '$2y$10$VbwS4iDwgKeljOSF836See9dn3.P6aVWHR7O7tKo9V94g5K6/ku3C', '061-9768008', 'นายณัฐพล ราศรี', 'NT@gmail.com', 'employee', 'ช่างโรงB', '2025-07-26 02:03:06'),
(33, '2025005', '$2y$10$li3vgLXCsVj/PaluS4xP2u5pQ.uKQ7l/kXY098DA4c9kzm.QX/Su.', '084-9709273', 'นายจีระกิตติ์ ละมูล', 'J@gmail.com', 'employee', 'QUICK SERVICE', '2025-07-26 02:04:20'),
(34, '67018', '$2y$10$uJZIwk7OKK3qFgqu1P8.2OpEw4fM4L7ZyLg9G1ERPrgcFKHJiet3S', '080-3820869', 'นายสุวิจักขณ์ ทองรอง', 'SUg@gmail.com', 'employee', 'QUICK SERVICE', '2025-07-26 02:05:39'),
(35, '1', '$2y$10$wq8.Cpf6TwdSvH723Sh2g.9NrljxQZB5XQtxUuxRZYvtZki/45Lx2', '092-6419965', 'นายชัยเจริญ รุ่งเรือง', 'CH@gmail.com', 'employee', 'QUICK SERVICE', '2025-07-26 02:06:30'),
(36, '3', '$2y$10$x1jGF8NjwhNpTaOvbrn5M.Nav06AH7IFR7eSSyZncwmN08yQBllwu', '093-4383577', 'นายมงคล พิมพ์ผกา', 'MM@gmail.com', 'employee', 'ทีม G', '2025-07-26 02:08:17'),
(37, '67001', '$2y$10$UwGzEFOFK46i9zsB1JLTTOwtHW0w.hDmY9sFLeyCJMOK.pJeUdnjS', '062-6381872', 'นายสิทธินันต์  มุคำ', 'SK@gmail.com', 'employee', 'ทีม G', '2025-07-26 02:09:05'),
(39, '2325', '$2y$10$h3ElwECqMtX9Rwc1hDL/rOsEKu9vYdpfayHmN2qL3mY1PMBSH8VGW', '098-0266607', 'นายเกรียงไกร เรือนคำใจ', 'UI@gmail.com', 'employee', 'ทีม G', '2025-07-26 02:11:03'),
(40, '67029', '$2y$10$zjV4Nii811C9WhteucXb1.R0X1biyChWntvno8dawf.3IhbpxEaA6', '095-6108282', 'นางสาวปิยะภรณ์ แซ่อึ้ง', 'pi@gmail.com', 'employee', 'SA', '2025-07-26 02:11:56'),
(41, 'UB-SA', '$2y$10$qRUAZE9cQcB9TniSjrKtlOJhj67F5oPABB52m0ImLISTZqmopJRla', '095-6108282', 'นางสาวปิยะภรณ์ แซ่อึ้ง', 'T@gmail.com', 'manager', 'SA', '2025-07-26 02:12:39'),
(42, '28', '$2y$10$F78QntROhsIBVn7Dec6pZeStTerdLbyUbT5LWGWMw.Ks/RwdnxES6', '066-0461305', 'นางสาวกนกวรรณ แก้วบัวขาว', 'KA@gmail.com', 'employee', 'SA', '2025-07-26 02:13:29'),
(43, '91', '$2y$10$jrbNpaTJ75RXNjQDWmgf9u5YAG6dflUyNc5SH1g6CIAC7FDHlmRYy', '085-4904432', 'นางสาวมินตรา บุญสุข', 'min@gmail.com', 'employee', 'SA', '2025-07-26 02:14:26'),
(44, '17', '$2y$10$kGhLpjAEGHstbvMmxEoqYuxn/heOwIRTbO0i2tFuxIoegX5/WWjG2', '097-0984089', 'นายศราวุฒิ ใจสุข', 'SR@gmail.com', 'employee', 'SA', '2025-07-26 02:15:16'),
(45, '68', '$2y$10$rUj32/XTHcXaBczip.CGJe7GKfdrF4cFa7wKx4niGfZKAEYWVEqC.', '094-7326831', 'นางสาวกมลรัตน์ ประสิทธิ์สาร', 'KI@gmail.com', 'employee', 'ตรอ', '2025-07-26 02:16:34'),
(46, '666', '$2y$10$Nw3WBVA8WIHnXFC2MOazJuTr.QAoGzeqzDGk1.tNsSTsvNZ/EqSn.', '064-4538781', 'นายนาวิน จินดาเนตร', 'NA@gmail.com', 'employee', 'ตรอ', '2025-07-26 02:34:39'),
(47, '6708', '$2y$10$7svDK9jTGynG5PlS19IdKewkjHNrt6wTkb91pTMlLB04D3fXBt1AC', '084-7096274', 'นายพีรภัทร์ กล้าหาญ', 'PE@gmail.com', 'employee', 'ตรอ', '2025-07-26 02:37:41'),
(48, '31', '$2y$10$wZD4.evwBwLvCqvpNJdlD.aywmvVL4Jk.yBpx4BysUSloTwhJbv6u', '065-0816579', 'นายคมกริช ไชยโพธิ์', 'KM@gmail.com', 'employee', 'ตรอ', '2025-07-26 02:38:34'),
(50, '24', '$2y$10$NAs..clwWWqDuUqSCn8OBOsTQnNsiQKGtzyH7KV5zt3aeSMtZJWaO', '094-5306412', 'นายอนุทิน สารบูรณ์', 'AT@gmail.com', 'employee', 'SPD', '2025-07-26 02:57:28'),
(54, '66017', '$2y$10$SxE5tlqFd4tfJPQbY0fRluDlIJrvOYnRe94xygi7eLDEJtgB0RM7.', '087-2479933', 'นายสมัค  ( คูก้า )  ขันตรี', 'minniemin0412@gmail.com', 'employee', 'IT', '2025-07-26 03:00:55'),
(55, '2', '$2y$10$tS0uqksH0r1N1Gtrz4V09Oxudx.vtjnpCw0wf1LbKraMstlk9Mr7K', '062-9078897', 'นายวราวุธ ตามสีวัน', 'zutter0099@gmail.com', 'employee', 'IT', '2025-07-26 03:02:07'),
(56, '2025002', '$2y$10$2b68/3nD4/XMeylX/bk6beOhwTzgNZ8uAYI9BL2nbXvj2HJj47/7.', '0986263845', 'นายธรรมทัศน์ พันตนนท์', 'op1613.twz@gmail.com', 'employee', 'IT', '2025-07-26 03:04:15'),
(58, '81', '$2y$10$AE3RQWauIGipUNN8iIH42.6o4SY2aWMLrKID4QSJYt7P1/MeJBLru', '088-1319541', 'นายธนกร กุลบุตร', 'TTH@gmail.com', 'employee', 'SPD', '2025-07-26 04:08:32'),
(59, '25001', '$2y$10$UQN3RVCWxLPPAkNRdPBSc.8Sy1eiqk7D3JHsWA4CZaHXCohwAb4Iu', '099-1310929', 'นางสาวถิรดา ลุนระพัฒน์', 'EE@gmail.com', 'employee', 'CAFE', '2025-07-31 07:21:26'),
(60, '16', '$2y$10$ddYJ6rMKDyQwhxHHwKrKJuESUuXDUlq66izcz/J2vmorU.HtqXvrG', '087-1259024', 'นางสาวสุพรรษา เทพารักษ์', 'SSSS@gmail.com', 'employee', 'AC', '2025-07-31 08:22:11'),
(61, '67010', '$2y$10$1s/q/CJ5FqDZ2aoLaOnvXutGbtCBXcCvlmD2o1.K1gtgBWAmCUTb6', '093-6498137', 'นางสาวปิยนันท์ สีก่ำ', 'PUD@gmail.com', 'employee', 'AC', '2025-07-31 08:23:57'),
(62, '30', '$2y$10$YlOyx.qVjiBMYA21fSy/ve1t.1S79eixoaSirACpu13lWTSf/R7oa', '082-3185646', 'นางสาววรรณวิสา จีนวงค์', 'POI@gmail.com', 'employee', 'AC', '2025-07-31 08:25:03'),
(63, '34', '$2y$10$cEPJ0iG27as94J9zs/Cddeb8ALB4hNZT6x7ev1S.RISRLvWgSsXRe', '094-5306412', 'นางสาวขนิษฐา รุ่งเรือง', 'KKUD@gmail.com', 'employee', 'SPD', '2025-07-31 08:31:16'),
(64, '66012', '$2y$10$YGWa8GFCamQHUUWZRcEzb.H3s6yktRMCo6/eW0/O7h8YELDBWzJj.', '063-0682846', 'นายณัชพล ตันเลิศ', 'ytg@gmail.com', 'employee', 'ทีม G', '2025-07-31 08:37:41'),
(65, 'AC', '$2y$10$H82BgZ.K6JM0K0msiK7EweEDf8tDnR1oFnTNbsFe4vRhK.pIAMqra', '087-1259024', 'นางสาวสุพรรษา เทพารักษ์', 'hyy@gmail.com', 'manager', 'AC', '2025-07-31 08:42:35'),
(67, 'laem', '$2y$10$oJ3VSs0VzcOKGhcPR66/H.9426RYdpvzWwuXlA.JToWryGjFFtFj6', '0629078897', 'นายวราวุธ ตามสีวัน', 'zutter0099@gmail.com', 'employee', 'IT &amp; MARKETING', '2025-09-30 03:27:28');

--
-- Indexes for dumped tables
--

--
-- Indexes for table `departments`
--
ALTER TABLE `departments`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `holiday_balance`
--
ALTER TABLE `holiday_balance`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `holiday_swaps`
--
ALTER TABLE `holiday_swaps`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `leave_requests`
--
ALTER TABLE `leave_requests`
  ADD PRIMARY KEY (`id`),
  ADD KEY `user_id` (`user_id`),
  ADD KEY `leave_type_id` (`leave_type_id`),
  ADD KEY `approved_by` (`approved_by`);

--
-- Indexes for table `leave_rules`
--
ALTER TABLE `leave_rules`
  ADD PRIMARY KEY (`id`),
  ADD KEY `leave_type_id` (`leave_type_id`),
  ADD KEY `created_by` (`created_by`);

--
-- Indexes for table `leave_types`
--
ALTER TABLE `leave_types`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `shift_swaps`
--
ALTER TABLE `shift_swaps`
  ADD PRIMARY KEY (`id`),
  ADD KEY `requester_id` (`requester_id`),
  ADD KEY `acceptor_id` (`acceptor_id`),
  ADD KEY `approved_by` (`approved_by`);

--
-- Indexes for table `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `username` (`username`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `departments`
--
ALTER TABLE `departments`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT for table `holiday_balance`
--
ALTER TABLE `holiday_balance`
  MODIFY `id` int NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `holiday_swaps`
--
ALTER TABLE `holiday_swaps`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT for table `leave_requests`
--
ALTER TABLE `leave_requests`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=42;

--
-- AUTO_INCREMENT for table `leave_rules`
--
ALTER TABLE `leave_rules`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=9;

--
-- AUTO_INCREMENT for table `leave_types`
--
ALTER TABLE `leave_types`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=9;

--
-- AUTO_INCREMENT for table `shift_swaps`
--
ALTER TABLE `shift_swaps`
  MODIFY `id` int NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `users`
--
ALTER TABLE `users`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=68;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `leave_requests`
--
ALTER TABLE `leave_requests`
  ADD CONSTRAINT `leave_requests_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`),
  ADD CONSTRAINT `leave_requests_ibfk_2` FOREIGN KEY (`leave_type_id`) REFERENCES `leave_types` (`id`),
  ADD CONSTRAINT `leave_requests_ibfk_3` FOREIGN KEY (`approved_by`) REFERENCES `users` (`id`);

--
-- Constraints for table `leave_rules`
--
ALTER TABLE `leave_rules`
  ADD CONSTRAINT `leave_rules_ibfk_1` FOREIGN KEY (`leave_type_id`) REFERENCES `leave_types` (`id`),
  ADD CONSTRAINT `leave_rules_ibfk_2` FOREIGN KEY (`created_by`) REFERENCES `users` (`id`);

--
-- Constraints for table `shift_swaps`
--
ALTER TABLE `shift_swaps`
  ADD CONSTRAINT `shift_swaps_ibfk_1` FOREIGN KEY (`requester_id`) REFERENCES `users` (`id`),
  ADD CONSTRAINT `shift_swaps_ibfk_2` FOREIGN KEY (`acceptor_id`) REFERENCES `users` (`id`),
  ADD CONSTRAINT `shift_swaps_ibfk_3` FOREIGN KEY (`approved_by`) REFERENCES `users` (`id`);
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
