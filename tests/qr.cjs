const assert = require('node:assert/strict');
const { createHash } = require('node:crypto');
const QRCode = require('qrcode');
const jsQR = require('jsqr');

const tickets = ['1AC07CA720DF3C67D0D1', ...Array.from({ length: 128 }, (_, i) =>
    createHash('sha256').update(`test-${i}`).digest('hex').slice(0, 20).toUpperCase())];
for (const ticket of tickets) {
    const payload = `KIOSK:V1:${ticket}`;
    const qr = QRCode.create(payload, { errorCorrectionLevel: 'M' });
    const scale = 6;
    const size = (qr.modules.size + 4) * scale;
    const pixels = new Uint8ClampedArray(size * size * 4).fill(255);
    for (let y = 0; y < qr.modules.size; y++) {
        for (let x = 0; x < qr.modules.size; x++) {
            if (!qr.modules.get(y, x)) continue;
            for (let dy = 0; dy < scale; dy++) {
                for (let dx = 0; dx < scale; dx++) {
                    const offset = (((y + 2) * scale + dy) * size + (x + 2) * scale + dx) * 4;
                    pixels[offset] = pixels[offset + 1] = pixels[offset + 2] = 0;
                }
            }
        }
    }
    assert.equal(jsQR(pixels, size, size)?.data, payload, `QR tidak terbaca: ${ticket}`);
}
console.log(`PASS: ${tickets.length} QR tiket dapat dibuat dan dibaca secara lokal.`);
