-- Migrasi: Tambahkan kolom akun_potongan_penjualan dan akun_potongan_pembelian ke tabel perusahaan
ALTER TABLE `perusahaan`
ADD COLUMN `akun_potongan_penjualan` VARCHAR(20) NULL DEFAULT NULL AFTER `akun_pajak_pembelian`,
ADD COLUMN `akun_potongan_pembelian` VARCHAR(20) NULL DEFAULT NULL AFTER `akun_potongan_penjualan`;
