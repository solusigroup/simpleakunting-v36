<?php $activeTab = 'anomali'; ?>

<div class="d-flex flex-wrap justify-content-between align-items-center mb-4 gap-3">
    <div>
        <h3 class="fw-bold mb-1"><i class="bi bi-exclamation-triangle text-danger me-2"></i>Analisis Jurnal Anomali</h3>
        <p class="text-muted mb-0">Deteksi ketidakseimbangan debit-kredit, baris tanpa induk, transaksi nol, dan anomali pembukuan.</p>
    </div>
    <div class="d-flex gap-2">
        <a href="<?php echo BASEURL; ?>/jurnal" class="btn btn-outline-primary rounded-pill px-3 shadow-sm">
            <i class="bi bi-journal-text me-1"></i> Buka Jurnal Umum
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
        <a class="nav-link rounded-pill active fw-bold bg-danger" href="<?php echo BASEURL; ?>/analisis/anomali">
            <i class="bi bi-exclamation-triangle me-1"></i> Jurnal Anomali
            <?php if (($data['anomali']['total'] ?? 0) > 0): ?>
                <span class="badge bg-white text-danger ms-1"><?php echo $data['anomali']['total']; ?></span>
            <?php endif; ?>
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
        <a class="nav-link rounded-pill text-dark" href="<?php echo BASEURL; ?>/analisis/kesehatan">
            <i class="bi bi-heart-pulse me-1"></i> Kesehatan Usaha
        </a>
    </li>
</ul>

<?php Flash::flash(); ?>

<!-- Summary Cards Anomali -->
<div class="row g-3 mb-4">
    <div class="col-md-3">
        <div class="card border-0 shadow-sm rounded-4 p-3 border-start border-primary border-4">
            <span class="text-muted small fw-medium">Total Anomali Ditemukan</span>
            <h2 class="fw-bold mb-0 text-dark"><?php echo $data['anomali']['total']; ?></h2>
            <small class="text-muted">Item perlu diverifikasi</small>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card border-0 shadow-sm rounded-4 p-3 border-start border-danger border-4">
            <span class="text-muted small fw-medium">Anomali Kritis (Unbalanced)</span>
            <h2 class="fw-bold mb-0 text-danger"><?php echo $data['anomali']['kritis']; ?></h2>
            <small class="text-muted">Debit ≠ Kredit</small>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card border-0 shadow-sm rounded-4 p-3 border-start border-warning border-4">
            <span class="text-muted small fw-medium">Anomali Tinggi (Struktur)</span>
            <h2 class="fw-bold mb-0 text-warning"><?php echo $data['anomali']['tinggi']; ?></h2>
            <small class="text-muted">Header kosong / Akun hilang</small>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card border-0 shadow-sm rounded-4 p-3 border-start border-info border-4">
            <span class="text-muted small fw-medium">Anomali Sedang / Duplikat</span>
            <h2 class="fw-bold mb-0 text-info"><?php echo $data['anomali']['sedang']; ?></h2>
            <small class="text-muted">Orphan baris & entri identik</small>
        </div>
    </div>
</div>

<!-- Filter Periode -->
<div class="card border-0 shadow-sm rounded-4 mb-4">
    <div class="card-body p-3">
        <form action="<?php echo BASEURL; ?>/analisis/anomali" method="get" class="row g-2 align-items-center">
            <div class="col-md-4">
                <label class="form-label small fw-bold text-muted mb-1">Dari Tanggal</label>
                <input type="date" name="tanggal_mulai" class="form-control form-control-sm rounded-3" value="<?php echo htmlspecialchars($data['tanggal_mulai']); ?>">
            </div>
            <div class="col-md-4">
                <label class="form-label small fw-bold text-muted mb-1">Sampai Tanggal</label>
                <input type="date" name="tanggal_selesai" class="form-control form-control-sm rounded-3" value="<?php echo htmlspecialchars($data['tanggal_selesai']); ?>">
            </div>
            <div class="col-md-4 d-flex align-items-end">
                <button type="submit" class="btn btn-danger btn-sm rounded-pill px-4 w-100 shadow-sm">
                    <i class="bi bi-search me-1"></i> Pindai Ulang Anomali
                </button>
            </div>
        </form>
    </div>
</div>

