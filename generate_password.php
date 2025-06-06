<?php
// Generate password hash for admin123
$password = 'admin123';
$hash = password_hash($password, PASSWORD_DEFAULT);
echo "Password: " . $password . "<br>";
echo "Hash: " . $hash . "<br>";

// Also test verification
if (password_verify($password, $hash)) {
    echo "Verification: SUCCESS<br>";
} else {
    echo "Verification: FAILED<br>";
}
?>
