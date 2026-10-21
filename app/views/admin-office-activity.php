<section class="panel modules-panel">
    <div class="panel-heading"><div><h2>Aktivitas per kecamatan</h2><p>Login admin wilayah dan pemuatan halaman KIOSK pada perangkat yang ditetapkan.</p></div></div>
    <?php if ($officeActivityError !== ''): ?><p class="admin-error" role="alert"><?= escape($officeActivityError) ?></p>
    <?php else: ?>
        <div class="table-scroll"><table><thead><tr><th scope="col">Kecamatan</th><th scope="col">Admin wilayah</th><th scope="col">Login terakhir</th><th scope="col">Perangkat KIOSK</th><th scope="col">Halaman dibuka</th><th scope="col">KIOSK terakhir dibuka</th><th scope="col">Aksi</th></tr></thead><tbody>
            <?php if ($officeActivityRows === []): ?><tr><td colspan="7" class="empty-table">Belum ada kecamatan terdaftar.</td></tr><?php endif; ?>
            <?php foreach ($officeActivityRows as $row): ?><tr>
                <td><strong><?= escape($row['name']) ?></strong></td>
                <td><?= (int) $row['active_accounts'] ?> aktif / <?= (int) $row['accounts'] ?> akun<span class="table-note"><?= (int) $row['logins_today'] ?> login berhasil hari ini (WIB)</span></td>
                <td><?= escape(office_activity_time($row['last_login_at'])) ?></td>
                <td><?= (int) $row['active_devices'] ?> aktif / <?= (int) $row['devices'] ?> terdaftar</td>
                <td><?= number_format((int) $row['page_views'], 0, ',', '.') ?></td>
                <td><?= escape(office_activity_time($row['last_seen_at'])) ?></td>
                <td><div class="category-actions"><a class="button category-edit" href="dashboard.php?module=users&amp;office_id=<?= (int) $row['id'] ?>">Akun</a><a class="button button-primary" href="dashboard.php?module=office&amp;tab=devices&amp;office_id=<?= (int) $row['id'] ?>">Perangkat</a></div></td>
            </tr><?php endforeach; ?>
        </tbody></table></div>
        <div class="panel-footer">Pencatatan KIOSK mulai sejak fitur ini aktif: setiap GET ke halaman kiosk.php dengan token perangkat aktif dihitung, termasuk reload dan pindah menu; bukan jumlah orang/sesi unik dan bukan status online. Browser tanpa penetapan tidak dihitung. Akun/perangkat yang dihapus tidak lagi masuk rekap. Data transaksi tidak direkap per kecamatan.</div>
    <?php endif; ?>
</section>
