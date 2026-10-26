<?php
$akun_map = [];
if (!empty($data['akun'])) {
    foreach ($data['akun'] as $ak) {
        $akun_map[$ak['kode_akun']] = $ak['nama_akun'];
    }
}
$bio_missing_coa = 0;
if (!empty($data['aset'])) {
    foreach ($data['aset'] as $a) {
        if ($a['status'] == 'Aktif' && (empty($a['akun_aset']) || empty($a['akun_keuntungan_fv']) || empty($a['akun_kerugian_fv']))) {
            $bio_missing_coa++;
        }
    }
}
?>

<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <h2 class="fw-bold mb-0">Manajemen Aset Biologis (PSAK 241)</h2>
        <p class="text-muted small mb-0">Kelola inventaris biologis, setting parameter COA, dan penyesuaian nilai wajar (adjustment).</p>
    </div>
    <div class="d-flex gap-2">
        <button type="button" class="btn btn-outline-primary rounded-pill px-4" data-bs-toggle="modal" data-bs-target="#modalPenyesuaian">
            <i class="bi bi-graph-up-arrow me-2"></i>Penyesuaian Nilai Wajar
        </button>
        <a href="<?php echo BASEURL; ?>/asetbiologis/laporan" class="btn btn-outline-secondary rounded-pill px-4">
            <i class="bi bi-file-earmark-text me-2"></i>Laporan
        </a>
        <a href="<?php echo BASEURL; ?>/asetbiologis/tambah" class="btn btn-primary rounded-pill px-4">
            <i class="bi bi-plus-circle me-2"></i>Tambah Aset
        </a>
    </div>
</div>

<?php if ($bio_missing_coa > 0): ?>
<div class="alert alert-warning border-0 shadow-sm d-flex align-items-center mb-4 rounded-3">
    <i class="bi bi-exclamation-triangle-fill fs-3 me-3 text-warning"></i>
    <div class="flex-grow-1">
        <h6 class="fw-bold mb-1">Perhatian: Setting Parameter COA Wajib Dilengkapi</h6>
        <div class="small">
            Terdapat <strong><?php echo $bio_missing_coa; ?> aset biologis aktif</strong> yang parameter COA-nya (Akun Aset Biologis, Akun Keuntungan Nilai Wajar, atau Akun Kerugian Nilai Wajar) belum diatur oleh pengguna. 
            Klik tombol <span class="badge bg-warning text-dark"><i class="bi bi-gear-fill me-1"></i>Setting COA</span> pada baris aset untuk melengkapi pemetaan akun agar fitur penyesuaian nilai wajar (adjustment) dapat diproses.
        </div>
    </div>
</div>
<?php endif; ?>

<div class="row g-3 mb-4">
    <div class="col-md-3">
        <div class="card border-0 shadow-sm">
            <div class="card-body">
                <h6 class="text-muted mb-2">Total Aset Aktif</h6>
                <h3 class="fw-bold mb-0"><?php echo count(array_filter($data['aset'], fn($a) => $a['status'] == 'Aktif')); ?></h3>
            </div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card border-0 shadow-sm">
            <div class="card-body">
                <h6 class="text-muted mb-2">Total Nilai Tercatat</h6>
                <h3 class="fw-bold mb-0">Rp <?php 
                    $total_nilai = array_sum(array_column(array_filter($data['aset'], fn($a) => $a['status'] == 'Aktif'), 'nilai_tercatat'));
                    echo number_format($total_nilai, 2, ',', '.'); 
                ?></h3>
            </div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card border-0 shadow-sm">
            <div class="card-body">
                <h6 class="text-muted mb-2">Hewan</h6>
                <h3 class="fw-bold mb-0"><?php echo count(array_filter($data['aset'], fn($a) => in_array($a['kategori'], ['Hewan Konsumsi', 'Hewan Produktif']) && $a['status'] == 'Aktif')); ?></h3>
            </div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card border-0 shadow-sm">
            <div class="card-body">
                <h6 class="text-muted mb-2">Tanaman</h6>
                <h3 class="fw-bold mb-0"><?php echo count(array_filter($data['aset'], fn($a) => in_array($a['kategori'], ['Tanaman Konsumsi', 'Tanaman Produktif', 'Hasil pada Tanaman']) && $a['status'] == 'Aktif')); ?></h3>
            </div>
        </div>
    </div>
