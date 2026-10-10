<?php

class TutupBuku extends Controller {
    public function __construct() {
        parent::__construct();
        if (!Auth::hasPermission('fin_tutup_buku') && !Auth::isAdmin() && !Auth::isManager()) {
            Flash::setFlash('Anda tidak memiliki izin untuk mengakses halaman Tutup Buku.', 'danger');
            header('Location: ' . BASEURL . '/dashboard');
            exit;
        }
    }

    public function index() {
        $data['judul'] = 'Tutup Buku Akhir Periode';
        
        $tutupBukuModel = $this->model('TutupBuku');
        $tenantId = $this->tenantId();

        $latestClosed = $tutupBukuModel->getLatestClosedPeriod($tenantId);
        $data['latest_closed'] = $latestClosed;

        if ($latestClosed) {
            $nextMonth = date('Y-m', strtotime($latestClosed['tahun'] . '-' . str_pad($latestClosed['bulan'], 2, '0', STR_PAD_LEFT) . '-01 +1 month'));
        } else {
            $nextMonth = date('Y-m', strtotime('first day of last month'));
        }
        $data['periode_bulanan_next'] = $nextMonth;
        
        // Ambil riwayat tutup buku
        $data['riwayat'] = $tutupBukuModel->getAllClosedPeriods($tenantId);
        
        // Informasi akun kontrol
        $data['accounts'] = $tutupBukuModel->resolveClosingAccounts($tenantId);

        $data['preview'] = null;

        if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['tipe_proses'])) {
            $data['tipe_proses'] = $_POST['tipe_proses'];
            $data['periode_raw'] = trim($_POST['periode']); // YYYY-MM untuk bulanan, YYYY untuk tahunan

            if ($data['tipe_proses'] === 'Bulanan') {
                $data['periode_label'] = date('F Y', strtotime($data['periode_raw'] . '-01'));
                $data['preview'] = $tutupBukuModel->getClosingJournalPreview($data['periode_raw'], $tenantId, false);
            } else { // Tahunan
                $data['periode_label'] = 'Tahun ' . $data['periode_raw'];
                $data['preview'] = $tutupBukuModel->getClosingJournalPreview($data['periode_raw'], $tenantId, true);
            }
        }
        
        $this->view('templates/header', $data);
        $this->view('tutupbuku/index', $data);
        $this->view('templates/footer');
    }
    
    public function proses() {
        if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['periode'])) {
            $tutupBukuModel = $this->model('TutupBuku');
            $userId = Auth::user()['id_user'] ?? null;
            $periode = trim($_POST['periode']);
            $tipeProses = $_POST['tipe_proses'] ?? 'Bulanan';

            try {
                $result = $tutupBukuModel->prosesTutupBuku($periode, $tipeProses, $this->tenantId(), $userId);
                if ($result['status']) {
                    Flash::setFlash("Proses tutup buku periode {$periode} ({$tipeProses}) berhasil diselesaikan. Periode telah dikunci dan jurnal penutup otomatis dibuat.", 'success');
                }
            } catch (Exception $e) {
                Flash::setFlash('Gagal memproses tutup buku: ' . $e->getMessage(), 'danger');
            }
        }
        header('Location: ' . BASEURL . '/tutupbuku');
        exit;
    }

    public function bukaKembali($id) {
        if (!Auth::isAdmin() && !Auth::isManager()) {
            Flash::setFlash('Hanya Admin atau Manajer yang berwenang membuka kembali periode akuntansi.', 'danger');
            header('Location: ' . BASEURL . '/tutupbuku');
            exit;
        }

        $tutupBukuModel = $this->model('TutupBuku');
        try {
            if ($tutupBukuModel->bukaKembaliPeriode($id, $this->tenantId())) {
                Flash::setFlash('Periode berhasil dibuka kembali. Jurnal penutup terkait telah dihapus dan transaksi pada periode tersebut kini dapat disesuaikan kembali.', 'success');
            }
        } catch (Exception $e) {
            Flash::setFlash('Gagal membuka kembali periode: ' . $e->getMessage(), 'danger');
        }

        header('Location: ' . BASEURL . '/tutupbuku');
        exit;
    }
}
