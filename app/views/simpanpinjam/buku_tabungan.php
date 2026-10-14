<?php
$rek = $data['rekening'];
$mutasi = $data['mutasi'] ?? [];
$akunKasList = $data['akun_kas'] ?? [];
?>

<div class="d-flex flex-wrap justify-content-between align-items-center mb-4 gap-3">
    <div>
        <nav aria-label="breadcrumb">
            <ol class="breadcrumb mb-1 small">
                <li class="breadcrumb-item"><a href="<?php echo BASEURL; ?>/simpanpinjam">Simpan Pinjam</a></li>
                <li class="breadcrumb-item"><a href="<?php echo BASEURL; ?>/simpanpinjam/simpanan">Simpanan</a></li>
                <li class="breadcrumb-item active">Buku Tabungan</li>
            </ol>
        </nav>
        <h2 class="fw-bold mb-0">Buku Tabungan / Rekening Simpanan</h2>
    </div>
    <div class="d-flex flex-wrap gap-2 d-print-none">
        <button type="button" class="btn btn-outline-success rounded-pill px-3" data-bs-toggle="modal" data-bs-target="#modalTransaksiCepat" data-jenis="Setor">
            <i class="bi bi-arrow-down-circle me-1"></i> Setor Simpanan
        </button>
        <button type="button" class="btn btn-outline-danger rounded-pill px-3" data-bs-toggle="modal" data-bs-target="#modalTransaksiCepat" data-jenis="Tarik">
            <i class="bi bi-arrow-up-circle me-1"></i> Tarik Simpanan
        </button>
        <button type="button" class="btn btn-outline-secondary rounded-pill px-3" onclick="window.print()">
            <i class="bi bi-printer me-1"></i> Cetak Mutasi
        </button>
        <a href="<?php echo BASEURL; ?>/simpanpinjam/simpanan" class="btn btn-light rounded-pill px-3">
            <i class="bi bi-arrow-left me-1"></i> Kembali
        </a>
    </div>
</div>

<!-- Header Card Identitas Rekening -->
<div class="card border-0 shadow-sm rounded-4 mb-4 overflow-hidden">
    <div class="card-body p-4 bg-primary text-white">
        <div class="row align-items-center g-3">
            <div class="col-md-7">
                <span class="badge bg-white text-primary rounded-pill px-3 py-1 mb-2 fw-bold">
                    <?php echo htmlspecialchars($rek['tipe_simpanan']); ?> - <?php echo htmlspecialchars($rek['nama_simpanan']); ?>
                </span>
                <h3 class="fw-bold mb-1 font-monospace"><?php echo htmlspecialchars($rek['no_rekening']); ?></h3>
                <div class="fs-5 opacity-90"><?php echo htmlspecialchars($rek['nama_nasabah']); ?> (No. Anggota: <?php echo htmlspecialchars($rek['no_anggota']); ?>)</div>
                <div class="small opacity-75 mt-1">
                    <i class="bi bi-telephone me-1"></i> <?php echo htmlspecialchars($rek['no_telp'] ?? '-'); ?> 
                    <span class="mx-2">&bull;</span>
                    <i class="bi bi-calendar3 me-1"></i> Dibuka sejak: <?php echo date('d F Y', strtotime($rek['tanggal_buka'])); ?>
                </div>
            </div>
            <div class="col-md-5 text-md-end">
                <div class="small opacity-75 text-uppercase fw-semibold">Saldo Tersedia</div>
                <h1 class="fw-bold mb-0 display-6">Rp <?php echo number_format($rek['saldo'], 0, ',', '.'); ?></h1>
                <span class="badge <?php echo ($rek['status'] === 'Aktif') ? 'bg-success' : 'bg-secondary'; ?> rounded-pill px-3 py-1 mt-2">
                    Status Rekening: <?php echo $rek['status']; ?>
                </span>
            </div>
        </div>
    </div>
</div>

