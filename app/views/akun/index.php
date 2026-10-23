<div class="d-flex justify-content-between align-items-center mb-3 flex-wrap gap-2">
    <h3>Daftar Akun (Chart of Accounts)</h3>
    <div class="btn-group">
        <a href="<?php echo BASEURL; ?>/akun/ekspor" class="btn btn-outline-success">
            <i class="bi bi-file-earmark-excel me-1"></i> Ekspor ke Excel
        </a>
        <?php if (Auth::isAdmin() || Auth::isManager()): // Tampilkan tombol hanya untuk Admin & Manajer ?>
            <button type="button" class="btn btn-outline-primary" data-bs-toggle="modal" data-bs-target="#modalPilihCoa">
                <i class="bi bi-collection me-1"></i> Pilih Template Akun Standar
            </button>
            <button type="button" class="btn btn-success" data-bs-toggle="modal" data-bs-target="#imporModal">
                <i class="bi bi-upload me-1"></i> Impor
            </button>
            <a href="<?php echo BASEURL; ?>/akun/tambah" class="btn btn-primary">
                <i class="bi bi-plus-circle me-1"></i> Tambah Akun Baru
            </a>
        <?php endif; ?>
    </div>
</div>

<div class="card shadow-sm">
    <div class="card-body">
        <div class="table-responsive">
            <table class="table table-hover table-striped align-middle">
                <thead class="table-dark">
                    <tr>
                        <th style="min-width: 180px;">Kode Akun</th>
                        <th>Nama Akun</th>
                        <th style="width: 100px;">Tipe</th>
                        <th style="width: 120px;">Saldo Normal</th>
                        <th class="text-end" style="width: 150px;">Saldo Awal</th>
                        <th class="text-end" style="width: 130px;">Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($data['akun'])): ?>
                        <tr>
                            <td colspan="6" class="text-center py-4">Belum ada data akun.</td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($data['akun'] as $akun): ?>
                        <?php
                            $kode = $akun['kode_akun'];
                            $indent = 0;
                            if (strpos($kode, '.') !== false) {
                                $parts = explode('.', $kode);
                                if (count($parts) >= 4) {
                                    if ($parts[1] === '0' && ($parts[2] === '00' || $parts[2] === '01') && $parts[3] === '00') {
                                        $indent = 0; // Level 1
                                    } elseif ($parts[2] === '00' && $parts[3] === '00') {
                                        $indent = 16; // Level 2
                                    } elseif ($parts[3] === '00') {
                                        $indent = 32; // Level 3
                                    } else {
                                        $indent = 48; // Level 4
                                    }
                                }
                            } else {
                                $digits = preg_replace("/[^0-9]/", "", $kode);
                                $len = strlen($digits);
                                if ($len >= 5) {
                                    $sub = substr($digits, 1);
                                    if ($sub === '0000') $indent = 0;
                                    elseif (substr($sub, 1) === '000') $indent = 16;
                                    elseif (substr($sub, 2) === '00') $indent = 32;
                                    else $indent = 48;
                                } else {
                                    $indent = max(0, ($len - 1) * 16);
                                }
                            }
                        ?>
                        <tr>
                            <td>
                                <span style="padding-left: <?php echo $indent; ?>px;">
                                    <?php if ($akun['tipe_akun'] == 'Header'): ?>
                                        <strong><?php echo htmlspecialchars($akun['kode_akun']); ?></strong>
                                    <?php else: ?>
                                        <?php echo htmlspecialchars($akun['kode_akun']); ?>
                                    <?php endif; ?>
                                </span>
                            </td>
                            <td>
                                <?php echo ($akun['tipe_akun'] == 'Header') ? '<strong>' : '' ?>
                                <?php echo htmlspecialchars($akun['nama_akun']); ?>
                                <?php echo ($akun['tipe_akun'] == 'Header') ? '</strong>' : '' ?>
                            </td>
                            <td>
                                <span class="badge rounded-pill <?php echo ($akun['tipe_akun'] == 'Header') ? 'text-bg-secondary' : 'text-bg-info' ?>">
                                    <?php echo htmlspecialchars($akun['tipe_akun']); ?>
                                </span>
                            </td>
                             <td>
                                <span class="badge rounded-pill <?php echo ($akun['posisi_saldo_normal'] == 'Debit') ? 'text-bg-primary' : 'text-bg-warning' ?>">
                                    <?php echo htmlspecialchars($akun['posisi_saldo_normal']); ?>
                                </span>
                            </td>
                            <td class="text-end font-monospace">
                                <?php echo number_format($akun['saldo_awal'], 2, ',', '.'); ?>
                            </td>
                            <td class="text-end">
                                <?php if (Auth::isAdmin() || Auth::isManager()): // Tampilkan tombol hanya untuk Admin & Manajer ?>
                                    <a href="<?php echo BASEURL; ?>/akun/edit/<?php echo $akun['kode_akun']; ?>" class="btn btn-sm btn-warning">Edit</a>
                                    <a href="<?php echo BASEURL; ?>/akun/hapus/<?php echo $akun['kode_akun']; ?>" class="btn btn-sm btn-danger" onclick="return confirm('Apakah Anda yakin ingin menghapus data ini?');">Hapus</a>
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
</div>

