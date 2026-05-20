<?php
/**
 * Migrate Funeral Bookings - Add New Columns
 * Run this once to add new columns to existing funeral_bookings table
 */

$conn = new mysqli('localhost', 'root', '', 'sjb_databases');

if ($conn->connect_error) {
    die('Connection failed: ' . $conn->connect_error);
}

$migrations = [
    "ALTER TABLE `funeral_bookings` ADD COLUMN `funeral_chapel` VARCHAR(50) AFTER `funeral_time`",
    "ALTER TABLE `funeral_bookings` MODIFY COLUMN `funeral_attendees` INT",
    "ALTER TABLE `funeral_bookings` ADD COLUMN `needs_funeral_mass` VARCHAR(10) AFTER `funeral_attendees`"
];

$migrated = 0;
$skipped = 0;

foreach ($migrations as $sql) {
    if (!$conn->query($sql)) {
        // Check if column already exists
        if (strpos($conn->error, 'Duplicate column name') !== false) {
            echo "⊘ Column already exists, skipping: " . $sql . "<br>";
            $skipped++;
        } else {
            echo "✗ Error: " . $conn->error . " - " . $sql . "<br>";
        }
    } else {
        echo "✓ Migrated: " . substr($sql, 0, 50) . "...<br>";
        $migrated++;
    }
}

echo "<br>Migration complete! Migrated: $migrated, Skipped: $skipped";
$conn->close();
