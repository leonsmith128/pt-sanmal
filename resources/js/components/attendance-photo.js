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
export async function createAttendancePhoto({ video, facingMode, name, eventLabel, address, capturedAt = new Date() }) {
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
    return {
        time: new Intl.DateTimeFormat('id-ID', {
            hour: '2-digit',
            minute: '2-digit',
            hourCycle: 'h23',
        }).format(date),
        date: new Intl.DateTimeFormat('id-ID', {
            day: '2-digit',
            month: 'short',
            year: 'numeric',
        }).format(date),
        weekday: new Intl.DateTimeFormat('id-ID', { weekday: 'long' }).format(date),
    };
}

/**
 * Watermark transparan, compact, dan menempel di kiri bawah.
 * Preview dan foto final memakai fungsi renderer yang sama.
 */
export function drawAttendanceWatermark(context, width, height, details, logo = null) {
    const marginX = Math.round(width * 0.03);
    const watermarkWidth = Math.round(width * 0.72);
    const bottom = Math.max(8, Math.round(height * 0.025));
    const logoWidth = Math.round(width * 0.11);
    const logoHeight = logo ? logoWidth * (logo.height / logo.width) : logoWidth * 0.38;
    const gap = Math.max(5, Math.round(width * 0.012));
    const timeSize = Math.max(18, Math.round(width * 0.058));
    const dateSize = Math.max(11, Math.round(width * 0.021));
    const nameSize = Math.max(12, Math.round(width * 0.022));
    const addressSize = Math.max(11, Math.round(width * 0.018));
    const lineHeight = Math.ceil(addressSize * 1.18);
    const rightEdge = Math.min(width - marginX, marginX + watermarkWidth);
    const textWidth = rightEdge - marginX;
    const textX = marginX + (logo ? logoWidth + gap : 0);
    const shadow = 'rgba(0, 0, 0, 0.82)';

    const timestamp = details.timestamp instanceof Date
        ? formatCaptureTimestamp(details.timestamp)
        : details.timestamp;
    const time = typeof timestamp === 'string'
        ? timestamp.match(/\d{1,2}:\d{2}/)?.[0] || ''
        : timestamp?.time || '';
    const capturedDate = new Date();
    const dateParts = typeof timestamp === 'object' && timestamp?.date
        ? timestamp.date
        : new Intl.DateTimeFormat('id-ID', {
            day: '2-digit',
            month: 'short',
            year: 'numeric',
        }).format(capturedDate);
    const weekday = typeof timestamp === 'object' && timestamp?.weekday
        ? timestamp.weekday
        : new Intl.DateTimeFormat('id-ID', { weekday: 'long' }).format(capturedDate);

    context.save();
    context.textBaseline = 'alphabetic';
    context.lineJoin = 'round';
    context.shadowColor = shadow;
    context.shadowBlur = Math.max(2, width * 0.0025);
    context.shadowOffsetX = 1;
    context.shadowOffsetY = 1;

    // Hitung baris alamat lebih dahulu, lalu susun seluruh watermark dari batas bawah ke atas.
    context.font = '500 ' + addressSize + 'px "Roboto Condensed", "Arial Narrow", Roboto, sans-serif';
    const address = String(details.address || 'Alamat belum tersedia');
    const addressLines = wrapText(context, address, textWidth).slice(0, 3);
    if (addressLines.length === 3) {
        addressLines[2] = addressLines[2].replace(/[.,;:]?$/, '…');
    }

    const addressFirstBaseline = height - bottom - (addressLines.length - 1) * lineHeight;
    const addressTopBaselineGap = Math.max(4, Math.round(addressSize * 0.45));
    const nameBaseline = addressFirstBaseline - addressTopBaselineGap - addressSize;
    const rowGap = Math.max(3, Math.round(nameSize * 0.18));
    const topRowHeight = Math.max(logoHeight, timeSize, dateSize * 2.05);
    const topRowY = nameBaseline - nameSize - rowGap - topRowHeight;

    if (logo) {
        const logoY = topRowY + (topRowHeight - logoHeight) / 2;
        context.drawImage(logo, marginX, logoY, logoWidth, logoHeight);
    }

    context.fillStyle = '#ffffff';
    context.font = '700 ' + timeSize + 'px "Roboto Condensed", "Arial Narrow", Roboto, sans-serif';
    context.fillText(time, textX, topRowY + timeSize);

    const dividerX = textX + context.measureText(time).width + gap * 0.55;
    context.shadowBlur = 0;
    context.fillStyle = 'rgba(255,255,255,0.8)';
    context.fillRect(
        dividerX,
        topRowY + Math.max(1, dateSize * 0.12),
        Math.max(2, width * 0.0025),
        timeSize + dateSize * 0.48,
    );

    const dateX = dividerX + gap * 0.7;
    context.shadowBlur = Math.max(2, width * 0.0025);
    context.fillStyle = '#ffffff';
    context.font = '600 ' + dateSize + 'px "Roboto Condensed", "Arial Narrow", Roboto, sans-serif';
    context.fillText(dateParts, dateX, topRowY + dateSize);
    context.font = '400 ' + dateSize + 'px "Roboto Condensed", "Arial Narrow", Roboto, sans-serif';
    context.fillText(weekday, dateX, topRowY + dateSize * 2.05);

    context.font = '600 ' + nameSize + 'px "Roboto Condensed", "Arial Narrow", Roboto, sans-serif';
    context.fillStyle = '#ffffff';
    const name = String(details.name || 'Pegawai SanMal');
    const event = String(details.eventLabel || 'Absensi');
    const nameMaxWidth = Math.min(textWidth * 0.62, textWidth);
    context.fillText(name, marginX, nameBaseline, nameMaxWidth);
    const nameWidth = Math.min(context.measureText(name).width, nameMaxWidth);
    const eventX = marginX + nameWidth + gap * 0.55;
    context.fillText('|', eventX, nameBaseline);
    context.fillText(event, eventX + gap * 0.75, nameBaseline, Math.max(0, rightEdge - eventX - gap));

    context.font = '500 ' + addressSize + 'px "Roboto Condensed", "Arial Narrow", Roboto, sans-serif';
    addressLines.forEach((line, index) => {
        context.fillText(line, marginX, addressFirstBaseline + index * lineHeight, textWidth);
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

/** Encode sekali per ukuran; turunkan resolusi sebelum mencoba kualitas berulang. */
async function compressJpeg(sourceCanvas) {
    let canvas = sourceCanvas;

    for (let attempt = 0; attempt < 4; attempt += 1) {
        const quality = attempt === 0 ? 0.68 : attempt === 1 ? 0.58 : attempt === 2 ? 0.48 : 0.38;
        const blob = await canvasToJpeg(canvas, quality);

        if (!blob) throw new Error('photo-encode-failed');
        if (blob.size <= MAX_PHOTO_BYTES) return blob;

        if (attempt === 3) break;

        const scale = Math.min(0.82, Math.sqrt(MAX_PHOTO_BYTES / blob.size) * 0.92);
        const targetMaxDimension = Math.max(
            320,
            Math.round(Math.max(canvas.width, canvas.height) * scale),
        );
        const dimensions = getProportionalDimensions(
            canvas.width,
            canvas.height,
            targetMaxDimension,
        );
        if (dimensions.width >= canvas.width && dimensions.height >= canvas.height) break;

        const smallerCanvas = document.createElement('canvas');
        smallerCanvas.width = dimensions.width;
        smallerCanvas.height = dimensions.height;

        const context = smallerCanvas.getContext('2d');
        if (!context) throw new Error('canvas-context-unavailable');
        context.drawImage(canvas, 0, 0, dimensions.width, dimensions.height);
        canvas = smallerCanvas;
    }

    const finalBlob = await canvasToJpeg(canvas, 0.3);
    if (!finalBlob) throw new Error('photo-encode-failed');
    if (finalBlob.size > MAX_PHOTO_BYTES) throw new Error('photo-size-limit-exceeded');

    return finalBlob;
}

function canvasToJpeg(canvas, quality) {
    return new Promise((resolve) => canvas.toBlob(resolve, 'image/jpeg', quality));
}
