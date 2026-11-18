<?php
$c = $data['perusahaan'] ?? [];
$fiskal = $data['laporan'] ?? [];
$tahunPajak = $data['tahun_pajak'] ?? date('Y');

$labaKomersial = (float)($fiskal['laba_komersial'] ?? 0);
$totalPendapatanKomersial = (float)($fiskal['total_pendapatan_komersial'] ?? 0);
$totalBebanKomersial = (float)($fiskal['total_beban_komersial'] ?? 0);

$rincianPositif = $fiskal['rincian_positif'] ?? [];
$totalPositif = (float)($fiskal['total_positif'] ?? 0);

$rincianNegatif = $fiskal['rincian_negatif'] ?? [];
$totalNegatif = (float)($fiskal['total_negatif'] ?? 0);

$netoFiskal = (float)($fiskal['penghasilan_neto_fiskal'] ?? 0);
$kompensasiRugi = (float)($fiskal['kompensasi_rugi'] ?? 0);
$pkp = (float)($fiskal['pkp'] ?? 0);

$skemaTarif = $fiskal['skema_tarif'] ?? 'pasal_31e';
$omzetBruto = (float)($fiskal['omzet_bruto'] ?? $totalPendapatanKomersial);
$pphTerutang = (float)($fiskal['pph_terutang'] ?? 0);

$rincianKredit = $fiskal['rincian_kredit'] ?? [];
$totalKredit = (float)($fiskal['total_kredit'] ?? 0);
$pphKurangBayar = (float)($fiskal['pph_kurang_bayar'] ?? 0);
$statusKurangBayar = $fiskal['status_kurang_bayar'] ?? 'PPh Terutang';
?>

<div class="row mb-4">
    <div class="col-md-12">
        <div class="d-flex align-items-center justify-content-between flex-wrap gap-2">
            <div>
                <h3 class="fw-bold mb-1"><i class="bi bi-file-earmark-spreadsheet-fill text-info me-2"></i>Laporan Keuangan Fiskal & Rekonsiliasi SPT 1771-I</h3>
                <p class="text-muted mb-0">Rekonsiliasi laba rugi komersial ke fiskal, koreksi positif/negatif UU PPh, dan perhitungan PPh Badan terutang.</p>
            </div>
            <div>
                <a href="<?php echo BASEURL; ?>/laporan" class="btn btn-outline-secondary rounded-pill px-3">
                    <i class="bi bi-arrow-left me-1"></i> Kembali ke Pusat Laporan
                </a>
            </div>
        </div>
    </div>
</div>

<!-- Card Filter Tahun Pajak & Unit -->
<div class="card shadow-sm border-0 mb-4">
    <div class="card-body p-4">
        <form id="filter-form" action="<?php echo BASEURL; ?>/laporan/keuanganFiskal" method="post">
            <div class="row g-3 align-items-end">
                <div class="col-md-2">
                    <label for="tahun_pajak" class="form-label fw-semibold small text-muted">Tahun Pajak</label>
                    <select name="tahun_pajak" id="tahun_pajak" class="form-select fw-bold">
                        <?php 
                        $currentYear = (int)date('Y');
                        for ($y = $currentYear + 1; $y >= $currentYear - 5; $y--): 
                        ?>
                            <option value="<?php echo $y; ?>" <?php echo ($tahunPajak == $y) ? 'selected' : ''; ?>>
                                <?php echo $y; ?>
                            </option>
                        <?php endfor; ?>
                    </select>
                </div>
                <div class="col-md-3">
                    <label for="tanggal_mulai" class="form-label fw-semibold small text-muted">Dari Tanggal</label>
                    <input type="date" name="tanggal_mulai" id="tanggal_mulai" class="form-control" value="<?php echo htmlspecialchars($data['tanggal_mulai'] ?? ($tahunPajak . '-01-01')); ?>">
                </div>
                <div class="col-md-3">
                    <label for="tanggal_selesai" class="form-label fw-semibold small text-muted">Sampai Tanggal</label>
                    <input type="date" name="tanggal_selesai" id="tanggal_selesai" class="form-control" value="<?php echo htmlspecialchars($data['tanggal_selesai'] ?? ($tahunPajak . '-12-31')); ?>">
                </div>
                <div class="col-md-2">
                    <label for="id_unit" class="form-label fw-semibold small text-muted">Unit Usaha</label>
                    <select name="id_unit" id="id_unit" class="form-select">
                        <option value="">-- Konsolidasi --</option>
                        <?php if (!empty($data['units'])): ?>
                            <?php foreach($data['units'] as $u): ?>
                                <option value="<?php echo $u['id_unit']; ?>" <?php echo ($data['id_unit'] == $u['id_unit']) ? 'selected' : ''; ?>>
                                    <?php echo htmlspecialchars($u['nama_unit']); ?>
                                </option>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </select>
                </div>
                <div class="col-md-2">
                    <div class="d-flex gap-2">
                        <button type="submit" class="btn btn-primary flex-grow-1">
                            <i class="bi bi-arrow-repeat me-1"></i> Muat
                        </button>
                        <button type="button" id="btn-cetak-estetik" class="btn btn-dark" title="Cetak Formulir SPT 1771-I">
                            <i class="bi bi-printer-fill"></i>
                        </button>
                        <button type="button" id="export-excel" class="btn btn-outline-success" title="Ekspor ke Excel">
                            <i class="bi bi-file-earmark-excel-fill"></i>
                        </button>
                    </div>
                </div>
            </div>

            <!-- Input tersembunyi untuk ekspor dan cetak -->
            <input type="hidden" name="id_unit_export" id="id_unit_export">
            <input type="hidden" name="tahun_pajak_export" id="tahun_pajak_export">
            <input type="hidden" name="tanggal_mulai_export" id="tanggal_mulai_export">
            <input type="hidden" name="tanggal_selesai_export" id="tanggal_selesai_export">
        </form>
    </div>
