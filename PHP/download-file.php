<?php
/**
 * Secure File Download Handler
 * Validates file path before serving to prevent unauthorized access
 */

session_start();

// Verify admin access
if (!isset($_SESSION['user_id']) || !isset($_SESSION['is_admin']) || $_SESSION['is_admin'] != 1) {
    http_response_code(403);
    die("Unauthorized access");
}

$file_path = $_GET['file'] ?? null;

if (!$file_path) {
    http_response_code(400);
    die("File path not provided");
}

// Validate file path - prevent directory traversal
$file_path = str_replace('..', '', $file_path);
$file_path = str_replace('\\', '/', $file_path);

// Ensure file is in UPLOADS directory
if (strpos($file_path, 'UPLOADS/') !== 0) {
    http_response_code(403);
    die("Invalid file path");
}

// Build full path
$full_path = dirname(__DIR__) . '/' . $file_path;

// Check if file exists
if (!file_exists($full_path) || !is_file($full_path)) {
    http_response_code(404);
    die("File not found");
}

// Get file info
$file_name = basename($full_path);
$file_size = filesize($full_path);
$file_type = mime_content_type($full_path);

// Set appropriate headers
header('Content-Type: ' . ($file_type ?: 'application/octet-stream'));
header('Content-Disposition: attachment; filename="' . $file_name . '"');
header('Content-Length: ' . $file_size);
header('Cache-Control: no-cache, no-store, must-revalidate');
header('Pragma: no-cache');
header('Expires: 0');

// Stream file
readfile($full_path);
exit();
?>
