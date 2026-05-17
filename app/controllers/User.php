<?php

class User extends Controller {
    /**
     * Constructor ini berjalan secara otomatis setiap kali controller User dipanggil.
     * Fungsinya sebagai gerbang keamanan.
     */
    public function __construct()
    {
        // **PERBAIKAN: Jalankan constructor dari Controller induk terlebih dahulu**
        // Ini akan memastikan koneksi database ($this->db) dibuat.
        parent::__construct();

        // Setelah itu, baru jalankan logika proteksi role.
        if (Auth::user()['role'] !== 'Admin' && Auth::user()['role'] !== 'Superadmin' && Auth::user()['role'] !== 'Manager') {
            Flash::setFlash('Anda tidak memiliki hak akses untuk halaman ini.', 'danger');
            header('Location: ' . BASEURL);
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
        // Cek apakah username sudah ada
        if ($this->model('User')->getUserByUsername($_POST['nama_user'])) {
            Flash::setFlash('Gagal', 'Username "' . $_POST['nama_user'] . '" sudah digunakan.', 'danger');
            header('Location: ' . BASEURL . '/user');
            exit;
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

        if ($data['user']['nama_user'] === 'superadmin') {
            Flash::setFlash('User Superadmin tidak dapat diubah.', 'danger');
            header('Location: ' . BASEURL . '/user');
            exit;
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
        $user = $this->model('User')->getUserById($_POST['id_user'], $this->tenantId());
        
        if (!$user) {
            Flash::setFlash('Pengguna tidak ditemukan atau Anda tidak memiliki akses.', 'danger');
            header('Location: ' . BASEURL . '/user');
            exit;
        }

        if ($user['nama_user'] === 'superadmin') {
            Flash::setFlash('User Superadmin tidak dapat diubah.', 'danger');
            header('Location: ' . BASEURL . '/user');
            exit;
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
        if ($this->model('User')->hapusDataUser($id, $this->tenantId()) > 0) {
            Flash::setFlash('Data pengguna berhasil dihapus.', 'success');
        } else {
            Flash::setFlash('Gagal menghapus data pengguna.', 'danger');
        }
        header('Location: ' . BASEURL . '/user');
        exit;
    }
}

