<?php
$rekeningList = $data['rekening'] ?? [];
$transaksiList = $data['transaksi'] ?? [];
$tglMulai = $data['tgl_mulai'] ?? date('Y-m-01');
$tglSelesai = $data['tgl_selesai'] ?? date('Y-m-d');

$totalSaldo = 0;
$saldoPerTipe = ['Pokok' => 0, 'Wajib' => 0, 'Sukarela' => 0, 'Berjangka' => 0];
foreach ($rekeningList as $r) {
    $totalSaldo += (float)$r['saldo'];
    $tipe = $r['tipe_simpanan'] ?? 'Sukarela';
    if (isset($saldoPerTipe[$tipe])) {
        $saldoPerTipe[$tipe] += (float)$r['saldo'];
    }
}

$totalSetorPeriode = 0;
$totalTarikPeriode = 0;
foreach ($transaksiList as $t) {
    if ($t['jenis_transaksi'] === 'Setor') {
        $totalSetorPeriode += (float)$t['nominal'];
    } else {
        $totalTarikPeriode += (float)$t['nominal'];
    }
}
?>

<div class="d-flex flex-wrap justify-content-between align-items-center mb-4 gap-3">
    <div>
        <nav aria-label="breadcrumb">
            <ol class="breadcrumb mb-1 small">
                <li class="breadcrumb-item"><a href="<?php echo BASEURL; ?>/simpanpinjam">Simpan Pinjam</a></li>
                <li class="breadcrumb-item"><a href="<?php echo BASEURL; ?>/simpanpinjam/simpanan">Simpanan</a></li>
                <li class="breadcrumb-item active">Laporan Simpanan</li>
            </ol>
        </nav>
        <h2 class="fw-bold mb-0">Laporan Simpanan Nasabah</h2>
    </div>
    <div class="d-flex gap-2 d-print-none">
        <button type="button" class="btn btn-outline-secondary rounded-pill px-3" onclick="window.print()">
            <i class="bi bi-printer me-1"></i> Cetak Laporan
        </button>
        <a href="<?php echo BASEURL; ?>/simpanpinjam/simpanan" class="btn btn-light rounded-pill px-3">
            <i class="bi bi-arrow-left me-1"></i> Kembali
        </a>
    </div>
</div>

<!-- Filter Periode (d-print-none) -->
<div class="card border-0 shadow-sm rounded-4 mb-4 d-print-none">
    <div class="card-body p-3">
        <form action="<?php echo BASEURL; ?>/simpanpinjam/laporan_simpanan" method="GET" class="row g-2 align-items-center">
            <div class="col-auto">
                <span class="small fw-bold text-muted"><i class="bi bi-funnel me-1"></i> Filter Periode Mutasi:</span>
            </div>
            <div class="col-md-3">
                <input type="date" name="tgl_mulai" class="form-control form-control-sm" value="<?php echo $tglMulai; ?>" required>
            </div>
            <div class="col-auto"><span class="small text-muted">s/d</span></div>
            <div class="col-md-3">
                <input type="date" name="tgl_selesai" class="form-control form-control-sm" value="<?php echo $tglSelesai; ?>" required>
            </div>
            <div class="col-auto">
                <button type="submit" class="btn btn-sm btn-primary rounded-pill px-3">
                    <i class="bi bi-search me-1"></i> Terapkan
                </button>
                <a href="<?php echo BASEURL; ?>/simpanpinjam/laporan_simpanan" class="btn btn-sm btn-light rounded-pill px-3">Reset</a>
            </div>
        </form>
    </div>
</div>

<!-- Header Cetak -->
<div class="d-none d-print-block mb-4 text-center">
    <h3 class="fw-bold mb-1">LAPORAN SIMPANAN NASABAH</h3>
    <p class="text-muted small mb-0">Periode Mutasi: <?php echo date('d/m/Y', strtotime($tglMulai)); ?> s/d <?php echo date('d/m/Y', strtotime($tglSelesai)); ?></p>
    <hr>
</div>

