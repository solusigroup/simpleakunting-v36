<?php
$files = ['public/panduan_pengguna.html', 'public/InfografisSimpleAkuntingUMKM.html'];

foreach ($files as $file) {
    if (file_exists($file)) {
        $content = file_get_contents($file);
        
        // Replace UMKM with BUMDesa
        $content = str_ireplace('UMKM', 'BUMDesa', $content);
        
        // Replace Dinas Koperasi with DPMD Jatim
        $content = str_ireplace('Dinas Koperasi', 'DPMD Jatim', $content);
        
        // Standardize DPMD Prov Jatim / DPMD Provinsi Jawa Timur to DPMD Jatim
        $content = str_replace('DPMD Provinsi Jawa Timur', 'DPMD Jatim', $content);
        $content = str_replace('DPMD Prov Jatim', 'DPMD Jatim', $content);
        
        // Fix potential double spaces or other minor issues
        $content = str_replace('DPMD Jatim Jatim', 'DPMD Jatim', $content);
        
        file_put_contents($file, $content);
        echo "✅ Fixed terminology in $file\n";
    }
}
