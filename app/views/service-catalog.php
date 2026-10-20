<a class="back-link" href="<?= $serviceId !== null ? 'kiosk.php?menu=' . kiosk_escape($selected) : 'kiosk.php' ?>" data-menu-voice="<?= $serviceId !== null ? kiosk_escape($menus[$selected]['title']) : 'Beranda KIOSK' ?>"><?= kiosk_icon($serviceId !== null ? 'document' : 'home') ?> <?= $serviceId !== null ? 'Kembali ke daftar layanan' : 'Kembali ke beranda' ?></a>
<?php if ($catalogError !== ''): ?>
    <section class="catalog-empty" role="alert"><span class="menu-icon orange"><?= kiosk_icon('help') ?></span><h1>Informasi layanan tidak tersedia</h1><p><?= kiosk_escape($catalogError) ?></p><a class="footer-button" href="kiosk.php?menu=<?= kiosk_escape($selected) ?>">Kembali ke daftar layanan</a></section>
<?php elseif ($service !== null): ?>
    <article class="detail-panel service-detail">
        <span class="menu-icon blue"><?= kiosk_icon('document') ?></span>
        <span class="detail-category"><?= kiosk_escape($service['category_name']) ?></span>
        <h1><?= kiosk_escape($service['name']) ?></h1>
        <?php if ($service['summary'] !== ''): ?><p class="detail-intro"><?= kiosk_escape($service['summary']) ?></p><?php endif; ?>
        <h2>Tentang layanan</h2>
        <p class="multiline"><?= kiosk_escape($service['description'] !== '' ? $service['description'] : 'Deskripsi layanan belum diisi oleh pengelola.') ?></p>
        <dl class="service-facts">
            <?php foreach (['eligibility' => 'Untuk siapa?', 'fee_text' => 'Biaya layanan', 'estimated_time' => 'Estimasi waktu', 'office_unit' => 'Unit penanggung jawab', 'service_location' => 'Lokasi / kanal pengurusan'] as $field => $label): ?>
                <div><dt><?= kiosk_escape($label) ?></dt><dd class="multiline"><?= kiosk_escape($service[$field] !== '' ? $service[$field] : 'Belum diinformasikan. Silakan konfirmasi kepada petugas.') ?></dd></div>
            <?php endforeach; ?>
        </dl>
        <section aria-labelledby="requirements-title">
            <div class="section-heading"><h2 id="requirements-title">Dokumen persyaratan</h2><span><?= count($requirements) ?> persyaratan tercatat</span></div>
            <?php if ($requirements === []): ?>
                <p class="detail-intro">Persyaratan belum diisi. Ini tidak berarti layanan tidak memerlukan dokumen; silakan konfirmasi kepada petugas.</p>
            <?php else: ?>
                <ol class="requirement-list">
                    <?php foreach ($requirements as $index => $requirement): ?>
                        <li><span class="requirement-number"><?= $index + 1 ?></span><div><h3><?= kiosk_escape($requirement['name']) ?></h3><?php if ($requirement['description'] !== ''): ?><p class="multiline"><?= kiosk_escape($requirement['description']) ?></p><?php endif; ?></div><span class="requirement-badge <?= (int) $requirement['is_required'] === 1 ? 'required' : '' ?>"><?= (int) $requirement['is_required'] === 1 ? 'Wajib' : 'Opsional' ?></span></li>
                    <?php endforeach; ?>
                </ol>
            <?php endif; ?>
        </section>
        <?php $officialUrl = service_official_url($service['official_url']); ?>
        <?php if ($officialUrl !== null): ?>
            <a class="footer-button official-link" href="<?= kiosk_escape($officialUrl) ?>" target="_blank" rel="noopener noreferrer">Buka kanal resmi (tab baru) <?= kiosk_icon('arrow') ?></a>
        <?php elseif ($service['official_url'] !== ''): ?>
            <p class="public-notice">Tautan kanal resmi belum valid. Silakan konfirmasi kepada petugas.</p>
        <?php endif; ?>
        <div class="detail-bottom"><?= kiosk_icon('help') ?><p>Halaman ini berisi informasi, bukan bukti pengajuan. Ikuti kanal pengurusan yang tercantum. Untuk layanan instansi lain, KIOSK tidak mengirim pengajuan ke instansi tersebut.</p></div>
    </article>
<?php else: ?>
    <section class="catalog-heading"><span class="eyebrow">INFORMASI LAYANAN KECAMATAN</span><h1><?= kiosk_escape($menus[$selected]['title']) ?></h1><p>Pilih layanan untuk membaca persyaratan, biaya, estimasi, dan lokasi pengurusan.</p></section>
    <?php if ($categories !== []): ?>
        <nav class="category-filters" aria-label="Filter kategori layanan">
            <a class="footer-button <?= $category === '' ? 'selected-filter' : '' ?>" href="kiosk.php?menu=<?= kiosk_escape($selected) ?>" <?= $category === '' ? 'aria-current="true"' : '' ?>>Semua kategori</a>
            <?php foreach ($categories as $slug => $name): ?>
                <a class="footer-button <?= $category === $slug ? 'selected-filter' : '' ?>" href="kiosk.php?menu=<?= kiosk_escape($selected) ?>&amp;kategori=<?= kiosk_escape(rawurlencode($slug)) ?>" <?= $category === $slug ? 'aria-current="true"' : '' ?>><?= kiosk_escape($name) ?></a>
            <?php endforeach; ?>
        </nav>
    <?php endif; ?>
    <p class="catalog-count"><?= count($catalog) ?> layanan tersedia<?= $category !== '' ? ' dalam kategori yang dipilih' : '' ?>.</p>
    <?php if ($catalog === []): ?>
        <section class="catalog-empty"><span class="menu-icon blue"><?= kiosk_icon('document') ?></span><h2><?= $category === '' ? 'Belum ada layanan dipublikasikan' : 'Belum ada layanan di kategori ini' ?></h2><p>Silakan tanyakan informasi layanan kepada petugas di lokasi. Daftar akan tampil setelah data resmi diisi dan dipublikasikan oleh pengelola.</p></section>
    <?php else: ?>
        <div class="catalog-grid">
            <?php foreach ($catalog as $entry): ?>
                <a class="menu-card" href="kiosk.php?menu=<?= kiosk_escape($selected) ?>&amp;layanan=<?= (int) $entry['id'] ?>" data-menu-voice="<?= kiosk_escape($entry['name']) ?>"><span class="card-top"><span class="menu-icon blue"><?= kiosk_icon('document') ?></span><span class="menu-status"><?= kiosk_escape($entry['category_name']) ?></span></span><h2><?= kiosk_escape($entry['name']) ?></h2><p><?= kiosk_escape($entry['summary'] !== '' ? $entry['summary'] : 'Lihat informasi lengkap dan persyaratan layanan.') ?></p><span class="card-action">Lihat detail & persyaratan <?= kiosk_icon('arrow') ?></span></a>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>
<?php endif; ?>
