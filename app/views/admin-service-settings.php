<?php if ($settingsError !== ''): ?><p class="admin-error" role="alert"><?= escape($settingsError) ?></p><?php endif; ?>
<?php if (isset($_GET['saved'])): ?><p class="admin-success" role="status">Pengaturan tersimpan.</p><?php endif; ?>
<section class="panel admin-detail">
    <h2>Pengaturan transaksi per layanan</h2>
    <p>Aktifkan pengajuan hanya untuk layanan yang berwenang diproses kecamatan. Minta data minimum dan tetapkan retensi sesuai kebijakan. CRUD konten layanan/kategori/persyaratan tetap menyusul.</p>
    <?php if ($configuredServices === []): ?><p>Belum ada layanan. Isi data resmi melalui SQLite atau jalankan seed DEMO untuk pengujian.</p><?php endif; ?>
    <?php foreach ($configuredServices as $configured): ?>
        <details class="service-setting"><summary><?= escape($configured['name']) ?></summary>
            <form method="post" class="admin-form">
                <input type="hidden" name="csrf_token" value="<?= escape(csrf_token()) ?>">
                <input type="hidden" name="service_id" value="<?= (int) $configured['id'] ?>">
                <?php foreach (['accepts_submissions' => 'Terima pengajuan di KIOSK', 'requires_name' => 'Nama wajib', 'requires_contact' => 'Kontak wajib', 'camera_enabled' => 'Izinkan foto dokumen persyaratan (opsional)'] as $key => $label): ?>
                    <label><input type="checkbox" name="<?= escape($key) ?>" value="1" <?= (int) $configured[$key] === 1 ? 'checked' : '' ?>> <?= escape($label) ?></label>
                <?php endforeach; ?>
                <label>Retensi pengajuan baru (1–365 hari)<input type="number" name="retention_days" min="1" max="365" required value="<?= (int) $configured['retention_days'] ?>"></label>
                <p>Perubahan retensi berlaku untuk pengajuan baru. Jalankan tugas penghapusan retensi setiap hari.</p>
                <button class="button button-primary" type="submit">Simpan pengaturan</button>
            </form>
        </details>
    <?php endforeach; ?>
</section>
