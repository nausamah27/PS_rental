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

// Function to get all available consoles with adjusted quantity based on active rentals
function getAvailableConsoles() {
    global $conn;
    // Get all consoles with status 'available'
    $query = "SELECT * FROM consoles WHERE status = 'available' ORDER BY price_per_day ASC";
    $result = $conn->query($query);
    $consoles = [];
    while ($console = $result->fetch_assoc()) {
        $console_id = $console['id'];
        $total_quantity = $console['quantity'];

        // Count active rentals for this console
        $rental_query = "SELECT COUNT(*) as rented_count FROM rentals WHERE console_id = '$console_id' AND status IN ('pending', 'active')";
        $rental_result = $conn->query($rental_query);
        $rented_count = 0;
        if ($rental_result) {
            $rented_count = $rental_result->fetch_assoc()['rented_count'] ?? 0;
        }

        // Calculate available quantity
        $available_quantity = max(0, $total_quantity - $rented_count);
        $console['available_quantity'] = $available_quantity;

        $consoles[] = $console;
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

// Function to create rental with quantity
function createRentalWithQuantity($user_id, $console_id, $start_date, $end_date, $quantity) {
    global $conn;
    
    $user_id = sanitize($user_id);
    $console_id = sanitize($console_id);
    $start_date = sanitize($start_date);
    $end_date = sanitize($end_date);
    $quantity = (int)$quantity;
    
    // Calculate days and total price
    $start = new DateTime($start_date);
    $end = new DateTime($end_date);
    $days = $end->diff($start)->days + 1;
    
    // Get console price per day
    $console = getConsoleById($console_id);
    if (!$console) {
        return false;
    }
    $price_per_day = $console['price_per_day'];
    $total_price = $price_per_day * $days * $quantity;
    
    // Insert rental
    $query = "INSERT INTO rentals (user_id, console_id, start_date, end_date, total_days, total_price, quantity) 
              VALUES ('$user_id', '$console_id', '$start_date', '$end_date', '$days', '$total_price', '$quantity')";
    
    if ($conn->query($query)) {
        // Update console status if all units rented out
        // Check total quantity and rented quantity
        $total_quantity = $console['quantity'];
        $rental_query = "SELECT SUM(quantity) as rented_sum FROM rentals WHERE console_id = '$console_id' AND status IN ('pending', 'active')";
        $rental_result = $conn->query($rental_query);
        $rented_sum = 0;
        if ($rental_result) {
            $rented_sum = $rental_result->fetch_assoc()['rented_sum'] ?? 0;
        }
        if ($rented_sum >= $total_quantity) {
            $update_query = "UPDATE consoles SET status = 'rented' WHERE id = '$console_id'";
            $conn->query($update_query);
        } else {
            $update_query = "UPDATE consoles SET status = 'available' WHERE id = '$console_id'";
            $conn->query($update_query);
        }
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
    $query = "SELECT *, quantity FROM consoles ORDER BY created_at DESC";
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
    
    // Total consoles (sum of quantity)
    $query = "SELECT SUM(quantity) as total FROM consoles";
    $result = $conn->query($query);
    $stats['total_consoles'] = $result->fetch_assoc()['total'] ?? 0;
    
    // Available consoles (sum of quantity where status = 'available')
    $query = "SELECT SUM(quantity) as total FROM consoles WHERE status = 'available'";
    $result = $conn->query($query);
    $stats['available_consoles'] = $result->fetch_assoc()['total'] ?? 0;
    
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
