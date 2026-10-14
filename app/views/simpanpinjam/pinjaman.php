<?php
$pinjamanList = $data['pinjaman'] ?? [];
$jenisList = $data['jenis_pinjaman'] ?? [];
$nasabahList = $data['nasabah'] ?? [];
$kelompokList = $data['kelompok'] ?? [];
$statusFilter = $data['status_filter'] ?? null;
?>

<div class="d-flex flex-wrap justify-content-between align-items-center mb-4 gap-3">
    <div>
        <nav aria-label="breadcrumb">
            <ol class="breadcrumb mb-1 small">
                <li class="breadcrumb-item"><a href="<?php echo BASEURL; ?>/simpanpinjam">Simpan Pinjam</a></li>
                <li class="breadcrumb-item active">Pinjaman & Pembiayaan</li>
            </ol>
        </nav>
        <h2 class="fw-bold mb-0">Manajemen Pinjaman & Pembiayaan</h2>
    </div>
    <div class="d-flex flex-wrap gap-2">
        <a href="<?php echo BASEURL; ?>/simpanpinjam/laporan_pinjaman" class="btn btn-outline-danger rounded-pill px-3">
            <i class="bi bi-shield-check me-1"></i> Laporan Kolektibilitas & NPL
        </a>
        <button type="button" class="btn btn-outline-primary rounded-pill px-3" data-bs-toggle="modal" data-bs-target="#modalTambahJenisPinjaman">
            <i class="bi bi-tag-fill me-1"></i> Tambah Jenis Pinjaman
        </button>
        <button type="button" class="btn btn-primary rounded-pill px-4" data-bs-toggle="modal" data-bs-target="#modalAjukanPinjaman">
            <i class="bi bi-file-earmark-plus-fill me-1"></i> Ajukan Pinjaman Baru
        </button>
    </div>
</div>

<!-- Tabs Utama -->
<ul class="nav nav-pills mb-4 gap-2" id="pinjamanTab" role="tablist">
    <li class="nav-item" role="presentation">
        <button class="nav-link active rounded-pill px-4" id="daftar-tab" data-bs-toggle="tab" data-bs-target="#daftar" type="button" role="tab">
            <i class="bi bi-list-task me-1"></i> Portofolio & Pengajuan (<?php echo count($pinjamanList); ?>)
        </button>
    </li>
    <li class="nav-item" role="presentation">
        <button class="nav-link rounded-pill px-4" id="jenis-tab" data-bs-toggle="tab" data-bs-target="#jenis" type="button" role="tab">
            <i class="bi bi-grid-3x3-gap-fill me-1"></i> Jenis-Jenis Pinjaman (<?php echo count($jenisList); ?>)
        </button>
    </li>
</ul>

