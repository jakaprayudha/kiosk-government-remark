<a class="back-link no-print" href="kiosk.php">← Kembali ke beranda</a>
<section class="detail-panel transaction-panel">
    <div class="no-print">
        <span class="detail-category">SUARA MASYARAKAT</span>
        <h1>Pengaduan & aspirasi</h1>
        <p class="detail-intro">Sampaikan kendala pelayanan atau usulan untuk kecamatan yang lebih baik. Anda boleh mengirim tanpa nama dan kontak.</p>
        <div class="service-facts">
            <div><h2>Pengaduan</h2><p>Ceritakan masalah, lokasi, dan kejadian yang perlu ditangani.</p></div>
            <div><h2>Aspirasi</h2><p>Sampaikan ide, saran, atau kebutuhan masyarakat.</p></div>
        </div>
        <nav class="complaint-tabs" aria-label="Pengaduan dan tanggapan">
            <a class="footer-button <?= $complaintTab === 'laporan' ? 'primary-action' : '' ?>" href="kiosk.php?menu=pengaduan" <?= $complaintTab === 'laporan' ? 'aria-current="page"' : '' ?>>Tulis laporan</a>
            <a class="footer-button <?= $complaintTab === 'tanggapan' ? 'primary-action' : '' ?>" href="kiosk.php?menu=pengaduan&amp;tab=tanggapan" <?= $complaintTab === 'tanggapan' ? 'aria-current="page"' : '' ?>>Cek tanggapan</a>
        </nav>
        <?php if ($complaintTab === 'laporan'): ?>
        <aside class="privacy-notice"><h2>Bukan kanal darurat</h2><p>Laporan disimpan di KIOSK untuk ditinjau admin kecamatan, bukan dikirim otomatis ke SP4N-LAPOR! atau instansi lain. Tidak ada jaminan waktu tanggapan. Untuk keadaan darurat, segera cari bantuan petugas atau layanan darurat resmi.</p><p>Jangan cantumkan NIK, kata sandi, data rekening, atau data pribadi pihak lain yang tidak diperlukan. Nama dan kontak opsional hanya dapat dilihat admin. Tanpa kontak, pantau tanggapan melalui kode dan PIN.</p><p>Data dan riwayat disimpan selama 180 hari sejak pengiriman, kemudian dihapus melalui pembersihan terjadwal oleh pengelola. Jangan gunakan perangkat bersama untuk meninggalkan salinan bukti pribadi.</p></aside>
        <?php else: ?>
            <p>Masukkan kode dan PIN pribadi dari bukti laporan untuk melihat tanggapan petugas dan riwayat tindak lanjut.</p>
        <?php endif; ?>
        <?php if ($complaintError !== ''): ?><p class="transaction-error" role="alert"><?= kiosk_escape($complaintError) ?></p><?php endif; ?>
    </div>
    <?php if ($complaintReceipt): ?>
        <article class="receipt-paper" aria-label="Bukti laporan">
            <h2>Laporan berhasil tersimpan</h2><p><?= kiosk_escape(complaint_kinds()[$complaintReceipt['kind']]) ?> Anda menunggu peninjauan petugas.</p>
            <p>Kode laporan <strong class="ticket-code"><?= kiosk_escape($complaintReceipt['public_code']) ?></strong></p>
            <p>PIN pribadi <strong class="ticket-pin"><?= kiosk_escape($complaintReceipt['pin']) ?></strong></p>
            <p>Simpan kode dan PIN secara pribadi untuk cek tanggapan pada menu Pengaduan & aspirasi, bukan menu Status pengajuan layanan.</p>
            <div class="action-buttons no-print"><button class="footer-button primary-action" type="button" data-print>Cetak bukti laporan</button><a class="footer-button" href="kiosk.php?menu=pengaduan">Tulis laporan baru</a></div>
        </article>
    <?php endif; ?>
    <?php if ($complaintResult): ?>
        <section class="status-result" aria-label="Tanggapan laporan">
            <h2>Tanggapan & riwayat laporan</h2><p class="ticket-code"><?= kiosk_escape($complaintResult['public_code']) ?></p>
            <p><?= kiosk_escape(complaint_kinds()[$complaintResult['kind']]) ?> · <?= kiosk_escape(complaint_categories()[$complaintResult['category']]) ?></p>
            <p>Status: <strong><?= kiosk_escape(complaint_statuses()[$complaintResult['status']]) ?></strong></p>
            <ol class="status-timeline">
                <?php foreach ($complaintResult['events'] as $index => $event): ?>
                    <li <?= $index === array_key_last($complaintResult['events']) ? 'aria-current="step"' : '' ?>><span class="status-dot" aria-hidden="true"></span><div>
                        <strong><?= kiosk_escape(complaint_statuses()[$event['new_status']]) ?></strong>
                        <?php if ($index === array_key_last($complaintResult['events'])): ?><span class="menu-status">Terbaru</span><?php endif; ?>
                        <time><?= kiosk_escape((new DateTimeImmutable($event['created_at'], new DateTimeZone('UTC')))->setTimezone(new DateTimeZone('Asia/Jakarta'))->format('d/m/Y H:i:s')) ?> WIB</time>
                        <p class="multiline"><?= kiosk_escape($event['public_message']) ?></p>
                    </div></li>
                <?php endforeach; ?>
            </ol>
        </section>
    <?php endif; ?>
    <div class="no-print">
        <?php if ($complaintTab === 'laporan' && !$complaintReceipt): ?>
            <form method="post" class="submission-form" id="complaint-form" data-private-form autocomplete="off">
                <h2>Tulis laporan Anda</h2>
                <input type="hidden" name="csrf" value="<?= kiosk_escape($_SESSION['csrf']) ?>">
                <input type="hidden" name="action" value="create">
                <input type="hidden" name="request_key" value="<?= kiosk_escape($complaintKey) ?>">
                <label for="complaint-kind">Jenis laporan *</label>
                <select id="complaint-kind" name="kind" required><?php foreach (complaint_kinds() as $value => $label): ?><option value="<?= kiosk_escape($value) ?>" <?= $complaintInput['kind'] === $value ? 'selected' : '' ?>><?= kiosk_escape($label) ?></option><?php endforeach; ?></select>
                <label for="complaint-category">Kategori *</label>
                <select id="complaint-category" name="category" required><?php foreach (complaint_categories() as $value => $label): ?><option value="<?= kiosk_escape($value) ?>" <?= $complaintInput['category'] === $value ? 'selected' : '' ?>><?= kiosk_escape($label) ?></option><?php endforeach; ?></select>
                <?php foreach (['title' => ['Judul laporan *', 160], 'location' => ['Lokasi / desa (opsional)', 240], 'name' => ['Nama (opsional, kosongkan untuk tanpa nama)', 120], 'contact' => ['Telepon / email (opsional)', 120]] as $key => [$label, $limit]): ?>
                    <label for="complaint-<?= $key ?>"><?= kiosk_escape($label) ?></label>
                    <input id="complaint-<?= $key ?>" name="<?= $key ?>" maxlength="<?= $limit ?>" value="<?= kiosk_escape($complaintInput[$key]) ?>" <?= $key === 'title' ? 'required' : '' ?>>
                <?php endforeach; ?>
                <label for="complaint-description">Uraian laporan *</label>
                <textarea id="complaint-description" name="description" rows="6" maxlength="3000" required placeholder="Jelaskan apa yang terjadi atau usulan Anda, lokasi, waktu bila relevan, dan harapan tindak lanjut."><?= kiosk_escape($complaintInput['description']) ?></textarea>
                <p>Batas judul 160, lokasi 240, nama/kontak 120, dan uraian 3000 byte (karakter non-ASCII dapat memakai lebih dari satu byte). Periksa kembali sebelum mengirim. Tidak perlu melampirkan foto atau dokumen identitas.</p>
                <label class="check-label"><input type="checkbox" name="consent" value="1" required> Saya memahami penggunaan data untuk tindak lanjut laporan dan retensi 180 hari.</label>
                <label class="check-label"><input type="checkbox" name="confirmed" value="1" required> Saya sudah memeriksa isi laporan dan tidak menyertakan data pribadi yang tidak diperlukan.</label>
                <button class="footer-button primary-action" type="submit">Kirim laporan</button>
            </form>
        <?php elseif ($complaintTab === 'tanggapan'): ?>
        <form method="post" action="kiosk.php?menu=pengaduan&amp;tab=tanggapan" class="lookup-form" id="complaint-lookup" data-private-form autocomplete="off">
            <h2>Cek tanggapan laporan</h2><p>Gunakan kode berawalan AD- dan PIN pada bukti laporan. Tanggapan tidak dipublikasikan dalam daftar terbuka.</p>
            <input type="hidden" name="csrf" value="<?= kiosk_escape($_SESSION['csrf']) ?>"><input type="hidden" name="action" value="lookup">
            <label for="complaint-ticket">Kode laporan</label><input id="complaint-ticket" name="ticket" maxlength="23" pattern="[Aa][Dd]-[A-Fa-f0-9]{20}" placeholder="AD- diikuti 20 karakter" required spellcheck="false" autocapitalize="characters">
            <label for="complaint-pin">PIN enam digit</label><input id="complaint-pin" name="pin" type="password" inputmode="numeric" maxlength="6" pattern="[0-9]{6}" required>
            <button class="footer-button" type="submit">Lihat tanggapan</button>
        </form>
        <?php endif; ?>
        <?php require __DIR__ . '/auth-keyboard.php'; ?>
    </div>
</section>
