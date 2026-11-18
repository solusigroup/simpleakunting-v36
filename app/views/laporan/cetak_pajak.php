<?php
$c = $data['perusahaan'] ?? [];
$logoPath = !empty($c['path_logo']) ? BASEURL . '/' . $c['path_logo'] : BASEURL . '/img/logo_jatim.png';
$kota = !empty($data['kota_laporan']) ? $data['kota_laporan'] : ($c['kota_laporan'] ?? 'Mojokerto');
$laporan = $data['laporan'] ?? [];

$rPpn = $laporan['ringkasan_ppn'] ?? [];
$ppnKeluaran = $laporan['ppn_keluaran'] ?? [];
$ppnMasukan = $laporan['ppn_masukan'] ?? [];
$pphWithholding = $laporan['pph_withholding'] ?? [];
$umkmFinal = $laporan['umkm_final'] ?? [];

$totalDppK = (float)($rPpn['total_dpp_keluaran'] ?? 0);
$totalPpnK = (float)($rPpn['total_ppn_keluaran'] ?? 0);
$totalDppM = (float)($rPpn['total_dpp_masukan'] ?? 0);
$totalPpnM = (float)($rPpn['total_ppn_masukan'] ?? 0);
$selisihPpn = (float)($rPpn['saldo_ppn'] ?? 0);
$statusPpn = $rPpn['status_ppn'] ?? 'Nihil';

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
    <title>Laporan Rekapitulasi Pajak - <?php echo htmlspecialchars($c['nama_perusahaan'] ?? 'SimpleAkunting'); ?></title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <link rel="stylesheet" href="<?php echo BASEURL; ?>/css/print.css?v=3.6">
    <style>
        .badge-tax {
            display: inline-block;
            padding: 2px 8px;
            font-size: 8pt;
            font-weight: 700;
            border-radius: 4px;
            text-transform: uppercase;
        }
        .badge-danger-tax { background: #fee2e2; color: #991b1b; }
        .badge-success-tax { background: #dcfce7; color: #166534; }
        .badge-info-tax { background: #e0f2fe; color: #075985; }
    </style>
</head>
<body class="print-preview-mode">

    <!-- Floating Top Toolbar (Screen Only) -->
    <div class="screen-container no-print">
        <div class="toolbar-print">
            <div class="toolbar-left">
                <a href="<?php echo BASEURL; ?>/laporan/pajak" class="btn-toolbar-back">
                    <i class="fas fa-arrow-left"></i>
                    <span>Kembali ke Laporan</span>
                </a>
                <div>
                    <div style="font-weight: 800; font-size: 12px; color: #ffffff; display: flex; align-items: center; gap: 8px;">
                        <span>Laporan Rekapitulasi Pajak Terpadu</span>
                        <span class="toolbar-badge">PPN &bull; PPH &bull; UMKM</span>
                    </div>
                    <div style="font-size: 11px; color: #94a3b8;">Kepatuhan Perpajakan &bull; Sistem SimpleAkunting</div>
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
                    <span class="kop-badge">DOKUMEN FISKAL</span>
                    <div class="kop-date">Dicetak: <?php echo date('d/m/Y H:i'); ?> WIB</div>
                </div>
            </header>

            <div class="kop-divider"></div>
            <div class="kop-divider-sub"></div>

            <!-- 2. JUDUL DOKUMEN -->
            <div class="doc-title-block">
                <h2 class="doc-title">LAPORAN REKAPITULASI PERPAJAKAN</h2>
                <div class="doc-subtitle">REKAPITULASI PPN (SPT MASA), PEMOTONGAN PPH, DAN PPH FINAL UMKM (PP 55/2022)</div>
                <div class="doc-period-pill">
                    <i class="fas fa-calendar-alt text-amber-600"></i>
                    <span>PERIODE: <?php echo htmlspecialchars($data['periode_1'] ?? '-'); ?></span>
                </div>
            </div>

            <!-- 3. BAGIAN I: REKAPITULASI PPN & SPT MASA -->
            <div style="font-weight: 800; font-size: 9pt; margin: 12px 0 6px 0; color: #0f172a; text-transform: uppercase; border-bottom: 2px solid #cbd5e1; padding-bottom: 4px;">
                I. Rekapitulasi Pajak Pertambahan Nilai (SPT Masa PPN)
            </div>

            <table class="data-table mb-3">
                <thead>
                    <tr>
                        <th style="width: 45%;">Uraian Komponen PPN</th>
                        <th class="text-right" style="width: 25%;">Dasar Pengenaan Pajak (DPP)</th>
                        <th class="text-right" style="width: 30%;">Jumlah PPN (Rp)</th>
                    </tr>
                </thead>
                <tbody>
                    <tr>
                        <td class="font-bold">1. Pajak Keluaran (Penjualan Kena PPN)</td>
                        <td class="text-right font-mono"><?php echo number_format($totalDppK, 0, ',', '.'); ?></td>
                        <td class="text-right font-mono font-bold" style="color: #0369a1;"><?php echo number_format($totalPpnK, 0, ',', '.'); ?></td>
                    </tr>
                    <tr>
                        <td class="font-bold">2. Pajak Masukan (Pembelian Dapat Dikreditkan)</td>
                        <td class="text-right font-mono"><?php echo number_format($totalDppM, 0, ',', '.'); ?></td>
                        <td class="text-right font-mono font-bold" style="color: #047857;"><?php echo number_format($totalPpnM, 0, ',', '.'); ?></td>
                    </tr>
                </tbody>
                <tfoot>
                    <tr class="grand-total-row">
                        <td>STATUS AKHIR SPT MASA PPN: <?php echo strtoupper($statusPpn); ?></td>
                        <td class="text-right font-mono font-bold">-</td>
                        <td class="text-right font-mono font-bold" style="font-size: 10.5pt; color: <?php echo ($selisihPpn > 0) ? '#b91c1c' : (($selisihPpn < 0) ? '#0369a1' : '#0f172a'); ?>;">
                            <?php echo format_rupiah($selisihPpn, 0); ?>
                        </td>
                    </tr>
                </tfoot>
            </table>

            <!-- Rincian PPN Keluaran & Masukan Ringkas -->
            <div style="display: flex; gap: 15px; margin-bottom: 12px;">
                <div style="flex: 1;">
                    <div style="font-size: 8pt; font-weight: 700; color: #475569; margin-bottom: 4px;">Faktur Penjualan (Keluaran) Terbanyak:</div>
                    <table class="data-table" style="font-size: 7.5pt;">
                        <thead>
                            <tr>
                                <th>Tanggal</th>
                                <th>No Faktur</th>
                                <th>Entitas</th>
                                <th class="text-right">PPN (Rp)</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (empty($ppnKeluaran)): ?>
                                <tr><td colspan="4" class="text-center" style="color: #94a3b8;">Nihil</td></tr>
                            <?php else: ?>
                                <?php foreach (array_slice($ppnKeluaran, 0, 5) as $pk): ?>
                                    <tr>
                                        <td><?php echo date('d/m/y', strtotime($pk['tanggal'])); ?></td>
                                        <td class="font-mono"><?php echo htmlspecialchars($pk['no_faktur']); ?></td>
                                        <td><?php echo htmlspecialchars($pk['entitas']); ?></td>
                                        <td class="text-right font-mono"><?php echo number_format($pk['nilai_pajak'], 0, ',', '.'); ?></td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>

                <div style="flex: 1;">
                    <div style="font-size: 8pt; font-weight: 700; color: #475569; margin-bottom: 4px;">Faktur Pembelian (Masukan) Terbanyak:</div>
                    <table class="data-table" style="font-size: 7.5pt;">
                        <thead>
                            <tr>
                                <th>Tanggal</th>
                                <th>No Faktur</th>
                                <th>Rekanan</th>
                                <th class="text-right">PPN (Rp)</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (empty($ppnMasukan)): ?>
                                <tr><td colspan="4" class="text-center" style="color: #94a3b8;">Nihil</td></tr>
                            <?php else: ?>
                                <?php foreach (array_slice($ppnMasukan, 0, 5) as $pm): ?>
                                    <tr>
                                        <td><?php echo date('d/m/y', strtotime($pm['tanggal'])); ?></td>
                                        <td class="font-mono"><?php echo htmlspecialchars($pm['no_faktur']); ?></td>
                                        <td><?php echo htmlspecialchars($pm['entitas']); ?></td>
                                        <td class="text-right font-mono"><?php echo number_format($pm['nilai_pajak'], 0, ',', '.'); ?></td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>

            <!-- 4. BAGIAN II: REKAPITULASI PEMOTONGAN PPH -->
            <div style="font-weight: 800; font-size: 9pt; margin: 15px 0 6px 0; color: #0f172a; text-transform: uppercase; border-bottom: 2px solid #cbd5e1; padding-bottom: 4px;">
                II. Rekapitulasi Pemotongan / Pemungutan Pajak Penghasilan (PPh)
            </div>

            <table class="data-table mb-3">
                <thead>
                    <tr>
                        <th style="width: 45%;">Jenis Pajak Penghasilan</th>
                        <th class="text-right" style="width: 20%;">Total Potong (Rp)</th>
                        <th class="text-right" style="width: 20%;">Disetor ke Kas Negara (Rp)</th>
                        <th class="text-right" style="width: 15%;">Saldo Terutang (Rp)</th>
                    </tr>
                </thead>
                <tbody>
                    <?php 
                    $totPphPotong = 0; $totPphSetor = 0; $totPphSaldo = 0;
                    foreach ($pphWithholding as $pw): 
                        $totPphPotong += $pw['total_potong'];
                        $totPphSetor += $pw['total_setor'];
                        $totPphSaldo += $pw['saldo_utang'];
                    ?>
                        <tr>
                            <td><?php echo htmlspecialchars($pw['label']); ?></td>
                            <td class="text-right font-mono"><?php echo number_format($pw['total_potong'], 0, ',', '.'); ?></td>
                            <td class="text-right font-mono"><?php echo number_format($pw['total_setor'], 0, ',', '.'); ?></td>
                            <td class="text-right font-mono font-bold" style="color: <?php echo $pw['saldo_utang'] > 0 ? '#b91c1c' : '#047857'; ?>;">
                                <?php echo number_format($pw['saldo_utang'], 0, ',', '.'); ?>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
                <tfoot>
                    <tr class="subtotal-row">
                        <td>TOTAL PPH WITHHOLDING:</td>
                        <td class="text-right font-mono font-bold"><?php echo number_format($totPphPotong, 0, ',', '.'); ?></td>
                        <td class="text-right font-mono font-bold"><?php echo number_format($totPphSetor, 0, ',', '.'); ?></td>
                        <td class="text-right font-mono font-bold" style="color: #b91c1c;"><?php echo number_format($totPphSaldo, 0, ',', '.'); ?></td>
                    </tr>
                </tfoot>
            </table>

            <!-- 5. BAGIAN III: REKAPITULASI OMZET & PPH FINAL UMKM (PP 55/2022) -->
            <div style="font-weight: 800; font-size: 9pt; margin: 15px 0 6px 0; color: #0f172a; text-transform: uppercase; border-bottom: 2px solid #cbd5e1; padding-bottom: 4px;">
                III. Rekapitulasi Omzet &amp; Estimasi PPh Final UMKM 0,5% (PP 55 Tahun 2022)
            </div>

            <table class="data-table mb-3">
                <thead>
                    <tr>
                        <th style="width: 25%;">Masa Pajak</th>
                        <th class="text-right" style="width: 25%;">Peredaran Bruto / Omzet (Rp)</th>
                        <th class="text-center" style="width: 15%;">Tarif</th>
                        <th class="text-right" style="width: 20%;">Estimasi PPh Terutang (Rp)</th>
                        <th class="text-center" style="width: 15%;">Jatuh Tempo</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($umkmFinal['rincian'])): ?>
                        <tr><td colspan="5" class="text-center" style="color: #94a3b8;">Tidak ada mutasi pendapatan</td></tr>
                    <?php else: ?>
                        <?php foreach ($umkmFinal['rincian'] as $uf): ?>
                            <tr>
                                <td><?php echo htmlspecialchars($uf['periode']); ?></td>
                                <td class="text-right font-mono"><?php echo number_format($uf['omzet'], 0, ',', '.'); ?></td>
                                <td class="text-center font-bold"><?php echo $uf['tarif']; ?></td>
                                <td class="text-right font-mono font-bold" style="color: #b91c1c;"><?php echo number_format($uf['pph_terutang'], 0, ',', '.'); ?></td>
                                <td class="text-center" style="font-size: 7.5pt; color: #64748b;"><?php echo $uf['jatuh_tempo']; ?></td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
                <tfoot>
                    <tr class="grand-total-row">
                        <td>TOTAL OMZET &amp; ESTIMASI PPH FINAL (0,5%):</td>
                        <td class="text-right font-mono font-bold"><?php echo format_rupiah($umkmFinal['total_omzet'] ?? 0, 0); ?></td>
                        <td></td>
                        <td class="text-right font-mono font-bold" style="color: #b91c1c;"><?php echo format_rupiah($umkmFinal['total_pph'] ?? 0, 0); ?></td>
                        <td></td>
                    </tr>
                </tfoot>
            </table>

            <!-- 6. PENGESAHAN / TANDA TANGAN -->
            <div class="ttd-container">
                <div class="ttd-city-date">
                    <?php echo htmlspecialchars($kota); ?>, <?php echo date('d F Y'); ?>
                </div>
                <div class="ttd-grid cols-2">
                    <div class="ttd-box">
                        <div class="ttd-title">Disiapkan Oleh (Bendahara / Staf Pajak)</div>
                        <div>
                            <div class="ttd-name"><?php echo htmlspecialchars($data['penandatangan_2']['nama_user'] ?? 'Bendahara'); ?></div>
                            <div class="ttd-role"><?php echo htmlspecialchars($data['penandatangan_2']['jabatan'] ?? 'Bendahara / Akuntan'); ?></div>
                        </div>
                    </div>
                    <div class="ttd-box">
                        <div class="ttd-title">Disetujui Oleh (Pimpinan / Direktur)</div>
                        <div>
                            <div class="ttd-name"><?php echo htmlspecialchars($data['penandatangan_1']['nama_user'] ?? 'Pimpinan'); ?></div>
                            <div class="ttd-role"><?php echo htmlspecialchars($data['penandatangan_1']['jabatan'] ?? 'Direktur Utama'); ?></div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- 7. FOOTER -->
            <footer class="paper-footer">
                <div>
                    Dokumen Rekapitulasi Perpajakan diterbitkan secara otomatis dan valid oleh sistem <strong>SimpleAkunting</strong>.
                </div>
                <div class="font-mono">
                    Halaman 1 / 1
                </div>
            </footer>

        </div>
    </div>

</body>
</html>
