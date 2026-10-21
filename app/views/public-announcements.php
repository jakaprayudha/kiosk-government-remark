<?php if ($announcementsError !== ''): ?>
    <p class="public-notice" role="alert"><?= kiosk_escape($announcementsError) ?></p>
<?php elseif ($announcements !== []): ?>
    <section class="announcements-section" aria-labelledby="announcements-title">
        <div class="section-heading"><h2 id="announcements-title">Pengumuman</h2><span>Informasi terbaru dari pengelola kecamatan</span></div>
        <div class="announcements-list">
            <?php foreach ($announcements as $announcement): ?>
                <article class="announcement-card">
                    <h3><?= kiosk_escape($announcement['title']) ?></h3>
                    <p><?= kiosk_escape($announcement['body']) ?></p>
                </article>
            <?php endforeach; ?>
        </div>
    </section>
<?php endif; ?>
