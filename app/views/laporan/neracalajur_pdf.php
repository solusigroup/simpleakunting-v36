<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Neraca Lajur - <?php echo htmlspecialchars($data['perusahaan']['nama_perusahaan'] ?? ''); ?></title>
    <style>
        body { font-family: 'Helvetica', 'Arial', sans-serif; font-size: 7.5pt; color: #1e293b; margin: 0; padding: 0; }
        .header { text-align: center; margin-bottom: 12px; border-bottom: 2px solid #0f172a; padding-bottom: 8px; }
        .header h2 { margin: 0; font-size: 13pt; text-transform: uppercase; color: #0f172a; }
        .header p { margin: 2px 0; font-size: 8pt; color: #475569; }
        .report-title { text-align: center; margin-bottom: 12px; }
        .report-title h3 { margin: 0; font-size: 11pt; text-transform: uppercase; color: #0f172a; }
        .report-title p { margin: 2px 0; font-size: 8pt; color: #64748b; }
        table { width: 100%; border-collapse: collapse; margin-top: 8px; }
        th, td { padding: 3px 4px; border: 1px solid #cbd5e1; font-size: 6.5pt; }
        thead th { background-color: #0f172a; color: #ffffff; text-align: center; font-weight: bold; }
        thead tr.sub-th th { background-color: #334155; color: #ffffff; font-size: 6pt; }
        .text-end { text-align: right; }
        .text-center { text-align: center; }
        .fw-bold { font-weight: bold; }
        tfoot td { background-color: #f1f5f9; font-weight: bold; }
        .footer-table { width: 100%; border: none; margin-top: 25px; page-break-inside: avoid; }
        .footer-table td { border: none; text-align: center; width: 50%; font-size: 8pt; padding: 0; }
    </style>
</head>
<body>
    <div class="header">
        <h2><?php echo htmlspecialchars($data['perusahaan']['nama_perusahaan'] ?? 'Perusahaan'); ?></h2>
        <p><?php echo htmlspecialchars($data['perusahaan']['alamat'] ?? ''); ?></p>
        <p><?php if(!empty($data['perusahaan']['telepon'])) echo "Telp: " . htmlspecialchars($data['perusahaan']['telepon']) . " | "; ?>Email: <?php echo htmlspecialchars($data['perusahaan']['email'] ?? '-'); ?></p>
    </div>

    <div class="report-title">
        <h3>KERTAS KERJA NERACA LAJUR (WORKSHEET)</h3>
        <p>Periode: <?php echo htmlspecialchars($data['periode_1'] ?? ''); ?></p>
    </div>

    <table>
        <thead>
            <tr>
                <th rowspan="2" style="width: 45px;">Kode</th>
                <th rowspan="2" style="text-align: left; padding-left: 6px;">Nama Akun Perkiraan</th>
                <th colspan="2">Saldo Awal</th>
                <th colspan="2">Mutasi Periode</th>
                <th colspan="2">Neraca Saldo</th>
                <th colspan="2">Penyesuaian</th>
                <th colspan="2">NS Disesuaikan</th>
                <th colspan="2">Laba / Rugi</th>
                <th colspan="2">Posisi Keuangan</th>
            </tr>
            <tr class="sub-th">
                <th>Debit</th><th>Kredit</th>
                <th>Debit</th><th>Kredit</th>
                <th>Debit</th><th>Kredit</th>
                <th>Debit</th><th>Kredit</th>
                <th>Debit</th><th>Kredit</th>
                <th>Debit</th><th>Kredit</th>
                <th>Debit</th><th>Kredit</th>
            </tr>
        </thead>
        <tbody>
            <?php
                $totals = array_fill_keys(['sa_debit', 'sa_kredit', 'mutasi_debit', 'mutasi_kredit', 'ns_debit', 'ns_kredit', 'penyesuaian_debit', 'penyesuaian_kredit', 'nsd_debit', 'nsd_kredit', 'lr_debit', 'lr_kredit', 'poskeu_debit', 'poskeu_kredit'], 0);
                if (!empty($data['laporan'])):
                    foreach($data['laporan'] as $row):
                        foreach($totals as $key => &$total) { $total += (float)($row[$key] ?? 0); }
            ?>
            <tr>
                <td class="text-center fw-bold"><?php echo htmlspecialchars($row['kode_akun']); ?></td>
                <td><?php echo htmlspecialchars($row['nama_akun']); ?></td>
                <td class="text-end"><?php echo ($row['sa_debit'] != 0) ? number_format($row['sa_debit'], 0, ',', '.') : '-'; ?></td>
                <td class="text-end"><?php echo ($row['sa_kredit'] != 0) ? number_format($row['sa_kredit'], 0, ',', '.') : '-'; ?></td>
                <td class="text-end"><?php echo ($row['mutasi_debit'] != 0) ? number_format($row['mutasi_debit'], 0, ',', '.') : '-'; ?></td>
                <td class="text-end"><?php echo ($row['mutasi_kredit'] != 0) ? number_format($row['mutasi_kredit'], 0, ',', '.') : '-'; ?></td>
                <td class="text-end"><?php echo ($row['ns_debit'] != 0) ? number_format($row['ns_debit'], 0, ',', '.') : '-'; ?></td>
                <td class="text-end"><?php echo ($row['ns_kredit'] != 0) ? number_format($row['ns_kredit'], 0, ',', '.') : '-'; ?></td>
                <td class="text-end"><?php echo ($row['penyesuaian_debit'] != 0) ? number_format($row['penyesuaian_debit'], 0, ',', '.') : '-'; ?></td>
                <td class="text-end"><?php echo ($row['penyesuaian_kredit'] != 0) ? number_format($row['penyesuaian_kredit'], 0, ',', '.') : '-'; ?></td>
                <td class="text-end"><?php echo ($row['nsd_debit'] != 0) ? number_format($row['nsd_debit'], 0, ',', '.') : '-'; ?></td>
                <td class="text-end"><?php echo ($row['nsd_kredit'] != 0) ? number_format($row['nsd_kredit'], 0, ',', '.') : '-'; ?></td>
                <td class="text-end"><?php echo ($row['lr_debit'] != 0) ? number_format($row['lr_debit'], 0, ',', '.') : '-'; ?></td>
                <td class="text-end"><?php echo ($row['lr_kredit'] != 0) ? number_format($row['lr_kredit'], 0, ',', '.') : '-'; ?></td>
                <td class="text-end"><?php echo ($row['poskeu_debit'] != 0) ? number_format($row['poskeu_debit'], 0, ',', '.') : '-'; ?></td>
                <td class="text-end"><?php echo ($row['poskeu_kredit'] != 0) ? number_format($row['poskeu_kredit'], 0, ',', '.') : '-'; ?></td>
            </tr>
            <?php endforeach; endif; ?>
        </tbody>
        <tfoot>
            <tr>
                <td colspan="2" class="text-center">TOTAL</td>
                <?php foreach($totals as $total): ?>
                <td class="text-end"><?php echo number_format($total, 0, ',', '.'); ?></td>
                <?php endforeach; ?>
            </tr>
            <?php 
                $labaRugi = $totals['lr_kredit'] - $totals['lr_debit'];
            ?>
            <tr style="background-color: #f8fafc;">
                <td colspan="12" class="text-end fw-bold">LABA (RUGI) BERSIH</td>
                <?php if ($labaRugi >= 0): ?>
                    <td class="text-end fw-bold"><?php echo number_format($labaRugi, 0, ',', '.'); ?></td><td>-</td>
                    <td>-</td><td class="text-end fw-bold"><?php echo number_format($labaRugi, 0, ',', '.'); ?></td>
                <?php else: ?>
                    <td>-</td><td class="text-end fw-bold"><?php echo number_format(abs($labaRugi), 0, ',', '.'); ?></td>
                    <td class="text-end fw-bold"><?php echo number_format(abs($labaRugi), 0, ',', '.'); ?></td><td>-</td>
                <?php endif; ?>
            </tr>
            <tr style="background-color: #e2e8f0; font-weight: bold;">
                <td colspan="12" class="text-end">TOTAL SEIMBANG</td>
                <td class="text-end"><?php echo number_format(max($totals['lr_debit'], $totals['lr_kredit']), 0, ',', '.'); ?></td>
                <td class="text-end"><?php echo number_format(max($totals['lr_debit'], $totals['lr_kredit']), 0, ',', '.'); ?></td>
                <td class="text-end"><?php echo number_format(max($totals['poskeu_debit'], $totals['poskeu_kredit']), 0, ',', '.'); ?></td>
                <td class="text-end"><?php echo number_format(max($totals['poskeu_debit'], $totals['poskeu_kredit']), 0, ',', '.'); ?></td>
            </tr>
        </tfoot>
    </table>

    <table class="footer-table">
        <tr>
            <td>
                <p><?php echo htmlspecialchars($data['penandatangan_1']['jabatan'] ?? 'Pimpinan'); ?></p>
                <br><br><br>
                <p class="fw-bold"><u><?php echo htmlspecialchars($data['penandatangan_1']['nama_user'] ?? 'Pimpinan'); ?></u></p>
            </td>
            <td>
                <p><?php echo htmlspecialchars($data['kota_laporan'] ?? 'Mojokerto'); ?>, <?php echo date('d F Y'); ?></p>
                <p><?php echo htmlspecialchars($data['penandatangan_2']['jabatan'] ?? 'Bendahara'); ?></p>
                <br><br><br>
                <p class="fw-bold"><u><?php echo htmlspecialchars($data['penandatangan_2']['nama_user'] ?? 'Bendahara'); ?></u></p>
            </td>
        </tr>
    </table>
</body>
</html>
