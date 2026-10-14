<div class="d-flex flex-wrap justify-content-between align-items-center mb-4 gap-3">
    <div>
        <nav aria-label="breadcrumb">
            <ol class="breadcrumb mb-1 small">
                <li class="breadcrumb-item"><a href="<?php echo BASEURL; ?>/simpanpinjam">Simpan Pinjam</a></li>
                <li class="breadcrumb-item active">Nasabah & Kelompok</li>
            </ol>
        </nav>
        <h2 class="fw-bold mb-0">Manajemen Nasabah & Kelompok</h2>
    </div>
    <div class="d-flex gap-2">
        <button type="button" class="btn btn-outline-primary rounded-pill px-3" data-bs-toggle="modal" data-bs-target="#modalKelompok">
            <i class="bi bi-people me-1"></i> Tambah Kelompok
        </button>
        <button type="button" class="btn btn-primary rounded-pill px-4" data-bs-toggle="modal" data-bs-target="#modalNasabah">
            <i class="bi bi-person-plus-fill me-1"></i> Tambah Nasabah
        </button>
    </div>
</div>

<!-- Nav Tabs -->
<ul class="nav nav-pills mb-4 gap-2" id="nasabahTab" role="tablist">
    <li class="nav-item" role="presentation">
        <button class="nav-link active rounded-pill px-4" id="perorangan-tab" data-bs-toggle="tab" data-bs-target="#perorangan" type="button" role="tab">
            <i class="bi bi-person-fill me-1"></i> Nasabah Perorangan (<?php echo count($data['nasabah']); ?>)
        </button>
    </li>
    <li class="nav-item" role="presentation">
        <button class="nav-link rounded-pill px-4" id="kelompok-tab" data-bs-toggle="tab" data-bs-target="#kelompok" type="button" role="tab">
            <i class="bi bi-diagram-3-fill me-1"></i> Kelompok Nasabah (<?php echo count($data['kelompok']); ?>)
        </button>
    </li>
</ul>

