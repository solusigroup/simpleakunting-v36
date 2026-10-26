<?php
$akun_map = [];
if (!empty($data['akun'])) {
    foreach ($data['akun'] as $ak) {
        $akun_map[$ak['kode_akun']] = $ak['nama_akun'];
    }
}
$aset_missing_coa = 0;
if (!empty($data['aset'])) {
    foreach ($data['aset'] as $a) {
        if ($a['status'] == 'Aktif' && (empty($a['akun_aset']) || empty($a['akun_akumulasi']) || empty($a['akun_beban']))) {
            $aset_missing_coa++;
        }
    }
}
?>

<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <h2 class="fw-bold mb-0">Manajemen Aset Tetap</h2>
        <p class="text-muted small mb-0">Kelola daftar aset tetap, setting parameter COA, dan proses penyusutan otomatis.</p>
    </div>
    <div class="d-flex gap-2">
        <button type="button" class="btn btn-outline-primary rounded-pill px-4" data-bs-toggle="modal" data-bs-target="#modalPenyusutan">
            <i class="bi bi-calculator me-2"></i>Jalankan Penyusutan
        </button>
        <a href="<?php echo BASEURL; ?>/aset/tambah" class="btn btn-primary rounded-pill px-4">
            <i class="bi bi-plus-circle me-2"></i>Tambah Aset
        </a>
    </div>
</div>

<?php if ($aset_missing_coa > 0): ?>
<div class="alert alert-warning border-0 shadow-sm d-flex align-items-center mb-4 rounded-3">
    <i class="bi bi-exclamation-triangle-fill fs-3 me-3 text-warning"></i>
    <div class="flex-grow-1">
        <h6 class="fw-bold mb-1">Perhatian: Setting Parameter COA Wajib Dilengkapi</h6>
        <div class="small">
            Terdapat <strong><?php echo $aset_missing_coa; ?> aset aktif</strong> yang parameter COA-nya (Akun Aset, Akun Akumulasi, atau Akun Beban) belum diatur oleh pengguna. 
            Klik tombol <span class="badge bg-warning text-dark"><i class="bi bi-gear-fill me-1"></i>Setting COA</span> pada baris aset untuk melengkapi pemetaan akun agar jurnal penyusutan dapat dihasilkan otomatis.
        </div>
    </div>
</div>
<?php endif; ?>

