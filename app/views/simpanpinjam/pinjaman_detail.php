<?php
$p = $data['pinjaman'];
$jadwal = $p['jadwal_angsuran'] ?? [];
$akunKasList = $data['akun_kas'] ?? [];
$isKelompok = ($p['tipe_debitur'] === 'Kelompok');
$debiturNama = $isKelompok ? $p['nama_kelompok'] : $p['nama_nasabah'];
?>

<div class="d-flex flex-wrap justify-content-between align-items-center mb-4 gap-3">
    <div>
        <nav aria-label="breadcrumb">
            <ol class="breadcrumb mb-1 small">
                <li class="breadcrumb-item"><a href="<?php echo BASEURL; ?>/simpanpinjam">Simpan Pinjam</a></li>
                <li class="breadcrumb-item"><a href="<?php echo BASEURL; ?>/simpanpinjam/pinjaman">Pinjaman</a></li>
                <li class="breadcrumb-item active"><?php echo htmlspecialchars($p['no_pinjaman']); ?></li>
            </ol>
        </nav>
        <h2 class="fw-bold mb-0 d-flex align-items-center gap-2">
            <span>Pinjaman: <?php echo htmlspecialchars($p['no_pinjaman']); ?></span>
            <?php
            $badge = match($p['status']) {
                'Diajukan' => 'bg-warning text-dark',
                'Disetujui' => 'bg-info text-white',
                'Dicairkan' => 'bg-primary text-white',
                'Lunas' => 'bg-success text-white',
                default => 'bg-danger text-white'
            };
            ?>
            <span class="badge <?php echo $badge; ?> fs-6 rounded-pill px-3 py-1"><?php echo $p['status']; ?></span>
        </h2>
    </div>
    <div class="d-flex flex-wrap gap-2 d-print-none">
        <?php if ($p['status'] === 'Diajukan'): ?>
            <button type="button" class="btn btn-success rounded-pill px-3" data-bs-toggle="modal" data-bs-target="#modalPersetujuan">
                <i class="bi bi-check-circle-fill me-1"></i> Proses Persetujuan
            </button>
        <?php elseif ($p['status'] === 'Disetujui'): ?>
            <button type="button" class="btn btn-primary rounded-pill px-4" data-bs-toggle="modal" data-bs-target="#modalPencairan">
                <i class="bi bi-cash-coin me-1"></i> Cairkan Pinjaman
            </button>
        <?php endif; ?>
        
        <button type="button" class="btn btn-outline-secondary rounded-pill px-3" onclick="window.print()">
            <i class="bi bi-printer me-1"></i> Cetak Kartu Pinjaman
        </button>
        <a href="<?php echo BASEURL; ?>/simpanpinjam/pinjaman" class="btn btn-light rounded-pill px-3">
            <i class="bi bi-arrow-left me-1"></i> Kembali
        </a>
    </div>
</div>

