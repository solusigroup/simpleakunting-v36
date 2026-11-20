<?php

class User extends Controller {
    /**
     * Constructor ini berjalan secara otomatis setiap kali controller User dipanggil.
     * Fungsinya sebagai gerbang keamanan.
     */
    public function __construct()
    {
        // Jalankan constructor dari Controller induk terlebih dahulu
        parent::__construct();

        // RBAC: Hanya Superadmin, Admin, atau pengguna dengan izin sys_user_management yang diizinkan
        if (!Auth::isAdmin() && !Auth::hasRole('Superadmin') && !Auth::hasPermission('sys_user_management')) {
            Flash::setFlash('Akses Ditolak: Anda tidak memiliki izin untuk mengelola pengguna.', 'danger');
            header('Location: ' . BASEURL . '/dashboard');
            exit;
        }
    }

    /**
     * Menampilkan halaman utama (daftar) pengguna.
     */
    public function index() {
        $data['judul'] = 'Data Pengguna';
        $data['users'] = $this->model('User')->getAllUsers($this->tenantId());
        $this->view('templates/header', $data);
        $this->view('user/index', $data);
        $this->view('templates/footer');
    }

    /**
     * Menampilkan form untuk menambah pengguna baru.
     */
    public function tambah() {
        $data['judul'] = 'Tambah Pengguna Baru';
        $data['roles'] = $this->model('Role')->getAllRoles($this->tenantId());
        $this->view('templates/header', $data);
        $this->view('user/tambah', $data);
        $this->view('templates/footer');
    }

    /**
     * Memproses data dari form tambah pengguna.
     */
    public function simpan() {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            header('Location: ' . BASEURL . '/user');
            exit;
        }

        // Cek apakah username sudah ada
        if ($this->model('User')->getUserByUsername($_POST['nama_user'])) {
            Flash::setFlash('Gagal', 'Username "' . $_POST['nama_user'] . '" sudah digunakan.', 'danger');
            header('Location: ' . BASEURL . '/user');
            exit;
        }

        // Pencegahan Privilege Escalation: Hanya Superadmin yang boleh menetapkan role Superadmin atau Penyelia Wilayah
        if (!Auth::hasRole('Superadmin')) {
            $requestedRole = $_POST['role'] ?? 'Staff';
            if (in_array($requestedRole, ['Superadmin', 'Penyelia Wilayah'])) {
                Flash::setFlash('Akses Ditolak: Anda tidak memiliki wewenang untuk menetapkan peran tersebut.', 'danger');
                header('Location: ' . BASEURL . '/user');
                exit;
            }
        }

