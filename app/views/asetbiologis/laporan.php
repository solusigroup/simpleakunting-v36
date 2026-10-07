<div class="d-flex justify-content-between align-items-center mb-4 d-print-none">
    <h3 class="fw-bold mb-0">Laporan Aset Biologis (PSAK 241)</h3>
    <a href="<?php echo BASEURL; ?>/asetbiologis" class="btn btn-outline-secondary rounded-pill px-4">
        <i class="bi bi-arrow-left me-2"></i>Kembali
    </a>
</div>

<div class="card border-0 shadow-sm mb-4 d-print-none">
    <div class="card-body">
        <form action="<?php echo BASEURL; ?>/asetbiologis/laporan" method="get" class="row g-3 align-items-end">
            <div class="col-md-4">
                <label class="form-label fw-bold small">Periode Mulai</label>
                <input type="date" name="mulai" class="form-control rounded-3" value="<?php echo htmlspecialchars($_GET['mulai'] ?? date('Y-m-01')); ?>" required>
            </div>
            <div class="col-md-4">
                <label class="form-label fw-bold small">Periode Selesai</label>
                <input type="date" name="selesai" class="form-control rounded-3" value="<?php echo htmlspecialchars($_GET['selesai'] ?? date('Y-m-t')); ?>" required>
            </div>
            <div class="col-md-4">
                <button type="submit" class="btn btn-primary rounded-pill px-4 w-100">
                    <i class="bi bi-filter me-2"></i>Tampilkan Laporan
                </button>
            </div>
        </form>
    </div>
</div>

<?php
$c = $data['perusahaan'] ?? [];
$logoPath = !empty($c['path_logo']) ? BASEURL . '/' . $c['path_logo'] : BASEURL . '/img/logo_jatim.png';
$kota = $c['kota_laporan'] ?? 'Mojokerto';
?>
<div class="d-none d-print-block mb-4">
    <div style="display: flex; align-items: center; gap: 20px; padding-bottom: 12px;">
        <img src="<?php echo $logoPath; ?>" alt="Logo" style="width: 65px; height: 65px; object-fit: contain;" onerror="this.src='<?php echo BASEURL; ?>/img/icon-512.png'">
        <div style="flex-grow: 1;">
            <div style="font-size: 15pt; font-weight: 800; text-transform: uppercase; color: #0f172a; line-height: 1.2;"><?php echo htmlspecialchars($c['nama_perusahaan'] ?? 'KLINIK BUMDESA PROVINSI JAWA TIMUR'); ?></div>
            <div style="font-size: 8.5pt; font-weight: 600; color: #475569; text-transform: uppercase;"><?php echo htmlspecialchars($c['jenis_usaha'] ?? 'Sistem Informasi Akuntansi & Tata Kelola Keuangan'); ?></div>
            <div style="font-size: 8pt; color: #64748b;"><?php echo htmlspecialchars($c['alamat'] ?? 'Jawa Timur, Indonesia'); ?></div>
            <div style="font-size: 7.5pt; color: #64748b;">
                <?php if(!empty($c['telepon'])): ?><span>Telp: <?php echo htmlspecialchars($c['telepon']); ?></span> &bull; <?php endif; ?>
                <?php if(!empty($c['email'])): ?><span>Email: <?php echo htmlspecialchars($c['email']); ?></span><?php endif; ?>
            </div>
        </div>
        <div style="text-align: right;">
            <span style="display: inline-block; padding: 3px 8px; font-size: 7.5pt; font-weight: 700; background: #f1f5f9; border: 1px solid #cbd5e1; border-radius: 4px;">PSAK 241 / IAS 41</span>
            <div style="font-size: 7.5pt; color: #94a3b8; margin-top: 4px;">Dicetak: <?php echo date('d/m/Y H:i'); ?> WIB</div>
        </div>
    </div>
    <div style="height: 2.5px; background: #0f172a; margin-bottom: 2px;"></div>
    <div style="height: 1px; background: #94a3b8; margin-bottom: 15px;"></div>
    
    <div style="text-align: center; margin-bottom: 18px;">
        <h4 style="font-size: 13pt; font-weight: 800; text-transform: uppercase; margin: 0; color: #0f172a;">LAPORAN ASET BIOLOGIS (AGRIKULTUR)</h4>
        <div style="font-size: 8pt; color: #64748b; text-transform: uppercase; margin-top: 2px;">STANDAR PSAK 241 / IAS 41 AGRIKULTUR &bull; ENTITAS BISNIS &amp; BUMDESA</div>
        <div style="display: inline-block; margin-top: 6px; padding: 2px 10px; background: #f8fafc; border: 1px solid #e2e8f0; border-radius: 9999px; font-size: 7.5pt; font-weight: 700; color: #334155;">
            Periode: <?php echo htmlspecialchars($_GET['mulai'] ?? date('Y-m-01')) . ' s/d ' . htmlspecialchars($_GET['selesai'] ?? date('Y-m-t')); ?>
        </div>
    </div>
