<p class="admin-warning"><strong>SIMULASI DEMO LOKAL</strong> — Semua instansi, jadwal, nomor dan panggilan di modul ini hanya contoh. Tidak menerbitkan reservasi resmi lintas instansi, tidak memanggil lewat speaker/SMS, dan bukan sistem triase medis.</p>
<nav class="office-tabs queue-tabs" aria-label="Pengelolaan antrean">
    <?php foreach (['agencies' => 'Instansi', 'services' => 'Layanan', 'slots' => 'Jadwal & kuota', 'bookings' => 'Panggilan & pemesanan'] as $view => $label): ?><a class="button <?= $queueView === $view ? 'button-primary' : 'category-edit' ?>" href="dashboard.php?module=queues&amp;view=<?= $view ?>&amp;date=<?= escape($queueDate) ?>" <?= $queueView === $view ? 'aria-current="page"' : '' ?>><?= escape($label) ?></a><?php endforeach; ?>
</nav>
<?php if ($queueAdminError !== ''): ?><p class="admin-error" role="alert"><?= escape($queueAdminError) ?></p><?php endif; ?>
<?php if (isset($_GET['saved']) && $queueAdminError === ''): ?><p class="admin-success" role="status">Perubahan antrean DEMO tersimpan.</p><?php endif; ?>
<?php if (in_array($queueView, ['slots','bookings'], true)): ?>
    <form method="get" class="admin-filter queue-filter"><input type="hidden" name="module" value="queues"><input type="hidden" name="view" value="<?= escape($queueView) ?>"><label for="queue-filter-date">Tanggal WIB</label><input id="queue-filter-date" type="date" name="date" value="<?= escape($queueDate) ?>" required data-auto-submit><?php if ($queueView === 'bookings'): ?><label for="queue-slot-filter">Jadwal layanan</label><select id="queue-slot-filter" name="slot" data-auto-submit><option value="0">Semua jadwal</option><?php foreach ($queueFilterSlots as $slot): ?><option value="<?= (int) $slot['id'] ?>" <?= $queueSlotFilter === (int) $slot['id'] ? 'selected' : '' ?>><?= escape($slot['agency_name'] . ' / ' . $slot['service_name'] . ' / ' . $slot['start_time'] . '–' . $slot['end_time']) ?></option><?php endforeach; ?></select><?php endif; ?><noscript><button class="button category-edit" type="submit">Tampilkan</button></noscript></form>
