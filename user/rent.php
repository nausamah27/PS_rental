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

// Get console ID from URL
$console_id = isset($_GET['console_id']) ? (int)$_GET['console_id'] : 0;

if (!$console_id) {
    setAlert('danger', 'PlayStation tidak ditemukan');
    redirect('index.php');
}

// Get console details
$console = getConsoleById($console_id);

if (!$console) {
    setAlert('danger', 'PlayStation tidak ditemukan');
    redirect('index.php');
}

if ($console['status'] !== 'available') {
    setAlert('danger', 'PlayStation tidak tersedia untuk disewa');
    redirect('index.php');
}

// Handle rental form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $start_date = $_POST['start_date'] ?? '';
    $end_date = $_POST['end_date'] ?? '';
    
    // Validation
    $errors = [];
    
    if (empty($start_date)) $errors[] = 'Tanggal mulai harus diisi';
    if (empty($end_date)) $errors[] = 'Tanggal selesai harus diisi';
    
    if (!empty($start_date) && !empty($end_date)) {
        $start = new DateTime($start_date);
        $end = new DateTime($end_date);
        $today = new DateTime();
        
        if ($start < $today) {
            $errors[] = 'Tanggal mulai tidak boleh kurang dari hari ini';
        }
        
        if ($end <= $start) {
            $errors[] = 'Tanggal selesai harus lebih dari tanggal mulai';
        }
        
        $days = $end->diff($start)->days + 1;
        if ($days > 30) {
            $errors[] = 'Maksimal penyewaan adalah 30 hari';
        }
    }
    
    if (empty($errors)) {
        $result = createRental($_SESSION['user_id'], $console_id, $start_date, $end_date);
        
        if ($result) {
            setAlert('success', 'Penyewaan berhasil dibuat! PlayStation akan segera disiapkan.');
            redirect('user/my-rentals.php');
        } else {
            setAlert('danger', 'Gagal membuat penyewaan. Silakan coba lagi.');
        }
    } else {
        setAlert('danger', implode('<br>', $errors));
    }
}

