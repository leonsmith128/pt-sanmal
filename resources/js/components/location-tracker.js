const MAX_ACCEPTABLE_ACCURACY_METERS = 100;
const MAX_READING_AGE_MS = 15_000;
const LOCATION_WAIT_TIMEOUT_MS = 25_000;

/**
 * Mengelola GPS dan reverse geocoding di luar komponen UI kamera.
 * Tidak memeriksa jarak dari kantor; hanya kualitas dan kesegaran pembacaan.
 */
export default class LocationTracker {
    constructor({ onChange, onError } = {}) {
        this.onChange = onChange;
        this.onError = onError;
        this.watchId = null;
        this.freshnessTimer = null;
        this.best = null;
        this.liveAccuracy = null;
        this.address = '';
        this.addressLoading = false;
        this.lastGeocodedAt = 0;
        this.lastGeocodedPosition = null;
        this.waiters = [];
        this.gpsReady = false;
        this.gpsStatusText = 'Mencari lokasi GPS presisi…';
    }

    snapshot() {
        return {
            best: this.best,
            liveAccuracy: this.liveAccuracy,
            address: this.address,
            addressLoading: this.addressLoading,
            gpsReady: this.gpsReady,
            gpsStatusText: this.gpsStatusText,
        };
    }

    publish() {
        this.updateStatus();
        this.onChange?.(this.snapshot());
    }

    updateStatus() {
        const age = this.best ? Date.now() - this.best.receivedAt : Infinity;
        const fresh = age >= 0 && age <= MAX_READING_AGE_MS;
        this.gpsReady = Boolean(
            this.best && fresh && Number.isFinite(this.best.accuracy)
            && this.best.accuracy >= 0 && this.best.accuracy <= MAX_ACCEPTABLE_ACCURACY_METERS
        );

        if (!this.best || !fresh) {
            this.gpsStatusText = 'Mencari lokasi GPS presisi…';
        } else if (this.best.accuracy > MAX_ACCEPTABLE_ACCURACY_METERS) {
            this.gpsStatusText = 'Akurasi GPS ±' + Math.round(this.best.accuracy) + ' m — menunggu lokasi lebih presisi';
        } else {
            this.gpsStatusText = 'GPS siap · akurasi ±' + Math.round(this.best.accuracy) + ' m';
        }
    }

    toPlain(position) {
        return {
            latitude: position.coords.latitude,
            longitude: position.coords.longitude,
            accuracy: position.coords.accuracy,
            receivedAt: Date.now(),
        };
    }

    start() {
        if (this.watchId !== null) {
            this.publish();
            return;
        }

        if (!navigator.geolocation) {
            this.gpsStatusText = 'Browser tidak mendukung GPS';
            this.publish();
            this.onError?.('Browser tidak mendukung pengambilan lokasi.');
            return;
        }

        this.watchId = navigator.geolocation.watchPosition(
            (position) => this.handlePosition(this.toPlain(position)),
            (error) => {
                if (error.code === 1) {
                    this.onError?.('Izin lokasi ditolak. Aktifkan izin lokasi di pengaturan browser.');
                } else if (error.code === 2) {
                    this.gpsStatusText = 'Sumber lokasi tidak tersedia';
                } else if (error.code === 3) {
                    this.gpsStatusText = 'GPS masih mencari lokasi presisi…';
                }
                this.publish();
            },
            { enableHighAccuracy: true, timeout: 25_000, maximumAge: 0 },
        );

        this.freshnessTimer = window.setInterval(() => this.publish(), 1000);
        this.publish();
    }

