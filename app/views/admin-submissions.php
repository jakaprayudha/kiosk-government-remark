<?php if ($adminError !== ''): ?><p class="admin-error" role="alert"><?= escape($adminError) ?></p><?php endif; ?>
<?php if (isset($_GET['saved'])): ?><p class="admin-success" role="status">Status dan riwayat berhasil disimpan.</p><?php endif; ?>
<?php if ($submission): ?>
    <a class="button" href="dashboard.php?module=submissions">← Daftar pengajuan</a>
    <section class="panel admin-detail">
        <?php if ((int) $submission['is_demo'] === 1): ?><p class="admin-warning">SIMULASI DEMO — BUKAN PENGAJUAN RESMI</p><?php endif; ?>
        <h2><?= escape($submission['service_name']) ?></h2>
        <dl class="admin-facts">
            <?php foreach (['public_code' => 'Tiket', 'name' => 'Nama', 'nik' => 'NIK (format belum diverifikasi Dukcapil)', 'birth_date' => 'Tanggal lahir', 'contact' => 'Kontak', 'description' => 'Uraian', 'created_at' => 'Dibuat (UTC)', 'expires_at' => 'Retensi berakhir (UTC)'] as $key => $label): ?>
                <dt><?= escape($label) ?></dt><dd><?= nl2br(escape($submission[$key] !== '' ? $submission[$key] : '(tidak diisi)')) ?></dd>
            <?php endforeach; ?>
        </dl>
        <h3>Foto dokumen / wajah</h3><p>Foto belum diverifikasi keasliannya. Tidak ada pengenalan wajah otomatis. Akses hanya untuk pemeriksaan layanan.</p>
        <?php if ($attachments === []): ?><p>Tidak ada foto. Minta pemohon membawa dokumen wajib ke petugas.</p><?php endif; ?>
        <ul><?php foreach ($attachments as $attachment): ?><li><a class="button" href="attachment.php?id=<?= (int) $attachment['id'] ?>" target="_blank" rel="noopener noreferrer"><?= escape($attachment['document_name']) ?> — buka foto (tab baru)</a></li><?php endforeach; ?></ul>
        <h3>Tindak lanjut</h3>
        <?php $allowedStatuses = submission_transitions($submission['status']); ?>
        <?php if ($allowedStatuses === []): ?><p>Status akhir: <?= escape(submission_statuses()[$submission['status']]) ?>. Pengajuan sudah ditutup.</p><?php else: ?>
            <form method="post" class="admin-form" enctype="multipart/form-data">
                <input type="hidden" name="csrf_token" value="<?= escape(csrf_token()) ?>">
                <input type="hidden" name="expected_status" value="<?= escape($submission['status']) ?>">
                <label for="next-status">Status berikutnya (saat ini: <?= escape(submission_statuses()[$submission['status']]) ?>)</label>
                <select id="next-status" name="status" required><?php foreach ($allowedStatuses as $status): ?><option value="<?= escape($status) ?>"><?= escape(submission_statuses()[$status]) ?></option><?php endforeach; ?></select>
                <label for="public-note">Catatan publik — terlihat pemohon, wajib untuk Perlu informasi / Ditolak</label>
                <textarea id="public-note" name="public_note" rows="3" maxlength="3000"><?= escape($submission['public_note']) ?></textarea>
                <label for="internal-note">Catatan internal — hanya untuk admin</label>
                <textarea id="internal-note" name="internal_note" rows="3" maxlength="3000"><?= escape($submission['internal_note']) ?></textarea>
                <?php if (in_array('completed', $allowedStatuses, true)): ?>
                    <label for="final-letter">PDF surat final (wajib jika memilih Selesai; maksimal 5 MB)</label>
                    <input type="file" id="final-letter" name="letter" accept="application/pdf">
                    <label><input type="checkbox" name="letter_certified" value="1"> PDF telah diperiksa dan disahkan oleh pejabat berwenang. Untuk DEMO hanya gunakan surat contoh berlabel simulasi.</label>
                    <p>Status Selesai menerbitkan nomor antrean pengambilan per layanan secara otomatis. PDF tidak dapat diganti setelah diterbitkan. KIOSK tidak membuat atau memverifikasi tanda tangan digital.</p>
                <?php endif; ?>
                <button class="button button-primary" type="submit">Simpan tindak lanjut</button>
            </form>
        <?php endif; ?>
        <?php if ($finalLetter): ?><h3>Surat final & pengambilan</h3><p>Nomor antrean: <strong><?= escape($finalLetter['pickup_number']) ?></strong>. Nomor urut layanan, bukan jadwal/jaminan waktu panggilan.</p><a class="button" href="surat.php?tiket=<?= escape($submission['public_code']) ?>" target="_blank" rel="noopener">Buka PDF final</a><?php endif; ?>
        <h3>Riwayat status</h3>
        <div class="table-scroll"><table><thead><tr><th>Status / waktu UTC</th><th>Catatan publik</th><th>Catatan internal / petugas</th></tr></thead><tbody>
        <?php foreach ($history as $event): ?><tr><td><?= escape(submission_statuses()[$event['new_status']]) ?><span class="table-note"><?= escape($event['created_at']) ?></span></td><td><?= nl2br(escape($event['public_message'])) ?></td><td><?= nl2br(escape($event['internal_note'])) ?><span class="table-note"><?= escape($event['officer_name'] ?? 'KIOSK') ?></span></td></tr><?php endforeach; ?>
        </tbody></table></div>
    </section>
