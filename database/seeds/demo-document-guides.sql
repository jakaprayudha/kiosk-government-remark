-- Contoh UI untuk Serdang Bedagai; bukan ketentuan dokumen atau tarif resmi.
INSERT INTO document_guides
    (slug, name, requirements_text, fee_amount, fee_note, legal_basis, is_demo, is_published, sort_order)
VALUES
    ('demo-sergai-kk', 'DEMO - Pengurusan Kartu Keluarga',
     'Formulir permohonan contoh (bukan formulir resmi)
Salinan dokumen pendukung contoh sesuai kebutuhan simulasi
Catatan perubahan data contoh', 0,
     'Rp0 hanya contoh tampilan, bukan penetapan layanan gratis. Konfirmasikan ke instansi berwenang.',
     'Belum diverifikasi. Nomor, tahun, pasal Perda atau dasar hukum lain belum diisi.', 1, 1, 1),
    ('demo-sergai-ktp', 'DEMO - Pengurusan KTP elektronik',
     'Formulir kebutuhan layanan contoh
Dokumen keluarga contoh untuk simulasi
Catatan kebutuhan penggantian atau perekaman contoh', 0,
     'Rp0 adalah data dummy, bukan tarif resmi atau pernyataan kewenangan kecamatan.',
     'Belum diverifikasi. Wajib merujuk ketentuan instansi berwenang sebelum dipakai publik.', 1, 1, 2),
    ('demo-sergai-akta', 'DEMO - Pengurusan Akta Kelahiran',
     'Formulir permohonan contoh
Dokumen keterangan kelahiran contoh
Dokumen pendukung keluarga contoh', 0,
     'Biaya simulasi Rp0. Persyaratan dan biaya resmi harus dikonfirmasi kepada unit berwenang.',
     'Belum diverifikasi. Tidak mengutip Perda Kabupaten Serdang Bedagai tertentu.', 1, 1, 3),
    ('demo-sergai-legalisasi', 'DEMO - Legalisasi salinan dokumen',
     'Dokumen asli contoh untuk pemeriksaan simulasi
Salinan dokumen contoh
Formulir permohonan contoh', 10000,
     'Rp10.000 adalah angka rekaan untuk uji tampilan, bukan pungutan yang boleh diterapkan.',
     'Belum diverifikasi. Tarif dan dasar hukum resmi belum ditetapkan pada data ini.', 1, 1, 4)
ON CONFLICT(slug) DO NOTHING;
