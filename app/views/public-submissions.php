<a class="back-link no-print" href="<?= $selected === 'pengajuan' && $transactionService !== null ? 'kiosk.php?menu=pengajuan' : 'kiosk.php' ?>">← <?= $selected === 'pengajuan' && $transactionService !== null ? 'Pilih layanan lain' : 'Kembali ke beranda' ?></a>
<?php if ($transactionError !== ''): ?><p class="transaction-error" role="alert"><?= kiosk_escape($transactionError) ?><?= $selected === 'pengajuan' ? ' Foto dan konfirmasi perlu diperiksa ulang sebelum mengirim kembali.' : '' ?></p><?php endif; ?>

<?php if ($selected === 'pengajuan' && $transactionService === null && $transactionError === ''): ?>
    <section class="catalog-heading"><span class="eyebrow">PENGAJUAN LAYANAN</span><h1>Pilih layanan yang ingin diajukan</h1><p>Pilih layanan → isi identitas sesuai kebutuhan → ambil foto jika diminta → periksa dan kirim. Surat final dan nomor antrean pengambilan tersedia setelah pemeriksaan serta penyelesaian oleh admin.</p></section>
    <div class="submission-choice-grid">
        <?php foreach ($transactionCatalog as $choice): ?>
            <a class="submission-choice" href="kiosk.php?menu=pengajuan&amp;layanan=<?= (int) $choice['id'] ?>">
                <span class="detail-category"><?= kiosk_escape($choice['category_name']) ?></span><h2><?= kiosk_escape($choice['name']) ?></h2>
                <?php if ((int) $choice['is_demo'] === 1): ?><span class="requirement-badge">SIMULASI DEMO</span><?php endif; ?>
                <p><?= kiosk_escape($choice['summary']) ?></p><p><?= (int) $choice['requires_nik'] === 1 ? 'NIK wajib · ' : '' ?><?= (int) $choice['requires_birth_date'] === 1 ? 'Tanggal lahir · ' : '' ?><?= (int) $choice['requires_face'] === 1 ? 'Foto wajah wajib' : 'Tanpa foto wajah' ?></p><strong>Pilih & isi formulir →</strong>
            </a>
        <?php endforeach; ?>
    </div>
    <?php if ($transactionCatalog === []): ?><p class="public-notice">Belum ada layanan yang menerima pengajuan KIOSK. Hubungi petugas.</p><?php endif; ?>
