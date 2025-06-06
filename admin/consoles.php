<?php
require_once '../includes/config.php';
require_once '../includes/functions.php';
require_once '../includes/auth.php';

// Require admin access
requireAdmin();

// Get action from URL
$action = isset($_GET['action']) ? $_GET['action'] : 'list';
$console_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

// Handle console actions
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        if (isset($_POST['add_console'])) {
            $name = $_POST['name'] ?? '';
            $type = $_POST['type'] ?? '';
            $description = $_POST['description'] ?? '';
            $price_per_day = $_POST['price_per_day'] ?? '';
            $quantity = $_POST['quantity'] ?? '';
            
            // Validation
            $errors = [];
            
            if (empty($name)) $errors[] = 'Nama PlayStation harus diisi';
            if (empty($type)) $errors[] = 'Tipe PlayStation harus diisi';
            if (empty($description)) $errors[] = 'Deskripsi harus diisi';
            if (empty($price_per_day)) $errors[] = 'Harga per hari harus diisi';
            if (empty($quantity)) $errors[] = 'Jumlah harus diisi';
            
            if (empty($errors)) {
                $query = "INSERT INTO consoles (name, type, description, price_per_day, quantity) 
                         VALUES (?, ?, ?, ?, ?)";
                $stmt = $conn->prepare($query);
                $stmt->bind_param("sssdi", $name, $type, $description, $price_per_day, $quantity);
                
                    if ($stmt->execute()) {
                        setAlert('success', 'PlayStation berhasil ditambahkan');
                        redirect('admin/consoles.php');
                    } else {
                        setAlert('danger', 'Gagal menambahkan PlayStation');
                    }
            } else {
                setAlert('danger', implode('<br>', $errors));
            }
        }
        
        elseif (isset($_POST['edit_console'])) {
            $console_id = $_POST['console_id'] ?? 0;
            $name = $_POST['name'] ?? '';
            $type = $_POST['type'] ?? '';
            $description = $_POST['description'] ?? '';
            $price_per_day = $_POST['price_per_day'] ?? '';
            $status = $_POST['status'] ?? '';
            $quantity = $_POST['quantity'] ?? '';
            
            // Validation
            $errors = [];
            
            if (empty($name)) $errors[] = 'Nama PlayStation harus diisi';
            if (empty($type)) $errors[] = 'Tipe PlayStation harus diisi';
            if (empty($description)) $errors[] = 'Deskripsi harus diisi';
            if (empty($price_per_day)) $errors[] = 'Harga per hari harus diisi';
            if (empty($status)) $errors[] = 'Status harus diisi';
            if (empty($quantity)) $errors[] = 'Jumlah harus diisi';
            
            if (empty($errors)) {
                $query = "UPDATE consoles 
                         SET name = ?, type = ?, description = ?, price_per_day = ?, status = ?, quantity = ? 
                         WHERE id = ?";
                $stmt = $conn->prepare($query);
                $stmt->bind_param("sssdsis", $name, $type, $description, $price_per_day, $status, $quantity, $console_id);
                
                    if ($stmt->execute()) {
                        setAlert('success', 'PlayStation berhasil diupdate');
                        redirect('admin/consoles.php');
                    } else {
                        setAlert('danger', 'Gagal mengupdate PlayStation');
                    }
            } else {
                setAlert('danger', implode('<br>', $errors));
            }
        }
    
    elseif (isset($_POST['delete_console'])) {
        $console_id = $_POST['console_id'] ?? 0;
        
        // Check if console has active rentals
        $query = "SELECT COUNT(*) as count FROM rentals WHERE console_id = ? AND status IN ('pending', 'active')";
        $stmt = $conn->prepare($query);
        $stmt->bind_param("i", $console_id);
        $stmt->execute();
        $result = $stmt->get_result();
        $active_rentals = $result->fetch_assoc()['count'];
        
        if ($active_rentals > 0) {
            setAlert('danger', 'PlayStation tidak dapat dihapus karena masih ada penyewaan aktif');
        } else {
            $query = "DELETE FROM consoles WHERE id = ?";
            $stmt = $conn->prepare($query);
            $stmt->bind_param("i", $console_id);
            
                if ($stmt->execute()) {
                    setAlert('success', 'PlayStation berhasil dihapus');
                    redirect('admin/consoles.php');
                } else {
                    setAlert('danger', 'Gagal menghapus PlayStation');
                }
        }
    }
}

// Get console data for editing
$console = null;
if ($action === 'edit' && $console_id) {
    $console = getConsoleById($console_id);
    if (!$console) {
        setAlert('danger', 'PlayStation tidak ditemukan');
        redirect('admin/consoles.php');
    }
}

