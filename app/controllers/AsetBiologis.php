<?php

class AsetBiologis extends Controller {

    public function __construct() {
        parent::__construct();
        if (!Auth::isLoggedIn()) {
            header('Location: ' . BASEURL . '/login');
            exit;
        }
    }

    public function index() {
        $data['judul'] = 'Manajemen Aset Biologis (PSAK 241)';
        
        $filters = [];
        if (!empty($_GET['kategori'])) $filters['kategori'] = $_GET['kategori'];
        if (!empty($_GET['status'])) $filters['status'] = $_GET['status'];
        
        $data['aset'] = $this->model('AsetBiologis')->getAllAsetBiologis($this->tenantId(), $filters);
        $data['akun'] = $this->model('Akun')->getAllAkun($this->tenantId());
        $data['ringkasan'] = $this->model('AsetBiologis')->getRingkasanKlasifikasi($this->tenantId());
        
        $this->view('templates/header', $data);
        $this->view('asetbiologis/index', $data);
        $this->view('templates/footer');
    }

    public function tambah() {
        $data['judul'] = 'Tambah Aset Biologis';
        $data['akun'] = $this->model('Akun')->getAllAkun($this->tenantId());
        $data['kode_otomatis'] = $this->generateAutoNumber('ABG', 'aset_biologis', 'kode_aset', $this->tenantId());
        
        $this->view('templates/header', $data);
        $this->view('asetbiologis/tambah', $data);
        $this->view('templates/footer');
    }

    public function detail($id) {
        $data['judul'] = 'Detail Aset Biologis';
        $data['aset'] = $this->model('AsetBiologis')->getAsetBiologisById($id, $this->tenantId());
        
        if (!$data['aset']) {
            Flash::setFlash('Data aset biologis tidak ditemukan.', 'danger');
            header('Location: ' . BASEURL . '/asetbiologis');
            exit;
        }
        
        $data['riwayat_penyesuaian'] = $this->model('AsetBiologis')->getRiwayatPenyesuaian($id, $this->tenantId());
        $data['riwayat_panen'] = $this->model('AsetBiologis')->getRiwayatPanen($id, $this->tenantId());
        $data['riwayat_pelepasan'] = $this->model('AsetBiologis')->getRiwayatPelepasan($id, $this->tenantId());

        $this->view('templates/header', $data);
        $this->view('asetbiologis/detail', $data);
        $this->view('templates/footer');
    }

    public function edit($id) {
        $data['judul'] = 'Edit Aset Biologis';
        $data['aset'] = $this->model('AsetBiologis')->getAsetBiologisById($id, $this->tenantId());
        $data['akun'] = $this->model('Akun')->getAllAkun($this->tenantId());

        $this->view('templates/header', $data);
        $this->view('asetbiologis/edit', $data);
        $this->view('templates/footer');
    }

    public function laporan() {
        $data['judul'] = 'Laporan Aset Biologis (PSAK 241)';
        $data['tanggal_mulai'] = $_GET['tanggal_mulai'] ?? date('Y-01-01');
        $data['tanggal_selesai'] = $_GET['tanggal_selesai'] ?? date('Y-m-d');

        $data['rekonsiliasi'] = $this->model('AsetBiologis')->getRekonsiliasiNilaiTercatat(
            $data['tanggal_mulai'], $data['tanggal_selesai'], $this->tenantId()
        );
        $data['ringkasan_klasifikasi'] = $this->model('AsetBiologis')->getRingkasanKlasifikasi($this->tenantId());

        $this->view('templates/header', $data);
        $this->view('asetbiologis/laporan', $data);
        $this->view('templates/footer');
    }

    // === Action Methods ===

    public function simpan() {
        $result = $this->model('AsetBiologis')->simpanAsetBiologis($_POST, $this->tenantId());
        if ($result) {
            Flash::setFlash('Aset biologis berhasil ditambahkan dengan jurnal pengakuan awal.', 'success');
        } else {
            Flash::setFlash('Gagal menambahkan aset biologis.', 'danger');
        }
        header('Location: ' . BASEURL . '/asetbiologis');
        exit;
    }

    public function update() {
        if ($this->model('AsetBiologis')->updateAsetBiologis($_POST, $this->tenantId()) > 0) {
            Flash::setFlash('Aset biologis berhasil diperbarui.', 'success');
        } else {
            Flash::setFlash('Tidak ada perubahan pada aset biologis.', 'info');
        }
        header('Location: ' . BASEURL . '/asetbiologis');
        exit;
    }

    public function hapus($id) {
        $result = $this->model('AsetBiologis')->hapusAsetBiologis($id, $this->tenantId());
        if ($result) {
            Flash::setFlash('Aset biologis berhasil dihapus.', 'success');
        } else {
            Flash::setFlash('Gagal menghapus. Aset memiliki transaksi terkait atau tidak dalam status Aktif.', 'danger');
        }
        header('Location: ' . BASEURL . '/asetbiologis');
        exit;
    }

    public function penyesuaianNilaiWajar() {
        $count = $this->model('AsetBiologis')->prosesNilaiWajar($_POST, $this->tenantId());
        if ($count > 0) {
            Flash::setFlash($count . ' aset biologis berhasil disesuaikan nilai wajarnya dengan jurnal otomatis.', 'success');
        } else {
            Flash::setFlash('Tidak ada penyesuaian yang diproses.', 'info');
        }
        header('Location: ' . BASEURL . '/asetbiologis');
        exit;
    }

    public function panen() {
        $result = $this->model('AsetBiologis')->prosesPanen($_POST, $this->tenantId());
        if ($result) {
            Flash::setFlash('Proses panen berhasil dicatat dengan jurnal otomatis ke persediaan.', 'success');
        } else {
            Flash::setFlash('Gagal mencatat proses panen.', 'danger');
        }
        header('Location: ' . BASEURL . '/asetbiologis');
        exit;
    }

    public function lepas() {
        $result = $this->model('AsetBiologis')->prosesPelepasan($_POST, $this->tenantId());
        if ($result) {
            Flash::setFlash('Proses pelepasan aset biologis berhasil dicatat dengan jurnal otomatis.', 'success');
        } else {
            Flash::setFlash('Gagal mencatat proses pelepasan.', 'danger');
        }
        header('Location: ' . BASEURL . '/asetbiologis');
        exit;
    }

    public function reklasifikasi($id) {
        $result = $this->model('AsetBiologis')->reklasifikasi($id, $this->tenantId());
        if ($result) {
            Flash::setFlash('Aset biologis berhasil direklasifikasi ke status Menghasilkan.', 'success');
        } else {
            Flash::setFlash('Gagal mereklasifikasi aset biologis.', 'danger');
        }
        header('Location: ' . BASEURL . '/asetbiologis/detail/' . $id);
        exit;
    }
}
