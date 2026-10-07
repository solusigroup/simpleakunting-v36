<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Neraca Saldo - <?php echo htmlspecialchars($data['perusahaan']['nama_perusahaan'] ?? ''); ?></title>
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
        <h3>LAPORAN NERACA SALDO (TRIAL BALANCE)</h3>
        <p>Per Tanggal: <?php echo $data['periode_1']; ?></p>
    </div>

    <table>
        <thead>
            <tr>
                <th style="width: 20%;">Kode Akun</th>
                <th style="width: 44%;">Nama Akun Perkiraan</th>
                <th class="text-end" style="width: 18%;">Debit (Rp)</th>
                <th class="text-end" style="width: 18%;">Kredit (Rp)</th>
            </tr>
        </thead>
        <tbody>
            <?php 
                $totalDebit = 0;
                $totalKredit = 0;
                foreach($data['laporan'] as $row): 
                $deb = (float)($row['debit'] ?? 0);
                $kre = (float)($row['kredit'] ?? 0);
                $totalDebit += $deb;
                $totalKredit += $kre;
            ?>
            <tr>
                <td class="fw-bold"><?php echo htmlspecialchars($row['kode_akun']); ?></td>
                <td><?php echo htmlspecialchars($row['nama_akun']); ?></td>
                <td class="text-end"><?php echo $deb > 0 ? number_format($deb, 0, ',', '.') : '-'; ?></td>
                <td class="text-end"><?php echo $kre > 0 ? number_format($kre, 0, ',', '.') : '-'; ?></td>
            </tr>
            <?php endforeach; ?>
        </tbody>
        <tfoot>
            <tr class="grand-total">
                <td colspan="2" class="text-end">TOTAL KESEIMBANGAN</td>
                <td class="text-end"><?php echo number_format($totalDebit, 0, ',', '.'); ?></td>
                <td class="text-end"><?php echo number_format($totalKredit, 0, ',', '.'); ?></td>
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
