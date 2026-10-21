export function faceCrop(width, height) {
    if (!Number.isFinite(width) || !Number.isFinite(height) || width <= 0 || height <= 0) {
        throw new RangeError('Camera dimensions must be positive.');
    }
    const cropWidth = Math.max(1, Math.floor(Math.min(width * 0.76, height * 0.76 * 3 / 4)));
    const cropHeight = Math.max(1, Math.floor(cropWidth * 4 / 3));
    return {
        x: Math.floor((width - cropWidth) / 2),
        y: Math.floor((height - cropHeight) / 2),
        width: cropWidth,
        height: cropHeight,
    };
}
