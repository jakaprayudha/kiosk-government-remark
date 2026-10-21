import QRCode from 'qrcode';
import jsQR from 'jsqr';

const message = document.querySelector('.service-actions .device-message');
document.querySelectorAll('canvas[data-qr]').forEach(async (canvas) => {
    const payload = canvas.dataset.qr === 'ticket'
        ? `KIOSK:V1:${canvas.dataset.ticket}`
        : window.location.href;
    try {
        await QRCode.toCanvas(canvas, payload, { width: 240, margin: 2, errorCorrectionLevel: 'M' });
    } catch (error) {
        console.error('QR generation failed:', error.name);
        const notice = document.createElement('p');
        notice.textContent = 'QR gagal dibuat. Gunakan alamat halaman atau kode tiket yang tertulis.';
        canvas.replaceWith(notice);
    }
});
document.querySelector('#show-service-qr')?.addEventListener('click', () => {
    const panel = document.querySelector('#service-qr-panel');
    panel.hidden = !panel.hidden;
});
document.querySelectorAll('[data-print]').forEach((button) => {
    button.addEventListener('click', () => {
        window.print();
        if (message) message.textContent = 'Dialog cetak dibuka. Periksa hasil printer; aplikasi tidak dapat memastikan kertas berhasil tercetak.';
    });
});

let cameraStream = null;
let scannerTimer = null;
let pendingPhoto = null;
const photos = new Map();
const video = document.querySelector('#document-camera');
const cameraMessage = document.querySelector('#camera-message');
const scannerVideo = document.querySelector('#qr-camera');
const scannerMessage = document.querySelector('#scanner-message');
const form = document.querySelector('#submission-form');
let cameraGeneration = 0;
let scannerGeneration = 0;

function stopCamera() {
    cameraGeneration += 1;
    cameraStream?.getTracks().forEach((track) => track.stop());
    cameraStream = null;
    if (video) {
        video.srcObject = null;
        video.hidden = true;
    }
    const capture = document.querySelector('#capture-photo');
    const stop = document.querySelector('#stop-camera');
    if (capture) capture.hidden = true;
    if (stop) stop.hidden = true;
}
function stopScanner() {
    scannerGeneration += 1;
    clearTimeout(scannerTimer);
    scannerTimer = null;
    if (scannerVideo) {
        const stream = scannerVideo.srcObject;
        if (stream instanceof MediaStream) stream.getTracks().forEach((track) => track.stop());
        scannerVideo.srcObject = null;
        scannerVideo.hidden = true;
    }
    const stop = document.querySelector('#stop-scanner');
    if (stop) stop.hidden = true;
}
function invalidateReview() {
    const review = document.querySelector('#submission-review');
    if (review) review.hidden = true;
    const confirmed = form?.elements.namedItem('confirmed');
    if (confirmed instanceof HTMLInputElement) confirmed.checked = false;
}