<!-- Tabel Mutasi / Buku Tabungan -->
<div class="card border-0 shadow-sm rounded-4 overflow-hidden">
    <div class="card-header bg-white py-3 border-0 d-flex justify-content-between align-items-center">
        <h6 class="fw-bold mb-0"><i class="bi bi-journal-text me-1 text-primary"></i> Lembar Mutasi Transaksi Tabungan</h6>
        <span class="badge bg-light text-muted border">Total <?php echo count($mutasi); ?> Transaksi</span>
    </div>
    <div class="table-responsive">
        <table class="table table-hover align-middle mb-0" id="tabelBukuTabungan">
            <thead class="table-light small">
                <tr>
                    <th class="ps-3" style="width: 50px;">No</th>
                    <th>Tanggal</th>
                    <th>No. Transaksi</th>
                    <th>Keterangan / Uraian</th>
                    <th class="text-end">Setoran (Kredit)</th>
                    <th class="text-end">Penarikan (Debit)</th>
                    <th class="text-end pe-3">Saldo Akhir</th>
                </tr>
            </thead>
            <tbody class="small">
                <?php if (empty($mutasi)): ?>
                    <tr>
                        <td colspan="7" class="text-center py-5 text-muted">
                            <i class="bi bi-inbox fs-1 d-block mb-2 text-secondary opacity-50"></i>
                            Belum ada riwayat mutasi transaksi pada rekening ini.
                        </td>
                    </tr>
                <?php else: ?>
                    <?php 
                    $no = 1;
                    $totalSetor = 0;
                    $totalTarik = 0;
                    foreach ($mutasi as $m): 
                        if ($m['jenis_transaksi'] === 'Setor') {
                            $totalSetor += (float)$m['nominal'];
                        } else {
                            $totalTarik += (float)$m['nominal'];
                        }
                    ?>
                    <tr>
                        <td class="ps-3 text-muted"><?php echo $no++; ?></td>
                        <td><?php echo date('d/m/Y', strtotime($m['tanggal'])); ?></td>
                        <td class="font-monospace text-primary"><?php echo htmlspecialchars($m['no_transaksi']); ?></td>
                        <td>
                            <div><?php echo htmlspecialchars($m['keterangan'] ?: ($m['jenis_transaksi'] . ' Simpanan')); ?></div>
                            <span class="text-muted" style="font-size:0.75rem;">Akun Kas: <?php echo htmlspecialchars($m['kode_akun_kas'] ?? '-'); ?></span>
                        </td>
                        <td class="text-end text-success fw-semibold">
                            <?php if ($m['jenis_transaksi'] === 'Setor'): ?>
                                +Rp <?php echo number_format($m['nominal'], 0, ',', '.'); ?>
                            <?php else: ?>
                                -
                            <?php endif; ?>
                        </td>
                        <td class="text-end text-danger fw-semibold">
                            <?php if ($m['jenis_transaksi'] === 'Tarik'): ?>
                                -Rp <?php echo number_format($m['nominal'], 0, ',', '.'); ?>
                            <?php else: ?>
                                -
                            <?php endif; ?>
                        </td>
                        <td class="text-end pe-3 fw-bold">
                            Rp <?php echo number_format($m['saldo_sesudah'], 0, ',', '.'); ?>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
            <?php if (!empty($mutasi)): ?>
            <tfoot class="table-light fw-bold small">
                <tr>
                    <td colspan="4" class="text-end ps-3">Total Akumulasi:</td>
                    <td class="text-end text-success">+Rp <?php echo number_format($totalSetor, 0, ',', '.'); ?></td>
                    <td class="text-end text-danger">-Rp <?php echo number_format($totalTarik, 0, ',', '.'); ?></td>
                    <td class="text-end pe-3 text-primary">Rp <?php echo number_format($rek['saldo'], 0, ',', '.'); ?></td>
                </tr>
            </tfoot>
            <?php endif; ?>
        </table>
    </div>
</div>

