<?php

class Akun_model {
    private $table = 'akun';
    private $db;

    public function __construct($db)
    {
        $this->db = $db;
    }

    public function getAllAkun($tenant_id)
    {
        $this->db->query('SELECT * FROM ' . $this->table . ' WHERE tenant_id = :tenant_id ORDER BY kode_akun ASC');
        $this->db->bind('tenant_id', $tenant_id);
        return $this->db->resultSet();
    }

    public function tambahDataAkun($data, $tenant_id)
    {
        // Keamanan: Saldo awal untuk Header selalu 0
        $saldo_awal = ($data['tipe_akun'] == 'Header') ? 0.00 : ($data['saldo_awal'] ?? 0.00);

        $query = "INSERT INTO {$this->table} (tenant_id, kode_akun, nama_akun, tipe_akun, saldo_normal, posisi_saldo_normal, saldo_awal) 
                  VALUES (:tenant_id, :kode_akun, :nama_akun, :tipe_akun, :saldo_normal, :posisi_saldo_normal, :saldo_awal)";
        
        $this->db->query($query);
        $this->db->bind('tenant_id', $tenant_id);
        $this->db->bind('kode_akun', $data['kode_akun']);
        $this->db->bind('nama_akun', $data['nama_akun']);
        $this->db->bind('tipe_akun', $data['tipe_akun']);
        $this->db->bind('saldo_normal', $data['posisi_saldo_normal']); // Use the same value for compatibility
        $this->db->bind('posisi_saldo_normal', $data['posisi_saldo_normal']);
        $this->db->bind('saldo_awal', $saldo_awal);
        
        $this->db->execute();
        return $this->db->rowCount();
    }

    /**
     * FUNGSI YANG DIPERBARUI dengan logika keamanan untuk Saldo Awal.
     */
    public function ubahDataAkun($data, $tenant_id)
    {
        // **LOGIKA BARU: Pastikan saldo awal untuk Header selalu 0 di sisi server**
        $saldo_awal = ($data['tipe_akun'] == 'Header') ? 0.00 : ($data['saldo_awal'] ?? 0.00);

        $query = "UPDATE {$this->table} SET 
                    nama_akun = :nama_akun,
                    tipe_akun = :tipe_akun,
                    saldo_normal = :saldo_normal,
                    posisi_saldo_normal = :posisi_saldo_normal,
                    saldo_awal = :saldo_awal
                  WHERE kode_akun = :kode_akun AND tenant_id = :tenant_id";
        
        $this->db->query($query);
        $this->db->bind('nama_akun', $data['nama_akun']);
        $this->db->bind('tipe_akun', $data['tipe_akun']);
        $this->db->bind('saldo_normal', $data['posisi_saldo_normal']);
        $this->db->bind('posisi_saldo_normal', $data['posisi_saldo_normal']);
        $this->db->bind('saldo_awal', $saldo_awal); 
        $this->db->bind('kode_akun', $data['kode_akun']);
        $this->db->bind('tenant_id', $tenant_id);
        
        $this->db->execute();
        return $this->db->rowCount();
    }

    public function importFromExcel($data, $tenant_id)
    {
        $this->db->query('SET FOREIGN_KEY_CHECKS=0; DELETE FROM ' . $this->table . ' WHERE tenant_id = :tenant_id; SET FOREIGN_KEY_CHECKS=1;');
        $this->db->bind('tenant_id', $tenant_id);
        $this->db->execute();

        $rowCount = 0;
        foreach ($data as $akun) {
            if (!empty($akun['kode_akun'])) {
                $this->tambahDataAkun($akun, $tenant_id);
                $rowCount++;
            }
        }
        return $rowCount;
    }

