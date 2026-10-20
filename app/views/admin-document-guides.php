<?php if ($guideError !== ''): ?><p class="admin-error" role="alert"><?= escape($guideError) ?></p><?php endif; ?>
<?php if (isset($_GET['saved']) && $guideError === ''): ?><p class="admin-success" role="status">Persyaratan dan biaya tersimpan di SQLite.</p><?php endif; ?>
<section class="panel admin-detail">
    <div class="panel-heading"><div><h2>Master persyaratan & biaya</h2><p>Data terpisah dari katalog pengajuan. Nonaktifkan publikasi untuk menarik informasi tanpa menghapusnya.</p></div><a class="button button-primary" href="dashboard.php?module=document-guides">Tambah pengurusan</a></div>
    <div class="table-scroll"><table><thead><tr><th>Pengurusan / wilayah</th><th>Jenis data</th><th>Publikasi</th><th>Aksi</th></tr></thead><tbody>
        <?php if ($managedGuides === []): ?><tr><td colspan="4">Belum ada data. Tambahkan panduan atau jalankan seed DEMO.</td></tr><?php endif; ?>
        <?php foreach ($managedGuides as $row): ?><tr><td><?= escape($row['name']) ?><br><small><?= escape($row['region']) ?></small></td><td><?= (int) $row['is_demo'] === 1 ? 'DEMO' : 'Non-DEMO' ?></td><td><?= (int) $row['is_published'] === 1 ? 'Tayang' : 'Draf' ?></td><td><a class="button" href="dashboard.php?module=document-guides&amp;id=<?= (int) $row['id'] ?>">Edit</a></td></tr><?php endforeach; ?>
    </tbody></table></div>
</section>
<section class="panel admin-detail">
    <h2><?= $guideId > 0 ? 'Edit pengurusan dokumen' : 'Tambah pengurusan dokumen' ?></h2>
    <p>Nominal DEMO tidak boleh digunakan untuk pungutan. Untuk data resmi, periksa dokumen hukum yang berlaku, termasuk nomor, tahun, pasal/lampiran tarif dan sumber resminya. Tidak semua layanan memiliki dasar tarif Perda.</p>
    <form method="post" class="admin-form" id="document-guide-form">
        <input type="hidden" name="csrf_token" value="<?= escape(csrf_token()) ?>">
        <input type="hidden" name="version" value="<?= (int) $guideForm['version'] ?>">
        <label>Nama pengurusan<input name="name" maxlength="180" required value="<?= escape($guideForm['name']) ?>"></label>
        <label>Wilayah<input name="region" maxlength="180" required value="<?= escape($guideForm['region']) ?>"></label>
        <label>Persyaratan (satu dokumen per baris, maksimal 30 baris)<textarea name="requirements_text" rows="7" maxlength="6000" required><?= escape($guideForm['requirements_text']) ?></textarea></label>
        <label>Biaya (rupiah bulat; kosong jika belum diketahui)<input type="number" name="fee_amount" min="0" max="1000000000" step="1" value="<?= escape((string) ($guideForm['fee_amount'] ?? '')) ?>"></label>
        <label>Catatan biaya<textarea name="fee_note" rows="3" maxlength="1500"><?= escape($guideForm['fee_note']) ?></textarea></label>
        <label>Dasar hukum / Perda (nomor, tahun, pasal atau lampiran)<textarea name="legal_basis" rows="3" maxlength="1500"><?= escape($guideForm['legal_basis']) ?></textarea></label>
        <label>URL sumber resmi<input type="url" name="source_url" maxlength="1000" value="<?= escape($guideForm['source_url']) ?>"></label>
        <label>Urutan tampil<input type="number" name="sort_order" min="0" max="9999" required value="<?= (int) $guideForm['sort_order'] ?>"></label>
        <?php foreach (['is_demo' => 'Data simulasi DEMO (bukan ketentuan resmi)', 'fee_verified' => 'Saya telah memverifikasi tarif dan dasar hukum pada sumber resmi (hanya non-DEMO)', 'is_published' => 'Publikasikan pada KIOSK'] as $key => $label): ?>
            <label><input type="checkbox" name="<?= escape($key) ?>" value="1" <?= (int) $guideForm[$key] === 1 ? 'checked' : '' ?>> <?= escape($label) ?></label>
        <?php endforeach; ?>
        <button class="button button-primary" type="submit">Simpan persyaratan & biaya</button>
    </form>
</section>
