<?php $activeTab = 'index'; ?>

<div class="d-flex flex-wrap justify-content-between align-items-center mb-4 gap-3">
    <div>
        <h3 class="fw-bold mb-1"><i class="bi bi-cpu text-primary me-2"></i>Modul Analisa Bisnis</h3>
        <p class="text-muted mb-0">Executive Dashboard & Diagnostik Finansial Komprehensif <?php echo htmlspecialchars($data['perusahaan']['nama_perusahaan'] ?? ''); ?></p>
    </div>
    <div class="d-flex gap-2">
        <a href="<?php echo BASEURL; ?>/analisis/cetakKesehatan?tanggal_mulai=<?php echo $data['tanggal_mulai']; ?>&tanggal_selesai=<?php echo $data['tanggal_selesai']; ?>" target="_blank" class="btn btn-outline-secondary rounded-pill px-3 shadow-sm">
            <i class="bi bi-printer me-1"></i> Cetak Laporan Analisa
        </a>
    </div>
</div>

<!-- Navigasi Tab Fitur Analisa Bisnis -->
<ul class="nav nav-pills nav-fill bg-white p-2 rounded-4 shadow-sm mb-4 border">
    <li class="nav-item">
        <a class="nav-link rounded-pill active fw-bold" href="<?php echo BASEURL; ?>/analisis">
            <i class="bi bi-speedometer2 me-1"></i> Ikhtisar Bisnis
        </a>
    </li>
    <li class="nav-item">
        <a class="nav-link rounded-pill text-dark" href="<?php echo BASEURL; ?>/analisis/anomali">
            <i class="bi bi-exclamation-triangle me-1"></i> Jurnal Anomali
            <?php if (($data['anomali']['total'] ?? 0) > 0): ?>
                <span class="badge bg-danger ms-1"><?php echo $data['anomali']['total']; ?></span>
            <?php endif; ?>
        </a>
    </li>
    <li class="nav-item">
        <a class="nav-link rounded-pill text-dark" href="<?php echo BASEURL; ?>/analisis/tanpaJurnal">
            <i class="bi bi-link-45deg me-1"></i> Transaksi Tanpa Jurnal
            <?php if (($data['tanpa_jurnal']['total'] ?? 0) > 0): ?>
                <span class="badge bg-warning text-dark ms-1"><?php echo $data['tanpa_jurnal']['total']; ?></span>
            <?php endif; ?>
        </a>
    </li>
    <li class="nav-item">
        <a class="nav-link rounded-pill text-dark" href="<?php echo BASEURL; ?>/analisis/rasio">
            <i class="bi bi-calculator me-1"></i> Rasio Keuangan
        </a>
    </li>
    <li class="nav-item">
        <a class="nav-link rounded-pill text-dark" href="<?php echo BASEURL; ?>/analisis/kesehatan">
            <i class="bi bi-heart-pulse me-1"></i> Kesehatan Usaha
        </a>
    </li>
</ul>

<!-- Filter Periode & Dimensi -->
<div class="card border-0 shadow-sm rounded-4 mb-4">
    <div class="card-body p-3">
        <form action="<?php echo BASEURL; ?>/analisis" method="get" class="row g-2 align-items-center">
            <div class="col-md-3">
                <label class="form-label small fw-bold text-muted mb-1">Dari Tanggal</label>
                <input type="date" name="tanggal_mulai" class="form-control form-control-sm rounded-3" value="<?php echo htmlspecialchars($data['tanggal_mulai']); ?>">
            </div>
            <div class="col-md-3">
                <label class="form-label small fw-bold text-muted mb-1">Sampai Tanggal</label>
                <input type="date" name="tanggal_selesai" class="form-control form-control-sm rounded-3" value="<?php echo htmlspecialchars($data['tanggal_selesai']); ?>">
            </div>
            <?php if (!empty($data['units'])): ?>
            <div class="col-md-3">
                <label class="form-label small fw-bold text-muted mb-1">Unit Usaha</label>
                <select name="id_unit" class="form-select form-select-sm rounded-3">
                    <option value="">-- Semua Unit Usaha --</option>
                    <?php foreach ($data['units'] as $u): ?>
                        <option value="<?php echo $u['id']; ?>" <?php echo ($data['id_unit'] == $u['id']) ? 'selected' : ''; ?>>
                            <?php echo htmlspecialchars($u['nama_unit']); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            <?php endif; ?>
            <div class="col-md-<?php echo empty($data['units']) ? '6' : '3'; ?> d-flex align-items-end">
                <button type="submit" class="btn btn-primary btn-sm rounded-pill px-4 w-100 shadow-sm">
                    <i class="bi bi-funnel-fill me-1"></i> Terapkan Analisis
                </button>
            </div>
        </form>
    </div>
