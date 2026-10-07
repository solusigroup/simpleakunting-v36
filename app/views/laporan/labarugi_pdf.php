<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Laba Rugi - <?php echo htmlspecialchars($data['perusahaan']['nama_perusahaan'] ?? ''); ?></title>
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
        <h3>LAPORAN LABA RUGI KOMPREHENSIF</h3>
        <p>Periode: <?php echo $data['periode_1']; ?> <?php if($data['periode_2']) echo " vs " . $data['periode_2']; ?></p>
    </div>

    <table>
        <thead>
            <tr>
                <th style="width: 50%;">Pos Akun / Keterangan</th>
                <th class="text-end" style="width: <?php echo $data['periode_2'] ? '25%' : '50%'; ?>;">Periode (Rp)<br><span style="font-size: 6.5pt; font-weight: normal;"><?php echo $data['periode_1']; ?></span></th>
                <?php if($data['periode_2']): ?>
                    <th class="text-end" style="width: 25%;">Pembanding (Rp)<br><span style="font-size: 6.5pt; font-weight: normal;"><?php echo $data['periode_2']; ?></span></th>
                <?php endif; ?>
            </tr>
        </thead>
        <tbody>
            <tr class="bg-section">
                <td colspan="<?php echo $data['periode_2'] ? '3' : '2'; ?>">I. PENDAPATAN OPERASIONAL &amp; USAHA</td>
            </tr>
            <?php foreach($data['laporan']['pendapatan'] as $item): ?>
            <tr>
                <td class="ps-4"><?php echo htmlspecialchars($item['nama_akun']); ?></td>
                <td class="text-end"><?php echo number_format($item['total_1'], 0, ',', '.'); ?></td>
                <?php if($data['periode_2']): ?>
                    <td class="text-end"><?php echo number_format($item['total_2'], 0, ',', '.'); ?></td>
                <?php endif; ?>
            </tr>
            <?php endforeach; ?>
            <tr class="total-row">
                <td>TOTAL PENDAPATAN</td>
                <td class="text-end"><?php echo number_format($data['laporan']['total_pendapatan_1'], 0, ',', '.'); ?></td>
                <?php if($data['periode_2']): ?>
                    <td class="text-end"><?php echo number_format($data['laporan']['total_pendapatan_2'], 0, ',', '.'); ?></td>
                <?php endif; ?>
            </tr>

            <tr class="bg-section">
                <td colspan="<?php echo $data['periode_2'] ? '3' : '2'; ?>">II. BEBAN OPERASIONAL &amp; USAHA</td>
            </tr>
            <?php foreach($data['laporan']['beban'] as $item): ?>
            <tr>
                <td class="ps-4"><?php echo htmlspecialchars($item['nama_akun']); ?></td>
                <td class="text-end"><?php echo number_format($item['total_1'], 0, ',', '.'); ?></td>
                <?php if($data['periode_2']): ?>
                    <td class="text-end"><?php echo number_format($item['total_2'], 0, ',', '.'); ?></td>
                <?php endif; ?>
            </tr>
            <?php endforeach; ?>
            <tr class="total-row">
                <td>TOTAL BEBAN</td>
                <td class="text-end"><?php echo number_format($data['laporan']['total_beban_1'], 0, ',', '.'); ?></td>
                <?php if($data['periode_2']): ?>
                    <td class="text-end"><?php echo number_format($data['laporan']['total_beban_2'], 0, ',', '.'); ?></td>
                <?php endif; ?>
            </tr>
        </tbody>
        <tfoot>
            <?php 
                $laba1 = $data['laporan']['total_pendapatan_1'] - $data['laporan']['total_beban_1'];
                $laba2 = $data['laporan']['total_pendapatan_2'] - $data['laporan']['total_beban_2'];
            ?>
            <tr class="grand-total">
                <td>LABA (RUGI) BERSIH</td>
                <td class="text-end"><?php echo number_format($laba1, 0, ',', '.'); ?></td>
                <?php if($data['periode_2']): ?>
                    <td class="text-end"><?php echo number_format($laba2, 0, ',', '.'); ?></td>
                <?php endif; ?>
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
