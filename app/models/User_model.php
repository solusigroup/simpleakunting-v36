<?php

class User_model {
    private $table = 'users';
    private $db;

    /**
     * Constructor baru yang menerima koneksi database dari Controller.
     */
    public function __construct($db) {
        $this->db = $db;
    }

    public function getAllUsers($tenant_id = null) {
        $query = 'SELECT u.*, 
                         t.name as tenant_name, 
                         t.code as tenant_code, 
                         t.kluster_wilayah_id as tenant_kluster_id,
                         COALESCE(kw_user.nama_kabupaten, kw_tenant.nama_kabupaten) as nama_kabupaten,
                         COALESCE(u.kluster_wilayah_id, t.kluster_wilayah_id) as effective_kluster_wilayah_id
                  FROM ' . $this->table . ' u
                  LEFT JOIN tenants t ON u.tenant_id = t.id
                  LEFT JOIN kluster_wilayah kw_user ON u.kluster_wilayah_id = kw_user.id
                  LEFT JOIN kluster_wilayah kw_tenant ON t.kluster_wilayah_id = kw_tenant.id';
        if ($tenant_id) {
            $query .= ' WHERE u.tenant_id = :tenant_id';
        }
        $query .= ' ORDER BY u.nama_user ASC';
        
        $this->db->query($query);
        if ($tenant_id) {
            $this->db->bind('tenant_id', $tenant_id);
        }
        return $this->db->resultSet();
    }

    public function getUserById($id, $tenant_id = null) {
        $query = 'SELECT u.*, 
                         t.name as tenant_name, 
                         t.code as tenant_code, 
                         t.kluster_wilayah_id as tenant_kluster_id,
                         COALESCE(kw_user.nama_kabupaten, kw_tenant.nama_kabupaten) as nama_kabupaten,
                         COALESCE(u.kluster_wilayah_id, t.kluster_wilayah_id) as effective_kluster_wilayah_id
                  FROM ' . $this->table . ' u
                  LEFT JOIN tenants t ON u.tenant_id = t.id
                  LEFT JOIN kluster_wilayah kw_user ON u.kluster_wilayah_id = kw_user.id
                  LEFT JOIN kluster_wilayah kw_tenant ON t.kluster_wilayah_id = kw_tenant.id
                  WHERE u.id_user = :id';
        if ($tenant_id) {
            $query .= ' AND u.tenant_id = :tenant_id';
        }
        $this->db->query($query);
        $this->db->bind('id', $id);
        if ($tenant_id) {
            $this->db->bind('tenant_id', $tenant_id);
        }
        return $this->db->single();
    }
    
    public function getUserByUsername($nama_user) {
        $this->db->query('SELECT * FROM ' . $this->table . ' WHERE nama_user = :nama_user');
        $this->db->bind('nama_user', $nama_user);
        return $this->db->single();
    }

    public function tambahDataUser($data, $tenant_id = null) {
        $role_id = !empty($data['role_id']) ? $data['role_id'] : null;
        $kluster_wilayah_id = !empty($data['kluster_wilayah_id']) ? $data['kluster_wilayah_id'] : null;
        $role = $data['role'] ?? 'Staff';

        // Jika Superadmin atau Penyelia Wilayah, tenant_id harus null
        if ($role === 'Superadmin' || $role === 'Penyelia Wilayah') {
            $tenant_id = null;
        }

        $query = "INSERT INTO {$this->table} (tenant_id, kluster_wilayah_id, nama_user, nama_lengkap, password_hash, role, role_id, jabatan) 
                  VALUES (:tenant_id, :kluster_wilayah_id, :nama, :nama_lengkap, :password, :role, :role_id, :jabatan)";
        $this->db->query($query);
        $this->db->bind('tenant_id', $tenant_id);
        $this->db->bind('kluster_wilayah_id', $kluster_wilayah_id);
        $this->db->bind('nama', $data['nama_user']);
        $this->db->bind('nama_lengkap', $data['nama_lengkap'] ?? $data['nama_user']);
        $this->db->bind('password', password_hash($data['password'], PASSWORD_DEFAULT));
        $this->db->bind('role', $role);
        $this->db->bind('role_id', $role_id);
        $this->db->bind('jabatan', $data['jabatan']);
        $this->db->execute();
        return $this->db->rowCount();
    }

