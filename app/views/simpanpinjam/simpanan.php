<div class="d-flex flex-wrap justify-content-between align-items-center mb-4 gap-3">
    <div>
        <nav aria-label="breadcrumb">
            <ol class="breadcrumb mb-1 small">
                <li class="breadcrumb-item"><a href="<?php echo BASEURL; ?>/simpanpinjam">Simpan Pinjam</a></li>
                <li class="breadcrumb-item active">Simpanan</li>
            </ol>
        </nav>
        <h2 class="fw-bold mb-0">Manajemen Simpanan Nasabah</h2>
    </div>
    <div class="d-flex flex-wrap gap-2">
        <a href="<?php echo BASEURL; ?>/simpanpinjam/laporan_simpanan" class="btn btn-outline-secondary rounded-pill px-3">
            <i class="bi bi-file-earmark-bar-graph me-1"></i> Laporan Simpanan
        </a>
        <button type="button" class="btn btn-outline-success rounded-pill px-3" data-bs-toggle="modal" data-bs-target="#modalTransaksiSimpanan" data-jenis="Setor">
            <i class="bi bi-arrow-down-circle me-1"></i> Setor Simpanan
        </button>
        <button type="button" class="btn btn-outline-danger rounded-pill px-3" data-bs-toggle="modal" data-bs-target="#modalTransaksiSimpanan" data-jenis="Tarik">
            <i class="bi bi-arrow-up-circle me-1"></i> Tarik Simpanan
        </button>
        <button type="button" class="btn btn-primary rounded-pill px-3" data-bs-toggle="modal" data-bs-target="#modalBukaRekening">
            <i class="bi bi-plus-circle-fill me-1"></i> Buka Rekening Baru
        </button>
    </div>
</div>

<!-- Tabs -->
<ul class="nav nav-pills mb-4 gap-2" id="simpananTab" role="tablist">
    <li class="nav-item" role="presentation">
        <button class="nav-link active rounded-pill px-4" id="rekening-tab" data-bs-toggle="tab" data-bs-target="#rekening" type="button" role="tab">
            <i class="bi bi-wallet2 me-1"></i> Rekening Simpanan (<?php echo count($data['rekening']); ?>)
        </button>
    </li>
    <li class="nav-item" role="presentation">
        <button class="nav-link rounded-pill px-4" id="produk-tab" data-bs-toggle="tab" data-bs-target="#produk" type="button" role="tab">
            <i class="bi bi-grid-3x3-gap me-1"></i> Jenis & Produk Simpanan (<?php echo count($data['jenis_simpanan']); ?>)
        </button>
    </li>
</ul>

