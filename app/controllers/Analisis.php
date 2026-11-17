<?php

class Analisis extends Controller {
    public function __construct() {
        parent::__construct();
        
        // Cek login & hak akses
        if (!Auth::isLoggedIn()) {
            header('Location: ' . BASEURL . '/login');
            exit;
        }

        if (!Auth::hasPermission('fin_laporan') && !Auth::isAdmin() && !Auth::isManager() && !Auth::isSuperadmin()) {
            Flash::setFlash('Akses Ditolak: Anda tidak memiliki izin untuk mengakses Modul Analisa Bisnis.', 'danger');
            header('Location: ' . BASEURL . '/dashboard');
            exit;
        }
    }

    /**
     * Dashboard Ikhtisar Analisa Bisnis (Overview & Executive Summary)
     */
    public function index() {
        $tenantId = $this->tenantId();
        $data['judul'] = 'Ikhtisar Analisa Bisnis';
        
        $data['tanggal_mulai'] = $_GET['tanggal_mulai'] ?? $_POST['tanggal_mulai'] ?? date('Y-01-01');
        $data['tanggal_selesai'] = $_GET['tanggal_selesai'] ?? $_POST['tanggal_selesai'] ?? date('Y-m-d');
        $data['id_unit'] = $_GET['id_unit'] ?? $_POST['id_unit'] ?? null;

        $analisisModel = $this->model('Analisis');
        
        // Data Rasio Keuangan & Kesehatan Usaha
        $data['rasio'] = $analisisModel->getRasioKeuangan($data['tanggal_mulai'], $data['tanggal_selesai'], $tenantId, $data['id_unit']);
        $data['kesehatan'] = $analisisModel->getKesehatanUsaha($data['tanggal_mulai'], $data['tanggal_selesai'], $tenantId, $data['id_unit']);
        
        // Status Jurnal Anomali & Transaksi Tanpa Jurnal
        $data['anomali'] = $analisisModel->getJurnalAnomali($tenantId, $data['tanggal_mulai'], $data['tanggal_selesai']);
        $data['tanpa_jurnal'] = $analisisModel->getTransaksiTanpaJurnal($tenantId);

        // Data Penunjang
        $data['periode'] = date('d M Y', strtotime($data['tanggal_mulai'])) . ' s/d ' . date('d M Y', strtotime($data['tanggal_selesai']));
        $data['perusahaan'] = $this->model('Perusahaan')->getPerusahaan($tenantId);
        $data['units'] = $this->model('Unit')->getAllUnits($tenantId);

        $this->view('templates/header', $data);
        $this->view('analisis/index', $data);
        $this->view('templates/footer');
    }

    /**
     * Feature A: Analisis Jurnal yang Anomali
     */
    public function anomali() {
        $tenantId = $this->tenantId();
        $data['judul'] = 'Analisis Jurnal Anomali';

        $data['tanggal_mulai'] = $_GET['tanggal_mulai'] ?? $_POST['tanggal_mulai'] ?? date('Y-01-01');
        $data['tanggal_selesai'] = $_GET['tanggal_selesai'] ?? $_POST['tanggal_selesai'] ?? date('Y-m-d');

        $analisisModel = $this->model('Analisis');
        $data['anomali'] = $analisisModel->getJurnalAnomali($tenantId, $data['tanggal_mulai'], $data['tanggal_selesai']);
        $data['periode'] = date('d M Y', strtotime($data['tanggal_mulai'])) . ' s/d ' . date('d M Y', strtotime($data['tanggal_selesai']));
        $data['perusahaan'] = $this->model('Perusahaan')->getPerusahaan($tenantId);

        $this->view('templates/header', $data);
        $this->view('analisis/anomali', $data);
        $this->view('templates/footer');
    }

    /**
     * Feature B: Transaksi Tanpa Jurnal
     */
    public function tanpaJurnal() {
        $tenantId = $this->tenantId();
        $data['judul'] = 'Transaksi Tanpa Jurnal';

        $data['filter_tipe'] = $_GET['tipe'] ?? 'semua';

        $analisisModel = $this->model('Analisis');
        $data['tanpa_jurnal'] = $analisisModel->getTransaksiTanpaJurnal($tenantId, $data['filter_tipe']);
        $data['perusahaan'] = $this->model('Perusahaan')->getPerusahaan($tenantId);

        $this->view('templates/header', $data);
        $this->view('analisis/tanpa_jurnal', $data);
        $this->view('templates/footer');
    }

    /**
     * Feature C: Analisis Rasio Keuangan
     */
    public function rasio() {
        $tenantId = $this->tenantId();
        $data['judul'] = 'Analisis Rasio Keuangan';

        $data['tanggal_mulai'] = $_GET['tanggal_mulai'] ?? $_POST['tanggal_mulai'] ?? date('Y-01-01');
        $data['tanggal_selesai'] = $_GET['tanggal_selesai'] ?? $_POST['tanggal_selesai'] ?? date('Y-m-d');
        $data['id_unit'] = $_GET['id_unit'] ?? $_POST['id_unit'] ?? null;

        $analisisModel = $this->model('Analisis');
        $data['rasio'] = $analisisModel->getRasioKeuangan($data['tanggal_mulai'], $data['tanggal_selesai'], $tenantId, $data['id_unit']);
        $data['periode'] = date('d M Y', strtotime($data['tanggal_mulai'])) . ' s/d ' . date('d M Y', strtotime($data['tanggal_selesai']));
        $data['perusahaan'] = $this->model('Perusahaan')->getPerusahaan($tenantId);
        $data['units'] = $this->model('Unit')->getAllUnits($tenantId);

        $this->view('templates/header', $data);
        $this->view('analisis/rasio', $data);
        $this->view('templates/footer');
    }

