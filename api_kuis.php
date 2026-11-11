<?php
/**
 * API Backend Kuis Evaluasi Pemahaman BUMDesa - SimpleAkunting v3.6
 * Menangani penyimpanan database, rekapitulasi nilai, pemisahan modul kuis (Akuntansi & Tata Kelola GCG),
 * serta otentikasi dashboard evaluator.
 */

// Muat konfigurasi sistem terlebih dahulu
$configFile = __DIR__ . '/app/config.php';
if (!file_exists($configFile)) {
    $configFile = __DIR__ . '/../app/config.php';
}
if (file_exists($configFile)) {
    require_once $configFile;
}

header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization, X-Requested-With, X-Evaluator-Key');

$reqMethod = $_SERVER['REQUEST_METHOD'] ?? 'GET';
if ($reqMethod === 'OPTIONS') {
    http_response_code(200);
    exit;
}

// Kunci Jawaban Resmi Evaluasi Akuntansi (5 Soal)
const ANSWER_KEYS_AKUNTANSI = [
    1 => 'B',
    2 => 'B',
    3 => 'C',
    4 => 'B',
    5 => 'B'
];

// Kunci Jawaban Resmi Evaluasi Tata Kelola yang Baik / GCG BUMDesa (6 Soal)
const ANSWER_KEYS_TATAKELOLA = [
    1 => 'C',
    2 => 'C',
    3 => 'A',
    4 => 'B',
    5 => 'D',
    6 => 'E'
];

// Fallback Backward Compatibility
const ANSWER_KEYS = ANSWER_KEYS_AKUNTANSI;

// Password Evaluator Terpusat (Sesuai kredensial kurasi SimpleAkunting)
const EVALUATOR_PASSWORD = '548412Yaa';

try {
    $dsn = 'mysql:host=' . DB_HOST . ';dbname=' . DB_NAME . ';charset=utf8mb4';
    $pdo = new PDO($dsn, DB_USER, DB_PASS, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_PERSISTENT => false
    ]);

    // Buat tabel jika belum ada (Auto Migration)
    $createTableQuery = "CREATE TABLE IF NOT EXISTS `kuis_evaluasi` (
        `id` INT AUTO_INCREMENT PRIMARY KEY,
        `session_id` VARCHAR(64) NULL,
        `kategori_kuis` VARCHAR(50) NOT NULL DEFAULT 'akuntansi',
        `tipe_ujian` VARCHAR(20) NOT NULL,
        `nama_bumdes` VARCHAR(255) NOT NULL,
        `nama_desa` VARCHAR(255) NULL,
        `kecamatan` VARCHAR(255) NULL,
        `kabupaten` VARCHAR(255) NULL,
        `nama_peserta` VARCHAR(255) NOT NULL,
        `jabatan` VARCHAR(100) NOT NULL,
        `tanggal_ujian` DATE NOT NULL,
        `jawaban_1` CHAR(1) NOT NULL,
        `jawaban_2` CHAR(1) NOT NULL,
        `jawaban_3` CHAR(1) NOT NULL,
        `jawaban_4` CHAR(1) NOT NULL,
        `jawaban_5` CHAR(1) NOT NULL,
        `jawaban_6` CHAR(1) NULL,
        `jumlah_benar` INT NOT NULL,
        `total_soal` INT NOT NULL DEFAULT 5,
        `skor` INT NOT NULL,
        `status_lulus` VARCHAR(50) NOT NULL,
        `ip_address` VARCHAR(45) NULL,
        `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
        INDEX idx_kategori (`kategori_kuis`),
        INDEX idx_bumdes (`nama_bumdes`),
        INDEX idx_desa (`nama_desa`),
        INDEX idx_kecamatan (`kecamatan`),
        INDEX idx_kabupaten (`kabupaten`),
        INDEX idx_tipe (`tipe_ujian`),
        INDEX idx_tanggal (`tanggal_ujian`)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;";
    
    $pdo->exec($createTableQuery);

    // Tabel pengaturan modul kuis (Aktif / Nonaktif)
    $createSettingsTableQuery = "CREATE TABLE IF NOT EXISTS `kuis_settings` (
        `setting_key` VARCHAR(64) PRIMARY KEY,
        `setting_value` TEXT NOT NULL,
        `updated_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;";
    $pdo->exec($createSettingsTableQuery);

    // Inisialisasi default settings jika belum ada
    $initSettings = $pdo->query("SELECT COUNT(*) FROM `kuis_settings`")->fetchColumn();
    if ($initSettings == 0) {
        $defaultConfig = json_encode([
            'tatakelola' => true,
            'akuntansi' => true
        ]);
        $pdo->prepare("INSERT INTO `kuis_settings` (`setting_key`, `setting_value`) VALUES ('module_status', ?)")
            ->execute([$defaultConfig]);
    }

    // Auto-migrate kolom jika tabel kuis_evaluasi sudah dibuat sebelumnya tanpa kolom wilayah / kategori / jawaban_6
    $existingCols = $pdo->query("SHOW COLUMNS FROM `kuis_evaluasi`")->fetchAll(PDO::FETCH_COLUMN);
    if (!in_array('nama_desa', $existingCols)) {
        $pdo->exec("ALTER TABLE `kuis_evaluasi` ADD COLUMN `nama_desa` VARCHAR(255) NULL AFTER `nama_bumdes`");
    }
    if (!in_array('kecamatan', $existingCols)) {
        $pdo->exec("ALTER TABLE `kuis_evaluasi` ADD COLUMN `kecamatan` VARCHAR(255) NULL AFTER `nama_desa`");
    }
    if (!in_array('kabupaten', $existingCols)) {
        $pdo->exec("ALTER TABLE `kuis_evaluasi` ADD COLUMN `kabupaten` VARCHAR(255) NULL AFTER `kecamatan`");
    }
    if (!in_array('kategori_kuis', $existingCols)) {
        $pdo->exec("ALTER TABLE `kuis_evaluasi` ADD COLUMN `kategori_kuis` VARCHAR(50) NOT NULL DEFAULT 'akuntansi' AFTER `session_id`");
        $pdo->exec("ALTER TABLE `kuis_evaluasi` ADD INDEX idx_kategori (`kategori_kuis`)");
    }
    if (!in_array('jawaban_6', $existingCols)) {
        $pdo->exec("ALTER TABLE `kuis_evaluasi` ADD COLUMN `jawaban_6` CHAR(1) NULL AFTER `jawaban_5`");
    }
    if (!in_array('total_soal', $existingCols)) {
        $pdo->exec("ALTER TABLE `kuis_evaluasi` ADD COLUMN `total_soal` INT NOT NULL DEFAULT 5 AFTER `jumlah_benar`");
    }

    // Auto-migrate tabel arsip jika sudah ada
    $checkArchiveTable = $pdo->query("SHOW TABLES LIKE 'kuis_evaluasi_archive'")->fetchColumn();
    if ($checkArchiveTable) {
        $archCols = $pdo->query("SHOW COLUMNS FROM `kuis_evaluasi_archive`")->fetchAll(PDO::FETCH_COLUMN);
        if (!in_array('nama_desa', $archCols)) {
            $pdo->exec("ALTER TABLE `kuis_evaluasi_archive` ADD COLUMN `nama_desa` VARCHAR(255) NULL AFTER `nama_bumdes`");
        }
        if (!in_array('kecamatan', $archCols)) {
            $pdo->exec("ALTER TABLE `kuis_evaluasi_archive` ADD COLUMN `kecamatan` VARCHAR(255) NULL AFTER `nama_desa`");
        }
        if (!in_array('kabupaten', $archCols)) {
            $pdo->exec("ALTER TABLE `kuis_evaluasi_archive` ADD COLUMN `kabupaten` VARCHAR(255) NULL AFTER `kecamatan`");
        }
        if (!in_array('kategori_kuis', $archCols)) {
            $pdo->exec("ALTER TABLE `kuis_evaluasi_archive` ADD COLUMN `kategori_kuis` VARCHAR(50) NOT NULL DEFAULT 'akuntansi' AFTER `session_id`");
        }
        if (!in_array('jawaban_6', $archCols)) {
            $pdo->exec("ALTER TABLE `kuis_evaluasi_archive` ADD COLUMN `jawaban_6` CHAR(1) NULL AFTER `jawaban_5`");
        }
        if (!in_array('total_soal', $archCols)) {
            $pdo->exec("ALTER TABLE `kuis_evaluasi_archive` ADD COLUMN `total_soal` INT NOT NULL DEFAULT 5 AFTER `jumlah_benar`");
        }
    }

} catch (Exception $e) {
    echo json_encode([
        'success' => false, 
        'message' => 'Gagal terhubung ke database: ' . $e->getMessage()
    ]);
    exit;
}