    public function ensureCentralKepmendesaTable()
    {
        try {
            $this->db->query("SHOW TABLES LIKE 'central_akun_kepmendesa'");
            $res = $this->db->resultSet();
            $needMigration = empty($res);
            if (!$needMigration) {
                $this->db->query("SELECT COUNT(*) as c FROM central_akun_kepmendesa");
                $cnt = $this->db->single();
                if (($cnt['c'] ?? 0) == 0) {
                    $needMigration = true;
                }
            }

            if ($needMigration) {
                $possiblePaths = [
                    dirname(__DIR__, 2) . '/public/migrations/create_central_akun_kepmendesa.sql',
                    __DIR__ . '/../../public/migrations/create_central_akun_kepmendesa.sql',
                    dirname(__DIR__, 2) . '/migrations/create_central_akun_kepmendesa.sql'
                ];
                if (isset($_SERVER['DOCUMENT_ROOT'])) {
                    $docRoot = rtrim($_SERVER['DOCUMENT_ROOT'], '/\\');
                    $possiblePaths[] = $docRoot . '/migrations/create_central_akun_kepmendesa.sql';
                    $possiblePaths[] = $docRoot . '/public/migrations/create_central_akun_kepmendesa.sql';
                }

                $sqlFile = null;
                foreach ($possiblePaths as $p) {
                    if (file_exists($p)) {
                        $sqlFile = $p;
                        break;
                    }
                }

                if ($sqlFile && file_exists($sqlFile)) {
                    $sql = file_get_contents($sqlFile);
                    if (!empty($sql)) {
                        try {
                            $this->db->dbh->exec($sql);
                        } catch (\PDOException $pe) {
                            $queries = explode(";\n", str_replace("\r\n", "\n", $sql));
                            foreach ($queries as $q) {
                                $trimmed = trim($q);
                                if (!empty($trimmed)) {
                                    $this->db->dbh->exec($trimmed);
                                }
                            }
                        }
                    }
                }
            }
        } catch (\Exception $e) {
            error_log("Error in ensureCentralKepmendesaTable: " . $e->getMessage());
        }
    }

