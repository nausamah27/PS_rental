<?php
require_once 'includes/config.php';
require_once 'includes/auth.php';

// Redirect if already logged in
if (isLoggedIn()) {
    if (isAdmin()) {
        redirect('admin/index.php');
    } else {
        redirect('user/index.php');
    }
}

// Handle login form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = $_POST['username'] ?? '';
    $password = $_POST['password'] ?? '';
    
    if (empty($username) || empty($password)) {
        setAlert('danger', 'Username dan password harus diisi');
    } else {
        $result = loginUser($username, $password);
        
        if ($result['success']) {
            setAlert('success', $result['message']);
            
            // Redirect based on role
            if ($result['role'] === 'admin') {
                redirect('admin/index.php');
            } else {
                redirect('user/index.php');
            }
        } else {
            setAlert('danger', $result['message']);
        }
    }
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - PlayStation Rental</title>
    <link rel="stylesheet" href="assets/css/style.css">
</head>
<body>
    <div class="auth-container">
        <div class="auth-card">
            <h1 class="auth-title">🎮 Login</h1>
            
            <?php echo showAlert(); ?>
            
            <form method="POST" action="">
                <div class="form-group">
                    <label for="username">Username</label>
                    <input type="text" id="username" name="username" class="form-control" 
                           value="<?php echo isset($_POST['username']) ? htmlspecialchars($_POST['username']) : ''; ?>" 
                           required>
                </div>
                
                <div class="form-group">
                    <label for="password">Password</label>
                    <input type="password" id="password" name="password" class="form-control" required>
                </div>
                
                <button type="submit" class="btn btn-primary" style="width: 100%; margin-bottom: 1rem;">
                    Login
                </button>
            </form>
            
            <div class="auth-links">
                <p>Belum punya akun? <a href="register.php">Daftar di sini</a></p>
                <p><a href="index.php">← Kembali ke Home</a></p>
            </div>
            
            <!-- Demo Accounts Info -->
            <div style="margin-top: 2rem; padding: 1rem; background: #f7fafc; border-radius: 8px; font-size: 0.875rem;">
                <strong>Demo Accounts:</strong><br>
                <strong>Admin:</strong> username: admin, password: admin123<br>
                <strong>User:</strong> Daftar akun baru atau gunakan akun yang sudah dibuat
            </div>
        </div>
    </div>
</body>
</html>
