<?php
$c = $data['perusahaan'] ?? [];
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

$totalWithholdingPotong = 0;
$totalWithholdingSetor = 0;
$totalWithholdingSaldo = 0;
foreach ($pphWithholding as $pw) {
    $totalWithholdingPotong += (float)$pw['total_potong'];
    $totalWithholdingSetor += (float)$pw['total_setor'];
    $totalWithholdingSaldo += (float)$pw['saldo_utang'];
}
?>

<div class="row mb-4">
    <div class="col-md-12">
        <div class="d-flex align-items-center justify-content-between flex-wrap gap-2">
            <div>
                <h3 class="fw-bold mb-1"><i class="bi bi-receipt-cutoff text-primary me-2"></i>Laporan Rekapitulasi Pajak</h3>
                <p class="text-muted mb-0">Rekapitulasi PPN Masukan/Keluaran, SPT Masa, Pemotongan PPh, dan PPh Final UMKM (PP 55/2022).</p>
            </div>
            <div>
                <a href="<?php echo BASEURL; ?>/laporan" class="btn btn-outline-secondary rounded-pill px-3">
                    <i class="bi bi-arrow-left me-1"></i> Kembali ke Pusat Laporan
                </a>
            </div>
        </div>
    </div>
</div>

<!-- Card Filter Periode & Unit Usaha -->
<div class="card shadow-sm border-0 mb-4">
    <div class="card-body p-4">
        <form id="laporan-form" action="<?php echo BASEURL; ?>/laporan/pajak" method="post">
            <div class="row g-3 align-items-end">
                <div class="col-md-3">
                    <label for="tanggal_mulai" class="form-label fw-semibold small text-muted">Dari Tanggal</label>
                    <input type="date" name="tanggal_mulai" id="tanggal_mulai" class="form-control" value="<?php echo htmlspecialchars($data['tanggal_mulai'] ?? date('Y-01-01')); ?>">
                </div>
                <div class="col-md-3">
                    <label for="tanggal_selesai" class="form-label fw-semibold small text-muted">Sampai Tanggal</label>
                    <input type="date" name="tanggal_selesai" id="tanggal_selesai" class="form-control" value="<?php echo htmlspecialchars($data['tanggal_selesai'] ?? date('Y-12-31')); ?>">
                </div>
                <div class="col-md-3">
                    <label for="id_unit" class="form-label fw-semibold small text-muted">Unit Usaha</label>
                    <select name="id_unit" id="id_unit" class="form-select">
                        <option value="">-- Semua Unit (Konsolidasi) --</option>
                        <?php if (!empty($data['units'])): ?>
                            <?php foreach($data['units'] as $u): ?>
                                <option value="<?php echo $u['id_unit']; ?>" <?php echo ($data['id_unit'] == $u['id_unit']) ? 'selected' : ''; ?>>
                                    <?php echo htmlspecialchars($u['nama_unit']); ?>
                                </option>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </select>
                </div>
                <div class="col-md-3">
                    <div class="d-flex gap-2">
                        <button type="submit" class="btn btn-primary flex-grow-1">
                            <i class="bi bi-funnel-fill me-1"></i> Filter
                        </button>
                        <button type="button" id="btn-cetak-estetik" class="btn btn-dark" title="Cetak Dokumen Resmi">
                            <i class="bi bi-printer-fill"></i> Cetak
                        </button>
                        <button type="button" id="export-excel" class="btn btn-outline-success" title="Ekspor ke Excel">
                            <i class="bi bi-file-earmark-excel-fill"></i>
                        </button>
                    </div>
                </div>
            </div>

            <!-- Input tersembunyi untuk ekspor dan cetak -->
            <input type="hidden" name="id_unit_export" id="id_unit_export">
            <input type="hidden" name="tanggal_mulai_export" id="tanggal_mulai_export">
            <input type="hidden" name="tanggal_selesai_export" id="tanggal_selesai_export">
        </form>
    </div>
</div>