</div>

<div class="card border-0 shadow-sm overflow-hidden mb-4">
    <div class="card-header bg-white py-3 border-0 d-flex justify-content-between align-items-center">
        <div class="d-flex gap-2">
            <select class="form-select form-select-sm w-auto rounded-pill" id="filterKategori">
                <option value="">Semua Kategori</option>
                <option value="Hewan Konsumsi">Hewan Konsumsi</option>
                <option value="Hewan Produktif">Hewan Produktif</option>
                <option value="Tanaman Konsumsi">Tanaman Konsumsi</option>
                <option value="Tanaman Produktif">Tanaman Produktif</option>
                <option value="Hasil pada Tanaman">Hasil pada Tanaman</option>
            </select>
            <select class="form-select form-select-sm w-auto rounded-pill" id="filterStatus">
                <option value="">Semua Status</option>
                <option value="Aktif">Aktif</option>
                <option value="Dipanen">Dipanen</option>
                <option value="Dijual">Dijual</option>
                <option value="Mati/Hapus">Mati/Hapus</option>
            </select>
        </div>
        <div class="d-flex gap-2">
            <button type="button" class="btn btn-sm btn-outline-success rounded-pill px-3" data-bs-toggle="modal" data-bs-target="#modalPanen">
                <i class="bi bi-flower1 me-1"></i>Panen
            </button>
            <button type="button" class="btn btn-sm btn-outline-warning rounded-pill px-3" data-bs-toggle="modal" data-bs-target="#modalPelepasan">
                <i class="bi bi-box-arrow-right me-1"></i>Pelepasan
            </button>
        </div>
    </div>
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0" id="tableAset">
                <thead class="bg-light">
                    <tr>
                        <th class="ps-4">Kode</th>
                        <th>Nama Aset</th>
                        <th>Kategori</th>
                        <th>Kematangan</th>
                        <th class="text-end">Kuantitas</th>
                        <th class="text-end">Nilai Tercatat</th>
                        <th>Parameter COA (Wajib)</th>
                        <th class="text-center">Status</th>
                        <th class="text-center pe-4">Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($data['aset'])): ?>
                    <tr>
                        <td colspan="9" class="text-center py-5 text-muted">Belum ada data aset biologis.</td>
                    </tr>
                    <?php endif; ?>
                    <?php foreach ($data['aset'] as $row): 
                        $has_complete_coa = (!empty($row['akun_aset']) && !empty($row['akun_keuntungan_fv']) && !empty($row['akun_kerugian_fv']));
                    ?>
                    <tr data-kategori="<?php echo htmlspecialchars($row['kategori']); ?>" data-status="<?php echo htmlspecialchars($row['status']); ?>">
                        <td class="ps-4 fw-medium text-primary"><?php echo htmlspecialchars($row['kode_aset']); ?></td>
                        <td>
                            <div class="fw-bold"><?php echo htmlspecialchars($row['nama_aset']); ?></div>
                        </td>
                        <td>
                            <?php 
                                $cat_color = match($row['kategori']) {
                                    'Hewan Konsumsi' => 'primary',
                                    'Hewan Produktif' => 'info',
                                    'Tanaman Konsumsi' => 'success',
                                    'Tanaman Produktif' => 'warning',
                                    'Hasil pada Tanaman' => 'secondary',
                                    default => 'dark'
                                };
                            ?>
                            <span class="badge bg-<?php echo $cat_color; ?>-subtle text-<?php echo $cat_color; ?>"><?php echo htmlspecialchars($row['kategori']); ?></span>
                        </td>
                        <td>
                            <?php if ($row['status_kematangan'] == 'Menghasilkan'): ?>
                                <span class="badge bg-success-subtle text-success">Menghasilkan</span>
                            <?php else: ?>
                                <span class="badge bg-warning-subtle text-warning">Belum Menghasilkan</span>
                            <?php endif; ?>
                        </td>
                        <td class="text-end"><?php echo format_kuantitas($row['kuantitas'], $row['satuan']); ?></td>
                        <td class="text-end fw-bold">Rp <?php echo number_format($row['nilai_tercatat'], 2, ',', '.'); ?></td>
                        <td>
                            <?php if ($has_complete_coa): ?>
                                <span class="badge bg-success-subtle text-success border border-success-subtle mb-1">
                                    <i class="bi bi-check-circle me-1"></i>COA Lengkap
                                </span>
                                <div class="text-muted" style="font-size: 0.76rem; line-height: 1.3;">
                                    <div><strong>Aset:</strong> <code><?php echo htmlspecialchars($row['akun_aset']); ?></code> <?php echo htmlspecialchars($akun_map[$row['akun_aset']] ?? ''); ?></div>
                                    <div><strong>Untung FV:</strong> <code><?php echo htmlspecialchars($row['akun_keuntungan_fv']); ?></code> <?php echo htmlspecialchars($akun_map[$row['akun_keuntungan_fv']] ?? ''); ?></div>
                                    <div><strong>Rugi FV:</strong> <code><?php echo htmlspecialchars($row['akun_kerugian_fv']); ?></code> <?php echo htmlspecialchars($akun_map[$row['akun_kerugian_fv']] ?? ''); ?></div>
                                </div>
                            <?php else: ?>
                                <span class="badge bg-danger-subtle text-danger border border-danger-subtle mb-1">
                                    <i class="bi bi-exclamation-triangle-fill me-1"></i>Belum Lengkap
                                </span>
                                <div class="text-danger small fw-bold" style="font-size: 0.75rem;">
                                    Wajib disetting oleh pengguna
                                </div>
                            <?php endif; ?>
                        </td>
                        <td class="text-center">
                            <?php 
                                $status_color = match($row['status']) {
                                    'Aktif' => 'success',
                                    'Dipanen' => 'info',
                                    'Dijual' => 'warning',
                                    'Mati/Hapus' => 'danger',
                                    default => 'secondary'
                                };
                            ?>
                            <span class="badge rounded-pill bg-<?php echo $status_color; ?>-subtle text-<?php echo $status_color; ?>">
                                <?php echo htmlspecialchars($row['status']); ?>
                            </span>
                        </td>
                        <td class="text-center pe-4">
                            <div class="btn-group">
                                <button type="button" class="btn btn-sm btn-outline-warning rounded-start-pill px-3" data-bs-toggle="modal" data-bs-target="#modalCoaBio<?php echo $row['id']; ?>" title="Setting Parameter COA">
                                    <i class="bi bi-gear-fill me-1"></i>Setting COA
                                </button>
                                <a href="<?php echo BASEURL; ?>/asetbiologis/detail/<?php echo $row['id']; ?>" class="btn btn-sm btn-outline-info px-3" title="Detail">
                                    <i class="bi bi-eye"></i>
                                </a>
                                <a href="<?php echo BASEURL; ?>/asetbiologis/edit/<?php echo $row['id']; ?>" class="btn btn-sm btn-outline-primary px-3" title="Edit">
                                    <i class="bi bi-pencil"></i>
                                </a>
                                <a href="<?php echo BASEURL; ?>/asetbiologis/hapus/<?php echo $row['id']; ?>" class="btn btn-sm btn-outline-danger rounded-end-pill px-3" onclick="return confirm('Apakah Anda yakin ingin menghapus aset ini?')" title="Hapus">
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