</div>

<!-- Kartu Ringkasan Metrik Fiskal -->
<div class="row g-3 mb-4">
    <div class="col-md-6 col-lg-3">
        <div class="card border-0 shadow-sm h-100 bg-light">
            <div class="card-body p-3">
                <div class="d-flex align-items-center justify-content-between mb-2">
                    <span class="small fw-semibold text-uppercase text-muted">Laba Komersial</span>
                    <i class="bi bi-book-half fs-4 text-secondary"></i>
                </div>
                <h4 class="fw-bold mb-1 <?php echo ($labaKomersial >= 0) ? 'text-success' : 'text-danger'; ?>" id="summary-laba-komersial">
                    Rp <?php echo number_format($labaKomersial, 0, ',', '.'); ?>
                </h4>
                <div class="small text-muted">Sebelum penyesuaian fiskal</div>
            </div>
        </div>
    </div>
    <div class="col-md-6 col-lg-3">
        <div class="card border-0 shadow-sm h-100 bg-warning-subtle text-warning-emphasis">
            <div class="card-body p-3">
                <div class="d-flex align-items-center justify-content-between mb-2">
                    <span class="small fw-semibold text-uppercase">Koreksi Fiskal Neto</span>
                    <i class="bi bi-arrow-left-right fs-4 text-warning"></i>
                </div>
                <h4 class="fw-bold mb-1 text-dark" id="summary-koreksi-neto">
                    Rp <?php echo number_format(($totalPositif - $totalNegatif), 0, ',', '.'); ?>
                </h4>
                <div class="small text-muted">+Positif: Rp <?php echo number_format($totalPositif, 0, ',', '.'); ?> | -Negatif: Rp <?php echo number_format($totalNegatif, 0, ',', '.'); ?></div>
            </div>
        </div>
    </div>
    <div class="col-md-6 col-lg-3">
        <div class="card border-0 shadow-sm h-100 bg-primary-subtle text-primary-emphasis">
            <div class="card-body p-3">
                <div class="d-flex align-items-center justify-content-between mb-2">
                    <span class="small fw-semibold text-uppercase">Penghasilan Kena Pajak</span>
                    <i class="bi bi-check2-circle fs-4 text-primary"></i>
                </div>
                <h4 class="fw-bold mb-1" id="summary-pkp">
                    Rp <?php echo number_format($pkp, 0, ',', '.'); ?>
                </h4>
                <div class="small text-muted">Setelah kompensasi rugi (dibulatkan)</div>
            </div>
        </div>
    </div>
    <div class="col-md-6 col-lg-3">
        <div class="card border-0 shadow-sm h-100 bg-danger-subtle text-danger-emphasis">
            <div class="card-body p-3">
                <div class="d-flex align-items-center justify-content-between mb-2">
                    <span class="small fw-semibold text-uppercase">PPh Pasal 29 (Kurang Bayar)</span>
                    <i class="bi bi-cash-coin fs-4 text-danger"></i>
                </div>
                <h4 class="fw-bold mb-1 text-danger" id="summary-kurang-bayar">
                    Rp <?php echo number_format($pphKurangBayar, 0, ',', '.'); ?>
                </h4>
                <div class="small text-muted" id="summary-terutang-kredit">
                    Terutang: Rp <?php echo number_format($pphTerutang, 0, ',', '.'); ?> - Kredit: Rp <?php echo number_format($totalKredit, 0, ',', '.'); ?>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Formulir Rekonsiliasi Fiskal Interaktif -->
