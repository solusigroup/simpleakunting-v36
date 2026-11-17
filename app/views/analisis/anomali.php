<?php $activeTab = 'anomali'; ?>

<div class="d-flex flex-wrap justify-content-between align-items-center mb-4 gap-3">
    <div>
        <h3 class="fw-bold mb-1"><i class="bi bi-exclamation-triangle text-danger me-2"></i>Analisis Jurnal Anomali</h3>
        <p class="text-muted mb-0">Deteksi ketidakseimbangan debit-kredit, baris tanpa induk, transaksi nol, dan anomali pembukuan.</p>
    </div>
    <div class="d-flex gap-2">
        <a href="<?php echo BASEURL; ?>/jurnal" class="btn btn-outline-primary rounded-pill px-3 shadow-sm">
            <i class="bi bi-journal-text me-1"></i> Buka Jurnal Umum
        </a>
    </div>
</div>

<!-- Navigasi Tab Fitur Analisa Bisnis -->
<ul class="nav nav-pills nav-fill bg-white p-2 rounded-4 shadow-sm mb-4 border">
    <li class="nav-item">
        <a class="nav-link rounded-pill text-dark" href="<?php echo BASEURL; ?>/analisis">
            <i class="bi bi-speedometer2 me-1"></i> Ikhtisar Bisnis
        </a>
    </li>
    <li class="nav-item">
        <a class="nav-link rounded-pill active fw-bold bg-danger" href="<?php echo BASEURL; ?>/analisis/anomali">
            <i class="bi bi-exclamation-triangle me-1"></i> Jurnal Anomali
            <?php if (($data['anomali']['total'] ?? 0) > 0): ?>
                <span class="badge bg-white text-danger ms-1"><?php echo $data['anomali']['total']; ?></span>
            <?php endif; ?>
        </a>
    </li>
    <li class="nav-item">
        <a class="nav-link rounded-pill text-dark" href="<?php echo BASEURL; ?>/analisis/tanpaJurnal">
            <i class="bi bi-link-45deg me-1"></i> Transaksi Tanpa Jurnal
        </a>
    </li>
    <li class="nav-item">
        <a class="nav-link rounded-pill text-dark" href="<?php echo BASEURL; ?>/analisis/rasio">
            <i class="bi bi-calculator me-1"></i> Rasio Keuangan
        </a>
    </li>
    <li class="nav-item">
        <a class="nav-link rounded-pill text-dark" href="<?php echo BASEURL; ?>/analisis/kesehatan">
            <i class="bi bi-heart-pulse me-1"></i> Kesehatan Usaha
        </a>
    </li>
</ul>

<?php Flash::flash(); ?>

<!-- Summary Cards Anomali -->
<div class="row g-3 mb-4">
    <div class="col-md-3">
        <div class="card border-0 shadow-sm rounded-4 p-3 border-start border-primary border-4">
            <span class="text-muted small fw-medium">Total Anomali Ditemukan</span>
            <h2 class="fw-bold mb-0 text-dark"><?php echo $data['anomali']['total']; ?></h2>
            <small class="text-muted">Item perlu diverifikasi</small>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card border-0 shadow-sm rounded-4 p-3 border-start border-danger border-4">
            <span class="text-muted small fw-medium">Anomali Kritis (Unbalanced)</span>
            <h2 class="fw-bold mb-0 text-danger"><?php echo $data['anomali']['kritis']; ?></h2>
            <small class="text-muted">Debit ≠ Kredit</small>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card border-0 shadow-sm rounded-4 p-3 border-start border-warning border-4">
            <span class="text-muted small fw-medium">Anomali Tinggi (Struktur)</span>
            <h2 class="fw-bold mb-0 text-warning"><?php echo $data['anomali']['tinggi']; ?></h2>
            <small class="text-muted">Header kosong / Akun hilang</small>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card border-0 shadow-sm rounded-4 p-3 border-start border-info border-4">
            <span class="text-muted small fw-medium">Anomali Sedang / Duplikat</span>
            <h2 class="fw-bold mb-0 text-info"><?php echo $data['anomali']['sedang']; ?></h2>
            <small class="text-muted">Orphan baris & entri identik</small>
        </div>
    </div>
