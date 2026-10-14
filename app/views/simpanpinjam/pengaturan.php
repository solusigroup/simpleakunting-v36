<?php
$p = $data['pengaturan'] ?? [];
$akunKasBank = $data['akun_kas_bank'] ?? [];
$akunDetail = $data['akun_detail'] ?? [];
$akunPiutang = $data['akun_piutang'] ?? [];
$akunKewajiban = $data['akun_kewajiban'] ?? [];
$akunEkuitas = $data['akun_ekuitas'] ?? [];
$akunPendapatan = $data['akun_pendapatan'] ?? [];
$akunBeban = $data['akun_beban'] ?? [];

if (!function_exists('renderAccountSelectOptions')) {
    function renderAccountSelectOptions($primaryList, $allDetailList, $selectedValue) {
        $primaryCodes = array_column($primaryList, 'kode_akun');
        if (!empty($primaryList)) {
            echo '<optgroup label="-- Rekomendasi Akun Terkait --">';
            foreach ($primaryList as $ak) {
                $sel = ($selectedValue == $ak['kode_akun']) ? 'selected' : '';
                echo '<option value="' . htmlspecialchars($ak['kode_akun']) . '" ' . $sel . '>' . htmlspecialchars($ak['kode_akun'] . ' - ' . $ak['nama_akun']) . ' (' . htmlspecialchars($ak['tipe_akun']) . ')</option>';
            }
            echo '</optgroup>';
        }

        $otherList = array_filter($allDetailList, function($ak) use ($primaryCodes) {
            return !in_array($ak['kode_akun'], $primaryCodes);
        });

        if (!empty($otherList)) {
            echo '<optgroup label="-- Akun Detail Lainnya --">';
            foreach ($otherList as $ak) {
                $sel = ($selectedValue == $ak['kode_akun']) ? 'selected' : '';
                echo '<option value="' . htmlspecialchars($ak['kode_akun']) . '" ' . $sel . '>' . htmlspecialchars($ak['kode_akun'] . ' - ' . $ak['nama_akun']) . ' (' . htmlspecialchars($ak['tipe_akun']) . ')</option>';
            }
            echo '</optgroup>';
        }
    }
}
?>

<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <nav aria-label="breadcrumb">
            <ol class="breadcrumb mb-1 small">
                <li class="breadcrumb-item"><a href="<?php echo BASEURL; ?>/simpanpinjam">Simpan Pinjam</a></li>
                <li class="breadcrumb-item active">Pengaturan & Parameter COA</li>
            </ol>
        </nav>
        <h2 class="fw-bold mb-0">Pengaturan Parameter & Bagan Akun (COA)</h2>
    </div>
    <div class="d-flex gap-2">
        <a href="<?php echo BASEURL; ?>/simpanpinjam/seed_coa" class="btn btn-outline-primary rounded-pill px-3" onclick="return confirm('Sistem akan menambahkan kode akun standar Koperasi / BUMDes Simpan Pinjam jika belum ada di database. Lanjutkan?')">
            <i class="bi bi-magic me-1"></i> 1-Klik Generate COA Standar
        </a>
        <a href="<?php echo BASEURL; ?>/simpanpinjam" class="btn btn-light rounded-pill px-3">
            <i class="bi bi-arrow-left me-1"></i> Kembali
        </a>
    </div>
</div>

