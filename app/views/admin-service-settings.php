<?php if ($serviceError !== ''): ?><p class="admin-error" role="alert"><?= escape($serviceError) ?></p><?php endif; ?>
<?php if ($serviceError === '' && (isset($_GET['saved']) || isset($_GET['deleted']))): ?><p class="admin-success" role="status"><?= isset($_GET['deleted']) ? 'Layanan dihapus.' : 'Layanan tersimpan. Publik KIOSK membaca perubahan saat halaman dimuat ulang.' ?></p><?php endif; ?>
<section class="panel category-panel">
    <div class="panel-heading">
        <div><h2>Data layanan (<?= count($serviceRecords) ?>)</h2><p>Kelola informasi, persyaratan dan pengaturan transaksi per layanan.</p></div>
        <a class="button button-primary" id="service-add" href="dashboard.php?module=services&amp;new=1" aria-haspopup="dialog" aria-controls="service-dialog">+ Tambah layanan</a>
    </div>
    <div class="category-table">
        <table id="service-table"><thead><tr><th scope="col">Layanan / slug</th><th scope="col">Kategori</th><th scope="col">Urutan</th><th scope="col">Status</th><th scope="col">Transaksi</th><th scope="col">Aksi</th></tr></thead><tbody>
        <?php foreach ($serviceRecords as $record): ?><tr>
            <td><?= escape($record['name']) ?><span class="table-note"><?= escape($record['slug']) ?><?= (int) $record['is_demo'] === 1 ? ' · DEMO' : '' ?></span></td>
            <td><?= escape($record['category_name']) ?><?php if ((int) $record['category_active'] !== 1): ?><span class="table-note">Kategori nonaktif: tersembunyi</span><?php endif; ?></td>
            <td><?= (int) $record['sort_order'] ?></td>
            <td><form method="post" action="dashboard.php?module=services&amp;id=<?= (int) $record['id'] ?>" class="category-status-form">
                <input type="hidden" name="csrf_token" value="<?= escape(csrf_token()) ?>"><input type="hidden" name="action" value="toggle"><input type="hidden" name="version" value="<?= (int) $record['version'] ?>">
                <label class="category-switch"><input type="checkbox" role="switch" name="is_published" value="1" aria-label="Aktifkan layanan <?= escape($record['name']) ?>" <?= (int) $record['is_published'] === 1 ? 'checked' : '' ?>><span class="category-switch-track" aria-hidden="true"></span><span><?= (int) $record['is_published'] === 1 ? 'Aktif' : 'Nonaktif' ?></span></label>
                <noscript><button class="button" type="submit">Simpan status</button></noscript>
            </form><span class="table-note"><?= escape($record['valid_from'] ?? 'Tanpa batas awal') ?> — <?= escape($record['valid_until'] ?? 'Tanpa batas akhir') ?></span></td>
            <td><?= (int) $record['accepts_submissions'] === 1 ? 'Terima pengajuan' : 'Informasi saja' ?><span class="table-note">Retensi <?= (int) $record['retention_days'] ?> hari</span></td>
            <td><div class="category-actions">
                <a class="button category-edit" href="dashboard.php?module=services&amp;id=<?= (int) $record['id'] ?>" aria-haspopup="dialog" aria-controls="service-dialog">Ubah<span class="sr-only"> <?= escape($record['name']) ?></span></a>
                <?php if ((int) $record['usage_count'] === 0): ?><form method="post" action="dashboard.php?module=services&amp;id=<?= (int) $record['id'] ?>" class="category-delete">
                    <input type="hidden" name="csrf_token" value="<?= escape(csrf_token()) ?>"><input type="hidden" name="action" value="delete"><input type="hidden" name="version" value="<?= (int) $record['version'] ?>">
                    <button class="button button-danger-outline" type="submit">Hapus<span class="sr-only"> <?= escape($record['name']) ?></span></button>
                </form><?php endif; ?>
            </div><?php if ((int) $record['usage_count'] > 0): ?><span class="table-note">Memiliki transaksi/nomor pengambilan; nonaktifkan bila perlu.</span><?php endif; ?></td>
        </tr><?php endforeach; ?>
        </tbody></table>
        <noscript><p>Aktifkan JavaScript untuk pencarian, sort dan pagination.</p></noscript>
    </div>