<div class="tab-content" id="pinjamanTabContent">
    <!-- TAB 1: PORTOFOLIO & PENGAJUAN PINJAMAN -->
    <div class="tab-pane fade show active" id="daftar" role="tabpanel">
        <!-- Sub Filter Status -->
        <div class="d-flex flex-wrap align-items-center gap-2 mb-3">
            <span class="small fw-bold text-muted me-2">Status:</span>
            <a href="<?php echo BASEURL; ?>/simpanpinjam/pinjaman" class="badge rounded-pill px-3 py-2 text-decoration-none <?php echo empty($statusFilter) ? 'bg-primary text-white' : 'bg-light text-dark border'; ?>">
                Semua Status
            </a>
            <a href="<?php echo BASEURL; ?>/simpanpinjam/pinjaman?status=Diajukan" class="badge rounded-pill px-3 py-2 text-decoration-none <?php echo ($statusFilter === 'Diajukan') ? 'bg-warning text-dark' : 'bg-light text-dark border'; ?>">
                <i class="bi bi-hourglass-split me-1"></i> Diajukan
            </a>
            <a href="<?php echo BASEURL; ?>/simpanpinjam/pinjaman?status=Disetujui" class="badge rounded-pill px-3 py-2 text-decoration-none <?php echo ($statusFilter === 'Disetujui') ? 'bg-info text-white' : 'bg-light text-dark border'; ?>">
                <i class="bi bi-check-circle me-1"></i> Disetujui
            </a>
            <a href="<?php echo BASEURL; ?>/simpanpinjam/pinjaman?status=Dicairkan" class="badge rounded-pill px-3 py-2 text-decoration-none <?php echo ($statusFilter === 'Dicairkan') ? 'bg-primary text-white' : 'bg-light text-dark border'; ?>">
                <i class="bi bi-cash-stack me-1"></i> Dicairkan (Aktif)
            </a>
            <a href="<?php echo BASEURL; ?>/simpanpinjam/pinjaman?status=Lunas" class="badge rounded-pill px-3 py-2 text-decoration-none <?php echo ($statusFilter === 'Lunas') ? 'bg-success text-white' : 'bg-light text-dark border'; ?>">
                <i class="bi bi-patch-check-fill me-1"></i> Lunas
            </a>
            <a href="<?php echo BASEURL; ?>/simpanpinjam/pinjaman?status=Ditolak" class="badge rounded-pill px-3 py-2 text-decoration-none <?php echo ($statusFilter === 'Ditolak') ? 'bg-danger text-white' : 'bg-light text-dark border'; ?>">
                <i class="bi bi-x-circle me-1"></i> Ditolak
            </a>
        </div>

        <div class="card border-0 shadow-sm rounded-4 overflow-hidden">
            <div class="card-header bg-white py-3 border-0 d-flex justify-content-between align-items-center">
                <h6 class="fw-bold mb-0"><i class="bi bi-credit-card me-1 text-primary"></i> Daftar Pinjaman Anggota & Kelompok</h6>
                <input type="text" id="cariPinjaman" class="form-control form-control-sm rounded-pill w-auto" placeholder="Cari nomor, debitur...">
            </div>
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0" id="tabelPinjaman">
                    <thead class="table-light small">
                        <tr>
                            <th class="ps-3">No. Pinjaman</th>
                            <th>Tanggal</th>
                            <th>Debitur (Peminjam)</th>
                            <th>Produk & Tenor</th>
                            <th class="text-end">Plafon</th>
                            <th class="text-end">Sisa Pokok</th>
                            <th class="text-center">Status</th>
                            <th class="text-end pe-3">Aksi</th>
                        </tr>
                    </thead>
                    <tbody class="small">
                        <?php if (empty($pinjamanList)): ?>
                            <tr><td colspan="8" class="text-center py-5 text-muted">Belum ada data pinjaman yang sesuai kriteria.</td></tr>
                        <?php else: ?>
                            <?php foreach ($pinjamanList as $p): ?>
                            <tr>
                                <td class="ps-3 font-monospace fw-bold">
                                    <a href="<?php echo BASEURL; ?>/simpanpinjam/pinjaman_detail/<?php echo $p['id_pinjaman']; ?>" class="text-decoration-none">
                                        <?php echo htmlspecialchars($p['no_pinjaman']); ?>
                                    </a>
                                </td>
                                <td><?php echo date('d/m/Y', strtotime($p['tanggal_pengajuan'])); ?></td>
                                <td>
                                    <?php if ($p['tipe_debitur'] === 'Kelompok'): ?>
                                        <span class="badge bg-info-subtle text-info border border-info-subtle me-1">Kelompok</span>
                                        <strong><?php echo htmlspecialchars($p['nama_kelompok'] ?? 'Kelompok'); ?></strong>
                                    <?php else: ?>
                                        <span class="badge bg-secondary-subtle text-secondary me-1">Perorangan</span>
                                        <strong><?php echo htmlspecialchars($p['nama_nasabah'] ?? 'Nasabah'); ?></strong>
                                        <span class="text-muted d-block" style="font-size:0.75rem;">No. Agt: <?php echo htmlspecialchars($p['no_anggota'] ?? '-'); ?></span>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <div><?php echo htmlspecialchars($p['nama_pinjaman']); ?></div>
                                    <span class="text-muted" style="font-size:0.75rem;">
                                        <?php echo $p['jangka_waktu']; ?> bln &bull; Bunga <?php echo $p['suku_bunga']; ?>% (<?php echo $p['sistem_bunga']; ?>)
                                    </span>
                                </td>
                                <td class="text-end fw-semibold">
                                    <?php if ($p['status'] === 'Diajukan'): ?>
                                        <span class="text-muted small">Diajukan:</span><br>Rp <?php echo number_format($p['jumlah_pengajuan'], 0, ',', '.'); ?>
                                    <?php else: ?>
                                        Rp <?php echo number_format($p['jumlah_disetujui'], 0, ',', '.'); ?>
                                    <?php endif; ?>
                                </td>
                                <td class="text-end fw-bold <?php echo ($p['status'] === 'Dicairkan') ? 'text-primary' : 'text-muted'; ?>">
                                    <?php if ($p['status'] === 'Dicairkan'): ?>
                                        Rp <?php echo number_format($p['sisa_pokok'], 0, ',', '.'); ?>
                                    <?php elseif ($p['status'] === 'Lunas'): ?>
                                        <span class="text-success"><i class="bi bi-check-all"></i> Lunas</span>
                                    <?php else: ?>
                                        -
                                    <?php endif; ?>
                                </td>
                                <td class="text-center">
                                    <?php
                                    $badge = match($p['status']) {
                                        'Diajukan' => 'bg-warning text-dark',
                                        'Disetujui' => 'bg-info text-white',
                                        'Dicairkan' => 'bg-primary text-white',
                                        'Lunas' => 'bg-success text-white',
                                        default => 'bg-danger text-white'
                                    };
                                    ?>
                                    <span class="badge <?php echo $badge; ?> rounded-pill px-3 py-1">
                                        <?php echo $p['status']; ?>
                                    </span>
                                </td>
                                <td class="text-end pe-3">
                                    <a href="<?php echo BASEURL; ?>/simpanpinjam/pinjaman_detail/<?php echo $p['id_pinjaman']; ?>" class="btn btn-sm btn-outline-primary rounded-pill px-3">
                                        <i class="bi bi-eye me-1"></i> Detail
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

    <!-- TAB 2: JENIS-JENIS PINJAMAN (PRODUK) -->
    <div class="tab-pane fade" id="jenis" role="tabpanel">
        <div class="card border-0 shadow-sm rounded-4 overflow-hidden mb-4">
            <div class="card-header bg-white py-3 border-0 d-flex justify-content-between align-items-center">
                <h6 class="fw-bold mb-0"><i class="bi bi-tags me-1 text-primary"></i> Master Produk Pinjaman & Pembiayaan</h6>
                <button type="button" class="btn btn-sm btn-primary rounded-pill px-3" data-bs-toggle="modal" data-bs-target="#modalTambahJenisPinjaman">
                    <i class="bi bi-plus-lg me-1"></i> Tambah Produk
                </button>
            </div>
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead class="table-light small">
                        <tr>
                            <th class="ps-3">Kode</th>
                            <th>Nama Pinjaman</th>
                            <th>Sistem Bunga</th>
                            <th>Suku Bunga (%)</th>
                            <th>Admin (%)</th>
                            <th>Denda (%)</th>
                            <th>Akun Piutang</th>
                            <th>Akun Jasa</th>
                            <th class="text-end pe-3">Aksi</th>
                        </tr>
                    </thead>
                    <tbody class="small">
                        <?php if (empty($jenisList)): ?>
                            <tr><td colspan="9" class="text-center py-4 text-muted">Belum ada jenis pinjaman yang didaftarkan.</td></tr>
                        <?php else: ?>
                            <?php foreach ($jenisList as $jp): ?>
                            <tr>
                                <td class="ps-3 font-monospace fw-bold text-primary"><?php echo htmlspecialchars($jp['kode_pinjaman']); ?></td>
                                <td class="fw-bold"><?php echo htmlspecialchars($jp['nama_pinjaman']); ?></td>
                                <td><span class="badge bg-secondary-subtle text-secondary"><?php echo htmlspecialchars($jp['sistem_bunga']); ?></span></td>
                                <td class="fw-bold text-danger"><?php echo $jp['suku_bunga_persen']; ?>% / <?php echo strtolower($jp['satuan_waktu']); ?></td>
                                <td><?php echo $jp['biaya_admin_persen']; ?>%</td>
                                <td><?php echo $jp['denda_keterlambatan_persen']; ?>%</td>
                                <td class="font-monospace small"><?php echo htmlspecialchars($jp['kode_akun_piutang'] ?? '-'); ?></td>
                                <td class="font-monospace small"><?php echo htmlspecialchars($jp['kode_akun_pendapatan_jasa'] ?? '-'); ?></td>
                                <td class="text-end pe-3">
                                    <a href="<?php echo BASEURL; ?>/simpanpinjam/jenis_pinjaman_hapus/<?php echo $jp['id_jenis_pinjaman']; ?>" class="btn btn-sm btn-light text-danger rounded-circle" onclick="return confirm('Apakah Anda yakin ingin menghapus produk pinjaman ini?')" title="Hapus">
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