<div class="tab-content" id="nasabahTabContent">
    <!-- TAB 1: NASABAH PERORANGAN -->
    <div class="tab-pane fade show active" id="perorangan" role="tabpanel">
        <div class="card border-0 shadow-sm rounded-4 overflow-hidden">
            <div class="card-header bg-white py-3 border-0 d-flex justify-content-between align-items-center">
                <h6 class="fw-bold mb-0"><i class="bi bi-list-ul me-1 text-primary"></i> Daftar Nasabah Perorangan</h6>
                <input type="text" id="cariNasabah" class="form-control form-control-sm rounded-pill w-auto" placeholder="Cari nama, NIK, no anggota...">
            </div>
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0" id="tabelNasabah">
                    <thead class="table-light small">
                        <tr>
                            <th class="ps-3">No. Anggota</th>
                            <th>Nama Lengkap & NIK</th>
                            <th>L/P</th>
                            <th>No. Telp</th>
                            <th>Kelompok / Wilayah</th>
                            <th>Status</th>
                            <th>Bergabung</th>
                            <th class="text-end pe-3">Aksi</th>
                        </tr>
                    </thead>
                    <tbody class="small">
                        <?php if (empty($data['nasabah'])): ?>
                            <tr><td colspan="8" class="text-center py-4 text-muted">Belum ada data nasabah perorangan.</td></tr>
                        <?php else: ?>
                            <?php foreach ($data['nasabah'] as $n): ?>
                            <tr>
                                <td class="ps-3 fw-bold text-primary"><?php echo htmlspecialchars($n['no_anggota']); ?></td>
                                <td>
                                    <div class="fw-bold"><?php echo htmlspecialchars($n['nama_lengkap']); ?></div>
                                    <small class="text-muted">NIK: <?php echo htmlspecialchars($n['nik'] ?? '-'); ?></small>
                                </td>
                                <td><span class="badge bg-light text-dark border"><?php echo $n['jenis_kelamin']; ?></span></td>
                                <td><?php echo htmlspecialchars($n['no_telp'] ?? '-'); ?></td>
                                <td>
                                    <?php if (!empty($n['id_kelompok'])): ?>
                                        <span class="badge bg-info-subtle text-info border">
                                            <?php echo htmlspecialchars($n['nama_kelompok']); ?> (<?php echo $n['peran_kelompok']; ?>)
                                        </span>
                                    <?php else: ?>
                                        <span class="text-muted">Mandiri</span>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <span class="badge <?php echo ($n['status'] === 'Aktif') ? 'bg-success' : 'bg-secondary'; ?> rounded-pill">
                                        <?php echo $n['status']; ?>
                                    </span>
                                </td>
                                <td><?php echo !empty($n['tanggal_daftar']) ? date('d/m/Y', strtotime($n['tanggal_daftar'])) : '-'; ?></td>
                                <td class="text-end pe-3">
                                    <button class="btn btn-sm btn-light rounded-circle text-primary edit-nasabah-btn me-1" 
                                            data-json='<?php echo htmlspecialchars(json_encode($n), ENT_QUOTES); ?>'
                                            title="Edit Nasabah">
                                        <i class="bi bi-pencil"></i>
                                    </button>
                                    <a href="<?php echo BASEURL; ?>/simpanpinjam/nasabah_hapus/<?php echo $n['id_nasabah']; ?>" 
                                       class="btn btn-sm btn-light rounded-circle text-danger" 
                                       onclick="return confirm('Apakah Anda yakin ingin menghapus data nasabah ini?')"
                                       title="Hapus">
                                        <i class="bi bi-trash"></i>
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

    <!-- TAB 2: KELOMPOK NASABAH -->
    <div class="tab-pane fade" id="kelompok" role="tabpanel">
        <div class="card border-0 shadow-sm rounded-4 overflow-hidden">
            <div class="card-header bg-white py-3 border-0 d-flex justify-content-between align-items-center">
                <h6 class="fw-bold mb-0"><i class="bi bi-diagram-3 me-1 text-primary"></i> Daftar Kelompok Nasabah</h6>
            </div>
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead class="table-light small">
                        <tr>
                            <th class="ps-3">Kode</th>
                            <th>Nama Kelompok</th>
                            <th>Ketua Kelompok</th>
                            <th>Kontak</th>
                            <th>Wilayah / Dusun</th>
                            <th>Anggota</th>
                            <th>Status</th>
                            <th class="text-end pe-3">Aksi</th>
                        </tr>
                    </thead>
                    <tbody class="small">
                        <?php if (empty($data['kelompok'])): ?>
                            <tr><td colspan="8" class="text-center py-4 text-muted">Belum ada kelompok nasabah.</td></tr>
                        <?php else: ?>
                            <?php foreach ($data['kelompok'] as $k): ?>
                            <tr>
                                <td class="ps-3 fw-bold text-primary"><?php echo htmlspecialchars($k['kode_kelompok']); ?></td>
                                <td class="fw-bold"><?php echo htmlspecialchars($k['nama_kelompok']); ?></td>
                                <td><?php echo htmlspecialchars($k['nama_ketua'] ?? '-'); ?></td>
                                <td><?php echo htmlspecialchars($k['kontak'] ?? '-'); ?></td>
                                <td><?php echo htmlspecialchars($k['alamat_wilayah'] ?? '-'); ?></td>
                                <td>
                                    <span class="badge bg-primary-subtle text-primary rounded-pill px-2">
                                        <i class="bi bi-people me-1"></i><?php echo $k['jumlah_anggota']; ?> Anggota
                                    </span>
                                </td>
                                <td>
                                    <span class="badge <?php echo ($k['status'] === 'Aktif') ? 'bg-success' : 'bg-secondary'; ?> rounded-pill">
                                        <?php echo $k['status']; ?>
                                    </span>
                                </td>
                                <td class="text-end pe-3">
                                    <a href="<?php echo BASEURL; ?>/simpanpinjam/kelompok_detail/<?php echo $k['id_kelompok']; ?>" 
                                       class="btn btn-sm btn-outline-primary rounded-pill px-3 me-1" title="Lihat & Kelola Anggota">
                                        <i class="bi bi-person-lines-fill me-1"></i> Detail Anggota
                                    </a>
                                    <button class="btn btn-sm btn-light rounded-circle text-primary edit-kelompok-btn me-1"
                                            data-json='<?php echo htmlspecialchars(json_encode($k), ENT_QUOTES); ?>'
                                            title="Edit Kelompok">
                                        <i class="bi bi-pencil"></i>
                                    </button>
                                    <a href="<?php echo BASEURL; ?>/simpanpinjam/kelompok_hapus/<?php echo $k['id_kelompok']; ?>" 
                                       class="btn btn-sm btn-light rounded-circle text-danger" 
                                       onclick="return confirm('Apakah Anda yakin ingin menghapus kelompok ini? Anggota di dalamnya akan diubah menjadi nasabah mandiri.')"
                                       title="Hapus">
                                        <i class="bi bi-trash"></i>
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

