<?php

// URL Dasar Aplikasi
$host = $_SERVER['HTTP_HOST'] ?? '';
if ($host === 'bumdesadigital.my.id' || $host === 'www.bumdesadigital.my.id') {
    define('BASEURL', 'https://bumdesadigital.my.id');
} else {
    // Fallback untuk localhost
    $protocol = (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on') ? "https" : "http";
    define('BASEURL', $protocol . "://" . $host);
}

// Pengaturan Session & Cookie agar tidak bentrok (Penting untuk Shared Hosting)
session_name('SA_V36_SESSION');
$is_cli = (php_sapi_name() === 'cli');
$is_local = ($is_cli || empty($host) || $host === 'localhost' || $host === 'localhost:8000' || $host === '127.0.0.1' || strpos($host, '.test') !== false);
if (PHP_VERSION_ID >= 70300) {
    session_set_cookie_params([
        'lifetime' => 0,
        'path' => '/',
        'domain' => $is_local ? '' : 'bumdesadigital.my.id',
        'secure' => (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on'),
        'httponly' => true,
        'samesite' => 'Lax'
    ]);
} else {
    session_set_cookie_params(0, '/; samesite=Lax', $is_local ? '' : 'bumdesadigital.my.id', (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on'), true);
}

// Path Absolut Aplikasi
define('APPROOT', dirname(dirname(__FILE__)));

// Membaca file .env jika ada
$env_path = dirname(dirname(__FILE__)) . '/.env';
if (file_exists($env_path)) {
    $env = parse_ini_file($env_path);
} else {
    $env = []; // Fallback jika tidak ada .env
}

// Konfigurasi Database (Otomatis antara Local dan Production)
if ($is_local && $host !== 'bumdesadigital.my.id' && $host !== 'www.bumdesadigital.my.id') {
    // Kredensial LOKAL
    define('DB_HOST', $env['DB_HOST_LOCAL'] ?? '127.0.0.1');
    define('DB_USER', $env['DB_USER_LOCAL'] ?? 'root');
    define('DB_PASS', $env['DB_PASS_LOCAL'] ?? 'root');
    define('DB_NAME', $env['DB_NAME_LOCAL'] ?? 'simpleak36');
} else {
    // Kredensial PRODUKSI (bumdesadigital.my.id)
    define('DB_HOST', $env['DB_HOST_PROD'] ?? 'localhost');
    define('DB_USER', $env['DB_USER_PROD'] ?? 'root'); // Jangan hardcode di sini!
    define('DB_PASS', $env['DB_PASS_PROD'] ?? '');
    define('DB_NAME', $env['DB_NAME_PROD'] ?? 'bumdesad_simpleakv36');
}