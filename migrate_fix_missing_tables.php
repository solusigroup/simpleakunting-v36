<?php
/**
 * Migration script to fix missing 'periode_akuntansi' table
 */
$_SERVER['HTTP_HOST'] = '127.0.0.1';
require_once 'app/config.php';
require_once 'app/core/Database.php';

$db = new Database();

try {
    echo "Starting migration to fix missing tables...\n";

    $sql = "
    CREATE TABLE IF NOT EXISTS `periode_akuntansi` (
      `id` INT AUTO_INCREMENT PRIMARY KEY,
      `tenant_id` INT NOT NULL,
      `tahun` INT(4) NOT NULL,
      `bulan` INT(2) NOT NULL,
      `status` ENUM('Open', 'Closed') DEFAULT 'Open',
      `tipe_proses` ENUM('Bulanan', 'Tahunan') DEFAULT 'Bulanan',
      `tanggal_tutup` DATETIME DEFAULT NULL,
      `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
      `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
      UNIQUE KEY `idx_tenant_periode` (`tenant_id`, `tahun`, `bulan`),
      INDEX (`tenant_id`)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
    ";

    $db->query($sql);
    $db->execute();
    
    echo "✅ Table 'periode_akuntansi' created successfully.\n";
    echo "Migration completed!\n";

} catch (Exception $e) {
    echo "❌ Migration failed: " . $e->getMessage() . "\n";
}
