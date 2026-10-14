<?php $k = $data['kelompok']; ?>

<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <nav aria-label="breadcrumb">
            <ol class="breadcrumb mb-1 small">
                <li class="breadcrumb-item"><a href="<?php echo BASEURL; ?>/simpanpinjam">Simpan Pinjam</a></li>
                <li class="breadcrumb-item"><a href="<?php echo BASEURL; ?>/simpanpinjam/nasabah">Nasabah & Kelompok</a></li>
                <li class="breadcrumb-item active"><?php echo htmlspecialchars($k['nama_kelompok']); ?></li>
            </ol>
        </nav>
        <h2 class="fw-bold mb-0">Kelompok: <?php echo htmlspecialchars($k['nama_kelompok']); ?></h2>
    </div>
    <div class="d-flex gap-2">
        <button type="button" class="btn btn-primary rounded-pill px-4" data-bs-toggle="modal" data-bs-target="#modalTambahAnggota">
            <i class="bi bi-person-plus-fill me-1"></i> Tambah Anggota
        </button>
        <a href="<?php echo BASEURL; ?>/simpanpinjam/nasabah" class="btn btn-light rounded-pill px-3">
            <i class="bi bi-arrow-left me-1"></i> Kembali
        </a>
    </div>
</div>

<div class="row g-4 mb-4">
    <div class="col-md-4">
        <div class="card border-0 shadow-sm rounded-4 h-100 p-4">
            <h6 class="fw-bold text-muted small text-uppercase mb-3">Informasi Kelompok</h6>
            <table class="table table-sm table-borderless small mb-0">
                <tr>
                    <td class="text-muted" style="width: 120px;">Kode Kelompok</td>
                    <td class="fw-bold text-primary"><?php echo htmlspecialchars($k['kode_kelompok']); ?></td>
                </tr>
                <tr>
                    <td class="text-muted">Ketua Kelompok</td>
                    <td class="fw-bold"><?php echo htmlspecialchars($k['nama_ketua'] ?? '-'); ?></td>
                </tr>
                <tr>
                    <td class="text-muted">Kontak HP</td>
                    <td><?php echo htmlspecialchars($k['kontak'] ?? '-'); ?></td>
                </tr>
                <tr>
                    <td class="text-muted">Wilayah / Alamat</td>
                    <td><?php echo htmlspecialchars($k['alamat_wilayah'] ?? '-'); ?></td>
                </tr>
                <tr>
                    <td class="text-muted">Tgl Berdiri</td>
                    <td><?php echo !empty($k['tanggal_berdiri']) ? date('d/m/Y', strtotime($k['tanggal_berdiri'])) : '-'; ?></td>
                </tr>
                <tr>
                    <td class="text-muted">Status</td>
                    <td>
                        <span class="badge <?php echo ($k['status'] === 'Aktif') ? 'bg-success' : 'bg-secondary'; ?> rounded-pill">
                            <?php echo $k['status']; ?>
                        </span>
                    </td>
                </tr>
            </table>
        </div>
    </div>

    <div class="col-md-8">
        <div class="card border-0 shadow-sm rounded-4 overflow-hidden">
            <div class="card-header bg-white py-3 border-0 d-flex justify-content-between align-items-center">
                <h6 class="fw-bold mb-0">
                    <i class="bi bi-people-fill me-1 text-primary"></i> Daftar Anggota Kelompok 
                    <span class="badge bg-primary-subtle text-primary rounded-pill ms-2"><?php echo count($k['anggota'] ?? []); ?> Orang</span>
                </h6>
            </div>
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead class="table-light small">
                        <tr>
                            <th class="ps-3">No. Anggota</th>
                            <th>Nama Anggota</th>
                            <th>Peran di Kelompok</th>
                            <th>No. Telp</th>
                            <th>Status</th>
                            <th class="text-end pe-3">Aksi</th>
                        </tr>
                    </thead>
                    <tbody class="small">
                        <?php if (empty($k['anggota'])): ?>
                            <tr><td colspan="6" class="text-center py-4 text-muted">Belum ada anggota dalam kelompok ini. Klik tombol "Tambah Anggota" di atas.</td></tr>
                        <?php else: ?>
                            <?php foreach ($k['anggota'] as $agt): ?>
                            <tr>
                                <td class="ps-3 fw-bold"><?php echo htmlspecialchars($agt['no_anggota']); ?></td>
                                <td class="fw-bold"><?php echo htmlspecialchars($agt['nama_lengkap']); ?></td>
                                <td>
                                    <?php
                                    $roleBadge = match($agt['peran_kelompok']) {
                                        'Ketua' => 'bg-primary text-white',
                                        'Sekretaris' => 'bg-info text-white',
                                        'Bendahara' => 'bg-warning text-dark',
                                        default => 'bg-light text-muted border'
                                    };
                                    ?>
                                    <span class="badge <?php echo $roleBadge; ?> rounded-pill px-2">
                                        <?php echo $agt['peran_kelompok']; ?>
                                    </span>
                                </td>
                                <td><?php echo htmlspecialchars($agt['no_telp'] ?? '-'); ?></td>
                                <td>
                                    <span class="badge <?php echo ($agt['status'] === 'Aktif') ? 'bg-success' : 'bg-secondary'; ?> rounded-pill">
                                        <?php echo $agt['status']; ?>
                                    </span>
                                </td>
                                <td class="text-end pe-3">
                                    <a href="<?php echo BASEURL; ?>/simpanpinjam/kelompok_hapus_anggota/<?php echo $agt['id_nasabah']; ?>/<?php echo $k['id_kelompok']; ?>" 
                                       class="btn btn-sm btn-outline-danger rounded-pill px-2 py-0" 
                                       onclick="return confirm('Keluarkan anggota ini dari kelompok?')"
                                       title="Keluarkan dari Kelompok">
                                        <i class="bi bi-x-circle me-1"></i> Keluarkan
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
</div>

