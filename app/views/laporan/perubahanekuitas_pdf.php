<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Perubahan Ekuitas - <?php echo htmlspecialchars($data['perusahaan']['nama_perusahaan'] ?? ''); ?></title>
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
        <h3>LAPORAN PERUBAHAN EKUITAS</h3>
        <p>Periode: <?php echo $data['periode_1']; ?> <?php if(!empty($data['periode_2'])) echo " vs " . $data['periode_2']; ?></p>
    </div>

    <table>
        <thead>
            <tr>
                <th style="width: 50%;">Keterangan Komponen Modal</th>
                <th class="text-end" style="width: <?php echo !empty($data['periode_2']) ? '25%' : '50%'; ?>;">Periode <?php echo $data['periode_1']; ?> (Rp)</th>
                <?php if(!empty($data['periode_2'])): ?>
                    <th class="text-end" style="width: 25%;">Pembanding <?php echo $data['periode_2']; ?> (Rp)</th>
                <?php endif; ?>
            </tr>
        </thead>
        <tbody>
            <tr>
                <td class="fw-bold">Saldo Modal Awal Periode</td>
                <td class="text-end"><?php echo number_format($data['laporan']['periode_1']['modal_awal'] ?? 0, 0, ',', '.'); ?></td>
                <?php if(!empty($data['periode_2'])): ?>
                    <td class="text-end"><?php echo number_format($data['laporan']['periode_2']['modal_awal'] ?? 0, 0, ',', '.'); ?></td>
                <?php endif; ?>
            </tr>
            <tr>
                <td class="ps-4">Setoran (Penarikan) Modal / Prive</td>
                <td class="text-end"><?php echo number_format($data['laporan']['periode_1']['perubahan_modal_langsung'] ?? 0, 0, ',', '.'); ?></td>
                <?php if(!empty($data['periode_2'])): ?>
                    <td class="text-end"><?php echo number_format($data['laporan']['periode_2']['perubahan_modal_langsung'] ?? 0, 0, ',', '.'); ?></td>
                <?php endif; ?>
            </tr>
            <tr>
                <td class="ps-4">Laba (Rugi) Periode Berjalan</td>
                <td class="text-end"><?php echo number_format($data['laporan']['periode_1']['laba_rugi_periode_berjalan'] ?? 0, 0, ',', '.'); ?></td>
                <?php if(!empty($data['periode_2'])): ?>
                    <td class="text-end"><?php echo number_format($data['laporan']['periode_2']['laba_rugi_periode_berjalan'] ?? 0, 0, ',', '.'); ?></td>
                <?php endif; ?>
            </tr>
        </tbody>
        <tfoot>
            <tr class="grand-total">
                <td>SALDO MODAL AKHIR PERIODE</td>
                <td class="text-end"><?php echo number_format($data['laporan']['periode_1']['modal_akhir'] ?? 0, 0, ',', '.'); ?></td>
                <?php if(!empty($data['periode_2'])): ?>
                    <td class="text-end"><?php echo number_format($data['laporan']['periode_2']['modal_akhir'] ?? 0, 0, ',', '.'); ?></td>
                <?php endif; ?>
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
