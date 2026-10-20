-- Nama desa mengacu daftar wilayah pada Wikipedia (rujukan BPS).
-- Semua koordinat desa, nama kades, penduduk, dan informasi kantor adalah SIMULASI.
INSERT INTO office_profiles
    (slug, name, regency, province, description, address, opening_hours, contact_note,
     latitude, longitude, source_url, is_demo, is_published)
VALUES
    ('sei-rampah', 'Kecamatan Sei Rampah', 'Kabupaten Serdang Bedagai', 'Sumatera Utara',
     'Profil contoh untuk mengenal kecamatan dan sebaran titik desa. Bukan publikasi resmi pemerintah; lokasi contoh tidak menunjukkan batas administrasi.',
     'DEMO - Alamat kantor belum diverifikasi. Konfirmasikan lokasi kepada petugas.',
     'DEMO - Senin-Jumat, 08.00-16.00 WIB. Contoh tampilan, bukan jam layanan resmi.',
     'Kontak resmi belum diisi. Tidak ada nomor telepon atau akun rekaan yang dapat dihubungi.',
     3.480749, 99.140363,
     'https://id.wikipedia.org/wiki/Sei_Rampah,_Serdang_Bedagai', 1, 1)
ON CONFLICT(slug) DO NOTHING;

INSERT INTO office_villages
    (office_id, slug, name, head_name, population, population_year, latitude, longitude,
     coordinate_note, is_demo, is_published, sort_order)
SELECT office_profiles.id, seed.slug, seed.name, seed.head_name, seed.population, 2026,
       seed.latitude, seed.longitude,
       'Koordinat DEMO perkiraan untuk visualisasi, bukan titik kantor desa, pusat desa, atau batas wilayah terverifikasi.',
       1, 1, seed.sort_order
FROM office_profiles
JOIN (
    SELECT 'tanah-raja' AS slug, 'Tanah Raja' AS name, 'Bapak Contoh 01 (fiktif)' AS head_name, 4200 AS population, 3.410 AS latitude, 99.080 AS longitude, 1 AS sort_order
    UNION ALL SELECT 'pematang-pelintahan', 'Pematang Pelintahan', 'Ibu Contoh 02 (fiktif)', 3800, 3.435, 99.095, 2
    UNION ALL SELECT 'pematang-ganjang', 'Pematang Ganjang', 'Bapak Contoh 03 (fiktif)', 5100, 3.455, 99.110, 3
    UNION ALL SELECT 'rambung-estate', 'Rambung Estate', 'Ibu Contoh 04 (fiktif)', 2100, 3.380, 99.105, 4
    UNION ALL SELECT 'rambung-sialang-hilir', 'Rambung Sialang Hilir', 'Bapak Contoh 05 (fiktif)', 2700, 3.405, 99.125, 5
    UNION ALL SELECT 'rambung-sialang-hulu', 'Rambung Sialang Hulu', 'Ibu Contoh 06 (fiktif)', 2300, 3.355, 99.120, 6
    UNION ALL SELECT 'rambung-sialang-tengah', 'Rambung Sialang Tengah', 'Bapak Contoh 07 (fiktif)', 2500, 3.380, 99.140, 7
    UNION ALL SELECT 'silau-rakyat', 'Silau Rakyat', 'Ibu Contoh 08 (fiktif)', 4600, 3.430, 99.150, 8
    UNION ALL SELECT 'simpang-empat', 'Simpang Empat', 'Bapak Contoh 09 (fiktif)', 5300, 3.450, 99.170, 9
    UNION ALL SELECT 'sei-rejo', 'Sei Rejo', 'Ibu Contoh 10 (fiktif)', 3600, 3.500, 99.155, 10
    UNION ALL SELECT 'sei-parit', 'Sei Parit', 'Bapak Contoh 11 (fiktif)', 3100, 3.520, 99.140, 11
    UNION ALL SELECT 'sinah-kasih', 'Sinah Kasih', 'Ibu Contoh 12 (fiktif)', 2900, 3.490, 99.190, 12
    UNION ALL SELECT 'firdaus', 'Firdaus', 'Bapak Contoh 13 (fiktif)', 7200, 3.475, 99.160, 13
    UNION ALL SELECT 'firdaus-estate', 'Firdaus Estate', 'Ibu Contoh 14 (fiktif)', 1900, 3.460, 99.195, 14
    UNION ALL SELECT 'sei-rampah', 'Sei Rampah', 'Bapak Contoh 15 (fiktif)', 9400, 3.485, 99.140, 15
    UNION ALL SELECT 'pergulaan', 'Pergulaan', 'Ibu Contoh 16 (fiktif)', 3400, 3.410, 99.175, 16
    UNION ALL SELECT 'cempedak-lobang', 'Cempedak Lobang', 'Bapak Contoh 17 (fiktif)', 4800, 3.505, 99.110, 17
) AS seed
WHERE office_profiles.slug = 'sei-rampah'
ON CONFLICT(office_id, slug) DO NOTHING;
