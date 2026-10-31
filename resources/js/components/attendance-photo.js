const MAX_PHOTO_DIMENSION = 1280;
const MAX_PHOTO_BYTES = 190 * 1024;
const JPEG_QUALITIES = [0.82, 0.72, 0.62, 0.52, 0.42, 0.34];

let watermarkLogoPromise;

/**
 * Logo sementara SanMal berbentuk SVG transparan agar tetap tajam pada berbagai ukuran foto.
 */
export function loadAttendanceWatermarkLogo() {
    if (!watermarkLogoPromise) {
        watermarkLogoPromise = new Promise((resolve) => {
            const image = new Image();
            image.onload = () => resolve(image);
            image.onerror = () => resolve(null);
            image.src = '/images/sanmal-logo-watermark.svg';
        });
    }

    return watermarkLogoPromise;
}

/**
 * Membuat foto absensi dari seluruh frame kamera.
 * Modul ini menangani gambar saja; alur kamera dan upload tetap di attendance-camera.js.
 */
export async function createAttendancePhoto({ video, facingMode, name, eventLabel, address }) {
    const dimensions = getProportionalDimensions(video.videoWidth, video.videoHeight, MAX_PHOTO_DIMENSION);
    const canvas = document.createElement('canvas');
    canvas.width = dimensions.width;
    canvas.height = dimensions.height;

    const context = canvas.getContext('2d');
    if (!context) throw new Error('canvas-context-unavailable');

    if (facingMode === 'user') {
        context.translate(canvas.width, 0);
        context.scale(-1, 1);
    }
    context.drawImage(video, 0, 0, canvas.width, canvas.height);
    context.setTransform(1, 0, 0, 1, 0, 0);

    const capturedAt = new Date();
    const details = {
        name,
        eventLabel,
        timestamp: formatCaptureTimestamp(capturedAt),
        address,
    };

    drawAttendanceWatermark(context, canvas.width, canvas.height, details, await loadAttendanceWatermarkLogo());

    const blob = await compressJpeg(canvas);
    return { blob, capturedAt: capturedAt.toISOString() };
}

/** Resize hanya berdasarkan sisi terpanjang agar rasio sumber tidak berubah. */
function getProportionalDimensions(sourceWidth, sourceHeight, maxDimension) {
    if (!sourceWidth || !sourceHeight) throw new Error('photo-source-dimensions-invalid');

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

/**
 * Watermark transparan, compact, dan menempel di kiri bawah.
 * Preview dan foto final memakai fungsi renderer yang sama.
 */
export function drawAttendanceWatermark(context, width, height, details, logo = null) {
    const scale = width / 1280;
    const marginX = Math.round(width * 0.035);
    const bottom = Math.round(height * 0.045);
    const logoWidth = Math.round(width * 0.145);
    const logoHeight = logo ? logoWidth * (logo.height / logo.width) : logoWidth * 0.38;
    const gap = Math.round(width * 0.018);
    const timeSize = Math.max(22, Math.round(width * 0.075));
    const dateSize = Math.max(13, Math.round(width * 0.027));
    const nameSize = Math.max(14, Math.round(width * 0.028));
    const addressSize = Math.max(12, Math.round(width * 0.024));
    const lineHeight = Math.round(addressSize * 1.3);
    const rightEdge = width - marginX;
    const textX = marginX + (logo ? logoWidth + gap : 0);
    const topRowY = height - bottom - Math.max(logoHeight, timeSize + dateSize * 1.9) - nameSize * 1.7 - lineHeight * 2.8;
    const shadow = 'rgba(0, 0, 0, 0.82)';

    context.save();
    context.textBaseline = 'alphabetic';
    context.lineJoin = 'round';
    context.shadowColor = shadow;
    context.shadowBlur = Math.max(2, width * 0.003);
    context.shadowOffsetX = 1;
    context.shadowOffsetY = 1;

    if (logo) {
        context.drawImage(logo, marginX, topRowY + Math.max(0, timeSize * 0.12), logoWidth, logoHeight);
    }

    const time = details.timestamp.match(/\d{1,2}:\d{2}/)?.[0] || '';
    const dateParts = new Intl.DateTimeFormat('id-ID', {
        day: '2-digit',
        month: 'short',
        year: 'numeric',
    }).format(new Date());
    const weekday = new Intl.DateTimeFormat('id-ID', { weekday: 'long' }).format(new Date());

    context.fillStyle = '#ffffff';
    context.font = '700 ' + timeSize + 'px "Roboto Condensed", "Arial Narrow", Roboto, sans-serif';
    context.fillText(time, textX, topRowY + timeSize);

    const dividerX = textX + context.measureText(time).width + gap * 0.55;
    context.shadowBlur = 0;
    context.fillStyle = 'rgba(255,255,255,0.8)';
    context.fillRect(dividerX, topRowY + 2, Math.max(2, width * 0.0025), timeSize + dateSize * 0.75);

    const dateX = dividerX + gap * 0.7;
    context.shadowBlur = Math.max(2, width * 0.003);
    context.fillStyle = '#ffffff';
    context.font = '600 ' + dateSize + 'px "Roboto Condensed", "Arial Narrow", Roboto, sans-serif';
    context.fillText(dateParts, dateX, topRowY + dateSize);
    context.font = '400 ' + dateSize + 'px "Roboto Condensed", "Arial Narrow", Roboto, sans-serif';
    context.fillText(weekday, dateX, topRowY + dateSize * 2.05);

    const nameY = topRowY + Math.max(timeSize + dateSize * 1.8, logoHeight) + nameSize * 1.2;
    context.font = '600 ' + nameSize + 'px "Roboto Condensed", "Arial Narrow", Roboto, sans-serif';
    context.fillStyle = '#ffffff';
    const name = String(details.name || 'Pegawai SanMal');
    context.fillText(name, marginX, nameY, width * 0.58);
    const nameWidth = context.measureText(name).width;
    const event = String(details.eventLabel || 'Absensi');
    context.font = '600 ' + nameSize + 'px "Roboto Condensed", "Arial Narrow", Roboto, sans-serif';
    const eventX = marginX + Math.min(nameWidth, width * 0.58) + gap * 0.65;
    context.fillText('|', eventX, nameY);
    context.fillText(event, eventX + gap * 0.9, nameY, width - eventX - gap);

    const address = String(details.address || 'Alamat belum tersedia');
    context.font = '500 ' + addressSize + 'px "Roboto Condensed", "Arial Narrow", Roboto, sans-serif';
    const addressLines = wrapText(context, address, rightEdge - marginX).slice(0, 3);
    if (addressLines.length === 3) {
        addressLines[2] = addressLines[2].replace(/[.,;:]?$/, '…');
    }

    addressLines.forEach((line, index) => {
        context.fillText(line, marginX, nameY + addressSize * 1.55 + index * lineHeight, rightEdge - marginX);
    });

    context.restore();
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
