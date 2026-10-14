<?php
/**
 * Migration Script for SimpleAkunting v3.6
 * Modul Unit Usaha Jasa Keuangan (Simpan Pinjam)
 */
require_once 'app/config.php';
require_once 'app/core/Database.php';
require_once 'app/models/SimpanPinjam_model.php';

$db = new Database();
$spModel = new SimpanPinjam_model($db);

echo "====================================================\n";
echo "MIGRASI MODUL JASA KEUANGAN (SIMPAN PINJAM) v3.6\n";
echo "====================================================\n\n";

try {
    // 1. Ambil semua tenant aktif
    $db->query("SELECT id, name FROM tenants");
    $tenants = $db->resultSet();
    if (empty($tenants)) {
        // Fallback tenant id 1
        $tenants = [['id' => 1, 'name' => 'Default Tenant']];
    }

    echo "1. Memastikan tabel & pengaturan default untuk " . count($tenants) . " tenant...\n";
    foreach ($tenants as $t) {
        $tid = (int)$t['id'];
        $spModel->ensureTablesExist($tid);
        $spModel->seedBaganAkun($tid);
        echo "   [OK] Tenant: {$t['name']} (ID: {$tid})\n";
    }

    // Standardisasi Collation tabel-tabel Simpan Pinjam ke utf8mb4_general_ci
    $spTables = ['sp_pengaturan', 'sp_kelompok', 'sp_nasabah', 'sp_jenis_simpanan', 'sp_rekening_simpanan', 'sp_transaksi_simpanan', 'sp_jenis_pinjaman', 'sp_pinjaman', 'sp_jadwal_angsuran'];
    foreach ($spTables as $tbl) {
        $db->query("ALTER TABLE `{$tbl}` CONVERT TO CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci");
        $db->execute();
    }
    echo "   [OK] Collation tabel Simpan Pinjam diselaraskan ke utf8mb4_general_ci.\n";

    // 2. Daftarkan Hak Akses / Permissions Baru di Sistem RBAC
    echo "\n2. Mendaftarkan Permissions RBAC Simpan Pinjam...\n";
    $permissions = [
        ['menu_simpan_pinjam', 'Akses Unit Simpan Pinjam', 'Jasa Keuangan'],
        ['sp_nasabah', 'Manajemen Nasabah & Kelompok', 'Jasa Keuangan'],
        ['sp_simpanan', 'Transaksi Simpanan (Setor/Tarik)', 'Jasa Keuangan'],
        ['sp_pinjaman', 'Pengajuan & Pencairan Pinjaman', 'Jasa Keuangan'],
        ['sp_angsuran', 'Penerimaan Angsuran Pinjaman', 'Jasa Keuangan'],
        ['sp_laporan', 'Laporan Kolektibilitas & Outstanding', 'Jasa Keuangan'],
        ['sp_pengaturan', 'Pengaturan Parameter COA Simpan Pinjam', 'Jasa Keuangan']
    ];

    foreach ($permissions as $p) {
        $db->query("SELECT id FROM permissions WHERE permission_key = :key");
        $db->bind('key', $p[0]);
        $row = $db->single();

        if (!$row) {
            $db->query("INSERT INTO permissions (permission_key, display_name, category) VALUES (:key, :name, :cat)");
            $db->bind('key', $p[0]);
            $db->bind('name', $p[1]);
            $db->bind('cat', $p[2]);
            $db->execute();
            echo "   [+] Permission '{$p[0]}' ditambahkan.\n";
        } else {
            echo "   [-] Permission '{$p[0]}' sudah ada.\n";
        }
    }

    // 3. Assign permissions to Superadmin (role_id 1), Admin (role_id 2), and Manager (role_id 3)
    echo "\n3. Menautkan permissions ke role Superadmin, Admin & Manager...\n";
    $db->query("INSERT IGNORE INTO role_permissions (role_id, permission_id) 
                SELECT 1, id FROM permissions");
    $db->execute();

    $db->query("INSERT IGNORE INTO role_permissions (role_id, permission_id) 
                SELECT 2, id FROM permissions WHERE permission_key != 'sys_user_management'");
    $db->execute();

    $db->query("INSERT IGNORE INTO role_permissions (role_id, permission_id) 
                SELECT 3, id FROM permissions WHERE permission_key IN (
                    'menu_programs', 'trx_programs_manage', 'rep_programs_realization',
                    'menu_units', 'trx_units_manage',
                    'menu_simpan_pinjam', 'sp_nasabah', 'sp_simpanan', 'sp_pinjaman', 'sp_angsuran', 'sp_laporan'
                )");
    $db->execute();
    echo "   [OK] Role Permissions updated.\n";

    echo "\n====================================================\n";
    echo "✅ MIGRASI MODUL SIMPAN PINJAM SELESAI DENGAN SUKSES!\n";
    echo "====================================================\n";

} catch (Exception $e) {
    echo "\n❌ ERROR SAAT MIGRASI: " . $e->getMessage() . "\n";
    exit(1);
}
