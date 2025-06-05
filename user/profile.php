<?php
require_once '../includes/config.php';
require_once '../includes/functions.php';
require_once '../includes/auth.php';

// Require user login
requireLogin();

// Redirect admin to admin dashboard
if (isAdmin()) {
    redirect('admin/index.php');
}

// Get user data
$user = getUserById($_SESSION['user_id']);

// Handle profile update
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_profile'])) {
    $name = $_POST['name'] ?? '';
    $email = $_POST['email'] ?? '';
    $phone = $_POST['phone'] ?? '';
    $address = $_POST['address'] ?? '';
    
    // Validation
    $errors = [];
    
    if (empty($name)) $errors[] = 'Nama harus diisi';
    if (empty($email)) $errors[] = 'Email harus diisi';
    if (empty($phone)) $errors[] = 'Nomor telepon harus diisi';
    if (empty($address)) $errors[] = 'Alamat harus diisi';
    
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errors[] = 'Format email tidak valid';
    }
    
    if (empty($errors)) {
        $result = updateProfile($_SESSION['user_id'], $name, $email, $phone, $address);
        
        if ($result['success']) {
            setAlert('success', $result['message']);
            redirect('profile.php');
        } else {
            setAlert('danger', $result['message']);
        }
    } else {
        setAlert('danger', implode('<br>', $errors));
    }
}

// Handle password change
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['change_password'])) {
    $current_password = $_POST['current_password'] ?? '';
    $new_password = $_POST['new_password'] ?? '';
    $confirm_password = $_POST['confirm_password'] ?? '';
    
    // Validation
    $errors = [];
    
    if (empty($current_password)) $errors[] = 'Password saat ini harus diisi';
    if (empty($new_password)) $errors[] = 'Password baru harus diisi';
    if (empty($confirm_password)) $errors[] = 'Konfirmasi password harus diisi';
    
    if ($new_password !== $confirm_password) {
        $errors[] = 'Password baru dan konfirmasi password tidak sama';
    }
    
    if (strlen($new_password) < 6) {
        $errors[] = 'Password minimal 6 karakter';
    }
    
    if (empty($errors)) {
        $result = changePassword($_SESSION['user_id'], $current_password, $new_password);
        
        if ($result['success']) {
            setAlert('success', $result['message']);
            redirect('profile.php');
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
    <title>Profil Saya - PlayStation Rental</title>
    <link rel="stylesheet" href="../assets/css/style.css">
</head>
<body>
    <!-- Header -->
    <header class="header">
        <div class="container">
            <nav class="navbar">
                <a href="../index.php" class="logo">🎮 PlayStation Rental</a>
                <ul class="nav-links">
                    <li><a href="../index.php">Home</a></li>
                    <li><a href="index.php">Dashboard</a></li>
                    <li><a href="my-rentals.php">Penyewaan Saya</a></li>
                    <li><a href="profile.php">Profil</a></li>
                    <li><a href="../logout.php">Logout (<?php echo $_SESSION['name']; ?>)</a></li>
                </ul>
            </nav>
        </div>
    </header>

    <!-- Main Content -->
    <main class="main-content">
        <div class="container">
            <?php echo showAlert(); ?>

            <!-- Profile Section -->
            <div class="card">
                <h1 style="margin-bottom: 2rem;">Profil Saya</h1>
                
                <form method="POST" action="">
                    <input type="hidden" name="update_profile" value="1">
                    
                    <div class="form-group">
                        <label for="username">Username</label>
                        <input type="text" id="username" class="form-control" value="<?php echo htmlspecialchars($user['username']); ?>" disabled>
                        <small style="color: #718096;">Username tidak dapat diubah</small>
                    </div>
                    
                    <div class="form-group">
                        <label for="name">Nama Lengkap</label>
                        <input type="text" id="name" name="name" class="form-control" value="<?php echo htmlspecialchars($user['name']); ?>" required>
                    </div>
                    
                    <div class="form-group">
                        <label for="email">Email</label>
                        <input type="email" id="email" name="email" class="form-control" value="<?php echo htmlspecialchars($user['email']); ?>" required>
                    </div>
                    
                    <div class="form-group">
                        <label for="phone">Nomor Telepon</label>
                        <input type="tel" id="phone" name="phone" class="form-control" value="<?php echo htmlspecialchars($user['phone']); ?>" required>
                    </div>
                    
                    <div class="form-group">
                        <label for="address">Alamat</label>
                        <textarea id="address" name="address" class="form-control" rows="3" required><?php echo htmlspecialchars($user['address']); ?></textarea>
                    </div>
                    
                    <button type="submit" class="btn btn-primary">Update Profil</button>
                </form>
            </div>

            <!-- Change Password Section -->
            <div class="card">
                <h2 style="margin-bottom: 2rem;">Ganti Password</h2>
                
                <form method="POST" action="">
                    <input type="hidden" name="change_password" value="1">
                    
                    <div class="form-group">
                        <label for="current_password">Password Saat Ini</label>
                        <input type="password" id="current_password" name="current_password" class="form-control" required>
                    </div>
                    
                    <div class="form-group">
                        <label for="new_password">Password Baru</label>
                        <input type="password" id="new_password" name="new_password" class="form-control" required>
                        <small style="color: #718096;">Minimal 6 karakter</small>
                    </div>
                    
                    <div class="form-group">
                        <label for="confirm_password">Konfirmasi Password Baru</label>
                        <input type="password" id="confirm_password" name="confirm_password" class="form-control" required>
                    </div>
                    
                    <button type="submit" class="btn btn-warning">Ganti Password</button>
                </form>
            </div>

            <!-- Account Info -->
            <div class="card">
                <h2 style="margin-bottom: 1rem;">Informasi Akun</h2>
                <p><strong>Tanggal Bergabung:</strong> <?php echo date('d/m/Y H:i', strtotime($user['created_at'])); ?></p>
                <p><strong>Status:</strong> <span class="console-status status-available">Aktif</span></p>
            </div>
        </div>
    </main>

    <!-- Footer -->
    <footer class="footer">
        <div class="container">
            <p>&copy; 2024 PlayStation Rental. All rights reserved.</p>
        </div>
    </footer>

    <script>
        // Password confirmation validation
        document.getElementById('confirm_password').addEventListener('input', function() {
            const newPassword = document.getElementById('new_password').value;
            const confirmPassword = this.value;
            
            if (newPassword !== confirmPassword) {
                this.setCustomValidity('Password tidak sama');
            } else {
                this.setCustomValidity('');
            }
        });
    </script>
</body>
</html>
