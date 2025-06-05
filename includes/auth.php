<?php
require_once 'functions.php';

// Function to register new user
function registerUser($username, $password, $name, $email, $phone, $address) {
    global $conn;
    
    // Sanitize inputs
    $username = sanitize($username);
    $name = sanitize($name);
    $email = sanitize($email);
    $phone = sanitize($phone);
    $address = sanitize($address);
    
    // Hash password
    $hashed_password = hashPassword($password);
    
    // Check if username exists
    $check_query = "SELECT id FROM users WHERE username = '$username' OR email = '$email'";
    $result = $conn->query($check_query);
    
    if ($result->num_rows > 0) {
        return ['success' => false, 'message' => 'Username or email already exists'];
    }
    
    // Insert new user
    $query = "INSERT INTO users (username, password, name, email, phone, address) 
              VALUES ('$username', '$hashed_password', '$name', '$email', '$phone', '$address')";
    
    if ($conn->query($query)) {
        return ['success' => true, 'message' => 'Registration successful'];
    }
    
    return ['success' => false, 'message' => 'Registration failed'];
}

// Function to login user
function loginUser($username, $password) {
    global $conn;
    
    // Sanitize input
    $username = sanitize($username);
    
    // Get user
    $query = "SELECT * FROM users WHERE username = '$username'";
    $result = $conn->query($query);
    
    if ($result->num_rows === 1) {
        $user = $result->fetch_assoc();
        
        // Verify password
        if (verifyPassword($password, $user['password'])) {
            // Set session
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['username'] = $user['username'];
            $_SESSION['name'] = $user['name'];
            $_SESSION['role'] = $user['role'];
            
            return ['success' => true, 'message' => 'Login successful', 'role' => $user['role']];
        }
    }
    
    return ['success' => false, 'message' => 'Invalid username or password'];
}

// Function to logout user
function logoutUser() {
    // Destroy session
    session_destroy();
    
    // Clear session variables
    $_SESSION = array();
    
    // Delete session cookie
    if (isset($_COOKIE[session_name()])) {
        setcookie(session_name(), '', time()-3600, '/');
    }
}

// Function to check if route is protected
function requireLogin() {
    if (!isLoggedIn()) {
        setAlert('danger', 'Please login to continue');
        redirect('login.php');
    }
}

// Function to check if route is admin only
function requireAdmin() {
    requireLogin();
    if (!isAdmin()) {
        setAlert('danger', 'Access denied');
        redirect('index.php');
    }
}

// Function to update user profile
function updateProfile($user_id, $name, $email, $phone, $address) {
    global $conn;
    
    // Sanitize inputs
    $user_id = sanitize($user_id);
    $name = sanitize($name);
    $email = sanitize($email);
    $phone = sanitize($phone);
    $address = sanitize($address);
    
    // Check if email exists
    $check_query = "SELECT id FROM users WHERE email = '$email' AND id != '$user_id'";
    $result = $conn->query($check_query);
    
    if ($result->num_rows > 0) {
        return ['success' => false, 'message' => 'Email already exists'];
    }
    
    // Update user
    $query = "UPDATE users SET 
              name = '$name',
              email = '$email',
              phone = '$phone',
              address = '$address'
              WHERE id = '$user_id'";
    
    if ($conn->query($query)) {
        // Update session name
        $_SESSION['name'] = $name;
        return ['success' => true, 'message' => 'Profile updated successfully'];
    }
    
    return ['success' => false, 'message' => 'Profile update failed'];
}

// Function to change password
function changePassword($user_id, $current_password, $new_password) {
    global $conn;
    
    // Get user
    $user = getUserById($user_id);
    
    // Verify current password
    if (!verifyPassword($current_password, $user['password'])) {
        return ['success' => false, 'message' => 'Current password is incorrect'];
    }
    
    // Hash new password
    $hashed_password = hashPassword($new_password);
    
    // Update password
    $query = "UPDATE users SET password = '$hashed_password' WHERE id = '$user_id'";
    
    if ($conn->query($query)) {
        return ['success' => true, 'message' => 'Password changed successfully'];
    }
    
    return ['success' => false, 'message' => 'Password change failed'];
}
?>
