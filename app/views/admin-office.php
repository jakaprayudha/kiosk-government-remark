<?php if ($officeAdminError !== ''): ?><p class="admin-error" role="alert"><?= escape($officeAdminError) ?></p><?php endif; ?>
<?php if ($officeAdminError === '' && isset($_GET['saved'])): ?><p class="admin-success" role="status"><?= $officeTab === 'accounts' ? 'Perubahan akun admin kecamatan tersimpan.' : 'Perubahan tersimpan. Publik KIOSK membaca perubahan saat halaman dimuat ulang.' ?></p><?php endif; ?>
<section class="panel admin-detail">
    <h2><?= $officeAccountsPage ? 'Akun admin per kecamatan' : 'Pengelolaan kecamatan' ?></h2>
    <p><?= $officeAccountsPage ? 'Khusus admin pusat. Pilih kecamatan untuk menambah akun, mengganti password, mengaktifkan atau menonaktifkan akses. Akun tetap terikat pada kecamatan asal dan tidak dapat dipromosikan menjadi admin pusat melalui menu ini.' : 'Tahap ini hanya profil, desa dan sarana yang terpisah per kecamatan. Layanan, pengumuman, pengajuan, pengaduan dan antrean masih bersama; admin kecamatan tidak dapat mengakses modul tersebut.' ?></p>
    <?php if ($officeScope === null): ?><form method="get" class="admin-filter">
        <input type="hidden" name="module" value="<?= escape($officeModule) ?>"><input type="hidden" name="tab" value="<?= escape($officeTab) ?>">
        <label for="office-selector">Kecamatan yang dikelola</label><select name="office_id" id="office-selector" data-auto-submit><option value="0">Pilih kecamatan</option><?php foreach ($officeOptions as $option): ?><option value="<?= (int) $option['id'] ?>" <?= $managedOfficeId === (int) $option['id'] ? 'selected' : '' ?>><?= escape($option['name']) ?></option><?php endforeach; ?></select><noscript><button class="button button-primary">Tampilkan</button></noscript>
    </form><?php else: ?><p><strong><?= escape($officeOptions[0]['name'] ?? 'Kecamatan Anda') ?></strong> — akses khusus wilayah ini.</p><?php endif; ?>
    <?php if ($officeAccountsPage): ?>
        <?php if ($officeOptions === []): ?><p>Belum ada kecamatan. Tambahkan profil kecamatan terlebih dahulu.</p><?php endif; ?>
        <a class="button category-edit" href="dashboard.php?module=office<?= $managedOfficeId > 0 ? '&amp;office_id=' . $managedOfficeId : '' ?>">Kelola profil kecamatan</a>
    <?php else: ?>
    <nav class="office-tabs" aria-label="Data kecamatan">
        <?php foreach ($officeDefinitions as $key => $definition): ?>
            <?php if ($officeScope !== null && in_array($key, ['accounts', 'devices'], true)) continue; ?>
            <a class="button <?= $officeTab === $key ? 'button-primary' : 'category-edit' ?>" href="dashboard.php?module=office&amp;tab=<?= escape($key) ?>&amp;office_id=<?= $managedOfficeId ?>" <?= $officeTab === $key ? 'aria-current="page"' : '' ?>><?= escape($definition['label']) ?></a>
        <?php endforeach; ?>
    </nav>
    <?php endif; ?>
</section>
<?php if ($officeTab !== 'profiles' && $managedOfficeId === 0): ?>
    <p class="notice" role="status">Pilih kecamatan terlebih dahulu untuk mengelola <?= $officeAccountsPage ? 'akun admin wilayahnya' : 'data wilayah' ?>.</p>
