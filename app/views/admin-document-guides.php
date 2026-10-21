<?php if ($guideError !== ''): ?><p class="admin-error" role="alert"><?= escape($guideError) ?></p><?php endif; ?>
<?php if ($guideError === '' && (isset($_GET['saved']) || isset($_GET['deleted']))): ?><p class="admin-success" role="status"><?= isset($_GET['deleted']) ? 'Panduan persyaratan & biaya dihapus.' : 'Persyaratan dan biaya tersimpan. Publik KIOSK membaca perubahan saat halaman dimuat ulang.' ?></p><?php endif; ?>
<section class="panel category-panel">
    <div class="panel-heading"><div><h2>Data persyaratan & biaya (<?= count($managedGuides) ?>)</h2><p>Referensi terpisah dari katalog pengajuan. OFF menyembunyikan panduan dari publik KIOSK.</p></div><a class="button button-primary" id="guide-add" href="dashboard.php?module=document-guides&amp;new=1" aria-haspopup="dialog" aria-controls="guide-dialog">+ Tambah pengurusan</a></div>
    <div class="category-table"><table id="guide-table"><thead><tr><th scope="col">Pengurusan / wilayah</th><th scope="col">Jenis data</th><th scope="col">Biaya (Rp)</th><th scope="col">Urutan</th><th scope="col">Status</th><th scope="col">Aksi</th></tr></thead><tbody>
        <?php foreach ($managedGuides as $row): ?><tr>
            <td><?= escape($row['name']) ?><span class="table-note"><?= escape($row['region']) ?></span></td>
            <td><?= (int) $row['is_demo'] === 1 ? 'DEMO' : 'Non-DEMO' ?><span class="table-note"><?= (int) $row['fee_verified'] === 1 ? 'Tarif diverifikasi pengelola' : 'Belum diverifikasi' ?></span></td>
            <td data-order="<?= $row['fee_amount'] === null ? -1 : (int) $row['fee_amount'] ?>"><?= $row['fee_amount'] === null ? 'Belum diketahui' : number_format((int) $row['fee_amount'], 0, ',', '.') ?></td>
            <td><?= (int) $row['sort_order'] ?></td>
            <td><form method="post" action="dashboard.php?module=document-guides&amp;id=<?= (int) $row['id'] ?>" class="category-status-form">
                <input type="hidden" name="csrf_token" value="<?= escape(csrf_token()) ?>"><input type="hidden" name="action" value="toggle"><input type="hidden" name="version" value="<?= (int) $row['version'] ?>">
                <label class="category-switch"><input type="checkbox" role="switch" name="is_published" value="1" aria-label="Aktifkan panduan <?= escape($row['name']) ?>" <?= (int) $row['is_published'] === 1 ? 'checked' : '' ?>><span class="category-switch-track" aria-hidden="true"></span><span><?= (int) $row['is_published'] === 1 ? 'Aktif' : 'Nonaktif' ?></span></label>
                <noscript><button class="button" type="submit">Simpan status</button></noscript>
            </form></td>
            <td><div class="category-actions">
                <a class="button category-edit" href="dashboard.php?module=document-guides&amp;id=<?= (int) $row['id'] ?>" aria-haspopup="dialog" aria-controls="guide-dialog">Ubah<span class="sr-only"> <?= escape($row['name']) ?></span></a>
                <form method="post" action="dashboard.php?module=document-guides&amp;id=<?= (int) $row['id'] ?>" class="category-delete">
                    <input type="hidden" name="csrf_token" value="<?= escape(csrf_token()) ?>"><input type="hidden" name="action" value="delete"><input type="hidden" name="version" value="<?= (int) $row['version'] ?>">
                    <button class="button button-danger-outline" type="submit">Hapus<span class="sr-only"> <?= escape($row['name']) ?></span></button>
                </form>
            </div></td>
        </tr><?php endforeach; ?>
    </tbody></table><noscript><p>Aktifkan JavaScript untuk pencarian, sort dan pagination.</p><?php if ($managedGuides === []): ?><p>Belum ada panduan.</p><?php endif; ?></noscript></div>
