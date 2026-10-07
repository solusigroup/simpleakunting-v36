<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Posisi Keuangan - <?php echo htmlspecialchars($data['perusahaan']['nama_perusahaan'] ?? ''); ?></title>
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
        <h3>LAPORAN POSISI KEUANGAN (NERACA)</h3>
        <p>Per Tanggal: <?php echo $data['periode_1']; ?> <?php if($data['periode_2']) echo " vs " . $data['periode_2']; ?></p>
    </div>

    <table>
        <thead>
            <tr>
                <th style="width: 50%;">Pos Akun / Keterangan</th>
                <th class="text-end" style="width: <?php echo $data['periode_2'] ? '25%' : '50%'; ?>;">Per <?php echo $data['periode_1']; ?> (Rp)</th>
                <?php if($data['periode_2']): ?>
                    <th class="text-end" style="width: 25%;">Per <?php echo $data['periode_2']; ?> (Rp)</th>
                <?php endif; ?>
            </tr>
        </thead>
        <tbody>
            <!-- ASET -->
            <tr class="bg-section">
                <td colspan="<?php echo $data['periode_2'] ? '3' : '2'; ?>">I. ASET (AKTIVA)</td>
            </tr>
            <?php foreach($data['laporan']['periode_1']['aset'] as $item): 
                $key2 = array_search($item['kode_akun'], array_column($data['laporan']['periode_2']['aset'] ?? [], 'kode_akun'));
                $total2 = ($key2 !== false) ? $data['laporan']['periode_2']['aset'][$key2]['total'] : 0;
            ?>
            <tr>
                <td class="ps-4"><?php echo htmlspecialchars($item['nama_akun']); ?></td>
                <td class="text-end"><?php echo number_format($item['total'], 0, ',', '.'); ?></td>
                <?php if($data['periode_2']): ?>
                    <td class="text-end"><?php echo number_format($total2, 0, ',', '.'); ?></td>
                <?php endif; ?>
            </tr>
            <?php endforeach; ?>
            <tr class="total-row">
                <td>TOTAL ASET</td>
                <td class="text-end"><?php echo number_format($data['laporan']['periode_1']['total_aset'], 0, ',', '.'); ?></td>
                <?php if($data['periode_2']): ?>
                    <td class="text-end"><?php echo number_format($data['laporan']['periode_2']['total_aset'], 0, ',', '.'); ?></td>
                <?php endif; ?>
            </tr>

            <!-- KEWAJIBAN -->
            <tr class="bg-section">
                <td colspan="<?php echo $data['periode_2'] ? '3' : '2'; ?>">II. KEWAJIBAN (LIABILITAS)</td>
            </tr>
            <?php foreach($data['laporan']['periode_1']['kewajiban'] as $item): 
                $key2 = array_search($item['kode_akun'], array_column($data['laporan']['periode_2']['kewajiban'] ?? [], 'kode_akun'));
                $total2 = ($key2 !== false) ? $data['laporan']['periode_2']['kewajiban'][$key2]['total'] : 0;
            ?>
            <tr>
                <td class="ps-4"><?php echo htmlspecialchars($item['nama_akun']); ?></td>
                <td class="text-end"><?php echo number_format($item['total'], 0, ',', '.'); ?></td>
                <?php if($data['periode_2']): ?>
                    <td class="text-end"><?php echo number_format($total2, 0, ',', '.'); ?></td>
                <?php endif; ?>
            </tr>
            <?php endforeach; ?>
            <tr class="total-row">
                <td>TOTAL KEWAJIBAN</td>
                <td class="text-end"><?php echo number_format($data['laporan']['periode_1']['total_kewajiban'], 0, ',', '.'); ?></td>
                <?php if($data['periode_2']): ?>
                    <td class="text-end"><?php echo number_format($data['laporan']['periode_2']['total_kewajiban'], 0, ',', '.'); ?></td>
                <?php endif; ?>
            </tr>

            <!-- EKUITAS -->
            <tr class="bg-section">
                <td colspan="<?php echo $data['periode_2'] ? '3' : '2'; ?>">III. EKUITAS (MODAL)</td>
            </tr>
            <?php foreach($data['laporan']['periode_1']['modal'] as $item): 
                $key2 = array_search($item['kode_akun'], array_column($data['laporan']['periode_2']['modal'] ?? [], 'kode_akun'));
                $total2 = ($key2 !== false) ? $data['laporan']['periode_2']['modal'][$key2]['total'] : 0;
            ?>
            <tr>
                <td class="ps-4"><?php echo htmlspecialchars($item['nama_akun']); ?></td>
                <td class="text-end"><?php echo number_format($item['total'], 0, ',', '.'); ?></td>
                <?php if($data['periode_2']): ?>
                    <td class="text-end"><?php echo number_format($total2, 0, ',', '.'); ?></td>
                <?php endif; ?>
            </tr>
            <?php endforeach; ?>
            <tr class="total-row">
                <td>TOTAL EKUITAS</td>
                <td class="text-end"><?php echo number_format($data['laporan']['periode_1']['total_modal'], 0, ',', '.'); ?></td>
                <?php if($data['periode_2']): ?>
                    <td class="text-end"><?php echo number_format($data['laporan']['periode_2']['total_modal'], 0, ',', '.'); ?></td>
                <?php endif; ?>
            </tr>
        </tbody>
        <tfoot>
            <tr class="grand-total">
                <td>TOTAL KEWAJIBAN &amp; EKUITAS</td>
                <td class="text-end"><?php echo number_format($data['laporan']['periode_1']['total_kewajiban'] + $data['laporan']['periode_1']['total_modal'], 0, ',', '.'); ?></td>
                <?php if($data['periode_2']): ?>
                    <td class="text-end"><?php echo number_format($data['laporan']['periode_2']['total_kewajiban'] + $data['laporan']['periode_2']['total_modal'], 0, ',', '.'); ?></td>
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