<!-- Header Card Status Pinjaman -->
<div class="row g-4 mb-4">
    <!-- Informasi Debitur -->
    <div class="col-lg-4">
        <div class="card border-0 shadow-sm rounded-4 h-100 p-4">
            <h6 class="fw-bold text-muted small text-uppercase mb-3">
                <i class="bi bi-person-badge text-primary me-1"></i> Informasi Debitur (Peminjam)
            </h6>
            <div class="d-flex align-items-center gap-2 mb-3">
                <span class="badge <?php echo $isKelompok ? 'bg-info' : 'bg-secondary'; ?> rounded-pill">
                    <?php echo $p['tipe_debitur']; ?>
                </span>
                <h5 class="fw-bold mb-0 text-dark"><?php echo htmlspecialchars($debiturNama); ?></h5>
            </div>
            
            <table class="table table-sm table-borderless small mb-0">
                <?php if ($isKelompok): ?>
                    <tr>
                        <td class="text-muted" style="width: 120px;">Kode Kelompok</td>
                        <td class="fw-bold font-monospace"><?php echo htmlspecialchars($p['kode_kelompok'] ?? '-'); ?></td>
                    </tr>
                    <tr>
                        <td class="text-muted">Ketua Kelompok</td>
                        <td class="fw-bold"><?php echo htmlspecialchars($p['nama_ketua'] ?? '-'); ?></td>
                    </tr>
                    <tr>
                        <td class="text-muted">Kontak</td>
                        <td><?php echo htmlspecialchars($p['kontak_kelompok'] ?? '-'); ?></td>
                    </tr>
                <?php else: ?>
                    <tr>
                        <td class="text-muted" style="width: 120px;">No. Anggota</td>
                        <td class="fw-bold font-monospace"><?php echo htmlspecialchars($p['no_anggota'] ?? '-'); ?></td>
                    </tr>
                    <tr>
                        <td class="text-muted">NIK</td>
                        <td><?php echo htmlspecialchars($p['nik'] ?? '-'); ?></td>
                    </tr>
                    <tr>
                        <td class="text-muted">No. Telepon</td>
                        <td><?php echo htmlspecialchars($p['no_telp'] ?? '-'); ?></td>
                    </tr>
                    <tr>
                        <td class="text-muted">Alamat</td>
                        <td><?php echo htmlspecialchars($p['alamat'] ?? '-'); ?></td>
                    </tr>
                <?php endif; ?>
                <tr>
                    <td class="text-muted">Agunan / Jaminan</td>
                    <td class="fw-semibold text-warning-emphasis"><?php echo htmlspecialchars($p['agunan'] ?: 'Tidak ada (Tanpa Jaminan)'); ?></td>
                </tr>
                <tr>
                    <td class="text-muted">Tujuan Pinjaman</td>
                    <td><?php echo htmlspecialchars($p['tujuan_pinjaman'] ?: '-'); ?></td>
                </tr>
            </table>
        </div>
    </div>

    <!-- Informasi Plafon & Angsuran -->
    <div class="col-lg-8">
        <div class="card border-0 shadow-sm rounded-4 h-100 overflow-hidden">
            <div class="card-body p-4">
                <div class="row g-3">
                    <div class="col-md-4">
                        <span class="text-muted small fw-bold text-uppercase d-block mb-1">Plafon Disetujui</span>
                        <h3 class="fw-bold text-primary mb-0">
                            Rp <?php echo number_format(($p['status'] === 'Diajukan') ? $p['jumlah_pengajuan'] : $p['jumlah_disetujui'], 0, ',', '.'); ?>
                        </h3>
                        <small class="text-muted">Pengajuan: Rp <?php echo number_format($p['jumlah_pengajuan'], 0, ',', '.'); ?></small>
                    </div>
                    <div class="col-md-4">
                        <span class="text-muted small fw-bold text-uppercase d-block mb-1">Sisa Pokok (Outstanding)</span>
                        <h3 class="fw-bold text-danger mb-0">
                            Rp <?php echo number_format($p['sisa_pokok'], 0, ',', '.'); ?>
                        </h3>
                        <small class="text-muted">Sisa Jasa: Rp <?php echo number_format($p['sisa_jasa'], 0, ',', '.'); ?></small>
                    </div>
                    <div class="col-md-4">
                        <span class="text-muted small fw-bold text-uppercase d-block mb-1">Angsuran per Bulan</span>
                        <h3 class="fw-bold text-success mb-0">
                            Rp <?php echo number_format($p['total_angsuran_per_periode'], 0, ',', '.'); ?>
                        </h3>
                        <small class="text-muted">Pokok: Rp <?php echo number_format($p['angsuran_pokok_per_periode'], 0, ',', '.'); ?> + Jasa: Rp <?php echo number_format($p['angsuran_jasa_per_periode'], 0, ',', '.'); ?></small>
                    </div>
                </div>

                <hr class="my-3">

                <div class="row g-2 small">
                    <div class="col-sm-4">
                        <span class="text-muted">Produk Pinjaman:</span>
                        <div class="fw-bold"><?php echo htmlspecialchars($p['nama_pinjaman']); ?></div>
                    </div>
                    <div class="col-sm-4">
                        <span class="text-muted">Suku Bunga & Sistem:</span>
                        <div class="fw-bold"><?php echo $p['suku_bunga']; ?>% / bln (<?php echo $p['sistem_bunga']; ?>)</div>
                    </div>
                    <div class="col-sm-4">
                        <span class="text-muted">Tenor:</span>
                        <div class="fw-bold"><?php echo $p['jangka_waktu']; ?> Bulan</div>
                    </div>
                    <div class="col-sm-4">
                        <span class="text-muted">Tanggal Pengajuan:</span>
                        <div class="fw-bold"><?php echo date('d F Y', strtotime($p['tanggal_pengajuan'])); ?></div>
                    </div>
                    <div class="col-sm-4">
                        <span class="text-muted">Tanggal Persetujuan:</span>
                        <div class="fw-bold"><?php echo !empty($p['tanggal_persetujuan']) ? date('d F Y', strtotime($p['tanggal_persetujuan'])) : '-'; ?></div>
                    </div>
                    <div class="col-sm-4">
                        <span class="text-muted">Tanggal Pencairan:</span>
                        <div class="fw-bold"><?php echo !empty($p['tanggal_pencairan']) ? date('d F Y', strtotime($p['tanggal_pencairan'])) : '-'; ?></div>
                    </div>
                </div>

                <?php if (!empty($p['catatan_persetujuan'])): ?>
                <div class="alert alert-light border small mt-3 mb-0 py-2">
                    <strong>Catatan Persetujuan:</strong> <?php echo htmlspecialchars($p['catatan_persetujuan']); ?>
                </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<!-- Kartu Jadwal Angsuran & Pembayaran -->
