<?php $activeTab = 'rasio'; ?>

<div class="d-flex flex-wrap justify-content-between align-items-center mb-4 gap-3">
    <div>
        <h3 class="fw-bold mb-1"><i class="bi bi-calculator text-primary me-2"></i>Analisis Rasio Keuangan</h3>
        <p class="text-muted mb-0">Evaluasi menyeluruh 14+ indikator rasio likuiditas, solvabilitas, profitabilitas, dan efisiensi operasional.</p>
    </div>
    <div class="d-flex gap-2">
        <a href="<?php echo BASEURL; ?>/analisis/cetakKesehatan?tanggal_mulai=<?php echo $data['tanggal_mulai']; ?>&tanggal_selesai=<?php echo $data['tanggal_selesai']; ?>" target="_blank" class="btn btn-outline-secondary rounded-pill px-3 shadow-sm">
            <i class="bi bi-printer me-1"></i> Cetak Analisis
        </a>
    </div>
</div>

<!-- Navigasi Tab Fitur Analisa Bisnis -->
<ul class="nav nav-pills nav-fill bg-white p-2 rounded-4 shadow-sm mb-4 border">
    <li class="nav-item">
        <a class="nav-link rounded-pill text-dark" href="<?php echo BASEURL; ?>/analisis">
            <i class="bi bi-speedometer2 me-1"></i> Ikhtisar Bisnis
        </a>
    </li>
    <li class="nav-item">
        <a class="nav-link rounded-pill text-dark" href="<?php echo BASEURL; ?>/analisis/anomali">
            <i class="bi bi-exclamation-triangle me-1"></i> Jurnal Anomali
        </a>
    </li>
    <li class="nav-item">
        <a class="nav-link rounded-pill text-dark" href="<?php echo BASEURL; ?>/analisis/tanpaJurnal">
            <i class="bi bi-link-45deg me-1"></i> Transaksi Tanpa Jurnal
        </a>
    </li>
    <li class="nav-item">
        <a class="nav-link rounded-pill active fw-bold bg-primary" href="<?php echo BASEURL; ?>/analisis/rasio">
            <i class="bi bi-calculator me-1"></i> Rasio Keuangan
        </a>
    </li>
    <li class="nav-item">
        <a class="nav-link rounded-pill text-dark" href="<?php echo BASEURL; ?>/analisis/kesehatan">
            <i class="bi bi-heart-pulse me-1"></i> Kesehatan Usaha
        </a>
    </li>
</ul>

<!-- Filter Periode & Unit -->
<div class="card border-0 shadow-sm rounded-4 mb-4">
    <div class="card-body p-3">
        <form action="<?php echo BASEURL; ?>/analisis/rasio" method="get" class="row g-2 align-items-center">
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
                    <i class="bi bi-filter me-1"></i> Hitung Ulang Rasio
                </button>
            </div>
        </form>
    </div>
</div>

<!-- ================= 1. RASIO LIKUIDITAS ================= -->
<div class="d-flex align-items-center mb-3">
    <div class="bg-primary text-white p-2 rounded-circle me-2 d-inline-flex">
        <i class="bi bi-droplet-fill"></i>
    </div>
    <div>
        <h5 class="fw-bold mb-0">1. Rasio Likuiditas (Liquidity Ratios)</h5>
        <small class="text-muted">Kemampuan perusahaan dalam melunasi kewajiban jangka pendek yang segera jatuh tempo.</small>
    </div>
</div>

<div class="row g-3 mb-4">
    <?php foreach ($data['rasio']['likuiditas'] as $key => $item): 
        $badgeClass = ($item['status'] === 'sehat') ? 'success' : (($item['status'] === 'waspada') ? 'warning text-dark' : 'danger');
    ?>
    <div class="col-md-6 col-lg-3">
        <div class="card border-0 shadow-sm rounded-4 h-100 p-3">
            <div class="d-flex justify-content-between align-items-start mb-2">
                <span class="text-muted small fw-bold text-uppercase" style="font-size: 0.75rem;"><?php echo str_replace('_', ' ', $key); ?></span>
                <span class="badge bg-<?php echo $badgeClass; ?> rounded-pill"><?php echo ucfirst($item['status']); ?></span>
            </div>
            <h3 class="fw-bold text-dark mb-1">
                <?php echo ($item['satuan'] === 'Rp') ? 'Rp ' . number_format($item['nilai'], 0, ',', '.') : number_format($item['nilai'], 2) . ' ' . $item['satuan']; ?>
            </h3>
            <small class="text-muted d-block" style="font-size: 0.72rem;"><strong>Standar:</strong> <?php echo $item['benchmark']; ?></small>
            <small class="text-secondary d-block mt-1 fst-italic" style="font-size: 0.7rem;"><?php echo $item['formula']; ?></small>
            <p class="text-muted small mt-2 mb-0" style="font-size: 0.78rem;"><?php echo $item['keterangan']; ?></p>
        </div>
    </div>
    <?php endforeach; ?>
