/**
 * Penjaga kirim ganda untuk form non-Livewire.
 *
 * Pemakaian:
 *   <form method="POST" x-data="submitGuard" x-on:submit="start()">
 *       <x-ui.button type="submit" x-bind:disabled="submitting" x-bind:aria-busy="submitting">Simpan</x-ui.button>
 *   </form>
 *
 * Ini hanya mencegah klik ganda di sisi klien. Server tetap penjaga akhir.
 * `pageshow` mengembalikan tombol saat pengguna menekan Back (bfcache).
 * Halaman Livewire memakai wire:loading dan wire:target, bukan penjaga ini.
 */
export default () => ({
    submitting: false,

    init() {
        window.addEventListener('pageshow', () => {
            this.submitting = false;
        });
    },

    start() {
        if (this.submitting) {
            return false;
        }

        // Tunda satu tick supaya field tombol submit tetap ikut terkirim sebelum tombol dinonaktifkan.
        setTimeout(() => {
            this.submitting = true;
        }, 0);

        return true;
    },
});