<div class="card border-0 shadow-sm rounded-4 overflow-hidden mb-4">
    <div class="card-header bg-white py-3 border-0 d-flex justify-content-between align-items-center">
        <h6 class="fw-bold mb-0"><i class="bi bi-calendar-check me-1 text-primary"></i> Jadwal Angsuran & Rekam Pembayaran</h6>
        <span class="badge bg-light text-muted border">Total <?php echo count($jadwal); ?> Angsuran</span>
    </div>
    <div class="table-responsive">
        <table class="table table-hover align-middle mb-0" id="tabelJadwalAngsuran">
            <thead class="table-light small">
                <tr>
                    <th class="ps-3 text-center" style="width: 50px;">Ke-</th>
                    <th>Jatuh Tempo</th>
                    <th class="text-end">Pokok</th>
                    <th class="text-end">Jasa / Bunga</th>
                    <th class="text-end">Total Tagihan</th>
                    <th class="text-end">Denda</th>
                    <th class="text-end">Total Bayar</th>
                    <th>Tgl Bayar</th>
                    <th class="text-center">Status</th>
                    <th class="text-end pe-3 d-print-none">Aksi</th>
                </tr>
            </thead>
            <tbody class="small">
                <?php if (empty($jadwal)): ?>
                    <tr>
                        <td colspan="10" class="text-center py-5 text-muted">
                            <i class="bi bi-clock-history fs-1 d-block mb-2 text-secondary opacity-50"></i>
                            Jadwal angsuran belum terbentuk. Jadwal akan di-generate otomatis saat pinjaman dicairkan.
                        </td>
                    </tr>
                <?php else: ?>
                    <?php 
                    $today = date('Y-m-d');
                    foreach ($jadwal as $j): 
                        $isTerlambat = ($j['status_bayar'] !== 'Lunas' && $j['jatuh_tempo'] < $today);
                    ?>
                    <tr class="<?php echo $isTerlambat ? 'table-warning' : ''; ?>">
                        <td class="ps-3 text-center fw-bold text-muted"><?php echo $j['angsuran_ke']; ?></td>
                        <td>
                            <span class="fw-semibold <?php echo $isTerlambat ? 'text-danger' : ''; ?>">
                                <?php echo date('d/m/Y', strtotime($j['jatuh_tempo'])); ?>
                            </span>
                            <?php if ($isTerlambat): ?>
                                <span class="badge bg-danger text-white rounded-pill ms-1" style="font-size:0.65rem;">Menunggak</span>
                            <?php endif; ?>
                        </td>
                        <td class="text-end">Rp <?php echo number_format($j['pokok'], 0, ',', '.'); ?></td>
                        <td class="text-end">Rp <?php echo number_format($j['jasa'], 0, ',', '.'); ?></td>
                        <td class="text-end fw-bold">Rp <?php echo number_format($j['total_tagihan'], 0, ',', '.'); ?></td>
                        <td class="text-end text-danger"><?php echo ($j['denda'] > 0) ? 'Rp ' . number_format($j['denda'], 0, ',', '.') : '-'; ?></td>
                        <td class="text-end text-success fw-bold"><?php echo ($j['total_bayar'] > 0) ? 'Rp ' . number_format($j['total_bayar'], 0, ',', '.') : '-'; ?></td>
                        <td><?php echo !empty($j['tanggal_bayar']) ? date('d/m/Y', strtotime($j['tanggal_bayar'])) : '-'; ?></td>
                        <td class="text-center">
                            <?php if ($j['status_bayar'] === 'Lunas'): ?>
                                <span class="badge bg-success-subtle text-success rounded-pill px-3 py-1"><i class="bi bi-check-circle me-1"></i> Lunas</span>
                            <?php else: ?>
                                <span class="badge bg-secondary-subtle text-secondary rounded-pill px-3 py-1">Belum</span>
                            <?php endif; ?>
                        </td>
                        <td class="text-end pe-3 d-print-none">
                            <?php if ($p['status'] === 'Dicairkan' && $j['status_bayar'] !== 'Lunas'): ?>
                                <button type="button" class="btn btn-sm btn-success rounded-pill px-3 btn-bayar-angsuran" 
                                        data-bs-toggle="modal" 
                                        data-bs-target="#modalBayarAngsuran"
                                        data-id="<?php echo $j['id_jadwal']; ?>"
                                        data-ke="<?php echo $j['angsuran_ke']; ?>"
                                        data-jt="<?php echo date('d/m/Y', strtotime($j['jatuh_tempo'])); ?>"
                                        data-pokok="<?php echo $j['pokok']; ?>"
                                        data-jasa="<?php echo $j['jasa']; ?>"
                                        data-tagihan="<?php echo $j['total_tagihan']; ?>">
                                    <i class="bi bi-cash me-1"></i> Bayar
                                </button>
                            <?php elseif ($j['status_bayar'] === 'Lunas'): ?>
                                <span class="text-muted small"><i class="bi bi-check2-all text-success"></i> Terbayar</span>
                            <?php else: ?>
                                <span class="text-muted">-</span>
                            <?php endif; ?>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<!-- MODAL 1: PERSETUJUAN PINJAMAN -->