<?php elseif ($id === 0): ?>
    <section class="panel">
        <div class="panel-heading"><div><h2>Pengajuan masuk</h2><p><?= $total ?> pengajuan sesuai filter · data DEMO ditandai.</p></div></div>
        <form method="get" class="admin-filter"><input type="hidden" name="module" value="submissions"><label for="status-filter">Status</label><select id="status-filter" name="status"><option value="">Semua status</option><?php foreach (submission_statuses() as $value => $label): ?><option value="<?= escape($value) ?>" <?= $filterStatus === $value ? 'selected' : '' ?>><?= escape($label) ?></option><?php endforeach; ?></select><button class="button" type="submit">Terapkan</button></form>
        <div class="table-scroll"><table><thead><tr><th>Tiket</th><th>Layanan / pemohon</th><th>Status</th><th>Aksi</th></tr></thead><tbody>
        <?php if ($records === []): ?><tr><td colspan="4" class="empty-table">Belum ada pengajuan.</td></tr><?php endif; ?>
        <?php foreach ($records as $record): ?><tr><td><?= escape($record['public_code']) ?><?php if ((int) $record['is_demo'] === 1): ?><span class="table-note">DEMO</span><?php endif; ?></td><td><?= escape($record['service_name']) ?><span class="table-note"><?= escape($record['name'] !== '' ? $record['name'] : '(tanpa nama)') ?></span></td><td><?= escape(submission_statuses()[$record['status']]) ?></td><td><a class="button" href="dashboard.php?module=submissions&amp;id=<?= (int) $record['id'] ?>">Periksa</a></td></tr><?php endforeach; ?>
        </tbody></table></div>
        <div class="panel-footer">Halaman <?= $pageNumber ?><?php if ($pageNumber > 1): ?> · <a href="dashboard.php?module=submissions&amp;p=<?= $pageNumber - 1 ?>&amp;status=<?= escape($filterStatus) ?>">Sebelumnya</a><?php endif; ?><?php if ($pageNumber * 20 < $total): ?> · <a href="dashboard.php?module=submissions&amp;p=<?= $pageNumber + 1 ?>&amp;status=<?= escape($filterStatus) ?>">Berikutnya</a><?php endif; ?></div>
    </section>
<?php endif; ?>
