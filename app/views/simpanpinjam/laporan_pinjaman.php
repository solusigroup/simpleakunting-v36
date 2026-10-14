<?php
$lap = $data['laporan'];
$cutOff = $data['cut_off'] ?? date('Y-m-d');
$rekap = $lap['rekap'] ?? [];
$detailList = $lap['detail'] ?? [];

$totalCkpn = 0;
foreach ($rekap as $rk) {
    $totalCkpn += $rk['ckpn'];
}
?>

<div class="d-flex flex-wrap justify-content-between align-items-center mb-4 gap-3">
    <div>
        <nav aria-label="breadcrumb">
            <ol class="breadcrumb mb-1 small">
                <li class="breadcrumb-item"><a href="<?php echo BASEURL; ?>/simpanpinjam">Simpan Pinjam</a></li>
                <li class="breadcrumb-item"><a href="<?php echo BASEURL; ?>/simpanpinjam/pinjaman">Pinjaman</a></li>
                <li class="breadcrumb-item active">Laporan Kolektibilitas & Outstanding</li>
            </ol>
        </nav>
        <h2 class="fw-bold mb-0">Laporan Kolektibilitas & Outstanding Pinjaman</h2>
    </div>
    <div class="d-flex gap-2 d-print-none">
        <button type="button" class="btn btn-outline-secondary rounded-pill px-3" onclick="window.print()">
            <i class="bi bi-printer me-1"></i> Cetak Laporan
        </button>
        <a href="<?php echo BASEURL; ?>/simpanpinjam/pinjaman" class="btn btn-light rounded-pill px-3">
            <i class="bi bi-arrow-left me-1"></i> Kembali
        </a>
    </div>
</div>

<!-- Filter Tanggal Cut-Off (d-print-none) -->
<div class="card border-0 shadow-sm rounded-4 mb-4 d-print-none">
    <div class="card-body p-3">
        <form action="<?php echo BASEURL; ?>/simpanpinjam/laporan_pinjaman" method="GET" class="row g-2 align-items-center">
            <div class="col-auto">
                <span class="small fw-bold text-muted"><i class="bi bi-calendar-event me-1"></i> Posisi Tanggal Cut-Off:</span>
            </div>
            <div class="col-md-3">
                <input type="date" name="cut_off" class="form-control form-control-sm" value="<?php echo $cutOff; ?>" required>
            </div>
            <div class="col-auto">
                <button type="submit" class="btn btn-sm btn-primary rounded-pill px-3">
                    <i class="bi bi-calculator me-1"></i> Hitung Kolektibilitas
                </button>
                <a href="<?php echo BASEURL; ?>/simpanpinjam/laporan_pinjaman" class="btn btn-sm btn-light rounded-pill px-3">Hari Ini</a>
            </div>
        </form>
    </div>
</div>

<!-- Header Cetak -->
<div class="d-none d-print-block mb-4 text-center">
    <h3 class="fw-bold mb-1">LAPORAN KUALITAS KREDIT & KOLEKTIBILITAS PINJAMAN</h3>
    <p class="text-muted small mb-0">Posisi Per Tanggal: <?php echo date('d F Y', strtotime($cutOff)); ?></p>
    <hr>
</div>

