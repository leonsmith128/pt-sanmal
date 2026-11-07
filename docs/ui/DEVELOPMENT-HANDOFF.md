# SanMal UI/UX Development Handoff

Panduan operasional ringkas untuk AI coding agent dan developer yang melanjutkan UI/UX V1. Terapkan perubahan langsung pada source Laravel; Figma bukan prasyarat.

## Mulai dari sini

Jangan membaca seluruh dokumentasi secara berurutan untuk setiap task. Gunakan jalur minimum berikut.

### Wajib sebelum mengubah repository

1. [Repository README](../../README.md) — tujuan aplikasi dan stack.
2. [Project Status](../PROJECT-STATUS.md) — milestone repository, risiko, dan status production.
3. [AI Agent Rules](../../AGENTS.md) — aturan wajib untuk perubahan dan Git.
4. [Architecture & Development](../ARCHITECTURE-AND-DEVELOPMENT.md) — struktur Laravel/Livewire, route, domain, dan testing.

### Wajib untuk pekerjaan UI

5. [Design Foundation](DESIGN-FOUNDATION.md) — token dan aturan visual.
6. [Page Inventory](PAGE-INVENTORY.md) — halaman in-scope dan fokus review.
7. [Component Catalog](COMPONENT-CATALOG.md) — cari komponen yang sudah ada.

### Baca sesuai area task

- Mengubah API komponen: [Reusable Blade Components](REUSABLE-BLADE-COMPONENTS.md).
- Employee Dashboard: [Layout V1](EMPLOYEE-DASHBOARD-LAYOUT-V1.md) dan [Data Contract](EMPLOYEE-DASHBOARD-DATA-CONTRACT.md).
- Employee Attendance: [Layout V1](EMPLOYEE-ATTENDANCE-LAYOUT-V1.md) dan [Calendar V1](EMPLOYEE-ATTENDANCE-CALENDAR-V1.md).
- Employee Attendance History: [V1 scope and behavior](EMPLOYEE-ATTENDANCE-HISTORY-V1.md).
- Employee Profile: [Layout V1](EMPLOYEE-PROFILE-LAYOUT-V1.md).
- Status pekerjaan: [Implementation Status](progress/IMPLEMENTATION-STATUS.md).
- Bukti browser/perangkat: [Visual QA Log](progress/VISUAL-QA-LOG.md).

Tidak perlu membaca audit lama atau semua spesifikasi fitur yang tidak terkait. Dokumen aktif adalah panduan; source, route, test, dan hasil browser aktual adalah kebenaran implementasi.

## Konteks produk

SanMal adalah aplikasi internal absensi dan administrasi pegawai PT SanMal Calibration Laboratory.

- **Employee:** mobile-first. Prioritaskan absensi, kamera/GPS/bukti, permohonan, riwayat, dan touch interaction.
- **Admin:** desktop/tablet information-first tetapi tetap mobile-friendly. Prioritaskan data operasional, filter, table/list, detail review, dan efisiensi administrasi.
- **Shared:** gunakan bahasa visual dan kontrak komponen yang konsisten; komposisi halaman tidak harus identik.
- **Design principle:** seamless-first. Jangan membungkus semua konten dengan panel atau membuat panel di dalam panel. Panel berborder 1px di kedua tema dan bayangan hanya untuk elemen melayang (lihat Design Foundation §8).

## Pola arsitektur UI yang digunakan

- **Blade + Controller:** halaman request/response sederhana tanpa state server reaktif yang kompleks.
- **Native Livewire 4 SFC:** halaman yang membutuhkan state server reaktif. Satu SFC menjadi orchestrator state halaman dan action.
- **Blade Components:** memecah markup berdasarkan tanggung jawab visual; bukan state owner Livewire tersendiri.
- **Actions:** proses bisnis/use-case seperti pencatatan absensi dan konfirmasi lembur.
- **Services:** kalkulasi, agregasi, dan klasifikasi domain yang dapat digunakan ulang.
- **Nested Livewire Component:** hanya jika bagian halaman benar-benar membutuhkan state, lifecycle, validasi, atau interaksi server independen.
- **Flux UI:** renderer komponen UI yang sudah digunakan aplikasi. Jangan membuat sistem komponen paralel atau memigrasikan halaman ke Volt/framework lain hanya demi keseragaman.

Contoh konkret: Employee Attendance tetap satu Native Livewire 4 SFC. Kalender, ringkasan jam, status card, dan photo preview dipisah menjadi Blade Components; kamera tetap komponen khusus karena lifecycle browser-nya. Lihat [Employee Attendance Layout V1](EMPLOYEE-ATTENDANCE-LAYOUT-V1.md).

## Scope dan non-goals

UI/UX V1 mencakup komponen bersama, halaman Employee/Admin/shared, layout, hierarchy, density, responsive behavior, feedback, status, data presentation, navigation, accessibility, dan QA.

Tanpa persetujuan eksplisit, jangan mengubah fitur produk, domain rules, attendance calculation/timestamp, approval logic, persistence, authorization, validation, camera/GPS/evidence workflow, atau melakukan framework/broad refactor hanya demi konsistensi visual. Jika perbaikan UI membutuhkan perubahan kontrak tersebut, berhenti dan jelaskan dependensinya.

