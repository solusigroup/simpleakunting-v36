<?php
// Aktifkan pelaporan error selama masa pengembangan
ini_set('display_errors', 1);
error_reporting(E_ALL);

// 1. **MEMUAT AUTOLOADER COMPOSER**
require_once __DIR__ . '/../vendor/autoload.php';

// 2. Memuat file inisialisasi aplikasi kita
require_once '../app/init.php';

// Mulai session (dilakukan SETELAH app/init.php -> config.php dimuat)
// Agar setting session_name & cookie params di config.php terbaca sebelum session_start
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// 3. Menjalankan aplikasi
$app = new App();
