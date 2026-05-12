<div class="card shadow-sm">
    <div class="card-header d-flex justify-content-between align-items-center">
        <h3 class="mb-0">Buku Besar (General Ledger)</h3>
        <div class="d-flex gap-2">
            <?php if(!empty($data['id_unit'])): ?>
                <span class="badge bg-info">Unit: <?php 
                    foreach($data['units'] as $u) if($u['id_unit'] == $data['id_unit']) echo htmlspecialchars($u['nama_unit']); 
                ?></span>
            <?php endif; ?>
            <?php if(!empty($data['id_program'])): ?>
                <span class="badge bg-warning text-dark">Program: <?php 
                    foreach($data['programs'] as $p) if($p['id_program'] == $data['id_program']) echo htmlspecialchars($p['nama_program']); 
                ?></span>
            <?php endif; ?>
        </div>
    </div>
    <div class="card-body">
        <form id="laporan-form" action="<?php echo BASEURL; ?>/laporan/bukuBesar" method="post">
            <div class="row g-3">
                <div class="col-md-4">
                    <label for="kode_akun" class="form-label fw-bold">Pilih Akun Detail</label>
                    <select class="form-select select-search" name="kode_akun" id="kode_akun" required>
                        <option value="">-- Pilih Akun --</option>
                        <?php foreach($data['akun'] as $akun): ?>
                            <?php if($akun['tipe_akun'] != 'Header'): ?>
                                <option value="<?php echo $akun['kode_akun'] ?>" <?php echo (($data['kode_akun_terpilih'] ?? '') == $akun['kode_akun']) ? 'selected' : '' ?>>
                                    <?php echo $akun['kode_akun'] . ' - ' . $akun['nama_akun'] ?>
                                </option>
                            <?php endif; ?>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-md-4">
                    <label for="id_unit" class="form-label fw-bold">Unit Usaha</label>
                    <select name="id_unit" id="id_unit" class="form-select">
                        <option value="">-- Semua Unit (Konsolidasi) --</option>
                        <?php foreach($data['units'] as $unit): ?>
                            <option value="<?php echo $unit['id_unit']; ?>" <?php echo (($data['id_unit'] ?? '') == $unit['id_unit']) ? 'selected' : ''; ?>>
                                <?php echo htmlspecialchars($unit['nama_unit']); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-md-4">
                    <label for="id_program" class="form-label fw-bold">Program / Sumber Dana</label>
                    <select name="id_program" id="id_program" class="form-select">
                        <option value="">-- Semua Sumber Dana --</option>
                        <?php foreach($data['programs'] as $prog): ?>
                            <option value="<?php echo $prog['id_program']; ?>" <?php echo (($data['id_program'] ?? '') == $prog['id_program']) ? 'selected' : ''; ?>>
                                <?php echo htmlspecialchars($prog['nama_program']); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                
                <div class="col-md-3">
                    <label for="tanggal_mulai" class="form-label">Dari Tanggal</label>
                    <input type="date" name="tanggal_mulai" id="tanggal_mulai" class="form-control" value="<?php echo htmlspecialchars($data['tanggal_mulai'] ?? ''); ?>">
                </div>
                <div class="col-md-3">
                    <label for="tanggal_selesai" class="form-label">Sampai Tanggal</label>
                    <input type="date" name="tanggal_selesai" id="tanggal_selesai" class="form-control" value="<?php echo htmlspecialchars($data['tanggal_selesai'] ?? ''); ?>">
                </div>
                <div class="col-md-6 text-end d-flex align-items-end justify-content-end">
                    <button type="submit" class="btn btn-primary px-4 me-2">
                        <i class="bi bi-search me-2"></i>Tampilkan
                    </button>
                    <div class="btn-group">
                        <button type="button" class="btn btn-outline-success dropdown-toggle" data-bs-toggle="dropdown" aria-expanded="false">
                            <i class="bi bi-download me-2"></i>Ekspor
                        </button>
                        <ul class="dropdown-menu dropdown-menu-end">
                            <li><button type="button" id="export-excel" class="dropdown-item">ke Excel</button></li>
                            <li><button type="button" id="export-pdf" class="dropdown-item">ke PDF</button></li>
                        </ul>
                    </div>
                </div>
            </div>
            <!-- Input tersembunyi untuk ekspor -->
            <input type="hidden" name="kode_akun_export" id="kode_akun_export">
            <input type="hidden" name="tanggal_mulai_export" id="tanggal_mulai_export">
            <input type="hidden" name="tanggal_selesai_export" id="tanggal_selesai_export">
            <input type="hidden" name="id_unit_export" id="id_unit_export">
            <input type="hidden" name="id_program_export" id="id_program_export">
        </form>
    </div>
</div>