    public function hapusDataUser($id, $tenant_id = null) {
        if ($tenant_id === null) {
            $query = "DELETE FROM {$this->table} WHERE id_user = :id";
            $this->db->query($query);
            $this->db->bind('id', $id);
        } else {
            $query = "DELETE FROM {$this->table} WHERE id_user = :id AND tenant_id = :tenant_id";
            $this->db->query($query);
            $this->db->bind('id', $id);
            $this->db->bind('tenant_id', $tenant_id);
        }
        $this->db->execute();
        return $this->db->rowCount();
    }

    public function hapusDataUserGlobal($id) {
        $query = "DELETE FROM {$this->table} WHERE id_user = :id";
        $this->db->query($query);
        $this->db->bind('id', $id);
        $this->db->execute();
        return $this->db->rowCount();
    }

    public function ubahDataUser($data, $tenant_id = null) {
        $role_id = !empty($data['role_id']) ? $data['role_id'] : null;
        $kluster_wilayah_id = !empty($data['kluster_wilayah_id']) ? $data['kluster_wilayah_id'] : null;
        $whereClause = ($tenant_id !== null) ? "WHERE id_user = :id AND tenant_id = :tenant_id" : "WHERE id_user = :id";

        if (!empty($data['password'])) {
            $query = "UPDATE {$this->table} SET 
                        nama_user = :nama,
                        nama_lengkap = :nama_lengkap,
                        password_hash = :password,
                        role = :role,
                        role_id = :role_id,
                        kluster_wilayah_id = :kluster_wilayah_id,
                        jabatan = :jabatan
                      {$whereClause}";
            $this->db->query($query);
            $this->db->bind('password', password_hash($data['password'], PASSWORD_DEFAULT));
        } else {
            $query = "UPDATE {$this->table} SET 
                        nama_user = :nama,
                        nama_lengkap = :nama_lengkap,
                        role = :role,
                        role_id = :role_id,
                        kluster_wilayah_id = :kluster_wilayah_id,
                        jabatan = :jabatan
                      {$whereClause}";
            $this->db->query($query);
        }
        
        $this->db->bind('nama', $data['nama_user']);
        $this->db->bind('nama_lengkap', $data['nama_lengkap'] ?? $data['nama_user']);
        $this->db->bind('role', $data['role'] ?? 'Staff');
        $this->db->bind('role_id', $role_id);
        $this->db->bind('kluster_wilayah_id', $kluster_wilayah_id);
        $this->db->bind('jabatan', $data['jabatan']);
        $this->db->bind('id', $data['id_user']);
        if ($tenant_id !== null) {
            $this->db->bind('tenant_id', $tenant_id);
        }
        $this->db->execute();
        return $this->db->rowCount();
    }

    public function ubahDataUserGlobal($data, $tenant_id = null) {
        $role = $data['role'] ?? 'Staff';
        $role_id = !empty($data['role_id']) ? $data['role_id'] : null;
        $kluster_wilayah_id = ($role === 'Penyelia Wilayah' && !empty($data['kluster_wilayah_id'])) 
            ? $data['kluster_wilayah_id'] 
            : null;

        // Jika Superadmin atau Penyelia Wilayah, tenant_id dipaksa null
        if ($role === 'Superadmin' || $role === 'Penyelia Wilayah') {
            $tenant_id = null;
        }

        if (!empty($data['password'])) {
            $query = "UPDATE {$this->table} SET 
                        nama_user = :nama,
                        nama_lengkap = :nama_lengkap,
                        password_hash = :password,
                        role = :role,
                        role_id = :role_id,
                        kluster_wilayah_id = :kluster_wilayah_id,
                        tenant_id = :tenant_id,
                        jabatan = :jabatan
                      WHERE id_user = :id";
            $this->db->query($query);
            $this->db->bind('password', password_hash($data['password'], PASSWORD_DEFAULT));
        } else {
            $query = "UPDATE {$this->table} SET 
                        nama_user = :nama,
                        nama_lengkap = :nama_lengkap,
                        role = :role,
                        role_id = :role_id,
                        kluster_wilayah_id = :kluster_wilayah_id,
                        tenant_id = :tenant_id,
                        jabatan = :jabatan
                      WHERE id_user = :id";
            $this->db->query($query);
        }
        
        $this->db->bind('nama', $data['nama_user']);
        $this->db->bind('nama_lengkap', $data['nama_lengkap'] ?? $data['nama_user']);
        $this->db->bind('role', $role);
        $this->db->bind('role_id', $role_id);
        $this->db->bind('kluster_wilayah_id', $kluster_wilayah_id);
        $this->db->bind('tenant_id', $tenant_id);
        $this->db->bind('jabatan', $data['jabatan']);
        $this->db->bind('id', $data['id_user']);
        $this->db->execute();
        return $this->db->rowCount();
    }
}
