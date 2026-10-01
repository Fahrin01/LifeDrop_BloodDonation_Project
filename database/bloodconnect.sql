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