<?php endif; ?>
<?php if ($queueView !== 'bookings'): ?>
    <dialog id="queue-dialog" class="category-dialog" aria-labelledby="queue-dialog-title" <?= $queueModalOpen ? 'open data-auto-open' : '' ?>>
        <div class="category-dialog-heading"><h2 id="queue-dialog-title"><?= $queueEdit ? 'Ubah data' : 'Tambah ' . ['agencies' => 'instansi DEMO', 'services' => 'layanan DEMO', 'slots' => 'jadwal DEMO'][$queueView] ?></h2><a class="button category-edit" href="<?= escape($queueBaseUrl) ?>" data-queue-close>Tutup</a></div>
        <div class="category-dialog-body">
        <?php if ($queueAdminError !== ''): ?><p class="admin-error" role="alert"><?= escape($queueAdminError) ?></p><?php endif; ?>
        <p>Seluruh data tetap berlabel DEMO di KIOSK. Nonaktifkan instansi/layanan atau tutup jadwal untuk menghentikan pemesanan baru; bukti lama tetap dapat dicek. Perubahan nama/petunjuk juga tampil pada bukti lama.</p>
        <form method="post" class="admin-form" id="queue-form">
            <input type="hidden" name="csrf_token" value="<?= escape(csrf_token()) ?>"><input type="hidden" name="id" value="<?= (int) ($queueEdit['id'] ?? 0) ?>">
            <?php if ($queueView === 'agencies'): ?>
                <label for="queue-agency-name">Nama instansi contoh</label><input id="queue-agency-name" name="name" maxlength="160" value="<?= escape($queueFormValues['name'] ?? '') ?>" required>
                <label for="queue-kind">Jenis instansi</label><select id="queue-kind" name="kind"><?php foreach (queue_kinds() as $value => $label): ?><option value="<?= $value ?>" <?= ($queueFormValues['kind'] ?? '') === $value ? 'selected' : '' ?>><?= escape($label) ?></option><?php endforeach; ?></select>
                <label for="queue-description">Deskripsi (maksimal 1000 byte)</label><textarea id="queue-description" name="description" maxlength="1000"><?= escape($queueFormValues['description'] ?? '') ?></textarea>
                <label for="queue-address">Alamat contoh (maksimal 300 byte)</label><input id="queue-address" name="address" maxlength="300" value="<?= escape($queueFormValues['address'] ?? '') ?>">
            <?php elseif ($queueView === 'services'): ?>
                <label for="queue-agency">Instansi (tidak dapat dipindahkan setelah dibuat)</label>
                <select id="queue-agency" name="agency_id" required><?php foreach ($queueAgencies as $agency): if ($queueEdit && (int) $queueEdit['agency_id'] !== (int) $agency['id']) continue; ?><option value="<?= (int) $agency['id'] ?>" <?= (int) ($queueFormValues['agency_id'] ?? 0) === (int) $agency['id'] ? 'selected' : '' ?>><?= escape($agency['name']) ?></option><?php endforeach; ?></select>
                <label for="queue-service-name">Nama layanan</label><input id="queue-service-name" name="name" maxlength="160" value="<?= escape($queueFormValues['name'] ?? '') ?>" required>
                <label for="queue-instructions">Petunjuk / persyaratan contoh (maksimal 1500 byte)</label><textarea id="queue-instructions" name="instructions" maxlength="1500"><?= escape($queueFormValues['instructions'] ?? '') ?></textarea>
            <?php else: ?>
                <?php if ($queueEdit): ?>
                    <p><?= escape($queueEdit['agency_name'] . ' / ' . $queueEdit['service_name'] . ' / ' . $queueEdit['service_date'] . ' ' . $queueEdit['start_time'] . '–' . $queueEdit['end_time']) ?> WIB</p>
                    <p>Tanggal, jam dan layanan tidak dapat dipindahkan. Tutup jadwal, batalkan pemesanan secara eksplisit, lalu buat jadwal baru jika diperlukan.</p>
                    <input type="hidden" name="version" value="<?= (int) $queueFormValues['version'] ?>">
                <?php else: ?>
                    <label for="queue-service">Layanan</label><select id="queue-service" name="service_id" required><?php foreach ($queueServices as $service): ?><option value="<?= (int) $service['id'] ?>" <?= (int) ($queueFormValues['service_id'] ?? 0) === (int) $service['id'] ? 'selected' : '' ?>><?= escape($service['agency_name'] . ' / ' . $service['name']) ?></option><?php endforeach; ?></select>
                    <label for="queue-date">Tanggal (hari ini s.d. 30 hari berikutnya)</label><input id="queue-date" name="service_date" type="date" min="<?= queue_now()->format('Y-m-d') ?>" max="<?= queue_now()->modify('+30 days')->format('Y-m-d') ?>" value="<?= escape($queueFormValues['service_date'] ?? $queueDate) ?>" required>
                    <label for="queue-start">Mulai (WIB)</label><input id="queue-start" name="start_time" type="time" value="<?= escape($queueFormValues['start_time'] ?? '09:00') ?>" required>
                    <label for="queue-end">Selesai (WIB)</label><input id="queue-end" name="end_time" type="time" value="<?= escape($queueFormValues['end_time'] ?? '11:00') ?>" required>
                <?php endif; ?>
                <label for="queue-capacity">Kuota (1–500, tidak boleh di bawah jumlah terisi)</label><input id="queue-capacity" type="number" name="capacity" min="1" max="500" value="<?= (int) ($queueFormValues['capacity'] ?? 10) ?>" required>
                <input type="hidden" name="is_open" value="0"><label class="category-switch"><input id="queue-open" type="checkbox" name="is_open" value="1" role="switch" <?= (int) ($queueFormValues['is_open'] ?? 1) === 1 ? 'checked' : '' ?>><span class="category-switch-track" aria-hidden="true"></span><span>Buka pemesanan jadwal</span></label>
            <?php endif; ?>
            <?php if ($queueView !== 'slots'): ?><input type="hidden" name="is_active" value="0"><label class="category-switch"><input id="queue-active" type="checkbox" name="is_active" value="1" role="switch" <?= (int) ($queueFormValues['is_active'] ?? 1) === 1 ? 'checked' : '' ?>><span class="category-switch-track" aria-hidden="true"></span><span>Aktif / tampil di publik</span></label><?php endif; ?>
        </form>
        </div><div class="category-dialog-footer"><a class="button category-edit" href="<?= escape($queueBaseUrl) ?>" data-queue-close>Batal</a><button class="button button-primary" type="submit" form="queue-form">Simpan DEMO</button></div>
    </dialog>