<form id="rekon-form" action="<?php echo BASEURL; ?>/laporan/simpanRekonsiliasiFiskal" method="post">
    <input type="hidden" name="tahun_pajak" value="<?php echo $tahunPajak; ?>">
    <input type="hidden" name="tanggal_mulai" value="<?php echo htmlspecialchars($data['tanggal_mulai'] ?? ($tahunPajak . '-01-01')); ?>">
    <input type="hidden" name="tanggal_selesai" value="<?php echo htmlspecialchars($data['tanggal_selesai'] ?? ($tahunPajak . '-12-31')); ?>">
    <input type="hidden" name="id_unit" value="<?php echo htmlspecialchars($data['id_unit'] ?? ''); ?>">
    <input type="hidden" name="laba_komersial" id="form-laba-komersial" value="<?php echo $labaKomersial; ?>">

    <div class="card border-0 shadow-sm mb-4">
        <div class="card-header bg-white py-3 border-bottom d-flex align-items-center justify-content-between flex-wrap gap-2">
            <div>
                <h5 class="fw-bold mb-0 text-dark">
                    <i class="bi bi-journal-text text-primary me-2"></i>Kertas Kerja Rekonsiliasi Fiskal (Formulir SPT 1771-I)
                </h5>
                <small class="text-muted">Standar Lampiran I SPT Tahunan PPh Wajib Pajak Badan Republik Indonesia</small>
            </div>
            <div class="d-flex gap-2">
                <button type="submit" class="btn btn-success">
                    <i class="bi bi-check2-circle me-1"></i> Simpan Kertas Kerja
                </button>
            </div>
        </div>
        <div class="card-body p-4">

            <!-- BAGIAN I: LABA KOMERSIAL -->
            <div class="p-3 mb-4 rounded bg-light border">
                <div class="row align-items-center">
                    <div class="col-md-8">
                        <h6 class="fw-bold mb-1 text-primary">I. PENGHASILAN NETO KOMERSIAL (LABA SEBELUM PAJAK)</h6>
                        <small class="text-muted">Diambil otomatis dari pembukuan Laba Rugi Komersial (Pendapatan: Rp <?php echo number_format($totalPendapatanKomersial, 0, ',', '.'); ?> - Beban: Rp <?php echo number_format($totalBebanKomersial, 0, ',', '.'); ?>)</small>
                    </div>
                    <div class="col-md-4 text-end">
                        <span class="fs-5 fw-bold font-monospace <?php echo ($labaKomersial >= 0) ? 'text-success' : 'text-danger'; ?>">
                            Rp <?php echo number_format($labaKomersial, 2, ',', '.'); ?>
                        </span>
                    </div>
                </div>
            </div>

            <!-- BAGIAN II: PENYESUAIAN FISKAL POSITIF (KOREKSI POSITIF) -->
            <div class="mb-4">
                <div class="d-flex justify-content-between align-items-center mb-3">
                    <h6 class="fw-bold mb-0 text-danger text-uppercase">
                        <i class="bi bi-plus-circle-fill me-2"></i>II. Penyesuaian Fiskal Positif (Koreksi Positif - Pasal 9 UU PPh)
                    </h6>
                    <span class="small text-muted">Menambah laba kena pajak / mengurangi beban komersial</span>
                </div>
                <div class="table-responsive">
                    <table class="table table-bordered table-hover align-middle">
                        <thead class="table-light">
                            <tr>
                                <th style="width: 5%;">No</th>
                                <th style="width: 65%;">Uraian Koreksi Positif Berdasarkan Ketentuan Perpajakan</th>
                                <th class="text-end" style="width: 30%;">Nilai Koreksi Positif (Rp)</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php $noP = 1; foreach ($rincianPositif as $k => $item): ?>
                                <tr>
                                    <td class="text-center fw-semibold"><?php echo $noP++; ?></td>
                                    <td>
                                        <label for="koreksi_positif_<?php echo $k; ?>" class="form-label mb-0 fw-normal">
                                            <?php echo htmlspecialchars($item['label']); ?>
                                        </label>
                                    </td>
                                    <td>
                                        <input type="number" step="any" 
                                               name="koreksi_positif[<?php echo $k; ?>]" 
                                               id="koreksi_positif_<?php echo $k; ?>" 
                                               class="form-control text-end font-monospace input-koreksi-positif" 
                                               value="<?php echo (float)$item['nilai']; ?>" 
                                               oninput="hitungSemuaFiskal()">
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                        <tfoot class="table-danger fw-bold">
                            <tr>
                                <td colspan="2" class="text-end">TOTAL PENYESUAIAN FISKAL POSITIF:</td>
                                <td class="text-end">
                                    <span id="label-total-positif" class="font-monospace fs-6">Rp <?php echo number_format($totalPositif, 2, ',', '.'); ?></span>
                                    <input type="hidden" name="total_koreksi_positif" id="form-total-positif" value="<?php echo $totalPositif; ?>">
                                </td>
                            </tr>
                        </tfoot>
                    </table>
                </div>
            </div>

            <!-- BAGIAN III: PENYESUAIAN FISKAL NEGATIF (KOREKSI NEGATIF) -->
            <div class="mb-4">
                <div class="d-flex justify-content-between align-items-center mb-3">
                    <h6 class="fw-bold mb-0 text-success text-uppercase">
                        <i class="bi bi-dash-circle-fill me-2"></i>III. Penyesuaian Fiskal Negatif (Koreksi Negatif - Pasal 4 UU PPh)
                    </h6>
                    <span class="small text-muted">Mengurangi laba kena pajak (Penghasilan Final & Bukan Objek Pajak)</span>
                </div>
                <div class="table-responsive">
                    <table class="table table-bordered table-hover align-middle">
                        <thead class="table-light">
                            <tr>
                                <th style="width: 5%;">No</th>
                                <th style="width: 65%;">Uraian Koreksi Negatif Berdasarkan Ketentuan Perpajakan</th>
                                <th class="text-end" style="width: 30%;">Nilai Koreksi Negatif (Rp)</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php $noN = 1; foreach ($rincianNegatif as $k => $item): ?>
                                <tr>
                                    <td class="text-center fw-semibold"><?php echo $noN++; ?></td>
                                    <td>
                                        <label for="koreksi_negatif_<?php echo $k; ?>" class="form-label mb-0 fw-normal">
                                            <?php echo htmlspecialchars($item['label']); ?>
                                        </label>
                                    </td>
                                    <td>
                                        <input type="number" step="any" 
                                               name="koreksi_negatif[<?php echo $k; ?>]" 
                                               id="koreksi_negatif_<?php echo $k; ?>" 
                                               class="form-control text-end font-monospace input-koreksi-negatif" 
                                               value="<?php echo (float)$item['nilai']; ?>" 
                                               oninput="hitungSemuaFiskal()">
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                        <tfoot class="table-success fw-bold">
                            <tr>
                                <td colspan="2" class="text-end">TOTAL PENYESUAIAN FISKAL NEGATIF:</td>
                                <td class="text-end">
                                    <span id="label-total-negatif" class="font-monospace fs-6">Rp <?php echo number_format($totalNegatif, 2, ',', '.'); ?></span>
                                    <input type="hidden" name="total_koreksi_negatif" id="form-total-negatif" value="<?php echo $totalNegatif; ?>">
                                </td>
                            </tr>
                        </tfoot>
                    </table>
                </div>
            </div>

            <!-- BAGIAN IV & V: PENGHASILAN NETO FISKAL & KOMPENSASI RUGI -->
            <div class="card border border-primary-subtle bg-light mb-4">
                <div class="card-body p-4">
                    <div class="row g-3 align-items-center mb-3">
                        <div class="col-md-8">
                            <h6 class="fw-bold mb-0 text-primary">IV. PENGHASILAN NETO FISKAL (I + II - III)</h6>
                            <small class="text-muted">Laba komersial ditambah koreksi positif dikurangi koreksi negatif</small>
                        </div>
                        <div class="col-md-4 text-end">
                            <span id="label-neto-fiskal" class="fs-5 fw-bold font-monospace text-primary">
                                Rp <?php echo number_format($netoFiskal, 2, ',', '.'); ?>
                            </span>
                            <input type="hidden" name="penghasilan_neto_fiskal" id="form-neto-fiskal" value="<?php echo $netoFiskal; ?>">
                        </div>
                    </div>

                    <div class="row g-3 align-items-center mb-3 pt-3 border-top">
                        <div class="col-md-8">
                            <h6 class="fw-bold mb-0 text-dark">V. KOMPENSASI KERUGIAN FISKAL</h6>
                            <small class="text-muted">Sisa kerugian tahun-tahun pajak sebelumnya yang dapat dikompensasikan (Maks. 5 tahun)</small>
                        </div>
                        <div class="col-md-4">
                            <input type="number" step="any" name="kompensasi_rugi" id="form-kompensasi-rugi" 
                                   class="form-control text-end font-monospace" 
                                   value="<?php echo $kompensasiRugi; ?>" 
                                   oninput="hitungSemuaFiskal()">
                        </div>
                    </div>

                    <div class="row g-3 align-items-center pt-3 border-top">
                        <div class="col-md-8">
                            <h5 class="fw-bold mb-0 text-dark">VI. PENGHASILAN KENA PAJAK (PKP) (IV - V)</h5>
                            <small class="text-muted">Sesuai UU KUP & PPh, PKP dibulatkan ke bawah hingga ribuan penuh</small>
                        </div>
                        <div class="col-md-4 text-end">
                            <span id="label-pkp" class="fs-4 fw-bold font-monospace text-dark">
                                Rp <?php echo number_format($pkp, 0, ',', '.'); ?>
                            </span>
                            <input type="hidden" name="pkp" id="form-pkp" value="<?php echo $pkp; ?>">
                        </div>
                    </div>
                </div>
            </div>

            <!-- BAGIAN VII: SKEMA TARIF & PERHITUNGAN PPH TERUTANG -->
            <div class="card border border-warning-subtle mb-4">
                <div class="card-header bg-warning-subtle py-3">
                    <h6 class="fw-bold mb-0 text-dark">
                        <i class="bi bi-percent me-2"></i>VII. Perhitungan Pajak Penghasilan (PPh) Badan Terutang
                    </h6>
                </div>
                <div class="card-body p-4">
                    <div class="row g-3">
                        <div class="col-md-6">
                            <label for="skema_tarif" class="form-label fw-bold">Pilih Skema Tarif Perpajakan</label>
                            <select name="skema_tarif" id="skema_tarif" class="form-select border-primary shadow-sm" onchange="hitungSemuaFiskal()">
                                <option value="pasal_31e" <?php echo ($skemaTarif === 'pasal_31e') ? 'selected' : ''; ?>>
                                    Fasilitas Pasal 31E UU PPh (Diskon 50% / Efektif 11% untuk Omzet s.d. 4,8M)
                                </option>
                                <option value="tarif_umum" <?php echo ($skemaTarif === 'tarif_umum') ? 'selected' : ''; ?>>
                                    Tarif Normal PPh Badan 22% (Pasal 17 ayat 1b)
                                </option>
                                <option value="umkm_final" <?php echo ($skemaTarif === 'umkm_final') ? 'selected' : ''; ?>>
                                    Skema PPh Final UMKM 0,5% dari Peredaran Bruto (PP 55/2022)
                                </option>
                            </select>
                            <div class="small text-muted mt-2" id="keterangan-skema">
                                <?php echo htmlspecialchars($fiskal['kalkulasi_detail']['keterangan'] ?? ''); ?>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <label for="omzet_bruto" class="form-label fw-bold">Total Peredaran Bruto / Omzet Setahun (Rp)</label>
                            <input type="number" step="any" name="omzet_bruto" id="form-omzet-bruto" 
                                   class="form-control font-monospace" 
                                   value="<?php echo $omzetBruto; ?>" 
                                   oninput="hitungSemuaFiskal()">
                            <small class="text-muted">Digunakan sebagai dasar penentuan batas fasilitas Pasal 31E atau PPh Final UMKM</small>
                        </div>
                    </div>

                    <div class="row mt-4 p-3 bg-light rounded border">
                        <div class="col-md-8">
                            <h5 class="fw-bold mb-0 text-dark">PPH BADAN TERUTANG TAHUN PAJAK <?php echo $tahunPajak; ?></h5>
                            <span class="small text-muted" id="label-skema-active">Skema terpilih: <?php echo htmlspecialchars($fiskal['kalkulasi_detail']['label'] ?? 'Pasal 31E'); ?></span>
                        </div>
                        <div class="col-md-4 text-end">
                            <span id="label-pph-terutang" class="fs-4 fw-bold font-monospace text-danger">
                                Rp <?php echo number_format($pphTerutang, 0, ',', '.'); ?>
                            </span>
                            <input type="hidden" name="pph_terutang" id="form-pph-terutang" value="<?php echo $pphTerutang; ?>">
                        </div>
                    </div>
                </div>
            </div>

            <!-- BAGIAN VIII & IX: KREDIT PAJAK & STATUS AKHIR -->
            <div class="card border border-info-subtle mb-4">
                <div class="card-header bg-info-subtle py-3">
                    <h6 class="fw-bold mb-0 text-dark">
                        <i class="bi bi-wallet2 me-2"></i>VIII & IX. Kredit Pajak & Status PPh Kurang / (Lebih) Bayar
                    </h6>
                </div>
                <div class="card-body p-4">
                    <div class="table-responsive mb-3">
                        <table class="table table-bordered align-middle">
                            <thead class="table-light">
                                <tr>
                                    <th style="width: 5%;">No</th>
                                    <th style="width: 65%;">Jenis Kredit Pajak (Pajak Telah Dibayar / Dipotong Pihak Lain)</th>
                                    <th class="text-end" style="width: 30%;">Jumlah Kredit Pajak (Rp)</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php $noK = 1; foreach ($rincianKredit as $k => $item): ?>
                                    <tr>
                                        <td class="text-center fw-semibold"><?php echo $noK++; ?></td>
                                        <td>
                                            <label for="kredit_pajak_<?php echo $k; ?>" class="form-label mb-0 fw-normal">
                                                <?php echo htmlspecialchars($item['label']); ?>
                                            </label>
                                        </td>
                                        <td>
                                            <input type="number" step="any" 
                                                   name="kredit_pajak[<?php echo $k; ?>]" 
                                                   id="kredit_pajak_<?php echo $k; ?>" 
                                                   class="form-control text-end font-monospace input-kredit-pajak" 
                                                   value="<?php echo (float)$item['nilai']; ?>" 
                                                   oninput="hitungSemuaFiskal()">
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                            <tfoot class="table-light fw-bold">
                                <tr>
                                    <td colspan="2" class="text-end">TOTAL KREDIT PAJAK:</td>
                                    <td class="text-end">
                                        <span id="label-total-kredit" class="font-monospace fs-6">Rp <?php echo number_format($totalKredit, 2, ',', '.'); ?></span>
                                        <input type="hidden" name="total_kredit_pajak" id="form-total-kredit" value="<?php echo $totalKredit; ?>">
                                    </td>
                                </tr>
                            </tfoot>
                        </table>
                    </div>

                    <!-- IX. STATUS AKHIR PPH 29 -->
                    <div class="p-3 rounded bg-danger-subtle border border-danger">
                        <div class="row align-items-center">
                            <div class="col-md-8">
                                <h5 class="fw-bold mb-1 text-danger-emphasis" id="label-status-kurang-bayar">
                                    <?php echo strtoupper($statusKurangBayar); ?>
                                </h5>
                                <div class="small text-muted">
                                    PPh Terutang dikurangi Total Kredit Pajak yang dapat dikreditkan
                                </div>
                            </div>
                            <div class="col-md-4 text-end">
                                <span id="label-pph-kurang-bayar" class="fs-3 fw-bold font-monospace text-danger">
                                    Rp <?php echo number_format($pphKurangBayar, 2, ',', '.'); ?>
                                </span>
                                <input type="hidden" name="pph_kurang_bayar" id="form-pph-kurang-bayar" value="<?php echo $pphKurangBayar; ?>">
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Catatan Rekonsiliasi -->
            <div class="mb-4">
                <label for="catatan" class="form-label fw-bold">Catatan Penjelasan Fiskal / Auditor</label>
                <textarea name="catatan" id="catatan" rows="3" class="form-control" placeholder="Tuliskan catatan rekonsiliasi fiskal, nomor surat keterangan, atau dasar koreksi khusus jika ada..."><?php echo htmlspecialchars($fiskal['catatan'] ?? ''); ?></textarea>
            </div>

            <!-- Tombol Simpan Bawah -->
            <div class="text-end">
                <button type="submit" class="btn btn-success btn-lg px-4 shadow">
                    <i class="bi bi-save me-2"></i> Simpan Kertas Kerja Rekonsiliasi Fiskal
                </button>
            </div>

        </div>
    </div>
