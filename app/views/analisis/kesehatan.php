<?php $activeTab = 'kesehatan'; ?>

<div class="d-flex flex-wrap justify-content-between align-items-center mb-4 gap-3">
    <div>
        <h3 class="fw-bold mb-1"><i class="bi bi-heart-pulse text-danger me-2"></i>Kesehatan Usaha & Diagnostik Finansial</h3>
        <p class="text-muted mb-0">Penilaian kesehatan komposit berbasis standar BUMDes/UMKM, model Altman Z-Score, dan rekomendasi manajerial.</p>
    </div>
    <div class="d-flex gap-2">
        <a href="<?php echo BASEURL; ?>/analisis/cetakKesehatan?tanggal_mulai=<?php echo $data['tanggal_mulai']; ?>&tanggal_selesai=<?php echo $data['tanggal_selesai']; ?>" target="_blank" class="btn btn-outline-secondary rounded-pill px-3 shadow-sm">
            <i class="bi bi-printer me-1"></i> Cetak Hasil Diagnosa
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
        <a class="nav-link rounded-pill text-dark" href="<?php echo BASEURL; ?>/analisis/rasio">
            <i class="bi bi-calculator me-1"></i> Rasio Keuangan
        </a>
    </li>
    <li class="nav-item">
        <a class="nav-link rounded-pill active fw-bold bg-danger text-white" href="<?php echo BASEURL; ?>/analisis/kesehatan">
            <i class="bi bi-heart-pulse me-1"></i> Kesehatan Usaha
        </a>
    </li>
</ul>

<!-- Filter Periode & Unit -->
<div class="card border-0 shadow-sm rounded-4 mb-4">
    <div class="card-body p-3">
        <form action="<?php echo BASEURL; ?>/analisis/kesehatan" method="get" class="row g-2 align-items-center">
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
                <button type="submit" class="btn btn-danger btn-sm rounded-pill px-4 w-100 shadow-sm">
                    <i class="bi bi-heart-pulse me-1"></i> Analisis Kesehatan
                </button>
            </div>
        </form>
    </div>
</div>

<!-- Header Skor & Visual Radar -->
<div class="row g-4 mb-4">
    <div class="col-lg-7">
        <div class="card border-0 shadow-sm rounded-4 h-100 p-4">
            <div class="d-flex justify-content-between align-items-center mb-3">
                <div>
                    <span class="badge bg-<?php echo $data['kesehatan']['badge_color']; ?>-subtle text-<?php echo $data['kesehatan']['badge_color']; ?> border border-<?php echo $data['kesehatan']['badge_color']; ?>-subtle rounded-pill px-3 py-1.5 fw-bold text-uppercase">
                        Kategori <?php echo $data['kesehatan']['kategori']; ?>
                    </span>
                </div>
                <small class="text-muted">Periode: <?php echo $data['periode']; ?></small>
            </div>

            <div class="row align-items-center mb-4">
                <div class="col-md-5 text-center text-md-start">
                    <div class="d-flex align-items-baseline justify-content-center justify-content-md-start">
                        <h1 class="display-2 fw-bolder mb-0 text-<?php echo $data['kesehatan']['badge_color']; ?>">
                            <?php echo $data['kesehatan']['skor_total']; ?>
                        </h1>
                        <span class="fs-4 text-muted ms-1">/100</span>
                    </div>
                    <h3 class="fw-bold mb-1 text-dark"><?php echo $data['kesehatan']['predikat']; ?></h3>
                </div>
                <div class="col-md-7 border-start-md ps-md-4">
                    <p class="text-secondary mb-0"><?php echo $data['kesehatan']['kesimpulan']; ?></p>
                </div>
            </div>

            <hr class="opacity-25">

            <!-- Progress 4 Pilar -->
            <h6 class="fw-bold mb-3 text-muted text-uppercase" style="font-size: 0.75rem;">Skor Berdasarkan 4 Pilar Utama</h6>
            <div class="row g-3">
                <?php foreach ($data['kesehatan']['pilar'] as $p): ?>
                <div class="col-6">
                    <div class="p-2.5 rounded-3 bg-light">
                        <div class="d-flex justify-content-between align-items-center mb-1">
                            <span class="small fw-bold text-dark"><?php echo $p['nama']; ?></span>
                            <span class="small fw-bold text-primary"><?php echo $p['skor']; ?>/100</span>
                        </div>
                        <div class="progress" style="height: 6px;">
                            <div class="progress-bar bg-<?php echo ($p['skor'] >= 70) ? 'success' : (($p['skor'] >= 50) ? 'warning' : 'danger'); ?>" style="width: <?php echo $p['skor']; ?>%;"></div>
                        </div>
                        <small class="text-muted" style="font-size: 0.68rem;">Bobot pilar: <?php echo $p['bobot']; ?>%</small>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
        </div>
    </div>

    <!-- Radar Chart Visualizer -->
    <div class="col-lg-5">
        <div class="card border-0 shadow-sm rounded-4 h-100 p-4 d-flex flex-column justify-content-between">
            <h6 class="fw-bold mb-3"><i class="bi bi-radar text-primary me-2"></i>Radar Keseimbangan Kinerja</h6>
            <div style="position: relative; height: 260px;">
                <canvas id="healthRadarChart"></canvas>
            </div>
            <small class="text-muted text-center mt-2" style="font-size: 0.72rem;">
                Grafik merefleksikan kekuatan relatif pada tiap pilar kesehatan bisnis.
            </small>
        </div>
    </div>
