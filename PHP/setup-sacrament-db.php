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
)";

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
    `guest_count` INT NOT NULL,
    `special_requests` TEXT,
    `status` ENUM('pending', 'approved', 'declined', 'completed') DEFAULT 'pending',
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    `admin_notes` TEXT,
    FOREIGN KEY (booking_id) REFERENCES sacrament_bookings(booking_id),
    INDEX(wedding_date, status),
    INDEX(status)
)";

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
    `godparent_name` VARCHAR(100) NOT NULL,
    `baptism_date` DATE NOT NULL,
    `special_requests` TEXT,
    `status` ENUM('pending', 'approved', 'declined', 'completed') DEFAULT 'pending',
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    `admin_notes` TEXT,
    FOREIGN KEY (booking_id) REFERENCES sacrament_bookings(booking_id),
    INDEX(baptism_date, status),
    INDEX(status)
)";

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
    `funeral_attendees` INT NOT NULL,
    `special_requests` TEXT,
    `status` ENUM('pending', 'approved', 'declined', 'completed') DEFAULT 'pending',
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    `admin_notes` TEXT,
    `is_urgent` BOOLEAN DEFAULT TRUE,
    FOREIGN KEY (booking_id) REFERENCES sacrament_bookings(booking_id),
    INDEX(funeral_date, status),
    INDEX(status, is_urgent)
)";

// Execute all queries
$queries = [$sql_bookings, $sql_wedding, $sql_baptism, $sql_funeral];

foreach ($queries as $sql) {
    if (!$conn->query($sql)) {
        echo "Error creating table: " . $conn->error . "<br>";
    }
}

echo "✓ Database setup complete! All tables created successfully.";
$conn->close();
?>