<?php else: ?>
<section class="panel category-panel">
    <div class="panel-heading"><div><h2><?= escape($officeDefinitions[$officeTab]['label']) ?> (<?= count($officeRecords) ?>)</h2><p>ON/OFF langsung menyimpan; form tambah/ubah melalui modal.</p></div>
        <?php if ($officeTab !== 'devices' && ($officeTab !== 'profiles' || $officeScope === null)): ?><a id="office-add" class="button button-primary" href="<?= escape($officeBaseUrl) ?>&amp;new=1" aria-haspopup="dialog" aria-controls="office-dialog">+ Tambah data</a><?php endif; ?>
    </div>
    <?php if ($officeTab === 'devices'): ?>
        <div class="admin-detail"><p>Jalankan di browser perangkat KIOSK fisik yang ingin ditetapkan. Cookie khusus menentukan profil publik pada browser ini selama 1 tahun; tidak berubah saat logout atau sesi publik dibersihkan. Jika cookie dihapus/browser diganti, tetapkan ulang. Admin kecamatan tidak dapat mengubah penetapan. Jangan daftarkan komputer pribadi sebagai KIOSK kecuali untuk pengujian.</p>
            <form method="post" action="<?= escape($officeBaseUrl) ?>" class="admin-form"><input type="hidden" name="csrf_token" value="<?= escape(csrf_token()) ?>"><input type="hidden" name="action" value="bind"><label>Nama perangkat / lokasi<input name="name" required maxlength="180"></label><button class="button button-primary">Tetapkan browser ini ke kecamatan terpilih</button></form>
            <p><a class="button category-edit" href="../kiosk.php?menu=kantor" target="_blank" rel="noopener noreferrer">Lihat profil publik browser ini (tab baru)</a></p>
        </div>
    <?php elseif ($officeTab === 'accounts'): ?><p class="admin-detail">Login melalui halaman admin yang sama. Akun hanya bisa mengelola profil, desa dan sarana kecamatan terpilih. Password tidak ditampilkan/disimpan ulang pada error; penggantian password oleh admin pusat di sini. Tidak ada kode pemulihan otomatis untuk akun baru.</p><?php endif; ?>
    <div class="category-table"><table id="office-table"><thead><tr><th scope="col">Nama</th><th scope="col"><?= $officeTab === 'accounts' ? 'Username / kecamatan' : 'Keterangan' ?></th><th scope="col">Status</th><th scope="col">Aksi</th></tr></thead><tbody>
        <?php foreach ($officeRecords as $record): ?>
            <?php $key = in_array($officeTab, ['accounts', 'devices'], true) ? 'is_active' : 'is_published'; $rowUrl = $officeBaseUrl . '&id=' . (int) $record['id']; ?>
            <tr><td><?= escape($record['name']) ?></td><td><?= escape($record['username'] ?? $record['slug'] ?? 'Perangkat terdaftar') ?><?php if ($officeTab === 'accounts'): ?><?php foreach ($officeOptions as $option): if ((int) $option['id'] !== $managedOfficeId) continue; ?><span class="table-note"><?= escape($option['name']) ?></span><?php endforeach; ?><?php elseif ($officeTab === 'profiles'): ?><span class="table-note"><?= escape($record['regency']) ?></span><?php endif; ?></td>
                <td><form method="post" action="<?= escape($rowUrl) ?>" class="category-status-form"><input type="hidden" name="csrf_token" value="<?= escape(csrf_token()) ?>"><input type="hidden" name="action" value="toggle"><input type="hidden" name="version" value="<?= (int) $record['version'] ?>">
                    <label class="category-switch"><input type="checkbox" role="switch" name="<?= escape($key) ?>" value="1" aria-label="Aktifkan <?= escape($record['name']) ?>" <?= (int) $record[$key] === 1 ? 'checked' : '' ?>><span class="category-switch-track" aria-hidden="true"></span><span><?= (int) $record[$key] === 1 ? 'Aktif' : 'Nonaktif' ?></span></label><noscript><button class="button">Simpan status</button></noscript>
                </form></td>
                <td><div class="category-actions">
                    <?php if ($officeTab !== 'devices'): ?><a class="button category-edit" href="<?= escape($rowUrl) ?>" aria-haspopup="dialog" aria-controls="office-dialog">Ubah<span class="sr-only"> <?= escape($record['name']) ?></span></a><?php endif; ?>
                    <?php if ($officeTab !== 'profiles' || $officeScope === null): ?><form method="post" action="<?= escape($rowUrl) ?>" class="category-delete"><input type="hidden" name="csrf_token" value="<?= escape(csrf_token()) ?>"><input type="hidden" name="action" value="delete"><input type="hidden" name="version" value="<?= (int) $record['version'] ?>"><button class="button button-danger-outline">Hapus<span class="sr-only"> <?= escape($record['name']) ?></span></button></form><?php endif; ?>
                    <?php if ($officeTab === 'profiles'): ?><a class="button category-edit" href="dashboard.php?module=office&amp;tab=villages&amp;office_id=<?= (int) $record['id'] ?>">Kelola wilayah</a><?php endif; ?>
                </div></td>
            </tr>
        <?php endforeach; ?>
    </tbody></table><noscript><p>Aktifkan JavaScript untuk search, sort dan pagination.</p></noscript></div>
