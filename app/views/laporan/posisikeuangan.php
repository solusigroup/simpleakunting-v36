<div class="card shadow-sm">
    <div class="card-header d-flex justify-content-between align-items-center">
        <h3 class="mb-0">Laporan Posisi Keuangan (Neraca)</h3>
        <?php if(!empty($data['id_unit'])): ?>
            <span class="badge bg-info">Unit: <?php 
                $unit_name = "Unit Tidak Ditemukan";
                foreach($data['units'] as $u) if($u['id_unit'] == $data['id_unit']) $unit_name = $u['nama_unit'];
                echo htmlspecialchars($unit_name); 
            ?></span>
        <?php else: ?>
            <span class="badge bg-primary">Konsolidasi (Seluruh Unit)</span>
        <?php endif; ?>
    </div>
    <div class="card-body">
        <form id="laporan-form" action="<?php echo BASEURL; ?>/laporan/posisiKeuangan" method="post">
            <div class="row g-3 align-items-end">
                <div class="col-md-3">
                    <label for="tanggal_selesai_1" class="form-label">Tanggal Laporan Utama</label>
                    <input type="date" name="tanggal_selesai_1" id="tanggal_selesai_1" class="form-control" value="<?php echo htmlspecialchars($data['tanggal_selesai_1'] ?? ''); ?>">
                </div>
                <div class="col-md-3">
                    <label for="tanggal_selesai_2" class="form-label">Tanggal Pembanding</label>
                    <input type="date" name="tanggal_selesai_2" id="tanggal_selesai_2" class="form-control" value="<?php echo htmlspecialchars($data['tanggal_selesai_2'] ?? ''); ?>">
                </div>
                <div class="col-md-4">
                    <label for="id_unit" class="form-label">Unit Usaha</label>
                    <select name="id_unit" id="id_unit" class="form-select">
                        <option value="">-- Semua Unit (Konsolidasi) --</option>
                        <?php foreach($data['units'] as $unit): ?>
                            <option value="<?php echo $unit['id_unit']; ?>" <?php echo ($data['id_unit'] == $unit['id_unit']) ? 'selected' : ''; ?>>
                                <?php echo htmlspecialchars($unit['nama_unit']); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-md-2">
                    <div class="d-flex gap-2">
                        <button type="submit" class="btn btn-primary w-100">Tampilkan</button>
                        <div class="btn-group">
                            <button type="button" class="btn btn-outline-success dropdown-toggle" data-bs-toggle="dropdown" aria-expanded="false">
                                <i class="bi bi-download"></i>
                            </button>
                            <ul class="dropdown-menu dropdown-menu-end">
                                <li><button type="button" id="export-excel" class="dropdown-item"><i class="bi bi-file-earmark-excel me-2"></i>Excel</button></li>
                                <li><button type="button" id="export-pdf" class="dropdown-item"><i class="bi bi-file-earmark-pdf me-2"></i>PDF</button></li>
                            </ul>
                        </div>
                    </div>
                </div>
            </div>
            <!-- Input tersembunyi untuk ekspor -->
            <input type="hidden" name="tanggal_selesai_1_export" id="tanggal_selesai_1_export">
            <input type="hidden" name="tanggal_selesai_2_export" id="tanggal_selesai_2_export">
            <input type="hidden" name="id_unit_export" id="id_unit_export">
        </form>
    </div>
</div>

