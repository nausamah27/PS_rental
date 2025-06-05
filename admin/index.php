<?php
require_once '../includes/config.php';
require_once '../includes/functions.php';
require_once '../includes/auth.php';

// Require admin access
requireAdmin();

// Get dashboard statistics
$stats = getDashboardStats();

// Get recent rentals
$recent_rentals = getAllRentals();
$recent_rentals = array_slice($recent_rentals, 0, 5); // Get latest 5 rentals
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Dashboard - PlayStation Rental</title>
    <link rel="stylesheet" href="../assets/css/style.css">
</head>
<body>
    <!-- Header -->
    <header class="header">
        <div class="container">
            <nav class="navbar">
                <a href="../index.php" class="logo">🎮 PlayStation Rental Admin</a>
                <ul class="nav-links">
                    <li><a href="index.php">Dashboard</a></li>
                    <li><a href="consoles.php">PlayStation</a></li>
                    <li><a href="rentals.php">Penyewaan</a></li>
                    <li><a href="users.php">Pengguna</a></li>
                    <li><a href="reports.php">Laporan</a></li>
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
                <h1 style="margin-bottom: 1rem;">Dashboard Admin</h1>
                <p>Selamat datang di panel admin PlayStation Rental. Kelola semua aspek bisnis rental Anda dari sini.</p>
                <p><strong>Alamat Perusahaan:</strong> Jl. Fiktif No.123, Jakarta, Indonesia 12345</p>
            </div>

            <!-- Statistics Grid -->
            <div class="stats-grid">
                <div class="stat-card">
                    <div class="stat-number"><?php echo $stats['total_users']; ?></div>
                    <div class="stat-label">Total Pengguna</div>
                </div>
                <div class="stat-card">
                    <div class="stat-number"><?php echo $stats['total_consoles']; ?></div>
                    <div class="stat-label">Total PlayStation</div>
                </div>
                <div class="stat-card">
                    <div class="stat-number"><?php echo $stats['available_consoles']; ?></div>
                    <div class="stat-label">PlayStation Tersedia</div>
                </div>
                <div class="stat-card">
                    <div class="stat-number"><?php echo $stats['active_rentals']; ?></div>
                    <div class="stat-label">Penyewaan Aktif</div>
                </div>
                <div class="stat-card">
                    <div class="stat-number"><?php echo formatCurrency($stats['today_revenue']); ?></div>
                    <div class="stat-label">Pendapatan Hari Ini</div>
                </div>
            </div>

            <!-- Quick Actions -->
            <div class="card">
                <h2 style="margin-bottom: 1.5rem;">Menu Cepat</h2>
                <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 1rem;">
                    <a href="consoles.php?action=add" class="btn btn-success" style="text-align: center;">
                        ➕ Tambah PlayStation
                    </a>
                    <a href="rentals.php" class="btn btn-primary" style="text-align: center;">
                        📋 Kelola Penyewaan
                    </a>
                    <a href="users.php" class="btn btn-secondary" style="text-align: center;">
                        👥 Kelola Pengguna
                    </a>
                    <a href="reports.php" class="btn btn-warning" style="text-align: center;">
                        📊 Lihat Laporan
                    </a>
                </div>
            </div>

            <!-- Recent Rentals -->
            <div class="card">
                <h2 style="margin-bottom: 1.5rem;">Penyewaan Terbaru</h2>
                
                <?php if (empty($recent_rentals)): ?>
                    <p>Belum ada data penyewaan.</p>
                <?php else: ?>
                    <div class="table-responsive">
                        <table class="table">
                            <thead>
                                <tr>
                                    <th>ID</th>
                                    <th>Pengguna</th>
                                    <th>PlayStation</th>
                                    <th>Tanggal</th>
                                    <th>Total Harga</th>
                                    <th>Status</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($recent_rentals as $rental): ?>
                                    <tr>
                                        <td>#<?php echo $rental['id']; ?></td>
                                        <td><?php echo htmlspecialchars($rental['user_name']); ?></td>
                                        <td>
                                            <?php echo htmlspecialchars($rental['console_name']); ?>
                                            <br>
                                            <small style="color: #718096;"><?php echo $rental['type']; ?></small>
                                        </td>
                                        <td>
                                            <?php echo formatDate($rental['start_date']); ?> - 
                                            <?php echo formatDate($rental['end_date']); ?>
                                        </td>
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
                    
                    <div style="text-align: center; margin-top: 1rem;">
                        <a href="rentals.php" class="btn btn-primary">Lihat Semua Penyewaan</a>
                    </div>
                <?php endif; ?>
            </div>

            <!-- System Status -->
            <div class="card">
                <h2 style="margin-bottom: 1.5rem;">Status Sistem</h2>
                <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(250px, 1fr)); gap: 1rem;">
                    <div style="padding: 1rem; background: #c6f6d5; border-radius: 8px;">
                        <strong style="color: #22543d;">Database</strong>
                        <p style="color: #22543d; margin: 0.5rem 0 0 0;">✅ Terhubung</p>
                    </div>
                    <div style="padding: 1rem; background: #c6f6d5; border-radius: 8px;">
                        <strong style="color: #22543d;">Sistem</strong>
                        <p style="color: #22543d; margin: 0.5rem 0 0 0;">✅ Berjalan Normal</p>
                    </div>
                    <div style="padding: 1rem; background: #bee3f8; border-radius: 8px;">
                        <strong style="color: #2a4365;">Versi</strong>
                        <p style="color: #2a4365; margin: 0.5rem 0 0 0;">v1.0.0</p>
                    </div>
                </div>
            </div>
        </div>
    </main>

    <!-- Footer -->
    <footer class="footer">
        <div class="container">
            <p>&copy; 2024 PlayStation Rental Admin Panel. All rights reserved.</p>
        </div>
    </footer>
</body>
</html>
