# PlayStation Rental Website

Website penyewaan PlayStation dengan sistem manajemen lengkap untuk user dan admin.

## Fitur

### User Features:
- ✅ Registrasi dan login pengguna
- ✅ Browse PlayStation yang tersedia
- ✅ Sistem penyewaan dengan perhitungan harga harian
- ✅ Riwayat penyewaan
- ✅ Manajemen profil

### Admin Features:
- ✅ Dashboard admin dengan statistik
- ✅ Manajemen PlayStation (tambah, edit, hapus)
- ✅ Manajemen pengguna
- ✅ Manajemen penyewaan
- ✅ Laporan transaksi (harian, bulanan, tahunan)
- ✅ Log penyewaan


## Setup Instructions

### 1. Database Setup
1. Buat database MySQL baru
2. Import file `database/playstation_rental.sql`
3. Update konfigurasi database di `includes/config.php`

### 2. File Configuration
Update `includes/config.php` sesuai dengan setup Anda:
```php
define('DB_HOST', 'localhost');
define('DB_USER', 'root');
define('DB_PASS', '');
define('DB_NAME', 'playstation_rental');
define('BASE_URL', 'http://localhost/Uas/');
```

### 3. Web Server
1. Copy semua file ke direktori web server (htdocs/www)
2. Pastikan PHP dan MySQL berjalan
3. Akses website melalui browser

## Demo Accounts

### Admin
- **Username**: admin
- **Password**: admin112

### User
- Daftar akun baru melalui halaman registrasi

## File Structure
```
/Uas
├── assets/
│   ├── css/style.css
│   └── js/script.js
├── includes/
│   ├── config.php
│   ├── functions.php
│   └── auth.php
├── admin/
│   ├── index.php (Dashboard)
│   ├── consoles.php (Manajemen PlayStation)
│   ├── users.php (Manajemen User)
│   ├── rentals.php (Manajemen Penyewaan)
│   └── reports.php (Laporan)
├── user/
│   ├── index.php (Dashboard User)
│   ├── rent.php (Penyewaan)
│   ├── my-rentals.php (Riwayat)
│   └── profile.php (Profil)
├── database/
│   └── playstation_rental.sql
├── index.php (Homepage)
├── login.php
├── register.php
└── logout.php
```

## Database Schema

### Tables:
1. **users** - Data pengguna dan admin
2. **consoles** - Data PlayStation yang tersedia
3. **rentals** - Data transaksi penyewaan

## Features Overview

### Dashboard Admin
- Statistik real-time
- Quick actions
- Recent rentals
- System status

### Console Management
- Add/Edit/Delete PlayStation
- Manage availability status
- Price management

### Rental Management
- View all rentals
- Update rental status
- Cancel rentals

### Reports
- Daily/Monthly/Yearly reports
- Revenue tracking
- Rental logs

### User Dashboard
- Browse available consoles
- Rent PlayStation
- View rental history
- Manage profile


## Support
Untuk bantuan teknis atau pertanyaan, silakan hubungi administrator sistem.

---
© 2024 PlayStation Rental System. All rights reserved.
