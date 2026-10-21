<?php
declare(strict_types=1);
if (PHP_SAPI !== 'cli') { http_response_code(404); exit; }
require_once dirname(__DIR__) . '/app/database.php';
require_once dirname(__DIR__) . '/app/migrations.php';
require_once dirname(__DIR__) . '/app/queues.php';
try {
    $pdo = open_database(true);
    run_migrations($pdo);
    $pdo->beginTransaction();
    foreach ([
        ['DEMO Disdukcapil', 'dukcapil', 'Simulasi antrean administrasi kependudukan.', ['Konsultasi KTP-el / KK', 'Informasi akta kelahiran']],
        ['DEMO Puskesmas', 'health', 'Simulasi antrean layanan kesehatan, bukan pendaftaran pasien.', ['Pendaftaran poli umum', 'Konsultasi administrasi']],
        ['DEMO Rumah Sakit', 'hospital', 'Simulasi janji administrasi rawat jalan. Bukan penanganan darurat.', ['Informasi rawat jalan', 'Administrasi rujukan']],
        ['DEMO Dinas Perizinan', 'government', 'Simulasi konsultasi layanan perizinan.', ['Konsultasi perizinan usaha']],
    ] as [$name, $kind, $description, $services]) {
        $pdo->prepare('INSERT INTO queue_agencies(name,kind,description,address) VALUES(?,?,?,?) ON CONFLICT(name) DO NOTHING')
            ->execute([$name, $kind, $description, 'Lokasi contoh, bukan alamat kantor terverifikasi']);
        $q = $pdo->prepare('SELECT id FROM queue_agencies WHERE name=?');
        $q->execute([$name]);
        $agency = (int) $q->fetchColumn();
        foreach ($services as $serviceName) {
            $pdo->prepare('INSERT INTO queue_services(agency_id,name,instructions) VALUES(?,?,?) ON CONFLICT(agency_id,name) DO NOTHING')
                ->execute([$agency, $serviceName, 'DEMO: jangan membawa atau memasukkan identitas/rekam medis asli. Hubungi instansi resmi untuk persyaratan dan pendaftaran sebenarnya.']);
            $q = $pdo->prepare('SELECT id FROM queue_services WHERE agency_id=? AND name=?');
            $q->execute([$agency, $serviceName]);
            $service = (int) $q->fetchColumn();
            for ($day = 0; $day <= 7; $day++) {
                $date = queue_now()->modify('+' . $day . ' days')->format('Y-m-d');
                $pdo->prepare('INSERT INTO queue_slots(service_id,service_date,start_time,end_time,capacity) VALUES(?,?,?,?,?) ON CONFLICT(service_id,service_date,start_time) DO NOTHING')
                    ->execute([$service, $date, '08:00', $day === 0 ? '23:59' : '11:00', 10]);
            }
        }
    }
    $pdo->commit();
    fwrite(STDOUT, "Seed DEMO 4 instansi, 7 layanan, jadwal hari ini + 7 hari selesai. Jam/kuota hanya contoh, bukan operasional resmi. Data lama tidak ditimpa.\n");
} catch (Throwable $error) {
    if (isset($pdo) && $pdo->inTransaction()) $pdo->rollBack();
    fwrite(STDERR, 'Seed antrean gagal: ' . $error->getMessage() . PHP_EOL);
    exit(1);
}