<!-- Modal Setting Parameter COA untuk Setiap Aset Biologis -->
<?php if (!empty($data['aset'])): foreach ($data['aset'] as $row): ?>
<div class="modal fade" id="modalCoaBio<?php echo $row['id']; ?>" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-centered">
        <div class="modal-content border-0 shadow">
            <div class="modal-header">
                <div>
                    <h5 class="modal-title fw-bold">Setting Parameter COA Aset Biologis</h5>
                    <small class="text-muted"><?php echo htmlspecialchars($row['kode_aset'] . ' - ' . $row['nama_aset']); ?></small>
                </div>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form action="<?php echo BASEURL; ?>/asetbiologis/settingCoa" method="post">
                <input type="hidden" name="id_aset" value="<?php echo $row['id']; ?>">
                <div class="modal-body py-4">
                    <div class="alert alert-info py-2 px-3 small border-0 mb-4">
                        <i class="bi bi-info-circle me-1"></i> Parameter COA di bawah ini <strong>wajib diatur oleh pengguna</strong> untuk kebutuhan pencatatan akuntansi dan penyesuaian nilai wajar (adjustment) sesuai PSAK 241.
                    </div>
                    
                    <h6 class="fw-bold mb-3 text-primary"><i class="bi bi-asterisk me-1 text-danger"></i>Parameter COA Utama (Mandatory)</h6>
                    <div class="row g-3 mb-4">
                        <div class="col-md-4">
                            <label class="form-label fw-bold small">Akun Aset Biologis (Neraca) <span class="text-danger">* (Wajib)</span></label>
                            <select name="akun_aset" class="form-select shadow-none" required>
                                <option value="">-- Pilih Akun Aset --</option>
                                <?php foreach($data['akun'] as $ak): if(substr($ak['kode_akun'], 0, 1) == '1' && $ak['tipe_akun'] == 'Detail'): ?>
                                <option value="<?php echo $ak['kode_akun']; ?>" <?php echo $ak['kode_akun'] == $row['akun_aset'] ? 'selected' : ''; ?>>
                                    [<?php echo $ak['kode_akun']; ?>] <?php echo htmlspecialchars($ak['nama_akun']); ?>
                                </option>
                                <?php endif; endforeach; ?>
                            </select>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label fw-bold small">Akun Keuntungan Nilai Wajar <span class="text-danger">* (Wajib)</span></label>
                            <select name="akun_keuntungan_fv" class="form-select shadow-none" required>
                                <option value="">-- Pilih Akun Keuntungan --</option>
                                <?php foreach($data['akun'] as $ak): if(in_array(substr($ak['kode_akun'], 0, 1), ['4', '7']) && $ak['tipe_akun'] == 'Detail'): ?>
                                <option value="<?php echo $ak['kode_akun']; ?>" <?php echo $ak['kode_akun'] == $row['akun_keuntungan_fv'] ? 'selected' : ''; ?>>
                                    [<?php echo $ak['kode_akun']; ?>] <?php echo htmlspecialchars($ak['nama_akun']); ?>
                                </option>
                                <?php endif; endforeach; ?>
                            </select>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label fw-bold small">Akun Kerugian Nilai Wajar <span class="text-danger">* (Wajib)</span></label>
                            <select name="akun_kerugian_fv" class="form-select shadow-none" required>
                                <option value="">-- Pilih Akun Kerugian --</option>
                                <?php foreach($data['akun'] as $ak): if(in_array(substr($ak['kode_akun'], 0, 1), ['5', '6', '7', '8']) && $ak['tipe_akun'] == 'Detail'): ?>
                                <option value="<?php echo $ak['kode_akun']; ?>" <?php echo $ak['kode_akun'] == $row['akun_kerugian_fv'] ? 'selected' : ''; ?>>
                                    [<?php echo $ak['kode_akun']; ?>] <?php echo htmlspecialchars($ak['nama_akun']); ?>
                                </option>
                                <?php endif; endforeach; ?>
                            </select>
                        </div>
                    </div>

                    <h6 class="fw-bold mb-3 text-secondary"><i class="bi bi-gear me-1"></i>Parameter COA Operasional & Panen</h6>
                    <div class="row g-3">
                        <div class="col-md-4">
                            <label class="form-label fw-bold small">Akun Beban Pemeliharaan</label>
                            <select name="akun_beban_pemeliharaan" class="form-select shadow-none">
                                <option value="">-- Pilih Akun --</option>
                                <?php foreach($data['akun'] as $ak): if(in_array(substr($ak['kode_akun'], 0, 1), ['5', '6', '7']) && $ak['tipe_akun'] == 'Detail'): ?>
                                <option value="<?php echo $ak['kode_akun']; ?>" <?php echo $ak['kode_akun'] == $row['akun_beban_pemeliharaan'] ? 'selected' : ''; ?>>
                                    [<?php echo $ak['kode_akun']; ?>] <?php echo htmlspecialchars($ak['nama_akun']); ?>
                                </option>
                                <?php endif; endforeach; ?>
                            </select>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label fw-bold small">Akun Hasil Panen (Persediaan)</label>
                            <select name="akun_hasil_panen" class="form-select shadow-none">
                                <option value="">-- Pilih Akun --</option>
                                <?php foreach($data['akun'] as $ak): if(substr($ak['kode_akun'], 0, 1) == '1' && $ak['tipe_akun'] == 'Detail'): ?>
                                <option value="<?php echo $ak['kode_akun']; ?>" <?php echo $ak['kode_akun'] == $row['akun_hasil_panen'] ? 'selected' : ''; ?>>
                                    [<?php echo $ak['kode_akun']; ?>] <?php echo htmlspecialchars($ak['nama_akun']); ?>
                                </option>
                                <?php endif; endforeach; ?>
                            </select>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label fw-bold small">Akun Keuntungan Panen</label>
                            <select name="akun_keuntungan_panen" class="form-select shadow-none">
                                <option value="">-- Pilih Akun --</option>
                                <?php foreach($data['akun'] as $ak): if(in_array(substr($ak['kode_akun'], 0, 1), ['4', '7']) && $ak['tipe_akun'] == 'Detail'): ?>
                                <option value="<?php echo $ak['kode_akun']; ?>" <?php echo $ak['kode_akun'] == $row['akun_keuntungan_panen'] ? 'selected' : ''; ?>>
                                    [<?php echo $ak['kode_akun']; ?>] <?php echo htmlspecialchars($ak['nama_akun']); ?>
                                </option>
                                <?php endif; endforeach; ?>
                            </select>
                        </div>
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