<!-- Kartu Ringkasan Metrik Pajak -->
<div class="row g-3 mb-4">
    <div class="col-md-6 col-lg-3">
        <div class="card border-0 shadow-sm h-100 bg-primary-subtle text-primary-emphasis">
            <div class="card-body p-3">
                <div class="d-flex align-items-center justify-content-between mb-2">
                    <span class="small fw-semibold text-uppercase">PPN Keluaran</span>
                    <i class="bi bi-arrow-up-right-circle-fill fs-4 text-primary"></i>
                </div>
                <h4 class="fw-bold mb-1">Rp <?php echo number_format($totalPpnK, 0, ',', '.'); ?></h4>
                <div class="small text-muted">DPP: Rp <?php echo number_format($totalDppK, 0, ',', '.'); ?></div>
            </div>
        </div>
    </div>
    <div class="col-md-6 col-lg-3">
        <div class="card border-0 shadow-sm h-100 bg-success-subtle text-success-emphasis">
            <div class="card-body p-3">
                <div class="d-flex align-items-center justify-content-between mb-2">
                    <span class="small fw-semibold text-uppercase">PPN Masukan</span>
                    <i class="bi bi-arrow-down-left-circle-fill fs-4 text-success"></i>
                </div>
                <h4 class="fw-bold mb-1">Rp <?php echo number_format($totalPpnM, 0, ',', '.'); ?></h4>
                <div class="small text-muted">DPP: Rp <?php echo number_format($totalDppM, 0, ',', '.'); ?></div>
            </div>
        </div>
    </div>
    <div class="col-md-6 col-lg-3">
        <div class="card border-0 shadow-sm h-100 <?php echo ($selisihPpn > 0) ? 'bg-danger-subtle text-danger-emphasis' : (($selisihPpn < 0) ? 'bg-info-subtle text-info-emphasis' : 'bg-light'); ?>">
            <div class="card-body p-3">
                <div class="d-flex align-items-center justify-content-between mb-2">
                    <span class="small fw-semibold text-uppercase">SPT Masa PPN</span>
                    <i class="bi bi-cash-stack fs-4"></i>
                </div>
                <h4 class="fw-bold mb-1">Rp <?php echo number_format(abs($selisihPpn), 0, ',', '.'); ?></h4>
                <span class="badge <?php echo ($selisihPpn > 0) ? 'bg-danger' : (($selisihPpn < 0) ? 'bg-info text-dark' : 'bg-secondary'); ?>">
                    <?php echo $statusPpn; ?>
                </span>
            </div>
        </div>
    </div>
    <div class="col-md-6 col-lg-3">
        <div class="card border-0 shadow-sm h-100 bg-warning-subtle text-warning-emphasis">
            <div class="card-body p-3">
                <div class="d-flex align-items-center justify-content-between mb-2">
                    <span class="small fw-semibold text-uppercase">PPh Final UMKM (0.5%)</span>
                    <i class="bi bi-pie-chart-fill fs-4 text-warning"></i>
                </div>
                <h4 class="fw-bold mb-1">Rp <?php echo number_format($umkmFinal['total_pph'] ?? 0, 0, ',', '.'); ?></h4>
                <div class="small text-muted">Omzet: Rp <?php echo number_format($umkmFinal['total_omzet'] ?? 0, 0, ',', '.'); ?></div>
            </div>
        </div>
    </div>
</div>

