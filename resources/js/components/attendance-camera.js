
/**
 * Komponen Alpine untuk absensi: kamera, GPS, kompresi foto, dan unggah ke Livewire.
 * Dipakai di Blade lewat x-data="attendanceCamera()".
 */
export default function attendanceCamera() {
    return {
        stream: null,
        active: false,
        starting: false,
        busy: false,
        fallback: false,
        error: null,

        // ---------- Lokasi ----------
        watchId: null,
        best: null,
        liveAccuracy: null,

        toPlain(pos) {
            return {
                latitude: pos.coords.latitude,
                longitude: pos.coords.longitude,
                accuracy: pos.coords.accuracy,
                receivedAt: Date.now(),
            };
        },

        remeasureLocation() {
            this.error = null;
            this.startLocationWatch();
            if (!navigator.geolocation) {
                this.error = 'Browser tidak mendukung pengambilan lokasi.';
                return;
            }

            navigator.geolocation.getCurrentPosition(
                (pos) => {
                    this.best = this.toPlain(pos);
                    this.liveAccuracy = Math.round(pos.coords.accuracy);
                },
                (err) => {
                    this.error = err.code === 1
                        ? 'Izin lokasi ditolak. Aktifkan izin lokasi di pengaturan browser.'
                        : 'Lokasi belum dapat diukur ulang. Coba lagi di area yang lebih terbuka.';
                },
                { enableHighAccuracy: true, timeout: 20000, maximumAge: 0 }
            );
        },

        startLocationWatch() {
            this.stopLocationWatch();
            this.best = null;
            this.liveAccuracy = null;

            if (!navigator.geolocation) {
                return;
            }

            this.watchId = navigator.geolocation.watchPosition(
                (pos) => {
                    const reading = this.toPlain(pos);
                    const outdated =
                        !this.best ||
                        reading.receivedAt - this.best.receivedAt > 30000;

                    if (outdated || reading.accuracy <= this.best.accuracy) {
                        this.best = reading;
                    }

                    this.liveAccuracy = Math.round(this.best.accuracy);
                },
                (err) => {
                    if (err.code === 1) {
                        this.error =
                            'Izin lokasi ditolak. Aktifkan izin lokasi di pengaturan browser, lalu coba lagi.';
                    }
                },
                {
                    enableHighAccuracy: true,
                    timeout: 20000,
                    maximumAge: 0,
                }
            );
        },

        stopLocationWatch() {
            if (this.watchId !== null && navigator.geolocation) {
                navigator.geolocation.clearWatch(this.watchId);
            }

            this.watchId = null;
        },

        getPosition() {
            // Gunakan pembacaan GPS terbaik yang masih segar (< 60 detik).
            if (
                this.best &&
                Date.now() - this.best.receivedAt < 60000
            ) {
                return Promise.resolve(this.best);
            }

            return new Promise((resolve, reject) => {
                if (!navigator.geolocation) {
                    reject(new Error('no-geolocation'));
                    return;
                }

                navigator.geolocation.getCurrentPosition(
                    (pos) => resolve(this.toPlain(pos)),
                    reject,
                    {
                        enableHighAccuracy: true,
                        timeout: 15000,
                        maximumAge: 10000,
                    }
                );
            });
        },

        // ---------- Kamera ----------

        /**
         * Cari elemen video melalui $refs terlebih dahulu.
         * Jika Alpine tidak menyediakan ref, cari langsung di elemen
         * root komponen ini sebagai fallback.
         */
        getVideoElement() {
            const video =
                this.$refs?.video ??
                this.$el?.querySelector('video[x-ref="video"]');

            return video instanceof HTMLVideoElement ? video : null;
        },

        async openCamera() {
            const attempts = [
                { video: { facingMode: 'user' }, audio: false },
                { video: true, audio: false },
            ];

            let lastError = null;

            for (const constraints of attempts) {
                try {
                    return await navigator.mediaDevices.getUserMedia(
                        constraints
                    );
                } catch (e) {
                    lastError = e;

                    // Jangan mengulang permintaan jika izin ditolak.
                    if (
                        e.name === 'NotAllowedError' ||
                        e.name === 'SecurityError'
                    ) {
                        break;
                    }
                }
            }

            throw lastError ?? new Error('camera-open-failed');
        },

        async start() {

            // Cegah permintaan kamera ganda saat izin masih diproses.
            if (this.starting || this.active) {
                return;
            }

            this.error = null;
            this.fallback = false;
            this.starting = true;

            // Pemantauan GPS berjalan paralel dengan pembukaan kamera.
            this.startLocationWatch();

            try {
                if (
                    !navigator.mediaDevices ||
                    !navigator.mediaDevices.getUserMedia
                ) {
                    throw new Error('camera-unavailable');
                }
                
                // Log for Camera
                console.log('Komponen Alpine:', this.$el);
                console.log('Referensi Alpine:', this.$refs);
                console.log(
                    'Video di dalam komponen:',
                    this.$el?.querySelectorAll('video').length
                );
                console.log(
                    'Video di seluruh halaman:',
                    document.querySelectorAll('video').length
                );

                this.stream = await this.openCamera();


                this.active = true;

                // Tunggu Alpine memperbarui DOM setelah active menjadi true.
                await this.$nextTick();

                // Gunakan helper agar tidak bergantung pada $refs saja.
                const video = this.getVideoElement();

                if (!video) {
                    throw new Error('video-element-not-found');
                }

                video.muted = true;
                video.playsInline = true;
                video.srcObject = this.stream;

                await video.play();

                this.checkFrames(video);
            } catch (e) {
                console.error('Kamera gagal dibuka:', e);

                this.stop();

                if (e.message === 'video-element-not-found') {
                    this.error =
                        'Elemen video tidak ditemukan. Muat ulang halaman dan coba kembali.';
                } else if (e.message === 'camera-unavailable') {
                    this.error =
                        'Kamera tidak tersedia. Buka lewat HTTPS atau localhost, atau unggah foto manual.';
                } else if (e.name === 'NotAllowedError') {
                    this.error =
                        'Izin kamera ditolak. Aktifkan izin kamera di pengaturan browser.';
                } else if (e.name === 'NotFoundError') {
                    this.error =
                        'Kamera tidak ditemukan di perangkat ini.';
                } else if (
                    e.name === 'NotReadableError' ||
                    e.name === 'AbortError'
                ) {
                    this.error =
                        'Kamera gagal dinyalakan oleh sistem. Tutup aplikasi lain yang memakai kamera, misalnya OBS atau Zoom, lalu coba lagi.';
                } else if (
                    e.name === 'NotSupportedError' ||
                    e.name === 'NotAllowedError'
                ) {
                    this.error =
                        'Video tidak dapat diputar. Periksa izin kamera dan dukungan browser.';
                } else if (e.name === 'NotSupportedError') {
                    this.error =
                        'Browser tidak mendukung pemutaran video kamera.';
                } else {
                    this.error =
                        'Kamera tidak dapat dibuka (' +
                        (e.name || 'error') +
                        '). Coba muat ulang halaman.';
                }

                this.fallback = true;
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

            if (this.stream) {
                this.stream.getTracks().forEach((track) => track.stop());
            }

            this.stream = null;
            this.active = false;
            this.stopLocationWatch();
        },

        checkFrames(video) {
            setTimeout(() => {
                if (
                    this.active &&
                    this.stream &&
                    video.isConnected &&
                    video.videoWidth === 0
                ) {
                    this.error =
                        'Kamera terbuka tetapi tidak mengirim gambar. Pastikan kamera tidak dipakai aplikasi atau tab lain, lalu tutup dan buka kembali.';
                    this.fallback = true;
                }
            }, 3000);
        },

        // ---------- Foto ----------

        async compress(source, w, h) {
            const limit = 190 * 1024;
            let scale = Math.min(1, 800 / Math.max(w, h));
            let blob = null;

            for (let round = 0; round < 4; round++) {
                const canvas = document.createElement('canvas');

                canvas.width = Math.max(1, Math.round(w * scale));
                canvas.height = Math.max(1, Math.round(h * scale));

                const context = canvas.getContext('2d');

                if (!context) {
                    throw new Error('canvas-context-unavailable');
                }

                context.drawImage(
                    source,
                    0,
                    0,
                    canvas.width,
                    canvas.height
                );

                // Watermark digambar sekali pada hasil capture, bukan setiap frame preview.
                const padding = Math.max(12, Math.round(canvas.width * 0.025));
                const lineHeight = Math.max(16, Math.round(canvas.width * 0.035));
                const name = document.querySelector('[data-attendance-watermark-name]')?.textContent?.trim() || 'Absensi SanMal';
                const eventLabel = document.querySelector('[data-attendance-watermark-event]')?.textContent?.trim() || 'Foto absensi';
                const locationText = this.best
                    ? this.best.latitude.toFixed(5) + ', ' + this.best.longitude.toFixed(5)
                    : 'Lokasi tidak tersedia';
                const accuracyText = this.best
                    ? 'Akurasi ±' + Math.round(this.best.accuracy) + ' m'
                    : 'Akurasi tidak tersedia';
                const timestamp = new Intl.DateTimeFormat('id-ID', {
                    dateStyle: 'medium',
                    timeStyle: 'short',
                }).format(new Date());
                const watermarkHeight = padding * 2 + lineHeight * 4;

                context.fillStyle = 'rgba(0, 0, 0, 0.64)';
                context.fillRect(0, canvas.height - watermarkHeight, canvas.width, watermarkHeight);
                context.fillStyle = '#ffffff';
                context.textBaseline = 'top';
                context.font = '600 ' + Math.max(14, Math.round(canvas.width * 0.034)) + 'px system-ui, sans-serif';
                context.fillText(name.slice(0, 64), padding, canvas.height - watermarkHeight + padding);
                context.font = Math.max(12, Math.round(canvas.width * 0.027)) + 'px system-ui, sans-serif';
                context.fillText(eventLabel.slice(0, 64) + ' · ' + timestamp, padding, canvas.height - watermarkHeight + padding + lineHeight);
                context.fillText(locationText, padding, canvas.height - watermarkHeight + padding + lineHeight * 2);
                context.fillText(accuracyText, padding, canvas.height - watermarkHeight + padding + lineHeight * 3);

                for (const quality of [0.8, 0.7, 0.6, 0.5, 0.4]) {
                    blob = await new Promise((resolve) => {
                        canvas.toBlob(resolve, 'image/jpeg', quality);
                    });

                    if (blob && blob.size <= limit) {
                        return blob;
                    }
                }

                scale *= 0.8;
            }

            return blob;
        },

        async send(source, w, h, kind) {
            this.error = null;
            this.busy = true;

            try {
                const [pos, blob] = await Promise.all([
                    this.getPosition(),
                    this.compress(source, w, h),
                ]);

                if (!blob) {
                    throw new Error('no-photo');
                }

                this.$wire.latitude = pos.latitude;
                this.$wire.longitude = pos.longitude;
                this.$wire.accuracy = pos.accuracy;
                this.$wire.selfieSource = kind;

                const file = new File([blob], 'selfie.jpg', {
                    type: 'image/jpeg',
                });

                await new Promise((resolve, reject) => {
                    this.$wire.upload(
                        'selfie',
                        file,
                        resolve,
                        reject
                    );
                });

                this.stop();
            } catch (e) {
                if (
                    typeof GeolocationPositionError !== 'undefined' &&
                    e instanceof GeolocationPositionError
                ) {
                    this.error =
                        e.code === 1
                            ? 'Izin lokasi ditolak. Aktifkan izin lokasi di pengaturan browser, lalu coba lagi.'
                            : 'Lokasi tidak dapat diambil. Coba lagi di tempat yang lebih terbuka.';
                } else if (e?.message === 'no-geolocation') {
                    this.error = 'Browser ini tidak mendukung GPS.';
                } else if (
                    e?.message === 'canvas-context-unavailable'
                ) {
                    this.error =
                        'Foto tidak dapat diproses oleh browser ini.';
                } else {
                    console.error('Gagal memproses atau mengunggah foto:', e);
                    this.error =
                        'Gagal memproses atau mengunggah foto. Coba lagi.';
                }
            } finally {
                this.busy = false;
            }
        },

        capture() {
            const video = this.getVideoElement();

            if (
                !video ||
                !video.videoWidth ||
                !video.videoHeight ||
                this.busy
            ) {
                return;
            }

            this.send(
                video,
                video.videoWidth,
                video.videoHeight,
                'camera'
            );
        },

        async onFile(event) {
            const file = event.target.files?.[0];

            if (!file || this.busy) {
                return;
            }

            try {
                const bitmap = await createImageBitmap(file, {
                    imageOrientation: 'from-image',
                });

                try {
                    await this.send(
                        bitmap,
                        bitmap.width,
                        bitmap.height,
                        'upload'
                    );
                } finally {
                    bitmap.close?.();
                }
            } catch (e) {
                console.error('Gagal membaca file gambar:', e);
                this.error = 'File gambar tidak dapat dibaca.';
            }

            event.target.value = '';
        },

        destroy() {
            this.stop();
        },
    };
}