</div>

<!-- Section 1: Skor Kesehatan & Diagnostik Cepat -->
<div class="row g-4 mb-4">
    <div class="col-lg-7">
        <div class="card border-0 shadow-sm rounded-4 h-100 bg-gradient text-white" style="background: linear-gradient(135deg, #1e3a8a 0%, #3b82f6 100%);">
            <div class="card-body p-4 d-flex flex-column justify-content-between">
                <div>
                    <div class="d-flex justify-content-between align-items-center mb-3">
                        <span class="badge bg-white text-primary rounded-pill px-3 py-2 fw-bold text-uppercase" style="letter-spacing: 0.5px;">
                            <i class="bi <?php echo $data['kesehatan']['icon']; ?> me-1"></i> Predikat <?php echo $data['kesehatan']['kategori']; ?>
                        </span>
                        <span class="small opacity-75">Periode: <?php echo $data['periode']; ?></span>
                    </div>
                    <div class="d-flex align-items-baseline gap-2 mb-2">
                        <h1 class="display-3 fw-bolder mb-0 text-white"><?php echo $data['kesehatan']['skor_total']; ?></h1>
                        <span class="fs-4 opacity-75">/ 100</span>
                    </div>
                    <h3 class="fw-bold mb-2"><?php echo $data['kesehatan']['predikat']; ?></h3>
                    <p class="opacity-90 mb-4"><?php echo $data['kesehatan']['kesimpulan']; ?></p>
                </div>
                
                <div class="bg-white bg-opacity-10 rounded-3 p-3 mt-auto">
                    <div class="row text-center g-2">
                        <div class="col-3 border-end border-white border-opacity-25">
                            <small class="d-block opacity-75" style="font-size: 0.75rem;">Likuiditas</small>
                            <span class="fw-bold fs-6"><?php echo $data['kesehatan']['pilar']['likuiditas']['skor']; ?></span>
                        </div>
                        <div class="col-3 border-end border-white border-opacity-25">
                            <small class="d-block opacity-75" style="font-size: 0.75rem;">Solvabilitas</small>
                            <span class="fw-bold fs-6"><?php echo $data['kesehatan']['pilar']['solvabilitas']['skor']; ?></span>
                        </div>
                        <div class="col-3 border-end border-white border-opacity-25">
                            <small class="d-block opacity-75" style="font-size: 0.75rem;">Profitabilitas</small>
                            <span class="fw-bold fs-6"><?php echo $data['kesehatan']['pilar']['profitabilitas']['skor']; ?></span>
                        </div>
                        <div class="col-3">
                            <small class="d-block opacity-75" style="font-size: 0.75rem;">Aktivitas</small>
                            <span class="fw-bold fs-6"><?php echo $data['kesehatan']['pilar']['aktivitas']['skor']; ?></span>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Diagnostic Alerts & Integrity Status -->
    <div class="col-lg-5">
        <div class="d-flex flex-column gap-3 h-100">
            <!-- Alert Jurnal Anomali -->
            <div class="card border-0 shadow-sm rounded-4 flex-fill <?php echo ($data['anomali']['total'] > 0) ? 'border-start border-danger border-4' : 'border-start border-success border-4'; ?>">
                <div class="card-body p-3 d-flex align-items-center justify-content-between">
                    <div class="d-flex align-items-center gap-3">
                        <div class="p-3 rounded-circle <?php echo ($data['anomali']['total'] > 0) ? 'bg-danger-subtle text-danger' : 'bg-success-subtle text-success'; ?>">
                            <i class="bi bi-exclamation-triangle fs-4"></i>
                        </div>
                        <div>
                            <h6 class="fw-bold mb-1">Jurnal Anomali</h6>
                            <small class="text-muted d-block">
                                <?php if ($data['anomali']['total'] > 0): ?>
                                    Terdeteksi <strong class="text-danger"><?php echo $data['anomali']['total']; ?> anomali</strong> (<?php echo $data['anomali']['kritis']; ?> kritis)
                                <?php else: ?>
                                    Semua entri jurnal seimbang & valid
                                <?php endif; ?>
                            </small>
                        </div>
                    </div>
                    <a href="<?php echo BASEURL; ?>/analisis/anomali" class="btn btn-outline-danger btn-sm rounded-pill px-3">
                        Periksa
                    </a>
                </div>
            </div>

            <!-- Alert Transaksi Tanpa Jurnal -->
            <div class="card border-0 shadow-sm rounded-4 flex-fill <?php echo ($data['tanpa_jurnal']['total'] > 0) ? 'border-start border-warning border-4' : 'border-start border-success border-4'; ?>">
                <div class="card-body p-3 d-flex align-items-center justify-content-between">
                    <div class="d-flex align-items-center gap-3">
                        <div class="p-3 rounded-circle <?php echo ($data['tanpa_jurnal']['total'] > 0) ? 'bg-warning-subtle text-warning' : 'bg-success-subtle text-success'; ?>">
                            <i class="bi bi-link-45deg fs-4"></i>
                        </div>
                        <div>
                            <h6 class="fw-bold mb-1">Transaksi Tanpa Jurnal</h6>
                            <small class="text-muted d-block">
                                <?php if ($data['tanpa_jurnal']['total'] > 0): ?>
                                    Terdapat <strong class="text-warning"><?php echo $data['tanpa_jurnal']['total']; ?> transaksi</strong> belum terjurnal
                                <?php else: ?>
                                    Seluruh transaksi operasional sinkron
                                <?php endif; ?>
                            </small>
                        </div>
                    </div>
                    <a href="<?php echo BASEURL; ?>/analisis/tanpaJurnal" class="btn btn-outline-warning text-dark btn-sm rounded-pill px-3">
                        Sinkronkan
                    </a>
                </div>
            </div>

            <!-- Altman Z-Score Card -->
            <div class="card border-0 shadow-sm rounded-4 flex-fill bg-light">
                <div class="card-body p-3 d-flex align-items-center justify-content-between">
                    <div>
                        <small class="text-uppercase fw-bold text-muted" style="font-size: 0.7rem;">Model Altman Z-Score (SMEs)</small>
                        <h6 class="fw-bold mb-0 text-<?php echo $data['kesehatan']['altman_z']['color']; ?>">
                            <?php echo $data['kesehatan']['altman_z']['zone']; ?> (Z = <?php echo $data['kesehatan']['altman_z']['score']; ?>)
                        </h6>
                    </div>
                    <a href="<?php echo BASEURL; ?>/analisis/kesehatan" class="btn btn-sm btn-light border rounded-pill px-3">Detail</a>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Section 2: Empat Pilar Rasio Utama -->
