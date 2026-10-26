<?php

class Aset extends Controller {

    public function __construct() {
        parent::__construct();
        if (!Auth::isLoggedIn()) {
            header('Location: ' . BASEURL . '/login');
            exit;
        }
    }

    public function index() {
        $data['judul'] = 'Daftar Aset Tetap';
        $data['aset'] = $this->model('Aset')->getAllAset($this->tenantId());
        $data['akun'] = $this->model('Akun')->getAllAkun($this->tenantId());
        $data['incomplete_coa'] = $this->model('Aset')->getAsetWithIncompleteCoa($this->tenantId());
        
        $this->view('templates/header', $data);
        $this->view('aset/index', $data);
        $this->view('templates/footer');
    }

    public function tambah() {
        $data['judul'] = 'Tambah Aset Tetap';
        $data['akun'] = $this->model('Akun')->getAllAkun($this->tenantId());
        $data['kode_otomatis'] = $this->generateAutoNumber('AST', 'aset_tetap', 'kode_aset', $this->tenantId());
        
        // Ambil pengaturan default dari perusahaan
        $data['perusahaan'] = $this->model('Perusahaan')->getPerusahaan($this->tenantId());

        $this->view('templates/header', $data);
        $this->view('aset/tambah', $data);
        $this->view('templates/footer');
    }

    public function simpan() {
        if (empty($_POST['akun_aset']) || empty($_POST['akun_akumulasi']) || empty($_POST['akun_beban'])) {
            Flash::setFlash('Gagal menambahkan aset: Seluruh parameter COA (Akun Aset, Akun Akumulasi, dan Akun Beban) wajib diisi oleh pengguna!', 'danger');
            header('Location: ' . BASEURL . '/aset/tambah');
            exit;
        }

        if ($this->model('Aset')->simpanAset($_POST, $this->tenantId()) > 0) {
            Flash::setFlash('Aset tetap berhasil ditambahkan dengan parameter COA lengkap.', 'success');
        } else {
            Flash::setFlash('Gagal menambahkan aset tetap.', 'danger');
        }
        header('Location: ' . BASEURL . '/aset');
        exit;
    }

    public function edit($id) {
        $data['judul'] = 'Edit Aset Tetap';
        $data['aset'] = $this->model('Aset')->getAsetById($id, $this->tenantId());
        if (!$data['aset']) {
            Flash::setFlash('Data aset tetap tidak ditemukan.', 'danger');
            header('Location: ' . BASEURL . '/aset');
            exit;
        }
        $data['akun'] = $this->model('Akun')->getAllAkun($this->tenantId());

        $this->view('templates/header', $data);
        $this->view('aset/edit', $data);
        $this->view('templates/footer');
    }

    public function update() {
        $akun_aset = $_POST['akun_aset'] ?? '';
        $akun_akumulasi = $_POST['akun_akumulasi'] ?? $_POST['akun_akumulasi_depresiasi'] ?? '';
        $akun_beban = $_POST['akun_beban'] ?? $_POST['akun_beban_depresiasi'] ?? '';
        $id = $_POST['id'] ?? $_POST['id_aset'] ?? '';

        if (empty($akun_aset) || empty($akun_akumulasi) || empty($akun_beban)) {
            Flash::setFlash('Gagal memperbarui aset: Seluruh parameter COA (Akun Aset, Akun Akumulasi, dan Akun Beban) wajib diisi oleh pengguna!', 'danger');
            header('Location: ' . BASEURL . '/aset/edit/' . $id);
            exit;
        }

        if ($this->model('Aset')->updateAset($_POST, $this->tenantId()) > 0) {
            Flash::setFlash('Aset tetap dan parameter COA berhasil diperbarui.', 'success');
        } else {
            Flash::setFlash('Tidak ada perubahan pada data aset tetap.', 'info');
        }
        header('Location: ' . BASEURL . '/aset');
        exit;
    }

    public function settingCoa() {
        $id = $_POST['id_aset'] ?? $_POST['id'] ?? null;
        $akun_aset = trim($_POST['akun_aset'] ?? '');
        $akun_akumulasi = trim($_POST['akun_akumulasi'] ?? '');
        $akun_beban = trim($_POST['akun_beban'] ?? '');

        if (!$id || empty($akun_aset) || empty($akun_akumulasi) || empty($akun_beban)) {
            Flash::setFlash('Gagal menyimpan parameter COA: Seluruh parameter COA (Akun Aset, Akun Akumulasi, dan Akun Beban) wajib diisi oleh pengguna!', 'danger');
            header('Location: ' . BASEURL . '/aset');
            exit;
        }

        $result = $this->model('Aset')->updateCoaParameter($id, $akun_aset, $akun_akumulasi, $akun_beban, $this->tenantId());
        if ($result) {
            Flash::setFlash('Setting parameter COA aset berhasil diperbarui.', 'success');
        } else {
            Flash::setFlash('Gagal memperbarui parameter COA aset.', 'danger');
        }
        header('Location: ' . BASEURL . '/aset');
        exit;
    }

    public function hapus($id) {
        if ($this->model('Aset')->hapusAset($id, $this->tenantId()) > 0) {
            Flash::setFlash('Aset tetap berhasil dihapus.', 'success');
        }
        header('Location: ' . BASEURL . '/aset');
        exit;
    }

    public function susutkan() {
        $bulan = $_POST['bulan'] ?? date('m');
        $tahun = $_POST['tahun'] ?? date('Y');
        
        $result = $this->model('Aset')->prosesPenyusutan($bulan, $tahun, $this->tenantId());
        $count = is_array($result) ? $result['success_count'] : (int)$result;
        $skipped = is_array($result) ? $result['skipped_coa'] : 0;
        
        if ($count > 0 && $skipped > 0) {
            Flash::setFlash($count . ' aset berhasil disusutkan untuk periode ' . $bulan . '/' . $tahun . '. Catatan: Terdapat ' . $skipped . ' aset dilewati karena parameter COA belum diatur pengguna.', 'warning');
        } elseif ($count > 0) {
            Flash::setFlash($count . ' aset berhasil disusutkan untuk periode ' . $bulan . '/' . $tahun . '.', 'success');
        } elseif ($skipped > 0) {
            Flash::setFlash('Penyusutan tidak dapat diproses: Terdapat ' . $skipped . ' aset aktif yang parameter COA-nya belum lengkap. Harap atur parameter COA pada daftar aset terlebih dahulu.', 'danger');
        } else {
            Flash::setFlash('Tidak ada aset yang perlu disusutkan atau semua aset sudah disusutkan untuk periode ini.', 'info');
        }
        header('Location: ' . BASEURL . '/aset');
        exit;
    }
}