<?php endif; ?>
<section class="panel category-panel">
    <div class="panel-heading"><div><h2><?= $queueView === 'bookings' ? 'Pemesanan / panggilan (' . $queueAdminTotal . ')' : ['agencies' => 'Data instansi', 'services' => 'Data layanan', 'slots' => 'Jadwal & kuota'][$queueView] . ' (' . count($queueRows) . ')' ?></h2><p><?= $queueView === 'bookings' ? 'Nomor antrean dan status tindak lanjut per jadwal.' : 'Cari, urutkan dan kelola data melalui modal.' ?></p></div><?php if ($queueView !== 'bookings'): ?><a class="button button-primary" href="<?= escape($queueBaseUrl) ?>&amp;new=1" aria-haspopup="dialog" aria-controls="queue-dialog">+ Tambah data</a><?php endif; ?></div>
    <?php if ($queueView === 'bookings'): ?><p class="queue-note">Panggilan mengikuti urutan nomor per jadwal, satu nomor aktif per jadwal. Selesaikan/tandai tidak hadir sebelum memanggil berikutnya. Panggilan dan penyelesaian hanya pada tanggal layanan setelah jam mulai. Pembatalan mengembalikan kuota, tetapi nomor tidak digunakan ulang. Selesai/tidak hadir tetap mengonsumsi kuota.</p><?php endif; ?>
    <div class="<?= $queueView === 'bookings' ? 'table-scroll' : 'category-table' ?>"><table <?= $queueView !== 'bookings' ? 'id="queue-table"' : '' ?>><thead><tr><th>Instansi / layanan</th><th>Informasi</th><th>Status</th><th>Aksi</th></tr></thead><tbody>
        <?php if ($queueRows === [] && $queueView === 'bookings'): ?><tr><td colspan="4" class="empty-table">Belum ada data sesuai pilihan.</td></tr><?php endif; ?>
        <?php foreach ($queueRows as $row): ?><tr>
            <td><?= escape($row['agency_name'] ?? $row['name']) ?><?php if (isset($row['service_name'])): ?><span class="table-note"><?= escape($row['service_name']) ?></span><?php endif; ?></td>
            <td>
                <?php if ($queueView === 'agencies'): ?><?= escape(queue_kinds()[$row['kind']]) ?><span class="table-note"><?= escape($row['address']) ?></span>
                <?php elseif ($queueView === 'services'): ?><?= escape($row['name']) ?><span class="table-note"><?= escape($row['instructions']) ?></span>
                <?php elseif ($queueView === 'slots'): ?>ID <?= (int) $row['id'] ?> · <?= escape($row['start_time'] . '–' . $row['end_time']) ?> WIB<span class="table-note">Terisi <?= (int) $row['occupied'] ?>/<?= (int) $row['capacity'] ?> · Sisa <?= max(0, (int) $row['capacity'] - (int) $row['occupied']) ?></span>
                <?php else: ?><strong><?= escape(queue_number($row)) ?></strong><span class="table-note"><?= escape($row['service_date'] . ' ' . $row['start_time'] . '–' . $row['end_time']) ?> WIB</span><?php endif; ?>
            </td><td>
                <?php if ($queueView === 'bookings'): ?><span class="submission-status queue-status-<?= escape($row['status']) ?>"><?= escape(queue_statuses()[$row['status']]) ?></span>
                <?php else: $enabled = (int) $row[$queueView === 'slots' ? 'is_open' : 'is_active'] === 1; ?><span class="submission-status submission-status-<?= $enabled ? 'completed' : 'rejected' ?>"><?= $queueView === 'slots' ? ($enabled ? 'Buka' : 'Tutup') : ($enabled ? 'Aktif' : 'Nonaktif') ?></span><?php endif; ?>
            </td><td><div class="category-actions">
                <?php if ($queueView === 'bookings'): ?>
                    <?php $choices = $row['status'] === 'waiting' ? ['called','cancelled'] : ($row['status'] === 'called' ? ['completed','missed','cancelled'] : []); ?>
                    <?php if ($choices !== []): ?><form method="post" class="admin-form queue-booking-action"><input type="hidden" name="csrf_token" value="<?= escape(csrf_token()) ?>"><input type="hidden" name="id" value="<?= (int) $row['id'] ?>"><input type="hidden" name="version" value="<?= (int) $row['version'] ?>"><label class="sr-only" for="booking-status-<?= (int) $row['id'] ?>">Tindak lanjut <?= escape(queue_number($row)) ?></label><select id="booking-status-<?= (int) $row['id'] ?>" name="status"><?php foreach ($choices as $value): ?><option value="<?= $value ?>"><?= escape(queue_statuses()[$value]) ?></option><?php endforeach; ?></select><button class="button button-primary" type="submit">Simpan</button></form><?php else: ?>Ditutup<?php endif; ?>
                <?php else: ?>
                    <a class="button category-edit" aria-haspopup="dialog" aria-controls="queue-dialog" href="dashboard.php?module=queues&amp;view=<?= $queueView ?>&amp;date=<?= escape($queueDate) ?>&amp;edit=<?= (int) $row['id'] ?>">Ubah</a>
                    <?php if ($queueView === 'slots'): ?><a class="button button-primary" href="dashboard.php?module=queues&amp;view=bookings&amp;date=<?= escape($queueDate) ?>&amp;slot=<?= (int) $row['id'] ?>">Panggilan</a><?php endif; ?>
                <?php endif; ?>
            </div></td>
        </tr><?php endforeach; ?>
    </tbody></table></div>
    <?php if ($queueView === 'bookings'): ?><div class="panel-footer">Halaman <?= $queueAdminPage ?><?php foreach ([-1 => 'Sebelumnya', 1 => 'Berikutnya'] as $direction => $label): $nextPage = $queueAdminPage + $direction; if ($nextPage < 1 || ($nextPage - 1) * 20 >= $queueAdminTotal) continue; ?> · <a href="dashboard.php?module=queues&amp;view=bookings&amp;date=<?= escape($queueDate) ?>&amp;slot=<?= (int) $queueSlotFilter ?>&amp;p=<?= $nextPage ?>"><?= $label ?></a><?php endforeach; ?></div><?php endif; ?>
</section>
