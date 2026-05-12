<?php

// URL Dasar Aplikasi (Otomatis mendeteksi localhost atau production)
// URL Dasar Aplikasi (Otomatis mendeteksi localhost atau production)
$protocol = (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on') || (isset($_SERVER['HTTP_X_FORWARDED_PROTO']) && $_SERVER['HTTP_X_FORWARDED_PROTO'] === 'https') ? "https" : "http";

$host = $_SERVER['HTTP_HOST'] ?? 'localhost:8000';
$script_name = $_SERVER['SCRIPT_NAME'] ?? '';
$base_dir = str_replace('\\', '/', dirname($script_name));
if ($base_dir === '/' || $base_dir === '.') $base_dir = '';
define('BASEURL', $protocol . "://" . $host . $base_dir);


// Path Absolut Aplikasi
define('APPROOT', dirname(dirname(__FILE__)));

// Konfigurasi Database (Produksi Shared Hosting)
define('DB_HOST', 'localhost');
define('DB_USER', 'bumdesad_simpleakv36');
define('DB_PASS', '5@8@12Yaa');
define('DB_NAME', 'bumdesad_simpleakv36');
