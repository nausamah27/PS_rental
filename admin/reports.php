<?php
require_once '../includes/config.php';
require_once '../includes/functions.php';
require_once '../includes/auth.php';

// Require admin access
requireAdmin();

// Get report type and date range
$report_type = isset($_GET['type']) ? $_GET['type'] : 'daily';
$date_from = isset($_GET['date_from']) ? $_GET['date_from'] : date('Y-m-d');
$date_to = isset($_GET['date_to']) ? $_GET['date_to'] : date('Y-m-d');

// Function to get rental reports
function getRentalReports($conn, $type, $date_from, $date_to) {
    $reports = [];
    
    switch ($type) {
        case 'daily':
            $query = "SELECT 
                        DATE(created_at) as period,
                        COUNT(*) as total_rentals,
                        SUM(total_price) as total_revenue,
                        COUNT(CASE WHEN status = 'completed' THEN 1 END) as completed_rentals,
                        COUNT(CASE WHEN status = 'active' THEN 1 END) as active_rentals,
                        COUNT(CASE WHEN status = 'cancelled' THEN 1 END) as cancelled_rentals
                      FROM rentals 
                      WHERE DATE(created_at) BETWEEN ? AND ?
                      GROUP BY DATE(created_at)
                      ORDER BY DATE(created_at) DESC";
            break;
            
        case 'monthly':
            $query = "SELECT 
                        DATE_FORMAT(created_at, '%Y-%m') as period,
                        COUNT(*) as total_rentals,
                        SUM(total_price) as total_revenue,
                        COUNT(CASE WHEN status = 'completed' THEN 1 END) as completed_rentals,
                        COUNT(CASE WHEN status = 'active' THEN 1 END) as active_rentals,
                        COUNT(CASE WHEN status = 'cancelled' THEN 1 END) as cancelled_rentals
                      FROM rentals 
                      WHERE DATE(created_at) BETWEEN ? AND ?
                      GROUP BY DATE_FORMAT(created_at, '%Y-%m')
                      ORDER BY DATE_FORMAT(created_at, '%Y-%m') DESC";
            break;
            
        case 'yearly':
            $query = "SELECT 
                        YEAR(created_at) as period,
                        COUNT(*) as total_rentals,
                        SUM(total_price) as total_revenue,
                        COUNT(CASE WHEN status = 'completed' THEN 1 END) as completed_rentals,
                        COUNT(CASE WHEN status = 'active' THEN 1 END) as active_rentals,
                        COUNT(CASE WHEN status = 'cancelled' THEN 1 END) as cancelled_rentals
                      FROM rentals 
                      WHERE DATE(created_at) BETWEEN ? AND ?
                      GROUP BY YEAR(created_at)
                      ORDER BY YEAR(created_at) DESC";
            break;
    }
    
    $stmt = $conn->prepare($query);
    $stmt->bind_param("ss", $date_from, $date_to);
    $stmt->execute();
    $result = $stmt->get_result();
    
    while ($row = $result->fetch_assoc()) {
        $reports[] = $row;
    }
    
    return $reports;
}

// Function to get console popularity
function getConsolePopularity($conn, $date_from, $date_to) {
    $query = "SELECT 
                c.name,
                c.type,
                COUNT(r.id) as rental_count,
                SUM(r.total_price) as total_revenue
              FROM consoles c
              LEFT JOIN rentals r ON c.id = r.console_id 
                AND DATE(r.created_at) BETWEEN ? AND ?
              GROUP BY c.id, c.name, c.type
              ORDER BY rental_count DESC";
    
    $stmt = $conn->prepare($query);
    $stmt->bind_param("ss", $date_from, $date_to);
    $stmt->execute();
    $result = $stmt->get_result();
    
    $popularity = [];
    while ($row = $result->fetch_assoc()) {
        $popularity[] = $row;
    }
    
    return $popularity;
}

// Function to get user activity
function getUserActivity($conn, $date_from, $date_to) {
    $query = "SELECT 
                u.name,
                u.email,
                COUNT(r.id) as rental_count,
                SUM(r.total_price) as total_spent,
                MAX(r.created_at) as last_rental
              FROM users u
              LEFT JOIN rentals r ON u.id = r.user_id 
                AND DATE(r.created_at) BETWEEN ? AND ?
              WHERE u.role = 'user'
              GROUP BY u.id, u.name, u.email
              HAVING rental_count > 0
              ORDER BY rental_count DESC
              LIMIT 10";
    
    $stmt = $conn->prepare($query);
    $stmt->bind_param("ss", $date_from, $date_to);
    $stmt->execute();
    $result = $stmt->get_result();
    
    $activity = [];
    while ($row = $result->fetch_assoc()) {
        $activity[] = $row;
    }
    
    return $activity;
}

