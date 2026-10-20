<a class="back-link no-print" href="kiosk.php?menu=layanan">← Kembali ke daftar layanan</a>
<?php if ($transactionError !== ''): ?><p class="transaction-error" role="alert"><?= kiosk_escape($transactionError) ?> Foto dan konfirmasi perlu diperiksa ulang sebelum mengirim kembali.</p><?php endif; ?>

<?php if ($selected === 'pengajuan' && $transactionService !== null && $formKey !== ''): ?>
    <section class="detail-panel transaction-panel">
        <span class="detail-category">PENGAJUAN LAYANAN</span>
        <h1><?= kiosk_escape($transactionService['name']) ?></h1>
        <?php if ((int) $transactionService['is_demo'] === 1): ?><p class="demo-banner">SIMULASI — gunakan nama, kontak, dan dokumen contoh saja. Bukan pengajuan resmi.</p><?php endif; ?>
        <div class="privacy-notice"><h2>Sebelum melanjutkan</h2><p>Data dan foto yang Anda kirim hanya untuk pemeriksaan layanan oleh petugas berwenang. Nama/kontak hanya wajib jika ditandai. Jangan sertakan NIK, nomor KK, OTP, atau foto identitas yang tidak diminta.         Masa akses data <?= (int) $transactionService['retention_days'] ?> hari sejak pengajuan; pengelola melakukan penghapusan berkala setelah masa retensi. Foto dokumen bersifat opsional; dokumen wajib yang tidak difoto tetap harus dibawa ke petugas.</p><p>Jika tidak ingin menyimpan data di KIOSK, batalkan dan temui petugas.</p></div>
        <form class="submission-form" method="post" enctype="multipart/form-data" id="submission-form">
            <input type="hidden" name="csrf" value="<?= kiosk_escape($_SESSION['csrf']) ?>">
            <input type="hidden" name="request_key" value="<?= kiosk_escape($formKey) ?>">
            <label class="check-label"><input type="checkbox" name="consent" value="1" required> Saya memahami pemberitahuan privasi dan menyetujui pengiriman data untuk layanan ini.</label>
            <label for="applicant-name">Nama <?= (int) $transactionService['requires_name'] === 1 ? '(wajib)' : '(opsional)' ?></label>
            <input id="applicant-name" name="name" maxlength="120" autocomplete="off" <?= (int) $transactionService['requires_name'] === 1 ? 'required' : '' ?> value="<?= kiosk_escape($input['name']) ?>">
            <label for="applicant-contact">Kontak <?= (int) $transactionService['requires_contact'] === 1 ? '(wajib)' : '(opsional)' ?></label>
            <input id="applicant-contact" name="contact" maxlength="120" autocomplete="off" <?= (int) $transactionService['requires_contact'] === 1 ? 'required' : '' ?> value="<?= kiosk_escape($input['contact']) ?>">
            <label for="request-description">Uraian kebutuhan (wajib)</label>
            <textarea id="request-description" name="description" rows="4" maxlength="3000" required autocomplete="off"><?= kiosk_escape($input['description']) ?></textarea>
            <p>Maksimal 3000 byte. Jangan masukkan data pribadi yang tidak diperlukan.</p>
            <?php require __DIR__ . '/auth-keyboard.php'; ?>

            <?php if ((int) $transactionService['camera_enabled'] === 1 && $transactionRequirements !== []): ?>
                <section class="camera-section no-print">
                    <h2>Foto dokumen (opsional)</h2>
                    <p>Maksimal 3 foto, masing-masing 2 MB. Jika kamera tidak tersedia, bawa dokumen ke petugas. Tidak ada verifikasi otomatis keaslian dokumen.</p>
                    <label for="photo-requirement">Dokumen yang akan difoto</label>
                    <select id="photo-requirement">
                        <?php foreach ($transactionRequirements as $requirement): ?><option value="<?= (int) $requirement['id'] ?>"><?= kiosk_escape($requirement['name']) ?> — <?= (int) $requirement['is_required'] === 1 ? 'dokumen wajib dibawa' : 'dokumen opsional' ?></option><?php endforeach; ?>
                    </select>
                    <div class="action-buttons"><button class="footer-button" type="button" id="start-camera">Aktifkan kamera</button><button class="footer-button" type="button" id="stop-camera" hidden>Matikan kamera</button></div>
                    <video id="document-camera" autoplay playsinline muted hidden aria-label="Pratinjau kamera dokumen"></video>
                    <button class="footer-button" type="button" id="capture-photo" hidden>Ambil foto</button>
                    <div id="capture-preview" hidden><img alt="Pratinjau foto sebelum digunakan"><div class="action-buttons"><button class="footer-button" type="button" id="accept-photo">Gunakan foto</button><button class="footer-button" type="button" id="retake-photo">Ulangi</button></div></div>
                    <div id="photo-list"></div>
                    <p id="camera-message" class="device-message" role="status"></p>
                </section>
            <?php endif; ?>
            <div class="action-buttons"><button class="footer-button" type="button" id="review-submission">Periksa ringkasan</button></div>
            <section id="submission-review" hidden><h2>Periksa kembali</h2><dl id="review-fields"></dl><p id="review-photos"></p><p>Dokumen wajib yang tidak difoto tetap dibawa ke petugas. Klik Periksa ringkasan lagi setelah mengubah data.</p><label class="check-label"><input type="checkbox" name="confirmed" value="1" required> Saya sudah memeriksa isian dan foto; data siap dikirim.</label><button class="footer-button primary-action" type="submit" id="send-submission">Kirim <?= (int) $transactionService['is_demo'] === 1 ? 'simulasi' : 'pengajuan' ?></button></section>
            <p class="device-message" id="submission-message" role="status"></p>
        </form>
    </section>
