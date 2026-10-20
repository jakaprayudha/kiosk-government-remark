<a class="back-link" href="kiosk.php" data-menu-voice="Beranda KIOSK"><?= kiosk_icon('home') ?> Kembali ke beranda</a>
<section class="catalog-heading">
    <span class="eyebrow">REFERENSI PENGURUSAN DOKUMEN</span>
    <h1>Persyaratan & biaya</h1>
    <p>Lihat jenis pengurusan, dokumen yang perlu disiapkan, biaya, dan dasar hukumnya langsung pada tabel. Halaman informasi ini tidak membuat pengajuan atau tiket.</p>
</section>
<?php if ($guidesError !== ''): ?>
    <section class="catalog-empty" role="alert"><h2>Informasi belum tersedia</h2><p><?= kiosk_escape($guidesError) ?></p></section>
<?php elseif ($documentGuides === []): ?>
    <section class="catalog-empty"><h2>Belum ada panduan dipublikasikan</h2><p>Pengelola perlu melengkapi dan mempublikasikan data persyaratan, biaya, dan dasar hukum. Konfirmasikan ketentuan kepada petugas.</p></section>
<?php else: ?>
    <?php if (array_filter($documentGuides, static fn(array $guide): bool => (int) $guide['is_demo'] === 1) !== []): ?>
        <p class="demo-banner">SIMULASI DEMO Kabupaten Serdang Bedagai — syarat dan nominal biaya hanya contoh, bukan ketentuan resmi atau dasar pungutan. Belum diverifikasi terhadap Perda atau dasar hukum lain yang berlaku.</p>
    <?php endif; ?>
    <p class="catalog-count"><?= count($documentGuides) ?> jenis pengurusan. Biaya kosong bukan berarti gratis. Rujukan tarif resmi harus diverifikasi pengelola.</p>
    <div class="document-guide-table">
        <table>
            <caption class="sr-only">Persyaratan dan biaya pengurusan dokumen</caption>
            <thead><tr><th scope="col">Pengurusan dokumen</th><th scope="col">Persyaratan</th><th scope="col">Biaya</th><th scope="col">Dasar hukum / Perda</th></tr></thead>
            <tbody>
                <?php foreach ($documentGuides as $guide): ?>
                    <tr>
                        <th scope="row" data-label="Pengurusan dokumen"><strong><?= kiosk_escape($guide['name']) ?></strong><span class="guide-region"><?= kiosk_escape($guide['region']) ?></span><?php if ((int) $guide['is_demo'] === 1): ?><span class="requirements-demo">SIMULASI DEMO</span><?php endif; ?></th>
                        <td data-label="Persyaratan"><ol><?php foreach (document_guide_lines($guide['requirements_text']) as $line): ?><li><?= kiosk_escape($line) ?></li><?php endforeach; ?></ol></td>
                        <td data-label="Biaya"><strong class="guide-fee"><?= $guide['fee_amount'] === null ? 'Belum diinformasikan' : 'Rp' . number_format((int) $guide['fee_amount'], 0, ',', '.') ?></strong><span class="guide-verification"><?= (int) $guide['is_demo'] === 1 ? 'Nominal simulasi, bukan tarif resmi' : ((int) $guide['fee_verified'] === 1 ? 'Diverifikasi oleh pengelola' : 'Belum diverifikasi') ?></span><p class="multiline"><?= kiosk_escape($guide['fee_note']) ?></p></td>
                        <td data-label="Dasar hukum / Perda"><p class="multiline"><?= kiosk_escape($guide['legal_basis'] !== '' ? $guide['legal_basis'] : 'Belum diisi. Konfirmasi kepada petugas.') ?></p>
                            <?php $source = service_official_url($guide['source_url']); ?>
                            <?php if ($source !== null): ?><a class="footer-button" href="<?= kiosk_escape($source) ?>" target="_blank" rel="noopener noreferrer">Baca sumber (tab baru)</a><?php endif; ?>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
<?php endif; ?>
