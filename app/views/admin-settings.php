<?php if ($settingsError !== ''): ?><p class="admin-error" role="alert"><?= escape($settingsError) ?></p><?php endif; ?>
<?php if (isset($_GET['saved']) && $settingsError === ''): ?><p class="admin-success" role="status">Pengaturan KIOSK berhasil disimpan.</p><?php endif; ?>
<?php if ($settingsForm !== null): ?>
<section class="panel">
    <div class="panel-heading"><div><h2>Identitas tampilan publik</h2><p>Berlaku untuk seluruh KIOSK dan halaman landing. Perubahan terlihat saat halaman publik dimuat ulang.</p></div></div>
    <form method="post" enctype="multipart/form-data" class="admin-form settings-form">
        <input type="hidden" name="csrf_token" value="<?= escape(csrf_token()) ?>">
        <input type="hidden" name="version" value="<?= (int) $settingsForm['version'] ?>">
        <label for="settings-region">Nama daerah / kabupaten / kota</label>
        <input id="settings-region" name="region_name" maxlength="120" value="<?= escape($settingsForm['region_name']) ?>" placeholder="Contoh: Kabupaten Serdang Bedagai">
        <label for="settings-title">Judul aplikasi</label>
        <input id="settings-title" name="kiosk_title" maxlength="60" required value="<?= escape($settingsForm['kiosk_title']) ?>">
        <label for="settings-tagline">Tagline / petunjuk beranda</label>
        <input id="settings-tagline" name="tagline" maxlength="240" required value="<?= escape($settingsForm['tagline']) ?>">
        <label for="settings-footer">Pesan footer</label>
        <input id="settings-footer" name="footer_text" maxlength="240" required value="<?= escape($settingsForm['footer_text']) ?>">
        <label for="settings-logo">Logo daerah</label>
        <?php if ($settingsForm['logo_data'] !== ''): ?><img class="settings-logo-preview" src="<?= escape($settingsForm['logo_data']) ?>" alt="Logo daerah saat ini"><?php endif; ?>
        <input id="settings-logo" name="logo" type="file" accept="image/png,image/jpeg,image/webp" aria-describedby="settings-logo-hint">
        <p id="settings-logo-hint">PNG, JPEG, atau WebP; maksimal 512 KB dan 2048 x 2048 piksel. Kosongkan untuk mempertahankan logo. Tanpa logo, ikon bawaan digunakan.</p>
        <label><input type="checkbox" name="remove_logo" value="1"> Hapus logo saat ini</label>
        <div class="notice"><strong>Nama kecamatan otomatis</strong><p>Mengikuti kecamatan yang ditetapkan pada browser melalui Profil kecamatan &rarr; Perangkat KIOSK. Ubah nama di profil kecamatan, bukan sebagai pengaturan global. Perangkat yang belum ditetapkan tidak menampilkan nama kecamatan tertentu.</p><a href="dashboard.php?module=office">Kelola profil dan perangkat kecamatan</a></div>
        <div class="settings-actions"><button class="button button-primary" type="submit">Simpan pengaturan</button><a class="button" href="dashboard.php?module=settings">Batal</a></div>
    </form>
</section>
<?php endif; ?>
