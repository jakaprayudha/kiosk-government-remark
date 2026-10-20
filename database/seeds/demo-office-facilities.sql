-- Semua nama dan koordinat fasilitas adalah rekaan untuk simulasi, bukan daftar fasilitas nyata.
INSERT INTO office_facilities
    (office_id, slug, category, name, description, address, opening_hours, latitude, longitude,
     coordinate_note, is_demo, is_published, sort_order)
SELECT office_profiles.id, seed.slug, seed.category, seed.name,
       seed.description, 'DEMO - Alamat belum diverifikasi. Konfirmasi kepada petugas.',
       'DEMO - Jadwal operasional belum tersedia.',
       seed.latitude, seed.longitude,
       'Koordinat DEMO perkiraan, bukan lokasi fasilitas nyata atau petunjuk navigasi.',
       1, 1, seed.sort_order
FROM office_profiles
JOIN (
    SELECT 'contoh-puskesmas' AS slug, 'health' AS category, 'DEMO - Puskesmas Contoh A' AS name, 'Contoh sarana pelayanan kesehatan masyarakat. Bukan rujukan layanan medis atau darurat.' AS description, 3.482 AS latitude, 99.148 AS longitude, 1 AS sort_order
    UNION ALL SELECT 'contoh-sd-a', 'sd', 'DEMO - SD Contoh A', 'Contoh sarana pendidikan sekolah dasar.', 3.470, 99.130, 2
    UNION ALL SELECT 'contoh-sd-b', 'sd', 'DEMO - SD Contoh B', 'Contoh sarana pendidikan sekolah dasar.', 3.445, 99.160, 3
    UNION ALL SELECT 'contoh-smp-a', 'smp', 'DEMO - SMP Contoh A', 'Contoh sarana pendidikan sekolah menengah pertama.', 3.490, 99.165, 4
    UNION ALL SELECT 'contoh-sma-a', 'sma', 'DEMO - SMA Contoh A', 'Contoh sarana pendidikan sekolah menengah atas.', 3.460, 99.145, 5
    UNION ALL SELECT 'contoh-kantor', 'government', 'DEMO - Kantor Pelayanan Contoh', 'Contoh sarana pelayanan administrasi masyarakat.', 3.477, 99.137, 6
    UNION ALL SELECT 'contoh-pasar', 'market', 'DEMO - Pasar Contoh', 'Contoh sarana perdagangan dan kebutuhan masyarakat.', 3.500, 99.135, 7
    UNION ALL SELECT 'contoh-olahraga', 'sport', 'DEMO - Lapangan Olahraga Contoh', 'Contoh sarana olahraga masyarakat.', 3.455, 99.180, 8
) AS seed
WHERE office_profiles.slug = 'sei-rampah'
ON CONFLICT(office_id, slug) DO NOTHING;