    public function generateFromCentral($tenant_id, $tipe = 'standar')
    {
        // 1. Bersihkan akun lama untuk tenant ini
        $this->db->query('SET FOREIGN_KEY_CHECKS=0; DELETE FROM ' . $this->table . ' WHERE tenant_id = :tenant_id; SET FOREIGN_KEY_CHECKS=1;');
        $this->db->bind('tenant_id', $tenant_id);
        $this->db->execute();

        // 2. Ambil dari central_akun atau central_akun_kepmendesa
        if ($tipe === 'kepmendesa') {
            $this->ensureCentralKepmendesaTable();
            $this->db->query("SELECT kode_akun, nama_akun, tipe_akun, posisi_saldo_normal FROM central_akun_kepmendesa ORDER BY kode_akun ASC");
            $central = $this->db->resultSet();

            // Default pengaturan akun perusahaan sesuai Kepmendesa 136
            $this->db->query("UPDATE perusahaan SET 
                                akun_piutang_default = '1.1.03.01',
                                akun_utang_default = '2.1.01.01',
                                akun_laba_ditahan = '3.3.01.01',
                                akun_ikhtisar_lr = '3.9.01.01',
                                akun_pajak_penjualan = '2.1.02.01',
                                akun_pajak_pembelian = '1.1.07.04',
                                akun_akumulasi_depresiasi_default = '1.3.07.01',
                                akun_beban_depresiasi_default = '6.1.07.02'
                              WHERE tenant_id = :tenant_id");
            $this->db->bind('tenant_id', $tenant_id);
            $this->db->execute();
        } else {
            $this->db->query("SELECT kode_akun, nama_akun, tipe_akun, posisi_saldo_normal FROM central_akun ORDER BY kode_akun ASC");
            $central = $this->db->resultSet();

            // Default pengaturan akun perusahaan sesuai Standar SAK EMKM
            $this->db->query("UPDATE perusahaan SET 
                                akun_piutang_default = '1-1200',
                                akun_utang_default = '2-1100',
                                akun_laba_ditahan = '3-2000',
                                akun_ikhtisar_lr = '3-3000',
                                akun_pajak_penjualan = '2-1200',
                                akun_pajak_pembelian = '1-1400',
                                akun_akumulasi_depresiasi_default = '1-2201',
                                akun_beban_depresiasi_default = '6-5000'
                              WHERE tenant_id = :tenant_id");
            $this->db->bind('tenant_id', $tenant_id);
            $this->db->execute();
        }

        // 3. Masukkan ke tabel akun tenant
        $count = 0;
        foreach ($central as $acc) {
            $data = [
                'kode_akun' => $acc['kode_akun'],
                'nama_akun' => $acc['nama_akun'],
                'tipe_akun' => $acc['tipe_akun'],
                'posisi_saldo_normal' => $acc['posisi_saldo_normal'],
                'saldo_awal' => 0
            ];
            $this->tambahDataAkun($data, $tenant_id);
            $count++;
        }
        return $count;
    }

    public function hapusDataAkun($kode, $tenant_id)
    {
        $query = "DELETE FROM {$this->table} WHERE kode_akun = :kode_akun AND tenant_id = :tenant_id";
        $this->db->query($query);
        $this->db->bind('kode_akun', $kode);
        $this->db->bind('tenant_id', $tenant_id);
        $this->db->execute();
        return $this->db->rowCount();
    }

    public function getAkunByKode($kode, $tenant_id)
    {
        $this->db->query('SELECT * FROM ' . $this->table . ' WHERE kode_akun=:kode_akun AND tenant_id = :tenant_id');
        $this->db->bind('kode_akun', $kode);
        $this->db->bind('tenant_id', $tenant_id);
        return $this->db->single();
    }
    
    public function isKodeAkunExists($kode, $tenant_id)
    {
        $this->db->query('SELECT 1 FROM ' . $this->table . ' WHERE kode_akun = :kode_akun AND tenant_id = :tenant_id');
        $this->db->bind('kode_akun', $kode);
        $this->db->bind('tenant_id', $tenant_id);
        $this->db->execute();
        return $this->db->rowCount() > 0;
    }

    public function getAkunKasBank($tenant_id)
    {
        $this->db->query("SELECT * FROM " . $this->table . " 
                          WHERE (kode_akun LIKE '1-%' OR kode_akun LIKE '1.%')
                          AND (tipe_akun IN ('Kas & Bank', 'Kas', 'Bank') OR kode_akun LIKE '1-11%' OR kode_akun LIKE '1.1.01%' OR kode_akun LIKE '1.1.02%' OR kode_akun LIKE '1-1000%' OR nama_akun LIKE '%Kas%' OR nama_akun LIKE '%Bank%') 
                          AND tipe_akun != 'Header' 
                          AND nama_akun NOT LIKE '%Beban%'
                          AND nama_akun NOT LIKE '%Utang%'
                          AND nama_akun NOT LIKE '%Hutang%'
                          AND tenant_id = :tenant_id 
                          ORDER BY kode_akun ASC");
        $this->db->bind('tenant_id', $tenant_id);
        return $this->db->resultSet();
    }

    public function isAkunKasBank($kode_akun, $tenant_id)
    {
        $this->db->query("SELECT 1 FROM " . $this->table . " 
                          WHERE kode_akun = :kode 
                          AND (kode_akun LIKE '1-%' OR kode_akun LIKE '1.%')
                          AND (tipe_akun IN ('Kas & Bank', 'Kas', 'Bank') OR kode_akun LIKE '1-11%' OR kode_akun LIKE '1.1.01%' OR kode_akun LIKE '1.1.02%' OR kode_akun LIKE '1-1000%' OR nama_akun LIKE '%Kas%' OR nama_akun LIKE '%Bank%') 
                          AND tipe_akun != 'Header' 
                          AND nama_akun NOT LIKE '%Beban%'
                          AND nama_akun NOT LIKE '%Utang%'
                          AND nama_akun NOT LIKE '%Hutang%'
                          AND tenant_id = :tenant_id");
        $this->db->bind('kode', $kode_akun);
        $this->db->bind('tenant_id', $tenant_id);
        $this->db->execute();
        return $this->db->rowCount() > 0;
    }

    public function getSaldoAkun($kode_akun, $tenant_id)
    {
        // Ambil info posisi normal dan saldo awal
        $this->db->query("SELECT posisi_saldo_normal, saldo_awal FROM " . $this->table . " WHERE kode_akun = :kode AND tenant_id = :tid");
        $this->db->bind('kode', $kode_akun);
        $this->db->bind('tid', $tenant_id);
        $akun = $this->db->single();
        if (!$akun) return 0;

        // Hitung total mutasi dari jurnal
        $this->db->query("SELECT SUM(jd.debit) as total_debit, SUM(jd.kredit) as total_kredit 
                          FROM jurnal_detail jd 
                          JOIN jurnal_umum ju ON jd.id_jurnal = ju.id_jurnal 
                          WHERE jd.kode_akun = :kode AND ju.tenant_id = :tid");
        $this->db->bind('kode', $kode_akun);
        $this->db->bind('tid', $tenant_id);
        $trx = $this->db->single();

        $saldo = (float)($akun['saldo_awal'] ?? 0);
        if (($akun['posisi_saldo_normal'] ?? 'Debit') == 'Debit') {
            $saldo += ((float)($trx['total_debit'] ?? 0) - (float)($trx['total_kredit'] ?? 0));
        } else {
            $saldo += ((float)($trx['total_kredit'] ?? 0) - (float)($trx['total_debit'] ?? 0));
        }
        return $saldo;
    }
}