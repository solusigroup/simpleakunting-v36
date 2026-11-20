    <div class="card">
        <div class="card-header"><h3>Form Edit Pemasok</h3></div>
        <div class="card-body">
            <form action="<?php echo BASEURL; ?>/pemasok/update" method="post">
            <?php echo csrf_field(); ?>
                <input type="hidden" name="id_pemasok" value="<?php echo htmlspecialchars($data['pemasok']['id_pemasok'], ENT_QUOTES, 'UTF-8'); ?>">
                <div class="mb-3"><label>Nama Pemasok</label><input type="text" name="nama_pemasok" class="form-control" value="<?php echo htmlspecialchars($data['pemasok']['nama_pemasok'], ENT_QUOTES, 'UTF-8'); ?>" required></div>
                <div class="mb-3"><label>Alamat</label><textarea name="alamat" class="form-control"><?php echo htmlspecialchars($data['pemasok']['alamat'] ?? '', ENT_QUOTES, 'UTF-8'); ?></textarea></div>
                <div class="mb-3"><label>Telepon</label><input type="text" name="telepon" class="form-control" value="<?php echo htmlspecialchars($data['pemasok']['telepon'] ?? '', ENT_QUOTES, 'UTF-8'); ?>"></div>
                <div class="mb-3"><label>Email</label><input type="email" name="email" class="form-control" value="<?php echo htmlspecialchars($data['pemasok']['email'] ?? '', ENT_QUOTES, 'UTF-8'); ?>"></div>
                <div class="mb-3"><label>Saldo Awal Hutang</label><input type="number" step="0.01" name="saldo_awal_hutang" class="form-control" value="<?php echo htmlspecialchars($data['pemasok']['saldo_awal_hutang'] ?? '0', ENT_QUOTES, 'UTF-8'); ?>"></div>
                <hr>
                <a href="<?php echo BASEURL; ?>/pemasok" class="btn btn-secondary">Batal</a>
                <button type="submit" class="btn btn-primary">Update</button>
            </form>
        </div>
    </div>
    

