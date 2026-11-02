# PT SanMal — Sistem Informasi Absensi

Aplikasi web internal untuk pencatatan kehadiran dan administrasi pegawai PT SanMal Calibration. Sistem mencakup absensi berbasis kamera dan GPS, pengelolaan pegawai serta master kerja, permohonan dan persetujuan, pemantauan kehadiran, riwayat, dan ekspor data.

> **Status:** aktif dikembangkan dan berada pada tahap persiapan menuju production. Dokumen ini tidak menyatakan aplikasi sudah production-ready.

## Teknologi utama

- PHP ^8.3 (CI menggunakan PHP 8.5)
- Laravel ^13.17
- Livewire ^4.1 dan Blade
- Laravel Fortify untuk autentikasi
- Tailwind CSS 4, Flux UI, dan Vite
- MariaDB/MySQL; CI menguji MariaDB 11 dan SQLite

Versi dependency yang sebenarnya selalu mengacu pada composer.json dan package.json.

## Mulai membaca dokumentasi

| Dokumen | Tujuan |
|---|---|
| [Indeks dokumentasi](docs/README.md) | Peta seluruh dokumentasi proyek |
| [Status dan progress](docs/PROJECT-STATUS.md) | Kondisi terkini, hasil CI yang sudah diketahui, risiko, dan pekerjaan berikutnya |
| [Arsitektur dan panduan development](docs/ARCHITECTURE-AND-DEVELOPMENT.md) | Struktur kode, pola Laravel/Livewire, domain, dan aturan kontribusi |
| [Instruksi AI agent](AGENTS.md) | Aturan kerja wajib bagi coding agent |
| [UI/UX Development Handoff](docs/ui/DEVELOPMENT-HANDOFF.md) | Panduan operasional AI agent/developer untuk finalisasi UI/UX V1 |
| [UI Page Inventory](docs/ui/PAGE-INVENTORY.md) | Daftar halaman Employee, Admin, dan shared yang masuk cakupan |
| [UI Component Catalog](docs/ui/COMPONENT-CATALOG.md) | Peta komponen bersama dan aturan penggunaannya |
| [UI Implementation Status](docs/ui/progress/IMPLEMENTATION-STATUS.md) | Progress milestone UI/UX V1 |
| [UI Visual QA Log](docs/ui/progress/VISUAL-QA-LOG.md) | Bukti browser/device QA |
| [Audit halaman Admin](docs/ui/ADMIN-PAGES-AUDIT.md) | Hasil standardisasi halaman Admin dan batas verifikasinya |

## Prinsip pengembangan

1. Periksa implementasi aktual sebelum menyimpulkan status fitur.
2. Pertahankan aturan bisnis, otorisasi, validasi, dan bukti historis.
3. Kerjakan perubahan secara bertahap, dengan test yang relevan.
4. Jangan menganggap audit source sebagai pengganti browser/device testing.
5. Jangan menjalankan perintah yang menghapus atau mereset data tanpa persetujuan eksplisit.

Lihat docs/README.md untuk peta dokumentasi dan docs/PROJECT-STATUS.md sebelum memulai pekerjaan baru.