<h5 class="fw-bold mb-3"><i class="bi bi-grid-fill text-primary me-2"></i>Indikator Kinerja Keuangan Utama</h5>
<div class="row g-3 mb-4">
    <!-- 1. Rasio Lancar -->
    <div class="col-md-3">
        <div class="card border-0 shadow-sm rounded-4 h-100 p-3">
            <div class="d-flex justify-content-between align-items-start mb-2">
                <span class="text-muted small fw-medium">Rasio Lancar (CR)</span>
                <span class="badge bg-<?php echo ($data['rasio']['likuiditas']['rasio_lancar']['status'] === 'sehat') ? 'success' : (($data['rasio']['likuiditas']['rasio_lancar']['status'] === 'waspada') ? 'warning' : 'danger'); ?> rounded-pill">
                    <?php echo ucfirst($data['rasio']['likuiditas']['rasio_lancar']['status']); ?>
                </span>
            </div>
            <h3 class="fw-bold mb-1 text-dark"><?php echo number_format($data['rasio']['likuiditas']['rasio_lancar']['nilai'], 2); ?> : 1</h3>
            <small class="text-muted" style="font-size: 0.75rem;">Target: <?php echo $data['rasio']['likuiditas']['rasio_lancar']['benchmark']; ?></small>
            <div class="progress mt-2" style="height: 6px;">
                <div class="progress-bar bg-primary" role="progressbar" style="width: <?php echo min(100, $data['rasio']['likuiditas']['rasio_lancar']['nilai'] * 50); ?>%;"></div>
            </div>
        </div>
    </div>

    <!-- 2. Margin Laba Bersih (NPM) -->
    <div class="col-md-3">
        <div class="card border-0 shadow-sm rounded-4 h-100 p-3">
            <div class="d-flex justify-content-between align-items-start mb-2">
                <span class="text-muted small fw-medium">Margin Bersih (NPM)</span>
                <span class="badge bg-<?php echo ($data['rasio']['profitabilitas']['npm']['status'] === 'sehat') ? 'success' : (($data['rasio']['profitabilitas']['npm']['status'] === 'waspada') ? 'warning' : 'danger'); ?> rounded-pill">
                    <?php echo ucfirst($data['rasio']['profitabilitas']['npm']['status']); ?>
                </span>
            </div>
            <h3 class="fw-bold mb-1 text-dark"><?php echo number_format($data['rasio']['profitabilitas']['npm']['nilai'], 2); ?> %</h3>
            <small class="text-muted" style="font-size: 0.75rem;">Target: <?php echo $data['rasio']['profitabilitas']['npm']['benchmark']; ?></small>
            <div class="progress mt-2" style="height: 6px;">
                <div class="progress-bar bg-success" role="progressbar" style="width: <?php echo min(100, max(0, $data['rasio']['profitabilitas']['npm']['nilai'] * 5)); ?>%;"></div>
            </div>
        </div>
    </div>

    <!-- 3. Solvabilitas (DAR) -->
    <div class="col-md-3">
        <div class="card border-0 shadow-sm rounded-4 h-100 p-3">
            <div class="d-flex justify-content-between align-items-start mb-2">
                <span class="text-muted small fw-medium">Rasio Utang (DAR)</span>
                <span class="badge bg-<?php echo ($data['rasio']['solvabilitas']['dar']['status'] === 'sehat') ? 'success' : (($data['rasio']['solvabilitas']['dar']['status'] === 'waspada') ? 'warning' : 'danger'); ?> rounded-pill">
                    <?php echo ucfirst($data['rasio']['solvabilitas']['dar']['status']); ?>
                </span>
            </div>
            <h3 class="fw-bold mb-1 text-dark"><?php echo number_format($data['rasio']['solvabilitas']['dar']['nilai'], 2); ?> %</h3>
            <small class="text-muted" style="font-size: 0.75rem;">Target: <?php echo $data['rasio']['solvabilitas']['dar']['benchmark']; ?></small>
            <div class="progress mt-2" style="height: 6px;">
                <div class="progress-bar bg-info" role="progressbar" style="width: <?php echo min(100, $data['rasio']['solvabilitas']['dar']['nilai']); ?>%;"></div>
            </div>
        </div>
    </div>

    <!-- 4. Efisiensi Biaya (BOPO) -->
    <div class="col-md-3">
        <div class="card border-0 shadow-sm rounded-4 h-100 p-3">
            <div class="d-flex justify-content-between align-items-start mb-2">
                <span class="text-muted small fw-medium">Efisiensi Biaya (BOPO)</span>
                <span class="badge bg-<?php echo ($data['rasio']['aktivitas']['bopo']['status'] === 'sehat') ? 'success' : (($data['rasio']['aktivitas']['bopo']['status'] === 'waspada') ? 'warning' : 'danger'); ?> rounded-pill">
                    <?php echo ucfirst($data['rasio']['aktivitas']['bopo']['status']); ?>
                </span>
            </div>
            <h3 class="fw-bold mb-1 text-dark"><?php echo number_format($data['rasio']['aktivitas']['bopo']['nilai'], 2); ?> %</h3>
            <small class="text-muted" style="font-size: 0.75rem;">Target: <?php echo $data['rasio']['aktivitas']['bopo']['benchmark']; ?></small>
            <div class="progress mt-2" style="height: 6px;">
                <div class="progress-bar bg-warning" role="progressbar" style="width: <?php echo min(100, $data['rasio']['aktivitas']['bopo']['nilai']); ?>%;"></div>
            </div>
        </div>
    </div>