<!-- Modal Penyesuaian Nilai Wajar -->
<div class="modal fade" id="modalPenyesuaian" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-xl modal-dialog-centered">
        <div class="modal-content border-0 shadow">
            <div class="modal-header">
                <h5 class="modal-title fw-bold">Penyesuaian Nilai Wajar Aset Biologis (Adjustment)</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form action="<?php echo BASEURL; ?>/asetbiologis/sesuaikan_batch" method="post">
                <div class="modal-body py-4">
                    <div class="mb-3 w-25">
                        <label class="form-label fw-bold small">Tanggal Penyesuaian</label>
                        <input type="date" name="tanggal_penyesuaian" class="form-control rounded-3 shadow-none" value="<?php echo date('Y-m-d'); ?>" required>
                    </div>

                    <?php if ($bio_missing_coa > 0): ?>
                    <div class="alert alert-warning py-2 px-3 small border-0 mb-3">
                        <i class="bi bi-exclamation-triangle-fill me-1"></i> Perhatian: Terdapat <strong><?php echo $bio_missing_coa; ?> aset biologis</strong> dengan parameter COA belum diatur. Aset tanpa parameter COA lengkap <strong>tidak dapat disesuaikan</strong> sampai parameter COA dilengkapi oleh pengguna.
                    </div>
                    <?php endif; ?>

                    <div class="table-responsive" style="max-height: 400px;">
                        <table class="table table-bordered align-middle">
                            <thead class="bg-light sticky-top">
                                <tr>
                                    <th>Aset</th>
                                    <th>Kuantitas</th>
                                    <th class="text-end">Nilai Tercatat Lama</th>
                                    <th>Nilai Wajar Baru</th>
                                    <th>Biaya Jual Baru</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php 
                                foreach ($data['aset'] as $row): 
                                    if ($row['status'] == 'Aktif' && $row['metode_pengukuran'] == 'Nilai Wajar'): 
                                        $row_coa_ok = (!empty($row['akun_aset']) && !empty($row['akun_keuntungan_fv']) && !empty($row['akun_kerugian_fv']));
                                ?>
                                <tr>
                                    <td>
                                        <div class="fw-bold"><?php echo htmlspecialchars($row['nama_aset']); ?></div>
                                        <small class="text-muted"><?php echo htmlspecialchars($row['kode_aset']); ?></small>
                                        <?php if ($row_coa_ok): ?>
                                            <input type="hidden" name="penyesuaian[<?php echo $row['id']; ?>][id_aset]" value="<?php echo $row['id']; ?>">
                                        <?php else: ?>
                                            <div class="mt-1">
                                                <span class="badge bg-danger-subtle text-danger border border-danger-subtle small">Parameter COA Belum Diatur</span>
                                            </div>
                                        <?php endif; ?>
                                    </td>
                                    <td><?php echo htmlspecialchars($row['kuantitas'] . ' ' . $row['satuan']); ?></td>
                                    <td class="text-end">Rp <?php echo number_format($row['nilai_tercatat'], 2, ',', '.'); ?></td>
                                    <td>
                                        <?php if ($row_coa_ok): ?>
                                            <input type="number" step="0.01" name="penyesuaian[<?php echo $row['id']; ?>][nilai_wajar_baru]" class="form-control form-control-sm" placeholder="Opsional jika tidak berubah">
                                        <?php else: ?>
                                            <input type="text" class="form-control form-control-sm bg-light text-muted" value="COA Wajib Diatur" disabled>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <?php if ($row_coa_ok): ?>
                                            <input type="number" step="0.01" name="penyesuaian[<?php echo $row['id']; ?>][biaya_jual_baru]" class="form-control form-control-sm" placeholder="Opsional jika tidak berubah">
                                        <?php else: ?>
                                            <button type="button" class="btn btn-sm btn-outline-warning py-1 px-3 w-100" data-bs-dismiss="modal" data-bs-toggle="modal" data-bs-target="#modalCoaBio<?php echo $row['id']; ?>">
                                                <i class="bi bi-gear-fill me-1"></i>Setting COA Sekarang
                                            </button>
                                        <?php endif; ?>
                                    </td>
                                </tr>
                                <?php endif; endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
                <div class="modal-footer border-0">
                    <button type="button" class="btn btn-light rounded-pill px-4" data-bs-dismiss="modal">Batal</button>
                    <button type="submit" class="btn btn-primary rounded-pill px-4">Proses Penyesuaian</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Modal Panen -->