<!-- Summary Metric Cards -->
<div class="row g-3 mb-4">
    <div class="col-xl-3 col-md-6">
        <div class="card border-0 shadow-sm rounded-4 h-100 bg-white">
            <div class="card-body p-3">
                <span class="text-muted small fw-bold text-uppercase d-block mb-1">Total Outstanding Pokok</span>
                <h3 class="fw-bold text-primary mb-0">Rp <?php echo number_format($lap['total_outstanding'], 0, ',', '.'); ?></h3>
                <small class="text-muted"><?php echo $lap['total_pinjaman']; ?> Pinjaman Aktif</small>
            </div>
        </div>
    </div>

    <div class="col-xl-3 col-md-6">
        <div class="card border-0 shadow-sm rounded-4 h-100 bg-white">
            <div class="card-body p-3">
                <span class="text-muted small fw-bold text-uppercase d-block mb-1">Non-Performing Loan (NPL)</span>
                <h3 class="fw-bold text-danger mb-0">Rp <?php echo number_format($lap['total_npl'], 0, ',', '.'); ?></h3>
                <small class="text-muted">KOL 3, 4, & 5 (Macet / Bermasalah)</small>
            </div>
        </div>
    </div>

    <div class="col-xl-3 col-md-6">
        <div class="card border-0 shadow-sm rounded-4 h-100 bg-white">
            <div class="card-body p-3">
                <span class="text-muted small fw-bold text-uppercase d-block mb-1">Rasio NPL (%)</span>
                <h3 class="fw-bold <?php echo ($lap['persen_npl'] > 5) ? 'text-danger' : 'text-success'; ?> mb-0">
                    <?php echo $lap['persen_npl']; ?>%
                </h3>
                <small class="text-muted">
                    <?php if ($lap['persen_npl'] <= 5): ?>
                        <span class="text-success"><i class="bi bi-shield-check"></i> Kategori Sehat (&le; 5%)</span>
                    <?php else: ?>
                        <span class="text-danger"><i class="bi bi-exclamation-triangle"></i> Melebihi Batas Sehat (&gt; 5%)</span>
                    <?php endif; ?>
                </small>
            </div>
        </div>
    </div>

    <div class="col-xl-3 col-md-6">
        <div class="card border-0 shadow-sm rounded-4 h-100 bg-white">
            <div class="card-body p-3">
                <span class="text-muted small fw-bold text-uppercase d-block mb-1">Estimasi Cadangan (CKPN)</span>
                <h3 class="fw-bold text-warning-emphasis mb-0">Rp <?php echo number_format($totalCkpn, 0, ',', '.'); ?></h3>
                <small class="text-muted">Cadangan Kerugian Penurunan Nilai</small>
            </div>
        </div>
    </div>
</div>

<!-- Tabel Rekap Kolektibilitas (Standar OJK / Koperasi) -->
<div class="card border-0 shadow-sm rounded-4 overflow-hidden mb-4">
    <div class="card-header bg-white py-3 border-0 d-flex justify-content-between align-items-center">
        <h6 class="fw-bold mb-0"><i class="bi bi-pie-chart me-1 text-primary"></i> Matriks Klasifikasi Kolektibilitas (Aging Portofolio)</h6>
        <span class="small text-muted">Standar Pengawasan Koperasi & Lembaga Jasa Keuangan</span>
    </div>
    <div class="table-responsive">
        <table class="table table-hover align-middle mb-0">
            <thead class="table-light small">
                <tr>
                    <th class="ps-3">Kategori Kualitas</th>
                    <th>Hari Tunggakan</th>
                    <th class="text-center">Jumlah Debitur</th>
                    <th class="text-end">Total Outstanding Pokok</th>
                    <th class="text-center">Bobot CKPN (%)</th>
                    <th class="text-end pe-3">Estimasi Beban CKPN</th>
                </tr>
            </thead>
            <tbody class="small">
                <?php
                $badges = [
                    'kol_1' => 'bg-success text-white',
                    'kol_2' => 'bg-info text-dark',
                    'kol_3' => 'bg-warning text-dark',
                    'kol_4' => 'bg-orange text-white',
                    'kol_5' => 'bg-danger text-white'
                ];
                foreach ($rekap as $kKey => $rk):
                ?>
                <tr>
                    <td class="ps-3">
                        <span class="badge <?php echo $badges[$kKey] ?? 'bg-secondary'; ?> rounded-pill px-3 py-1 me-2">
                            <?php echo $rk['label']; ?>
                        </span>
                    </td>
                    <td><?php echo $rk['hari']; ?></td>
                    <td class="text-center fw-bold"><?php echo $rk['count']; ?></td>
                    <td class="text-end fw-bold">Rp <?php echo number_format($rk['outstanding'], 0, ',', '.'); ?></td>
                    <td class="text-center"><?php echo $rk['persen_ckpn']; ?>%</td>
                    <td class="text-end pe-3 fw-bold text-danger">Rp <?php echo number_format($rk['ckpn'], 0, ',', '.'); ?></td>
                </tr>
                <?php endforeach; ?>
            </tbody>
            <tfoot class="table-light fw-bold small">
                <tr>
                    <td colspan="2" class="ps-3">TOTAL:</td>
                    <td class="text-center"><?php echo $lap['total_pinjaman']; ?></td>
                    <td class="text-end text-primary">Rp <?php echo number_format($lap['total_outstanding'], 0, ',', '.'); ?></td>
                    <td class="text-center">-</td>
                    <td class="text-end pe-3 text-danger">Rp <?php echo number_format($totalCkpn, 0, ',', '.'); ?></td>
                </tr>
            </tfoot>
        </table>
    </div>
</div>