<!-- KPI Cards -->
<div class="row g-3 mb-4">
    <div class="col-md-3 col-sm-6">
        <div class="card border-0 shadow-sm rounded-4 h-100 bg-white">
            <div class="card-body p-3">
                <span class="text-muted small fw-bold text-uppercase d-block mb-1">Total Saldo Terkumpul</span>
                <h4 class="fw-bold text-primary mb-0">Rp <?php echo number_format($totalSaldo, 0, ',', '.'); ?></h4>
                <small class="text-muted"><?php echo count($rekeningList); ?> Rekening Aktif</small>
            </div>
        </div>
    </div>
    <div class="col-md-3 col-sm-6">
        <div class="card border-0 shadow-sm rounded-4 h-100 bg-white">
            <div class="card-body p-3">
                <span class="text-muted small fw-bold text-uppercase d-block mb-1">Simpanan Sukarela</span>
                <h4 class="fw-bold text-success mb-0">Rp <?php echo number_format($saldoPerTipe['Sukarela'], 0, ',', '.'); ?></h4>
                <small class="text-muted">Tabungan Fleksibel</small>
            </div>
        </div>
    </div>
    <div class="col-md-3 col-sm-6">
        <div class="card border-0 shadow-sm rounded-4 h-100 bg-white">
            <div class="card-body p-3">
                <span class="text-muted small fw-bold text-uppercase d-block mb-1">Simpanan Pokok & Wajib</span>
                <h4 class="fw-bold text-info mb-0">Rp <?php echo number_format($saldoPerTipe['Pokok'] + $saldoPerTipe['Wajib'], 0, ',', '.'); ?></h4>
                <small class="text-muted">Pokok: Rp <?php echo number_format($saldoPerTipe['Pokok'], 0, ',', '.'); ?></small>
            </div>
        </div>
    </div>
    <div class="col-md-3 col-sm-6">
        <div class="card border-0 shadow-sm rounded-4 h-100 bg-white">
            <div class="card-body p-3">
                <span class="text-muted small fw-bold text-uppercase d-block mb-1">Arus Mutasi Periode</span>
                <div class="d-flex justify-content-between small">
                    <span class="text-success fw-bold">+Rp <?php echo number_format($totalSetorPeriode, 0, ',', '.'); ?></span>
                    <span class="text-danger fw-bold">-Rp <?php echo number_format($totalTarikPeriode, 0, ',', '.'); ?></span>
                </div>
                <small class="text-muted">Net: Rp <?php echo number_format($totalSetorPeriode - $totalTarikPeriode, 0, ',', '.'); ?></small>
            </div>
        </div>
    </div>
</div>

<!-- Nav Tabs -->
<ul class="nav nav-pills mb-4 gap-2 d-print-none" id="laporanTab" role="tablist">
    <li class="nav-item" role="presentation">
        <button class="nav-link active rounded-pill px-4" id="rekap-tab" data-bs-toggle="tab" data-bs-target="#rekap" type="button" role="tab">
            <i class="bi bi-wallet2 me-1"></i> Rekapitulasi Saldo Rekening (<?php echo count($rekeningList); ?>)
        </button>
    </li>
    <li class="nav-item" role="presentation">
        <button class="nav-link rounded-pill px-4" id="mutasi-tab" data-bs-toggle="tab" data-bs-target="#mutasi" type="button" role="tab">
            <i class="bi bi-clock-history me-1"></i> Rincian Mutasi Periode (<?php echo count($transaksiList); ?>)
        </button>
    </li>
</ul>