<!-- Tabs Laporan Pajak -->
<div class="card border-0 shadow-sm mb-4">
    <div class="card-header bg-white border-bottom p-0">
        <ul class="nav nav-tabs card-header-tabs m-0 px-3" id="taxTabs" role="tablist">
            <li class="nav-item" role="presentation">
                <button class="nav-link active py-3 px-4 fw-semibold" id="ppn-tab" data-bs-toggle="tab" data-bs-target="#ppn-pane" type="button" role="tab">
                    <i class="bi bi-file-earmark-ruled me-1"></i> Rekapitulasi PPN & SPT Masa
                </button>
            </li>
            <li class="nav-item" role="presentation">
                <button class="nav-link py-3 px-4 fw-semibold" id="withholding-tab" data-bs-toggle="tab" data-bs-target="#withholding-pane" type="button" role="tab">
                    <i class="bi bi-scissors me-1"></i> Pemotongan/Pemungutan PPh
                </button>
            </li>
            <li class="nav-item" role="presentation">
                <button class="nav-link py-3 px-4 fw-semibold" id="umkm-tab" data-bs-toggle="tab" data-bs-target="#umkm-pane" type="button" role="tab">
                    <i class="bi bi-calculator me-1"></i> PPh Final UMKM (PP 55/2022)
                </button>
            </li>
        </ul>
    </div>
    <div class="card-body p-4">
        <div class="tab-content" id="taxTabsContent">

            <!-- TAB 1: REKAPITULASI PPN & SPT MASA -->
            <div class="tab-pane fade show active" id="ppn-pane" role="tabpanel">
                <!-- Lembar Rekapitulasi SPT Masa -->
                <div class="card border border-primary-subtle bg-light mb-4">
                    <div class="card-body p-4">
                        <div class="d-flex justify-content-between align-items-center mb-3">
                            <h5 class="fw-bold mb-0 text-primary"><i class="bi bi-calculator me-2"></i>Kalkulasi SPT Masa PPN (Formulir 1111)</h5>
                            <span class="badge bg-primary px-3 py-2">Tarif Efektif: <?php echo htmlspecialchars($data['laporan']['config']['persentase_pajak_default'] ?? 11); ?>%</span>
                        </div>
                        <div class="table-responsive">
                            <table class="table table-bordered bg-white mb-0">
                                <thead class="table-light">
                                    <tr>
                                        <th>Uraian Rekapitulasi</th>
                                        <th class="text-end" style="width: 25%;">Dasar Pengenaan Pajak (DPP)</th>
                                        <th class="text-end" style="width: 25%;">Jumlah PPN (Rp)</th>
                                        <th style="width: 20%;">Catatan Fiskal</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <tr>
                                        <td><strong>I. Pajak Keluaran (Penjualan Kena PPN)</strong></td>
                                        <td class="text-end fw-semibold">Rp <?php echo number_format($totalDppK, 2, ',', '.'); ?></td>
                                        <td class="text-end fw-bold text-primary">Rp <?php echo number_format($totalPpnK, 2, ',', '.'); ?></td>
                                        <td><span class="badge bg-secondary">Utang Pajak</span></td>
                                    </tr>
                                    <tr>
                                        <td><strong>II. Pajak Masukan (Dapat Dikreditkan)</strong></td>
                                        <td class="text-end fw-semibold">Rp <?php echo number_format($totalDppM, 2, ',', '.'); ?></td>
                                        <td class="text-end fw-bold text-success">Rp <?php echo number_format($totalPpnM, 2, ',', '.'); ?></td>
                                        <td><span class="badge bg-secondary">Kredit Pajak</span></td>
                                    </tr>
                                    <tr class="table-active">
                                        <td><strong>III. Selisih Kurang / (Lebih) Bayar PPN</strong></td>
                                        <td class="text-muted text-end">-</td>
                                        <td class="text-end fw-bold fs-6 <?php echo ($selisihPpn > 0) ? 'text-danger' : (($selisihPpn < 0) ? 'text-info' : 'text-dark'); ?>">
                                            Rp <?php echo number_format($selisihPpn, 2, ',', '.'); ?>
                                        </td>
                                        <td>
                                            <span class="badge <?php echo ($selisihPpn > 0) ? 'bg-danger' : (($selisihPpn < 0) ? 'bg-info text-dark' : 'bg-success'); ?>">
                                                <?php echo $statusPpn; ?>
                                            </span>
                                        </td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                        <div class="small text-muted mt-2">
                            <i class="bi bi-info-circle me-1"></i> Jika PPN Keluaran > PPN Masukan, selisih wajib disetor ke kas negara paling lambat akhir bulan berikutnya sebelum SPT Masa PPN dilaporkan.
                        </div>
                    </div>
                </div>

                <!-- Sub-tabel PPN Keluaran -->
                <div class="mb-4">
                    <h5 class="fw-bold d-flex align-items-center mb-3">
                        <i class="bi bi-arrow-up-right-circle text-primary me-2"></i> Daftar Faktur / Transaksi PPN Keluaran
                    </h5>
                    <div class="table-responsive">
                        <table class="table table-hover table-striped table-bordered align-middle">
                            <thead class="table-light">
                                <tr>
                                    <th style="width: 5%;">No</th>
                                    <th>Tanggal</th>
                                    <th>No Faktur / Bukti</th>
                                    <th>Pelanggan / Entitas</th>
                                    <th>Keterangan</th>
                                    <th class="text-end">DPP (Rp)</th>
                                    <th class="text-center">Tarif</th>
                                    <th class="text-end">PPN Keluaran (Rp)</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (empty($ppnKeluaran)): ?>
                                    <tr>
                                        <td colspan="8" class="text-center text-muted py-4">
                                            <i class="bi bi-inbox fs-4 d-block mb-1"></i>
                                            Tidak ada transaksi PPN Keluaran pada periode ini.
                                        </td>
                                    </tr>
                                <?php else: ?>
                                    <?php $no = 1; foreach ($ppnKeluaran as $k): ?>
                                        <tr>
                                            <td class="text-center"><?php echo $no++; ?></td>
                                            <td><?php echo date('d/m/Y', strtotime($k['tanggal'])); ?></td>
                                            <td class="fw-semibold"><?php echo htmlspecialchars($k['no_faktur']); ?></td>
                                            <td><?php echo htmlspecialchars($k['entitas']); ?></td>
                                            <td><small class="text-muted"><?php echo htmlspecialchars($k['keterangan'] ?? '-'); ?></small></td>
                                            <td class="text-end"><?php echo number_format($k['dpp'], 2, ',', '.'); ?></td>
                                            <td class="text-center"><span class="badge bg-light text-dark border"><?php echo $k['tarif_persen']; ?>%</span></td>
                                            <td class="text-end fw-bold text-primary"><?php echo number_format($k['nilai_pajak'], 2, ',', '.'); ?></td>
                                        </tr>
                                    <?php endforeach; ?>
                                <?php endif; ?>
                            </tbody>
                            <tfoot class="table-light fw-bold">
                                <tr>
                                    <td colspan="5" class="text-end">TOTAL PPN KELUARAN:</td>
                                    <td class="text-end">Rp <?php echo number_format($totalDppK, 2, ',', '.'); ?></td>
                                    <td></td>
                                    <td class="text-end text-primary">Rp <?php echo number_format($totalPpnK, 2, ',', '.'); ?></td>
                                </tr>
                            </tfoot>
                        </table>
                    </div>
                </div>

                <!-- Sub-tabel PPN Masukan -->
                <div class="mb-2">
                    <h5 class="fw-bold d-flex align-items-center mb-3">
                        <i class="bi bi-arrow-down-left-circle text-success me-2"></i> Daftar Faktur / Transaksi PPN Masukan
                    </h5>
                    <div class="table-responsive">
                        <table class="table table-hover table-striped table-bordered align-middle">
                            <thead class="table-light">
                                <tr>
                                    <th style="width: 5%;">No</th>
                                    <th>Tanggal</th>
                                    <th>No Faktur / Bukti</th>
                                    <th>Pemasok / Rekanan</th>
                                    <th>Keterangan</th>
                                    <th class="text-end">DPP (Rp)</th>
                                    <th class="text-center">Tarif</th>
                                    <th class="text-end">PPN Masukan (Rp)</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (empty($ppnMasukan)): ?>
                                    <tr>
                                        <td colspan="8" class="text-center text-muted py-4">
                                            <i class="bi bi-inbox fs-4 d-block mb-1"></i>
                                            Tidak ada transaksi PPN Masukan pada periode ini.
                                        </td>
                                    </tr>
                                <?php else: ?>
                                    <?php $no = 1; foreach ($ppnMasukan as $m): ?>
                                        <tr>
                                            <td class="text-center"><?php echo $no++; ?></td>
                                            <td><?php echo date('d/m/Y', strtotime($m['tanggal'])); ?></td>
                                            <td class="fw-semibold"><?php echo htmlspecialchars($m['no_faktur']); ?></td>
                                            <td><?php echo htmlspecialchars($m['entitas']); ?></td>
                                            <td><small class="text-muted"><?php echo htmlspecialchars($m['keterangan'] ?? '-'); ?></small></td>
                                            <td class="text-end"><?php echo number_format($m['dpp'], 2, ',', '.'); ?></td>
                                            <td class="text-center"><span class="badge bg-light text-dark border"><?php echo $m['tarif_persen']; ?>%</span></td>
                                            <td class="text-end fw-bold text-success"><?php echo number_format($m['nilai_pajak'], 2, ',', '.'); ?></td>
                                        </tr>
                                    <?php endforeach; ?>
                                <?php endif; ?>
                            </tbody>
                            <tfoot class="table-light fw-bold">
                                <tr>
                                    <td colspan="5" class="text-end">TOTAL PPN MASUKAN:</td>
                                    <td class="text-end">Rp <?php echo number_format($totalDppM, 2, ',', '.'); ?></td>
                                    <td></td>
                                    <td class="text-end text-success">Rp <?php echo number_format($totalPpnM, 2, ',', '.'); ?></td>
                                </tr>
                            </tfoot>
                        </table>
                    </div>
                </div>
            </div>

            <!-- TAB 2: PEMOTONGAN & PEMUNGUTAN PPH (WITHHOLDING TAX) -->
            <div class="tab-pane fade" id="withholding-pane" role="tabpanel">
                <div class="row g-3 mb-4">
                    <?php foreach ($pphWithholding as $wKey => $w): ?>
                        <div class="col-md-4">
                            <div class="card border border-light-subtle shadow-sm h-100">
                                <div class="card-body p-3">
                                    <h6 class="fw-bold mb-2 text-dark"><?php echo htmlspecialchars($w['label']); ?></h6>
                                    <div class="d-flex justify-content-between small text-muted mb-1">
                                        <span>Total Potong/Utang:</span>
                                        <span class="fw-semibold text-danger">Rp <?php echo number_format($w['total_potong'], 0, ',', '.'); ?></span>
                                    </div>
                                    <div class="d-flex justify-content-between small text-muted mb-1">
                                        <span>Total Disetor:</span>
                                        <span class="fw-semibold text-success">Rp <?php echo number_format($w['total_setor'], 0, ',', '.'); ?></span>
                                    </div>
                                    <hr class="my-2">
                                    <div class="d-flex justify-content-between fw-bold">
                                        <span>Saldo Terutang:</span>
                                        <span class="<?php echo ($w['saldo_utang'] > 0) ? 'text-danger' : 'text-dark'; ?>">
                                            Rp <?php echo number_format($w['saldo_utang'], 0, ',', '.'); ?>
                                        </span>
                                    </div>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>

                <h5 class="fw-bold mb-3"><i class="bi bi-list-check me-2 text-primary"></i>Rincian Mutasi Transaksi Pajak Penghasilan (PPh)</h5>
                <div class="table-responsive">
                    <table class="table table-bordered table-striped table-hover align-middle">
                        <thead class="table-light">
                            <tr>
                                <th>Tanggal</th>
                                <th>No Transaksi</th>
                                <th>Akun PPh</th>
                                <th>Deskripsi Transaksi</th>
                                <th class="text-end">Dipotong / Terutang (Rp)</th>
                                <th class="text-end">Disetor ke Kas Negara (Rp)</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php
                            $hasItem = false;
                            foreach ($pphWithholding as $w) {
                                foreach ($w['items'] as $item) {
                                    $hasItem = true;
                                    ?>
                                    <tr>
                                        <td><?php echo date('d/m/Y', strtotime($item['tanggal'])); ?></td>
                                        <td class="fw-semibold"><?php echo htmlspecialchars($item['no_transaksi']); ?></td>
                                        <td>
                                            <span class="badge bg-secondary"><?php echo htmlspecialchars($item['kode_akun']); ?></span>
                                            <span class="small d-block text-muted"><?php echo htmlspecialchars($item['nama_akun']); ?></span>
                                        </td>
                                        <td><small><?php echo htmlspecialchars($item['deskripsi'] ?? '-'); ?></small></td>
                                        <td class="text-end fw-semibold text-danger">Rp <?php echo number_format($item['nilai_potong'], 2, ',', '.'); ?></td>
                                        <td class="text-end fw-semibold text-success">Rp <?php echo number_format($item['nilai_setor'], 2, ',', '.'); ?></td>
                                    </tr>
                                    <?php
                                }
                            }
                            if (!$hasItem):
                            ?>
                                <tr>
                                    <td colspan="6" class="text-center text-muted py-4">
                                        <i class="bi bi-inbox fs-4 d-block mb-1"></i>
                                        Tidak ada mutasi akun PPh pada periode terpilih.
                                    </td>
                                </tr>
                            <?php endif; ?>
                        </tbody>
                        <tfoot class="table-light fw-bold">
                            <tr>
                                <td colspan="4" class="text-end">TOTAL KESELURUHAN PPH:</td>
                                <td class="text-end text-danger">Rp <?php echo number_format($totalWithholdingPotong, 2, ',', '.'); ?></td>
                                <td class="text-end text-success">Rp <?php echo number_format($totalWithholdingSetor, 2, ',', '.'); ?></td>
                            </tr>
                        </tfoot>
                    </table>
                </div>
            </div>

            <!-- TAB 3: PPH FINAL UMKM (PP 55 TAHUN 2022) -->
            <div class="tab-pane fade" id="umkm-pane" role="tabpanel">
                <div class="alert alert-info border-0 shadow-sm d-flex align-items-center mb-4">
                    <i class="bi bi-info-circle-fill fs-3 me-3 text-info"></i>
                    <div>
                        <h6 class="fw-bold mb-1">Skema PPh Final 0,5% Sesuai Peraturan Pemerintah No. 55 Tahun 2022</h6>
                        <div class="small">
                            Wajib Pajak Badan / BUMDesa dengan omzet peredaran bruto tidak melebihi Rp 4,8 Miliar dalam 1 tahun pajak dapat memanfaatkan tarif PPh Final 0,5% dari omzet bruto bulanan. Pembayaran disetor paling lambat <strong>tanggal 15 bulan berikutnya</strong>.
                        </div>
                    </div>
                </div>

                <div class="table-responsive">
                    <table class="table table-bordered table-striped table-hover align-middle">
                        <thead class="table-light">
                            <tr>
                                <th style="width: 5%;">No</th>
                                <th>Masa Pajak</th>
                                <th class="text-end">Peredaran Bruto / Omzet (Rp)</th>
                                <th class="text-end">Akumulasi Omzet YTD (Rp)</th>
                                <th class="text-center">Tarif Pajak</th>
                                <th class="text-end">Estimasi PPh Terutang (Rp)</th>
                                <th class="text-center">Batas Penyetoran</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (empty($umkmFinal['rincian'])): ?>
                                <tr>
                                    <td colspan="7" class="text-center text-muted py-4">
                                        <i class="bi bi-inbox fs-4 d-block mb-1"></i>
                                        Tidak ada catatan pendapatan pada periode ini.
                                    </td>
                                </tr>
                            <?php else: ?>
                                <?php $noU = 1; foreach ($umkmFinal['rincian'] as $uf): ?>
                                    <tr>
                                        <td class="text-center"><?php echo $noU++; ?></td>
                                        <td class="fw-semibold"><?php echo htmlspecialchars($uf['periode']); ?></td>
                                        <td class="text-end">Rp <?php echo number_format($uf['omzet'], 2, ',', '.'); ?></td>
                                        <td class="text-end text-muted">Rp <?php echo number_format($uf['kumulatif'], 2, ',', '.'); ?></td>
                                        <td class="text-center"><span class="badge bg-warning text-dark"><?php echo $uf['tarif']; ?></span></td>
                                        <td class="text-end fw-bold text-danger">Rp <?php echo number_format($uf['pph_terutang'], 2, ',', '.'); ?></td>
                                        <td class="text-center"><span class="badge bg-light text-dark border"><?php echo $uf['jatuh_tempo']; ?></span></td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </tbody>
                        <tfoot class="table-light fw-bold">
                            <tr>
                                <td colspan="2" class="text-end">TOTAL SETAHUN:</td>
                                <td class="text-end text-primary">Rp <?php echo number_format($umkmFinal['total_omzet'] ?? 0, 2, ',', '.'); ?></td>
                                <td></td>
                                <td></td>
                                <td class="text-end text-danger">Rp <?php echo number_format($umkmFinal['total_pph'] ?? 0, 2, ',', '.'); ?></td>
                                <td></td>
                            </tr>
                        </tfoot>
                    </table>
                </div>
            </div>

        </div>

        <!-- Blok Tanda Tangan -->
        <div class="row mt-5 pt-4 border-top" style="page-break-inside: avoid;">
            <div class="col-6 text-center">
                <p class="mb-1 text-muted small"><?php echo htmlspecialchars($data['penandatangan_1']['jabatan'] ?? 'Pimpinan'); ?></p>
                <br><br><br>
                <p class="fw-bold mb-0 text-decoration-underline"><?php echo htmlspecialchars($data['penandatangan_1']['nama_user'] ?? 'Pimpinan Perusahaan'); ?></p>
                <small class="text-muted">NIP/Identitas Penandatangan</small>
            </div>
            <div class="col-6 text-center">
                <p class="mb-1 text-muted small"><?php echo htmlspecialchars($data['kota_laporan'] ?? 'Mojokerto'); ?>, <?php echo date('d F Y'); ?></p>
                <p class="mb-1 text-muted small"><?php echo htmlspecialchars($data['penandatangan_2']['jabatan'] ?? 'Bendahara'); ?></p>
                <br><br>
                <p class="fw-bold mb-0 text-decoration-underline"><?php echo htmlspecialchars($data['penandatangan_2']['nama_user'] ?? 'Bendahara / Akuntan'); ?></p>
                <small class="text-muted">NIP/Identitas Penandatangan</small>
            </div>
        </div>

    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const form = document.getElementById('laporan-form');
    const exportExcelBtn = document.getElementById('export-excel');
    const cetakEstetikBtn = document.getElementById('btn-cetak-estetik');

    function prepareExportData() {
        document.getElementById('id_unit_export').value = document.getElementById('id_unit').value;
        document.getElementById('tanggal_mulai_export').value = document.getElementById('tanggal_mulai').value;
        document.getElementById('tanggal_selesai_export').value = document.getElementById('tanggal_selesai').value;
    }

    if (exportExcelBtn) {
        exportExcelBtn.addEventListener('click', function() {
            prepareExportData();
            form.action = "<?php echo BASEURL; ?>/laporan/eksporPajak";
            form.target = "_self";
            form.submit();
            form.action = "<?php echo BASEURL; ?>/laporan/pajak";
        });
    }

    if (cetakEstetikBtn) {
        cetakEstetikBtn.addEventListener('click', function() {
            prepareExportData();
            form.action = "<?php echo BASEURL; ?>/laporan/cetakPajak";
            form.target = "_blank";
            form.submit();
            form.action = "<?php echo BASEURL; ?>/laporan/pajak";
            form.target = "_self";
        });
    }
});
</script>
