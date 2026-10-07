<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Arus Kas - <?php echo htmlspecialchars($data['perusahaan']['nama_perusahaan'] ?? ''); ?></title>
    <style>
        body { font-family: 'Helvetica', 'Arial', sans-serif; font-size: 8.5pt; color: #1e293b; margin: 0; padding: 0; line-height: 1.4; }
        .header { text-align: center; margin-bottom: 12px; }
        .header h2 { margin: 0; font-size: 14pt; text-transform: uppercase; color: #0f172a; font-weight: bold; }
        .header p { margin: 2px 0; font-size: 8pt; color: #475569; }
        .divider-double { border-top: 2px solid #0f172a; border-bottom: 1px solid #94a3b8; height: 2px; margin-bottom: 15px; }
        .report-title { text-align: center; margin-bottom: 16px; }
        .report-title h3 { margin: 0; font-size: 12pt; text-transform: uppercase; color: #0f172a; font-weight: bold; letter-spacing: 0.5px; }
        .report-title p { margin: 3px 0 0 0; font-size: 8pt; color: #64748b; }
        table { width: 100%; border-collapse: collapse; margin-bottom: 20px; }
        th { background-color: #0f172a; color: #ffffff; border: 1px solid #0f172a; padding: 6px 8px; text-align: left; font-size: 8pt; font-weight: bold; }
        td { border: 1px solid #cbd5e1; padding: 5px 8px; font-size: 8pt; }
        .text-end { text-align: right; }
        .text-center { text-align: center; }
        .fw-bold { font-weight: bold; }
        .ps-4 { padding-left: 20px; }
        .bg-section { background-color: #f1f5f9; font-weight: bold; color: #0f172a; }
        .total-row { background-color: #f8fafc; font-weight: bold; }
        .grand-total { background-color: #0f172a; color: #ffffff; font-weight: bold; font-size: 9pt; }
        .grand-total td { border: 1px solid #0f172a; }
        .footer-table { width: 100%; border: none; margin-top: 30px; page-break-inside: avoid; }
        .footer-table td { border: none; text-align: center; width: 50%; font-size: 8pt; }
    </style>
</head>
<body>
    <div class="header">
        <h2><?php echo htmlspecialchars($data['perusahaan']['nama_perusahaan'] ?? 'KLINIK BUMDESA PROVINSI JAWA TIMUR'); ?></h2>
        <p><?php echo htmlspecialchars($data['perusahaan']['alamat'] ?? ''); ?></p>
        <p><?php if(!empty($data['perusahaan']['telepon'])) echo "Telp: " . htmlspecialchars($data['perusahaan']['telepon']) . " | "; ?>Email: <?php echo htmlspecialchars($data['perusahaan']['email'] ?? '-'); ?></p>
    </div>
    <div class="divider-double"></div>

    <div class="report-title">
        <h3>LAPORAN ARUS KAS (CASH FLOW)</h3>
        <p>Metode: <?php echo strtoupper($data['laporan']['metode'] ?? 'Indirect'); ?> &bull; Periode: <?php echo htmlspecialchars($data['periode_1'] ?? ''); ?></p>
    </div>

    <table>
        <thead>
            <tr>
                <th style="width: 70%;">Aktivitas / Uraian Arus Kas</th>
                <th class="text-end" style="width: 30%;">Jumlah (Rp)</th>
            </tr>
        </thead>
        <tbody>
            <tr class="bg-section">
                <td colspan="2">I. ARUS KAS DARI AKTIVITAS OPERASI</td>
            </tr>
            <?php if(($data['laporan']['metode'] ?? 'Indirect') == 'Indirect'): ?>
                <tr>
                    <td class="ps-4">Laba (Rugi) Bersih Periode Berjalan</td>
                    <td class="text-end"><?php echo number_format($data['laporan']['laba_bersih'] ?? 0, 0, ',', '.'); ?></td>
                </tr>
                <?php 
                $totalOperasi = (float)($data['laporan']['laba_bersih'] ?? 0); 
                foreach($data['laporan']['penyesuaian'] ?? [] as $item): 
                    $v = (float)($item['jumlah'] ?? 0);
                    $totalOperasi += $v; 
                ?>
                    <tr>
                        <td class="ps-4"><?php echo htmlspecialchars($item['label']); ?></td>
                        <td class="text-end"><?php echo ($v < 0) ? '(' . number_format(abs($v), 0, ',', '.') . ')' : number_format($v, 0, ',', '.'); ?></td>
                    </tr>
                <?php endforeach; ?>
            <?php else: ?>
                <?php 
                $totalOperasi = 0; 
                foreach($data['laporan']['arus_operasi'] ?? [] as $item): 
                    $v = (float)($item['jumlah'] ?? 0);
                    $totalOperasi += $v; 
                ?>
                    <tr>
                        <td class="ps-4"><?php echo htmlspecialchars($item['label']); ?></td>
                        <td class="text-end"><?php echo ($v < 0) ? '(' . number_format(abs($v), 0, ',', '.') . ')' : number_format($v, 0, ',', '.'); ?></td>
                    </tr>
                <?php endforeach; ?>
            <?php endif; ?>
            <tr class="total-row">
                <td>ARUS KAS BERSIH DARI AKTIVITAS OPERASI</td>
                <td class="text-end"><?php echo ($totalOperasi < 0) ? '(' . number_format(abs($totalOperasi), 0, ',', '.') . ')' : number_format($totalOperasi, 0, ',', '.'); ?></td>
            </tr>

            <tr class="bg-section">
                <td colspan="2">II. REKONSILIASI SALDO KAS &amp; SETARA KAS</td>
            </tr>
            <tr>
                <td class="ps-4">Saldo Kas Awal Periode</td>
                <td class="text-end"><?php echo number_format($data['laporan']['kas_awal'] ?? 0, 0, ',', '.'); ?></td>
            </tr>
            <?php 
                $kenaikanKas = (float)($data['laporan']['kas_akhir'] ?? 0) - (float)($data['laporan']['kas_awal'] ?? 0);
            ?>
            <tr>
                <td class="ps-4">Kenaikan (Penurunan) Kas Bersih</td>
                <td class="text-end"><?php echo ($kenaikanKas < 0) ? '(' . number_format(abs($kenaikanKas), 0, ',', '.') . ')' : number_format($kenaikanKas, 0, ',', '.'); ?></td>
            </tr>
        </tbody>
        <tfoot>
            <tr class="grand-total">
                <td>SALDO KAS &amp; SETARA KAS AKHIR PERIODE</td>
                <td class="text-end"><?php echo number_format($data['laporan']['kas_akhir'] ?? 0, 0, ',', '.'); ?></td>
            </tr>
        </tfoot>
    </table>

    <table class="footer-table">
        <tr>
            <td>
                <p><?php echo htmlspecialchars($data['penandatangan_1']['jabatan']); ?></p>
                <br><br><br>
                <p class="fw-bold"><u><?php echo htmlspecialchars($data['penandatangan_1']['nama_user']); ?></u></p>
            </td>
            <td>
                <p><?php echo htmlspecialchars($data['kota_laporan']); ?>, <?php echo date('d F Y'); ?></p>
                <p><?php echo htmlspecialchars($data['penandatangan_2']['jabatan']); ?></p>
                <br><br><br>
                <p class="fw-bold"><u><?php echo htmlspecialchars($data['penandatangan_2']['nama_user']); ?></u></p>
            </td>
        </tr>
    </table>
</body>
</html>
