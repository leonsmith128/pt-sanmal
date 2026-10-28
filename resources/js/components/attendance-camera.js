/**
 * Komponen Alpine untuk absensi: kamera, GPS, kompresi foto, dan unggah ke Livewire.
 * Dipakai di Blade lewat x-data="attendanceCamera()".
 * Magic Livewire/Alpine diakses lewat `this` ($wire, $refs, $nextTick).
 */
export default function attendanceCamera() {
    return {
        stream: null,
        active: false,
        busy: false,
        fallback: false,
        error: null,

        // Lokasi: dibaca terus-menerus selama kamera terbuka, dipakai pembacaan paling akurat.
        watchId: null,
        best: null,
        liveAccuracy: null,

        // ---------- Lokasi ----------
        toPlain(pos) {
            return {
                latitude: pos.coords.latitude,
                longitude: pos.coords.longitude,
                accuracy: pos.coords.accuracy,
                receivedAt: Date.now(),
            };
        },

        startLocationWatch() {
            this.stopLocationWatch();
            this.best = null;
            this.liveAccuracy = null;

            if (! navigator.geolocation) {
                return;
            }

            this.watchId = navigator.geolocation.watchPosition(
                (pos) => {
                    const reading = this.toPlain(pos);
                    const outdated = ! this.best || (reading.receivedAt - this.best.receivedAt) > 30000;

                    // Simpan pembacaan paling akurat; yang lebih buruk hanya menggantikan bila yang tersimpan sudah lama.
                    if (outdated || reading.accuracy <= this.best.accuracy) {
                        this.best = reading;
                    }
                    this.liveAccuracy = Math.round(this.best.accuracy);
                },
                (err) => {
                    if (err.code === 1) {
                        this.error = 'Izin lokasi ditolak. Aktifkan izin lokasi di pengaturan browser, lalu coba lagi.';
                    }
                },
                { enableHighAccuracy: true, timeout: 20000, maximumAge: 0 }
            );
        },

        stopLocationWatch() {
            if (this.watchId !== null && navigator.geolocation) {
                navigator.geolocation.clearWatch(this.watchId);
            }
            this.watchId = null;
        },

        getPosition() {
            // Pakai pembacaan terbaik dari pemantauan bila masih segar (< 60 detik).
            if (this.best && (Date.now() - this.best.receivedAt) < 60000) {
                return Promise.resolve(this.best);
            }

            return new Promise((resolve, reject) => {
                if (! navigator.geolocation) {
                    reject(new Error('no-geolocation'));
                    return;
                }
                navigator.geolocation.getCurrentPosition(
                    (pos) => resolve(this.toPlain(pos)),
                    reject,
                    { enableHighAccuracy: true, timeout: 15000, maximumAge: 10000 }
                );
            });
        },

        // ---------- Kamera ----------
        async openCamera() {
            // Resolusi tidak dipaksa (foto toh dikecilkan ke 800 px); beberapa driver kamera
            // menghasilkan layar hitam kalau diminta resolusi tertentu.
            const attempts = [
                { video: { facingMode: 'user' }, audio: false },
                { video: true, audio: false },
            ];
            let lastError = null;
            for (const constraints of attempts) {
                try {
                    return await navigator.mediaDevices.getUserMedia(constraints);
                } catch (e) {
                    lastError = e;
                    if (e.name === 'NotAllowedError' || e.name === 'SecurityError') {
                        break;
                    }
                }
            }
            throw lastError;
        },

        async start() {
            this.error = null;

            // Lokasi dimulai lebih dulu dan berjalan paralel dengan kamera,
            // jadi tidak bergantung pada keberhasilan kamera.
            this.startLocationWatch();

            if (! navigator.mediaDevices || ! navigator.mediaDevices.getUserMedia) {
                this.error = 'Kamera tidak tersedia. Buka lewat HTTPS atau localhost, atau unggah foto manual.';
                this.fallback = true;
                return;
            }

            try {
                this.stream = await this.openCamera();
                this.active = true;
                await this.$nextTick();

                const video = this.$refs.video;
                video.muted = true;
                video.srcObject = this.stream;
                try { await video.play(); } catch (playError) { /* autoplay akan menjalankannya */ }

                this.checkFrames(video);
            } catch (e) {
                console.error('Kamera gagal dibuka:', e);
                this.stop();

                if (e.name === 'NotAllowedError') {
                    this.error = 'Izin kamera ditolak. Aktifkan izin kamera di pengaturan browser.';
                } else if (e.name === 'NotFoundError') {
                    this.error = 'Kamera tidak ditemukan di perangkat ini.';
                } else if (e.name === 'NotReadableError' || e.name === 'AbortError') {
                    this.error = 'Kamera gagal dinyalakan oleh sistem. Tutup aplikasi lain yang memakai kamera (misalnya OBS atau Zoom), lalu coba lagi.';
                } else {
                    this.error = 'Kamera tidak dapat dibuka (' + (e.name || 'error') + '). Coba muat ulang halaman.';
                }
                this.fallback = true;
            }
        },

        stop() {
            if (this.stream) {
                this.stream.getTracks().forEach((t) => t.stop());
            }
            this.stream = null;
            this.active = false;
            this.stopLocationWatch();
        },

        checkFrames(video) {
            setTimeout(() => {
                if (this.active && video.videoWidth === 0) {
                    this.error = 'Kamera terbuka tetapi tidak mengirim gambar. Pastikan kamera tidak dipakai aplikasi atau tab lain, lalu tutup dan buka kembali.';
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
                canvas.width = Math.round(w * scale);
                canvas.height = Math.round(h * scale);
                canvas.getContext('2d').drawImage(source, 0, 0, canvas.width, canvas.height);
                for (const q of [0.8, 0.7, 0.6, 0.5, 0.4]) {
                    blob = await new Promise((res) => canvas.toBlob(res, 'image/jpeg', q));
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
                const [pos, blob] = await Promise.all([this.getPosition(), this.compress(source, w, h)]);
                if (! blob) {
                    throw new Error('no-photo');
                }
                this.$wire.latitude = pos.latitude;
                this.$wire.longitude = pos.longitude;
                this.$wire.accuracy = pos.accuracy;
                this.$wire.selfieSource = kind;
                const file = new File([blob], 'selfie.jpg', { type: 'image/jpeg' });
                await new Promise((resolve, reject) => this.$wire.upload('selfie', file, resolve, reject));
                this.stop();
            } catch (e) {
                if (typeof GeolocationPositionError !== 'undefined' && e instanceof GeolocationPositionError) {
                    this.error = e.code === 1
                        ? 'Izin lokasi ditolak. Aktifkan izin lokasi di pengaturan browser, lalu coba lagi.'
                        : 'Lokasi tidak dapat diambil. Coba lagi di tempat yang lebih terbuka.';
                } else if (e && e.message === 'no-geolocation') {
                    this.error = 'Browser ini tidak mendukung GPS.';
                } else {
                    this.error = 'Gagal memproses atau mengunggah foto. Coba lagi.';
                }
            } finally {
                this.busy = false;
            }
        },

        capture() {
            const video = this.$refs.video;
            if (! video || ! video.videoWidth || this.busy) {
                return;
            }
            this.send(video, video.videoWidth, video.videoHeight, 'camera');
        },

        async onFile(event) {
            const file = event.target.files[0];
            if (! file || this.busy) {
                return;
            }
            try {
                const bitmap = await createImageBitmap(file, { imageOrientation: 'from-image' });
                await this.send(bitmap, bitmap.width, bitmap.height, 'upload');
            } catch (e) {
                this.error = 'File gambar tidak dapat dibaca.';
            }
            event.target.value = '';
        },

        destroy() {
            this.stop();
        },
    };
}
