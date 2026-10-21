<a class="back-link" href="kiosk.php" data-menu-voice="Beranda KIOSK"><?= kiosk_icon('home') ?> Kembali ke beranda</a>
<?php if ($officeError !== ''): ?>
    <section class="catalog-empty" role="alert"><h1>Informasi kecamatan belum dapat dimuat</h1><p><?= kiosk_escape($officeError) ?></p></section>
<?php elseif ($office === null): ?>
    <section class="catalog-empty"><h1>Profil kecamatan belum dipublikasikan</h1><p>Silakan konfirmasikan alamat, jam layanan, dan informasi desa kepada petugas.</p></section>
<?php else: ?>
    <section class="catalog-heading">
        <span class="eyebrow">PROFIL WILAYAH & DESA</span>
        <h1><?= kiosk_escape($office['name']) ?></h1>
        <p><?= kiosk_escape($office['regency']) ?> · <?= kiosk_escape($office['province']) ?></p>
    </section>
    <?php $hasDemo = (int) $office['is_demo'] === 1 || array_filter([...$office['villages'], ...$office['facilities']], static fn(array $entry): bool => (int) $entry['is_demo'] === 1) !== []; ?>
    <?php if ($hasDemo): ?><p class="demo-banner">SIMULASI DEMO — profil contoh. Nama kades fiktif, jumlah penduduk dummy, dan koordinat desa perkiraan; bukan data resmi atau petunjuk navigasi. Peta titik tidak menunjukkan batas desa/kecamatan.</p><?php endif; ?>
    <section class="office-profile" aria-label="Profil kantor kecamatan">
        <div class="detail-panel"><h2>Tentang kecamatan</h2><p class="multiline"><?= kiosk_escape($office['description']) ?></p>
            <dl class="service-facts">
                <?php foreach (['address' => 'Alamat kantor', 'opening_hours' => 'Jam pelayanan', 'contact_note' => 'Kontak & bantuan'] as $field => $label): ?>
                    <div><dt><?= kiosk_escape($label) ?></dt><dd class="multiline"><?= kiosk_escape($office[$field] !== '' ? $office[$field] : 'Belum diinformasikan. Konfirmasi kepada petugas.') ?></dd></div>
                <?php endforeach; ?>
            </dl>
            <?php $reference = service_official_url($office['source_url']); ?>
            <?php if ($reference !== null): ?><a class="footer-button" href="<?= kiosk_escape($reference) ?>" target="_blank" rel="noopener noreferrer">Rujukan nama wilayah (tab baru)</a><p class="device-message">Rujukan nama wilayah bukan sumber koordinat, pejabat, atau penduduk DEMO.</p><?php endif; ?>
        </div>
        <div class="office-totals"><article><span>Desa ditampilkan</span><strong><?= count($office['villages']) ?></strong><p>Desa yang dipublikasikan pada database.</p></article><article><span><?= $hasDemo ? 'Total penduduk contoh' : 'Total penduduk tercatat' ?></span><strong><?= number_format(array_sum(array_column($office['villages'], 'population')), 0, ',', '.') ?></strong><p>Jiwa; jumlah dari desa yang tampil. Tahun data tercantum per desa<?= $hasDemo ? ', bukan statistik resmi' : '' ?>.</p></article></div>
    </section>
    <section class="office-map-panel" aria-labelledby="office-map-title">
        <div class="section-heading"><h2 id="office-map-title">Peta desa & sarana kecamatan</h2><button class="footer-button" type="button" id="map-reset" disabled>Lihat semua titik</button></div>
        <p>Sentuh titik desa bernomor, kode sarana, atau tombol pada kartu untuk memusatkan peta. Koordinat tersimpan di SQLite, bukan lokasi perangkat Anda.</p>
        <p class="device-message">Pilih Satelit untuk melihat citra permukaan bumi. Citra bukan siaran langsung, data kemacetan, atau batas bidang tanah.</p>
        <div id="office-map-home"><div id="office-map-stage">
            <div class="map-legend" aria-label="Legenda warna dan kode peta"><span><i class="legend-dot village-color"></i> Desa (biru)</span><?php foreach (office_facility_categories() as $key => $category): ?><span><i class="legend-dot facility-<?= kiosk_escape($key) ?>"></i><?= kiosk_escape($category['code'] . ' — ' . $category['label']) ?></span><?php endforeach; ?></div>
            <div id="office-map" role="region" aria-label="Peta interaktif desa dan sarana kecamatan" tabindex="0">
                <div id="map-controls" class="map-layer-picker"><label for="map-layer">Mode peta</label><select id="map-layer" disabled><option value="street">Peta jalan</option><option value="satellite">Satelit</option></select><button id="map-fullscreen" type="button" aria-haspopup="dialog" aria-controls="map-dialog" disabled>Layar penuh</button></div>
            </div>
            <p id="map-status" class="device-message" role="status">Menyiapkan peta titik desa…</p>
        </div></div>
        <dialog id="map-dialog" aria-labelledby="map-dialog-title">
            <header class="map-dialog-header"><h2 id="map-dialog-title">Peta desa & sarana — <?= kiosk_escape($office['name']) ?></h2><button id="map-dialog-close" class="footer-button" type="button" autofocus>Tutup peta</button></header>
            <div id="map-dialog-body"></div>
        </dialog>
        <noscript><p class="transaction-error">JavaScript diperlukan untuk peta interaktif. Koordinat dan informasi desa serta sarana tetap tersedia di kartu di bawah.</p></noscript>
        <p>Peta jalan OpenStreetMap dimuat otomatis dan memerlukan internet. Pilihan Satelit menggunakan Esri World Imagery. Penyedia layer yang dipilih menerima IP, origin situs, dan area peta yang dimuat. Data kades, penduduk, dan telepon tidak dikirim ke penyedia peta. Anda dapat mematikan peta dasar.</p>
        <button class="footer-button" type="button" id="map-tiles" aria-pressed="false" disabled>Memuat peta dasar…</button>
    </section>
    <section aria-labelledby="village-list-title">
        <div class="section-heading"><h2 id="village-list-title">Desa di <?= kiosk_escape($office['name']) ?></h2><span><?= count($office['villages']) ?> desa</span></div>
        <?php if ($office['villages'] === []): ?><p class="public-notice">Data desa belum dipublikasikan. Sarana yang dipublikasikan tetap dapat dilihat pada peta.</p><?php endif; ?>
        <div class="village-grid">
            <?php foreach ($office['villages'] as $index => $village): ?>
                <article class="village-card" id="desa-<?= (int) $village['id'] ?>">
                    <span class="detail-category">DESA <?= $index + 1 ?><?php if ((int) $village['is_demo'] === 1): ?> · DEMO<?php endif; ?></span>
                    <h3><?= kiosk_escape($village['name']) ?></h3>
                    <dl><div><dt>Kepala desa<?= (int) $village['is_demo'] === 1 ? ' (fiktif)' : '' ?></dt><dd><?= kiosk_escape($village['head_name'] !== '' ? $village['head_name'] : 'Belum diinformasikan') ?></dd></div><div><dt>Penduduk<?= (int) $village['is_demo'] === 1 ? ' (dummy)' : '' ?></dt><dd><?= number_format((int) $village['population'], 0, ',', '.') ?> jiwa · <?= (int) $village['population_year'] ?></dd></div><div><dt>Telepon desa</dt><dd><?= kiosk_escape($village['phone'] !== '' ? $village['phone'] : 'Belum tersedia. Konfirmasi kepada petugas.') ?></dd></div><div><dt>Koordinat (lat, lng)</dt><dd><?= number_format((float) $village['latitude'], 6, '.', '') ?>, <?= number_format((float) $village['longitude'], 6, '.', '') ?></dd></div></dl>
                    <p class="device-message"><?= kiosk_escape($village['coordinate_note']) ?></p>
                    <button class="footer-button village-map-button" type="button" data-village-id="<?= (int) $village['id'] ?>" disabled>Lihat titik di peta</button>
                </article>
            <?php endforeach; ?>
        </div>
    </section>
    <section aria-labelledby="facility-list-title">
        <div class="section-heading"><h2 id="facility-list-title">Sarana kecamatan</h2><span><?= count($office['facilities']) ?> fasilitas dipublikasikan</span></div>
        <?php if (array_filter($office['facilities'], static fn(array $entry): bool => (int) $entry['is_demo'] === 1) !== []): ?><p class="demo-banner">Fasilitas berlabel DEMO memakai nama dan lokasi rekaan, bukan daftar fasilitas nyata di <?= kiosk_escape($office['name']) ?>. Jangan gunakan sebagai rujukan medis, darurat, sekolah, atau navigasi.</p><?php endif; ?>
        <?php if ($office['facilities'] === []): ?><p>Belum ada sarana kecamatan dipublikasikan.</p><?php endif; ?>
        <div class="village-grid">
            <?php foreach ($office['facilities'] as $facility): ?>
                <article class="village-card facility-card" id="fasilitas-<?= (int) $facility['id'] ?>">
                    <span class="detail-category"><?= kiosk_escape(office_facility_categories()[$facility['category']]['label']) ?><?= (int) $facility['is_demo'] === 1 ? ' · DEMO' : '' ?></span>
                    <h3><?= kiosk_escape($facility['name']) ?></h3>
                    <dl>
                        <?php foreach (['description' => 'Informasi', 'address' => 'Alamat', 'opening_hours' => 'Jam operasional', 'phone' => 'Telepon'] as $field => $label): ?>
                            <div><dt><?= kiosk_escape($label) ?></dt><dd><?= kiosk_escape($facility[$field] !== '' ? $facility[$field] : 'Belum tersedia. Konfirmasi kepada petugas.') ?></dd></div>
                        <?php endforeach; ?>
                        <div><dt>Koordinat (lat, lng)</dt><dd><?= number_format((float) $facility['latitude'], 6, '.', '') ?>, <?= number_format((float) $facility['longitude'], 6, '.', '') ?></dd></div>
                    </dl>
                    <p class="device-message"><?= kiosk_escape($facility['coordinate_note']) ?></p>
                    <button class="footer-button" type="button" data-facility-id="<?= (int) $facility['id'] ?>" disabled>Lihat fasilitas di peta</button>
                </article>
            <?php endforeach; ?>
        </div>
    </section>
    <script type="application/json" id="office-map-data"><?= json_encode(office_map_data($office), JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_THROW_ON_ERROR) ?></script>
<?php endif; ?>