// Get all consoles for listing
$consoles = getAllConsoles();
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Kelola PlayStation - Admin Dashboard</title>
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

            <?php if ($action === 'add' || $action === 'edit'): ?>
                <!-- Add/Edit Console Form -->
                <div class="card">
                    <h1><?php echo $action === 'add' ? 'Tambah PlayStation' : 'Edit PlayStation'; ?></h1>
                    
                    <form method="POST" action="">
                        <?php if ($action === 'edit'): ?>
                            <input type="hidden" name="console_id" value="<?php echo $console['id']; ?>">
                        <?php endif; ?>
                        
                        <div class="form-group">
                            <label for="name">Nama PlayStation</label>
                            <input type="text" id="name" name="name" class="form-control" 
                                   value="<?php echo $action === 'edit' ? htmlspecialchars($console['name']) : ''; ?>" 
                                   required>
                        </div>
                        
                        <div class="form-group">
                            <label for="type">Tipe</label>
                            <select id="type" name="type" class="form-control" required>
                                <option value="">Pilih Tipe</option>
                                <option value="PS5" <?php echo ($action === 'edit' && $console['type'] === 'PS5') ? 'selected' : ''; ?>>PlayStation 5</option>
                                <option value="PS4" <?php echo ($action === 'edit' && $console['type'] === 'PS4') ? 'selected' : ''; ?>>PlayStation 4</option>
                                <option value="PS3" <?php echo ($action === 'edit' && $console['type'] === 'PS3') ? 'selected' : ''; ?>>PlayStation 3</option>
                                <option value="PS2" <?php echo ($action === 'edit' && $console['type'] === 'PS2') ? 'selected' : ''; ?>>PlayStation 2</option>
                            </select>
                        </div>
                        
                        <div class="form-group">
                            <label for="description">Deskripsi</label>
                            <textarea id="description" name="description" class="form-control" rows="4" required><?php echo $action === 'edit' ? htmlspecialchars($console['description']) : ''; ?></textarea>
                        </div>
                        
                        <div class="form-group">
                            <label for="price_per_day">Harga per Hari</label>
                            <input type="number" id="price_per_day" name="price_per_day" class="form-control" 
                                   value="<?php echo $action === 'edit' ? $console['price_per_day'] : ''; ?>" 
                                   min="0" step="1000" required>
                        </div>

                        <div class="form-group">
                            <label for="quantity">Jumlah</label>
                            <input type="number" id="quantity" name="quantity" class="form-control"
                                   value="<?php echo $action === 'edit' ? $console['quantity'] : ''; ?>"
                                   min="1" step="1" required>
                        </div>
                        
                        <?php if ($action === 'edit'): ?>
                            <div class="form-group">
                                <label for="status">Status</label>
                                <select id="status" name="status" class="form-control" required>
                                    <option value="available" <?php echo $console['status'] === 'available' ? 'selected' : ''; ?>>Tersedia</option>
                                    <option value="rented" <?php echo $console['status'] === 'rented' ? 'selected' : ''; ?>>Disewa</option>
                                    <option value="maintenance" <?php echo $console['status'] === 'maintenance' ? 'selected' : ''; ?>>Maintenance</option>
                                </select>
                            </div>
                            <div class="form-group">
                                <label for="quantity">Jumlah</label>
                                <input type="number" id="quantity" name="quantity" class="form-control"
                                       value="<?php echo $action === 'edit' ? $console['quantity'] : ''; ?>"
                                       min="1" step="1" required>
                            </div>
                        <?php endif; ?>
                        
                        <div style="display: flex; gap: 1rem;">
                            <button type="submit" name="<?php echo $action === 'add' ? 'add_console' : 'edit_console'; ?>" class="btn btn-primary">
                                <?php echo $action === 'add' ? 'Tambah PlayStation' : 'Update PlayStation'; ?>
                            </button>
                            <a href="consoles.php" class="btn btn-secondary">Batal</a>
                        </div>
                    </form>
                </div>
            <?php else: ?>
                <!-- Console List -->
                <div class="card">
                    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 2rem;">
                        <h1>Kelola PlayStation</h1>
                        <a href="?action=add" class="btn btn-success">➕ Tambah PlayStation</a>
                    </div>
                    
                    <?php if (empty($consoles)): ?>
                        <p>Belum ada data PlayStation.</p>
                    <?php else: ?>
                        <div class="table-responsive">
                            <table class="table">
                                <thead>
                                    <tr>
                                <th>ID</th>
                                <th>Nama</th>
                                <th>Tipe</th>
                                <th>Harga/Hari</th>
                                <th>Jumlah</th>
                                <th>Status</th>
                                <th>Aksi</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php $counter = 1; ?>
                            <?php foreach ($consoles as $console): ?>
                                <tr>
                                    <td>#<?php echo $counter++; ?></td>
                                    <td>
                                        <?php echo htmlspecialchars($console['name']); ?>
                                        <br>
                                        <small style="color: #718096;"><?php echo substr($console['description'], 0, 50) . '...'; ?></small>
                                    </td>
                                    <td><?php echo $console['type']; ?></td>
                                    <td><?php echo formatCurrency($console['price_per_day']); ?></td>
                                    <td><?php echo $console['quantity']; ?></td>
                                    <td>
                                        <span class="console-status status-<?php echo $console['status']; ?>">
                                            <?php
                                            switch($console['status']) {
                                                case 'available': echo 'Tersedia'; break;
                                                case 'rented': echo 'Disewa'; break;
                                                case 'maintenance': echo 'Maintenance'; break;
                                            }
                                            ?>
                                        </span>
                                    </td>
                                    <td>
                                        <div style="display: flex; gap: 0.5rem;">
                                            <a href="?action=edit&id=<?php echo $console['id']; ?>" class="btn btn-warning" style="padding: 0.25rem 0.5rem;">
                                                ✏️ Edit
                                            </a>
                                            <form method="POST" action="" style="display: inline;" 
                                                  onsubmit="return confirm('Apakah Anda yakin ingin menghapus PlayStation ini?');">
                                                <input type="hidden" name="console_id" value="<?php echo $console['id']; ?>">
                                                <button type="submit" name="delete_console" class="btn btn-danger" style="padding: 0.25rem 0.5rem;">
                                                    🗑️ Hapus
                                                </button>
                                            </form>
                                        </div>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                            </table>
                        </div>
                    <?php endif; ?>
                </div>
            <?php endif; ?>
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
