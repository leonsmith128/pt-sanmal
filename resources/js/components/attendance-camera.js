import LocationTracker from './location-tracker.js';
import { createAttendancePhoto } from './attendance-photo.js';
/**
 * Kamera absensi berbasis Alpine.
 *
 * Tanggung jawab:
 * - membuka/menutup kamera dan beralih kamera depan/belakang bila tersedia;
 * - mengambil GPS dan reverse geocoding alamat;
 * - mengirim frame kamera ke pemroses foto dan mengunggah hasilnya ke Livewire.
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
        activeDeviceId: null,
        videoReady: false,
        isLandscape: window.matchMedia('(orientation: landscape)').matches,
        orientationQuery: null,
        orientationHandler: null,

        locationTracker: null,
        best: null,
        liveAccuracy: null,
        gpsReady: false,
        gpsStatusText: 'Mencari lokasi GPS presisi…',
        address: '',
        addressLoading: false,

        capturedBlob: null,
        capturedUrl: null,
        capturedPosition: null,
        capturedAddress: '',
        formattedTimestamp: '',

        get currentTimestamp() {
            return new Intl.DateTimeFormat('id-ID', {
                dateStyle: 'medium',
                timeStyle: 'short',
            }).format(new Date());
        },

        get viewportStyle() {
            const isSmallScreen = window.matchMedia('(max-width: 640px)').matches;

            // HP memakai seluruh layar. Laptop memakai maksimal 80% layar.
            // Rasio viewport hanya mengatur tampilan; foto final memakai rasio asli kamera.
            if (isSmallScreen) {
                return this.isLandscape
                    ? 'width:100vw; height:100dvh; max-width:100vw; max-height:100dvh; aspect-ratio:16 / 9;'
                    : 'width:100vw; height:100dvh; max-width:100vw; max-height:100dvh; aspect-ratio:9 / 16;';
            }

            return this.isLandscape
                ? 'width:min(80vw, 142.222dvh); max-width:80vw; max-height:80dvh; aspect-ratio:16 / 9;'
                : 'width:min(45dvh, 80vw); max-width:80vw; max-height:80dvh; aspect-ratio:9 / 16;';
        },

        init() {
            this.name = this.$el?.dataset?.attendanceName || this.name;
            this.eventLabel = this.$el?.dataset?.attendanceEvent || this.eventLabel;
            this.orientationQuery = window.matchMedia('(orientation: landscape)');
            this.orientationHandler = (event) => {
                this.isLandscape = event.matches;
            };
            this.orientationQuery.addEventListener?.('change', this.orientationHandler);

            this.locationTracker = new LocationTracker({
                onChange: (state) => {
                    this.best = state.best;
                    this.liveAccuracy = state.liveAccuracy;
                    this.address = state.address;
                    this.addressLoading = state.addressLoading;
                    this.gpsReady = state.gpsReady;
                    this.gpsStatusText = state.gpsStatusText;
                },
                onError: (message) => {
                    if (message) {
                        this.error = message;
                        this.gpsStatusText = message;
                    }
                },
            });
        },

        startLocationWatch() {
            this.locationTracker?.start();
        },

        stopLocationWatch() {
            this.locationTracker?.stop();
        },

        async remeasureLocation() {
            this.error = null;
            try {
                const position = await this.locationTracker.remeasure();
                this.best = position;
                this.liveAccuracy = Math.round(position.accuracy);
            } catch (error) {
                // LocationTracker already supplies a user-facing error message.
            }
        },

        getPosition() {
            if (!this.locationTracker) return Promise.reject(new Error('no-geolocation'));
            return this.locationTracker.getFreshPosition();
        },

        getVideoElement() {
            const video = this.$refs?.video ?? this.$el?.querySelector('video[x-ref="video"]');
            return video instanceof HTMLVideoElement ? video : null;
        },

        async openCamera(deviceId = null) {
            const video = deviceId
                ? { deviceId: { exact: deviceId } }
                : { facingMode: { ideal: this.facingMode } };

            return navigator.mediaDevices.getUserMedia({ audio: false, video });
        },

        async waitForVideoFrame(video, timeoutMs = 10_000) {
            const hasFrame = () => video.readyState >= HTMLMediaElement.HAVE_CURRENT_DATA
                && video.videoWidth > 0
                && video.videoHeight > 0;

            if (hasFrame()) {
                this.videoReady = true;
                return;
            }

            this.videoReady = false;

            await new Promise((resolve, reject) => {
                let timeout;

                const cleanup = () => {
                    video.removeEventListener('loadeddata', onFrame);
                    video.removeEventListener('canplay', onFrame);
                    window.clearTimeout(timeout);
                };

                const onFrame = () => {
                    if (!hasFrame()) return;
                    cleanup();
                    this.videoReady = true;
                    resolve();
                };

                video.addEventListener('loadeddata', onFrame);
                video.addEventListener('canplay', onFrame);
                timeout = window.setTimeout(() => {
                    cleanup();
                    reject(new Error('video-frame-timeout'));
                }, timeoutMs);

                onFrame();
            });
        },

        updateCameraMetadata(stream) {
            const settings = stream?.getVideoTracks?.()[0]?.getSettings?.() ?? {};
            this.activeDeviceId = settings.deviceId || null;
            this.facingMode = settings.facingMode || this.facingMode;
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
                this.updateCameraMetadata(this.stream);
                this.active = true;
                await this.$nextTick();

                const video = this.getVideoElement();
                if (!video) throw new Error('video-element-not-found');

                video.muted = true;
                video.playsInline = true;
                video.srcObject = this.stream;
                await video.play();
                await this.waitForVideoFrame(video);
                await this.refreshCameraList().catch(() => {});
            } catch (error) {
                console.error('Kamera gagal dibuka:', error);
                this.stop();

                if (error.message === 'video-element-not-found') {
                    this.error = 'Elemen video tidak ditemukan. Muat ulang halaman dan coba kembali.';
                } else if (error.message === 'video-frame-timeout') {
                    this.error = 'Kamera terbuka tetapi belum mengirim gambar. Tutup aplikasi kamera lain lalu coba kembali.';
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
            if (this.busy || this.starting || !this.canSwitchCamera) return;

            const previousFacingMode = this.facingMode;
            const previousStream = this.stream;
            let nextStream = null;

            this.facingMode = previousFacingMode === 'user' ? 'environment' : 'user';
            this.starting = true;
            this.error = null;

            try {
                // Keep the current camera running until the replacement is ready.
                const alternative = this.activeDeviceId
                    ? this.cameraInputs.find((device) => device.deviceId && device.deviceId !== this.activeDeviceId)
                    : null;
                this.facingMode = previousFacingMode === 'user' ? 'environment' : 'user';
                nextStream = await this.openCamera(alternative?.deviceId || null);

                const video = this.getVideoElement();
                if (!video) throw new Error('video-element-not-found');

                video.srcObject = nextStream;
                await video.play();
                await this.waitForVideoFrame(video);

                this.stream = nextStream;
                this.updateCameraMetadata(nextStream);
                previousStream?.getTracks().forEach((track) => track.stop());

                // Device enumeration is informational; it must not invalidate a
                // camera stream that has already switched successfully.
                await this.refreshCameraList().catch(() => {});
            } catch (error) {
                nextStream?.getTracks().forEach((track) => track.stop());
                this.facingMode = previousFacingMode;

                const video = this.getVideoElement();
                if (video && previousStream && video.srcObject !== previousStream) {
                    video.srcObject = previousStream;
                    await video.play().catch(() => {});
                    this.videoReady = video.videoWidth > 0 && video.videoHeight > 0;
                }

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
            this.activeDeviceId = null;
            this.videoReady = false;
            this.active = false;
            this.starting = false;
            this.stopLocationWatch();
            this.clearCapturedPhoto();
        },

        clearCapturedPhoto() {
            if (this.capturedUrl) URL.revokeObjectURL(this.capturedUrl);
            this.capturedUrl = null;
            this.capturedBlob = null;
            this.formattedTimestamp = '';
            this.capturedPosition = null;
            this.capturedAddress = '';
        },

        async capture() {
            const video = this.getVideoElement();
            if (!video || !this.videoReady || !video.videoWidth || !video.videoHeight || this.busy) return;

            this.error = null;
            this.busy = true;
            this.clearCapturedPhoto();
            try {
                let position = await this.getPosition();
                this.best = position;
                this.liveAccuracy = Math.round(position.accuracy);
                await this.locationTracker.resolveAddress(position);

                // Reverse geocoding bisa memerlukan waktu. Pastikan posisi yang
                // dipakai saat foto dibuat tetap segar setelah alamat diperoleh.
                if (Date.now() - (position.measuredAt ?? position.receivedAt) > 15_000) {
                    position = await this.getPosition();
                    this.best = position;
                    this.liveAccuracy = Math.round(position.accuracy);
                }

                const addressSnapshot = this.address || '';
                const photo = await createAttendancePhoto({
                    video,
                    facingMode: this.facingMode,
                    name: this.name,
                    eventLabel: this.eventLabel,
                    address: addressSnapshot,
                });
                this.formattedTimestamp = photo.timestamp;
                const blob = photo.blob;
                this.capturedPosition = position;
                this.capturedAddress = addressSnapshot;
                this.capturedBlob = blob;
                this.capturedUrl = URL.createObjectURL(blob);
            } catch (error) {
                console.error('Gagal mengambil foto:', error);
                this.error = error.message === 'canvas-context-unavailable'
                    ? 'Foto tidak dapat diproses oleh browser ini.'
                    : error.message === 'photo-size-limit-exceeded'
                        ? 'Ukuran foto tidak dapat diperkecil hingga batas 190 KB. Coba ambil foto kembali.'
                        : error.message === 'location-not-accurate'
                        ? 'Akurasi GPS belum memadai. Tunggu hingga GPS siap atau coba refresh lokasi.'
                        : error.message === 'location-stopped'
                            ? 'Pemantauan lokasi berhenti. Buka kamera kembali dan coba lagi.'
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
                const position = this.capturedPosition;
                if (!position) throw new Error('captured-location-missing');
                this.$wire.latitude = position.latitude;
                this.$wire.longitude = position.longitude;
                this.$wire.accuracy = position.accuracy;
                this.$wire.addressSnapshot = this.capturedAddress || '';
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