</form>

<script>
function hitungSemuaFiskal() {
    const labaKomersial = parseFloat(document.getElementById('form-laba-komersial').value) || 0;

    // 1. Total Positif
    let totPositif = 0;
    document.querySelectorAll('.input-koreksi-positif').forEach(inp => {
        totPositif += parseFloat(inp.value) || 0;
    });
    document.getElementById('form-total-positif').value = totPositif;
    document.getElementById('label-total-positif').textContent = 'Rp ' + formatRupiahJs(totPositif);

    // 2. Total Negatif
    let totNegatif = 0;
    document.querySelectorAll('.input-koreksi-negatif').forEach(inp => {
        totNegatif += parseFloat(inp.value) || 0;
    });
    document.getElementById('form-total-negatif').value = totNegatif;
    document.getElementById('label-total-negatif').textContent = 'Rp ' + formatRupiahJs(totNegatif);

    // 3. Neto Fiskal
    const netoFiskal = labaKomersial + totPositif - totNegatif;
    document.getElementById('form-neto-fiskal').value = netoFiskal;
    document.getElementById('label-neto-fiskal').textContent = 'Rp ' + formatRupiahJs(netoFiskal);

    // 4. Kompensasi Rugi & PKP
    const kompensasi = parseFloat(document.getElementById('form-kompensasi-rugi').value) || 0;
    let pkpRaw = Math.max(0, netoFiskal - kompensasi);
    // Pembulatan ke bawah ribuan penuh
    const pkp = Math.floor(pkpRaw / 1000) * 1000;
    document.getElementById('form-pkp').value = pkp;
    document.getElementById('label-pkp').textContent = 'Rp ' + formatRupiahJs(pkp, 0);

    // 5. PPh Terutang berdasarkan Skema
    const skema = document.getElementById('skema_tarif').value;
    const omzet = parseFloat(document.getElementById('form-omzet-bruto').value) || 0;
    let pphTerutang = 0;
    let ketSkema = '';
    let labelSkema = '';

    if (skema === 'umkm_final') {
        pphTerutang = Math.round(omzet * 0.005);
        ketSkema = 'Dikenakan PPh Final 0,5% dari seluruh omzet bruto Rp ' + formatRupiahJs(omzet, 0);
        labelSkema = 'PPh Final UMKM 0,5% (PP 55 Tahun 2022)';
    } else if (skema === 'tarif_umum') {
        pphTerutang = Math.round(pkp * 0.22);
        ketSkema = 'Dikenakan Tarif Normal 22% dari seluruh Penghasilan Kena Pajak Rp ' + formatRupiahJs(pkp, 0);
        labelSkema = 'Tarif Normal PPh Badan 22% (Pasal 17 UU PPh)';
    } else {
        // pasal_31e
        if (omzet <= 4800000000) {
            pphTerutang = Math.round(pkp * 0.11);
            ketSkema = 'Fasilitas pengurangan 50% tarif (efektif 11%) untuk seluruh PKP karena omzet <= Rp 4,8 M';
            labelSkema = 'Fasilitas Pasal 31E UU PPh (Tarif Efektif 11%)';
        } else if (omzet <= 50000000000) {
            const proporsi = 4800000000 / omzet;
            const pkpFasilitas = Math.floor((proporsi * pkp) / 1000) * 1000;
            const pkpNonFasilitas = Math.max(0, pkp - pkpFasilitas);
            pphTerutang = Math.round((pkpFasilitas * 0.11) + (pkpNonFasilitas * 0.22));
            ketSkema = 'Proporsional: Bagian PKP Fasilitas 11% (Rp ' + formatRupiahJs(pkpFasilitas, 0) + ') + Non-Fasilitas 22% (Rp ' + formatRupiahJs(pkpNonFasilitas, 0) + ')';
            labelSkema = 'Fasilitas Proporsional Pasal 31E UU PPh';
        } else {
            pphTerutang = Math.round(pkp * 0.22);
            ketSkema = 'Tarif penuh 22% karena omzet melebihi Rp 50 Miliar';
            labelSkema = 'Tarif PPh Badan 22% (Tanpa Fasilitas)';
        }
    }

    document.getElementById('form-pph-terutang').value = pphTerutang;
    document.getElementById('label-pph-terutang').textContent = 'Rp ' + formatRupiahJs(pphTerutang, 0);
    document.getElementById('keterangan-skema').textContent = ketSkema;
    document.getElementById('label-skema-active').textContent = 'Skema terpilih: ' + labelSkema;

    // 6. Kredit Pajak
    let totKredit = 0;
    document.querySelectorAll('.input-kredit-pajak').forEach(inp => {
        totKredit += parseFloat(inp.value) || 0;
    });
    document.getElementById('form-total-kredit').value = totKredit;
    document.getElementById('label-total-kredit').textContent = 'Rp ' + formatRupiahJs(totKredit);

    // 7. PPh Kurang / Lebih Bayar (Pasal 29 / 28A)
    const kurangBayar = pphTerutang - totKredit;
    document.getElementById('form-pph-kurang-bayar').value = kurangBayar;
    document.getElementById('label-pph-kurang-bayar').textContent = 'Rp ' + formatRupiahJs(kurangBayar);

    let statusTeks = 'NIHIL';
    if (kurangBayar > 0) {
        statusTeks = 'PPH PASAL 29 (KURANG BAYAR)';
    } else if (kurangBayar < 0) {
        statusTeks = 'PPH PASAL 28A (LEBIH BAYAR)';
    }
    document.getElementById('label-status-kurang-bayar').textContent = statusTeks;

    // Update kartu metrik ringkasan
    document.getElementById('summary-koreksi-neto').textContent = 'Rp ' + formatRupiahJs(totPositif - totNegatif, 0);
    document.getElementById('summary-pkp').textContent = 'Rp ' + formatRupiahJs(pkp, 0);
    document.getElementById('summary-kurang-bayar').textContent = 'Rp ' + formatRupiahJs(kurangBayar, 0);
    document.getElementById('summary-terutang-kredit').textContent = 'Terutang: Rp ' + formatRupiahJs(pphTerutang, 0) + ' - Kredit: Rp ' + formatRupiahJs(totKredit, 0);
}