</div>

<?php if (isset($data['rekonsiliasi'])): ?>
<div class="card border-0 shadow-sm mb-4">
    <div class="card-header bg-white border-0 pt-4 pb-0">
        <h5 class="fw-bold mb-0">Rekonsiliasi Nilai Tercatat (IAS 41.50)</h5>
    </div>
    <div class="card-body">
        <div class="table-responsive">
            <table class="table table-bordered table-hover align-middle mb-0">
                <thead class="bg-light">
                    <tr class="text-center align-middle">
                        <th rowspan="2">Kategori</th>
                        <th rowspan="2">Saldo Awal</th>
                        <th colspan="2">Penambahan</th>
                        <th colspan="2">Penyesuaian Nilai Wajar</th>
                        <th colspan="3">Pengurangan</th>
                        <th rowspan="2">Saldo Akhir</th>
                    </tr>
                    <tr class="text-center">
                        <th>Pembelian</th>
                        <th>Kelahiran/Pertumbuhan</th>
                        <th>Keuntungan</th>
                        <th>Kerugian</th>
                        <th>Panen</th>
                        <th>Penjualan</th>
                        <th>Kematian/Hapus</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($data['rekonsiliasi'] as $kat => $row): ?>
                    <tr>
                        <td class="fw-medium"><?php echo htmlspecialchars($kat); ?></td>
                        <td class="text-end">Rp <?php echo number_format($row['saldo_awal'] ?? 0, 2, ',', '.'); ?></td>
                        <td class="text-end">Rp <?php echo number_format($row['pembelian'] ?? 0, 2, ',', '.'); ?></td>
                        <td class="text-end">Rp <?php echo number_format($row['kelahiran'] ?? 0, 2, ',', '.'); ?></td>
                        <td class="text-end text-success">Rp <?php echo number_format($row['keuntungan_fv'] ?? 0, 2, ',', '.'); ?></td>
                        <td class="text-end text-danger">Rp <?php echo number_format($row['kerugian_fv'] ?? 0, 2, ',', '.'); ?></td>
                        <td class="text-end text-danger">Rp <?php echo number_format($row['panen'] ?? 0, 2, ',', '.'); ?></td>
                        <td class="text-end text-danger">Rp <?php echo number_format($row['penjualan'] ?? 0, 2, ',', '.'); ?></td>
                        <td class="text-end text-danger">Rp <?php echo number_format($row['kematian'] ?? 0, 2, ',', '.'); ?></td>
                        <td class="text-end fw-bold">Rp <?php echo number_format($row['saldo_akhir'] ?? 0, 2, ',', '.'); ?></td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>
<?php endif; ?>