</div>

<!-- Filter Periode -->
<div class="card border-0 shadow-sm rounded-4 mb-4">
    <div class="card-body p-3">
        <form action="<?php echo BASEURL; ?>/analisis/anomali" method="get" class="row g-2 align-items-center">
            <div class="col-md-4">
                <label class="form-label small fw-bold text-muted mb-1">Dari Tanggal</label>
                <input type="date" name="tanggal_mulai" class="form-control form-control-sm rounded-3" value="<?php echo htmlspecialchars($data['tanggal_mulai']); ?>">
            </div>
            <div class="col-md-4">
                <label class="form-label small fw-bold text-muted mb-1">Sampai Tanggal</label>
                <input type="date" name="tanggal_selesai" class="form-control form-control-sm rounded-3" value="<?php echo htmlspecialchars($data['tanggal_selesai']); ?>">
            </div>
            <div class="col-md-4 d-flex align-items-end">
                <button type="submit" class="btn btn-danger btn-sm rounded-pill px-4 w-100 shadow-sm">
                    <i class="bi bi-search me-1"></i> Pindai Ulang Anomali
                </button>
            </div>
        </form>
    </div>
</div>

<!-- Form Bulk Action -->
<form id="bulkAnomaliForm" action="<?php echo BASEURL; ?>/analisis/bulkPerbaikiAnomali" method="post">
    <input type="hidden" name="bulk_action" id="bulkActionInput" value="bersihkan">

    <!-- Daftar Temuan Anomali -->
    <div class="card border-0 shadow-sm rounded-4">
        <div class="card-header bg-white border-0 pt-4 px-4 pb-3">
            <div class="d-flex flex-wrap justify-content-between align-items-center gap-3">
                <div>
                    <h5 class="fw-bold mb-0">Daftar Hasil Audit Anomali Jurnal</h5>
                    <small class="text-muted">Periode pemindaian: <?php echo $data['periode']; ?></small>
                </div>

                <!-- Tombol Cepat Sapu Bersih (Purge All) -->
                <?php if (!empty($data['anomali']['daftar'])): ?>
                <div>
                    <button type="button" class="btn btn-danger btn-sm rounded-pill px-3 shadow-sm" onclick="executePurgeAll()">
                        <i class="bi bi-stars me-1"></i> Sapu Bersih Semua Data Yatim & Header Kosong
                    </button>
                </div>
                <?php endif; ?>
            </div>

            <!-- Toolbar Bulk Action Interaktif -->
            <?php if (!empty($data['anomali']['daftar'])): ?>
            <div class="bg-light p-2.5 rounded-3 mt-3 border d-flex flex-wrap align-items-center justify-content-between gap-2" id="bulkToolbar">
                <div class="d-flex align-items-center gap-2">
                    <div class="form-check ms-1 mb-0">
                        <input class="form-check-input" type="checkbox" id="selectAllCheckbox" onchange="toggleSelectAll(this)">
                        <label class="form-check-label small fw-bold text-dark cursor-pointer" for="selectAllCheckbox">
                            Pilih Semua
                        </label>
                    </div>
                    <span class="badge bg-secondary rounded-pill ms-2" id="selectedCountBadge">0 item dipilih</span>
                </div>

                <div class="d-flex align-items-center gap-2">
                    <button type="button" class="btn btn-outline-danger btn-sm rounded-pill px-3" id="btnBulkClean" disabled onclick="executeBulkAction('bersihkan')">
                        <i class="bi bi-eraser-fill me-1"></i> Bersihkan Terpilih
                    </button>
                    <button type="button" class="btn btn-outline-dark btn-sm rounded-pill px-3" id="btnBulkDelete" disabled onclick="executeBulkAction('hapus_terpilih')">
                        <i class="bi bi-trash3 me-1"></i> Hapus Jurnal Terpilih
                    </button>
                </div>
            </div>
            <?php endif; ?>
        </div>

        <div class="card-body p-4 pt-1">
            <?php if (empty($data['anomali']['daftar'])): ?>
                <div class="text-center py-5">
                    <div class="bg-success-subtle text-success p-3 rounded-circle d-inline-block mb-3">
                        <i class="bi bi-shield-check display-5"></i>
                    </div>
                    <h5 class="fw-bold text-success">Luar Biasa! Tidak Ditemukan Anomali Jurnal</h5>
                    <p class="text-muted small mb-0">Seluruh entri jurnal umum berada dalam kondisi seimbang (debit = kredit), memiliki struktur lengkap, dan terdaftar di bagan akun.</p>
                </div>
            <?php else: ?>
                <div class="table-responsive">
                    <table class="table table-hover align-middle mb-0" id="anomaliTable">
                        <thead class="table-light">
                            <tr>
                                <th style="width: 40px;" class="text-center">
                                    <span class="visually-hidden">Pilih</span>
                                </th>
                                <th style="width: 40px;">No</th>
                                <th>No. Transaksi / Dokumen</th>
                                <th>Tanggal</th>
                                <th>Sumber Transaksi</th>
                                <th>Jenis Anomali</th>
                                <th>Tingkat</th>
                                <th>Deskripsi Temuan Masalah</th>
                                <th class="text-center" style="width: 140px;">Tindakan</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($data['anomali']['daftar'] as $idx => $row): 
                                $rowValue = $row['aksi'] . ':' . $row['id_jurnal'];
                            ?>
                                <tr id="row-<?php echo $idx; ?>">
                                    <td class="text-center">
                                        <input class="form-check-input item-checkbox" type="checkbox" name="selected_items[]" value="<?php echo htmlspecialchars($rowValue); ?>" onchange="updateBulkUI()">
                                    </td>
                                    <td><?php echo $idx + 1; ?></td>
                                    <td>
                                        <strong><?php echo htmlspecialchars($row['no_transaksi']); ?></strong>
                                        <?php if (!empty($row['deskripsi'])): ?>
                                            <small class="d-block text-muted text-truncate" style="max-width: 200px;">
                                                <?php echo htmlspecialchars($row['deskripsi']); ?>
                                            </small>
                                        <?php endif; ?>
                                    </td>
                                    <td><?php echo $row['tanggal']; ?></td>
                                    <td>
                                        <span class="badge bg-light text-dark border">
                                            <?php echo htmlspecialchars($row['sumber']); ?>
                                        </span>
                                    </td>
                                    <td>
                                        <span class="fw-medium text-dark"><?php echo htmlspecialchars($row['jenis_anomali']); ?></span>
                                    </td>
                                    <td>
                                        <span class="badge bg-<?php echo $row['badge']; ?> rounded-pill">
                                            <?php echo $row['tingkat_keparahan']; ?>
                                        </span>
                                    </td>
                                    <td>
                                        <small class="text-secondary"><?php echo htmlspecialchars($row['detail']); ?></small>
                                    </td>
                                    <td class="text-center">
                                        <?php if ($row['aksi'] === 'edit_jurnal'): ?>
                                            <a href="<?php echo BASEURL; ?>/jurnal/detail/<?php echo $row['id_jurnal']; ?>" target="_blank" class="btn btn-outline-primary btn-sm rounded-pill px-2.5">
                                                <i class="bi bi-pencil-square me-1"></i> Koreksi
                                            </a>
                                        <?php elseif ($row['aksi'] === 'hapus_header_kosong'): ?>
                                            <button type="button" class="btn btn-sm btn-outline-danger rounded-pill px-2.5" onclick="executeSingleAction('hapus_header_kosong', <?php echo $row['id_jurnal']; ?>)">
                                                <i class="bi bi-trash me-1"></i> Bersihkan
                                            </button>
                                        <?php elseif ($row['aksi'] === 'bersihkan_orphan_detail'): ?>
                                            <button type="button" class="btn btn-sm btn-outline-warning text-dark rounded-pill px-2.5" onclick="executeSingleAction('bersihkan_orphan_detail', <?php echo $row['id_jurnal']; ?>)">
                                                <i class="bi bi-eraser me-1"></i> Hapus Detail
                                            </button>
                                        <?php else: ?>
                                            <a href="<?php echo BASEURL; ?>/jurnal" class="btn btn-sm btn-light border rounded-pill px-2.5">
                                                <i class="bi bi-eye"></i> Cek
                                            </a>
                                        <?php endif; ?>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php endif; ?>
        </div>
    </div>
