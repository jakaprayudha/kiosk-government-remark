import L from 'leaflet';

const container = document.querySelector('#office-map');
const status = document.querySelector('#map-status');
const reset = document.querySelector('#map-reset');
const tilesButton = document.querySelector('#map-tiles');
const layerPicker = document.querySelector('#map-layer');
const fullscreenButton = document.querySelector('#map-fullscreen');
const mapDialog = document.querySelector('#map-dialog');
const buttons = [...document.querySelectorAll('[data-village-id], [data-facility-id]')];
if (container) {
    try {
        const data = JSON.parse(document.querySelector('#office-map-data').textContent);
        const map = L.map(container, { scrollWheelZoom: false, maxZoom: 18 }).setView(data.center, 11);
        const control = new L.Control({ position: 'topleft' });
        control.onAdd = () => {
            const controls = document.querySelector('#map-controls');
            L.DomEvent.disableClickPropagation(controls);
            L.DomEvent.disableScrollPropagation(controls);
            return controls;
        };
        control.addTo(map);
        const stage = document.querySelector('#office-map-stage');
        const home = document.querySelector('#office-map-home');
        const dialogBody = document.querySelector('#map-dialog-body');
        const restoreMap = () => {
            home.append(stage);
            document.documentElement.classList.remove('map-modal-open');
            map.invalidateSize({ animate: false });
        };
        const closeDialog = () => {
            if (!mapDialog.open) return;
            mapDialog.close();
            restoreMap();
            fullscreenButton.focus({ preventScroll: true });
        };
        fullscreenButton.disabled = false;
        fullscreenButton.addEventListener('click', () => {
            dialogBody.append(stage);
            document.documentElement.classList.add('map-modal-open');
            mapDialog.showModal();
            map.invalidateSize({ animate: false });
        });
        document.querySelector('#map-dialog-close').addEventListener('click', closeDialog);
        mapDialog.addEventListener('close', () => {
            if (!mapDialog.open) restoreMap();
        });
        mapDialog.addEventListener('cancel', (event) => {
            event.preventDefault();
            closeDialog();
        });
        const markers = new Map();
        const points = [];
        const entries = [
            ...data.villages.map((entry, index) => ({ ...entry, key: `desa-${entry.id}`, markerClass: 'village-marker', markerLabel: String(index + 1) })),
            ...data.facilities.map((entry) => ({ ...entry, key: `fasilitas-${entry.id}`, markerClass: `facility-marker facility-${entry.category}`, markerLabel: entry.code })),
        ];
        entries.forEach((village) => {
            const point = [village.latitude, village.longitude];
            points.push(point);
            const icon = L.divIcon({
                className: village.markerClass,
                html: '<span></span>',
                iconSize: [44, 44], iconAnchor: [22, 22],
            });
            const label = document.createElement('div');
            label.className = 'village-popup';
            const card = document.getElementById(village.key);
            if (!card) throw new Error('VillageCardMissing');
            label.append(card.querySelector('.detail-category').cloneNode(true), card.querySelector('h3').cloneNode(true), card.querySelector('dl').cloneNode(true));
            const note = document.createElement('p');
            note.textContent = village.isDemo ? 'Titik DEMO perkiraan, bukan lokasi resmi.' : 'Titik dari database, bukan batas wilayah.';
            const coordinateNote = card.querySelector('.device-message').cloneNode(true);
            const link = document.createElement('a');
            link.href = `#${village.key}`;
            link.textContent = 'Baca informasi lengkap';
            link.addEventListener('click', () => {
                closeDialog();
                restoreMap();
                card.scrollIntoView({ block: 'start', behavior: 'auto' });
            });
            label.append(note, coordinateNote, link);
            const marker = L.marker(point, { icon, title: village.name, alt: `Titik ${village.name}`, keyboard: true })
                .addTo(map).bindPopup(label, { minWidth: 180, maxWidth: Math.min(320, Math.max(180, container.clientWidth - 80)), maxHeight: 280, autoPanPadding: [24, 24] });
            marker.getElement().querySelector('span').textContent = village.markerLabel;
            marker.on('click', () => {
                status.textContent = `Informasi ${village.name}${village.isDemo ? ' — data DEMO' : ''} ditampilkan pada peta.`;
            });
            markers.set(village.key, marker);
        });
        const showAll = () => {
            map.closePopup();
            if (points.length) map.fitBounds(L.latLngBounds(points), { padding: [45, 45], maxZoom: 13 });
            else map.setView(data.center, 11);
        };
        showAll();
        reset.disabled = false;
        reset.addEventListener('click', showAll);
        buttons.forEach((button) => {
            button.disabled = false;
            button.addEventListener('click', () => {
                const key = button.dataset.facilityId ? `fasilitas-${button.dataset.facilityId}` : `desa-${button.dataset.villageId}`;
                const marker = markers.get(key);
                if (!marker) {
                    status.textContent = 'Titik tidak ditemukan. Muat ulang halaman atau hubungi petugas.';
                    return;
                }
                map.setView(marker.getLatLng(), 13);
                marker.openPopup();
                container.scrollIntoView({ block: 'center', behavior: 'auto' });
                container.focus({ preventScroll: true });
                status.textContent = `${marker.options.title}. Periksa label DEMO dan koordinat pada kartu.`;
            });
        });
        let tiles = null;
        let failed = false;
        const layers = {
            street: {
                name: 'Peta jalan OpenStreetMap',
                url: 'https://tile.openstreetmap.org/{z}/{x}/{y}.png',
                attribution: '&copy; <a href="https://www.openstreetmap.org/copyright" target="_blank" rel="noopener noreferrer">OpenStreetMap</a> contributors',
            },
            satellite: {
                name: 'Satelit Esri World Imagery',
                url: 'https://services.arcgisonline.com/ArcGIS/rest/services/World_Imagery/MapServer/tile/{z}/{y}/{x}',
                attribution: 'Source: <a href="https://goto.arcgisonline.com/maps/World_Imagery" target="_blank" rel="noopener noreferrer">Esri</a>, Vantor, Earthstar Geographics, and the GIS User Community',
            },
        };
        tilesButton.disabled = false;
        layerPicker.disabled = false;
        const removeTiles = () => {
            if (!tiles) return;
            const previous = tiles;
            tiles = null;
            map.removeLayer(previous);
            previous.off();
        };
        const loadTiles = () => {
            const selected = layers[layerPicker.value];
            if (!selected) throw new Error('UnknownMapLayer');
            failed = false;
            const layer = L.tileLayer(selected.url, {
                maxZoom: 18,
                referrerPolicy: 'strict-origin-when-cross-origin',
                attribution: selected.attribution,
            });
            tiles = layer;
            tilesButton.textContent = 'Matikan peta dasar';
            tilesButton.setAttribute('aria-pressed', 'true');
            layer.on('tileerror', () => {
                if (tiles !== layer) return;
                failed = true;
                tilesButton.textContent = 'Coba lagi peta dasar';
                status.textContent = `${selected.name} gagal dimuat sebagian atau seluruhnya. Periksa internet, coba lagi, atau pilih layer lain. Titik desa dan sarana tetap tersedia.`;
            });
            layer.on('loading', () => {
                if (tiles === layer) status.textContent = `Memuat ${selected.name}…`;
            });
            layer.on('load', () => {
                if (tiles === layer && !failed) status.textContent = `${selected.name} dimuat. Titik DEMO tidak menunjukkan lokasi resmi atau batas administrasi.`;
            });
            layer.addTo(map);
        };
        layerPicker.addEventListener('change', () => {
            removeTiles();
            loadTiles();
        });
        tilesButton.addEventListener('click', () => {
            const retry = failed;
            if (tiles) {
                removeTiles();
                tilesButton.setAttribute('aria-pressed', 'false');
                tilesButton.textContent = 'Tampilkan peta dasar';
                status.textContent = 'Peta dasar dimatikan. Titik desa dan sarana tetap tersedia; petak yang sudah dimuat dapat tersimpan pada cache browser.';
                if (!retry) return;
            }
            loadTiles();
        });
        const resize = new ResizeObserver(() => {
            map.invalidateSize({ animate: false });
            markers.forEach((marker) => {
                const popup = marker.getPopup();
                popup.options.maxWidth = Math.min(320, Math.max(180, container.clientWidth - 80));
                if (popup.isOpen()) popup.update();
            });
        });
        resize.observe(container);
        document.addEventListener('kiosk-session-ended', () => {
            closeDialog();
            removeTiles();
            map.closePopup();
            tilesButton.textContent = 'Tampilkan peta dasar';
            tilesButton.setAttribute('aria-pressed', 'false');
            failed = false;
        });
        window.addEventListener('pagehide', () => { resize.disconnect(); map.remove(); });
        window.addEventListener('pageshow', (event) => { if (event.persisted) window.location.reload(); });
        loadTiles();
    } catch (error) {
        reset.disabled = true;
        tilesButton.disabled = true;
        layerPicker.disabled = true;
        fullscreenButton.disabled = true;
        buttons.forEach((button) => { button.disabled = true; });
        status.textContent = 'Peta interaktif gagal dibuka. Gunakan koordinat dan informasi pada kartu desa/sarana atau hubungi petugas.';
        console.error('Office map initialization failed:', error.name);
    }
}