<!-- Modal 1: Ajukan Pinjaman Baru -->
<div class="modal fade" id="modalAjukanPinjaman" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-centered">
        <div class="modal-content border-0 shadow rounded-4">
            <form action="<?php echo BASEURL; ?>/simpanpinjam/pinjaman_ajukan" method="POST">
                <div class="modal-header border-0 pb-0">
                    <h5 class="modal-title fw-bold"><i class="bi bi-file-earmark-plus text-primary me-2"></i>Form Pengajuan Pinjaman Baru</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body p-4">
                    <div class="row g-3">
                        <div class="col-md-6">
                            <label class="form-label small fw-bold text-muted">NOMOR PENGAJUAN PINJAMAN</label>
                            <input type="text" name="no_pinjaman" class="form-control font-monospace" value="<?php echo $data['no_pinj_baru']; ?>" required>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label small fw-bold text-muted">TANGGAL PENGAJUAN</label>
                            <input type="date" name="tanggal_pengajuan" class="form-control" value="<?php echo date('Y-m-d'); ?>" required>
                        </div>

                        <!-- Tipe Debitur -->
                        <div class="col-md-12">
                            <label class="form-label small fw-bold text-muted">TIPE DEBITUR (PEMINJAM)</label>
                            <div class="d-flex gap-4 p-2 bg-light rounded-3">
                                <div class="form-check">
                                    <input class="form-check-input" type="radio" name="tipe_debitur" id="tipe_perorangan" value="Perorangan" checked onchange="toggleDebiturType()">
                                    <label class="form-check-label fw-bold" for="tipe_perorangan">
                                        <i class="bi bi-person-fill text-primary me-1"></i> Nasabah Perorangan
                                    </label>
                                </div>
                                <div class="form-check">
                                    <input class="form-check-input" type="radio" name="tipe_debitur" id="tipe_kelompok" value="Kelompok" onchange="toggleDebiturType()">
                                    <label class="form-check-label fw-bold" for="tipe_kelompok">
                                        <i class="bi bi-diagram-3-fill text-info me-1"></i> Kelompok Nasabah
                                    </label>
                                </div>
                            </div>
                        </div>

                        <!-- Dropdown Nasabah Perorangan -->
                        <div class="col-md-12" id="group_nasabah">
                            <label class="form-label small fw-bold text-muted">PILIH NASABAH PERORANGAN</label>
                            <select name="id_nasabah" id="select_nasabah" class="form-select select2-basic">
                                <option value="">-- Pilih Nasabah --</option>
                                <?php foreach ($nasabahList as $nb): ?>
                                    <option value="<?php echo $nb['id_nasabah']; ?>">
                                        <?php echo htmlspecialchars($nb['no_anggota']) . ' - ' . htmlspecialchars($nb['nama_lengkap']) . ' (' . htmlspecialchars($nb['nik'] ?? '-') . ')'; ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>

                        <!-- Dropdown Kelompok -->
                        <div class="col-md-12 d-none" id="group_kelompok">
                            <label class="form-label small fw-bold text-muted">PILIH KELOMPOK NASABAH</label>
                            <select name="id_kelompok" id="select_kelompok" class="form-select">
                                <option value="">-- Pilih Kelompok --</option>
                                <?php foreach ($kelompokList as $kp): ?>
                                    <option value="<?php echo $kp['id_kelompok']; ?>">
                                        <?php echo htmlspecialchars($kp['kode_kelompok']) . ' - ' . htmlspecialchars($kp['nama_kelompok']) . ' (Ketua: ' . htmlspecialchars($kp['nama_ketua'] ?? '-') . ')'; ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>

                        <!-- Jenis Pinjaman -->
                        <div class="col-md-6">
                            <label class="form-label small fw-bold text-muted">JENIS / PRODUK PINJAMAN</label>
                            <select name="id_jenis_pinjaman" id="pilih_jenis_pinjaman" class="form-select" required onchange="updateInfoProduk()">
                                <option value="">-- Pilih Produk --</option>
                                <?php foreach ($jenisList as $jp): ?>
                                    <option value="<?php echo $jp['id_jenis_pinjaman']; ?>" 
                                            data-bunga="<?php echo $jp['suku_bunga_persen']; ?>" 
                                            data-admin="<?php echo $jp['biaya_admin_persen']; ?>"
                                            data-sistem="<?php echo $jp['sistem_bunga']; ?>">
                                        <?php echo htmlspecialchars($jp['nama_pinjaman']); ?> (Bunga <?php echo $jp['suku_bunga_persen']; ?>%/bln)
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>

                        <!-- Jumlah Pengajuan -->
                        <div class="col-md-6">
                            <label class="form-label small fw-bold text-muted">PLAFON YANG DIAJUKAN (RP)</label>
                            <div class="input-group">
                                <span class="input-group-text bg-white">Rp</span>
                                <input type="number" step="50000" name="jumlah_pengajuan" id="input_plafon" class="form-control fw-bold" placeholder="Contoh: 5000000" required oninput="hitungSimulasi()">
                            </div>
                        </div>

                        <!-- Tenor & Suku Bunga -->
                        <div class="col-md-6">
                            <label class="form-label small fw-bold text-muted">JANGKA WAKTU / TENOR (BULAN)</label>
                            <input type="number" min="1" max="120" name="jangka_waktu" id="input_tenor" class="form-control" value="12" required oninput="hitungSimulasi()">
                        </div>

                        <div class="col-md-6">
                            <label class="form-label small fw-bold text-muted">SUKU BUNGA / JASA (% PER BULAN)</label>
                            <div class="input-group">
                                <input type="number" step="0.01" name="suku_bunga" id="input_bunga" class="form-control" value="1.50" required oninput="hitungSimulasi()">
                                <span class="input-group-text bg-white">%</span>
                            </div>
                        </div>

                        <!-- Real-time Simulator Panel -->
                        <div class="col-md-12">
                            <div class="p-3 bg-light rounded-4 border">
                                <h6 class="fw-bold mb-2 text-primary"><i class="bi bi-calculator me-1"></i> Simulasi Estimasi Angsuran Bulanan</h6>
                                <div class="row g-2 small">
                                    <div class="col-md-3">
                                        <span class="text-muted d-block">Angsuran Pokok:</span>
                                        <strong id="sim_pokok" class="fs-6">Rp 0</strong>
                                    </div>
                                    <div class="col-md-3">
                                        <span class="text-muted d-block">Jasa / Bunga:</span>
                                        <strong id="sim_jasa" class="fs-6 text-danger">Rp 0</strong>
                                    </div>
                                    <div class="col-md-3">
                                        <span class="text-muted d-block">Total per Bulan:</span>
                                        <strong id="sim_total" class="fs-6 text-success">Rp 0</strong>
                                    </div>
                                    <div class="col-md-3">
                                        <span class="text-muted d-block">Biaya Admin Awal:</span>
                                        <strong id="sim_admin" class="fs-6 text-secondary">Rp 0</strong>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="col-md-6">
                            <label class="form-label small fw-bold text-muted">AGUNAN / JAMINAN</label>
                            <input type="text" name="agunan" class="form-control" placeholder="Contoh: BPKB Motor, Sertifikat, Tanggung Renteng">
                        </div>

                        <div class="col-md-6">
                            <label class="form-label small fw-bold text-muted">TUJUAN PENGGUNAAN PINJAMAN</label>
                            <input type="text" name="tujuan_pinjaman" class="form-control" placeholder="Contoh: Tambahan Modal Warung Sembako">
                        </div>
                    </div>
                </div>
                <div class="modal-footer border-0 pt-0">
                    <button type="button" class="btn btn-light rounded-pill px-3" data-bs-dismiss="modal">Batal</button>
                    <button type="submit" class="btn btn-primary rounded-pill px-4">Simpan Pengajuan Pinjaman</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Modal 2: Tambah Jenis Pinjaman Baru -->
