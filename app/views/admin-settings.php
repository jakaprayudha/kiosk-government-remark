<?php if ($settingsError !== ''): ?><p class="admin-error" role="alert"><?= escape($settingsError) ?></p><?php endif; ?>
<?php if (isset($_GET['saved']) && $settingsError === ''): ?><p class="admin-success" role="status">Pengaturan KIOSK berhasil disimpan.</p><?php endif; ?>
<?php if ($settingsForm !== null): ?>
<section class="panel">
    <div class="panel-heading"><div><h2>Identitas tampilan publik</h2><p>Berlaku untuk seluruh KIOSK dan halaman landing. Perubahan terlihat saat halaman publik dimuat ulang.</p></div></div>
    <form method="post" enctype="multipart/form-data" class="settings-form">
        <input type="hidden" name="csrf_token" value="<?= escape(csrf_token()) ?>">
        <input type="hidden" name="version" value="<?= (int) $settingsForm['version'] ?>">
        <div class="settings-layout">
        <section class="settings-section" aria-labelledby="settings-identity-heading">
        <h3 id="settings-identity-heading">Identitas &amp; pesan KIOSK</h3>
        <p class="settings-hint">Sesuaikan nama dan pesan yang dibaca pengguna pada tampilan publik.</p>
        <div class="settings-fields">
        <div class="settings-field">
        <label for="settings-region">Nama daerah / kabupaten / kota</label>
        <input id="settings-region" name="region_name" maxlength="120" value="<?= escape($settingsForm['region_name']) ?>" placeholder="Contoh: Kabupaten Serdang Bedagai">
        </div>
        <div class="settings-field">
        <label for="settings-title">Judul aplikasi</label>
        <input id="settings-title" name="kiosk_title" maxlength="60" required value="<?= escape($settingsForm['kiosk_title']) ?>">
        </div>
        <div class="settings-field settings-field-wide">
        <label for="settings-tagline">Tagline / petunjuk beranda</label>
        <textarea id="settings-tagline" name="tagline" maxlength="240" rows="3" required><?= escape($settingsForm['tagline']) ?></textarea>
        </div>
        <div class="settings-field settings-field-wide">
        <label for="settings-footer">Pesan footer</label>
        <textarea id="settings-footer" name="footer_text" maxlength="240" rows="3" required><?= escape($settingsForm['footer_text']) ?></textarea>
        </div>
        </div>
        </section>
        <section class="settings-section settings-logo-section" aria-labelledby="settings-logo-heading">
        <h3 id="settings-logo-heading">Logo daerah</h3>
        <div class="settings-logo-box">
        <?php if ($settingsForm['logo_data'] !== ''): ?>
            <img class="settings-logo-preview" src="<?= escape($settingsForm['logo_data']) ?>" alt="Logo daerah saat ini">
        <?php else: ?>
            <span class="settings-logo-empty">Belum ada logo<span>Ikon bawaan digunakan</span></span>
        <?php endif; ?>
        </div>
        <div class="settings-field">
        <label for="settings-logo">Logo daerah</label>
        <input id="settings-logo" name="logo" type="file" accept="image/png,image/jpeg,image/webp" aria-describedby="settings-logo-hint">
        <p id="settings-logo-hint" class="settings-hint">PNG, JPEG, atau WebP; maksimal 512 KB dan 2048 x 2048 piksel. Kosongkan untuk mempertahankan logo.</p>
        </div>
        <label class="settings-remove-logo"><input type="checkbox" name="remove_logo" value="1"> Hapus logo saat ini</label>
        </section>
        </div>
        <aside class="settings-office-note"><strong>Nama kecamatan otomatis</strong><p>Mengikuti kecamatan yang ditetapkan pada browser melalui Profil kecamatan &rarr; Perangkat KIOSK. Ubah nama di profil kecamatan, bukan sebagai pengaturan global. Perangkat yang belum ditetapkan tidak menampilkan nama kecamatan tertentu.</p><a href="dashboard.php?module=office">Kelola profil dan perangkat kecamatan &rarr;</a></aside>
        <div class="settings-actions"><span>Berlaku untuk seluruh perangkat KIOSK.</span><div><a class="button settings-cancel" href="dashboard.php?module=settings">Batal</a><button class="button button-primary" type="submit">Simpan pengaturan</button></div></div>
    </form>
</section>
<?php endif; ?>
