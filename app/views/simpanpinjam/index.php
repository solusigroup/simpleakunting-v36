<?php
$isAktif = !empty($data['pengaturan']['status_aktif']);
$p = $data['pengaturan'];
$s = $data['stats'];
?>

<div class="d-flex flex-wrap justify-content-between align-items-center mb-4 gap-3">
    <div>
        <h2 class="fw-bold mb-1 d-flex align-items-center gap-2">
            <i class="bi bi-wallet2 text-primary"></i> 
            <?php echo htmlspecialchars($p['nama_unit_sp'] ?? 'Unit Usaha Simpan Pinjam'); ?>
        </h2>
        <p class="text-muted mb-0 small">Sistem Administrasi Keuangan Simpan Pinjam BUMDesa / Koperasi Terintegrasi Akuntansi</p>
    </div>
    <div class="d-flex align-items-center gap-2">
        <form action="<?php echo BASEURL; ?>/simpanpinjam/toggle_status" method="POST" class="d-inline">
            <button type="submit" class="btn <?php echo $isAktif ? 'btn-success' : 'btn-outline-danger'; ?> rounded-pill px-3 shadow-sm d-flex align-items-center gap-2" onclick="return confirm('Apakah Anda yakin ingin mengubah status aktif unit simpan pinjam ini?')">
                <i class="bi <?php echo $isAktif ? 'bi-check-circle-fill' : 'bi-x-circle'; ?>"></i>
                <span>Status: <strong><?php echo $isAktif ? 'AKTIF' : 'NON-AKTIF'; ?></strong></span>
            </button>
        </form>
        <a href="<?php echo BASEURL; ?>/simpanpinjam/pengaturan" class="btn btn-outline-secondary rounded-pill px-3">
            <i class="bi bi-gear-fill me-1"></i> Parameter COA
        </a>
    </div>
</div>

<?php if (!$isAktif): ?>
<div class="alert alert-warning border-0 shadow-sm rounded-4 mb-4 d-flex align-items-center justify-content-between">
    <div class="d-flex align-items-center gap-3">
        <i class="bi bi-exclamation-triangle-fill fs-3 text-warning"></i>
        <div>
            <h6 class="fw-bold mb-0">Unit Simpan Pinjam Sedang Non-Aktif</h6>
            <span class="small text-muted">Fitur operasional dinonaktifkan sementara. Klik tombol status di atas untuk mengaktifkannya kembali.</span>
        </div>
    </div>
</div>
<?php endif; ?>

<!-- KPI Summary Cards -->
<div class="row g-3 mb-4">
    <div class="col-xl-3 col-md-6">
        <div class="card border-0 shadow-sm rounded-4 h-100 bg-white">
            <div class="card-body p-3">
                <div class="d-flex align-items-center justify-content-between mb-2">
                    <span class="text-muted small fw-bold text-uppercase">Total Nasabah</span>
                    <span class="p-2 bg-primary-subtle text-primary rounded-circle"><i class="bi bi-people-fill"></i></span>
                </div>
                <h3 class="fw-bold mb-1"><?php echo number_format($s['total_nasabah']); ?></h3>
                <span class="small text-muted">
                    <i class="bi bi-diagram-2"></i> <?php echo number_format($s['total_kelompok']); ?> Kelompok Nasabah
                </span>
            </div>
        </div>
    </div>

    <div class="col-xl-3 col-md-6">
        <div class="card border-0 shadow-sm rounded-4 h-100 bg-white">
            <div class="card-body p-3">
                <div class="d-flex align-items-center justify-content-between mb-2">
                    <span class="text-muted small fw-bold text-uppercase">Total Simpanan</span>
                    <span class="p-2 bg-success-subtle text-success rounded-circle"><i class="bi bi-safe-fill"></i></span>
                </div>
                <h3 class="fw-bold mb-1 text-success">Rp <?php echo number_format($s['total_simpanan'], 0, ',', '.'); ?></h3>
                <span class="small text-muted">Saldo Dana Mengendap</span>
            </div>
        </div>
    </div>

    <div class="col-xl-3 col-md-6">
        <div class="card border-0 shadow-sm rounded-4 h-100 bg-white">
            <div class="card-body p-3">
                <div class="d-flex align-items-center justify-content-between mb-2">
                    <span class="text-muted small fw-bold text-uppercase">Outstanding Pinjaman</span>
                    <span class="p-2 bg-info-subtle text-info rounded-circle"><i class="bi bi-cash-stack"></i></span>
                </div>
                <h3 class="fw-bold mb-1 text-primary">Rp <?php echo number_format($s['total_outstanding'], 0, ',', '.'); ?></h3>
                <span class="small text-muted">
                    Dari Total Salur: Rp <?php echo number_format($s['total_penyaluran'], 0, ',', '.'); ?>
                </span>
            </div>
        </div>
    </div>

    <div class="col-xl-3 col-md-6">
        <div class="card border-0 shadow-sm rounded-4 h-100 bg-white">
            <div class="card-body p-3">
                <div class="d-flex align-items-center justify-content-between mb-2">
                    <span class="text-muted small fw-bold text-uppercase">Pengajuan Pending</span>
                    <span class="p-2 bg-warning-subtle text-warning rounded-circle"><i class="bi bi-hourglass-split"></i></span>
                </div>
                <h3 class="fw-bold mb-1 text-warning"><?php echo number_format($s['pending_pinjaman_count']); ?></h3>
                <span class="small text-muted">
                    Nominal: Rp <?php echo number_format($s['pending_pinjaman_nominal'], 0, ',', '.'); ?>
                </span>
            </div>
        </div>
    </div>
