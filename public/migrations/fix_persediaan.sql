ALTER TABLE master_persediaan ADD COLUMN IF NOT EXISTS kategori VARCHAR(255) NULL AFTER nama_barang;

CREATE TABLE IF NOT EXISTS `kartu_stok` (
  `id_kartu` bigint unsigned NOT NULL AUTO_INCREMENT,
  `id_barang` bigint unsigned NOT NULL,
  `tipe_transaksi` enum('IN','OUT') NOT NULL,
  `kuantitas` decimal(15,2) NOT NULL,
  `keterangan` text,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id_kartu`),
  FOREIGN KEY (`id_barang`) REFERENCES `master_persediaan` (`id_barang`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
