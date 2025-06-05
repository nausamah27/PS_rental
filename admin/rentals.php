<?php
require_once '../includes/config.php';
require_once '../includes/functions.php';
require_once '../includes/auth.php';

// Require admin access
requireAdmin();

// Handle rental status updates
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['update_status'])) {
        $rental_id = $_POST['rental_id'] ?? 0;
        $status = $_POST['status'] ?? '';
        
        if ($rental_id && $status) {
            $query = "UPDATE rentals SET status = ? WHERE id = ?";
            $stmt = $conn->prepare($query);
            $stmt->bind_param("si", $status, $rental_id);
            
            if ($stmt->execute()) {
                // If status is completed or cancelled, make console available again and refund if cancelled
                if ($status === 'completed' || $status === 'cancelled') {
                    $rental_query = "SELECT console_id FROM rentals WHERE id = ?";
                    $rental_stmt = $conn->prepare($rental_query);
                    $rental_stmt->bind_param("i", $rental_id);
                    $rental_stmt->execute();
                    $rental_result = $rental_stmt->get_result();
                    $rental_data = $rental_result->fetch_assoc();
                    
                    if ($rental_data) {
                        $console_query = "UPDATE consoles SET status = 'available' WHERE id = ?";
                        $console_stmt = $conn->prepare($console_query);
                        $console_stmt->bind_param("i", $rental_data['console_id']);
                        $console_stmt->execute();
                    }
                    
                    if ($status === 'cancelled') {
                        // Refund: set total_price to 0
                        $refund_query = "UPDATE rentals SET total_price = 0 WHERE id = ?";
                        $refund_stmt = $conn->prepare($refund_query);
                        $refund_stmt->bind_param("i", $rental_id);
                        $refund_stmt->execute();
                    }
                }
                
                setAlert('success', 'Status penyewaan berhasil diupdate');
            } else {
                setAlert('danger', 'Gagal mengupdate status penyewaan');
            }
        }
    }
}

// Get filter parameters
$status_filter = isset($_GET['status']) ? $_GET['status'] : '';
$date_filter = isset($_GET['date']) ? $_GET['date'] : '';

// Get all rentals with filters
$query = "SELECT r.*, u.name as user_name, u.phone, c.name as console_name, c.type 
          FROM rentals r 
          JOIN users u ON r.user_id = u.id 
          JOIN consoles c ON r.console_id = c.id";

$conditions = [];
$params = [];
$types = "";

if ($status_filter) {
    $conditions[] = "r.status = ?";
    $params[] = $status_filter;
    $types .= "s";
}

if ($date_filter) {
    $conditions[] = "DATE(r.created_at) = ?";
    $params[] = $date_filter;
    $types .= "s";
}

if (!empty($conditions)) {
    $query .= " WHERE " . implode(" AND ", $conditions);
}

$query .= " ORDER BY r.created_at DESC";

$stmt = $conn->prepare($query);
if (!empty($params)) {
    $stmt->bind_param($types, ...$params);
}
$stmt->execute();
$result = $stmt->get_result();

