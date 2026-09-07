-- Migration: Add indexes for performance optimization (Phase 1)
-- SimpleAkunting v3.6

ALTER TABLE `jurnal_detail` ADD INDEX `idx_jd_kode_akun` (`kode_akun`);
ALTER TABLE `jurnal_umum` ADD INDEX `idx_ju_tenant_tgl` (`tenant_id`, `tanggal`);
ALTER TABLE `penjualan` ADD INDEX `idx_pj_tenant_tgl` (`tenant_id`, `tanggal_faktur`);
ALTER TABLE `pembelian` ADD INDEX `idx_pb_tenant_tgl` (`tenant_id`, `tanggal_faktur`);
ALTER TABLE `master_persediaan` ADD INDEX `idx_mp_tenant` (`tenant_id`);
ALTER TABLE `kas_transaksi` ADD INDEX `idx_kt_tenant_tgl` (`tenant_id`, `tanggal`);
