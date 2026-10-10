<?php
/**
 * Migration Script: Fitur Tutup Buku & Periode Akuntansi
 * SimpleAkunting v3.6
 */
require_once __DIR__ . '/app/config.php';
require_once __DIR__ . '/app/core/Database.php';

try {
    echo "Memulai migrasi tabel periode_akuntansi dan permission Tutup Buku...\n";
    $db = new Database();

    // 1. Buat Tabel periode_akuntansi jika belum ada
    $sqlTable = "
    CREATE TABLE IF NOT EXISTS `periode_akuntansi` (
        `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
        `tenant_id` int(11) NOT NULL,
        `tahun` int(11) NOT NULL,
        `bulan` int(11) NOT NULL,
        `status` enum('Open','Closed') NOT NULL DEFAULT 'Closed',
        `tipe_proses` varchar(20) NOT NULL DEFAULT 'Bulanan',
        `id_jurnal` bigint(20) unsigned DEFAULT NULL,
        `total_pendapatan` decimal(15,2) NOT NULL DEFAULT 0.00,
        `total_beban` decimal(15,2) NOT NULL DEFAULT 0.00,
        `laba_bersih` decimal(15,2) NOT NULL DEFAULT 0.00,
        `tanggal_tutup` datetime DEFAULT NULL,
        `closed_by` bigint(20) unsigned DEFAULT NULL,
        `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
        `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        PRIMARY KEY (`id`),
        UNIQUE KEY `uq_tenant_tahun_bulan` (`tenant_id`, `tahun`, `bulan`),
        KEY `idx_tenant_status` (`tenant_id`, `status`),
        KEY `idx_id_jurnal` (`id_jurnal`)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
    ";
    $db->query($sqlTable);
    $db->execute();

    // 1b. Periksa dan tambahkan kolom jika tabel sudah ada dari versi sebelumnya
    $db->query("SHOW COLUMNS FROM `periode_akuntansi`");
    $existingCols = [];
    foreach ($db->resultSet() as $row) {
        $existingCols[] = strtolower($row['Field']);
    }

    if (!in_array('id_jurnal', $existingCols)) {
        $db->query("ALTER TABLE `periode_akuntansi` ADD COLUMN `id_jurnal` bigint(20) unsigned DEFAULT NULL");
        $db->execute();
    }
    if (!in_array('total_pendapatan', $existingCols)) {
        $db->query("ALTER TABLE `periode_akuntansi` ADD COLUMN `total_pendapatan` decimal(15,2) NOT NULL DEFAULT 0.00");
        $db->execute();
    }
    if (!in_array('total_beban', $existingCols)) {
        $db->query("ALTER TABLE `periode_akuntansi` ADD COLUMN `total_beban` decimal(15,2) NOT NULL DEFAULT 0.00");
        $db->execute();
    }
    if (!in_array('laba_bersih', $existingCols)) {
        $db->query("ALTER TABLE `periode_akuntansi` ADD COLUMN `laba_bersih` decimal(15,2) NOT NULL DEFAULT 0.00");
        $db->execute();
    }
    if (!in_array('tanggal_tutup', $existingCols)) {
        $db->query("ALTER TABLE `periode_akuntansi` ADD COLUMN `tanggal_tutup` datetime DEFAULT NULL");
        $db->execute();
    }
    if (!in_array('closed_by', $existingCols)) {
        $db->query("ALTER TABLE `periode_akuntansi` ADD COLUMN `closed_by` bigint(20) unsigned DEFAULT NULL");
        $db->execute();
    }
    if (!in_array('tipe_proses', $existingCols)) {
        $db->query("ALTER TABLE `periode_akuntansi` ADD COLUMN `tipe_proses` varchar(20) NOT NULL DEFAULT 'Bulanan'");
        $db->execute();
    }

    echo "✅ Tabel 'periode_akuntansi' dan seluruh kolom berhasil disiapkan!\n";

    // 2. Tambahkan Permission 'fin_tutup_buku' jika belum ada
    $sqlPerm = "
    INSERT IGNORE INTO `permissions` (`permission_key`, `display_name`, `category`) 
    VALUES ('fin_tutup_buku', 'Tutup Buku Akhir Periode', 'Keuangan');
    ";
    $db->query($sqlPerm);
    $db->execute();

    // 3. Kaitkan ke Role Superadmin, Admin, Manager
    $sqlRolePerm = "
    INSERT IGNORE INTO `role_permissions` (`role_id`, `permission_id`)
    SELECT r.id, p.id FROM roles r, permissions p
    WHERE p.permission_key = 'fin_tutup_buku' AND r.role_name IN ('Superadmin', 'Admin', 'Manager');
    ";
    $db->query($sqlRolePerm);
    $db->execute();
    echo "✅ Permission 'fin_tutup_buku' berhasil dikonfigurasi untuk Superadmin, Admin, dan Manager!\n";

    // 4. Pastikan kolom di tabel perusahaan tersedia
    $db->query("SHOW COLUMNS FROM perusahaan LIKE 'akun_ikhtisar_lr'");
    if (empty($db->resultSet())) {
        $db->query("ALTER TABLE perusahaan ADD COLUMN akun_ikhtisar_lr VARCHAR(20) DEFAULT '3-3000' AFTER akun_laba_ditahan");
        $db->execute();
        echo "✅ Kolom 'akun_ikhtisar_lr' ditambahkan ke tabel perusahaan!\n";
    }

    echo "🎉 Migrasi Fitur Tutup Buku Selesai dengan Sukses!\n";
} catch (Exception $e) {
    echo "❌ Error migrasi: " . $e->getMessage() . "\n";
}
