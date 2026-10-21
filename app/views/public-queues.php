<a class="back-link no-print" href="kiosk.php">← Kembali ke beranda</a>
<section class="detail-panel transaction-panel">
    <div class="no-print">
        <span class="detail-category">RENCANAKAN KUNJUNGAN ANDA</span>
        <h1>Antrean & janji layanan</h1>
        <p class="detail-intro">Pilih instansi dan layanan, lalu ambil nomor hari ini atau pilih jadwal kunjungan berikutnya.</p>
        <p class="demo-banner"><strong>SIMULASI DEMO LOKAL</strong> — Nomor dan janji ini tidak berlaku di Disdukcapil, puskesmas, rumah sakit, atau instansi sebenarnya. Belum terintegrasi ke sistem resmi. Bukan kanal darurat atau konsultasi medis.</p>
        <nav class="complaint-tabs queue-tabs" aria-label="Pilihan antrean">
            <?php foreach (['hari-ini' => 'Antrean hari ini', 'janji' => 'Janji layanan', 'cek' => 'Cek antrean'] as $tab => $label): ?>
                <a class="footer-button <?= $queueTab === $tab ? 'primary-action' : '' ?>" href="kiosk.php?menu=antrean&amp;tab=<?= $tab ?>" <?= $queueTab === $tab ? 'aria-current="page"' : '' ?>><?= kiosk_escape($label) ?></a>
            <?php endforeach; ?>
        </nav>
        <?php if ($queueError !== ''): ?><p class="transaction-error" role="alert"><?= kiosk_escape($queueError) ?></p><?php endif; ?>
    </div>
    <?php if ($queueReceipt): ?>
        <article class="receipt-paper status-result" aria-label="Bukti antrean DEMO">
            <p class="demo-banner">BUKTI SIMULASI — TIDAK BERLAKU DI INSTANSI SEBENARNYA</p>
            <h2><?= kiosk_escape($queueReceipt['agency_name']) ?></h2><p><?= kiosk_escape($queueReceipt['service_name']) ?></p>
            <strong class="queue-ticket-number"><?= kiosk_escape(queue_number($queueReceipt)) ?></strong>
            <p>Status: <strong><?= kiosk_escape(queue_statuses()[$queueReceipt['status']]) ?></strong></p>
            <dl class="service-facts">
                <div><dt>Tanggal layanan (WIB)</dt><dd><?= kiosk_escape($queueReceipt['service_date']) ?></dd></div>
                <div><dt>Rentang jadwal (WIB)</dt><dd><?= kiosk_escape($queueReceipt['start_time'] . '–' . $queueReceipt['end_time']) ?></dd></div>
            </dl>
            <p><?= kiosk_escape($queueReceipt['address']) ?></p><p class="multiline"><?= kiosk_escape($queueReceipt['instructions']) ?></p>
            <p>Kode pribadi <strong class="ticket-code"><?= kiosk_escape($queueReceipt['public_code']) ?></strong></p>
            <?php if (isset($queueReceipt['pin'])): ?><p>PIN pribadi <strong class="ticket-pin"><?= kiosk_escape($queueReceipt['pin']) ?></strong></p><?php endif; ?>
            <p>Simpan bukti dan PIN. Nomor urut berlaku hanya untuk jadwal di atas; rentang jam bukan jaminan waktu panggilan. Ini bukan nomor pengambilan surat pengajuan layanan.</p>
            <h3>Riwayat antrean</h3><ol class="status-timeline">
                <?php foreach ($queueReceipt['events'] as $event): ?><li><span class="status-dot" aria-hidden="true"></span><div><strong><?= kiosk_escape(queue_statuses()[$event['status']]) ?></strong><time><?= kiosk_escape((new DateTimeImmutable($event['created_at'], new DateTimeZone('UTC')))->setTimezone(new DateTimeZone('Asia/Jakarta'))->format('d/m/Y H:i:s')) ?> WIB</time></div></li><?php endforeach; ?>
            </ol>
            <div class="action-buttons no-print"><button class="footer-button primary-action" type="button" data-print>Cetak bukti DEMO</button><a class="footer-button" href="kiosk.php?menu=antrean&amp;tab=cek">Cek pembaruan antrean</a></div>
        </article>
    <?php elseif ($queueTab !== 'cek'): ?>
        <div class="no-print">
            <p class="device-message">1. Pilih instansi → 2. Pilih layanan → 3. Pilih jadwal → 4. Cetak bukti DEMO</p>
            <?php if (!$queueAgency): ?>
                <h2>Pilih instansi tujuan</h2>
                <div class="queue-card-grid">
                    <?php foreach ($queueAgencies as $agency): ?>
                        <a class="submission-choice queue-agency-card" href="kiosk.php?menu=antrean&amp;tab=<?= $queueTab ?>&amp;instansi=<?= (int) $agency['id'] ?>">
                            <span class="menu-icon <?= in_array($agency['kind'], ['health','hospital'], true) ? 'green' : 'blue' ?>"><?= kiosk_icon($agency['kind'] === 'health' || $agency['kind'] === 'hospital' ? 'help' : 'building') ?></span>
                            <span class="menu-status"><?= kiosk_escape(queue_kinds()[$agency['kind']]) ?></span>
                            <h3><?= kiosk_escape($agency['name']) ?></h3><p><?= kiosk_escape($agency['description']) ?></p><strong>Pilih instansi →</strong>
                        </a>
                    <?php endforeach; ?>
                </div>
                <?php if ($queueAgencies === [] && $queueError === ''): ?><p>Belum ada instansi DEMO yang diaktifkan pengelola.</p><?php endif; ?>
            <?php else: ?>
                <a class="back-link" href="kiosk.php?menu=antrean&amp;tab=<?= $queueTab ?>">← Ganti instansi</a>
                <h2><?= kiosk_escape($queueAgency['name']) ?></h2>
                <?php if (!$queueService): ?>
                    <h3>Pilih layanan</h3><div class="queue-card-grid">
                        <?php foreach ($queueServices as $service): ?><a class="submission-choice" href="kiosk.php?menu=antrean&amp;tab=<?= $queueTab ?>&amp;instansi=<?= (int) $queueAgency['id'] ?>&amp;layanan=<?= (int) $service['id'] ?>"><h3><?= kiosk_escape($service['name']) ?></h3><p><?= kiosk_escape($service['instructions']) ?></p><strong>Lihat jadwal →</strong></a><?php endforeach; ?>
                    </div>
                    <?php if ($queueServices === []): ?><p>Belum ada layanan aktif untuk instansi ini.</p><?php endif; ?>
                <?php else: ?>
                    <a class="back-link" href="kiosk.php?menu=antrean&amp;tab=<?= $queueTab ?>&amp;instansi=<?= (int) $queueAgency['id'] ?>">← Ganti layanan</a>
                    <h3><?= kiosk_escape($queueService['name']) ?></h3><p class="multiline"><?= kiosk_escape($queueService['instructions']) ?></p>
                    <p>Kuota diperiksa kembali saat pemesanan. Waktu dan tanggal menggunakan WIB. Tidak perlu nama, NIK, nomor BPJS, atau rekam medis.</p>
                    <form method="post" class="submission-form" data-private-form>
                        <input type="hidden" name="csrf" value="<?= kiosk_escape($_SESSION['csrf']) ?>"><input type="hidden" name="action" value="book"><input type="hidden" name="request_key" value="<?= kiosk_escape($queueKey) ?>">
                        <fieldset class="queue-slot-list"><legend>Pilih jadwal <?= $queueTab === 'hari-ini' ? 'hari ini' : 'tanggal berikutnya (maksimal 30 hari)' ?></legend>
                            <?php $available = false; foreach ($queueSlots as $slot): $canBook = (int) $slot['is_open'] === 1 && (int) $slot['occupied'] < (int) $slot['capacity']; $available = $available || $canBook; ?>
                                <label class="queue-slot"><input type="radio" name="slot_id" value="<?= (int) $slot['id'] ?>" <?= $canBook ? 'required' : 'disabled' ?>><span><strong><?= kiosk_escape($slot['service_date'] . ' · ' . $slot['start_time'] . '–' . $slot['end_time']) ?> WIB</strong>
                                    <span><?= (int) $slot['is_open'] !== 1 ? 'Ditutup' : ($canBook ? 'Sisa kuota ' . ((int) $slot['capacity'] - (int) $slot['occupied']) . ' dari ' . (int) $slot['capacity'] : 'Kuota penuh') ?></span>
                                    <span>Menunggu: <?= (int) $slot['waiting_count'] ?> · Dipanggil: <?= $slot['called_number'] === null ? 'Belum ada' : kiosk_escape(queue_number(['slot_id' => $slot['id'], 'number' => $slot['called_number']])) ?></span>
                                </span></label>
                            <?php endforeach; ?>
                            <?php if ($queueSlots === []): ?><p>Tidak ada jadwal tersedia. Coba tab <?= $queueTab === 'hari-ini' ? 'Janji layanan' : 'Antrean hari ini' ?> atau hubungi pengelola.</p><?php endif; ?>
                        </fieldset>
                        <?php if ($available): ?>
                            <label class="check-label"><input type="checkbox" name="confirmed" value="1" required> Saya memahami ini simulasi lokal, bukan reservasi resmi. Bukti/riwayat disimpan sampai 30 hari setelah tanggal layanan.</label>
                            <button class="footer-button primary-action" type="submit"><?= $queueTab === 'hari-ini' ? 'Ambil nomor DEMO' : 'Buat janji DEMO' ?></button>
                        <?php endif; ?>
                    </form>
                <?php endif; ?>
            <?php endif; ?>
        </div>
    <?php endif; ?>
    <?php if ($queueTab === 'cek'): ?>
        <div class="no-print">
            <form method="post" action="kiosk.php?menu=antrean&amp;tab=cek" class="lookup-form" data-private-form autocomplete="off">
                <h2>Cek antrean / janji Anda</h2><p>Masukkan kode berawalan AN- dan PIN dari bukti. Klik cek kembali untuk pembaruan; tidak ada notifikasi atau pembaruan otomatis.</p>
                <input type="hidden" name="csrf" value="<?= kiosk_escape($_SESSION['csrf']) ?>"><input type="hidden" name="action" value="lookup">
                <label for="queue-code">Kode antrean pribadi</label><input id="queue-code" name="ticket" maxlength="23" pattern="[Aa][Nn]-[A-Fa-f0-9]{20}" required spellcheck="false" autocapitalize="characters">
                <label for="queue-pin">PIN enam digit</label><input id="queue-pin" name="pin" type="password" inputmode="numeric" maxlength="6" pattern="[0-9]{6}" required>
                <button class="footer-button primary-action" type="submit">Cek antrean DEMO</button>
            </form>
            <?php require __DIR__ . '/auth-keyboard.php'; ?>
        </div>
    <?php endif; ?>
</section>
