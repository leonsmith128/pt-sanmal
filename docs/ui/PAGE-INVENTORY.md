# SanMal V1 UI/UX Page Inventory

> Indeks cakupan halaman dan fokus review. Ini bukan klaim bahwa seluruh halaman sudah lolos visual QA. Source, route, dan test pada branch kerja selalu menjadi acuan implementasi.

## Cara menggunakan

1. Pilih halaman atau workflow yang akan dikerjakan.
2. Periksa route, Blade/SFC, komponen, dan test aktual pada branch.
3. Catat kontrak perilaku yang harus dipertahankan sebelum mengubah UI.
4. Setelah implementasi, perbarui [Implementation Status](progress/IMPLEMENTATION-STATUS.md) dan bukti aktual di [Visual QA Log](progress/VISUAL-QA-LOG.md).

## Admin

| Halaman / workflow | Fokus review |
|---|---|
| Dashboard Admin | Monitoring seluruh pegawai; overview kehadiran hari ini, antrean tindakan, dan aktivitas terbaru; hierarki aktivitas, density, wrapping tablet/mobile |
| Dashboard Pribadi Admin | Halaman terpisah untuk Admin/Superadmin dengan profil pegawai; employee-scoped attendance, work duration, overtime, requests, dan recent activity; ringkasan bulan 2 kolom di bawah `xl`, 4 kolom mulai `xl`, label/nilai rata tengah tanpa separator |
| Client list dan detail | Hierarki Client–Site–Office, ringkasan, list, navigasi, dan aksi |
| Client create/edit | Grouping field, label, validasi, lebar form, dan hierarki aksi |
| Location create/edit | Kondisi Office/Site, ownership Client, koordinat/timezone, error mobile |
| Project list dan create/edit | Expandable card, metadata panjang, pemilihan Site, dan konsistensi form |
| Employee list dan create/edit | Keterbacaan data, status, aksi, grouping akun/profil, dan validasi |
| Attendance correction queue/detail | Jenis koreksi, timestamp asli/efektif, bukti, status, keputusan, dan attachment |
| Leave request queue/detail | Filter, status, periode, detail, konfirmasi, dan aksi persetujuan |
| Overtime request queue/detail | Jadwal, approval status vs operational state, rentang waktu, keputusan/finalisasi |
| Audit log | Urutan kronologis, header kolom, nilai panjang, horizontal scroll terlingkup |
| Daily attendance recap | Pengelompokan event, status, form review/koreksi, POST/CSRF/error |
| Attendance monitoring | Filter, sorting, pagination, data padat, bukti, peta, ekspor, dan seluruh aksi |

## Employee

| Halaman / workflow | Fokus review |
|---|---|
| Attendance / Clock In-Out | Prioritas mobile; kamera, GPS, izin, kesiapan capture, lokasi kerja, bukti, upload, konfirmasi, error. Lihat [Attendance Layout V1](EMPLOYEE-ATTENDANCE-LAYOUT-V1.md) dan [Calendar V1](EMPLOYEE-ATTENDANCE-CALENDAR-V1.md) |
| [Employee profile](EMPLOYEE-PROFILE-LAYOUT-V1.md) | Hierarki informasi, circular avatar, preview foto dalam dialog, upload error feedback, nilai kosong/panjang, section responsif |
| [Attendance history](EMPLOYEE-ATTENDANCE-HISTORY-V1.md) | Scope implementasi V1 selesai: tiga indikator, filter tanggal/urutan collapsible, empty state, date-group 10 tanggal/halaman, status-only group, dialog detail event, timestamp asli/efektif, GPS/alamat/foto privat, pagination, dan ekspor |
| Attendance correction submission | Pemilihan record/timestamp, alasan, attachment, validasi mobile |
| Leave/request list | Status, periode, ringkasan, empty state, navigasi detail |
| Overtime list | Ringkasan jadwal, status, detail, modal/form, aksi |

## Shared dan supporting pages

| Halaman / workflow | Fokus review |
|---|---|
| Notifications | Read/unread/resolved, pesan panjang, aksi, empty state |
| Account security | Form password/session, feedback, lebar form responsif |
| Authentication | Login, forgot/reset password, email verification, password confirmation |
| Application shell/navigation | Header, Admin sidebar, Employee navigation, active state, badge, overflow, mobile/desktop |

## Shared component families

- Button dan action hierarchy.
- Field, input, select, textarea, checkbox, date/search controls.
- Badge dan domain status adapter.
- Page/layout/header/section/card/surface.
- Flash, alert, empty, loading, confirmation feedback.
- Metadata dan detail presentation.
- Table, review list, filter, pagination, dan data display.
- Admin sidebar dan Employee navigation.

Lihat [Component Catalog](COMPONENT-CATALOG.md) untuk peta primitive dan [Reusable Blade Components](REUSABLE-BLADE-COMPONENTS.md) untuk detail kontrak komponen.

## Prioritas dan batas perilaku

- Employee Attendance adalah workflow mobile kritis karena kamera, GPS, dan bukti absensi.
- Admin Attendance adalah workflow data padat dengan filter, sorting, pagination, bukti, peta, dan ekspor.
- Koreksi absensi harus mempertahankan perbedaan timestamp asli dan timestamp efektif.
- Status persetujuan lembur dan operational state adalah dua dimensi berbeda.
- Semua perubahan UI harus mempertahankan route, authorization, validasi, query/filter, sorting, pagination, ekspor, Livewire contract, bukti historis, dan workflow kamera/GPS kecuali perubahan perilaku disetujui secara eksplisit.

## Pemeliharaan

Saat route/halaman berubah, perbarui inventaris ini berdasarkan route dan source aktual. Jangan menyalin hasil audit lama sebagai kondisi terkini. Catat status implementasi dan QA hanya berdasarkan bukti yang benar-benar tersedia.


## Source review checkpoint — 5 November 2026

The Application Shell inventory was revalidated against the current PR branch. The active root layout is `resources/views/layouts/app.blade.php`, which delegates to `resources/views/layouts/app/sidebar.blade.php`. Authenticated pages use the shared full-width `<flux:main>` region and the same collapsible sidebar across roles; this remains the only supported authenticated application shell.

The legacy `resources/views/layouts/app/header.blade.php` starter-kit shell was removed in this refactor. It represented a separate header/navbar shell and duplicated responsibilities now owned by the SanMal sidebar shell. The shared `x-desktop-user-menu` component is retained because the active sidebar layout still uses it. The shared `x-app-logo` and `x-app-logo-icon` components are retained; do not classify them as unused based only on the removed header template.

Authentication was later included in a focused visual pass: active routes use `layouts/auth/simple.blade.php` with the shared brand treatment, and `pages/auth/*` retain their Fortify forms and route contracts. `layouts/auth/card.blade.php` and `layouts/auth/split.blade.php` had no active references in the source scan and were left untouched.

The existing `x-ui.page`, `x-ui.page-header`, and `x-ui.section` remain the page composition primitives. No parallel `x-layouts.page` system was introduced. `partials/head.blade.php` remains shared document-head infrastructure; `partials/settings-heading.blade.php` remains available for settings views. Other component families were inventoried but were not mass-deleted: removal requires a complete reference check and a demonstrated lack of runtime use.

This checkpoint records source-level shell consolidation only. It does not claim browser/device QA or full reference-index coverage. View Composer extraction of sidebar data, broader reusable-component cleanup, and page-folder consolidation are separate follow-up stages.
