-- SIMULASI saja. PIN ketiga laporan contoh: 123456 (hash di bawah).
-- Konflik tidak menimpa laporan, tanggapan, status atau masa retensi sebelumnya.
INSERT INTO complaints(public_code,pin_hash,request_key,kind,category,title,location,description,name,contact,expires_at)
VALUES
('AD-DE000000000000000001','$2y$12$52N0M/lAWVAVXwrz4DLJkOE/D46qtsVX69X4HdFSGn8tpHabORf4.','de0000000000000000000000000000000000000000000001','complaint','infrastructure',
 'DEMO - Lampu penerangan area tunggu tidak menyala','DEMO - Area tunggu kantor contoh',
 'SIMULASI, BUKAN LAPORAN WARGA. Lampu pada area tunggu rekaan tidak menyala. Mohon pemeriksaan petugas untuk latihan tindak lanjut.',
 'DEMO - Budi Contoh (fiktif)','budi.demo@example.invalid',datetime('now','+180 days')),
('AD-DE000000000000000002','$2y$12$52N0M/lAWVAVXwrz4DLJkOE/D46qtsVX69X4HdFSGn8tpHabORf4.','de0000000000000000000000000000000000000000000002','complaint','environment',
 'DEMO - Tempat sampah area pelayanan penuh','DEMO - Halaman pelayanan contoh',
 'SIMULASI LAPORAN ANONIM. Tempat sampah rekaan perlu dibersihkan. Tidak ada identitas atau lokasi warga nyata.',
 '','',datetime('now','+180 days')),
('AD-DE000000000000000003','$2y$12$52N0M/lAWVAVXwrz4DLJkOE/D46qtsVX69X4HdFSGn8tpHabORf4.','de0000000000000000000000000000000000000000000003','aspiration','service',
 'DEMO - Usulan papan petunjuk loket layanan','DEMO - Ruang pelayanan contoh',
 'SIMULASI ASPIRASI, BUKAN USULAN RESMI. Mengusulkan papan petunjuk loket yang mudah dibaca. Gunakan untuk latihan memberi tanggapan admin.',
 'DEMO - Siti Contoh (fiktif)','siti.demo@example.invalid',datetime('now','+180 days'))
ON CONFLICT DO NOTHING;

INSERT INTO complaint_events(complaint_id,new_status,public_message)
SELECT c.id,'new','SIMULASI DEMO - Laporan contoh tersimpan dan menunggu peninjauan petugas.'
FROM complaints c
WHERE (c.public_code,c.request_key) IN (
 ('AD-DE000000000000000001','de0000000000000000000000000000000000000000000001'),
 ('AD-DE000000000000000002','de0000000000000000000000000000000000000000000002'),
 ('AD-DE000000000000000003','de0000000000000000000000000000000000000000000003')
)
AND NOT EXISTS(SELECT 1 FROM complaint_events e WHERE e.complaint_id=c.id);