document.querySelector('#start-camera')?.addEventListener('click', async () => {
    stopCamera();
    const generation = cameraGeneration;
    document.querySelector('#stop-camera').hidden = false;
    cameraMessage.textContent = 'Menunggu izin dan gambar kamera…';
    try {
        if (!navigator.mediaDevices?.getUserMedia) throw new Error('CameraUnsupported');
        const isFace = document.querySelector('#photo-requirement')?.value === 'face';
        const stream = await navigator.mediaDevices.getUserMedia({ video: { facingMode: { ideal: isFace ? 'user' : 'environment' }, width: { ideal: 1600 }, height: { ideal: 1200 } }, audio: false });
        if (generation !== cameraGeneration) {
            stream.getTracks().forEach((track) => track.stop());
            return;
        }
        cameraStream = stream;
        video.srcObject = stream;
        video.hidden = false;
        await video.play();
        if (generation !== cameraGeneration) return;
        document.querySelector('#capture-photo').hidden = false;
        document.querySelector('#stop-camera').hidden = false;
        cameraMessage.textContent = isFace ? 'Posisikan wajah pada kamera, lalu Ambil foto. Untuk DEMO hanya gunakan foto contoh.' : 'Posisikan dokumen, pastikan terbaca, lalu Ambil foto. Jangan gunakan identitas asli untuk DEMO.';
    } catch (error) {
        if (generation !== cameraGeneration) return;
        stopCamera();
        cameraMessage.textContent = 'Kamera tidak dapat dibuka. Periksa izin kamera, HTTPS, dan koneksi perangkat; dokumen dapat dibawa langsung ke petugas.';
        console.error('Document camera unavailable:', error.name);
    }
});
document.querySelector('#stop-camera')?.addEventListener('click', stopCamera);
document.querySelector('#photo-requirement')?.addEventListener('change', () => {
    stopCamera();
    if (pendingPhoto) URL.revokeObjectURL(pendingPhoto.url);
    pendingPhoto = null;
    document.querySelector('#capture-preview').hidden = true;
    cameraMessage.textContent = 'Jenis foto diubah. Aktifkan kamera kembali untuk jenis foto yang dipilih.';
});