<?php elseif ($selected === 'pengajuan' && $transactionService !== null && $formKey !== ''): ?>
    <section class="detail-panel transaction-panel">
        <span class="detail-category">PENGAJUAN LAYANAN</span>
        <h1><?= kiosk_escape($transactionService['name']) ?></h1>
        <?php if ((int) $transactionService['is_demo'] === 1): ?><p class="demo-banner">SIMULASI — gunakan nama, kontak, dan dokumen contoh saja. Bukan pengajuan resmi.</p><?php endif; ?>
        <div class="privacy-notice"><h2>Sebelum melanjutkan</h2><p>Data dan foto yang Anda kirim hanya untuk pemeriksaan layanan oleh petugas berwenang. Isian wajib mengikuti pengaturan layanan. NIK hanya diminta jika diperlukan; pemeriksaan 16 digit bukan verifikasi Dukcapil. Foto wajah jika diminta hanya untuk pemeriksaan manual, bukan pengenalan wajah otomatis. Jangan kirim nomor KK, OTP atau identitas lain yang tidak diminta. Masa akses data <?= (int) $transactionService['retention_days'] ?> hari; pengelola menghapus data dan lampiran setelah retensi. Foto dokumen bersifat opsional; dokumen wajib tetap dibawa ke petugas.</p><p>Jika tidak ingin menyimpan data di KIOSK atau kamera tidak tersedia untuk foto wajib, batalkan dan temui petugas. DEMO hanya memakai identitas/foto contoh, bukan data pribadi asli.</p></div>
        <form class="submission-form" method="post" enctype="multipart/form-data" id="submission-form">
            <input type="hidden" name="csrf" value="<?= kiosk_escape($_SESSION['csrf']) ?>">
            <input type="hidden" name="request_key" value="<?= kiosk_escape($formKey) ?>">
            <label class="check-label"><input type="checkbox" name="consent" value="1" required> Saya memahami pemberitahuan privasi dan menyetujui pengiriman data untuk layanan ini.</label>
            <label for="applicant-name">Nama <?= (int) $transactionService['requires_name'] === 1 ? '(wajib)' : '(opsional)' ?></label>
            <input id="applicant-name" name="name" maxlength="120" autocomplete="off" <?= (int) $transactionService['requires_name'] === 1 ? 'required' : '' ?> value="<?= kiosk_escape($input['name']) ?>">
            <?php if ((int) $transactionService['requires_nik'] === 1): ?>
                <label for="applicant-nik">NIK (wajib 16 digit)</label>
                <input id="applicant-nik" name="nik" inputmode="numeric" pattern="[0-9]{16}" minlength="16" maxlength="16" required autocomplete="off" value="<?= kiosk_escape($input['nik']) ?>">
            <?php endif; ?>
            <?php if ((int) $transactionService['requires_birth_date'] === 1): ?>
                <label for="applicant-birth-date">Tanggal lahir (wajib, YYYY-MM-DD)</label>
                <input id="applicant-birth-date" name="birth_date" inputmode="numeric" placeholder="1990-01-31" pattern="[0-9]{4}-[0-9]{2}-[0-9]{2}" maxlength="10" required autocomplete="off" value="<?= kiosk_escape($input['birth_date']) ?>">
            <?php endif; ?>
            <label for="applicant-contact">Kontak <?= (int) $transactionService['requires_contact'] === 1 ? '(wajib)' : '(opsional)' ?></label>
            <input id="applicant-contact" name="contact" maxlength="120" autocomplete="off" <?= (int) $transactionService['requires_contact'] === 1 ? 'required' : '' ?> value="<?= kiosk_escape($input['contact']) ?>">
            <label for="request-description">Uraian kebutuhan (wajib)</label>
            <textarea id="request-description" name="description" rows="4" maxlength="3000" required autocomplete="off"><?= kiosk_escape($input['description']) ?></textarea>
            <p>Maksimal 3000 byte. Jangan masukkan data pribadi yang tidak diperlukan.</p>
            <?php require __DIR__ . '/auth-keyboard.php'; ?>

            <?php if ((int) $transactionService['requires_face'] === 1 || ((int) $transactionService['camera_enabled'] === 1 && $transactionRequirements !== [])): ?>
                <section class="camera-section no-print">
                    <h2>Foto <?= (int) $transactionService['requires_face'] === 1 ? 'wajah (wajib) & dokumen' : 'dokumen (opsional)' ?></h2>
                    <p>Maksimal 3 foto dokumen dan 1 wajah jika diminta, masing-masing 2 MB. Foto wajah wajib harus diambil dan dipilih “Gunakan foto”; jika kamera tidak tersedia hubungi petugas. Tidak ada verifikasi otomatis keaslian atau kecocokan wajah.</p>
                    <label for="photo-requirement">Jenis foto</label>
                    <select id="photo-requirement">
                        <?php if ((int) $transactionService['requires_face'] === 1): ?><option value="face">Foto wajah pemohon (wajib)</option><?php endif; ?>
                        <?php if ((int) $transactionService['camera_enabled'] === 1): foreach ($transactionRequirements as $requirement): ?><option value="<?= (int) $requirement['id'] ?>"><?= kiosk_escape($requirement['name']) ?> — <?= (int) $requirement['is_required'] === 1 ? 'dokumen wajib dibawa' : 'dokumen opsional' ?></option><?php endforeach; endif; ?>
                    </select>
                    <div class="action-buttons"><button class="footer-button" type="button" id="start-camera">Aktifkan kamera</button><button class="footer-button" type="button" id="stop-camera" hidden>Matikan kamera</button></div>
                    <div id="document-camera-preview" class="document-camera-preview" hidden>
                        <video id="document-camera" autoplay playsinline muted hidden aria-label="Pratinjau kamera foto" aria-describedby="camera-message"></video>
                        <div id="face-guide" class="face-guide" hidden aria-hidden="true"></div>
                    </div>
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
    <section class="detail-panel status-panel">
        <div class="no-print">
        <span class="detail-category">PELACAKAN PENGAJUAN</span>
        <h1>Status pengajuan</h1><p>Scan QR pada bukti atau ketik nomor pengajuan untuk melihat progres layanan dan file yang sudah diterbitkan.</p>
        <div class="status-entry-actions">
            <button class="footer-button primary-action" id="start-scanner" type="button">Scan QR dengan kamera</button>
            <button class="footer-button" id="manual-ticket" type="button">Ketik nomor pengajuan</button>
        </div>
        <p class="device-message">Scanner QR USB juga didukung: sentuh kolom nomor pengajuan, lalu scan. PIN enam digit pada bukti tetap wajib untuk menjaga kerahasiaan dokumen.</p>
        <button class="footer-button" id="stop-scanner" type="button" hidden>Matikan scanner</button>
        <div id="qr-scanner-preview" class="qr-scanner-preview" hidden>
            <video id="qr-camera" hidden playsinline muted aria-label="Kamera pemindai QR" aria-describedby="qr-scanner-instruction"></video>
            <div class="qr-scanner-overlay">
                <span class="qr-scanner-label">Kamera aktif · Scan QR code</span>
                <div class="qr-scanner-frame" aria-hidden="true"><span class="qr-scanner-line"></span></div>
                <p id="qr-scanner-instruction">Tempatkan QR tiket di dalam kotak</p>
            </div>
        </div>
        <p id="scanner-message" class="device-message" role="status"></p>
        <form method="post" class="lookup-form" id="ticket-form">
            <input type="hidden" name="csrf" value="<?= kiosk_escape($_SESSION['csrf']) ?>">
            <label for="ticket-code">Nomor pengajuan / kode tiket</label><input id="ticket-code" name="ticket" maxlength="40" autocomplete="off" autocapitalize="characters" spellcheck="false" required placeholder="20 karakter pada bukti atau hasil scan QR">
            <label for="ticket-pin">PIN enam digit</label><input id="ticket-pin" name="pin" type="password" inputmode="numeric" maxlength="6" pattern="[0-9]{6}" autocomplete="off" required>
            <button class="footer-button primary-action" type="submit">Cek status</button>
        </form>
        <?php require __DIR__ . '/auth-keyboard.php'; ?>
        </div>
        <?php if ($ticketStatus): ?>
            <section class="status-result" aria-label="Hasil cek status">
                <?php if ((int) $ticketStatus['is_demo'] === 1): ?><p class="demo-banner">TIKET SIMULASI DEMO</p><?php endif; ?>
                <h2><?= kiosk_escape($ticketStatus['service_name']) ?></h2><p>Kode: <?= kiosk_escape($ticketStatus['public_code']) ?></p><p>Status: <strong><?= kiosk_escape(submission_statuses()[$ticketStatus['status']]) ?></strong></p>
                <dl class="service-facts">
                    <?php foreach (['created_at' => 'Diajukan', 'updated_at' => 'Pembaruan terakhir'] as $field => $label): ?>
                        <div><dt><?= kiosk_escape($label) ?></dt><dd><?= kiosk_escape((new DateTimeImmutable($ticketStatus[$field], new DateTimeZone('UTC')))->setTimezone(new DateTimeZone('Asia/Jakarta'))->format('d/m/Y H:i:s')) ?> WIB</dd></div>
                    <?php endforeach; ?>
                </dl>
                <p class="multiline"><?= kiosk_escape($ticketStatus['public_note']) ?></p>
                <?php if ($finalLetter): ?>
                    <section class="pickup-result"><h3>File layanan tersedia</h3><p>Surat final PDF <?= (int) $ticketStatus['is_demo'] === 1 ? '(SIMULASI DEMO)' : '' ?> · <?= number_format((int) $finalLetter['size_bytes'] / 1024, 1, ',', '.') ?> KB</p><p>Nomor antrean pengambilan: <strong class="ticket-code"><?= kiosk_escape($finalLetter['pickup_number']) ?></strong></p><p>Nomor urut khusus layanan ini, bukan jadwal atau jaminan waktu panggilan. Konfirmasi loket dan waktu pengambilan kepada petugas. Bawa tiket serta dokumen persyaratan.</p>
                    <div class="action-buttons no-print">
                    <a class="footer-button primary-action" href="surat.php?tiket=<?= kiosk_escape($ticketStatus['public_code']) ?>&amp;download=1">Unduh PDF surat</a>
                    <a class="footer-button" href="surat.php?tiket=<?= kiosk_escape($ticketStatus['public_code']) ?>" target="_blank" rel="noopener">Buka / cetak PDF surat</a>
                    </div>
                    <button type="button" class="footer-button no-print" data-print>Cetak nomor antrean pengambilan</button><p class="no-print">PDF dibuka pada penampil browser; gunakan tombol cetak di penampil PDF. Akses 5 menit sejak cek PIN dan tetap memerlukan sesi aktif. Tutup tab PDF setelah mencetak; cetak tidak menjamin printer berhasil.</p></section>
                <?php else: ?><p>Surat final dan nomor antrean belum tersedia. Keduanya diterbitkan setelah admin menyelesaikan layanan serta mengunggah PDF yang disahkan.</p><?php endif; ?>
                <h3>Riwayat progres pengajuan</h3><p>Riwayat aktual dari petugas, dari yang paling awal. Perubahan tampil setelah Anda mengecek tiket kembali.</p>
                <ol class="status-timeline"><?php foreach ($ticketEvents as $index => $event): ?><li<?= $index === array_key_last($ticketEvents) ? ' aria-current="step"' : '' ?>><span class="status-dot" aria-hidden="true"></span><div><strong><?= kiosk_escape(submission_statuses()[$event['new_status']]) ?></strong><?php if ($index === array_key_last($ticketEvents)): ?><span class="requirement-badge">Terbaru</span><?php endif; ?><p class="multiline"><?= kiosk_escape($event['public_message'] !== '' ? $event['public_message'] : 'Status diperbarui oleh petugas.') ?></p><time datetime="<?= kiosk_escape((new DateTimeImmutable($event['created_at'], new DateTimeZone('UTC')))->format(DATE_ATOM)) ?>"><?= kiosk_escape((new DateTimeImmutable($event['created_at'], new DateTimeZone('UTC')))->setTimezone(new DateTimeZone('Asia/Jakarta'))->format('d/m/Y H:i:s')) ?> WIB</time></div></li><?php endforeach; ?></ol>
                <p>Perlu melengkapi informasi? Hubungi petugas dan bawa kode tiket. Pelengkapan daring belum tersedia.</p>
            </section>
        <?php endif; ?>
    </section>
<?php endif; ?>
