<?php
/**
 * API Backend Kuis Evaluasi Pemahaman BUMDesa - SimpleAkunting v3.6
 * Menangani penyimpanan database, rekapitulasi nilai, dan otentikasi dashboard evaluator.
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

// Kunci Jawaban Resmi Evaluasi
const ANSWER_KEYS = [
    1 => 'B',
    2 => 'B',
    3 => 'C',
    4 => 'B',
    5 => 'B'
];

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
        `tipe_ujian` VARCHAR(20) NOT NULL,
        `nama_bumdes` VARCHAR(255) NOT NULL,
        `nama_peserta` VARCHAR(255) NOT NULL,
        `jabatan` VARCHAR(100) NOT NULL,
        `tanggal_ujian` DATE NOT NULL,
        `jawaban_1` CHAR(1) NOT NULL,
        `jawaban_2` CHAR(1) NOT NULL,
        `jawaban_3` CHAR(1) NOT NULL,
        `jawaban_4` CHAR(1) NOT NULL,
        `jawaban_5` CHAR(1) NOT NULL,
        `jumlah_benar` INT NOT NULL,
        `skor` INT NOT NULL,
        `status_lulus` VARCHAR(50) NOT NULL,
        `ip_address` VARCHAR(45) NULL,
        `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
        INDEX idx_bumdes (`nama_bumdes`),
        INDEX idx_tipe (`tipe_ujian`),
        INDEX idx_tanggal (`tanggal_ujian`)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;";
    
    $pdo->exec($createTableQuery);

} catch (Exception $e) {
    echo json_encode([
        'success' => false, 
        'message' => 'Gagal terhubung ke database: ' . $e->getMessage()
    ]);
    exit;
}

// Ambil input JSON atau Request Parameters
$inputRaw = file_get_contents('php://input');
$inputData = json_decode($inputRaw, true) ?? [];
$action = $_GET['action'] ?? $inputData['action'] ?? '';

// Helper validasi evaluator
function isAuthorizedEvaluator($inputData) {
    $pass = $_SERVER['HTTP_X_EVALUATOR_KEY'] ?? $_GET['eval_key'] ?? $inputData['eval_key'] ?? '';
    return ($pass === EVALUATOR_PASSWORD);
}

switch ($action) {

    // 1. SUBMIT JAWABAN PESERTA KE DATABASE
    case 'submit':
        $tipeUjian = trim($inputData['tipe_ujian'] ?? 'Pre-Test');
        $namaBumdes = trim($inputData['nama_bumdes'] ?? '');
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

        if ($j1 === ANSWER_KEYS[1]) $correctCount++;
        if ($j2 === ANSWER_KEYS[2]) $correctCount++;
        if ($j3 === ANSWER_KEYS[3]) $correctCount++;
        if ($j4 === ANSWER_KEYS[4]) $correctCount++;
        if ($j5 === ANSWER_KEYS[5]) $correctCount++;

        $skor = $correctCount * 20; // 5 soal x 20 = 100
        $statusLulus = ($skor >= 80) ? 'LULUS TEKNIS' : 'PERLU PENDAMPINGAN';
        $ipAddress = $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0';
        $sessionId = bin2hex(random_bytes(16));

        $stmt = $pdo->prepare("INSERT INTO `kuis_evaluasi` 
            (`session_id`, `tipe_ujian`, `nama_bumdes`, `nama_peserta`, `jabatan`, `tanggal_ujian`, 
             `jawaban_1`, `jawaban_2`, `jawaban_3`, `jawaban_4`, `jawaban_5`, 
             `jumlah_benar`, `skor`, `status_lulus`, `ip_address`, `created_at`)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, NOW())");

        $stmt->execute([
            $sessionId, $tipeUjian, $namaBumdes, $namaPeserta, $jabatan, $tanggalUjian,
            $j1, $j2, $j3, $j4, $j5,
            $correctCount, $skor, $statusLulus, $ipAddress
        ]);

        $insertedId = $pdo->lastInsertId();

        // Cari perbandingan Pre-Test vs Post-Test untuk peserta yang sama di BUMDesa yang sama
        $preScore = null;
        $postScore = null;
        $deltaScore = null;

        if ($tipeUjian === 'Post-Test') {
            $preStmt = $pdo->prepare("SELECT skor, jumlah_benar, tanggal_ujian, created_at FROM `kuis_evaluasi` 
                WHERE LOWER(nama_peserta) = LOWER(?) AND LOWER(nama_bumdes) = LOWER(?) AND tipe_ujian = 'Pre-Test' 
                ORDER BY id DESC LIMIT 1");
            $preStmt->execute([$namaPeserta, $namaBumdes]);
            $preRow = $preStmt->fetch();
            if ($preRow) {
                $preScore = (int)$preRow['skor'];
                $postScore = $skor;
                $deltaScore = $postScore - $preScore;
            }
        } elseif ($tipeUjian === 'Pre-Test') {
            $postStmt = $pdo->prepare("SELECT skor, jumlah_benar, tanggal_ujian, created_at FROM `kuis_evaluasi` 
                WHERE LOWER(nama_peserta) = LOWER(?) AND LOWER(nama_bumdes) = LOWER(?) AND tipe_ujian = 'Post-Test' 
                ORDER BY id DESC LIMIT 1");
            $postStmt->execute([$namaPeserta, $namaBumdes]);
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
                'tipe_ujian' => $tipeUjian,
                'nama_bumdes' => $namaBumdes,
                'nama_peserta' => $namaPeserta,
                'jabatan' => $jabatan,
                'jumlah_benar' => $correctCount,
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

    // 1B. MATRIKS PERBANDINGAN PRE-TEST VS POST-TEST PESERTA YANG SAMA (KHUSUS EVALUATOR)
    case 'comparison_matrix':
        if (!isAuthorizedEvaluator($inputData)) {
            http_response_code(403);
            echo json_encode(['success' => false, 'message' => 'Akses ditolak. Diperlukan izin Evaluator.']);
            exit;
        }

        $matrixSql = "SELECT 
            nama_peserta, 
            nama_bumdes, 
            MAX(jabatan) as jabatan,
            MAX(CASE WHEN tipe_ujian = 'Pre-Test' THEN skor ELSE NULL END) as pre_score,
            MAX(CASE WHEN tipe_ujian = 'Post-Test' THEN skor ELSE NULL END) as post_score,
            MAX(CASE WHEN tipe_ujian = 'Pre-Test' THEN jumlah_benar ELSE NULL END) as pre_correct,
            MAX(CASE WHEN tipe_ujian = 'Post-Test' THEN jumlah_benar ELSE NULL END) as post_correct,
            MAX(CASE WHEN tipe_ujian = 'Pre-Test' THEN tanggal_ujian ELSE NULL END) as pre_date,
            MAX(CASE WHEN tipe_ujian = 'Post-Test' THEN tanggal_ujian ELSE NULL END) as post_date
        FROM `kuis_evaluasi`
        GROUP BY LOWER(TRIM(nama_peserta)), LOWER(TRIM(nama_bumdes))
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
                'jabatan' => $row['jabatan'],
                'pre_score' => $pre,
                'post_score' => $post,
                'delta' => $delta,
                'pre_correct' => $row['pre_correct'],
                'post_correct' => $row['post_correct'],
                'pre_date' => $row['pre_date'],
                'post_date' => $row['post_date'],
                'is_complete_pair' => ($pre !== null && $post !== null),
                'status_akhir' => ($post !== null) ? (($post >= 80) ? 'LULUS TEKNIS' : 'PERLU PENDAMPINGAN') : 'BELUM POST-TEST'
            ];
        }

        $summary = [
            'total_peserta_terdaftar' => count($matrixRows),
            'total_lengkap_pre_post' => $totalPaired,
            'avg_pre_score' => ($totalPaired > 0) ? round($sumPre / $totalPaired, 1) : 0,
            'avg_post_score' => ($totalPaired > 0) ? round($sumPost / $totalPaired, 1) : 0,
            'avg_delta' => ($totalPaired > 0) ? round($sumDelta / $totalPaired, 1) : 0
        ];

        echo json_encode([
            'success' => true,
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

    // 3. DAFTAR HASIL SELURUH PESERTA (KHUSUS EVALUATOR)
    case 'list':
        if (!isAuthorizedEvaluator($inputData)) {
            http_response_code(403);
            echo json_encode(['success' => false, 'message' => 'Akses ditolak. Diperlukan izin Evaluator.']);
            exit;
        }

        $search = trim($_GET['search'] ?? '');
        $filterType = trim($_GET['type'] ?? '');
        $filterStatus = trim($_GET['status'] ?? '');

        $sql = "SELECT id, session_id, tipe_ujian, nama_bumdes, nama_peserta, jabatan, tanggal_ujian, 
                       jawaban_1, jawaban_2, jawaban_3, jawaban_4, jawaban_5,
                       jumlah_benar, skor, status_lulus, created_at 
                FROM `kuis_evaluasi` WHERE 1=1";
        $params = [];

        if (!empty($search)) {
            $sql .= " AND (nama_bumdes LIKE ? OR nama_peserta LIKE ?)";
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
            'total' => count($rows),
            'data' => $rows
        ]);
        break;

    // 4. STATISTIK & ANALISIS BUTIR SOAL (ITEM DIFFICULTY ANALYSIS)
    case 'stats':
        if (!isAuthorizedEvaluator($inputData)) {
            http_response_code(403);
            echo json_encode(['success' => false, 'message' => 'Akses ditolak. Diperlukan izin Evaluator.']);
            exit;
        }

        $totalStmt = $pdo->query("SELECT COUNT(*) as total, 
                                         COALESCE(AVG(skor), 0) as avg_score,
                                         SUM(CASE WHEN skor >= 80 THEN 1 ELSE 0 END) as total_lulus,
                                         SUM(CASE WHEN skor < 80 THEN 1 ELSE 0 END) as total_belum,
                                         SUM(CASE WHEN tipe_ujian = 'Pre-Test' THEN 1 ELSE 0 END) as total_pre,
                                         SUM(CASE WHEN tipe_ujian = 'Post-Test' THEN 1 ELSE 0 END) as total_post
                                  FROM `kuis_evaluasi`");
        $overview = $totalStmt->fetch();

        // Analisis Butir Soal 1 s/d 5 (Tingkat Ketepatan Jawaban)
        $itemAnalysisQuery = "SELECT 
            SUM(CASE WHEN jawaban_1 = 'B' THEN 1 ELSE 0 END) as q1_correct,
            SUM(CASE WHEN jawaban_2 = 'B' THEN 1 ELSE 0 END) as q2_correct,
            SUM(CASE WHEN jawaban_3 = 'C' THEN 1 ELSE 0 END) as q3_correct,
            SUM(CASE WHEN jawaban_4 = 'B' THEN 1 ELSE 0 END) as q4_correct,
            SUM(CASE WHEN jawaban_5 = 'B' THEN 1 ELSE 0 END) as q5_correct,
            COUNT(*) as total_responses
        FROM `kuis_evaluasi`";
        $itemStmt = $pdo->query($itemAnalysisQuery);
        $itemData = $itemStmt->fetch();

        $totalResp = max((int)($itemData['total_responses'] ?? 0), 1);

        $questionStats = [
            1 => [
                'topic' => 'Konsep Stewardship & Moralitas Laporan Keuangan',
                'correct_count' => (int)($itemData['q1_correct'] ?? 0),
                'percent' => round(((int)($itemData['q1_correct'] ?? 0) / $totalResp) * 100, 1)
            ],
            2 => [
                'topic' => 'Pemisahan Kas Entitas BUMDesa dari Pribadi Pengurus',
                'correct_count' => (int)($itemData['q2_correct'] ?? 0),
                'percent' => round(((int)($itemData['q2_correct'] ?? 0) / $totalResp) * 100, 1)
            ],
            3 => [
                'topic' => 'Pencatatan Penjualan Kredit (Piutang vs BKU)',
                'correct_count' => (int)($itemData['q3_correct'] ?? 0),
                'percent' => round(((int)($itemData['q3_correct'] ?? 0) / $totalResp) * 100, 1)
            ],
            4 => [
                'topic' => 'Komponen Pokok SAK EMKM & Kepmen Desa 136/2022',
                'correct_count' => (int)($itemData['q4_correct'] ?? 0),
                'percent' => round(((int)($itemData['q4_correct'] ?? 0) / $totalResp) * 100, 1)
            ],
            5 => [
                'topic' => 'Keseimbangan Neraca (Aset = Liabilitas + Ekuitas)',
                'correct_count' => (int)($itemData['q5_correct'] ?? 0),
                'percent' => round(((int)($itemData['q5_correct'] ?? 0) / $totalResp) * 100, 1)
            ]
        ];

        echo json_encode([
            'success' => true,
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

    // 7. EKSPOR CSV
    case 'export':
        if (!isAuthorizedEvaluator($inputData)) {
            header('Content-Type: text/plain; charset=utf-8');
            http_response_code(403);
            echo 'Akses ditolak.';
            exit;
        }

        $stmt = $pdo->query("SELECT id, session_id, tipe_ujian, nama_bumdes, nama_peserta, jabatan, tanggal_ujian, 
                                    jawaban_1, jawaban_2, jawaban_3, jawaban_4, jawaban_5, 
                                    jumlah_benar, skor, status_lulus, created_at 
                             FROM `kuis_evaluasi` ORDER BY id DESC");
        $rows = $stmt->fetchAll();

        header('Content-Type: text/csv; charset=utf-8');
        header('Content-Disposition: attachment; filename="Rekap_Evaluasi_BUMDesa_' . date('Y-m-d_His') . '.csv"');

        $out = fopen('php://output', 'w');
        fputcsv($out, ['ID', 'Tipe Ujian', 'Nama BUMDesa', 'Nama Peserta', 'Jabatan', 'Tanggal', 'Jawaban 1', 'Jawaban 2', 'Jawaban 3', 'Jawaban 4', 'Jawaban 5', 'Jumlah Benar', 'Skor (%)', 'Status Kelulusan', 'Waktu Submit']);

        foreach ($rows as $r) {
            fputcsv($out, [
                $r['id'],
                $r['tipe_ujian'],
                $r['nama_bumdes'],
                $r['nama_peserta'],
                $r['jabatan'],
                $r['tanggal_ujian'],
                $r['jawaban_1'],
                $r['jawaban_2'],
                $r['jawaban_3'],
                $r['jawaban_4'],
                $r['jawaban_5'],
                $r['jumlah_benar'],
                $r['skor'],
                $r['status_lulus'],
                $r['created_at']
            ]);
        }
        fclose($out);
        exit;

    default:
        echo json_encode([
            'success' => true,
            'service' => 'API Kuis Evaluasi Pemahaman BUMDesa v3.6',
            'status' => 'ONLINE',
            'database' => 'CONNECTED'
        ]);
        break;
}
