<?php
$c = $data['perusahaan'] ?? [];
$logoPath = !empty($c['path_logo']) ? BASEURL . '/' . $c['path_logo'] : BASEURL . '/img/logo_jatim.png';
$kota = !empty($data['kota_laporan']) ? $data['kota_laporan'] : ($c['kota_laporan'] ?? 'Mojokerto');
$fiskal = $data['laporan'] ?? [];
$tahunPajak = $data['tahun_pajak'] ?? date('Y');

$labaKomersial = (float)($fiskal['laba_komersial'] ?? 0);
$totalPendapatanKomersial = (float)($fiskal['total_pendapatan_komersial'] ?? 0);
$totalBebanKomersial = (float)($fiskal['total_beban_komersial'] ?? 0);

$rincianPositif = $fiskal['rincian_positif'] ?? [];
$totalPositif = (float)($fiskal['total_positif'] ?? 0);

$rincianNegatif = $fiskal['rincian_negatif'] ?? [];
$totalNegatif = (float)($fiskal['total_negatif'] ?? 0);

$netoFiskal = (float)($fiskal['penghasilan_neto_fiskal'] ?? 0);
$kompensasiRugi = (float)($fiskal['kompensasi_rugi'] ?? 0);
$pkp = (float)($fiskal['pkp'] ?? 0);

$skemaTarif = $fiskal['skema_tarif'] ?? 'pasal_31e';
$omzetBruto = (float)($fiskal['omzet_bruto'] ?? $totalPendapatanKomersial);
$pphTerutang = (float)($fiskal['pph_terutang'] ?? 0);

$rincianKredit = $fiskal['rincian_kredit'] ?? [];
$totalKredit = (float)($fiskal['total_kredit'] ?? 0);
$pphKurangBayar = (float)($fiskal['pph_kurang_bayar'] ?? 0);
$statusKurangBayar = $fiskal['status_kurang_bayar'] ?? 'PPh Terutang';