</div>

<!-- ================= 2. RASIO SOLVABILITAS ================= -->
<div class="d-flex align-items-center mb-3">
    <div class="bg-info text-white p-2 rounded-circle me-2 d-inline-flex">
        <i class="bi bi-shield-shaded"></i>
    </div>
    <div>
        <h5 class="fw-bold mb-0">2. Rasio Solvabilitas / Leverage (Solvency Ratios)</h5>
        <small class="text-muted">Kemampuan memenuhi seluruh liabilitas jangka panjang dan evaluasi struktur permodalan.</small>
    </div>
</div>

<div class="row g-3 mb-4">
    <?php foreach ($data['rasio']['solvabilitas'] as $key => $item): 
        $badgeClass = ($item['status'] === 'sehat') ? 'success' : (($item['status'] === 'waspada') ? 'warning text-dark' : 'danger');
    ?>
    <div class="col-md-4">
        <div class="card border-0 shadow-sm rounded-4 h-100 p-3">
            <div class="d-flex justify-content-between align-items-start mb-2">
                <span class="text-muted small fw-bold text-uppercase" style="font-size: 0.75rem;"><?php echo str_replace('_', ' ', $key); ?></span>
                <span class="badge bg-<?php echo $badgeClass; ?> rounded-pill"><?php echo ucfirst($item['status']); ?></span>
            </div>
            <h3 class="fw-bold text-dark mb-1"><?php echo number_format($item['nilai'], 2); ?> <?php echo $item['satuan']; ?></h3>
            <small class="text-muted d-block" style="font-size: 0.72rem;"><strong>Standar:</strong> <?php echo $item['benchmark']; ?></small>
            <small class="text-secondary d-block mt-1 fst-italic" style="font-size: 0.7rem;"><?php echo $item['formula']; ?></small>
            <p class="text-muted small mt-2 mb-0" style="font-size: 0.78rem;"><?php echo $item['keterangan']; ?></p>
        </div>
    </div>
    <?php endforeach; ?>
</div>

<!-- ================= 3. RASIO PROFITABILITAS ================= -->
<div class="d-flex align-items-center mb-3">
    <div class="bg-success text-white p-2 rounded-circle me-2 d-inline-flex">
        <i class="bi bi-graph-up-arrow"></i>
    </div>
    <div>
        <h5 class="fw-bold mb-0">3. Rasio Profitabilitas (Profitability Ratios)</h5>
        <small class="text-muted">Efektivitas usaha menghasilkan laba bersih terhadap penjualan, total aset, dan ekuitas.</small>
    </div>
</div>

<div class="row g-3 mb-4">
    <?php foreach ($data['rasio']['profitabilitas'] as $key => $item): 
        $badgeClass = ($item['status'] === 'sehat') ? 'success' : (($item['status'] === 'waspada') ? 'warning text-dark' : 'danger');
    ?>
    <div class="col-md-4 col-lg">
        <div class="card border-0 shadow-sm rounded-4 h-100 p-3">
            <div class="d-flex justify-content-between align-items-start mb-2">
                <span class="text-muted small fw-bold text-uppercase" style="font-size: 0.72rem;"><?php echo str_replace('_', ' ', $key); ?></span>
                <span class="badge bg-<?php echo $badgeClass; ?> rounded-pill" style="font-size: 0.65rem;"><?php echo ucfirst($item['status']); ?></span>
            </div>
            <h4 class="fw-bold text-dark mb-1"><?php echo number_format($item['nilai'], 2); ?> <?php echo $item['satuan']; ?></h4>
            <small class="text-muted d-block" style="font-size: 0.7rem;"><strong>Standar:</strong> <?php echo $item['benchmark']; ?></small>
            <small class="text-secondary d-block mt-1 fst-italic" style="font-size: 0.68rem;"><?php echo $item['formula']; ?></small>
            <p class="text-muted small mt-2 mb-0" style="font-size: 0.75rem;"><?php echo $item['keterangan']; ?></p>
        </div>
    </div>
    <?php endforeach; ?>
</div>

