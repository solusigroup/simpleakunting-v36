<?php

class Pajak_model {
    private $db;

    public function __construct($db) {
        $this->db = $db;
        $this->_checkAndInstallSchema();
    }

    private function _checkAndInstallSchema() {
        try {
            // Tabel penyimpanan hasil rekonsiliasi fiskal tahunan/periodik
            $sqlRekon = "CREATE TABLE IF NOT EXISTS `rekonsiliasi_fiskal` (
                `id` INT AUTO_INCREMENT PRIMARY KEY,
                `tenant_id` INT NOT NULL,
                `tahun_pajak` INT NOT NULL,
                `tanggal_mulai` DATE NOT NULL,
                `tanggal_selesai` DATE NOT NULL,
                `id_unit` INT NULL,
                `laba_komersial` DECIMAL(15,2) DEFAULT 0.00,
                `koreksi_positif_rincian` LONGTEXT NULL,
                `total_koreksi_positif` DECIMAL(15,2) DEFAULT 0.00,
                `koreksi_negatif_rincian` LONGTEXT NULL,
                `total_koreksi_negatif` DECIMAL(15,2) DEFAULT 0.00,
                `penghasilan_neto_fiskal` DECIMAL(15,2) DEFAULT 0.00,
                `kompensasi_rugi` DECIMAL(15,2) DEFAULT 0.00,
                `pkp` DECIMAL(15,2) DEFAULT 0.00,
                `skema_tarif` VARCHAR(50) DEFAULT 'pasal_31e',
                `omzet_bruto` DECIMAL(15,2) DEFAULT 0.00,
                `pph_terutang` DECIMAL(15,2) DEFAULT 0.00,
                `kredit_pajak_rincian` LONGTEXT NULL,
                `total_kredit_pajak` DECIMAL(15,2) DEFAULT 0.00,
                `pph_kurang_bayar` DECIMAL(15,2) DEFAULT 0.00,
                `catatan` TEXT NULL,
                `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                INDEX idx_tenant_tahun (`tenant_id`, `tahun_pajak`)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;";
            $this->db->query($sqlRekon);
            $this->db->execute();

            // Cek kolom npwp pada tabel perusahaan
            $this->db->query("SHOW COLUMNS FROM `perusahaan` LIKE 'npwp'");
            $colNpwp = $this->db->single();
            if (!$colNpwp) {
                $this->db->query("ALTER TABLE `perusahaan` ADD COLUMN `npwp` VARCHAR(35) NULL AFTER `nama_perusahaan`");
                $this->db->execute();
            }
        } catch (Exception $e) {
            // Lewati jika sudah ada atau ada error minor
        }
    }

    /**
     * Mengambil konfigurasi akun pajak dari tabel perusahaan
     */
    public function getTaxConfig($tenant_id) {
        $this->db->query("SELECT akun_pajak_penjualan, akun_pajak_pembelian, persentase_pajak_default, npwp, nama_perusahaan, jenis_usaha, kota_laporan 
                          FROM perusahaan WHERE tenant_id = :tenant_id");
        $this->db->bind('tenant_id', $tenant_id);
        $conf = $this->db->single();

        $defaultPajakPersen = !empty($conf['persentase_pajak_default']) ? (float)$conf['persentase_pajak_default'] : 11.0;
        
        return [
            'akun_pajak_penjualan' => $conf['akun_pajak_penjualan'] ?? null,
            'akun_pajak_pembelian' => $conf['akun_pajak_pembelian'] ?? null,
            'persentase_pajak_default' => $defaultPajakPersen,
            'npwp' => $conf['npwp'] ?? '',
            'nama_perusahaan' => $conf['nama_perusahaan'] ?? '',
            'jenis_usaha' => $conf['jenis_usaha'] ?? '',
            'kota_laporan' => $conf['kota_laporan'] ?? 'Mojokerto'
        ];
    }

    /**
     * Mengambil laporan pajak lengkap: PPN Keluaran, PPN Masukan, SPT Masa PPN, Pemotongan PPh, dan Omzet Bulanan
     */
    public function getLaporanPajak($tanggal_mulai, $tanggal_selesai, $tenant_id, $id_unit = null) {
        $taxConfig = $this->getTaxConfig($tenant_id);
        $unitFilterPenjualan = $id_unit ? " AND p.id_unit = :id_unit " : "";
        $unitFilterPembelian = $id_unit ? " AND pb.id_unit = :id_unit " : "";
        $unitFilterJurnal = $id_unit ? " AND ju.id_unit = :id_unit " : "";

        // 1. PPN KELUARAN DARI TABEL PENJUALAN
        // DPP dihitung dari total - pajak + diskon (atau total subtotal bruto kena pajak)
        $sqlKeluaranTrx = "
            SELECT 
                p.id_penjualan, p.no_faktur, p.tanggal_faktur as tanggal, 
                COALESCE(c.nama_pelanggan, 'Pelanggan Umum') as entitas,
                p.keterangan, 
                (p.total - COALESCE(p.pajak, 0) + COALESCE(p.diskon, 0)) as dpp,
                p.pajak as nilai_pajak,
                CASE 
                    WHEN (p.total - COALESCE(p.pajak, 0) + COALESCE(p.diskon, 0)) > 0 
                    THEN ROUND((p.pajak / (p.total - COALESCE(p.pajak, 0) + COALESCE(p.diskon, 0))) * 100, 1)
                    ELSE :def_rate 
                END as tarif_persen,
                p.id_jurnal,
                'Faktur Penjualan' as jenis_dokumen
            FROM penjualan p
            LEFT JOIN pelanggan c ON p.id_pelanggan = c.id_pelanggan
            WHERE p.tenant_id = :tenant_id 
              AND p.tanggal_faktur BETWEEN :tgl_mulai AND :tgl_selesai
              AND p.pajak > 0
              $unitFilterPenjualan
            ORDER BY p.tanggal_faktur ASC, p.id_penjualan ASC";

        $this->db->query($sqlKeluaranTrx);
        $this->db->bind('tenant_id', $tenant_id);
        $this->db->bind('tgl_mulai', $tanggal_mulai);
        $this->db->bind('tgl_selesai', $tanggal_selesai);
        $this->db->bind('def_rate', $taxConfig['persentase_pajak_default']);
        if ($id_unit) $this->db->bind('id_unit', $id_unit);
        $listKeluaranTrx = $this->db->resultSet();

        // 2. PPN KELUARAN DARI JURNAL UMUM (misal penyesuaian/pos/jurnal manual akun PPN Keluaran)
        // Akun PPN Keluaran standar atau yang diatur tenant
        $akunPpnKeluaranList = array_filter([
            $taxConfig['akun_pajak_penjualan'],
            '2.1.02.01',
            '2-1200',
            '2-10300'
        ]);
        
        $placeholdersPpnK = implode(',', array_fill(0, count($akunPpnKeluaranList), '?'));
        $sqlKeluaranJurnal = "
            SELECT 
                ju.id_jurnal, ju.no_transaksi, ju.tanggal, 
                'Penyesuaian Jurnal / Kasir' as entitas,
                ju.deskripsi as keterangan,
                CASE 
                    WHEN :def_rate > 0 THEN ROUND(jd.kredit / (:def_rate / 100), 2)
                    ELSE jd.kredit 
                END as dpp,
                jd.kredit as nilai_pajak,
                :def_rate as tarif_persen,
                ju.id_jurnal,
                'Jurnal Umum / POS' as jenis_dokumen
            FROM jurnal_detail jd
            JOIN jurnal_umum ju ON jd.id_jurnal = ju.id_jurnal
            LEFT JOIN penjualan p ON ju.id_jurnal = p.id_jurnal
            WHERE ju.tenant_id = ?
              AND ju.tanggal BETWEEN ? AND ?
              AND jd.kode_akun IN ($placeholdersPpnK)
              AND jd.kredit > 0
              AND p.id_penjualan IS NULL
              " . ($id_unit ? " AND ju.id_unit = ? " : "") . "
            ORDER BY ju.tanggal ASC";

        // Eksekusi manual PDO query untuk bind variadic
        $paramsK = array_merge([$tenant_id, $tanggal_mulai, $tanggal_selesai], array_values($akunPpnKeluaranList));
        if ($id_unit) $paramsK[] = $id_unit;
        
        $this->db->query($sqlKeluaranJurnal);
        $this->db->bind(':def_rate', $taxConfig['persentase_pajak_default']);
        // Jalankan via raw statement jika perlu atau fallback
        // Karena wrapper Database.php menggunakan parameter terikat, mari kita kumpulkan akun PPN Keluaran lewat query
        $listKeluaranJurnal = $this->_getJurnalPajakEntries($tenant_id, $tanggal_mulai, $tanggal_selesai, $akunPpnKeluaranList, 'kredit', 'penjualan', $id_unit, $taxConfig['persentase_pajak_default']);

        $ppnKeluaran = array_merge($listKeluaranTrx, $listKeluaranJurnal);

        // 3. PPN MASUKAN DARI TABEL PEMBELIAN
        $sqlMasukanTrx = "
            SELECT 
                pb.id_pembelian, pb.no_faktur_pembelian as no_faktur, pb.tanggal_faktur as tanggal,
                COALESCE(s.nama_pemasok, 'Pemasok Umum') as entitas,
                pb.keterangan,
                (pb.total - COALESCE(pb.pajak, 0) + COALESCE(pb.diskon, 0)) as dpp,
                pb.pajak as nilai_pajak,
                CASE 
                    WHEN (pb.total - COALESCE(pb.pajak, 0) + COALESCE(pb.diskon, 0)) > 0 
                    THEN ROUND((pb.pajak / (pb.total - COALESCE(pb.pajak, 0) + COALESCE(pb.diskon, 0))) * 100, 1)
                    ELSE :def_rate 
                END as tarif_persen,
                pb.id_jurnal,
                'Faktur Pembelian' as jenis_dokumen
            FROM pembelian pb
            LEFT JOIN pemasok s ON pb.id_pemasok = s.id_pemasok
            WHERE pb.tenant_id = :tenant_id 
              AND pb.tanggal_faktur BETWEEN :tgl_mulai AND :tgl_selesai
              AND pb.pajak > 0
              $unitFilterPembelian
            ORDER BY pb.tanggal_faktur ASC, pb.id_pembelian ASC";

        $this->db->query($sqlMasukanTrx);
        $this->db->bind('tenant_id', $tenant_id);
        $this->db->bind('tgl_mulai', $tanggal_mulai);
        $this->db->bind('tgl_selesai', $tanggal_selesai);
        $this->db->bind('def_rate', $taxConfig['persentase_pajak_default']);
        if ($id_unit) $this->db->bind('id_unit', $id_unit);
        $listMasukanTrx = $this->db->resultSet();

        // 4. PPN MASUKAN DARI JURNAL UMUM
        $akunPpnMasukanList = array_filter([
            $taxConfig['akun_pajak_pembelian'],
            '1.1.07.04',
            '1-1400'
        ]);
        $listMasukanJurnal = $this->_getJurnalPajakEntries($tenant_id, $tanggal_mulai, $tanggal_selesai, $akunPpnMasukanList, 'debit', 'pembelian', $id_unit, $taxConfig['persentase_pajak_default']);

        $ppnMasukan = array_merge($listMasukanTrx, $listMasukanJurnal);

        // Hitung akumulasi PPN
        $totDppKeluaran = 0;
        $totPpnKeluaran = 0;
        foreach ($ppnKeluaran as $k) {
            $totDppKeluaran += (float)$k['dpp'];
            $totPpnKeluaran += (float)$k['nilai_pajak'];
        }

        $totDppMasukan = 0;
        $totPpnMasukan = 0;
        foreach ($ppnMasukan as $m) {
            $totDppMasukan += (float)$m['dpp'];
            $totPpnMasukan += (float)$m['nilai_pajak'];
        }

        $selisihPpn = $totPpnKeluaran - $totPpnMasukan;
        $statusPpn = 'Nihil';
        if ($selisihPpn > 0) {
            $statusPpn = 'Kurang Bayar (Utang PPN)';
        } elseif ($selisihPpn < 0) {
            $statusPpn = 'Lebih Bayar (Kompensasi/Restitusi)';
        }

        // 5. REKAPITULASI PEMOTONGAN / PEMUNGUTAN PPH (WITHHOLDING TAX)
        $pphMutasi = $this->_getWithholdingTaxRecords($tenant_id, $tanggal_mulai, $tanggal_selesai, $id_unit);

        // 6. REKAPITULASI OMZET DAN ESTIMASI PPH FINAL UMKM (PP 55/2022 - 0.5%)
        $omzetBulanan = $this->_getOmzetBulananDanPphFinal($tenant_id, $tanggal_mulai, $tanggal_selesai, $id_unit);

        return [
            'config' => $taxConfig,
            'ppn_keluaran' => $ppnKeluaran,
            'ppn_masukan' => $ppnMasukan,
            'ringkasan_ppn' => [
                'total_dpp_keluaran' => $totDppKeluaran,
                'total_ppn_keluaran' => $totPpnKeluaran,
                'total_dpp_masukan' => $totDppMasukan,
                'total_ppn_masukan' => $totPpnMasukan,
                'selisih_ppn' => abs($selisihPpn),
                'saldo_ppn' => $selisihPpn,
                'status_ppn' => $statusPpn
            ],
            'pph_withholding' => $pphMutasi,
            'umkm_final' => $omzetBulanan
        ];
    }

    /**
     * Mengambil entri jurnal pajak yang bukan berasal dari form transaksi penjualan/pembelian
     */
    private function _getJurnalPajakEntries($tenant_id, $tgl_mulai, $tgl_selesai, $akunList, $posisi = 'kredit', $sourceTable = 'penjualan', $id_unit = null, $defRate = 11.0) {
        if (empty($akunList)) return [];

        $cleanedList = array_map(function($a) {
            return "'" . addslashes(trim($a)) . "'";
        }, array_unique($akunList));
        $inClause = implode(',', $cleanedList);

        $unitFilter = $id_unit ? " AND ju.id_unit = " . (int)$id_unit : "";
        $colValue = ($posisi === 'kredit') ? 'jd.kredit' : 'jd.debit';
        $joinExclude = ($sourceTable === 'penjualan') 
            ? "LEFT JOIN penjualan src ON ju.id_jurnal = src.id_jurnal" 
            : "LEFT JOIN pembelian src ON ju.id_jurnal = src.id_jurnal";
        $whereExclude = ($sourceTable === 'penjualan') 
            ? "AND src.id_penjualan IS NULL" 
            : "AND src.id_pembelian IS NULL";

        $sql = "
            SELECT 
                ju.id_jurnal, 
                ju.no_transaksi as no_faktur, 
                ju.tanggal, 
                'Penyesuaian Akuntansi / POS' as entitas,
                CONCAT('(', a.nama_akun, ') ', COALESCE(ju.deskripsi, '')) as keterangan,
                CASE 
                    WHEN {$defRate} > 0 THEN ROUND({$colValue} / ({$defRate} / 100), 2)
                    ELSE {$colValue} 
                END as dpp,
                {$colValue} as nilai_pajak,
                {$defRate} as tarif_persen,
                ju.id_jurnal,
                'Jurnal Umum' as jenis_dokumen
            FROM jurnal_detail jd
            JOIN jurnal_umum ju ON jd.id_jurnal = ju.id_jurnal
            JOIN akun a ON jd.kode_akun = a.kode_akun AND a.tenant_id = ju.tenant_id
            {$joinExclude}
            WHERE ju.tenant_id = :tenant_id
              AND ju.tanggal BETWEEN :tgl_mulai AND :tgl_selesai
              AND jd.kode_akun IN ({$inClause})
              AND {$colValue} > 0
              {$whereExclude}
              {$unitFilter}
            ORDER BY ju.tanggal ASC";

        $this->db->query($sql);
        $this->db->bind('tenant_id', $tenant_id);
        $this->db->bind('tgl_mulai', $tgl_mulai);
        $this->db->bind('tgl_selesai', $tgl_selesai);
        return $this->db->resultSet();
    }

    /**
     * Mengambil daftar dan mutasi pajak pemotongan (PPh 21, 22, 23, 4(2), 25, 29)
     */
    private function _getWithholdingTaxRecords($tenant_id, $tgl_mulai, $tgl_selesai, $id_unit = null) {
        $unitFilter = $id_unit ? " AND ju.id_unit = :id_unit " : "";

        // Cari semua akun yang berkaitan dengan PPh
        $sqlAkun = "SELECT kode_akun, nama_akun, tipe_akun, saldo_normal 
                    FROM akun 
                    WHERE tenant_id = :tenant_id 
                      AND (nama_akun LIKE '%PPh%' OR nama_akun LIKE '%Pajak Penghasilan%' OR kode_akun IN ('1.1.07.03','2.1.02.02','2.1.02.03','2.1.02.04','7.3.01.06','7.3.01.07','7.3.01.08','7.3.01.09','7.3.01.10'))
                    ORDER BY kode_akun ASC";
        $this->db->query($sqlAkun);
        $this->db->bind('tenant_id', $tenant_id);
        $taxAccounts = $this->db->resultSet();

        $kategoriMap = [
            'pph21' => ['label' => 'PPh Pasal 21 (Gaji / Upah / Jasa Orang Pribadi)', 'total_potong' => 0, 'total_setor' => 0, 'saldo_utang' => 0, 'items' => []],
            'pph22' => ['label' => 'PPh Pasal 22 (Pengadaan Barang / Transaksi Pemerintah)', 'total_potong' => 0, 'total_setor' => 0, 'saldo_utang' => 0, 'items' => []],
            'pph23' => ['label' => 'PPh Pasal 23 (Sewa, Jasa Badan, Dividen, Royalti)', 'total_potong' => 0, 'total_setor' => 0, 'saldo_utang' => 0, 'items' => []],
            'pph4_2' => ['label' => 'PPh Pasal 4 ayat (2) (Final: Sewa Tanah/Bangunan, Konstruksi, UMKM)', 'total_potong' => 0, 'total_setor' => 0, 'saldo_utang' => 0, 'items' => []],
            'pph25' => ['label' => 'PPh Pasal 25 (Uang Muka / Angsuran Bulanan PPh Badan)', 'total_potong' => 0, 'total_setor' => 0, 'saldo_utang' => 0, 'items' => []],
            'pph29' => ['label' => 'PPh Pasal 29 (Pelunasan Akhir Tahun PPh Terutang)', 'total_potong' => 0, 'total_setor' => 0, 'saldo_utang' => 0, 'items' => []],
            'pph_lain' => ['label' => 'Pajak & Beban PPh Lainnya', 'total_potong' => 0, 'total_setor' => 0, 'saldo_utang' => 0, 'items' => []]
        ];

        if (empty($taxAccounts)) {
            return $kategoriMap;
        }

        // Ambil mutasi transaksi jurnal untuk akun-akun ini
        $accountCodes = array_column($taxAccounts, 'kode_akun');
        $placeholders = implode(',', array_map(function($c) { return "'" . addslashes($c) . "'"; }, $accountCodes));

        $sqlMutasi = "
            SELECT 
                ju.id_jurnal, ju.no_transaksi, ju.tanggal, ju.deskripsi,
                jd.kode_akun, jd.debit, jd.kredit,
                a.nama_akun, a.tipe_akun, a.saldo_normal
            FROM jurnal_detail jd
            JOIN jurnal_umum ju ON jd.id_jurnal = ju.id_jurnal
            JOIN akun a ON jd.kode_akun = a.kode_akun AND a.tenant_id = ju.tenant_id
            WHERE ju.tenant_id = :tenant_id
              AND ju.tanggal BETWEEN :tgl_mulai AND :tgl_selesai
              AND jd.kode_akun IN ({$placeholders})
              $unitFilter
            ORDER BY ju.tanggal ASC, ju.id_jurnal ASC";

        $this->db->query($sqlMutasi);
        $this->db->bind('tenant_id', $tenant_id);
        $this->db->bind('tgl_mulai', $tgl_mulai);
        $this->db->bind('tgl_selesai', $tgl_selesai);
        if ($id_unit) $this->db->bind('id_unit', $id_unit);
        $mutasiList = $this->db->resultSet();

        foreach ($mutasiList as $m) {
            $nama = strtolower($m['nama_akun']);
            $kode = $m['kode_akun'];

            // Tentukan kategori PPh
            $kat = 'pph_lain';
            if (strpos($nama, '21') !== false || $kode === '2.1.02.02' || $kode === '7.3.01.06') {
                $kat = 'pph21';
            } elseif (strpos($nama, '22') !== false) {
                $kat = 'pph22';
            } elseif (strpos($nama, '23') !== false || $kode === '2.1.02.03' || $kode === '7.3.01.07') {
                $kat = 'pph23';
            } elseif (strpos($nama, 'final') !== false || strpos($nama, '4(2)') !== false || strpos($nama, '4 ayat 2') !== false || $kode === '7.3.01.10') {
                $kat = 'pph4_2';
            } elseif (strpos($nama, '25') !== false || $kode === '1.1.07.03' || $kode === '7.3.01.08') {
                $kat = 'pph25';
            } elseif (strpos($nama, '29') !== false || $kode === '2.1.02.04' || $kode === '7.3.01.09') {
                $kat = 'pph29';
            }

            $debit = (float)$m['debit'];
            $kredit = (float)$m['kredit'];

            // Untuk akun kewajiban/utang pajak (saldo normal Kredit):
            // Kredit = pemotongan / timbul utang pajak
            // Debit = penyetoran utang pajak ke kas negara
            if ($m['saldo_normal'] === 'Kredit' || substr($m['kode_akun'], 0, 1) === '2') {
                $potong = $kredit;
                $setor = $debit;
            } else {
                // Untuk akun beban atau pajak dibayar dimuka (saldo normal Debit):
                $potong = $debit;
                $setor = $kredit;
            }

            $kategoriMap[$kat]['total_potong'] += $potong;
            $kategoriMap[$kat]['total_setor'] += $setor;
            $kategoriMap[$kat]['saldo_utang'] += ($potong - $setor);
            $kategoriMap[$kat]['items'][] = [
                'tanggal' => $m['tanggal'],
                'no_transaksi' => $m['no_transaksi'],
                'kode_akun' => $m['kode_akun'],
                'nama_akun' => $m['nama_akun'],
                'deskripsi' => $m['deskripsi'],
                'debit' => $debit,
                'kredit' => $kredit,
                'nilai_potong' => $potong,
                'nilai_setor' => $setor
            ];
        }

        return $kategoriMap;
    }

    /**
     * Menghitung rekapitulasi omzet bulanan dan estimasi PPh Final UMKM 0.5% (PP 55/2022)
     */
    private function _getOmzetBulananDanPphFinal($tenant_id, $tgl_mulai, $tgl_selesai, $id_unit = null) {
        $unitFilter = $id_unit ? " AND ju.id_unit = :id_unit " : "";

        // Ambil data bulanan dari akun pendapatan (kepala 4 dan 8)
        $sql = "
            SELECT 
                DATE_FORMAT(ju.tanggal, '%Y-%m') as bulan_tahun,
                DATE_FORMAT(ju.tanggal, '%M %Y') as nama_bulan,
                SUM(CASE WHEN a.saldo_normal = 'Kredit' THEN jd.kredit - jd.debit ELSE jd.debit - jd.kredit END) as omzet
            FROM jurnal_detail jd
            JOIN jurnal_umum ju ON jd.id_jurnal = ju.id_jurnal
            JOIN akun a ON jd.kode_akun = a.kode_akun AND a.tenant_id = ju.tenant_id
            WHERE ju.tenant_id = :tenant_id
              AND ju.tanggal BETWEEN :tgl_mulai AND :tgl_selesai
              AND (SUBSTR(a.kode_akun, 1, 1) IN ('4', '8') OR a.tipe_akun IN ('Pendapatan', 'Pendapatan Lainnya'))
              $unitFilter
            GROUP BY DATE_FORMAT(ju.tanggal, '%Y-%m'), DATE_FORMAT(ju.tanggal, '%M %Y')
            ORDER BY bulan_tahun ASC";

        $this->db->query($sql);
        $this->db->bind('tenant_id', $tenant_id);
        $this->db->bind('tgl_mulai', $tgl_mulai);
        $this->db->bind('tgl_selesai', $tgl_selesai);
        if ($id_unit) $this->db->bind('id_unit', $id_unit);
        $rows = $this->db->resultSet();

        $hasil = [];
        $kumulatifOmzet = 0;
        $totalPphFinal = 0;
        $totalOmzet = 0;

        foreach ($rows as $r) {
            $omzet = max(0, (float)$r['omzet']);
            $kumulatifOmzet += $omzet;
            $pphFinal = round($omzet * 0.005, 0); // 0.5% sesuai PP 55/2022
            $totalPphFinal += $pphFinal;
            $totalOmzet += $omzet;

            // Jatuh tempo pembayaran PPh Final adalah tgl 15 bulan berikutnya
            $timestampBulan = strtotime($r['bulan_tahun'] . '-01');
            $tglJatuhTempo = date('15/m/Y', strtotime('+1 month', $timestampBulan));

            $hasil[] = [
                'periode' => $r['nama_bulan'],
                'bulan_key' => $r['bulan_tahun'],
                'omzet' => $omzet,
                'kumulatif' => $kumulatifOmzet,
                'tarif' => '0.5%',
                'pph_terutang' => $pphFinal,
                'jatuh_tempo' => $tglJatuhTempo
            ];
        }

        return [
            'rincian' => $hasil,
            'total_omzet' => $totalOmzet,
            'total_pph' => $totalPphFinal
        ];
    }

    /**
     * Mengambil laporan keuangan fiskal (Rekonsiliasi Fiskal Laba Rugi SPT 1771-I)
     */
    public function getLaporanFiskal($tanggal_mulai, $tanggal_selesai, $tenant_id, $id_unit = null) {
        $tahun = (int)date('Y', strtotime($tanggal_selesai));

        // 1. Ambil data tersimpan di tabel rekonsiliasi_fiskal jika sudah ada
        $saved = $this->getRekonsiliasiSaved($tahun, $tenant_id, $id_unit);

        // 2. Ambil data laba komersial dari Jurnal_model
        $labaKomersialData = $this->_getLabaRugiKomersial($tanggal_mulai, $tanggal_selesai, $tenant_id, $id_unit);
        $labaKomersial = $labaKomersialData['laba_bersih'];
        $totalPendapatanKomersial = $labaKomersialData['total_pendapatan'];
        $totalBebanKomersial = $labaKomersialData['total_beban'];

        // 3. Deteksi otomatis akun-akun komersial untuk saran koreksi fiskal
        $autoSuggestions = $this->_detectFiscalAdjustments($tanggal_mulai, $tanggal_selesai, $tenant_id, $id_unit);

        // Standar item koreksi positif (Pasal 9 UU PPh)
        $standardPositif = [
            'biaya_pribadi' => ['label' => 'Biaya untuk kepentingan pribadi pengurus / pemegang saham / anggota (Ps. 9 ayat 1a)', 'default' => 0.0],
            'dana_cadangan' => ['label' => 'Pembentukan atau pemupukan dana cadangan / penyisihan piutang (Ps. 9 ayat 1c)', 'default' => 0.0],
            'natura' => ['label' => 'Natura dan kenikmatan yang tidak memenuhi kriteria PMK 66/2023 (Ps. 9 ayat 1e)', 'default' => 0.0],
            'afiliasi_melebihi' => ['label' => 'Jumlah melebihi kewajaran dibayarkan ke pihak afiliasi (Ps. 9 ayat 1f)', 'default' => 0.0],
            'beban_pph' => ['label' => 'Pajak Penghasilan (PPh Badan/Final yang dibebankan) (Ps. 9 ayat 1h)', 'default' => $autoSuggestions['beban_pph']],
            'sumbangan' => ['label' => 'Biaya sumbangan / hibah non-kualifikasi PMK (Ps. 9 ayat 1g)', 'default' => $autoSuggestions['sumbangan']],
            'sanksi_pajak' => ['label' => 'Sanksi administrasi (bunga, denda, kenaikan) pajak (Ps. 9 ayat 1k)', 'default' => $autoSuggestions['sanksi_pajak']],
            'selisih_penyusutan_positif' => ['label' => 'Selisih penyusutan komersial di atas penyusutan fiskal', 'default' => 0.0],
            'biaya_non_3m' => ['label' => 'Biaya operasional lainnya yang tidak berhubungan langsung dengan 3M penghasilan', 'default' => 0.0]
        ];

        // Standar item koreksi negatif (Pasal 4 ayat 2 & ayat 3 UU PPh)
        $standardNegatif = [
            'penghasilan_final' => ['label' => 'Penghasilan dikenakan PPh Bersifat Final (Bunga Deposito/Tabungan, Jasa Giro, Sewa Tanah) (Ps. 4 ayat 2)', 'default' => $autoSuggestions['penghasilan_final']],
            'bukan_objek_pajak' => ['label' => 'Penghasilan yang bukan merupakan objek pajak (Bantuan, Hibah, Dividen kualifikasi) (Ps. 4 ayat 3)', 'default' => 0.0],
            'selisih_penyusutan_negatif' => ['label' => 'Selisih penyusutan komersial di bawah penyusutan fiskal', 'default' => 0.0],
            'koreksi_negatif_lainnya' => ['label' => 'Penyesuaian fiskal negatif lainnya', 'default' => 0.0]
        ];

        // Gabungkan dengan data tersimpan jika ada
        $rincianPositif = [];
        $totalPositif = 0;
        $savedPositif = !empty($saved['koreksi_positif_rincian']) ? json_decode($saved['koreksi_positif_rincian'], true) : [];

        foreach ($standardPositif as $k => $item) {
            $val = isset($savedPositif[$k]) ? (float)$savedPositif[$k] : (float)$item['default'];
            $rincianPositif[$k] = [
                'label' => $item['label'],
                'nilai' => $val
            ];
            $totalPositif += $val;
        }

        $rincianNegatif = [];
        $totalNegatif = 0;
        $savedNegatif = !empty($saved['koreksi_negatif_rincian']) ? json_decode($saved['koreksi_negatif_rincian'], true) : [];

        foreach ($standardNegatif as $k => $item) {
            $val = isset($savedNegatif[$k]) ? (float)$savedNegatif[$k] : (float)$item['default'];
            $rincianNegatif[$k] = [
                'label' => $item['label'],
                'nilai' => $val
            ];
            $totalNegatif += $val;
        }

        // Kalkulasi Penghasilan Neto Fiskal
        $penghasilanNetoFiskal = $labaKomersial + $totalPositif - $totalNegatif;

        // Kompensasi Kerugian
        $kompensasiRugi = isset($saved['kompensasi_rugi']) ? (float)$saved['kompensasi_rugi'] : 0.0;

        // Penghasilan Kena Pajak (PKP) - Dibulatkan ke bawah dalam ribuan
        $pkpSebelumBulat = max(0, $penghasilanNetoFiskal - $kompensasiRugi);
        $pkp = floor($pkpSebelumBulat / 1000) * 1000;

        // Skema Tarif & Omzet
        $skemaTarif = $saved['skema_tarif'] ?? 'pasal_31e';
        $omzetBruto = isset($saved['omzet_bruto']) && $saved['omzet_bruto'] > 0 
            ? (float)$saved['omzet_bruto'] 
            : (float)$totalPendapatanKomersial;

        // Hitung PPh Terutang
        $kalkulasiPph = $this->_calculatePphBadan($pkp, $omzetBruto, $skemaTarif);
        $pphTerutang = $kalkulasiPph['pph_terutang'];

        // Kredit Pajak (PPh 22, 23, 25)
        $standardKredit = [
            'pph_22' => ['label' => 'PPh Pasal 22 (Pemungutan Pihak Lain / Bendahara)', 'default' => 0.0],
            'pph_23' => ['label' => 'PPh Pasal 23 (Bukti Potong Dividen/Bunga/Sewa/Jasa)', 'default' => 0.0],
            'pph_25' => ['label' => 'PPh Pasal 25 (Setoran Angsuran Bulanan Berjalan)', 'default' => $autoSuggestions['kredit_pph_25']]
        ];

        $rincianKredit = [];
        $totalKredit = 0;
        $savedKredit = !empty($saved['kredit_pajak_rincian']) ? json_decode($saved['kredit_pajak_rincian'], true) : [];

        foreach ($standardKredit as $k => $item) {
            $val = isset($savedKredit[$k]) ? (float)$savedKredit[$k] : (float)$item['default'];
            $rincianKredit[$k] = [
                'label' => $item['label'],
                'nilai' => $val
            ];
            $totalKredit += $val;
        }

        // PPh Kurang / Lebih Bayar (Pasal 29 / 28A)
        $pphKurangBayar = $pphTerutang - $totalKredit;

        return [
            'tahun_pajak' => $tahun,
            'tanggal_mulai' => $tanggal_mulai,
            'tanggal_selesai' => $tanggal_selesai,
            'is_saved' => !empty($saved),
            'saved_id' => $saved['id'] ?? null,
            'catatan' => $saved['catatan'] ?? '',
            'laba_komersial' => $labaKomersial,
            'total_pendapatan_komersial' => $totalPendapatanKomersial,
            'total_beban_komersial' => $totalBebanKomersial,
            'rincian_positif' => $rincianPositif,
            'total_positif' => $totalPositif,
            'rincian_negatif' => $rincianNegatif,
            'total_negatif' => $totalNegatif,
            'penghasilan_neto_fiskal' => $penghasilanNetoFiskal,
            'kompensasi_rugi' => $kompensasiRugi,
            'pkp' => $pkp,
            'skema_tarif' => $skemaTarif,
            'omzet_bruto' => $omzetBruto,
            'pph_terutang' => $pphTerutang,
            'kalkulasi_detail' => $kalkulasiPph,
            'rincian_kredit' => $rincianKredit,
            'total_kredit' => $totalKredit,
            'pph_kurang_bayar' => $pphKurangBayar,
            'status_kurang_bayar' => ($pphKurangBayar > 0) ? 'PPh Pasal 29 (Kurang Bayar)' : (($pphKurangBayar < 0) ? 'PPh Pasal 28A (Lebih Bayar)' : 'Nihil')
        ];
    }

    /**
     * Hitung PPh Badan Terutang berdasarkan 3 skema perpajakan resmi Indonesia
     */
    private function _calculatePphBadan($pkp, $omzet, $skema) {
        $tarifNormal = 0.22; // UU PPh & HPP Pasal 17 ayat 1b = 22%
        $tarifFasilitas = 0.11; // 50% x 22% = 11%

        if ($skema === 'umkm_final') {
            // Skema PP 55/2022: 0.5% dari seluruh peredaran bruto
            $pph = round($omzet * 0.005, 0);
            return [
                'skema' => 'umkm_final',
                'label' => 'PPh Final UMKM 0.5% (PP 55 Tahun 2022)',
                'pkp_fasilitas' => 0,
                'pkp_non_fasilitas' => 0,
                'pph_fasilitas' => 0,
                'pph_non_fasilitas' => 0,
                'pph_terutang' => $pph,
                'keterangan' => 'Dihitung 0.5% dari total peredaran bruto Rp ' . number_format($omzet, 0, ',', '.')
            ];
        }

        if ($skema === 'tarif_umum') {
            // Tarif Normal 22% tanpa fasilitas
            $pph = round($pkp * $tarifNormal, 0);
            return [
                'skema' => 'tarif_umum',
                'label' => 'Tarif Normal PPh Badan 22% (Pasal 17 UU PPh)',
                'pkp_fasilitas' => 0,
                'pkp_non_fasilitas' => $pkp,
                'pph_fasilitas' => 0,
                'pph_non_fasilitas' => $pph,
                'pph_terutang' => $pph,
                'keterangan' => 'Dihitung 22% dari seluruh Penghasilan Kena Pajak Rp ' . number_format($pkp, 0, ',', '.')
            ];
        }

        // Default: Fasilitas Pasal 31E UU PPh
        if ($omzet <= 4800000000) {
            // Seluruh PKP mendapat fasilitas 50% x 22% = 11%
            $pph = round($pkp * $tarifFasilitas, 0);
            return [
                'skema' => 'pasal_31e',
                'label' => 'Fasilitas Pasal 31E UU PPh (Peredaran Bruto <= Rp 4,8 Miliar)',
                'pkp_fasilitas' => $pkp,
                'pkp_non_fasilitas' => 0,
                'pph_fasilitas' => $pph,
                'pph_non_fasilitas' => 0,
                'pph_terutang' => $pph,
                'keterangan' => 'Mendapatkan fasilitas diskon 50% tarif (efektif 11%) untuk seluruh PKP karena omzet <= Rp 4,8 M'
            ];
        } elseif ($omzet <= 50000000000) {
            // Omzet antara 4,8 M s.d. 50 M: Sebagian fasilitas, sebagian tarif normal
            $proporsi = 4800000000 / $omzet;
            $pkpFasilitas = floor(($proporsi * $pkp) / 1000) * 1000;
            $pkpNonFasilitas = max(0, $pkp - $pkpFasilitas);
            $pphFasilitas = round($pkpFasilitas * $tarifFasilitas, 0);
            $pphNonFasilitas = round($pkpNonFasilitas * $tarifNormal, 0);
            $pphTotal = $pphFasilitas + $pphNonFasilitas;

            return [
                'skema' => 'pasal_31e',
                'label' => 'Fasilitas Proporsional Pasal 31E UU PPh (Omzet Rp 4,8 M s.d. Rp 50 M)',
                'pkp_fasilitas' => $pkpFasilitas,
                'pkp_non_fasilitas' => $pkpNonFasilitas,
                'pph_fasilitas' => $pphFasilitas,
                'pph_non_fasilitas' => $pphNonFasilitas,
                'pph_terutang' => $pphTotal,
                'keterangan' => 'PKP Fasilitas (11%): Rp ' . number_format($pkpFasilitas, 0, ',', '.') . ' + PKP Normal (22%): Rp ' . number_format($pkpNonFasilitas, 0, ',', '.')
            ];
        } else {
            // Omzet di atas 50 M: Tarif normal penuh 22%
            $pph = round($pkp * $tarifNormal, 0);
            return [
                'skema' => 'pasal_31e',
                'label' => 'Tarif Normal PPh Badan 22% (Omzet > Rp 50 Miliar)',
                'pkp_fasilitas' => 0,
                'pkp_non_fasilitas' => $pkp,
                'pph_fasilitas' => 0,
                'pph_non_fasilitas' => $pph,
                'pph_terutang' => $pph,
                'keterangan' => 'Tidak mendapatkan fasilitas pengurangan tarif karena omzet melebihi Rp 50 Miliar'
            ];
        }
    }

    /**
     * Mengambil laba rugi komersial dari tabel jurnal
     */
    private function _getLabaRugiKomersial($tgl_mulai, $tgl_selesai, $tenant_id, $id_unit = null) {
        $unitFilter = $id_unit ? " AND ju.id_unit = :id_unit " : "";

        $sql = "
            SELECT 
                a.kode_akun, a.nama_akun, a.saldo_normal,
                COALESCE(trx.total_debit, 0) as total_debit,
                COALESCE(trx.total_kredit, 0) as total_kredit
            FROM akun a
            LEFT JOIN (
                SELECT jd.kode_akun, SUM(jd.debit) as total_debit, SUM(jd.kredit) as total_kredit
                FROM jurnal_detail jd
                JOIN jurnal_umum ju ON jd.id_jurnal = ju.id_jurnal
                WHERE ju.tenant_id = :tenant_id 
                  AND ju.tanggal BETWEEN :tgl_mulai AND :tgl_selesai
                  $unitFilter
                GROUP BY jd.kode_akun
            ) trx ON a.kode_akun = trx.kode_akun
            WHERE a.tenant_id = :tenant_id
              AND (a.tipe_akun != 'Header')
              AND SUBSTR(a.kode_akun, 1, 1) IN ('4', '5', '6', '7', '8')";

        $this->db->query($sql);
        $this->db->bind('tenant_id', $tenant_id);
        $this->db->bind('tgl_mulai', $tgl_mulai);
        $this->db->bind('tgl_selesai', $tgl_selesai);
        if ($id_unit) $this->db->bind('id_unit', $id_unit);
        $rows = $this->db->resultSet();

        $totPendapatan = 0;
        $totBeban = 0;

        foreach ($rows as $r) {
            $prefix = substr($r['kode_akun'], 0, 1);
            $saldo = ($r['saldo_normal'] === 'Kredit') ? ($r['total_kredit'] - $r['total_debit']) : ($r['total_debit'] - $r['total_kredit']);

            if ($prefix === '4' || $prefix === '8') {
                $totPendapatan += $saldo;
            } else {
                $totBeban += $saldo;
            }
        }

        return [
            'total_pendapatan' => $totPendapatan,
            'total_beban' => $totBeban,
            'laba_bersih' => ($totPendapatan - $totBeban)
        ];
    }

    /**
     * Deteksi otomatis akun-akun komersial untuk rekomendasi koreksi fiskal
     */
    private function _detectFiscalAdjustments($tgl_mulai, $tgl_selesai, $tenant_id, $id_unit = null) {
        $unitFilter = $id_unit ? " AND ju.id_unit = :id_unit " : "";

        $sql = "
            SELECT 
                a.kode_akun, a.nama_akun, a.saldo_normal,
                SUM(jd.debit) as deb, SUM(jd.kredit) as kre
            FROM jurnal_detail jd
            JOIN jurnal_umum ju ON jd.id_jurnal = ju.id_jurnal
            JOIN akun a ON jd.kode_akun = a.kode_akun AND a.tenant_id = ju.tenant_id
            WHERE ju.tenant_id = :tenant_id
              AND ju.tanggal BETWEEN :tgl_mulai AND :tgl_selesai
              $unitFilter
            GROUP BY a.kode_akun, a.nama_akun, a.saldo_normal";

        $this->db->query($sql);
        $this->db->bind('tenant_id', $tenant_id);
        $this->db->bind('tgl_mulai', $tgl_mulai);
        $this->db->bind('tgl_selesai', $tgl_selesai);
        if ($id_unit) $this->db->bind('id_unit', $id_unit);
        $rows = $this->db->resultSet();

        $deteksi = [
            'beban_pph' => 0.0,
            'sumbangan' => 0.0,
            'sanksi_pajak' => 0.0,
            'penghasilan_final' => 0.0,
            'kredit_pph_25' => 0.0
        ];

        foreach ($rows as $r) {
            $nama = strtolower($r['nama_akun']);
            $kode = $r['kode_akun'];
            $saldoBeban = (float)($r['deb'] - $r['kre']);
            $saldoPendapatan = (float)($r['kre'] - $r['deb']);

            // Beban PPh dibukukan di Laba Rugi
            if ((strpos($nama, 'beban pph') !== false || strpos($nama, 'beban pajak penghasilan') !== false || strpos($nama, 'pajak final') !== false) && $saldoBeban > 0) {
                $deteksi['beban_pph'] += $saldoBeban;
            }

            // Sumbangan / CSR
            if ((strpos($nama, 'sumbangan') !== false || strpos($nama, 'donasi') !== false || strpos($nama, 'bansos') !== false) && $saldoBeban > 0) {
                $deteksi['sumbangan'] += $saldoBeban;
            }

            // Denda / Sanksi Pajak
            if ((strpos($nama, 'denda') !== false || strpos($nama, 'sanksi') !== false) && $saldoBeban > 0) {
                $deteksi['sanksi_pajak'] += $saldoBeban;
            }

            // Pendapatan Bunga Bank / Jasa Giro (Dikenakan PPh Final 20% oleh bank)
            if ((strpos($nama, 'bunga bank') !== false || strpos($nama, 'jasa giro') !== false || strpos($nama, 'bunga deposito') !== false) && $saldoPendapatan > 0) {
                $deteksi['penghasilan_final'] += $saldoPendapatan;
            }

            // Uang Muka PPh 25 (Pajak Dibayar Dimuka)
            if ((strpos($nama, 'pph 25') !== false || $kode === '1.1.07.03') && $r['deb'] > 0) {
                $deteksi['kredit_pph_25'] += (float)$r['deb'];
            }
        }

        return $deteksi;
    }

    /**
     * Menyimpan hasil formulir rekonsiliasi fiskal ke database
     */
    public function simpanRekonsiliasiFiskal($data, $tenant_id) {
        $tahun = (int)($data['tahun_pajak'] ?? date('Y'));
        $tglMulai = $data['tanggal_mulai'] ?? ($tahun . '-01-01');
        $tglSelesai = $data['tanggal_selesai'] ?? ($tahun . '-12-31');
        $idUnit = !empty($data['id_unit']) ? (int)$data['id_unit'] : null;

        $labaKomersial = (float)($data['laba_komersial'] ?? 0);
        $rincianPositif = is_array($data['koreksi_positif'] ?? null) ? json_encode($data['koreksi_positif']) : ($data['koreksi_positif'] ?? '{}');
        $totalPositif = (float)($data['total_koreksi_positif'] ?? 0);

        $rincianNegatif = is_array($data['koreksi_negatif'] ?? null) ? json_encode($data['koreksi_negatif']) : ($data['koreksi_negatif'] ?? '{}');
        $totalNegatif = (float)($data['total_koreksi_negatif'] ?? 0);

        $netoFiskal = (float)($data['penghasilan_neto_fiskal'] ?? ($labaKomersial + $totalPositif - $totalNegatif));
        $kompensasi = (float)($data['kompensasi_rugi'] ?? 0);
        $pkp = (float)($data['pkp'] ?? max(0, $netoFiskal - $kompensasi));
        $skema = $data['skema_tarif'] ?? 'pasal_31e';
        $omzet = (float)($data['omzet_bruto'] ?? 0);
        $pphTerutang = (float)($data['pph_terutang'] ?? 0);

        $rincianKredit = is_array($data['kredit_pajak'] ?? null) ? json_encode($data['kredit_pajak']) : ($data['kredit_pajak'] ?? '{}');
        $totalKredit = (float)($data['total_kredit_pajak'] ?? 0);
        $kurangBayar = (float)($data['pph_kurang_bayar'] ?? ($pphTerutang - $totalKredit));
        $catatan = $data['catatan'] ?? '';

        // Cek apakah sudah ada rekonsiliasi tersimpan
        $existing = $this->getRekonsiliasiSaved($tahun, $tenant_id, $idUnit);

        if ($existing) {
            $sql = "UPDATE `rekonsiliasi_fiskal` SET 
                        tanggal_mulai = :tgl_mulai,
                        tanggal_selesai = :tgl_selesai,
                        laba_komersial = :laba_komersial,
                        koreksi_positif_rincian = :koreksi_positif,
                        total_koreksi_positif = :total_positif,
                        koreksi_negatif_rincian = :koreksi_negatif,
                        total_koreksi_negatif = :total_negatif,
                        penghasilan_neto_fiskal = :neto_fiskal,
                        kompensasi_rugi = :kompensasi,
                        pkp = :pkp,
                        skema_tarif = :skema,
                        omzet_bruto = :omzet,
                        pph_terutang = :pph_terutang,
                        kredit_pajak_rincian = :kredit_pajak,
                        total_kredit_pajak = :total_kredit,
                        pph_kurang_bayar = :kurang_bayar,
                        catatan = :catatan,
                        updated_at = NOW()
                    WHERE id = :id AND tenant_id = :tenant_id";
            $this->db->query($sql);
            $this->db->bind('id', $existing['id']);
        } else {
            $sql = "INSERT INTO `rekonsiliasi_fiskal` 
                    (tenant_id, tahun_pajak, tanggal_mulai, tanggal_selesai, id_unit, laba_komersial,
                     koreksi_positif_rincian, total_koreksi_positif, koreksi_negatif_rincian, total_koreksi_negatif,
                     penghasilan_neto_fiskal, kompensasi_rugi, pkp, skema_tarif, omzet_bruto,
                     pph_terutang, kredit_pajak_rincian, total_kredit_pajak, pph_kurang_bayar, catatan, created_at, updated_at)
                    VALUES 
                    (:tenant_id, :tahun, :tgl_mulai, :tgl_selesai, :id_unit, :laba_komersial,
                     :koreksi_positif, :total_positif, :koreksi_negatif, :total_negatif,
                     :neto_fiskal, :kompensasi, :pkp, :skema, :omzet,
                     :pph_terutang, :kredit_pajak, :total_kredit, :kurang_bayar, :catatan, NOW(), NOW())";
            $this->db->query($sql);
            $this->db->bind('tahun', $tahun);
            $this->db->bind('id_unit', $idUnit);
        }

        $this->db->bind('tenant_id', $tenant_id);
        $this->db->bind('tgl_mulai', $tglMulai);
        $this->db->bind('tgl_selesai', $tglSelesai);
        $this->db->bind('laba_komersial', $labaKomersial);
        $this->db->bind('koreksi_positif', $rincianPositif);
        $this->db->bind('total_positif', $totalPositif);
        $this->db->bind('koreksi_negatif', $rincianNegatif);
        $this->db->bind('total_negatif', $totalNegatif);
        $this->db->bind('neto_fiskal', $netoFiskal);
        $this->db->bind('kompensasi', $kompensasi);
        $this->db->bind('pkp', $pkp);
        $this->db->bind('skema', $skema);
        $this->db->bind('omzet', $omzet);
        $this->db->bind('pph_terutang', $pphTerutang);
        $this->db->bind('kredit_pajak', $rincianKredit);
        $this->db->bind('total_kredit', $totalKredit);
        $this->db->bind('kurang_bayar', $kurangBayar);
        $this->db->bind('catatan', $catatan);

        return $this->db->execute();
    }

    /**
     * Mengambil data rekonsiliasi tersimpan
     */
    public function getRekonsiliasiSaved($tahun, $tenant_id, $id_unit = null) {
        $unitClause = $id_unit ? " AND id_unit = :id_unit " : " AND (id_unit IS NULL OR id_unit = 0) ";
        $sql = "SELECT * FROM `rekonsiliasi_fiskal` 
                WHERE tenant_id = :tenant_id AND tahun_pajak = :tahun $unitClause 
                LIMIT 1";
        $this->db->query($sql);
        $this->db->bind('tenant_id', $tenant_id);
        $this->db->bind('tahun', $tahun);
        if ($id_unit) $this->db->bind('id_unit', $id_unit);
        return $this->db->single();
    }
}