</section>
<?php if ($officeTab !== 'devices'): ?>
<dialog id="office-dialog" class="category-dialog" aria-labelledby="office-dialog-title" <?= $officeModalOpen ? 'open data-auto-open' : '' ?>>
    <div class="category-dialog-heading"><h2 id="office-dialog-title"><?= $officeRecordId > 0 ? 'Ubah ' : 'Tambah ' ?><?= escape($officeDefinitions[$officeTab]['label']) ?></h2><a class="button" href="<?= escape($officeBaseUrl) ?>" data-office-close>Tutup</a></div>
    <div class="category-dialog-body">
        <?php if ($officeAdminError !== ''): ?><p class="admin-error" role="alert"><?= escape($officeAdminError) ?></p><?php endif; ?>
        <form method="post" class="admin-form" id="office-form" action="<?= escape($officeBaseUrl . ($officeRecordId > 0 ? '&id=' . $officeRecordId : '')) ?>">
            <input type="hidden" name="csrf_token" value="<?= escape(csrf_token()) ?>"><input type="hidden" name="action" value="save"><input type="hidden" name="version" value="<?= (int) $officeForm['version'] ?>">
            <?php foreach ($officeDefinitions[$officeTab]['fields'] as $key => $field): ?>
                <?php [$label, $type] = $field; $value = $officeForm[$key]; ?>
                <?php if ($type === 'flag'): ?><label class="category-switch"><input type="checkbox" role="switch" name="<?= escape($key) ?>" value="1" <?= (int) $value === 1 ? 'checked' : '' ?>><span class="category-switch-track" aria-hidden="true"></span><span><?= escape($label) ?></span></label>
                <?php elseif ($type === 'category'): ?><label><?= escape($label) ?><select name="<?= escape($key) ?>"><?php foreach (office_facility_categories() as $categoryKey => $category): ?><option value="<?= escape($categoryKey) ?>" <?= $value === $categoryKey ? 'selected' : '' ?>><?= escape($category['label']) ?></option><?php endforeach; ?></select></label>
                <?php elseif ($type === 'textarea'): ?><label><?= escape($label) ?> (maks. <?= $field[2] ?> byte)<textarea name="<?= escape($key) ?>" rows="3" maxlength="<?= $field[2] ?>"><?= escape((string) $value) ?></textarea></label>
                <?php else: ?><label><?= escape($label) ?><input name="<?= escape($key) ?>" type="<?= in_array($type, ['integer', 'latitude', 'longitude'], true) ? 'number' : ($type === 'password' ? 'password' : ($type === 'url' ? 'url' : 'text')) ?>" value="<?= $type === 'password' ? '' : escape((string) $value) ?>"
                    <?= $type === 'password' ? 'autocomplete="new-password"' : '' ?>
                    <?= $type === 'integer' ? 'min="' . $field[2] . '" max="' . $field[3] . '"' : '' ?>
                    <?= in_array($type, ['latitude', 'longitude'], true) ? 'step="any" min="-' . ($type === 'latitude' ? 90 : 180) . '" max="' . ($type === 'latitude' ? 90 : 180) . '"' : '' ?>
                    <?= isset($field[2]) && !in_array($type, ['integer'], true) ? 'maxlength="' . $field[2] . '"' : '' ?>
                    <?= in_array($type, ['required', 'slug', 'username', 'integer', 'latitude', 'longitude'], true) || $key === 'name' || ($type === 'password' && $officeRecordId === 0) ? 'required' : '' ?>></label>
                <?php endif; ?>
            <?php endforeach; ?>
            <p><?= $officeTab === 'accounts' ? 'OFF menghentikan akses login dan sesi admin pada permintaan berikutnya tanpa menghapus akun. Password kosong saat ubah mempertahankan password lama. Akun hanya mengelola profil, desa dan sarana wilayahnya; tidak dapat membuka modul pusat.' : 'OFF tidak menghapus data. Profil OFF menyembunyikan seluruh desa/sarananya di publik. Pastikan koordinat benar; DEMO hanya untuk data contoh. Kode desa/sarana unik dalam kecamatan yang sama.' ?></p>
        </form>
    </div>
    <div class="category-dialog-footer"><a class="button" data-office-close href="<?= escape($officeBaseUrl) ?>">Batal</a><button class="button button-primary" type="submit" form="office-form">Simpan</button></div>
</dialog>
<script type="application/json" id="office-form-defaults"><?= json_encode(office_master_defaults($officeTab), JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_THROW_ON_ERROR) ?></script>
<?php endif; ?>
<?php endif; ?>
