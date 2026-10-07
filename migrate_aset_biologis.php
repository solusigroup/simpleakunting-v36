<?php
/**
 * Migration script for Biological Assets (Aset Biologis) module
 */
require_once 'app/config.php';
require_once 'app/core/Database.php';

$db = new Database();

$sql = "
SET FOREIGN_KEY_CHECKS = 0;

CREATE TABLE IF NOT EXISTS aset_biologis (
    id INT AUTO_INCREMENT PRIMARY KEY,
    tenant_id INT NOT NULL,
    kode_aset VARCHAR(50) NOT NULL,
    nama_aset VARCHAR(255) NOT NULL,
    kategori ENUM('Hewan Konsumsi', 'Hewan Produktif', 'Tanaman Konsumsi', 'Tanaman Produktif', 'Hasil Agrikultur pada Tanaman') NOT NULL,
    jenis VARCHAR(100) DEFAULT NULL COMMENT 'Jenis spesifik: Sapi Perah, Kelapa Sawit, dll',
    status_kematangan ENUM('Belum Menghasilkan', 'Menghasilkan') NOT NULL DEFAULT 'Belum Menghasilkan',
    metode_pengukuran ENUM('Nilai Wajar', 'Biaya Perolehan') NOT NULL DEFAULT 'Nilai Wajar',
    tanggal_perolehan DATE NOT NULL,
    biaya_perolehan DECIMAL(18,2) NOT NULL DEFAULT 0,
    biaya_jual DECIMAL(18,2) NOT NULL DEFAULT 0 COMMENT 'Costs to sell',
    nilai_wajar DECIMAL(18,2) NOT NULL DEFAULT 0 COMMENT 'Fair value',
    nilai_tercatat DECIMAL(18,2) NOT NULL DEFAULT 0 COMMENT 'Carrying amount = FV - Costs to Sell',
    kuantitas DECIMAL(18,4) NOT NULL DEFAULT 0 COMMENT 'Jumlah fisik: ekor, pohon, hektar, kg',
    satuan VARCHAR(50) DEFAULT 'Ekor' COMMENT 'Satuan: Ekor, Pohon, Ha, Kg, Ton',
    lokasi VARCHAR(255) DEFAULT NULL,
    umur_ekonomis INT DEFAULT NULL COMMENT 'Bulan, hanya untuk Bearer Plants model Biaya',
    akun_aset_biologis VARCHAR(20) NOT NULL COMMENT 'Akun neraca aset biologis',
    akun_keuntungan_nilai_wajar VARCHAR(20) DEFAULT NULL COMMENT 'Akun P&L gain FV',
    akun_kerugian_nilai_wajar VARCHAR(20) DEFAULT NULL COMMENT 'Akun P&L loss FV',
    akun_beban_pemeliharaan VARCHAR(20) DEFAULT NULL COMMENT 'Akun beban pemeliharaan',
    akun_hasil_panen VARCHAR(20) DEFAULT NULL COMMENT 'Akun persediaan hasil panen',
    akun_keuntungan_panen VARCHAR(20) DEFAULT NULL COMMENT 'Akun P&L gain on harvest',
    status ENUM('Aktif', 'Dipanen', 'Dijual', 'Mati/Hapus') NOT NULL DEFAULT 'Aktif',
    keterangan TEXT DEFAULT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_tenant (tenant_id),
    INDEX idx_kategori (tenant_id, kategori),
    INDEX idx_status (tenant_id, status)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS aset_biologis_penyesuaian (
    id INT AUTO_INCREMENT PRIMARY KEY,
    tenant_id INT NOT NULL,
    id_aset_biologis INT NOT NULL,
    id_jurnal INT DEFAULT NULL,
    tanggal DATE NOT NULL,
    tipe_penyesuaian ENUM('Nilai Wajar', 'Penyusutan', 'Reklasifikasi') NOT NULL,
    nilai_wajar_lama DECIMAL(18,2) NOT NULL DEFAULT 0,
    nilai_wajar_baru DECIMAL(18,2) NOT NULL DEFAULT 0,
    selisih DECIMAL(18,2) NOT NULL DEFAULT 0,
    kuantitas_lama DECIMAL(18,4) DEFAULT 0,
    kuantitas_baru DECIMAL(18,4) DEFAULT 0,
    keterangan TEXT DEFAULT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_tenant_aset (tenant_id, id_aset_biologis),
    FOREIGN KEY (id_aset_biologis) REFERENCES aset_biologis(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS aset_biologis_panen (
    id INT AUTO_INCREMENT PRIMARY KEY,
    tenant_id INT NOT NULL,
    id_aset_biologis INT NOT NULL,
    id_jurnal INT DEFAULT NULL,
    tanggal_panen DATE NOT NULL,
    nama_hasil VARCHAR(255) NOT NULL,
    kuantitas_panen DECIMAL(18,4) NOT NULL,
    satuan_panen VARCHAR(50) NOT NULL,
    nilai_wajar_saat_panen DECIMAL(18,2) NOT NULL COMMENT 'FV less costs to sell at harvest',
    biaya_panen DECIMAL(18,2) DEFAULT 0,
    akun_persediaan VARCHAR(20) NOT NULL COMMENT 'Target inventory account',
    keterangan TEXT DEFAULT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_tenant_aset (tenant_id, id_aset_biologis),
    FOREIGN KEY (id_aset_biologis) REFERENCES aset_biologis(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS aset_biologis_pelepasan (
    id INT AUTO_INCREMENT PRIMARY KEY,
    tenant_id INT NOT NULL,
    id_aset_biologis INT NOT NULL,
    id_jurnal INT DEFAULT NULL,
    tanggal DATE NOT NULL,
    tipe ENUM('Penjualan', 'Kematian', 'Hapus Buku') NOT NULL,
    kuantitas DECIMAL(18,4) NOT NULL DEFAULT 0,
    nilai_tercatat DECIMAL(18,2) NOT NULL DEFAULT 0 COMMENT 'Carrying amount saat pelepasan',
    nilai_jual DECIMAL(18,2) DEFAULT 0 COMMENT 'Proceeds from sale',
    laba_rugi DECIMAL(18,2) DEFAULT 0 COMMENT 'Gain/loss on disposal',
    keterangan TEXT DEFAULT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_tenant_aset (tenant_id, id_aset_biologis),
    FOREIGN KEY (id_aset_biologis) REFERENCES aset_biologis(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

SET FOREIGN_KEY_CHECKS = 1;
";

$accounts = [
    ['1-10700', 'Aset Biologis', 'Header', 'Debit'],
    ['1-10701', 'Aset Biologis - Hewan Konsumsi', 'Detail', 'Debit'],
    ['1-10702', 'Aset Biologis - Hewan Produktif', 'Detail', 'Debit'],
    ['1-10703', 'Aset Biologis - Tanaman Konsumsi', 'Detail', 'Debit'],
    ['1-10704', 'Aset Biologis - Hasil pada Tanaman', 'Detail', 'Debit'],
    ['1-10300', 'Persediaan Hasil Agrikultur', 'Detail', 'Debit'],
    ['4-40300', 'Keuntungan Nilai Wajar Aset Biologis', 'Detail', 'Kredit'],
    ['4-40400', 'Keuntungan Panen Produk Agrikultur', 'Detail', 'Kredit'],
    ['4-40500', 'Pendapatan Penjualan Aset Biologis', 'Detail', 'Kredit'],
    ['5-50300', 'Beban Pokok Aset Biologis Terjual', 'Detail', 'Debit'],
    ['6-60400', 'Kerugian Penurunan Nilai Wajar Aset Biologis', 'Detail', 'Debit'],
    ['6-60500', 'Beban Pemeliharaan Aset Biologis', 'Detail', 'Debit'],
    ['6-60600', 'Kerugian Kematian/Hapus Aset Biologis', 'Detail', 'Debit'],
    ['6-60700', 'Beban Panen', 'Detail', 'Debit']
];

try {
    echo "Running Biological Assets (Aset Biologis) Migration...\n";
    
    // 1. Create Tables
    $db->query($sql);
    $db->execute();
    echo "✅ Created tables: aset_biologis, aset_biologis_penyesuaian, aset_biologis_panen, aset_biologis_pelepasan\n";
    
    // 2. Fetch all tenants
    $db->query("SELECT id FROM tenants");
    $tenants = $db->resultSet();
    
    // 3. Insert COA entries for each tenant
    $coa_inserted = 0;
    foreach ($tenants as $t) {
        $tenant_id = $t['id'];
        
        foreach ($accounts as $acc) {
            // Use INSERT IGNORE to avoid duplicate key errors
            $db->query("INSERT IGNORE INTO akun (kode_akun, nama_akun, tipe_akun, posisi_saldo_normal, saldo_awal, tenant_id) 
                       VALUES (:kode, :nama, :tipe, :posisi, 0, :tenant)");
            $db->bind('kode', $acc[0]);
            $db->bind('nama', $acc[1]);
            $db->bind('tipe', $acc[2]);
            $db->bind('posisi', $acc[3]);
            $db->bind('tenant', $tenant_id);
            $db->execute();
            
            if ($db->rowCount() > 0) {
                $coa_inserted++;
            }
        }
    }
    
    echo "✅ Added {$coa_inserted} Chart of Accounts entries across " . count($tenants) . " tenants.\n";
    echo "✅ Migration Successful!\n";
    
} catch (Exception $e) {
    echo "❌ Migration Error: " . $e->getMessage() . "\n";
}