</div>

<!-- Section Altman Z-Score & Distress Assessment -->
<div class="card border-0 shadow-sm rounded-4 mb-4">
    <div class="card-header bg-white border-0 pt-4 px-4 pb-2">
        <div class="d-flex justify-content-between align-items-center">
            <h5 class="fw-bold mb-0"><i class="bi bi-shield-lock-fill text-primary me-2"></i>Model Prediksi Kebangkrutan (Altman Z''-Score)</h5>
            <span class="badge bg-<?php echo $data['kesehatan']['altman_z']['color']; ?> rounded-pill px-3 py-1.5">
                <?php echo $data['kesehatan']['altman_z']['zone']; ?>
            </span>
        </div>
    </div>
    <div class="card-body p-4">
        <div class="row align-items-center">
            <div class="col-md-4 text-center border-end">
                <small class="text-muted text-uppercase fw-bold" style="font-size: 0.75rem;">Nilai Z-Score Terhitung</small>
                <h1 class="display-4 fw-bolder mb-1 text-<?php echo $data['kesehatan']['altman_z']['color']; ?>">
                    <?php echo $data['kesehatan']['altman_z']['score']; ?>
                </h1>
                <p class="small text-muted mb-0"><?php echo $data['kesehatan']['altman_z']['description']; ?></p>
            </div>
            <div class="col-md-8 ps-md-4 mt-3 mt-md-0">
                <h6 class="fw-bold mb-2">Formula Standar Pasar Berkembang (Emerging Markets & Non-Manufacture):</h6>
                <code class="d-block p-2 bg-light rounded-3 mb-3 text-dark">
                    Z'' = 6.56(X1) + 3.26(X2) + 6.72(X3) + 1.05(X4)
                </code>
                <div class="row g-2 small text-secondary">
                    <div class="col-6"><strong>X1 (Modal Kerja / Aset):</strong> <?php echo number_format($data['kesehatan']['altman_z']['components']['x1'], 3); ?></div>
                    <div class="col-6"><strong>X2 (Laba Bersih / Aset):</strong> <?php echo number_format($data['kesehatan']['altman_z']['components']['x2'], 3); ?></div>
                    <div class="col-6"><strong>X3 (EBIT / Aset):</strong> <?php echo number_format($data['kesehatan']['altman_z']['components']['x3'], 3); ?></div>
                    <div class="col-6"><strong>X4 (Ekuitas / Liabilitas):</strong> <?php echo number_format($data['kesehatan']['altman_z']['components']['x4'], 3); ?></div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Section Analisis SWOT Finansial & Rekomendasi Manajerial -->
