<?php

class Central extends Controller {
    public function __construct() {
        parent::__construct();
        if (!Auth::isLoggedIn() || !Auth::isActuallySuperadmin()) {
            Flash::setFlash('Akses Ditolak', 'Hanya Superadmin/Penyelia Wilayah yang bisa mengakses modul Central', 'danger');
            header('Location: ' . BASEURL . '/dashboard');
            exit;
        }
    }

    public function index() {
        header('Location: ' . BASEURL . '/dashboard');
        exit;
    }

    /**
     * Manajemen Pengguna Global
     */
    public function users() {
        $data['judul'] = 'Global User Management';
        $data['tenants'] = $this->model('Tenants')->getAllTenants();
        $data['klusters'] = $this->model('KlusterWilayah')->getActiveKluster();
        
        if (Auth::isPenyeliaWilayah()) {
            // Penyelia Wilayah hanya melihat user di tenant yang masuk klusternya
            $klusterId = Auth::getKlusterWilayahId();
            $tenantIds = [];
            foreach ($data['tenants'] as $tenant) {
                if ($tenant['kluster_wilayah_id'] == $klusterId) {
                    $tenantIds[] = $tenant['id'];
                }
            }
            // Filter tenants hanya kluster ini
            $data['tenants'] = array_filter($data['tenants'], function($t) use ($klusterId) {
                return $t['kluster_wilayah_id'] == $klusterId;
            });
            // Ambil semua users lalu filter berdasarkan tenant_id
            $allUsers = $this->model('User')->getAllUsers();
            $data['users'] = array_filter($allUsers, function($u) use ($tenantIds) {
                return in_array($u['tenant_id'], $tenantIds) || ($u['role'] === 'Penyelia Wilayah' && $u['kluster_wilayah_id'] == $klusterId);
            });
            $data['users'] = array_values($data['users']);
        } else {
            $data['users'] = $this->model('User')->getAllUsers();
        }
        
        $this->view('templates/header', $data);
        $this->view('central/users', $data);
        $this->view('templates/footer');
    }

    public function user_simpan() {
        // Cek apakah username sudah ada
        if ($this->model('User')->getUserByUsername($_POST['nama_user'])) {
            Flash::setFlash('Gagal', 'Username "' . $_POST['nama_user'] . '" sudah digunakan. Silakan gunakan username lain.', 'danger');
            header('Location: ' . BASEURL . '/central/users');
            exit;
        }

        $role = $_POST['role'] ?? 'Staff';
        $tenant_id = (!empty($_POST['tenant_id']) && $role !== 'Superadmin' && $role !== 'Penyelia Wilayah') ? $_POST['tenant_id'] : null;

        if ($this->model('User')->tambahDataUser($_POST, $tenant_id) > 0) {
            Flash::setFlash('Berhasil', 'User baru telah ditambahkan ke sistem', 'success');
        } else {
            Flash::setFlash('Gagal', 'Gagal menambahkan user baru', 'danger');
        }
        header('Location: ' . BASEURL . '/central/users');
        exit;
    }

