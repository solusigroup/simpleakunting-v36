<?php

class SimpanPinjam extends Controller {

    public function __construct() {
        parent::__construct();
        if (!Auth::isLoggedIn()) {
            header('Location: ' . BASEURL . '/login');
            exit;
        }

        // Pastikan tabel database tersedia
        $this->model('SimpanPinjam')->ensureTablesExist($this->tenantId());
    }

    /**
     * Dashboard Utama Simpan Pinjam
     */
    public function index() {
        $spModel = $this->model('SimpanPinjam');
        $data['judul'] = 'Unit Jasa Keuangan (Simpan Pinjam)';
        $data['pengaturan'] = $spModel->getPengaturan($this->tenantId());
        $data['stats'] = $spModel->getDashboardStats($this->tenantId());
        $data['pinjaman_terbaru'] = $spModel->getAllPinjaman($this->tenantId());
        $data['transaksi_terbaru'] = $spModel->getRiwayatTransaksiSimpanan($this->tenantId());

        $this->view('templates/header', $data);
        $this->view('simpanpinjam/index', $data);
        $this->view('templates/footer');
    }

    /**
     * Saklar Cepat Aktif - Non Aktif
     */
    public function toggle_status() {
        $spModel = $this->model('SimpanPinjam');
        $newStatus = $spModel->toggleStatusAktif($this->tenantId());
        $statusStr = $newStatus ? 'diaktifkan' : 'dinonaktifkan';
        Flash::setFlash('Berhasil', "Unit Usaha Jasa Keuangan (Simpan Pinjam) berhasil {$statusStr}.", 'success');
        header('Location: ' . BASEURL . '/simpanpinjam');
        exit;
    }

    // ==========================================
    // PENGATURAN & PARAMETER CHART OF ACCOUNT (COA)
    // ==========================================

    public function pengaturan() {
        $spModel = $this->model('SimpanPinjam');
        $akunModel = $this->model('Akun');
        $tenantId = $this->tenantId();

        $data['judul'] = 'Pengaturan Parameter & COA Simpan Pinjam';
        $data['pengaturan'] = $spModel->getPengaturan($tenantId);
        
        // Akun Kas & Bank murni DETAIL (Aset Kas & Bank, bukan Header)
        $data['akun_kas_bank'] = $akunModel->getAkunKasBank($tenantId);
        
        // Ambil semua akun detail (mengecualikan tipe Header) untuk mapping yang aman & akurat
        $semuaAkun = $akunModel->getAllAkun($tenantId);
        $detailAkun = array_values(array_filter($semuaAkun, function($a) {
            return ($a['tipe_akun'] ?? '') !== 'Header';
        }));

        $data['akun_detail'] = $detailAkun;
        
        // Klasifikasi akun detail per kelompok untuk memudahkan pilihan user
        $data['akun_piutang'] = array_values(array_filter($detailAkun, function($a) {
            return str_starts_with($a['kode_akun'], '1-');
        }));
        $data['akun_kewajiban'] = array_values(array_filter($detailAkun, function($a) {
            return str_starts_with($a['kode_akun'], '2-');
        }));
        $data['akun_ekuitas'] = array_values(array_filter($detailAkun, function($a) {
            return str_starts_with($a['kode_akun'], '3-');
        }));
        $data['akun_pendapatan'] = array_values(array_filter($detailAkun, function($a) {
            return str_starts_with($a['kode_akun'], '4-') || str_starts_with($a['kode_akun'], '8-');
        }));
        $data['akun_beban'] = array_values(array_filter($detailAkun, function($a) {
            return str_starts_with($a['kode_akun'], '5-') || str_starts_with($a['kode_akun'], '6-') || str_starts_with($a['kode_akun'], '9-');
        }));

        $this->view('templates/header', $data);
        $this->view('simpanpinjam/pengaturan', $data);
        $this->view('templates/footer');
    }

