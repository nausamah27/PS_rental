<?php
session_start();

// Database configuration
define('DB_HOST', 'localhost');
define('DB_USER', 'root');
define('DB_PASS', '');
define('DB_NAME', 'playstation_rental');

// Create database connection
$conn = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);

// Check connection
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Set charset to UTF-8
$conn->set_charset("utf8");

// Base URL - Update this according to your setup
define('BASE_URL', 'http://localhost/Uas/');

// Function to check if user is logged in
function isLoggedIn() {
    return isset($_SESSION['user_id']);
}

// Function to check if user is admin
function isAdmin() {
    return isset($_SESSION['role']) && $_SESSION['role'] === 'admin';
}

// Function to redirect
function redirect($path) {
    header("Location: " . BASE_URL . $path);
    exit();
}

// Function to show alert messages
function setAlert($type, $message) {
    $_SESSION['alert'] = [
        'type' => $type,
        'message' => $message
    ];
}

// Function to display alert messages
function showAlert() {
    if (isset($_SESSION['alert'])) {
        $alert = $_SESSION['alert'];
        $type = $alert['type'];
        $message = $alert['message'];
        unset($_SESSION['alert']);
        return "<div class='alert alert-{$type}'>{$message}</div>";
    }
    return '';
}
?>
