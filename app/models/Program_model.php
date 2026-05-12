<?php

class Program_model {
    private $table = 'programs';
    private $db;

    public function __construct($db)
    {
        $this->db = $db;
    }

    public function getAllPrograms($tenant_id)
    {
        $this->db->query("SELECT * FROM {$this->table} WHERE tenant_id = :tenant_id ORDER BY created_at DESC");
        $this->db->bind('tenant_id', $tenant_id);
        return $this->db->resultSet();
    }

    public function getProgramById($id, $tenant_id)
    {
        $this->db->query("SELECT * FROM {$this->table} WHERE id_program = :id AND tenant_id = :tenant_id");
        $this->db->bind('id', $id);
        $this->db->bind('tenant_id', $tenant_id);
        return $this->db->single();
    }

    public function tambahProgram($data, $tenant_id)
    {
        $query = "INSERT INTO {$this->table} (tenant_id, nama_program, tipe, anggaran_total, deskripsi) 
                  VALUES (:tenant_id, :nama_program, :tipe, :anggaran_total, :deskripsi)";
        
        $this->db->query($query);
        $this->db->bind('tenant_id', $tenant_id);
        $this->db->bind('nama_program', $data['nama_program']);
        $this->db->bind('tipe', $data['tipe']);
        $this->db->bind('anggaran_total', $data['anggaran_total'] ?? 0);
        $this->db->bind('deskripsi', $data['deskripsi'] ?? '');
        
        $this->db->execute();
        return $this->db->rowCount();
    }

    public function ubahProgram($data, $tenant_id)
    {
        $query = "UPDATE {$this->table} SET 
                    nama_program = :nama_program,
                    tipe = :tipe,
                    anggaran_total = :anggaran_total,
                    deskripsi = :deskripsi
                  WHERE id_program = :id AND tenant_id = :tenant_id";
        
        $this->db->query($query);
        $this->db->bind('nama_program', $data['nama_program']);
        $this->db->bind('tipe', $data['tipe']);
        $this->db->bind('anggaran_total', $data['anggaran_total']);
        $this->db->bind('deskripsi', $data['deskripsi']);
        $this->db->bind('id', $data['id_program']);
        $this->db->bind('tenant_id', $tenant_id);
        
        $this->db->execute();
        return $this->db->rowCount();
    }

    public function hapusProgram($id, $tenant_id)
    {
        $query = "DELETE FROM {$this->table} WHERE id_program = :id AND tenant_id = :tenant_id";
        $this->db->query($query);
        $this->db->bind('id', $id);
        $this->db->bind('tenant_id', $tenant_id);
        $this->db->execute();
        return $this->db->rowCount();
    }

    public function getProgramRealization($id_program, $tenant_id)
    {
        // Get Program Info
        $program = $this->getProgramById($id_program, $tenant_id);
        if (!$program) return null;

        // Get Income (Debit entries to Cash/Bank accounts related to this program)
        // Usually, income for a program is credited to a Revenue account and debited to Cash.
        // But for simplicity, we look at all transactions linked to this program ID.
        
        $this->db->query("
            SELECT a.kode_akun, a.nama_akun, a.posisi_saldo_normal,
                   SUM(jd.debit) as total_debit, SUM(jd.kredit) as total_kredit
            FROM jurnal_detail jd
            JOIN jurnal_umum ju ON jd.id_jurnal = ju.id_jurnal
            JOIN akun a ON jd.kode_akun = a.kode_akun AND a.tenant_id = ju.tenant_id
            WHERE ju.id_program = :id_program AND ju.tenant_id = :tenant_id
            GROUP BY jd.kode_akun
        ");
        $this->db->bind('id_program', $id_program);
        $this->db->bind('tenant_id', $tenant_id);
        $details = $this->db->resultSet();

        $realization = [
            'program' => $program,
            'income' => [],
            'expense' => [],
            'total_income' => 0,
            'total_expense' => 0
        ];

        foreach ($details as $row) {
            $kode = substr($row['kode_akun'], 0, 1);
            // 4: Revenue, 8: Other Revenue
            if ($kode == '4' || $kode == '8') {
                $amount = $row['total_kredit'] - $row['total_debit'];
                if ($amount != 0) {
                    $realization['income'][] = ['nama' => $row['nama_akun'], 'jumlah' => $amount];
                    $realization['total_income'] += $amount;
                }
            } 
            // 5: COGS, 6: Expense, 7: Other Expense
            elseif ($kode == '5' || $kode == '6' || $kode == '7') {
                $amount = $row['total_debit'] - $row['total_kredit'];
                if ($amount != 0) {
                    $realization['expense'][] = ['nama' => $row['nama_akun'], 'jumlah' => $amount];
                    $realization['total_expense'] += $amount;
                }
            }
        }

        return $realization;
    }
}