    public function user_update() {
        if (!isset($_POST['id_user'])) {
            header('Location: ' . BASEURL . '/central/users');
            exit;
        }

        $user = $this->model('User')->getUserById($_POST['id_user']);
        if (!$user) {
            Flash::setFlash('Gagal', 'Data user tidak ditemukan.', 'danger');
            header('Location: ' . BASEURL . '/central/users');
            exit;
        }

        // Keamanan: Hanya Superadmin yang boleh mengedit user Superadmin
        if ($user['role'] == 'Superadmin' && !Auth::hasRole('Superadmin')) {
            Flash::setFlash('Akses Ditolak', 'User Superadmin tidak dapat diubah.', 'danger');
            header('Location: ' . BASEURL . '/central/users');
            exit;
        }

        // Cek apakah username baru sudah digunakan oleh user lain
        $existingUser = $this->model('User')->getUserByUsername($_POST['nama_user']);
        if ($existingUser && $existingUser['id_user'] != $_POST['id_user']) {
            Flash::setFlash('Gagal', 'Username "' . $_POST['nama_user'] . '" sudah digunakan oleh pengguna lain.', 'danger');
            header('Location: ' . BASEURL . '/central/users');
            exit;
        }

        $role = $_POST['role'] ?? $user['role'];
        $tenant_id = (!empty($_POST['tenant_id']) && $role !== 'Superadmin' && $role !== 'Penyelia Wilayah') ? $_POST['tenant_id'] : null;

        if ($this->model('User')->ubahDataUserGlobal($_POST, $tenant_id) > 0) {
            Flash::setFlash('Berhasil', 'Data user berhasil diperbarui', 'success');
        } else {
            Flash::setFlash('Info', 'Data user telah disimpan (tidak ada perubahan data)', 'info');
        }
        header('Location: ' . BASEURL . '/central/users');
        exit;
    }

    public function user_hapus($id) {
        // Hanya Superadmin yang berhak melakukan penghapusan user secara global
        if (!Auth::hasRole('Superadmin')) {
            Flash::setFlash('Akses Ditolak', 'Hanya Superadmin yang memiliki hak akses penuh untuk menghapus user global.', 'danger');
            header('Location: ' . BASEURL . '/central/users');
            exit;
        }

        // Jangan hapus akun sendiri yang sedang aktif digunakan
        if ($id == Auth::user()['id']) {
            Flash::setFlash('Gagal', 'Anda tidak bisa menghapus akun Anda sendiri.', 'danger');
            header('Location: ' . BASEURL . '/central/users');
            exit;
        }

        // Cari data user terlebih dahulu
        $user = $this->model('User')->getUserById($id);
        if (!$user) {
            Flash::setFlash('Gagal', 'Data user tidak ditemukan.', 'danger');
            header('Location: ' . BASEURL . '/central/users');
            exit;
        }

        // Eksekusi penghapusan user secara global
        if ($this->model('User')->hapusDataUserGlobal($id) > 0) {
            Flash::setFlash('Berhasil', 'User "' . htmlspecialchars($user['nama_user']) . '" telah berhasil dihapus dari sistem.', 'success');
        } else {
            Flash::setFlash('Gagal', 'Gagal menghapus user dari sistem.', 'danger');
        }
        header('Location: ' . BASEURL . '/central/users');
        exit;
    }

    public function user_impersonate($id) {
        $user = $this->model('User')->getUserById($id);
        if (!$user) {
            Flash::setFlash('Gagal', 'User tidak ditemukan', 'danger');
            header('Location: ' . BASEURL . '/central/users');
            exit;
        }

        // Jangan impersonate diri sendiri atau sesama superadmin
        if ($user['role'] == 'Superadmin') {
            Flash::setFlash('Gagal', 'Tidak bisa impersonate Superadmin', 'warning');
            header('Location: ' . BASEURL . '/central/users');
            exit;
        }

        Auth::impersonate($user);
        Flash::setFlash('Impersonation', 'Anda sekarang login sebagai ' . $user['nama_user'], 'info');
        header('Location: ' . BASEURL . '/dashboard');
        exit;
    }

    public function user_stop_impersonate() {
        if (Auth::stopImpersonating()) {
            Flash::setFlash('Berhasil', 'Anda telah kembali ke akun Superadmin', 'success');
        }
        header('Location: ' . BASEURL . '/central/users');
        exit;
    }

    /**
     * Manajemen Role Terpusat (RBAC)
     */
    public function roles() {
        // Penyelia Wilayah tidak boleh mengelola roles
        if (Auth::isPenyeliaWilayah()) {
            Flash::setFlash('Akses Ditolak', 'Hanya Superadmin yang bisa mengelola Role', 'danger');
            header('Location: ' . BASEURL . '/dashboard');
            exit;
        }

        $data['judul'] = 'Role Management';
        $data['roles'] = $this->model('Role')->getAllRoles();
        $data['permissions'] = $this->model('Role')->getAllPermissions();
        
        $this->view('templates/header', $data);
        $this->view('central/roles', $data);
        $this->view('templates/footer');
    }

