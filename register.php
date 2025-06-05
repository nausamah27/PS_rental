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

// Handle registration form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = $_POST['username'] ?? '';
    $password = $_POST['password'] ?? '';
    $confirm_password = $_POST['confirm_password'] ?? '';
    $name = $_POST['name'] ?? '';
    $email = $_POST['email'] ?? '';
    $phone = $_POST['phone'] ?? '';
    $address = $_POST['address'] ?? '';
    
    // Validation
    $errors = [];
    
    if (empty($username)) $errors[] = 'Username harus diisi';
    if (empty($password)) $errors[] = 'Password harus diisi';
    if (empty($confirm_password)) $errors[] = 'Konfirmasi password harus diisi';
    if (empty($name)) $errors[] = 'Nama harus diisi';
    if (empty($email)) $errors[] = 'Email harus diisi';
    if (empty($phone)) $errors[] = 'Nomor telepon harus diisi';
    if (empty($address)) $errors[] = 'Alamat harus diisi';
    
    if ($password !== $confirm_password) {
        $errors[] = 'Password dan konfirmasi password tidak sama';
    }
    
    if (strlen($password) < 6) {
        $errors[] = 'Password minimal 6 karakter';
    }
    
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errors[] = 'Format email tidak valid';
    }
    
    if (empty($errors)) {
        $result = registerUser($username, $password, $name, $email, $phone, $address);
        
        if ($result['success']) {
            setAlert('success', $result['message'] . ' Silakan login.');
            redirect('login.php');
        } else {
            setAlert('danger', $result['message']);
        }
    } else {
        setAlert('danger', implode('<br>', $errors));
    }
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Register - PlayStation Rental</title>
    <link rel="stylesheet" href="assets/css/style.css">
</head>
<body>
    <div class="auth-container">
        <div class="auth-card" style="max-width: 500px;">
            <h1 class="auth-title">🎮 Daftar Akun</h1>
            
            <?php echo showAlert(); ?>
            
            <form method="POST" action="">
                <div class="form-group">
                    <label for="username">Username</label>
                    <input type="text" id="username" name="username" class="form-control" 
                           value="<?php echo isset($_POST['username']) ? htmlspecialchars($_POST['username']) : ''; ?>" 
                           required>
                </div>
                
                <div class="form-group">
                    <label for="name">Nama Lengkap</label>
                    <input type="text" id="name" name="name" class="form-control" 
                           value="<?php echo isset($_POST['name']) ? htmlspecialchars($_POST['name']) : ''; ?>" 
                           required>
                </div>
                
                <div class="form-group">
                    <label for="email">Email</label>
                    <input type="email" id="email" name="email" class="form-control" 
                           value="<?php echo isset($_POST['email']) ? htmlspecialchars($_POST['email']) : ''; ?>" 
                           required>
                </div>
                
                <div class="form-group">
                    <label for="phone">Nomor Telepon</label>
                    <input type="tel" id="phone" name="phone" class="form-control" 
                           value="<?php echo isset($_POST['phone']) ? htmlspecialchars($_POST['phone']) : ''; ?>" 
                           required>
                </div>
                
                <div class="form-group">
                    <label for="address">Alamat</label>
                    <textarea id="address" name="address" class="form-control" rows="3" required><?php echo isset($_POST['address']) ? htmlspecialchars($_POST['address']) : ''; ?></textarea>
                </div>
                
                <div class="form-group">
                    <label for="password">Password</label>
                    <input type="password" id="password" name="password" class="form-control" required>
                    <small style="color: #718096;">Minimal 6 karakter</small>
                </div>
                
                <div class="form-group">
                    <label for="confirm_password">Konfirmasi Password</label>
                    <input type="password" id="confirm_password" name="confirm_password" class="form-control" required>
                </div>
                
                <button type="submit" class="btn btn-primary" style="width: 100%; margin-bottom: 1rem;">
                    Daftar
                </button>
            </form>
            
            <div class="auth-links">
                <p>Sudah punya akun? <a href="login.php">Login di sini</a></p>
                <p><a href="index.php">← Kembali ke Home</a></p>
            </div>
        </div>
    </div>
    
    <script>
        // Password confirmation validation
        document.getElementById('confirm_password').addEventListener('input', function() {
            const password = document.getElementById('password').value;
            const confirmPassword = this.value;
            
            if (password !== confirmPassword) {
                this.setCustomValidity('Password tidak sama');
            } else {
                this.setCustomValidity('');
            }
        });
    </script>
</body>
</html>