</section>
<dialog id="guide-dialog" class="category-dialog" aria-labelledby="guide-dialog-title" <?= $guideModalOpen ? 'open data-auto-open' : '' ?>>
    <div class="category-dialog-heading"><h2 id="guide-dialog-title"><?= $guideId > 0 ? 'Ubah pengurusan dokumen' : 'Tambah pengurusan dokumen' ?></h2><a class="button" href="dashboard.php?module=document-guides" data-guide-close aria-label="Tutup formulir panduan">Tutup</a></div>
    <div class="category-dialog-body">
    <?php if ($guideError !== ''): ?><p class="admin-error" role="alert"><?= escape($guideError) ?></p><?php endif; ?>
    <p>Nominal DEMO tidak boleh digunakan untuk pungutan. Untuk data resmi, periksa dokumen hukum yang berlaku, termasuk nomor, tahun, pasal/lampiran tarif dan sumber resminya. Tidak semua layanan memiliki dasar tarif Perda.</p>
    <form method="post" class="admin-form" id="document-guide-form" action="dashboard.php?module=document-guides<?= $guideId > 0 ? '&amp;id=' . $guideId : '' ?>">
        <input type="hidden" name="csrf_token" value="<?= escape(csrf_token()) ?>">
        <input type="hidden" name="action" value="save">
        <input type="hidden" name="version" value="<?= (int) $guideForm['version'] ?>">
        <label>Nama pengurusan<input name="name" maxlength="180" required value="<?= escape($guideForm['name']) ?>"></label>
        <label>Wilayah<input name="region" maxlength="180" required value="<?= escape($guideForm['region']) ?>"></label>
        <label>Persyaratan (satu dokumen per baris, maksimal 30 baris)<textarea name="requirements_text" rows="7" maxlength="6000" required><?= escape($guideForm['requirements_text']) ?></textarea></label>
        <label>Biaya (rupiah bulat; kosong jika belum diketahui)<input type="number" name="fee_amount" min="0" max="1000000000" step="1" value="<?= escape((string) ($guideForm['fee_amount'] ?? '')) ?>"></label>
        <label>Catatan biaya<textarea name="fee_note" rows="3" maxlength="1500"><?= escape($guideForm['fee_note']) ?></textarea></label>
        <label>Dasar hukum / Perda (nomor, tahun, pasal atau lampiran)<textarea name="legal_basis" rows="3" maxlength="1500"><?= escape($guideForm['legal_basis']) ?></textarea></label>
        <label>URL sumber resmi<input type="url" name="source_url" maxlength="1000" value="<?= escape($guideForm['source_url']) ?>"></label>
        <label>Urutan tampil<input type="number" name="sort_order" min="0" max="9999" required value="<?= (int) $guideForm['sort_order'] ?>"></label>
        <?php foreach (['is_demo' => 'Data simulasi DEMO (bukan ketentuan resmi)', 'fee_verified' => 'Saya telah memverifikasi tarif dan dasar hukum pada sumber resmi (hanya non-DEMO)', 'is_published' => 'Aktif / tampil di publik KIOSK'] as $key => $label): ?>
            <label class="<?= $key === 'fee_verified' ? '' : 'category-switch' ?>"><input type="checkbox" <?= $key === 'fee_verified' ? '' : 'role="switch"' ?> name="<?= escape($key) ?>" value="1" <?= (int) $guideForm[$key] === 1 ? 'checked' : '' ?>><?= $key === 'fee_verified' ? '' : '<span class="category-switch-track" aria-hidden="true"></span>' ?><span><?= escape($label) ?></span></label>
        <?php endforeach; ?>
        <p>Status ON data non-DEMO memerlukan nominal, dasar hukum, URL sumber dan konfirmasi verifikasi. OFF tidak menghapus data. Biaya kosong berarti belum diketahui; 0 berarti nominal nol.</p>
    </form>
    </div>
    <div class="category-dialog-footer"><a class="button" href="dashboard.php?module=document-guides" data-guide-close>Batal</a><button class="button button-primary" type="submit" form="document-guide-form">Simpan persyaratan & biaya</button></div>
</dialog>
