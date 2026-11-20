<?php $activeTab = 'tanpaJurnal'; ?>

<div class="d-flex flex-wrap justify-content-between align-items-center mb-4 gap-3">
    <div>
        <h3 class="fw-bold mb-1"><i class="bi bi-link-45deg text-warning me-2"></i>Transaksi Tanpa Jurnal</h3>
        <p class="text-muted mb-0">Identifikasi transaksi operasional (penjualan, pembelian, kas) yang belum membentuk entri pembukuan double-entry.</p>
    </div>
    <div class="d-flex gap-2">
        <a href="<?php echo BASEURL; ?>/laporan/neracaSaldo" class="btn btn-outline-secondary rounded-pill px-3 shadow-sm">
            <i class="bi bi-table me-1"></i> Cek Neraca Saldo
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
        <a class="nav-link rounded-pill active fw-bold bg-warning text-dark" href="<?php echo BASEURL; ?>/analisis/tanpaJurnal">
            <i class="bi bi-link-45deg me-1"></i> Transaksi Tanpa Jurnal
            <?php if (($data['tanpa_jurnal']['total'] ?? 0) > 0): ?>
                <span class="badge bg-dark text-white ms-1"><?php echo $data['tanpa_jurnal']['total']; ?></span>
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

<?php Flash::flash(); ?>

<!-- Keterangan & Panduan Solusi -->
<div class="alert alert-info border-0 rounded-4 shadow-sm d-flex align-items-center mb-4">
    <i class="bi bi-info-circle-fill fs-3 me-3 text-info"></i>
    <div class="small">
        <strong>Mengapa transaksi bisa tidak memiliki jurnal?</strong> Hal ini dapat terjadi akibat impor data lama, transaksi manual yang terputus koneksi saat posting, atau penghapusan manual jurnal terkait. Gunakan fitur <strong>Sinkronkan Otomatis</strong> di bawah untuk membuat jurnal penyesuaian double-entry secara otomatis sesuai aturan akuntansi baku.
    </div>
</div>

<!-- Filter Tipe Transaksi (Pills) -->
<div class="card border-0 shadow-sm rounded-4 mb-4">
    <div class="card-body p-3">
        <div class="d-flex flex-wrap gap-2 align-items-center">
            <span class="small fw-bold text-muted me-2">Filter Modul:</span>
            <?php
            $tipeOptions = [
                'semua' => 'Semua Modul',
                'penjualan' => 'Penjualan (Faktur)',
                'pembelian' => 'Pembelian (Faktur)',
                'kas' => 'Kas & Bank',
                'penerimaan' => 'Penerimaan Piutang',
                'pembayaran' => 'Pembayaran Utang',
                'simpanpinjam' => 'Simpan Pinjam',
                'penyusutan' => 'Penyusutan Aset'
            ];
            $currentFilter = $data['filter_tipe'] ?? 'semua';
            foreach ($tipeOptions as $k => $label):
                $activeBtn = ($currentFilter === $k) ? 'btn-dark text-white' : 'btn-light border text-secondary';
            ?>
                <a href="<?php echo BASEURL; ?>/analisis/tanpaJurnal?tipe=<?php echo $k; ?>" class="btn btn-sm rounded-pill px-3 <?php echo $activeBtn; ?>">
                    <?php echo $label; ?>
                </a>
            <?php endforeach; ?>
        </div>
    </div>
</div>

<!-- Tabel Daftar Transaksi Tanpa Jurnal -->
<div class="card border-0 shadow-sm rounded-4">
    <div class="card-header bg-white border-0 pt-4 px-4 pb-2 d-flex justify-content-between align-items-center">
        <div>
            <h5 class="fw-bold mb-0">Daftar Transaksi Operasional Belum Terjurnal</h5>
            <small class="text-muted">Total terdeteksi: <?php echo $data['tanpa_jurnal']['total']; ?> transaksi</small>
        </div>
    </div>
    <div class="card-body p-4">
        <?php if (empty($data['tanpa_jurnal']['daftar'])): ?>
            <div class="text-center py-5">
                <div class="bg-success-subtle text-success p-3 rounded-circle d-inline-block mb-3">
                    <i class="bi bi-check-all display-5"></i>
                </div>
                <h5 class="fw-bold text-success">Semua Transaksi Telah Sinkron dengan Jurnal!</h5>
                <p class="text-muted small mb-0">Tidak ada transaksi yang terlewat atau tidak memiliki pasangan jurnal double-entry.</p>
            </div>
        <?php else: ?>
            <div class="table-responsive">
                <table class="table table-hover align-middle">
                    <thead class="table-light">
                        <tr>
                            <th style="width: 50px;">No</th>
                            <th>Modul Transaksi</th>
                            <th>No. Bukti / Faktur</th>
                            <th>Tanggal</th>
                            <th>Pelanggan / Rekanan</th>
                            <th class="text-end">Nominal (Rp)</th>
                            <th>Status / Keterangan</th>
                            <th class="text-center" style="width: 200px;">Aksi Perbaikan</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($data['tanpa_jurnal']['daftar'] as $idx => $row): ?>
                            <tr>
                                <td><?php echo $idx + 1; ?></td>
                                <td>
                                    <span class="badge bg-primary-subtle text-primary border border-primary-subtle rounded-pill px-2.5">
                                        <?php echo htmlspecialchars($row['tipe']); ?>
                                    </span>
                                </td>
                                <td><strong><?php echo htmlspecialchars($row['no_bukti']); ?></strong></td>
                                <td><?php echo $row['tanggal']; ?></td>
                                <td><?php echo htmlspecialchars($row['pihak']); ?></td>
                                <td class="text-end fw-bold text-dark">
                                    Rp <?php echo number_format($row['nominal'], 2, ',', '.'); ?>
                                </td>
                                <td>
                                    <small class="text-muted d-block"><?php echo htmlspecialchars($row['keterangan']); ?></small>
                                </td>
                                <td class="text-center">
                                    <div class="d-flex justify-content-center gap-1">
                                        <?php if ($row['bisa_sinkron']): ?>
                                            <form action="<?php echo BASEURL; ?>/analisis/sinkronkan" method="post" onsubmit="return confirm('Buat jurnal otomatis untuk transaksi <?php echo htmlspecialchars($row['no_bukti']); ?>
            <?php echo csrf_field(); ?>?');">
                                                <input type="hidden" name="tipe" value="<?php echo htmlspecialchars($row['tipe']); ?>">
                                                <input type="hidden" name="id_transaksi" value="<?php echo $row['id_transaksi']; ?>">
                                                <button type="submit" class="btn btn-sm btn-success rounded-pill px-2.5" title="Generate Jurnal Otomatis">
                                                    <i class="bi bi-magic me-1"></i> Sinkronkan
                                                </button>
                                            </form>
                                        <?php endif; ?>
                                        <a href="<?php echo $row['link_detail']; ?>" target="_blank" class="btn btn-sm btn-outline-secondary rounded-pill px-2.5" title="Lihat Sumber Dokumen">
                                            <i class="bi bi-box-arrow-up-right"></i>
                                        </a>
                                    </div>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>
    </div>
</div>