<div class="card border-0 shadow-sm mb-4">
    <div class="card-header bg-white border-0 pt-4 pb-0">
        <h5 class="fw-bold mb-0">Ringkasan Klasifikasi Aset Aktif</h5>
    </div>
    <div class="card-body">
        <div class="table-responsive">
            <table class="table table-bordered table-hover align-middle mb-0">
                <thead class="bg-light">
                    <tr>
                        <th>Kategori</th>
                        <th>Status Kematangan</th>
                        <th class="text-center">Jumlah Aset</th>
                        <th class="text-end">Total Nilai Tercatat</th>
                    </tr>
                </thead>
                <tbody>
                    <?php 
                    $total_aset = 0;
                    $total_nilai = 0;
                    foreach ($data['ringkasan'] ?? [] as $row): 
                        $total_aset += $row['jumlah'];
                        $total_nilai += $row['total_nilai'];
                    ?>
                    <tr>
                        <td class="fw-medium"><?php echo htmlspecialchars($row['kategori']); ?></td>
                        <td><?php echo htmlspecialchars($row['status_kematangan']); ?></td>
                        <td class="text-center"><?php echo htmlspecialchars($row['jumlah']); ?></td>
                        <td class="text-end">Rp <?php echo number_format($row['total_nilai'], 2, ',', '.'); ?></td>
                    </tr>
                    <?php endforeach; ?>
                    <tr class="bg-light fw-bold">
                        <td colspan="2" class="text-end">TOTAL</td>
                        <td class="text-center"><?php echo $total_aset; ?></td>
                        <td class="text-end text-success">Rp <?php echo number_format($total_nilai, 2, ',', '.'); ?></td>
                    </tr>
                </tbody>
            </table>
        </div>
    </div>
</div>

<!-- Tanda Tangan Formal Saat Cetak -->
<div class="d-none d-print-block mt-5 pt-4">
    <div style="display: flex; justify-content: space-between; text-align: center; page-break-inside: avoid;">
        <div style="width: 250px;">
            <p style="margin: 0; font-size: 9pt; color: #475569;">Mengetahui,</p>
            <p style="margin: 2px 0 0 0; font-weight: 700; font-size: 9.5pt; color: #0f172a;">Pimpinan / Direktur</p>
            <div style="height: 65px;"></div>
            <p style="margin: 0; font-weight: 700; text-decoration: underline; font-size: 9.5pt; color: #0f172a;">( ............................................ )</p>
            <p style="margin: 2px 0 0 0; font-size: 8pt; color: #64748b;">Penanggung Jawab Usaha</p>
        </div>
        <div style="width: 250px;">
            <p style="margin: 0; font-size: 9pt; color: #475569;"><?php echo htmlspecialchars($kota); ?>, <?php echo tanggal_indo(date('Y-m-d')); ?></p>
            <p style="margin: 2px 0 0 0; font-weight: 700; font-size: 9.5pt; color: #0f172a;">Pengelola Aset / Akuntan</p>
            <div style="height: 65px;"></div>
            <p style="margin: 0; font-weight: 700; text-decoration: underline; font-size: 9.5pt; color: #0f172a;">( ............................................ )</p>
            <p style="margin: 2px 0 0 0; font-size: 8pt; color: #64748b;">Penyusun Laporan</p>
        </div>
    </div>
</div>

<div class="text-end d-print-none mb-5">
    <button type="button" class="btn btn-dark rounded-pill px-4" onclick="window.print()">
        <i class="fas fa-print me-2"></i>Cetak Laporan
    </button>
</div>

<style>
@media print {
    body { background-color: #fff !important; }
    .card { box-shadow: none !important; border: 1px solid #cbd5e1 !important; margin-bottom: 20px !important; }
    .card-header { border-bottom: 1px solid #cbd5e1 !important; background-color: #f8fafc !important; }
    .table-bordered th, .table-bordered td { border: 1px solid #94a3b8 !important; font-size: 8pt !important; }
    .table-bordered thead th { background-color: #f1f5f9 !important; color: #0f172a !important; font-weight: 700 !important; }
    .badge { border: 1px solid #000; color: #000 !important; background: transparent !important; }
    .d-print-none { display: none !important; }
}
</style>