</form>

<!-- Form Tersembunyi untuk Tindakan Tunggal -->
<form id="singleActionForm" action="<?php echo BASEURL; ?>/analisis/perbaikiAnomali" method="post" style="display:none;">
    <input type="hidden" name="aksi" id="singleAksi">
    <input type="hidden" name="id_target" id="singleIdTarget">
</form>

<script>
function toggleSelectAll(masterCheckbox) {
    const checkboxes = document.querySelectorAll('.item-checkbox');
    checkboxes.forEach(cb => {
        cb.checked = masterCheckbox.checked;
        const tr = cb.closest('tr');
        if (tr) {
            if (masterCheckbox.checked) tr.classList.add('table-warning');
            else tr.classList.remove('table-warning');
        }
    });
    updateBulkUI();
}

function updateBulkUI() {
    const checkboxes = document.querySelectorAll('.item-checkbox');
    const checked = document.querySelectorAll('.item-checkbox:checked');
    const count = checked.length;

    // Update row highlighting
    checkboxes.forEach(cb => {
        const tr = cb.closest('tr');
        if (tr) {
            if (cb.checked) tr.classList.add('table-warning');
            else tr.classList.remove('table-warning');
        }
    });

    const badge = document.getElementById('selectedCountBadge');
    if (badge) {
        badge.textContent = count + ' item dipilih';
        if (count > 0) {
            badge.className = 'badge bg-primary rounded-pill ms-2';
        } else {
            badge.className = 'badge bg-secondary rounded-pill ms-2';
        }
    }

    const btnClean = document.getElementById('btnBulkClean');
    const btnDelete = document.getElementById('btnBulkDelete');
    if (btnClean) btnClean.disabled = (count === 0);
    if (btnDelete) btnDelete.disabled = (count === 0);

    const masterCheckbox = document.getElementById('selectAllCheckbox');
    if (masterCheckbox && checkboxes.length > 0) {
        masterCheckbox.checked = (count === checkboxes.length);
    }
}