document.querySelector('#capture-photo')?.addEventListener('click', async () => {
    const generation = cameraGeneration;
    if (!video?.videoWidth) {
        cameraMessage.textContent = 'Kamera belum siap. Tunggu gambar muncul atau aktifkan ulang kamera.';
        return;
    }
    const canvas = document.createElement('canvas');
    const ratio = Math.min(1, 1600 / video.videoWidth, 1600 / video.videoHeight);
    canvas.width = Math.round(video.videoWidth * ratio);
    canvas.height = Math.round(video.videoHeight * ratio);
    canvas.getContext('2d').drawImage(video, 0, 0, canvas.width, canvas.height);
    const blob = await new Promise((resolve) => canvas.toBlob(resolve, 'image/jpeg', 0.85));
    if (generation !== cameraGeneration) return;
    if (!blob || blob.size > 2 * 1024 * 1024) {
        cameraMessage.textContent = 'Foto gagal diproses atau terlalu besar. Ambil ulang dengan resolusi lebih rendah.';
        return;
    }
    if (pendingPhoto) URL.revokeObjectURL(pendingPhoto.url);
    const select = document.querySelector('#photo-requirement');
    pendingPhoto = { blob, id: select.value, name: select.selectedOptions[0].textContent, url: URL.createObjectURL(blob) };
    const preview = document.querySelector('#capture-preview');
    preview.querySelector('img').src = pendingPhoto.url;
    preview.hidden = false;
    cameraMessage.textContent = 'Periksa keterbacaan foto. Pilih Gunakan foto atau Ulangi.';
});
document.querySelector('#retake-photo')?.addEventListener('click', () => {
    if (pendingPhoto) URL.revokeObjectURL(pendingPhoto.url);
    pendingPhoto = null;
    document.querySelector('#capture-preview').hidden = true;
});
function renderPhotos() {
    const list = document.querySelector('#photo-list');
    list.replaceChildren();
    for (const [id, photo] of photos) {
        const card = document.createElement('div');
        card.className = 'saved-photo';
        const img = document.createElement('img');
        img.src = photo.url;
        img.alt = `Foto ${photo.name}`;
        const label = document.createElement('p');
        label.textContent = photo.name;
        const remove = document.createElement('button');
        remove.type = 'button';
        remove.className = 'footer-button';
        remove.textContent = 'Hapus foto';
        remove.addEventListener('click', () => {
            URL.revokeObjectURL(photo.url);
            photos.delete(id);
            renderPhotos();
            invalidateReview();
        });
        card.append(img, label, remove);
        list.append(card);
    }
}
document.querySelector('#accept-photo')?.addEventListener('click', () => {
    if (!pendingPhoto) return;
    if (pendingPhoto.id !== 'face' && !photos.has(pendingPhoto.id) && [...photos.keys()].filter(id => id !== 'face').length >= 3) {
        cameraMessage.textContent = 'Maksimal tiga foto dokumen. Hapus salah satu sebelum menambah.';
        return;
    }
    const previous = photos.get(pendingPhoto.id);
    if (previous) URL.revokeObjectURL(previous.url);
    photos.set(pendingPhoto.id, pendingPhoto);
    pendingPhoto = null;
    document.querySelector('#capture-preview').hidden = true;
    renderPhotos();
    invalidateReview();
    cameraMessage.textContent = 'Foto ditambahkan ke ringkasan. Foto belum tersimpan di server sampai formulir dikirim.';
});
form?.addEventListener('input', (event) => {
    if (event.target.name !== 'confirmed') invalidateReview();
});
document.querySelector('#review-submission')?.addEventListener('click', () => {
    const data = new FormData(form);
    const description = form.elements.namedItem('description');
    if (!description.value.trim() || !form.elements.namedItem('consent').checked) {
        document.querySelector('#submission-message').textContent = 'Isi uraian dan setujui privasi sebelum memeriksa ringkasan.';
        return;
    }
    const invalidField = [...form.querySelectorAll('input:not([name="confirmed"]), textarea')].find(field => !field.checkValidity());
    if (invalidField) {
        invalidField.reportValidity();
        document.querySelector('#submission-message').textContent = 'Lengkapi isian wajib sesuai format sebelum memeriksa ringkasan.';
        return;
    }
    if (document.querySelector('#photo-requirement option[value="face"]') && !photos.has('face')) {
        document.querySelector('#submission-message').textContent = 'Ambil dan gunakan foto wajah yang diwajibkan layanan.';
        return;
    }
    const fields = document.querySelector('#review-fields');
    fields.replaceChildren();
    for (const [key, label] of [['name', 'Nama'], ['nik', 'NIK'], ['birth_date', 'Tanggal lahir'], ['contact', 'Kontak'], ['description', 'Uraian']]) {
        if (!form.elements.namedItem(key)) continue;
        const dt = document.createElement('dt');
        dt.textContent = label;
        const dd = document.createElement('dd');
        dd.textContent = data.get(key) || '(tidak diisi)';
        fields.append(dt, dd);
    }
    document.querySelector('#review-photos').textContent = `${photos.size} foto: ${[...photos.values()].map(photo => photo.name).join(', ') || 'tidak ada; bawa dokumen wajib ke petugas'}`;
    document.querySelector('#submission-review').hidden = false;
    stopCamera();
    document.querySelector('#submission-message').textContent = '';
});
form?.addEventListener('formdata', (event) => {
    for (const [id, photo] of photos) {
        event.formData.set(`photos[${id}]`, photo.blob, 'capture.jpg');
    }
});
form?.addEventListener('submit', () => {
    stopCamera();
    const submit = document.querySelector('#send-submission');
    submit.disabled = true;
    submit.textContent = 'Menyimpan pengajuan…';
});

