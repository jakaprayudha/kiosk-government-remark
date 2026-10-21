<?php if ($announcementError !== ''): ?><p class="admin-error" role="alert"><?= escape($announcementError) ?></p><?php endif; ?>
<?php if ($announcementError === '' && (isset($_GET['saved']) || isset($_GET['deleted']))): ?><p class="admin-success" role="status"><?= isset($_GET['deleted']) ? 'Pengumuman dihapus.' : 'Pengumuman tersimpan. Publik KIOSK membaca perubahan saat halaman dimuat ulang.' ?></p><?php endif; ?>
<section class="panel category-panel">
    <div class="panel-heading"><div><h2>Data pengumuman (<?= count($announcementRecords) ?>)</h2><p>Pengumuman aktif ditampilkan di beranda KIOSK sesuai periode tayang (WIB).</p></div><a class="button button-primary" id="announcement-add" href="dashboard.php?module=announcements&amp;new=1" aria-haspopup="dialog" aria-controls="announcement-dialog">+ Tambah pengumuman</a></div>
    <div class="category-table"><table id="announcement-table"><thead><tr><th scope="col">Judul</th><th scope="col">Prioritas</th><th scope="col">Periode tayang (WIB)</th><th scope="col">Status</th><th scope="col">Aksi</th></tr></thead><tbody>
    <?php foreach ($announcementRecords as $record): ?><tr>
        <td><?= escape($record['title']) ?></td><td><?= (int) $record['priority'] ?></td>
        <td><?= escape($record['starts_at'] ?? 'Tanpa batas awal') ?><span class="table-note">sampai <?= escape($record['ends_at'] ?? 'Tanpa batas akhir') ?></span></td>
        <td><form method="post" action="dashboard.php?module=announcements&amp;id=<?= (int) $record['id'] ?>" class="category-status-form">
            <input type="hidden" name="csrf_token" value="<?= escape(csrf_token()) ?>"><input type="hidden" name="action" value="toggle"><input type="hidden" name="version" value="<?= (int) $record['version'] ?>">
            <label class="category-switch"><input type="checkbox" role="switch" name="is_published" value="1" aria-label="Aktifkan pengumuman <?= escape($record['title']) ?>" <?= (int) $record['is_published'] === 1 ? 'checked' : '' ?>><span class="category-switch-track" aria-hidden="true"></span><span><?= (int) $record['is_published'] === 1 ? 'Aktif' : 'Nonaktif' ?></span></label>
            <noscript><button class="button" type="submit">Simpan status</button></noscript>
        </form></td>
        <td><div class="category-actions"><a class="button category-edit" href="dashboard.php?module=announcements&amp;id=<?= (int) $record['id'] ?>" aria-haspopup="dialog" aria-controls="announcement-dialog">Ubah<span class="sr-only"> <?= escape($record['title']) ?></span></a>
            <form method="post" action="dashboard.php?module=announcements&amp;id=<?= (int) $record['id'] ?>" class="category-delete">
                <input type="hidden" name="csrf_token" value="<?= escape(csrf_token()) ?>"><input type="hidden" name="action" value="delete"><input type="hidden" name="version" value="<?= (int) $record['version'] ?>">
                <button class="button button-danger-outline" type="submit">Hapus<span class="sr-only"> <?= escape($record['title']) ?></span></button>
            </form></div></td>
    </tr><?php endforeach; ?>
    </tbody></table><noscript><p>Aktifkan JavaScript untuk pencarian, sort dan pagination.</p><?php if ($announcementRecords === []): ?><p>Belum ada pengumuman.</p><?php endif; ?></noscript></div>
</section>
<dialog id="announcement-dialog" class="category-dialog" aria-labelledby="announcement-dialog-title" <?= $announcementModalOpen ? 'open data-auto-open' : '' ?>>
    <div class="category-dialog-heading"><h2 id="announcement-dialog-title"><?= $announcementId > 0 ? 'Ubah pengumuman' : 'Tambah pengumuman' ?></h2><a class="button" data-announcement-close href="dashboard.php?module=announcements" aria-label="Tutup formulir pengumuman">Tutup</a></div>
    <div class="category-dialog-body">
        <?php if ($announcementError !== ''): ?><p class="admin-error" role="alert"><?= escape($announcementError) ?></p><?php endif; ?>
        <form method="post" class="admin-form" id="announcement-form" action="dashboard.php?module=announcements<?= $announcementId > 0 ? '&amp;id=' . $announcementId : '' ?>">
            <input type="hidden" name="csrf_token" value="<?= escape(csrf_token()) ?>"><input type="hidden" name="action" value="save"><input type="hidden" name="version" value="<?= (int) $announcementForm['version'] ?>">
            <label for="announcement-title">Judul (maksimal 180 byte)</label><input id="announcement-title" name="title" required maxlength="180" value="<?= escape($announcementForm['title']) ?>">
            <label for="announcement-body">Isi pengumuman (maksimal 10000 byte)</label><textarea id="announcement-body" name="body" required rows="8" maxlength="10000"><?= escape($announcementForm['body']) ?></textarea>
            <p>Gunakan teks biasa, bukan HTML. Jangan masukkan data pribadi. Semua isi yang ditayangkan dapat dibaca masyarakat.</p>
            <label for="announcement-priority">Prioritas (0–9999; angka besar tampil lebih awal)</label><input id="announcement-priority" name="priority" type="number" min="0" max="9999" required value="<?= (int) $announcementForm['priority'] ?>">
            <label for="announcement-start">Mulai tayang (opsional, WIB)</label><input id="announcement-start" name="starts_at" type="datetime-local" value="<?= escape($announcementForm['starts_at']) ?>">
            <label for="announcement-end">Akhir tayang (opsional, WIB)</label><input id="announcement-end" name="ends_at" type="datetime-local" value="<?= escape($announcementForm['ends_at']) ?>">
            <label class="category-switch"><input type="checkbox" role="switch" name="is_published" value="1" <?= (int) $announcementForm['is_published'] === 1 ? 'checked' : '' ?>><span class="category-switch-track" aria-hidden="true"></span><span>Aktif / tampil di publik KIOSK</span></label>
            <p>OFF menyembunyikan tanpa menghapus. ON tetap mengikuti periode tayang; batas awal/akhir kosong berarti tanpa batas. Akhir tayang mencakup menit yang dipilih. Perubahan tampil setelah beranda dimuat ulang, bukan notifikasi otomatis.</p>
        </form>
    </div>
    <div class="category-dialog-footer"><a class="button" data-announcement-close href="dashboard.php?module=announcements">Batal</a><button class="button button-primary" type="submit" form="announcement-form">Simpan pengumuman</button></div>
</dialog>