if (!function_exists('format_rupiah')) {
    function format_rupiah($angka, $desimal = 2) {
        return 'Rp ' . number_format($angka, $desimal, ',', '.');
    }
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Laporan Keuangan Fiskal & Rekonsiliasi SPT 1771-I - <?php echo htmlspecialchars($c['nama_perusahaan'] ?? 'SimpleAkunting'); ?></title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <link rel="stylesheet" href="<?php echo BASEURL; ?>/css/print.css?v=3.6">
</head>
<body class="print-preview-mode">

    <!-- Floating Top Toolbar (Screen Only) -->
    <div class="screen-container no-print">
        <div class="toolbar-print">
            <div class="toolbar-left">
                <a href="<?php echo BASEURL; ?>/laporan/keuanganFiskal" class="btn-toolbar-back">
                    <i class="fas fa-arrow-left"></i>
                    <span>Kembali ke Laporan</span>
                </a>
                <div>
                    <div style="font-weight: 800; font-size: 12px; color: #ffffff; display: flex; align-items: center; gap: 8px;">
                        <span>Laporan Keuangan Fiskal & Rekonsiliasi SPT 1771-I</span>
                        <span class="toolbar-badge">TAHUN PAJAK <?php echo $tahunPajak; ?></span>
                    </div>
                    <div style="font-size: 11px; color: #94a3b8;">Standar Perpajakan DJP &bull; Sistem SimpleAkunting</div>
                </div>
            </div>
            <div class="toolbar-right">
                <button onclick="window.print()" class="btn-toolbar-print">
                    <i class="fas fa-print"></i>
                    <span>Cetak Dokumen / Simpan PDF</span>
                </button>
            </div>
        </div>
    </div>

    <!-- Paper Canvas Sheet -->
    <div class="screen-container">
        <div class="paper-canvas">
            
            <!-- 1. KOP SURAT RESMI -->
            <header class="kop-header">
                <div class="kop-brand">
                    <img src="<?php echo $logoPath; ?>" alt="Logo" class="kop-logo" onerror="this.src='<?php echo BASEURL; ?>/img/icon-512.png'">
                    <div>
                        <h1 class="kop-title"><?php echo htmlspecialchars($c['nama_perusahaan'] ?? 'KLINIK BUMDESA PROVINSI JAWA TIMUR'); ?></h1>
                        <p class="kop-sub"><?php echo htmlspecialchars($c['jenis_usaha'] ?? 'Sistem Informasi Akuntansi & Tata Kelola Keuangan'); ?></p>
                        <p class="kop-desc"><?php echo htmlspecialchars($c['alamat'] ?? 'Jawa Timur, Indonesia'); ?></p>
                        <p class="kop-meta">
                            <?php if(!empty($c['npwp'])): ?><span><strong>NPWP:</strong> <?php echo htmlspecialchars($c['npwp']); ?></span> &bull; <?php endif; ?>
                            <?php if(!empty($c['telepon'])): ?><span><strong>Telp:</strong> <?php echo htmlspecialchars($c['telepon']); ?></span> &bull; <?php endif; ?>
                            <?php if(!empty($c['email'])): ?><span><strong>Email:</strong> <?php echo htmlspecialchars($c['email']); ?></span> &bull; <?php endif; ?>
                            <span><strong>Kota:</strong> <?php echo htmlspecialchars($kota); ?></span>
                        </p>
                    </div>
                </div>
                <div class="kop-badge-box">
                    <span class="kop-badge">FORMULIR 1771-I</span>
                    <div class="kop-date">Dicetak: <?php echo date('d/m/Y H:i'); ?> WIB</div>
                </div>
            </header>

            <div class="kop-divider"></div>
            <div class="kop-divider-sub"></div>

            <!-- 2. JUDUL DOKUMEN -->
            <div class="doc-title-block">
                <h2 class="doc-title">LAPORAN KEUANGAN FISKAL &amp; REKONSILIASI FISKAL</h2>
                <div class="doc-subtitle">LAMPIRAN I SURAT PEMBERITAHUAN (SPT) TAHUNAN PAJAK PENGHASILAN WAJIB PAJAK BADAN</div>
                <div class="doc-period-pill">
                    <i class="fas fa-calendar-alt text-amber-600"></i>
                    <span>TAHUN PAJAK <?php echo $tahunPajak; ?> &bull; PERIODE: <?php echo htmlspecialchars($data['periode_1'] ?? '-'); ?></span>
                </div>
            </div>

            <!-- 3. TABEL REKONSILIASI FISKAL UTAMA -->
            <table class="data-table mb-4">
                <thead>
                    <tr>
                        <th style="width: 8%;">No</th>
                        <th style="width: 62%;">Uraian Rekonsiliasi Fiskal (Standar SPT 1771-I)</th>
                        <th class="text-right" style="width: 30%;">Jumlah (Rupiah)</th>
                    </tr>
                </thead>
                <tbody>
                    <!-- I. Laba Komersial -->
                    <tr class="section-header-row" style="background: #f1f5f9;">
                        <td class="font-bold">I</td>
                        <td class="font-bold text-uppercase">PENGHASILAN NETO KOMERSIAL (LABA SEBELUM PAJAK)</td>
                        <td class="text-right font-mono font-bold" style="font-size: 9.5pt; color: <?php echo $labaKomersial >= 0 ? '#047857' : '#b91c1c'; ?>;">
                            <?php echo number_format($labaKomersial, 0, ',', '.'); ?>
                        </td>
                    </tr>

                    <!-- II. Koreksi Positif -->
                    <tr class="section-header-row">
                        <td class="font-bold">II</td>
                        <td class="font-bold text-uppercase" colspan="2">PENYESUAIAN FISKAL POSITIF (PASAL 9 UU PPH):</td>
                    </tr>
                    <?php $noP = 1; foreach ($rincianPositif as $rp): ?>
                        <tr>
                            <td class="text-center font-mono" style="font-size: 7.5pt; color: #64748b;"><?php echo $noP++; ?></td>
                            <td style="padding-left: 20px; font-size: 8pt;"><?php echo htmlspecialchars($rp['label']); ?></td>
                            <td class="text-right font-mono"><?php echo number_format($rp['nilai'], 0, ',', '.'); ?></td>
                        </tr>
                    <?php endforeach; ?>
                    <tr class="subtotal-row" style="background: #fef2f2;">
                        <td colspan="2" style="text-align: right; font-weight: 700;">TOTAL PENYESUAIAN FISKAL POSITIF (II):</td>
                        <td class="text-right font-mono font-bold" style="color: #b91c1c;">
                            + <?php echo number_format($totalPositif, 0, ',', '.'); ?>
                        </td>
                    </tr>

                    <!-- III. Koreksi Negatif -->
                    <tr class="section-header-row">
                        <td class="font-bold">III</td>
                        <td class="font-bold text-uppercase" colspan="2">PENYESUAIAN FISKAL NEGATIF (PASAL 4 AYAT 2 &amp; AYAT 3 UU PPH):</td>
                    </tr>
                    <?php $noN = 1; foreach ($rincianNegatif as $rn): ?>
                        <tr>
                            <td class="text-center font-mono" style="font-size: 7.5pt; color: #64748b;"><?php echo $noN++; ?></td>
                            <td style="padding-left: 20px; font-size: 8pt;"><?php echo htmlspecialchars($rn['label']); ?></td>
                            <td class="text-right font-mono"><?php echo number_format($rn['nilai'], 0, ',', '.'); ?></td>
                        </tr>
                    <?php endforeach; ?>
                    <tr class="subtotal-row" style="background: #f0fdf4;">
                        <td colspan="2" style="text-align: right; font-weight: 700;">TOTAL PENYESUAIAN FISKAL NEGATIF (III):</td>
                        <td class="text-right font-mono font-bold" style="color: #047857;">
                            - <?php echo number_format($totalNegatif, 0, ',', '.'); ?>
                        </td>
                    </tr>

                    <!-- IV. Penghasilan Neto Fiskal -->
                    <tr class="section-header-row" style="background: #f8fafc; border-top: 2px solid #0f172a;">
                        <td class="font-bold">IV</td>
                        <td class="font-bold text-uppercase">PENGHASILAN NETO FISKAL (I + II - III)</td>
                        <td class="text-right font-mono font-bold" style="font-size: 9.5pt; color: #0369a1;">
                            <?php echo number_format($netoFiskal, 0, ',', '.'); ?>
                        </td>
                    </tr>

                    <!-- V. Kompensasi Rugi -->
                    <tr>
                        <td class="font-bold">V</td>
                        <td>Kompensasi Kerugian Fiskal Tahun Sebelumnya</td>
                        <td class="text-right font-mono"><?php echo number_format($kompensasiRugi, 0, ',', '.'); ?></td>
                    </tr>

                    <!-- VI. PKP -->
                    <tr class="subtotal-row" style="background: #f1f5f9; border-top: 1px solid #cbd5e1; border-bottom: 2px solid #0f172a;">
                        <td class="font-bold">VI</td>
                        <td class="font-bold text-uppercase">PENGHASILAN KENA PAJAK (PKP) [Dibulatkan ke Ribuan]</td>
                        <td class="text-right font-mono font-bold" style="font-size: 10pt; color: #0f172a;">
                            <?php echo format_rupiah($pkp, 0); ?>
                        </td>
                    </tr>

                    <!-- VII. PPh Terutang -->
                    <tr class="section-header-row">
                        <td class="font-bold">VII</td>
                        <td>
                            <div class="font-bold text-uppercase">PAJAK PENGHASILAN (PPH) TERUTANG TAHUN <?php echo $tahunPajak; ?></div>
                            <div style="font-size: 7.5pt; color: #64748b;"><?php echo htmlspecialchars($fiskal['kalkulasi_detail']['keterangan'] ?? ''); ?></div>
                        </td>
                        <td class="text-right font-mono font-bold" style="font-size: 10pt; color: #b91c1c;">
                            <?php echo format_rupiah($pphTerutang, 0); ?>
                        </td>
                    </tr>

                    <!-- VIII. Kredit Pajak -->
                    <tr class="section-header-row">
                        <td class="font-bold">VIII</td>
                        <td class="font-bold text-uppercase" colspan="2">KREDIT PAJAK TAHUN BERJALAN:</td>
                    </tr>
                    <?php foreach ($rincianKredit as $rk): ?>
                        <tr>
                            <td class="text-center font-mono">-</td>
                            <td style="padding-left: 20px; font-size: 8pt;"><?php echo htmlspecialchars($rk['label']); ?></td>
                            <td class="text-right font-mono"><?php echo number_format($rk['nilai'], 0, ',', '.'); ?></td>
                        </tr>
                    <?php endforeach; ?>
                    <tr class="subtotal-row">
                        <td colspan="2" style="text-align: right; font-weight: 700;">TOTAL KREDIT PAJAK (VIII):</td>
                        <td class="text-right font-mono font-bold" style="color: #047857;">
                            - <?php echo number_format($totalKredit, 0, ',', '.'); ?>
                        </td>
                    </tr>
                </tbody>
                <tfoot>
                    <tr class="grand-total-row">
                        <td class="font-bold">IX</td>
                        <td style="font-size: 9.5pt;">STATUS AKHIR: <?php echo strtoupper($statusKurangBayar); ?></td>
                        <td class="text-right font-mono font-bold" style="font-size: 11pt; color: <?php echo $pphKurangBayar > 0 ? '#b91c1c' : '#047857'; ?>;">
                            <?php echo format_rupiah($pphKurangBayar, 0); ?>
                        </td>
                    </tr>
                </tfoot>
            </table>

            <!-- Catatan Tambahan jika ada -->
            <?php if (!empty($fiskal['catatan'])): ?>
                <div style="margin-bottom: 15px; font-size: 8pt; background: #f8fafc; padding: 8px 12px; border: 1px dashed #cbd5e1; border-radius: 4px;">
                    <strong>Catatan Auditor / Tim Pajak:</strong> <?php echo nl2br(htmlspecialchars($fiskal['catatan'])); ?>
                </div>
            <?php endif; ?>

            <!-- 4. PENGESAHAN / TANDA TANGAN -->
            <div class="ttd-container">
                <div class="ttd-city-date">
                    <?php echo htmlspecialchars($kota); ?>, <?php echo date('d F Y'); ?>
                </div>
                <div class="ttd-grid cols-2">
                    <div class="ttd-box">
                        <div class="ttd-title">Disiapkan &amp; Dihitung Oleh (Bendahara / Staf Akuntansi)</div>
                        <div>
                            <div class="ttd-name"><?php echo htmlspecialchars($data['penandatangan_2']['nama_user'] ?? 'Bendahara'); ?></div>
                            <div class="ttd-role"><?php echo htmlspecialchars($data['penandatangan_2']['jabatan'] ?? 'Bendahara / Akuntan'); ?></div>
                        </div>
                    </div>
                    <div class="ttd-box">
                        <div class="ttd-title">Disetujui &amp; Dilaporkan Oleh (Pimpinan / Kuasa WP)</div>
                        <div>
                            <div class="ttd-name"><?php echo htmlspecialchars($data['penandatangan_1']['nama_user'] ?? 'Pimpinan'); ?></div>
                            <div class="ttd-role"><?php echo htmlspecialchars($data['penandatangan_1']['jabatan'] ?? 'Direktur Utama'); ?></div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- 5. FOOTER -->
            <footer class="paper-footer">
                <div>
                    Laporan Keuangan Fiskal &amp; Rekonsiliasi SPT 1771-I sah dan diterbitkan otomatis oleh sistem <strong>SimpleAkunting</strong>.
                </div>
                <div class="font-mono">
                    Halaman 1 / 1
                </div>
            </footer>

        </div>
    </div>

</body>
</html>