<div class="row g-4 mb-4">
    <!-- Kekuatan (Strengths) -->
    <div class="col-md-6">
        <div class="card border-0 shadow-sm rounded-4 h-100 p-4 border-top border-success border-4">
            <h5 class="fw-bold text-success mb-3"><i class="bi bi-check-circle-fill me-2"></i>Kekuatan Finansial (Strengths)</h5>
            <?php if (empty($data['kesehatan']['swot']['kekuatan'])): ?>
                <p class="text-muted small">Belum ada indikator yang mencapai level sangat aman pada periode ini.</p>
            <?php else: ?>
                <ul class="list-unstyled mb-0 d-flex flex-column gap-2">
                    <?php foreach ($data['kesehatan']['swot']['kekuatan'] as $str): ?>
                        <li class="d-flex align-items-start gap-2 small text-secondary">
                            <i class="bi bi-check-lg text-success mt-0.5"></i>
                            <span><?php echo htmlspecialchars($str); ?></span>
                        </li>
                    <?php endforeach; ?>
                </ul>
            <?php endif; ?>
        </div>
    </div>

    <!-- Kelemahan & Titik Rawan (Weaknesses) -->
    <div class="col-md-6">
        <div class="card border-0 shadow-sm rounded-4 h-100 p-4 border-top border-danger border-4">
            <h5 class="fw-bold text-danger mb-3"><i class="bi bi-exclamation-octagon-fill me-2"></i>Kelemahan & Titik Risiko (Risks)</h5>
            <?php if (empty($data['kesehatan']['swot']['kelemahan'])): ?>
                <p class="text-muted small">Tidak terdeteksi titik kelemahan finansial kritis pada periode ini.</p>
            <?php else: ?>
                <ul class="list-unstyled mb-0 d-flex flex-column gap-2">
                    <?php foreach ($data['kesehatan']['swot']['kelemahan'] as $wk): ?>
                        <li class="d-flex align-items-start gap-2 small text-secondary">
                            <i class="bi bi-x-circle-fill text-danger mt-0.5"></i>
                            <span><?php echo htmlspecialchars($wk); ?></span>
                        </li>
                    <?php endforeach; ?>
                </ul>
            <?php endif; ?>
        </div>
    </div>
</div>

<!-- Tabel Rekomendasi Manajerial -->
<div class="card border-0 shadow-sm rounded-4">
    <div class="card-header bg-white border-0 pt-4 px-4 pb-2">
        <h5 class="fw-bold mb-0"><i class="bi bi-clipboard2-check-fill text-primary me-2"></i>Rencana Aksi Manajerial Direkomendasikan</h5>
    </div>
    <div class="card-body p-4">
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
                <thead class="table-light">
                    <tr>
                        <th style="width: 120px;">Prioritas</th>
                        <th style="width: 220px;">Bidang Pengelolaan</th>
                        <th>Arahan Tindakan Strategis</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($data['kesehatan']['rekomendasi'] as $rek): ?>
                        <tr>
                            <td>
                                <span class="badge bg-<?php echo ($rek['prioritas'] === 'Tinggi') ? 'danger' : (($rek['prioritas'] === 'Sedang') ? 'warning text-dark' : 'info'); ?> rounded-pill px-3">
                                    <?php echo $rek['prioritas']; ?>
                                </span>
                            </td>
                            <td><strong><?php echo htmlspecialchars($rek['bidang']); ?></strong></td>
                            <td class="text-secondary small"><?php echo htmlspecialchars($rek['saran']); ?></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<!-- Chart.js Radar Initialization -->
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
document.addEventListener('DOMContentLoaded', function() {
    const ctx = document.getElementById('healthRadarChart');
    if (ctx) {
        new Chart(ctx, {
            type: 'radar',
            data: {
                labels: <?php echo $data['radar_labels']; ?>,
                datasets: [{
                    label: 'Skor Kesehatan (%)',
                    data: <?php echo $data['radar_scores']; ?>,
                    backgroundColor: 'rgba(239, 68, 68, 0.2)',
                    borderColor: 'rgba(239, 68, 68, 0.8)',
                    pointBackgroundColor: '#ef4444',
                    pointBorderColor: '#fff',
                    pointHoverBackgroundColor: '#fff',
                    pointHoverBorderColor: '#ef4444'
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                scales: {
                    r: {
                        beginAtZero: true,
                        max: 100,
                        ticks: {
                            stepSize: 20,
                            display: false
                        },
                        pointLabels: {
                            font: {
                                size: 11,
                                weight: 'bold'
                            }
                        }
                    }
                },
                plugins: {
                    legend: {
                        display: false
                    }
                }
            }
        });
    }
});
</script>