<!-- Modal Tambah Anggota ke Kelompok -->
<div class="modal fade" id="modalTambahAnggota" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0 shadow-lg rounded-4">
            <form action="<?php echo BASEURL; ?>/simpanpinjam/kelompok_tambah_anggota" method="POST">
                <input type="hidden" name="id_kelompok" value="<?php echo $k['id_kelompok']; ?>">
                <div class="modal-header bg-primary text-white border-0 py-3">
                    <h5 class="modal-title fw-bold">Tambah Anggota ke Kelompok</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body p-4">
                    <div class="mb-3">
                        <label class="form-label small fw-bold text-muted">PILIH NASABAH PERORANGAN</label>
                        <select name="id_nasabah" class="form-select" required>
                            <option value="">-- Pilih Nasabah (Belum Berkelompok) --</option>
                            <?php foreach ($data['nasabah_tersedia'] as $nt): ?>
                            <option value="<?php echo $nt['id_nasabah']; ?>">
                                <?php echo htmlspecialchars($nt['no_anggota'] . ' - ' . $nt['nama_lengkap'] . ' (' . ($nt['no_telp'] ?? '-') . ')'); ?>
                            </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="mb-0">
                        <label class="form-label small fw-bold text-muted">PERAN DALAM KELOMPOK</label>
                        <select name="peran_kelompok" class="form-select" required>
                            <option value="Anggota" selected>Anggota</option>
                            <option value="Ketua">Ketua</option>
                            <option value="Sekretaris">Sekretaris</option>
                            <option value="Bendahara">Bendahara</option>
                        </select>
                    </div>
                </div>
                <div class="modal-footer border-0 p-4 pt-0">
                    <button type="button" class="btn btn-light rounded-pill px-4" data-bs-dismiss="modal">Batal</button>
                    <button type="submit" class="btn btn-primary rounded-pill px-4 fw-bold">Tambahkan Anggota</button>
                </div>
            </form>
        </div>
    </div>
</div>