<div class="modal fade" id="modalTambahJenisPinjaman" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0 shadow rounded-4">
            <form action="<?php echo BASEURL; ?>/simpanpinjam/jenis_pinjaman_simpan" method="POST">
                <div class="modal-header border-0 pb-0">
                    <h5 class="modal-title fw-bold"><i class="bi bi-tag text-primary me-2"></i>Tambah Jenis / Produk Pinjaman</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body p-4">
                    <div class="row g-3">
                        <div class="col-md-4">
                            <label class="form-label small fw-bold text-muted">KODE</label>
                            <input type="text" name="kode_pinjaman" class="form-control text-uppercase" placeholder="PMU" required>
                        </div>
                        <div class="col-md-8">
                            <label class="form-label small fw-bold text-muted">NAMA PRODUK PINJAMAN</label>
                            <input type="text" name="nama_pinjaman" class="form-control" placeholder="Pinjaman Modal Usaha" required>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label small fw-bold text-muted">SISTEM PERHITUNGAN BUNGA</label>
                            <select name="sistem_bunga" class="form-select">
                                <option value="Flat">Flat (Tetap)</option>
                                <option value="Menurun">Menurun (Efektif)</option>
                                <option value="Anuitas">Anuitas</option>
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label small fw-bold text-muted">SUKU BUNGA (% PER SATUAN)</label>
                            <input type="number" step="0.01" name="suku_bunga_persen" class="form-control" value="1.50" required>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label small fw-bold text-muted">BIAYA ADMINISTRASI (%)</label>
                            <input type="number" step="0.01" name="biaya_admin_persen" class="form-control" value="1.00">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label small fw-bold text-muted">DENDA KETERLAMBATAN (%)</label>
                            <input type="number" step="0.01" name="denda_keterlambatan_persen" class="form-control" value="0.50">
                        </div>
                        <div class="col-md-12">
                            <label class="form-label small fw-bold text-muted">KETERANGAN / DESKRIPSI PRODUK</label>
                            <textarea name="keterangan" class="form-control" rows="2" placeholder="Ketentuan atau syarat khusus..."></textarea>
                        </div>
                    </div>
                </div>
                <div class="modal-footer border-0 pt-0">
                    <button type="button" class="btn btn-light rounded-pill px-3" data-bs-dismiss="modal">Batal</button>
                    <button type="submit" class="btn btn-primary rounded-pill px-4">Simpan Produk</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
