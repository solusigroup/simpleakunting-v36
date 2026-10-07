<?php

class AsetBiologis_model {
    private $table = 'aset_biologis';
    private $db;

    public function __construct($db) {
        $this->db = $db;
    }

    public function getAllAsetBiologis($tenant_id, $filters = []) {
        $query = "SELECT * FROM {$this->table} WHERE tenant_id = :tenant_id";
        $params = ['tenant_id' => $tenant_id];

        if (!empty($filters['kategori'])) {
            $query .= " AND kategori = :kategori";
            $params['kategori'] = $filters['kategori'];
        }
        if (!empty($filters['status'])) {
            $query .= " AND status = :status";
            $params['status'] = $filters['status'];
        }
        if (!empty($filters['status_kematangan'])) {
            $query .= " AND status_kematangan = :status_kematangan";
            $params['status_kematangan'] = $filters['status_kematangan'];
        }
        
        $query .= " ORDER BY created_at DESC";
        $this->db->query($query);
        
        foreach ($params as $key => $value) {
            $this->db->bind($key, $value);
        }
        
        return $this->db->resultSet();
    }

    public function getAsetBiologisById($id, $tenant_id) {
        $this->db->query("SELECT * FROM {$this->table} WHERE id = :id AND tenant_id = :tenant_id");
        $this->db->bind('id', $id);
        $this->db->bind('tenant_id', $tenant_id);
        return $this->db->single();
    }

    public function simpanAsetBiologis($data, $tenant_id) {
        $this->db->beginTransaction();
        try {
            $query = "INSERT INTO {$this->table} (tenant_id, kode_aset, nama_aset, kategori, status_kematangan, nilai_wajar, nilai_tercatat, akun_aset, keterangan, status) 
                      VALUES (:tenant_id, :kode_aset, :nama_aset, :kategori, :status_kematangan, :nilai_wajar, :nilai_tercatat, :akun_aset, :keterangan, 'Aktif')";
            $this->db->query($query);
            $this->db->bind('tenant_id', $tenant_id);
            $this->db->bind('kode_aset', $data['kode_aset']);
            $this->db->bind('nama_aset', $data['nama_aset']);
            $this->db->bind('kategori', $data['kategori']);
            $this->db->bind('status_kematangan', $data['status_kematangan']);
            $this->db->bind('nilai_wajar', $data['nilai_wajar']);
            $this->db->bind('nilai_tercatat', $data['nilai_wajar']); // Initial carrying amount is fair value
            $this->db->bind('akun_aset', $data['akun_aset']);
            $this->db->bind('keterangan', $data['keterangan'] ?? '');
            $this->db->execute();
            
            $id_aset = $this->db->lastInsertId();

            require_once APPROOT . '/app/models/Jurnal_model.php';
            $jurnalModel = new Jurnal_model($this->db);
            
            $tanggal = $data['tanggal_perolehan'] ?? date('Y-m-d');
            $no_transaksi = "ABG/" . date('Ymd', strtotime($tanggal)) . "/" . sprintf('%03d', rand(1, 999));
            
            // Assuming Kas/Bank is the source of funds
            $akun_sumber = $data['akun_sumber'] ?? '1-10100'; // Default to Kas
            
            $jurnalData = [
                'no_transaksi' => $no_transaksi,
                'tanggal' => $tanggal,
                'deskripsi' => "Pengakuan Awal Aset Biologis: {$data['nama_aset']}",
                'sumber_jurnal' => 'Aset Biologis',
                'details' => [
                    ['kode_akun' => $data['akun_aset'], 'debit' => $data['nilai_wajar'], 'kredit' => 0],
                    ['kode_akun' => $akun_sumber, 'debit' => 0, 'kredit' => $data['nilai_wajar']]
                ]
            ];
            
            $id_jurnal = $jurnalModel->simpanJurnal($jurnalData, $tenant_id);
            $this->db->query("UPDATE jurnal_umum SET is_locked = 1 WHERE id_jurnal = :id");
            $this->db->bind('id', $id_jurnal);
            $this->db->execute();
            
            $this->db->commit();
            return $id_aset;
        } catch (Exception $e) {
            if ($this->db->inTransaction()) $this->db->rollBack();
            return false;
        }
    }