function formatRupiahJs(number, decimals = 2) {
    let parts = (Math.abs(number).toFixed(decimals)).split('.');
    parts[0] = parts[0].replace(/\B(?=(\d{3})+(?!\d))/g, '.');
    let res = parts.join(',');
    return (number < 0 ? '-' : '') + res;
}

document.addEventListener('DOMContentLoaded', function() {
    const filterForm = document.getElementById('filter-form');
    const exportExcelBtn = document.getElementById('export-excel');
    const cetakEstetikBtn = document.getElementById('btn-cetak-estetik');

    function prepareFilterExportData() {
        document.getElementById('id_unit_export').value = document.getElementById('id_unit').value;
        document.getElementById('tahun_pajak_export').value = document.getElementById('tahun_pajak').value;
        document.getElementById('tanggal_mulai_export').value = document.getElementById('tanggal_mulai').value;
        document.getElementById('tanggal_selesai_export').value = document.getElementById('tanggal_selesai').value;
    }

    if (exportExcelBtn) {
        exportExcelBtn.addEventListener('click', function() {
            prepareFilterExportData();
            filterForm.action = "<?php echo BASEURL; ?>/laporan/eksporKeuanganFiskal";
            filterForm.target = "_self";
            filterForm.submit();
            filterForm.action = "<?php echo BASEURL; ?>/laporan/keuanganFiskal";
        });
    }

    if (cetakEstetikBtn) {
        cetakEstetikBtn.addEventListener('click', function() {
            prepareFilterExportData();
            filterForm.action = "<?php echo BASEURL; ?>/laporan/cetakKeuanganFiskal";
            filterForm.target = "_blank";
            filterForm.submit();
            filterForm.action = "<?php echo BASEURL; ?>/laporan/keuanganFiskal";
            filterForm.target = "_self";
        });
    }
});
</script>
