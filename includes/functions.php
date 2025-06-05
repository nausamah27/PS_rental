<?php
require_once 'config.php';

// Function to sanitize input
function sanitize($input) {
    global $conn;
    return mysqli_real_escape_string($conn, trim($input));
}

// Function to hash password
function hashPassword($password) {
    return password_hash($password, PASSWORD_DEFAULT);
}

// Function to verify password
function verifyPassword($password, $hash) {
    return password_verify($password, $hash);
}

// Function to get user by ID
function getUserById($id) {
    global $conn;
    $id = sanitize($id);
    $query = "SELECT * FROM users WHERE id = '$id'";
    $result = $conn->query($query);
    return $result->fetch_assoc();
}

// Function to get console by ID
function getConsoleById($id) {
    global $conn;
    $id = sanitize($id);
    $query = "SELECT * FROM consoles WHERE id = '$id'";
    $result = $conn->query($query);
    return $result->fetch_assoc();
}

// Function to get all available consoles
function getAvailableConsoles() {
    global $conn;
    $query = "SELECT * FROM consoles WHERE status = 'available' ORDER BY price_per_day ASC";
    $result = $conn->query($query);
    $consoles = [];
    while ($row = $result->fetch_assoc()) {
        $consoles[] = $row;
    }
    return $consoles;
}

// Function to get user rentals
function getUserRentals($user_id) {
    global $conn;
    $user_id = sanitize($user_id);
    $query = "SELECT r.*, c.name as console_name, c.type 
              FROM rentals r 
              JOIN consoles c ON r.console_id = c.id 
              WHERE r.user_id = '$user_id' 
              ORDER BY r.created_at DESC";
    $result = $conn->query($query);
    $rentals = [];
    while ($row = $result->fetch_assoc()) {
        $rentals[] = $row;
    }
    return $rentals;
}

// Function to calculate rental price
function calculateRentalPrice($console_id, $days) {
    global $conn;
    $console = getConsoleById($console_id);
    if ($console) {
        return $console['price_per_day'] * $days;
    }
    return 0;
}

// Function to create rental
function createRental($user_id, $console_id, $start_date, $end_date) {
    global $conn;
    
    $user_id = sanitize($user_id);
    $console_id = sanitize($console_id);
    $start_date = sanitize($start_date);
    $end_date = sanitize($end_date);
    
    // Calculate days and total price
    $start = new DateTime($start_date);
    $end = new DateTime($end_date);
    $days = $end->diff($start)->days + 1;
    $total_price = calculateRentalPrice($console_id, $days);
    
    // Insert rental
    $query = "INSERT INTO rentals (user_id, console_id, start_date, end_date, total_days, total_price) 
              VALUES ('$user_id', '$console_id', '$start_date', '$end_date', '$days', '$total_price')";
    
    if ($conn->query($query)) {
        // Update console status
        $update_query = "UPDATE consoles SET status = 'rented' WHERE id = '$console_id'";
        $conn->query($update_query);
        return true;
    }
    return false;
}

// Function to get all rentals (for admin)
function getAllRentals() {
    global $conn;
    $query = "SELECT r.*, u.name as user_name, c.name as console_name, c.type 
              FROM rentals r 
              JOIN users u ON r.user_id = u.id 
              JOIN consoles c ON r.console_id = c.id 
              ORDER BY r.created_at DESC";
    $result = $conn->query($query);
    $rentals = [];
    while ($row = $result->fetch_assoc()) {
        $rentals[] = $row;
    }
    return $rentals;
}

// Function to get all users (for admin)
function getAllUsers() {
    global $conn;
    $query = "SELECT * FROM users WHERE role = 'user' ORDER BY created_at DESC";
    $result = $conn->query($query);
    $users = [];
    while ($row = $result->fetch_assoc()) {
        $users[] = $row;
    }
    return $users;
}

// Function to get all consoles (for admin)
function getAllConsoles() {
    global $conn;
    $query = "SELECT * FROM consoles ORDER BY created_at DESC";
    $result = $conn->query($query);
    $consoles = [];
    while ($row = $result->fetch_assoc()) {
        $consoles[] = $row;
    }
    return $consoles;
}

// Function to format currency
function formatCurrency($amount) {
    return 'Rp ' . number_format($amount, 0, ',', '.');
}

// Function to format date
function formatDate($date) {
    return date('d/m/Y', strtotime($date));
}

// Function to get dashboard stats
function getDashboardStats() {
    global $conn;
    
    $stats = [];
    
    // Total users
    $query = "SELECT COUNT(*) as total FROM users WHERE role = 'user'";
    $result = $conn->query($query);
    $stats['total_users'] = $result->fetch_assoc()['total'];
    
    // Total consoles
    $query = "SELECT COUNT(*) as total FROM consoles";
    $result = $conn->query($query);
    $stats['total_consoles'] = $result->fetch_assoc()['total'];
    
    // Available consoles
    $query = "SELECT COUNT(*) as total FROM consoles WHERE status = 'available'";
    $result = $conn->query($query);
    $stats['available_consoles'] = $result->fetch_assoc()['total'];
    
    // Active rentals
    $query = "SELECT COUNT(*) as total FROM rentals WHERE status = 'active'";
    $result = $conn->query($query);
    $stats['active_rentals'] = $result->fetch_assoc()['total'];
    
    // Today's revenue
    $query = "SELECT SUM(total_price) as revenue FROM rentals WHERE DATE(created_at) = CURDATE()";
    $result = $conn->query($query);
    $stats['today_revenue'] = $result->fetch_assoc()['revenue'] ?? 0;
    
    return $stats;
}
?>
