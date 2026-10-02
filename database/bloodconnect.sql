-- =========================================================
-- LifeDrop - Complete Database Schema + Sample Data
-- =========================================================
-- Import this file via phpMyAdmin, or run:
--   mysql -u root -p < bloodconnect.sql
-- =========================================================

SET FOREIGN_KEY_CHECKS = 0;
SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
SET time_zone = "+06:00";

CREATE DATABASE IF NOT EXISTS `bloodconnect_bd` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE `bloodconnect_bd`;

-- ---------------------------------------------------------
-- Table: locations  (Division -> District -> Upazila/Area)
-- ---------------------------------------------------------
DROP TABLE IF EXISTS `locations`;
CREATE TABLE `locations` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `division` VARCHAR(50) NOT NULL,
  `district` VARCHAR(50) NOT NULL,
  `area` VARCHAR(80) NOT NULL,
  PRIMARY KEY (`id`),
  KEY `idx_division` (`division`),
  KEY `idx_district` (`district`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ---------------------------------------------------------
-- Table: users
-- ---------------------------------------------------------
DROP TABLE IF EXISTS `users`;
CREATE TABLE `users` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `full_name` VARCHAR(120) NOT NULL,
  `email` VARCHAR(150) NOT NULL,
  `phone` VARCHAR(20) NOT NULL,
  `password_hash` VARCHAR(255) NOT NULL,
  `role` ENUM('donor','seeker','both','admin') NOT NULL DEFAULT 'seeker',
  `blood_group` ENUM('A+','A-','B+','B-','AB+','AB-','O+','O-') DEFAULT NULL,
  `division` VARCHAR(50) DEFAULT NULL,
  `district` VARCHAR(50) DEFAULT NULL,
  `area` VARCHAR(80) DEFAULT NULL,
  `gender` ENUM('Male','Female','Other') DEFAULT NULL,
  `date_of_birth` DATE DEFAULT NULL,
  `profile_photo` VARCHAR(255) DEFAULT NULL,
  `last_donation_date` DATE DEFAULT NULL,
  `availability` ENUM('available','maybe','unavailable') NOT NULL DEFAULT 'available',
  `is_verified` TINYINT(1) NOT NULL DEFAULT 0,
  `is_donor_profile` TINYINT(1) NOT NULL DEFAULT 0,
  `status` ENUM('active','suspended') NOT NULL DEFAULT 'active',
  `total_donations` INT UNSIGNED NOT NULL DEFAULT 0,
  `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_email` (`email`),
  UNIQUE KEY `uq_phone` (`phone`),
  KEY `idx_blood_group` (`blood_group`),
  KEY `idx_division_district` (`division`,`district`),
  KEY `idx_availability` (`availability`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
-- ---------------------------------------------------------
-- Table: blood_requests
-- ---------------------------------------------------------
DROP TABLE IF EXISTS `blood_requests`;
CREATE TABLE `blood_requests` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `request_code` VARCHAR(20) NOT NULL,
  `seeker_id` INT UNSIGNED NOT NULL,
  `blood_group` ENUM('A+','A-','B+','B-','AB+','AB-','O+','O-') NOT NULL,
  `units_needed` TINYINT UNSIGNED NOT NULL DEFAULT 1,
  `patient_name` VARCHAR(120) NOT NULL,
  `hospital_name` VARCHAR(150) NOT NULL,
  `division` VARCHAR(50) NOT NULL,
  `district` VARCHAR(50) NOT NULL,
  `area` VARCHAR(80) DEFAULT NULL,
  `required_date` DATE NOT NULL,
  `required_time` TIME DEFAULT NULL,
  `contact_number` VARCHAR(20) NOT NULL,
  `additional_info` TEXT DEFAULT NULL,
  `request_type` ENUM('normal','emergency') NOT NULL DEFAULT 'normal',
  `status` ENUM('pending','searching','donor_found','fulfilled','cancelled','expired') NOT NULL DEFAULT 'pending',
  `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_request_code` (`request_code`),
  KEY `idx_blood_group` (`blood_group`),
  KEY `idx_status` (`status`),
  KEY `idx_type` (`request_type`),
  KEY `idx_location` (`division`,`district`),
  CONSTRAINT `fk_br_seeker` FOREIGN KEY (`seeker_id`) REFERENCES `users`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ---------------------------------------------------------
-- Table: donor_requests (a request sent to a specific donor)
-- ---------------------------------------------------------
DROP TABLE IF EXISTS `donor_requests`;
CREATE TABLE `donor_requests` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `blood_request_id` INT UNSIGNED NOT NULL,
  `donor_id` INT UNSIGNED NOT NULL,
  `match_score` TINYINT UNSIGNED DEFAULT NULL,
  `status` ENUM('pending','accepted','declined','expired') NOT NULL DEFAULT 'pending',
  `responded_at` TIMESTAMP NULL DEFAULT NULL,
  `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_request_donor` (`blood_request_id`,`donor_id`),
  KEY `idx_donor` (`donor_id`),
  CONSTRAINT `fk_dr_request` FOREIGN KEY (`blood_request_id`) REFERENCES `blood_requests`(`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_dr_donor` FOREIGN KEY (`donor_id`) REFERENCES `users`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ---------------------------------------------------------
-- Table: donation_history
-- ---------------------------------------------------------
DROP TABLE IF EXISTS `donation_history`;
CREATE TABLE `donation_history` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `donor_id` INT UNSIGNED NOT NULL,
  `donation_date` DATE NOT NULL,
  `hospital_name` VARCHAR(150) NOT NULL,
  `blood_group` ENUM('A+','A-','B+','B-','AB+','AB-','O+','O-') NOT NULL,
  `units` TINYINT UNSIGNED NOT NULL DEFAULT 1,
  `notes` VARCHAR(255) DEFAULT NULL,
  `status` ENUM('completed','pending','cancelled') NOT NULL DEFAULT 'completed',
  `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_donor` (`donor_id`),
  CONSTRAINT `fk_dh_donor` FOREIGN KEY (`donor_id`) REFERENCES `users`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Table: notifications
-- ---------------------------------------------------------
DROP TABLE IF EXISTS `notifications`;
CREATE TABLE `notifications` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `user_id` INT UNSIGNED NOT NULL,
  `type` VARCHAR(40) NOT NULL,
  `title` VARCHAR(150) NOT NULL,
  `message` VARCHAR(255) NOT NULL,
  `link` VARCHAR(255) DEFAULT NULL,
  `is_read` TINYINT(1) NOT NULL DEFAULT 0,
  `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_user_unread` (`user_id`,`is_read`),
  CONSTRAINT `fk_notif_user` FOREIGN KEY (`user_id`) REFERENCES `users`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ---------------------------------------------------------
-- Table: messages
-- ---------------------------------------------------------
DROP TABLE IF EXISTS `messages`;
CREATE TABLE `messages` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `blood_request_id` INT UNSIGNED DEFAULT NULL,
  `sender_id` INT UNSIGNED NOT NULL,
  `receiver_id` INT UNSIGNED NOT NULL,
  `message` TEXT NOT NULL,
  `is_read` TINYINT(1) NOT NULL DEFAULT 0,
  `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_conversation` (`sender_id`,`receiver_id`),
  KEY `idx_request` (`blood_request_id`),
  CONSTRAINT `fk_msg_sender` FOREIGN KEY (`sender_id`) REFERENCES `users`(`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_msg_receiver` FOREIGN KEY (`receiver_id`) REFERENCES `users`(`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_msg_request` FOREIGN KEY (`blood_request_id`) REFERENCES `blood_requests`(`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ---------------------------------------------------------
-- Table: hospitals (hospitals & blood banks)
-- ---------------------------------------------------------
DROP TABLE IF EXISTS `hospitals`;
CREATE TABLE `hospitals` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `name` VARCHAR(150) NOT NULL,
  `type` ENUM('hospital','blood_bank','collection_center') NOT NULL DEFAULT 'hospital',
  `address` VARCHAR(255) NOT NULL,
  `division` VARCHAR(50) NOT NULL,
  `district` VARCHAR(50) NOT NULL,
  `phone` VARCHAR(20) DEFAULT NULL,
  `opening_hours` VARCHAR(100) DEFAULT NULL,
  `available_blood_groups` VARCHAR(100) DEFAULT NULL,
  `latitude` DECIMAL(10,7) DEFAULT NULL,
  `longitude` DECIMAL(10,7) DEFAULT NULL,
  `map_link` VARCHAR(255) DEFAULT NULL,
  `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_location` (`division`,`district`),
  KEY `idx_type` (`type`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ---------------------------------------------------------
-- Table: blood_banks (kept separate for schema completeness
-- - inventory-style records linked to a hospital/blood bank)
-- ---------------------------------------------------------
DROP TABLE IF EXISTS `blood_banks`;
CREATE TABLE `blood_banks` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `hospital_id` INT UNSIGNED NOT NULL,
  `blood_group` ENUM('A+','A-','B+','B-','AB+','AB-','O+','O-') NOT NULL,
  `units_available` INT UNSIGNED NOT NULL DEFAULT 0,
  `updated_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_hospital_bg` (`hospital_id`,`blood_group`),
  CONSTRAINT `fk_bb_hospital` FOREIGN KEY (`hospital_id`) REFERENCES `hospitals`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ---------------------------------------------------------
-- Table: donation_camps
-- ---------------------------------------------------------
DROP TABLE IF EXISTS `donation_camps`;
CREATE TABLE `donation_camps` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `camp_name` VARCHAR(150) NOT NULL,
  `organization` VARCHAR(150) DEFAULT NULL,
  `camp_date` DATE NOT NULL,
  `camp_time` VARCHAR(60) DEFAULT NULL,
  `location` VARCHAR(255) NOT NULL,
  `division` VARCHAR(50) DEFAULT NULL,
  `district` VARCHAR(50) DEFAULT NULL,
  `description` TEXT DEFAULT NULL,
  `contact` VARCHAR(50) DEFAULT NULL,
  `registration_info` VARCHAR(255) DEFAULT NULL,
  `created_by` INT UNSIGNED DEFAULT NULL,
  `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_camp_date` (`camp_date`),
  CONSTRAINT `fk_camp_admin` FOREIGN KEY (`created_by`) REFERENCES `users`(`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ---------------------------------------------------------
-- Table: reports
-- ---------------------------------------------------------
DROP TABLE IF EXISTS `reports`;
CREATE TABLE `reports` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `reporter_id` INT UNSIGNED NOT NULL,
  `reported_user_id` INT UNSIGNED DEFAULT NULL,
  `reported_request_id` INT UNSIGNED DEFAULT NULL,
  `reason` ENUM('fake_request','fake_donor','spam','harassment','incorrect_info','suspicious_activity','other') NOT NULL,
  `details` TEXT DEFAULT NULL,
  `status` ENUM('pending','reviewed','resolved','rejected') NOT NULL DEFAULT 'pending',
  `admin_notes` TEXT DEFAULT NULL,
  `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_status` (`status`),
  CONSTRAINT `fk_report_reporter` FOREIGN KEY (`reporter_id`) REFERENCES `users`(`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_report_user` FOREIGN KEY (`reported_user_id`) REFERENCES `users`(`id`) ON DELETE SET NULL,
  CONSTRAINT `fk_report_request` FOREIGN KEY (`reported_request_id`) REFERENCES `blood_requests`(`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

SET FOREIGN_KEY_CHECKS = 1;

-- =========================================================
-- SAMPLE / DEMO DATA
-- =========================================================

-- Locations (subset of divisions/districts/areas for demo)
INSERT INTO `locations` (`division`,`district`,`area`) VALUES
('Dhaka','Dhaka','Mirpur'),
('Dhaka','Dhaka','Uttara'),
('Dhaka','Dhaka','Dhanmondi'),
('Dhaka','Dhaka','Mohammadpur'),
('Dhaka','Dhaka','Gulshan'),
('Dhaka','Dhaka','Banani'),
('Dhaka','Gazipur','Tongi'),
('Dhaka','Narayanganj','Narayanganj Sadar'),
('Chattogram','Chattogram','Agrabad'),
('Chattogram','Chattogram','Panchlaish'),
('Chattogram','Cox\'s Bazar','Cox\'s Bazar Sadar'),
('Rajshahi','Rajshahi','Boalia'),
('Rajshahi','Bogura','Bogura Sadar'),
('Khulna','Khulna','Khalishpur'),
('Khulna','Jessore','Jessore Sadar'),
('Barishal','Barishal','Barishal Sadar'),
('Sylhet','Sylhet','Zindabazar'),
('Rangpur','Rangpur','Rangpur Sadar'),
('Mymensingh','Mymensingh','Mymensingh Sadar');

INSERT INTO `users`
(`full_name`,`email`,`phone`,`password_hash`,`role`,`blood_group`,`division`,`district`,`area`,`gender`,`is_verified`,`status`)
VALUES
('BloodConnect Admin','admin@bloodconnect.test','01700000000','PLACEHOLDER_RUN_SEED_PASSWORDS_PHP','admin',NULL,'Dhaka','Dhaka','Mirpur','Other',1,'active');

-- Demo Donors (password for all demo accounts: Donor@12345)
INSERT INTO `users`
(`full_name`,`email`,`phone`,`password_hash`,`role`,`blood_group`,`division`,`district`,`area`,`gender`,`date_of_birth`,`last_donation_date`,`availability`,`is_verified`,`is_donor_profile`,`total_donations`,`status`)
VALUES
('Rahim Ahmed','rahim@example.com','01711000001','PLACEHOLDER_RUN_SEED_PASSWORDS_PHP','donor','B+','Dhaka','Dhaka','Mirpur','Male','1996-04-12','2026-07-15','available',1,1,8,'active'),
('Karim Hossain','karim@example.com','01711000002','PLACEHOLDER_RUN_SEED_PASSWORDS_PHP','donor','O+','Dhaka','Dhaka','Uttara','Male','1994-01-20','2026-05-02','available',1,1,12,'active'),
('Farida Yasmin','farida@example.com','01711000003','PLACEHOLDER_RUN_SEED_PASSWORDS_PHP','donor','A-','Dhaka','Dhaka','Dhanmondi','Female','1998-09-05','2026-03-11','maybe',1,1,4,'active'),
('Nayeem Islam','nayeem@example.com','01711000004','PLACEHOLDER_RUN_SEED_PASSWORDS_PHP','donor','O-','Dhaka','Dhaka','Mohammadpur','Male','1992-11-30',NULL,'available',0,1,0,'active'),
('Sadia Akter','sadia@example.com','01711000005','PLACEHOLDER_RUN_SEED_PASSWORDS_PHP','donor','AB+','Dhaka','Dhaka','Gulshan','Female','1999-06-18','2026-08-01','unavailable',1,1,3,'active'),
('Tanvir Rahman','tanvir@example.com','01711000006','PLACEHOLDER_RUN_SEED_PASSWORDS_PHP','donor','B+','Chattogram','Chattogram','Agrabad','Male','1995-02-25','2026-06-20','available',1,1,6,'active'),
('Mitu Chowdhury','mitu@example.com','01711000007','PLACEHOLDER_RUN_SEED_PASSWORDS_PHP','donor','A+','Chattogram','Chattogram','Panchlaish','Female','1997-08-14','2026-01-09','available',0,1,2,'active'),
('Habibur Rahman','habib@example.com','01711000008','PLACEHOLDER_RUN_SEED_PASSWORDS_PHP','donor','O+','Rajshahi','Rajshahi','Boalia','Male','1993-03-03','2026-04-22','available',1,1,10,'active'),
('Runa Laila','runa@example.com','01711000009','PLACEHOLDER_RUN_SEED_PASSWORDS_PHP','donor','B-','Khulna','Khulna','Khalishpur','Female','1996-12-01',NULL,'available',1,1,1,'active'),
('Jahangir Alam','jahangir@example.com','01711000010','PLACEHOLDER_RUN_SEED_PASSWORDS_PHP','donor','AB-','Sylhet','Sylhet','Zindabazar','Male','1991-07-07','2026-02-14','maybe',1,1,15,'active');