        if ($this->model('User')->tambahDataUser($_POST, $this->tenantId()) > 0) {
            Flash::setFlash('Pengguna baru berhasil ditambahkan.', 'success');
        } else {
            Flash::setFlash('Gagal menambahkan pengguna baru.', 'danger');
        }
        header('Location: ' . BASEURL . '/user');
        exit;
    }

    /**
     * Menampilkan form untuk mengedit data pengguna.
     */
    public function edit($id) {
        $data['judul'] = 'Edit Data Pengguna';
        $data['user'] = $this->model('User')->getUserById($id, $this->tenantId());
        
        if (!$data['user']) {
            Flash::setFlash('Pengguna tidak ditemukan atau Anda tidak memiliki akses.', 'danger');
            header('Location: ' . BASEURL . '/user');
            exit;
        }

        if ($data['user']['nama_user'] === 'superadmin' || $data['user']['role'] === 'Superadmin') {
            if (!Auth::hasRole('Superadmin')) {
                Flash::setFlash('Pengguna Superadmin tidak dapat diubah.', 'danger');
                header('Location: ' . BASEURL . '/user');
                exit;
            }
        }

        $data['roles'] = $this->model('Role')->getAllRoles($this->tenantId());
        $this->view('templates/header', $data);
        $this->view('user/edit', $data);
        $this->view('templates/footer');
    }

    /**
     * Memproses data dari form edit pengguna.
     */
    public function update() {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            header('Location: ' . BASEURL . '/user');
            exit;
        }

        $user = $this->model('User')->getUserById($_POST['id_user'], $this->tenantId());
        
        if (!$user) {
            Flash::setFlash('Pengguna tidak ditemukan atau Anda tidak memiliki akses.', 'danger');
            header('Location: ' . BASEURL . '/user');
            exit;
        }

        // Pencegahan Privilege Escalation: Pengguna non-Superadmin tidak boleh mengedit akun Superadmin
        if ($user['nama_user'] === 'superadmin' || $user['role'] === 'Superadmin') {
            if (!Auth::hasRole('Superadmin')) {
                Flash::setFlash('User Superadmin tidak dapat diubah.', 'danger');
                header('Location: ' . BASEURL . '/user');
                exit;
            }
        }

        // Pencegahan Privilege Escalation: Non-Superadmin tidak boleh menaikkan role menjadi Superadmin / Penyelia Wilayah
        if (!Auth::hasRole('Superadmin')) {
            $requestedRole = $_POST['role'] ?? 'Staff';
            if (in_array($requestedRole, ['Superadmin', 'Penyelia Wilayah'])) {
                Flash::setFlash('Akses Ditolak: Anda tidak memiliki wewenang untuk menetapkan peran tersebut.', 'danger');
                header('Location: ' . BASEURL . '/user');
                exit;
            }
        }

        // Cek apakah username baru sudah digunakan oleh user lain
        $existingUser = $this->model('User')->getUserByUsername($_POST['nama_user']);
        if ($existingUser && $existingUser['id_user'] != $_POST['id_user']) {
            Flash::setFlash('Gagal', 'Username "' . $_POST['nama_user'] . '" sudah digunakan oleh pengguna lain.', 'danger');
            header('Location: ' . BASEURL . '/user');
            exit;
        }

        if ($this->model('User')->ubahDataUser($_POST, $this->tenantId()) > 0) {
            Flash::setFlash('Data pengguna berhasil diubah.', 'success');
        } else {
            Flash::setFlash('Gagal mengubah data pengguna.', 'danger');
        }
        header('Location: ' . BASEURL . '/user');
        exit;
    }

    /**
     * Menghapus data pengguna.
     */
    public function hapus($id) {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            http_response_code(405);
            Flash::setFlash('Metode tidak diizinkan. Penghapusan harus menggunakan metode HTTP POST.', 'danger');
            header('Location: ' . BASEURL . '/user');
            exit;
        }

        $user = $this->model('User')->getUserById($id, $this->tenantId());
        if (!$user) {
            Flash::setFlash('Pengguna tidak ditemukan atau Anda tidak memiliki akses.', 'danger');
            header('Location: ' . BASEURL . '/user');
            exit;
        }

        // Tidak boleh menghapus Superadmin
        if ($user['role'] === 'Superadmin' || $user['nama_user'] === 'superadmin') {
            Flash::setFlash('Akun Superadmin tidak dapat dihapus.', 'danger');
            header('Location: ' . BASEURL . '/user');
            exit;
        }

        // Tidak boleh menghapus akun sendiri yang sedang aktif
        $currentUser = Auth::user();
        if ($currentUser && $currentUser['id'] == $id) {
            Flash::setFlash('Anda tidak dapat menghapus akun Anda sendiri.', 'danger');
            header('Location: ' . BASEURL . '/user');
            exit;
        }

        if ($this->model('User')->hapusDataUser($id, $this->tenantId()) > 0) {
            Flash::setFlash('Data pengguna berhasil dihapus.', 'success');
        } else {
            Flash::setFlash('Gagal menghapus data pengguna.', 'danger');
        }
        header('Location: ' . BASEURL . '/user');
        exit;
    }
}

