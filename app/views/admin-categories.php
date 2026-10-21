<?php if ($categoryError !== ''): ?><p class="admin-error" role="alert"><?= escape($categoryError) ?></p><?php endif; ?>
<?php if ($categoryError === '' && (isset($_GET['saved']) || isset($_GET['deleted']))): ?><p class="admin-success" role="status"><?= isset($_GET['deleted']) ? 'Kategori dihapus.' : 'Kategori tersimpan. Perubahan berlaku saat halaman publik dimuat ulang.' ?></p><?php endif; ?>
<section class="panel category-panel">
    <div class="panel-heading">
        <div><h2>Data kategori layanan (<?= count($categoryRecords) ?>)</h2><p>Kelola kelompok layanan yang ditampilkan pada katalog publik.</p></div>
        <a class="button button-primary" id="category-add" href="dashboard.php?module=categories&amp;new=1" aria-haspopup="dialog" aria-controls="category-dialog">+ Tambah kategori</a>
    </div>
    <div class="category-table">
        <table id="category-table"><thead><tr><th scope="col">Kategori / slug</th><th scope="col">Urutan</th><th scope="col">Status</th><th scope="col">Layanan</th><th scope="col">Aksi</th></tr></thead><tbody>
            <?php foreach ($categoryRecords as $record): ?><tr>
                <td><?= escape($record['name']) ?><span class="table-note"><?= escape($record['slug']) ?></span></td>
                <td><?= (int) $record['sort_order'] ?></td>
                <td>
                    <form method="post" action="dashboard.php?module=categories&amp;id=<?= (int) $record['id'] ?>" class="category-status-form">
                        <input type="hidden" name="csrf_token" value="<?= escape(csrf_token()) ?>"><input type="hidden" name="action" value="toggle"><input type="hidden" name="version" value="<?= (int) $record['version'] ?>">
                        <label class="category-switch"><input type="checkbox" role="switch" name="is_active" value="1" aria-label="Aktifkan kategori <?= escape($record['name']) ?>" <?= (int) $record['is_active'] === 1 ? 'checked' : '' ?>><span class="category-switch-track" aria-hidden="true"></span><span><?= (int) $record['is_active'] === 1 ? 'Aktif' : 'Nonaktif' ?></span></label>
                        <noscript><button class="button" type="submit">Simpan status</button></noscript>
                    </form>
                </td>
                <td><?= (int) $record['service_count'] ?></td>
                <td><div class="category-actions"><a class="button category-edit" href="dashboard.php?module=categories&amp;id=<?= (int) $record['id'] ?>" aria-haspopup="dialog" aria-controls="category-dialog">Ubah<span class="sr-only"> <?= escape($record['name']) ?></span></a>
                    <?php if ((int) $record['service_count'] === 0): ?>
                        <form method="post" action="dashboard.php?module=categories&amp;id=<?= (int) $record['id'] ?>" class="category-delete">
                            <input type="hidden" name="csrf_token" value="<?= escape(csrf_token()) ?>"><input type="hidden" name="action" value="delete"><input type="hidden" name="version" value="<?= (int) $record['version'] ?>">
                            <button class="button button-danger-outline" type="submit">Hapus<span class="sr-only"> <?= escape($record['name']) ?></span></button>
                        </form>
                    <?php endif; ?></div>
                    <?php if ((int) $record['service_count'] > 0): ?><span class="table-note">Masih dipakai layanan.</span><?php endif; ?>
                </td>
            </tr><?php endforeach; ?>
        </tbody></table>
        <noscript><p>Aktifkan JavaScript untuk pencarian, pengurutan, dan pagination tabel.</p><?php if ($categoryRecords === []): ?><p>Belum ada kategori.</p><?php endif; ?></noscript>
    </div>
