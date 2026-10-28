document.addEventListener('alpine:init', () => {
    Alpine.data('attendanceCamera', () => ({
                    stream: null,
                    active: false,
                    busy: false,
                    fallback: false,
                    error: null,
                    async openCamera() {
                        // Resolusi tidak dipaksa (foto toh dikecilkan ke 800 px); beberapa driver kamera
                        // menghasilkan layar hitam kalau diminta resolusi tertentu.
                        const attempts = [
                            { video: { facingMode: 'user' }, audio: false },
                            { video: true, audio: false }
                        ];
                        let lastError = null;
                        for (const constraints of attempts) {
                            try {
                                return await navigator.mediaDevices.getUserMedia(constraints);
                            } catch (e) {
                                lastError = e;
                                if (e.name === 'NotAllowedError' || e.name === 'SecurityError') { break; }
                            }
                        }
                        throw lastError;
                    },
                    async start() {
                        this.error = null;
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
                            try {
                                await video.play();
                            } catch (playError) {
                                this.error = 'Kamera berhasil diakses, tetapi video tidak dapat diputar. Tutup kamera lalu coba lagi atau unggah foto manual.';
                                this.stop();
                                this.fallback = true;
                                return;
                            }
                            this.warmUpLocation();
                            this.checkFrames(video);
                        } catch (e) {
                            this.stop();
                            if (e.name === 'NotAllowedError') {
                                this.error = 'Izin kamera ditolak. Aktifkan izin kamera di pengaturan browser.';
                            } else if (e.name === 'NotFoundError') {
                                this.error = 'Kamera tidak ditemukan di perangkat ini.';
                            } else if (e.name === 'NotReadableError') {
                                this.error = 'Kamera tidak bisa dibuka. Mungkin sedang dipakai aplikasi atau tab lain.';
                            } else {
                                this.error = 'Kamera tidak dapat dibuka. Coba muat ulang halaman.';
                            }
                            this.fallback = true;
                        }
                    },
                    checkFrames(video) {
                        setTimeout(() => {
                            if (! this.active) { return; }

                            if (
                                video.readyState < HTMLMediaElement.HAVE_CURRENT_DATA
                                || video.videoWidth === 0
                                || video.videoHeight === 0
                            ) {
                                this.error = 'Kamera tidak menampilkan gambar. Tutup aplikasi lain yang menggunakan kamera, lalu coba lagi atau unggah foto manual.';
                                this.stop();
                                this.fallback = true;
                            }
                        }, 5000);
                    },
                    stop() {
                        if (this.stream) { this.stream.getTracks().forEach((t) => t.stop()); }
                        this.stream = null;
                        this.active = false;
                    },
                    warmUpLocation() {
                        if (! navigator.geolocation) { return; }
                        navigator.geolocation.getCurrentPosition(
                            () => {},
                            (err) => {
                                if (err.code === 1) {
                                    this.error = 'Izin lokasi ditolak. Aktifkan izin lokasi di pengaturan browser, lalu coba lagi.';
                                }
                            },
                            { enableHighAccuracy: true, timeout: 15000, maximumAge: 0 }
                        );
                    },
                    getPosition() {
                        return new Promise((resolve, reject) => {
                            if (! navigator.geolocation) {
                                reject(new Error('no-geolocation'));
                                return;
                            }
                            navigator.geolocation.getCurrentPosition(resolve, reject, {
                                enableHighAccuracy: true,
                                timeout: 15000,
                                maximumAge: 10000
                            });
                        });
                    },
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
                                if (blob && blob.size <= limit) { return blob; }
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
                            if (! blob) { throw new Error('no-photo'); }
                            this.$wire.latitude = pos.coords.latitude;
                            this.$wire.longitude = pos.coords.longitude;
                            this.$wire.accuracy = pos.coords.accuracy;
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
                        if (! video || ! video.videoWidth || this.busy) { return; }
                        this.send(video, video.videoWidth, video.videoHeight, 'camera');
                    },
                    async onFile(event) {
                        const file = event.target.files[0];
                        if (! file || this.busy) { return; }
                        try {
                            const bitmap = await createImageBitmap(file, { imageOrientation: 'from-image' });
                            await this.send(bitmap, bitmap.width, bitmap.height, 'upload');
                        } catch (e) {
                            this.error = 'File gambar tidak dapat dibaca.';
                        }
                        event.target.value = '';
                    },
                    destroy() { this.stop(); }
                }));
});
