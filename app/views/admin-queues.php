<p class="admin-warning"><strong>SIMULASI DEMO LOKAL</strong> — Semua instansi, jadwal, nomor dan panggilan di modul ini hanya contoh. Tidak menerbitkan reservasi resmi lintas instansi, tidak memanggil lewat speaker/SMS, dan bukan sistem triase medis.</p>
<nav class="admin-filter" aria-label="Pengelolaan antrean">
    <?php foreach (['agencies' => 'Instansi', 'services' => 'Layanan', 'slots' => 'Jadwal & kuota', 'bookings' => 'Panggilan & pemesanan'] as $view => $label): ?><a class="button <?= $queueView === $view ? 'button-primary' : '' ?>" href="dashboard.php?module=queues&amp;view=<?= $view ?>" <?= $queueView === $view ? 'aria-current="page"' : '' ?>><?= escape($label) ?></a><?php endforeach; ?>
</nav>
<?php if ($queueAdminError !== ''): ?><p class="admin-error" role="alert"><?= escape($queueAdminError) ?></p><?php endif; ?>
<?php if (isset($_GET['saved']) && $queueAdminError === ''): ?><p class="admin-success" role="status">Perubahan antrean DEMO tersimpan.</p><?php endif; ?>
<?php if (in_array($queueView, ['slots','bookings'], true)): ?>
    <form method="get" class="admin-filter"><input type="hidden" name="module" value="queues"><input type="hidden" name="view" value="<?= escape($queueView) ?>"><label for="queue-filter-date">Tanggal WIB</label><input id="queue-filter-date" type="date" name="date" value="<?= escape($queueDate) ?>" required><?php if ($queueView === 'bookings'): ?><label for="queue-slot-filter">ID jadwal (0 = semua)</label><input id="queue-slot-filter" name="slot" type="number" min="0" value="<?= (int) ($queueSlotFilter ?? 0) ?>"><?php endif; ?><button class="button" type="submit">Tampilkan</button></form>