<div class="tab-content" id="laporanTabContent">
    <!-- TAB 1: REKAPITULASI SALDO REKENING -->
    <div class="tab-pane fade show active" id="rekap" role="tabpanel">
        <div class="card border-0 shadow-sm rounded-4 overflow-hidden mb-4">
            <div class="card-header bg-white py-3 border-0 d-flex justify-content-between align-items-center">
                <h6 class="fw-bold mb-0"><i class="bi bi-list-check me-1 text-primary"></i> Rekapitulasi Saldo Simpanan per Nasabah</h6>
            </div>
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead class="table-light small">
                        <tr>
                            <th class="ps-3" style="width: 50px;">No</th>
                            <th>No. Rekening</th>
                            <th>No. Anggota</th>
                            <th>Nama Nasabah</th>
                            <th>Jenis Simpanan</th>
                            <th>Tgl Buka</th>
                            <th>Status</th>
                            <th class="text-end pe-3">Saldo Akhir</th>
                        </tr>
                    </thead>
                    <tbody class="small">
                        <?php if (empty($rekeningList)): ?>
                            <tr><td colspan="8" class="text-center py-4 text-muted">Belum ada rekening simpanan.</td></tr>
                        <?php else: ?>
                            <?php 
                            $no = 1; 
                            foreach ($rekeningList as $r): 
                            ?>
                            <tr>
                                <td class="ps-3 text-muted"><?php echo $no++; ?></td>
                                <td class="font-monospace fw-bold">
                                    <a href="<?php echo BASEURL; ?>/simpanpinjam/buku_tabungan/<?php echo $r['id_rekening']; ?>" class="text-decoration-none">
                                        <?php echo htmlspecialchars($r['no_rekening']); ?>
                                    </a>
                                </td>
                                <td><?php echo htmlspecialchars($r['no_anggota']); ?></td>
                                <td class="fw-bold"><?php echo htmlspecialchars($r['nama_nasabah']); ?></td>
                                <td>
                                    <span class="badge bg-secondary-subtle text-secondary me-1"><?php echo htmlspecialchars($r['tipe_simpanan']); ?></span>
                                    <?php echo htmlspecialchars($r['nama_simpanan']); ?>
                                </td>
                                <td><?php echo date('d/m/Y', strtotime($r['tanggal_buka'])); ?></td>
                                <td>
                                    <span class="badge <?php echo ($r['status'] === 'Aktif') ? 'bg-success' : 'bg-secondary'; ?> rounded-pill">
                                        <?php echo $r['status']; ?>
                                    </span>
                                </td>
                                <td class="text-end pe-3 fw-bold text-primary">
                                    Rp <?php echo number_format($r['saldo'], 0, ',', '.'); ?>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                    <tfoot class="table-light fw-bold small">
                        <tr>
                            <td colspan="7" class="text-end ps-3">TOTAL KESELURUHAN SALDO SIMPANAN:</td>
                            <td class="text-end pe-3 text-primary fs-6">Rp <?php echo number_format($totalSaldo, 0, ',', '.'); ?></td>
                        </tr>
                    </tfoot>
                </table>
            </div>
        </div>
    </div>

    <!-- TAB 2: RINCIAN MUTASI TRANSAKSI -->
    <div class="tab-pane fade" id="mutasi" role="tabpanel">
        <div class="card border-0 shadow-sm rounded-4 overflow-hidden mb-4">
            <div class="card-header bg-white py-3 border-0 d-flex justify-content-between align-items-center">
                <h6 class="fw-bold mb-0"><i class="bi bi-clock-history me-1 text-primary"></i> Jurnal / Mutasi Transaksi Periode Terpilih</h6>
                <span class="small text-muted"><?php echo date('d/m/Y', strtotime($tglMulai)); ?> s/d <?php echo date('d/m/Y', strtotime($tglSelesai)); ?></span>
            </div>
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead class="table-light small">
                        <tr>
                            <th class="ps-3" style="width: 50px;">No</th>
                            <th>Tanggal</th>
                            <th>No. Transaksi</th>
                            <th>No. Rekening</th>
                            <th>Nama Nasabah</th>
                            <th>Jenis Mutasi</th>
                            <th>Keterangan</th>
                            <th class="text-end pe-3">Nominal</th>
                        </tr>
                    </thead>
                    <tbody class="small">
                        <?php if (empty($transaksiList)): ?>
                            <tr><td colspan="8" class="text-center py-4 text-muted">Tidak ada transaksi simpanan pada rentang periode ini.</td></tr>
                        <?php else: ?>
                            <?php 
                            $noTrx = 1; 
                            foreach ($transaksiList as $t): 
                            ?>
                            <tr>
                                <td class="ps-3 text-muted"><?php echo $noTrx++; ?></td>
                                <td><?php echo date('d/m/Y', strtotime($t['tanggal'])); ?></td>
                                <td class="font-monospace text-primary"><?php echo htmlspecialchars($t['no_transaksi']); ?></td>
                                <td class="font-monospace"><?php echo htmlspecialchars($t['no_rekening']); ?></td>
                                <td class="fw-semibold"><?php echo htmlspecialchars($t['nama_nasabah']); ?></td>
                                <td>
                                    <?php if ($t['jenis_transaksi'] === 'Setor'): ?>
                                        <span class="badge bg-success-subtle text-success rounded-pill px-2">Setor</span>
                                    <?php else: ?>
                                        <span class="badge bg-danger-subtle text-danger rounded-pill px-2">Tarik</span>
                                    <?php endif; ?>
                                </td>
                                <td><?php echo htmlspecialchars($t['keterangan'] ?? '-'); ?></td>
                                <td class="text-end pe-3 fw-bold <?php echo ($t['jenis_transaksi'] === 'Setor') ? 'text-success' : 'text-danger'; ?>">
                                    <?php echo ($t['jenis_transaksi'] === 'Setor') ? '+' : '-'; ?>Rp <?php echo number_format($t['nominal'], 0, ',', '.'); ?>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                    <tfoot class="table-light fw-bold small">
                        <tr>
                            <td colspan="7" class="text-end ps-3">Total Setoran Periode:</td>
                            <td class="text-end pe-3 text-success">+Rp <?php echo number_format($totalSetorPeriode, 0, ',', '.'); ?></td>
                        </tr>
                        <tr>
                            <td colspan="7" class="text-end ps-3">Total Penarikan Periode:</td>
                            <td class="text-end pe-3 text-danger">-Rp <?php echo number_format($totalTarikPeriode, 0, ',', '.'); ?></td>
                        </tr>
                    </tfoot>
                </table>
            </div>
        </div>
    </div>
</div>

<style>
@media print {
    .d-print-none, .sidebar, .navbar, footer {
        display: none!important;
    }
    body {
        background: #fff!important;
        font-size: 10pt;
    }
    .card {
        border: none!important;
        box-shadow: none!important;
    }
    .tab-content > .tab-pane {
        display: block!important;
        opacity: 1!important;
        visibility: visible!important;
    }
}
</style>
