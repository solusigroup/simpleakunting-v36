<?php

require_once 'Jurnal_model.php';

class TutupBuku_model 
{
    private $db;
    private static $schemaChecked = false;

    public function __construct($db) {
        $this->db = $db;
        $this->ensureTableSchema();
    }

    /**
     * Memastikan struktur tabel periode_akuntansi selalu lengkap di database mana pun (Lokal & Produksi)
     */
    private function ensureTableSchema() {
        if (self::$schemaChecked) {
            return;
        }

        try {
            // 1. Pastikan tabel periode_akuntansi dibuat jika belum ada
            $this->db->query("CREATE TABLE IF NOT EXISTS `periode_akuntansi` (
                `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
                `tenant_id` int(11) NOT NULL,
                `tahun` int(11) NOT NULL,
                `bulan` int(11) NOT NULL,
                `status` enum('Open','Closed') NOT NULL DEFAULT 'Closed',
                `tipe_proses` varchar(20) NOT NULL DEFAULT 'Bulanan',
                PRIMARY KEY (`id`)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;");
            $this->db->execute();

            // 2. Periksa kolom yang sudah ada
            $this->db->query("SHOW COLUMNS FROM `periode_akuntansi`");
            $columns = [];
            foreach ($this->db->resultSet() as $col) {
                $columns[] = strtolower($col['Field']);
            }

            // 3. Tambahkan kolom baru yang belum ada di database lama
            if (!in_array('id_jurnal', $columns)) {
                $this->db->query("ALTER TABLE `periode_akuntansi` ADD COLUMN `id_jurnal` bigint(20) unsigned DEFAULT NULL");
                $this->db->execute();
            }
            if (!in_array('total_pendapatan', $columns)) {
                $this->db->query("ALTER TABLE `periode_akuntansi` ADD COLUMN `total_pendapatan` decimal(15,2) NOT NULL DEFAULT 0.00");
                $this->db->execute();
            }
            if (!in_array('total_beban', $columns)) {
                $this->db->query("ALTER TABLE `periode_akuntansi` ADD COLUMN `total_beban` decimal(15,2) NOT NULL DEFAULT 0.00");
                $this->db->execute();
            }
            if (!in_array('laba_bersih', $columns)) {
                $this->db->query("ALTER TABLE `periode_akuntansi` ADD COLUMN `laba_bersih` decimal(15,2) NOT NULL DEFAULT 0.00");
                $this->db->execute();
            }
            if (!in_array('tanggal_tutup', $columns)) {
                $this->db->query("ALTER TABLE `periode_akuntansi` ADD COLUMN `tanggal_tutup` datetime DEFAULT NULL");
                $this->db->execute();
            }
            if (!in_array('closed_by', $columns)) {
                $this->db->query("ALTER TABLE `periode_akuntansi` ADD COLUMN `closed_by` bigint(20) unsigned DEFAULT NULL");
                $this->db->execute();
            }
            if (!in_array('tipe_proses', $columns)) {
                $this->db->query("ALTER TABLE `periode_akuntansi` ADD COLUMN `tipe_proses` varchar(20) NOT NULL DEFAULT 'Bulanan'");
                $this->db->execute();
            }
            if (!in_array('created_at', $columns)) {
                $this->db->query("ALTER TABLE `periode_akuntansi` ADD COLUMN `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP");
                $this->db->execute();
            }
            if (!in_array('updated_at', $columns)) {
                $this->db->query("ALTER TABLE `periode_akuntansi` ADD COLUMN `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP");
                $this->db->execute();
            }

            self::$schemaChecked = true;
        } catch (\Throwable $e) {
            error_log("Schema sync warning: " . $e->getMessage());
        }
    }