<?php endif; ?>
<?php if ($queueView !== 'bookings'): ?>
    <section class="panel admin-detail">
        <h2><?= $queueEdit ? 'Ubah data' : 'Tambah ' . ['agencies' => 'instansi DEMO', 'services' => 'layanan DEMO', 'slots' => 'jadwal DEMO'][$queueView] ?></h2>
        <p>Seluruh data tetap berlabel DEMO di KIOSK. Nonaktifkan instansi/layanan atau tutup jadwal untuk menghentikan pemesanan baru; bukti lama tetap dapat dicek. Perubahan nama/petunjuk juga tampil pada bukti lama.</p>
        <form method="post" class="admin-form">
            <input type="hidden" name="csrf_token" value="<?= escape(csrf_token()) ?>"><input type="hidden" name="id" value="<?= (int) ($queueEdit['id'] ?? 0) ?>">
            <?php if ($queueView === 'agencies'): ?>
                <label for="queue-agency-name">Nama instansi contoh</label><input id="queue-agency-name" name="name" maxlength="160" value="<?= escape($queueEdit['name'] ?? '') ?>" required>
                <label for="queue-kind">Jenis instansi</label><select id="queue-kind" name="kind"><?php foreach (queue_kinds() as $value => $label): ?><option value="<?= $value ?>" <?= ($queueEdit['kind'] ?? '') === $value ? 'selected' : '' ?>><?= escape($label) ?></option><?php endforeach; ?></select>
                <label for="queue-description">Deskripsi (maksimal 1000 byte)</label><textarea id="queue-description" name="description" maxlength="1000"><?= escape($queueEdit['description'] ?? '') ?></textarea>
                <label for="queue-address">Alamat contoh (maksimal 300 byte)</label><input id="queue-address" name="address" maxlength="300" value="<?= escape($queueEdit['address'] ?? '') ?>">
            <?php elseif ($queueView === 'services'): ?>
                <label for="queue-agency">Instansi (tidak dapat dipindahkan setelah dibuat)</label>
                <select id="queue-agency" name="agency_id" required><?php foreach ($queueAgencies as $agency): if ($queueEdit && (int) $queueEdit['agency_id'] !== (int) $agency['id']) continue; ?><option value="<?= (int) $agency['id'] ?>"><?= escape($agency['name']) ?></option><?php endforeach; ?></select>
                <label for="queue-service-name">Nama layanan</label><input id="queue-service-name" name="name" maxlength="160" value="<?= escape($queueEdit['name'] ?? '') ?>" required>
                <label for="queue-instructions">Petunjuk / persyaratan contoh (maksimal 1500 byte)</label><textarea id="queue-instructions" name="instructions" maxlength="1500"><?= escape($queueEdit['instructions'] ?? '') ?></textarea>
            <?php else: ?>
                <?php if ($queueEdit): ?>
                    <p><?= escape($queueEdit['agency_name'] . ' / ' . $queueEdit['service_name'] . ' / ' . $queueEdit['service_date'] . ' ' . $queueEdit['start_time'] . '–' . $queueEdit['end_time']) ?> WIB</p>
                    <p>Tanggal, jam dan layanan tidak dapat dipindahkan. Tutup jadwal, batalkan pemesanan secara eksplisit, lalu buat jadwal baru jika diperlukan.</p>
                    <input type="hidden" name="version" value="<?= (int) $queueEdit['version'] ?>">
                <?php else: ?>
                    <label for="queue-service">Layanan</label><select id="queue-service" name="service_id" required><?php foreach ($queueServices as $service): ?><option value="<?= (int) $service['id'] ?>"><?= escape($service['agency_name'] . ' / ' . $service['name']) ?></option><?php endforeach; ?></select>
                    <label for="queue-date">Tanggal (hari ini s.d. 30 hari berikutnya)</label><input id="queue-date" name="service_date" type="date" min="<?= queue_now()->format('Y-m-d') ?>" max="<?= queue_now()->modify('+30 days')->format('Y-m-d') ?>" value="<?= escape($queueDate) ?>" required>
                    <label for="queue-start">Mulai (WIB)</label><input id="queue-start" name="start_time" type="time" value="09:00" required>
                    <label for="queue-end">Selesai (WIB)</label><input id="queue-end" name="end_time" type="time" value="11:00" required>
                <?php endif; ?>
                <label for="queue-capacity">Kuota (1–500, tidak boleh di bawah jumlah terisi)</label><input id="queue-capacity" type="number" name="capacity" min="1" max="500" value="<?= (int) ($queueEdit['capacity'] ?? 10) ?>" required>
                <label for="queue-open">Pemesanan jadwal</label><select id="queue-open" name="is_open"><option value="1">Buka</option><option value="0" <?= isset($queueEdit['is_open']) && (int) $queueEdit['is_open'] === 0 ? 'selected' : '' ?>>Tutup</option></select>
            <?php endif; ?>
            <?php if ($queueView !== 'slots'): ?><label for="queue-active">Publikasi</label><select id="queue-active" name="is_active"><option value="1">Aktif</option><option value="0" <?= isset($queueEdit['is_active']) && (int) $queueEdit['is_active'] === 0 ? 'selected' : '' ?>>Nonaktif</option></select><?php endif; ?>
            <button class="button button-primary" type="submit">Simpan DEMO</button>
        </form>
    </section>