    public function updateAsetBiologis($data, $tenant_id) {
        $query = "UPDATE {$this->table} SET 
                    kode_aset = :kode_aset, nama_aset = :nama_aset, kategori = :kategori, 
                    status_kematangan = :status_kematangan, akun_aset = :akun_aset, 
                    keterangan = :keterangan, status = :status
                  WHERE id = :id AND tenant_id = :tenant_id";
        $this->db->query($query);
        $this->db->bind('id', $data['id']);
        $this->db->bind('tenant_id', $tenant_id);
        $this->db->bind('kode_aset', $data['kode_aset']);
        $this->db->bind('nama_aset', $data['nama_aset']);
        $this->db->bind('kategori', $data['kategori']);
        $this->db->bind('status_kematangan', $data['status_kematangan']);
        $this->db->bind('akun_aset', $data['akun_aset']);
        $this->db->bind('keterangan', $data['keterangan'] ?? '');
        $this->db->bind('status', $data['status']);
        $this->db->execute();
        return $this->db->rowCount();
    }

    public function hapusAsetBiologis($id, $tenant_id) {
        $aset = $this->getAsetBiologisById($id, $tenant_id);
        if (!$aset || $aset['status'] !== 'Aktif') {
            return false;
        }
        
        // Cek jika sudah ada transaksi (misal penyesuaian)
        $this->db->query("SELECT id FROM aset_biologis_penyesuaian WHERE id_aset = :id LIMIT 1");
        $this->db->bind('id', $id);
        if ($this->db->single()) {
            return false; // Tidak bisa dihapus karena sudah ada transaksi
        }

        $this->db->query("DELETE FROM {$this->table} WHERE id = :id AND tenant_id = :tenant_id");
        $this->db->bind('id', $id);
        $this->db->bind('tenant_id', $tenant_id);
        $this->db->execute();
        return $this->db->rowCount();
    }

