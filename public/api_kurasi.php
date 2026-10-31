<?php
/**
 * API Backend Terpusat Kurasi BUMDesa - SimpleAkunting v3.6
 * Menangani pendaftaran BUMDesa, perhitungan skor otomatis,
 * penyimpanan database MySQL terpusat, dan otentikasi kurator.
 */

// Muat konfigurasi sistem SimpleAkunting
$configFile = __DIR__ . '/app/config.php';
if (!file_exists($configFile)) {
    $configFile = __DIR__ . '/../app/config.php';
}
if (file_exists($configFile)) {
    require_once $configFile;
}

if (!headers_sent()) {
    header('Content-Type: application/json; charset=utf-8');
    header('Access-Control-Allow-Origin: *');
    header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
    header('Access-Control-Allow-Headers: Content-Type, Authorization, X-Requested-With, X-Curator-Key, X-Evaluator-Key');
}

$reqMethod = $_SERVER['REQUEST_METHOD'] ?? 'GET';
if ($reqMethod === 'OPTIONS') {
    http_response_code(200);
    exit;
}

// Password Kurator Terpusat SimpleAkunting
if (!defined('CURATOR_PASSWORD')) {
    define('CURATOR_PASSWORD', '548412Yaa');
}

try {
    $dsn = 'mysql:host=' . DB_HOST . ';dbname=' . DB_NAME . ';charset=utf8mb4';
    $pdo = new PDO($dsn, DB_USER, DB_PASS, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_PERSISTENT => false
    ]);

    // Auto-migration: Buat tabel kurasi_bumdesa jika belum ada
    $createTableQuery = "CREATE TABLE IF NOT EXISTS `kurasi_bumdesa` (
        `id` INT AUTO_INCREMENT PRIMARY KEY,
        `nomor_registrasi` VARCHAR(50) NOT NULL UNIQUE,
        `nama_bumdes` VARCHAR(255) NOT NULL,
        `nama_direktur` VARCHAR(255) NOT NULL,
        `kontak` VARCHAR(100) NOT NULL,
        `unit_usaha` VARCHAR(255) NOT NULL,
        `desa` VARCHAR(255) NOT NULL,
        `kabupaten` VARCHAR(255) NOT NULL,
        `provinsi` VARCHAR(100) NOT NULL,
        `sk_operator` VARCHAR(10) NOT NULL DEFAULT '1',
        `infrastruktur` VARCHAR(10) NOT NULL DEFAULT '1',
        `kontribusi` VARCHAR(10) NOT NULL DEFAULT '1',
        `nda_agreement` VARCHAR(10) NOT NULL DEFAULT '1',
        `score` INT NOT NULL DEFAULT 0,
        `status` VARCHAR(50) NOT NULL DEFAULT 'Cadangan',
        `catatan` TEXT NULL,
        `ip_address` VARCHAR(45) NULL,
        `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
        `updated_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        INDEX idx_bumdes (`nama_bumdes`),
        INDEX idx_kabupaten (`kabupaten`),
        INDEX idx_provinsi (`provinsi`),
        INDEX idx_status (`status`),
        INDEX idx_created (`created_at`)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;";
    
    $pdo->exec($createTableQuery);

    // Auto-migration: Tambahkan kolom sertifikat kelayakan dan kredensial SaaS jika belum ada
    $certColumns = [
        'nda_signed' => "VARCHAR(20) NOT NULL DEFAULT 'Belum'",
        'spk_signed' => "VARCHAR(20) NOT NULL DEFAULT 'Belum'",
        'pakta_integritas' => "VARCHAR(20) NOT NULL DEFAULT 'Belum'",
        'kode_bisnis' => "VARCHAR(50) NULL",
        'saas_username' => "VARCHAR(100) NULL",
        'saas_password' => "VARCHAR(100) NULL",
        'no_sertifikat' => "VARCHAR(100) NULL",
        'tanggal_sertifikat' => "DATE NULL"
    ];
    foreach ($certColumns as $col => $definition) {
        $checkCol = $pdo->query("SHOW COLUMNS FROM `kurasi_bumdesa` LIKE '{$col}'")->fetch();
        if (!$checkCol) {
            $pdo->exec("ALTER TABLE `kurasi_bumdesa` ADD COLUMN `{$col}` {$definition}");
        }
    }

} catch (Exception $e) {
    echo json_encode([
        'success' => false, 
        'message' => 'Gagal terhubung ke database MySQL: ' . $e->getMessage()
    ]);
    exit;
}