</div>

<!-- Section 3: Ringkasan Nilai Finansial Riil & Rekomendasi Manajerial -->
<div class="row g-4 mb-4">
    <div class="col-lg-6">
        <div class="card border-0 shadow-sm rounded-4 h-100">
            <div class="card-header bg-white border-0 pt-4 px-4 pb-2">
                <h5 class="fw-bold mb-0"><i class="bi bi-wallet2 text-primary me-2"></i>Komposisi Nilai Neraca & Laba Rugi</h5>
            </div>
            <div class="card-body px-4 pb-4">
                <div class="table-responsive">
                    <table class="table table-borderless table-sm mb-0">
                        <tbody>
                            <tr class="border-bottom">
                                <td class="text-muted">Total Pendapatan Usaha</td>
                                <td class="text-end fw-bold text-success">Rp <?php echo number_format($data['rasio']['ringkasan_keuangan']['total_pendapatan'], 2, ',', '.'); ?></td>
                            </tr>
                            <tr class="border-bottom">
                                <td class="text-muted">Total Beban & HPP</td>
                                <td class="text-end fw-bold text-danger">Rp <?php echo number_format($data['rasio']['ringkasan_keuangan']['total_beban'], 2, ',', '.'); ?></td>
                            </tr>
                            <tr class="border-bottom bg-light">
                                <td class="fw-bold">Laba (Rugi) Bersih</td>
                                <td class="text-end fw-bold <?php echo ($data['rasio']['ringkasan_keuangan']['laba_bersih'] >= 0) ? 'text-primary' : 'text-danger'; ?>">
                                    Rp <?php echo number_format($data['rasio']['ringkasan_keuangan']['laba_bersih'], 2, ',', '.'); ?>
                                </td>
                            </tr>
                            <tr class="border-bottom">
                                <td class="text-muted">Total Aset (Aktiva)</td>
                                <td class="text-end fw-bold">Rp <?php echo number_format($data['rasio']['ringkasan_keuangan']['total_aset'], 2, ',', '.'); ?></td>
                            </tr>
                            <tr class="border-bottom">
                                <td class="text-muted">Total Liabilitas (Utang)</td>
                                <td class="text-end fw-bold">Rp <?php echo number_format($data['rasio']['ringkasan_keuangan']['total_liabilitas'], 2, ',', '.'); ?></td>
                            </tr>
                            <tr class="border-bottom">
                                <td class="text-muted">Total Ekuitas (Modal)</td>
                                <td class="text-end fw-bold">Rp <?php echo number_format($data['rasio']['ringkasan_keuangan']['total_ekuitas'], 2, ',', '.'); ?></td>
                            </tr>
                            <tr>
                                <td class="text-muted">Modal Kerja Bersih (NWC)</td>
                                <td class="text-end fw-bold text-primary">Rp <?php echo number_format($data['rasio']['ringkasan_keuangan']['modal_kerja_bersih'], 2, ',', '.'); ?></td>
                            </tr>
                        </tbody>
                    </table>
                </div>
                <div class="mt-3 text-end">
                    <a href="<?php echo BASEURL; ?>/analisis/rasio" class="btn btn-outline-primary btn-sm rounded-pill px-3">Lihat Semua Rasio (14+ Rasio) ➔</a>
                </div>
            </div>
        </div>
    </div>

    <div class="col-lg-6">
        <div class="card border-0 shadow-sm rounded-4 h-100">
            <div class="card-header bg-white border-0 pt-4 px-4 pb-2">
                <h5 class="fw-bold mb-0"><i class="bi bi-lightbulb-fill text-warning me-2"></i>Rekomendasi Tindakan Strategis</h5>
            </div>
            <div class="card-body px-4 pb-4">
                <div class="d-flex flex-column gap-3">
                    <?php foreach ($data['kesehatan']['rekomendasi'] as $idx => $rek): ?>
                        <div class="p-3 rounded-3 bg-light border-start border-<?php echo ($rek['prioritas'] === 'Tinggi') ? 'danger' : (($rek['prioritas'] === 'Sedang') ? 'warning' : 'info'); ?> border-3">
                            <div class="d-flex justify-content-between align-items-center mb-1">
                                <span class="fw-bold text-dark"><?php echo htmlspecialchars($rek['bidang']); ?></span>
                                <span class="badge bg-<?php echo ($rek['prioritas'] === 'Tinggi') ? 'danger' : (($rek['prioritas'] === 'Sedang') ? 'warning text-dark' : 'info'); ?> rounded-pill" style="font-size: 0.65rem;">
                                    Prioritas <?php echo $rek['prioritas']; ?>
                                </span>
                            </div>
                            <small class="text-muted d-block"><?php echo htmlspecialchars($rek['saran']); ?></small>
                        </div>
                    <?php endforeach; ?>
                </div>
                <div class="mt-3 text-end">
                    <a href="<?php echo BASEURL; ?>/analisis/kesehatan" class="btn btn-outline-success btn-sm rounded-pill px-3">Buka Analisis Kesehatan Lengkap ➔</a>
                </div>
            </div>
        </div>
    </div>
</div>