<?php elseif ($selected === 'bukti' && $receipt): ?>
    <article class="detail-panel receipt-paper">
        <span class="detail-category">BUKTI TERSIMPAN DI KIOSK</span>
        <?php if ((int) $receipt['is_demo'] === 1): ?><p class="demo-banner">SIMULASI DEMO — BUKAN BUKTI LAYANAN RESMI</p><?php endif; ?>
        <h1>Pengajuan tersimpan</h1><p><?= kiosk_escape($receipt['service_name']) ?></p>
        <p>Waktu: <?= kiosk_escape((new DateTimeImmutable($receipt['created_at'], new DateTimeZone('UTC')))->setTimezone(new DateTimeZone('Asia/Jakarta'))->format('d/m/Y H:i:s')) ?> WIB</p>
        <h2>Kode tiket</h2><strong class="ticket-code"><?= kiosk_escape($receipt['public_code']) ?></strong>
        <canvas data-qr="ticket" data-ticket="<?= kiosk_escape($receipt['public_code']) ?>" aria-label="QR tiket pengajuan"></canvas>
        <p>PIN cek status: <strong class="ticket-pin"><?= kiosk_escape($_SESSION['receipts'][$receipt['public_code']]) ?></strong></p>
        <p>Simpan bukti ini secara pribadi. QR hanya berisi kode tiket, bukan PIN. Gunakan scanner KIOSK dan masukkan PIN untuk cek status.</p>
        <p>Status: <strong><?= kiosk_escape(submission_statuses()[$receipt['status']]) ?></strong>. Tersimpan bukan berarti disetujui; tunggu pemeriksaan petugas.</p>
        <div class="action-buttons no-print"><button class="footer-button primary-action" type="button" data-print>Cetak / cetak ulang bukti</button></div>
        <p class="device-message no-print" role="status">Pilih printer pada dialog cetak. Jika gagal, tiket tetap tersimpan; catat kode dan PIN sebelum mengakhiri sesi.</p>
    </article>
<?php elseif ($selected === 'status'): ?>
    <section class="detail-panel">
        <h1>Scan & cek tiket pengajuan</h1><p>Gunakan scanner QR perangkat, kamera, atau ketik kode tiket. Tetap masukkan PIN enam digit dari bukti.</p>
        <form method="post" class="lookup-form" id="ticket-form">
            <input type="hidden" name="csrf" value="<?= kiosk_escape($_SESSION['csrf']) ?>">
            <label for="ticket-code">Kode tiket / hasil scan</label><input id="ticket-code" name="ticket" maxlength="40" autocomplete="off" autocapitalize="characters" required placeholder="Arahkan scanner setelah menyentuh kolom ini">
            <label for="ticket-pin">PIN enam digit</label><input id="ticket-pin" name="pin" type="password" inputmode="numeric" maxlength="6" pattern="[0-9]{6}" autocomplete="off" required>
            <button class="footer-button primary-action" type="submit">Cek status</button>
        </form>
        <?php require __DIR__ . '/auth-keyboard.php'; ?>
        <div class="action-buttons"><button class="footer-button" id="start-scanner" type="button">Scan QR dengan kamera</button><button class="footer-button" id="stop-scanner" type="button" hidden>Matikan scanner</button></div>
        <video id="qr-camera" hidden playsinline muted aria-label="Kamera pemindai QR"></video>
        <p id="scanner-message" class="device-message" role="status"></p>
        <?php if ($ticketStatus): ?>
            <section class="status-result" aria-label="Hasil cek status">
                <?php if ((int) $ticketStatus['is_demo'] === 1): ?><p class="demo-banner">TIKET SIMULASI DEMO</p><?php endif; ?>
                <h2><?= kiosk_escape($ticketStatus['service_name']) ?></h2><p>Kode: <?= kiosk_escape($ticketStatus['public_code']) ?></p><p>Status: <strong><?= kiosk_escape(submission_statuses()[$ticketStatus['status']]) ?></strong></p>
                <p class="multiline"><?= kiosk_escape($ticketStatus['public_note']) ?></p>
                <h3>Riwayat publik</h3>
                <ol class="guide-list"><?php foreach ($ticketEvents as $event): ?><li><div><strong><?= kiosk_escape(submission_statuses()[$event['new_status']]) ?></strong><p class="multiline"><?= kiosk_escape($event['public_message']) ?></p><small><?= kiosk_escape((new DateTimeImmutable($event['created_at'], new DateTimeZone('UTC')))->setTimezone(new DateTimeZone('Asia/Jakarta'))->format('d/m/Y H:i')) ?> WIB</small></div></li><?php endforeach; ?></ol>
                <p>Perlu melengkapi informasi? Hubungi petugas dan bawa kode tiket. Pelengkapan daring belum tersedia.</p>
            </section>
        <?php endif; ?>
    </section>
<?php endif; ?>
