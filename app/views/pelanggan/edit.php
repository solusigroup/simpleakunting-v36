    <div class="card">
        <div class="card-header"><h3>Form Edit Pelanggan</h3></div>
        <div class="card-body">
            <form action="<?php echo BASEURL; ?>/pelanggan/update" method="post">
            <?php echo csrf_field(); ?>
                <input type="hidden" name="id_pelanggan" value="<?php echo htmlspecialchars($data['pelanggan']['id_pelanggan'], ENT_QUOTES, 'UTF-8'); ?>">
                <div class="mb-3"><label>Nama Pelanggan</label><input type="text" name="nama_pelanggan" class="form-control" value="<?php echo htmlspecialchars($data['pelanggan']['nama_pelanggan'], ENT_QUOTES, 'UTF-8'); ?>" required></div>
                <div class="mb-3"><label>Alamat</label><textarea name="alamat" class="form-control"><?php echo htmlspecialchars($data['pelanggan']['alamat'] ?? '', ENT_QUOTES, 'UTF-8'); ?></textarea></div>
                <div class="mb-3"><label>Telepon</label><input type="text" name="telepon" class="form-control" value="<?php echo htmlspecialchars($data['pelanggan']['telepon'] ?? '', ENT_QUOTES, 'UTF-8'); ?>"></div>
                <div class="mb-3"><label>Email</label><input type="email" name="email" class="form-control" value="<?php echo htmlspecialchars($data['pelanggan']['email'] ?? '', ENT_QUOTES, 'UTF-8'); ?>"></div>
                <div class="mb-3"><label>Saldo Awal Piutang</label><input type="number" step="0.01" name="saldo_awal_piutang" class="form-control" value="<?php echo htmlspecialchars($data['pelanggan']['saldo_awal_piutang'] ?? '0', ENT_QUOTES, 'UTF-8'); ?>"></div>
                <hr>
                <a href="<?php echo BASEURL; ?>/pelanggan" class="btn btn-secondary">Batal</a>
                <button type="submit" class="btn btn-primary">Update</button>
            </form>
        </div>
    </div>
    