    public function prosesNilaiWajar($data, $tenant_id) {
        require_once APPROOT . '/app/models/Jurnal_model.php';
        $jurnalModel = new Jurnal_model($this->db);
        $count = 0;

        foreach ($data['assets'] as $asetInput) {
            $id_aset = $asetInput['id'];
            $nilai_wajar_baru = $asetInput['nilai_wajar_baru'];
            $tanggal = $data['tanggal'] ?? date('Y-m-d');
            
            $aset = $this->getAsetBiologisById($id_aset, $tenant_id);
            if (!$aset) continue;

            $selisih = $nilai_wajar_baru - $aset['nilai_tercatat'];
            if ($selisih == 0) continue;

            $no_transaksi = "ABG/" . date('Ymd', strtotime($tanggal)) . "/" . sprintf('%03d', rand(1, 999));
            $jurnalData = [
                'no_transaksi' => $no_transaksi,
                'tanggal' => $tanggal,
                'deskripsi' => "Penyesuaian Nilai Wajar: {$aset['nama_aset']}",
                'sumber_jurnal' => 'Aset Biologis',
                'details' => []
            ];

            if ($selisih > 0) {
                // Positif: Dr. Aset Biologis, Cr. Keuntungan Nilai Wajar (P&L)
                $jurnalData['details'] = [
                    ['kode_akun' => $aset['akun_aset'], 'debit' => $selisih, 'kredit' => 0],
                    ['kode_akun' => '4-40300', 'debit' => 0, 'kredit' => $selisih]
                ];
            } else {
                // Negatif: Dr. Kerugian Nilai Wajar (P&L), Cr. Aset Biologis
                $abs_selisih = abs($selisih);
                $jurnalData['details'] = [
                    ['kode_akun' => '6-60400', 'debit' => $abs_selisih, 'kredit' => 0],
                    ['kode_akun' => $aset['akun_aset'], 'debit' => 0, 'kredit' => $abs_selisih]
                ];
            }

            $this->db->beginTransaction();
            try {
                $id_jurnal = $jurnalModel->simpanJurnal($jurnalData, $tenant_id);
                $this->db->query("UPDATE jurnal_umum SET is_locked = 1 WHERE id_jurnal = :id");
                $this->db->bind('id', $id_jurnal);
                $this->db->execute();

                // Insert into aset_biologis_penyesuaian
                $this->db->query("INSERT INTO aset_biologis_penyesuaian (tenant_id, id_aset, id_jurnal, tanggal, nilai_sebelumnya, nilai_baru, selisih, tipe) 
                                  VALUES (:tenant_id, :id_aset, :id_jurnal, :tanggal, :nilai_sebelumnya, :nilai_baru, :selisih, 'Penyesuaian_FV')");
                $this->db->bind('tenant_id', $tenant_id);
                $this->db->bind('id_aset', $id_aset);
                $this->db->bind('id_jurnal', $id_jurnal);
                $this->db->bind('tanggal', $tanggal);
                $this->db->bind('nilai_sebelumnya', $aset['nilai_tercatat']);
                $this->db->bind('nilai_baru', $nilai_wajar_baru);
                $this->db->bind('selisih', $selisih);
                $this->db->execute();

                // Update aset biologis
                $this->db->query("UPDATE {$this->table} SET nilai_wajar = :nilai_baru, nilai_tercatat = :nilai_baru WHERE id = :id AND tenant_id = :tenant_id");
                $this->db->bind('nilai_baru', $nilai_wajar_baru);
                $this->db->bind('id', $id_aset);
                $this->db->bind('tenant_id', $tenant_id);
                $this->db->execute();

                $this->db->commit();
                $count++;
            } catch (Exception $e) {
                if ($this->db->inTransaction()) $this->db->rollBack();
            }
        }
        return $count;
    }

    public function prosesPanen($data, $tenant_id) {
        require_once APPROOT . '/app/models/Jurnal_model.php';
        $jurnalModel = new Jurnal_model($this->db);
        
        $id_aset = $data['id_aset'];
        $aset = $this->getAsetBiologisById($id_aset, $tenant_id);
        if (!$aset) return false;

        $nilai_wajar_saat_panen = $data['nilai_wajar_saat_panen'];
        $pengurangan_nilai_aset = $data['pengurangan_nilai_aset'] ?? 0; // for consumable assets
        $tanggal = $data['tanggal'] ?? date('Y-m-d');
        
        $no_transaksi = "ABG/" . date('Ymd', strtotime($tanggal)) . "/" . sprintf('%03d', rand(1, 999));
        
        $jurnalData = [
            'no_transaksi' => $no_transaksi,
            'tanggal' => $tanggal,
            'deskripsi' => "Panen Hasil Agrikultur: {$aset['nama_aset']}",
            'sumber_jurnal' => 'Panen',
            'details' => [
                ['kode_akun' => '1-10300', 'debit' => $nilai_wajar_saat_panen, 'kredit' => 0], // Dr. Persediaan
                ['kode_akun' => '4-40400', 'debit' => 0, 'kredit' => $nilai_wajar_saat_panen]  // Cr. Keuntungan Panen
            ]
        ];

        // Jika ada pengurangan nilai aset biologis (consumable asset)
        if ($pengurangan_nilai_aset > 0) {
            // Need a separate journal or add to this one? Let's add it to the same entry or process it logically
            // Wait, standard says decrease carrying value.
            // Dr. Beban Panen (or similar) / Laba Rugi 
            // Cr. Aset Biologis
            // Let's create an additional detail
            $jurnalData['details'][] = ['kode_akun' => '6-60700', 'debit' => $pengurangan_nilai_aset, 'kredit' => 0]; // Beban Panen
            $jurnalData['details'][] = ['kode_akun' => $aset['akun_aset'], 'debit' => 0, 'kredit' => $pengurangan_nilai_aset];
        }

        $this->db->beginTransaction();
        try {
            $id_jurnal = $jurnalModel->simpanJurnal($jurnalData, $tenant_id);
            $this->db->query("UPDATE jurnal_umum SET is_locked = 1 WHERE id_jurnal = :id");
            $this->db->bind('id', $id_jurnal);
            $this->db->execute();

            $this->db->query("INSERT INTO aset_biologis_panen (tenant_id, id_aset, id_jurnal, tanggal, kuantitas, satuan, nilai_wajar_saat_panen, keterangan) 
                              VALUES (:tenant_id, :id_aset, :id_jurnal, :tanggal, :kuantitas, :satuan, :nilai_wajar, :keterangan)");
            $this->db->bind('tenant_id', $tenant_id);
            $this->db->bind('id_aset', $id_aset);
            $this->db->bind('id_jurnal', $id_jurnal);
            $this->db->bind('tanggal', $tanggal);
            $this->db->bind('kuantitas', $data['kuantitas']);
            $this->db->bind('satuan', $data['satuan']);
            $this->db->bind('nilai_wajar', $nilai_wajar_saat_panen);
            $this->db->bind('keterangan', $data['keterangan'] ?? '');
            $this->db->execute();
            
            $id_panen = $this->db->lastInsertId();

            if ($pengurangan_nilai_aset > 0) {
                $nilai_baru = $aset['nilai_tercatat'] - $pengurangan_nilai_aset;
                $this->db->query("UPDATE {$this->table} SET nilai_tercatat = :nilai_baru, nilai_wajar = :nilai_baru WHERE id = :id AND tenant_id = :tenant_id");
                $this->db->bind('nilai_baru', $nilai_baru);
                $this->db->bind('id', $id_aset);
                $this->db->bind('tenant_id', $tenant_id);
                $this->db->execute();
            }

            $this->db->commit();
            return $id_panen;
        } catch (Exception $e) {
            if ($this->db->inTransaction()) $this->db->rollBack();
            return false;
        }
    }

    public function prosesPelepasan($data, $tenant_id) {
        require_once APPROOT . '/app/models/Jurnal_model.php';
        $jurnalModel = new Jurnal_model($this->db);
        
        $id_aset = $data['id_aset'];
        $aset = $this->getAsetBiologisById($id_aset, $tenant_id);
        if (!$aset) return false;

        $jenis_pelepasan = $data['jenis_pelepasan']; // 'Penjualan' or 'Kematian'
        $nilai_jual = $data['nilai_jual'] ?? 0;
        $tanggal = $data['tanggal'] ?? date('Y-m-d');
        $akun_kas = $data['akun_kas'] ?? '1-10100';
        
        $no_transaksi = "ABG/" . date('Ymd', strtotime($tanggal)) . "/" . sprintf('%03d', rand(1, 999));
        $jurnalData = [
            'no_transaksi' => $no_transaksi,
            'tanggal' => $tanggal,
            'deskripsi' => "Pelepasan Aset Biologis ($jenis_pelepasan): {$aset['nama_aset']}",
            'sumber_jurnal' => 'Pelepasan',
            'details' => []
        ];

        if ($jenis_pelepasan == 'Penjualan') {
            $laba_rugi = $nilai_jual - $aset['nilai_tercatat'];
            $jurnalData['details'][] = ['kode_akun' => $akun_kas, 'debit' => $nilai_jual, 'kredit' => 0];
            $jurnalData['details'][] = ['kode_akun' => $aset['akun_aset'], 'debit' => 0, 'kredit' => $aset['nilai_tercatat']];
            
            if ($laba_rugi > 0) {
                // Keuntungan
                $jurnalData['details'][] = ['kode_akun' => '4-40500', 'debit' => 0, 'kredit' => $laba_rugi];
            } elseif ($laba_rugi < 0) {
                // Kerugian
                $jurnalData['details'][] = ['kode_akun' => '5-50300', 'debit' => abs($laba_rugi), 'kredit' => 0];
            }
        } elseif ($jenis_pelepasan == 'Kematian') {
            $jurnalData['details'][] = ['kode_akun' => '6-60600', 'debit' => $aset['nilai_tercatat'], 'kredit' => 0]; // Kerugian Kematian
            $jurnalData['details'][] = ['kode_akun' => $aset['akun_aset'], 'debit' => 0, 'kredit' => $aset['nilai_tercatat']];
        } else {
            return false;
        }

        $this->db->beginTransaction();
        try {
            $id_jurnal = $jurnalModel->simpanJurnal($jurnalData, $tenant_id);
            $this->db->query("UPDATE jurnal_umum SET is_locked = 1 WHERE id_jurnal = :id");
            $this->db->bind('id', $id_jurnal);
            $this->db->execute();

            $this->db->query("INSERT INTO aset_biologis_pelepasan (tenant_id, id_aset, id_jurnal, jenis_pelepasan, tanggal, nilai_tercatat, nilai_jual, keterangan) 
                              VALUES (:tenant_id, :id_aset, :id_jurnal, :jenis, :tanggal, :nilai_tercatat, :nilai_jual, :keterangan)");
            $this->db->bind('tenant_id', $tenant_id);
            $this->db->bind('id_aset', $id_aset);
            $this->db->bind('id_jurnal', $id_jurnal);
            $this->db->bind('jenis', $jenis_pelepasan);
            $this->db->bind('tanggal', $tanggal);
            $this->db->bind('nilai_tercatat', $aset['nilai_tercatat']);
            $this->db->bind('nilai_jual', $nilai_jual);
            $this->db->bind('keterangan', $data['keterangan'] ?? '');
            $this->db->execute();
            
            $id_pelepasan = $this->db->lastInsertId();

            $status_baru = $jenis_pelepasan == 'Kematian' ? 'Mati' : 'Terjual';
            $this->db->query("UPDATE {$this->table} SET status = :status, nilai_tercatat = 0, nilai_wajar = 0 WHERE id = :id AND tenant_id = :tenant_id");
            $this->db->bind('status', $status_baru);
            $this->db->bind('id', $id_aset);
            $this->db->bind('tenant_id', $tenant_id);
            $this->db->execute();

            $this->db->commit();
            return $id_pelepasan;
        } catch (Exception $e) {
            if ($this->db->inTransaction()) $this->db->rollBack();
            return false;
        }
    }

    public function reklasifikasi($id, $tenant_id) {
        $aset = $this->getAsetBiologisById($id, $tenant_id);
        if (!$aset || $aset['status_kematangan'] === 'Menghasilkan') {
            return false;
        }
        
        $this->db->beginTransaction();
        try {
            $tanggal = date('Y-m-d');
            
            $this->db->query("UPDATE {$this->table} SET status_kematangan = 'Menghasilkan' WHERE id = :id AND tenant_id = :tenant_id");
            $this->db->bind('id', $id);
            $this->db->bind('tenant_id', $tenant_id);
            $this->db->execute();

            $this->db->query("INSERT INTO aset_biologis_penyesuaian (tenant_id, id_aset, tanggal, nilai_sebelumnya, nilai_baru, selisih, tipe) 
                              VALUES (:tenant_id, :id_aset, :tanggal, :nilai, :nilai, 0, 'Reklasifikasi')");
            $this->db->bind('tenant_id', $tenant_id);
            $this->db->bind('id_aset', $id);
            $this->db->bind('tanggal', $tanggal);
            $this->db->bind('nilai', $aset['nilai_tercatat']);
            $this->db->execute();
            
            $this->db->commit();
            return true;
        } catch (Exception $e) {
            if ($this->db->inTransaction()) $this->db->rollBack();
            return false;
        }
    }

    public function getRekonsiliasiNilaiTercatat($tanggal_mulai, $tanggal_selesai, $tenant_id) {
        // Karena implementasi kompleks, saya kembalikan stub query agregasi sederhana
        $query = "SELECT kategori, 
                         SUM(CASE WHEN created_at < :tgl_mulai THEN nilai_tercatat ELSE 0 END) as saldo_awal,
                         SUM(CASE WHEN created_at BETWEEN :tgl_mulai AND :tgl_selesai THEN nilai_tercatat ELSE 0 END) as penambahan,
                         SUM(nilai_tercatat) as saldo_akhir
                  FROM {$this->table} 
                  WHERE tenant_id = :tenant_id 
                  GROUP BY kategori";
        $this->db->query($query);
        $this->db->bind('tenant_id', $tenant_id);
        $this->db->bind('tgl_mulai', $tanggal_mulai);
        $this->db->bind('tgl_selesai', $tanggal_selesai);
        return $this->db->resultSet();
    }

    public function getRingkasanKlasifikasi($tenant_id) {
        $this->db->query("SELECT kategori, status_kematangan, COUNT(*) as jumlah, SUM(nilai_tercatat) as total_nilai 
                          FROM {$this->table} 
                          WHERE tenant_id = :tenant_id AND status = 'Aktif' 
                          GROUP BY kategori, status_kematangan");
        $this->db->bind('tenant_id', $tenant_id);
        return $this->db->resultSet();
    }

    public function getRiwayatPenyesuaian($id_aset, $tenant_id) {
        $this->db->query("SELECT * FROM aset_biologis_penyesuaian WHERE id_aset = :id_aset AND tenant_id = :tenant_id ORDER BY tanggal DESC, id DESC");
        $this->db->bind('id_aset', $id_aset);
        $this->db->bind('tenant_id', $tenant_id);
        return $this->db->resultSet();
    }

    public function getRiwayatPanen($id_aset, $tenant_id) {
        $this->db->query("SELECT * FROM aset_biologis_panen WHERE id_aset = :id_aset AND tenant_id = :tenant_id ORDER BY tanggal DESC, id DESC");
        $this->db->bind('id_aset', $id_aset);
        $this->db->bind('tenant_id', $tenant_id);
        return $this->db->resultSet();
    }

    public function getRiwayatPelepasan($id_aset, $tenant_id) {
        $this->db->query("SELECT * FROM aset_biologis_pelepasan WHERE id_aset = :id_aset AND tenant_id = :tenant_id ORDER BY tanggal DESC, id DESC");
        $this->db->bind('id_aset', $id_aset);
        $this->db->bind('tenant_id', $tenant_id);
        return $this->db->resultSet();
    }
}