<div class="modal fade" id="modalPersetujuan" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0 shadow rounded-4">
            <form action="<?php echo BASEURL; ?>/simpanpinjam/pinjaman_persetujuan" method="POST">
                <input type="hidden" name="id_pinjaman" value="<?php echo $p['id_pinjaman']; ?>">
                <div class="modal-header border-0 pb-0">
                    <h5 class="modal-title fw-bold"><i class="bi bi-check2-circle text-primary me-2"></i>Persetujuan Pinjaman</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body p-4">
                    <div class="mb-3">
                        <label class="form-label small fw-bold text-muted">KEPUTUSAN</label>
                        <select name="status" id="status_putusan" class="form-select" onchange="toggleDisetujuiNominal(this.value)" required>
                            <option value="Disetujui">Setujui Pengajuan</option>
                            <option value="Ditolak">Tolak Pengajuan</option>
                        </select>
                    </div>

                    <div class="mb-3" id="nominal_disetujui_group">
                        <label class="form-label small fw-bold text-muted">PLAFON NOMINAL YANG DISETUJUI (RP)</label>
                        <div class="input-group">
                            <span class="input-group-text bg-white">Rp</span>
                            <input type="number" step="50000" name="jumlah_disetujui" class="form-control form-control-lg fw-bold" value="<?php echo $p['jumlah_pengajuan']; ?>" required>
                        </div>
                        <div class="form-text small">Dapat disesuaikan jika nominal persetujuan berbeda dari nominal pengajuan.</div>
                    </div>

                    <div class="mb-3">
                        <label class="form-label small fw-bold text-muted">CATATAN / REKOMENDASI KOMITE KREDIT</label>
                        <textarea name="catatan_persetujuan" class="form-control" rows="3" placeholder="Catatan analisa kredit atau alasan penolakan..."></textarea>
                    </div>
                </div>
                <div class="modal-footer border-0 pt-0">
                    <button type="button" class="btn btn-light rounded-pill px-3" data-bs-dismiss="modal">Batal</button>
                    <button type="submit" class="btn btn-primary rounded-pill px-4">Simpan Keputusan</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- MODAL 2: PENCAIRAN PINJAMAN -->
