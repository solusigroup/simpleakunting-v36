<?php
$c = $data['perusahaan'] ?? [];
$logoPath = !empty($c['path_logo']) ? BASEURL . '/' . $c['path_logo'] : BASEURL . '/img/logo_jatim.png';
$kota = !empty($c['kota_laporan']) ? $c['kota_laporan'] : 'Mojokerto';
$k = $data['kesehatan'] ?? [];
$r = $k['rasio_data'] ?? [];
$ringkasan = $r['ringkasan_keuangan'] ?? [];
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Laporan Analisa Bisnis & Kesehatan Usaha - <?php echo htmlspecialchars($c['nama_perusahaan'] ?? 'SimpleAkunting'); ?></title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <link rel="stylesheet" href="<?php echo BASEURL; ?>/css/print.css?v=3.6">
    <style>
        .health-score-box {
            border: 2px solid #0f172a;
            padding: 16px;
            border-radius: 8px;
            background: #f8fafc;
            text-align: center;
            margin-bottom: 20px;
        }
        .score-val {
            font-size: 38px;
            font-weight: 900;
            color: #1e3a8a;
            line-height: 1;
        }
        .badge-kategori {
            display: inline-block;
            background: #1e3a8a;
            color: #ffffff;
            font-size: 11px;
            font-weight: bold;
            padding: 4px 12px;
            border-radius: 9999px;
            text-transform: uppercase;
            margin-top: 6px;
        }
        .table-ratio {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 16px;
            font-size: 11px;
        }
        .table-ratio th, .table-ratio td {
            border: 1px solid #cbd5e1;
            padding: 6px 10px;
        }
        .table-ratio th {
            background: #f1f5f9;
            font-weight: bold;
            text-align: left;
        }
        .section-header {
            font-size: 13px;
            font-weight: bold;
            color: #0f172a;
            border-bottom: 2px solid #334155;
            padding-bottom: 4px;
            margin-top: 18px;
            margin-bottom: 10px;
            text-transform: uppercase;
        }
        .status-pill {
            font-weight: bold;
            text-transform: uppercase;
            font-size: 9px;
            padding: 2px 6px;
            border-radius: 4px;
        }
        .status-sehat { background: #dcfce7; color: #166534; }
        .status-waspada { background: #fef9c3; color: #854d0e; }
        .status-bahaya { background: #fee2e2; color: #991b1b; }
    </style>
</head>
<body class="print-preview-mode">

    <!-- Floating Top Toolbar (Screen Only) -->
    <div class="screen-container no-print">
        <div class="toolbar-print">
            <div class="toolbar-left">
                <a href="<?php echo BASEURL; ?>/analisis" class="btn-toolbar-back">
                    <i class="fas fa-arrow-left"></i>
                    <span>Kembali ke Analisa Bisnis</span>
                </a>
                <div>
                    <div style="font-weight: 800; font-size: 12px; color: #ffffff;">Laporan Analisa Bisnis & Kesehatan Usaha</div>
                    <div style="font-size: 11px; color: #94a3b8;">Standar SAK-EP &bull; Sistem SimpleAkunting</div>
                </div>
            </div>
            <div class="toolbar-right">
                <button onclick="window.print()" class="btn-toolbar-print">
                    <i class="fas fa-print"></i>
                    <span>Cetak Dokumen</span>
                </button>
            </div>
        </div>
    </div>

    <!-- Halaman Cetak -->
    <div class="print-page">
        <!-- Kop Surat -->
        <div style="display: flex; align-items: center; border-bottom: 2px solid #000; padding-bottom: 12px; margin-bottom: 16px;">
            <img src="<?php echo $logoPath; ?>" alt="Logo" style="width: 60px; height: 60px; object-fit: contain; margin-right: 16px;">
            <div style="flex: 1;">
                <h2 style="margin: 0; font-size: 18px; font-weight: 800; text-transform: uppercase; color: #000;">
                    <?php echo htmlspecialchars($c['nama_perusahaan'] ?? 'BUMDesa / Perusahaan'); ?>
                </h2>
                <div style="font-size: 11px; color: #333; margin-top: 2px;">
                    <?php echo htmlspecialchars($c['alamat'] ?? ''); ?> &bull; Telp: <?php echo htmlspecialchars($c['telepon'] ?? '-'); ?>
                </div>
                <div style="font-size: 11px; font-weight: bold; color: #1e3a8a; margin-top: 2px;">
                    LAPORAN HASIL EVALUASI & ANALISA KESEHATAN USAHA
                </div>
            </div>
            <div style="text-align: right; font-size: 10px; color: #64748b;">
                <div>Periode: <strong><?php echo $data['periode']; ?></strong></div>
                <div>Dicetak: <?php echo date('d/m/Y H:i'); ?></div>
            </div>
        </div>

        <!-- Box Skor Kesehatan -->
        <div class="health-score-box">
            <div style="font-size: 11px; font-weight: bold; color: #64748b; text-transform: uppercase; letter-spacing: 1px;">
                Indeks Kesehatan Usaha Komposit
            </div>
            <div class="score-val"><?php echo $k['skor_total'] ?? 0; ?> <span style="font-size: 18px; color: #64748b;">/ 100</span></div>
            <div style="font-size: 15px; font-weight: 800; color: #0f172a; margin-top: 4px;">
                PREDIKAT: <?php echo $k['predikat'] ?? '-'; ?>
            </div>
            <div class="badge-kategori">Kategori <?php echo $k['kategori'] ?? '-'; ?> &bull; <?php echo $k['altman_z']['zone'] ?? ''; ?></div>
            <p style="font-size: 11px; color: #475569; margin: 8px auto 0; max-width: 600px;">
                <?php echo $k['kesimpulan'] ?? ''; ?>
            </p>
        </div>

        <!-- Pilar Penilaian -->
        <div style="display: flex; gap: 8px; margin-bottom: 16px;">
            <?php foreach (($k['pilar'] ?? []) as $p): ?>
                <div style="flex: 1; border: 1px solid #cbd5e1; border-radius: 6px; padding: 8px; text-align: center; background: #ffffff;">
                    <div style="font-size: 10px; color: #64748b; font-weight: bold;"><?php echo $p['nama']; ?></div>
                    <div style="font-size: 16px; font-weight: bold; color: #0f172a;"><?php echo $p['skor']; ?>/100</div>
                    <div style="font-size: 9px; color: #94a3b8;">Bobot: <?php echo $p['bobot']; ?>%</div>
                </div>
            <?php endforeach; ?>
        </div>

        <!-- Matriks Rasio Keuangan -->
        <div class="section-header">1. Matriks Rasio Keuangan Utama</div>
        <table class="table-ratio">
            <thead>
                <tr>
                    <th>Kelompok Rasio</th>
                    <th>Nama Indikator</th>
                    <th>Formula</th>
                    <th style="text-align: right;">Nilai</th>
                    <th>Standar Sehat</th>
                    <th style="text-align: center;">Status</th>
                </tr>
            </thead>
            <tbody>
                <!-- Likuiditas -->
                <tr>
                    <td rowspan="3" style="font-weight: bold; vertical-align: middle;">Likuiditas</td>
                    <td>Rasio Lancar (Current Ratio)</td>
                    <td>Aset Lancar / Utang Lancar</td>
                    <td style="text-align: right; font-weight: bold;"><?php echo number_format($r['likuiditas']['rasio_lancar']['nilai'] ?? 0, 2); ?> : 1</td>
                    <td>≥ 1.50 : 1</td>
                    <td style="text-align: center;"><span class="status-pill status-<?php echo $r['likuiditas']['rasio_lancar']['status'] ?? 'sehat'; ?>"><?php echo $r['likuiditas']['rasio_lancar']['status'] ?? ''; ?></span></td>
                </tr>
                <tr>
                    <td>Rasio Cepat (Quick Ratio)</td>
                    <td>(Aset Lancar - Stok) / Utang Lancar</td>
                    <td style="text-align: right; font-weight: bold;"><?php echo number_format($r['likuiditas']['rasio_cepat']['nilai'] ?? 0, 2); ?> : 1</td>
                    <td>≥ 1.00 : 1</td>
                    <td style="text-align: center;"><span class="status-pill status-<?php echo $r['likuiditas']['rasio_cepat']['status'] ?? 'sehat'; ?>"><?php echo $r['likuiditas']['rasio_cepat']['status'] ?? ''; ?></span></td>
                </tr>
                <tr>
                    <td>Rasio Kas (Cash Ratio)</td>
                    <td>Kas / Utang Lancar</td>
                    <td style="text-align: right; font-weight: bold;"><?php echo number_format($r['likuiditas']['rasio_kas']['nilai'] ?? 0, 2); ?> : 1</td>
                    <td>≥ 0.25 : 1</td>
                    <td style="text-align: center;"><span class="status-pill status-<?php echo $r['likuiditas']['rasio_kas']['status'] ?? 'sehat'; ?>"><?php echo $r['likuiditas']['rasio_kas']['status'] ?? ''; ?></span></td>
                </tr>
                <!-- Solvabilitas -->
                <tr>
                    <td rowspan="2" style="font-weight: bold; vertical-align: middle;">Solvabilitas</td>
                    <td>Debt to Asset Ratio (DAR)</td>
                    <td>Total Utang / Total Aset</td>
                    <td style="text-align: right; font-weight: bold;"><?php echo number_format($r['solvabilitas']['dar']['nilai'] ?? 0, 2); ?> %</td>
                    <td>≤ 50.00%</td>
                    <td style="text-align: center;"><span class="status-pill status-<?php echo $r['solvabilitas']['dar']['status'] ?? 'sehat'; ?>"><?php echo $r['solvabilitas']['dar']['status'] ?? ''; ?></span></td>
                </tr>
                <tr>
                    <td>Debt to Equity Ratio (DER)</td>
                    <td>Total Utang / Modal Sendiri</td>
                    <td style="text-align: right; font-weight: bold;"><?php echo number_format($r['solvabilitas']['der']['nilai'] ?? 0, 2); ?> %</td>
                    <td>≤ 100.00%</td>
                    <td style="text-align: center;"><span class="status-pill status-<?php echo $r['solvabilitas']['der']['status'] ?? 'sehat'; ?>"><?php echo $r['solvabilitas']['der']['status'] ?? ''; ?></span></td>
                </tr>
                <!-- Profitabilitas -->
                <tr>
                    <td rowspan="3" style="font-weight: bold; vertical-align: middle;">Profitabilitas</td>
                    <td>Margin Laba Bersih (NPM)</td>
                    <td>Laba Bersih / Total Pendapatan</td>
                    <td style="text-align: right; font-weight: bold;"><?php echo number_format($r['profitabilitas']['npm']['nilai'] ?? 0, 2); ?> %</td>
                    <td>≥ 10.00%</td>
                    <td style="text-align: center;"><span class="status-pill status-<?php echo $r['profitabilitas']['npm']['status'] ?? 'sehat'; ?>"><?php echo $r['profitabilitas']['npm']['status'] ?? ''; ?></span></td>
                </tr>
                <tr>
                    <td>Return on Assets (ROA)</td>
                    <td>Laba Bersih / Total Aset</td>
                    <td style="text-align: right; font-weight: bold;"><?php echo number_format($r['profitabilitas']['roa']['nilai'] ?? 0, 2); ?> %</td>
                    <td>≥ 5.00%</td>
                    <td style="text-align: center;"><span class="status-pill status-<?php echo $r['profitabilitas']['roa']['status'] ?? 'sehat'; ?>"><?php echo $r['profitabilitas']['roa']['status'] ?? ''; ?></span></td>
                </tr>
                <tr>
                    <td>Return on Equity (ROE)</td>
                    <td>Laba Bersih / Total Ekuitas</td>
                    <td style="text-align: right; font-weight: bold;"><?php echo number_format($r['profitabilitas']['roe']['nilai'] ?? 0, 2); ?> %</td>
                    <td>≥ 10.00%</td>
                    <td style="text-align: center;"><span class="status-pill status-<?php echo $r['profitabilitas']['roe']['status'] ?? 'sehat'; ?>"><?php echo $r['profitabilitas']['roe']['status'] ?? ''; ?></span></td>
                </tr>
                <!-- Efisiensi -->
                <tr>
                    <td rowspan="2" style="font-weight: bold; vertical-align: middle;">Aktivitas</td>
                    <td>Perputaran Total Aset (TATO)</td>
                    <td>Pendapatan / Total Aset</td>
                    <td style="text-align: right; font-weight: bold;"><?php echo number_format($r['aktivitas']['tato']['nilai'] ?? 0, 2); ?> x</td>
                    <td>≥ 1.00x</td>
                    <td style="text-align: center;"><span class="status-pill status-<?php echo $r['aktivitas']['tato']['status'] ?? 'sehat'; ?>"><?php echo $r['aktivitas']['tato']['status'] ?? ''; ?></span></td>
                </tr>
                <tr>
                    <td>Rasio Biaya Operasional (BOPO)</td>
                    <td>Beban / Pendapatan</td>
                    <td style="text-align: right; font-weight: bold;"><?php echo number_format($r['aktivitas']['bopo']['nilai'] ?? 0, 2); ?> %</td>
                    <td>≤ 85.00%</td>
                    <td style="text-align: center;"><span class="status-pill status-<?php echo $r['aktivitas']['bopo']['status'] ?? 'sehat'; ?>"><?php echo $r['aktivitas']['bopo']['status'] ?? ''; ?></span></td>
                </tr>
            </tbody>
        </table>

        <!-- SWOT & Rekomendasi Manajerial -->
        <div class="section-header">2. Rekomendasi Aksi Strategis Manajerial</div>
        <table class="table-ratio">
            <thead>
                <tr>
                    <th style="width: 100px;">Prioritas</th>
                    <th style="width: 160px;">Bidang</th>
                    <th>Arahan Tindakan Perbaikan</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach (($k['rekomendasi'] ?? []) as $rek): ?>
                <tr>
                    <td style="font-weight: bold;"><?php echo $rek['prioritas']; ?></td>
                    <td><strong><?php echo htmlspecialchars($rek['bidang']); ?></strong></td>
                    <td><?php echo htmlspecialchars($rek['saran']); ?></td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>

        <!-- Tanda Tangan -->
        <div style="margin-top: 30px; display: flex; justify-content: space-between; page-break-inside: avoid;">
            <div style="width: 200px; text-align: center; font-size: 11px;">
                <div>Mengetahui,</div>
                <div style="font-weight: bold; margin-bottom: 50px;">Direktur / Pengelola Usaha</div>
                <div style="border-bottom: 1px solid #000; font-weight: bold;">( .................................... )</div>
            </div>
            <div style="width: 200px; text-align: center; font-size: 11px;">
                <div><?php echo htmlspecialchars($kota); ?>, <?php echo date('d F Y'); ?></div>
                <div style="font-weight: bold; margin-bottom: 50px;">Bagian Keuangan / Akuntan</div>
                <div style="border-bottom: 1px solid #000; font-weight: bold;">( .................................... )</div>
            </div>
        </div>
    </div>
</body>
</html>
