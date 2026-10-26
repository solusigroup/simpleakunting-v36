<?php

require_once __DIR__ . '/Jurnal_model.php';

class Aset_model {
    private $table = 'aset_tetap';
    private $db;

    public function __construct($db) {
        $this->db = $db;
    }

    public function getAllAset($tenant_id) {
        $this->db->query("SELECT * FROM {$this->table} WHERE tenant_id = :tenant_id ORDER BY created_at DESC");
        $this->db->bind('tenant_id', $tenant_id);
        return $this->db->resultSet();
    }

    public function getAsetById($id, $tenant_id) {
        $this->db->query("SELECT * FROM {$this->table} WHERE id = :id AND tenant_id = :tenant_id");
        $this->db->bind('id', $id);
        $this->db->bind('tenant_id', $tenant_id);
        return $this->db->single();
    }

    public function getAsetWithIncompleteCoa($tenant_id) {
        $this->db->query("SELECT * FROM {$this->table} 
                          WHERE tenant_id = :tenant_id 
                            AND status = 'Aktif'
                            AND (akun_aset IS NULL OR akun_aset = '' 
                              OR akun_akumulasi IS NULL OR akun_akumulasi = '' 
                              OR akun_beban IS NULL OR akun_beban = '')");
        $this->db->bind('tenant_id', $tenant_id);
        return $this->db->resultSet();
    }

    public function simpanAset($data, $tenant_id) {
        if (empty($data['akun_aset']) || empty($data['akun_akumulasi']) || empty($data['akun_beban'])) {
            return false;
        }

        $query = "INSERT INTO {$this->table} (tenant_id, kode_aset, nama_aset, tanggal_perolehan, harga_perolehan, nilai_residu, umur_ekonomis, metode_penyusutan, akun_aset, akun_akumulasi, akun_beban, keterangan) 
                  VALUES (:tenant_id, :kode, :nama, :tanggal, :harga, :residu, :umur, :metode, :akun_aset, :akun_akumulasi, :akun_beban, :keterangan)";
        $this->db->query($query);
        $this->db->bind('tenant_id', $tenant_id);
        $this->db->bind('kode', $data['kode_aset']);
        $this->db->bind('nama', $data['nama_aset']);
        $this->db->bind('tanggal', $data['tanggal_perolehan']);
        $this->db->bind('harga', $data['harga_perolehan']);
        $this->db->bind('residu', $data['nilai_residu'] ?? 0);
        $this->db->bind('umur', $data['umur_ekonomis']);
        $this->db->bind('metode', $data['metode_penyusutan'] ?? 'Garis Lurus');
        $this->db->bind('akun_aset', $data['akun_aset']);
        $this->db->bind('akun_akumulasi', $data['akun_akumulasi']);
        $this->db->bind('akun_beban', $data['akun_beban']);
        $this->db->bind('keterangan', $data['keterangan'] ?? '');
        $this->db->execute();
        return $this->db->rowCount();
    }

    public function updateAset($data, $tenant_id) {
        $id = $data['id'] ?? $data['id_aset'] ?? null;
        if (!$id) return 0;

        $akun_aset = $data['akun_aset'] ?? '';
        $akun_akumulasi = $data['akun_akumulasi'] ?? $data['akun_akumulasi_depresiasi'] ?? '';
        $akun_beban = $data['akun_beban'] ?? $data['akun_beban_depresiasi'] ?? '';

        if (empty($akun_aset) || empty($akun_akumulasi) || empty($akun_beban)) {
            return false;
        }

        $query = "UPDATE {$this->table} SET 
                    kode_aset = :kode, nama_aset = :nama, tanggal_perolehan = :tanggal, harga_perolehan = :harga, 
                    nilai_residu = :residu, umur_ekonomis = :umur, metode_penyusutan = :metode, 
                    akun_aset = :akun_aset, akun_akumulasi = :akun_akumulasi, akun_beban = :akun_beban, 
                    status = :status, keterangan = :keterangan
                  WHERE id = :id AND tenant_id = :tenant_id";
        $this->db->query($query);
        $this->db->bind('id', $id);
        $this->db->bind('tenant_id', $tenant_id);
        $this->db->bind('kode', $data['kode_aset']);
        $this->db->bind('nama', $data['nama_aset']);
        $this->db->bind('tanggal', $data['tanggal_perolehan']);
        $this->db->bind('harga', $data['harga_perolehan']);
        $this->db->bind('residu', $data['nilai_residu'] ?? 0);
        $this->db->bind('umur', $data['umur_ekonomis'] ?? $data['masa_manfaat'] ?? 0);
        $this->db->bind('metode', $data['metode_penyusutan'] ?? 'Garis Lurus');
        $this->db->bind('akun_aset', $akun_aset);
        $this->db->bind('akun_akumulasi', $akun_akumulasi);
        $this->db->bind('akun_beban', $akun_beban);
        $this->db->bind('status', $data['status'] ?? 'Aktif');
        $this->db->bind('keterangan', $data['keterangan'] ?? '');
        $this->db->execute();
        return $this->db->rowCount();
    }

    public function updateCoaParameter($id, $akun_aset, $akun_akumulasi, $akun_beban, $tenant_id) {
        $this->db->query("UPDATE {$this->table} SET 
                            akun_aset = :akun_aset, 
                            akun_akumulasi = :akun_akumulasi, 
                            akun_beban = :akun_beban 
                          WHERE id = :id AND tenant_id = :tenant_id");
        $this->db->bind('id', $id);
        $this->db->bind('tenant_id', $tenant_id);
        $this->db->bind('akun_aset', $akun_aset);
        $this->db->bind('akun_akumulasi', $akun_akumulasi);
        $this->db->bind('akun_beban', $akun_beban);
        $this->db->execute();
        return $this->db->rowCount() >= 0;
    }

    public function hapusAset($id, $tenant_id) {
        $this->db->query("DELETE FROM {$this->table} WHERE id = :id AND tenant_id = :tenant_id");
        $this->db->bind('id', $id);
        $this->db->bind('tenant_id', $tenant_id);
        $this->db->execute();
        return $this->db->rowCount();
    }

    public function prosesPenyusutan($bulan, $tahun, $tenant_id) {
        $tanggal_akhir = date("Y-m-t", strtotime("$tahun-$bulan-01"));
        $this->db->query("SELECT * FROM {$this->table} WHERE status = 'Aktif' AND tenant_id = :tenant_id AND tanggal_perolehan <= :tgl_akhir");
        $this->db->bind('tenant_id', $tenant_id);
        $this->db->bind('tgl_akhir', $tanggal_akhir);
        $assets = $this->db->resultSet();
        
        $count = 0;
        $skipped_missing_coa = 0;
        $jurnalModel = new Jurnal_model($this->db);

        foreach ($assets as $aset) {
            $this->db->query("SELECT id FROM penyusutan_aset WHERE id_aset = :id_aset AND bulan = :bulan AND tahun = :tahun");
            $this->db->bind('id_aset', $aset['id']);
            $this->db->bind('bulan', $bulan);
            $this->db->bind('tahun', $tahun);
            if ($this->db->single()) continue; 
            
            // Validasi mandatori parameter COA oleh pengguna
            if (empty($aset['akun_beban']) || empty($aset['akun_akumulasi'])) {
                $skipped_missing_coa++;
                continue;
            }

            if ((int)$aset['umur_ekonomis'] <= 0) continue;
            
            $monthly = ($aset['harga_perolehan'] - $aset['nilai_residu']) / $aset['umur_ekonomis'];
            if ($monthly <= 0) continue;
            
            $jurnalData = [
                'no_transaksi' => "DEP/" . str_replace('/', '', $aset['kode_aset']) . "/$tahun$bulan",
                'tanggal' => $tanggal_akhir,
                'deskripsi' => "Penyusutan Aset: {$aset['nama_aset']} ($bulan/$tahun)",
                'sumber_jurnal' => 'Penyusutan',
                'details' => [
                    ['kode_akun' => $aset['akun_beban'], 'debit' => $monthly, 'kredit' => 0],
                    ['kode_akun' => $aset['akun_akumulasi'], 'debit' => 0, 'kredit' => $monthly]
                ]
            ];
            
            $this->db->beginTransaction();
            try {
                $id_jurnal = $jurnalModel->simpanJurnal($jurnalData, $tenant_id);
                $this->db->query("UPDATE jurnal_umum SET is_locked = 1 WHERE id_jurnal = :id");
                $this->db->bind('id', $id_jurnal);
                $this->db->execute();

                $this->db->query("INSERT INTO penyusutan_aset (tenant_id, id_aset, id_jurnal, bulan, tahun, jumlah) VALUES (:tenant_id, :id_aset, :id_jurnal, :bulan, :tahun, :jumlah)");
                $this->db->bind('tenant_id', $tenant_id);
                $this->db->bind('id_aset', $aset['id']);
                $this->db->bind('id_jurnal', $id_jurnal);
                $this->db->bind('bulan', $bulan);
                $this->db->bind('tahun', $tahun);
                $this->db->bind('jumlah', $monthly);
                $this->db->execute();
                
                $this->db->commit();
                $count++;
            } catch (Exception $e) {
                if ($this->db->inTransaction()) $this->db->rollBack();
            }
        }
        return [
            'success_count' => $count,
            'skipped_coa' => $skipped_missing_coa
        ];
    }

    public function getDepreciationData($periode, $tenant_id = null) {
        if (!$tenant_id) {
            $user = Auth::user();
            $tenant_id = $user['tenant_id'] ?? 1;
        }

        $parts = explode('-', $periode);
        $tahun = $parts[0] ?? date('Y');
        $bulan = sprintf('%02d', (int)($parts[1] ?? date('m')));
        $tanggal_akhir = date("Y-m-t", strtotime("$tahun-$bulan-01"));

        $this->db->query("SELECT * FROM {$this->table} WHERE status = 'Aktif' AND tenant_id = :tenant_id AND tanggal_perolehan <= :tgl_akhir");
        $this->db->bind('tenant_id', $tenant_id);
        $this->db->bind('tgl_akhir', $tanggal_akhir);
        $assets = $this->db->resultSet();

        $preview = [];
        foreach ($assets as $aset) {
            $this->db->query("SELECT id FROM penyusutan_aset WHERE id_aset = :id_aset AND bulan = :bulan AND tahun = :tahun");
            $this->db->bind('id_aset', $aset['id']);
            $this->db->bind('bulan', $bulan);
            $this->db->bind('tahun', $tahun);
            if ($this->db->single()) continue;

            if (empty($aset['akun_beban']) || empty($aset['akun_akumulasi'])) continue;
            if ((int)$aset['umur_ekonomis'] <= 0) continue;

            $monthly = ($aset['harga_perolehan'] - $aset['nilai_residu']) / $aset['umur_ekonomis'];
            if ($monthly <= 0) continue;

            $preview[] = [
                'id_aset' => $aset['id'],
                'kode_aset' => $aset['kode_aset'],
                'nama_aset' => $aset['nama_aset'],
                'akun_beban_depresiasi' => $aset['akun_beban'],
                'akun_akumulasi_depresiasi' => $aset['akun_akumulasi'],
                'beban_bulanan' => $monthly
            ];
        }
        return $preview;
    }

    public function runDepreciationJournal($periode, $depreciationData, $tenant_id = null) {
        if (!$tenant_id) {
            $user = Auth::user();
            $tenant_id = $user['tenant_id'] ?? 1;
        }
        $parts = explode('-', $periode);
        $tahun = $parts[0] ?? date('Y');
        $bulan = sprintf('%02d', (int)($parts[1] ?? date('m')));
        $result = $this->prosesPenyusutan($bulan, $tahun, $tenant_id);
        return is_array($result) ? $result['success_count'] > 0 : $result > 0;
    }
}
