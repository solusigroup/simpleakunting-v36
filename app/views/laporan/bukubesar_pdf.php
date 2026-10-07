<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Buku Besar - <?php echo htmlspecialchars($data['perusahaan']['nama_perusahaan'] ?? ''); ?></title>
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
        td { border: 1px solid #cbd5e1; padding: 5px 8px; font-size: 8pt; vertical-align: top; }
        .text-end { text-align: right; }
        .text-center { text-align: center; }
        .fw-bold { font-weight: bold; }
        .grand-total { background-color: #0f172a; color: #ffffff; font-weight: bold; font-size: 8.5pt; }
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
        <h3>LAPORAN BUKU BESAR (GENERAL LEDGER)</h3>
        <p>Akun: <strong>[<?php echo htmlspecialchars($data['kode_akun_terpilih'] ?? '-'); ?>] <?php echo htmlspecialchars($data['nama_akun_terpilih'] ?? ''); ?></strong> &bull; Saldo Normal: <?php echo strtoupper($data['laporan']['posisi_saldo_normal'] ?? 'Debit'); ?><br>
        Periode: <?php echo htmlspecialchars($data['periode_1'] ?? ''); ?></p>
    </div>

    <table>
        <thead>
            <tr>
                <th style="width: 12%;">Tanggal</th>
                <th style="width: 15%;">No. Bukti</th>
                <th style="width: 37%;">Keterangan</th>
                <th style="width: 12%;" class="text-end">Debit (Rp)</th>
                <th style="width: 12%;" class="text-end">Kredit (Rp)</th>
                <th style="width: 12%;" class="text-end">Saldo (Rp)</th>
            </tr>
        </thead>
        <tbody>
            <tr style="background-color: #f8fafc; font-weight: bold;">
                <td><?php echo !empty($data['tanggal_mulai']) ? date('d/m/Y', strtotime($data['tanggal_mulai'])) : '-'; ?></td>
                <td>-</td>
                <td>SALDO AWAL PERIODE</td>
                <td class="text-end">-</td>
                <td class="text-end">-</td>
                <td class="text-end"><?php echo number_format($data['laporan']['saldo_awal_periode'] ?? 0, 0, ',', '.'); ?></td>
            </tr>
            <?php 
                $saldo = (float)($data['laporan']['saldo_awal_periode'] ?? 0);
                $totDeb = 0;
                $totKre = 0;
                foreach ($data['laporan']['transaksi'] as $trx): 
                    $d = (float)($trx['debit'] ?? 0);
                    $k = (float)($trx['kredit'] ?? 0);
                    $totDeb += $d;
                    $totKre += $k;
                    if (($data['laporan']['posisi_saldo_normal'] ?? 'Debit') == 'Debit') {
                        $saldo += ($d - $k);
                    } else {
                        $saldo += ($k - $d);
                    }
            ?>
            <tr>
                <td><?php echo date('d/m/Y', strtotime($trx['tanggal'])); ?></td>
                <td class="fw-bold"><?php echo htmlspecialchars($trx['no_transaksi']); ?></td>
                <td><?php echo htmlspecialchars($trx['deskripsi']); ?></td>
                <td class="text-end"><?php echo $d > 0 ? number_format($d, 0, ',', '.') : '-'; ?></td>
                <td class="text-end"><?php echo $k > 0 ? number_format($k, 0, ',', '.') : '-'; ?></td>
                <td class="text-end fw-bold"><?php echo number_format($saldo, 0, ',', '.'); ?></td>
            </tr>
            <?php endforeach; ?>
            <tr style="background-color: #f1f5f9; font-weight: bold;">
                <td colspan="3" class="text-end">TOTAL MUTASI</td>
                <td class="text-end"><?php echo number_format($totDeb, 0, ',', '.'); ?></td>
                <td class="text-end"><?php echo number_format($totKre, 0, ',', '.'); ?></td>
                <td class="text-end">-</td>
            </tr>
        </tbody>
        <tfoot>
            <tr class="grand-total">
                <td colspan="5" class="text-end">SALDO AKHIR PERIODE (<?php echo strtoupper($data['laporan']['posisi_saldo_normal'] ?? 'DEBIT'); ?>)</td>
                <td class="text-end"><?php echo number_format($saldo, 0, ',', '.'); ?></td>
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