<div class="tab-content" id="simpananTabContent">
    <!-- TAB 1: DAFTAR REKENING SIMPANAN -->
    <div class="tab-pane fade show active" id="rekening" role="tabpanel">
        <div class="card border-0 shadow-sm rounded-4 overflow-hidden">
            <div class="card-header bg-white py-3 border-0 d-flex justify-content-between align-items-center">
                <h6 class="fw-bold mb-0"><i class="bi bi-credit-card-2-back me-1 text-primary"></i> Daftar Rekening Simpanan Nasabah</h6>
                <input type="text" id="cariRekening" class="form-control form-control-sm rounded-pill w-auto" placeholder="Cari nama, no rekening...">
            </div>
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0" id="tabelRekening">
                    <thead class="table-light small">
                        <tr>
                            <th class="ps-3">No. Rekening</th>
                            <th>Nasabah</th>
                            <th>Jenis Simpanan</th>
                            <th>Saldo Simpanan</th>
                            <th>Tgl Buka</th>
                            <th>Status</th>
                            <th class="text-end pe-3">Aksi</th>
                        </tr>
                    </thead>
                    <tbody class="small">
                        <?php if (empty($data['rekening'])): ?>
                            <tr><td colspan="7" class="text-center py-4 text-muted">Belum ada rekening simpanan yang dibuka.</td></tr>
                        <?php else: ?>
                            <?php foreach ($data['rekening'] as $r): ?>
                            <tr>
                                <td class="ps-3 fw-bold text-primary"><?php echo htmlspecialchars($r['no_rekening']); ?></td>
                                <td>
                                    <div class="fw-bold"><?php echo htmlspecialchars($r['nama_nasabah']); ?></div>
                                    <small class="text-muted"><?php echo htmlspecialchars($r['no_anggota']); ?></small>
                                </td>
                                <td>
                                    <span class="badge bg-light text-dark border"><?php echo htmlspecialchars($r['nama_simpanan']); ?></span>
                                    <small class="text-muted d-block"><?php echo $r['tipe_simpanan']; ?></small>
                                </td>
                                <td class="fw-bold text-success fs-6">Rp <?php echo number_format($r['saldo'], 0, ',', '.'); ?></td>
                                <td><?php echo date('d/m/Y', strtotime($r['tanggal_buka'])); ?></td>
                                <td>
                                    <span class="badge <?php echo ($r['status'] === 'Aktif') ? 'bg-success' : 'bg-secondary'; ?> rounded-pill">
                                        <?php echo $r['status']; ?>
                                    </span>
                                </td>
                                <td class="text-end pe-3">
                                    <a href="<?php echo BASEURL; ?>/simpanpinjam/buku_tabungan/<?php echo $r['id_rekening']; ?>" 
                                       class="btn btn-sm btn-outline-primary rounded-pill px-3 me-1" title="Buku Tabungan / Mutasi">
                                        <i class="bi bi-book me-1"></i> Buku Tabungan
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

    <!-- TAB 2: JENIS PRODUK SIMPANAN -->
    <div class="tab-pane fade" id="produk" role="tabpanel">
        <div class="card border-0 shadow-sm rounded-4 overflow-hidden">
            <div class="card-header bg-white py-3 border-0 d-flex justify-content-between align-items-center">
                <h6 class="fw-bold mb-0"><i class="bi bi-tags me-1 text-primary"></i> Master Jenis & Produk Simpanan</h6>
                <button type="button" class="btn btn-sm btn-primary rounded-pill px-3" data-bs-toggle="modal" data-bs-target="#modalTambahJenisSimpanan">
                    <i class="bi bi-plus me-1"></i> Tambah Produk Simpanan
                </button>
            </div>
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead class="table-light small">
                        <tr>
                            <th class="ps-3">Kode</th>
                            <th>Nama Simpanan</th>
                            <th>Tipe Simpanan</th>
                            <th>Minimal Setor</th>
                            <th>Bunga (% / thn)</th>
                            <th>Akun COA Terkait</th>
                            <th>Status</th>
                            <th class="text-end pe-3">Aksi</th>
                        </tr>
                    </thead>
                    <tbody class="small">
                        <?php foreach ($data['jenis_simpanan'] as $js): ?>
                        <tr>
                            <td class="ps-3 fw-bold"><?php echo htmlspecialchars($js['kode_simpanan']); ?></td>
                            <td class="fw-bold"><?php echo htmlspecialchars($js['nama_simpanan']); ?></td>
                            <td><span class="badge bg-info-subtle text-info border"><?php echo $js['tipe_simpanan']; ?></span></td>
                            <td>Rp <?php echo number_format($js['nominal_minimal_setor'], 0, ',', '.'); ?></td>
                            <td><?php echo $js['bunga_pertahun']; ?>%</td>
                            <td><?php echo htmlspecialchars($js['kode_akun'] ?? '-'); ?> <?php echo !empty($js['nama_akun']) ? '(' . htmlspecialchars($js['nama_akun']) . ')' : ''; ?></td>
                            <td>
                                <span class="badge <?php echo ($js['status'] === 'Aktif') ? 'bg-success' : 'bg-secondary'; ?> rounded-pill">
                                    <?php echo $js['status']; ?>
                                </span>
                            </td>
                            <td class="text-end pe-3">
                                <a href="<?php echo BASEURL; ?>/simpanpinjam/jenis_simpanan_hapus/<?php echo $js['id_jenis_simpanan']; ?>" 
                                   class="btn btn-sm btn-light rounded-circle text-danger" 
                                   onclick="return confirm('Apakah Anda yakin ingin menghapus produk simpanan ini?')"
                                   title="Hapus">
                                    <i class="bi bi-trash"></i>
                                </a>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<!-- Modal Buka Rekening Simpanan -->