// Ambil input JSON atau POST/GET parameters
$inputRaw = file_get_contents('php://input');
$inputData = json_decode($inputRaw, true);
if (!is_array($inputData) || empty($inputData)) {
    $inputData = !empty($_POST) ? $_POST : $_GET;
}
$action = $_GET['action'] ?? $inputData['action'] ?? '';

// Helper verifikasi otoritas kurator
if (!function_exists('isAuthorizedCurator')) {
    function isAuthorizedCurator($inputData) {
        $pass = $_SERVER['HTTP_X_CURATOR_KEY'] ?? $_SERVER['HTTP_X_EVALUATOR_KEY'] ?? $_GET['curator_key'] ?? $inputData['curator_key'] ?? '';
        return ($pass === CURATOR_PASSWORD);
    }
}

// Perhitungan skor bobot resmi kurasi
if (!function_exists('calculateKurasiScore')) {
    function calculateKurasiScore($sk, $infra, $kontri, $nda) {
        $opScore = ($sk === "3") ? 100 : (($sk === "2") ? 66 : 33);
        $infraScore = ($infra === "3") ? 100 : (($infra === "2") ? 66 : 33);
        $kontriScore = ($kontri === "3") ? 100 : (($kontri === "2") ? 66 : 33);
        $ndaScore = ($nda === "3") ? 100 : 33;

        $finalScore = ($opScore * 0.25) + ($infraScore * 0.20) + ($kontriScore * 0.30) + ($ndaScore * 0.25);
        return (int)round($finalScore);
    }
}