<?php if (Auth::isAdmin() || Auth::isManager()): ?>
<!-- Modal Pilih Template Akun Standar -->
<div class="modal fade" id="modalPilihCoa" tabindex="-1" aria-labelledby="modalPilihCoaLabel" aria-hidden="true">
  <div class="modal-dialog modal-lg">
    <div class="modal-content">
      <div class="modal-header bg-primary text-white">
        <h5 class="modal-title" id="modalPilihCoaLabel">
          <i class="bi bi-collection me-2"></i>Pilihan Bagan Akun Standar (COA)
        </h5>
        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>
      <div class="modal-body p-4">
        <div class="alert alert-warning d-flex align-items-center mb-4" role="alert">
          <i class="bi bi-exclamation-triangle-fill fs-4 me-3 flex-shrink-0"></i>
          <div>
            <strong>Perhatian:</strong> Menerapkan template akun standar akan <strong>menghapus seluruh akun yang ada saat ini</strong> pada entitas ini dan menggantinya dengan akun template yang dipilih. Pengaturan akun default di profil perusahaan juga akan disesuaikan otomatis.
          </div>
        </div>

        <p class="text-muted mb-3">Silakan pilih template bagan akun (Chart of Accounts) yang sesuai dengan karakteristik entitas usaha Anda:</p>

        <div class="row g-3">
          <!-- Opsi 1: SAK EMKM Standar -->
          <div class="col-md-6">
            <div class="card h-100 border-primary shadow-sm">
              <div class="card-header bg-light d-flex justify-content-between align-items-center">
                <span class="fw-bold text-primary">Standar SAK EMKM Umum</span>
                <span class="badge bg-secondary">37 Akun</span>
              </div>
              <div class="card-body d-flex flex-column">
                <p class="card-text small text-secondary flex-grow-1">
                  Format akun ringkas dan fleksibel dengan sistem kode strip (<code>1-1100</code>). Mencakup struktur akuntansi dasar untuk usaha mikro, kecil, dan menengah umum seperti toko, jasa, dan perdagangan ritel.
                </p>
                <ul class="small text-muted ps-3 mb-3">
                  <li>Format Kode: <code>X-YYYY</code></li>
                  <li>37 Akun Terstruktur</li>
                  <li>Sederhana & Mudah Disesuaikan</li>
                </ul>
                <div class="d-grid gap-2">
                  <a href="<?php echo BASEURL; ?>/akun/generate_central?tipe=standar" 
                     class="btn btn-outline-primary"
                     onclick="return confirm('Apakah Anda yakin ingin menerapkan Template Standar SAK EMKM (37 Akun)? Akun yang ada saat ini akan diganti.');">
                    <i class="bi bi-check-circle me-1"></i> Terapkan Template Standar
                  </a>
                  <a href="<?php echo BASEURL; ?>/template_akun_baru.xlsx" class="btn btn-link btn-sm text-center text-decoration-none" download>
                    <i class="bi bi-download me-1"></i> Unduh File Excel (.xlsx)
                  </a>
                </div>
              </div>
            </div>
          </div>

          <!-- Opsi 2: Kepmendesa 136/2022 -->
          <div class="col-md-6">
            <div class="card h-100 border-success shadow-sm">
              <div class="card-header bg-success text-white d-flex justify-content-between align-items-center">
                <span class="fw-bold">Kepmendesa No. 136 Th 2022</span>
                <span class="badge bg-light text-success">343 Akun</span>
              </div>
              <div class="card-body d-flex flex-column">
                <p class="card-text small text-secondary flex-grow-1">
                  Bagan Akun Standar resmi Keputusan Menteri Desa PDTT No. 136/2022 untuk <strong>BUMDesa dan BUMDesma</strong>. Struktur 4 level lengkap dengan unit usaha desa (Wisata, Air Bersih, Sampah, Katering, dll).
                </p>
                <ul class="small text-muted ps-3 mb-3">
                  <li>Format Kode: <code>X.Y.ZZ.WW</code> (4 Level)</li>
                  <li>343 Akun Lengkap & Terverifikasi</li>
                  <li>Mendukung Rekening Koran Unit Usaha & Aset Biologis</li>
                </ul>
                <div class="d-grid gap-2">
                  <a href="<?php echo BASEURL; ?>/akun/generate_central?tipe=kepmendesa" 
                     class="btn btn-success"
                     onclick="return confirm('Apakah Anda yakin ingin menerapkan Template Kepmendesa No. 136 Tahun 2022 (343 Akun)? Akun yang ada saat ini akan diganti dan pengaturan akun perusahaan akan disesuaikan otomatis.');">
                    <i class="bi bi-check2-all me-1"></i> Terapkan Template Kepmendesa
                  </a>
                  <a href="<?php echo BASEURL; ?>/template_akun_kepmendesa136.xlsx" class="btn btn-link btn-sm text-center text-decoration-none text-success" download>
                    <i class="bi bi-download me-1"></i> Unduh File Excel (.xlsx)
                  </a>
                </div>
              </div>
            </div>
          </div>
        </div>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Tutup</button>
      </div>
    </div>
  </div>