    public function getLatestClosedPeriod($tenant_id) {
        $this->ensureTableSchema();
        $this->db->query("SELECT * FROM periode_akuntansi 
                          WHERE status = 'Closed' AND tenant_id = :tenant_id 
                          ORDER BY tahun DESC, bulan DESC LIMIT 1");
        $this->db->bind('tenant_id', $tenant_id);
        return $this->db->single();
    }

    public function getAllClosedPeriods($tenant_id) {
        $this->ensureTableSchema();

        $hasClosedBy = false;
        try {
            $this->db->query("SHOW COLUMNS FROM `periode_akuntansi` LIKE 'closed_by'");
            $hasClosedBy = !empty($this->db->resultSet());
        } catch (\Throwable $e) {}

        if ($hasClosedBy) {
            $this->db->query("SELECT pa.*, COALESCE(u.nama_lengkap, u.nama_user) as nama_user_tutup, ju.no_transaksi as no_jurnal_penutup
                              FROM periode_akuntansi pa
                              LEFT JOIN users u ON pa.closed_by = u.id_user
                              LEFT JOIN jurnal_umum ju ON pa.id_jurnal = ju.id_jurnal
                              WHERE pa.tenant_id = :tenant_id
                              ORDER BY pa.tahun DESC, pa.bulan DESC");
        } else {
            $this->db->query("SELECT pa.*, NULL as nama_user_tutup, NULL as no_jurnal_penutup
                              FROM periode_akuntansi pa
                              WHERE pa.tenant_id = :tenant_id
                              ORDER BY pa.tahun DESC, pa.bulan DESC");
        }

        $this->db->bind('tenant_id', $tenant_id);
        return $this->db->resultSet();
    }
    
    public function getClosingJournalPreview($periode, $tenant_id, $isTahunan = false) {
        $jurnalModel = new Jurnal_model($this->db);
        
        if ($isTahunan) {
            $tanggal_mulai = $periode . '-01-01';
            $tanggal_selesai = $periode . '-12-31';
        } else { // Bulanan
            $tanggal_mulai = $periode . '-01';
            $tanggal_selesai = date('Y-m-t', strtotime($tanggal_mulai));
        }
        
        $labaRugi = $jurnalModel->getLabaRugi($tanggal_mulai, $tanggal_selesai, null, null, $tenant_id);
        
        // Ambil akun pengaturan
        $pengaturanAkun = $this->resolveClosingAccounts($tenant_id);
        
        $labaRugi['tanggal_mulai'] = $tanggal_mulai;
        $labaRugi['tanggal_selesai'] = $tanggal_selesai;
        $labaRugi['akun_ikhtisar_lr'] = $pengaturanAkun['ikhtisar'];
        $labaRugi['akun_laba_ditahan'] = $pengaturanAkun['laba_ditahan'];
        
        return $labaRugi;
    }

    /**
     * Resolusi akun Ikhtisar L/R dan Laba Ditahan untuk tenant
     */
    public function resolveClosingAccounts($tenant_id) {
        $this->db->query("SELECT akun_laba_ditahan, akun_ikhtisar_lr FROM perusahaan WHERE tenant_id = :tenant_id");
        $this->db->bind('tenant_id', $tenant_id);
        $pengaturan = $this->db->single();

        $akun_laba_ditahan = $pengaturan['akun_laba_ditahan'] ?? null;
        $akun_ikhtisar_lr = $pengaturan['akun_ikhtisar_lr'] ?? null;

        // Fallback jika belum di-set di perusahaan
        if (empty($akun_ikhtisar_lr)) {
            // Cari akun ikhtisar di tabel akun tenant
            $this->db->query("SELECT kode_akun, nama_akun FROM akun 
                              WHERE tenant_id = :tenant_id AND (kode_akun = '3-3000' OR nama_akun LIKE '%Ikhtisar%') 
                              LIMIT 1");
            $this->db->bind('tenant_id', $tenant_id);
            $foundIkhtisar = $this->db->single();
            $akun_ikhtisar_lr = $foundIkhtisar['kode_akun'] ?? '3-3000';
        }

        if (empty($akun_laba_ditahan)) {
            // Cari akun laba ditahan di tabel akun tenant
            $this->db->query("SELECT kode_akun, nama_akun FROM akun 
                              WHERE tenant_id = :tenant_id AND (nama_akun LIKE '%Laba Ditahan%' OR kode_akun IN ('3-2000', '3-30000', '3-10000')) 
                              LIMIT 1");
            $this->db->bind('tenant_id', $tenant_id);
            $foundLd = $this->db->single();
            $akun_laba_ditahan = $foundLd['kode_akun'] ?? '3-2000';
        }

        // Ambil nama akun untuk display
        $this->db->query("SELECT kode_akun, nama_akun FROM akun WHERE tenant_id = :tenant_id AND kode_akun = :kode");
        $this->db->bind('tenant_id', $tenant_id);
        $this->db->bind('kode', $akun_ikhtisar_lr);
        $ikhtisarRow = $this->db->single();

        $this->db->query("SELECT kode_akun, nama_akun FROM akun WHERE tenant_id = :tenant_id AND kode_akun = :kode");
        $this->db->bind('tenant_id', $tenant_id);
        $this->db->bind('kode', $akun_laba_ditahan);
        $labaDitahanRow = $this->db->single();

        return [
            'ikhtisar' => [
                'kode' => $akun_ikhtisar_lr,
                'nama' => $ikhtisarRow['nama_akun'] ?? 'Ikhtisar Laba Rugi'
            ],
            'laba_ditahan' => [
                'kode' => $akun_laba_ditahan,
                'nama' => $labaDitahanRow['nama_akun'] ?? 'Laba Ditahan'
            ]
        ];
    }

    public function prosesTutupBuku($periode, $tipe_proses, $tenant_id, $user_id = null) {
        $jurnalModel = new Jurnal_model($this->db);
        $isTahunan = ($tipe_proses === 'Tahunan');

        if ($isTahunan) {
            $tahun = (int) $periode;
            $bulan = 12;
            $tanggal_mulai = $periode . '-01-01';
            $tanggal_selesai = $periode . '-12-31';
            
            // Cek apakah tahun ini sudah ditutup
            $this->db->query("SELECT id, status FROM periode_akuntansi WHERE tenant_id = :tenant_id AND tahun = :tahun AND status = 'Closed' LIMIT 1");
            $this->db->bind('tenant_id', $tenant_id);
            $this->db->bind('tahun', $tahun);
            $existing = $this->db->single();
            if ($existing) {
                throw new Exception("Periode Tahun {$tahun} sudah pernah ditutup sebelumnya.");
            }
        } else { // Bulanan
            $tahun = (int) date('Y', strtotime($periode . '-01'));
            $bulan = (int) date('m', strtotime($periode . '-01'));
            $tanggal_mulai = $periode . '-01';
            $tanggal_selesai = date('Y-m-t', strtotime($tanggal_mulai));

            // Cek apakah bulan ini sudah ditutup
            $this->db->query("SELECT id, status FROM periode_akuntansi WHERE tenant_id = :tenant_id AND tahun = :tahun AND bulan = :bulan AND status = 'Closed'");
            $this->db->bind('tenant_id', $tenant_id);
            $this->db->bind('tahun', $tahun);
            $this->db->bind('bulan', $bulan);
            $existing = $this->db->single();
            if ($existing) {
                throw new Exception("Periode bulan " . date('F Y', strtotime($tanggal_mulai)) . " sudah pernah ditutup.");
            }
        }

        $labaRugiData = $this->getClosingJournalPreview($periode, $tenant_id, $isTahunan);
        $totalPendapatan = (float)($labaRugiData['total_pendapatan_1'] ?? 0);
        $totalBeban = (float)($labaRugiData['total_beban_1'] ?? 0);
        $labaBersih = $totalPendapatan - $totalBeban;

        $accounts = $this->resolveClosingAccounts($tenant_id);
        $akun_ikhtisar_lr = $accounts['ikhtisar']['kode'];
        $akun_laba_ditahan = $accounts['laba_ditahan']['kode'];

        $this->db->beginTransaction();
        try {
            $id_jurnal = null;
            
            // Buat entri jurnal penutup jika ada akun nominal pendapatan atau beban
            $hasNominalTrx = (!empty($labaRugiData['pendapatan']) || !empty($labaRugiData['beban']));

            if ($hasNominalTrx) {
                $jurnalData = [
                    'no_transaksi' => 'CL-' . ($isTahunan ? $periode : date('Ym', strtotime($tanggal_mulai))),
                    'tanggal' => $tanggal_selesai,
                    'deskripsi' => 'Jurnal Penutup ' . $tipe_proses . ' Periode ' . ($isTahunan ? 'Tahun ' . $periode : date('F Y', strtotime($tanggal_mulai))),
                    'sumber_jurnal' => 'Penutup',
                    'details' => []
                ];

                // 1. Tutup semua akun Pendapatan ke Ikhtisar L/R
                if (!empty($labaRugiData['pendapatan'])) {
                    foreach ($labaRugiData['pendapatan'] as $akun) {
                        if ($akun['total_1'] > 0) {
                            $jurnalData['details'][] = [
                                'kode_akun' => $akun['kode_akun'],
                                'debit' => (float)$akun['total_1'],
                                'kredit' => 0
                            ];
                        }
                    }
                    if ($totalPendapatan > 0) {
                        $jurnalData['details'][] = [
                            'kode_akun' => $akun_ikhtisar_lr,
                            'debit' => 0,
                            'kredit' => $totalPendapatan
                        ];
                    }
                }

                // 2. Tutup semua akun Beban ke Ikhtisar L/R
                if (!empty($labaRugiData['beban'])) {
                    if ($totalBeban > 0) {
                        $jurnalData['details'][] = [
                            'kode_akun' => $akun_ikhtisar_lr,
                            'debit' => $totalBeban,
                            'kredit' => 0
                        ];
                    }
                    foreach ($labaRugiData['beban'] as $akun) {
                        if ($akun['total_1'] > 0) {
                            $jurnalData['details'][] = [
                                'kode_akun' => $akun['kode_akun'],
                                'debit' => 0,
                                'kredit' => (float)$akun['total_1']
                            ];
                        }
                    }
                }
                
                // 3. Tutup Ikhtisar L/R ke Laba Ditahan
                if ($labaBersih > 0) {
                    $jurnalData['details'][] = [
                        'kode_akun' => $akun_ikhtisar_lr,
                        'debit' => $labaBersih,
                        'kredit' => 0
                    ];
                    $jurnalData['details'][] = [
                        'kode_akun' => $akun_laba_ditahan,
                        'debit' => 0,
                        'kredit' => $labaBersih
                    ];
                } elseif ($labaBersih < 0) {
                    $jurnalData['details'][] = [
                        'kode_akun' => $akun_laba_ditahan,
                        'debit' => abs($labaBersih),
                        'kredit' => 0
                    ];
                    $jurnalData['details'][] = [
                        'kode_akun' => $akun_ikhtisar_lr,
                        'debit' => 0,
                        'kredit' => abs($labaBersih)
                    ];
                }

                // Simpan jurnal penutup
                $id_jurnal = $jurnalModel->simpanJurnal($jurnalData, $tenant_id);
                if (!$id_jurnal) {
                    throw new Exception("Gagal menyimpan ayat jurnal penutup.");
                }

                // Kunci jurnal penutup agar tidak dapat diedit langsung
                $this->db->query("UPDATE jurnal_umum SET is_locked = 1 WHERE id_jurnal = :id AND tenant_id = :tenant_id");
                $this->db->bind('id', $id_jurnal);
                $this->db->bind('tenant_id', $tenant_id);
                $this->db->execute();
            }

            // 4. Catat status ke tabel periode_akuntansi
            if ($isTahunan) {
                // Untuk tahunan, tandai bulan 1-12 sebagai Closed
                for ($b = 1; $b <= 12; $b++) {
                    $this->db->query("INSERT INTO periode_akuntansi 
                                      (tenant_id, tahun, bulan, status, tipe_proses, id_jurnal, total_pendapatan, total_beban, laba_bersih, closed_by, tanggal_tutup) 
                                      VALUES (:tenant_id, :tahun, :bulan, 'Closed', :tipe, :id_jurnal, :total_pendapatan, :total_beban, :laba_bersih, :closed_by, NOW()) 
                                      ON DUPLICATE KEY UPDATE 
                                      status = 'Closed', tipe_proses = :tipe, id_jurnal = :id_jurnal, 
                                      total_pendapatan = :total_pendapatan, total_beban = :total_beban, laba_bersih = :laba_bersih, 
                                      closed_by = :closed_by, tanggal_tutup = NOW()");
                    $this->db->bind('tenant_id', $tenant_id);
                    $this->db->bind('tahun', $tahun);
                    $this->db->bind('bulan', $b);
                    $this->db->bind('tipe', $tipe_proses);
                    $this->db->bind('id_jurnal', $id_jurnal);
                    $this->db->bind('total_pendapatan', ($b === 12 ? $totalPendapatan : 0));
                    $this->db->bind('total_beban', ($b === 12 ? $totalBeban : 0));
                    $this->db->bind('laba_bersih', ($b === 12 ? $labaBersih : 0));
                    $this->db->bind('closed_by', $user_id);
                    $this->db->execute();
                }
            } else {
                $this->db->query("INSERT INTO periode_akuntansi 
                                  (tenant_id, tahun, bulan, status, tipe_proses, id_jurnal, total_pendapatan, total_beban, laba_bersih, closed_by, tanggal_tutup) 
                                  VALUES (:tenant_id, :tahun, :bulan, 'Closed', :tipe, :id_jurnal, :total_pendapatan, :total_beban, :laba_bersih, :closed_by, NOW()) 
                                  ON DUPLICATE KEY UPDATE 
                                  status = 'Closed', tipe_proses = :tipe, id_jurnal = :id_jurnal, 
                                  total_pendapatan = :total_pendapatan, total_beban = :total_beban, laba_bersih = :laba_bersih, 
                                  closed_by = :closed_by, tanggal_tutup = NOW()");
                $this->db->bind('tenant_id', $tenant_id);
                $this->db->bind('tahun', $tahun);
                $this->db->bind('bulan', $bulan);
                $this->db->bind('tipe', $tipe_proses);
                $this->db->bind('id_jurnal', $id_jurnal);
                $this->db->bind('total_pendapatan', $totalPendapatan);
                $this->db->bind('total_beban', $totalBeban);
                $this->db->bind('laba_bersih', $labaBersih);
                $this->db->bind('closed_by', $user_id);
                $this->db->execute();
            }

            $this->db->commit();
            return [
                'status' => true,
                'laba_bersih' => $labaBersih,
                'id_jurnal' => $id_jurnal
            ];
        } catch (Exception $e) {
            if ($this->db->inTransaction()) {
                $this->db->rollBack();
            }
            throw $e;
        }
    }

    /**
     * Membuka kembali periode yang ditutup (Re-open / Rollback)
     */
    public function bukaKembaliPeriode($id_periode, $tenant_id) {
        $this->db->query("SELECT * FROM periode_akuntansi WHERE id = :id AND tenant_id = :tenant_id");
        $this->db->bind('id', $id_periode);
        $this->db->bind('tenant_id', $tenant_id);
        $periode = $this->db->single();

        if (!$periode) {
            throw new Exception("Data periode akuntansi tidak ditemukan.");
        }

        $this->db->beginTransaction();
        try {
            // Jika ada jurnal penutup terkait, hapus jurnal tersebut
            if (!empty($periode['id_jurnal'])) {
                $id_jurnal = $periode['id_jurnal'];
                // Buka kunci terlebih dahulu
                $this->db->query("UPDATE jurnal_umum SET is_locked = 0 WHERE id_jurnal = :id AND tenant_id = :tenant_id");
                $this->db->bind('id', $id_jurnal);
                $this->db->bind('tenant_id', $tenant_id);
                $this->db->execute();

                // Hapus detail jurnal
                $this->db->query("DELETE FROM jurnal_detail WHERE id_jurnal = :id");
                $this->db->bind('id', $id_jurnal);
                $this->db->execute();

                // Hapus header jurnal
                $this->db->query("DELETE FROM jurnal_umum WHERE id_jurnal = :id AND tenant_id = :tenant_id");
                $this->db->bind('id', $id_jurnal);
                $this->db->bind('tenant_id', $tenant_id);
                $this->db->execute();
            }

            if ($periode['tipe_proses'] === 'Tahunan') {
                // Buka kembali seluruh bulan di tahun tersebut
                $this->db->query("DELETE FROM periode_akuntansi WHERE tenant_id = :tenant_id AND tahun = :tahun");
                $this->db->bind('tenant_id', $tenant_id);
                $this->db->bind('tahun', $periode['tahun']);
                $this->db->execute();
            } else {
                // Hapus record periode bulanan
                $this->db->query("DELETE FROM periode_akuntansi WHERE id = :id AND tenant_id = :tenant_id");
                $this->db->bind('id', $id_periode);
                $this->db->bind('tenant_id', $tenant_id);
                $this->db->execute();
            }

            $this->db->commit();
            return true;
        } catch (Exception $e) {
            if ($this->db->inTransaction()) {
                $this->db->rollBack();
            }
            throw $e;
        }
    }
    
    public function isPeriodClosed($tanggal, $tenant_id) {
        if (empty($tanggal) || empty($tenant_id)) {
            return false;
        }

        $tahun = (int) date('Y', strtotime($tanggal));
        $bulan = (int) date('m', strtotime($tanggal));

        $this->db->query("SELECT status FROM periode_akuntansi 
                          WHERE tahun = :tahun AND bulan = :bulan AND tenant_id = :tenant_id AND status = 'Closed' 
                          LIMIT 1");
        $this->db->bind('tahun', $tahun);
        $this->db->bind('bulan', $bulan);
        $this->db->bind('tenant_id', $tenant_id);
        
        $result = $this->db->single();
        
        return ($result && $result['status'] === 'Closed');
    }
}