<?php if (isset($data['laporan']) && $data['laporan'] !== null): ?>
<div class="card shadow-sm mt-4">
    <div class="card-header text-center py-4 bg-white">
        <h5 class="fw-bold mb-1"><?php echo htmlspecialchars($data['perusahaan']['nama_perusahaan']); ?></h5>
        <h6 class="text-uppercase tracking-wider mb-1">Laporan Posisi Keuangan <?php if(!empty($data['periode_2'])) echo "Komparatif"; ?></h6>
        <?php if(!empty($data['id_unit'])): ?>
            <p class="mb-1 fw-bold text-teal-600">Unit: <?php echo htmlspecialchars($unit_name); ?></p>
        <?php else: ?>
            <p class="mb-1 fw-bold text-primary">Laporan Konsolidasi (Gabungan)</p>
        <?php endif; ?>
        <p class="mb-0 text-muted">Per Tanggal <?php echo $data['periode_1'] . (!empty($data['periode_2']) ? ' dan ' . $data['periode_2'] : ''); ?></p>
    </div>
    <div class="card-body">
        <div class="table-responsive">
            <table class="table table-hover table-sm">
                <thead class="table-light">
                    <tr>
                        <th class="py-3 ps-3">Keterangan Akun</th>
                        <th class="text-end py-3"><?php echo $data['periode_1']; ?></th>
                        <?php if(!empty($data['periode_2'])): ?>
                            <th class="text-end py-3"><?php echo $data['periode_2']; ?></th>
                            <th class="text-end py-3">Selisih (Rp)</th>
                            <th class="text-end py-3">Var (%)</th>
                        <?php endif; ?>
                    </tr>
                </thead>
                <tbody>
                    <tr class="bg-light"><td colspan="5" class="fw-bold text-primary py-2 ps-3 uppercase small">ASET</td></tr>
                    <?php 
                        foreach($data['laporan']['periode_1']['aset'] as $item): 
                        $total1 = $item['total'];
                        $key = array_search($item['kode_akun'], array_column($data['laporan']['periode_2']['aset'] ?? [], 'kode_akun'));
                        $total2 = ($key !== false) ? $data['laporan']['periode_2']['aset'][$key]['total'] : 0;
                        $perubahan_rp = $total1 - $total2;
                        $perubahan_persen = ($total2 != 0) ? ($perubahan_rp / abs($total2)) * 100 : 0;
                    ?>
                    <tr>
                        <td class="ps-4"><?php echo htmlspecialchars($item['nama_akun']); ?></td>
                        <td class="text-end font-monospace"><?php echo number_format($total1, 2, ',', '.'); ?></td>
                        <?php if(!empty($data['periode_2'])): ?>
                            <td class="text-end font-monospace"><?php echo number_format($total2, 2, ',', '.'); ?></td>
                            <td class="text-end font-monospace"><?php echo number_format($perubahan_rp, 2, ',', '.'); ?></td>
                            <td class="text-end"><span class="badge <?php echo ($perubahan_rp >= 0) ? 'bg-success-subtle text-success' : 'bg-danger-subtle text-danger'; ?>"><?php echo ($total2 != 0) ? number_format($perubahan_persen, 1) . '%' : '-'; ?></span></td>
                        <?php endif; ?>
                    </tr>
                    <?php endforeach; ?>
                    <tr class="fw-bold table-info"><td class="ps-3">TOTAL ASET</td><td class="text-end font-monospace"><?php echo number_format($data['laporan']['periode_1']['total_aset'] ?? 0, 2, ',', '.'); ?></td><?php if(!empty($data['periode_2'])): ?><td class="text-end font-monospace"><?php echo number_format($data['laporan']['periode_2']['total_aset'] ?? 0, 2, ',', '.'); ?></td><td colspan="2"></td><?php endif; ?></tr>

                    <tr class="bg-light"><td colspan="5" class="fw-bold text-primary py-2 ps-3 uppercase small mt-3">KEWAJIBAN & EKUITAS</td></tr>
                    <tr><td colspan="5" class="ps-3 py-2 text-muted fw-bold small italic">Kewajiban</td></tr>
                    <?php foreach($data['laporan']['periode_1']['kewajiban'] ?? [] as $item): 
                        $total1 = $item['total'];
                        $key = array_search($item['kode_akun'], array_column($data['laporan']['periode_2']['kewajiban'] ?? [], 'kode_akun'));
                        $total2 = ($key !== false) ? $data['laporan']['periode_2']['kewajiban'][$key]['total'] : 0;
                        $perubahan_rp = $total1 - $total2;
                        $perubahan_persen = ($total2 != 0) ? ($perubahan_rp / abs($total2)) * 100 : 0;
                    ?>
                    <tr>
                        <td class="ps-4"><?php echo htmlspecialchars($item['nama_akun']); ?></td>
                        <td class="text-end font-monospace"><?php echo number_format($item['total'], 2, ',', '.'); ?></td>
                         <?php if(!empty($data['periode_2'])): ?>
                            <td class="text-end font-monospace"><?php echo number_format($total2, 2, ',', '.'); ?></td>
                            <td class="text-end font-monospace"><?php echo number_format($perubahan_rp, 2, ',', '.'); ?></td>
                            <td class="text-end"><span class="badge <?php echo ($perubahan_rp >= 0) ? 'bg-danger-subtle text-danger' : 'bg-success-subtle text-success'; ?>"><?php echo ($total2 != 0) ? number_format($perubahan_persen, 1) . '%' : '-'; ?></span></td>
                         <?php endif; ?>
                    </tr>
                    <?php endforeach; ?>
                    <tr class="fw-bold"><td class="ps-4">Total Kewajiban</td><td class="text-end font-monospace"><?php echo number_format($data['laporan']['periode_1']['total_kewajiban'] ?? 0, 2, ',', '.'); ?></td><?php if(!empty($data['periode_2'])): ?><td class="text-end font-monospace"><?php echo number_format($data['laporan']['periode_2']['total_kewajiban'] ?? 0, 2, ',', '.'); ?></td><td colspan="2"></td><?php endif; ?></tr>
                    
                    <tr><td colspan="5" class="ps-3 py-2 text-muted fw-bold small italic mt-2">Ekuitas (Modal)</td></tr>
                    <?php foreach($data['laporan']['periode_1']['modal'] ?? [] as $item): 
                        $total1 = $item['total'];
                        $key = array_search($item['kode_akun'], array_column($data['laporan']['periode_2']['modal'] ?? [], 'kode_akun'));
                        $total2 = ($key !== false) ? $data['laporan']['periode_2']['modal'][$key]['total'] : 0;
                        $perubahan_rp = $total1 - $total2;
                        $perubahan_persen = ($total2 != 0) ? ($perubahan_rp / abs($total2)) * 100 : 0;
                    ?>
                    <tr>
                        <td class="ps-4"><?php echo htmlspecialchars($item['nama_akun']); ?></td>
                        <td class="text-end font-monospace"><?php echo number_format($item['total'], 2, ',', '.'); ?></td>
                         <?php if(!empty($data['periode_2'])): ?>
                            <td class="text-end font-monospace"><?php echo number_format($total2, 2, ',', '.'); ?></td>
                            <td class="text-end font-monospace"><?php echo number_format($perubahan_rp, 2, ',', '.'); ?></td>
                            <td class="text-end"><span class="badge <?php echo ($perubahan_rp >= 0) ? 'bg-success-subtle text-success' : 'bg-danger-subtle text-danger'; ?>"><?php echo ($total2 != 0) ? number_format($perubahan_persen, 1) . '%' : '-'; ?></span></td>
                         <?php endif; ?>
                    </tr>
                    <?php endforeach; ?>
                    <tr class="fw-bold"><td class="ps-4">Total Ekuitas</td><td class="text-end font-monospace"><?php echo number_format($data['laporan']['periode_1']['total_modal'] ?? 0, 2, ',', '.'); ?></td><?php if(!empty($data['periode_2'])): ?><td class="text-end font-monospace"><?php echo number_format($data['laporan']['periode_2']['total_modal'] ?? 0, 2, ',', '.'); ?></td><td colspan="2"></td><?php endif; ?></tr>
                </tbody>
                <tfoot class="table-dark fw-bold">
                    <tr>
                        <td class="ps-3">TOTAL KEWAJIBAN DAN EKUITAS</td>
                        <td class="text-end font-monospace"><?php echo number_format(($data['laporan']['periode_1']['total_kewajiban'] ?? 0) + ($data['laporan']['periode_1']['total_modal'] ?? 0), 2, ',', '.'); ?></td>
                        <?php if(!empty($data['periode_2'])): ?><td class="text-end font-monospace"><?php echo number_format(($data['laporan']['periode_2']['total_kewajiban'] ?? 0) + ($data['laporan']['periode_2']['total_modal'] ?? 0), 2, ',', '.'); ?></td><td colspan="2"></td><?php endif; ?>
                    </tr>
                </tfoot>
            </table>
        </div>
        
        <div class="row mt-5" style="page-break-inside: avoid;">
            <div class="col-6 text-center">
                <br>
                <p><?php echo htmlspecialchars($data['penandatangan_1']['jabatan'] ?? 'Ketua BUMDesa'); ?></p>
                <br><br><br>
                <p class="fw-bold mb-0"><u><?php echo htmlspecialchars($data['penandatangan_1']['nama_user'] ?? ''); ?></u></p>
            </div>
            <div class="col-6 text-center">
                <p><?php echo htmlspecialchars($data['kota_laporan'] ?? 'Pasuruan'); ?>, <?php echo date('d F Y'); ?></p>
                <p><?php echo htmlspecialchars($data['penandatangan_2']['jabatan'] ?? 'Bendahara'); ?></p>
                <br><br><br>
                <p class="fw-bold mb-0"><u><?php echo htmlspecialchars($data['penandatangan_2']['nama_user'] ?? ''); ?></u></p>
            </div>
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
            document.getElementById('tanggal_selesai_1_export').value = document.getElementById('tanggal_selesai_1').value;
            document.getElementById('tanggal_selesai_2_export').value = document.getElementById('tanggal_selesai_2').value;
            document.getElementById('id_unit_export').value = document.getElementById('id_unit').value;
        }

        if (exportExcelBtn) {
            exportExcelBtn.addEventListener('click', function() {
                prepareExportData();
                form.action = "<?php echo BASEURL; ?>/laporan/eksporPosisiKeuangan";
                form.target = "_self";
                form.submit();
                form.action = "<?php echo BASEURL; ?>/laporan/posisiKeuangan";
            });
        }
        
        if (exportPdfBtn) {
            exportPdfBtn.addEventListener('click', function() {
                prepareExportData();
                form.action = "<?php echo BASEURL; ?>/laporan/eksporPdfPosisiKeuangan";
                form.target = "_blank";
                form.submit();
                form.action = "<?php echo BASEURL; ?>/laporan/posisiKeuangan";
                form.target = "_self";
            });
        }
    });
</script>