<?php

require_once 'Jurnal_model.php';

class Analisis_model {
    private $db;

    public function __construct($db) {
        $this->db = $db;
    }

    /**
     * =========================================================================
     * FEATURE C: ANALISIS RASIO KEUANGAN
     * =========================================================================
     */
    public function getRasioKeuangan($tanggal_mulai, $tanggal_selesai, $tenant_id, $id_unit = null) {
        $jurnalModel = new Jurnal_model($this->db);

        // Ambil Laba Rugi dan Posisi Keuangan dari Jurnal_model
        $labaRugi = $jurnalModel->getLabaRugi($tanggal_mulai, $tanggal_selesai, null, null, $tenant_id, $id_unit);
        $posisiKeuangan = $jurnalModel->getPosisiKeuangan($tanggal_selesai, null, $tenant_id, $id_unit);

        $totalPendapatan = (float)($labaRugi['total_pendapatan_1'] ?? 0);
        $totalBeban = (float)($labaRugi['total_beban_1'] ?? 0);
        $labaBersih = $totalPendapatan - $totalBeban;

        // Pisahkan HPP (kode akun 5-xxx) dan Beban Operasional (kode akun 6-xxx)
        $hpp = 0;
        $bebanOperasional = 0;
        $bebanLainnya = 0;
        foreach (($labaRugi['beban'] ?? []) as $akun) {
            $prefix = substr($akun['kode_akun'], 0, 1);
            if ($prefix === '5') {
                $hpp += (float)$akun['total_1'];
            } elseif ($prefix === '6') {
                $bebanOperasional += (float)$akun['total_1'];
            } else {
                $bebanLainnya += (float)$akun['total_1'];
            }
        }
        $labaKotor = $totalPendapatan - $hpp;
        $labaOperasional = $labaKotor - $bebanOperasional;

        // Evaluasi Komponen Aset
        $kasDanBank = 0;
        $piutang = 0;
        $persediaan = 0;
        $asetLancarLainnya = 0;
        $asetLancar = 0;
        $asetTetap = 0;

        foreach (($posisiKeuangan['periode_1']['aset'] ?? []) as $akun) {
            $kode = $akun['kode_akun'];
            $nilai = (float)$akun['total'];

            // Cek klasifikasi akun dari kode atau query akun jika perlu
            // Standard SimpleAkunting: 1-1 = Aset Lancar, 1-2 = Aset Tetap
            if (substr($kode, 0, 3) === '1-1') {
                $asetLancar += $nilai;

                if (substr($kode, 0, 4) === '1-10' && (int)substr($kode, 4, 1) <= 0) {
                    // Kas / Bank: 1-100xx
                    $kasDanBank += $nilai;
                } elseif (substr($kode, 0, 5) === '1-101' || stripos($akun['nama_akun'], 'piutang') !== false) {
                    $piutang += $nilai;
                } elseif (substr($kode, 0, 5) === '1-102' || stripos($akun['nama_akun'], 'persediaan') !== false) {
                    $persediaan += $nilai;
                } else {
                    $asetLancarLainnya += $nilai;
                }
            } elseif (substr($kode, 0, 3) === '1-2' || substr($kode, 0, 1) === '1') {
                $asetTetap += $nilai;
            }
        }

        // Jika rincian kasDanBank belum tertangkap karena format kode akun alternatif, cari dari tipe_akun
        if ($kasDanBank == 0 && $asetLancar > 0) {
            $this->db->query("SELECT a.kode_akun, a.nama_akun, a.tipe_akun 
                              FROM akun a WHERE a.tenant_id = :tenant_id AND a.tipe_akun = 'Kas & Bank'");
            $this->db->bind('tenant_id', $tenant_id);
            $kasAccounts = array_column($this->db->resultSet(), 'kode_akun');

            foreach (($posisiKeuangan['periode_1']['aset'] ?? []) as $akun) {
                if (in_array($akun['kode_akun'], $kasAccounts)) {
                    $kasDanBank += (float)$akun['total'];
                }
            }
        }

        // Evaluasi Komponen Liabilitas & Ekuitas
        $utangLancar = 0;
        $utangJangkaPanjang = 0;
        foreach (($posisiKeuangan['periode_1']['kewajiban'] ?? []) as $akun) {
            $kode = $akun['kode_akun'];
            $nilai = (float)$akun['total'];
            if (substr($kode, 0, 3) === '2-1') {
                $utangLancar += $nilai;
            } else {
                $utangJangkaPanjang += $nilai;
            }
        }

        $totalLiabilitas = (float)($posisiKeuangan['periode_1']['total_kewajiban'] ?? 0);
        if ($totalLiabilitas == 0) {
            $totalLiabilitas = $utangLancar + $utangJangkaPanjang;
        }

        $totalAset = (float)($posisiKeuangan['periode_1']['total_aset'] ?? 0);
        $totalEkuitas = (float)($posisiKeuangan['periode_1']['total_modal'] ?? 0);

        // 1. RASIO LIKUIDITAS
        $rasioLancar = ($utangLancar > 0) ? ($asetLancar / $utangLancar) : ($asetLancar > 0 ? 99.9 : 0);
        $rasioCepat = ($utangLancar > 0) ? (($asetLancar - $persediaan) / $utangLancar) : ($asetLancar > 0 ? 99.9 : 0);
        $rasioKas = ($utangLancar > 0) ? ($kasDanBank / $utangLancar) : ($kasDanBank > 0 ? 99.9 : 0);
        $modalKerjaBersih = $asetLancar - $utangLancar;

        // 2. RASIO SOLVABILITAS / LEVERAGE
        $dar = ($totalAset > 0) ? ($totalLiabilitas / $totalAset) * 100 : 0;
        $der = ($totalEkuitas > 0) ? ($totalLiabilitas / $totalEkuitas) * 100 : 0;
        $equityMultiplier = ($totalEkuitas > 0) ? ($totalAset / $totalEkuitas) : 0;

        // 3. RASIO PROFITABILITAS
        $gpm = ($totalPendapatan > 0) ? ($labaKotor / $totalPendapatan) * 100 : 0;
        $opm = ($totalPendapatan > 0) ? ($labaOperasional / $totalPendapatan) * 100 : 0;
        $npm = ($totalPendapatan > 0) ? ($labaBersih / $totalPendapatan) * 100 : 0;
        $roa = ($totalAset > 0) ? ($labaBersih / $totalAset) * 100 : 0;
        $roe = ($totalEkuitas > 0) ? ($labaBersih / $totalEkuitas) * 100 : 0;

        // 4. RASIO AKTIVITAS & EFISIENSI
        $tato = ($totalAset > 0) ? ($totalPendapatan / $totalAset) : 0;
        $workingCapitalTurnover = ($modalKerjaBersih > 0) ? ($totalPendapatan / $modalKerjaBersih) : 0;
        $bopo = ($totalPendapatan > 0) ? (($hpp + $bebanOperasional) / $totalPendapatan) * 100 : 0;

        return [
            'ringkasan_keuangan' => [
                'total_pendapatan' => $totalPendapatan,
                'hpp' => $hpp,
                'laba_kotor' => $labaKotor,
                'beban_operasional' => $bebanOperasional,
                'laba_operasional' => $labaOperasional,
                'total_beban' => $totalBeban,
                'laba_bersih' => $labaBersih,
                'kas_dan_bank' => $kasDanBank,
                'piutang' => $piutang,
                'persediaan' => $persediaan,
                'aset_lancar' => $asetLancar,
                'aset_tetap' => $asetTetap,
                'total_aset' => $totalAset,
                'utang_lancar' => $utangLancar,
                'utang_jangka_panjang' => $utangJangkaPanjang,
                'total_liabilitas' => $totalLiabilitas,
                'total_ekuitas' => $totalEkuitas,
                'modal_kerja_bersih' => $modalKerjaBersih,
            ],
            'likuiditas' => [
                'rasio_lancar' => [
                    'nilai' => $rasioLancar,
                    'satuan' => ': 1',
                    'formula' => 'Aset Lancar / Utang Lancar',
                    'benchmark' => '≥ 1.50 : 1',
                    'status' => ($rasioLancar >= 1.5) ? 'sehat' : (($rasioLancar >= 1.0) ? 'waspada' : 'bahaya'),
                    'keterangan' => ($rasioLancar >= 1.5) 
                        ? 'Likuiditas sangat memadai untuk melunasi kewajiban jangka pendek.' 
                        : (($rasioLancar >= 1.0) ? 'Likuiditas cukup namun margin keamanan tipis.' : 'Risiko gagal bayar utang jangka pendek tinggi.')
                ],
                'rasio_cepat' => [
                    'nilai' => $rasioCepat,
                    'satuan' => ': 1',
                    'formula' => '(Aset Lancar - Persediaan) / Utang Lancar',
                    'benchmark' => '≥ 1.00 : 1',
                    'status' => ($rasioCepat >= 1.0) ? 'sehat' : (($rasioCepat >= 0.7) ? 'waspada' : 'bahaya'),
                    'keterangan' => ($rasioCepat >= 1.0) 
                        ? 'Aset sangat likuid mampu menutupi utang lancar tanpa harus menjual stok.' 
                        : 'Ketergantungan pada likuidasi persediaan untuk melunasi kewajiban.'
                ],
                'rasio_kas' => [
                    'nilai' => $rasioKas,
                    'satuan' => ': 1',
                    'formula' => 'Kas & Setara Kas / Utang Lancar',
                    'benchmark' => '≥ 0.25 : 1',
                    'status' => ($rasioKas >= 0.25) ? 'sehat' : (($rasioKas >= 0.1) ? 'waspada' : 'bahaya'),
                    'keterangan' => ($rasioKas >= 0.25) 
                        ? 'Ketersediaan kas siap pakai sangat aman untuk kebutuhan mendesak.' 
                        : 'Saldo kas tunai minim terhadap utang lancar, perlu percepat penagihan.'
                ],
                'modal_kerja' => [
                    'nilai' => $modalKerjaBersih,
                    'satuan' => 'Rp',
                    'formula' => 'Aset Lancar - Utang Lancar',
                    'benchmark' => '> 0 (Positif)',
                    'status' => ($modalKerjaBersih > 0) ? 'sehat' : 'bahaya',
                    'keterangan' => ($modalKerjaBersih > 0) 
                        ? 'Modal kerja positif, operasional harian terjamin dan aman.' 
                        : 'Defisit modal kerja, operasional harian berisiko terganggu.'
                ]
            ],
            'solvabilitas' => [
                'dar' => [
                    'nilai' => $dar,
                    'satuan' => '%',
                    'formula' => 'Total Utang / Total Aset × 100%',
                    'benchmark' => '≤ 50.00%',
                    'status' => ($dar <= 50.0) ? 'sehat' : (($dar <= 70.0) ? 'waspada' : 'bahaya'),
                    'keterangan' => ($dar <= 50.0) 
                        ? 'Porsi utang terhadap aset terkendali secara prudent.' 
                        : (($dar <= 70.0) ? 'Leverage sedang, sebagian besar aset dibiayai utang.' : 'Risiko insolvensi tinggi, aset didominasi liabilitas.')
                ],
                'der' => [
                    'nilai' => $der,
                    'satuan' => '%',
                    'formula' => 'Total Utang / Total Ekuitas × 100%',
                    'benchmark' => '≤ 100.00%',
                    'status' => ($der <= 100.0) ? 'sehat' : (($der <= 150.0) ? 'waspada' : 'bahaya'),
                    'keterangan' => ($der <= 100.0) 
                        ? 'Struktur modal sehat, modal sendiri lebih besar dari seluruh utang.' 
                        : 'Kewajiban melebihi modal sendiri, waspadai beban bunga dan jatuh tempo.'
                ],
                'equity_multiplier' => [
                    'nilai' => $equityMultiplier,
                    'satuan' => 'x',
                    'formula' => 'Total Aset / Total Ekuitas',
                    'benchmark' => '1.0 - 2.0x',
                    'status' => ($equityMultiplier <= 2.0 && $equityMultiplier >= 1.0) ? 'sehat' : 'waspada',
                    'keterangan' => 'Pengungkit finansial pemanfaatan modal terhadap total aset.'
                ]
            ],
            'profitabilitas' => [
                'gpm' => [
                    'nilai' => $gpm,
                    'satuan' => '%',
                    'formula' => 'Laba Kotor / Total Pendapatan × 100%',
                    'benchmark' => '≥ 25.00%',
                    'status' => ($gpm >= 25.0) ? 'sehat' : (($gpm >= 15.0) ? 'waspada' : 'bahaya'),
                    'keterangan' => 'Margin keuntungan langsung dari penjualan setelah dikurangi HPP.'
                ],
                'opm' => [
                    'nilai' => $opm,
                    'satuan' => '%',
                    'formula' => 'Laba Operasional / Total Pendapatan × 100%',
                    'benchmark' => '≥ 15.00%',
                    'status' => ($opm >= 15.0) ? 'sehat' : (($opm >= 5.0) ? 'waspada' : 'bahaya'),
                    'keterangan' => 'Tingkat efisiensi kegiatan operasional utama dalam mencetak laba.'
                ],
                'npm' => [
                    'nilai' => $npm,
                    'satuan' => '%',
                    'formula' => 'Laba Bersih / Total Pendapatan × 100%',
                    'benchmark' => '≥ 10.00%',
                    'status' => ($npm >= 10.0) ? 'sehat' : (($npm >= 3.0) ? 'waspada' : 'bahaya'),
                    'keterangan' => ($npm >= 10.0) 
                        ? 'Persentase keuntungan bersih sangat baik setelah seluruh beban.' 
                        : (($npm > 0) ? 'Margin laba bersih tipis, perlu efisiensi biaya.' : 'Usaha mengalami kerugian bersih pada periode ini.')
                ],
                'roa' => [
                    'nilai' => $roa,
                    'satuan' => '%',
                    'formula' => 'Laba Bersih / Total Aset × 100%',
                    'benchmark' => '≥ 5.00%',
                    'status' => ($roa >= 5.0) ? 'sehat' : (($roa >= 2.0) ? 'waspada' : 'bahaya'),
                    'keterangan' => 'Produktivitas pemanfaatan aset dalam menghasilkan laba bersih.'
                ],
                'roe' => [
                    'nilai' => $roe,
                    'satuan' => '%',
                    'formula' => 'Laba Bersih / Total Ekuitas × 100%',
                    'benchmark' => '≥ 10.00%',
                    'status' => ($roe >= 10.0) ? 'sehat' : (($roe >= 5.0) ? 'waspada' : 'bahaya'),
                    'keterangan' => 'Tingkat pengembalian hasil investasi atas modal sendiri.'
                ]
            ],
            'aktivitas' => [
                'tato' => [
                    'nilai' => $tato,
                    'satuan' => 'x / periode',
                    'formula' => 'Pendapatan / Total Aset',
                    'benchmark' => '≥ 1.00x',
                    'status' => ($tato >= 1.0) ? 'sehat' : (($tato >= 0.5) ? 'waspada' : 'bahaya'),
                    'keterangan' => 'Kecepatan perputaran aset menghasilkan pendapatan penjualan.'
                ],
                'bopo' => [
                    'nilai' => $bopo,
                    'satuan' => '%',
                    'formula' => '(HPP + Beban Operasional) / Pendapatan × 100%',
                    'benchmark' => '≤ 85.00%',
                    'status' => ($bopo <= 80.0 && $bopo > 0) ? 'sehat' : (($bopo <= 90.0) ? 'waspada' : 'bahaya'),
                    'keterangan' => ($bopo <= 80.0 && $bopo > 0) 
                        ? 'Pengendalian biaya sangat efisien terhadap perolehan omset.' 
                        : 'Beban operasional tinggi menyerap sebagian besar omset usaha.'
                ],
                'wct' => [
                    'nilai' => $workingCapitalTurnover,
                    'satuan' => 'x',
                    'formula' => 'Pendapatan / Modal Kerja Bersih',
                    'benchmark' => '2.0 - 6.0x',
                    'status' => ($workingCapitalTurnover >= 2.0 && $workingCapitalTurnover <= 10.0) ? 'sehat' : 'waspada',
                    'keterangan' => 'Efektivitas perputaran modal kerja mendanai penjualan.'
                ]
            ]
        ];
    }

    /**
     * =========================================================================
     * FEATURE D: KESEHATAN USAHA (HEALTH SCORE & DIAGNOSIS)
     * =========================================================================
     */
    public function getKesehatanUsaha($tanggal_mulai, $tanggal_selesai, $tenant_id, $id_unit = null) {
        $rasioData = $this->getRasioKeuangan($tanggal_mulai, $tanggal_selesai, $tenant_id, $id_unit);
        $ringkasan = $rasioData['ringkasan_keuangan'];

        // 1. Hitung Skor Pilar Likuiditas (Bobot 25%)
        $cr = $rasioData['likuiditas']['rasio_lancar']['nilai'];
        $cashR = $rasioData['likuiditas']['rasio_kas']['nilai'];
        $skorLikuiditas = 0;
        if ($cr >= 1.5) $skorLikuiditas += 60;
        elseif ($cr >= 1.2) $skorLikuiditas += 45;
        elseif ($cr >= 1.0) $skorLikuiditas += 30;
        elseif ($cr >= 0.7) $skorLikuiditas += 15;
        else $skorLikuiditas += 5;

        if ($cashR >= 0.3) $skorLikuiditas += 40;
        elseif ($cashR >= 0.15) $skorLikuiditas += 30;
        elseif ($cashR >= 0.05) $skorLikuiditas += 20;
        else $skorLikuiditas += 5;

        // 2. Hitung Skor Pilar Solvabilitas (Bobot 25%)
        $dar = $rasioData['solvabilitas']['dar']['nilai'];
        $der = $rasioData['solvabilitas']['der']['nilai'];
        $skorSolvabilitas = 0;
        if ($dar <= 40.0) $skorSolvabilitas += 50;
        elseif ($dar <= 60.0) $skorSolvabilitas += 40;
        elseif ($dar <= 75.0) $skorSolvabilitas += 25;
        else $skorSolvabilitas += 10;

        if ($der <= 80.0) $skorSolvabilitas += 50;
        elseif ($der <= 120.0) $skorSolvabilitas += 40;
        elseif ($der <= 180.0) $skorSolvabilitas += 25;
        else $skorSolvabilitas += 10;

        // 3. Hitung Skor Pilar Profitabilitas (Bobot 30%)
        $npm = $rasioData['profitabilitas']['npm']['nilai'];
        $roa = $rasioData['profitabilitas']['roa']['nilai'];
        $roe = $rasioData['profitabilitas']['roe']['nilai'];
        $skorProfitabilitas = 0;
        if ($npm >= 15.0) $skorProfitabilitas += 35;
        elseif ($npm >= 8.0) $skorProfitabilitas += 28;
        elseif ($npm >= 3.0) $skorProfitabilitas += 20;
        elseif ($npm > 0) $skorProfitabilitas += 10;
        else $skorProfitabilitas += 0;

        if ($roa >= 8.0) $skorProfitabilitas += 35;
        elseif ($roa >= 4.0) $skorProfitabilitas += 28;
        elseif ($roa >= 1.0) $skorProfitabilitas += 20;
        elseif ($roa > 0) $skorProfitabilitas += 10;
        else $skorProfitabilitas += 0;

        if ($roe >= 12.0) $skorProfitabilitas += 30;
        elseif ($roe >= 6.0) $skorProfitabilitas += 24;
        elseif ($roe >= 2.0) $skorProfitabilitas += 15;
        elseif ($roe > 0) $skorProfitabilitas += 8;
        else $skorProfitabilitas += 0;

        // 4. Hitung Skor Pilar Aktivitas & Efisiensi (Bobot 20%)
        $bopo = $rasioData['aktivitas']['bopo']['nilai'];
        $tato = $rasioData['aktivitas']['tato']['nilai'];
        $skorAktivitas = 0;
        if ($bopo <= 75.0 && $bopo > 0) $skorAktivitas += 60;
        elseif ($bopo <= 85.0) $skorAktivitas += 45;
        elseif ($bopo <= 95.0) $skorAktivitas += 30;
        elseif ($bopo > 95.0) $skorAktivitas += 10;
        else $skorAktivitas += 30;

        if ($tato >= 1.0) $skorAktivitas += 40;
        elseif ($tato >= 0.6) $skorAktivitas += 30;
        elseif ($tato >= 0.3) $skorAktivitas += 20;
        else $skorAktivitas += 10;

        // Skor Komposit Total (0 - 100)
        $totalSkor = ($skorLikuiditas * 0.25) + 
                     ($skorSolvabilitas * 0.25) + 
                     ($skorProfitabilitas * 0.30) + 
                     ($skorAktivitas * 0.20);
        $totalSkor = round($totalSkor, 1);

        // Kategori Predikat Kesehatan Usaha
        if ($totalSkor >= 85) {
            $predikat = 'SEHAT SEKALI';
            $kategori = 'AAA';
            $badgeColor = 'success';
            $icon = 'bi-shield-check';
            $kesimpulan = 'Kinerja keuangan usaha berada pada level prima. Likuiditas berlimpah, permodalan kokoh, dan profitabilitas sangat positif.';
        } elseif ($totalSkor >= 70) {
            $predikat = 'SEHAT';
            $kategori = 'AA';
            $badgeColor = 'success';
            $icon = 'bi-check-circle-fill';
            $kesimpulan = 'Kondisi keuangan usaha sehat dan stabil. Operasional harian berjalan lancar dengan risiko keuangan terkendali baik.';
        } elseif ($totalSkor >= 55) {
            $predikat = 'CUKUP SEHAT';
            $kategori = 'A';
            $badgeColor = 'info';
            $icon = 'bi-info-circle-fill';
            $kesimpulan = 'Kondisi keuangan wajar dan beroperasi normal. Namun memerlukan peningkatan margin laba dan pengawasan pada pengeluaran operasional.';
        } elseif ($totalSkor >= 40) {
            $predikat = 'KURANG SEHAT';
            $kategori = 'B';
            $badgeColor = 'warning';
            $icon = 'bi-exclamation-triangle-fill';
            $kesimpulan = 'Kondisi keuangan berada pada zona waspada. Likuiditas tertekan atau margin keuntungan tergerus beban operasional yang tinggi.';
        } else {
            $predikat = 'TIDAK SEHAT';
            $kategori = 'C';
            $badgeColor = 'danger';
            $icon = 'bi-slash-circle-fill';
            $kesimpulan = 'Kondisi keuangan dalam zona kritis. Usaha menghadapi defisit likuiditas atau beban liabilitas yang melampaui kemampuan bayar.';
        }

        // 5. Model Altman Z-Score (Emerging Market Z'' Model untuk UMKM & BUMDes)
        // Z'' = 6.56*X1 + 3.26*X2 + 6.72*X3 + 1.05*X4
        // X1 = Working Capital / Total Assets
        // X2 = Retained Earnings (Laba Bersih) / Total Assets
        // X3 = EBIT (Laba Operasional) / Total Assets
        // X4 = Book Value of Equity / Total Liabilities
        $totalAset = $ringkasan['total_aset'];
        $totalLiabilitas = $ringkasan['total_liabilitas'];
        $totalEkuitas = $ringkasan['total_ekuitas'];
        $modalKerja = $ringkasan['modal_kerja_bersih'];
        $labaBersih = $ringkasan['laba_bersih'];
        $labaOperasional = $ringkasan['laba_operasional'];

        $x1 = ($totalAset > 0) ? ($modalKerja / $totalAset) : 0;
        $x2 = ($totalAset > 0) ? ($labaBersih / $totalAset) : 0;
        $x3 = ($totalAset > 0) ? ($labaOperasional / $totalAset) : 0;
        $x4 = ($totalLiabilitas > 0) ? ($totalEkuitas / $totalLiabilitas) : ($totalEkuitas > 0 ? 5.0 : 0);

        $zScore = round((6.56 * $x1) + (3.26 * $x2) + (6.72 * $x3) + (1.05 * $x4), 2);
        if ($zScore > 2.60) {
            $zZone = 'Zona Aman (Safe Zone)';
            $zColor = 'success';
            $zDesc = 'Risiko kebangkrutan sangat rendah. Ketahanan modal kerja dan solvabilitas sangat kuat.';
        } elseif ($zScore >= 1.10) {
            $zZone = 'Zona Abu-Abu (Grey Zone)';
            $zColor = 'warning';
            $zDesc = 'Risiko sedang. Perlu mewaspadai jatuh tempo utang dan memantapkan margin keuntungan.';
        } else {
            $zZone = 'Zona Distress (Kritis)';
            $zColor = 'danger';
            $zDesc = 'Risiko insolvensi tinggi. Perlu restrukturisasi biaya dan pemulihan arus kas mendesak.';
        }

        // 6. Analisis SWOT Finansial & Rekomendasi Manajerial
        $kekuatan = [];
        $kelemahan = [];
        $rekomendasi = [];

        if ($cr >= 1.5) $kekuatan[] = 'Rasio Lancar (' . number_format($cr, 2) . 'x) sangat kokoh menjamin kewajiban jangka pendek.';
        else $kelemahan[] = 'Rasio Lancar (' . number_format($cr, 2) . 'x) di bawah standar aman 1.5x.';

        if ($dar <= 50.0) $kekuatan[] = 'Struktur modal prudent, ketergantungan pada utang rendah (' . number_format($dar, 1) . '%).';
        else $kelemahan[] = 'Porsi utang terhadap total aset tinggi (' . number_format($dar, 1) . '%), rentan jika pendapatan menurun.';

        if ($npm >= 10.0) $kekuatan[] = 'Margin laba bersih sehat (' . number_format($npm, 1) . '%).';
        elseif ($npm > 0) $kelemahan[] = 'Margin laba bersih tipis (' . number_format($npm, 1) . '%), rawan tergerus inflasi/biaya tak terduga.';
        else $kelemahan[] = 'Usaha membukukan kerugian operasional pada periode ini.';

        if ($bopo <= 80.0 && $bopo > 0) $kekuatan[] = 'Efisiensi beban operasional sangat baik (BOPO: ' . number_format($bopo, 1) . '%).';
        elseif ($bopo > 85.0) $kelemahan[] = 'Efisiensi biaya rendah (BOPO: ' . number_format($bopo, 1) . '%), menyerap mayoritas pendapatan.';

        // Rekomendasi Aksi Nyata
        if ($cr < 1.5 || $cashR < 0.2) {
            $rekomendasi[] = [
                'prioritas' => 'Tinggi',
                'bidang' => 'Manajemen Likuiditas',
                'saran' => 'Percepat penagihan piutang usaha yang jatuh tempo dan tunda belanja barang modal non-esensial untuk mempertebal cadangan kas tunai.'
            ];
        }
        if ($bopo > 85.0 || $npm < 5.0) {
            $rekomendasi[] = [
                'prioritas' => 'Tinggi',
                'bidang' => 'Efisiensi Biaya (Cost Control)',
                'saran' => 'Lakukan audit berkala pada pos beban operasional (overhead, utilitas, logistik) serta renegosiasi harga beli ke pemasok untuk menekan HPP.'
            ];
        }
        if ($dar > 60.0 || $der > 120.0) {
            $rekomendasi[] = [
                'prioritas' => 'Sedang',
                'bidang' => 'Struktur Modal & Liabilitas',
                'saran' => 'Prioritaskan pelunasan utang berbunga tinggi dan alokasikan laba ditahan untuk memperkuat ekuitas sebelum mengambil pembiayaan baru.'
            ];
        }
        if ($tato < 0.8) {
            $rekomendasi[] = [
                'prioritas' => 'Sedang',
                'bidang' => 'Optimalisasi Aset & Penjualan',
                'saran' => 'Tingkatkan utilitas kapasitas aset produksi dan inventarisasi aset yang tidak produktif untuk dilepas atau dikerjasamakan.'
            ];
        }
        if (empty($rekomendasi)) {
            $rekomendasi[] = [
                'prioritas' => 'Pemeliharaan',
                'bidang' => 'Ekspansi Terukur',
                'saran' => 'Fundamental keuangan sangat baik. Perusahaan siap mempertimbangkan ekspansi unit usaha baru atau investasi penambahan kapasitas.'
            ];
        }

        return [
            'skor_total' => $totalSkor,
            'predikat' => $predikat,
            'kategori' => $kategori,
            'badge_color' => $badgeColor,
            'icon' => $icon,
            'kesimpulan' => $kesimpulan,
            'pilar' => [
                'likuiditas' => ['nama' => 'Likuiditas', 'skor' => $skorLikuiditas, 'bobot' => 25],
                'solvabilitas' => ['nama' => 'Solvabilitas', 'skor' => $skorSolvabilitas, 'bobot' => 25],
                'profitabilitas' => ['nama' => 'Profitabilitas', 'skor' => $skorProfitabilitas, 'bobot' => 30],
                'aktivitas' => ['nama' => 'Efisiensi & Aktivitas', 'skor' => $skorAktivitas, 'bobot' => 20]
            ],
            'altman_z' => [
                'score' => $zScore,
                'zone' => $zZone,
                'color' => $zColor,
                'description' => $zDesc,
                'components' => ['x1' => $x1, 'x2' => $x2, 'x3' => $x3, 'x4' => $x4]
            ],
            'swot' => [
                'kekuatan' => $kekuatan,
                'kelemahan' => $kelemahan
            ],
            'rekomendasi' => $rekomendasi,
            'rasio_data' => $rasioData
        ];
    }

    /**
     * =========================================================================
     * FEATURE A: ANALISIS JURNAL YANG ANOMALI
     * =========================================================================
     */
    public function getJurnalAnomali($tenant_id, $tanggal_mulai = null, $tanggal_selesai = null) {
        $anomaliList = [];

        // 1. JURNAL TIDAK SEIMBANG (DEBIT != KREDIT)
        $dateFilter = "";
        if ($tanggal_mulai && $tanggal_selesai) {
            $dateFilter = " AND ju.tanggal BETWEEN :tgl_mulai AND :tgl_selesai ";
        }
        $sqlUnbalanced = "
            SELECT 
                ju.id_jurnal, ju.no_transaksi, ju.tanggal, ju.deskripsi, ju.sumber_jurnal,
                SUM(jd.debit) as total_debit, SUM(jd.kredit) as total_kredit,
                ABS(SUM(jd.debit) - SUM(jd.kredit)) as selisih
            FROM jurnal_umum ju
            JOIN jurnal_detail jd ON ju.id_jurnal = jd.id_jurnal
            WHERE ju.tenant_id = :tenant_id $dateFilter
            GROUP BY ju.id_jurnal
            HAVING selisih > 0.01
            ORDER BY ju.tanggal DESC";
        
        $this->db->query($sqlUnbalanced);
        $this->db->bind('tenant_id', $tenant_id);
        if ($tanggal_mulai && $tanggal_selesai) {
            $this->db->bind('tgl_mulai', $tanggal_mulai);
            $this->db->bind('tgl_selesai', $tanggal_selesai);
        }
        $unbalanced = $this->db->resultSet();
        foreach ($unbalanced as $row) {
            $anomaliList[] = [
                'id_jurnal' => $row['id_jurnal'],
                'no_transaksi' => $row['no_transaksi'],
                'tanggal' => $row['tanggal'],
                'deskripsi' => $row['deskripsi'],
                'sumber' => $row['sumber_jurnal'],
                'jenis_anomali' => 'Jurnal Tidak Seimbang (Unbalanced)',
                'tingkat_keparahan' => 'Kritis',
                'badge' => 'danger',
                'detail' => 'Debit: Rp ' . number_format($row['total_debit'], 2) . ' vs Kredit: Rp ' . number_format($row['total_kredit'], 2) . ' (Selisih: Rp ' . number_format($row['selisih'], 2) . ')',
                'aksi' => 'edit_jurnal'
            ];
        }

        // 2. HEADER JURNAL KOSONG (TANPA DETAIL BARIS TRANSAKSI)
        $sqlEmptyHeader = "
            SELECT ju.id_jurnal, ju.no_transaksi, ju.tanggal, ju.deskripsi, ju.sumber_jurnal
            FROM jurnal_umum ju
            LEFT JOIN jurnal_detail jd ON ju.id_jurnal = jd.id_jurnal
            WHERE ju.tenant_id = :tenant_id $dateFilter AND jd.id_detail IS NULL
            ORDER BY ju.tanggal DESC";
        $this->db->query($sqlEmptyHeader);
        $this->db->bind('tenant_id', $tenant_id);
        if ($tanggal_mulai && $tanggal_selesai) {
            $this->db->bind('tgl_mulai', $tanggal_mulai);
            $this->db->bind('tgl_selesai', $tanggal_selesai);
        }
        $emptyHeaders = $this->db->resultSet();
        foreach ($emptyHeaders as $row) {
            $anomaliList[] = [
                'id_jurnal' => $row['id_jurnal'],
                'no_transaksi' => $row['no_transaksi'],
                'tanggal' => $row['tanggal'],
                'deskripsi' => $row['deskripsi'],
                'sumber' => $row['sumber_jurnal'],
                'jenis_anomali' => 'Jurnal Kosong (Tanpa Baris Akun)',
                'tingkat_keparahan' => 'Tinggi',
                'badge' => 'warning',
                'detail' => 'Header transaksi tersimpan di jurnal_umum namun tidak memiliki satu pun baris jurnal_detail.',
                'aksi' => 'hapus_header_kosong'
            ];
        }

        // 3. ORPHAN JURNAL DETAIL (BARIS DETAIL TANPA HEADER INDUK)
        $sqlOrphanDetails = "
            SELECT jd.id_jurnal, COUNT(*) as jml_baris, SUM(jd.debit) as total_debit, SUM(jd.kredit) as total_kredit
            FROM jurnal_detail jd
            LEFT JOIN jurnal_umum ju ON jd.id_jurnal = ju.id_jurnal
            WHERE ju.id_jurnal IS NULL
            GROUP BY jd.id_jurnal";
        $this->db->query($sqlOrphanDetails);
        $orphanDetails = $this->db->resultSet();
        foreach ($orphanDetails as $row) {
            $anomaliList[] = [
                'id_jurnal' => $row['id_jurnal'],
                'no_transaksi' => 'Orphan ID #' . $row['id_jurnal'],
                'tanggal' => '-',
                'deskripsi' => 'Data detail tersisa dari penghapusan jurnal sebelumnya.',
                'sumber' => 'Database Sync',
                'jenis_anomali' => 'Detail Jurnal Yatim (Orphan Records)',
                'tingkat_keparahan' => 'Sedang',
                'badge' => 'warning',
                'detail' => $row['jml_baris'] . ' baris detail (Debit Rp ' . number_format($row['total_debit'], 0) . ') tanpa header jurnal_umum.',
                'aksi' => 'bersihkan_orphan_detail'
            ];
        }

        // 4. ENTRI DENGAN NILAI NOL ATAU NEGATIF
        $sqlZeroOrNegative = "
            SELECT ju.id_jurnal, ju.no_transaksi, ju.tanggal, ju.deskripsi, ju.sumber_jurnal,
                   jd.kode_akun, jd.debit, jd.kredit
            FROM jurnal_detail jd
            JOIN jurnal_umum ju ON jd.id_jurnal = ju.id_jurnal
            WHERE ju.tenant_id = :tenant_id $dateFilter 
              AND ((jd.debit = 0 AND jd.kredit = 0) OR jd.debit < 0 OR jd.kredit < 0)
            ORDER BY ju.tanggal DESC LIMIT 50";
        $this->db->query($sqlZeroOrNegative);
        $this->db->bind('tenant_id', $tenant_id);
        if ($tanggal_mulai && $tanggal_selesai) {
            $this->db->bind('tgl_mulai', $tanggal_mulai);
            $this->db->bind('tgl_selesai', $tanggal_selesai);
        }
        $zeroNeg = $this->db->resultSet();
        foreach ($zeroNeg as $row) {
            $anomaliList[] = [
                'id_jurnal' => $row['id_jurnal'],
                'no_transaksi' => $row['no_transaksi'],
                'tanggal' => $row['tanggal'],
                'deskripsi' => $row['deskripsi'],
                'sumber' => $row['sumber_jurnal'],
                'jenis_anomali' => 'Baris Bernilai Nol / Negatif',
                'tingkat_keparahan' => 'Sedang',
                'badge' => 'secondary',
                'detail' => 'Akun ' . $row['kode_akun'] . ' memiliki debit: ' . $row['debit'] . ', kredit: ' . $row['kredit'],
                'aksi' => 'edit_jurnal'
            ];
        }

        // 5. KODE AKUN TIDAK TERDAFTAR DI COA
        $sqlInvalidAccount = "
            SELECT ju.id_jurnal, ju.no_transaksi, ju.tanggal, ju.deskripsi, ju.sumber_jurnal, jd.kode_akun
            FROM jurnal_detail jd
            JOIN jurnal_umum ju ON jd.id_jurnal = ju.id_jurnal
            LEFT JOIN akun a ON jd.kode_akun = a.kode_akun AND a.tenant_id = ju.tenant_id
            WHERE ju.tenant_id = :tenant_id $dateFilter AND a.kode_akun IS NULL
            GROUP BY ju.id_jurnal, jd.kode_akun";
        $this->db->query($sqlInvalidAccount);
        $this->db->bind('tenant_id', $tenant_id);
        if ($tanggal_mulai && $tanggal_selesai) {
            $this->db->bind('tgl_mulai', $tanggal_mulai);
            $this->db->bind('tgl_selesai', $tanggal_selesai);
        }
        $invalidAccs = $this->db->resultSet();
        foreach ($invalidAccs as $row) {
            $anomaliList[] = [
                'id_jurnal' => $row['id_jurnal'],
                'no_transaksi' => $row['no_transaksi'],
                'tanggal' => $row['tanggal'],
                'deskripsi' => $row['deskripsi'],
                'sumber' => $row['sumber_jurnal'],
                'jenis_anomali' => 'Akun Tidak Ditemukan di COA',
                'tingkat_keparahan' => 'Tinggi',
                'badge' => 'danger',
                'detail' => 'Kode akun ' . $row['kode_akun'] . ' tidak ditemukan dalam Bagan Akun (COA) tenant.',
                'aksi' => 'edit_jurnal'
            ];
        }

        // 6. TANGGAL TRANSAKSI EKSTREM (JAUH DI MASA DEPAN ATAU DI BAWAH TAHUN 2010)
        $sqlExtremeDates = "
            SELECT id_jurnal, no_transaksi, tanggal, deskripsi, sumber_jurnal
            FROM jurnal_umum
            WHERE tenant_id = :tenant_id 
              AND (tanggal > DATE_ADD(CURRENT_DATE, INTERVAL 60 DAY) OR tanggal < '2015-01-01')
            ORDER BY tanggal DESC";
        $this->db->query($sqlExtremeDates);
        $this->db->bind('tenant_id', $tenant_id);
        $extremeDates = $this->db->resultSet();
        foreach ($extremeDates as $row) {
            $anomaliList[] = [
                'id_jurnal' => $row['id_jurnal'],
                'no_transaksi' => $row['no_transaksi'],
                'tanggal' => $row['tanggal'],
                'deskripsi' => $row['deskripsi'],
                'sumber' => $row['sumber_jurnal'],
                'jenis_anomali' => 'Tanggal Di Luar Batas Wajar',
                'tingkat_keparahan' => 'Perhatian',
                'badge' => 'info',
                'detail' => 'Tanggal transaksi terinput ' . $row['tanggal'] . ' (jauh dari periode kalender normal).',
                'aksi' => 'edit_jurnal'
            ];
        }

        // 7. DUPLIKASI TRANSAKSI (TANGGAL, TOTAL NOMINAL, DAN DESKRIPSI IDENTIK)
        $sqlDuplicates = "
            SELECT 
                ju.tanggal, ju.deskripsi, SUM(jd.debit) as total_nominal, COUNT(DISTINCT ju.id_jurnal) as jml_duplikat,
                GROUP_CONCAT(ju.no_transaksi SEPARATOR ', ') as daftar_no_transaksi,
                GROUP_CONCAT(ju.id_jurnal SEPARATOR ',') as ids
            FROM jurnal_umum ju
            JOIN jurnal_detail jd ON ju.id_jurnal = jd.id_jurnal
            WHERE ju.tenant_id = :tenant_id $dateFilter
            GROUP BY ju.tanggal, ju.deskripsi, jd.debit
            HAVING jml_duplikat > 1 AND total_nominal > 0
            LIMIT 20";
        $this->db->query($sqlDuplicates);
        $this->db->bind('tenant_id', $tenant_id);
        if ($tanggal_mulai && $tanggal_selesai) {
            $this->db->bind('tgl_mulai', $tanggal_mulai);
            $this->db->bind('tgl_selesai', $tanggal_selesai);
        }
        $duplicates = $this->db->resultSet();
        foreach ($duplicates as $row) {
            $idArray = explode(',', $row['ids']);
            $anomaliList[] = [
                'id_jurnal' => $idArray[0],
                'no_transaksi' => $row['daftar_no_transaksi'],
                'tanggal' => $row['tanggal'],
                'deskripsi' => $row['deskripsi'],
                'sumber' => 'Deteksi Duplikasi',
                'jenis_anomali' => 'Indikasi Jurnal Duplikat (' . $row['jml_duplikat'] . 'x)',
                'tingkat_keparahan' => 'Sedang',
                'badge' => 'warning',
                'detail' => 'Ditemukan ' . $row['jml_duplikat'] . ' transaksi pada tanggal yang sama dengan nominal Rp ' . number_format($row['total_nominal'], 0) . ' dan deskripsi yang sama.',
                'aksi' => 'periksa_duplikat'
            ];
        }

        // Summary counts
        $totalAnomali = count($anomaliList);
        $kritisCount = count(array_filter($anomaliList, fn($a) => $a['tingkat_keparahan'] === 'Kritis'));
        $tinggiCount = count(array_filter($anomaliList, fn($a) => $a['tingkat_keparahan'] === 'Tinggi'));
        $sedangCount = count(array_filter($anomaliList, fn($a) => in_array($a['tingkat_keparahan'], ['Sedang', 'Perhatian'])));

        return [
            'total' => $totalAnomali,
            'kritis' => $kritisCount,
            'tinggi' => $tinggiCount,
            'sedang' => $sedangCount,
            'daftar' => $anomaliList
        ];
    }

    /**
     * =========================================================================
     * FEATURE B: TRANSAKSI TANPA JURNAL (UNJOURNALED TRANSACTIONS)
     * =========================================================================
     */
    public function getTransaksiTanpaJurnal($tenant_id, $filter_tipe = 'semua') {
        $transaksiList = [];

        // 1. PENJUALAN TANPA JURNAL
        if ($filter_tipe === 'semua' || $filter_tipe === 'penjualan') {
            $sqlPenjualan = "
                SELECT p.id_penjualan, p.no_faktur, p.tanggal_faktur, p.total, p.metode_pembayaran, p.status_pembayaran,
                       p.keterangan, pel.nama_pelanggan, p.id_jurnal
                FROM penjualan p
                LEFT JOIN pelanggan pel ON p.id_pelanggan = pel.id_pelanggan
                WHERE p.tenant_id = :tenant_id 
                  AND (p.id_jurnal IS NULL OR p.id_jurnal = 0 OR p.id_jurnal NOT IN (SELECT id_jurnal FROM jurnal_umum WHERE tenant_id = :tenant_id))
                ORDER BY p.tanggal_faktur DESC";
            $this->db->query($sqlPenjualan);
            $this->db->bind('tenant_id', $tenant_id);
            $sales = $this->db->resultSet();
            foreach ($sales as $row) {
                $transaksiList[] = [
                    'tipe' => 'Penjualan',
                    'id_transaksi' => $row['id_penjualan'],
                    'no_bukti' => $row['no_faktur'],
                    'tanggal' => $row['tanggal_faktur'],
                    'pihak' => $row['nama_pelanggan'] ?? 'Umum',
                    'nominal' => (float)$row['total'],
                    'status' => $row['status_pembayaran'],
                    'keterangan' => $row['keterangan'] ?? 'Faktur Penjualan (' . $row['metode_pembayaran'] . ')',
                    'bisa_sinkron' => true,
                    'link_detail' => BASEURL . '/penjualan'
                ];
            }
        }

        // 2. PEMBELIAN TANPA JURNAL
        if ($filter_tipe === 'semua' || $filter_tipe === 'pembelian') {
            $sqlPembelian = "
                SELECT pb.id_pembelian, pb.no_faktur_pembelian as no_faktur, pb.tanggal_faktur, pb.total, pb.metode_pembayaran, pb.status_pembayaran,
                       pb.keterangan, pem.nama_pemasok, pb.id_jurnal
                FROM pembelian pb
                LEFT JOIN pemasok pem ON pb.id_pemasok = pem.id_pemasok
                WHERE pb.tenant_id = :tenant_id 
                  AND (pb.id_jurnal IS NULL OR pb.id_jurnal = 0 OR pb.id_jurnal NOT IN (SELECT id_jurnal FROM jurnal_umum WHERE tenant_id = :tenant_id))
                ORDER BY pb.tanggal_faktur DESC";
            $this->db->query($sqlPembelian);
            $this->db->bind('tenant_id', $tenant_id);
            $purchases = $this->db->resultSet();
            foreach ($purchases as $row) {
                $transaksiList[] = [
                    'tipe' => 'Pembelian',
                    'id_transaksi' => $row['id_pembelian'],
                    'no_bukti' => $row['no_faktur'],
                    'tanggal' => $row['tanggal_faktur'],
                    'pihak' => $row['nama_pemasok'] ?? 'Pemasok',
                    'nominal' => (float)$row['total'],
                    'status' => $row['status_pembayaran'],
                    'keterangan' => $row['keterangan'] ?? 'Faktur Pembelian (' . $row['metode_pembayaran'] . ')',
                    'bisa_sinkron' => true,
                    'link_detail' => BASEURL . '/pembelian'
                ];
            }
        }

        // 3. KAS & BANK TANPA JURNAL
        if ($filter_tipe === 'semua' || $filter_tipe === 'kas') {
            $sqlKas = "
                SELECT kt.id_transaksi, kt.no_bukti, kt.tanggal, kt.tipe_transaksi, kt.jumlah,
                       kt.akun_kas_bank, kt.akun_lawan, kt.deskripsi, kt.id_jurnal
                FROM kas_transaksi kt
                WHERE kt.tenant_id = :tenant_id 
                  AND (kt.id_jurnal IS NULL OR kt.id_jurnal = 0 OR kt.id_jurnal NOT IN (SELECT id_jurnal FROM jurnal_umum WHERE tenant_id = :tenant_id))
                ORDER BY kt.tanggal DESC";
            $this->db->query($sqlKas);
            $this->db->bind('tenant_id', $tenant_id);
            $cashes = $this->db->resultSet();
            foreach ($cashes as $row) {
                $transaksiList[] = [
                    'tipe' => 'Kas & Bank (' . $row['tipe_transaksi'] . ')',
                    'id_transaksi' => $row['id_transaksi'],
                    'no_bukti' => $row['no_bukti'],
                    'tanggal' => $row['tanggal'],
                    'pihak' => $row['akun_kas_bank'] . ' ➔ ' . $row['akun_lawan'],
                    'nominal' => (float)$row['jumlah'],
                    'status' => 'Selesai',
                    'keterangan' => $row['deskripsi'],
                    'bisa_sinkron' => true,
                    'link_detail' => BASEURL . '/kas'
                ];
            }
        }

        // 4. PENERIMAAN PIUTANG TANPA JURNAL
        if ($filter_tipe === 'semua' || $filter_tipe === 'penerimaan') {
            $sqlPenerimaan = "
                SELECT pp.id_penerimaan, pp.no_bukti, pp.tanggal, pp.total_diterima, pp.akun_kas_bank,
                       pp.keterangan, pel.nama_pelanggan, pp.id_jurnal
                FROM penerimaan_pelanggan pp
                LEFT JOIN pelanggan pel ON pp.id_pelanggan = pel.id_pelanggan
                WHERE pp.tenant_id = :tenant_id 
                  AND (pp.id_jurnal IS NULL OR pp.id_jurnal = 0 OR pp.id_jurnal NOT IN (SELECT id_jurnal FROM jurnal_umum WHERE tenant_id = :tenant_id))
                ORDER BY pp.tanggal DESC";
            $this->db->query($sqlPenerimaan);
            $this->db->bind('tenant_id', $tenant_id);
            $receipts = $this->db->resultSet();
            foreach ($receipts as $row) {
                $transaksiList[] = [
                    'tipe' => 'Penerimaan Piutang',
                    'id_transaksi' => $row['id_penerimaan'],
                    'no_bukti' => $row['no_bukti'],
                    'tanggal' => $row['tanggal'],
                    'pihak' => $row['nama_pelanggan'] ?? 'Pelanggan',
                    'nominal' => (float)$row['total_diterima'],
                    'status' => 'Lunas',
                    'keterangan' => $row['keterangan'] ?? 'Penerimaan pembayaran piutang via ' . $row['akun_kas_bank'],
                    'bisa_sinkron' => true,
                    'link_detail' => BASEURL . '/penerimaan'
                ];
            }
        }

        // 5. PEMBAYARAN PEMASOK TANPA JURNAL
        if ($filter_tipe === 'semua' || $filter_tipe === 'pembayaran') {
            $sqlPembayaran = "
                SELECT pm.id_pembayaran, pm.no_bukti, pm.tanggal, pm.total_dibayar, pm.akun_kas_bank,
                       pm.keterangan, pms.nama_pemasok, pm.id_jurnal
                FROM pembayaran_pemasok pm
                LEFT JOIN pemasok pms ON pm.id_pemasok = pms.id_pemasok
                WHERE pm.tenant_id = :tenant_id 
                  AND (pm.id_jurnal IS NULL OR pm.id_jurnal = 0 OR pm.id_jurnal NOT IN (SELECT id_jurnal FROM jurnal_umum WHERE tenant_id = :tenant_id))
                ORDER BY pm.tanggal DESC";
            $this->db->query($sqlPembayaran);
            $this->db->bind('tenant_id', $tenant_id);
            $payments = $this->db->resultSet();
            foreach ($payments as $row) {
                $transaksiList[] = [
                    'tipe' => 'Pembayaran Utang',
                    'id_transaksi' => $row['id_pembayaran'],
                    'no_bukti' => $row['no_bukti'],
                    'tanggal' => $row['tanggal'],
                    'pihak' => $row['nama_pemasok'] ?? 'Pemasok',
                    'nominal' => (float)$row['total_dibayar'],
                    'status' => 'Lunas',
                    'keterangan' => $row['keterangan'] ?? 'Pembayaran faktur utang via ' . $row['akun_kas_bank'],
                    'bisa_sinkron' => true,
                    'link_detail' => BASEURL . '/pembayaran'
                ];
            }
        }

        // 6. TRANSAKSI SIMPAN PINJAM (JIKA TABEL TERSEDIA)
        if ($filter_tipe === 'semua' || $filter_tipe === 'simpanpinjam') {
            try {
                $sqlSp = "
                    SELECT st.id_transaksi, st.no_transaksi, st.tanggal, st.jenis_transaksi, st.nominal,
                           st.keterangan, n.nama_lengkap, st.id_jurnal
                    FROM sp_transaksi_simpanan st
                    JOIN sp_rekening_simpanan r ON st.id_rekening = r.id_rekening
                    JOIN sp_nasabah n ON r.id_nasabah = n.id_nasabah
                    WHERE st.tenant_id = :tenant_id
                      AND (st.id_jurnal IS NULL OR st.id_jurnal = 0 OR st.id_jurnal NOT IN (SELECT id_jurnal FROM jurnal_umum WHERE tenant_id = :tenant_id))
                    ORDER BY st.tanggal DESC LIMIT 20";
                $this->db->query($sqlSp);
                $this->db->bind('tenant_id', $tenant_id);
                $spTrx = $this->db->resultSet();
                foreach ($spTrx as $row) {
                    $transaksiList[] = [
                        'tipe' => 'Simpan Pinjam',
                        'id_transaksi' => $row['id_transaksi'],
                        'no_bukti' => $row['no_transaksi'],
                        'tanggal' => $row['tanggal'],
                        'pihak' => $row['nama_lengkap'],
                        'nominal' => (float)$row['nominal'],
                        'status' => $row['jenis_transaksi'],
                        'keterangan' => $row['keterangan'],
                        'bisa_sinkron' => false,
                        'link_detail' => BASEURL . '/simpanpinjam/simpanan'
                    ];
                }
            } catch (Exception $e) {
                // Abaikan jika tabel SP tidak aktif
            }
        }

        // 7. PENYUSUTAN ASET TANPA JURNAL
        if ($filter_tipe === 'semua' || $filter_tipe === 'penyusutan') {
            try {
                $sqlPenyusutan = "
                    SELECT pa.id, at.nama_aset, pa.bulan, pa.tahun, pa.jumlah, pa.tanggal_proses, pa.id_jurnal
                    FROM penyusutan_aset pa
                    JOIN aset_tetap at ON pa.id_aset = at.id_aset
                    WHERE pa.tenant_id = :tenant_id
                      AND (pa.id_jurnal IS NULL OR pa.id_jurnal = 0 OR pa.id_jurnal NOT IN (SELECT id_jurnal FROM jurnal_umum WHERE tenant_id = :tenant_id))
                    ORDER BY pa.tanggal_proses DESC";
                $this->db->query($sqlPenyusutan);
                $this->db->bind('tenant_id', $tenant_id);
                $depr = $this->db->resultSet();
                foreach ($depr as $row) {
                    $transaksiList[] = [
                        'tipe' => 'Penyusutan Aset',
                        'id_transaksi' => $row['id'],
                        'no_bukti' => 'DEP/' . $row['tahun'] . str_pad($row['bulan'], 2, '0', STR_PAD_LEFT) . '/' . $row['id'],
                        'tanggal' => $row['tanggal_proses'],
                        'pihak' => $row['nama_aset'],
                        'nominal' => (float)$row['jumlah'],
                        'status' => 'Proses',
                        'keterangan' => 'Penyusutan Aset Tetap Periode ' . $row['bulan'] . '/' . $row['tahun'],
                        'bisa_sinkron' => false,
                        'link_detail' => BASEURL . '/aset'
                    ];
                }
            } catch (Exception $e) {}
        }

        return [
            'total' => count($transaksiList),
            'daftar' => $transaksiList
        ];
    }

    /**
     * Otomatis membuat entri jurnal untuk transaksi yang belum memiliki jurnal
     */
    public function sinkronkanJurnalTransaksi($tipe, $id_transaksi, $tenant_id) {
        $jurnalModel = new Jurnal_model($this->db);

        $tipeLower = strtolower($tipe);

        if (strpos($tipeLower, 'kas') !== false) {
            // SINKRONISASI KAS & BANK
            $this->db->query("SELECT * FROM kas_transaksi WHERE id_transaksi = :id AND tenant_id = :tenant_id");
            $this->db->bind('id', $id_transaksi);
            $this->db->bind('tenant_id', $tenant_id);
            $trx = $this->db->single();
            if (!$trx) throw new Exception("Data transaksi kas tidak ditemukan.");

            $nominal = (float)$trx['jumlah'];
            $noTransaksi = $jurnalModel->generateNoTransaksi($tenant_id);

            $details = [];
            if ($trx['tipe_transaksi'] === 'Masuk') {
                $details[] = ['kode_akun' => $trx['akun_kas_bank'], 'debit' => $nominal, 'kredit' => 0];
                $details[] = ['kode_akun' => $trx['akun_lawan'], 'debit' => 0, 'kredit' => $nominal];
            } else {
                $details[] = ['kode_akun' => $trx['akun_lawan'], 'debit' => $nominal, 'kredit' => 0];
                $details[] = ['kode_akun' => $trx['akun_kas_bank'], 'debit' => 0, 'kredit' => $nominal];
            }

            $jurnalData = [
                'no_transaksi' => $noTransaksi,
                'tanggal' => $trx['tanggal'],
                'deskripsi' => 'Sinkronisasi Otomatis: Kas ' . $trx['tipe_transaksi'] . ' - ' . $trx['no_bukti'] . ' - ' . $trx['deskripsi'],
                'sumber_jurnal' => 'Kas & Bank',
                'details' => $details
            ];

            $id_jurnal = $jurnalModel->simpanJurnal($jurnalData, $tenant_id);
            $this->db->query("UPDATE kas_transaksi SET id_jurnal = :id_jurnal WHERE id_transaksi = :id AND tenant_id = :tenant_id");
            $this->db->bind('id_jurnal', $id_jurnal);
            $this->db->bind('id', $id_transaksi);
            $this->db->bind('tenant_id', $tenant_id);
            $this->db->execute();

            return $id_jurnal;

        } elseif (strpos($tipeLower, 'penerimaan') !== false) {
            // SINKRONISASI PENERIMAAN PIUTANG
            $this->db->query("SELECT * FROM penerimaan_pelanggan WHERE id_penerimaan = :id AND tenant_id = :tenant_id");
            $this->db->bind('id', $id_transaksi);
            $this->db->bind('tenant_id', $tenant_id);
            $trx = $this->db->single();
            if (!$trx) throw new Exception("Data penerimaan pelanggan tidak ditemukan.");

            // Cari akun piutang default
            $this->db->query("SELECT kode_akun FROM akun WHERE tenant_id = :tid AND (tipe_akun = 'Piutang' OR kode_akun = '1-10100') LIMIT 1");
            $this->db->bind('tid', $tenant_id);
            $akunPiutang = $this->db->single()['kode_akun'] ?? '1-10100';

            $nominal = (float)$trx['total_diterima'];
            $noTransaksi = $jurnalModel->generateNoTransaksi($tenant_id);

            $jurnalData = [
                'no_transaksi' => $noTransaksi,
                'tanggal' => $trx['tanggal'],
                'deskripsi' => 'Sinkronisasi Otomatis: Penerimaan Piutang ' . $trx['no_bukti'] . ' - ' . $trx['keterangan'],
                'sumber_jurnal' => 'Penerimaan',
                'details' => [
                    ['kode_akun' => $trx['akun_kas_bank'], 'debit' => $nominal, 'kredit' => 0],
                    ['kode_akun' => $akunPiutang, 'debit' => 0, 'kredit' => $nominal]
                ]
            ];

            $id_jurnal = $jurnalModel->simpanJurnal($jurnalData, $tenant_id);
            $this->db->query("UPDATE penerimaan_pelanggan SET id_jurnal = :id_jurnal WHERE id_penerimaan = :id AND tenant_id = :tenant_id");
            $this->db->bind('id_jurnal', $id_jurnal);
            $this->db->bind('id', $id_transaksi);
            $this->db->bind('tenant_id', $tenant_id);
            $this->db->execute();

            return $id_jurnal;

        } elseif (strpos($tipeLower, 'pembayaran') !== false) {
            // SINKRONISASI PEMBAYARAN PEMASOK
            $this->db->query("SELECT * FROM pembayaran_pemasok WHERE id_pembayaran = :id AND tenant_id = :tenant_id");
            $this->db->bind('id', $id_transaksi);
            $this->db->bind('tenant_id', $tenant_id);
            $trx = $this->db->single();
            if (!$trx) throw new Exception("Data pembayaran pemasok tidak ditemukan.");

            // Cari akun utang default
            $this->db->query("SELECT kode_akun FROM akun WHERE tenant_id = :tid AND (tipe_akun = 'Utang Usaha' OR kode_akun = '2-10100') LIMIT 1");
            $this->db->bind('tid', $tenant_id);
            $akunUtang = $this->db->single()['kode_akun'] ?? '2-10100';

            $nominal = (float)$trx['total_dibayar'];
            $noTransaksi = $jurnalModel->generateNoTransaksi($tenant_id);

            $jurnalData = [
                'no_transaksi' => $noTransaksi,
                'tanggal' => $trx['tanggal'],
                'deskripsi' => 'Sinkronisasi Otomatis: Pembayaran Utang ' . $trx['no_bukti'] . ' - ' . $trx['keterangan'],
                'sumber_jurnal' => 'Pembayaran',
                'details' => [
                    ['kode_akun' => $akunUtang, 'debit' => $nominal, 'kredit' => 0],
                    ['kode_akun' => $trx['akun_kas_bank'], 'debit' => 0, 'kredit' => $nominal]
                ]
            ];

            $id_jurnal = $jurnalModel->simpanJurnal($jurnalData, $tenant_id);
            $this->db->query("UPDATE pembayaran_pemasok SET id_jurnal = :id_jurnal WHERE id_pembayaran = :id AND tenant_id = :tenant_id");
            $this->db->bind('id_jurnal', $id_jurnal);
            $this->db->bind('id', $id_transaksi);
            $this->db->bind('tenant_id', $tenant_id);
            $this->db->execute();

            return $id_jurnal;

        } elseif (strpos($tipeLower, 'penjualan') !== false) {
            // SINKRONISASI PENJUALAN
            $this->db->query("SELECT * FROM penjualan WHERE id_penjualan = :id AND tenant_id = :tenant_id");
            $this->db->bind('id', $id_transaksi);
            $this->db->bind('tenant_id', $tenant_id);
            $trx = $this->db->single();
            if (!$trx) throw new Exception("Data faktur penjualan tidak ditemukan.");

            $nominal = (float)$trx['total'];
            $noTransaksi = $jurnalModel->generateNoTransaksi($tenant_id);

            // Akun Debit: Kas/Bank jika Tunai, Piutang jika Kredit
            if ($trx['metode_pembayaran'] === 'Tunai' && !empty($trx['akun_kas_bank'])) {
                $akunDebit = $trx['akun_kas_bank'];
            } else {
                $this->db->query("SELECT kode_akun FROM akun WHERE tenant_id = :tid AND (tipe_akun = 'Piutang' OR kode_akun = '1-10100') LIMIT 1");
                $this->db->bind('tid', $tenant_id);
                $akunDebit = $this->db->single()['kode_akun'] ?? '1-10100';
            }

            // Akun Kredit: Pendapatan Penjualan
            $this->db->query("SELECT kode_akun FROM akun WHERE tenant_id = :tid AND (tipe_akun = 'Pendapatan' OR kode_akun LIKE '4-%') ORDER BY kode_akun ASC LIMIT 1");
            $this->db->bind('tid', $tenant_id);
            $akunKredit = $this->db->single()['kode_akun'] ?? '4-10100';

            $jurnalData = [
                'no_transaksi' => $noTransaksi,
                'tanggal' => $trx['tanggal_faktur'],
                'deskripsi' => 'Sinkronisasi Otomatis: Penjualan Faktur #' . $trx['no_faktur'] . ' (' . $trx['metode_pembayaran'] . ')',
                'sumber_jurnal' => 'Penjualan',
                'details' => [
                    ['kode_akun' => $akunDebit, 'debit' => $nominal, 'kredit' => 0],
                    ['kode_akun' => $akunKredit, 'debit' => 0, 'kredit' => $nominal]
                ]
            ];

            $id_jurnal = $jurnalModel->simpanJurnal($jurnalData, $tenant_id);
            $this->db->query("UPDATE penjualan SET id_jurnal = :id_jurnal WHERE id_penjualan = :id AND tenant_id = :tenant_id");
            $this->db->bind('id_jurnal', $id_jurnal);
            $this->db->bind('id', $id_transaksi);
            $this->db->bind('tenant_id', $tenant_id);
            $this->db->execute();

            return $id_jurnal;

        } elseif (strpos($tipeLower, 'pembelian') !== false) {
            // SINKRONISASI PEMBELIAN
            $this->db->query("SELECT * FROM pembelian WHERE id_pembelian = :id AND tenant_id = :tenant_id");
            $this->db->bind('id', $id_transaksi);
            $this->db->bind('tenant_id', $tenant_id);
            $trx = $this->db->single();
            if (!$trx) throw new Exception("Data faktur pembelian tidak ditemukan.");

            $nominal = (float)$trx['total'];
            $noTransaksi = $jurnalModel->generateNoTransaksi($tenant_id);

            // Akun Debit: Persediaan / Beban Pembelian
            $this->db->query("SELECT kode_akun FROM akun WHERE tenant_id = :tid AND (tipe_akun = 'Persediaan' OR kode_akun = '1-10200' OR kode_akun LIKE '5-%') ORDER BY kode_akun ASC LIMIT 1");
            $this->db->bind('tid', $tenant_id);
            $akunDebit = $this->db->single()['kode_akun'] ?? '1-10200';

            // Akun Kredit: Kas/Bank jika Tunai, Utang jika Kredit
            if ($trx['metode_pembayaran'] === 'Tunai' && !empty($trx['akun_kas_bank'])) {
                $akunKredit = $trx['akun_kas_bank'];
            } else {
                $this->db->query("SELECT kode_akun FROM akun WHERE tenant_id = :tid AND (tipe_akun = 'Utang Usaha' OR kode_akun = '2-10100') LIMIT 1");
                $this->db->bind('tid', $tenant_id);
                $akunKredit = $this->db->single()['kode_akun'] ?? '2-10100';
            }

            $jurnalData = [
                'no_transaksi' => $noTransaksi,
                'tanggal' => $trx['tanggal_faktur'],
                'deskripsi' => 'Sinkronisasi Otomatis: Pembelian Faktur #' . ($trx['no_faktur_pembelian'] ?? $trx['no_faktur'] ?? '') . ' (' . $trx['metode_pembayaran'] . ')',
                'sumber_jurnal' => 'Pembelian',
                'details' => [
                    ['kode_akun' => $akunDebit, 'debit' => $nominal, 'kredit' => 0],
                    ['kode_akun' => $akunKredit, 'debit' => 0, 'kredit' => $nominal]
                ]
            ];

            $id_jurnal = $jurnalModel->simpanJurnal($jurnalData, $tenant_id);
            $this->db->query("UPDATE pembelian SET id_jurnal = :id_jurnal WHERE id_pembelian = :id AND tenant_id = :tenant_id");
            $this->db->bind('id_jurnal', $id_jurnal);
            $this->db->bind('id', $id_transaksi);
            $this->db->bind('tenant_id', $tenant_id);
            $this->db->execute();

            return $id_jurnal;
        }

        throw new Exception("Tipe transaksi '$tipe' belum didukung untuk auto-sinkronisasi jurnal.");
    }

    /**
     * Pembersihan anomali: hapus header kosong atau orphan detail
     */
    public function bersihkanAnomali($aksi, $id_target, $tenant_id) {
        if ($aksi === 'hapus_header_kosong') {
            $this->db->query("DELETE FROM jurnal_umum WHERE id_jurnal = :id AND tenant_id = :tenant_id");
            $this->db->bind('id', $id_target);
            $this->db->bind('tenant_id', $tenant_id);
            return $this->db->execute();
        } elseif ($aksi === 'bersihkan_orphan_detail') {
            $this->db->query("DELETE FROM jurnal_detail WHERE id_jurnal = :id");
            $this->db->bind('id', $id_target);
            return $this->db->execute();
        }
        return false;
    }

    /**
     * Bulk Action: Pembersihan beberapa item anomali sekaligus
     */
    public function bulkBersihkanAnomali($items, $tenant_id, $actionType = 'bersihkan') {
        $countSuccess = 0;
        if (empty($items) || !is_array($items)) {
            return 0;
        }

        foreach ($items as $itemStr) {
            $parts = explode(':', $itemStr, 2);
            if (count($parts) !== 2) continue;
            $aksi = $parts[0];
            $idTarget = (int)$parts[1];

            if ($actionType === 'hapus_terpilih') {
                // Hapus jurnal lengkap beserta detailnya
                $this->db->query("DELETE FROM jurnal_detail WHERE id_jurnal = :id");
                $this->db->bind('id', $idTarget);
                $this->db->execute();

                $this->db->query("DELETE FROM jurnal_umum WHERE id_jurnal = :id AND tenant_id = :tenant_id");
                $this->db->bind('id', $idTarget);
                $this->db->bind('tenant_id', $tenant_id);
                $this->db->execute();
                $countSuccess++;
            } else {
                // Bersihkan sesuai jenis anomali
                if ($aksi === 'bersihkan_orphan_detail') {
                    $this->db->query("DELETE FROM jurnal_detail WHERE id_jurnal = :id");
                    $this->db->bind('id', $idTarget);
                    $this->db->execute();
                    $countSuccess++;
                } elseif ($aksi === 'hapus_header_kosong') {
                    $this->db->query("DELETE FROM jurnal_umum WHERE id_jurnal = :id AND tenant_id = :tenant_id");
                    $this->db->bind('id', $idTarget);
                    $this->db->bind('tenant_id', $tenant_id);
                    $this->db->execute();
                    $countSuccess++;
                } elseif ($aksi === 'edit_jurnal') {
                    // Jika user memilih item untuk dibersihkan/dihapus
                    $this->db->query("DELETE FROM jurnal_detail WHERE id_jurnal = :id");
                    $this->db->bind('id', $idTarget);
                    $this->db->execute();

                    $this->db->query("DELETE FROM jurnal_umum WHERE id_jurnal = :id AND tenant_id = :tenant_id");
                    $this->db->bind('id', $idTarget);
                    $this->db->bind('tenant_id', $tenant_id);
                    $this->db->execute();
                    $countSuccess++;
                }
            }
        }

        return $countSuccess;
    }

    /**
     * Sapu Bersih Semua Data Yatim (Orphan Details) & Header Kosong untuk tenant ini
     */
    public function purgeAllFixableAnomalies($tenant_id) {
        $count = 0;

        // 1. Bersihkan semua baris detail yatim (orphan details)
        $this->db->query("SELECT DISTINCT id_jurnal FROM jurnal_detail WHERE id_jurnal NOT IN (SELECT id_jurnal FROM jurnal_umum)");
        $orphans = $this->db->resultSet();
        if (!empty($orphans)) {
            $orphanIds = array_column($orphans, 'id_jurnal');
            $this->db->query("DELETE FROM jurnal_detail WHERE id_jurnal NOT IN (SELECT id_jurnal FROM jurnal_umum)");
            $this->db->execute();
            $count += count($orphanIds);
        }

        // 2. Bersihkan header kosong (tanpa detail) untuk tenant ini
        $this->db->query("SELECT ju.id_jurnal FROM jurnal_umum ju 
                          LEFT JOIN jurnal_detail jd ON ju.id_jurnal = jd.id_jurnal 
                          WHERE ju.tenant_id = :tenant_id AND jd.id_detail IS NULL");
        $this->db->bind('tenant_id', $tenant_id);
        $emptyHeaders = $this->db->resultSet();
        if (!empty($emptyHeaders)) {
            $emptyIds = array_column($emptyHeaders, 'id_jurnal');
            foreach ($emptyIds as $eId) {
                $this->db->query("DELETE FROM jurnal_umum WHERE id_jurnal = :id AND tenant_id = :tenant_id");
                $this->db->bind('id', $eId);
                $this->db->bind('tenant_id', $tenant_id);
                $this->db->execute();
                $count++;
            }
        }

        return $count;
    }
}
