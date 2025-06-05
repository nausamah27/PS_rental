<?php
require_once 'includes/config.php';
require_once 'includes/functions.php';

// New password
$new_password = 'admin112';
$hashed_password = hashPassword($new_password);

// Update admin password
$query = "UPDATE users SET password = '$hashed_password' WHERE username = 'admin'";

if ($conn->query($query)) {
    echo "Admin password updated successfully!<br>";
    echo "Username: admin<br>";
    echo "Password: " . $new_password . "<br>";
    echo "Hash: " . $hashed_password . "<br>";
    
    // Test verification
    if (password_verify($new_password, $hashed_password)) {
        echo "Password verification: SUCCESS<br>";
    } else {
        echo "Password verification: FAILED<br>";
    }
} else {
    echo "Error updating password: " . $conn->error;
}
?>