</section>
<dialog id="category-dialog" class="category-dialog" aria-labelledby="category-dialog-title" <?= ($categoryId > 0 && !isset($_GET['saved']) && ($_SERVER['REQUEST_METHOD'] ?? '') !== 'POST') || isset($_GET['new']) || ($_SERVER['REQUEST_METHOD'] ?? '') === 'POST' && ($_POST['action'] ?? '') === 'save' ? 'open data-auto-open' : '' ?>>
    <div class="category-dialog-heading">
        <h2 id="category-dialog-title"><?= $categoryId > 0 ? 'Ubah kategori layanan' : 'Tambah kategori layanan' ?></h2>
        <a class="button" data-category-close href="dashboard.php?module=categories" aria-label="Tutup formulir kategori">Tutup</a>
    </div>
    <div class="category-dialog-body">
    <?php if ($categoryError !== ''): ?><p class="admin-error" role="alert"><?= escape($categoryError) ?></p><?php endif; ?>
    <p>Kategori mengelompokkan katalog Layanan administrasi, bukan menambah menu utama KIOSK. Kategori nonaktif menyembunyikan seluruh layanan di dalamnya dari katalog, detail, dan pilihan pengajuan baru. Pengajuan lama tetap tersimpan.</p>
    <form method="post" class="admin-form" id="category-form" action="dashboard.php?module=categories<?= $categoryId > 0 ? '&amp;id=' . $categoryId : '' ?>">
        <input type="hidden" name="csrf_token" value="<?= escape(csrf_token()) ?>"><input type="hidden" name="action" value="save"><input type="hidden" name="version" value="<?= (int) $categoryForm['version'] ?>">
        <label for="category-name">Nama kategori (maksimal 120 byte)</label><input id="category-name" name="name" maxlength="120" required autofocus value="<?= escape($categoryForm['name']) ?>">
        <label for="category-slug">Slug / kode URL unik</label><input id="category-slug" name="slug" maxlength="120" pattern="[a-z0-9]+(-[a-z0-9]+)*" required value="<?= escape($categoryForm['slug']) ?>" placeholder="contoh: administrasi-umum">
        <p>Gunakan huruf kecil, angka dan tanda hubung. Mengubah slug mengganti alamat filter kategori; tautan filter lama tidak dialihkan otomatis.</p>
        <label for="category-description">Deskripsi (maksimal 1500 byte)</label><textarea id="category-description" name="description" rows="3" maxlength="1500"><?= escape($categoryForm['description']) ?></textarea>
        <label for="category-icon">Ikon kategori</label><select id="category-icon" name="icon"><?php foreach (service_category_icons() as $value => $label): ?><option value="<?= escape($value) ?>" <?= $categoryForm['icon'] === $value ? 'selected' : '' ?>><?= escape($label) ?></option><?php endforeach; ?></select>
        <label for="category-order">Urutan (0–9999, angka kecil tampil lebih awal)</label><input id="category-order" type="number" name="sort_order" min="0" max="9999" required value="<?= (int) $categoryForm['sort_order'] ?>">
        <label class="category-switch"><input id="category-active" type="checkbox" role="switch" name="is_active" value="1" <?= (int) $categoryForm['is_active'] === 1 ? 'checked' : '' ?>><span class="category-switch-track" aria-hidden="true"></span><span data-category-status-label><?= (int) $categoryForm['is_active'] === 1 ? 'Aktif' : 'Nonaktif' ?></span></label>
        <p>Nonaktif: kategori dan seluruh layanan di dalamnya tidak tampil di publik KIOSK.</p>
    </form>
    <p>Kategori aktif baru tampil di filter publik setelah memiliki layanan yang dipublikasikan dan masih berlaku. Kategori kosong tidak tampil di publik.</p>
    <a class="button" href="../kiosk.php?menu=layanan" target="_blank" rel="noopener noreferrer">Lihat katalog publik (tab baru)</a>
    </div>
    <div class="category-dialog-footer">
        <a class="button" data-category-close href="dashboard.php?module=categories">Batal</a>
        <button class="button button-primary" type="submit" form="category-form">Simpan kategori</button>
    </div>
</dialog>
