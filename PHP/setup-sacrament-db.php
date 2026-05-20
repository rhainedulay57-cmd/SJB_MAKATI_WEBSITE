<?php
/**
 * Database Setup for Sacrament Booking System
 * Creates tables for weddings, baptisms, and funerals
 */

$conn = new mysqli("localhost", "root", "", "sjb_databases");

if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Create Bookings table for all sacraments
$sql_bookings = "CREATE TABLE IF NOT EXISTS `sacrament_bookings` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `booking_id` VARCHAR(50) UNIQUE NOT NULL,
    `sacrament_type` ENUM('wedding', 'baptism', 'funeral') NOT NULL,
    `status` ENUM('pending', 'approved', 'declined', 'completed') DEFAULT 'pending',
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    `admin_notes` TEXT,
    INDEX(sacrament_type, status),
    INDEX(booking_id)
) DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci";

// Create Wedding Bookings table
$sql_wedding = "CREATE TABLE IF NOT EXISTS `wedding_bookings` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `booking_id` VARCHAR(50) UNIQUE NOT NULL,
    `groom_name` VARCHAR(100) NOT NULL,
    `bride_name` VARCHAR(100) NOT NULL,
    `groom_email` VARCHAR(100) NOT NULL,
    `groom_phone` VARCHAR(20) NOT NULL,
    `wedding_date` DATE NOT NULL,
    `wedding_time` TIME NOT NULL,
    `special_requests` TEXT,
    `requirements_file` TEXT,
    `deposit_proof_file` VARCHAR(255),
    `contribution_proof_file` VARCHAR(255),
    `status` ENUM('pending', 'approved', 'declined', 'completed') DEFAULT 'pending',
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    `admin_notes` TEXT,
    FOREIGN KEY (booking_id) REFERENCES sacrament_bookings(booking_id) ON DELETE CASCADE,
    INDEX(wedding_date, status),
    INDEX(status)
) DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci";

// Create Baptism Bookings table
$sql_baptism = "CREATE TABLE IF NOT EXISTS `baptism_bookings` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `booking_id` VARCHAR(50) UNIQUE NOT NULL,
    `baptism_type` ENUM('Infant Baptism', 'Adult Baptism') NOT NULL,
    `candidate_name` VARCHAR(100) NOT NULL,
    `parent1_name` VARCHAR(100) NOT NULL,
    `parent1_email` VARCHAR(100) NOT NULL,
    `parent1_phone` VARCHAR(20) NOT NULL,
    `parent2_name` VARCHAR(100),
    `parent2_email` VARCHAR(100),
    `parent2_phone` VARCHAR(20),
    `godparent_name` VARCHAR(100),
    `baptism_date` DATE NOT NULL,
    `special_requests` TEXT,
    `birth_certificate_file` VARCHAR(255),
    `confirmation_certificate_file` VARCHAR(255),
    `attendance_proof_file` VARCHAR(255),
    `donation_proof_file` VARCHAR(255),
    `status` ENUM('pending', 'approved', 'declined', 'completed') DEFAULT 'pending',
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    `admin_notes` TEXT,
    FOREIGN KEY (booking_id) REFERENCES sacrament_bookings(booking_id) ON DELETE CASCADE,
    INDEX(baptism_date, status),
    INDEX(status)
) DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci";

// Create Funeral Bookings table
$sql_funeral = "CREATE TABLE IF NOT EXISTS `funeral_bookings` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `booking_id` VARCHAR(50) UNIQUE NOT NULL,
    `deceased_name` VARCHAR(100) NOT NULL,
    `death_date` DATE NOT NULL,
    `requestor_name` VARCHAR(100) NOT NULL,
    `relationship` VARCHAR(100) NOT NULL,
    `requestor_email` VARCHAR(100) NOT NULL,
    `requestor_phone` VARCHAR(20) NOT NULL,
    `funeral_date` DATE NOT NULL,
    `funeral_time` TIME NOT NULL,
    `funeral_chapel` VARCHAR(50),
    `needs_funeral_mass` VARCHAR(10),
    `special_requests` TEXT,
    `status` ENUM('pending', 'approved', 'declined', 'completed') DEFAULT 'pending',
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    `admin_notes` TEXT,
    `is_urgent` BOOLEAN DEFAULT TRUE,
    FOREIGN KEY (booking_id) REFERENCES sacrament_bookings(booking_id) ON DELETE CASCADE,
    INDEX(funeral_date, status),
    INDEX(status, is_urgent)
) DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci";

// Create Mass & Blessing request table
$sql_mass_blessing = "CREATE TABLE IF NOT EXISTS `mass_blessing` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `service` VARCHAR(255) NOT NULL,
    `date_filled` DATE NOT NULL,
    `mass_type` VARCHAR(255) NOT NULL,
    `attendees` VARCHAR(50) NOT NULL,
    `intention` TEXT NOT NULL,
    `pref_sched` DATE NOT NULL,
    `pref_time` TIME NOT NULL,
    `alter_sched` DATE NULL,
    `alter_time` TIME NULL,
    `company_name` VARCHAR(255),
    `company_owner` VARCHAR(255),
    `address` TEXT NOT NULL,
    `contact_person` VARCHAR(255) NOT NULL,
    `department` VARCHAR(255),
    `mobile_no` VARCHAR(50) NOT NULL,
    `email` VARCHAR(100) NOT NULL,
    `status` ENUM('pending', 'approved', 'declined') NOT NULL DEFAULT 'pending',
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci";

// Execute all queries
$queries = [$sql_bookings, $sql_wedding, $sql_baptism, $sql_funeral, $sql_mass_blessing];

foreach ($queries as $sql) {
    if (!$conn->query($sql)) {
        echo "Error creating table: " . $conn->error . "<br>";
    }
}

echo "✓ Database setup complete! All tables created successfully.";
$conn->close();
?>
