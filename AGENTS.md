# Instruksi untuk AI Coding Agent

Dokumen ini adalah aturan kerja repository. Baca juga status proyek dan arsitektur serta panduan development sebelum mengubah kode.

## 1. Cara memulai setiap tugas

1. Pastikan repository dan branch yang sedang dikerjakan.
2. Periksa status working tree dan commit terbaru.
3. Baca dokumentasi aktif yang relevan dengan area tugas. Untuk perubahan UI, wajib baca `docs/ui/DESIGN-FOUNDATION.md`, `docs/ui/PAGE-INVENTORY.md`, dan katalog/kontrak komponen terkait; lalu verifikasi route, source, serta test aktual.
4. Baca file implementasi aktual, route, model, migration, test, dan komponen terkait sebelum mengusulkan perubahan.
5. Bedakan fakta dari source, keputusan yang telah disepakati, rekomendasi, dan asumsi.
6. Buat perubahan sekecil mungkin dan tetap dalam ruang lingkup permintaan.

Jangan mengandalkan percakapan lama sebagai bukti bahwa kode saat ini masih sama. Source pada branch target adalah acuan utama.

## 2. Batas perubahan

- Jika tugas hanya meminta UI/UX atau standardisasi tampilan, jangan mengubah query, state, filter, sorting, pagination, route, otorisasi, validasi, persistence, perhitungan absensi, atau business logic.
- Ikuti `docs/ui/DESIGN-FOUNDATION.md`: Employee mobile-first; Admin mengutamakan keterbacaan informasi pada desktop/tablet dan tetap mobile-friendly. Terapkan desain langsung di source code, gunakan komponen yang ada, dan verifikasi browser pada viewport yang relevan.
- Jangan mengubah kontrak Livewire: nama property, action, event, lifecycle hook, binding, dan parameter tanpa kebutuhan yang eksplisit.
- Jangan mengubah alur kamera, GPS, watermark, upload, Clock In, Clock Out, atau lembur ketika tugas tidak mencakupnya.
- Jangan menghapus data historis atau melemahkan audit trail.
- Jangan menambah abstraksi/komponen generik sebelum ditemukan pengulangan markup sekaligus kontrak perilaku yang benar-benar sama.
- Jangan melakukan refactor besar hanya demi menyamakan gaya.

## 3. Aturan arsitektur

- Gunakan Blade + Controller untuk halaman HTTP yang sederhana dan tidak membutuhkan state reaktif kompleks.
- Gunakan Livewire 4 ketika interaksi server reaktif memang diperlukan.
- Native Livewire 4 Single-File Component (SFC) adalah pola yang valid. Jangan menyebut semua SFC sebagai Volt.
- Halaman resources/views/pages/admin/⚡kehadiran.blade.php adalah Native Livewire 4 SFC, bukan Volt.
- Jangan memigrasikan halaman ke Volt, class-based Livewire, atau framework frontend lain hanya demi migrasi.
- Komponen UI bertanggung jawab atas presentasi dan atribut; keputusan bisnis tetap berada di lapisan aplikasi/domain.
- Pertahankan komponen yang sudah ada sebelum membuat komponen baru. Lihat daftar primitive di dokumen arsitektur.

## 4. Aturan domain yang tidak boleh diasumsikan ulang

- users dan employees adalah entitas berbeda.
- superadmin tidak wajib memiliki profil Employee.
- admin dan employee harus memiliki profil Employee.
- Location office menggunakan client_id = null.
- Location site harus dimiliki Client.
- Satu Project memiliki satu Location.
- Project yang tersedia untuk absensi harus memenuhi aturan status dan Location pada implementasi domain terkini.
- AttendanceRecord menyimpan konteks Location/Project yang relevan pada saat absensi; jangan mengganti konteks historis dengan relasi master yang telah berubah.
- Status persetujuan lembur dan state operasional lembur adalah dua konsep berbeda.
- Timestamp bukti asli dan timestamp efektif hasil koreksi harus tetap dapat dibedakan.

Jika source bertentangan dengan dokumen, jangan langsung memilih salah satu. Laporkan perbedaan dan periksa keputusan/domain test terbaru sebelum mengubahnya.

## 5. PHP, format, dan keamanan

- Ikuti style yang diterapkan Laravel Pint.
- Gunakan namespace PHP dengan satu leading backslash bila fully-qualified name diperlukan, contoh: \App\Enums\LeaveRequestStatus::Pending.
- Jangan menulis namespace dengan double leading slash.
- Jangan menyimpan .env, APP_KEY, password, token, kredensial hosting, atau data pribadi ke repository.
- Jangan menonaktifkan middleware, policy, validasi, CSRF, atau proteksi upload untuk membuat test lolos.
- Jangan menjalankan migrate:fresh, db:wipe, truncate, atau operasi destruktif pada database yang mungkin berisi data.
- Jangan mengarang hasil test, lint, CI, browser test, atau verifikasi perangkat.

## 6. Pengujian dan pelaporan

- Pilih test yang sesuai dengan perubahan, kemudian jalankan test suite yang relevan jika runtime tersedia.
- Untuk perubahan PHP, jalankan Pint atau pemeriksaan Pint sesuai workflow.
- Periksa MariaDB compatibility untuk migration dan constraint; SQLite pass saja tidak membuktikan kompatibilitas MariaDB.
- Jika test tidak dijalankan, nyatakan alasannya secara eksplisit.
- Bedakan hasil repository-wide PHPStan dari targeted PHPStan. Workflow saat ini menoleransi baseline repository-wide dengan continue-on-error.
- Laporkan file yang berubah, kontrak yang dipertahankan, test yang benar-benar dijalankan, hasil CI, dan keterbatasan verifikasi.

## 7. Git workflow

- For the SanMal V1 UI/UX finalization scope, use only the existing long-lived branch `docs/ui-v1-scope-and-pr-workflow` and the single tracking PR #27 targeting `main`.
- Do not create another branch or PR for any UI/UX milestone, component, page, test, or documentation task. Do not merge PR #27 until the complete V1 UI/UX scope is finished, reviewed, and verified.
- Commit focused, logically grouped changes to that same branch. Before every write, inspect the current branch HEAD and latest file SHA to avoid overwriting newer work.
- The authoritative workflow and handoff are documented in `docs/ui/DEVELOPMENT-HANDOFF.md`; design decisions are in `docs/ui/DESIGN-FOUNDATION.md`; scope is tracked in `docs/ui/progress/IMPLEMENTATION-STATUS.md`.

- Untuk pekerjaan di luar program UI/UX SanMal V1, secara default kerjakan di feature branch dari main, buat commit terfokus, lalu buka Pull Request. Untuk UI/UX V1, aturan satu branch dan satu PR di atas selalu berlaku.
- Commit langsung ke main hanya jika pengguna secara eksplisit memintanya.
- Jangan force-push, mengubah riwayat, merge, atau mengubah status PR tanpa instruksi eksplisit.
- Sebelum menulis ke branch, periksa ulang HEAD dan hindari menimpa perubahan yang masuk setelah inspeksi.
- Jangan menyatakan pekerjaan selesai sebelum perubahan berhasil di-fetch kembali dan diverifikasi.