<form action="<?php echo BASEURL; ?>/simpanpinjam/pengaturan_simpan" method="POST">
    <div class="row g-4">
        <!-- Kolom Pengaturan Umum & Status -->
        <div class="col-lg-5">
            <div class="card border-0 shadow-sm rounded-4 mb-4">
                <div class="card-header bg-white py-3 border-0">
                    <h6 class="fw-bold mb-0"><i class="bi bi-toggles me-1 text-primary"></i> Status & Identitas Unit Usaha</h6>
                </div>
                <div class="card-body p-4 pt-0">
                    <div class="mb-3">
                        <label class="form-label small fw-bold text-muted">STATUS UNIT USAHA SIMPAN PINJAM</label>
                        <div class="form-check form-switch p-2 bg-light rounded-3 d-flex align-items-center justify-content-between ps-3 pe-3">
                            <label class="form-check-label fw-bold" for="status_aktif">
                                <span id="status_text"><?php echo !empty($p['status_aktif']) ? 'Unit Usaha Aktif' : 'Unit Usaha Non-Aktif'; ?></span>
                            </label>
                            <input class="form-check-input fs-4" type="checkbox" role="switch" name="status_aktif" id="status_aktif" value="1" <?php echo !empty($p['status_aktif']) ? 'checked' : ''; ?> onchange="document.getElementById('status_text').innerText = this.checked ? 'Unit Usaha Aktif' : 'Unit Usaha Non-Aktif'">
                        </div>
                        <div class="form-text small">Jika dinonaktifkan, transaksi operasional Simpan Pinjam dibatasi dan dapat disembunyikan.</div>
                    </div>

                    <div class="mb-3">
                        <label class="form-label small fw-bold text-muted">NAMA UNIT USAHA</label>
                        <input type="text" name="nama_unit_sp" class="form-control" value="<?php echo htmlspecialchars($p['nama_unit_sp'] ?? 'Unit Usaha Simpan Pinjam'); ?>" required>
                    </div>

                    <div class="mb-3">
                        <label class="form-label small fw-bold text-muted">INTEGRASI JURNAL AKUNTANSI</label>
                        <div class="form-check form-switch p-2 bg-light rounded-3 d-flex align-items-center justify-content-between ps-3 pe-3">
                            <label class="form-check-label fw-bold" for="posting_jurnal_otomatis">
                                Posting Jurnal Otomatis
                            </label>
                            <input class="form-check-input fs-4" type="checkbox" role="switch" name="posting_jurnal_otomatis" id="posting_jurnal_otomatis" value="1" <?php echo !empty($p['posting_jurnal_otomatis']) ? 'checked' : ''; ?>>
                        </div>
                        <div class="form-text small">Otomatis membukukan jurnal umum saat pencairan pinjaman, pembayaran angsuran, setor, dan penarikan simpanan.</div>
                    </div>
                </div>
            </div>

            <!-- Petunjuk Bagan Akun -->
            <div class="card border-0 bg-primary-subtle text-primary-emphasis rounded-4 p-4 shadow-sm">
                <h6 class="fw-bold mb-2"><i class="bi bi-info-circle-fill me-1"></i> Standar Akuntansi Simpan Pinjam</h6>
                <p class="small mb-2">Sistem ini mengacu pada standar PSAK / SAK EP untuk entitas simpan pinjam / BUMDesa:</p>
                <ul class="small mb-2 ps-3">
                    <li><strong>Kas & Bank:</strong> Harus mengarah ke akun kas/bank riil (tipe Detail), bukan akun Header/Induk.</li>
                    <li><strong>Simpanan Pokok:</strong> Dicatat sebagai Ekuitas Anggota (Modal).</li>
                    <li><strong>Simpanan Wajib & Sukarela:</strong> Dicatat sebagai Kewajiban Lancar kepada Anggota.</li>
                    <li><strong>Pinjaman Anggota:</strong> Dicatat sebagai Aset Lancar (Piutang Pinjaman).</li>
                    <li><strong>Bunga & Admin Pinjaman:</strong> Diakui sebagai Pendapatan Operasional.</li>
                </ul>
                <div class="alert alert-info py-2 px-3 mb-0 small border-0 bg-white bg-opacity-75">
                    <i class="bi bi-check-circle-fill text-success me-1"></i> Seluruh opsi dropdown akun telah difilter ketat hanya menampilkan akun <strong>Detail</strong> yang siap menjurnal.
                </div>
            </div>
        </div>

        <!-- Kolom Mapping Parameter COA -->
        <div class="col-lg-7">
            <div class="card border-0 shadow-sm rounded-4">
                <div class="card-header bg-white py-3 border-0 d-flex justify-content-between align-items-center">
                    <h6 class="fw-bold mb-0"><i class="bi bi-diagram-3 me-1 text-primary"></i> Mapping Chart of Account (COA)</h6>
                    <span class="badge bg-light text-muted border">Akun Detail (Non-Header)</span>
                </div>
                <div class="card-body p-4 pt-0">
                    <div class="row g-3">
                        <!-- Akun Kas -->
                        <div class="col-md-12">
                            <label class="form-label small fw-bold text-muted">AKUN KAS & BANK DEFAULT (PENERIMAAN / PENCAIRAN)</label>
                            <select name="akun_kas_default" class="form-select" required>
                                <option value="">-- Pilih Akun Kas / Bank Detail --</option>
                                <?php foreach ($akunKasBank as $ak): ?>
                                    <option value="<?php echo htmlspecialchars($ak['kode_akun']); ?>" <?php echo (($p['akun_kas_default'] ?? '') == $ak['kode_akun']) ? 'selected' : ''; ?>>
                                        <?php echo htmlspecialchars($ak['kode_akun'] . ' - ' . $ak['nama_akun']) . ' (' . htmlspecialchars($ak['tipe_akun']) . ')'; ?>
                                    </option>
                                <?php endforeach; ?>
                                <?php if (empty($akunKasBank)): ?>
                                    <?php foreach ($akunPiutang as $ak): ?>
                                        <option value="<?php echo htmlspecialchars($ak['kode_akun']); ?>" <?php echo (($p['akun_kas_default'] ?? '') == $ak['kode_akun']) ? 'selected' : ''; ?>>
                                            <?php echo htmlspecialchars($ak['kode_akun'] . ' - ' . $ak['nama_akun']) . ' (' . htmlspecialchars($ak['tipe_akun']) . ')'; ?>
                                        </option>
                                    <?php endforeach; ?>
                                <?php endif; ?>
                            </select>
                            <div class="form-text small">Hanya menampilkan akun Kas & Bank bertipe Detail yang aktif pada tenant ini.</div>
                        </div>

                        <!-- Akun Piutang Pinjaman -->
                        <div class="col-md-6">
                            <label class="form-label small fw-bold text-muted">AKUN PIUTANG PINJAMAN ANGGOTA</label>
                            <select name="akun_piutang_pinjaman" class="form-select" required>
                                <option value="">-- Pilih Akun Detail --</option>
                                <?php renderAccountSelectOptions($akunPiutang, $akunDetail, $p['akun_piutang_pinjaman'] ?? ''); ?>
                            </select>
                        </div>

                        <!-- Akun Cadangan Kerugian -->
                        <div class="col-md-6">
                            <label class="form-label small fw-bold text-muted">AKUN CADANGAN KERUGIAN PINJAMAN (CKPN)</label>
                            <select name="akun_cadangan_kerugian" class="form-select" required>
                                <option value="">-- Pilih Akun Detail --</option>
                                <?php renderAccountSelectOptions($akunPiutang, $akunDetail, $p['akun_cadangan_kerugian'] ?? ''); ?>
                            </select>
                        </div>

                        <!-- Simpanan Pokok -->
                        <div class="col-md-4">
                            <label class="form-label small fw-bold text-muted">AKUN SIMPANAN POKOK (EKUITAS)</label>
                            <select name="akun_simpanan_pokok" class="form-select" required>
                                <option value="">-- Pilih Akun Detail --</option>
                                <?php renderAccountSelectOptions($akunEkuitas, $akunDetail, $p['akun_simpanan_pokok'] ?? ''); ?>
                            </select>
                        </div>

                        <!-- Simpanan Wajib -->
                        <div class="col-md-4">
                            <label class="form-label small fw-bold text-muted">AKUN SIMPANAN WAJIB (KEWAJIBAN)</label>
                            <select name="akun_simpanan_wajib" class="form-select" required>
                                <option value="">-- Pilih Akun Detail --</option>
                                <?php renderAccountSelectOptions($akunKewajiban, $akunDetail, $p['akun_simpanan_wajib'] ?? ''); ?>
                            </select>
                        </div>

                        <!-- Simpanan Sukarela -->
                        <div class="col-md-4">
                            <label class="form-label small fw-bold text-muted">AKUN SIMPANAN SUKARELA / TABUNGAN</label>
                            <select name="akun_simpanan_sukarela" class="form-select" required>
                                <option value="">-- Pilih Akun Detail --</option>
                                <?php renderAccountSelectOptions($akunKewajiban, $akunDetail, $p['akun_simpanan_sukarela'] ?? ''); ?>
                            </select>
                        </div>

                        <!-- Pendapatan Jasa Pinjaman -->
                        <div class="col-md-6">
                            <label class="form-label small fw-bold text-muted">AKUN PENDAPATAN JASA / BUNGA PINJAMAN</label>
                            <select name="akun_pendapatan_jasa" class="form-select" required>
                                <option value="">-- Pilih Akun Detail --</option>
                                <?php renderAccountSelectOptions($akunPendapatan, $akunDetail, $p['akun_pendapatan_jasa'] ?? ''); ?>
                            </select>
                        </div>

                        <!-- Pendapatan Admin -->
                        <div class="col-md-6">
                            <label class="form-label small fw-bold text-muted">AKUN PENDAPATAN PROVISI & ADMIN PINJAMAN</label>
                            <select name="akun_pendapatan_admin" class="form-select" required>
                                <option value="">-- Pilih Akun Detail --</option>
                                <?php renderAccountSelectOptions($akunPendapatan, $akunDetail, $p['akun_pendapatan_admin'] ?? ''); ?>
                            </select>
                        </div>

                        <!-- Pendapatan Denda -->
                        <div class="col-md-6">
                            <label class="form-label small fw-bold text-muted">AKUN PENDAPATAN DENDA KETERLAMBATAN</label>
                            <select name="akun_pendapatan_denda" class="form-select" required>
                                <option value="">-- Pilih Akun Detail --</option>
                                <?php renderAccountSelectOptions($akunPendapatan, $akunDetail, $p['akun_pendapatan_denda'] ?? ''); ?>
                            </select>
                        </div>

                        <!-- Beban Bunga Simpanan -->
                        <div class="col-md-6">
                            <label class="form-label small fw-bold text-muted">AKUN BEBAN BUNGA / JASA SIMPANAN</label>
                            <select name="akun_beban_bunga_simpanan" class="form-select" required>
                                <option value="">-- Pilih Akun Detail --</option>
                                <?php renderAccountSelectOptions($akunBeban, $akunDetail, $p['akun_beban_bunga_simpanan'] ?? ''); ?>
                            </select>
                        </div>
                    </div>

                    <hr class="my-4">

                    <div class="d-flex justify-content-end gap-2">
                        <button type="submit" class="btn btn-primary rounded-pill px-4 fw-bold shadow-sm">
                            <i class="bi bi-save me-1"></i> Simpan Pengaturan
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </div>
</form>