</div>

<!-- Navigasi Modul & Shortcut Cepat -->
<div class="row g-3 mb-4">
    <div class="col-md-3">
        <a href="<?php echo BASEURL; ?>/simpanpinjam/nasabah" class="card border-0 shadow-sm rounded-4 text-decoration-none text-dark h-100 p-3 hover-shadow transition">
            <div class="d-flex align-items-center gap-3">
                <div class="p-3 bg-primary text-white rounded-3 fs-4"><i class="bi bi-person-lines-fill"></i></div>
                <div>
                    <h6 class="fw-bold mb-1">Data Nasabah</h6>
                    <small class="text-muted">Kelompok & Perorangan</small>
                </div>
            </div>
        </a>
    </div>
    <div class="col-md-3">
        <a href="<?php echo BASEURL; ?>/simpanpinjam/simpanan" class="card border-0 shadow-sm rounded-4 text-decoration-none text-dark h-100 p-3 hover-shadow transition">
            <div class="d-flex align-items-center gap-3">
                <div class="p-3 bg-success text-white rounded-3 fs-4"><i class="bi bi-piggy-bank"></i></div>
                <div>
                    <h6 class="fw-bold mb-1">Modul Simpanan</h6>
                    <small class="text-muted">Setor, Tarik, & Rekening</small>
                </div>
            </div>
        </a>
    </div>
    <div class="col-md-3">
        <a href="<?php echo BASEURL; ?>/simpanpinjam/pinjaman" class="card border-0 shadow-sm rounded-4 text-decoration-none text-dark h-100 p-3 hover-shadow transition">
            <div class="d-flex align-items-center gap-3">
                <div class="p-3 bg-info text-white rounded-3 fs-4"><i class="bi bi-credit-card"></i></div>
                <div>
                    <h6 class="fw-bold mb-1">Modul Pinjaman</h6>
                    <small class="text-muted">Pengajuan & Angsuran</small>
                </div>
            </div>
        </a>
    </div>
    <div class="col-md-3">
        <a href="<?php echo BASEURL; ?>/simpanpinjam/laporan_pinjaman" class="card border-0 shadow-sm rounded-4 text-decoration-none text-dark h-100 p-3 hover-shadow transition">
            <div class="d-flex align-items-center gap-3">
                <div class="p-3 bg-danger text-white rounded-3 fs-4"><i class="bi bi-shield-check"></i></div>
                <div>
                    <h6 class="fw-bold mb-1">Kolektibilitas</h6>
                    <small class="text-muted">NPL & Outstanding</small>
                </div>
            </div>
        </a>
    </div>
</div>