function parseTicket(raw) {
    const text = raw.trim().toUpperCase();
    const match = /^(?:KIOSK:V1:)?([A-F0-9]{20})$/.exec(text);
    return match?.[1] ?? null;
}
document.querySelector('#start-scanner')?.addEventListener('click', async () => {
    stopScanner();
    const generation = scannerGeneration;
    document.querySelector('#stop-scanner').hidden = false;
    scannerMessage.textContent = 'Menunggu izin dan gambar kamera…';
    try {
        if (!navigator.mediaDevices?.getUserMedia) throw new Error('CameraUnsupported');
        const stream = await navigator.mediaDevices.getUserMedia({
            video: { facingMode: { ideal: 'environment' }, width: { ideal: 1280 }, height: { ideal: 720 } },
            audio: false,
        });
        if (generation !== scannerGeneration) {
            stream.getTracks().forEach((track) => track.stop());
            return;
        }
        scannerVideo.srcObject = stream;
        scannerVideo.hidden = false;
        await scannerVideo.play();
        if (generation !== scannerGeneration) return;
        scannerMessage.textContent = 'Arahkan QR tiket ke kamera.';
        const canvas = document.createElement('canvas');
        const context = canvas.getContext('2d', { willReadFrequently: true });
        const scan = () => {
            if (generation !== scannerGeneration) return;
            try {
                if (scannerVideo.readyState >= HTMLMediaElement.HAVE_CURRENT_DATA && scannerVideo.videoWidth > 0) {
                    const scale = Math.min(1, 960 / scannerVideo.videoWidth);
                    canvas.width = Math.round(scannerVideo.videoWidth * scale);
                    canvas.height = Math.round(scannerVideo.videoHeight * scale);
                    context.drawImage(scannerVideo, 0, 0, canvas.width, canvas.height);
                    const frame = context.getImageData(0, 0, canvas.width, canvas.height);
                    const result = jsQR(frame.data, frame.width, frame.height);
                    if (result) {
                        const ticket = parseTicket(result.data);
                        if (ticket) {
                            document.querySelector('#ticket-code').value = ticket;
                            document.querySelector('#ticket-pin').focus();
                            scannerMessage.textContent = 'Tiket terbaca. Masukkan PIN untuk melihat status.';
                            stopScanner();
                            return;
                        }
                        scannerMessage.textContent = 'QR bukan tiket KIOSK yang valid. Scanner tidak akan membuka URL dari QR.';
                    }
                }
                scannerTimer = setTimeout(scan, 250);
            } catch (error) {
                stopScanner();
                scannerMessage.textContent = 'Pemindai gagal membaca kamera. Aktifkan ulang atau gunakan scanner USB/kode manual.';
                console.error('QR decoding failed:', error.name);
            }
        };
        scan();
    } catch (error) {
        if (generation !== scannerGeneration) return;
        stopScanner();
        scannerMessage.textContent = 'Scanner kamera tidak tersedia. Periksa izin atau gunakan scanner USB/ketik kode tiket.';
        console.error('QR camera unavailable:', error.name);
    }
});
document.querySelector('#stop-scanner')?.addEventListener('click', stopScanner);
document.querySelector('#ticket-code')?.addEventListener('keydown', (event) => {
    if (event.key === 'Enter') {
        event.preventDefault();
        const ticket = parseTicket(event.currentTarget.value);
        if (ticket) {
            event.currentTarget.value = ticket;
            document.querySelector('#ticket-pin').focus();
        } else scannerMessage.textContent = 'Format tiket tidak valid. Gunakan QR tiket KIOSK, bukan QR tautan layanan.';
    }
});
function releaseDevices() {
    stopCamera();
    stopScanner();
    for (const photo of photos.values()) URL.revokeObjectURL(photo.url);
    photos.clear();
    if (pendingPhoto) URL.revokeObjectURL(pendingPhoto.url);
    pendingPhoto = null;
}
window.addEventListener('pagehide', releaseDevices);
document.addEventListener('kiosk-session-ended', () => {
    releaseDevices();
    form?.reset();
    document.querySelector('#ticket-form')?.reset();
    document.querySelectorAll('.submission-form input:not([type="hidden"]), .submission-form textarea, .lookup-form input:not([type="hidden"])').forEach((input) => {
        input.value = '';
        if (input.type === 'checkbox') input.checked = false;
    });
    document.querySelector('.receipt-paper')?.replaceChildren();
    document.querySelector('.status-result')?.replaceChildren();
    document.querySelector('#submission-review')?.replaceChildren();
    document.querySelector('#capture-preview')?.setAttribute('hidden', '');
    document.querySelector('#photo-list')?.replaceChildren();
});
document.addEventListener('visibilitychange', () => {
    if (document.hidden) {
        stopCamera();
        stopScanner();
    }
});
window.addEventListener('pageshow', (event) => {
    if (event.persisted) window.location.replace('kiosk.php');
    const submit = document.querySelector('#send-submission');
    if (submit) submit.disabled = false;
});
