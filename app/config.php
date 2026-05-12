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

// Path Absolut Aplikasi
define('APPROOT', dirname(dirname(__FILE__)));

// Konfigurasi Database (Otomatis antara Local dan Production)
if ($host === 'localhost:8000' || $host === '127.0.0.1' || strpos($host, '.test') !== false) {
    // Kredensial LOKAL
    define('DB_HOST', '127.0.0.1');
    define('DB_USER', 'root');
    define('DB_PASS', 'root');
    define('DB_NAME', 'simpleak36');
} else {
    // Kredensial PRODUKSI (bumdesadigital.my.id)
    define('DB_HOST', 'localhost');
    define('DB_USER', 'bumdesad_simpleakv36');
    define('DB_PASS', '5@8@12Yaa');
    define('DB_NAME', 'bumdesad_simpleakv36');
}