// Ambil input JSON atau Request Parameters
$inputRaw = file_get_contents('php://input');
$inputData = json_decode($inputRaw, true);
if (!is_array($inputData) || empty($inputData)) {
    $inputData = !empty($_POST) ? $_POST : $_GET;
}
$action = $_GET['action'] ?? $inputData['action'] ?? '';

// Helper validasi evaluator
if (!function_exists('isAuthorizedEvaluator')) {
    function isAuthorizedEvaluator($inputData) {
        $pass = $_SERVER['HTTP_X_EVALUATOR_KEY'] ?? $_GET['eval_key'] ?? $inputData['eval_key'] ?? '';
        return ($pass === EVALUATOR_PASSWORD);
    }
}

switch ($action) {

    // 1. SUBMIT JAWABAN PESERTA KE DATABASE (MENDUKUNG MODUL AKUNTANSI & TATA KELOLA GCG)
    case 'submit':
        $rawKategori = strtolower(trim($inputData['kategori_kuis'] ?? $inputData['kategori'] ?? 'tatakelola'));
        if ($rawKategori === 'tatakelola' || $rawKategori === 'tata_kelola' || $rawKategori === 'tata kelola' || $rawKategori === 'gcg') {
            $kategoriKuis = 'tatakelola';
            $keys = ANSWER_KEYS_TATAKELOLA;
            $totalSoal = 6;
        } else {
            $kategoriKuis = 'akuntansi';
            $keys = ANSWER_KEYS_AKUNTANSI;
            $totalSoal = 5;
        }

        // Cek status aktif modul kuis
        $statusStmt = $pdo->prepare("SELECT `setting_value` FROM `kuis_settings` WHERE `setting_key` = 'module_status' LIMIT 1");
        $statusStmt->execute();
        $statusRow = $statusStmt->fetchColumn();
        if ($statusRow) {
            $moduleStatus = json_decode($statusRow, true);
            if (isset($moduleStatus[$kategoriKuis]) && $moduleStatus[$kategoriKuis] === false) {
                http_response_code(403);
                $modLabel = ($kategoriKuis === 'tatakelola') ? 'Tata Kelola (GCG BUMDesa)' : 'Akuntansi (SAK EMKM)';
                echo json_encode([
                    'success' => false,
                    'message' => "Modul Kuis {$modLabel} saat ini sedang dinonaktifkan oleh Fasilitator/Evaluator."
                ]);
                exit;
            }
        }

        $tipeUjian = trim($inputData['tipe_ujian'] ?? 'Pre-Test');
        $namaBumdes = trim($inputData['nama_bumdes'] ?? '');
        $namaDesa = trim($inputData['nama_desa'] ?? '');
        $kecamatan = trim($inputData['kecamatan'] ?? '');
        $kabupaten = trim($inputData['kabupaten'] ?? '');
        $namaPeserta = trim($inputData['nama_peserta'] ?? '');
        $jabatan = trim($inputData['jabatan'] ?? '');
        $tanggalUjian = trim($inputData['tanggal_ujian'] ?? date('Y-m-d'));
        $answers = $inputData['answers'] ?? [];

        if (empty($namaBumdes) || empty($namaPeserta) || empty($jabatan)) {
            echo json_encode(['success' => false, 'message' => 'Identitas peserta dan BUMDesa wajib diisi!']);
            exit;
        }

        // Hitung jawaban benar di server (Otoritatif & Anti-Kecurangan)
        $correctCount = 0;
        $j1 = strtoupper($answers[1] ?? '');
        $j2 = strtoupper($answers[2] ?? '');
        $j3 = strtoupper($answers[3] ?? '');
        $j4 = strtoupper($answers[4] ?? '');
        $j5 = strtoupper($answers[5] ?? '');
        $j6 = ($totalSoal >= 6) ? strtoupper($answers[6] ?? '') : null;

        if ($j1 === ($keys[1] ?? '')) $correctCount++;
        if ($j2 === ($keys[2] ?? '')) $correctCount++;
        if ($j3 === ($keys[3] ?? '')) $correctCount++;
        if ($j4 === ($keys[4] ?? '')) $correctCount++;
        if ($j5 === ($keys[5] ?? '')) $correctCount++;
        if ($totalSoal >= 6 && $j6 === ($keys[6] ?? '')) $correctCount++;

        $skor = (int)round(($correctCount / $totalSoal) * 100);
        $statusLulus = ($skor >= 80) ? 'LULUS TEKNIS' : 'PERLU PENDAMPINGAN';
        $ipAddress = $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0';
        $sessionId = bin2hex(random_bytes(16));

        $stmt = $pdo->prepare("INSERT INTO `kuis_evaluasi` 
            (`session_id`, `kategori_kuis`, `tipe_ujian`, `nama_bumdes`, `nama_desa`, `kecamatan`, `kabupaten`, `nama_peserta`, `jabatan`, `tanggal_ujian`, 
             `jawaban_1`, `jawaban_2`, `jawaban_3`, `jawaban_4`, `jawaban_5`, `jawaban_6`, 
             `jumlah_benar`, `total_soal`, `skor`, `status_lulus`, `ip_address`, `created_at`)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, NOW())");

        $stmt->execute([
            $sessionId, $kategoriKuis, $tipeUjian, $namaBumdes, $namaDesa, $kecamatan, $kabupaten, $namaPeserta, $jabatan, $tanggalUjian,
            $j1, $j2, $j3, $j4, $j5, $j6,
            $correctCount, $totalSoal, $skor, $statusLulus, $ipAddress
        ]);

        $insertedId = $pdo->lastInsertId();

        // Cari perbandingan Pre-Test vs Post-Test untuk peserta yang sama di BUMDesa yang sama & KATEGORI YANG SAMA
        $preScore = null;
        $postScore = null;
        $deltaScore = null;

        if ($tipeUjian === 'Post-Test') {
            $preStmt = $pdo->prepare("SELECT skor, jumlah_benar, tanggal_ujian, created_at FROM `kuis_evaluasi` 
                WHERE LOWER(nama_peserta) = LOWER(?) AND LOWER(nama_bumdes) = LOWER(?) AND tipe_ujian = 'Pre-Test' AND kategori_kuis = ? 
                ORDER BY id DESC LIMIT 1");
            $preStmt->execute([$namaPeserta, $namaBumdes, $kategoriKuis]);
            $preRow = $preStmt->fetch();
            if ($preRow) {
                $preScore = (int)$preRow['skor'];
                $postScore = $skor;
                $deltaScore = $postScore - $preScore;
            }
        } elseif ($tipeUjian === 'Pre-Test') {
            $postStmt = $pdo->prepare("SELECT skor, jumlah_benar, tanggal_ujian, created_at FROM `kuis_evaluasi` 
                WHERE LOWER(nama_peserta) = LOWER(?) AND LOWER(nama_bumdes) = LOWER(?) AND tipe_ujian = 'Post-Test' AND kategori_kuis = ? 
                ORDER BY id DESC LIMIT 1");
            $postStmt->execute([$namaPeserta, $namaBumdes, $kategoriKuis]);
            $postRow = $postStmt->fetch();
            if ($postRow) {
                $preScore = $skor;
                $postScore = (int)$postRow['skor'];
                $deltaScore = $postScore - $preScore;
            }
        }

        echo json_encode([
            'success' => true,
            'message' => 'Jawaban kuis berhasil disimpan ke database!',
            'data' => [
                'id' => (int)$insertedId,
                'session_id' => $sessionId,
                'kategori_kuis' => $kategoriKuis,
                'tipe_ujian' => $tipeUjian,
                'nama_bumdes' => $namaBumdes,
                'nama_desa' => $namaDesa,
                'kecamatan' => $kecamatan,
                'kabupaten' => $kabupaten,
                'nama_peserta' => $namaPeserta,
                'jabatan' => $jabatan,
                'jumlah_benar' => $correctCount,
                'total_soal' => $totalSoal,
                'skor' => $skor,
                'status_lulus' => $statusLulus,
                'is_passed' => ($skor >= 80),
                'comparison' => [
                    'has_comparison' => ($preScore !== null && $postScore !== null),
                    'pre_score' => $preScore,
                    'post_score' => $postScore,
                    'delta_score' => $deltaScore
                ]
            ]
        ]);
        break;

    // 1B. MATRIKS PERBANDINGAN PRE-TEST VS POST-TEST PESERTA YANG SAMA (TERPISAH PER KATEGORI/MODUL)
    case 'comparison_matrix':
        if (!isAuthorizedEvaluator($inputData)) {
            http_response_code(403);
            echo json_encode(['success' => false, 'message' => 'Akses ditolak. Diperlukan izin Evaluator.']);
            exit;
        }

        $kategori = strtolower(trim($_GET['kategori'] ?? $inputData['kategori'] ?? 'tatakelola'));
        if ($kategori !== 'akuntansi' && $kategori !== 'tatakelola' && $kategori !== 'all') {
            $kategori = 'tatakelola';
        }

        $whereClause = "";
        if ($kategori === 'tatakelola') {
            $whereClause = "WHERE kategori_kuis = 'tatakelola'";
        } elseif ($kategori === 'akuntansi') {
            $whereClause = "WHERE (kategori_kuis = 'akuntansi' OR kategori_kuis IS NULL OR kategori_kuis = '')";
        }

        $matrixSql = "SELECT 
            nama_peserta, 
            nama_bumdes, 
            MAX(nama_desa) as nama_desa,
            MAX(kecamatan) as kecamatan,
            MAX(kabupaten) as kabupaten,
            MAX(jabatan) as jabatan,
            kategori_kuis,
            MAX(CASE WHEN tipe_ujian = 'Pre-Test' THEN skor ELSE NULL END) as pre_score,
            MAX(CASE WHEN tipe_ujian = 'Post-Test' THEN skor ELSE NULL END) as post_score,
            MAX(CASE WHEN tipe_ujian = 'Pre-Test' THEN jumlah_benar ELSE NULL END) as pre_correct,
            MAX(CASE WHEN tipe_ujian = 'Post-Test' THEN jumlah_benar ELSE NULL END) as post_correct,
            MAX(CASE WHEN tipe_ujian = 'Pre-Test' THEN total_soal ELSE NULL END) as pre_total,
            MAX(CASE WHEN tipe_ujian = 'Post-Test' THEN total_soal ELSE NULL END) as post_total,
            MAX(CASE WHEN tipe_ujian = 'Pre-Test' THEN tanggal_ujian ELSE NULL END) as pre_date,
            MAX(CASE WHEN tipe_ujian = 'Post-Test' THEN tanggal_ujian ELSE NULL END) as post_date
        FROM `kuis_evaluasi`
        $whereClause
        GROUP BY LOWER(TRIM(nama_peserta)), LOWER(TRIM(nama_bumdes)), kategori_kuis
        ORDER BY nama_bumdes ASC, nama_peserta ASC";

        $stmt = $pdo->query($matrixSql);
        $matrixRows = $stmt->fetchAll();

        $processed = [];
        $totalPaired = 0;
        $sumPre = 0;
        $sumPost = 0;
        $sumDelta = 0;

        foreach ($matrixRows as $row) {
            $pre = ($row['pre_score'] !== null) ? (int)$row['pre_score'] : null;
            $post = ($row['post_score'] !== null) ? (int)$row['post_score'] : null;
            $delta = ($pre !== null && $post !== null) ? ($post - $pre) : null;

            if ($pre !== null && $post !== null) {
                $totalPaired++;
                $sumPre += $pre;
                $sumPost += $post;
                $sumDelta += $delta;
            }

            $processed[] = [
                'nama_peserta' => $row['nama_peserta'],
                'nama_bumdes' => $row['nama_bumdes'],
                'nama_desa' => $row['nama_desa'] ?? '',
                'kecamatan' => $row['kecamatan'] ?? '',
                'kabupaten' => $row['kabupaten'] ?? '',
                'jabatan' => $row['jabatan'],
                'kategori_kuis' => $row['kategori_kuis'] ?? 'tatakelola',
                'pre_score' => $pre,
                'post_score' => $post,
                'delta' => $delta,
                'pre_correct' => $row['pre_correct'],
                'post_correct' => $row['post_correct'],
                'pre_total' => $row['pre_total'] ?? 6,
                'post_total' => $row['post_total'] ?? 6,
                'pre_date' => $row['pre_date'],
                'post_date' => $row['post_date'],
                'is_complete_pair' => ($pre !== null && $post !== null),
                'status_akhir' => ($post !== null) ? (($post >= 80) ? 'LULUS TEKNIS' : 'PERLU PENDAMPINGAN') : 'BELUM POST-TEST'
            ];
        }

        // Himpun daftar unik wilayah yang terdata
        $uniqueDesa = array_values(array_filter(array_unique(array_column($matrixRows, 'nama_desa'))));
        $uniqueKec = array_values(array_filter(array_unique(array_column($matrixRows, 'kecamatan'))));
        $uniqueKab = array_values(array_filter(array_unique(array_column($matrixRows, 'kabupaten'))));

        $summary = [
            'kategori' => $kategori,
            'total_peserta_terdaftar' => count($matrixRows),
            'total_lengkap_pre_post' => $totalPaired,
            'avg_pre_score' => ($totalPaired > 0) ? round($sumPre / $totalPaired, 1) : 0,
            'avg_post_score' => ($totalPaired > 0) ? round($sumPost / $totalPaired, 1) : 0,
            'avg_delta' => ($totalPaired > 0) ? round($sumDelta / $totalPaired, 1) : 0,
            'wilayah' => [
                'desa' => $uniqueDesa,
                'kecamatan' => $uniqueKec,
                'kabupaten' => $uniqueKab
            ]
        ];

        echo json_encode([
            'success' => true,
            'kategori' => $kategori,
            'summary' => $summary,
            'data' => $processed
        ]);
        break;

    // 2. VERIFIKASI AKSES EVALUATOR (LOGIN)
    case 'verify_evaluator':
        $password = $inputData['password'] ?? $_GET['password'] ?? '';
        if ($password === EVALUATOR_PASSWORD) {
            echo json_encode([
                'success' => true,
                'message' => 'Otentikasi evaluator berhasil!',
                'eval_key' => EVALUATOR_PASSWORD
            ]);
        } else {
            http_response_code(401);
            echo json_encode([
                'success' => false,
                'message' => 'Kata sandi evaluator tidak valid!'
            ]);
        }
        break;

    // 3. DAFTAR HASIL SELURUH PESERTA (KHUSUS EVALUATOR - DAPAT DI-FILTER PER KATEGORI)
    case 'list':
        if (!isAuthorizedEvaluator($inputData)) {
            http_response_code(403);
            echo json_encode(['success' => false, 'message' => 'Akses ditolak. Diperlukan izin Evaluator.']);
            exit;
        }

        $search = trim($_GET['search'] ?? '');
        $filterType = trim($_GET['type'] ?? '');
        $filterStatus = trim($_GET['status'] ?? '');
        $filterKategori = strtolower(trim($_GET['kategori'] ?? ''));

        $sql = "SELECT id, session_id, kategori_kuis, tipe_ujian, nama_bumdes, nama_desa, kecamatan, kabupaten, nama_peserta, jabatan, tanggal_ujian, 
                       jawaban_1, jawaban_2, jawaban_3, jawaban_4, jawaban_5, jawaban_6,
                       jumlah_benar, total_soal, skor, status_lulus, created_at 
                FROM `kuis_evaluasi` WHERE 1=1";
        $params = [];

        if (!empty($filterKategori) && $filterKategori !== 'all') {
            if ($filterKategori === 'akuntansi') {
                $sql .= " AND (kategori_kuis = 'akuntansi' OR kategori_kuis IS NULL OR kategori_kuis = '')";
            } else {
                $sql .= " AND kategori_kuis = ?";
                $params[] = $filterKategori;
            }
        }

        if (!empty($search)) {
            $sql .= " AND (nama_bumdes LIKE ? OR nama_peserta LIKE ? OR nama_desa LIKE ? OR kecamatan LIKE ? OR kabupaten LIKE ?)";
            $params[] = "%$search%";
            $params[] = "%$search%";
            $params[] = "%$search%";
            $params[] = "%$search%";
            $params[] = "%$search%";
        }

        if (!empty($filterType)) {
            $sql .= " AND tipe_ujian = ?";
            $params[] = $filterType;
        }

        if (!empty($filterStatus)) {
            $sql .= " AND status_lulus = ?";
            $params[] = $filterStatus;
        }

        $sql .= " ORDER BY id DESC";

        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);
        $rows = $stmt->fetchAll();

        echo json_encode([
            'success' => true,
            'kategori' => $filterKategori ?: 'all',
            'total' => count($rows),
            'data' => $rows
        ]);
        break;

    // 4. STATISTIK & ANALISIS BUTIR SOAL TERPISAH (ITEM DIFFICULTY ANALYSIS PER MODUL)
    case 'stats':
        if (!isAuthorizedEvaluator($inputData)) {
            http_response_code(403);
            echo json_encode(['success' => false, 'message' => 'Akses ditolak. Diperlukan izin Evaluator.']);
            exit;
        }

        $kategori = strtolower(trim($_GET['kategori'] ?? $inputData['kategori'] ?? 'tatakelola'));
        if ($kategori !== 'akuntansi' && $kategori !== 'tatakelola' && $kategori !== 'all') {
            $kategori = 'tatakelola';
        }

        $whereKategori = ($kategori === 'tatakelola') 
            ? "WHERE kategori_kuis = 'tatakelola'" 
            : "WHERE (kategori_kuis = 'akuntansi' OR kategori_kuis IS NULL OR kategori_kuis = '')";

        $totalStmt = $pdo->query("SELECT COUNT(*) as total, 
                                         COALESCE(AVG(skor), 0) as avg_score,
                                         SUM(CASE WHEN skor >= 80 THEN 1 ELSE 0 END) as total_lulus,
                                         SUM(CASE WHEN skor < 80 THEN 1 ELSE 0 END) as total_belum,
                                         SUM(CASE WHEN tipe_ujian = 'Pre-Test' THEN 1 ELSE 0 END) as total_pre,
                                         SUM(CASE WHEN tipe_ujian = 'Post-Test' THEN 1 ELSE 0 END) as total_post
                                  FROM `kuis_evaluasi` $whereKategori");
        $overview = $totalStmt->fetch();

        if ($kategori === 'tatakelola') {
            // Analisis 6 Butir Soal Tata Kelola yang Baik (GCG BUMDesa)
            // Kunci: 1:C, 2:C, 3:A, 4:B, 5:D, 6:E
            $itemAnalysisQuery = "SELECT 
                SUM(CASE WHEN UPPER(jawaban_1) = 'C' THEN 1 ELSE 0 END) as q1_correct,
                SUM(CASE WHEN UPPER(jawaban_2) = 'C' THEN 1 ELSE 0 END) as q2_correct,
                SUM(CASE WHEN UPPER(jawaban_3) = 'A' THEN 1 ELSE 0 END) as q3_correct,
                SUM(CASE WHEN UPPER(jawaban_4) = 'B' THEN 1 ELSE 0 END) as q4_correct,
                SUM(CASE WHEN UPPER(jawaban_5) = 'D' THEN 1 ELSE 0 END) as q5_correct,
                SUM(CASE WHEN UPPER(jawaban_6) = 'E' THEN 1 ELSE 0 END) as q6_correct,
                COUNT(*) as total_responses
            FROM `kuis_evaluasi` $whereKategori";
            $itemStmt = $pdo->query($itemAnalysisQuery);
            $itemData = $itemStmt->fetch();

            $totalResp = max((int)($itemData['total_responses'] ?? 0), 1);

            $questionStats = [
                1 => [
                    'topic' => 'Prinsip Tata Kelola yang Baik (TARIF: Transparansi, Akuntabilitas, Responsibilitas, Independensi, Kewajaran)',
                    'correct_ans' => 'C',
                    'correct_count' => (int)($itemData['q1_correct'] ?? 0),
                    'percent' => round(((int)($itemData['q1_correct'] ?? 0) / $totalResp) * 100, 1)
                ],
                2 => [
                    'topic' => 'Indikator Pengukuran Kepuasan Pelanggan (Survei Kepuasan Pelayanan)',
                    'correct_ans' => 'C',
                    'correct_count' => (int)($itemData['q2_correct'] ?? 0),
                    'percent' => round(((int)($itemData['q2_correct'] ?? 0) / $totalResp) * 100, 1)
                ],
                3 => [
                    'topic' => 'Empat Perspektif Balanced Scorecard (Pembelajaran & Pertumbuhan, Proses Bisnis Internal, Pelanggan, Keuangan)',
                    'correct_ans' => 'A',
                    'correct_count' => (int)($itemData['q3_correct'] ?? 0),
                    'percent' => round(((int)($itemData['q3_correct'] ?? 0) / $totalResp) * 100, 1)
                ],
                4 => [
                    'topic' => 'Pengertian Proyeksi Keuangan dalam Pengelolaan BUM Desa',
                    'correct_ans' => 'B',
                    'correct_count' => (int)($itemData['q4_correct'] ?? 0),
                    'percent' => round(((int)($itemData['q4_correct'] ?? 0) / $totalResp) * 100, 1)
                ],
                5 => [
                    'topic' => 'Tujuan Utama Penyusunan Proyeksi Keuangan (Estimasi Modal, Pendapatan, Biaya, dan Laba)',
                    'correct_ans' => 'D',
                    'correct_count' => (int)($itemData['q5_correct'] ?? 0),
                    'percent' => round(((int)($itemData['q5_correct'] ?? 0) / $totalResp) * 100, 1)
                ],
                6 => [
                    'topic' => 'Tindakan Pengendalian Bila Realisasi Pendapatan Lebih Rendah dari Proyeksi (Evaluasi & Langkah Perbaikan)',
                    'correct_ans' => 'E',
                    'correct_count' => (int)($itemData['q6_correct'] ?? 0),
                    'percent' => round(((int)($itemData['q6_correct'] ?? 0) / $totalResp) * 100, 1)
                ]
            ];
        } else {
            // Analisis 5 Butir Soal Akuntansi SAK EMKM / SAK EP
            // Kunci: 1:B, 2:B, 3:C, 4:B, 5:B
            $itemAnalysisQuery = "SELECT 
                SUM(CASE WHEN UPPER(jawaban_1) = 'B' THEN 1 ELSE 0 END) as q1_correct,
                SUM(CASE WHEN UPPER(jawaban_2) = 'B' THEN 1 ELSE 0 END) as q2_correct,
                SUM(CASE WHEN UPPER(jawaban_3) = 'C' THEN 1 ELSE 0 END) as q3_correct,
                SUM(CASE WHEN UPPER(jawaban_4) = 'B' THEN 1 ELSE 0 END) as q4_correct,
                SUM(CASE WHEN UPPER(jawaban_5) = 'B' THEN 1 ELSE 0 END) as q5_correct,
                COUNT(*) as total_responses
            FROM `kuis_evaluasi` $whereKategori";
            $itemStmt = $pdo->query($itemAnalysisQuery);
            $itemData = $itemStmt->fetch();

            $totalResp = max((int)($itemData['total_responses'] ?? 0), 1);

            $questionStats = [
                1 => [
                    'topic' => 'Konsep Stewardship & Moralitas Laporan Keuangan',
                    'correct_ans' => 'B',
                    'correct_count' => (int)($itemData['q1_correct'] ?? 0),
                    'percent' => round(((int)($itemData['q1_correct'] ?? 0) / $totalResp) * 100, 1)
                ],
                2 => [
                    'topic' => 'Pemisahan Kas Entitas BUMDesa dari Pribadi Pengurus',
                    'correct_ans' => 'B',
                    'correct_count' => (int)($itemData['q2_correct'] ?? 0),
                    'percent' => round(((int)($itemData['q2_correct'] ?? 0) / $totalResp) * 100, 1)
                ],
                3 => [
                    'topic' => 'Pencatatan Penjualan Kredit (Piutang vs BKU)',
                    'correct_ans' => 'C',
                    'correct_count' => (int)($itemData['q3_correct'] ?? 0),
                    'percent' => round(((int)($itemData['q3_correct'] ?? 0) / $totalResp) * 100, 1)
                ],
                4 => [
                    'topic' => 'Komponen Pokok SAK EMKM / SAK EP & Kepmen Desa 136/2022',
                    'correct_ans' => 'B',
                    'correct_count' => (int)($itemData['q4_correct'] ?? 0),
                    'percent' => round(((int)($itemData['q4_correct'] ?? 0) / $totalResp) * 100, 1)
                ],
                5 => [
                    'topic' => 'Keseimbangan Neraca (Aset = Liabilitas + Ekuitas)',
                    'correct_ans' => 'B',
                    'correct_count' => (int)($itemData['q5_correct'] ?? 0),
                    'percent' => round(((int)($itemData['q5_correct'] ?? 0) / $totalResp) * 100, 1)
                ]
            ];
        }

        echo json_encode([
            'success' => true,
            'kategori' => $kategori,
            'overview' => [
                'total_peserta' => (int)$overview['total'],
                'avg_score' => round((float)$overview['avg_score'], 1),
                'total_lulus' => (int)$overview['total_lulus'],
                'total_belum' => (int)$overview['total_belum'],
                'persen_lulus' => ($overview['total'] > 0) ? round(($overview['total_lulus'] / $overview['total']) * 100, 1) : 0,
                'total_pre' => (int)$overview['total_pre'],
                'total_post' => (int)$overview['total_post']
            ],
            'questions' => $questionStats
        ]);
        break;

    // 5. DETAIL SATU PESERTA
    case 'detail':
        if (!isAuthorizedEvaluator($inputData)) {
            http_response_code(403);
            echo json_encode(['success' => false, 'message' => 'Akses ditolak. Diperlukan izin Evaluator.']);
            exit;
        }

        $id = (int)($_GET['id'] ?? 0);
        $stmt = $pdo->prepare("SELECT * FROM `kuis_evaluasi` WHERE id = ?");
        $stmt->execute([$id]);
        $row = $stmt->fetch();

        if (!$row) {
            echo json_encode(['success' => false, 'message' => 'Data tidak ditemukan.']);
            exit;
        }

        echo json_encode(['success' => true, 'data' => $row]);
        break;

    // 6. HAPUS DATA EVALUASI
    case 'delete':
        if (!isAuthorizedEvaluator($inputData)) {
            http_response_code(403);
            echo json_encode(['success' => false, 'message' => 'Akses ditolak. Diperlukan izin Evaluator.']);
            exit;
        }

        $id = (int)($inputData['id'] ?? $_GET['id'] ?? 0);
        if ($id <= 0) {
            echo json_encode(['success' => false, 'message' => 'ID data tidak valid.']);
            exit;
        }

        $stmt = $pdo->prepare("DELETE FROM `kuis_evaluasi` WHERE id = ?");
        $stmt->execute([$id]);

        echo json_encode(['success' => true, 'message' => "Data evaluasi ID #$id berhasil dihapus."]);
        break;

    // 7. EKSPOR CSV TERPISAH PER MODUL
    case 'export':
        if (!isAuthorizedEvaluator($inputData)) {
            header('Content-Type: text/plain; charset=utf-8');
            http_response_code(403);
            echo 'Akses ditolak.';
            exit;
        }

        $filterKategori = strtolower(trim($_GET['kategori'] ?? 'tatakelola'));
        $sql = "SELECT id, session_id, kategori_kuis, tipe_ujian, nama_bumdes, nama_desa, kecamatan, kabupaten, nama_peserta, jabatan, tanggal_ujian, 
                       jawaban_1, jawaban_2, jawaban_3, jawaban_4, jawaban_5, jawaban_6, 
                       jumlah_benar, total_soal, skor, status_lulus, created_at 
                FROM `kuis_evaluasi` WHERE 1=1";
        $params = [];

        if (!empty($filterKategori) && $filterKategori !== 'all') {
            if ($filterKategori === 'akuntansi') {
                $sql .= " AND (kategori_kuis = 'akuntansi' OR kategori_kuis IS NULL OR kategori_kuis = '')";
            } else {
                $sql .= " AND kategori_kuis = ?";
                $params[] = $filterKategori;
            }
        }
        $sql .= " ORDER BY id DESC";

        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);
        $rows = $stmt->fetchAll();

        $catLabel = ($filterKategori === 'tatakelola') ? 'TataKelola_GCG' : (($filterKategori === 'akuntansi') ? 'Akuntansi_SAK_EMKM' : 'Semua');
        header('Content-Type: text/csv; charset=utf-8');
        header('Content-Disposition: attachment; filename="Rekap_Evaluasi_' . $catLabel . '_BUMDesa_' . date('Y-m-d_His') . '.csv"');

        $out = fopen('php://output', 'w');
        fputcsv($out, ['ID', 'Modul Kuis', 'Tipe Ujian', 'Nama BUMDesa', 'Desa', 'Kecamatan', 'Kabupaten', 'Nama Peserta', 'Jabatan', 'Tanggal', 'Jawaban 1', 'Jawaban 2', 'Jawaban 3', 'Jawaban 4', 'Jawaban 5', 'Jawaban 6', 'Jumlah Benar', 'Total Soal', 'Skor (%)', 'Status Kelulusan', 'Waktu Submit']);

        foreach ($rows as $r) {
            $modulName = ($r['kategori_kuis'] === 'tatakelola') ? 'Tata Kelola GCG' : 'Akuntansi SAK EMKM';
            fputcsv($out, [
                $r['id'],
                $modulName,
                $r['tipe_ujian'],
                $r['nama_bumdes'],
                $r['nama_desa'] ?? '',
                $r['kecamatan'] ?? '',
                $r['kabupaten'] ?? '',
                $r['nama_peserta'],
                $r['jabatan'],
                $r['tanggal_ujian'],
                $r['jawaban_1'],
                $r['jawaban_2'],
                $r['jawaban_3'],
                $r['jawaban_4'],
                $r['jawaban_5'],
                $r['jawaban_6'] ?? '',
                $r['jumlah_benar'],
                $r['total_soal'] ?? (($r['kategori_kuis'] === 'tatakelola') ? 6 : 5),
                $r['skor'],
                $r['status_lulus'],
                $r['created_at']
            ]);
        }
        fclose($out);
        exit;

    // 8. RESET TOTAL DATABASE KUIS EVALUASI (DAPAT DIRESET PER KATEGORI ATAU SELURUHNYA)
    case 'reset_database':
        if (!isAuthorizedEvaluator($inputData)) {
            http_response_code(403);
            echo json_encode(['success' => false, 'message' => 'Akses ditolak. Diperlukan izin Evaluator.']);
            exit;
        }

        $confirm = trim($inputData['confirm_text'] ?? $_GET['confirm_text'] ?? '');
        if (strtoupper($confirm) !== 'RESET' && $confirm !== EVALUATOR_PASSWORD) {
            echo json_encode([
                'success' => false, 
                'message' => 'Konfirmasi tidak valid. Harap ketik "RESET" atau masukkan kata sandi Evaluator.'
            ]);
            exit;
        }

        $kategoriReset = strtolower(trim($inputData['kategori'] ?? $_GET['kategori'] ?? 'all'));

        try {
            if ($kategoriReset === 'tatakelola') {
                $pdo->exec("DELETE FROM `kuis_evaluasi` WHERE kategori_kuis = 'tatakelola'");
                $msg = "Database kuis evaluasi khusus Modul Tata Kelola (GCG) berhasil dikosongkan.";
            } elseif ($kategoriReset === 'akuntansi') {
                $pdo->exec("DELETE FROM `kuis_evaluasi` WHERE kategori_kuis = 'akuntansi' OR kategori_kuis IS NULL OR kategori_kuis = ''");
                $msg = "Database kuis evaluasi khusus Modul Akuntansi SAK EMKM berhasil dikosongkan.";
            } else {
                $pdo->exec("TRUNCATE TABLE `kuis_evaluasi`");
                $msg = "Database kuis evaluasi berhasil di-reset total. Seluruh data peserta telah dikosongkan dan nomor urut kembali ke 1.";
            }
        } catch (Exception $e) {
            $pdo->exec("DELETE FROM `kuis_evaluasi`");
            $pdo->exec("ALTER TABLE `kuis_evaluasi` AUTO_INCREMENT = 1");
            $msg = "Database kuis evaluasi berhasil di-reset.";
        }

        echo json_encode([
            'success' => true,
            'message' => $msg
        ]);
        break;

    // 9. BUKA DATABASE / SESI BARU & ARSIPKAN DATA AKTIF
    case 'new_database':
        if (!isAuthorizedEvaluator($inputData)) {
            http_response_code(403);
            echo json_encode(['success' => false, 'message' => 'Akses ditolak. Diperlukan izin Evaluator.']);
            exit;
        }

        $batchName = trim($inputData['batch_name'] ?? $_GET['batch_name'] ?? '');
        if (empty($batchName)) {
            $batchName = 'Sesi Arsip ' . date('d-m-Y H:i');
        }

        // 1. Pastikan tabel arsip sudah ada
        $createArchiveSql = "CREATE TABLE IF NOT EXISTS `kuis_evaluasi_archive` (
            `archive_id` INT AUTO_INCREMENT PRIMARY KEY,
            `batch_name` VARCHAR(255) NOT NULL,
            `original_id` INT NOT NULL,
            `session_id` VARCHAR(64) NULL,
            `kategori_kuis` VARCHAR(50) NOT NULL DEFAULT 'akuntansi',
            `tipe_ujian` VARCHAR(20) NOT NULL,
            `nama_bumdes` VARCHAR(255) NOT NULL,
            `nama_desa` VARCHAR(255) NULL,
            `kecamatan` VARCHAR(255) NULL,
            `kabupaten` VARCHAR(255) NULL,
            `nama_peserta` VARCHAR(255) NOT NULL,
            `jabatan` VARCHAR(100) NOT NULL,
            `tanggal_ujian` DATE NOT NULL,
            `jawaban_1` CHAR(1) NOT NULL,
            `jawaban_2` CHAR(1) NOT NULL,
            `jawaban_3` CHAR(1) NOT NULL,
            `jawaban_4` CHAR(1) NOT NULL,
            `jawaban_5` CHAR(1) NOT NULL,
            `jawaban_6` CHAR(1) NULL,
            `jumlah_benar` INT NOT NULL,
            `total_soal` INT NOT NULL DEFAULT 5,
            `skor` INT NOT NULL,
            `status_lulus` VARCHAR(50) NOT NULL,
            `ip_address` VARCHAR(45) NULL,
            `original_created_at` DATETIME NOT NULL,
            `archived_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
            INDEX idx_batch (`batch_name`),
            INDEX idx_archived_at (`archived_at`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;";
        $pdo->exec($createArchiveSql);

        // 2. Hitung jumlah data yang akan diarsipkan
        $countStmt = $pdo->query("SELECT COUNT(*) FROM `kuis_evaluasi`");
        $count = (int)$countStmt->fetchColumn();

        if ($count > 0) {
            $copySql = "INSERT INTO `kuis_evaluasi_archive` 
                (`batch_name`, `original_id`, `session_id`, `kategori_kuis`, `tipe_ujian`, `nama_bumdes`, `nama_desa`, `kecamatan`, `kabupaten`, `nama_peserta`, `jabatan`, `tanggal_ujian`, 
                 `jawaban_1`, `jawaban_2`, `jawaban_3`, `jawaban_4`, `jawaban_5`, `jawaban_6`, 
                 `jumlah_benar`, `total_soal`, `skor`, `status_lulus`, `ip_address`, `original_created_at`, `archived_at`)
                SELECT 
                    ?, `id`, `session_id`, `kategori_kuis`, `tipe_ujian`, `nama_bumdes`, `nama_desa`, `kecamatan`, `kabupaten`, `nama_peserta`, `jabatan`, `tanggal_ujian`,
                    `jawaban_1`, `jawaban_2`, `jawaban_3`, `jawaban_4`, `jawaban_5`, `jawaban_6`, 
                    `jumlah_benar`, `total_soal`, `skor`, `status_lulus`, `ip_address`, `created_at`, NOW()
                FROM `kuis_evaluasi`";
            $copyStmt = $pdo->prepare($copySql);
            $copyStmt->execute([$batchName]);
        }

        // 3. Kosongkan tabel aktif kuis_evaluasi
        try {
            $pdo->exec("TRUNCATE TABLE `kuis_evaluasi`");
        } catch (Exception $e) {
            $pdo->exec("DELETE FROM `kuis_evaluasi`");
            $pdo->exec("ALTER TABLE `kuis_evaluasi` AUTO_INCREMENT = 1");
        }

        echo json_encode([
            'success' => true,
            'message' => "Database baru berhasil dibuka. Data sebelumnya sebanyak $count rekaman telah berhasil disimpan ke arsip: '$batchName'.",
            'archived_count' => $count,
            'batch_name' => $batchName
        ]);
        break;

    // 10. DAFTAR ARSIP BATCH DATABASE (KHUSUS EVALUATOR)
    case 'archive_list':
        if (!isAuthorizedEvaluator($inputData)) {
            http_response_code(403);
            echo json_encode(['success' => false, 'message' => 'Akses ditolak. Diperlukan izin Evaluator.']);
            exit;
        }

        $check = $pdo->query("SHOW TABLES LIKE 'kuis_evaluasi_archive'")->fetchColumn();
        if (!$check) {
            echo json_encode(['success' => true, 'total' => 0, 'data' => []]);
            exit;
        }

        $sql = "SELECT 
            batch_name, 
            MIN(original_id) as min_id, 
            MAX(original_id) as max_id, 
            COUNT(*) as total_peserta,
            MIN(archived_at) as archived_at,
            MIN(tanggal_ujian) as min_date,
            MAX(tanggal_ujian) as max_date,
            SUM(CASE WHEN tipe_ujian = 'Pre-Test' THEN 1 ELSE 0 END) as total_pre,
            SUM(CASE WHEN tipe_ujian = 'Post-Test' THEN 1 ELSE 0 END) as total_post,
            COALESCE(AVG(skor), 0) as avg_score,
            SUM(CASE WHEN skor >= 80 THEN 1 ELSE 0 END) as total_lulus
        FROM `kuis_evaluasi_archive`
        GROUP BY batch_name
        ORDER BY MAX(archive_id) DESC";

        $stmt = $pdo->query($sql);
        $rows = $stmt->fetchAll();

        $result = [];
        foreach ($rows as $r) {
            $total = (int)$r['total_peserta'];
            $lulus = (int)$r['total_lulus'];
            $result[] = [
                'batch_name' => $r['batch_name'],
                'total_peserta' => $total,
                'total_pre' => (int)$r['total_pre'],
                'total_post' => (int)$r['total_post'],
                'avg_score' => round((float)$r['avg_score'], 1),
                'total_lulus' => $lulus,
                'persen_lulus' => ($total > 0) ? round(($lulus / $total) * 100, 1) : 0,
                'archived_at' => $r['archived_at'],
                'periode_ujian' => ($r['min_date'] === $r['max_date']) ? $r['min_date'] : ($r['min_date'] . ' s/d ' . $r['max_date'])
            ];
        }

        echo json_encode([
            'success' => true,
            'total' => count($result),
            'data' => $result
        ]);
        break;

    // 11. EKSPOR DATA ARSIP SESI TERTENTU KE CSV
    case 'export_archive':
        if (!isAuthorizedEvaluator($inputData)) {
            header('Content-Type: text/plain; charset=utf-8');
            http_response_code(403);
            echo 'Akses ditolak.';
            exit;
        }

        $batchName = trim($_GET['batch_name'] ?? $inputData['batch_name'] ?? '');
        if (empty($batchName)) {
            http_response_code(400);
            echo 'Nama batch arsip tidak ditentukan.';
            exit;
        }

        $stmt = $pdo->prepare("SELECT original_id as id, batch_name, session_id, kategori_kuis, tipe_ujian, nama_bumdes, nama_desa, kecamatan, kabupaten, nama_peserta, jabatan, tanggal_ujian, 
                                      jawaban_1, jawaban_2, jawaban_3, jawaban_4, jawaban_5, jawaban_6, 
                                      jumlah_benar, total_soal, skor, status_lulus, original_created_at, archived_at 
                               FROM `kuis_evaluasi_archive` WHERE batch_name = ? ORDER BY original_id ASC");
        $stmt->execute([$batchName]);
        $rows = $stmt->fetchAll();

        $safeBatchName = preg_replace('/[^a-zA-Z0-9_\-]/', '_', $batchName);
        header('Content-Type: text/csv; charset=utf-8');
        header('Content-Disposition: attachment; filename="Arsip_Evaluasi_' . $safeBatchName . '_' . date('Ymd_His') . '.csv"');

        $out = fopen('php://output', 'w');
        fputcsv($out, ['ID Asli', 'Batch Arsip', 'Modul Kuis', 'Tipe Ujian', 'Nama BUMDesa', 'Desa', 'Kecamatan', 'Kabupaten', 'Nama Peserta', 'Jabatan', 'Tanggal Ujian', 'Jawaban 1', 'Jawaban 2', 'Jawaban 3', 'Jawaban 4', 'Jawaban 5', 'Jawaban 6', 'Jumlah Benar', 'Total Soal', 'Skor (%)', 'Status Kelulusan', 'Waktu Submit Asli', 'Waktu Diarsipkan']);

        foreach ($rows as $r) {
            $modulName = ($r['kategori_kuis'] === 'tatakelola') ? 'Tata Kelola GCG' : 'Akuntansi SAK EMKM';
            fputcsv($out, [
                $r['id'],
                $r['batch_name'],
                $modulName,
                $r['tipe_ujian'],
                $r['nama_bumdes'],
                $r['nama_desa'] ?? '',
                $r['kecamatan'] ?? '',
                $r['kabupaten'] ?? '',
                $r['nama_peserta'],
                $r['jabatan'],
                $r['tanggal_ujian'],
                $r['jawaban_1'],
                $r['jawaban_2'],
                $r['jawaban_3'],
                $r['jawaban_4'],
                $r['jawaban_5'],
                $r['jawaban_6'] ?? '',
                $r['jumlah_benar'],
                $r['total_soal'] ?? (($r['kategori_kuis'] === 'tatakelola') ? 6 : 5),
                $r['skor'],
                $r['status_lulus'],
                $r['original_created_at'],
                $r['archived_at']
            ]);
        }
        fclose($out);
        exit;

    // 12. HAPUS BATCH ARSIP TERTENTU
    case 'delete_archive':
        if (!isAuthorizedEvaluator($inputData)) {
            http_response_code(403);
            echo json_encode(['success' => false, 'message' => 'Akses ditolak. Diperlukan izin Evaluator.']);
            exit;
        }

        $batchName = trim($inputData['batch_name'] ?? $_GET['batch_name'] ?? '');
        if (empty($batchName)) {
            echo json_encode(['success' => false, 'message' => 'Nama batch arsip tidak valid.']);
            exit;
        }

        $stmt = $pdo->prepare("DELETE FROM `kuis_evaluasi_archive` WHERE batch_name = ?");
        $stmt->execute([$batchName]);
        $deleted = $stmt->rowCount();

        echo json_encode([
            'success' => true,
            'message' => "Arsip batch '$batchName' ($deleted rekaman) berhasil dihapus."
        ]);
        break;

    // 13. RESTORE BATCH ARSIP KE DATABASE AKTIF (OPSI A: SAFE RESTORE DENGAN AUTO-ARCHIVE)
    case 'restore_archive':
        if (!isAuthorizedEvaluator($inputData)) {
            http_response_code(403);
            echo json_encode(['success' => false, 'message' => 'Akses ditolak. Diperlukan izin Evaluator.']);
            exit;
        }

        $batchName = trim($inputData['batch_name'] ?? $_GET['batch_name'] ?? '');
        if (empty($batchName)) {
            echo json_encode(['success' => false, 'message' => 'Nama batch arsip yang ingin di-restore tidak ditentukan.']);
            exit;
        }

        // Cek keberadaan batch di tabel arsip
        $checkStmt = $pdo->prepare("SELECT COUNT(*) FROM `kuis_evaluasi_archive` WHERE batch_name = ?");
        $checkStmt->execute([$batchName]);
        $archiveCount = (int)$checkStmt->fetchColumn();

        if ($archiveCount === 0) {
            echo json_encode(['success' => false, 'message' => "Arsip batch '$batchName' tidak ditemukan atau kosong."]);
            exit;
        }

        // Cek data aktif saat ini
        $activeCountStmt = $pdo->query("SELECT COUNT(*) FROM `kuis_evaluasi`");
        $activeCount = (int)$activeCountStmt->fetchColumn();
        $autoArchivedCount = 0;

        $autoArchiveCurrent = !empty($inputData['auto_archive_current']);
        $currentBatchName = trim($inputData['current_batch_name'] ?? '');
        if (empty($currentBatchName)) {
            $currentBatchName = 'Sesi Aktif Sebelum Restore ' . date('d-m-Y H:i');
        }

        // Jika ada data aktif dan auto_archive_current aktif, amankan data aktif saat ini ke arsip
        if ($activeCount > 0 && $autoArchiveCurrent) {
            $copySql = "INSERT INTO `kuis_evaluasi_archive` 
                (`batch_name`, `original_id`, `session_id`, `kategori_kuis`, `tipe_ujian`, `nama_bumdes`, `nama_desa`, `kecamatan`, `kabupaten`, `nama_peserta`, `jabatan`, `tanggal_ujian`, 
                 `jawaban_1`, `jawaban_2`, `jawaban_3`, `jawaban_4`, `jawaban_5`, `jawaban_6`, 
                 `jumlah_benar`, `total_soal`, `skor`, `status_lulus`, `ip_address`, `original_created_at`, `archived_at`)
                SELECT 
                    ?, `id`, `session_id`, `kategori_kuis`, `tipe_ujian`, `nama_bumdes`, `nama_desa`, `kecamatan`, `kabupaten`, `nama_peserta`, `jabatan`, `tanggal_ujian`,
                    `jawaban_1`, `jawaban_2`, `jawaban_3`, `jawaban_4`, `jawaban_5`, `jawaban_6`, 
                    `jumlah_benar`, `total_soal`, `skor`, `status_lulus`, `ip_address`, `created_at`, NOW()
                FROM `kuis_evaluasi`";
            $copyStmt = $pdo->prepare($copySql);
            $copyStmt->execute([$currentBatchName]);
            $autoArchivedCount = $activeCount;
        }

        // Kosongkan tabel aktif kuis_evaluasi
        try {
            $pdo->exec("TRUNCATE TABLE `kuis_evaluasi`");
        } catch (Exception $e) {
            $pdo->exec("DELETE FROM `kuis_evaluasi`");
            $pdo->exec("ALTER TABLE `kuis_evaluasi` AUTO_INCREMENT = 1");
        }

        // Salin batch arsip terpilih ke tabel aktif
        $restoreSql = "INSERT INTO `kuis_evaluasi` 
            (`session_id`, `kategori_kuis`, `tipe_ujian`, `nama_bumdes`, `nama_desa`, `kecamatan`, `kabupaten`, `nama_peserta`, `jabatan`, `tanggal_ujian`, 
             `jawaban_1`, `jawaban_2`, `jawaban_3`, `jawaban_4`, `jawaban_5`, `jawaban_6`, 
             `jumlah_benar`, `total_soal`, `skor`, `status_lulus`, `ip_address`, `created_at`)
            SELECT 
                `session_id`, `kategori_kuis`, `tipe_ujian`, `nama_bumdes`, `nama_desa`, `kecamatan`, `kabupaten`, `nama_peserta`, `jabatan`, `tanggal_ujian`,
                `jawaban_1`, `jawaban_2`, `jawaban_3`, `jawaban_4`, `jawaban_5`, `jawaban_6`, 
                `jumlah_benar`, `total_soal`, `skor`, `status_lulus`, `ip_address`, `original_created_at`
            FROM `kuis_evaluasi_archive`
            WHERE `batch_name` = ?
            ORDER BY `archive_id` ASC";
        $restoreStmt = $pdo->prepare($restoreSql);
        $restoreStmt->execute([$batchName]);
        $restoredCount = $restoreStmt->rowCount();

        $msg = "Batch arsip '$batchName' berhasil dipulihkan (restore) ke database aktif ($restoredCount data peserta).";
        if ($autoArchivedCount > 0) {
            $msg .= " Data aktif sebelumnya ($autoArchivedCount data) berhasil diamankan ke arsip '$currentBatchName'.";
        }

        echo json_encode([
            'success' => true,
            'message' => $msg,
            'restored_count' => $restoredCount,
            'auto_archived_count' => $autoArchivedCount,
            'batch_name' => $batchName
        ]);
        break;

    // 14. GET STATUS AKTIF MODUL KUIS (PUBLIC / EVALUATOR)
    case 'get_module_status':
        $stmt = $pdo->prepare("SELECT `setting_value` FROM `kuis_settings` WHERE `setting_key` = 'module_status' LIMIT 1");
        $stmt->execute();
        $row = $stmt->fetchColumn();
        $status = $row ? json_decode($row, true) : ['tatakelola' => true, 'akuntansi' => true];
        if (!is_array($status)) {
            $status = ['tatakelola' => true, 'akuntansi' => true];
        }
        echo json_encode([
            'success' => true,
            'data' => $status
        ]);
        break;

    // 15. TOGGLE / UPDATE STATUS AKTIF MODUL KUIS (KHUSUS EVALUATOR)
    case 'set_module_status':
        if (!isAuthorizedEvaluator($inputData)) {
            http_response_code(403);
            echo json_encode(['success' => false, 'message' => 'Akses ditolak. Diperlukan otentikasi Evaluator.']);
            exit;
        }

        $module = trim($inputData['module'] ?? '');
        $isActive = filter_var($inputData['is_active'] ?? true, FILTER_VALIDATE_BOOLEAN);

        if ($module !== 'tatakelola' && $module !== 'akuntansi') {
            echo json_encode(['success' => false, 'message' => 'Modul tidak valid. Pilih tatakelola atau akuntansi.']);
            exit;
        }

        $stmt = $pdo->prepare("SELECT `setting_value` FROM `kuis_settings` WHERE `setting_key` = 'module_status' LIMIT 1");
        $stmt->execute();
        $row = $stmt->fetchColumn();
        $status = $row ? json_decode($row, true) : ['tatakelola' => true, 'akuntansi' => true];
        if (!is_array($status)) {
            $status = ['tatakelola' => true, 'akuntansi' => true];
        }

        $status[$module] = $isActive;
        $newJson = json_encode($status);

        $upStmt = $pdo->prepare("INSERT INTO `kuis_settings` (`setting_key`, `setting_value`) VALUES ('module_status', ?) ON DUPLICATE KEY UPDATE `setting_value` = VALUES(`setting_value`)");
        $upStmt->execute([$newJson]);

        $modName = ($module === 'tatakelola') ? 'Tata Kelola (GCG)' : 'Akuntansi (SAK EMKM)';
        $statusLabel = $isActive ? 'Diaktifkan (Aktif)' : 'Dinonaktifkan (Deactivated)';

        echo json_encode([
            'success' => true,
            'message' => "Modul {$modName} berhasil {$statusLabel}.",
            'data' => $status
        ]);
        break;

    default:
        echo json_encode([
            'success' => true,
            'service' => 'API Kuis Evaluasi Pemahaman BUMDesa v3.6',
            'modules' => [
                'tatakelola' => 'Kuis Tata Kelola yang Baik bagi BUM Desa (GCG BUMDesa) - 6 Soal',
                'akuntansi' => 'Evaluasi Pemahaman Akuntansi & SAK EMKM BUMDesa - 5 Soal'
            ],
            'status' => 'ONLINE',
            'database' => 'CONNECTED'
        ]);
        break;
}