<!-- Modal Transaksi Cepat (Setor / Tarik Langsung) -->
<div class="modal fade" id="modalTransaksiCepat" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0 shadow rounded-4">
            <form action="<?php echo BASEURL; ?>/simpanpinjam/simpanan_transaksi" method="POST">
                <input type="hidden" name="id_rekening" value="<?php echo $rek['id_rekening']; ?>">
                <input type="hidden" name="jenis_transaksi" id="modal_jenis_transaksi" value="Setor">
                
                <div class="modal-header border-0 pb-0">
                    <h5 class="modal-title fw-bold" id="modalTransaksiTitle">Setor Simpanan</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body p-4">
                    <div class="p-3 bg-light rounded-3 mb-3 small">
                        <div class="d-flex justify-content-between mb-1">
                            <span class="text-muted">No. Rekening:</span>
                            <span class="fw-bold font-monospace"><?php echo htmlspecialchars($rek['no_rekening']); ?></span>
                        </div>
                        <div class="d-flex justify-content-between mb-1">
                            <span class="text-muted">Nama Nasabah:</span>
                            <span class="fw-bold"><?php echo htmlspecialchars($rek['nama_nasabah']); ?></span>
                        </div>
                        <div class="d-flex justify-content-between">
                            <span class="text-muted">Saldo Saat Ini:</span>
                            <span class="fw-bold text-success">Rp <?php echo number_format($rek['saldo'], 0, ',', '.'); ?></span>
                        </div>
                    </div>

                    <div class="mb-3">
                        <label class="form-label small fw-bold text-muted">TANGGAL TRANSAKSI</label>
                        <input type="date" name="tanggal" class="form-control" value="<?php echo date('Y-m-d'); ?>" required>
                    </div>

                    <div class="mb-3">
                        <label class="form-label small fw-bold text-muted" id="labelNominal">NOMINAL SETORAN (RP)</label>
                        <div class="input-group">
                            <span class="input-group-text bg-white">Rp</span>
                            <input type="number" step="1000" name="nominal" id="inputNominal" class="form-control form-control-lg fw-bold" placeholder="0" required>
                        </div>
                        <div class="form-text small" id="helpNominal"></div>
                    </div>

                    <div class="mb-3">
                        <label class="form-label small fw-bold text-muted">AKUN KAS / BANK PENAMPUNG</label>
                        <select name="kode_akun_kas" class="form-select" required>
                            <?php foreach ($akunKasList as $idx => $ak): ?>
                                <option value="<?php echo htmlspecialchars($ak['kode_akun']); ?>" <?php echo ($idx === 0) ? 'selected' : ''; ?>>
                                    <?php echo htmlspecialchars($ak['kode_akun'] . ' - ' . $ak['nama_akun']) . ' (' . htmlspecialchars($ak['tipe_akun']) . ')'; ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div class="mb-3">
                        <label class="form-label small fw-bold text-muted">KETERANGAN / BERITA</label>
                        <textarea name="keterangan" class="form-control" rows="2" placeholder="Catatan transaksi..."></textarea>
                    </div>
                </div>
                <div class="modal-footer border-0 pt-0">
                    <button type="button" class="btn btn-light rounded-pill px-3" data-bs-dismiss="modal">Batal</button>
                    <button type="submit" class="btn btn-primary rounded-pill px-4" id="btnSubmitTrx">Konfirmasi Transaksi</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    var modalTransaksi = document.getElementById('modalTransaksiCepat');
    if (modalTransaksi) {
        modalTransaksi.addEventListener('show.bs.modal', function(event) {
            var button = event.relatedTarget;
            var jenis = button.getAttribute('data-jenis') || 'Setor';
            
            document.getElementById('modal_jenis_transaksi').value = jenis;
            var title = document.getElementById('modalTransaksiTitle');
            var labelNominal = document.getElementById('labelNominal');
            var btnSubmit = document.getElementById('btnSubmitTrx');
            var helpNominal = document.getElementById('helpNominal');

            if (jenis === 'Tarik') {
                title.innerHTML = '<i class="bi bi-arrow-up-circle text-danger me-1"></i> Tarik Simpanan';
                labelNominal.innerText = 'NOMINAL PENARIKAN (RP)';
                btnSubmit.className = 'btn btn-danger rounded-pill px-4';
                btnSubmit.innerText = 'Konfirmasi Penarikan';
                helpNominal.innerText = 'Maksimal penarikan: Rp <?php echo number_format($rek['saldo'], 0, ',', '.'); ?>';
            } else {
                title.innerHTML = '<i class="bi bi-arrow-down-circle text-success me-1"></i> Setor Simpanan';
                labelNominal.innerText = 'NOMINAL SETORAN (RP)';
                btnSubmit.className = 'btn btn-success rounded-pill px-4';
                btnSubmit.innerText = 'Konfirmasi Setoran';
                helpNominal.innerText = 'Minimal setoran sesuai ketentuan produk simpanan.';
            }
        });
    }
});
</script>

<style>
@media print {
    body {
        background: #fff!important;
        font-size: 11pt;
    }
    .d-print-none, .sidebar, .navbar, footer {
        display: none!important;
    }
    .card {
        box-shadow: none!important;
        border: 1px solid #ddd!important;
    }
    .card-body.bg-primary {
        background-color: #f8f9fa!important;
        color: #000!important;
        border-bottom: 2px solid #000;
    }
    .badge {
        border: 1px solid #000!important;
        color: #000!important;
        background: none!important;
    }
}
</style>
