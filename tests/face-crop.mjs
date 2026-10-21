import assert from 'node:assert/strict';
import { faceCrop } from '../resources/js/face-crop.mjs';

for (const [width, height] of [[1600, 1200], [1280, 720], [720, 1280], [640, 480], [480, 640], [1920, 1080]]) {
    const crop = faceCrop(width, height);
    assert.ok(crop.x >= 0 && crop.y >= 0);
    assert.ok(crop.x + crop.width <= width && crop.y + crop.height <= height);
    assert.ok(Math.abs(crop.width / crop.height - 3 / 4) < 0.005);
    assert.ok(Math.abs(crop.x * 2 + crop.width - width) <= 1);
    assert.ok(Math.abs(crop.y * 2 + crop.height - height) <= 1);
    assert.ok(crop.width <= width * 0.76 && crop.height <= height * 0.76);
}
assert.deepEqual(faceCrop(640, 480), { x: 183, y: 58, width: 273, height: 364 });
for (const dimensions of [[0, 480], [640, 0], [-1, 100], [NaN, 100]]) {
    assert.throws(() => faceCrop(...dimensions), RangeError);
}
console.log('PASS: centered 3:4 face crop matches guide for landscape/portrait camera sizes; invalid dimensions rejected.');
