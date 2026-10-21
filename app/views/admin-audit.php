<section class="panel">
    <div class="panel-heading"><div><h2>Riwayat aktivitas admin</h2><p>Catatan tersimpan di SQLite, hanya untuk dibaca oleh admin pusat.</p></div></div>
    <form method="get" class="admin-filter report-filter">
        <input type="hidden" name="module" value="audit">
        <label for="audit-from">Dari tanggal</label><input id="audit-from" type="date" name="from" min="1900-01-01" max="9998-12-31" value="<?= escape($auditFilters['from']) ?>" required>
        <label for="audit-until">Sampai tanggal</label><input id="audit-until" type="date" name="until" min="1900-01-01" max="9998-12-31" value="<?= escape($auditFilters['until']) ?>" required>
        <label for="audit-action">Aktivitas</label>
        <select id="audit-action" name="action" data-auto-submit><option value="">Semua aktivitas</option>
            <?php $auditActions = array_unique([...array_keys($actionLabels), ...($audit['actions'] ?? []), ...($auditFilters['action'] !== '' ? [$auditFilters['action']] : [])]); ?>
            <?php foreach ($auditActions as $value): ?><option value="<?= escape($value) ?>" <?= $auditFilters['action'] === $value ? 'selected' : '' ?>><?= escape($actionLabels[$value] ?? $value) ?></option><?php endforeach; ?>
        </select>
        <label for="audit-order">Urutan waktu</label><select id="audit-order" name="order" data-auto-submit><option value="desc">Terbaru dahulu</option><option value="asc" <?= $auditFilters['order'] === 'asc' ? 'selected' : '' ?>>Terlama dahulu</option></select>
        <label for="audit-search">Pencarian</label><input id="audit-search" name="q" type="search" maxlength="200" placeholder="Nama, username, ringkasan, objek" value="<?= escape($auditFilters['q']) ?>">
        <button class="button button-primary" type="submit">Cari</button><a class="button" href="dashboard.php?module=audit">Reset filter</a>
    </form>
    <?php if ($auditError !== ''): ?><p class="admin-error" role="alert"><?= escape($auditError) ?></p><?php endif; ?>
    <?php if ($audit !== null): ?>
        <div class="table-scroll"><table>
            <caption class="sr-only">Riwayat aktivitas admin sesuai filter, waktu WIB</caption>
            <thead><tr><th scope="col">Waktu (WIB)</th><th scope="col">Pengguna</th><th scope="col">Aktivitas</th><th scope="col">Objek</th><th scope="col">Ringkasan</th></tr></thead>
            <tbody>
                <?php if ($audit['rows'] === []): ?><tr><td colspan="5" class="empty-table">Tidak ada aktivitas sesuai filter.</td></tr><?php endif; ?>
                <?php foreach ($audit['rows'] as $row): ?>
                    <?php $auditTime = (new DateTimeImmutable($row['created_at'], new DateTimeZone('UTC')))->setTimezone(new DateTimeZone('Asia/Jakarta')); ?>
                    <tr>
                        <td class="time-cell"><time datetime="<?= escape($auditTime->format(DATE_ATOM)) ?>"><?= $auditTime->format('d/m/Y') ?><span class="table-note"><?= $auditTime->format('H:i:s') ?></span></time><span class="table-note">#<?= (int) $row['id'] ?></span></td>
                        <td><strong><?= escape($row['name'] ?? 'Akun tidak tersedia') ?></strong><span class="table-note"><?= escape($row['username'] ?? 'Akun dihapus / tidak terkait') ?></span></td>
                        <td><span class="action-label"><?= escape($actionLabels[$row['action']] ?? $row['action']) ?></span></td>
                        <td><?= escape($row['entity_type']) ?><?= $row['entity_id'] !== null ? ' #' . (int) $row['entity_id'] : '' ?></td>
                        <td><?= nl2br(escape($row['summary'])) ?></td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table></div>
        <div class="panel-footer"><?= $audit['total'] ?> aktivitas &middot; Halaman <?= $audit['page'] ?> / <?= $audit['pages'] ?> &middot; Maksimal 20 baris per halaman
            <?php foreach ([-1 => 'Sebelumnya', 1 => 'Berikutnya'] as $step => $label): ?>
                <?php if ($audit['page'] + $step >= 1 && $audit['page'] + $step <= $audit['pages']): ?>
                    &middot; <a href="dashboard.php?<?= escape(http_build_query(['module' => 'audit', ...$auditFilters, 'p' => $audit['page'] + $step])) ?>"><?= $label ?></a>
                <?php endif; ?>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>
</section>
<p class="notice">Periode mengikuti WIB, termasuk seluruh tanggal akhir; bawaan bulan berjalan. Riwayat ini menampilkan aktivitas yang sudah dicatat aplikasi, bukan seluruh klik atau kunjungan KIOSK. Nama dan username mengikuti akun saat ini; riwayat tetap tersedia jika akun dihapus, tetapi identitasnya tidak lagi tersedia. Tidak ada fasilitas mengubah atau menghapus audit dari menu ini.</p>