// Calculate price for preview
$preview_price = 0;
$preview_days = 0;
if (isset($_POST['start_date']) && isset($_POST['end_date'])) {
    $start_date = $_POST['start_date'];
    $end_date = $_POST['end_date'];
    
    if (!empty($start_date) && !empty($end_date)) {
        $start = new DateTime($start_date);
        $end = new DateTime($end_date);
        
        if ($end > $start) {
            $preview_days = $end->diff($start)->days + 1;
            $preview_price = $console['price_per_day'] * $preview_days;
        }
    }
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Sewa PlayStation - PlayStation Rental</title>
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

            <!-- Console Details -->
            <div class="card">
                <div style="display: grid; grid-template-columns: 1fr 2fr; gap: 2rem; align-items: start;">
                    <div class="console-image" style="height: 300px; border-radius: 10px;">
                        <?php echo $console['type']; ?>
                    </div>
                    
                    <div>
                        <h1 style="margin-bottom: 1rem;"><?php echo htmlspecialchars($console['name']); ?></h1>
                        <span class="console-type"><?php echo htmlspecialchars($console['type']); ?></span>
                        <p style="margin: 1rem 0; color: #718096; line-height: 1.6;">
                            <?php echo htmlspecialchars($console['description']); ?>
                        </p>
                        <div style="font-size: 1.5rem; font-weight: bold; color: #48bb78; margin-bottom: 1rem;">
                            <?php echo formatCurrency($console['price_per_day']); ?>/hari
                        </div>
                        <span class="console-status status-<?php echo $console['status']; ?>">
                            <?php 
                            switch($console['status']) {
                                case 'available': echo 'Tersedia'; break;
                                case 'rented': echo 'Disewa'; break;
                                case 'maintenance': echo 'Maintenance'; break;
                            }
                            ?>
                        </span>
                    </div>
                </div>
            </div>

            <!-- Rental Form -->
            <div class="card">
                <h2 style="margin-bottom: 2rem;">Form Penyewaan</h2>
                
                <form method="POST" action="" id="rentalForm">
                    <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 2rem;">
                        <div class="form-group">
                            <label for="start_date">Tanggal Mulai</label>
                            <input type="date" id="start_date" name="start_date" class="form-control" 
                                   min="<?php echo date('Y-m-d'); ?>"
                                   value="<?php echo isset($_POST['start_date']) ? $_POST['start_date'] : date('Y-m-d'); ?>" 
                                   required>
                        </div>
                        
                        <div class="form-group">
                            <label for="end_date">Tanggal Selesai</label>
                            <input type="date" id="end_date" name="end_date" class="form-control" 
                                   min="<?php echo date('Y-m-d', strtotime('+1 day')); ?>"
                                   value="<?php echo isset($_POST['end_date']) ? $_POST['end_date'] : ''; ?>" 
                                   required>
                        </div>
                    </div>
                    
                    <!-- Price Preview -->
                    <div id="pricePreview" style="margin: 2rem 0; padding: 1.5rem; background: #f7fafc; border-radius: 10px; display: none;">
                        <h3 style="margin-bottom: 1rem;">Ringkasan Penyewaan</h3>
                        <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 1rem;">
                            <div>
                                <strong>PlayStation:</strong> <?php echo htmlspecialchars($console['name']); ?>
                            </div>
                            <div>
                                <strong>Harga per hari:</strong> <?php echo formatCurrency($console['price_per_day']); ?>
                            </div>
                            <div>
                                <strong>Total hari:</strong> <span id="totalDays">0</span> hari
                            </div>
                            <div>
                                <strong>Total harga:</strong> <span id="totalPrice">Rp 0</span>
                            </div>
                        </div>
                    </div>
                    
                    <div style="display: flex; gap: 1rem;">
                        <button type="submit" class="btn btn-success">Konfirmasi Penyewaan</button>
                        <a href="../index.php" class="btn btn-secondary">Batal</a>
                    </div>
                </form>
            </div>

            <!-- Terms and Conditions -->
            <div class="card">
                <h2 style="margin-bottom: 1rem;">Syarat dan Ketentuan</h2>
                <ul style="color: #718096; line-height: 1.6;">
                    <li>PlayStation harus dikembalikan dalam kondisi baik</li>
                    <li>Kerusakan yang disebabkan oleh kelalaian penyewa akan dikenakan biaya tambahan</li>
                    <li>Pembayaran dilakukan di muka sebelum PlayStation diserahkan</li>
                    <li>Keterlambatan pengembalian akan dikenakan denda</li>
                    <li>PlayStation sudah termasuk 2 controller dan kabel lengkap</li>
                    <li>Maksimal penyewaan adalah 30 hari</li>
                </ul>
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
        const startDateInput = document.getElementById('start_date');
        const endDateInput = document.getElementById('end_date');
        const pricePreview = document.getElementById('pricePreview');
        const totalDaysSpan = document.getElementById('totalDays');
        const totalPriceSpan = document.getElementById('totalPrice');
        const pricePerDay = <?php echo $console['price_per_day']; ?>;

        function updatePrice() {
            const startDate = new Date(startDateInput.value);
            const endDate = new Date(endDateInput.value);
            
            if (startDate && endDate && endDate > startDate) {
                const timeDiff = endDate.getTime() - startDate.getTime();
                const daysDiff = Math.ceil(timeDiff / (1000 * 3600 * 24)) + 1;
                const totalPrice = daysDiff * pricePerDay;
                
                totalDaysSpan.textContent = daysDiff;
                totalPriceSpan.textContent = 'Rp ' + totalPrice.toLocaleString('id-ID');
                pricePreview.style.display = 'block';
                
                // Update end date minimum
                const minEndDate = new Date(startDate);
                minEndDate.setDate(minEndDate.getDate() + 1);
                endDateInput.min = minEndDate.toISOString().split('T')[0];
            } else {
                pricePreview.style.display = 'none';
            }
        }

        startDateInput.addEventListener('change', updatePrice);
        endDateInput.addEventListener('change', updatePrice);

        // Initial calculation if dates are set
        if (startDateInput.value && endDateInput.value) {
            updatePrice();
        }

        // Update start date minimum to today
        startDateInput.addEventListener('change', function() {
            const selectedDate = new Date(this.value);
            const minEndDate = new Date(selectedDate);
            minEndDate.setDate(minEndDate.getDate() + 1);
            endDateInput.min = minEndDate.toISOString().split('T')[0];
            
            if (endDateInput.value && new Date(endDateInput.value) <= selectedDate) {
                endDateInput.value = '';
            }
        });
    </script>
</body>
</html>