<!-- Tabel Rincian Outstanding Seluruh Debitur -->
<div class="card border-0 shadow-sm rounded-4 overflow-hidden mb-4">
    <div class="card-header bg-white py-3 border-0 d-flex justify-content-between align-items-center">
        <h6 class="fw-bold mb-0"><i class="bi bi-people me-1 text-primary"></i> Rincian Outstanding & Tunggakan per Pinjaman</h6>
        <span class="badge bg-light text-muted border"><?php echo count($detailList); ?> Rekening</span>
    </div>
    <div class="table-responsive">
        <table class="table table-hover align-middle mb-0">
            <thead class="table-light small">
                <tr>
                    <th class="ps-3" style="width: 40px;">No</th>
                    <th>No. Pinjaman</th>
                    <th>Nama Debitur</th>
                    <th>Produk</th>
                    <th class="text-end">Plafon Salur</th>
                    <th class="text-end">Sisa Pokok</th>
                    <th class="text-center">Hari Nunggak</th>
                    <th class="text-center">Kolektibilitas</th>
                    <th class="text-end">Tunggakan Pokok</th>
                    <th class="text-end pe-3">Tunggakan Bunga</th>
                </tr>
            </thead>
            <tbody class="small">
                <?php if (empty($detailList)): ?>
                    <tr><td colspan="10" class="text-center py-4 text-muted">Belum ada pinjaman berstatus aktif / dicairkan.</td></tr>
                <?php else: ?>
                    <?php 
                    $no = 1;
                    foreach ($detailList as $d): 
                        $debName = ($d['tipe_debitur'] === 'Kelompok') ? $d['nama_kelompok'] : $d['nama_nasabah'];
                    ?>
                    <tr>
                        <td class="ps-3 text-muted"><?php echo $no++; ?></td>
                        <td class="font-monospace fw-bold">
                            <a href="<?php echo BASEURL; ?>/simpanpinjam/pinjaman_detail/<?php echo $d['id_pinjaman']; ?>" class="text-decoration-none">
                                <?php echo htmlspecialchars($d['no_pinjaman']); ?>
                            </a>
                        </td>
                        <td>
                            <div class="fw-bold"><?php echo htmlspecialchars($debName); ?></div>
                            <span class="text-muted" style="font-size:0.75rem;">
                                <?php echo ($d['tipe_debitur'] === 'Kelompok') ? 'Kelompok' : ('Agt: ' . ($d['no_anggota'] ?? '-')); ?>
                            </span>
                        </td>
                        <td><?php echo htmlspecialchars($d['nama_pinjaman']); ?></td>
                        <td class="text-end">Rp <?php echo number_format($d['jumlah_disetujui'], 0, ',', '.'); ?></td>
                        <td class="text-end fw-bold text-primary">Rp <?php echo number_format($d['sisa_pokok'], 0, ',', '.'); ?></td>
                        <td class="text-center">
                            <span class="fw-bold <?php echo ($d['hari_menunggak'] > 0) ? 'text-danger' : 'text-success'; ?>">
                                <?php echo $d['hari_menunggak']; ?> hr
                            </span>
                        </td>
                        <td class="text-center">
                            <?php
                            $kolBadge = match($d['kol_kode']) {
                                'kol_1' => 'bg-success',
                                'kol_2' => 'bg-info text-dark',
                                'kol_3' => 'bg-warning text-dark',
                                'kol_4' => 'bg-orange text-white',
                                default => 'bg-danger text-white'
                            };
                            ?>
                            <span class="badge <?php echo $kolBadge; ?> rounded-pill px-2 py-1">
                                <?php echo strtoupper(str_replace('_', ' ', $d['kol_kode'])); ?>
                            </span>
                        </td>
                        <td class="text-end text-danger fw-semibold">
                            <?php echo ($d['tunggakan_pokok'] > 0) ? 'Rp ' . number_format($d['tunggakan_pokok'], 0, ',', '.') : '-'; ?>
                        </td>
                        <td class="text-end pe-3 text-danger fw-semibold">
                            <?php echo ($d['tunggakan_jasa'] > 0) ? 'Rp ' . number_format($d['tunggakan_jasa'], 0, ',', '.') : '-'; ?>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<style>
.bg-orange {
    background-color: #fd7e14!important;
}
@media print {
    body {
        background: #fff!important;
        font-size: 10pt;
    }
    .d-print-none, .sidebar, .navbar, footer {
        display: none!important;
    }
    .card {
        border: 1px solid #ddd!important;
        box-shadow: none!important;
    }
}
</style>