$rentals = [];
while ($row = $result->fetch_assoc()) {
    $rentals[] = $row;
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Kelola Penyewaan - Admin Dashboard</title>
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
                <h1>Kelola Penyewaan</h1>
                <p>Kelola semua penyewaan PlayStation dan update status penyewaan.</p>
            </div>

            <!-- Filter Section -->
            <div class="card">
                <h2 style="margin-bottom: 1rem;">Filter Penyewaan</h2>
                <form method="GET" action="">
                    <div style="display: grid; grid-template-columns: 1fr 1fr auto; gap: 1rem; align-items: end;">
                        <div class="form-group" style="margin-bottom: 0;">
                            <label for="status">Status</label>
                            <select id="status" name="status" class="form-control">
                                <option value="">Semua Status</option>
                                <option value="pending" <?php echo $status_filter === 'pending' ? 'selected' : ''; ?>>Menunggu</option>
                                <option value="active" <?php echo $status_filter === 'active' ? 'selected' : ''; ?>>Aktif</option>
                                <option value="completed" <?php echo $status_filter === 'completed' ? 'selected' : ''; ?>>Selesai</option>
                                <option value="cancelled" <?php echo $status_filter === 'cancelled' ? 'selected' : ''; ?>>Dibatalkan</option>
                            </select>
                        </div>
                        
                        <div class="form-group" style="margin-bottom: 0;">
                            <label for="date">Tanggal</label>
                            <input type="date" id="date" name="date" class="form-control" value="<?php echo $date_filter; ?>">
                        </div>
                        
                        <div style="display: flex; gap: 0.5rem;">
                            <button type="submit" class="btn btn-primary">Filter</button>
                            <a href="rentals.php" class="btn btn-secondary">Reset</a>
                        </div>
                    </div>
                </form>
            </div>

            <!-- Rentals Table -->
            <div class="card">
                <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 2rem;">
                    <h2>Daftar Penyewaan (<?php echo count($rentals); ?> data)</h2>
                </div>
                
                <?php if (empty($rentals)): ?>
                    <p>Tidak ada data penyewaan yang ditemukan.</p>
                <?php else: ?>
                    <div class="table-responsive">
                        <table class="table">
                            <thead>
                                <tr>
                                    <th>ID</th>
                                    <th>Pengguna</th>
                                    <th>PlayStation</th>
                                    <th>Periode</th>
                                    <th>Total</th>
                                    <th>Status</th>
                                    <th>Aksi</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($rentals as $rental): ?>
                                    <tr>
                                        <td>#<?php echo $rental['id']; ?></td>
                                        <td>
                                            <?php echo htmlspecialchars($rental['user_name']); ?>
                                            <br>
                                            <small style="color: #718096;"><?php echo $rental['phone']; ?></small>
                                        </td>
                                        <td>
                                            <?php echo htmlspecialchars($rental['console_name']); ?>
                                            <br>
                                            <small style="color: #718096;"><?php echo $rental['type']; ?></small>
                                        </td>
                                        <td>
                                            <?php echo formatDate($rental['start_date']); ?> - 
                                            <?php echo formatDate($rental['end_date']); ?>
                                            <br>
                                            <small style="color: #718096;"><?php echo $rental['total_days']; ?> hari</small>
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
                                        <td>
                                            <?php if ($rental['status'] !== 'completed' && $rental['status'] !== 'cancelled'): ?>
                                                <form method="POST" action="" style="display: inline;">
                                                    <input type="hidden" name="rental_id" value="<?php echo $rental['id']; ?>">
                                                    <select name="status" class="form-control" style="width: auto; display: inline-block; margin-right: 0.5rem;" onchange="this.form.submit()">
                                                        <option value="">Ubah Status</option>
                                                        <?php if ($rental['status'] === 'pending'): ?>
                                                            <option value="active">Aktifkan</option>
                                                            <option value="cancelled">Batalkan</option>
                                                        <?php elseif ($rental['status'] === 'active'): ?>
                                                            <option value="completed">Selesaikan</option>
                                                            <option value="cancelled">Batalkan</option>
                                                        <?php endif; ?>
                                                    </select>
                                                    <input type="hidden" name="update_status" value="1">
                                                </form>
                                            <?php else: ?>
                                                <span style="color: #718096;">-</span>
                                            <?php endif; ?>
                                        </td>
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
                                    $total_revenue = array_reduce($rentals, function($carry, $rental) {
                                        return $carry + $rental['total_price'];
                                    }, 0);
                                    echo formatCurrency($total_revenue);
                                    ?>
                                </div>
                                <div class="stat-label">Total Pendapatan</div>
                            </div>
                            <div class="stat-card">
                                <div class="stat-number">
                                    <?php
                                    $active_count = count(array_filter($rentals, function($rental) {
                                        return $rental['status'] === 'active';
                                    }));
                                    echo $active_count;
                                    ?>
                                </div>
                                <div class="stat-label">Penyewaan Aktif</div>
                            </div>
                            <div class="stat-card">
                                <div class="stat-number">
                                    <?php
                                    $completed_count = count(array_filter($rentals, function($rental) {
                                        return $rental['status'] === 'completed';
                                    }));
                                    echo $completed_count;
                                    ?>
                                </div>
                                <div class="stat-label">Penyewaan Selesai</div>
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
            <p>&copy; 2024 PlayStation Rental Admin Panel. All rights reserved.</p>
        </div>
    </footer>
</body>
</html>