<div class="modal fade" id="modalPencairan" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0 shadow rounded-4">
            <form action="<?php echo BASEURL; ?>/simpanpinjam/pinjaman_cairkan" method="POST">
                <input type="hidden" name="id_pinjaman" value="<?php echo $p['id_pinjaman']; ?>">
                <div class="modal-header border-0 pb-0">
                    <h5 class="modal-title fw-bold text-primary"><i class="bi bi-cash-stack me-2"></i>Pencairan Pinjaman</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body p-4">
                    <div class="p-3 bg-light rounded-3 mb-3 small">
                        <div class="d-flex justify-content-between mb-1">
                            <span class="text-muted">Peminjam:</span>
                            <span class="fw-bold"><?php echo htmlspecialchars($debiturNama); ?></span>
                        </div>
                        <div class="d-flex justify-content-between mb-1">
                            <span class="text-muted">Nominal Pencairan:</span>
                            <span class="fw-bold text-primary fs-6">Rp <?php echo number_format($p['jumlah_disetujui'], 0, ',', '.'); ?></span>
                        </div>
                        <div class="d-flex justify-content-between">
                            <span class="text-muted">Tenor Pinjaman:</span>
                            <span class="fw-bold"><?php echo $p['jangka_waktu']; ?> Bulan</span>
                        </div>
                    </div>

                    <div class="mb-3">
                        <label class="form-label small fw-bold text-muted">TANGGAL PENCAIRAN</label>
                        <input type="date" name="tanggal_pencairan" class="form-control" value="<?php echo date('Y-m-d'); ?>" required>
                    </div>

                    <div class="mb-3">
                        <label class="form-label small fw-bold text-muted">SUMBER AKUN KAS / BANK PENCAIRAN</label>
                        <select name="kode_akun_kas_pencairan" class="form-select" required>
                            <?php foreach ($akunKasList as $ak): ?>
                                <?php if (str_starts_with($ak['kode_akun'], '1-10') || in_array($ak['tipe_akun'], ['Kas & Bank', 'Kas', 'Bank'])): ?>
                                <option value="<?php echo $ak['kode_akun']; ?>" <?php echo ($ak['kode_akun'] === '1-10001') ? 'selected' : ''; ?>>
                                    <?php echo $ak['kode_akun'] . ' - ' . $ak['nama_akun']; ?>
                                </option>
                                <?php endif; ?>
                            <?php endforeach; ?>
                        </select>
                        <div class="form-text small">Kas yang berkurang saat pinjaman diserahkan kepada nasabah.</div>
                    </div>

                    <div class="alert alert-info border-0 rounded-3 small mb-0">
                        <i class="bi bi-info-circle-fill me-1"></i> Setelah pencairan dikonfirmasi, sistem akan otomatis:
                        <ul class="mb-0 ps-3 mt-1">
                            <li>Menghasilkan <?php echo $p['jangka_waktu']; ?> baris jadwal angsuran bulanan.</li>
                            <li>Membukukan jurnal pengeluaran kas (Debit: Piutang Pinjaman, Kredit: Kas/Bank).</li>
                        </ul>
                    </div>
                </div>
                <div class="modal-footer border-0 pt-0">
                    <button type="button" class="btn btn-light rounded-pill px-3" data-bs-dismiss="modal">Batal</button>
                    <button type="submit" class="btn btn-primary rounded-pill px-4" onclick="return confirm('Apakah Anda yakin data pencairan sudah valid?')">Konfirmasi & Cairkan</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- MODAL 3: BAYAR ANGSURAN -->
