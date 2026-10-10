<div class="container-fluid px-0">
    <!-- Header Section -->
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h3 class="fw-bold mb-1"><i class="bi bi-calendar-check text-success me-2"></i>Tutup Buku Akhir Periode</h3>
            <p class="text-muted mb-0">Menutup saldo akun nominal (Pendapatan & Beban) ke Laba Ditahan serta mengunci transaksi periode lampau.</p>
        </div>
        <div>
            <a href="<?php echo BASEURL; ?>/perusahaan" class="btn btn-outline-secondary btn-sm">
                <i class="bi bi-gear me-1"></i> Pengaturan Akun Kontrol
            </a>
            <a href="<?php echo BASEURL; ?>/jurnal" class="btn btn-outline-primary btn-sm">
                <i class="bi bi-vector-pen me-1"></i> Riwayat Jurnal
            </a>
        </div>
    </div>

    <!-- Status Cards -->
    <div class="row g-3 mb-4">
        <div class="col-md-4">
            <div class="card border-0 shadow-sm rounded-3 h-100 bg-light">
                <div class="card-body">
                    <span class="text-muted small text-uppercase fw-semibold">Status Periode Terakhir</span>
                    <?php if (!empty($data['latest_closed'])): ?>
                        <h4 class="mt-2 mb-1 fw-bold text-danger">
                            <i class="bi bi-lock-fill me-1"></i>
                            <?php 
                                echo $data['latest_closed']['tipe_proses'] === 'Tahunan' 
                                    ? 'Tahun ' . $data['latest_closed']['tahun']
                                    : date('F Y', strtotime($data['latest_closed']['tahun'] . '-' . str_pad($data['latest_closed']['bulan'], 2, '0', STR_PAD_LEFT) . '-01'));
                            ?>
                        </h4>
                        <span class="badge bg-danger">Terkunci (Closed)</span>
                        <small class="text-muted d-block mt-2">
                            Ditutup pada: <?php echo date('d M Y H:i', strtotime($data['latest_closed']['tanggal_tutup'])); ?>
                        </small>
                    <?php else: ?>
                        <h5 class="mt-2 mb-1 text-muted">Belum ada periode ditutup</h5>
                        <span class="badge bg-secondary">Semua Periode Terbuka</span>
                    <?php endif; ?>
                </div>
            </div>
        </div>

        <div class="col-md-4">
            <div class="card border-0 shadow-sm rounded-3 h-100 bg-light">
                <div class="card-body">
                    <span class="text-muted small text-uppercase fw-semibold">Rekomendasi Tutup Buku</span>
                    <h4 class="mt-2 mb-1 fw-bold text-primary">
                        <i class="bi bi-calendar-event me-1"></i>
                        <?php echo date('F Y', strtotime(($data['periode_bulanan_next'] ?? date('Y-m')) . '-01')); ?>
                    </h4>
                    <span class="badge bg-primary-subtle text-primary border border-primary-subtle">Periode Siap Diproses</span>
                    <small class="text-muted d-block mt-2">Bulan berikutnya setelah penutupan terakhir.</small>
                </div>
            </div>
        </div>

        <div class="col-md-4">
            <div class="card border-0 shadow-sm rounded-3 h-100 bg-light">
                <div class="card-body">
                    <span class="text-muted small text-uppercase fw-semibold">Akun Penampung (Default)</span>
                    <div class="mt-2 small">
                        <div class="d-flex justify-content-between mb-1">
                            <span class="text-muted">Ikhtisar L/R:</span>
                            <span class="fw-semibold text-truncate ms-2" title="<?php echo htmlspecialchars($data['accounts']['ikhtisar']['nama'] ?? ''); ?>">
                                [<?php echo htmlspecialchars($data['accounts']['ikhtisar']['kode'] ?? '3-3000'); ?>] <?php echo htmlspecialchars($data['accounts']['ikhtisar']['nama'] ?? 'Ikhtisar L/R'); ?>
                            </span>
                        </div>
                        <div class="d-flex justify-content-between">
                            <span class="text-muted">Laba Ditahan:</span>
                            <span class="fw-semibold text-truncate ms-2" title="<?php echo htmlspecialchars($data['accounts']['laba_ditahan']['nama'] ?? ''); ?>">
                                [<?php echo htmlspecialchars($data['accounts']['laba_ditahan']['kode'] ?? '3-2000'); ?>] <?php echo htmlspecialchars($data['accounts']['laba_ditahan']['nama'] ?? 'Laba Ditahan'); ?>
                            </span>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Opsi Tutup Buku Form Cards -->
    <div class="row g-4 mb-4">
        <!-- Opsi Bulanan -->
        <div class="col-lg-6">
            <div class="card shadow-sm border-0 rounded-3 h-100">
                <div class="card-header bg-success text-white py-3">
                    <h5 class="mb-0 fw-bold"><i class="bi bi-calendar-month me-2"></i>Tutup Buku Bulanan</h5>
                </div>
                <div class="card-body d-flex flex-column p-4">
                    <p class="text-secondary flex-grow-1">
                        Menghitung seluruh pendapatan dan beban untuk <strong>1 bulan kalender</strong> yang dipilih, mengosongkan saldonya ke Ikhtisar L/R lalu memindahkan laba/rugi bersih ke Laba Ditahan, serta <strong>mengunci</strong> tanggal transaksi di bulan tersebut.
                    </p>
                    <form action="<?php echo BASEURL; ?>/tutupbuku/index" method="post">
                        <input type="hidden" name="tipe_proses" value="Bulanan">
                        <div class="mb-3">
                            <label for="periode_bulanan" class="form-label fw-semibold">Pilih Periode Bulan & Tahun</label>
                            <input type="month" name="periode" id="periode_bulanan" class="form-control form-control-lg" value="<?php echo htmlspecialchars($data['periode_bulanan_next'] ?? date('Y-m')); ?>" required>
                        </div>
                        <div class="d-grid">
                            <button type="submit" class="btn btn-success btn-lg">
                                <i class="bi bi-eye-fill me-1"></i> Pratinjau Jurnal Penutup Bulanan
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>

        <!-- Opsi Tahunan -->
        <div class="col-lg-6">
            <div class="card shadow-sm border-0 rounded-3 h-100">
                <div class="card-header bg-primary text-white py-3">
                    <h5 class="mb-0 fw-bold"><i class="bi bi-calendar-date me-2"></i>Tutup Buku Tahunan</h5>
                </div>
                <div class="card-body d-flex flex-column p-4">
                    <p class="text-secondary flex-grow-1">
                        Proses tutup buku akhir tahun fiskal (per 31 Desember). Menutup seluruh saldo pendapatan dan beban setahun penuh, membukukan laba/rugi tahunan ke Laba Ditahan, serta <strong>mengunci seluruh 12 bulan</strong> di tahun buku terkait.
                    </p>
                    <form action="<?php echo BASEURL; ?>/tutupbuku/index" method="post">
                        <input type="hidden" name="tipe_proses" value="Tahunan">
                        <div class="mb-3">
                            <label for="periode_tahunan" class="form-label fw-semibold">Pilih Tahun Buku</label>
                            <select name="periode" id="periode_tahunan" class="form-select form-select-lg" required>
                                <?php for ($i = (int)date('Y'); $i >= (int)date('Y') - 5; $i--): ?>
                                    <option value="<?php echo $i; ?>"><?php echo $i; ?></option>
                                <?php endfor; ?>
                            </select>
                        </div>
                        <div class="d-grid">
                            <button type="submit" class="btn btn-primary btn-lg">
                                <i class="bi bi-eye-fill me-1"></i> Pratinjau Jurnal Penutup Tahunan
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <!-- Area Pratinjau Jurnal -->
    <?php if (isset($data['preview']) && $data['preview'] !== null): ?>
    <?php 
        $totalPendapatan = (float)($data['preview']['total_pendapatan_1'] ?? 0);
        $totalBeban = (float)($data['preview']['total_beban_1'] ?? 0);
        $labaRugi = $totalPendapatan - $totalBeban;
        $isUntung = ($labaRugi >= 0);
    ?>
    <div class="card shadow-sm border-0 rounded-3 mb-4">
        <div class="card-header bg-dark text-white py-3 d-flex justify-content-between align-items-center">
            <h5 class="mb-0 fw-bold">
                <i class="bi bi-journal-check me-2"></i>Pratinjau Jurnal Penutup <?php echo htmlspecialchars($data['tipe_proses'] ?? ''); ?>: <?php echo htmlspecialchars($data['periode_label'] ?? ''); ?>
            </h5>
            <span class="badge bg-light text-dark">
                <?php echo date('d M Y', strtotime($data['preview']['tanggal_mulai'])); ?> s/d <?php echo date('d M Y', strtotime($data['preview']['tanggal_selesai'])); ?>
            </span>
        </div>
        <div class="card-body p-4">
            <!-- Ringkasan Hasil Laba Rugi -->
            <div class="alert <?php echo $isUntung ? 'alert-success' : 'alert-warning'; ?> border-0 shadow-sm mb-4">
                <div class="d-flex align-items-center">
                    <div class="fs-1 me-3">
                        <i class="bi <?php echo $isUntung ? 'bi-graph-up-arrow text-success' : 'bi-graph-down-arrow text-warning'; ?>"></i>
                    </div>
                    <div>
                        <h5 class="alert-heading fw-bold mb-1">
                            <?php echo $isUntung ? 'Laba Bersih Periode Ini' : 'Rugi Bersih Periode Ini'; ?>: 
                            Rp <?php echo number_format(abs($labaRugi), 2, ',', '.'); ?>
                        </h5>
                        <p class="mb-0 small">
                            Total Pendapatan: <strong>Rp <?php echo number_format($totalPendapatan, 2, ',', '.'); ?></strong> | 
                            Total Beban: <strong>Rp <?php echo number_format($totalBeban, 2, ',', '.'); ?></strong>.
                            Saldo ini akan dialihkan ke akun Ekuitas <strong>[<?php echo htmlspecialchars($data['preview']['akun_laba_ditahan']['kode'] ?? ''); ?>] <?php echo htmlspecialchars($data['preview']['akun_laba_ditahan']['nama'] ?? ''); ?></strong>.
                        </p>
                    </div>
                </div>
            </div>

            <!-- Tabel Konstruksi Ayat Jurnal Penutup -->
            <h6 class="fw-bold mb-3"><i class="bi bi-list-columns-reverse me-2"></i>Rincian Ayat Jurnal yang Akan Diterbitkan:</h6>
            <div class="table-responsive mb-4">
                <table class="table table-bordered table-hover align-middle">
                    <thead class="table-light">
                        <tr>
                            <th style="width: 15%;">Kode Akun</th>
                            <th style="width: 45%;">Nama Akun / Keterangan</th>
                            <th style="width: 20%;" class="text-end">Debit (Rp)</th>
                            <th style="width: 20%;" class="text-end">Kredit (Rp)</th>
                        </tr>
                    </thead>
                    <tbody>
                        <!-- 1. Menutup Akun Pendapatan -->
                        <tr class="table-secondary fw-semibold">
                            <td colspan="4">1. Penutupan Akun Pendapatan ke Ikhtisar L/R</td>
                        </tr>
                        <?php if (!empty($data['preview']['pendapatan'])): ?>
                            <?php foreach ($data['preview']['pendapatan'] as $akun): ?>
                                <?php if ($akun['total_1'] > 0): ?>
                                <tr>
                                    <td><code><?php echo htmlspecialchars($akun['kode_akun']); ?></code></td>
                                    <td><?php echo htmlspecialchars($akun['nama_akun']); ?></td>
                                    <td class="text-end text-success fw-semibold"><?php echo number_format($akun['total_1'], 2, ',', '.'); ?></td>
                                    <td class="text-end">-</td>
                                </tr>
                                <?php endif; ?>
                            <?php endforeach; ?>
                            <tr>
                                <td><code><?php echo htmlspecialchars($data['preview']['akun_ikhtisar_lr']['kode']); ?></code></td>
                                <td class="ps-4"><em><?php echo htmlspecialchars($data['preview']['akun_ikhtisar_lr']['nama']); ?> (Akumulasi Pendapatan)</em></td>
                                <td class="text-end">-</td>
                                <td class="text-end text-success fw-semibold"><?php echo number_format($totalPendapatan, 2, ',', '.'); ?></td>
                            </tr>
                        <?php else: ?>
                            <tr>
                                <td colspan="4" class="text-muted text-center italic">Tidak ada transaksi pendapatan pada periode ini.</td>
                            </tr>
                        <?php endif; ?>

                        <!-- 2. Menutup Akun Beban -->
                        <tr class="table-secondary fw-semibold">
                            <td colspan="4">2. Penutupan Akun Beban ke Ikhtisar L/R</td>
                        </tr>
                        <?php if (!empty($data['preview']['beban'])): ?>
                            <tr>
                                <td><code><?php echo htmlspecialchars($data['preview']['akun_ikhtisar_lr']['kode']); ?></code></td>
                                <td><?php echo htmlspecialchars($data['preview']['akun_ikhtisar_lr']['nama']); ?> (Akumulasi Beban)</td>
                                <td class="text-end text-danger fw-semibold"><?php echo number_format($totalBeban, 2, ',', '.'); ?></td>
                                <td class="text-end">-</td>
                            </tr>
                            <?php foreach ($data['preview']['beban'] as $akun): ?>
                                <?php if ($akun['total_1'] > 0): ?>
                                <tr>
                                    <td><code><?php echo htmlspecialchars($akun['kode_akun']); ?></code></td>
                                    <td class="ps-4"><em><?php echo htmlspecialchars($akun['nama_akun']); ?></em></td>
                                    <td class="text-end">-</td>
                                    <td class="text-end text-danger fw-semibold"><?php echo number_format($akun['total_1'], 2, ',', '.'); ?></td>
                                </tr>
                                <?php endif; ?>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="4" class="text-muted text-center italic">Tidak ada transaksi beban pada periode ini.</td>
                            </tr>
                        <?php endif; ?>

                        <!-- 3. Menutup Ikhtisar L/R ke Laba Ditahan -->
                        <tr class="table-secondary fw-semibold">
                            <td colspan="4">3. Penutupan Saldo Ikhtisar L/R ke Laba Ditahan</td>
                        </tr>
                        <?php if ($labaRugi > 0): ?>
                            <tr>
                                <td><code><?php echo htmlspecialchars($data['preview']['akun_ikhtisar_lr']['kode']); ?></code></td>
                                <td><?php echo htmlspecialchars($data['preview']['akun_ikhtisar_lr']['nama']); ?></td>
                                <td class="text-end fw-bold"><?php echo number_format($labaRugi, 2, ',', '.'); ?></td>
                                <td class="text-end">-</td>
                            </tr>
                            <tr>
                                <td><code><?php echo htmlspecialchars($data['preview']['akun_laba_ditahan']['kode']); ?></code></td>
                                <td class="ps-4"><em><?php echo htmlspecialchars($data['preview']['akun_laba_ditahan']['nama']); ?></em></td>
                                <td class="text-end">-</td>
                                <td class="text-end fw-bold"><?php echo number_format($labaRugi, 2, ',', '.'); ?></td>
                            </tr>
                        <?php elseif ($labaRugi < 0): ?>
                            <tr>
                                <td><code><?php echo htmlspecialchars($data['preview']['akun_laba_ditahan']['kode']); ?></code></td>
                                <td><?php echo htmlspecialchars($data['preview']['akun_laba_ditahan']['nama']); ?></td>
                                <td class="text-end fw-bold"><?php echo number_format(abs($labaRugi), 2, ',', '.'); ?></td>
                                <td class="text-end">-</td>
                            </tr>
                            <tr>
                                <td><code><?php echo htmlspecialchars($data['preview']['akun_ikhtisar_lr']['kode']); ?></code></td>
                                <td class="ps-4"><em><?php echo htmlspecialchars($data['preview']['akun_ikhtisar_lr']['nama']); ?></em></td>
                                <td class="text-end">-</td>
                                <td class="text-end fw-bold"><?php echo number_format(abs($labaRugi), 2, ',', '.'); ?></td>
                            </tr>
                        <?php else: ?>
                            <tr>
                                <td colspan="4" class="text-muted text-center">Laba bersih 0, tidak ada pemindahan saldo ke Laba Ditahan.</td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>

            <!-- Tombol Aksi Konfirmasi -->
            <div class="d-flex justify-content-between align-items-center bg-light p-3 rounded-3 border">
                <div class="text-muted small">
                    <i class="bi bi-info-circle me-1"></i> Setelah tutup buku diproses, transaksi pada periode ini tidak akan dapat diubah atau dihapus tanpa izin pembukaan kembali.
                </div>
                <div class="d-flex gap-2">
                    <a href="<?php echo BASEURL; ?>/tutupbuku" class="btn btn-outline-secondary">
                        <i class="bi bi-x-circle me-1"></i> Batal
                    </a>
                    <form action="<?php echo BASEURL; ?>/tutupbuku/proses" method="post" onsubmit="return confirm('Apakah Anda yakin ingin memproses Tutup Buku untuk periode ini? Tindakan ini akan mengunci transaksi pada periode tersebut.');">
                        <input type="hidden" name="periode" value="<?php echo htmlspecialchars($data['periode_raw'] ?? ''); ?>">
                        <input type="hidden" name="tipe_proses" value="<?php echo htmlspecialchars($data['tipe_proses'] ?? ''); ?>">
                        <button type="submit" class="btn btn-success px-4 fw-semibold">
                            <i class="bi bi-check2-circle me-1"></i> Konfirmasi & Proses Tutup Buku
                        </button>
                    </form>
                </div>
            </div>
        </div>
    </div>
    <?php endif; ?>

    <!-- Tabel Riwayat Periode Ditutup -->
    <div class="card shadow-sm border-0 rounded-3">
        <div class="card-header bg-white py-3">
            <h5 class="mb-0 fw-bold"><i class="bi bi-clock-history me-2 text-primary"></i>Riwayat Periode Tutup Buku</h5>
        </div>
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead class="table-light">
                        <tr>
                            <th class="ps-3">Periode</th>
                            <th>Tipe</th>
                            <th>Tanggal Tutup</th>
                            <th>No. Jurnal Penutup</th>
                            <th class="text-end">Laba/Rugi Bersih</th>
                            <th>Ditutup Oleh</th>
                            <th class="text-center">Status</th>
                            <th class="text-center pe-3">Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (!empty($data['riwayat'])): ?>
                            <?php foreach ($data['riwayat'] as $row): ?>
                                <tr>
                                    <td class="ps-3 fw-bold">
                                        <?php 
                                            echo $row['tipe_proses'] === 'Tahunan'
                                                ? 'Tahun ' . $row['tahun']
                                                : date('F Y', strtotime($row['tahun'] . '-' . str_pad($row['bulan'], 2, '0', STR_PAD_LEFT) . '-01'));
                                        ?>
                                    </td>
                                    <td>
                                        <span class="badge <?php echo $row['tipe_proses'] === 'Tahunan' ? 'bg-primary' : 'bg-info text-dark'; ?>">
                                            <?php echo htmlspecialchars($row['tipe_proses']); ?>
                                        </span>
                                    </td>
                                    <td><?php echo !empty($row['tanggal_tutup']) ? date('d M Y H:i', strtotime($row['tanggal_tutup'])) : '-'; ?></td>
                                    <td>
                                        <?php if (!empty($row['id_jurnal']) && !empty($row['no_jurnal_penutup'])): ?>
                                            <a href="<?php echo BASEURL; ?>/jurnal/detail/<?php echo $row['id_jurnal']; ?>" class="badge bg-secondary-subtle text-secondary text-decoration-none border">
                                                <i class="bi bi-receipt me-1"></i><?php echo htmlspecialchars($row['no_jurnal_penutup']); ?>
                                            </a>
                                        <?php else: ?>
                                            <span class="text-muted">-</span>
                                        <?php endif; ?>
                                    </td>
                                    <td class="text-end fw-semibold <?php echo ($row['laba_bersih'] >= 0) ? 'text-success' : 'text-danger'; ?>">
                                        Rp <?php echo number_format($row['laba_bersih'], 2, ',', '.'); ?>
                                    </td>
                                    <td><?php echo htmlspecialchars($row['nama_user_tutup'] ?? 'Sistem'); ?></td>
                                    <td class="text-center">
                                        <span class="badge bg-danger rounded-pill px-3">
                                            <i class="bi bi-lock-fill me-1"></i><?php echo htmlspecialchars($row['status']); ?>
                                        </span>
                                    </td>
                                    <td class="text-center pe-3">
                                        <?php if (Auth::isAdmin() || Auth::isManager()): ?>
                                            <a href="<?php echo BASEURL; ?>/tutupbuku/bukaKembali/<?php echo $row['id']; ?>" 
                                               class="btn btn-outline-danger btn-sm"
                                               onclick="return confirm('PERINGATAN: Membuka kembali periode ini akan menghapus jurnal penutup terkait dan membuka kunci transaksi pada periode ini. Lanjutkan?');"
                                               title="Buka Kembali Periode (Rollback)">
                                                <i class="bi bi-unlock-fill me-1"></i> Buka Periode
                                            </a>
                                        <?php else: ?>
                                            <span class="text-muted small">-</span>
                                        <?php endif; ?>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="8" class="text-center py-4 text-muted">
                                    <i class="bi bi-inbox fs-3 d-block mb-2"></i>
                                    Belum ada periode akuntansi yang ditutup.
                                </td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>