function executeBulkAction(action) {
    const checked = document.querySelectorAll('.item-checkbox:checked');
    if (checked.length === 0) {
        alert('Silakan pilih minimal 1 item anomali terlebih dahulu.');
        return;
    }

    let confirmMsg = 'Apakah Anda yakin ingin memproses ' + checked.length + ' item anomali terpilih?';
    if (action === 'hapus_terpilih') {
        confirmMsg = 'PERINGATAN: ' + checked.length + ' jurnal terpilih beserta seluruh baris detailnya akan dihapus permanen. Lanjutkan?';
    }

    if (confirm(confirmMsg)) {
        document.getElementById('bulkActionInput').value = action;
        document.getElementById('bulkAnomaliForm').submit();
    }
}

function executePurgeAll() {
    const confirmMsg = 'APAKAH ANDA YAKIN?\n\nFitur ini akan menyapu bersih SEMUA detail yatim (orphan details) dan SEMUA header jurnal kosong yang terdeteksi di seluruh database tenant ini secara otomatis.\n\nLanjutkan pembersihan massal?';
    if (confirm(confirmMsg)) {
        document.getElementById('bulkActionInput').value = 'purge_all';
        document.getElementById('bulkAnomaliForm').submit();
    }
}

function executeSingleAction(aksi, idTarget) {
    if (confirm('Konfirmasi pembersihan untuk item ini?')) {
        document.getElementById('singleAksi').value = aksi;
        document.getElementById('singleIdTarget').value = idTarget;
        document.getElementById('singleActionForm').submit();
    }
}
</script>