<!-- MODAL TAMBAH / EDIT NASABAH -->
<div class="modal fade" id="modalNasabah" tabindex="-1">
    <div class="modal-dialog modal-lg modal-dialog-centered">
        <div class="modal-content border-0 shadow-lg rounded-4">
            <form action="<?php echo BASEURL; ?>/simpanpinjam/nasabah_simpan" method="POST">
                <input type="hidden" name="id_nasabah" id="nasabah_id">
                <div class="modal-header bg-primary text-white border-0 py-3">
                    <h5 class="modal-title fw-bold" id="nasabahModalTitle">Tambah Nasabah Baru</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body p-4">
                    <div class="row g-3">
                        <div class="col-md-6">
                            <label class="form-label small fw-bold text-muted">NOMOR ANGGOTA / NASABAH</label>
                            <input type="text" name="no_anggota" id="nasabah_no_anggota" class="form-control" value="<?php echo $data['no_anggota_baru']; ?>" required>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label small fw-bold text-muted">NOMOR INDUK KEPENDUDUKAN (NIK)</label>
                            <input type="text" name="nik" id="nasabah_nik" class="form-control" placeholder="16 Digit NIK KTP">
                        </div>
                        <div class="col-md-8">
                            <label class="form-label small fw-bold text-muted">NAMA LENGKAP</label>
                            <input type="text" name="nama_lengkap" id="nasabah_nama" class="form-control" required placeholder="Nama sesuai KTP">
                        </div>
                        <div class="col-md-4">
                            <label class="form-label small fw-bold text-muted">JENIS KELAMIN</label>
                            <select name="jenis_kelamin" id="nasabah_jk" class="form-select">
                                <option value="L">Laki-laki</option>
                                <option value="P">Perempuan</option>
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label small fw-bold text-muted">TEMPAT LAHIR</label>
                            <input type="text" name="tempat_lahir" id="nasabah_tempat_lahir" class="form-control">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label small fw-bold text-muted">TANGGAL LAHIR</label>
                            <input type="date" name="tanggal_lahir" id="nasabah_tgl_lahir" class="form-control">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label small fw-bold text-muted">NO. TELEPON / HP (WHATSAPP)</label>
                            <input type="text" name="no_telp" id="nasabah_telp" class="form-control" placeholder="08xxxxxxxxxx">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label small fw-bold text-muted">PEKERJAAN / USAHA</label>
                            <input type="text" name="pekerjaan" id="nasabah_pekerjaan" class="form-control" placeholder="Contoh: Petani, Pedagang">
                        </div>
                        <div class="col-md-12">
                            <label class="form-label small fw-bold text-muted">ALAMAT DOMISILI</label>
                            <textarea name="alamat" id="nasabah_alamat" class="form-control" rows="2" placeholder="Alamat lengkap, RT/RW, Dusun, Desa"></textarea>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label small fw-bold text-muted">KELOMPOK NASABAH (OPSIONAL)</label>
                            <select name="id_kelompok" id="nasabah_kelompok" class="form-select">
                                <option value="">-- Tidak Berkelompok (Mandiri) --</option>
                                <?php foreach ($data['kelompok'] as $k): ?>
                                <option value="<?php echo $k['id_kelompok']; ?>"><?php echo htmlspecialchars($k['nama_kelompok']); ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label small fw-bold text-muted">STATUS KEANGGOTAAN</label>
                            <select name="status" id="nasabah_status" class="form-select">
                                <option value="Aktif">Aktif</option>
                                <option value="Non-Aktif">Non-Aktif</option>
                            </select>
                        </div>
                    </div>
                </div>
                <div class="modal-footer border-0 p-4 pt-0">
                    <button type="button" class="btn btn-light rounded-pill px-4" data-bs-dismiss="modal">Batal</button>
                    <button type="submit" class="btn btn-primary rounded-pill px-4 fw-bold">Simpan Data Nasabah</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- MODAL TAMBAH / EDIT KELOMPOK -->