<?php if (isset($data['laporan']) && $data['laporan'] !== null): ?>
    <div class="card shadow-sm mt-4">
        <div class="card-header text-center py-4 bg-white">
            <h5 class="fw-bold mb-1"><?php echo htmlspecialchars($data['perusahaan']['nama_perusahaan'] ?? 'Nama Perusahaan'); ?></h5>
            <h6 class="text-uppercase mb-1">Buku Besar (General Ledger)</h6>
            <p class="mb-1 font-monospace fw-bold">[<?php echo htmlspecialchars($data['kode_akun_terpilih'] ?? ''); ?>] <?php echo htmlspecialchars($data['nama_akun_terpilih'] ?? ''); ?></p>
            <div class="small text-muted">
                Periode: <?php echo htmlspecialchars($data['periode_1'] ?? ''); ?>
                <?php if(!empty($data['id_unit'])): ?> | Unit: <?php echo $unit_name ?? 'Unit Spesifik'; ?><?php endif; ?>
                <?php if(!empty($data['id_program'])): ?> | Program: <?php echo $prog_name ?? 'Program Spesifik'; ?><?php endif; ?>
            </div>
        </div>
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-hover table-bordered table-sm">
                    <thead class="table-light text-center">
                        <tr>
                            <th width="100">Tanggal</th>
                            <th width="150">No. Transaksi</th>
                            <th>Deskripsi</th>
                            <th width="150" class="text-end">Debit</th>
                            <th width="150" class="text-end">Kredit</th>
                            <th width="150" class="text-end">Saldo</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr class="table-secondary">
                            <td colspan="5" class="ps-3 fw-bold italic text-muted">Saldo Awal Periode</td>
                            <td class="text-end fw-bold font-monospace"><?php echo number_format($data['laporan']['saldo_awal_periode'] ?? 0, 2, ',', '.') ?></td>
                        </tr>
                        <?php
                            $saldo = $data['laporan']['saldo_awal_periode'] ?? 0;
                            foreach(($data['laporan']['transaksi'] ?? []) as $row):
                                if (($data['laporan']['posisi_saldo_normal'] ?? 'Debit') == 'Debit') {
                                    $saldo = $saldo + $row['debit'] - $row['kredit'];
                                } else { // Kredit
                                    $saldo = $saldo - $row['debit'] + $row['kredit'];
                                }
                        ?>
                        <tr>
                            <td class="text-center"><?php echo date('d/m/Y', strtotime($row['tanggal'])) ?></td>
                            <td class="text-center font-monospace small"><?php echo htmlspecialchars($row['no_transaksi']) ?></td>
                            <td><?php echo htmlspecialchars($row['deskripsi']) ?></td>
                            <td class="text-end font-monospace"><?php echo ($row['debit'] > 0) ? number_format($row['debit'], 2, ',', '.') : '-' ?></td>
                            <td class="text-end font-monospace"><?php echo ($row['kredit'] > 0) ? number_format($row['kredit'], 2, ',', '.') : '-' ?></td>
                            <td class="text-end font-monospace fw-bold"><?php echo number_format($saldo, 2, ',', '.') ?></td>
                        </tr>
                        <?php endforeach; ?>
                        <?php if(empty($data['laporan']['transaksi'])): ?>
                            <tr><td colspan="6" class="text-center py-3 text-muted">Tidak ada transaksi pada periode ini.</td></tr>
                        <?php endif; ?>
                    </tbody>
                     <tfoot class="table-dark">
                        <tr>
                            <td colspan="5" class="ps-3 fw-bold">SALDO AKHIR PERIODE</td>
                            <td class="text-end fw-bold font-monospace"><?php echo number_format($saldo, 2, ',', '.'); ?></td>
                        </tr>
                    </tfoot>
                </table>
            </div>
        </div>
    </div>
<?php endif; ?>

<script>
    document.addEventListener('DOMContentLoaded', function() {
        const form = document.getElementById('laporan-form');
        const exportExcelBtn = document.getElementById('export-excel');
        const exportPdfBtn = document.getElementById('export-pdf');

        function prepareExportData() {
            document.getElementById('kode_akun_export').value = document.getElementById('kode_akun').value;
            document.getElementById('tanggal_mulai_export').value = document.getElementById('tanggal_mulai').value;
            document.getElementById('tanggal_selesai_export').value = document.getElementById('tanggal_selesai').value;
            document.getElementById('id_unit_export').value = document.getElementById('id_unit').value;
            document.getElementById('id_program_export').value = document.getElementById('id_program').value;
        }

        if (exportExcelBtn) {
            exportExcelBtn.addEventListener('click', function() {
                prepareExportData();
                form.action = "<?php echo BASEURL; ?>/laporan/eksporBukuBesar";
                form.target = "_self";
                form.submit();
                form.action = "<?php echo BASEURL; ?>/laporan/bukuBesar";
            });
        }
        
        if (exportPdfBtn) {
            exportPdfBtn.addEventListener('click', function() {
                prepareExportData();
                form.action = "<?php echo BASEURL; ?>/laporan/eksporPdfBukuBesar";
                form.target = "_blank";
                form.submit();
                form.action = "<?php echo BASEURL; ?>/laporan/bukuBesar";
                form.target = "_self";
            });
        }
    });
</script>