<div class="modal fade" id="modalPanen" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-centered">
        <div class="modal-content border-0 shadow">
            <div class="modal-header">
                <h5 class="modal-title fw-bold">Catat Panen / Agrikultur</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form action="<?php echo BASEURL; ?>/asetbiologis/panen" method="post">
                <div class="modal-body py-4">
                    <div class="row g-3 mb-3">
                        <div class="col-md-6">
                            <label class="form-label fw-bold small">Aset Sumber</label>
                            <select name="id_aset" class="form-select rounded-3 search-select shadow-none" required>
                                <option value="">-- Pilih Aset --</option>
                                <?php foreach ($data['aset'] as $row): if ($row['status'] == 'Aktif'): ?>
                                <option value="<?php echo $row['id']; ?>">[<?php echo htmlspecialchars($row['kode_aset']); ?>] <?php echo htmlspecialchars($row['nama_aset']); ?></option>
                                <?php endif; endforeach; ?>
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-bold small">Tanggal Panen</label>
                            <input type="date" name="tanggal" class="form-control rounded-3" value="<?php echo date('Y-m-d'); ?>" required>
                        </div>
                    </div>
                    <div class="row g-3 mb-3">
                        <div class="col-md-6">
                            <label class="form-label fw-bold small">Nama Hasil Panen</label>
                            <input type="text" name="nama_hasil" class="form-control rounded-3" required>
                        </div>
                        <div class="col-md-3">
                            <label class="form-label fw-bold small">Kuantitas</label>
                            <input type="number" step="0.01" name="kuantitas" class="form-control rounded-3" required>
                        </div>
                        <div class="col-md-3">
                            <label class="form-label fw-bold small">Satuan</label>
                            <input type="text" name="satuan" class="form-control rounded-3" required>
                        </div>
                    </div>
                    <div class="row g-3 mb-3">
                        <div class="col-md-6">
                            <label class="form-label fw-bold small">Nilai Wajar Saat Panen</label>
                            <div class="input-group">
                                <span class="input-group-text bg-light border-end-0">Rp</span>
                                <input type="number" step="0.01" name="nilai_wajar" class="form-control rounded-3 border-start-0" required>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-bold small">Estimasi Biaya Jual/Panen</label>
                            <div class="input-group">
                                <span class="input-group-text bg-light border-end-0">Rp</span>
                                <input type="number" step="0.01" name="biaya_panen" class="form-control rounded-3 border-start-0" required>
                            </div>
                        </div>
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-bold small">Akun Persediaan (Debit)</label>
                        <select name="akun_persediaan" class="form-select rounded-3 search-select shadow-none" required>
                            <option value="">-- Pilih Akun Persediaan --</option>
                            <?php foreach($data['akun'] as $row): if(substr($row['kode_akun'], 0, 1) == '1' && $row['tipe_akun'] == 'Detail'): ?>
                            <option value="<?php echo $row['kode_akun']; ?>">[<?php echo $row['kode_akun']; ?>] <?php echo htmlspecialchars($row['nama_akun']); ?></option>
                            <?php endif; endforeach; ?>
                        </select>
                    </div>
                </div>
                <div class="modal-footer border-0">
                    <button type="button" class="btn btn-light rounded-pill px-4" data-bs-dismiss="modal">Batal</button>
                    <button type="submit" class="btn btn-success rounded-pill px-4">Catat Panen</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Modal Pelepasan -->