    handlePosition(reading) {
        const previous = this.best;
        const previousFresh = previous && Date.now() - previous.receivedAt <= MAX_READING_AGE_MS;
        const validAccuracy = Number.isFinite(reading.accuracy)
            && reading.accuracy >= 0
            && reading.accuracy <= MAX_ACCEPTABLE_ACCURACY_METERS;

        // Utamakan pembacaan terbaru yang memenuhi kualitas. Jika belum ada,
        // pertahankan pembacaan terbaik yang masih segar sambil terus mencari.
        if (!previous || !previousFresh || validAccuracy || reading.accuracy < previous.accuracy) {
            this.best = reading;
            this.liveAccuracy = Math.round(reading.accuracy);
        }

        if (validAccuracy) {
            const waiters = [...this.waiters];
            this.waiters = [];
            waiters.forEach((waiter) => {
                window.clearTimeout(waiter.timer);
                waiter.resolve(reading);
            });

            const lastPosition = this.lastGeocodedPosition;
            const movedFar = !lastPosition || this.distanceMeters(lastPosition, reading) > 75;
            const addressExpired = Date.now() - this.lastGeocodedAt >= 300_000;
            if (!this.address || movedFar || addressExpired) this.resolveAddress(reading);
        }

        this.publish();
    }

    distanceMeters(a, b) {
        const radians = (degrees) => degrees * Math.PI / 180;
        const dLat = radians(b.latitude - a.latitude);
        const dLon = radians(b.longitude - a.longitude);
        const lat1 = radians(a.latitude);
        const lat2 = radians(b.latitude);
        const value = Math.sin(dLat / 2) ** 2
            + Math.cos(lat1) * Math.cos(lat2) * Math.sin(dLon / 2) ** 2;
        return 6371000 * 2 * Math.atan2(Math.sqrt(value), Math.sqrt(1 - value));
    }

    async resolveAddress(position, force = false) {
        if (!position || this.addressLoading) return this.address;

        const previous = this.lastGeocodedPosition;
        const movedFar = !previous || this.distanceMeters(previous, position) > 75;
        if (!force && this.address && !movedFar && Date.now() - this.lastGeocodedAt < 300_000) {
            return this.address;
        }

        if (movedFar) {
            this.address = '';
            this.publish();
        }

        this.addressLoading = true;
        this.publish();

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
        } finally {
            this.addressLoading = false;
            this.publish();
        }

        return this.address;
    }

    getFreshPosition() {
        if (!navigator.geolocation) return Promise.reject(new Error('no-geolocation'));

        const age = this.best ? Date.now() - this.best.receivedAt : Infinity;
        if (this.best && age >= 0 && age <= MAX_READING_AGE_MS
            && this.best.accuracy <= MAX_ACCEPTABLE_ACCURACY_METERS) {
            return Promise.resolve(this.best);
        }

        if (this.watchId === null) this.start();

        return new Promise((resolve, reject) => {
            const waiter = {
                resolve,
                reject,
                timer: window.setTimeout(() => {
                    this.waiters = this.waiters.filter((item) => item !== waiter);
                    reject(new Error('location-not-accurate'));
                }, LOCATION_WAIT_TIMEOUT_MS),
            };
            this.waiters.push(waiter);
        });
    }

    async remeasure() {
        this.onError?.(null);
        try {
            const position = await this.getFreshPosition();
            await this.resolveAddress(position, true);
            return position;
        } catch (error) {
            this.onError?.(
                error.message === 'location-not-accurate'
                    ? 'Akurasi GPS belum memadai setelah menunggu. Pindah ke area terbuka lalu coba refresh lokasi.'
                    : 'Lokasi belum dapat diperoleh. Periksa izin lokasi dan coba lagi.'
            );
            throw error;
        }
    }

    stop() {
        if (this.watchId !== null && navigator.geolocation) {
            navigator.geolocation.clearWatch(this.watchId);
        }
        this.watchId = null;

        if (this.freshnessTimer !== null) {
            window.clearInterval(this.freshnessTimer);
            this.freshnessTimer = null;
        }

        const waiters = [...this.waiters];
        this.waiters = [];
        waiters.forEach((waiter) => {
            window.clearTimeout(waiter.timer);
            waiter.reject(new Error('location-stopped'));
        });
        this.publish();
    }
}
