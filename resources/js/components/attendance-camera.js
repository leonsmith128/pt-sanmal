/**
 * Kamera absensi berbasis Alpine.
 *
 * Tanggung jawab:
 * - membuka/menutup kamera dan beralih kamera depan/belakang bila tersedia;
 * - mengambil GPS dan reverse geocoding alamat;
 * - memotong gambar sesuai rasio viewport, memberi watermark, lalu menampilkan
 *   hasil final sebelum file diunggah ke Livewire.
 */
export default function attendanceCamera(config = {}) {
    return {
        stream: null,
        active: false,
        starting: false,
        busy: false,
        fallback: false,
        error: null,
        name: config.name || 'Pegawai SanMal',
        eventLabel: config.eventLabel || 'Absensi',
        facingMode: 'user',
        cameraInputs: [],
        canSwitchCamera: false,
        isLandscape: window.matchMedia('(orientation: landscape)').matches,
        orientationQuery: null,
        orientationHandler: null,

        watchId: null,
        best: null,
        liveAccuracy: null,
        address: '',
        addressLoading: false,
        lastGeocodedAt: 0,
        lastGeocodedPosition: null,

        capturedBlob: null,
        capturedUrl: null,
        formattedTimestamp: '',

        get currentTimestamp() {
            return new Intl.DateTimeFormat('id-ID', {
                dateStyle: 'medium',
                timeStyle: 'short',
            }).format(new Date());
        },

        get viewportStyle() {
            const isSmallScreen = window.matchMedia('(max-width: 640px)').matches;
            const maxWidth = isSmallScreen ? '100vw' : '80vw';
            const maxHeight = isSmallScreen ? '100dvh' : '80dvh';

            // Keep the camera frame at 16:9 in landscape and 9:16 in portrait.
            // The paired width/height limits prevent the frame from exceeding the screen.
            return this.isLandscape
                ? `width:min(${maxWidth}, ${isSmallScreen ? '177.777dvh' : '142.222dvh'}); max-width:${maxWidth}; max-height:${maxHeight}; aspect-ratio:16 / 9;`
                : `width:min(${maxWidth}, ${isSmallScreen ? '56.25dvh' : '45dvh'}); max-width:${maxWidth}; max-height:${maxHeight}; aspect-ratio:9 / 16;`;
        },

        init() {
            this.name = this.$el?.dataset?.attendanceName || this.name;
            this.eventLabel = this.$el?.dataset?.attendanceEvent || this.eventLabel;
            this.orientationQuery = window.matchMedia('(orientation: landscape)');
            this.orientationHandler = (event) => {
                this.isLandscape = event.matches;
            };
            this.orientationQuery.addEventListener?.('change', this.orientationHandler);
        },

        toPlain(position) {
            return {
                latitude: position.coords.latitude,
                longitude: position.coords.longitude,
                accuracy: position.coords.accuracy,
                receivedAt: Date.now(),
            };
        },

        async resolveAddress(position, force = false) {
            if (!position) return this.address;
            // Location watch may emit several readings while the first address is loading.
            // Let the next GPS reading retry after this request finishes.
            if (this.addressLoading) return this.address;

            const previous = this.lastGeocodedPosition;
            const movedFar = !previous || this.distanceMeters(previous, position) > 75;
            if (!force && this.address && !movedFar && Date.now() - this.lastGeocodedAt < 300000) {
                return this.address;
            }

            this.addressLoading = true;
            try {
                const url = new URL('https://nominatim.openstreetmap.org/reverse');
                url.search = new URLSearchParams({
                    format: 'jsonv2',
                    lat: String(position.latitude),
                    lon: String(position.longitude),
                    zoom: '18',
                    addressdetails: '1',
                }).toString();

                const response = await fetch(url.toString(), {
                    headers: { Accept: 'application/json' },
                    signal: AbortSignal.timeout ? AbortSignal.timeout(8000) : undefined,
                });
                if (!response.ok) throw new Error('reverse-geocode-failed');
                const result = await response.json();
                this.address = typeof result.display_name === 'string'
                    ? result.display_name.slice(0, 500)
                    : '';
                this.lastGeocodedAt = Date.now();
                this.lastGeocodedPosition = position;
            } catch (error) {
                console.warn('Reverse geocoding tidak tersedia:', error);
                this.address = '';
            } finally {
                this.addressLoading = false;
            }

            return this.address;
        },

        distanceMeters(a, b) {
            const radians = (degrees) => degrees * Math.PI / 180;
            const dLat = radians(b.latitude - a.latitude);
            const dLon = radians(b.longitude - a.longitude);
            const lat1 = radians(a.latitude);
            const lat2 = radians(b.latitude);
            const value = Math.sin(dLat / 2) ** 2
                + Math.cos(lat1) * Math.cos(lat2) * Math.sin(dLon / 2) ** 2;
            return 6371000 * 2 * Math.atan2(Math.sqrt(value), Math.sqrt(1 - value));
        },

        remeasureLocation() {
            this.error = null;
            if (!navigator.geolocation) {
                this.error = 'Browser tidak mendukung pengambilan lokasi.';
                return;
            }

            navigator.geolocation.getCurrentPosition(
                async (position) => {
                    this.best = this.toPlain(position);
                    this.liveAccuracy = Math.round(position.coords.accuracy);
                    await this.resolveAddress(this.best, true);
                },
                (error) => {
                    this.error = error.code === 1
                        ? 'Izin lokasi ditolak. Aktifkan izin lokasi di pengaturan browser.'
                        : 'Lokasi belum dapat diukur ulang. Coba lagi di area yang lebih terbuka.';
                },
                { enableHighAccuracy: true, timeout: 20000, maximumAge: 0 },
            );
        },

        startLocationWatch() {
            this.stopLocationWatch();
            this.best = null;
            this.liveAccuracy = null;
            this.address = '';

            if (!navigator.geolocation) return;

            this.watchId = navigator.geolocation.watchPosition(
                (position) => {
                    const reading = this.toPlain(position);
                    const previous = this.best;
                    const outdated = !previous || reading.receivedAt - previous.receivedAt > 30000;
                    const moreAccurate = !previous || reading.accuracy < previous.accuracy * 0.9;
                    const movedFar = previous && this.distanceMeters(previous, reading) > 75;

                    if (outdated || reading.accuracy <= previous.accuracy) {
                        this.best = reading;
                        this.liveAccuracy = Math.round(reading.accuracy);

                        // Resolve an address as soon as the viewport opens, then refresh
                        // it when GPS gets meaningfully more accurate or the user moves.
                        if (!this.address || moreAccurate || movedFar) {
                            this.resolveAddress(reading);
                        }
                    }
                },
                (error) => {
                    if (error.code === 1) {
                        this.error = 'Izin lokasi ditolak. Aktifkan izin lokasi di pengaturan browser, lalu coba lagi.';
                    }
                },
                { enableHighAccuracy: true, timeout: 20000, maximumAge: 0 },
            );
        },

        stopLocationWatch() {
            if (this.watchId !== null && navigator.geolocation) {
                navigator.geolocation.clearWatch(this.watchId);
            }
            this.watchId = null;
        },

        getPosition() {
            if (this.best && Date.now() - this.best.receivedAt < 60000) {
                return Promise.resolve(this.best);
            }

            return new Promise((resolve, reject) => {
                if (!navigator.geolocation) {
                    reject(new Error('no-geolocation'));
                    return;
                }
                navigator.geolocation.getCurrentPosition(
                    (position) => resolve(this.toPlain(position)),
                    reject,
                    { enableHighAccuracy: true, timeout: 15000, maximumAge: 10000 },
                );
            });
        },

        getVideoElement() {
            const video = this.$refs?.video ?? this.$el?.querySelector('video[x-ref="video"]');
            return video instanceof HTMLVideoElement ? video : null;
        },

        async openCamera() {
            const constraints = {
                audio: false,
                video: {
                    facingMode: { ideal: this.facingMode },
                },
            };
            return navigator.mediaDevices.getUserMedia(constraints);
        },

        async refreshCameraList() {
            if (!navigator.mediaDevices?.enumerateDevices) {
                this.cameraInputs = [];
                this.canSwitchCamera = false;
                return;
            }

            const devices = await navigator.mediaDevices.enumerateDevices();
            this.cameraInputs = devices.filter((device) => device.kind === 'videoinput');
            this.canSwitchCamera = this.cameraInputs.length > 1;
        },

        async start() {
            if (this.starting || this.active) return;

            this.error = null;
            this.fallback = false;
            this.starting = true;
            this.startLocationWatch();

            try {
                if (!navigator.mediaDevices?.getUserMedia) {
                    throw new Error('camera-unavailable');
                }

                this.stream = await this.openCamera();
                this.active = true;
                await this.$nextTick();

                const video = this.getVideoElement();
                if (!video) throw new Error('video-element-not-found');

                video.muted = true;
                video.playsInline = true;
                video.srcObject = this.stream;
                await video.play();
                await this.refreshCameraList();
                this.checkFrames(video);
            } catch (error) {
                console.error('Kamera gagal dibuka:', error);
                this.stop();

                if (error.message === 'video-element-not-found') {
                    this.error = 'Elemen video tidak ditemukan. Muat ulang halaman dan coba kembali.';
                } else if (error.message === 'camera-unavailable' || error.name === 'SecurityError') {
                    this.error = 'Kamera tidak tersedia. Buka halaman melalui HTTPS atau localhost.';
                } else if (error.name === 'NotAllowedError') {
                    this.error = 'Izin kamera ditolak. Aktifkan izin kamera di pengaturan browser.';
                } else if (error.name === 'NotFoundError') {
                    this.error = 'Kamera tidak ditemukan di perangkat ini.';
                } else if (error.name === 'NotReadableError' || error.name === 'AbortError') {
                    this.error = 'Kamera sedang digunakan aplikasi lain. Tutup aplikasi tersebut lalu coba lagi.';
                } else {
                    this.error = 'Kamera tidak dapat dibuka. Periksa izin dan dukungan browser.';
                }

                this.fallback = true;
            } finally {
                this.starting = false;
            }
        },

        async switchCamera() {
            if (this.busy || this.starting) return;
            this.facingMode = this.facingMode === 'user' ? 'environment' : 'user';
            this.starting = true;

            try {
                this.stream?.getTracks().forEach((track) => track.stop());
                this.stream = await this.openCamera();
                const video = this.getVideoElement();
                if (!video) throw new Error('video-element-not-found');
                video.srcObject = this.stream;
                await video.play();
                await this.refreshCameraList();
            } catch (error) {
                this.error = 'Kamera tidak dapat diganti pada perangkat ini.';
                console.warn('Pergantian kamera gagal:', error);
            } finally {
                this.starting = false;
            }
        },

        stop() {
            const video = this.getVideoElement();
            if (video) {
                video.pause();
                video.srcObject = null;
            }
            this.stream?.getTracks().forEach((track) => track.stop());
            this.stream = null;
            this.active = false;
            this.starting = false;
            this.stopLocationWatch();
            this.clearCapturedPhoto();
        },

        checkFrames(video) {
            window.setTimeout(() => {
                if (this.active && this.stream && video.isConnected && video.videoWidth === 0) {
                    this.error = 'Kamera terbuka tetapi tidak mengirim gambar. Tutup dan buka kamera kembali.';
                    this.fallback = true;
                }
            }, 3000);
        },

        clearCapturedPhoto() {
            if (this.capturedUrl) URL.revokeObjectURL(this.capturedUrl);
            this.capturedUrl = null;
            this.capturedBlob = null;
            this.formattedTimestamp = '';
        },

        async makeFinalPhoto(video, position) {
            const viewport = video.getBoundingClientRect();
            const targetRatio = viewport.width / viewport.height;
            const sourceRatio = video.videoWidth / video.videoHeight;
            let sourceWidth = video.videoWidth;
            let sourceHeight = video.videoHeight;

            if (sourceRatio > targetRatio) {
                sourceWidth = video.videoHeight * targetRatio;
            } else {
                sourceHeight = video.videoWidth / targetRatio;
            }

            const sourceX = (video.videoWidth - sourceWidth) / 2;
            const sourceY = (video.videoHeight - sourceHeight) / 2;
            const maxDimension = 1200;
            const scale = Math.min(1, maxDimension / Math.max(sourceWidth, sourceHeight));
            const width = Math.max(1, Math.round(sourceWidth * scale));
            const height = Math.max(1, Math.round(sourceHeight * scale));
            const canvas = document.createElement('canvas');
            canvas.width = width;
            canvas.height = height;

            const context = canvas.getContext('2d');
            if (!context) throw new Error('canvas-context-unavailable');

            // Mirror pada canvas agar hasil sesuai dengan preview kamera depan.
            context.save();
            context.translate(width, 0);
            context.scale(-1, 1);
            context.drawImage(video, sourceX, sourceY, sourceWidth, sourceHeight, 0, 0, width, height);
            context.restore();

            const timestamp = new Intl.DateTimeFormat('id-ID', {
                dateStyle: 'medium',
                timeStyle: 'short',
            }).format(new Date());
            this.formattedTimestamp = timestamp;

            const padding = Math.max(12, Math.round(width * 0.025));
            const titleSize = Math.max(14, Math.round(width * 0.035));
            const bodySize = Math.max(11, Math.round(width * 0.027));
            const lineHeight = Math.max(15, Math.round(bodySize * 1.35));
            const textMaxWidth = width - padding * 2;
            const watermarkLines = [
                this.name,
                this.eventLabel + ' · ' + timestamp,
                this.address || 'Alamat tidak tersedia',
                '© OpenStreetMap contributors',
            ];
            const wrappedLines = [];
            context.font = '600 ' + titleSize + 'px system-ui, sans-serif';
            wrappedLines.push(...this.wrapText(context, watermarkLines[0], textMaxWidth));
            context.font = bodySize + 'px system-ui, sans-serif';
            wrappedLines.push(...this.wrapText(context, watermarkLines[1], textMaxWidth));
            wrappedLines.push(...this.wrapText(context, watermarkLines[2], textMaxWidth));

            context.font = bodySize + 'px system-ui, sans-serif';
            wrappedLines.push(...this.wrapText(context, watermarkLines[3], textMaxWidth));

            const watermarkHeight = padding * 2 + lineHeight * wrappedLines.length;
            const watermarkTop = padding;
            context.fillStyle = 'rgba(255, 255, 255, 0.78)';
            context.fillRect(0, watermarkTop, width, watermarkHeight);
            context.fillStyle = '#18181b';
            context.textBaseline = 'top';
            let y = watermarkTop + padding;
            wrappedLines.forEach((line, index) => {
                context.font = (index === 0 ? '600 ' + titleSize : bodySize) + 'px system-ui, sans-serif';
                context.fillText(line, padding, y, textMaxWidth);
                y += lineHeight;
            });

            const maxBytes = 190 * 1024;
            let outputCanvas = canvas;

            // Kompresi bertahap; bila foto tetap besar, kecilkan resolusi tanpa
            // mengubah komposisi crop maupun watermark yang sudah digambar.
            for (let resizeRound = 0; resizeRound < 5; resizeRound++) {
                for (const quality of [0.82, 0.72, 0.62, 0.52, 0.42, 0.34]) {
                    const blob = await new Promise((resolve) => outputCanvas.toBlob(resolve, 'image/jpeg', quality));
                    if (blob && blob.size <= maxBytes) return blob;
                }

                if (outputCanvas.width <= 480 || outputCanvas.height <= 480) break;
                const smaller = document.createElement('canvas');
                smaller.width = Math.max(1, Math.round(outputCanvas.width * 0.8));
                smaller.height = Math.max(1, Math.round(outputCanvas.height * 0.8));
                const smallerContext = smaller.getContext('2d');
                if (!smallerContext) throw new Error('canvas-context-unavailable');
                smallerContext.drawImage(outputCanvas, 0, 0, smaller.width, smaller.height);
                outputCanvas = smaller;
            }

            const finalBlob = await new Promise((resolve) => outputCanvas.toBlob(resolve, 'image/jpeg', 0.3));
            if (!finalBlob) throw new Error('photo-encode-failed');
            return finalBlob;
        },

        wrapText(context, text, maxWidth) {
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
        },

        async capture() {
            const video = this.getVideoElement();
            if (!video || !video.videoWidth || !video.videoHeight || this.busy || !this.best) return;

            this.error = null;
            this.busy = true;
            try {
                const position = await this.getPosition();
                this.best = position;
                await this.resolveAddress(position, true);
                const blob = await this.makeFinalPhoto(video, position);
                this.clearCapturedPhoto();
                this.capturedBlob = blob;
                this.capturedUrl = URL.createObjectURL(blob);
            } catch (error) {
                console.error('Gagal mengambil foto:', error);
                this.error = error.message === 'canvas-context-unavailable'
                    ? 'Foto tidak dapat diproses oleh browser ini.'
                    : 'Foto atau lokasi gagal diproses. Periksa izin lokasi dan coba lagi.';
            } finally {
                this.busy = false;
            }
        },

        retake() {
            this.clearCapturedPhoto();
            this.error = null;
        },

        async useCapturedPhoto() {
            if (!this.capturedBlob || this.busy) return;
            this.busy = true;
            this.error = null;

            try {
                const position = this.best || await this.getPosition();
                this.$wire.latitude = position.latitude;
                this.$wire.longitude = position.longitude;
                this.$wire.accuracy = position.accuracy;
                this.$wire.addressSnapshot = this.address || '';
                this.$wire.selfieSource = 'camera';

                const file = new File([this.capturedBlob], 'attendance-selfie.jpg', {
                    type: 'image/jpeg',
                    lastModified: Date.now(),
                });

                await new Promise((resolve, reject) => {
                    this.$wire.upload('selfie', file, resolve, reject);
                });

                this.stop();
            } catch (error) {
                console.error('Gagal mengunggah foto:', error);
                this.error = 'Foto gagal diunggah. Coba gunakan foto ini kembali.';
            } finally {
                this.busy = false;
            }
        },

        destroy() {
            this.stop();
            this.orientationQuery?.removeEventListener?.('change', this.orientationHandler);
        },
    };
}