<div class="modal fade" id="modalKelompok" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0 shadow-lg rounded-4">
            <form action="<?php echo BASEURL; ?>/simpanpinjam/kelompok_simpan" method="POST">
                <input type="hidden" name="id_kelompok" id="kelompok_id">
                <div class="modal-header bg-dark text-white border-0 py-3">
                    <h5 class="modal-title fw-bold" id="kelompokModalTitle">Tambah Kelompok Nasabah</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body p-4">
                    <div class="mb-3">
                        <label class="form-label small fw-bold text-muted">KODE KELOMPOK</label>
                        <input type="text" name="kode_kelompok" id="kelompok_kode" class="form-control" value="<?php echo $data['kode_kelompok_baru']; ?>" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label small fw-bold text-muted">NAMA KELOMPOK</label>
                        <input type="text" name="nama_kelompok" id="kelompok_nama" class="form-control" required placeholder="Contoh: Kelompok Tani Makmur, KWT Melati">
                    </div>
                    <div class="mb-3">
                        <label class="form-label small fw-bold text-muted">NAMA KETUA KELOMPOK</label>
                        <input type="text" name="nama_ketua" id="kelompok_ketua" class="form-control" placeholder="Nama ketua kelompok">
                    </div>
                    <div class="mb-3">
                        <label class="form-label small fw-bold text-muted">KONTAK / NO. HP</label>
                        <input type="text" name="kontak" id="kelompok_kontak" class="form-control" placeholder="08xxxxxxxxxx">
                    </div>
                    <div class="mb-3">
                        <label class="form-label small fw-bold text-muted">WILAYAH / DUSUN / RT-RW</label>
                        <input type="text" name="alamat_wilayah" id="kelompok_alamat" class="form-control" placeholder="Contoh: Dusun Krajan RT 02 RW 01">
                    </div>
                    <div class="mb-0">
                        <label class="form-label small fw-bold text-muted">STATUS KELOMPOK</label>
                        <select name="status" id="kelompok_status" class="form-select">
                            <option value="Aktif">Aktif</option>
                            <option value="Non-Aktif">Non-Aktif</option>
                        </select>
                    </div>
                </div>
                <div class="modal-footer border-0 p-4 pt-0">
                    <button type="button" class="btn btn-light rounded-pill px-4" data-bs-dismiss="modal">Batal</button>
                    <button type="submit" class="btn btn-primary rounded-pill px-4 fw-bold">Simpan Kelompok</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Search Nasabah
    const searchInput = document.getElementById('cariNasabah');
    if (searchInput) {
        searchInput.addEventListener('input', function() {
            const query = this.value.toLowerCase();
            document.querySelectorAll('#tabelNasabah tbody tr').forEach(row => {
                row.style.display = row.innerText.toLowerCase().includes(query) ? '' : 'none';
            });
        });
    }

    // Edit Nasabah
    document.querySelectorAll('.edit-nasabah-btn').forEach(btn => {
        btn.addEventListener('click', function() {
            const d = JSON.parse(this.dataset.json);
            document.getElementById('nasabahModalTitle').innerText = 'Edit Data Nasabah';
            document.getElementById('nasabah_id').value = d.id_nasabah;
            document.getElementById('nasabah_no_anggota').value = d.no_anggota;
            document.getElementById('nasabah_nik').value = d.nik || '';
            document.getElementById('nasabah_nama').value = d.nama_lengkap;
            document.getElementById('nasabah_jk').value = d.jenis_kelamin;
            document.getElementById('nasabah_tempat_lahir').value = d.tempat_lahir || '';
            document.getElementById('nasabah_tgl_lahir').value = d.tanggal_lahir || '';
            document.getElementById('nasabah_telp').value = d.no_telp || '';
            document.getElementById('nasabah_pekerjaan').value = d.pekerjaan || '';
            document.getElementById('nasabah_alamat').value = d.alamat || '';
            document.getElementById('nasabah_kelompok').value = d.id_kelompok || '';
            document.getElementById('nasabah_status').value = d.status;

            new bootstrap.Modal(document.getElementById('modalNasabah')).show();
        });
    });

    // Reset Modal Nasabah on Add
    document.getElementById('modalNasabah').addEventListener('hidden.bs.modal', function() {
        document.getElementById('nasabahModalTitle').innerText = 'Tambah Nasabah Baru';
        document.getElementById('nasabah_id').value = '';
    });

    // Edit Kelompok
    document.querySelectorAll('.edit-kelompok-btn').forEach(btn => {
        btn.addEventListener('click', function() {
            const d = JSON.parse(this.dataset.json);
            document.getElementById('kelompokModalTitle').innerText = 'Edit Kelompok Nasabah';
            document.getElementById('kelompok_id').value = d.id_kelompok;
            document.getElementById('kelompok_kode').value = d.kode_kelompok;
            document.getElementById('kelompok_nama').value = d.nama_kelompok;
            document.getElementById('kelompok_ketua').value = d.nama_ketua || '';
            document.getElementById('kelompok_kontak').value = d.kontak || '';
            document.getElementById('kelompok_alamat').value = d.alamat_wilayah || '';
            document.getElementById('kelompok_status').value = d.status;

            new bootstrap.Modal(document.getElementById('modalKelompok')).show();
        });
    });

    document.getElementById('modalKelompok').addEventListener('hidden.bs.modal', function() {
        document.getElementById('kelompokModalTitle').innerText = 'Tambah Kelompok Nasabah';
        document.getElementById('kelompok_id').value = '';
    });
});
</script>