    public function pengaturan_simpan() {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            header('Location: ' . BASEURL . '/simpanpinjam/pengaturan');
            exit;
        }

        $spModel = $this->model('SimpanPinjam');
        $spModel->simpanPengaturan($_POST, $this->tenantId());
        Flash::setFlash('Berhasil', 'Pengaturan parameter & COA Simpan Pinjam berhasil disimpan.', 'success');
        header('Location: ' . BASEURL . '/simpanpinjam/pengaturan');
        exit;
    }

    public function seed_coa() {
        $spModel = $this->model('SimpanPinjam');
        $count = $spModel->seedBaganAkun($this->tenantId());
        if ($count > 0) {
            Flash::setFlash('Berhasil', "Sebanyak {$count} akun standar Simpan Pinjam berhasil ditambahkan ke Bagan Akun (COA).", 'success');
        } else {
            Flash::setFlash('Info', 'Seluruh akun standar Simpan Pinjam sudah ada di sistem.', 'info');
        }
        header('Location: ' . BASEURL . '/simpanpinjam/pengaturan');
        exit;
    }

    // ==========================================
    // NASABAH (PERORANGAN & KELOMPOK)
    // ==========================================

    public function nasabah() {
        $spModel = $this->model('SimpanPinjam');
        $data['judul'] = 'Data Nasabah & Kelompok';
        $data['nasabah'] = $spModel->getAllNasabah($this->tenantId());
        $data['kelompok'] = $spModel->getAllKelompok($this->tenantId());
        $data['no_anggota_baru'] = $spModel->generateNoAnggota($this->tenantId());
        $data['kode_kelompok_baru'] = $spModel->generateKodeKelompok($this->tenantId());

        $this->view('templates/header', $data);
        $this->view('simpanpinjam/nasabah', $data);
        $this->view('templates/footer');
    }

    public function nasabah_simpan() {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            header('Location: ' . BASEURL . '/simpanpinjam/nasabah');
            exit;
        }

        $spModel = $this->model('SimpanPinjam');
        if (!empty($_POST['id_nasabah'])) {
            $spModel->ubahNasabah($_POST, $this->tenantId());
            Flash::setFlash('Berhasil', 'Data nasabah berhasil diperbarui.', 'success');
        } else {
            $spModel->tambahNasabah($_POST, $this->tenantId());
            Flash::setFlash('Berhasil', 'Nasabah baru berhasil didaftarkan.', 'success');
        }
        header('Location: ' . BASEURL . '/simpanpinjam/nasabah');
        exit;
    }

    public function nasabah_hapus($id) {
        $spModel = $this->model('SimpanPinjam');
        $res = $spModel->hapusNasabah($id, $this->tenantId());
        if ($res === -1) {
            Flash::setFlash('Gagal', 'Nasabah tidak dapat dihapus karena masih memiliki pinjaman aktif.', 'danger');
        } elseif ($res === -2) {
            Flash::setFlash('Gagal', 'Nasabah tidak dapat dihapus karena masih memiliki saldo simpanan.', 'danger');
        } elseif ($res > 0) {
            Flash::setFlash('Berhasil', 'Data nasabah berhasil dihapus.', 'success');
        } else {
            Flash::setFlash('Gagal', 'Gagal menghapus data nasabah.', 'danger');
        }
        header('Location: ' . BASEURL . '/simpanpinjam/nasabah');
        exit;
    }

    public function kelompok_simpan() {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            header('Location: ' . BASEURL . '/simpanpinjam/nasabah');
            exit;
        }

        $spModel = $this->model('SimpanPinjam');
        if (!empty($_POST['id_kelompok'])) {
            $spModel->ubahKelompok($_POST, $this->tenantId());
            Flash::setFlash('Berhasil', 'Data kelompok berhasil diperbarui.', 'success');
        } else {
            $spModel->tambahKelompok($_POST, $this->tenantId());
            Flash::setFlash('Berhasil', 'Kelompok baru berhasil ditambahkan.', 'success');
        }
        header('Location: ' . BASEURL . '/simpanpinjam/nasabah');
        exit;
    }

    public function kelompok_detail($id) {
        $spModel = $this->model('SimpanPinjam');
        $data['kelompok'] = $spModel->getKelompokById($id, $this->tenantId());
        if (!$data['kelompok']) {
            Flash::setFlash('Gagal', 'Kelompok tidak ditemukan.', 'danger');
            header('Location: ' . BASEURL . '/simpanpinjam/nasabah');
            exit;
        }

        $data['judul'] = 'Detail Kelompok: ' . $data['kelompok']['nama_kelompok'];
        // Nasabah yang belum punya kelompok untuk opsi tambah anggota
        $data['nasabah_tersedia'] = array_filter($spModel->getAllNasabah($this->tenantId()), function($n) {
            return empty($n['id_kelompok']);
        });

        $this->view('templates/header', $data);
        $this->view('simpanpinjam/kelompok_detail', $data);
        $this->view('templates/footer');
    }

    public function kelompok_tambah_anggota() {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            header('Location: ' . BASEURL . '/simpanpinjam/nasabah');
            exit;
        }

        $spModel = $this->model('SimpanPinjam');
        $spModel->assignAnggotaKelompok($_POST['id_nasabah'], $_POST['id_kelompok'], $_POST['peran_kelompok'], $this->tenantId());
        Flash::setFlash('Berhasil', 'Anggota berhasil ditambahkan ke dalam kelompok.', 'success');
        header('Location: ' . BASEURL . '/simpanpinjam/kelompok_detail/' . $_POST['id_kelompok']);
        exit;
    }

    public function kelompok_hapus_anggota($id_nasabah, $id_kelompok) {
        $spModel = $this->model('SimpanPinjam');
        $spModel->removeAnggotaKelompok($id_nasabah, $this->tenantId());
        Flash::setFlash('Berhasil', 'Anggota telah dikeluarkan dari kelompok.', 'success');
        header('Location: ' . BASEURL . '/simpanpinjam/kelompok_detail/' . $id_kelompok);
        exit;
    }

    public function kelompok_hapus($id) {
        $spModel = $this->model('SimpanPinjam');
        $res = $spModel->hapusKelompok($id, $this->tenantId());
        if ($res === -1) {
            Flash::setFlash('Gagal', 'Kelompok tidak dapat dihapus karena masih memiliki pinjaman kelompok aktif.', 'danger');
        } elseif ($res > 0) {
            Flash::setFlash('Berhasil', 'Kelompok berhasil dihapus.', 'success');
        } else {
            Flash::setFlash('Gagal', 'Gagal menghapus kelompok.', 'danger');
        }
        header('Location: ' . BASEURL . '/simpanpinjam/nasabah');
        exit;
    }

    // ==========================================
    // SIMPANAN (PRODUK, REKENING, SETOR/TARIK, LAPORAN)
    // ==========================================

    public function simpanan() {
        $spModel = $this->model('SimpanPinjam');
        $akunModel = $this->model('Akun');
        $tenantId = $this->tenantId();

        $data['judul'] = 'Simpanan Nasabah';
        $data['rekening'] = $spModel->getAllRekeningSimpanan($tenantId);
        $data['jenis_simpanan'] = $spModel->getAllJenisSimpanan($tenantId);
        $data['nasabah'] = $spModel->getAllNasabah($tenantId);
        $data['akun_kas'] = $akunModel->getAkunKasBank($tenantId);
        $data['no_rek_baru'] = $spModel->generateNoRekening($tenantId);

        // Akun detail non-header untuk COA produk simpanan
        $semuaAkun = $akunModel->getAllAkun($tenantId);
        $data['akun_detail'] = array_values(array_filter($semuaAkun, function($a) {
            return ($a['tipe_akun'] ?? '') !== 'Header';
        }));

        $this->view('templates/header', $data);
        $this->view('simpanpinjam/simpanan', $data);
        $this->view('templates/footer');
    }

    public function jenis_simpanan_simpan() {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            header('Location: ' . BASEURL . '/simpanpinjam/simpanan');
            exit;
        }

        $spModel = $this->model('SimpanPinjam');
        $spModel->tambahJenisSimpanan($_POST, $this->tenantId());
        Flash::setFlash('Berhasil', 'Produk simpanan berhasil disimpan.', 'success');
        header('Location: ' . BASEURL . '/simpanpinjam/simpanan');
        exit;
    }

    public function jenis_simpanan_hapus($id) {
        $spModel = $this->model('SimpanPinjam');
        $res = $spModel->hapusJenisSimpanan($id, $this->tenantId());
        if ($res === -1) {
            Flash::setFlash('Gagal', 'Produk simpanan tidak dapat dihapus karena sudah memiliki rekening nasabah.', 'danger');
        } else {
            Flash::setFlash('Berhasil', 'Produk simpanan berhasil dihapus.', 'success');
        }
        header('Location: ' . BASEURL . '/simpanpinjam/simpanan');
        exit;
    }

    public function rekening_buka() {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            header('Location: ' . BASEURL . '/simpanpinjam/simpanan');
            exit;
        }

        $spModel = $this->model('SimpanPinjam');
        try {
            $spModel->bukaRekening($_POST, $this->tenantId());
            Flash::setFlash('Berhasil', 'Rekening simpanan baru berhasil dibuka.', 'success');
        } catch (\Exception $e) {
            Flash::setFlash('Gagal', $e->getMessage(), 'danger');
        }
        header('Location: ' . BASEURL . '/simpanpinjam/simpanan');
        exit;
    }

    public function simpanan_transaksi() {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            header('Location: ' . BASEURL . '/simpanpinjam/simpanan');
            exit;
        }

        $spModel = $this->model('SimpanPinjam');
        try {
            $spModel->prosesTransaksiSimpanan($_POST, $this->tenantId());
            $jenis = $_POST['jenis_transaksi'];
            Flash::setFlash('Berhasil', "Transaksi {$jenis} simpanan sebesar Rp " . number_format($_POST['nominal'], 0, ',', '.') . " berhasil diproses.", 'success');
        } catch (\Exception $e) {
            Flash::setFlash('Gagal', $e->getMessage(), 'danger');
        }
        header('Location: ' . BASEURL . '/simpanpinjam/simpanan');
        exit;
    }

    public function buku_tabungan($id_rekening) {
        $spModel = $this->model('SimpanPinjam');
        $data['rekening'] = $spModel->getRekeningById($id_rekening, $this->tenantId());
        if (!$data['rekening']) {
            Flash::setFlash('Gagal', 'Rekening tidak ditemukan.', 'danger');
            header('Location: ' . BASEURL . '/simpanpinjam/simpanan');
            exit;
        }

        $data['judul'] = 'Buku Tabungan / Mutasi Simpanan: ' . $data['rekening']['no_rekening'];
        $data['mutasi'] = $spModel->getRiwayatTransaksiSimpanan($this->tenantId(), $id_rekening);
        $data['akun_kas'] = $this->model('Akun')->getAkunKasBank($this->tenantId());

        $this->view('templates/header', $data);
        $this->view('simpanpinjam/buku_tabungan', $data);
        $this->view('templates/footer');
    }

    public function laporan_simpanan() {
        $spModel = $this->model('SimpanPinjam');
        $data['judul'] = 'Laporan Rekapitulasi & Mutasi Simpanan';
        $tglMulai = $_GET['tgl_mulai'] ?? date('Y-m-01');
        $tglSelesai = $_GET['tgl_selesai'] ?? date('Y-m-d');
        
        $data['tgl_mulai'] = $tglMulai;
        $data['tgl_selesai'] = $tglSelesai;
        $data['rekening'] = $spModel->getAllRekeningSimpanan($this->tenantId());
        $data['transaksi'] = $spModel->getRiwayatTransaksiSimpanan($this->tenantId(), null, $tglMulai, $tglSelesai);

        $this->view('templates/header', $data);
        $this->view('simpanpinjam/laporan_simpanan', $data);
        $this->view('templates/footer');
    }

    // ==========================================
    // PINJAMAN (PRODUK, PENGAJUAN, PERSETUJUAN, ANGSURAN, LAPORAN)
    // ==========================================

    public function pinjaman() {
        $spModel = $this->model('SimpanPinjam');
        $data['judul'] = 'Data Pinjaman & Pembiayaan';
        $status = $_GET['status'] ?? null;
        $data['status_filter'] = $status;
        $data['pinjaman'] = $spModel->getAllPinjaman($this->tenantId(), $status);
        $data['jenis_pinjaman'] = $spModel->getAllJenisPinjaman($this->tenantId());
        $data['nasabah'] = $spModel->getAllNasabah($this->tenantId());
        $data['kelompok'] = $spModel->getAllKelompok($this->tenantId());
        $data['no_pinj_baru'] = $spModel->generateNoPinjaman($this->tenantId());

        $this->view('templates/header', $data);
        $this->view('simpanpinjam/pinjaman', $data);
        $this->view('templates/footer');
    }

    public function jenis_pinjaman_simpan() {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            header('Location: ' . BASEURL . '/simpanpinjam/pinjaman');
            exit;
        }

        $spModel = $this->model('SimpanPinjam');
        $spModel->tambahJenisPinjaman($_POST, $this->tenantId());
        Flash::setFlash('Berhasil', 'Produk pinjaman berhasil disimpan.', 'success');
        header('Location: ' . BASEURL . '/simpanpinjam/pinjaman');
        exit;
    }

    public function jenis_pinjaman_hapus($id) {
        $spModel = $this->model('SimpanPinjam');
        $res = $spModel->hapusJenisPinjaman($id, $this->tenantId());
        if ($res === -1) {
            Flash::setFlash('Gagal', 'Produk pinjaman tidak dapat dihapus karena sudah memiliki data pinjaman nasabah.', 'danger');
        } else {
            Flash::setFlash('Berhasil', 'Produk pinjaman berhasil dihapus.', 'success');
        }
        header('Location: ' . BASEURL . '/simpanpinjam/pinjaman');
        exit;
    }

    public function pinjaman_ajukan() {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            header('Location: ' . BASEURL . '/simpanpinjam/pinjaman');
            exit;
        }

        $spModel = $this->model('SimpanPinjam');
        try {
            $id = $spModel->ajukanPinjaman($_POST, $this->tenantId());
            Flash::setFlash('Berhasil', 'Pengajuan pinjaman baru berhasil disimpan.', 'success');
            header('Location: ' . BASEURL . '/simpanpinjam/pinjaman_detail/' . $id);
            exit;
        } catch (\Exception $e) {
            Flash::setFlash('Gagal', $e->getMessage(), 'danger');
            header('Location: ' . BASEURL . '/simpanpinjam/pinjaman');
            exit;
        }
    }

    public function pinjaman_detail($id) {
        $spModel = $this->model('SimpanPinjam');
        $data['pinjaman'] = $spModel->getPinjamanById($id, $this->tenantId());
        if (!$data['pinjaman']) {
            Flash::setFlash('Gagal', 'Pinjaman tidak ditemukan.', 'danger');
            header('Location: ' . BASEURL . '/simpanpinjam/pinjaman');
            exit;
        }

        $data['judul'] = 'Detail Pinjaman: ' . $data['pinjaman']['no_pinjaman'];
        $data['akun_kas'] = $this->model('Akun')->getAkunKasBank($this->tenantId());
        $data['pengaturan'] = $spModel->getPengaturan($this->tenantId());

        $this->view('templates/header', $data);
        $this->view('simpanpinjam/pinjaman_detail', $data);
        $this->view('templates/footer');
    }

    public function pinjaman_persetujuan() {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            header('Location: ' . BASEURL . '/simpanpinjam/pinjaman');
            exit;
        }

        $spModel = $this->model('SimpanPinjam');
        try {
            $id = $_POST['id_pinjaman'];
            $status = $_POST['status']; // Disetujui / Ditolak
            $nominal = $_POST['jumlah_disetujui'] ?? 0;
            $catatan = $_POST['catatan_persetujuan'] ?? '';

            $spModel->prosesPersetujuanPinjaman($id, $status, $nominal, $catatan, $this->tenantId());
            Flash::setFlash('Berhasil', "Status pinjaman berhasil diperbarui menjadi '{$status}'.", 'success');
            header('Location: ' . BASEURL . '/simpanpinjam/pinjaman_detail/' . $id);
            exit;
        } catch (\Exception $e) {
            Flash::setFlash('Gagal', $e->getMessage(), 'danger');
            header('Location: ' . BASEURL . '/simpanpinjam/pinjaman');
            exit;
        }
    }

    public function pinjaman_cairkan() {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            header('Location: ' . BASEURL . '/simpanpinjam/pinjaman');
            exit;
        }

        $spModel = $this->model('SimpanPinjam');
        try {
            $id = $_POST['id_pinjaman'];
            $kodeKas = $_POST['kode_akun_kas_pencairan'];
            $tglCair = $_POST['tanggal_pencairan'] ?? date('Y-m-d');

            $spModel->cairkanPinjaman($id, $kodeKas, $tglCair, $this->tenantId());
            Flash::setFlash('Berhasil', 'Pinjaman berhasil dicairkan, jadwal angsuran dibuat, dan jurnal pengeluaran kas telah dibukukan.', 'success');
            header('Location: ' . BASEURL . '/simpanpinjam/pinjaman_detail/' . $id);
            exit;
        } catch (\Exception $e) {
            Flash::setFlash('Gagal', $e->getMessage(), 'danger');
            header('Location: ' . BASEURL . '/simpanpinjam/pinjaman_detail/' . $_POST['id_pinjaman']);
            exit;
        }
    }

    public function angsuran_bayar() {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            header('Location: ' . BASEURL . '/simpanpinjam/pinjaman');
            exit;
        }

        $spModel = $this->model('SimpanPinjam');
        try {
            $idJadwal = $_POST['id_jadwal'];
            $idPinjaman = $_POST['id_pinjaman'];
            $spModel->bayarAngsuran($idJadwal, $_POST, $this->tenantId());
            Flash::setFlash('Berhasil', 'Pembayaran angsuran berhasil diterima dan dibukukan.', 'success');
            header('Location: ' . BASEURL . '/simpanpinjam/pinjaman_detail/' . $idPinjaman);
            exit;
        } catch (\Exception $e) {
            Flash::setFlash('Gagal', $e->getMessage(), 'danger');
            header('Location: ' . BASEURL . '/simpanpinjam/pinjaman_detail/' . $_POST['id_pinjaman']);
            exit;
        }
    }

    public function laporan_pinjaman() {
        $spModel = $this->model('SimpanPinjam');
        $data['judul'] = 'Laporan Kolektibilitas & Outstanding Pinjaman';
        $tglCutOff = $_GET['cut_off'] ?? date('Y-m-d');
        $data['cut_off'] = $tglCutOff;
        $data['laporan'] = $spModel->getLaporanKolektibilitas($this->tenantId(), $tglCutOff);

        $this->view('templates/header', $data);
        $this->view('simpanpinjam/laporan_pinjaman', $data);
        $this->view('templates/footer');
    }
}