</section>
<dialog id="service-dialog" class="category-dialog" aria-labelledby="service-dialog-title" <?= $serviceModalOpen ? 'open data-auto-open' : '' ?>>
    <div class="category-dialog-heading"><h2 id="service-dialog-title"><?= $serviceId > 0 ? 'Ubah layanan' : 'Tambah layanan' ?></h2><a class="button" href="dashboard.php?module=services" data-service-close aria-label="Tutup formulir layanan">Tutup</a></div>
    <div class="category-dialog-body">
        <?php if ($serviceError !== ''): ?><p class="admin-error" role="alert"><?= escape($serviceError) ?></p><?php endif; ?>
        <p>OFF menyembunyikan layanan dari publik tanpa menghapus pengajuan lama. ON tetap mengikuti status kategori dan tanggal berlaku.</p>
        <form method="post" class="admin-form" id="service-form" action="dashboard.php?module=services<?= $serviceId > 0 ? '&amp;id=' . $serviceId : '' ?>">
            <input type="hidden" name="csrf_token" value="<?= escape(csrf_token()) ?>"><input type="hidden" name="action" value="save"><input type="hidden" name="version" value="<?= (int) $serviceForm['version'] ?>">
            <h3>Informasi layanan</h3>
            <label for="service-category">Kategori</label><select id="service-category" name="category_id" required><option value="">Pilih kategori</option><?php foreach ($serviceCategories as $category): ?><option value="<?= (int) $category['id'] ?>" <?= (int) $serviceForm['category_id'] === (int) $category['id'] ? 'selected' : '' ?>><?= escape($category['name']) ?><?= (int) $category['is_active'] === 0 ? ' (nonaktif)' : '' ?></option><?php endforeach; ?></select>
            <?php if ($serviceCategories === []): ?><p>Tambahkan kategori terlebih dahulu melalui <a href="dashboard.php?module=categories">Kategori layanan</a>.</p><?php endif; ?>
            <?php foreach (['name' => 'Nama layanan', 'slug' => 'Slug unik (huruf kecil, angka, tanda hubung)', 'summary' => 'Ringkasan', 'description' => 'Penjelasan layanan', 'eligibility' => 'Siapa yang dapat mengajukan', 'fee_text' => 'Informasi biaya / dasar ketentuan', 'estimated_time' => 'Estimasi waktu', 'office_unit' => 'Unit penanggung jawab', 'service_location' => 'Lokasi pelayanan', 'official_url' => 'URL sumber / kanal resmi'] as $key => $label): ?>
                <label for="service-<?= escape($key) ?>"><?= escape($label) ?> (maks. <?= service_master_text_fields()[$key] ?> byte)</label>
                <?php if (in_array($key, ['summary', 'description', 'eligibility', 'fee_text', 'service_location'], true)): ?>
                    <textarea id="service-<?= escape($key) ?>" name="<?= escape($key) ?>" rows="<?= $key === 'description' ? 4 : 2 ?>" maxlength="<?= service_master_text_fields()[$key] ?>"><?= escape($serviceForm[$key]) ?></textarea>
                <?php else: ?>
                    <input id="service-<?= escape($key) ?>" name="<?= escape($key) ?>" type="<?= $key === 'official_url' ? 'url' : 'text' ?>" maxlength="<?= service_master_text_fields()[$key] ?>" <?= in_array($key, ['name', 'slug'], true) ? 'required' : '' ?> <?= $key === 'slug' ? 'pattern="[a-z0-9]+(-[a-z0-9]+)*"' : '' ?> value="<?= escape($serviceForm[$key]) ?>">
                <?php endif; ?>
            <?php endforeach; ?>
            <label for="service-order">Urutan (0–9999)</label><input id="service-order" name="sort_order" type="number" min="0" max="9999" required value="<?= (int) $serviceForm['sort_order'] ?>">
            <label for="service-from">Berlaku mulai (opsional, WIB)</label><input id="service-from" name="valid_from" type="date" value="<?= escape($serviceForm['valid_from'] ?? '') ?>">
            <label for="service-until">Berlaku sampai (opsional, WIB)</label><input id="service-until" name="valid_until" type="date" value="<?= escape($serviceForm['valid_until'] ?? '') ?>">
            <h3>Status dan pengaturan transaksi</h3>
            <?php foreach (service_master_flags() as $key => $label): ?>
                <label class="category-switch"><input type="checkbox" role="switch" name="<?= escape($key) ?>" value="1" <?= (int) $serviceForm[$key] === 1 ? 'checked' : '' ?>><span class="category-switch-track" aria-hidden="true"></span><span><?= escape($label) ?></span></label>
            <?php endforeach; ?>
            <p>Aktifkan pengajuan hanya untuk layanan yang berwenang diproses kecamatan. Minta data minimum. Foto wajah terpisah dari foto dokumen; tidak ada pengenalan wajah otomatis.</p>
            <label for="service-retention">Retensi pengajuan baru (1–365 hari)</label><input id="service-retention" name="retention_days" type="number" min="1" max="365" required value="<?= (int) $serviceForm['retention_days'] ?>">
            <p>Retensi berlaku untuk pengajuan baru, bukan mengubah batas simpan tiket lama. Jalankan tugas purge harian. PDF final dan nomor pengambilan tetap mengikuti alur penyelesaian admin yang sudah ada.</p>
            <h3>Persyaratan layanan</h3>
            <p>Urutan sesuai baris. Maksimal 50 persyaratan; nama 300 byte dan penjelasan 1500 byte. Ini persyaratan katalog/pengajuan, terpisah dari master referensi Persyaratan & biaya.</p>
            <div id="service-requirements">
                <?php foreach ($serviceRequirements as $index => $requirement): ?>
                    <fieldset class="service-requirement">
                        <legend>Persyaratan</legend>
                        <input type="hidden" name="requirements[<?= $index ?>][id]" value="<?= (int) $requirement['id'] ?>">
                        <label>Nama persyaratan<input name="requirements[<?= $index ?>][name]" required maxlength="300" value="<?= escape($requirement['name']) ?>"></label>
                        <label>Penjelasan<textarea name="requirements[<?= $index ?>][description]" maxlength="1500" rows="2"><?= escape($requirement['description']) ?></textarea></label>
                        <label><input type="checkbox" name="requirements[<?= $index ?>][is_required]" value="1" <?= (int) $requirement['is_required'] === 1 ? 'checked' : '' ?>> Wajib</label>
                        <button type="button" class="button button-danger-outline" data-remove-requirement>Hapus baris</button>
                    </fieldset>
                <?php endforeach; ?>
            </div>
            <button type="button" class="button category-edit" id="service-requirement-add">+ Tambah persyaratan</button>
        </form>
    </div>
    <div class="category-dialog-footer"><a class="button" href="dashboard.php?module=services" data-service-close>Batal</a><button class="button button-primary" type="submit" form="service-form">Simpan layanan</button></div>
</dialog>
<template id="service-requirement-template">
    <fieldset class="service-requirement">
        <legend>Persyaratan</legend><input type="hidden" data-field="id" value="0">
        <label>Nama persyaratan<input data-field="name" required maxlength="300"></label>
        <label>Penjelasan<textarea data-field="description" maxlength="1500" rows="2"></textarea></label>
        <label><input type="checkbox" data-field="is_required" value="1" checked> Wajib</label>
        <button type="button" class="button button-danger-outline" data-remove-requirement>Hapus baris</button>
    </fieldset>
</template>