<!-- Tabel Pinjaman Terbaru & Transaksi Simpanan Terakhir -->
<div class="row g-4">
    <div class="col-lg-7">
        <div class="card border-0 shadow-sm rounded-4">
            <div class="card-header bg-white py-3 border-0 d-flex justify-content-between align-items-center">
                <h6 class="fw-bold mb-0"><i class="bi bi-file-earmark-text me-1 text-primary"></i> Pengajuan Pinjaman Terkini</h6>
                <a href="<?php echo BASEURL; ?>/simpanpinjam/pinjaman" class="small text-decoration-none fw-bold">Lihat Semua &rarr;</a>
            </div>
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead class="table-light small">
                        <tr>
                            <th class="ps-3">No. Pinjaman</th>
                            <th>Debitur</th>
                            <th>Nominal</th>
                            <th>Status</th>
                            <th class="text-end pe-3">Aksi</th>
                        </tr>
                    </thead>
                    <tbody class="small">
                        <?php if (empty($data['pinjaman_terbaru'])): ?>
                            <tr><td colspan="5" class="text-center py-4 text-muted">Belum ada data pengajuan pinjaman.</td></tr>
                        <?php else: ?>
                            <?php foreach (array_slice($data['pinjaman_terbaru'], 0, 5) as $pj): ?>
                            <tr>
                                <td class="ps-3 fw-bold"><?php echo htmlspecialchars($pj['no_pinjaman']); ?></td>
                                <td>
                                    <?php if ($pj['tipe_debitur'] === 'Kelompok'): ?>
                                        <span class="badge bg-info-subtle text-info me-1">Kelompok</span>
                                        <strong><?php echo htmlspecialchars($pj['nama_kelompok']); ?></strong>
                                    <?php else: ?>
                                        <span class="badge bg-secondary-subtle text-secondary me-1">Perorangan</span>
                                        <strong><?php echo htmlspecialchars($pj['nama_nasabah']); ?></strong>
                                    <?php endif; ?>
                                </td>
                                <td>Rp <?php echo number_format($pj['jumlah_pengajuan'], 0, ',', '.'); ?></td>
                                <td>
                                    <?php
                                    $badgeClass = match($pj['status']) {
                                        'Diajukan' => 'bg-warning text-dark',
                                        'Disetujui' => 'bg-info text-white',
                                        'Dicairkan' => 'bg-primary text-white',
                                        'Lunas' => 'bg-success text-white',
                                        default => 'bg-danger text-white'
                                    };
                                    ?>
                                    <span class="badge <?php echo $badgeClass; ?> rounded-pill px-2"><?php echo $pj['status']; ?></span>
                                </td>
                                <td class="text-end pe-3">
                                    <a href="<?php echo BASEURL; ?>/simpanpinjam/pinjaman_detail/<?php echo $pj['id_pinjaman']; ?>" class="btn btn-sm btn-light rounded-circle" title="Detail">
                                        <i class="bi bi-chevron-right"></i>
                                    </a>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <div class="col-lg-5">
        <div class="card border-0 shadow-sm rounded-4">
            <div class="card-header bg-white py-3 border-0 d-flex justify-content-between align-items-center">
                <h6 class="fw-bold mb-0"><i class="bi bi-clock-history me-1 text-success"></i> Mutasi Simpanan Terakhir</h6>
                <a href="<?php echo BASEURL; ?>/simpanpinjam/laporan_simpanan" class="small text-decoration-none fw-bold">Laporan &rarr;</a>
            </div>
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead class="table-light small">
                        <tr>
                            <th class="ps-3">Nasabah</th>
                            <th>Jenis</th>
                            <th>Nominal</th>
                            <th class="text-end pe-3">Tanggal</th>
                        </tr>
                    </thead>
                    <tbody class="small">
                        <?php if (empty($data['transaksi_terbaru'])): ?>
                            <tr><td colspan="4" class="text-center py-4 text-muted">Belum ada transaksi simpanan.</td></tr>
                        <?php else: ?>
                            <?php foreach (array_slice($data['transaksi_terbaru'], 0, 5) as $trx): ?>
                            <tr>
                                <td class="ps-3">
                                    <div class="fw-bold"><?php echo htmlspecialchars($trx['nama_nasabah']); ?></div>
                                    <span class="text-muted" style="font-size:0.75rem;"><?php echo htmlspecialchars($trx['no_rekening']); ?></span>
                                </td>
                                <td>
                                    <?php if ($trx['jenis_transaksi'] === 'Setor'): ?>
                                        <span class="badge bg-success-subtle text-success">Setor</span>
                                    <?php else: ?>
                                        <span class="badge bg-danger-subtle text-danger">Tarik</span>
                                    <?php endif; ?>
                                </td>
                                <td class="fw-bold <?php echo ($trx['jenis_transaksi'] === 'Setor') ? 'text-success' : 'text-danger'; ?>">
                                    <?php echo ($trx['jenis_transaksi'] === 'Setor') ? '+' : '-'; ?>Rp <?php echo number_format($trx['nominal'], 0, ',', '.'); ?>
                                </td>
                                <td class="text-end pe-3 text-muted"><?php echo date('d/m/Y', strtotime($trx['tanggal'])); ?></td>
                            </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<style>
.hover-shadow:hover {
    transform: translateY(-2px);
    box-shadow: 0 .5rem 1rem rgba(0,0,0,.15)!important;
}
.transition {
    transition: all .2s ease-in-out;
}
</style>
