<?php

class SimpanPinjam_model {
    private $db;

    public function __construct($db) {
        $this->db = $db;
    }

    /**
     * Memastikan semua tabel yang dibutuhkan untuk modul Jasa Keuangan (Simpan Pinjam) tersedia.
     */
    public function ensureTablesExist($tenant_id) {
        // 1. Tabel Pengaturan Unit & Parameter COA
        $this->db->query("CREATE TABLE IF NOT EXISTS `sp_pengaturan` (
            `id` int(11) NOT NULL AUTO_INCREMENT,
            `tenant_id` int(11) NOT NULL,
            `status_aktif` tinyint(1) NOT NULL DEFAULT 1,
            `nama_unit_sp` varchar(255) NOT NULL DEFAULT 'Unit Usaha Simpan Pinjam',
            `akun_kas_default` varchar(20) DEFAULT NULL,
            `akun_piutang_pinjaman` varchar(20) DEFAULT NULL,
            `akun_cadangan_kerugian` varchar(20) DEFAULT NULL,
            `akun_simpanan_pokok` varchar(20) DEFAULT NULL,
            `akun_simpanan_wajib` varchar(20) DEFAULT NULL,
            `akun_simpanan_sukarela` varchar(20) DEFAULT NULL,
            `akun_pendapatan_jasa` varchar(20) DEFAULT NULL,
            `akun_pendapatan_admin` varchar(20) DEFAULT NULL,
            `akun_pendapatan_denda` varchar(20) DEFAULT NULL,
            `akun_beban_bunga_simpanan` varchar(20) DEFAULT NULL,
            `posting_jurnal_otomatis` tinyint(1) NOT NULL DEFAULT 1,
            `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
            `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY (`id`),
            UNIQUE KEY `sp_setting_tenant` (`tenant_id`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;");
        $this->db->execute();

        // 2. Tabel Kelompok Nasabah
        $this->db->query("CREATE TABLE IF NOT EXISTS `sp_kelompok` (
            `id_kelompok` int(11) NOT NULL AUTO_INCREMENT,
            `tenant_id` int(11) NOT NULL,
            `kode_kelompok` varchar(50) NOT NULL,
            `nama_kelompok` varchar(255) NOT NULL,
            `nama_ketua` varchar(255) DEFAULT NULL,
            `kontak` varchar(50) DEFAULT NULL,
            `alamat_wilayah` text DEFAULT NULL,
            `tanggal_berdiri` date DEFAULT NULL,
            `status` enum('Aktif','Non-Aktif') NOT NULL DEFAULT 'Aktif',
            `keterangan` text DEFAULT NULL,
            `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
            `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY (`id_kelompok`),
            KEY `idx_sp_kelompok_tenant` (`tenant_id`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;");
        $this->db->execute();

        // 3. Tabel Nasabah Perorangan
        $this->db->query("CREATE TABLE IF NOT EXISTS `sp_nasabah` (
            `id_nasabah` int(11) NOT NULL AUTO_INCREMENT,
            `tenant_id` int(11) NOT NULL,
            `id_kelompok` int(11) DEFAULT NULL,
            `peran_kelompok` enum('Ketua','Sekretaris','Bendahara','Anggota') NOT NULL DEFAULT 'Anggota',
            `no_anggota` varchar(50) NOT NULL,
            `nik` varchar(20) DEFAULT NULL,
            `nama_lengkap` varchar(255) NOT NULL,
            `jenis_kelamin` enum('L','P') DEFAULT 'L',
            `tempat_lahir` varchar(100) DEFAULT NULL,
            `tanggal_lahir` date DEFAULT NULL,
            `alamat` text DEFAULT NULL,
            `no_telp` varchar(30) DEFAULT NULL,
            `pekerjaan` varchar(100) DEFAULT NULL,
            `status` enum('Aktif','Non-Aktif') NOT NULL DEFAULT 'Aktif',
            `tanggal_daftar` date DEFAULT NULL,
            `catatan` text DEFAULT NULL,
            `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
            `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY (`id_nasabah`),
            KEY `idx_sp_nasabah_tenant` (`tenant_id`),
            KEY `idx_sp_nasabah_kelompok` (`id_kelompok`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;");
        $this->db->execute();

        // 4. Tabel Jenis Simpanan
        $this->db->query("CREATE TABLE IF NOT EXISTS `sp_jenis_simpanan` (
            `id_jenis_simpanan` int(11) NOT NULL AUTO_INCREMENT,
            `tenant_id` int(11) NOT NULL,
            `kode_simpanan` varchar(50) NOT NULL,
            `nama_simpanan` varchar(255) NOT NULL,
            `tipe_simpanan` enum('Pokok','Wajib','Sukarela','Berjangka') NOT NULL DEFAULT 'Sukarela',
            `nominal_minimal_setor` decimal(15,2) NOT NULL DEFAULT 0.00,
            `bunga_pertahun` decimal(5,2) NOT NULL DEFAULT 0.00,
            `kode_akun` varchar(20) DEFAULT NULL,
            `status` enum('Aktif','Non-Aktif') NOT NULL DEFAULT 'Aktif',
            `keterangan` text DEFAULT NULL,
            `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
            `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY (`id_jenis_simpanan`),
            KEY `idx_sp_jenis_simpanan_tenant` (`tenant_id`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;");
        $this->db->execute();

        // 5. Tabel Rekening Simpanan
        $this->db->query("CREATE TABLE IF NOT EXISTS `sp_rekening_simpanan` (
            `id_rekening` int(11) NOT NULL AUTO_INCREMENT,
            `tenant_id` int(11) NOT NULL,
            `no_rekening` varchar(50) NOT NULL,
            `id_nasabah` int(11) NOT NULL,
            `id_jenis_simpanan` int(11) NOT NULL,
            `saldo` decimal(15,2) NOT NULL DEFAULT 0.00,
            `tanggal_buka` date NOT NULL,
            `status` enum('Aktif','Tutup') NOT NULL DEFAULT 'Aktif',
            `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
            `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY (`id_rekening`),
            KEY `idx_sp_rek_tenant` (`tenant_id`),
            KEY `idx_sp_rek_nasabah` (`id_nasabah`),
            KEY `idx_sp_rek_jenis` (`id_jenis_simpanan`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;");
        $this->db->execute();

        // 6. Tabel Transaksi Simpanan (Setor & Tarik)
        $this->db->query("CREATE TABLE IF NOT EXISTS `sp_transaksi_simpanan` (
            `id_transaksi` int(11) NOT NULL AUTO_INCREMENT,
            `tenant_id` int(11) NOT NULL,
            `no_transaksi` varchar(50) NOT NULL,
            `id_rekening` int(11) NOT NULL,
            `jenis_transaksi` enum('Setor','Tarik','Bunga','Biaya_Admin') NOT NULL,
            `tanggal` date NOT NULL,
            `nominal` decimal(15,2) NOT NULL,
            `saldo_sebelum` decimal(15,2) NOT NULL,
            `saldo_sesudah` decimal(15,2) NOT NULL,
            `kode_akun_kas` varchar(20) DEFAULT NULL,
            `id_jurnal` int(11) DEFAULT NULL,
            `keterangan` text DEFAULT NULL,
            `created_by` int(11) DEFAULT NULL,
            `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (`id_transaksi`),
            KEY `idx_sp_trx_simp_tenant` (`tenant_id`),
            KEY `idx_sp_trx_simp_rek` (`id_rekening`),
            KEY `idx_sp_trx_simp_tgl` (`tanggal`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;");
        $this->db->execute();

        // 7. Tabel Jenis Pinjaman
        $this->db->query("CREATE TABLE IF NOT EXISTS `sp_jenis_pinjaman` (
            `id_jenis_pinjaman` int(11) NOT NULL AUTO_INCREMENT,
            `tenant_id` int(11) NOT NULL,
            `kode_pinjaman` varchar(50) NOT NULL,
            `nama_pinjaman` varchar(255) NOT NULL,
            `sistem_bunga` enum('Flat','Menurun','Anuitas') NOT NULL DEFAULT 'Flat',
            `suku_bunga_persen` decimal(5,2) NOT NULL DEFAULT 1.00,
            `satuan_waktu` enum('Bulan','Minggu') NOT NULL DEFAULT 'Bulan',
            `biaya_admin_persen` decimal(5,2) NOT NULL DEFAULT 0.00,
            `denda_keterlambatan_persen` decimal(5,2) NOT NULL DEFAULT 0.00,
            `kode_akun_piutang` varchar(20) DEFAULT NULL,
            `kode_akun_pendapatan_jasa` varchar(20) DEFAULT NULL,
            `status` enum('Aktif','Non-Aktif') NOT NULL DEFAULT 'Aktif',
            `keterangan` text DEFAULT NULL,
            `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
            `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY (`id_jenis_pinjaman`),
            KEY `idx_sp_jenis_pinjaman_tenant` (`tenant_id`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;");
        $this->db->execute();

        // 8. Tabel Pinjaman (Pengajuan, Persetujuan, Realisasi)
        $this->db->query("CREATE TABLE IF NOT EXISTS `sp_pinjaman` (
            `id_pinjaman` int(11) NOT NULL AUTO_INCREMENT,
            `tenant_id` int(11) NOT NULL,
            `no_pinjaman` varchar(50) NOT NULL,
            `tipe_debitur` enum('Perorangan','Kelompok') NOT NULL DEFAULT 'Perorangan',
            `id_nasabah` int(11) DEFAULT NULL,
            `id_kelompok` int(11) DEFAULT NULL,
            `id_jenis_pinjaman` int(11) NOT NULL,
            `tanggal_pengajuan` date NOT NULL,
            `jumlah_pengajuan` decimal(15,2) NOT NULL,
            `jumlah_disetujui` decimal(15,2) NOT NULL DEFAULT 0.00,
            `jangka_waktu` int(11) NOT NULL,
            `suku_bunga` decimal(5,2) NOT NULL DEFAULT 0.00,
            `sistem_bunga` varchar(20) NOT NULL DEFAULT 'Flat',
            `biaya_admin` decimal(15,2) NOT NULL DEFAULT 0.00,
            `angsuran_pokok_per_periode` decimal(15,2) NOT NULL DEFAULT 0.00,
            `angsuran_jasa_per_periode` decimal(15,2) NOT NULL DEFAULT 0.00,
            `total_angsuran_per_periode` decimal(15,2) NOT NULL DEFAULT 0.00,
            `sisa_pokok` decimal(15,2) NOT NULL DEFAULT 0.00,
            `sisa_jasa` decimal(15,2) NOT NULL DEFAULT 0.00,
            `tujuan_pinjaman` text DEFAULT NULL,
            `agunan` text DEFAULT NULL,
            `status` enum('Diajukan','Disetujui','Ditolak','Dicairkan','Lunas') NOT NULL DEFAULT 'Diajukan',
            `tanggal_persetujuan` date DEFAULT NULL,
            `catatan_persetujuan` text DEFAULT NULL,
            `tanggal_pencairan` date DEFAULT NULL,
            `kode_akun_kas_pencairan` varchar(20) DEFAULT NULL,
            `id_jurnal_pencairan` int(11) DEFAULT NULL,
            `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
            `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY (`id_pinjaman`),
            KEY `idx_sp_pinj_tenant` (`tenant_id`),
            KEY `idx_sp_pinj_nasabah` (`id_nasabah`),
            KEY `idx_sp_pinj_kelompok` (`id_kelompok`),
            KEY `idx_sp_pinj_status` (`status`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;");
        $this->db->execute();

        // 9. Tabel Jadwal & Pembayaran Angsuran
        $this->db->query("CREATE TABLE IF NOT EXISTS `sp_jadwal_angsuran` (
            `id_jadwal` int(11) NOT NULL AUTO_INCREMENT,
            `tenant_id` int(11) NOT NULL,
            `id_pinjaman` int(11) NOT NULL,
            `angsuran_ke` int(11) NOT NULL,
            `jatuh_tempo` date NOT NULL,
            `pokok` decimal(15,2) NOT NULL,
            `jasa` decimal(15,2) NOT NULL,
            `total_tagihan` decimal(15,2) NOT NULL,
            `denda` decimal(15,2) NOT NULL DEFAULT 0.00,
            `total_bayar` decimal(15,2) NOT NULL DEFAULT 0.00,
            `tanggal_bayar` date DEFAULT NULL,
            `kode_akun_kas_bayar` varchar(20) DEFAULT NULL,
            `id_jurnal_bayar` int(11) DEFAULT NULL,
            `status_bayar` enum('Belum','Lunas','Sebagian') NOT NULL DEFAULT 'Belum',
            `keterangan` varchar(255) DEFAULT NULL,
            `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
            `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY (`id_jadwal`),
            KEY `idx_sp_jadwal_tenant` (`tenant_id`),
            KEY `idx_sp_jadwal_pinjaman` (`id_pinjaman`),
            KEY `idx_sp_jadwal_jatuh_tempo` (`jatuh_tempo`),
            KEY `idx_sp_jadwal_status` (`status_bayar`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;");
        $this->db->execute();

        // Pastikan record pengaturan default untuk tenant ini ada
        $this->initDefaultPengaturan($tenant_id);
    }

    /**
     * Inisialisasi pengaturan default jika belum ada.
     */
    public function initDefaultPengaturan($tenant_id) {
        $this->db->query("SELECT id FROM sp_pengaturan WHERE tenant_id = :tenant_id");
        $this->db->bind('tenant_id', $tenant_id);
        if (!$this->db->single()) {
            $this->db->query("INSERT INTO sp_pengaturan (tenant_id, status_aktif, nama_unit_sp, akun_kas_default, akun_piutang_pinjaman, akun_cadangan_kerugian, akun_simpanan_pokok, akun_simpanan_wajib, akun_simpanan_sukarela, akun_pendapatan_jasa, akun_pendapatan_admin, akun_pendapatan_denda, akun_beban_bunga_simpanan, posting_jurnal_otomatis) 
                              VALUES (:tenant_id, 1, 'Unit Usaha Jasa Keuangan (USP)', '1-10001', '1-10500', '1-10590', '3-10100', '2-10420', '2-10400', '4-20100', '4-20200', '4-20300', '5-20100', 1)");
            $this->db->bind('tenant_id', $tenant_id);
            $this->db->execute();

            // Inisialisasi jenis simpanan standar
            $this->initDefaultProduk($tenant_id);
        }
    }

    /**
     * Inisialisasi produk simpanan dan pinjaman default jika kosong.
     */
    private function initDefaultProduk($tenant_id) {
        // Cek jenis simpanan
        $this->db->query("SELECT COUNT(*) as cnt FROM sp_jenis_simpanan WHERE tenant_id = :tenant_id");
        $this->db->bind('tenant_id', $tenant_id);
        $res = $this->db->single();
        if ($res && $res['cnt'] == 0) {
            $defaultSimpanan = [
                ['SPK', 'Simpanan Pokok', 'Pokok', 100000, 0, '3-10100', 'Simpanan pokok wajib disetor saat pertama kali bergabung'],
                ['SWJ', 'Simpanan Wajib', 'Wajib', 20000, 0, '2-10420', 'Simpanan wajib dibayarkan rutin setiap bulan'],
                ['SSK', 'Simpanan Sukarela (Tabungan)', 'Sukarela', 10000, 2.5, '2-10400', 'Tabungan sukarela yang dapat disetor dan ditarik sewaktu-waktu']
            ];
            foreach ($defaultSimpanan as $s) {
                $this->db->query("INSERT INTO sp_jenis_simpanan (tenant_id, kode_simpanan, nama_simpanan, tipe_simpanan, nominal_minimal_setor, bunga_pertahun, kode_akun, keterangan)
                                  VALUES (:tenant_id, :kode, :nama, :tipe, :nominal, :bunga, :akun, :ket)");
                $this->db->bind('tenant_id', $tenant_id);
                $this->db->bind('kode', $s[0]);
                $this->db->bind('nama', $s[1]);
                $this->db->bind('tipe', $s[2]);
                $this->db->bind('nominal', $s[3]);
                $this->db->bind('bunga', $s[4]);
                $this->db->bind('akun', $s[5]);
                $this->db->bind('ket', $s[6]);
                $this->db->execute();
            }
        }

        // Cek jenis pinjaman
        $this->db->query("SELECT COUNT(*) as cnt FROM sp_jenis_pinjaman WHERE tenant_id = :tenant_id");
        $this->db->bind('tenant_id', $tenant_id);
        $resPinj = $this->db->single();
        if ($resPinj && $resPinj['cnt'] == 0) {
            $defaultPinjaman = [
                ['PMU', 'Pinjaman Modal Usaha', 'Flat', 1.5, 'Bulan', 1.0, 0.5, '1-10500', '4-20100', 'Pinjaman produktif modal kerja perorangan'],
                ['PKS', 'Pinjaman Konsumtif / Multiguna', 'Flat', 2.0, 'Bulan', 1.5, 1.0, '1-10500', '4-20100', 'Pinjaman keperluan pribadi atau keluarga'],
                ['PKL', 'Pinjaman Kelompok (Tanggung Renteng)', 'Flat', 1.25, 'Bulan', 1.0, 0.5, '1-10500', '4-20100', 'Pinjaman permodalan kelompok usaha bersama']
            ];
            foreach ($defaultPinjaman as $p) {
                $this->db->query("INSERT INTO sp_jenis_pinjaman (tenant_id, kode_pinjaman, nama_pinjaman, sistem_bunga, suku_bunga_persen, satuan_waktu, biaya_admin_persen, denda_keterlambatan_persen, kode_akun_piutang, kode_akun_pendapatan_jasa, keterangan)
                                  VALUES (:tenant_id, :kode, :nama, :sistem, :bunga, :satuan, :admin, :denda, :piutang, :jasa, :ket)");
                $this->db->bind('tenant_id', $tenant_id);
                $this->db->bind('kode', $p[0]);
                $this->db->bind('nama', $p[1]);
                $this->db->bind('sistem', $p[2]);
                $this->db->bind('bunga', $p[3]);
                $this->db->bind('satuan', $p[4]);
                $this->db->bind('admin', $p[5]);
                $this->db->bind('denda', $p[6]);
                $this->db->bind('piutang', $p[7]);
                $this->db->bind('jasa', $p[8]);
                $this->db->bind('ket', $p[9]);
                $this->db->execute();
            }
        }
    }

    /**
     * Otomatis membuatkan Akun COA Simpan Pinjam jika belum ada di tabel `akun`.
     */
    public function seedBaganAkun($tenant_id) {
        $coaList = [
            ['kode' => '1-10500', 'nama' => 'Piutang Pinjaman Anggota', 'tipe' => 'Piutang', 'saldo' => 'Debit'],
            ['kode' => '1-10590', 'nama' => 'Cadangan Kerugian Piutang Pinjaman', 'tipe' => 'Detail', 'saldo' => 'Kredit'],
            ['kode' => '2-10400', 'nama' => 'Simpanan Sukarela / Tabungan Anggota', 'tipe' => 'Kewajiban Lancar Lainnya', 'saldo' => 'Kredit'],
            ['kode' => '2-10410', 'nama' => 'Simpanan Berjangka', 'tipe' => 'Kewajiban Lancar Lainnya', 'saldo' => 'Kredit'],
            ['kode' => '2-10420', 'nama' => 'Simpanan Wajib Anggota', 'tipe' => 'Kewajiban Lancar Lainnya', 'saldo' => 'Kredit'],
            ['kode' => '3-10100', 'nama' => 'Simpanan Pokok Anggota', 'tipe' => 'Ekuitas', 'saldo' => 'Kredit'],
            ['kode' => '4-20100', 'nama' => 'Pendapatan Jasa / Bunga Pinjaman', 'tipe' => 'Pendapatan', 'saldo' => 'Kredit'],
            ['kode' => '4-20200', 'nama' => 'Pendapatan Administrasi & Provisi Pinjaman', 'tipe' => 'Pendapatan', 'saldo' => 'Kredit'],
            ['kode' => '4-20300', 'nama' => 'Pendapatan Denda Keterlambatan', 'tipe' => 'Pendapatan', 'saldo' => 'Kredit'],
            ['kode' => '5-20100', 'nama' => 'Beban Bunga / Jasa Simpanan Anggota', 'tipe' => 'Beban', 'saldo' => 'Debit']
        ];

        $inserted = 0;
        foreach ($coaList as $c) {
            $this->db->query("SELECT 1 FROM akun WHERE kode_akun = :kode AND tenant_id = :tenant_id");
            $this->db->bind('kode', $c['kode']);
            $this->db->bind('tenant_id', $tenant_id);
            if (!$this->db->single()) {
                $this->db->query("INSERT INTO akun (tenant_id, kode_akun, nama_akun, tipe_akun, saldo_normal, posisi_saldo_normal, saldo_awal) 
                                  VALUES (:tenant_id, :kode, :nama, :tipe, :saldo, :posisi, 0.00)");
                $this->db->bind('tenant_id', $tenant_id);
                $this->db->bind('kode', $c['kode']);
                $this->db->bind('nama', $c['nama']);
                $this->db->bind('tipe', $c['tipe']);
                $this->db->bind('saldo', $c['saldo']);
                $this->db->bind('posisi', $c['saldo']);
                $this->db->execute();
                $inserted++;
            }
        }
        return $inserted;
    }

    // ==========================================
    // PENGATURAN STATUS & PARAMETER COA
    // ==========================================

    public function getPengaturan($tenant_id) {
        $this->db->query("SELECT * FROM sp_pengaturan WHERE tenant_id = :tenant_id");
        $this->db->bind('tenant_id', $tenant_id);
        $res = $this->db->single();
        if (!$res) {
            $this->initDefaultPengaturan($tenant_id);
            $this->db->query("SELECT * FROM sp_pengaturan WHERE tenant_id = :tenant_id");
            $this->db->bind('tenant_id', $tenant_id);
            $res = $this->db->single();
        }
        return $res;
    }

    public function isModulAktif($tenant_id) {
        $p = $this->getPengaturan($tenant_id);
        return !empty($p['status_aktif']);
    }

    public function toggleStatusAktif($tenant_id) {
        $curr = $this->getPengaturan($tenant_id);
        $newStatus = empty($curr['status_aktif']) ? 1 : 0;
        $this->db->query("UPDATE sp_pengaturan SET status_aktif = :status WHERE tenant_id = :tenant_id");
        $this->db->bind('status', $newStatus);
        $this->db->bind('tenant_id', $tenant_id);
        $this->db->execute();
        return $newStatus;
    }

    public function simpanPengaturan($data, $tenant_id) {
        $query = "UPDATE sp_pengaturan SET 
                    status_aktif = :status_aktif,
                    nama_unit_sp = :nama_unit_sp,
                    akun_kas_default = :akun_kas,
                    akun_piutang_pinjaman = :akun_piutang,
                    akun_cadangan_kerugian = :akun_cadangan,
                    akun_simpanan_pokok = :akun_pokok,
                    akun_simpanan_wajib = :akun_wajib,
                    akun_simpanan_sukarela = :akun_sukarela,
                    akun_pendapatan_jasa = :akun_jasa,
                    akun_pendapatan_admin = :akun_admin,
                    akun_pendapatan_denda = :akun_denda,
                    akun_beban_bunga_simpanan = :akun_beban,
                    posting_jurnal_otomatis = :posting_jurnal
                  WHERE tenant_id = :tenant_id";
        
        $this->db->query($query);
        $this->db->bind('status_aktif', isset($data['status_aktif']) ? 1 : 0);
        $this->db->bind('nama_unit_sp', $data['nama_unit_sp'] ?? 'Unit Usaha Simpan Pinjam');
        $this->db->bind('akun_kas', $data['akun_kas_default'] ?? null);
        $this->db->bind('akun_piutang', $data['akun_piutang_pinjaman'] ?? null);
        $this->db->bind('akun_cadangan', $data['akun_cadangan_kerugian'] ?? null);
        $this->db->bind('akun_pokok', $data['akun_simpanan_pokok'] ?? null);
        $this->db->bind('akun_wajib', $data['akun_simpanan_wajib'] ?? null);
        $this->db->bind('akun_sukarela', $data['akun_simpanan_sukarela'] ?? null);
        $this->db->bind('akun_jasa', $data['akun_pendapatan_jasa'] ?? null);
        $this->db->bind('akun_admin', $data['akun_pendapatan_admin'] ?? null);
        $this->db->bind('akun_denda', $data['akun_pendapatan_denda'] ?? null);
        $this->db->bind('akun_beban', $data['akun_beban_bunga_simpanan'] ?? null);
        $this->db->bind('posting_jurnal', isset($data['posting_jurnal_otomatis']) ? 1 : 0);
        $this->db->bind('tenant_id', $tenant_id);
        $this->db->execute();
        return $this->db->rowCount();
    }

    // ==========================================
    // STATISTIK & RINGKASAN DASHBOARD
    // ==========================================

    public function getDashboardStats($tenant_id) {
        // Total Nasabah
        $this->db->query("SELECT 
                            COUNT(*) as total_nasabah,
                            SUM(CASE WHEN jenis_kelamin = 'L' THEN 1 ELSE 0 END) as nasabah_laki,
                            SUM(CASE WHEN jenis_kelamin = 'P' THEN 1 ELSE 0 END) as nasabah_perempuan
                          FROM sp_nasabah WHERE tenant_id = :tenant_id AND status = 'Aktif'");
        $this->db->bind('tenant_id', $tenant_id);
        $nasabahStat = $this->db->single();

        // Total Kelompok
        $this->db->query("SELECT COUNT(*) as total_kelompok FROM sp_kelompok WHERE tenant_id = :tenant_id AND status = 'Aktif'");
        $this->db->bind('tenant_id', $tenant_id);
        $kelompokStat = $this->db->single();

        // Total Saldo Simpanan
        $this->db->query("SELECT COALESCE(SUM(saldo), 0) as total_simpanan FROM sp_rekening_simpanan WHERE tenant_id = :tenant_id AND status = 'Aktif'");
        $this->db->bind('tenant_id', $tenant_id);
        $simpananStat = $this->db->single();

        // Total Pinjaman Dicairkan & Outstanding
        $this->db->query("SELECT 
                            COUNT(*) as total_rekening_pinjaman,
                            COALESCE(SUM(jumlah_disetujui), 0) as total_penyaluran,
                            COALESCE(SUM(sisa_pokok), 0) as total_outstanding,
                            COALESCE(SUM(sisa_jasa), 0) as total_jasa_outstanding
                          FROM sp_pinjaman WHERE tenant_id = :tenant_id AND status = 'Dicairkan'");
        $this->db->bind('tenant_id', $tenant_id);
        $pinjamanStat = $this->db->single();

        // Pengajuan Menunggu Persetujuan
        $this->db->query("SELECT COUNT(*) as pending_count, COALESCE(SUM(jumlah_pengajuan), 0) as pending_nominal 
                          FROM sp_pinjaman WHERE tenant_id = :tenant_id AND status = 'Diajukan'");
        $this->db->bind('tenant_id', $tenant_id);
        $pendingStat = $this->db->single();

        return [
            'total_nasabah' => $nasabahStat['total_nasabah'] ?? 0,
            'total_kelompok' => $kelompokStat['total_kelompok'] ?? 0,
            'total_simpanan' => $simpananStat['total_simpanan'] ?? 0,
            'total_penyaluran' => $pinjamanStat['total_penyaluran'] ?? 0,
            'total_outstanding' => $pinjamanStat['total_outstanding'] ?? 0,
            'total_jasa_outstanding' => $pinjamanStat['total_jasa_outstanding'] ?? 0,
            'pending_pinjaman_count' => $pendingStat['pending_count'] ?? 0,
            'pending_pinjaman_nominal' => $pendingStat['pending_nominal'] ?? 0
        ];
    }

    // ==========================================
    // DATA NASABAH (PERORANGAN)
    // ==========================================

    public function getAllNasabah($tenant_id, $id_kelompok = null) {
        $query = "SELECT n.*, k.nama_kelompok, k.kode_kelompok 
                  FROM sp_nasabah n
                  LEFT JOIN sp_kelompok k ON n.id_kelompok = k.id_kelompok
                  WHERE n.tenant_id = :tenant_id";
        if ($id_kelompok) {
            $query .= " AND n.id_kelompok = :id_kelompok";
        }
        $query .= " ORDER BY n.no_anggota ASC";
        $this->db->query($query);
        $this->db->bind('tenant_id', $tenant_id);
        if ($id_kelompok) $this->db->bind('id_kelompok', $id_kelompok);
        return $this->db->resultSet();
    }

    public function getNasabahById($id, $tenant_id) {
        $this->db->query("SELECT n.*, k.nama_kelompok, k.kode_kelompok 
                          FROM sp_nasabah n
                          LEFT JOIN sp_kelompok k ON n.id_kelompok = k.id_kelompok
                          WHERE n.id_nasabah = :id AND n.tenant_id = :tenant_id");
        $this->db->bind('id', $id);
        $this->db->bind('tenant_id', $tenant_id);
        return $this->db->single();
    }

    public function generateNoAnggota($tenant_id) {
        $prefix = 'AGT-' . date('Ym') . '-';
        $this->db->query("SELECT no_anggota FROM sp_nasabah WHERE tenant_id = :tenant_id AND no_anggota LIKE :prefix ORDER BY id_nasabah DESC LIMIT 1");
        $this->db->bind('tenant_id', $tenant_id);
        $this->db->bind('prefix', $prefix . '%');
        $res = $this->db->single();
        if ($res) {
            $lastNum = (int)substr($res['no_anggota'], -4);
            return $prefix . str_pad($lastNum + 1, 4, '0', STR_PAD_LEFT);
        }
        return $prefix . '0001';
    }

    public function tambahNasabah($data, $tenant_id) {
        $no_anggota = !empty($data['no_anggota']) ? $data['no_anggota'] : $this->generateNoAnggota($tenant_id);
        $id_kelompok = !empty($data['id_kelompok']) ? $data['id_kelompok'] : null;

        $query = "INSERT INTO sp_nasabah (tenant_id, id_kelompok, peran_kelompok, no_anggota, nik, nama_lengkap, jenis_kelamin, tempat_lahir, tanggal_lahir, alamat, no_telp, pekerjaan, status, tanggal_daftar, catatan)
                  VALUES (:tenant_id, :id_kelompok, :peran_kelompok, :no_anggota, :nik, :nama_lengkap, :jenis_kelamin, :tempat_lahir, :tanggal_lahir, :alamat, :no_telp, :pekerjaan, :status, :tanggal_daftar, :catatan)";
        $this->db->query($query);
        $this->db->bind('tenant_id', $tenant_id);
        $this->db->bind('id_kelompok', $id_kelompok);
        $this->db->bind('peran_kelompok', $data['peran_kelompok'] ?? 'Anggota');
        $this->db->bind('no_anggota', $no_anggota);
        $this->db->bind('nik', $data['nik'] ?? null);
        $this->db->bind('nama_lengkap', $data['nama_lengkap']);
        $this->db->bind('jenis_kelamin', $data['jenis_kelamin'] ?? 'L');
        $this->db->bind('tempat_lahir', $data['tempat_lahir'] ?? null);
        $this->db->bind('tanggal_lahir', !empty($data['tanggal_lahir']) ? $data['tanggal_lahir'] : null);
        $this->db->bind('alamat', $data['alamat'] ?? null);
        $this->db->bind('no_telp', $data['no_telp'] ?? null);
        $this->db->bind('pekerjaan', $data['pekerjaan'] ?? null);
        $this->db->bind('status', $data['status'] ?? 'Aktif');
        $this->db->bind('tanggal_daftar', !empty($data['tanggal_daftar']) ? $data['tanggal_daftar'] : date('Y-m-d'));
        $this->db->bind('catatan', $data['catatan'] ?? null);
        $this->db->execute();
        return $this->db->rowCount();
    }

    public function ubahNasabah($data, $tenant_id) {
        $id_kelompok = !empty($data['id_kelompok']) ? $data['id_kelompok'] : null;
        $query = "UPDATE sp_nasabah SET 
                    id_kelompok = :id_kelompok,
                    peran_kelompok = :peran_kelompok,
                    no_anggota = :no_anggota,
                    nik = :nik,
                    nama_lengkap = :nama_lengkap,
                    jenis_kelamin = :jenis_kelamin,
                    tempat_lahir = :tempat_lahir,
                    tanggal_lahir = :tanggal_lahir,
                    alamat = :alamat,
                    no_telp = :no_telp,
                    pekerjaan = :pekerjaan,
                    status = :status,
                    tanggal_daftar = :tanggal_daftar,
                    catatan = :catatan
                  WHERE id_nasabah = :id AND tenant_id = :tenant_id";
        $this->db->query($query);
        $this->db->bind('id_kelompok', $id_kelompok);
        $this->db->bind('peran_kelompok', $data['peran_kelompok'] ?? 'Anggota');
        $this->db->bind('no_anggota', $data['no_anggota']);
        $this->db->bind('nik', $data['nik'] ?? null);
        $this->db->bind('nama_lengkap', $data['nama_lengkap']);
        $this->db->bind('jenis_kelamin', $data['jenis_kelamin'] ?? 'L');
        $this->db->bind('tempat_lahir', $data['tempat_lahir'] ?? null);
        $this->db->bind('tanggal_lahir', !empty($data['tanggal_lahir']) ? $data['tanggal_lahir'] : null);
        $this->db->bind('alamat', $data['alamat'] ?? null);
        $this->db->bind('no_telp', $data['no_telp'] ?? null);
        $this->db->bind('pekerjaan', $data['pekerjaan'] ?? null);
        $this->db->bind('status', $data['status'] ?? 'Aktif');
        $this->db->bind('tanggal_daftar', !empty($data['tanggal_daftar']) ? $data['tanggal_daftar'] : date('Y-m-d'));
        $this->db->bind('catatan', $data['catatan'] ?? null);
        $this->db->bind('id', $data['id_nasabah']);
        $this->db->bind('tenant_id', $tenant_id);
        $this->db->execute();
        return $this->db->rowCount();
    }

    public function hapusNasabah($id, $tenant_id) {
        // Cek apakah nasabah memiliki rekening simpanan aktif atau pinjaman aktif
        $this->db->query("SELECT 1 FROM sp_pinjaman WHERE id_nasabah = :id AND tenant_id = :tenant_id AND status IN ('Diajukan','Disetujui','Dicairkan')");
        $this->db->bind('id', $id);
        $this->db->bind('tenant_id', $tenant_id);
        if ($this->db->single()) {
            return -1; // Ada pinjaman aktif
        }

        $this->db->query("SELECT 1 FROM sp_rekening_simpanan WHERE id_nasabah = :id AND tenant_id = :tenant_id AND saldo > 0");
        $this->db->bind('id', $id);
        $this->db->bind('tenant_id', $tenant_id);
        if ($this->db->single()) {
            return -2; // Masih ada saldo simpanan
        }

        $this->db->query("DELETE FROM sp_nasabah WHERE id_nasabah = :id AND tenant_id = :tenant_id");
        $this->db->bind('id', $id);
        $this->db->bind('tenant_id', $tenant_id);
        $this->db->execute();
        return $this->db->rowCount();
    }

    // ==========================================
    // DATA KELOMPOK (DENGAN ANGGOTA)
    // ==========================================

    public function getAllKelompok($tenant_id) {
        $this->db->query("SELECT k.*, COUNT(n.id_nasabah) as jumlah_anggota 
                          FROM sp_kelompok k
                          LEFT JOIN sp_nasabah n ON k.id_kelompok = n.id_kelompok AND n.status = 'Aktif'
                          WHERE k.tenant_id = :tenant_id
                          GROUP BY k.id_kelompok
                          ORDER BY k.nama_kelompok ASC");
        $this->db->bind('tenant_id', $tenant_id);
        return $this->db->resultSet();
    }

    public function getKelompokById($id, $tenant_id) {
        $this->db->query("SELECT k.*, COUNT(n.id_nasabah) as jumlah_anggota 
                          FROM sp_kelompok k
                          LEFT JOIN sp_nasabah n ON k.id_kelompok = n.id_kelompok AND n.status = 'Aktif'
                          WHERE k.id_kelompok = :id AND k.tenant_id = :tenant_id
                          GROUP BY k.id_kelompok");
        $this->db->bind('id', $id);
        $this->db->bind('tenant_id', $tenant_id);
        $kelompok = $this->db->single();
        if ($kelompok) {
            $kelompok['anggota'] = $this->getAllNasabah($tenant_id, $id);
        }
        return $kelompok;
    }

    public function generateKodeKelompok($tenant_id) {
        $prefix = 'KLP-' . date('Y') . '-';
        $this->db->query("SELECT kode_kelompok FROM sp_kelompok WHERE tenant_id = :tenant_id AND kode_kelompok LIKE :prefix ORDER BY id_kelompok DESC LIMIT 1");
        $this->db->bind('tenant_id', $tenant_id);
        $this->db->bind('prefix', $prefix . '%');
        $res = $this->db->single();
        if ($res) {
            $lastNum = (int)substr($res['kode_kelompok'], -3);
            return $prefix . str_pad($lastNum + 1, 3, '0', STR_PAD_LEFT);
        }
        return $prefix . '001';
    }

    public function tambahKelompok($data, $tenant_id) {
        $kode = !empty($data['kode_kelompok']) ? $data['kode_kelompok'] : $this->generateKodeKelompok($tenant_id);
        $query = "INSERT INTO sp_kelompok (tenant_id, kode_kelompok, nama_kelompok, nama_ketua, kontak, alamat_wilayah, tanggal_berdiri, status, keterangan)
                  VALUES (:tenant_id, :kode, :nama, :ketua, :kontak, :alamat, :tgl, :status, :ket)";
        $this->db->query($query);
        $this->db->bind('tenant_id', $tenant_id);
        $this->db->bind('kode', $kode);
        $this->db->bind('nama', $data['nama_kelompok']);
        $this->db->bind('ketua', $data['nama_ketua'] ?? null);
        $this->db->bind('kontak', $data['kontak'] ?? null);
        $this->db->bind('alamat', $data['alamat_wilayah'] ?? null);
        $this->db->bind('tgl', !empty($data['tanggal_berdiri']) ? $data['tanggal_berdiri'] : date('Y-m-d'));
        $this->db->bind('status', $data['status'] ?? 'Aktif');
        $this->db->bind('ket', $data['keterangan'] ?? null);
        $this->db->execute();
        return $this->db->rowCount();
    }

    public function ubahKelompok($data, $tenant_id) {
        $query = "UPDATE sp_kelompok SET 
                    nama_kelompok = :nama,
                    nama_ketua = :ketua,
                    kontak = :kontak,
                    alamat_wilayah = :alamat,
                    tanggal_berdiri = :tgl,
                    status = :status,
                    keterangan = :ket
                  WHERE id_kelompok = :id AND tenant_id = :tenant_id";
        $this->db->query($query);
        $this->db->bind('nama', $data['nama_kelompok']);
        $this->db->bind('ketua', $data['nama_ketua'] ?? null);
        $this->db->bind('kontak', $data['kontak'] ?? null);
        $this->db->bind('alamat', $data['alamat_wilayah'] ?? null);
        $this->db->bind('tgl', !empty($data['tanggal_berdiri']) ? $data['tanggal_berdiri'] : date('Y-m-d'));
        $this->db->bind('status', $data['status'] ?? 'Aktif');
        $this->db->bind('ket', $data['keterangan'] ?? null);
        $this->db->bind('id', $data['id_kelompok']);
        $this->db->bind('tenant_id', $tenant_id);
        $this->db->execute();
        return $this->db->rowCount();
    }

    public function assignAnggotaKelompok($id_nasabah, $id_kelompok, $peran, $tenant_id) {
        $this->db->query("UPDATE sp_nasabah SET id_kelompok = :id_kelompok, peran_kelompok = :peran WHERE id_nasabah = :id AND tenant_id = :tenant_id");
        $this->db->bind('id_kelompok', $id_kelompok);
        $this->db->bind('peran', $peran);
        $this->db->bind('id', $id_nasabah);
        $this->db->bind('tenant_id', $tenant_id);
        $this->db->execute();
        return $this->db->rowCount();
    }

    public function removeAnggotaKelompok($id_nasabah, $tenant_id) {
        $this->db->query("UPDATE sp_nasabah SET id_kelompok = NULL, peran_kelompok = 'Anggota' WHERE id_nasabah = :id AND tenant_id = :tenant_id");
        $this->db->bind('id', $id_nasabah);
        $this->db->bind('tenant_id', $tenant_id);
        $this->db->execute();
        return $this->db->rowCount();
    }

    public function hapusKelompok($id, $tenant_id) {
        // Cek apakah ada pinjaman kelompok yang aktif
        $this->db->query("SELECT 1 FROM sp_pinjaman WHERE id_kelompok = :id AND tenant_id = :tenant_id AND status IN ('Diajukan','Disetujui','Dicairkan')");
        $this->db->bind('id', $id);
        $this->db->bind('tenant_id', $tenant_id);
        if ($this->db->single()) {
            return -1; // Masih ada pinjaman kelompok aktif
        }

        // Lepaskan semua anggota dari kelompok ini
        $this->db->query("UPDATE sp_nasabah SET id_kelompok = NULL WHERE id_kelompok = :id AND tenant_id = :tenant_id");
        $this->db->bind('id', $id);
        $this->db->bind('tenant_id', $tenant_id);
        $this->db->execute();

        // Hapus kelompok
        $this->db->query("DELETE FROM sp_kelompok WHERE id_kelompok = :id AND tenant_id = :tenant_id");
        $this->db->bind('id', $id);
        $this->db->bind('tenant_id', $tenant_id);
        $this->db->execute();
        return $this->db->rowCount();
    }

    // ==========================================
    // SIMPANAN (PRODUK, REKENING, TRANSAKSI)
    // ==========================================

    public function getAllJenisSimpanan($tenant_id) {
        $this->db->query("SELECT js.*, a.nama_akun 
                          FROM sp_jenis_simpanan js
                          LEFT JOIN akun a ON js.kode_akun = a.kode_akun AND a.tenant_id = js.tenant_id
                          WHERE js.tenant_id = :tenant_id
                          ORDER BY js.tipe_simpanan ASC, js.nama_simpanan ASC");
        $this->db->bind('tenant_id', $tenant_id);
        return $this->db->resultSet();
    }

    public function tambahJenisSimpanan($data, $tenant_id) {
        $query = "INSERT INTO sp_jenis_simpanan (tenant_id, kode_simpanan, nama_simpanan, tipe_simpanan, nominal_minimal_setor, bunga_pertahun, kode_akun, status, keterangan)
                  VALUES (:tenant_id, :kode, :nama, :tipe, :nominal, :bunga, :akun, :status, :ket)";
        $this->db->query($query);
        $this->db->bind('tenant_id', $tenant_id);
        $this->db->bind('kode', $data['kode_simpanan']);
        $this->db->bind('nama', $data['nama_simpanan']);
        $this->db->bind('tipe', $data['tipe_simpanan']);
        $this->db->bind('nominal', (float)($data['nominal_minimal_setor'] ?? 0));
        $this->db->bind('bunga', (float)($data['bunga_pertahun'] ?? 0));
        $this->db->bind('akun', !empty($data['kode_akun']) ? $data['kode_akun'] : null);
        $this->db->bind('status', $data['status'] ?? 'Aktif');
        $this->db->bind('ket', $data['keterangan'] ?? null);
        $this->db->execute();
        return $this->db->rowCount();
    }

    public function hapusJenisSimpanan($id, $tenant_id) {
        $this->db->query("SELECT 1 FROM sp_rekening_simpanan WHERE id_jenis_simpanan = :id AND tenant_id = :tenant_id");
        $this->db->bind('id', $id);
        $this->db->bind('tenant_id', $tenant_id);
        if ($this->db->single()) {
            return -1; // Sudah ada rekening yang menggunakan jenis simpanan ini
        }
        $this->db->query("DELETE FROM sp_jenis_simpanan WHERE id_jenis_simpanan = :id AND tenant_id = :tenant_id");
        $this->db->bind('id', $id);
        $this->db->bind('tenant_id', $tenant_id);
        $this->db->execute();
        return $this->db->rowCount();
    }

    public function getAllRekeningSimpanan($tenant_id, $id_nasabah = null) {
        $query = "SELECT r.*, n.nama_lengkap as nama_nasabah, n.no_anggota, js.nama_simpanan, js.tipe_simpanan, js.kode_akun as akun_simpanan
                  FROM sp_rekening_simpanan r
                  JOIN sp_nasabah n ON r.id_nasabah = n.id_nasabah
                  JOIN sp_jenis_simpanan js ON r.id_jenis_simpanan = js.id_jenis_simpanan
                  WHERE r.tenant_id = :tenant_id";
        if ($id_nasabah) {
            $query .= " AND r.id_nasabah = :id_nasabah";
        }
        $query .= " ORDER BY r.tanggal_buka DESC, r.no_rekening DESC";
        $this->db->query($query);
        $this->db->bind('tenant_id', $tenant_id);
        if ($id_nasabah) $this->db->bind('id_nasabah', $id_nasabah);
        return $this->db->resultSet();
    }

    public function getRekeningById($id, $tenant_id) {
        $this->db->query("SELECT r.*, n.nama_lengkap as nama_nasabah, n.no_anggota, n.no_telp, js.nama_simpanan, js.tipe_simpanan, js.kode_akun as akun_simpanan
                          FROM sp_rekening_simpanan r
                          JOIN sp_nasabah n ON r.id_nasabah = n.id_nasabah
                          JOIN sp_jenis_simpanan js ON r.id_jenis_simpanan = js.id_jenis_simpanan
                          WHERE r.id_rekening = :id AND r.tenant_id = :tenant_id");
        $this->db->bind('id', $id);
        $this->db->bind('tenant_id', $tenant_id);
        return $this->db->single();
    }

    public function generateNoRekening($tenant_id, $tipe = 'S') {
        $prefix = 'REK-' . date('ym') . '-';
        $this->db->query("SELECT no_rekening FROM sp_rekening_simpanan WHERE tenant_id = :tenant_id AND no_rekening LIKE :prefix ORDER BY id_rekening DESC LIMIT 1");
        $this->db->bind('tenant_id', $tenant_id);
        $this->db->bind('prefix', $prefix . '%');
        $res = $this->db->single();
        if ($res) {
            $lastNum = (int)substr($res['no_rekening'], -4);
            return $prefix . str_pad($lastNum + 1, 4, '0', STR_PAD_LEFT);
        }
        return $prefix . '0001';
    }

    public function bukaRekening($data, $tenant_id) {
        $no_rek = !empty($data['no_rekening']) ? $data['no_rekening'] : $this->generateNoRekening($tenant_id);
        $setoran_awal = (float)($data['setoran_awal'] ?? 0);

        $this->db->beginTransaction();
        try {
            $this->db->query("INSERT INTO sp_rekening_simpanan (tenant_id, no_rekening, id_nasabah, id_jenis_simpanan, saldo, tanggal_buka, status)
                              VALUES (:tenant_id, :no_rek, :nasabah, :jenis, 0.00, :tgl, 'Aktif')");
            $this->db->bind('tenant_id', $tenant_id);
            $this->db->bind('no_rek', $no_rek);
            $this->db->bind('nasabah', $data['id_nasabah']);
            $this->db->bind('jenis', $data['id_jenis_simpanan']);
            $this->db->bind('tgl', !empty($data['tanggal_buka']) ? $data['tanggal_buka'] : date('Y-m-d'));
            $this->db->execute();
            $id_rekening = $this->db->lastInsertId();

            // Jika ada setoran awal
            if ($setoran_awal > 0) {
                $this->prosesTransaksiSimpanan([
                    'id_rekening' => $id_rekening,
                    'jenis_transaksi' => 'Setor',
                    'nominal' => $setoran_awal,
                    'tanggal' => !empty($data['tanggal_buka']) ? $data['tanggal_buka'] : date('Y-m-d'),
                    'kode_akun_kas' => $data['kode_akun_kas'] ?? '1-10001',
                    'keterangan' => 'Setoran awal pembukaan rekening ' . $no_rek
                ], $tenant_id);
            }

            $this->db->commit();
            return $id_rekening;
        } catch (\Exception $e) {
            $this->db->rollBack();
            throw $e;
        }
    }

    public function generateNoTransaksiSimpanan($tenant_id) {
        $prefix = 'TRX-SP-' . date('ymd') . '-';
        $this->db->query("SELECT no_transaksi FROM sp_transaksi_simpanan WHERE tenant_id = :tenant_id AND no_transaksi LIKE :prefix ORDER BY id_transaksi DESC LIMIT 1");
        $this->db->bind('tenant_id', $tenant_id);
        $this->db->bind('prefix', $prefix . '%');
        $res = $this->db->single();
        if ($res) {
            $lastNum = (int)substr($res['no_transaksi'], -4);
            return $prefix . str_pad($lastNum + 1, 4, '0', STR_PAD_LEFT);
        }
        return $prefix . '0001';
    }

    public function prosesTransaksiSimpanan($data, $tenant_id) {
        $rek = $this->getRekeningById($data['id_rekening'], $tenant_id);
        if (!$rek) {
            throw new \Exception("Rekening simpanan tidak ditemukan.");
        }

        $nominal = (float)$data['nominal'];
        if ($nominal <= 0) {
            throw new \Exception("Nominal transaksi harus lebih besar dari 0.");
        }

        $jenis = $data['jenis_transaksi']; // Setor / Tarik
        $saldoSebelum = (float)$rek['saldo'];

        if ($jenis === 'Tarik' && $nominal > $saldoSebelum) {
            throw new \Exception("Saldo tidak mencukupi untuk melakukan penarikan! Saldo saat ini: Rp " . number_format($saldoSebelum, 0, ',', '.'));
        }

        $saldoSesudah = ($jenis === 'Setor') ? ($saldoSebelum + $nominal) : ($saldoSebelum - $nominal);
        $no_transaksi = !empty($data['no_transaksi']) ? $data['no_transaksi'] : $this->generateNoTransaksiSimpanan($tenant_id);
        $tgl = !empty($data['tanggal']) ? $data['tanggal'] : date('Y-m-d');
        $kode_kas = $data['kode_akun_kas'] ?? '1-10001';

        // 1. Simpan Transaksi Simpanan
        $this->db->query("INSERT INTO sp_transaksi_simpanan (tenant_id, no_transaksi, id_rekening, jenis_transaksi, tanggal, nominal, saldo_sebelum, saldo_sesudah, kode_akun_kas, keterangan, created_by)
                          VALUES (:tenant_id, :no_trx, :rek, :jenis, :tgl, :nominal, :s_seb, :s_ses, :kas, :ket, :uid)");
        $this->db->bind('tenant_id', $tenant_id);
        $this->db->bind('no_trx', $no_transaksi);
        $this->db->bind('rek', $data['id_rekening']);
        $this->db->bind('jenis', $jenis);
        $this->db->bind('tgl', $tgl);
        $this->db->bind('nominal', $nominal);
        $this->db->bind('s_seb', $saldoSebelum);
        $this->db->bind('s_ses', $saldoSesudah);
        $this->db->bind('kas', $kode_kas);
        $this->db->bind('ket', $data['keterangan'] ?? '');
        $userId = (class_exists('Auth') && method_exists('Auth', 'user') && Auth::user()) ? (Auth::user()['id'] ?? 1) : 1;
        $this->db->bind('uid', $userId);
        $this->db->execute();
        $id_transaksi = $this->db->lastInsertId();

        // 2. Update Saldo Rekening
        $this->db->query("UPDATE sp_rekening_simpanan SET saldo = :saldo WHERE id_rekening = :rek AND tenant_id = :tenant_id");
        $this->db->bind('saldo', $saldoSesudah);
        $this->db->bind('rek', $data['id_rekening']);
        $this->db->bind('tenant_id', $tenant_id);
        $this->db->execute();

        // 3. Posting Jurnal Umum Otomatis (jika aktif)
        $setting = $this->getPengaturan($tenant_id);
        if (!empty($setting['posting_jurnal_otomatis'])) {
            $akunSimpanan = !empty($rek['akun_simpanan']) ? $rek['akun_simpanan'] : ($setting['akun_simpanan_sukarela'] ?? '2-10400');
            
            // Jurnal Simpanan
            $jurnalData = [
                'no_transaksi' => $no_transaksi,
                'tanggal' => $tgl,
                'deskripsi' => "Simpanan {$jenis} - Rek {$rek['no_rekening']} ({$rek['nama_nasabah']}) - " . ($data['keterangan'] ?? ''),
                'sumber_jurnal' => 'Simpan Pinjam',
                'details' => []
            ];

            if ($jenis === 'Setor') {
                // Debit Kas, Kredit Simpanan (Kewajiban)
                $jurnalData['details'][] = ['kode_akun' => $kode_kas, 'debit' => $nominal, 'kredit' => 0];
                $jurnalData['details'][] = ['kode_akun' => $akunSimpanan, 'debit' => 0, 'kredit' => $nominal];
            } else {
                // Tarik: Debit Simpanan, Kredit Kas
                $jurnalData['details'][] = ['kode_akun' => $akunSimpanan, 'debit' => $nominal, 'kredit' => 0];
                $jurnalData['details'][] = ['kode_akun' => $kode_kas, 'debit' => 0, 'kredit' => $nominal];
            }

            try {
                $jurnalModel = new Jurnal_model($this->db);
                $id_jurnal = $jurnalModel->simpanJurnal($jurnalData, $tenant_id);
                // Update id_jurnal di transaksi
                $this->db->query("UPDATE sp_transaksi_simpanan SET id_jurnal = :id_jurnal WHERE id_transaksi = :id");
                $this->db->bind('id_jurnal', $id_jurnal);
                $this->db->bind('id', $id_transaksi);
                $this->db->execute();
            } catch (\Exception $e) {
                // Abaikan error posting jurnal agar transaksi simpanan tetap tercatat
            }
        }

        return $id_transaksi;
    }

    public function getRiwayatTransaksiSimpanan($tenant_id, $id_rekening = null, $tglMulai = null, $tglSelesai = null) {
        $query = "SELECT t.*, r.no_rekening, n.nama_lengkap as nama_nasabah, n.no_anggota, js.nama_simpanan
                  FROM sp_transaksi_simpanan t
                  JOIN sp_rekening_simpanan r ON t.id_rekening = r.id_rekening
                  JOIN sp_nasabah n ON r.id_nasabah = n.id_nasabah
                  JOIN sp_jenis_simpanan js ON r.id_jenis_simpanan = js.id_jenis_simpanan
                  WHERE t.tenant_id = :tenant_id";
        
        if ($id_rekening) {
            $query .= " AND t.id_rekening = :id_rekening";
        }
        if ($tglMulai && $tglSelesai) {
            $query .= " AND t.tanggal BETWEEN :tgl_mulai AND :tgl_selesai";
        }
        $query .= " ORDER BY t.tanggal DESC, t.id_transaksi DESC";

        $this->db->query($query);
        $this->db->bind('tenant_id', $tenant_id);
        if ($id_rekening) $this->db->bind('id_rekening', $id_rekening);
        if ($tglMulai && $tglSelesai) {
            $this->db->bind('tgl_mulai', $tglMulai);
            $this->db->bind('tgl_selesai', $tglSelesai);
        }
        return $this->db->resultSet();
    }

    // ==========================================
    // PINJAMAN (PRODUK, PENGAJUAN, PERSETUJUAN, ANGSURAN)
    // ==========================================

    public function getAllJenisPinjaman($tenant_id) {
        $this->db->query("SELECT jp.*, a1.nama_akun as nama_akun_piutang, a2.nama_akun as nama_akun_jasa
                          FROM sp_jenis_pinjaman jp
                          LEFT JOIN akun a1 ON jp.kode_akun_piutang = a1.kode_akun AND a1.tenant_id = jp.tenant_id
                          LEFT JOIN akun a2 ON jp.kode_akun_pendapatan_jasa = a2.kode_akun AND a2.tenant_id = jp.tenant_id
                          WHERE jp.tenant_id = :tenant_id
                          ORDER BY jp.nama_pinjaman ASC");
        $this->db->bind('tenant_id', $tenant_id);
        return $this->db->resultSet();
    }

    public function tambahJenisPinjaman($data, $tenant_id) {
        $query = "INSERT INTO sp_jenis_pinjaman (tenant_id, kode_pinjaman, nama_pinjaman, sistem_bunga, suku_bunga_persen, satuan_waktu, biaya_admin_persen, denda_keterlambatan_persen, kode_akun_piutang, kode_akun_pendapatan_jasa, status, keterangan)
                  VALUES (:tenant_id, :kode, :nama, :sistem, :bunga, :satuan, :admin, :denda, :piutang, :jasa, :status, :ket)";
        $this->db->query($query);
        $this->db->bind('tenant_id', $tenant_id);
        $this->db->bind('kode', $data['kode_pinjaman']);
        $this->db->bind('nama', $data['nama_pinjaman']);
        $this->db->bind('sistem', $data['sistem_bunga'] ?? 'Flat');
        $this->db->bind('bunga', (float)($data['suku_bunga_persen'] ?? 1.5));
        $this->db->bind('satuan', $data['satuan_waktu'] ?? 'Bulan');
        $this->db->bind('admin', (float)($data['biaya_admin_persen'] ?? 0));
        $this->db->bind('denda', (float)($data['denda_keterlambatan_persen'] ?? 0));
        $this->db->bind('piutang', !empty($data['kode_akun_piutang']) ? $data['kode_akun_piutang'] : null);
        $this->db->bind('jasa', !empty($data['kode_akun_pendapatan_jasa']) ? $data['kode_akun_pendapatan_jasa'] : null);
        $this->db->bind('status', $data['status'] ?? 'Aktif');
        $this->db->bind('ket', $data['keterangan'] ?? null);
        $this->db->execute();
        return $this->db->rowCount();
    }

    public function hapusJenisPinjaman($id, $tenant_id) {
        $this->db->query("SELECT 1 FROM sp_pinjaman WHERE id_jenis_pinjaman = :id AND tenant_id = :tenant_id");
        $this->db->bind('id', $id);
        $this->db->bind('tenant_id', $tenant_id);
        if ($this->db->single()) {
            return -1; // Sudah pernah digunakan pada transaksi pinjaman
        }
        $this->db->query("DELETE FROM sp_jenis_pinjaman WHERE id_jenis_pinjaman = :id AND tenant_id = :tenant_id");
        $this->db->bind('id', $id);
        $this->db->bind('tenant_id', $tenant_id);
        $this->db->execute();
        return $this->db->rowCount();
    }

    public function getAllPinjaman($tenant_id, $status = null) {
        $query = "SELECT p.*, jp.nama_pinjaman, jp.sistem_bunga as sistem_bunga_produk,
                         n.nama_lengkap as nama_nasabah, n.no_anggota, n.no_telp,
                         k.nama_kelompok, k.kode_kelompok
                  FROM sp_pinjaman p
                  JOIN sp_jenis_pinjaman jp ON p.id_jenis_pinjaman = jp.id_jenis_pinjaman
                  LEFT JOIN sp_nasabah n ON p.id_nasabah = n.id_nasabah
                  LEFT JOIN sp_kelompok k ON p.id_kelompok = k.id_kelompok
                  WHERE p.tenant_id = :tenant_id";
        if ($status) {
            $query .= " AND p.status = :status";
        }
        $query .= " ORDER BY p.tanggal_pengajuan DESC, p.id_pinjaman DESC";
        $this->db->query($query);
        $this->db->bind('tenant_id', $tenant_id);
        if ($status) $this->db->bind('status', $status);
        return $this->db->resultSet();
    }

    public function getPinjamanById($id, $tenant_id) {
        $this->db->query("SELECT p.*, jp.nama_pinjaman, jp.kode_akun_piutang, jp.kode_akun_pendapatan_jasa,
                         n.nama_lengkap as nama_nasabah, n.no_anggota, n.no_telp, n.nik, n.alamat,
                         k.nama_kelompok, k.kode_kelompok, k.nama_ketua, k.kontak as kontak_kelompok
                  FROM sp_pinjaman p
                  JOIN sp_jenis_pinjaman jp ON p.id_jenis_pinjaman = jp.id_jenis_pinjaman
                  LEFT JOIN sp_nasabah n ON p.id_nasabah = n.id_nasabah
                  LEFT JOIN sp_kelompok k ON p.id_kelompok = k.id_kelompok
                  WHERE p.id_pinjaman = :id AND p.tenant_id = :tenant_id");
        $this->db->bind('id', $id);
        $this->db->bind('tenant_id', $tenant_id);
        $pinj = $this->db->single();
        if ($pinj) {
            $pinj['jadwal_angsuran'] = $this->getJadwalAngsuranByPinjaman($id, $tenant_id);
        }
        return $pinj;
    }

    public function generateNoPinjaman($tenant_id) {
        $prefix = 'PJM-' . date('ym') . '-';
        $this->db->query("SELECT no_pinjaman FROM sp_pinjaman WHERE tenant_id = :tenant_id AND no_pinjaman LIKE :prefix ORDER BY id_pinjaman DESC LIMIT 1");
        $this->db->bind('tenant_id', $tenant_id);
        $this->db->bind('prefix', $prefix . '%');
        $res = $this->db->single();
        if ($res) {
            $lastNum = (int)substr($res['no_pinjaman'], -4);
            return $prefix . str_pad($lastNum + 1, 4, '0', STR_PAD_LEFT);
        }
        return $prefix . '0001';
    }

    public function ajukanPinjaman($data, $tenant_id) {
        $no_pinj = !empty($data['no_pinjaman']) ? $data['no_pinjaman'] : $this->generateNoPinjaman($tenant_id);
        $tipeDebitur = $data['tipe_debitur'] ?? 'Perorangan';
        $id_nasabah = ($tipeDebitur === 'Perorangan') ? $data['id_nasabah'] : null;
        $id_kelompok = ($tipeDebitur === 'Kelompok') ? $data['id_kelompok'] : null;

        // Ambil info produk pinjaman
        $this->db->query("SELECT * FROM sp_jenis_pinjaman WHERE id_jenis_pinjaman = :id AND tenant_id = :tenant_id");
        $this->db->bind('id', $data['id_jenis_pinjaman']);
        $this->db->bind('tenant_id', $tenant_id);
        $produk = $this->db->single();
        if (!$produk) {
            throw new \Exception("Produk pinjaman tidak ditemukan.");
        }

        $jumlah_pengajuan = (float)$data['jumlah_pengajuan'];
        $jangka_waktu = (int)$data['jangka_waktu'];
        $bunga_persen = (float)($data['suku_bunga'] ?? $produk['suku_bunga_persen']);
        $biaya_admin_persen = (float)$produk['biaya_admin_persen'];
        $biaya_admin = round(($biaya_admin_persen / 100) * $jumlah_pengajuan, 2);

        // Simulasi perhitungan angsuran (Flat default)
        $angsuran_pokok = round($jumlah_pengajuan / $jangka_waktu, 2);
        $angsuran_jasa = round(($bunga_persen / 100) * $jumlah_pengajuan, 2);
        $total_angsuran = $angsuran_pokok + $angsuran_jasa;

        $query = "INSERT INTO sp_pinjaman (tenant_id, no_pinjaman, tipe_debitur, id_nasabah, id_kelompok, id_jenis_pinjaman, tanggal_pengajuan, jumlah_pengajuan, jangka_waktu, suku_bunga, sistem_bunga, biaya_admin, angsuran_pokok_per_periode, angsuran_jasa_per_periode, total_angsuran_per_periode, tujuan_pinjaman, agunan, status)
                  VALUES (:tenant_id, :no_pinj, :tipe, :nasabah, :kelompok, :jenis, :tgl, :nominal, :tenor, :bunga, :sistem, :admin, :a_pokok, :a_jasa, :a_total, :tujuan, :agunan, 'Diajukan')";
        $this->db->query($query);
        $this->db->bind('tenant_id', $tenant_id);
        $this->db->bind('no_pinj', $no_pinj);
        $this->db->bind('tipe', $tipeDebitur);
        $this->db->bind('nasabah', $id_nasabah);
        $this->db->bind('kelompok', $id_kelompok);
        $this->db->bind('jenis', $data['id_jenis_pinjaman']);
        $this->db->bind('tgl', !empty($data['tanggal_pengajuan']) ? $data['tanggal_pengajuan'] : date('Y-m-d'));
        $this->db->bind('nominal', $jumlah_pengajuan);
        $this->db->bind('tenor', $jangka_waktu);
        $this->db->bind('bunga', $bunga_persen);
        $this->db->bind('sistem', $produk['sistem_bunga']);
        $this->db->bind('admin', $biaya_admin);
        $this->db->bind('a_pokok', $angsuran_pokok);
        $this->db->bind('a_jasa', $angsuran_jasa);
        $this->db->bind('a_total', $total_angsuran);
        $this->db->bind('tujuan', $data['tujuan_pinjaman'] ?? null);
        $this->db->bind('agunan', $data['agunan'] ?? null);
        $this->db->execute();
        return $this->db->lastInsertId();
    }

    public function prosesPersetujuanPinjaman($id_pinjaman, $status, $jumlah_disetujui, $catatan, $tenant_id) {
        $pinj = $this->getPinjamanById($id_pinjaman, $tenant_id);
        if (!$pinj) {
            throw new \Exception("Data pinjaman tidak ditemukan.");
        }
        if ($pinj['status'] !== 'Diajukan') {
            throw new \Exception("Pinjaman sudah diproses sebelumnya.");
        }

        $nominalDisetujui = ($status === 'Disetujui') ? (float)$jumlah_disetujui : 0.00;
        $jangka_waktu = (int)$pinj['jangka_waktu'];
        $bunga_persen = (float)$pinj['suku_bunga'];

        // Recalculate based on nominal disetujui
        $angsuran_pokok = $nominalDisetujui > 0 ? round($nominalDisetujui / $jangka_waktu, 2) : 0;
        $angsuran_jasa = $nominalDisetujui > 0 ? round(($bunga_persen / 100) * $nominalDisetujui, 2) : 0;
        $total_angsuran = $angsuran_pokok + $angsuran_jasa;

        $query = "UPDATE sp_pinjaman SET 
                    status = :status,
                    jumlah_disetujui = :nominal_setuju,
                    angsuran_pokok_per_periode = :a_pokok,
                    angsuran_jasa_per_periode = :a_jasa,
                    total_angsuran_per_periode = :a_total,
                    tanggal_persetujuan = :tgl,
                    catatan_persetujuan = :catatan
                  WHERE id_pinjaman = :id AND tenant_id = :tenant_id";
        $this->db->query($query);
        $this->db->bind('status', $status);
        $this->db->bind('nominal_setuju', $nominalDisetujui);
        $this->db->bind('a_pokok', $angsuran_pokok);
        $this->db->bind('a_jasa', $angsuran_jasa);
        $this->db->bind('a_total', $total_angsuran);
        $this->db->bind('tgl', date('Y-m-d'));
        $this->db->bind('catatan', $catatan);
        $this->db->bind('id', $id_pinjaman);
        $this->db->bind('tenant_id', $tenant_id);
        $this->db->execute();
        return $this->db->rowCount();
    }

    public function cairkanPinjaman($id_pinjaman, $kode_akun_kas, $tanggal_cair, $tenant_id) {
        $pinj = $this->getPinjamanById($id_pinjaman, $tenant_id);
        if (!$pinj) {
            throw new \Exception("Pinjaman tidak ditemukan.");
        }
        if ($pinj['status'] !== 'Disetujui') {
            throw new \Exception("Hanya pinjaman berstatus 'Disetujui' yang dapat dicairkan.");
        }

        $nominalCair = (float)$pinj['jumlah_disetujui'];
        $jangka_waktu = (int)$pinj['jangka_waktu'];
        $angsuran_pokok = (float)$pinj['angsuran_pokok_per_periode'];
        $angsuran_jasa = (float)$pinj['angsuran_jasa_per_periode'];
        $total_jasa = round($angsuran_jasa * $jangka_waktu, 2);

        $this->db->beginTransaction();
        try {
            // 1. Generate Jadwal Angsuran
            $tglAcuan = new \DateTime(!empty($tanggal_cair) ? $tanggal_cair : date('Y-m-d'));
            for ($i = 1; $i <= $jangka_waktu; $i++) {
                $tglJatuhTempo = clone $tglAcuan;
                // Default per bulan
                $tglJatuhTempo->modify("+{$i} month");
                $tglJtStr = $tglJatuhTempo->format('Y-m-d');

                $this->db->query("INSERT INTO sp_jadwal_angsuran (tenant_id, id_pinjaman, angsuran_ke, jatuh_tempo, pokok, jasa, total_tagihan, status_bayar)
                                  VALUES (:tenant_id, :pinjaman, :ke, :jt, :pokok, :jasa, :total, 'Belum')");
                $this->db->bind('tenant_id', $tenant_id);
                $this->db->bind('pinjaman', $id_pinjaman);
                $this->db->bind('ke', $i);
                $this->db->bind('jt', $tglJtStr);
                $this->db->bind('pokok', $angsuran_pokok);
                $this->db->bind('jasa', $angsuran_jasa);
                $this->db->bind('total', $angsuran_pokok + $angsuran_jasa);
                $this->db->execute();
            }

            // 2. Update status pinjaman jadi 'Dicairkan'
            $this->db->query("UPDATE sp_pinjaman SET 
                                status = 'Dicairkan',
                                tanggal_pencairan = :tgl_cair,
                                kode_akun_kas_pencairan = :kas,
                                sisa_pokok = :sisa_p,
                                sisa_jasa = :sisa_j
                              WHERE id_pinjaman = :id AND tenant_id = :tenant_id");
            $this->db->bind('tgl_cair', !empty($tanggal_cair) ? $tanggal_cair : date('Y-m-d'));
            $this->db->bind('kas', $kode_akun_kas);
            $this->db->bind('sisa_p', $nominalCair);
            $this->db->bind('sisa_j', $total_jasa);
            $this->db->bind('id', $id_pinjaman);
            $this->db->bind('tenant_id', $tenant_id);
            $this->db->execute();

            // 3. Posting Jurnal Pencairan Pinjaman
            $setting = $this->getPengaturan($tenant_id);
            if (!empty($setting['posting_jurnal_otomatis'])) {
                $akunPiutang = !empty($pinj['kode_akun_piutang']) ? $pinj['kode_akun_piutang'] : ($setting['akun_piutang_pinjaman'] ?? '1-10500');
                $namaDebitur = ($pinj['tipe_debitur'] === 'Kelompok') ? $pinj['nama_kelompok'] : $pinj['nama_nasabah'];
                
                // Debit: Piutang Pinjaman, Kredit: Kas
                $jurnalData = [
                    'no_transaksi' => 'CAIR-' . $pinj['no_pinjaman'],
                    'tanggal' => !empty($tanggal_cair) ? $tanggal_cair : date('Y-m-d'),
                    'deskripsi' => "Pencairan Pinjaman {$pinj['no_pinjaman']} kepada {$namaDebitur}",
                    'sumber_jurnal' => 'Simpan Pinjam',
                    'details' => [
                        ['kode_akun' => $akunPiutang, 'debit' => $nominalCair, 'kredit' => 0],
                        ['kode_akun' => $kode_akun_kas, 'debit' => 0, 'kredit' => $nominalCair]
                    ]
                ];

                try {
                    $jurnalModel = new Jurnal_model($this->db);
                    $id_jurnal = $jurnalModel->simpanJurnal($jurnalData, $tenant_id);
                    $this->db->query("UPDATE sp_pinjaman SET id_jurnal_pencairan = :jurnal WHERE id_pinjaman = :id");
                    $this->db->bind('jurnal', $id_jurnal);
                    $this->db->bind('id', $id_pinjaman);
                    $this->db->execute();
                } catch (\Exception $e) {
                    // Fail-safe jika jurnal gagal, jangan gagalkan pencairan
                }
            }

            $this->db->commit();
            return true;
        } catch (\Exception $e) {
            $this->db->rollBack();
            throw $e;
        }
    }

    public function getJadwalAngsuranByPinjaman($id_pinjaman, $tenant_id) {
        $this->db->query("SELECT * FROM sp_jadwal_angsuran WHERE id_pinjaman = :id AND tenant_id = :tenant_id ORDER BY angsuran_ke ASC");
        $this->db->bind('id', $id_pinjaman);
        $this->db->bind('tenant_id', $tenant_id);
        return $this->db->resultSet();
    }

    public function bayarAngsuran($id_jadwal, $data, $tenant_id) {
        $this->db->query("SELECT j.*, p.no_pinjaman, p.id_pinjaman, p.tipe_debitur, p.sisa_pokok, p.sisa_jasa,
                                 jp.kode_akun_piutang, jp.kode_akun_pendapatan_jasa,
                                 n.nama_lengkap as nama_nasabah, k.nama_kelompok
                          FROM sp_jadwal_angsuran j
                          JOIN sp_pinjaman p ON j.id_pinjaman = p.id_pinjaman
                          JOIN sp_jenis_pinjaman jp ON p.id_jenis_pinjaman = jp.id_jenis_pinjaman
                          LEFT JOIN sp_nasabah n ON p.id_nasabah = n.id_nasabah
                          LEFT JOIN sp_kelompok k ON p.id_kelompok = k.id_kelompok
                          WHERE j.id_jadwal = :id AND j.tenant_id = :tenant_id");
        $this->db->bind('id', $id_jadwal);
        $this->db->bind('tenant_id', $tenant_id);
        $jadwal = $this->db->single();
        if (!$jadwal) {
            throw new \Exception("Data jadwal angsuran tidak ditemukan.");
        }
        if ($jadwal['status_bayar'] === 'Lunas') {
            throw new \Exception("Tagihan angsuran ke-{$jadwal['angsuran_ke']} sudah berstatus Lunas.");
        }

        $tglBayar = !empty($data['tanggal_bayar']) ? $data['tanggal_bayar'] : date('Y-m-d');
        $kodeKas = $data['kode_akun_kas'] ?? '1-10001';
        $denda = (float)($data['denda'] ?? 0);
        $totalBayar = (float)$jadwal['total_tagihan'] + $denda;
        $pokok = (float)$jadwal['pokok'];
        $jasa = (float)$jadwal['jasa'];

        $this->db->beginTransaction();
        try {
            // 1. Update status jadwal jadi Lunas
            $this->db->query("UPDATE sp_jadwal_angsuran SET 
                                status_bayar = 'Lunas',
                                denda = :denda,
                                total_bayar = :bayar,
                                tanggal_bayar = :tgl,
                                kode_akun_kas_bayar = :kas,
                                keterangan = :ket
                              WHERE id_jadwal = :id AND tenant_id = :tenant_id");
            $this->db->bind('denda', $denda);
            $this->db->bind('bayar', $totalBayar);
            $this->db->bind('tgl', $tglBayar);
            $this->db->bind('kas', $kodeKas);
            $this->db->bind('ket', $data['keterangan'] ?? 'Pelunasan Angsuran ke-' . $jadwal['angsuran_ke']);
            $this->db->bind('id', $id_jadwal);
            $this->db->bind('tenant_id', $tenant_id);
            $this->db->execute();

            // 2. Kurangi sisa pokok & sisa jasa pada master pinjaman
            $newSisaPokok = max(0, (float)$jadwal['sisa_pokok'] - $pokok);
            $newSisaJasa = max(0, (float)$jadwal['sisa_jasa'] - $jasa);

            // Cek apakah seluruh angsuran pada pinjaman ini sudah lunas
            $this->db->query("SELECT COUNT(*) as sisa_angsuran FROM sp_jadwal_angsuran WHERE id_pinjaman = :id AND tenant_id = :tenant_id AND status_bayar != 'Lunas'");
            $this->db->bind('id', $jadwal['id_pinjaman']);
            $this->db->bind('tenant_id', $tenant_id);
            $sisaAngsuranRow = $this->db->single();
            $statusPinjaman = ($sisaAngsuranRow && $sisaAngsuranRow['sisa_angsuran'] == 0) ? 'Lunas' : 'Dicairkan';

            $this->db->query("UPDATE sp_pinjaman SET 
                                sisa_pokok = :s_pokok,
                                sisa_jasa = :s_jasa,
                                status = :status
                              WHERE id_pinjaman = :id AND tenant_id = :tenant_id");
            $this->db->bind('s_pokok', $newSisaPokok);
            $this->db->bind('s_jasa', $newSisaJasa);
            $this->db->bind('status', $statusPinjaman);
            $this->db->bind('id', $jadwal['id_pinjaman']);
            $this->db->bind('tenant_id', $tenant_id);
            $this->db->execute();

            // 3. Posting Jurnal Penerimaan Angsuran
            $setting = $this->getPengaturan($tenant_id);
            if (!empty($setting['posting_jurnal_otomatis'])) {
                $akunPiutang = !empty($jadwal['kode_akun_piutang']) ? $jadwal['kode_akun_piutang'] : ($setting['akun_piutang_pinjaman'] ?? '1-10500');
                $akunJasa = !empty($jadwal['kode_akun_pendapatan_jasa']) ? $jadwal['kode_akun_pendapatan_jasa'] : ($setting['akun_pendapatan_jasa'] ?? '4-20100');
                $akunDenda = $setting['akun_pendapatan_denda'] ?? '4-20300';
                $namaDebitur = ($jadwal['tipe_debitur'] === 'Kelompok') ? $jadwal['nama_kelompok'] : $jadwal['nama_nasabah'];

                // Debit: Kas (totalBayar)
                // Kredit: Piutang Pinjaman (pokok)
                // Kredit: Pendapatan Jasa Pinjaman (jasa)
                // Kredit: Pendapatan Denda (jika ada denda > 0)
                $jurnalDetails = [
                    ['kode_akun' => $kodeKas, 'debit' => $totalBayar, 'kredit' => 0],
                    ['kode_akun' => $akunPiutang, 'debit' => 0, 'kredit' => $pokok],
                    ['kode_akun' => $akunJasa, 'debit' => 0, 'kredit' => $jasa]
                ];
                if ($denda > 0) {
                    $jurnalDetails[] = ['kode_akun' => $akunDenda, 'debit' => 0, 'kredit' => $denda];
                }

                $jurnalData = [
                    'no_transaksi' => 'ANGS-' . $jadwal['no_pinjaman'] . '-' . $jadwal['angsuran_ke'],
                    'tanggal' => $tglBayar,
                    'deskripsi' => "Penerimaan Angsuran ke-{$jadwal['angsuran_ke']} Pinjaman {$jadwal['no_pinjaman']} ({$namaDebitur})",
                    'sumber_jurnal' => 'Simpan Pinjam',
                    'details' => $jurnalDetails
                ];

                try {
                    $jurnalModel = new Jurnal_model($this->db);
                    $id_jurnal = $jurnalModel->simpanJurnal($jurnalData, $tenant_id);
                    $this->db->query("UPDATE sp_jadwal_angsuran SET id_jurnal_bayar = :jurnal WHERE id_jadwal = :id");
                    $this->db->bind('jurnal', $id_jurnal);
                    $this->db->bind('id', $id_jadwal);
                    $this->db->execute();
                } catch (\Exception $e) {
                    // Abaikan kegagalan jurnal agar transaksi tetap terekam
                }
            }

            $this->db->commit();
            return true;
        } catch (\Exception $e) {
            $this->db->rollBack();
            throw $e;
        }
    }

    // ==========================================
    // LAPORAN KOLEKTIBILITAS & OUTSTANDING PINJAMAN
    // ==========================================

    /**
     * Menghitung Laporan Outstanding dan Kolektibilitas (NPL / Kualitas Kredit)
     * Kategori Kolektibilitas (Standar OJK / Koperasi):
     * - Kol-1 (Lancar): 0 hari menunggak (jatuh tempo belum lewat / terbayar tepat waktu)
     * - Kol-2 (Dalam Perhatian Khusus - DPK): Menunggak 1 - 30 hari
     * - Kol-3 (Kurang Lancar): Menunggak 31 - 90 hari
     * - Kol-4 (Diragukan): Menunggak 91 - 180 hari
     * - Kol-5 (Macet): Menunggak > 180 hari
     */
    public function getLaporanKolektibilitas($tenant_id, $tgl_cut_off = null) {
        $cutOff = !empty($tgl_cut_off) ? $tgl_cut_off : date('Y-m-d');

        // Ambil semua pinjaman yang sedang dicairkan atau belum lunas
        $this->db->query("SELECT p.*, jp.nama_pinjaman,
                                 n.nama_lengkap as nama_nasabah, n.no_anggota, n.no_telp,
                                 k.nama_kelompok, k.kode_kelompok
                          FROM sp_pinjaman p
                          JOIN sp_jenis_pinjaman jp ON p.id_jenis_pinjaman = jp.id_jenis_pinjaman
                          LEFT JOIN sp_nasabah n ON p.id_nasabah = n.id_nasabah
                          LEFT JOIN sp_kelompok k ON p.id_kelompok = k.id_kelompok
                          WHERE p.tenant_id = :tenant_id AND p.status = 'Dicairkan'
                          ORDER BY p.tanggal_pencairan ASC");
        $this->db->bind('tenant_id', $tenant_id);
        $pinjamanList = $this->db->resultSet();

        $rekapKolektibilitas = [
            'kol_1' => ['label' => 'KOL-1 (Lancar)', 'hari' => '0 hari', 'persen_ckpn' => 0.5, 'count' => 0, 'outstanding' => 0, 'ckpn' => 0],
            'kol_2' => ['label' => 'KOL-2 (Dalam Perhatian Khusus)', 'hari' => '1 - 30 hari', 'persen_ckpn' => 5, 'count' => 0, 'outstanding' => 0, 'ckpn' => 0],
            'kol_3' => ['label' => 'KOL-3 (Kurang Lancar)', 'hari' => '31 - 90 hari', 'persen_ckpn' => 15, 'count' => 0, 'outstanding' => 0, 'ckpn' => 0],
            'kol_4' => ['label' => 'KOL-4 (Diragukan)', 'hari' => '91 - 180 hari', 'persen_ckpn' => 50, 'count' => 0, 'outstanding' => 0, 'ckpn' => 0],
            'kol_5' => ['label' => 'KOL-5 (Macet)', 'hari' => '> 180 hari', 'persen_ckpn' => 100, 'count' => 0, 'outstanding' => 0, 'ckpn' => 0],
        ];

        $detailList = [];
        $totalOutstanding = 0;
        $totalNpl = 0;

        $cutOffDate = new \DateTime($cutOff);

        foreach ($pinjamanList as $p) {
            // Cari jadwal angsuran tertua yang jatuh tempo <= cutOff dan belum lunas
            $this->db->query("SELECT * FROM sp_jadwal_angsuran 
                              WHERE id_pinjaman = :id AND tenant_id = :tenant_id AND status_bayar != 'Lunas' 
                              ORDER BY angsuran_ke ASC");
            $this->db->bind('id', $p['id_pinjaman']);
            $this->db->bind('tenant_id', $tenant_id);
            $jadwals = $this->db->resultSet();

            $hariMenunggak = 0;
            $angsuranTertunggakCount = 0;
            $tunggakanPokok = 0;
            $tunggakanJasa = 0;

            foreach ($jadwals as $j) {
                $jtDate = new \DateTime($j['jatuh_tempo']);
                if ($jtDate <= $cutOffDate) {
                    $diff = $cutOffDate->diff($jtDate);
                    $days = (int)$diff->format("%a");
                    if ($days > $hariMenunggak) {
                        $hariMenunggak = $days;
                    }
                    $angsuranTertunggakCount++;
                    $tunggakanPokok += (float)$j['pokok'];
                    $tunggakanJasa += (float)$j['jasa'];
                }
            }

            // Klasifikasi Kolektibilitas
            if ($hariMenunggak == 0) {
                $kolKode = 'kol_1';
            } elseif ($hariMenunggak <= 30) {
                $kolKode = 'kol_2';
            } elseif ($hariMenunggak <= 90) {
                $kolKode = 'kol_3';
            } elseif ($hariMenunggak <= 180) {
                $kolKode = 'kol_4';
            } else {
                $kolKode = 'kol_5';
            }

            $outstandingPokok = (float)$p['sisa_pokok'];
            $ckpnNominal = round(($rekapKolektibilitas[$kolKode]['persen_ckpn'] / 100) * $outstandingPokok, 2);

            $rekapKolektibilitas[$kolKode]['count']++;
            $rekapKolektibilitas[$kolKode]['outstanding'] += $outstandingPokok;
            $rekapKolektibilitas[$kolKode]['ckpn'] += $ckpnNominal;

            $totalOutstanding += $outstandingPokok;
            if (in_array($kolKode, ['kol_3', 'kol_4', 'kol_5'])) {
                $totalNpl += $outstandingPokok;
            }

            $p['hari_menunggak'] = $hariMenunggak;
            $p['kol_kode'] = $kolKode;
            $p['kol_label'] = $rekapKolektibilitas[$kolKode]['label'];
            $p['angsuran_tertunggak_count'] = $angsuranTertunggakCount;
            $p['tunggakan_pokok'] = $tunggakanPokok;
            $p['tunggakan_jasa'] = $tunggakanJasa;
            $p['ckpn_estimasi'] = $ckpnNominal;
            $detailList[] = $p;
        }

        $persenNpl = $totalOutstanding > 0 ? round(($totalNpl / $totalOutstanding) * 100, 2) : 0.00;

        return [
            'cut_off' => $cutOff,
            'total_pinjaman' => count($pinjamanList),
            'total_outstanding' => $totalOutstanding,
            'total_npl' => $totalNpl,
            'persen_npl' => $persenNpl,
            'rekap' => $rekapKolektibilitas,
            'detail' => $detailList
        ];
    }
}
