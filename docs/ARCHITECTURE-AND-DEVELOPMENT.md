# Arsitektur dan Panduan Development

Dokumen ini menjelaskan pola yang dipakai repository saat ini. Jika akan mengubah struktur atau kontrak teknis, verifikasi source pada branch target terlebih dahulu.

## 1. Stack dan versi

| Bagian | Implementasi |
|---|---|
| Runtime PHP | ^8.3; workflow CI menggunakan PHP 8.5 |
| Framework | Laravel ^13.17 |
| Reactive UI | Livewire ^4.1 |
| Template | Blade |
| Authentication | Laravel Fortify |
| UI library | Flux UI ^2.13.1 |
| CSS | Tailwind CSS 4 |
| Build | Vite 8 / Vite Plus |
| Database | MariaDB/MySQL; CI memakai MariaDB 11 dan SQLite |
| Automated tests | Pest 5 / PHPUnit configuration |
| Formatting | Laravel Pint |
| Static analysis | Larastan/PHPStan |

Versi aktual ditentukan oleh composer.json, package.json, dan workflow CI.

## 2. Peta struktur source

Struktur berikut menjelaskan lokasi tanggung jawab utama, bukan inventaris lengkap setiap file.

~~~text
.
├── app/
│   ├── Actions/              # Use-case/action yang dipisahkan dari HTTP layer
│   ├── Enums/                # Enum status, role, dan jenis domain
│   ├── Http/
│   │   ├── Controllers/      # Endpoint dan halaman berbasis request
│   │   └── Middleware/       # Proteksi request dan role
│   ├── Models/               # Eloquent models dan relasi domain
│   └── Notifications/        # Notifikasi aplikasi
├── database/
│   ├── factories/
│   ├── migrations/
│   └── seeders/
├── resources/
│   ├── css/
│   ├── js/
│   └── views/
│       ├── pages/
│       │   ├── admin/
│       │   ├── employee/
│       │   └── ...
│       ├── components/
│       │   ├── ui/
│       │   └── admin/
│       └── ...
├── routes/
│   └── web.php
├── tests/
│   ├── Feature/
│   └── Unit/
├── docs/
└── AGENTS.md
~~~

Periksa folder aktual sebelum menambahkan subfolder baru. Hindari membuat struktur duplikat yang hanya berbeda nama.

## 3. Memilih Blade, Controller, dan Livewire

### Blade + Controller

Gunakan untuk halaman yang:
- Mengambil data saat request.
- Tidak membutuhkan interaksi server reaktif yang kompleks.
- Memiliki form HTTP standar atau navigasi biasa.
- Dapat diuji dengan feature test controller/HTTP.

Controller menangani request, authorization, validasi, dan pemanggilan use-case sesuai pola aplikasi. Blade menangani presentasi.

### Livewire 4

Gunakan ketika UI memang membutuhkan:
- Filter atau state yang reaktif di server.
- Sorting/pagination interaktif.
- Modal atau aksi yang memperbarui state tanpa full page navigation.
- Interaksi kompleks yang perlu mempertahankan state server.

Native Livewire 4 Single-File Component adalah pola resmi dan valid. Jangan menyebut semua SFC sebagai Volt. Contoh penting: resources/views/pages/admin/⚡kehadiran.blade.php adalah Native Livewire 4 SFC dengan anonymous class, bukan Volt.

### Volt

Volt boleh digunakan bila memang sesuai dengan pola komponen yang sudah ada, tetapi tidak perlu memigrasikan SFC atau halaman Blade ke Volt hanya untuk menyeragamkan teknologi.

### Alpine.js

Gunakan untuk interaksi lokal browser yang tidak perlu menjadi state server, misalnya toggle, modal lokal, lifecycle kamera, dan integrasi DOM. Jangan menduplikasi state server yang sudah dikelola Livewire tanpa alasan.

## 4. Route dan akses

- Route web didefinisikan di routes/web.php.
- EnsureUserIsActive melindungi route terautentikasi dari akun nonaktif.
- EnsureUserHasRole membatasi route administrasi sesuai role.
- Route admin menggunakan prefix admin dan name prefix admin.
- Route Livewire menggunakan alias view seperti pages::employee.attendance atau pages::admin.kehadiran.

Jangan menganggap menyembunyikan tombol/menu sebagai authorization. Pemeriksaan akses harus tetap dilakukan di server melalui middleware, policy, gate, atau mekanisme domain yang sesuai.

## 5. Domain dan aturan penting

### User dan Employee

- users menyimpan identitas autentikasi dan role.
- employees menyimpan profil kepegawaian.
- Satu User paling banyak memiliki satu Employee.
- superadmin tidak wajib memiliki Employee.
- admin dan employee harus memiliki Employee.

Jangan menyatukan kedua tabel hanya untuk menyederhanakan form atau relasi.

### Client, Location, dan Project

~~~text
Client
  └── has many Locations (type = site)

Location
  ├── office: client_id = null
  └── site: client_id wajib

Project
  └── belongs to exactly one Location (site milik Client)
~~~

Aturan domain yang telah disepakati dan diterapkan pada perubahan PR #24:

- Client tidak memakai status aktif/nonaktif.
- Location memiliki jenis office atau site, dan memiliki is_active.
- Office internal SanMal menggunakan client_id = null.
- Site wajib dimiliki Client.
- Satu Project memiliki tepat satu Location.
- Project yang digunakan untuk absensi harus memenuhi status eligibility dan Location aktif sesuai validasi domain.
- Office tidak boleh di-hardcode berdasarkan nama atau ID.
- Jangan menghidupkan kembali sistem ProjectAssignment tanpa keputusan baru; relasi assignment pegawai telah dihapus dari rancangan terkini.

### AttendanceRecord dan bukti

- AttendanceRecord menyimpan referensi Location/Project sesuai konteks kerja saat event dibuat.
- work_type membedakan konteks Office dan Onsite.
- Data alamat/koordinat snapshot adalah bukti saat absensi, bukan sekadar turunan dari master yang bisa berubah.
- Perubahan master tidak boleh mengubah makna historis absensi.
- Timestamp asli dan timestamp efektif hasil koreksi harus tetap dapat dibedakan.
- Event absensi terkait dengan sesi; sesi lintas tengah malam dan data legacy ambigu memerlukan perlakuan khusus.

### Overtime

- Status approval dan operational state merupakan dua dimensi berbeda.
- Jadwal, konfirmasi foto, event absensi, dan pembatalan memiliki state transition tersendiri.
- Jangan menyatukan enum atau menyimpulkan bahwa status pending approval sama dengan status proses lembur.

## 6. UI primitives dan aturan presentasi

Prinsip desain: **Seamless-First Design System**. Konsistensi tidak berarti semua konten harus dibungkus card, border, atau shadow. Hindari card-in-card dan abstraksi berlebihan.

Primitive yang telah tersedia dan perlu diperiksa sebelum membuat yang baru:

- Layout: x-ui.page, x-ui.page-header, x-ui.section, x-admin.page-header
- Filter: x-admin.filter-panel
- Form: x-ui.field, x-ui.input, x-ui.select, x-ui.textarea, x-ui.checkbox
- Action: x-ui.button
- Surface: x-ui.card
- Feedback: x-ui.flash-message, x-admin.flash-message, x-ui.empty-state, x-admin.empty-state
- Status: x-ui.badge, x-admin.request-status-badge
- Metadata: x-ui.metadata-item

Panduan:
1. Gunakan primitive jika semantik dan kontrak atributnya sesuai.
2. Pertahankan nama input, old/model value, required, error, wire binding, Alpine binding, route, dan method.
3. Jangan memindahkan business logic ke komponen presentasi.
4. Jangan mengubah tabel menjadi card secara otomatis. Pilih table/list/card berdasarkan kebutuhan perbandingan data dan aksi.
5. Pertahankan Flux ketika interaksi atau integrasinya memang spesifik.
6. Gunakan komponen baru hanya jika markup dan kontrak interaksi benar-benar berulang.

## 7. Test dan CI

Test suite berada di tests/Feature dan tests/Unit. CI workflow .github/workflows/tests.yml saat ini:
- Memakai PHP 8.5 dan Node 22.
- Menjalankan setup dependency.
- Memeriksa Pint pada PHP yang berubah.
- Menjalankan repository-wide PHPStan dengan continue-on-error: true karena baseline debt.
- Menjalankan targeted PHPStan pada daftar file aplikasi yang ditentukan workflow.
- Menjalankan Pest pada MariaDB 11 dan SQLite in-memory.

Jangan mengubah test agar sekadar cocok dengan markup internal framework. Assertion sebaiknya memeriksa kontrak pengguna atau perilaku komponen yang relevan.

## 8. Alur kerja kontribusi

1. Buat branch terfokus dari main, kecuali pengguna meminta commit langsung.
2. Audit file dan kontrak yang terdampak.
3. Catat tujuan dan risiko perubahan.
4. Implementasikan perubahan kecil.
5. Tambahkan atau perbarui test.
6. Jalankan Pint dan test relevan.
7. Periksa diff dan pastikan tidak ada perubahan di luar scope.
8. Buat commit dengan pesan yang menjelaskan tujuan.
9. Buka PR dan tunggu CI/review sebelum merge.
10. Setelah merge, periksa kembali CI pada commit main.

## 9. Perintah pengembangan

Periksa composer.json untuk script yang tersedia. Script penting yang didefinisikan saat dokumentasi ini dibuat:

~~~bash
composer install
composer lint
composer lint:check
composer types:check
php artisan test
npm run build
~~~

Jangan menjalankan composer setup atau perintah migration pada database yang sudah berisi data tanpa memeriksa efeknya terlebih dahulu. composer setup pada repository ini menjalankan migration dengan --force.

## 10. Definition of Done

- Scope perubahan terpenuhi tanpa perubahan sampingan.
- Route, authorization, validasi, data, dan interaksi tetap benar.
- Test relevan ditambahkan/diperbarui dan hasilnya dicatat.
- Pint dan build diperiksa bila relevan.
- Tidak ada secret atau data sensitif dalam diff.
- Dokumentasi status diperbarui jika keputusan atau milestone berubah.
- Laporan menyebutkan secara jujur apa yang diuji dan apa yang belum diuji.
