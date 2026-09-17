-- ==============================================================================
-- Migration: Update Central COA & Default Accounts
-- SimpleAkunting v3.6
-- ==============================================================================

-- 1. Refresh Data central_akun
TRUNCATE TABLE `central_akun`;

INSERT INTO `central_akun` (`kode_akun`, `nama_akun`, `tipe_akun`, `posisi_saldo_normal`) VALUES
('1-0000', 'ASET', 'Header', 'Debit'),
('1-1000', 'ASET LANCAR', 'Header', 'Debit'),
('1-1100', 'Kas & Bank', 'Detail', 'Debit'),
('1-1101', 'Kas Kecil', 'Detail', 'Debit'),
('1-1102', 'Bank BCA', 'Detail', 'Debit'),
('1-1200', 'Piutang Usaha', 'Detail', 'Debit'),
('1-1201', 'Piutang Jasa Keuangan', 'Detail', 'Debit'),
('1-1300', 'Persediaan Barang', 'Detail', 'Debit'),
('1-1301', 'Peralatan Kantor - Komputer & Printer', 'Detail', 'Debit'),
('1-1302', 'Akumulasi Dep. Peralatan Kantor', 'Detail', 'Kredit'),
('1-1400', 'Pajak Dibayar Dimuka (PPN Masukan)', 'Detail', 'Debit'),
('1-2000', 'ASET TETAP', 'Header', 'Debit'),
('1-2100', 'Tanah', 'Detail', 'Debit'),
('1-2200', 'Bangunan', 'Detail', 'Debit'),
('1-2201', 'Akum. Penyusutan Bangunan', 'Detail', 'Kredit'),
('1-2300', 'Kendaraan', 'Detail', 'Debit'),
('1-2301', 'Akum. Penyusutan Kendaraan', 'Detail', 'Kredit'),
('2-0000', 'KEWAJIBAN', 'Header', 'Kredit'),
('2-1000', 'KEWAJIBAN LANCAR', 'Header', 'Kredit'),
('2-1100', 'Hutang Usaha', 'Detail', 'Kredit'),
('2-1200', 'Hutang Pajak (PPN Keluaran)', 'Detail', 'Kredit'),
('2-1300', 'Hutang Gaji', 'Detail', 'Kredit'),
('2-2000', 'KEWAJIBAN JANGKA PANJANG', 'Header', 'Kredit'),
('2-2100', 'Hutang Bank', 'Detail', 'Kredit'),
('3-0000', 'EKUITAS', 'Header', 'Kredit'),
('3-1000', 'Modal Disetor', 'Detail', 'Kredit'),
('3-2000', 'Laba Ditahan', 'Detail', 'Kredit'),
('3-3000', 'Ikhtisar Laba Rugi', 'Detail', 'Kredit'),
('4-0000', 'PENDAPATAN', 'Header', 'Kredit'),
('4-1000', 'Pendapatan Usaha Jasa', 'Detail', 'Kredit'),
('4-2000', 'Pendapatan Lain-lain', 'Detail', 'Kredit'),
('4-3000', 'Penjualan Barang Dagangan', 'Detail', 'Kredit'),
('4-3001', 'Potongan Penjualan', 'Detail', 'Debit'),
('5-0000', 'HARGA POKOK PENJUALAN', 'Header', 'Debit'),
('5-1000', 'HPP Barang Dagangan', 'Detail', 'Debit'),
('5-1001', 'Potongan Pembelian', 'Detail', 'Kredit'),
('6-0000', 'BEBAN OPERASIONAL', 'Header', 'Debit'),
('6-1000', 'Beban Gaji & Upah', 'Detail', 'Debit'),
('6-2000', 'Beban Listrik, Air & Telp', 'Detail', 'Debit'),
('6-3000', 'Beban Sewa', 'Detail', 'Debit'),
('6-4000', 'Beban Perlengkapan Kantor', 'Detail', 'Debit'),
('6-5000', 'Beban Penyusutan Aset', 'Detail', 'Debit'),
('6-9000', 'Beban Lain-lain', 'Detail', 'Debit');

-- 2. Update Pengaturan Default Akun di tabel perusahaan (jika ada tenant yang memakai default lama)
UPDATE `perusahaan` SET
  `akun_piutang_default` = '1-1200',
  `akun_utang_default` = '2-1100',
  `akun_potongan_penjualan` = '4-3001',
  `akun_potongan_pembelian` = '5-1001',
  `akun_laba_ditahan` = '3-2000',
  `akun_ikhtisar_lr` = '3-3000',
  `akun_pajak_penjualan` = '2-1200',
  `akun_pajak_pembelian` = '1-1400',
  `akun_akumulasi_depresiasi_default` = '1-2201',
  `akun_beban_depresiasi_default` = '6-5000'
WHERE `tenant_id` IS NOT NULL;
