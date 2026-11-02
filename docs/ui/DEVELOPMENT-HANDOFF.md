# SanMal UI/UX Development Handoff

Panduan operasional ringkas untuk AI coding agent dan developer yang melanjutkan UI/UX V1. Terapkan perubahan langsung pada source Laravel; Figma bukan prasyarat.

## Mulai dari sini

Baca berurutan:

1. [Repository README](../../README.md) — tujuan aplikasi dan stack.
2. [Project Status](../PROJECT-STATUS.md) — milestone repository, risiko, dan status production.
3. [AI Agent Rules](../../AGENTS.md) — batas perubahan, domain, testing, dan Git.
4. [Architecture & Development](../ARCHITECTURE-AND-DEVELOPMENT.md) — struktur Laravel/Livewire, route, domain, dan test.
5. [Design Foundation](DESIGN-FOUNDATION.md) — brand, prinsip visual, responsive role priorities.
6. [Page Inventory](PAGE-INVENTORY.md) — cakupan halaman dan fokus review.
7. [Component Catalog](COMPONENT-CATALOG.md) — peta primitive bersama.
8. [Reusable Blade Components](REUSABLE-BLADE-COMPONENTS.md) — detail API/kontrak komponen.
9. [Implementation Status](progress/IMPLEMENTATION-STATUS.md) dan [Visual QA Log](progress/VISUAL-QA-LOG.md) — status dan bukti yang tercatat.

Tidak perlu membaca audit lama. Dokumen aktif di atas adalah panduan; source, route, test, dan hasil browser aktual adalah kebenaran implementasi.

## Konteks produk

SanMal adalah aplikasi internal absensi dan administrasi pegawai PT SanMal Calibration Laboratory.

- **Employee:** mobile-first. Prioritaskan absensi, kamera/GPS/bukti, permohonan, riwayat, dan touch interaction.
- **Admin:** desktop/tablet information-first tetapi tetap mobile-friendly. Prioritaskan data operasional, filter, table/list, detail review, dan efisiensi administrasi.
- **Shared:** gunakan bahasa visual dan kontrak komponen yang konsisten; komposisi halaman tidak harus identik.
- **Design principle:** seamless-first. Jangan membungkus semua konten dengan card/border/shadow atau membuat card-in-card.

## Scope dan non-goals

UI/UX V1 mencakup komponen bersama, halaman Employee/Admin/shared, layout, hierarchy, density, responsive behavior, feedback, status, data presentation, navigation, accessibility, dan QA.

Tanpa persetujuan eksplisit, jangan mengubah fitur produk, domain rules, attendance calculation/timestamp, approval logic, persistence, authorization, validation, camera/GPS/evidence workflow, atau melakukan framework/broad refactor hanya demi konsistensi visual. Jika perbaikan UI membutuhkan perubahan kontrak tersebut, berhenti dan jelaskan dependensinya.

## Git workflow wajib

| Item | Nilai |
|---|---|
| Repository | `leonsmith128/pt-sanmal` |
| Branch | `docs/ui-v1-scope-and-pr-workflow` |
| Tracking PR | [#27](https://github.com/leonsmith128/pt-sanmal/pull/27) ke `main` |

- Semua pekerjaan UI/UX V1, test, dan dokumentasi dikerjakan pada branch dan PR yang sama.
- Jangan membuat branch/PR UI/UX lain atau commit langsung ke `main`.
- Jangan merge PR #27 sampai seluruh V1 selesai, direview, dan diverifikasi.
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
- Update PR #27 dan laporkan file, kontrak, test, QA, serta keterbatasan secara eksplisit.

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

## Definition of ready untuk merge V1

- Semua halaman in-scope Employee, Admin, shared, dan authentication telah direview.
- Komponen bersama telah diperiksa pada konteks penggunaan nyata.
- Responsive, interaction, accessibility, dan visual QA telah dilakukan atau gap diterima eksplisit oleh owner.
- Test, formatting, dan asset build relevan telah dijalankan.
- Alur kamera/GPS kritis telah diuji pada perangkat nyata bila terdampak.
- PR checklist, implementation status, dan QA log konsisten.
- Tidak ada regression high-severity atau gap scope yang belum didokumentasikan.