<!-- Daftar Temuan Anomali -->
<div class="card border-0 shadow-sm rounded-4">
    <div class="card-header bg-white border-0 pt-4 px-4 pb-2 d-flex justify-content-between align-items-center">
        <div>
            <h5 class="fw-bold mb-0">Daftar Hasil Audit Anomali Jurnal</h5>
            <small class="text-muted">Periode pemindaian: <?php echo $data['periode']; ?></small>
        </div>
    </div>
    <div class="card-body p-4">
        <?php if (empty($data['anomali']['daftar'])): ?>
            <div class="text-center py-5">
                <div class="bg-success-subtle text-success p-3 rounded-circle d-inline-block mb-3">
                    <i class="bi bi-shield-check display-5"></i>
                </div>
                <h5 class="fw-bold text-success">Luar Biasa! Tidak Ditemukan Anomali Jurnal</h5>
                <p class="text-muted small mb-0">Seluruh entri jurnal umum berada dalam kondisi seimbang (debit = kredit), memiliki struktur lengkap, dan terdaftar di bagan akun.</p>
            </div>
        <?php else: ?>
            <div class="table-responsive">
                <table class="table table-hover align-middle">
                    <thead class="table-light">
                        <tr>
                            <th style="width: 50px;">No</th>
                            <th>No. Transaksi / Dokumen</th>
                            <th>Tanggal</th>
                            <th>Sumber Transaksi</th>
                            <th>Jenis Anomali</th>
                            <th>Tingkat</th>
                            <th>Deskripsi Temuan Masalah</th>
                            <th class="text-center" style="width: 140px;">Tindakan</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($data['anomali']['daftar'] as $idx => $row): ?>
                            <tr>
                                <td><?php echo $idx + 1; ?></td>
                                <td>
                                    <strong><?php echo htmlspecialchars($row['no_transaksi']); ?></strong>
                                    <?php if (!empty($row['deskripsi'])): ?>
                                        <small class="d-block text-muted text-truncate" style="max-width: 200px;">
                                            <?php echo htmlspecialchars($row['deskripsi']); ?>
                                        </small>
                                    <?php endif; ?>
                                </td>
                                <td><?php echo $row['tanggal']; ?></td>
                                <td>
                                    <span class="badge bg-light text-dark border">
                                        <?php echo htmlspecialchars($row['sumber']); ?>
                                    </span>
                                </td>
                                <td>
                                    <span class="fw-medium text-dark"><?php echo htmlspecialchars($row['jenis_anomali']); ?></span>
                                </td>
                                <td>
                                    <span class="badge bg-<?php echo $row['badge']; ?> rounded-pill">
                                        <?php echo $row['tingkat_keparahan']; ?>
                                    </span>
                                </td>
                                <td>
                                    <small class="text-secondary"><?php echo htmlspecialchars($row['detail']); ?></small>
                                </td>
                                <td class="text-center">
                                    <?php if ($row['aksi'] === 'edit_jurnal'): ?>
                                        <a href="<?php echo BASEURL; ?>/jurnal/detail/<?php echo $row['id_jurnal']; ?>" target="_blank" class="btn btn-outline-primary btn-sm rounded-pill px-2.5">
                                            <i class="bi bi-pencil-square me-1"></i> Koreksi
                                        </a>
                                    <?php elseif ($row['aksi'] === 'hapus_header_kosong'): ?>
                                        <form action="<?php echo BASEURL; ?>/analisis/perbaikiAnomali" method="post" onsubmit="return confirm('Hapus header jurnal kosong ini?');">
                                            <input type="hidden" name="aksi" value="hapus_header_kosong">
                                            <input type="hidden" name="id_target" value="<?php echo $row['id_jurnal']; ?>">
                                            <button type="submit" class="btn btn-sm btn-outline-danger rounded-pill px-2.5">
                                                <i class="bi bi-trash me-1"></i> Bersihkan
                                            </button>
                                        </form>
                                    <?php elseif ($row['aksi'] === 'bersihkan_orphan_detail'): ?>
                                        <form action="<?php echo BASEURL; ?>/analisis/perbaikiAnomali" method="post" onsubmit="return confirm('Hapus baris detail yatim ini dari database?');">
                                            <input type="hidden" name="aksi" value="bersihkan_orphan_detail">
                                            <input type="hidden" name="id_target" value="<?php echo $row['id_jurnal']; ?>">
                                            <button type="submit" class="btn btn-sm btn-outline-warning text-dark rounded-pill px-2.5">
                                                <i class="bi bi-eraser me-1"></i> Hapus Detail
                                            </button>
                                        </form>
                                    <?php else: ?>
                                        <a href="<?php echo BASEURL; ?>/jurnal" class="btn btn-sm btn-light border rounded-pill px-2.5">
                                            <i class="bi bi-eye"></i> Cek
                                        </a>
                                    <?php endif; ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>
    </div>
</div>
