const MAX_PHOTO_DIMENSION = 1280;
const MAX_PHOTO_BYTES = 190 * 1024;
const JPEG_QUALITIES = [0.82, 0.72, 0.62, 0.52, 0.42, 0.34];

/**
 * Membuat foto absensi dari seluruh frame kamera.
 * Modul ini menangani gambar saja; alur kamera dan upload tetap di attendance-camera.js.
 */
export async function createAttendancePhoto({ video, facingMode, name, eventLabel, address }) {
    const dimensions = getProportionalDimensions(
        video.videoWidth,
        video.videoHeight,
        MAX_PHOTO_DIMENSION,
    );
    const canvas = document.createElement('canvas');
    canvas.width = dimensions.width;
    canvas.height = dimensions.height;

    const context = canvas.getContext('2d');
    if (!context) throw new Error('canvas-context-unavailable');

    // Gunakan seluruh frame sumber. Jangan mengambil source rectangle/crop.
    if (facingMode === 'user') {
        context.translate(canvas.width, 0);
        context.scale(-1, 1);
    }
    context.drawImage(video, 0, 0, canvas.width, canvas.height);
    context.setTransform(1, 0, 0, 1, 0, 0);

    const timestamp = formatCaptureTimestamp(new Date());
    drawWatermark(context, canvas.width, canvas.height, {
        name,
        eventLabel,
        timestamp,
        address,
    });

    const blob = await compressJpeg(canvas);
    return { blob, timestamp };
}

/** Resize hanya berdasarkan sisi terpanjang agar rasio sumber tidak berubah. */
function getProportionalDimensions(sourceWidth, sourceHeight, maxDimension) {
    if (!sourceWidth || !sourceHeight) {
        throw new Error('photo-source-dimensions-invalid');
    }

    const scale = Math.min(1, maxDimension / Math.max(sourceWidth, sourceHeight));

    return {
        width: Math.max(1, Math.round(sourceWidth * scale)),
        height: Math.max(1, Math.round(sourceHeight * scale)),
    };
}

function formatCaptureTimestamp(date) {
    return new Intl.DateTimeFormat('id-ID', {
        dateStyle: 'medium',
        timeStyle: 'short',
    }).format(date);
}

/** Gambar watermark ke frame foto; konfigurasi geometri akan disatukan pada irisan berikutnya. */
function drawWatermark(context, width, height, details) {
    const cardWidth = width * 0.8;
    const cardX = (width - cardWidth) / 2;
    const cardTop = height * 0.03;
    const padding = Math.max(6, Math.round(width * 0.012));
    const titleSize = Math.max(10, Math.round(width * 0.016));
    const bodySize = Math.max(8, Math.round(width * 0.012));
    const lineHeight = Math.max(10, Math.round(bodySize * 1.3));
    const textMaxWidth = cardWidth - padding * 2;
    const watermarkLines = [
        details.name,
        details.eventLabel + ' · ' + details.timestamp,
        details.address || 'Alamat belum tersedia',
        '© OpenStreetMap contributors',
    ];

    context.font = '600 ' + titleSize + 'px system-ui, sans-serif';
    const titleLines = wrapText(context, watermarkLines[0], textMaxWidth).slice(0, 1);

    context.font = bodySize + 'px system-ui, sans-serif';
    const eventLines = wrapText(context, watermarkLines[1], textMaxWidth).slice(0, 1);
    let addressLines = wrapText(context, watermarkLines[2], textMaxWidth);
    if (addressLines.length > 2) {
        addressLines = addressLines.slice(0, 2);
        addressLines[1] = addressLines[1].replace(/[.,;:]?$/, '…');
    }

    const attributionLines = wrapText(context, watermarkLines[3], textMaxWidth).slice(0, 1);
    const wrappedLines = [...titleLines, ...eventLines, ...addressLines, ...attributionLines];
    const watermarkHeight = padding * 2 + lineHeight * wrappedLines.length;
    const radius = Math.max(6, Math.round(width * 0.015));

    context.fillStyle = 'rgba(255, 255, 255, 0.62)';
    context.beginPath();
    context.roundRect(cardX, cardTop, cardWidth, watermarkHeight, radius);
    context.fill();
    context.strokeStyle = 'rgba(255, 255, 255, 0.28)';
    context.lineWidth = Math.max(1, width * 0.0015);
    context.stroke();

    context.fillStyle = '#18181b';
    context.textBaseline = 'top';
    let y = cardTop + padding;

    wrappedLines.forEach((line, index) => {
        context.font = (index === 0 ? '600 ' + titleSize : bodySize) + 'px system-ui, sans-serif';
        context.fillText(line, cardX + padding, y, textMaxWidth);
        y += lineHeight;
    });
}

function wrapText(context, text, maxWidth) {
    const words = String(text || '').split(/\s+/);
    const lines = [];
    let line = '';

    for (const word of words) {
        const candidate = line ? line + ' ' + word : word;
        if (line && context.measureText(candidate).width > maxWidth) {
            lines.push(line);
            line = word;
        } else {
            line = candidate;
        }
    }

    if (line) lines.push(line);
    return lines.length ? lines : [''];
}

/** Turunkan kualitas lebih dahulu, lalu resolusi; selalu pertahankan rasio aspek. */
async function compressJpeg(sourceCanvas) {
    let canvas = sourceCanvas;

    while (true) {
        for (const quality of JPEG_QUALITIES) {
            const blob = await canvasToJpeg(canvas, quality);
            if (blob && blob.size <= MAX_PHOTO_BYTES) return blob;
        }

        const smallestSide = Math.min(canvas.width, canvas.height);
        if (smallestSide <= 320) break;

        const dimensions = getProportionalDimensions(
            canvas.width,
            canvas.height,
            Math.max(320, Math.round(Math.max(canvas.width, canvas.height) * 0.8)),
        );
        const smallerCanvas = document.createElement('canvas');
        smallerCanvas.width = dimensions.width;
        smallerCanvas.height = dimensions.height;

        const context = smallerCanvas.getContext('2d');
        if (!context) throw new Error('canvas-context-unavailable');
        context.drawImage(canvas, 0, 0, smallerCanvas.width, smallerCanvas.height);
        canvas = smallerCanvas;
    }

    const finalBlob = await canvasToJpeg(canvas, 0.28);
    if (!finalBlob) throw new Error('photo-encode-failed');
    if (finalBlob.size > MAX_PHOTO_BYTES) throw new Error('photo-size-limit-exceeded');

    return finalBlob;
}

function canvasToJpeg(canvas, quality) {
    return new Promise((resolve) => canvas.toBlob(resolve, 'image/jpeg', quality));
}