function toggleDebiturType() {
    var isPerorangan = document.getElementById('tipe_perorangan').checked;
    var groupNasabah = document.getElementById('group_nasabah');
    var groupKelompok = document.getElementById('group_kelompok');
    var selectNasabah = document.getElementById('select_nasabah');
    var selectKelompok = document.getElementById('select_kelompok');

    if (isPerorangan) {
        groupNasabah.classList.remove('d-none');
        groupKelompok.classList.add('d-none');
        selectNasabah.required = true;
        selectKelompok.required = false;
        selectKelompok.value = '';
    } else {
        groupNasabah.classList.add('d-none');
        groupKelompok.classList.remove('d-none');
        selectNasabah.required = false;
        selectKelompok.required = true;
        selectNasabah.value = '';
    }
}

function updateInfoProduk() {
    var select = document.getElementById('pilih_jenis_pinjaman');
    var selectedOption = select.options[select.selectedIndex];
    if (selectedOption && selectedOption.dataset.bunga) {
        document.getElementById('input_bunga').value = selectedOption.dataset.bunga;
        hitungSimulasi();
    }
}

function hitungSimulasi() {
    var plafon = parseFloat(document.getElementById('input_plafon').value) || 0;
    var tenor = parseInt(document.getElementById('input_tenor').value) || 1;
    var bunga = parseFloat(document.getElementById('input_bunga').value) || 0;

    var select = document.getElementById('pilih_jenis_pinjaman');
    var adminPersen = 1.0;
    if (select && select.selectedIndex > 0) {
        adminPersen = parseFloat(select.options[select.selectedIndex].dataset.admin) || 0;
    }

    if (plafon > 0 && tenor > 0) {
        var pokokPerBulan = Math.round(plafon / tenor);
        var jasaPerBulan = Math.round((bunga / 100) * plafon);
        var totalPerBulan = pokokPerBulan + jasaPerBulan;
        var adminAwal = Math.round((adminPersen / 100) * plafon);

        document.getElementById('sim_pokok').innerText = 'Rp ' + pokokPerBulan.toLocaleString('id-ID');
        document.getElementById('sim_jasa').innerText = 'Rp ' + jasaPerBulan.toLocaleString('id-ID');
        document.getElementById('sim_total').innerText = 'Rp ' + totalPerBulan.toLocaleString('id-ID');
        document.getElementById('sim_admin').innerText = 'Rp ' + adminAwal.toLocaleString('id-ID');
    } else {
        document.getElementById('sim_pokok').innerText = 'Rp 0';
        document.getElementById('sim_jasa').innerText = 'Rp 0';
        document.getElementById('sim_total').innerText = 'Rp 0';
        document.getElementById('sim_admin').innerText = 'Rp 0';
    }
}

document.addEventListener('DOMContentLoaded', function() {
    // Quick search filter for loans table
    var cari = document.getElementById('cariPinjaman');
    if (cari) {
        cari.addEventListener('keyup', function() {
            var val = this.value.toLowerCase();
            var rows = document.querySelectorAll('#tabelPinjaman tbody tr');
            rows.forEach(function(row) {
                var text = row.innerText.toLowerCase();
                row.style.display = text.includes(val) ? '' : 'none';
            });
        });
    }
});
</script>
