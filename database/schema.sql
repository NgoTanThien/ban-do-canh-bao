-- Schema chuẩn của dự án (khớp với CSDL đang chạy).
-- users.status CHỈ nhận 'active' | 'blocked' (không dùng 0/1).

CREATE DATABASE IF NOT EXISTS `traffic_map_db`
CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;

USE `traffic_map_db`;

CREATE TABLE IF NOT EXISTS `users` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `username` VARCHAR(50) NOT NULL UNIQUE,
    `full_name` VARCHAR(100) NULL,
    `avatar` VARCHAR(255) NULL,
    `email` VARCHAR(100) NOT NULL UNIQUE,
    `google_id` VARCHAR(100) NULL,
    `phone` VARCHAR(20) NULL,
    `password` VARCHAR(255) NOT NULL,
    `role` ENUM('user', 'admin') DEFAULT 'user',
    `status` ENUM('active', 'blocked') DEFAULT 'active',
    `is_verified` TINYINT(1) DEFAULT 0,
    `reputation_score` INT DEFAULT 10, -- Điểm uy tín (trừ điểm nếu báo tin giả)
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX `idx_google_id` (`google_id`)
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS `reports` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `user_id` INT NOT NULL,
    `type` ENUM('ngap_nuoc', 'ket_xe') NOT NULL,
    `severity` ENUM('nhe', 'vua', 'nghiem_trong') DEFAULT 'vua',

    `latitude` DECIMAL(10, 8) NOT NULL,
    `longitude` DECIMAL(11, 8) NOT NULL,
    `address_text` VARCHAR(255) NULL,
    `description` TEXT NULL,
    `image_url` VARCHAR(255) NULL,

    `confirm_count` INT DEFAULT 1,
    `reject_count` INT DEFAULT 0,

    `status` ENUM('dang_dien_ra', 'da_ket_thuc', 'bi_an') DEFAULT 'dang_dien_ra',
    `expires_at` TIMESTAMP NULL,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,

    CONSTRAINT `fk_reports_user` FOREIGN KEY (`user_id`)
        REFERENCES `users`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS `report_votes` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `report_id` INT NOT NULL,
    `user_id` INT NOT NULL,
    `vote_type` ENUM('confirm', 'reject') NOT NULL,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,

    CONSTRAINT `fk_votes_report` FOREIGN KEY (`report_id`)
        REFERENCES `reports`(`id`) ON DELETE CASCADE,
    CONSTRAINT `fk_votes_user` FOREIGN KEY (`user_id`)
        REFERENCES `users`(`id`) ON DELETE CASCADE,

    UNIQUE KEY `unique_user_report_vote` (`report_id`, `user_id`)
) ENGINE=InnoDB;

-- Token ghi nhớ đăng nhập (Remember Me 30 ngày) và đặt lại mật khẩu
CREATE TABLE IF NOT EXISTS `user_tokens` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `user_id` INT NOT NULL,
    `token_hash` VARCHAR(64) NOT NULL,
    `type` ENUM('remember_me', 'reset_password') NOT NULL,
    `expires_at` DATETIME NOT NULL,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,

    CONSTRAINT `fk_tokens_user` FOREIGN KEY (`user_id`)
        REFERENCES `users`(`id`) ON DELETE CASCADE,
    INDEX `idx_token_type` (`token_hash`, `type`)
) ENGINE=InnoDB;

-- Mã OTP xác thực email khi đăng ký hoặc đặt lại mật khẩu
CREATE TABLE IF NOT EXISTS `email_otps` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `email` VARCHAR(100) NOT NULL,
    `otp_code` VARCHAR(10) NOT NULL,
    `action` ENUM('register', 'reset_password') NOT NULL,
    `data` TEXT NULL,
    `expires_at` DATETIME NOT NULL,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,

    INDEX `idx_email_action` (`email`, `action`)
) ENGINE=InnoDB;