<div class="modal fade" id="modalBukaRekening" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0 shadow-lg rounded-4">
            <form action="<?php echo BASEURL; ?>/simpanpinjam/rekening_buka" method="POST">
                <div class="modal-header bg-primary text-white border-0 py-3">
                    <h5 class="modal-title fw-bold">Buka Rekening Simpanan Baru</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body p-4">
                    <div class="mb-3">
                        <label class="form-label small fw-bold text-muted">NOMOR REKENING</label>
                        <input type="text" name="no_rekening" class="form-control" value="<?php echo $data['no_rek_baru']; ?>" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label small fw-bold text-muted">PILIH NASABAH</label>
                        <select name="id_nasabah" class="form-select" required>
                            <option value="">-- Pilih Nasabah --</option>
                            <?php foreach ($data['nasabah'] as $n): ?>
                            <option value="<?php echo $n['id_nasabah']; ?>">
                                <?php echo htmlspecialchars($n['no_anggota'] . ' - ' . $n['nama_lengkap']); ?>
                            </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label class="form-label small fw-bold text-muted">JENIS SIMPANAN</label>
                        <select name="id_jenis_simpanan" class="form-select" required>
                            <option value="">-- Pilih Jenis Simpanan --</option>
                            <?php foreach ($data['jenis_simpanan'] as $js): ?>
                            <option value="<?php echo $js['id_jenis_simpanan']; ?>">
                                <?php echo htmlspecialchars($js['nama_simpanan'] . ' (' . $js['tipe_simpanan'] . ')'); ?>
                            </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label class="form-label small fw-bold text-muted">TANGGAL PEMBUKAAN</label>
                        <input type="date" name="tanggal_buka" class="form-control" value="<?php echo date('Y-m-d'); ?>" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label small fw-bold text-muted">SETORAN AWAL (OPSIONAL)</label>
                        <div class="input-group">
                            <span class="input-group-text">Rp</span>
                            <input type="number" step="any" name="setoran_awal" class="form-control" placeholder="0" min="0">
                        </div>
                    </div>
                    <div class="mb-0">
                        <label class="form-label small fw-bold text-muted">AKUN KAS / BANK PENERIMA SETORAN AWAL</label>
                        <select name="kode_akun_kas" class="form-select">
                            <?php foreach ($data['akun_kas'] as $ak): ?>
                                <?php if (str_starts_with($ak['kode_akun'], '1-10') || in_array($ak['tipe_akun'], ['Kas & Bank', 'Kas', 'Bank'])): ?>
                                <option value="<?php echo $ak['kode_akun']; ?>"><?php echo $ak['kode_akun'] . ' - ' . $ak['nama_akun']; ?></option>
                                <?php endif; ?>
                            <?php endforeach; ?>
                        </select>
                    </div>
                </div>
                <div class="modal-footer border-0 p-4 pt-0">
                    <button type="button" class="btn btn-light rounded-pill px-4" data-bs-dismiss="modal">Batal</button>
                    <button type="submit" class="btn btn-primary rounded-pill px-4 fw-bold">Buka Rekening</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Modal Transaksi Simpanan (Setor / Tarik) -->
<div class="modal fade" id="modalTransaksiSimpanan" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0 shadow-lg rounded-4">
            <form action="<?php echo BASEURL; ?>/simpanpinjam/simpanan_transaksi" method="POST">
                <div class="modal-header border-0 py-3" id="trxModalHeader">
                    <h5 class="modal-title fw-bold text-white" id="trxModalTitle">Transaksi Simpanan</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body p-4">
                    <div class="mb-3">
                        <label class="form-label small fw-bold text-muted">JENIS TRANSAKSI</label>
                        <select name="jenis_transaksi" id="trx_jenis" class="form-select fw-bold" required onchange="updateTrxModalStyle(this.value)">
                            <option value="Setor">Setor Simpanan (+)</option>
                            <option value="Tarik">Tarik Simpanan (-)</option>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label class="form-label small fw-bold text-muted">PILIH REKENING NASABAH</label>
                        <select name="id_rekening" class="form-select" required>
                            <option value="">-- Pilih Rekening Nasabah --</option>
                            <?php foreach ($data['rekening'] as $rk): ?>
                            <option value="<?php echo $rk['id_rekening']; ?>">
                                <?php echo htmlspecialchars($rk['no_rekening'] . ' - ' . $rk['nama_nasabah'] . ' (' . $rk['nama_simpanan'] . ' | Saldo: Rp ' . number_format($rk['saldo'], 0, ',', '.') . ')'); ?>
                            </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label class="form-label small fw-bold text-muted">TANGGAL TRANSAKSI</label>
                        <input type="date" name="tanggal" class="form-control" value="<?php echo date('Y-m-d'); ?>" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label small fw-bold text-muted">NOMINAL TRANSAKSI</label>
                        <div class="input-group">
                            <span class="input-group-text">Rp</span>
                            <input type="number" step="any" name="nominal" class="form-control fs-5 fw-bold" placeholder="0" min="1" required>
                        </div>
                    </div>
                    <div class="mb-3">
                        <label class="form-label small fw-bold text-muted">KAS / BANK</label>
                        <select name="kode_akun_kas" class="form-select" required>
                            <?php foreach ($data['akun_kas'] as $ak): ?>
                                <?php if (str_starts_with($ak['kode_akun'], '1-10') || in_array($ak['tipe_akun'], ['Kas & Bank', 'Kas', 'Bank'])): ?>
                                <option value="<?php echo $ak['kode_akun']; ?>"><?php echo $ak['kode_akun'] . ' - ' . $ak['nama_akun']; ?></option>
                                <?php endif; ?>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="mb-0">
                        <label class="form-label small fw-bold text-muted">KETERANGAN / BERITA</label>
                        <input type="text" name="keterangan" class="form-control" placeholder="Contoh: Setoran sukarela kas desa">
                    </div>
                </div>
                <div class="modal-footer border-0 p-4 pt-0">
                    <button type="button" class="btn btn-light rounded-pill px-4" data-bs-dismiss="modal">Batal</button>
                    <button type="submit" class="btn btn-primary rounded-pill px-4 fw-bold" id="trxSubmitBtn">Proses Transaksi</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Modal Tambah Jenis Simpanan -->
