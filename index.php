<?php
require_once 'includes/config.php';
require_once 'includes/functions.php';

// Get available consoles
$consoles = getAvailableConsoles();

// Handle filter
$filter_type = isset($_GET['type']) ? $_GET['type'] : '';
if ($filter_type) {
    $filtered_consoles = array_filter($consoles, function($console) use ($filter_type) {
        return $console['type'] === $filter_type;
    });
    $consoles = $filtered_consoles;
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>PlayStation Rental - Sewa PlayStation Terbaik</title>
    <link rel="stylesheet" href="assets/css/style.css">
</head>
<body>
    <!-- Header -->
    <header class="header">
        <div class="container">
            <nav class="navbar">
                <a href="index.php" class="logo">🎮 PlayStation Rental</a>
                <ul class="nav-links">
                    <li><a href="index.php">Home</a></li>
                    <?php if (isLoggedIn()): ?>
                        <?php if (isAdmin()): ?>
                            <li><a href="admin/index.php">Dashboard Admin</a></li>
                        <?php else: ?>
                            <li><a href="user/index.php">Dashboard</a></li>
                            <li><a href="user/my-rentals.php">Penyewaan Saya</a></li>
                            <li><a href="user/profile.php">Profil</a></li>
                        <?php endif; ?>
                        <li><a href="logout.php">Logout (<?php echo $_SESSION['name']; ?>)</a></li>
                    <?php else: ?>
                        <li><a href="login.php">Login</a></li>
                        <li><a href="register.php">Register</a></li>
                    <?php endif; ?>
                </ul>
            </nav>
        </div>
    </header>

    <!-- Main Content -->
    <main class="main-content">
        <div class="container">
            <?php echo showAlert(); ?>
            
            <!-- Hero Section -->
            <div class="card">
                <h1 style="text-align: center; margin-bottom: 1rem; color: #2d3748; font-size: 2.5rem;">
                    Selamat Datang di PlayStation Rental
                </h1>
                <p style="text-align: center; color: #718096; font-size: 1.2rem; margin-bottom: 2rem;">
                    Sewa PlayStation favorit Anda dengan harga terjangkau dan pelayanan terbaik
                </p>
                <p style="text-align: center; color: #718096; font-size: 1rem; margin-bottom: 2rem;">
                    <strong>Alamat Perusahaan:</strong> Jl. Fiktif No.123, Jakarta, Indonesia 12345
                </p>
                <?php if (!isLoggedIn()): ?>
                    <div style="text-align: center;">
                        <a href="register.php" class="btn btn-primary" style="margin-right: 1rem;">Daftar Sekarang</a>
                        <a href="login.php" class="btn btn-secondary">Login</a>
                    </div>
                <?php endif; ?>
            </div>

            <!-- Filter Section -->
            <div class="card">
                <h2 style="margin-bottom: 1rem;">Filter PlayStation</h2>
                <div style="display: flex; gap: 1rem; flex-wrap: wrap;">
                    <a href="index.php" class="btn <?php echo $filter_type === '' ? 'btn-primary' : 'btn-secondary'; ?>">
                        Semua
                    </a>
                    <a href="index.php?type=PS5" class="btn <?php echo $filter_type === 'PS5' ? 'btn-primary' : 'btn-secondary'; ?>">
                        PlayStation 5
                    </a>
                    <a href="index.php?type=PS4" class="btn <?php echo $filter_type === 'PS4' ? 'btn-primary' : 'btn-secondary'; ?>">
                        PlayStation 4
                    </a>
                    <a href="index.php?type=PS3" class="btn <?php echo $filter_type === 'PS3' ? 'btn-primary' : 'btn-secondary'; ?>">
                        PlayStation 3
                    </a>
                    <a href="index.php?type=PS2" class="btn <?php echo $filter_type === 'PS2' ? 'btn-primary' : 'btn-secondary'; ?>">
                        PlayStation 2
                    </a>
                </div>
            </div>

            <!-- Consoles Grid -->
            <div class="console-grid">
                <?php if (empty($consoles)): ?>
                    <div class="card" style="grid-column: 1 / -1; text-align: center;">
                        <h3>Tidak ada PlayStation yang tersedia</h3>
                        <p>Silakan coba filter lain atau kembali lagi nanti.</p>
                    </div>
                <?php else: ?>
                    <?php foreach ($consoles as $console): ?>
                        <div class="console-card">
                            <div class="console-image">
                                <?php echo $console['type']; ?>
                            </div>
                            <div class="console-info">
                                <h3 class="console-name"><?php echo htmlspecialchars($console['name']); ?></h3>
                                <span class="console-type"><?php echo htmlspecialchars($console['type']); ?></span>
                                <p class="console-description"><?php echo htmlspecialchars($console['description']); ?></p>
                                <div class="console-price"><?php echo formatCurrency($console['price_per_day']); ?>/hari</div>
                                <span class="console-status status-<?php echo $console['status']; ?>">
                                    <?php 
                                    switch($console['status']) {
                                        case 'available': echo 'Tersedia'; break;
                                        case 'rented': echo 'Disewa'; break;
                                        case 'maintenance': echo 'Maintenance'; break;
                                    }
                                    ?>
                                </span>
                                <div style="margin-top: 0.5rem; font-weight: bold; color: #2f855a;">
                                    Jumlah tersedia: <?php echo $console['available_quantity'] ?? $console['quantity']; ?>
                                </div>
                                
                                <?php if ($console['status'] === 'available'): ?>
                                    <?php if (isLoggedIn() && !isAdmin()): ?>
                                        <div style="margin-top: 1rem;">
                                            <a href="user/rent.php?console_id=<?php echo $console['id']; ?>" class="btn btn-success" style="width: 100%;">
                                                Sewa Sekarang
                                            </a>
                                        </div>
                                    <?php elseif (!isLoggedIn()): ?>
                                        <div style="margin-top: 1rem;">
                                            <a href="login.php" class="btn btn-primary" style="width: 100%;">
                                                Login untuk Menyewa
                                            </a>
                                        </div>
                                    <?php endif; ?>
                                <?php endif; ?>
                            </div>
                        </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>

            <!-- Features Section -->
            <div class="card" style="margin-top: 3rem;">
                <h2 style="text-align: center; margin-bottom: 2rem;">Mengapa Memilih Kami?</h2>
                <div class="stats-grid">
                    <div class="stat-card">
                        <div class="stat-number">💯</div>
                        <div class="stat-label">Kualitas Terjamin</div>
                    </div>
                    <div class="stat-card">
                        <div class="stat-number">⚡</div>
                        <div class="stat-label">Proses Cepat</div>
                    </div>
                    <div class="stat-card">
                        <div class="stat-number">💰</div>
                        <div class="stat-label">Harga Terjangkau</div>
                    </div>
                    <div class="stat-card">
                        <div class="stat-number">🎮</div>
                        <div class="stat-label">Lengkap & Terawat</div>
                    </div>
                </div>
            </div>
        </div>
    </main>

    <!-- Footer -->
    <footer class="footer">
        <div class="container">
            <p>&copy; 2024 PlayStation Rental. All rights reserved.</p>
        </div>
    </footer>

    <script src="assets/js/script.js"></script>
</body>
</html>