<div class="modal fade" id="modalBayarAngsuran" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0 shadow rounded-4">
            <form action="<?php echo BASEURL; ?>/simpanpinjam/angsuran_bayar" method="POST">
                <input type="hidden" name="id_pinjaman" value="<?php echo $p['id_pinjaman']; ?>">
                <input type="hidden" name="id_jadwal" id="bayar_id_jadwal" value="">
                
                <div class="modal-header border-0 pb-0">
                    <h5 class="modal-title fw-bold text-success"><i class="bi bi-cash-stack me-2"></i>Penerimaan Pembayaran Angsuran</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body p-4">
                    <div class="p-3 bg-light rounded-3 mb-3 small">
                        <div class="d-flex justify-content-between mb-1">
                            <span class="text-muted">Angsuran Ke:</span>
                            <span class="fw-bold" id="bayar_ke_text">-</span>
                        </div>
                        <div class="d-flex justify-content-between mb-1">
                            <span class="text-muted">Jatuh Tempo:</span>
                            <span class="fw-bold text-danger" id="bayar_jt_text">-</span>
                        </div>
                        <div class="d-flex justify-content-between mb-1">
                            <span class="text-muted">Pokok:</span>
                            <span class="fw-bold" id="bayar_pokok_text">Rp 0</span>
                        </div>
                        <div class="d-flex justify-content-between">
                            <span class="text-muted">Jasa / Bunga:</span>
                            <span class="fw-bold text-danger" id="bayar_jasa_text">Rp 0</span>
                        </div>
                    </div>

                    <div class="mb-3">
                        <label class="form-label small fw-bold text-muted">TANGGAL PEMBAYARAN</label>
                        <input type="date" name="tanggal_bayar" class="form-control" value="<?php echo date('Y-m-d'); ?>" required>
                    </div>

                    <div class="mb-3">
                        <label class="form-label small fw-bold text-muted">DENDA KETERLAMBATAN (RP, JIKA ADA)</label>
                        <input type="number" step="1000" name="denda" id="bayar_denda" class="form-control" value="0" oninput="updateTotalBayarModal()">
                    </div>

                    <div class="mb-3">
                        <label class="form-label small fw-bold text-muted">TOTAL NOMINAL DITERIMA (RP)</label>
                        <div class="input-group">
                            <span class="input-group-text bg-white">Rp</span>
                            <input type="text" id="bayar_total_tampil" class="form-control form-control-lg fw-bold text-success" readonly>
                        </div>
                    </div>

                    <div class="mb-3">
                        <label class="form-label small fw-bold text-muted">AKUN KAS / BANK PENERIMAAN</label>
                        <select name="kode_akun_kas" class="form-select" required>
                            <?php foreach ($akunKasList as $ak): ?>
                                <?php if (str_starts_with($ak['kode_akun'], '1-10') || in_array($ak['tipe_akun'], ['Kas & Bank', 'Kas', 'Bank'])): ?>
                                <option value="<?php echo $ak['kode_akun']; ?>" <?php echo ($ak['kode_akun'] === '1-10001') ? 'selected' : ''; ?>>
                                    <?php echo $ak['kode_akun'] . ' - ' . $ak['nama_akun']; ?>
                                </option>
                                <?php endif; ?>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div class="mb-3">
                        <label class="form-label small fw-bold text-muted">KETERANGAN / CATATAN BUKTI SETOR</label>
                        <input type="text" name="keterangan" class="form-control" placeholder="Contoh: Pembayaran angsuran tunai">
                    </div>
                </div>
                <div class="modal-footer border-0 pt-0">
                    <button type="button" class="btn btn-light rounded-pill px-3" data-bs-dismiss="modal">Batal</button>
                    <button type="submit" class="btn btn-success rounded-pill px-4">Terima Pembayaran</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
function toggleDisetujuiNominal(val) {
    var group = document.getElementById('nominal_disetujui_group');
    if (val === 'Ditolak') {
        group.classList.add('d-none');
    } else {
        group.classList.remove('d-none');
    }
}

var currentTagihan = 0;

document.addEventListener('DOMContentLoaded', function() {
    var modalBayar = document.getElementById('modalBayarAngsuran');
    if (modalBayar) {
        modalBayar.addEventListener('show.bs.modal', function(event) {
            var button = event.relatedTarget;
            var id = button.getAttribute('data-id');
            var ke = button.getAttribute('data-ke');
            var jt = button.getAttribute('data-jt');
            var pokok = parseFloat(button.getAttribute('data-pokok')) || 0;
            var jasa = parseFloat(button.getAttribute('data-jasa')) || 0;
            var tagihan = parseFloat(button.getAttribute('data-tagihan')) || 0;

            currentTagihan = tagihan;

            document.getElementById('bayar_id_jadwal').value = id;
            document.getElementById('bayar_ke_text').innerText = 'Ke-' + ke;
            document.getElementById('bayar_jt_text').innerText = jt;
            document.getElementById('bayar_pokok_text').innerText = 'Rp ' + pokok.toLocaleString('id-ID');
            document.getElementById('bayar_jasa_text').innerText = 'Rp ' + jasa.toLocaleString('id-ID');
            document.getElementById('bayar_denda').value = '0';
            
            updateTotalBayarModal();
        });
    }
});

function updateTotalBayarModal() {
    var denda = parseFloat(document.getElementById('bayar_denda').value) || 0;
    var total = currentTagihan + denda;
    document.getElementById('bayar_total_tampil').value = total.toLocaleString('id-ID');
}
</script>

<style>
@media print {
    body {
        background: #fff!important;
        font-size: 10pt;
    }
    .d-print-none, .sidebar, .navbar, footer {
        display: none!important;
    }
    .card {
        border: 1px solid #ddd!important;
        box-shadow: none!important;
    }
}
</style>
