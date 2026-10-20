<?php
declare(strict_types=1);
require_once dirname(__DIR__) . '/app/migrations.php';
require_once dirname(__DIR__) . '/app/office.php';
require_once dirname(__DIR__) . '/app/service-catalog.php';

function check_office(bool $condition, string $message): void
{
    if (!$condition) throw new RuntimeException($message);
}
function kiosk_escape(string $value): string { return htmlspecialchars($value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8'); }
function kiosk_icon(string $name): string { return ''; }
function render_office(?array $office, string $officeError = ''): string
{
    ob_start();
    require dirname(__DIR__) . '/app/views/public-office.php';
    return ob_get_clean();
}
$pdo = new PDO('sqlite::memory:', null, null, [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION, PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC]);
$pdo->exec('PRAGMA foreign_keys = ON');
run_migrations($pdo);
run_migrations($pdo);
check_office(public_office($pdo, 'sei-rampah') === null, 'Profil awal harus kosong.');
$sql = file_get_contents(dirname(__DIR__) . '/database/seeds/demo-sei-rampah.sql');
if ($sql === false) throw new RuntimeException('Seed hilang.');
$pdo->exec($sql);
$pdo->exec($sql);
$facilitySeed = file_get_contents(dirname(__DIR__) . '/database/seeds/demo-office-facilities.sql');
if ($facilitySeed === false) throw new RuntimeException('Seed fasilitas hilang.');
$pdo->exec($facilitySeed);
$pdo->exec($facilitySeed);
$office = public_office($pdo, 'sei-rampah');
check_office(count($office['facilities']) === 8, 'Seed fasilitas harus idempoten dan memuat 8 sarana.');
check_office(count(array_unique(array_column($office['facilities'], 'category'))) === 7, 'Semua kategori fasilitas harus tersedia.');
$facilityHtml = render_office($office);
check_office(substr_count($facilityHtml, 'class="village-card facility-card"') === 8, 'Semua fasilitas publik harus memiliki kartu.');
check_office(str_contains($facilityHtml, 'bukan daftar fasilitas nyata') && str_contains($facilityHtml, 'Legenda warna dan kode peta'), 'Fasilitas harus memiliki disclaimer dan legenda.');
$pdo->exec("UPDATE office_facilities SET name = '<script>fasilitas</script>', phone = '<img src=x>', address = 'Edit petugas' WHERE slug = 'contoh-puskesmas'");
$pdo->exec($facilitySeed);
$changed = public_office($pdo, 'sei-rampah');
check_office($changed['facilities'][0]['address'] === 'Edit petugas', 'Seed fasilitas tidak boleh menimpa perubahan.');
$facilityHtml = render_office($changed);
check_office(str_contains($facilityHtml, '&lt;script&gt;fasilitas&lt;/script&gt;') && !str_contains($facilityHtml, '<script>fasilitas') && !str_contains($facilityHtml, '<img src=x>'), 'Data fasilitas harus aman pada HTML dan JSON.');
$facilityMap = office_map_data($changed)['facilities'];
check_office(count($facilityMap) === 8 && $facilityMap[0]['code'] === 'KES' && is_float($facilityMap[0]['latitude']), 'Payload fasilitas harus bertipe benar dan memiliki kode.');
$pdo->exec("UPDATE office_facilities SET is_published = 0 WHERE slug = 'contoh-puskesmas'");
check_office(count(public_office($pdo, 'sei-rampah')['facilities']) === 7, 'Fasilitas draf harus disembunyikan.');
$pdo->exec("UPDATE office_facilities SET is_published = 1 WHERE slug = 'contoh-puskesmas'");
check_office(count($office['villages']) === 17, 'Seed ulang harus menghasilkan tepat 17 desa.');
check_office((int) $pdo->query('SELECT COUNT(*) FROM office_profiles')->fetchColumn() === 1, 'Profil tidak boleh ganda.');
check_office(public_office($pdo, 'tidak-ada') === null, 'Slug asing harus kosong.');
$html = render_office($office);
check_office(substr_count($html, 'class="village-card"') === 17, 'Semua desa harus punya kartu.');
check_office(str_contains($html, '68.900') && str_contains($html, 'SIMULASI DEMO'), 'Total penduduk dan peringatan DEMO harus ditampilkan.');
foreach (['(fiktif)', '(dummy)', '3.410000, 99.080000', 'OpenStreetMap', 'bukan data resmi', '2026', 'Telepon desa', 'Belum tersedia. Konfirmasi kepada petugas.', 'dimuat otomatis'] as $text) {
    check_office(str_contains($html, $text), 'Isi hilang: ' . $text);
}
$map = office_map_data($office);
check_office(is_float($map['center'][0]) && count($map['villages']) === 17, 'Payload peta harus numerik dan lengkap.');
check_office(array_keys($map['villages'][0]) === ['id', 'name', 'latitude', 'longitude', 'isDemo'], 'Payload peta tidak boleh mengirim kades/penduduk.');
$pdo->exec("UPDATE office_profiles SET address = 'Edit petugas', source_url = 'javascript:alert(1)' WHERE slug = 'sei-rampah'");
$pdo->exec("UPDATE office_villages SET name = '</script><script>alert(1)</script>', head_name = '<img src=x onerror=alert(1)>', phone = '<img src=x onerror=alert(2)>' WHERE slug = 'tanah-raja'");
$pdo->exec($sql);
$office = public_office($pdo, 'sei-rampah');
check_office($office['address'] === 'Edit petugas' && $office['villages'][0]['name'] === '</script><script>alert(1)</script>', 'Seed tidak boleh menimpa perubahan.');
check_office($office['villages'][0]['phone'] === '<img src=x onerror=alert(2)>', 'Telepon dari SQLite harus dipertahankan saat seed diulang.');
$html = render_office($office);
check_office(!str_contains($html, '<script>alert') && !str_contains($html, '<img src=x') && !str_contains($html, 'javascript:'), 'HTML dan URL harus aman.');
check_office(str_contains($html, '&lt;img src=x onerror=alert(2)&gt;'), 'Telepon harus di-escape.');
preg_match('/id="office-map-data">(.*?)<\/script>/s', $html, $match);
$decoded = json_decode($match[1], true, 512, JSON_THROW_ON_ERROR);
check_office($decoded['villages'][0]['name'] === $office['villages'][0]['name'], 'JSON harus menjaga nama tanpa memecah script.');
$pdo->exec("INSERT INTO office_profiles (slug, name, regency, province, latitude, longitude, is_published) VALUES ('lain', 'Lain', 'Kabupaten', 'Provinsi', 0, 0, 1)");
$insertVillage = $pdo->prepare("INSERT INTO office_villages (office_id, slug, name, population, population_year, latitude, longitude, is_published) VALUES (?, 'asing', 'Desa asing', 1, 2026, 0, 0, 1)");
$insertVillage->execute([(int) $pdo->lastInsertId()]);
$otherOfficeId = (int) $pdo->query("SELECT id FROM office_profiles WHERE slug = 'lain'")->fetchColumn();
$insertFacility = $pdo->prepare("INSERT INTO office_facilities (office_id, slug, category, name, latitude, longitude, is_published) VALUES (?, 'asing', 'sd', 'Sarana asing', 0, 0, 1)");
$insertFacility->execute([$otherOfficeId]);
check_office(count(public_office($pdo, 'sei-rampah')['facilities']) === 8, 'Fasilitas profil lain tidak boleh ikut tampil.');
foreach (["category = 'asing'", 'latitude = 91', 'longitude = 181', 'is_published = 2'] as $invalid) {
    try {
        $pdo->exec('UPDATE office_facilities SET ' . $invalid);
        throw new RuntimeException('Constraint fasilitas harus menolak: ' . $invalid);
    } catch (PDOException $error) {
        check_office($error->getCode() === '23000', 'Kegagalan fasilitas harus karena constraint.');
    }
}
$pdo->exec("UPDATE office_villages SET is_published = 0 WHERE slug = 'tanah-raja'");
$office = public_office($pdo, 'sei-rampah');
check_office(count($office['villages']) === 16 && array_sum(array_column($office['villages'], 'population')) === 64700, 'Hanya desa terpublikasi milik profil dipilih yang dihitung.');
check_office(str_contains(render_office($office), '64.700'), 'Total publik tidak boleh menghitung desa tersembunyi.');
$pdo->exec("UPDATE office_profiles SET is_published = 0 WHERE slug = 'sei-rampah'");
check_office(public_office($pdo, 'sei-rampah') === null, 'Profil draf tidak boleh tampil.');
$pdo->exec("UPDATE office_profiles SET is_published = 1 WHERE slug = 'sei-rampah'");
$pdo->exec('UPDATE office_villages SET is_published = 0');
$office = public_office($pdo, 'sei-rampah');
check_office($office['villages'] === [] && str_contains(render_office($office), 'Data desa belum dipublikasikan'), 'Desa kosong harus eksplisit.');
check_office(str_contains(render_office(null), 'Profil kecamatan belum dipublikasikan'), 'Profil kosong harus eksplisit.');
check_office(str_contains(render_office(null, 'Gagal database'), 'role="alert"'), 'Kegagalan database harus eksplisit.');
$pdo->exec('UPDATE office_facilities SET is_published = 0');
check_office(str_contains(render_office(public_office($pdo, 'sei-rampah')), 'Belum ada sarana kecamatan dipublikasikan'), 'Fasilitas kosong harus ditampilkan eksplisit.');
foreach (['population = -1', 'population_year = 1800', 'latitude = 91', 'longitude = 181'] as $invalid) {
    try {
        $pdo->exec('UPDATE office_villages SET ' . $invalid);
        throw new RuntimeException('Constraint harus menolak: ' . $invalid);
    } catch (PDOException $error) {
        check_office($error->getCode() === '23000', 'Kegagalan harus karena constraint.');
    }
}
check_office((int) $pdo->query('SELECT COUNT(*) FROM services')->fetchColumn() === 0, 'Seed profil tidak boleh mengubah layanan.');
$mapScript = file_get_contents(dirname(__DIR__) . '/resources/js/office-map.js');
check_office($mapScript !== false && str_contains($mapScript, "referrerPolicy: 'strict-origin-when-cross-origin'"), 'Tile harus mengirim origin situs sesuai kebijakan OSM tanpa membocorkan path atau query sesi.');
check_office(str_contains(render_office(public_office($pdo, 'sei-rampah')), 'value="satellite"'), 'Pemilih layer satelit harus tersedia.');
check_office(str_contains($mapScript, 'World_Imagery/MapServer/tile/{z}/{y}/{x}'), 'Tile Esri harus memakai urutan z/y/x.');
check_office(str_contains($html, 'id="map-dialog"') && str_contains($html, 'aria-haspopup="dialog"'), 'Peta harus menyediakan modal fullscreen berlabel.');
check_office(str_contains($mapScript, "position: 'topleft'") && str_contains($mapScript, 'control.addTo(map)'), 'Kontrol mode peta harus berada di dalam peta di bawah zoom.');
fwrite(STDOUT, "PASS: migrasi, seed 17 desa idempoten, preservasi perubahan, publikasi, isolasi profil, total penduduk, constraint, escaping HTML/JSON/URL, dan fallback kosong/error.\n");
