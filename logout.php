<?php
require_once 'includes/config.php';
require_once 'includes/auth.php';

// Logout user
logoutUser();

// Set success message
setAlert('success', 'Anda telah berhasil logout');

// Redirect to home
redirect('index.php');
?>