    public function role_simpan() {
        if ($this->model('Role')->tambahRole($_POST)) {
            Flash::setFlash('Berhasil', 'Role baru telah ditambahkan', 'success');
        } else {
            Flash::setFlash('Gagal', 'Gagal menambahkan role', 'danger');
        }
        header('Location: ' . BASEURL . '/central/roles');
        exit;
    }

    public function role_update() {
        if ($this->model('Role')->updateRole($_POST)) {
            Flash::setFlash('Berhasil', 'Data role berhasil diperbarui', 'success');
        } else {
            Flash::setFlash('Gagal', 'Gagal memperbarui role', 'danger');
        }
        header('Location: ' . BASEURL . '/central/roles');
        exit;
    }

    public function role_hapus($id) {
        if ($this->model('Role')->hapusRole($id) > 0) {
            Flash::setFlash('Berhasil', 'Role telah dihapus', 'success');
        } else {
            Flash::setFlash('Gagal', 'Gagal menghapus role', 'danger');
        }
        header('Location: ' . BASEURL . '/central/roles');
        exit;
    }

    /**
     * Monitoring Transaksi Global (Semua Tenant)
     */
    public function monitoring() {
        $data['judul'] = 'Global Transaction Monitoring';
        
        if (Auth::isPenyeliaWilayah()) {
            $klusterId = Auth::getKlusterWilayahId();
            // Ambil 50 transaksi terbaru dari tenant di kluster ini
            $this->db->query("SELECT ju.*, t.name as tenant_name, 
                                COALESCE(SUM(jd.debit), 0) as total
                              FROM jurnal_umum ju
                              JOIN tenants t ON ju.tenant_id = t.id
                              LEFT JOIN jurnal_detail jd ON ju.id_jurnal = jd.id_jurnal
                              WHERE t.kluster_wilayah_id = :kluster_id
                              GROUP BY ju.id_jurnal
                              ORDER BY ju.created_at DESC 
                              LIMIT 50");
            $this->db->bind('kluster_id', $klusterId);
        } else {
            // Ambil 50 transaksi terbaru dari semua tenant
            $this->db->query("SELECT ju.*, t.name as tenant_name, 
                                COALESCE(SUM(jd.debit), 0) as total
                              FROM jurnal_umum ju
                              JOIN tenants t ON ju.tenant_id = t.id
                              LEFT JOIN jurnal_detail jd ON ju.id_jurnal = jd.id_jurnal
                              GROUP BY ju.id_jurnal
                              ORDER BY ju.created_at DESC 
                              LIMIT 50");
        }
        $data['transactions'] = $this->db->resultSet();
        
        $this->view('templates/header', $data);
        $this->view('central/monitoring', $data);
        $this->view('templates/footer');
    }

    /**
     * Laporan Agregat Global
     */
    public function agregat() {
        $data['judul'] = 'Global Aggregate Report';
        $dashboardModel = $this->model('Dashboard');
        
        if (Auth::isPenyeliaWilayah()) {
            $klusterId = Auth::getKlusterWilayahId();
            $data['summary'] = $dashboardModel->getCentralSummaryByKluster($klusterId);
            $data['tenants_report'] = array_filter(
                $this->model('Tenants')->getAllTenantsWithStats(), 
                function($t) use ($klusterId) {
                    return $t['kluster_wilayah_id'] == $klusterId;
                }
            );
        } else {
            $data['summary'] = $dashboardModel->getCentralSummary();
            $data['tenants_report'] = $this->model('Tenants')->getAllTenantsWithStats();
        }
        
        $this->view('templates/header', $data);
        $this->view('central/agregat', $data);
        $this->view('templates/footer');
    }
}