## Git workflow wajib

| Item | Nilai |
|---|---|
| Repository | `leonsmith128/pt-sanmal` |
| Branch | `feature/ui-production-readiness` |
| Tracking PR | Satu PR ke `main` (nomor dicatat di deskripsi PR). PR #27 dan #29 sudah di-merge dan tidak lagi menjadi acuan |

- Semua pekerjaan finalisasi UI/UX, test, dan dokumentasi dikerjakan pada branch dan PR yang sama, per fase (F0 sampai F6). Progres fase dicatat sebagai tasklist di deskripsi PR.
- Jangan membuat branch/PR UI/UX lain atau commit langsung ke `main` selama PR ini terbuka.
- Jangan merge PR sampai Definition of Done di bawah terpenuhi dan bukti QA tercatat.
- Sebelum setiap write, periksa status PR, branch HEAD, dan SHA file terbaru. Jangan menimpa perubahan yang lebih baru.
- Gunakan commit kecil dan fokus; jangan force-push atau rewrite history.

## Workflow setiap task

### Sebelum mengubah kode

- Periksa branch, HEAD, working tree, route, view/SFC, komponen, dan test aktual.
- Baca [Page Inventory](PAGE-INVENTORY.md) dan [Component Catalog](COMPONENT-CATALOG.md) yang relevan.
- Periksa implementasi komponen di source; jangan menganggap dokumentasi API selalu mutakhir.
- Catat perilaku dan kontrak yang harus dipertahankan.
- Tentukan role, viewport, state kritis, acceptance criteria, dan test yang relevan.

### Saat implementasi

- Gunakan primitive yang sudah ada jika semantik dan kontraknya sesuai.
- Komponen presentasi tidak boleh mengambil keputusan bisnis.
- Pertahankan route, HTTP method, authorization, validation, field name/ID/value, query/filter, sorting, pagination, export, Livewire property/action/event/lifecycle, dan test hooks.
- Pertahankan Alpine/browser lifecycle, kamera, GPS, evidence, upload, dan workflow absensi.
- Pertahankan semantic HTML, accessible name, focus, keyboard, touch target, dan label.
- Hindari abstraksi generik atau refactor luas tanpa pengulangan struktur sekaligus kontrak perilaku yang terbukti.

### Sebelum menyatakan selesai

- Jalankan focused component/feature tests; jalankan regression tests lebih luas jika dampaknya lintas halaman.
- Jalankan Pint dan frontend build bila relevan.
- Periksa browser pada viewport relevan: mobile 360–430 px, tablet 768/1024 px, desktop 1280–1440 px.
- Periksa state default, hover, focus-visible, active, disabled, loading, error, empty, long content, serta light/dark jika didukung.
- Untuk perubahan kamera/GPS, uji perangkat nyata, HTTPS, permission, orientasi, network, dan upload; emulasi viewport saja tidak cukup.
- Catat hanya hasil yang benar-benar dijalankan pada [Implementation Status](progress/IMPLEMENTATION-STATUS.md) dan [Visual QA Log](progress/VISUAL-QA-LOG.md).
- Update PR UI yang aktif dan laporkan file, kontrak, test, QA, serta keterbatasan secara eksplisit.

Source inspection bukan browser test. Component test bukan bukti seluruh workflow halaman. Desktop QA bukan bukti mobile QA.

## Kontrak berisiko tinggi

Jangan mengubah tanpa scope dan persetujuan yang jelas:

- Role-based access dan server-side authorization.
- Form fields, validation errors, old/model values, HTTP methods, CSRF.
- Livewire public properties, actions, events, lifecycle hooks, bindings.
- Query/filter semantics, sorting, pagination, export/download permissions.
- Attendance session, bukti asli, timestamp efektif hasil koreksi, historical snapshot.
- Camera permission/stream lifecycle, GPS readiness/accuracy, selfie, watermark, upload, Clock In/Out.
- Overtime approval status versus operational state.
- Leave/request dan attendance correction state transitions.

Jika dokumentasi bertentangan dengan source/test terbaru, investigasi dan perbaiki dokumen yang paling sempit. Jangan menebak.

## Definition of Done sebelum merge

- Semua halaman in-scope Employee, Admin, shared, dan authentication telah direview.
- Komponen bersama telah diperiksa pada konteks penggunaan nyata.
- Responsive, interaction, accessibility, dan visual QA telah dilakukan atau gap diterima eksplisit oleh owner.
- Test, formatting, dan asset build relevan telah dijalankan; `public/build` diunggah di staging.
- Kontras dihitung ulang dan tercatat: tidak ada teks di bawah 4,5:1 atau kontrol di bawah 3:1.
- Alur kamera/GPS kritis telah diuji pada perangkat nyata bila terdampak.
- PR checklist, implementation status, dan QA log konsisten dengan source.
- Tidak ada regression high-severity atau gap scope yang belum didokumentasikan.