<div class="modal fade" id="modalPelepasan" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0 shadow">
            <div class="modal-header">
                <h5 class="modal-title fw-bold">Pelepasan Aset (Jual / Mati)</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form action="<?php echo BASEURL; ?>/asetbiologis/pelepasan" method="post">
                <div class="modal-body py-4">
                    <div class="mb-3">
                        <label class="form-label fw-bold small">Aset</label>
                        <select name="id_aset" class="form-select rounded-3 search-select shadow-none" required>
                            <option value="">-- Pilih Aset --</option>
                            <?php foreach ($data['aset'] as $row): if ($row['status'] == 'Aktif'): ?>
                            <option value="<?php echo $row['id']; ?>">[<?php echo htmlspecialchars($row['kode_aset']); ?>] <?php echo htmlspecialchars($row['nama_aset']); ?> (Sisa: <?php echo htmlspecialchars($row['kuantitas'] . ' ' . $row['satuan']); ?>)</option>
                            <?php endif; endforeach; ?>
                        </select>
                    </div>
                    <div class="row g-3 mb-3">
                        <div class="col-md-6">
                            <label class="form-label fw-bold small">Tipe Pelepasan</label>
                            <select name="tipe_pelepasan" id="tipePelepasan" class="form-select rounded-3 shadow-none" required>
                                <option value="Penjualan">Penjualan</option>
                                <option value="Kematian">Kematian</option>
                                <option value="Hapus Buku">Hapus Buku</option>
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-bold small">Kuantitas Dilepas</label>
                            <input type="number" step="0.01" name="kuantitas" class="form-control rounded-3" required>
                        </div>
                    </div>
                    <div class="mb-3" id="divNilaiJual">
                        <label class="form-label fw-bold small">Total Nilai Jual</label>
                        <div class="input-group">
                            <span class="input-group-text bg-light border-end-0">Rp</span>
                            <input type="number" step="0.01" name="nilai_jual" class="form-control rounded-3 border-start-0">
                        </div>
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-bold small">Keterangan</label>
                        <textarea name="keterangan" class="form-control rounded-3" rows="2"></textarea>
                    </div>
                </div>
                <div class="modal-footer border-0">
                    <button type="button" class="btn btn-light rounded-pill px-4" data-bs-dismiss="modal">Batal</button>
                    <button type="submit" class="btn btn-warning rounded-pill px-4">Proses Pelepasan</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Tom Select init
    if (typeof TomSelect !== 'undefined') {
        document.querySelectorAll('.search-select').forEach(function(el) {
            new TomSelect(el, {
                create: false,
                sortField: { field: "text", direction: "asc" }
            });
        });
    }

    // Filtering logic
    const filterKat = document.getElementById('filterKategori');
    const filterStat = document.getElementById('filterStatus');
    const rows = document.querySelectorAll('#tableAset tbody tr[data-kategori]');

    function applyFilters() {
        const kat = filterKat ? filterKat.value : '';
        const stat = filterStat ? filterStat.value : '';

        rows.forEach(row => {
            const rowKat = row.getAttribute('data-kategori');
            const rowStat = row.getAttribute('data-status');
            const matchKat = kat === '' || rowKat === kat;
            const matchStat = stat === '' || rowStat === stat;
            row.style.display = matchKat && matchStat ? '' : 'none';
        });
    }

    if (filterKat) filterKat.addEventListener('change', applyFilters);
    if (filterStat) filterStat.addEventListener('change', applyFilters);

    // Pelepasan logic
    const tipePelepasan = document.getElementById('tipePelepasan');
    const divNilaiJual = document.getElementById('divNilaiJual');
    if (tipePelepasan && divNilaiJual) {
        tipePelepasan.addEventListener('change', function() {
            if (this.value === 'Penjualan') {
                divNilaiJual.style.display = 'block';
                divNilaiJual.querySelector('input').required = true;
            } else {
                divNilaiJual.style.display = 'none';
                divNilaiJual.querySelector('input').required = false;
            }
        });
    }
});
</script>
