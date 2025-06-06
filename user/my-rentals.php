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

// Get user's rentals
$rentals = getUserRentals($_SESSION['user_id']);

// Handle filter
$status_filter = isset($_GET['status']) ? $_GET['status'] : '';
if ($status_filter) {
    $rentals = array_filter($rentals, function($rental) use ($status_filter) {
        return $rental['status'] === $status_filter;
    });
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Penyewaan Saya - PlayStation Rental</title>
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

            <!-- Page Title -->
            <div class="card">
                <h1>Riwayat Penyewaan</h1>
                <p>Lihat semua riwayat penyewaan PlayStation Anda.</p>
            </div>

            <!-- Filter Section -->
            <div class="card">
                <h2 style="margin-bottom: 1rem;">Filter Status</h2>
                <div style="display: flex; gap: 1rem; flex-wrap: wrap;">
                    <a href="my-rentals.php" class="btn <?php echo $status_filter === '' ? 'btn-primary' : 'btn-secondary'; ?>">
                        Semua
                    </a>
                    <a href="my-rentals.php?status=pending" class="btn <?php echo $status_filter === 'pending' ? 'btn-primary' : 'btn-secondary'; ?>">
                        Menunggu
                    </a>
                    <a href="my-rentals.php?status=active" class="btn <?php echo $status_filter === 'active' ? 'btn-primary' : 'btn-secondary'; ?>">
                        Aktif
                    </a>
                    <a href="my-rentals.php?status=completed" class="btn <?php echo $status_filter === 'completed' ? 'btn-primary' : 'btn-secondary'; ?>">
                        Selesai
                    </a>
                    <a href="my-rentals.php?status=cancelled" class="btn <?php echo $status_filter === 'cancelled' ? 'btn-primary' : 'btn-secondary'; ?>">
                        Dibatalkan
                    </a>
                </div>
            </div>

            <!-- Rentals Table -->
            <div class="card">
                <?php if (empty($rentals)): ?>
                    <div style="text-align: center;">
                        <h3>Tidak ada data penyewaan</h3>
                        <p>Anda belum memiliki riwayat penyewaan PlayStation.</p>
                        <a href="../index.php" class="btn btn-primary" style="margin-top: 1rem;">Sewa PlayStation</a>
                    </div>
                <?php else: ?>
                    <div class="table-responsive">
                        <table class="table">
                            <thead>
                                <tr>
                                    <th>ID</th>
                                    <th>PlayStation</th>
                                    <th>Tanggal Mulai</th>
                                    <th>Tanggal Selesai</th>
                                    <th>Total Hari</th>
                                    <th>Total Harga</th>
                                    <th>Status</th>
                                    <th>Tanggal Dibuat</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php $counter = 1; ?>
                                <?php foreach ($rentals as $rental): ?>
                                    <tr>
                                        <td>#<?php echo $counter++; ?></td>
                                        <td>
                                            <?php echo htmlspecialchars($rental['console_name']); ?>
                                            <br>
                                            <small style="color: #718096;"><?php echo $rental['type']; ?></small>
                                        </td>
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
                                        <td><?php echo date('d/m/Y H:i', strtotime($rental['created_at'])); ?></td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>

                    <!-- Summary -->
                    <div style="margin-top: 2rem; padding-top: 2rem; border-top: 1px solid #e2e8f0;">
                        <h3>Ringkasan</h3>
                        <div class="stats-grid" style="margin-top: 1rem;">
                            <div class="stat-card">
                                <div class="stat-number"><?php echo count($rentals); ?></div>
                                <div class="stat-label">Total Penyewaan</div>
                            </div>
                            <div class="stat-card">
                                <div class="stat-number">
                                    <?php
                                    $total_days = array_reduce($rentals, function($carry, $rental) {
                                        return $carry + $rental['total_days'];
                                    }, 0);
                                    echo $total_days;
                                    ?>
                                </div>
                                <div class="stat-label">Total Hari</div>
                            </div>
                            <div class="stat-card">
                                <div class="stat-number">
                                    <?php
                                    $total_spent = array_reduce($rentals, function($carry, $rental) {
                                        return $carry + $rental['total_price'];
                                    }, 0);
                                    echo formatCurrency($total_spent);
                                    ?>
                                </div>
                                <div class="stat-label">Total Pembayaran</div>
                            </div>
                        </div>
                    </div>
                <?php endif; ?>
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