switch ($action) {

    // 1. PENDAFTARAN BUMDESA BARU
    case 'submit':
        $namaBumdes = trim($inputData['nama_bumdes'] ?? '');
        $namaDirektur = trim($inputData['nama_direktur'] ?? '');
        $kontak = trim($inputData['kontak'] ?? '');
        $unitUsaha = trim($inputData['unit_usaha'] ?? '');
        $desa = trim($inputData['desa'] ?? '');
        $kabupaten = trim($inputData['kabupaten'] ?? '');
        $provinsi = trim($inputData['provinsi'] ?? '');
        $skOperator = (string)($inputData['sk_operator'] ?? '1');
        $infrastruktur = (string)($inputData['infrastruktur'] ?? '1');
        $kontribusi = (string)($inputData['kontribusi'] ?? '1');
        $ndaAgreement = (string)($inputData['nda_agreement'] ?? '1');

        if (empty($namaBumdes) || empty($namaDirektur) || empty($kontak) || empty($unitUsaha) || empty($desa) || empty($kabupaten) || empty($provinsi)) {
            echo json_encode([
                'success' => false, 
                'message' => 'Seluruh formulir pendaftaran wajib diisi lengkap!'
            ]);
            exit;
        }

        // Hitung skor kelayakan otomatis
        $score = calculateKurasiScore($skOperator, $infrastruktur, $kontribusi, $ndaAgreement);
        $status = 'Cadangan';
        if ($score >= 80) {
            $status = 'Lolos';
        } elseif ($score < 50) {
            $status = 'Gugur';
        }

        // Generate Nomor Registrasi BUMD-xxx
        $countStmt = $pdo->query("SELECT MAX(id) as max_id FROM `kurasi_bumdesa`");
        $maxId = (int)$countStmt->fetchColumn();
        $nextNum = $maxId + 1;
        $nomorRegistrasi = 'BUMD-' . str_pad($nextNum, 3, '0', STR_PAD_LEFT);

        $ipAddress = $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0';

        $insertStmt = $pdo->prepare("INSERT INTO `kurasi_bumdesa` (
            `nomor_registrasi`, `nama_bumdes`, `nama_direktur`, `kontak`, `unit_usaha`,
            `desa`, `kabupaten`, `provinsi`, `sk_operator`, `infrastruktur`, `kontribusi`, `nda_agreement`,
            `score`, `status`, `ip_address`, `created_at`
        ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, NOW())");

        $insertStmt->execute([
            $nomorRegistrasi, $namaBumdes, $namaDirektur, $kontak, $unitUsaha,
            $desa, $kabupaten, $provinsi, $skOperator, $infrastruktur, $kontribusi, $ndaAgreement,
            $score, $status, $ipAddress
        ]);

        $insertedId = (int)$pdo->lastInsertId();

        $applicantData = [
            'id' => $nomorRegistrasi,
            'db_id' => $insertedId,
            'nomor_registrasi' => $nomorRegistrasi,
            'nama_bumdes' => $namaBumdes,
            'nama_direktur' => $namaDirektur,
            'kontak' => $kontak,
            'unit_usaha' => $unitUsaha,
            'desa' => $desa,
            'kabupaten' => $kabupaten,
            'provinsi' => $provinsi,
            'sk_operator' => $skOperator,
            'infrastruktur' => $infrastruktur,
            'kontribusi' => $kontribusi,
            'nda_agreement' => $ndaAgreement,
            'score' => $score,
            'status' => $status,
            'created_at' => date('Y-m-d H:i:s')
        ];

        echo json_encode([
            'success' => true,
            'message' => "Pendaftaran BUMDesa \"{$namaBumdes}\" berhasil tersimpan di database server!",
            'data' => $applicantData
        ]);
        break;

    // 2. DAFTAR SEMUA PESERTA KURASI (UNTUK DASHBOARD KURATOR)
    case 'list':
        $search = trim($_GET['search'] ?? $inputData['search'] ?? '');
        $statusFilter = trim($_GET['status'] ?? $inputData['status'] ?? '');
        $provFilter = trim($_GET['provinsi'] ?? $inputData['provinsi'] ?? '');

        $where = [];
        $params = [];

        if ($search !== '') {
            $where[] = "(nama_bumdes LIKE ? OR desa LIKE ? OR kabupaten LIKE ? OR nama_direktur LIKE ?)";
            $searchTerm = "%{$search}%";
            $params[] = $searchTerm;
            $params[] = $searchTerm;
            $params[] = $searchTerm;
            $params[] = $searchTerm;
        }

        if ($statusFilter !== '') {
            $where[] = "status = ?";
            $params[] = $statusFilter;
        }

        if ($provFilter !== '') {
            $where[] = "provinsi = ?";
            $params[] = $provFilter;
        }

        $sql = "SELECT id as db_id, nomor_registrasi as id, nomor_registrasi, nama_bumdes, nama_direktur, kontak, unit_usaha,
                       desa, kabupaten, provinsi, sk_operator, infrastruktur, kontribusi, nda_agreement,
                       score, status, catatan, nda_signed, spk_signed, pakta_integritas, kode_bisnis,
                       saas_username, saas_password, no_sertifikat, tanggal_sertifikat, created_at, updated_at
                FROM `kurasi_bumdesa`";

        if (!empty($where)) {
            $sql .= " WHERE " . implode(" AND ", $where);
        }
        $sql .= " ORDER BY score DESC, id DESC";

        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);
        $rows = $stmt->fetchAll();

        // Hitung statistik keseluruhan dari database
        $statsStmt = $pdo->query("SELECT 
            COUNT(*) as total,
            SUM(CASE WHEN status = 'Lolos' THEN 1 ELSE 0 END) as lolos,
            SUM(CASE WHEN status = 'Cadangan' THEN 1 ELSE 0 END) as review,
            SUM(CASE WHEN status = 'Gugur' THEN 1 ELSE 0 END) as gugur,
            ROUND(AVG(score)) as avg_score
            FROM `kurasi_bumdesa`");
        $stats = $statsStmt->fetch();

        echo json_encode([
            'success' => true,
            'data' => $rows,
            'stats' => [
                'total' => (int)($stats['total'] ?? 0),
                'lolos' => (int)($stats['lolos'] ?? 0),
                'review' => (int)($stats['review'] ?? 0),
                'gugur' => (int)($stats['gugur'] ?? 0),
                'avg_score' => (int)($stats['avg_score'] ?? 0)
            ]
        ]);
        break;

    // 3. STATISTIK RINGKAS
    case 'stats':
        $statsStmt = $pdo->query("SELECT 
            COUNT(*) as total,
            SUM(CASE WHEN status = 'Lolos' THEN 1 ELSE 0 END) as lolos,
            SUM(CASE WHEN status = 'Cadangan' THEN 1 ELSE 0 END) as review,
            SUM(CASE WHEN status = 'Gugur' THEN 1 ELSE 0 END) as gugur,
            ROUND(AVG(score)) as avg_score
            FROM `kurasi_bumdesa`");
        $stats = $statsStmt->fetch();

        echo json_encode([
            'success' => true,
            'stats' => [
                'total' => (int)($stats['total'] ?? 0),
                'lolos' => (int)($stats['lolos'] ?? 0),
                'review' => (int)($stats['review'] ?? 0),
                'gugur' => (int)($stats['gugur'] ?? 0),
                'avg_score' => (int)($stats['avg_score'] ?? 0)
            ]
        ]);
        break;

    // 4. UPDATE STATUS KEPUTUSAN KURASI (KHUSUS KURATOR)
    case 'update_status':
        if (!isAuthorizedCurator($inputData)) {
            http_response_code(403);
            echo json_encode(['success' => false, 'message' => 'Akses ditolak. Diperlukan sandi kurator yang sah.']);
            exit;
        }

        $targetId = trim($inputData['id'] ?? $inputData['nomor_registrasi'] ?? '');
        $newStatus = trim($inputData['status'] ?? '');
        $catatan = trim($inputData['catatan'] ?? '');

        if (empty($targetId) || empty($newStatus)) {
            echo json_encode(['success' => false, 'message' => 'ID peserta dan status baru wajib ditentukan!']);
            exit;
        }

        $updateStmt = $pdo->prepare("UPDATE `kurasi_bumdesa` 
            SET `status` = ?, `catatan` = IF(? != '', ?, `catatan`), `updated_at` = NOW() 
            WHERE `nomor_registrasi` = ? OR `id` = ?");
        $updateStmt->execute([$newStatus, $catatan, $catatan, $targetId, $targetId]);

        if ($updateStmt->rowCount() > 0) {
            echo json_encode([
                'success' => true,
                'message' => "Status kelayakan [{$targetId}] berhasil diperbarui menjadi [{$newStatus}]."
            ]);
        } else {
            echo json_encode([
                'success' => true,
                'message' => "Tidak ada perubahan data atau ID tidak ditemukan."
            ]);
        }
        break;

    // 5. HAPUS SATU DATA PENDAFTAR (KHUSUS KURATOR)
    case 'delete':
        if (!isAuthorizedCurator($inputData)) {
            http_response_code(403);
            echo json_encode(['success' => false, 'message' => 'Akses ditolak. Diperlukan sandi kurator.']);
            exit;
        }

        $targetId = trim($inputData['id'] ?? $inputData['nomor_registrasi'] ?? '');
        if (empty($targetId)) {
            echo json_encode(['success' => false, 'message' => 'ID data wajib diisi.']);
            exit;
        }

        $stmt = $pdo->prepare("DELETE FROM `kurasi_bumdesa` WHERE `nomor_registrasi` = ? OR `id` = ?");
        $stmt->execute([$targetId, $targetId]);

        echo json_encode([
            'success' => true,
            'message' => "Data pendaftar [{$targetId}] berhasil dihapus dari database."
        ]);
        break;

    // 6. KOSONGKAN SELURUH DATABASE KURASI (KHUSUS KURATOR)
    case 'clear_all':
        if (!isAuthorizedCurator($inputData)) {
            http_response_code(403);
            echo json_encode(['success' => false, 'message' => 'Akses ditolak. Diperlukan sandi kurator.']);
            exit;
        }

        $pdo->exec("TRUNCATE TABLE `kurasi_bumdesa`");

        echo json_encode([
            'success' => true,
            'message' => "Seluruh database kurasi berhasil dikosongkan."
        ]);
        break;

    // 7. VERIFIKASI SANDI KURATOR
    case 'verify_curator':
        $pass = trim($inputData['password'] ?? $inputData['curator_key'] ?? '');
        if ($pass === CURATOR_PASSWORD) {
            echo json_encode([
                'success' => true,
                'message' => 'Akses Kurator Terverifikasi.'
            ]);
        } else {
            http_response_code(401);
            echo json_encode([
                'success' => false,
                'message' => 'Sandi Kurator salah!'
            ]);
        }
        break;

    // 8. EKSPOR CSV DARI SERVER
    case 'export':
        if (!isAuthorizedCurator($inputData)) {
            http_response_code(403);
            echo json_encode(['success' => false, 'message' => 'Akses ditolak.']);
            exit;
        }

        $stmt = $pdo->query("SELECT nomor_registrasi, nama_bumdes, nama_direktur, kontak, unit_usaha, desa, kabupaten, provinsi, score, status, created_at 
            FROM `kurasi_bumdesa` ORDER BY score DESC, id DESC");
        $rows = $stmt->fetchAll();

        header('Content-Type: text/csv; charset=utf-8');
        header('Content-Disposition: attachment; filename="Laporan_Kurasi_BUMDesa_SimpleAkunting_' . date('Ymd_His') . '.csv"');

        $out = fopen('php://output', 'w');
        fputcsv($out, ['No Registrasi', 'Nama BUMDesa', 'Nama Direktur', 'Kontak WhatsApp', 'Unit Usaha', 'Desa', 'Kabupaten/Kota', 'Provinsi', 'Skor Kelayakan', 'Status Kelayakan', 'Tanggal Pendaftaran']);

        foreach ($rows as $r) {
            fputcsv($out, [
                $r['nomor_registrasi'],
                $r['nama_bumdes'],
                $r['nama_direktur'],
                $r['kontak'],
                $r['unit_usaha'],
                $r['desa'],
                $r['kabupaten'],
                $r['provinsi'],
                $r['score'],
                $r['status'],
                $r['created_at']
            ]);
        }
        fclose($out);
        exit;

    // 9. UPDATE DATA SERTIFIKAT KELAYAKAN & KREDENSIAL SAAS (KHUSUS KURATOR)
    case 'update_certificate':
        if (!isAuthorizedCurator($inputData)) {
            http_response_code(403);
            echo json_encode(['success' => false, 'message' => 'Akses ditolak. Diperlukan sandi kurator yang sah.']);
            exit;
        }

        $targetId = trim($inputData['id'] ?? $inputData['nomor_registrasi'] ?? '');
        $ndaSigned = trim($inputData['nda_signed'] ?? 'Belum');
        $spkSigned = trim($inputData['spk_signed'] ?? 'Belum');
        $paktaIntegritas = trim($inputData['pakta_integritas'] ?? 'Belum');
        $kodeBisnis = trim($inputData['kode_bisnis'] ?? '');
        $saasUsername = trim($inputData['saas_username'] ?? '');
        $saasPassword = trim($inputData['saas_password'] ?? '');
        $noSertifikat = trim($inputData['no_sertifikat'] ?? '');
        $tanggalSertifikat = trim($inputData['tanggal_sertifikat'] ?? date('Y-m-d'));

        if (empty($targetId)) {
            echo json_encode(['success' => false, 'message' => 'ID BUMDesa wajib diisi!']);
            exit;
        }

        $updateStmt = $pdo->prepare("UPDATE `kurasi_bumdesa` SET
            `nda_signed` = ?,
            `spk_signed` = ?,
            `pakta_integritas` = ?,
            `kode_bisnis` = ?,
            `saas_username` = ?,
            `saas_password` = ?,
            `no_sertifikat` = ?,
            `tanggal_sertifikat` = ?,
            `updated_at` = NOW()
            WHERE `nomor_registrasi` = ? OR `id` = ?");
        
        $updateStmt->execute([
            $ndaSigned, $spkSigned, $paktaIntegritas,
            $kodeBisnis, $saasUsername, $saasPassword,
            $noSertifikat, $tanggalSertifikat,
            $targetId, $targetId
        ]);

        echo json_encode([
            'success' => true,
            'message' => "Data sertifikat kelayakan & kredensial SaaS [{$targetId}] berhasil disimpan!",
            'data' => [
                'id' => $targetId,
                'nda_signed' => $ndaSigned,
                'spk_signed' => $spkSigned,
                'pakta_integritas' => $paktaIntegritas,
                'kode_bisnis' => $kodeBisnis,
                'saas_username' => $saasUsername,
                'saas_password' => $saasPassword,
                'no_sertifikat' => $noSertifikat,
                'tanggal_sertifikat' => $tanggalSertifikat
            ]
        ]);
        break;

    default:
        echo json_encode([
            'success' => true,
            'message' => 'Endpoint API Kurasi BUMDesa SimpleAkunting v3.6 Aktif',
            'endpoints' => [
                'POST ?action=submit' => 'Pendaftaran BUMDesa baru',
                'GET ?action=list' => 'Daftar semua pendaftar & statistik',
                'GET ?action=stats' => 'Statistik ringkas',
                'POST ?action=update_status' => 'Ubah status kelayakan (Kurator)',
                'POST ?action=update_certificate' => 'Update data sertifikat kelayakan & kredensial SaaS (Kurator)',
                'POST ?action=delete' => 'Hapus data pendaftar (Kurator)',
                'POST ?action=clear_all' => 'Kosongkan database kurasi (Kurator)',
                'POST ?action=verify_curator' => 'Verifikasi sandi kurator',
                'GET ?action=export' => 'Download laporan CSV terpusat'
            ]
        ]);
        break;
}