<?php endif; ?>
<section class="panel">
    <div class="panel-heading"><h2><?= $queueView === 'bookings' ? 'Pemesanan / panggilan (' . $queueAdminTotal . ')' : 'Data tersimpan' ?></h2></div>
    <?php if ($queueView === 'bookings'): ?><p>Panggilan mengikuti urutan nomor per jadwal, satu nomor aktif per jadwal. Selesaikan/tandai tidak hadir sebelum memanggil berikutnya. Panggilan dan penyelesaian hanya pada tanggal layanan setelah jam mulai. Pembatalan mengembalikan kuota, tetapi nomor tidak digunakan ulang. Selesai/tidak hadir tetap mengonsumsi kuota.</p><?php endif; ?>
    <div class="table-scroll"><table><thead><tr><th>Instansi / layanan</th><th>Informasi</th><th>Aksi</th></tr></thead><tbody>
        <?php if ($queueRows === []): ?><tr><td colspan="3" class="empty-table">Belum ada data sesuai pilihan.</td></tr><?php endif; ?>
        <?php foreach ($queueRows as $row): ?><tr>
            <td><?= escape($row['agency_name'] ?? $row['name']) ?><?php if (isset($row['service_name'])): ?><span class="table-note"><?= escape($row['service_name']) ?></span><?php endif; ?></td>
            <td>
                <?php if ($queueView === 'agencies'): ?><?= escape(queue_kinds()[$row['kind']]) ?> · <?= (int) $row['is_active'] === 1 ? 'Aktif' : 'Nonaktif' ?>
                <?php elseif ($queueView === 'services'): ?><?= escape($row['name']) ?> · <?= (int) $row['is_active'] === 1 ? 'Aktif' : 'Nonaktif' ?>
                <?php elseif ($queueView === 'slots'): ?>ID <?= (int) $row['id'] ?> · <?= escape($row['start_time'] . '–' . $row['end_time']) ?> WIB<span class="table-note">Terisi <?= (int) $row['occupied'] ?>/<?= (int) $row['capacity'] ?> · <?= (int) $row['is_open'] === 1 ? 'Buka' : 'Tutup' ?></span>
                <?php else: ?><strong><?= escape(queue_number($row)) ?></strong> · <?= escape(queue_statuses()[$row['status']]) ?><span class="table-note"><?= escape($row['service_date'] . ' ' . $row['start_time'] . '–' . $row['end_time']) ?> WIB</span><?php endif; ?>
            </td><td>
                <?php if ($queueView === 'bookings'): ?>
                    <?php $choices = $row['status'] === 'waiting' ? ['called','cancelled'] : ($row['status'] === 'called' ? ['completed','missed','cancelled'] : []); ?>
                    <?php if ($choices !== []): ?><form method="post" class="admin-form"><input type="hidden" name="csrf_token" value="<?= escape(csrf_token()) ?>"><input type="hidden" name="id" value="<?= (int) $row['id'] ?>"><input type="hidden" name="version" value="<?= (int) $row['version'] ?>"><label for="booking-status-<?= (int) $row['id'] ?>">Tindak lanjut <?= escape(queue_number($row)) ?></label><select id="booking-status-<?= (int) $row['id'] ?>" name="status"><?php foreach ($choices as $value): ?><option value="<?= $value ?>"><?= escape(queue_statuses()[$value]) ?></option><?php endforeach; ?></select><button class="button" type="submit">Simpan</button></form><?php else: ?>Ditutup<?php endif; ?>
                <?php else: ?>
                    <a class="button" href="dashboard.php?module=queues&amp;view=<?= $queueView ?>&amp;date=<?= escape($queueDate) ?>&amp;edit=<?= (int) $row['id'] ?>">Ubah</a>
                    <?php if ($queueView === 'slots'): ?><a class="button" href="dashboard.php?module=queues&amp;view=bookings&amp;date=<?= escape($queueDate) ?>&amp;slot=<?= (int) $row['id'] ?>">Panggilan</a><?php endif; ?>
                <?php endif; ?>
            </td>
        </tr><?php endforeach; ?>
    </tbody></table></div>
    <?php if ($queueView === 'bookings'): ?><div class="panel-footer">Halaman <?= $queueAdminPage ?><?php foreach ([-1 => 'Sebelumnya', 1 => 'Berikutnya'] as $direction => $label): $nextPage = $queueAdminPage + $direction; if ($nextPage < 1 || ($nextPage - 1) * 20 >= $queueAdminTotal) continue; ?> · <a href="dashboard.php?module=queues&amp;view=bookings&amp;date=<?= escape($queueDate) ?>&amp;slot=<?= (int) $queueSlotFilter ?>&amp;p=<?= $nextPage ?>"><?= $label ?></a><?php endforeach; ?></div><?php endif; ?>
</section>
