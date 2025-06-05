<?php
require_once '../includes/config.php';
require_once '../includes/functions.php';
require_once '../includes/auth.php';

// Require admin access
requireAdmin();

// Handle user actions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['delete_user'])) {
        $user_id = $_POST['user_id'] ?? 0;
        
        // Check if user has active rentals
        $query = "SELECT COUNT(*) as count FROM rentals WHERE user_id = ? AND status IN ('pending', 'active')";
        $stmt = $conn->prepare($query);
        $stmt->bind_param("i", $user_id);
        $stmt->execute();
        $result = $stmt->get_result();
        $active_rentals = $result->fetch_assoc()['count'];
        
        if ($active_rentals > 0) {
            setAlert('danger', 'Pengguna tidak dapat dihapus karena masih memiliki penyewaan aktif');
        } else {
            $query = "DELETE FROM users WHERE id = ? AND role = 'user'";
            $stmt = $conn->prepare($query);
            $stmt->bind_param("i", $user_id);
            
            if ($stmt->execute()) {
                setAlert('success', 'Pengguna berhasil dihapus');
            } else {
                setAlert('danger', 'Gagal menghapus pengguna');
            }
        }
    }
}

// Get all users
$users = getAllUsers();

// Get user statistics
$user_stats = [];
foreach ($users as &$user) {
    // Get rental count
    $query = "SELECT COUNT(*) as total_rentals, 
                     SUM(total_price) as total_spent,
                     COUNT(CASE WHEN status = 'active' THEN 1 END) as active_rentals
              FROM rentals WHERE user_id = ?";
    $stmt = $conn->prepare($query);
    $stmt->bind_param("i", $user['id']);
    $stmt->execute();
    $result = $stmt->get_result();
    $stats = $result->fetch_assoc();
    
    $user['total_rentals'] = $stats['total_rentals'] ?? 0;
    $user['total_spent'] = $stats['total_spent'] ?? 0;
    $user['active_rentals'] = $stats['active_rentals'] ?? 0;
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Kelola Pengguna - Admin Dashboard</title>
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
                <h1>Kelola Pengguna</h1>
                <p>Kelola semua pengguna yang terdaftar di sistem PlayStation Rental.</p>
            </div>

            <!-- Users Statistics -->
            <div class="stats-grid">
                <div class="stat-card">
                    <div class="stat-number"><?php echo count($users); ?></div>
                    <div class="stat-label">Total Pengguna</div>
                </div>
                <div class="stat-card">
                    <div class="stat-number">
                        <?php
                        $active_users = count(array_filter($users, function($user) {
                            return $user['active_rentals'] > 0;
                        }));
                        echo $active_users;
                        ?>
                    </div>
                    <div class="stat-label">Pengguna Aktif</div>
                </div>
                <div class="stat-card">
                    <div class="stat-number">
                        <?php
                        $total_revenue = array_reduce($users, function($carry, $user) {
                            return $carry + $user['total_spent'];
                        }, 0);
                        echo formatCurrency($total_revenue);
                        ?>
                    </div>
                    <div class="stat-label">Total Pendapatan</div>
                </div>
                <div class="stat-card">
                    <div class="stat-number">
                        <?php
                        $new_users_today = count(array_filter($users, function($user) {
                            return date('Y-m-d', strtotime($user['created_at'])) === date('Y-m-d');
                        }));
                        echo $new_users_today;
                        ?>
                    </div>
                    <div class="stat-label">Pengguna Baru Hari Ini</div>
                </div>
            </div>

            <!-- Users Table -->
            <div class="card">
                <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 2rem;">
                    <h2>Daftar Pengguna (<?php echo count($users); ?> pengguna)</h2>
                </div>
                
                <?php if (empty($users)): ?>
                    <p>Belum ada pengguna yang terdaftar.</p>
                <?php else: ?>
                    <div class="table-responsive">
                        <table class="table">
                            <thead>
                                <tr>
                                    <th>ID</th>
                                    <th>Informasi Pengguna</th>
                                    <th>Kontak</th>
                                    <th>Statistik</th>
                                    <th>Bergabung</th>
                                    <th>Aksi</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($users as $user): ?>
                                    <tr>
                                        <td>#<?php echo $user['id']; ?></td>
                                        <td>
                                            <strong><?php echo htmlspecialchars($user['name']); ?></strong>
                                            <br>
                                            <small style="color: #718096;">@<?php echo htmlspecialchars($user['username']); ?></small>
                                            <?php if ($user['active_rentals'] > 0): ?>
                                                <br>
                                                <span class="console-status status-available" style="font-size: 0.75rem;">
                                                    <?php echo $user['active_rentals']; ?> penyewaan aktif
                                                </span>
                                            <?php endif; ?>
                                        </td>
                                        <td>
                                            <?php echo htmlspecialchars($user['email']); ?>
                                            <br>
                                            <small style="color: #718096;"><?php echo htmlspecialchars($user['phone']); ?></small>
                                            <br>
                                            <small style="color: #718096;"><?php echo htmlspecialchars(substr($user['address'], 0, 30)) . '...'; ?></small>
                                        </td>
                                        <td>
                                            <div style="font-size: 0.875rem;">
                                                <div><strong><?php echo $user['total_rentals']; ?></strong> penyewaan</div>
                                                <div style="color: #48bb78;"><strong><?php echo formatCurrency($user['total_spent']); ?></strong></div>
                                            </div>
                                        </td>
                                        <td>
                                            <?php echo date('d/m/Y', strtotime($user['created_at'])); ?>
                                            <br>
                                            <small style="color: #718096;">
                                                <?php
                                                $days_ago = floor((time() - strtotime($user['created_at'])) / (60 * 60 * 24));
                                                echo $days_ago . ' hari lalu';
                                                ?>
                                            </small>
                                        </td>
                                        <td>
                                            <div style="display: flex; flex-direction: column; gap: 0.5rem;">
                                                <a href="rentals.php?user_id=<?php echo $user['id']; ?>" class="btn btn-primary" style="padding: 0.25rem 0.5rem; font-size: 0.875rem;">
                                                    📋 Lihat Penyewaan
                                                </a>
                                                
                                                <?php if ($user['active_rentals'] == 0): ?>
                                                    <form method="POST" action="" style="display: inline;" 
                                                          onsubmit="return confirm('Apakah Anda yakin ingin menghapus pengguna ini?');">
                                                        <input type="hidden" name="user_id" value="<?php echo $user['id']; ?>">
                                                        <button type="submit" name="delete_user" class="btn btn-danger" style="padding: 0.25rem 0.5rem; font-size: 0.875rem; width: 100%;">
                                                            🗑️ Hapus
                                                        </button>
                                                    </form>
                                                <?php else: ?>
                                                    <span style="font-size: 0.75rem; color: #718096; text-align: center;">
                                                        Tidak dapat dihapus
                                                    </span>
                                                <?php endif; ?>
                                            </div>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>

                    <!-- Top Users -->
                    <div style="margin-top: 2rem; padding-top: 2rem; border-top: 1px solid #e2e8f0;">
                        <h3>Top Pengguna</h3>
                        <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(300px, 1fr)); gap: 1rem; margin-top: 1rem;">
                            <!-- Most Active User -->
                            <?php
                            $most_active = array_reduce($users, function($carry, $user) {
                                return ($user['total_rentals'] > ($carry['total_rentals'] ?? 0)) ? $user : $carry;
                            }, []);
                            ?>
                            <?php if (!empty($most_active)): ?>
                                <div class="stat-card">
                                    <div style="font-size: 1.5rem; margin-bottom: 0.5rem;">🏆</div>
                                    <div style="font-weight: bold; margin-bottom: 0.5rem;">Paling Aktif</div>
                                    <div style="color: #667eea;"><?php echo htmlspecialchars($most_active['name']); ?></div>
                                    <div style="font-size: 0.875rem; color: #718096;"><?php echo $most_active['total_rentals']; ?> penyewaan</div>
                                </div>
                            <?php endif; ?>

                            <!-- Highest Spender -->
                            <?php
                            $highest_spender = array_reduce($users, function($carry, $user) {
                                return ($user['total_spent'] > ($carry['total_spent'] ?? 0)) ? $user : $carry;
                            }, []);
                            ?>
                            <?php if (!empty($highest_spender)): ?>
                                <div class="stat-card">
                                    <div style="font-size: 1.5rem; margin-bottom: 0.5rem;">💰</div>
                                    <div style="font-weight: bold; margin-bottom: 0.5rem;">Pengeluaran Tertinggi</div>
                                    <div style="color: #48bb78;"><?php echo htmlspecialchars($highest_spender['name']); ?></div>
                                    <div style="font-size: 0.875rem; color: #718096;"><?php echo formatCurrency($highest_spender['total_spent']); ?></div>
                                </div>
                            <?php endif; ?>

                            <!-- Newest User -->
                            <?php
                            $newest_user = array_reduce($users, function($carry, $user) {
                                return (strtotime($user['created_at']) > strtotime($carry['created_at'] ?? '1970-01-01')) ? $user : $carry;
                            }, []);
                            ?>
                            <?php if (!empty($newest_user)): ?>
                                <div class="stat-card">
                                    <div style="font-size: 1.5rem; margin-bottom: 0.5rem;">⭐</div>
                                    <div style="font-weight: bold; margin-bottom: 0.5rem;">Pengguna Terbaru</div>
                                    <div style="color: #ed8936;"><?php echo htmlspecialchars($newest_user['name']); ?></div>
                                    <div style="font-size: 0.875rem; color: #718096;"><?php echo date('d/m/Y', strtotime($newest_user['created_at'])); ?></div>
                                </div>
                            <?php endif; ?>
                        </div>
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
</body>
</html>