<div class="card border-0 shadow-sm overflow-hidden">
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
                <thead class="bg-light">
                    <tr>
                        <th class="ps-4">Kode</th>
                        <th>Nama Aset</th>
                        <th>Tanggal Perolehan</th>
                        <th class="text-end">Harga Perolehan</th>
                        <th class="text-center">Umur (Bulan)</th>
                        <th>Parameter COA (Wajib)</th>
                        <th>Status</th>
                        <th class="text-center pe-4">Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($data['aset'])): ?>
                    <tr>
                        <td colspan="8" class="text-center py-5 text-muted">Belum ada data aset tetap.</td>
                    </tr>
                    <?php endif; ?>
                    <?php foreach ($data['aset'] as $row): 
                        $has_complete_coa = (!empty($row['akun_aset']) && !empty($row['akun_akumulasi']) && !empty($row['akun_beban']));
                    ?>
                    <tr>
                        <td class="ps-4 fw-medium text-primary"><?php echo htmlspecialchars($row['kode_aset']); ?></td>
                        <td>
                            <div class="fw-bold"><?php echo htmlspecialchars($row['nama_aset']); ?></div>
                            <small class="text-muted"><?php echo htmlspecialchars($row['metode_penyusutan']); ?></small>
                        </td>
                        <td><?php echo date('d/m/Y', strtotime($row['tanggal_perolehan'])); ?></td>
                        <td class="text-end fw-bold">Rp <?php echo number_format($row['harga_perolehan'], 2, ',', '.'); ?></td>
                        <td class="text-center"><?php echo $row['umur_ekonomis']; ?></td>
                        <td>
                            <?php if ($has_complete_coa): ?>
                                <span class="badge bg-success-subtle text-success border border-success-subtle mb-1">
                                    <i class="bi bi-check-circle me-1"></i>COA Lengkap
                                </span>
                                <div class="text-muted" style="font-size: 0.76rem; line-height: 1.3;">
                                    <div><strong>Aset:</strong> <code><?php echo htmlspecialchars($row['akun_aset']); ?></code> <?php echo htmlspecialchars($akun_map[$row['akun_aset']] ?? ''); ?></div>
                                    <div><strong>Akum:</strong> <code><?php echo htmlspecialchars($row['akun_akumulasi']); ?></code> <?php echo htmlspecialchars($akun_map[$row['akun_akumulasi']] ?? ''); ?></div>
                                    <div><strong>Beban:</strong> <code><?php echo htmlspecialchars($row['akun_beban']); ?></code> <?php echo htmlspecialchars($akun_map[$row['akun_beban']] ?? ''); ?></div>
                                </div>
                            <?php else: ?>
                                <span class="badge bg-danger-subtle text-danger border border-danger-subtle mb-1">
                                    <i class="bi bi-exclamation-triangle-fill me-1"></i>Belum Diatur
                                </span>
                                <div class="text-danger small fw-bold" style="font-size: 0.75rem;">
                                    Wajib disetting oleh pengguna
                                </div>
                            <?php endif; ?>
                        </td>
                        <td>
                            <span class="badge rounded-pill <?php 
                                echo $row['status'] == 'Aktif' ? 'bg-success-subtle text-success' : 'bg-secondary-subtle text-secondary'; 
                            ?>">
                                <?php echo htmlspecialchars($row['status']); ?>
                            </span>
                        </td>
                        <td class="text-center pe-4">
                            <div class="btn-group">
                                <button type="button" class="btn btn-sm btn-outline-warning rounded-start-pill px-3" data-bs-toggle="modal" data-bs-target="#modalCoaAset<?php echo $row['id']; ?>" title="Setting Parameter COA">
                                    <i class="bi bi-gear-fill me-1"></i>Setting COA
                                </button>
                                <a href="<?php echo BASEURL; ?>/aset/edit/<?php echo $row['id']; ?>" class="btn btn-sm btn-outline-primary px-3" title="Edit Aset">
                                    <i class="bi bi-pencil"></i>
                                </a>
                                <a href="<?php echo BASEURL; ?>/aset/hapus/<?php echo $row['id']; ?>" class="btn btn-sm btn-outline-danger rounded-end-pill px-3" onclick="return confirm('Apakah Anda yakin ingin menghapus aset ini?')" title="Hapus Aset">
                                    <i class="bi bi-trash"></i>
                                </a>
                            </div>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<!-- Modal Setting Parameter COA untuk Setiap Aset -->
<?php if (!empty($data['aset'])): foreach ($data['aset'] as $row): ?>
<div class="modal fade" id="modalCoaAset<?php echo $row['id']; ?>" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0 shadow">
            <div class="modal-header">
                <div>
                    <h5 class="modal-title fw-bold">Setting Parameter COA Aset</h5>
                    <small class="text-muted"><?php echo htmlspecialchars($row['kode_aset'] . ' - ' . $row['nama_aset']); ?></small>
                </div>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form action="<?php echo BASEURL; ?>/aset/settingCoa" method="post">
                <input type="hidden" name="id_aset" value="<?php echo $row['id']; ?>">
                <div class="modal-body py-4">
                    <div class="alert alert-info py-2 px-3 small border-0 mb-3">
                        <i class="bi bi-info-circle me-1"></i> Parameter COA di bawah ini <strong>wajib diatur oleh pengguna</strong> untuk menjamin akurasi jurnal penyusutan bulanan otomatis.
                    </div>
                    
                    <div class="mb-3">
                        <label class="form-label fw-bold small">Akun Aset (Neraca) <span class="text-danger">* (Wajib)</span></label>
                        <select name="akun_aset" class="form-select shadow-none" required>
                            <option value="">-- Pilih Akun Aset --</option>
                            <?php foreach($data['akun'] as $ak): if(substr($ak['kode_akun'], 0, 1) == '1' && $ak['tipe_akun'] == 'Detail'): ?>
                            <option value="<?php echo $ak['kode_akun']; ?>" <?php echo $ak['kode_akun'] == $row['akun_aset'] ? 'selected' : ''; ?>>
                                [<?php echo $ak['kode_akun']; ?>] <?php echo htmlspecialchars($ak['nama_akun']); ?>
                            </option>
                            <?php endif; endforeach; ?>
                        </select>
                    </div>

                    <div class="mb-3">
                        <label class="form-label fw-bold small">Akun Akumulasi Penyusutan (Neraca/Kontra Aset) <span class="text-danger">* (Wajib)</span></label>
                        <select name="akun_akumulasi" class="form-select shadow-none" required>
                            <option value="">-- Pilih Akun Akumulasi --</option>
                            <?php foreach($data['akun'] as $ak): if(substr($ak['kode_akun'], 0, 1) == '1' && $ak['tipe_akun'] == 'Detail'): ?>
                            <option value="<?php echo $ak['kode_akun']; ?>" <?php echo $ak['kode_akun'] == $row['akun_akumulasi'] ? 'selected' : ''; ?>>
                                [<?php echo $ak['kode_akun']; ?>] <?php echo htmlspecialchars($ak['nama_akun']); ?>
                            </option>
                            <?php endif; endforeach; ?>
                        </select>
                    </div>

                    <div class="mb-3">
                        <label class="form-label fw-bold small">Akun Beban Penyusutan (Laba Rugi) <span class="text-danger">* (Wajib)</span></label>
                        <select name="akun_beban" class="form-select shadow-none" required>
                            <option value="">-- Pilih Akun Beban --</option>
                            <?php foreach($data['akun'] as $ak): if(in_array(substr($ak['kode_akun'], 0, 1), ['5', '6', '7']) && $ak['tipe_akun'] == 'Detail'): ?>
                            <option value="<?php echo $ak['kode_akun']; ?>" <?php echo $ak['kode_akun'] == $row['akun_beban'] ? 'selected' : ''; ?>>
                                [<?php echo $ak['kode_akun']; ?>] <?php echo htmlspecialchars($ak['nama_akun']); ?>
                            </option>
                            <?php endif; endforeach; ?>
                        </select>
                    </div>
                </div>
                <div class="modal-footer border-0 pt-0">
                    <button type="button" class="btn btn-light rounded-pill px-4" data-bs-dismiss="modal">Batal</button>
                    <button type="submit" class="btn btn-primary rounded-pill px-4">Simpan Parameter COA</button>
                </div>
            </form>
        </div>
    </div>