<!-- ================= 4. RASIO AKTIVITAS & EFISIENSI ================= -->
<div class="d-flex align-items-center mb-3">
    <div class="bg-warning text-dark p-2 rounded-circle me-2 d-inline-flex">
        <i class="bi bi-speedometer"></i>
    </div>
    <div>
        <h5 class="fw-bold mb-0">4. Rasio Efisiensi & Aktivitas (Activity Ratios)</h5>
        <small class="text-muted">Kecepatan perputaran aset dan pengendalian beban terhadap perolehan pendapatan.</small>
    </div>
</div>

<div class="row g-3 mb-4">
    <?php foreach ($data['rasio']['aktivitas'] as $key => $item): 
        $badgeClass = ($item['status'] === 'sehat') ? 'success' : (($item['status'] === 'waspada') ? 'warning text-dark' : 'danger');
    ?>
    <div class="col-md-4">
        <div class="card border-0 shadow-sm rounded-4 h-100 p-3">
            <div class="d-flex justify-content-between align-items-start mb-2">
                <span class="text-muted small fw-bold text-uppercase" style="font-size: 0.75rem;"><?php echo str_replace('_', ' ', $key); ?></span>
                <span class="badge bg-<?php echo $badgeClass; ?> rounded-pill"><?php echo ucfirst($item['status']); ?></span>
            </div>
            <h3 class="fw-bold text-dark mb-1"><?php echo number_format($item['nilai'], 2); ?> <?php echo $item['satuan']; ?></h3>
            <small class="text-muted d-block" style="font-size: 0.72rem;"><strong>Standar:</strong> <?php echo $item['benchmark']; ?></small>
            <small class="text-secondary d-block mt-1 fst-italic" style="font-size: 0.7rem;"><?php echo $item['formula']; ?></small>
            <p class="text-muted small mt-2 mb-0" style="font-size: 0.78rem;"><?php echo $item['keterangan']; ?></p>
        </div>
    </div>
    <?php endforeach; ?>
</div>

<!-- Basis Angka Finansial Riil -->
<div class="card border-0 shadow-sm rounded-4">
    <div class="card-header bg-white border-0 pt-4 px-4 pb-2">
        <h5 class="fw-bold mb-0"><i class="bi bi-journal-check text-primary me-2"></i>Komponen Angka Riil Pembentuk Rasio</h5>
    </div>
    <div class="card-body p-4">
        <div class="table-responsive">
            <table class="table table-bordered table-sm align-middle text-center mb-0">
                <thead class="table-light">
                    <tr>
                        <th>Pendapatan</th>
                        <th>HPP</th>
                        <th>Beban Operasional</th>
                        <th>Laba Bersih</th>
                        <th>Aset Lancar</th>
                        <th>Total Aset</th>
                        <th>Utang Lancar</th>
                        <th>Total Utang</th>
                        <th>Ekuitas (Modal)</th>
                    </tr>
                </thead>
                <tbody>
                    <tr class="fw-bold">
                        <td class="text-success">Rp <?php echo number_format($data['rasio']['ringkasan_keuangan']['total_pendapatan'], 0, ',', '.'); ?></td>
                        <td>Rp <?php echo number_format($data['rasio']['ringkasan_keuangan']['hpp'], 0, ',', '.'); ?></td>
                        <td>Rp <?php echo number_format($data['rasio']['ringkasan_keuangan']['beban_operasional'], 0, ',', '.'); ?></td>
                        <td class="<?php echo ($data['rasio']['ringkasan_keuangan']['laba_bersih'] >= 0) ? 'text-primary' : 'text-danger'; ?>">
                            Rp <?php echo number_format($data['rasio']['ringkasan_keuangan']['laba_bersih'], 0, ',', '.'); ?>
                        </td>
                        <td>Rp <?php echo number_format($data['rasio']['ringkasan_keuangan']['aset_lancar'], 0, ',', '.'); ?></td>
                        <td>Rp <?php echo number_format($data['rasio']['ringkasan_keuangan']['total_aset'], 0, ',', '.'); ?></td>
                        <td>Rp <?php echo number_format($data['rasio']['ringkasan_keuangan']['utang_lancar'], 0, ',', '.'); ?></td>
                        <td>Rp <?php echo number_format($data['rasio']['ringkasan_keuangan']['total_liabilitas'], 0, ',', '.'); ?></td>
                        <td class="text-primary">Rp <?php echo number_format($data['rasio']['ringkasan_keuangan']['total_ekuitas'], 0, ',', '.'); ?></td>
                    </tr>
                </tbody>
            </table>
        </div>
    </div>
</div>
