<?php if ($complaintAdminError !== ''): ?><p class="admin-error" role="alert"><?= escape($complaintAdminError) ?></p><?php endif; ?>
<?php if (isset($_GET['saved']) && $complaintAdminError === ''): ?><p class="admin-success" role="status">Tanggapan dan riwayat berhasil disimpan.</p><?php endif; ?>
<?php if ($complaintRecord): ?>
    <a class="button" href="dashboard.php?module=complaints">← Daftar laporan</a>
    <section class="panel admin-detail">
        <h2><?= escape($complaintRecord['title']) ?></h2>
        <p><?= escape(complaint_kinds()[$complaintRecord['kind']]) ?> · <?= escape(complaint_categories()[$complaintRecord['category']]) ?> · <strong><?= escape(complaint_statuses()[$complaintRecord['status']]) ?></strong></p>
        <dl class="admin-facts">
            <?php foreach (['public_code' => 'Kode laporan', 'name' => 'Nama (opsional)', 'contact' => 'Kontak (opsional)', 'location' => 'Lokasi', 'description' => 'Uraian', 'created_at' => 'Diterima (UTC)', 'expires_at' => 'Retensi berakhir (UTC)'] as $key => $label): ?>
                <dt><?= escape($label) ?></dt><dd><?= nl2br(escape($complaintRecord[$key] !== '' ? $complaintRecord[$key] : '(tidak diisi)')) ?></dd>
            <?php endforeach; ?>
        </dl>
        <h3>Tindak lanjut</h3><p>Nama dan kontak tidak diverifikasi. Hindari menulis identitas pribadi pada tanggapan publik. Tidak ada pengiriman SMS/email otomatis.</p>
        <?php $allowed = complaint_transitions($complaintRecord['status']); ?>
        <?php if ($allowed === []): ?><p>Laporan sudah ditutup.</p><?php else: ?>
            <form method="post" class="admin-form">
                <input type="hidden" name="csrf_token" value="<?= escape(csrf_token()) ?>">
                <input type="hidden" name="expected_status" value="<?= escape($complaintRecord['status']) ?>">
                <input type="hidden" name="version" value="<?= (int) $complaintRecord['version'] ?>">
                <label for="complaint-status">Status tindak lanjut</label>
                <select id="complaint-status" name="status" required><?php foreach ($allowed as $value): ?><option value="<?= escape($value) ?>"><?= escape(complaint_statuses()[$value]) ?></option><?php endforeach; ?></select>
                <label for="complaint-reply">Tanggapan publik (wajib, terlihat melalui kode + PIN)</label>
                <textarea id="complaint-reply" name="public_message" rows="4" maxlength="3000" required><?= escape(is_string($_POST['public_message'] ?? null) ? $_POST['public_message'] : '') ?></textarea>
                <label for="complaint-internal">Catatan internal (hanya admin)</label>
                <textarea id="complaint-internal" name="internal_note" rows="3" maxlength="3000"><?= escape(is_string($_POST['internal_note'] ?? null) ? $_POST['internal_note'] : '') ?></textarea>
                <button class="button button-primary" type="submit">Simpan tindak lanjut</button>
            </form>
        <?php endif; ?>
        <h3>Riwayat tindak lanjut</h3>
        <div class="table-scroll"><table><thead><tr><th>Status / UTC</th><th>Tanggapan publik</th><th>Catatan internal / petugas</th></tr></thead><tbody>
            <?php foreach ($complaintHistory as $event): ?><tr><td><?= escape(complaint_statuses()[$event['new_status']]) ?><span class="table-note"><?= escape($event['created_at']) ?></span></td><td><?= nl2br(escape($event['public_message'])) ?></td><td><?= nl2br(escape($event['internal_note'])) ?><span class="table-note"><?= escape($event['officer_name'] ?? 'KIOSK') ?></span></td></tr><?php endforeach; ?>
        </tbody></table></div>
    </section>
<?php elseif ($complaintId === 0 && $complaintAdminError === ''): ?>
    <section class="panel">
        <div class="panel-heading"><div><h2>Pengaduan & aspirasi masuk</h2><p><?= $complaintTotal ?> laporan sesuai filter · retensi 180 hari.</p></div></div>
        <form method="get" class="admin-filter"><input type="hidden" name="module" value="complaints"><label for="complaint-filter">Status</label><select id="complaint-filter" name="status"><option value="">Semua status</option><?php foreach (complaint_statuses() as $value => $label): ?><option value="<?= escape($value) ?>" <?= $complaintFilter === $value ? 'selected' : '' ?>><?= escape($label) ?></option><?php endforeach; ?></select><button class="button" type="submit">Terapkan</button></form>
        <div class="table-scroll"><table><thead><tr><th>Kode / jenis</th><th>Judul</th><th>Status</th><th>Aksi</th></tr></thead><tbody>
            <?php if ($complaintRecords === []): ?><tr><td colspan="4" class="empty-table">Belum ada laporan.</td></tr><?php endif; ?>
            <?php foreach ($complaintRecords as $record): ?><tr><td><?= escape($record['public_code']) ?><span class="table-note"><?= escape(complaint_kinds()[$record['kind']]) ?></span></td><td><?= escape($record['title']) ?></td><td><?= escape(complaint_statuses()[$record['status']]) ?></td><td><a class="button" href="dashboard.php?module=complaints&amp;id=<?= (int) $record['id'] ?>">Periksa</a></td></tr><?php endforeach; ?>
        </tbody></table></div>
        <div class="panel-footer">Halaman <?= $complaintPage ?><?php if ($complaintPage > 1): ?> · <a href="dashboard.php?module=complaints&amp;p=<?= $complaintPage - 1 ?>&amp;status=<?= escape($complaintFilter) ?>">Sebelumnya</a><?php endif; ?><?php if ($complaintPage * 20 < $complaintTotal): ?> · <a href="dashboard.php?module=complaints&amp;p=<?= $complaintPage + 1 ?>&amp;status=<?= escape($complaintFilter) ?>">Berikutnya</a><?php endif; ?></div>
    </section>
<?php endif; ?>