    /**
     * Feature D: Kesehatan Usaha (Diagnosis, Skor & Rekomendasi)
     */
    public function kesehatan() {
        $tenantId = $this->tenantId();
        $data['judul'] = 'Kesehatan Usaha & Diagnostik Finansial';

        $data['tanggal_mulai'] = $_GET['tanggal_mulai'] ?? $_POST['tanggal_mulai'] ?? date('Y-01-01');
        $data['tanggal_selesai'] = $_GET['tanggal_selesai'] ?? $_POST['tanggal_selesai'] ?? date('Y-m-d');
        $data['id_unit'] = $_GET['id_unit'] ?? $_POST['id_unit'] ?? null;

        $analisisModel = $this->model('Analisis');
        $data['kesehatan'] = $analisisModel->getKesehatanUsaha($data['tanggal_mulai'], $data['tanggal_selesai'], $tenantId, $data['id_unit']);
        $data['periode'] = date('d M Y', strtotime($data['tanggal_mulai'])) . ' s/d ' . date('d M Y', strtotime($data['tanggal_selesai']));
        $data['perusahaan'] = $this->model('Perusahaan')->getPerusahaan($tenantId);
        $data['units'] = $this->model('Unit')->getAllUnits($tenantId);

        // Siapkan data Radar Chart
        $radarLabels = [];
        $radarScores = [];
        foreach ($data['kesehatan']['pilar'] as $pilar) {
            $radarLabels[] = $pilar['nama'];
            $radarScores[] = $pilar['skor'];
        }
        $data['radar_labels'] = json_encode($radarLabels);
        $data['radar_scores'] = json_encode($radarScores);

        $this->view('templates/header', $data);
        $this->view('analisis/kesehatan', $data);
        $this->view('templates/footer');
    }

    /**
     * Action: 1-Click Sinkronisasi Jurnal Otomatis untuk Transaksi
     */
    public function sinkronkan() {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            header('Location: ' . BASEURL . '/analisis/tanpaJurnal');
            exit;
        }

        $tipe = $_POST['tipe'] ?? '';
        $idTransaksi = $_POST['id_transaksi'] ?? 0;
        $tenantId = $this->tenantId();

        try {
            $analisisModel = $this->model('Analisis');
            $idJurnal = $analisisModel->sinkronkanJurnalTransaksi($tipe, $idTransaksi, $tenantId);
            Flash::setFlash("Berhasil membuat dan menyinkronkan entri jurnal (ID Jurnal: #{$idJurnal}) untuk transaksi {$tipe}.", 'success');
        } catch (Exception $e) {
            Flash::setFlash("Gagal menyinkronkan jurnal: " . $e->getMessage(), 'danger');
        }

        header('Location: ' . BASEURL . '/analisis/tanpaJurnal');
        exit;
    }

    /**
     * Action: Pembersihan Anomali Database
     */
    public function perbaikiAnomali() {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            header('Location: ' . BASEURL . '/analisis/anomali');
            exit;
        }

        $aksi = $_POST['aksi'] ?? '';
        $idTarget = $_POST['id_target'] ?? 0;
        $tenantId = $this->tenantId();

        try {
            $analisisModel = $this->model('Analisis');
            $success = $analisisModel->bersihkanAnomali($aksi, $idTarget, $tenantId);
            if ($success) {
                Flash::setFlash("Data anomali berhasil dibersihkan dari basis data.", 'success');
            } else {
                Flash::setFlash("Operasi tidak menghasilkan perubahan.", 'warning');
            }
        } catch (Exception $e) {
            Flash::setFlash("Gagal membersihkan anomali: " . $e->getMessage(), 'danger');
        }

        header('Location: ' . BASEURL . '/analisis/anomali');
        exit;
    }

    /**
     * Cetak Laporan Kesehatan Usaha & Analisa Bisnis
     */
    public function cetakKesehatan() {
        $tenantId = $this->tenantId();
        $data['judul'] = 'Laporan Hasil Analisa Kesehatan Usaha';

        $data['tanggal_mulai'] = $_GET['tanggal_mulai'] ?? date('Y-01-01');
        $data['tanggal_selesai'] = $_GET['tanggal_selesai'] ?? date('Y-m-d');
        $data['id_unit'] = $_GET['id_unit'] ?? null;

        $analisisModel = $this->model('Analisis');
        $data['kesehatan'] = $analisisModel->getKesehatanUsaha($data['tanggal_mulai'], $data['tanggal_selesai'], $tenantId, $data['id_unit']);
        $data['periode'] = date('d M Y', strtotime($data['tanggal_mulai'])) . ' s/d ' . date('d M Y', strtotime($data['tanggal_selesai']));
        $data['perusahaan'] = $this->model('Perusahaan')->getPerusahaan($tenantId);

        $this->view('analisis/cetak_kesehatan', $data);
    }
}
