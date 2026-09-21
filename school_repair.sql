CREATE DATABASE IF NOT EXISTS `school_repair` DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE `school_repair`;

-- 1. ตารางผู้ใช้งาน (users)
CREATE TABLE IF NOT EXISTS `users` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `username` VARCHAR(50) NOT NULL UNIQUE,
  `password` VARCHAR(255) NOT NULL,
  `fullname` VARCHAR(100) NOT NULL,
  `role` ENUM('admin', 'teacher', 'technician') NOT NULL DEFAULT 'teacher',
  `email` VARCHAR(100) DEFAULT NULL,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- 2. ตารางใบแจ้งซ่อม (repair_requests)
CREATE TABLE IF NOT EXISTS `repair_requests` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `request_no` VARCHAR(30) NOT NULL UNIQUE,
  `user_id` INT NOT NULL,
  `building` VARCHAR(100) NOT NULL,
  `room` VARCHAR(50) NOT NULL,
  `problem_type` ENUM('electrical', 'furniture', 'computer', 'aircon', 'other') NOT NULL,
  `description` TEXT NOT NULL,
  `urgency` ENUM('low', 'medium', 'high') DEFAULT 'medium',
  `status` ENUM('pending', 'accepted', 'in_progress', 'completed') DEFAULT 'pending',
  `assigned_to` INT DEFAULT NULL,
  `due_date` DATE DEFAULT NULL,
  `completion_date` DATE DEFAULT NULL,
  `cost` DECIMAL(10,2) DEFAULT NULL,
  `result` TEXT DEFAULT NULL,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (`user_id`) REFERENCES `users`(`id`) ON DELETE CASCADE,
  FOREIGN KEY (`assigned_to`) REFERENCES `users`(`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- 3. ตารางรูปภาพประกอบ (repair_images)
CREATE TABLE IF NOT EXISTS `repair_images` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `repair_id` INT NOT NULL,
  `image_type` ENUM('before', 'after') NOT NULL,
  `image_path` VARCHAR(255) NOT NULL,
  `uploaded_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (`repair_id`) REFERENCES `repair_requests`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- 4. ตารางความคิดเห็น (comments)
CREATE TABLE IF NOT EXISTS `comments` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `repair_id` INT NOT NULL,
  `user_id` INT NOT NULL,
  `comment` TEXT NOT NULL,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (`repair_id`) REFERENCES `repair_requests`(`id`) ON DELETE CASCADE,
  FOREIGN KEY (`user_id`) REFERENCES `users`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ==========================================================
-- ข้อมูลเริ่มต้นสำหรับทดสอบ (Seed Data)
-- รหัสผ่านผู้ใช้ทุกบัญชีคือ: 123456
-- ==========================================================

INSERT INTO `users` (`id`, `username`, `password`, `fullname`, `role`, `email`) VALUES
(1, 'admin', '$2y$10$p8O2NRa2bn2jbcAIHZDHyuqS2vZ3ffOgNcMxzZp2lxC5XR/OqNZGm', 'ผู้ดูแลระบบ (Admin)', 'admin', 'admin@school.ac.th'),
(2, 'teacher1', '$2y$10$p8O2NRa2bn2jbcAIHZDHyuqS2vZ3ffOgNcMxzZp2lxC5XR/OqNZGm', 'ครูสมชาย ใจดี', 'teacher', 'somchai@school.ac.th'),
(3, 'teacher2', '$2y$10$p8O2NRa2bn2jbcAIHZDHyuqS2vZ3ffOgNcMxzZp2lxC5XR/OqNZGm', 'ครูวิภา รัตนอุบล', 'teacher', 'wipa@school.ac.th'),
(4, 'tech1', '$2y$10$p8O2NRa2bn2jbcAIHZDHyuqS2vZ3ffOgNcMxzZp2lxC5XR/OqNZGm', 'ช่างประเสริฐ ฝีมือดี', 'technician', 'prasert@school.ac.th'),
(5, 'tech2', '$2y$10$p8O2NRa2bn2jbcAIHZDHyuqS2vZ3ffOgNcMxzZp2lxC5XR/OqNZGm', 'ช่างสมศักดิ์ ซ่อมไว', 'technician', 'somsak@school.ac.th')
ON DUPLICATE KEY UPDATE `fullname` = VALUES(`fullname`);

INSERT INTO `repair_requests` (`id`, `request_no`, `user_id`, `building`, `room`, `problem_type`, `description`, `urgency`, `status`, `assigned_to`, `due_date`, `completion_date`, `cost`, `result`) VALUES
(1, 'RP-202609-001', 2, 'อาคารเรียน 3', 'ห้อง 302', 'aircon', 'เครื่องปรับอากาศมีน้ำหยดตลอดเวลาและไม่ค่อยมีความเย็น', 'high', 'pending', NULL, NULL, NULL, NULL, NULL),
(2, 'RP-202609-002', 3, 'อาคารศูนย์คอมพิวเตอร์', 'Lab 1', 'computer', 'เครื่องคอมพิวเตอร์เปิดไม่ติด 3 เครื่อง หน้าจอขึ้น No Signal', 'medium', 'in_progress', 4, '2026-09-25', NULL, NULL, 'กำลังตรวจสอบ RAM และสายสัญญาณ VGA/HDMI'),
(3, 'RP-202609-003', 2, 'อาคารอำนวยการ', 'ห้องพักครูหมวดวิทยาศาสตร์', 'furniture', 'ขาเก้าอี้ทำงานของครูหัก โยกเยกไม่ปลอดภัย', 'low', 'completed', 5, '2026-09-20', '2026-09-20', 150.00, 'ทำการเชื่อมซ่อมขาเก้าอี้เหล็กและเปลี่ยนน็อตยึดใหม่เรียบร้อยแล้ว'),
(4, 'RP-202609-004', 3, 'อาคารเรียน 2', 'ห้อง 205', 'electrical', 'หลอดไฟติดๆ ดับๆ 2 หลอด มีเสียงกระพริบดังกวนสมาธินักเรียน', 'medium', 'accepted', 4, '2026-09-24', NULL, NULL, NULL)
ON DUPLICATE KEY UPDATE `status` = VALUES(`status`);

INSERT INTO `comments` (`id`, `repair_id`, `user_id`, `comment`) VALUES
(1, 2, 4, 'ได้รับเรื่องแล้วครับ จะเข้าตรวจสอบช่วงบ่ายวันนี้ครับ'),
(2, 3, 2, 'ขอบคุณช่างสมศักดิ์มากครับ ซ่อมไวและใช้งานได้ดีมาก')
ON DUPLICATE KEY UPDATE `comment` = VALUES(`comment`);