// Get reports data
$reports = getRentalReports($conn, $report_type, $date_from, $date_to);
$console_popularity = getConsolePopularity($conn, $date_from, $date_to);
$user_activity = getUserActivity($conn, $date_from, $date_to);

// Calculate totals
$total_rentals = array_sum(array_column($reports, 'total_rentals'));
$total_revenue = array_sum(array_column($reports, 'total_revenue'));
$total_completed = array_sum(array_column($reports, 'completed_rentals'));
$total_active = array_sum(array_column($reports, 'active_rentals'));
$total_cancelled = array_sum(array_column($reports, 'cancelled_rentals'));
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Laporan - Admin Dashboard</title>
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

            <!-- Page Title -->
            <div class="card">
                <h1>Laporan Transaksi dan Penyewaan</h1>
                <p>Analisis lengkap transaksi dan aktivitas penyewaan PlayStation.</p>
            </div>

            <!-- Filter Section -->
            <div class="card">
                <h2 style="margin-bottom: 1rem;">Filter Laporan</h2>
                <form method="GET" action="">
                    <div style="display: grid; grid-template-columns: auto 1fr 1fr auto; gap: 1rem; align-items: end;">
                        <div class="form-group" style="margin-bottom: 0;">
                            <label for="type">Tipe Laporan</label>
                            <select id="type" name="type" class="form-control">
                                <option value="daily" <?php echo $report_type === 'daily' ? 'selected' : ''; ?>>Harian</option>
                                <option value="monthly" <?php echo $report_type === 'monthly' ? 'selected' : ''; ?>>Bulanan</option>
                                <option value="yearly" <?php echo $report_type === 'yearly' ? 'selected' : ''; ?>>Tahunan</option>
                            </select>
                        </div>
                        
                        <div class="form-group" style="margin-bottom: 0;">
                            <label for="date_from">Dari Tanggal</label>
                            <input type="date" id="date_from" name="date_from" class="form-control" value="<?php echo $date_from; ?>">
                        </div>
                        
                        <div class="form-group" style="margin-bottom: 0;">
                            <label for="date_to">Sampai Tanggal</label>
                            <input type="date" id="date_to" name="date_to" class="form-control" value="<?php echo $date_to; ?>">
                        </div>
                        
                        <button type="submit" class="btn btn-primary">Generate Laporan</button>
                    </div>
                </form>
            </div>

            <!-- Summary Statistics -->
            <div class="stats-grid">
                <div class="stat-card">
                    <div class="stat-number"><?php echo $total_rentals; ?></div>
                    <div class="stat-label">Total Penyewaan</div>
                </div>
                <div class="stat-card">
                    <div class="stat-number"><?php echo formatCurrency($total_revenue); ?></div>
                    <div class="stat-label">Total Pendapatan</div>
                </div>
                <div class="stat-card">
                    <div class="stat-number"><?php echo $total_completed; ?></div>
                    <div class="stat-label">Penyewaan Selesai</div>
                </div>
                <div class="stat-card">
                    <div class="stat-number"><?php echo $total_active; ?></div>
                    <div class="stat-label">Penyewaan Aktif</div>
                </div>
                <div class="stat-card">
                    <div class="stat-number"><?php echo $total_cancelled; ?></div>
                    <div class="stat-label">Penyewaan Dibatalkan</div>
                </div>
            </div>

            <!-- Reports Table -->
            <div class="card">
                <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 2rem;">
                    <h2>Laporan <?php echo ucfirst($report_type); ?></h2>
                    <button onclick="window.print()" class="btn btn-secondary">🖨️ Print Laporan</button>
                </div>
                
                <?php if (empty($reports)): ?>
                    <p>Tidak ada data untuk periode yang dipilih.</p>
                <?php else: ?>
                    <div class="table-responsive">
                        <table class="table">
                            <thead>
                                <tr>
                                    <th>Periode</th>
                                    <th>Total Penyewaan</th>
                                    <th>Pendapatan</th>
                                    <th>Selesai</th>
                                    <th>Aktif</th>
                                    <th>Dibatalkan</th>
                                    <th>Success Rate</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($reports as $report): ?>
                                    <tr>
                                        <td>
                                            <?php 
                                            if ($report_type === 'daily') {
                                                echo date('d/m/Y', strtotime($report['period']));
                                            } elseif ($report_type === 'monthly') {
                                                echo date('F Y', strtotime($report['period'] . '-01'));
                                            } else {
                                                echo $report['period'];
                                            }
                                            ?>
                                        </td>
                                        <td><?php echo $report['total_rentals']; ?></td>
                                        <td><?php echo formatCurrency($report['total_revenue']); ?></td>
                                        <td><?php echo $report['completed_rentals']; ?></td>
                                        <td><?php echo $report['active_rentals']; ?></td>
                                        <td><?php echo $report['cancelled_rentals']; ?></td>
                                        <td>
                                            <?php 
                                            $success_rate = $report['total_rentals'] > 0 ? 
                                                round(($report['completed_rentals'] / $report['total_rentals']) * 100, 1) : 0;
                                            echo $success_rate . '%';
                                            ?>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                <?php endif; ?>
            </div>

            <!-- Console Popularity -->
            <div class="card">
                <h2 style="margin-bottom: 1.5rem;">Popularitas PlayStation</h2>
                
                <?php if (empty($console_popularity)): ?>
                    <p>Tidak ada data popularitas untuk periode yang dipilih.</p>
                <?php else: ?>
                    <div class="table-responsive">
                        <table class="table">
                            <thead>
                                <tr>
                                    <th>Ranking</th>
                                    <th>PlayStation</th>
                                    <th>Tipe</th>
                                    <th>Jumlah Penyewaan</th>
                                    <th>Total Pendapatan</th>
                                    <th>Rata-rata per Penyewaan</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($console_popularity as $index => $console): ?>
                                    <tr>
                                        <td>
                                            <?php 
                                            $rank = $index + 1;
                                            if ($rank === 1) echo '🥇';
                                            elseif ($rank === 2) echo '🥈';
                                            elseif ($rank === 3) echo '🥉';
                                            else echo '#' . $rank;
                                            ?>
                                        </td>
                                        <td><?php echo htmlspecialchars($console['name']); ?></td>
                                        <td><?php echo $console['type']; ?></td>
                                        <td><?php echo $console['rental_count']; ?></td>
                                        <td><?php echo formatCurrency($console['total_revenue']); ?></td>
                                        <td>
                                            <?php 
                                            $avg = $console['rental_count'] > 0 ? 
                                                $console['total_revenue'] / $console['rental_count'] : 0;
                                            echo formatCurrency($avg);
                                            ?>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                <?php endif; ?>
            </div>

            <!-- User Activity -->
            <div class="card">
                <h2 style="margin-bottom: 1.5rem;">Top 10 Pengguna Aktif</h2>
                
                <?php if (empty($user_activity)): ?>
                    <p>Tidak ada aktivitas pengguna untuk periode yang dipilih.</p>
                <?php else: ?>
                    <div class="table-responsive">
                        <table class="table">
                            <thead>
                                <tr>
                                    <th>Ranking</th>
                                    <th>Nama Pengguna</th>
                                    <th>Email</th>
                                    <th>Jumlah Penyewaan</th>
                                    <th>Total Pengeluaran</th>
                                    <th>Penyewaan Terakhir</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($user_activity as $index => $user): ?>
                                    <tr>
                                        <td>
                                            <?php 
                                            $rank = $index + 1;
                                            if ($rank === 1) echo '🥇';
                                            elseif ($rank === 2) echo '🥈';
                                            elseif ($rank === 3) echo '🥉';
                                            else echo '#' . $rank;
                                            ?>
                                        </td>
                                        <td><?php echo htmlspecialchars($user['name']); ?></td>
                                        <td><?php echo htmlspecialchars($user['email']); ?></td>
                                        <td><?php echo $user['rental_count']; ?></td>
                                        <td><?php echo formatCurrency($user['total_spent']); ?></td>
                                        <td><?php echo date('d/m/Y H:i', strtotime($user['last_rental'])); ?></td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </main>

    <!-- Footer -->
    <footer class="footer">
        <div class="container">
            <p>&copy; 2024 PlayStation Rental Admin Panel. All rights reserved.</p>
        </div>
    </footer>

    <style>
        @media print {
            .header, .footer, .btn, form {
                display: none !important;
            }
            
            .card {
                box-shadow: none !important;
                border: 1px solid #ddd !important;
                margin-bottom: 1rem !important;
            }
            
            body {
                background: white !important;
            }
        }
    </style>
</body>
</html>
