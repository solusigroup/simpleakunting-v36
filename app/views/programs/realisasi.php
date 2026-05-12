<?php 
$realisasi = $data['realisasi'];
$program = $realisasi['program'];
?>
<div class="container-fluid">
    <div class="row mb-4">
        <div class="col-md-6">
            <h1 class="h3 mb-0 text-gray-800">Laporan Realisasi Dana</h1>
            <p class="text-muted">Rincian penggunaan dana untuk: <strong><?php echo $program['nama_program']; ?></strong></p>
        </div>
        <div class="col-md-6 text-end">
            <button class="btn btn-outline-secondary" onclick="window.print()">
                <i class="bi bi-printer"></i> Cetak Laporan
            </button>
            <a href="<?php echo BASEURL; ?>/programs" class="btn btn-secondary">Kembali</a>
        </div>
    </div>

    <div class="row">
        <!-- Summary Cards -->
        <div class="col-md-4 mb-4">
            <div class="card border-left-primary shadow h-100 py-2">
                <div class="card-body">
                    <div class="row no-gutters align-items-center">
                        <div class="col mr-2">
                            <div class="text-xs font-weight-bold text-primary text-uppercase mb-1">Total Dana Masuk</div>
                            <div class="h5 mb-0 font-weight-bold text-gray-800">Rp <?php echo number_format($realisasi['total_income'], 0, ',', '.'); ?></div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-md-4 mb-4">
            <div class="card border-left-danger shadow h-100 py-2">
                <div class="card-body">
                    <div class="row no-gutters align-items-center">
                        <div class="col mr-2">
                            <div class="text-xs font-weight-bold text-danger text-uppercase mb-1">Total Penggunaan</div>
                            <div class="h5 mb-0 font-weight-bold text-gray-800">Rp <?php echo number_format($realisasi['total_expense'], 0, ',', '.'); ?></div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-md-4 mb-4">
            <div class="card border-left-success shadow h-100 py-2">
                <div class="card-body">
                    <div class="row no-gutters align-items-center">
                        <div class="col mr-2">
                            <div class="text-xs font-weight-bold text-success text-uppercase mb-1">Sisa Saldo Dana</div>
                            <div class="h5 mb-0 font-weight-bold text-gray-800">Rp <?php echo number_format($realisasi['total_income'] - $realisasi['total_expense'], 0, ',', '.'); ?></div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="row">
        <!-- Income Table -->
        <div class="col-md-6 mb-4">
            <div class="card shadow border-0">
                <div class="card-header bg-primary text-white py-3">
                    <h6 class="m-0 font-weight-bold">Rincian Penerimaan Dana</h6>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-bordered">
                            <thead class="bg-light">
                                <tr>
                                    <th>Akun Pendapatan</th>
                                    <th class="text-end">Jumlah</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach($realisasi['income'] as $inc): ?>
                                <tr>
                                    <td><?php echo $inc['nama']; ?></td>
                                    <td class="text-end">Rp <?php echo number_format($inc['jumlah'], 0, ',', '.'); ?></td>
                                </tr>
                                <?php endforeach; ?>
                                <?php if(empty($realisasi['income'])): ?>
                                <tr><td colspan="2" class="text-center text-muted">Belum ada dana masuk.</td></tr>
                                <?php endif; ?>
                            </tbody>
                            <tfoot class="fw-bold bg-light">
                                <tr>
                                    <td>TOTAL</td>
                                    <td class="text-end">Rp <?php echo number_format($realisasi['total_income'], 0, ',', '.'); ?></td>
                                </tr>
                            </tfoot>
                        </table>
                    </div>
                </div>
            </div>
        </div>

        <!-- Expense Table -->
        <div class="col-md-6 mb-4">
            <div class="card shadow border-0">
                <div class="card-header bg-danger text-white py-3">
                    <h6 class="m-0 font-weight-bold">Rincian Penggunaan Dana</h6>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-bordered">
                            <thead class="bg-light">
                                <tr>
                                    <th>Akun Biaya/Belanja</th>
                                    <th class="text-end">Jumlah</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach($realisasi['expense'] as $exp): ?>
                                <tr>
                                    <td><?php echo $exp['nama']; ?></td>
                                    <td class="text-end">Rp <?php echo number_format($exp['jumlah'], 0, ',', '.'); ?></td>
                                </tr>
                                <?php endforeach; ?>
                                <?php if(empty($realisasi['expense'])): ?>
                                <tr><td colspan="2" class="text-center text-muted">Belum ada penggunaan dana.</td></tr>
                                <?php endif; ?>
                            </tbody>
                            <tfoot class="fw-bold bg-light">
                                <tr>
                                    <td>TOTAL</td>
                                    <td class="text-end">Rp <?php echo number_format($realisasi['total_expense'], 0, ',', '.'); ?></td>
                                </tr>
                            </tfoot>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<style>
@media print {
    .btn, .sidebar, .navbar { display: none !important; }
    .card { border: 1px solid #ddd !important; shadow: none !important; }
    .container-fluid { padding: 0 !important; }
}
.border-left-primary { border-left: .25rem solid #4e73df!important; }
.border-left-success { border-left: .25rem solid #1cc88a!important; }
.border-left-info { border-left: .25rem solid #36b9cc!important; }
.border-left-danger { border-left: .25rem solid #e74a3b!important; }
</style>