</div>

<!-- Modal untuk Form Impor Excel (Hanya dibuat jika user adalah Admin/Manager) -->
<div class="modal fade" id="imporModal" tabindex="-1" aria-labelledby="imporModalLabel" aria-hidden="true">
  <div class="modal-dialog">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title" id="imporModalLabel">Impor Data Akun dari Excel</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>
      <form action="<?php echo BASEURL; ?>/akun/impor" method="post" enctype="multipart/form-data">
          <div class="modal-body">
            <p class="mb-2">Siapkan file Excel Anda (.xlsx) dengan kolom: <br><strong>A: Kode Akun</strong>, <strong>B: Nama Akun</strong>, <strong>C: Tipe</strong>, <strong>D: Saldo Normal</strong>, <strong>E: Saldo Awal</strong>.</p>
            <div class="mb-3 d-flex flex-column gap-1">
                <span class="small text-muted fw-bold">Unduh Contoh Template:</span>
                <a href="<?php echo BASEURL; ?>/template_akun_baru.xlsx" class="btn btn-outline-secondary btn-sm text-start" download>
                    <i class="bi bi-download me-1"></i> Template Standar SAK EMKM (.xlsx - 37 Akun)
                </a>
                <a href="<?php echo BASEURL; ?>/template_akun_kepmendesa136.xlsx" class="btn btn-outline-success btn-sm text-start" download>
                    <i class="bi bi-download me-1"></i> Template Kepmendesa No. 136 Tahun 2022 (.xlsx - 343 Akun)
                </a>
            </div>
            <hr>
            <div class="mb-3">
                <label for="file_excel" class="form-label">Pilih file Excel untuk diimpor:</label>
                <input class="form-control" type="file" name="file_excel" id="file_excel" accept=".xlsx" required>
            </div>
          </div>
          <div class="modal-footer">
            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Batal</button>
            <button type="submit" class="btn btn-primary">Mulai Proses Impor</button>
          </div>
      </form>
    </div>
  </div>
</div>
<?php endif; ?>
