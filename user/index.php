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

// Get user's active rentals
$user_rentals = getUserRentals($_SESSION['user_id']);
$active_rentals = array_filter($user_rentals, function($rental) {
    return $rental['status'] === 'active';
});
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard - PlayStation Rental</title>
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

            <!-- Welcome Card -->
            <div class="card">
                <h1 style="margin-bottom: 1rem;">Selamat Datang, <?php echo htmlspecialchars($_SESSION['name']); ?>!</h1>
                <p>Selamat datang di dashboard PlayStation Rental. Di sini Anda dapat mengelola penyewaan dan melihat riwayat transaksi Anda.</p>
                <p><strong>Alamat Perusahaan:</strong> Jl. Fiktif No.123, Jakarta, Indonesia 12345</p>
            </div>

            <!-- Stats Grid -->
            <div class="stats-grid">
                <div class="stat-card">
                    <div class="stat-number"><?php echo count($active_rentals); ?></div>
                    <div class="stat-label">Penyewaan Aktif</div>
                </div>
                <div class="stat-card">
                    <div class="stat-number"><?php echo count($user_rentals); ?></div>
                    <div class="stat-label">Total Penyewaan</div>
                </div>
                <div class="stat-card">
                    <div class="stat-number">
                        <?php
                        $total_spent = array_reduce($user_rentals, function($carry, $rental) {
                            return $carry + $rental['total_price'];
                        }, 0);
                        echo formatCurrency($total_spent);
                        ?>
                    </div>
                    <div class="stat-label">Total Pembayaran</div>
                </div>
            </div>

            <!-- Active Rentals -->
            <div class="card">
                <h2 style="margin-bottom: 1.5rem;">Penyewaan Aktif</h2>
                
                <?php if (empty($active_rentals)): ?>
                    <p>Tidak ada penyewaan aktif saat ini.</p>
                    <a href="../index.php" class="btn btn-primary" style="margin-top: 1rem;">Sewa PlayStation</a>
                <?php else: ?>
                    <div class="table-responsive">
                        <table class="table">
                            <thead>
                                <tr>
                                    <th>PlayStation</th>
                                    <th>Tanggal Mulai</th>
                                    <th>Tanggal Selesai</th>
                                    <th>Total Hari</th>
                                    <th>Total Harga</th>
                                    <th>Status</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($active_rentals as $rental): ?>
                                    <tr>
                                        <td><?php echo htmlspecialchars($rental['console_name']); ?></td>
                                        <td><?php echo formatDate($rental['start_date']); ?></td>
                                        <td><?php echo formatDate($rental['end_date']); ?></td>
                                        <td><?php echo $rental['total_days']; ?> hari</td>
                                        <td><?php echo formatCurrency($rental['total_price']); ?></td>
                                        <td>
                                            <span class="console-status status-<?php echo $rental['status']; ?>">
                                                <?php
                                                switch($rental['status']) {
                                                    case 'pending': echo 'Menunggu'; break;
                                                    case 'active': echo 'Aktif'; break;
                                                    case 'completed': echo 'Selesai'; break;
                                                    case 'cancelled': echo 'Dibatalkan'; break;
                                                }
                                                ?>
                                            </span>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                <?php endif; ?>
            </div>

            <!-- Quick Actions -->
            <div class="card">
                <h2 style="margin-bottom: 1.5rem;">Menu Cepat</h2>
                <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 1rem;">
                    <a href="../index.php" class="btn btn-primary" style="text-align: center;">
                        Sewa PlayStation
                    </a>
                    <a href="my-rentals.php" class="btn btn-secondary" style="text-align: center;">
                        Lihat Semua Penyewaan
                    </a>
                    <a href="profile.php" class="btn btn-secondary" style="text-align: center;">
                        Update Profil
                    </a>
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
</body>
</html>
