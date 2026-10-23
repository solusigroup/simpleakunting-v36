<?php
/**
 * Standalone web migration runner for central_akun_kepmendesa
 * Can be run directly via browser or CLI
 */
require_once __DIR__ . '/../app/config.php';
require_once __DIR__ . '/../app/core/Database.php';

header('Content-Type: text/html; charset=utf-8');

$db = new Database();

echo "<!DOCTYPE html><html><head><title>Migrasi COA Kepmendesa 136</title>";
echo "<link href='https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css' rel='stylesheet'>";
echo "</head><body class='bg-light p-4'><div class='container' style='max-width: 600px;'>";
echo "<div class='card shadow-sm'><div class='card-header bg-success text-white'><h4>Migrasi COA Kepmendesa No. 136 Tahun 2022</h4></div><div class='card-body'>";

try {
    $sqlFile = __DIR__ . '/migrations/create_central_akun_kepmendesa.sql';
    if (!file_exists($sqlFile)) {
        throw new Exception("File migrasi tidak ditemukan di: " . $sqlFile);
    }

    $sql = file_get_contents($sqlFile);
    if (empty($sql)) {
        throw new Exception("File migrasi kosong.");
    }

    // Eksekusi SQL
    $db->dbh->exec($sql);

    // Cek hasil
    $db->query("SELECT COUNT(*) as c FROM central_akun_kepmendesa");
    $cnt = $db->single();
    $total = $cnt['c'] ?? 0;

    echo "<div class='alert alert-success'>";
    echo "<h5 class='alert-heading'>✅ Migrasi Berhasil!</h5>";
    echo "<p class='mb-0'>Tabel <code>central_akun_kepmendesa</code> telah berhasil dibuat dan diisi dengan <strong>{$total} akun</strong>.</p>";
    echo "</div>";
    echo "<a href='" . BASEURL . "/akun' class='btn btn-primary w-100'>Kembali ke Menu Daftar Akun</a>";
} catch (Exception $e) {
    echo "<div class='alert alert-danger'>";
    echo "<h5 class='alert-heading'>❌ Gagal Migrasi</h5>";
    echo "<p class='mb-0'>" . htmlspecialchars($e->getMessage()) . "</p>";
    echo "</div>";
}

echo "</div></div></div></body></html>";
