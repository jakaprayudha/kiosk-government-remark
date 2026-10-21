<section class="panel">
    <div class="panel-heading"><div><h2>Laporan operasional</h2><p>Ringkasan transaksi masuk berdasarkan tanggal dibuat dalam WIB.</p></div></div>
    <form method="get" class="admin-filter report-filter">
        <input type="hidden" name="module" value="reports">
        <label for="report-from">Dari tanggal</label><input id="report-from" type="date" name="from" min="1900-01-01" max="9998-12-31" value="<?= escape($reportStart) ?>" required>
        <label for="report-until">Sampai tanggal</label><input id="report-until" type="date" name="until" min="1900-01-01" max="9998-12-31" value="<?= escape($reportEnd) ?>" required>
        <button class="button button-primary" type="submit">Tampilkan laporan</button>
        <a class="button category-edit" href="dashboard.php?module=reports">Bulan ini</a>
    </form>
</section>
<?php if ($reportError !== ''): ?><p class="admin-error" role="alert"><?= escape($reportError) ?></p><?php endif; ?>
<?php if ($report !== null): ?>
    <p class="notice">Periode <strong><?= escape($reportStart) ?> s.d. <?= escape($reportEnd) ?> WIB</strong>, termasuk seluruh tanggal akhir. Angka menunjukkan <strong>status terkini</strong> dari transaksi yang dibuat pada periode tersebut, bukan jumlah perubahan status pada periode itu. Hanya data yang masih dalam retensi dihitung; data kedaluwarsa/terhapus tidak tersedia untuk laporan historis. Data DEMO ikut dihitung. Laporan gabungan seluruh kecamatan; transaksi belum dipisahkan per wilayah.</p>
    <div class="stats-grid report-stats">
        <?php foreach ($reportGroups as $key => $group): ?>
            <article class="stat-card"><div class="stat-top"><?= escape($group['label']) ?></div><strong class="stat-number"><?= number_format(array_sum($report[$key]), 0, ',', '.') ?></strong><p>Transaksi dibuat pada periode terpilih</p></article>
        <?php endforeach; ?>
    </div>
    <div class="report-grid">
        <?php foreach ($reportGroups as $key => $group): ?>
            <section class="panel"><div class="panel-heading"><h2><?= escape($group['label']) ?></h2></div>
                <div class="table-scroll"><table><thead><tr><th scope="col">Status terkini</th><th scope="col">Jumlah</th></tr></thead><tbody>
                    <?php foreach ($group['statuses'] as $status => $label): ?><tr><td><span class="submission-status <?= $key === 'queue_bookings' ? 'queue-status-' : 'submission-status-' ?><?= escape($status) ?>"><?= escape($label) ?></span></td><td><strong><?= number_format($report[$key][$status] ?? 0, 0, ',', '.') ?></strong></td></tr><?php endforeach; ?>
                </tbody></table></div>
            </section>
        <?php endforeach; ?>
    </div>
    <section class="panel report-services">
        <div class="panel-heading"><div><h2>Pengajuan per layanan</h2><p>Nama layanan mengikuti snapshot saat pengajuan, bukan nama master terkini.</p></div></div>
        <div class="table-scroll"><table><thead><tr><th scope="col">Layanan</th><th scope="col">Total</th><th scope="col">Berjalan</th><th scope="col">Selesai</th><th scope="col">Ditolak</th><th scope="col">Termasuk DEMO</th></tr></thead><tbody>
            <?php if ($report['services'] === []): ?><tr><td colspan="6" class="empty-table">Belum ada pengajuan pada periode ini.</td></tr><?php endif; ?>
            <?php foreach ($report['services'] as $row): ?><tr><td><?= escape($row['service_name']) ?></td><td><?= (int) $row['total'] ?></td><td><?= (int) $row['total'] - (int) $row['completed'] - (int) $row['rejected'] ?></td><td><?= (int) $row['completed'] ?></td><td><?= (int) $row['rejected'] ?></td><td><?= (int) $row['demo'] ?></td></tr><?php endforeach; ?>
        </tbody></table></div>
    </section>
<?php endif; ?>