<div class="modal fade" id="modalTambahJenisSimpanan" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0 shadow-lg rounded-4">
            <form action="<?php echo BASEURL; ?>/simpanpinjam/jenis_simpanan_simpan" method="POST">
                <div class="modal-header bg-dark text-white border-0 py-3">
                    <h5 class="modal-title fw-bold">Tambah Produk Simpanan Baru</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body p-4">
                    <div class="mb-3">
                        <label class="form-label small fw-bold text-muted">KODE SIMPANAN</label>
                        <input type="text" name="kode_simpanan" class="form-control" placeholder="Contoh: THR, QUR, SIBER" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label small fw-bold text-muted">NAMA PRODUK SIMPANAN</label>
                        <input type="text" name="nama_simpanan" class="form-control" placeholder="Contoh: Tabungan Hari Raya, Simpanan Berjangka" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label small fw-bold text-muted">TIPE SIMPANAN</label>
                        <select name="tipe_simpanan" class="form-select" required>
                            <option value="Sukarela">Sukarela (Bisa setor & tarik kapan saja)</option>
                            <option value="Wajib">Wajib (Rutin periodik)</option>
                            <option value="Pokok">Pokok (Modal keanggotaan)</option>
                            <option value="Berjangka">Berjangka (Deposito / waktu tertentu)</option>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label class="form-label small fw-bold text-muted">MINIMAL SETORAN (RP)</label>
                        <input type="number" step="any" name="nominal_minimal_setor" class="form-control" value="10000">
                    </div>
                    <div class="mb-3">
                        <label class="form-label small fw-bold text-muted">BUNGA / BAGI HASIL (% PER TAHUN)</label>
                        <input type="number" step="0.01" name="bunga_pertahun" class="form-control" value="0.00">
                    </div>
                    <div class="mb-3">
                        <label class="form-label small fw-bold text-muted">KODE AKUN COA (KEWAJIBAN)</label>
                        <select name="kode_akun" class="form-select">
                            <option value="">-- Gunakan Akun Default Pengaturan --</option>
                            <?php foreach ($data['akun_kas'] as $ak): ?>
                            <option value="<?php echo $ak['kode_akun']; ?>"><?php echo $ak['kode_akun'] . ' - ' . $ak['nama_akun']; ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="mb-0">
                        <label class="form-label small fw-bold text-muted">KETERANGAN</label>
                        <textarea name="keterangan" class="form-control" rows="2"></textarea>
                    </div>
                </div>
                <div class="modal-footer border-0 p-4 pt-0">
                    <button type="button" class="btn btn-light rounded-pill px-4" data-bs-dismiss="modal">Batal</button>
                    <button type="submit" class="btn btn-primary rounded-pill px-4 fw-bold">Simpan Produk</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
function updateTrxModalStyle(jenis) {
    const hdr = document.getElementById('trxModalHeader');
    const title = document.getElementById('trxModalTitle');
    const btn = document.getElementById('trxSubmitBtn');
    if (jenis === 'Setor') {
        hdr.className = 'modal-header bg-success text-white border-0 py-3';
        title.innerText = 'Setor Simpanan Nasabah';
        btn.className = 'btn btn-success rounded-pill px-4 fw-bold';
        btn.innerText = 'Proses Setoran';
    } else {
        hdr.className = 'modal-header bg-danger text-white border-0 py-3';
        title.innerText = 'Tarik Simpanan Nasabah';
        btn.className = 'btn btn-danger rounded-pill px-4 fw-bold';
        btn.innerText = 'Proses Penarikan';
    }
}

document.addEventListener('DOMContentLoaded', function() {
    const modalTrx = document.getElementById('modalTransaksiSimpanan');
    modalTrx.addEventListener('show.bs.modal', function(event) {
        const button = event.relatedTarget;
        const jenis = button.getAttribute('data-jenis') || 'Setor';
        document.getElementById('trx_jenis').value = jenis;
        updateTrxModalStyle(jenis);
    });

    const searchInput = document.getElementById('cariRekening');
    if (searchInput) {
        searchInput.addEventListener('input', function() {
            const query = this.value.toLowerCase();
            document.querySelectorAll('#tabelRekening tbody tr').forEach(row => {
                row.style.display = row.innerText.toLowerCase().includes(query) ? '' : 'none';
            });
        });
    }
});
</script>
