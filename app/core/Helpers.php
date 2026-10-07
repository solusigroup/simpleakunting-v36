<?php

/**
 * Helper Terbilang Bahasa Indonesia
 */
function terbilang($angka) {
    $angka = (int)abs($angka);
    $baca = ['', 'Satu', 'Dua', 'Tiga', 'Empat', 'Lima', 'Enam', 'Tujuh', 'Delapan', 'Sembilan', 'Sepuluh', 'Sebelas'];
    $terbilang = '';

    if ($angka < 12) {
        $terbilang = ' ' . $baca[$angka];
    } elseif ($angka < 20) {
        $terbilang = terbilang($angka - 10) . ' Belas';
    } elseif ($angka < 100) {
        $terbilang = terbilang(intdiv($angka, 10)) . ' Puluh ' . terbilang($angka % 10);
    } elseif ($angka < 200) {
        $terbilang = ' Seratus ' . terbilang($angka - 100);
    } elseif ($angka < 1000) {
        $terbilang = terbilang(intdiv($angka, 100)) . ' Ratus ' . terbilang($angka % 100);
    } elseif ($angka < 2000) {
        $terbilang = ' Seribu ' . terbilang($angka - 1000);
    } elseif ($angka < 1000000) {
        $terbilang = terbilang(intdiv($angka, 1000)) . ' Ribu ' . terbilang($angka % 1000);
    } elseif ($angka < 1000000000) {
        $terbilang = terbilang(intdiv($angka, 1000000)) . ' Juta ' . terbilang($angka % 1000000);
    } elseif ($angka < 1000000000000) {
        $terbilang = terbilang(intdiv($angka, 1000000000)) . ' Miliar ' . terbilang($angka % 1000000000);
    } elseif ($angka < 1000000000000000) {
        $terbilang = terbilang(intdiv($angka, 1000000000000)) . ' Triliun ' . terbilang($angka % 1000000000000);
    }

    return preg_replace('/\s+/', ' ', trim($terbilang));
}

function terbilang_rupiah($angka) {
    $num = (float)$angka;
    if ($num == 0) return 'Nol Rupiah';
    $prefix = ($num < 0) ? 'Minus ' : '';
    return $prefix . trim(terbilang(abs($num))) . ' Rupiah';
}

/**
 * Format Tanggal Indonesia
 */
function tanggal_indo($tanggal, $cetak_hari = false) {
    if (empty($tanggal) || $tanggal === '0000-00-00') return '-';
    $hari = [
        1 => 'Senin', 'Selasa', 'Rabu', 'Kamis', 'Jumat', 'Sabtu', 'Minggu'
    ];
    $bulan = [
        1 => 'Januari', 'Februari', 'Maret', 'April', 'Mei', 'Juni',
        'Juli', 'Agustus', 'September', 'Oktober', 'November', 'Desember'
    ];
    
    $timestamp = strtotime($tanggal);
    if (!$timestamp) return $tanggal;
    
    $tgl = date('d', $timestamp);
    $bln = $bulan[(int)date('m', $timestamp)];
    $thn = date('Y', $timestamp);
    
    $hasil = "$tgl $bln $thn";
    if ($cetak_hari) {
        $nama_hari = $hari[(int)date('N', $timestamp)];
        $hasil = "$nama_hari, $hasil";
    }
    return $hasil;
}

/**
 * Format Rupiah Standar
 */
function format_rupiah($angka, $desimal = 0) {
    return 'Rp ' . number_format((float)$angka, $desimal, ',', '.');
}
