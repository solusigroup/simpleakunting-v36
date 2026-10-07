<div class="d-flex justify-content-between align-items-center mb-4 d-print-none">
    <h3 class="fw-bold mb-0">Laporan Aset Biologis (PSAK 241)</h3>
    <a href="<?php echo BASEURL; ?>/asetbiologis" class="btn btn-outline-secondary rounded-pill px-4">
        <i class="bi bi-arrow-left me-2"></i>Kembali
    </a>
</div>

<div class="card border-0 shadow-sm mb-4 d-print-none">
    <div class="card-body">
        <form action="<?php echo BASEURL; ?>/asetbiologis/laporan" method="get" class="row g-3 align-items-end">
            <div class="col-md-4">
                <label class="form-label fw-bold small">Periode Mulai</label>
                <input type="date" name="mulai" class="form-control rounded-3" value="<?php echo htmlspecialchars($_GET['mulai'] ?? date('Y-m-01')); ?>" required>
            </div>
            <div class="col-md-4">
                <label class="form-label fw-bold small">Periode Selesai</label>
                <input type="date" name="selesai" class="form-control rounded-3" value="<?php echo htmlspecialchars($_GET['selesai'] ?? date('Y-m-t')); ?>" required>
            </div>
            <div class="col-md-4">
                <button type="submit" class="btn btn-primary rounded-pill px-4 w-100">
                    <i class="bi bi-filter me-2"></i>Tampilkan Laporan
                </button>
            </div>
        </form>
    </div>
</div>

<div class="d-none d-print-block mb-4 text-center">
    <h3 class="fw-bold">Laporan Aset Biologis (PSAK 241)</h3>
    <p>Periode: <?php echo htmlspecialchars($_GET['mulai'] ?? date('Y-m-01')) . ' s/d ' . htmlspecialchars($_GET['selesai'] ?? date('Y-m-t')); ?></p>
    <hr>
</div>

<?php if (isset($data['rekonsiliasi'])): ?>
<div class="card border-0 shadow-sm mb-4">
    <div class="card-header bg-white border-0 pt-4 pb-0">
        <h5 class="fw-bold mb-0">Rekonsiliasi Nilai Tercatat (IAS 41.50)</h5>
    </div>
    <div class="card-body">
        <div class="table-responsive">
            <table class="table table-bordered table-hover align-middle mb-0">
                <thead class="bg-light">
                    <tr class="text-center align-middle">
                        <th rowspan="2">Kategori</th>
                        <th rowspan="2">Saldo Awal</th>
                        <th colspan="2">Penambahan</th>
                        <th colspan="2">Penyesuaian Nilai Wajar</th>
                        <th colspan="3">Pengurangan</th>
                        <th rowspan="2">Saldo Akhir</th>
                    </tr>
                    <tr class="text-center">
                        <th>Pembelian</th>
                        <th>Kelahiran/Pertumbuhan</th>
                        <th>Keuntungan</th>
                        <th>Kerugian</th>
                        <th>Panen</th>
                        <th>Penjualan</th>
                        <th>Kematian/Hapus</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($data['rekonsiliasi'] as $kat => $row): ?>
                    <tr>
                        <td class="fw-medium"><?php echo htmlspecialchars($kat); ?></td>
                        <td class="text-end">Rp <?php echo number_format($row['saldo_awal'] ?? 0, 2, ',', '.'); ?></td>
                        <td class="text-end">Rp <?php echo number_format($row['pembelian'] ?? 0, 2, ',', '.'); ?></td>
                        <td class="text-end">Rp <?php echo number_format($row['kelahiran'] ?? 0, 2, ',', '.'); ?></td>
                        <td class="text-end text-success">Rp <?php echo number_format($row['keuntungan_fv'] ?? 0, 2, ',', '.'); ?></td>
                        <td class="text-end text-danger">Rp <?php echo number_format($row['kerugian_fv'] ?? 0, 2, ',', '.'); ?></td>
                        <td class="text-end text-danger">Rp <?php echo number_format($row['panen'] ?? 0, 2, ',', '.'); ?></td>
                        <td class="text-end text-danger">Rp <?php echo number_format($row['penjualan'] ?? 0, 2, ',', '.'); ?></td>
                        <td class="text-end text-danger">Rp <?php echo number_format($row['kematian'] ?? 0, 2, ',', '.'); ?></td>
                        <td class="text-end fw-bold">Rp <?php echo number_format($row['saldo_akhir'] ?? 0, 2, ',', '.'); ?></td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>
<?php endif; ?>

<div class="card border-0 shadow-sm mb-4">
    <div class="card-header bg-white border-0 pt-4 pb-0">
        <h5 class="fw-bold mb-0">Ringkasan Klasifikasi Aset Aktif</h5>
    </div>
    <div class="card-body">
        <div class="table-responsive">
            <table class="table table-bordered table-hover align-middle mb-0">
                <thead class="bg-light">
                    <tr>
                        <th>Kategori</th>
                        <th>Status Kematangan</th>
                        <th class="text-center">Jumlah Aset</th>
                        <th class="text-end">Total Nilai Tercatat</th>
                    </tr>
                </thead>
                <tbody>
                    <?php 
                    $total_aset = 0;
                    $total_nilai = 0;
                    foreach ($data['ringkasan'] ?? [] as $row): 
                        $total_aset += $row['jumlah'];
                        $total_nilai += $row['total_nilai'];
                    ?>
                    <tr>
                        <td class="fw-medium"><?php echo htmlspecialchars($row['kategori']); ?></td>
                        <td><?php echo htmlspecialchars($row['status_kematangan']); ?></td>
                        <td class="text-center"><?php echo htmlspecialchars($row['jumlah']); ?></td>
                        <td class="text-end">Rp <?php echo number_format($row['total_nilai'], 2, ',', '.'); ?></td>
                    </tr>
                    <?php endforeach; ?>
                    <tr class="bg-light fw-bold">
                        <td colspan="2" class="text-end">TOTAL</td>
                        <td class="text-center"><?php echo $total_aset; ?></td>
                        <td class="text-end text-success">Rp <?php echo number_format($total_nilai, 2, ',', '.'); ?></td>
                    </tr>
                </tbody>
            </table>
        </div>
    </div>
</div>

<div class="text-end d-print-none mb-5">
    <button type="button" class="btn btn-outline-dark rounded-pill px-4" onclick="window.print()">
        <i class="bi bi-printer me-2"></i>Cetak Laporan
    </button>
</div>

<style>
@media print {
    body { background-color: #fff; }
    .card { box-shadow: none !important; border: 1px solid #ddd !important; }
    .card-header { border-bottom: 1px solid #ddd !important; }
    .table-bordered th, .table-bordered td { border: 1px solid #000 !important; }
    .badge { border: 1px solid #000; color: #000 !important; background: transparent !important; }
}
</style>