</div>
<?php endforeach; endif; ?>

<!-- Modal Penyusutan -->
<div class="modal fade" id="modalPenyusutan" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0 shadow">
            <div class="modal-header">
                <h5 class="modal-title fw-bold">Proses Penyusutan Bulanan</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form action="<?php echo BASEURL; ?>/aset/susutkan" method="post">
                <div class="modal-body py-4">
                    <p class="text-muted small mb-3">Sistem akan menghitung penyusutan untuk semua aset aktif yang parameter COA-nya sudah lengkap dan membuat jurnal otomatis di akhir bulan yang dipilih.</p>
                    
                    <?php if ($aset_missing_coa > 0): ?>
                    <div class="alert alert-warning py-2 px-3 small border-0 mb-4">
                        <i class="bi bi-exclamation-triangle-fill me-1"></i> Perhatian: Terdapat <strong><?php echo $aset_missing_coa; ?> aset</strong> dengan parameter COA belum diatur. Aset tersebut akan dilewati dalam proses penyusutan sampai parameter COA dilengkapi.
                    </div>
                    <?php endif; ?>

                    <div class="row g-3">
                        <div class="col-6">
                            <label class="form-label small fw-bold">Pilih Bulan</label>
                            <select name="bulan" class="form-select shadow-none">
                                <?php 
                                $months = ['Januari', 'Februari', 'Maret', 'April', 'Mei', 'Juni', 'Juli', 'Agustus', 'September', 'Oktober', 'November', 'Desember'];
                                foreach ($months as $i => $m): 
                                ?>
                                <option value="<?php echo sprintf('%02d', $i+1); ?>" <?php echo date('m') == ($i+1) ? 'selected' : ''; ?>>
                                    <?php echo $m; ?>
                                </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-6">
                            <label class="form-label small fw-bold">Pilih Tahun</label>
                            <select name="tahun" class="form-select shadow-none">
                                <?php for($y = date('Y')-2; $y <= date('Y')+1; $y++): ?>
                                <option value="<?php echo $y; ?>" <?php echo date('Y') == $y ? 'selected' : ''; ?>><?php echo $y; ?></option>
                                <?php endfor; ?>
                            </select>
                        </div>
                    </div>
                </div>
                <div class="modal-footer border-0">
                    <button type="button" class="btn btn-light rounded-pill px-4" data-bs-dismiss="modal">Batal</button>
                    <button type="submit" class="btn btn-primary rounded-pill px-4">Proses Sekarang</button>
                </div>
            </form>
        </div>
    </div>
</div>
