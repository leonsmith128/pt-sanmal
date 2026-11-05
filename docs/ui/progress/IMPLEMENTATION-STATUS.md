# UI/UX V1 — Status Implementasi

> Snapshot: 5 November 2026. Tracking: branch `docs/ui-v1-scope-and-pr-workflow` · [PR #27](https://github.com/leonsmith128/pt-sanmal/pull/27). Periksa ulang source, branch HEAD, dan CI sebelum memakai snapshot ini untuk keputusan berikutnya.

## Cara membaca status

- **Source implemented** berarti implementasi tersedia di branch; belum otomatis berarti tampilannya sudah benar di browser.
- **Automated test passed** berarti test terkait benar-benar lulus pada run yang disebut.
- **Browser/device QA passed** hanya boleh ditulis jika hasil browser atau perangkat dicatat di [Visual QA Log](VISUAL-QA-LOG.md).
- Source review, test komponen, dan CI tidak menggantikan visual QA atau pengujian kamera/GPS pada perangkat nyata.

## Ringkasan saat ini

| Area | Kondisi source | Bukti otomatis terbaru | Browser/device QA |
|---|---|---|---|
| Design foundation dan token | Implemented. Palette, full-width Flux main shell, surface, dan aturan card dipusatkan di Design Foundation dan CSS aplikasi. | Tidak ada klaim bahwa seluruh halaman sudah lolos visual test. | Belum dilakukan secara menyeluruh. |
| Shared UI primitives | 14 primitive `x-ui.*` tersedia. Tiga utility tambahan: `x-ui.avatar`, `x-ui.photo-preview`, dan `x-ui.notification-dropdown`. `x-ui.sidebar-section` adalah helper navigasi shell. | Test button, card, layout, feedback, metadata, notification dropdown, dan photo preview lulus pada run #1460. | Belum dilakukan secara menyeluruh. |
| Application shell dan navigasi | Sidebar Admin/Manager berkelompok; Employee tetap tidak berkelompok. Semua role memakai sidebar collapsible pada desktop dan header/drawer pada ponsel, tanpa fixed bottom navigation. Main menggunakan surface full-width tanpa inset dan mengikuti document scrolling, tanpa nested scroll container. | `AttendancePagesTest` dan `AppSidebarComposerTest` lokal: 33 passed (250 assertions); Pint check lulus. | Browser QA scroll/layout belum dilakukan pada desktop, Firefox/WebKit, light/dark, dan ponsel. |
| Halaman Admin | Source review mencakup halaman Admin dan komponen terkait. Beberapa perbaikan filter, wrapping, density, dan layout sudah diterapkan. | Test yang terkait berjalan dalam CI; lihat hasil run terbaru di bawah. | Belum ada bukti QA lintas halaman dan viewport. |
| Employee Dashboard | Layout V1, ringkasan bulanan, Pengajuan Saya, Akses Cepat, Aktivitas Terbaru, dan notifikasi tersedia. | Test dashboard lulus pada run #1460. Assertion divider diperbaiki agar hanya memeriksa container Ringkasan Bulan Ini, bukan seluruh halaman. | Belum dilakukan. |
| Employee Profile | Source diperbarui: circular avatar melalui `x-ui.avatar`, modal pratinjau bersama `x-ui.photo-preview`, deskripsi redundan dihapus, dan pesan validasi upload menjadi feedback batas ukuran. URL foto tersimpan disajikan oleh endpoint authenticated yang tidak bergantung pada `public/storage` symlink. Follow-up 5 November membatasi lebar halaman ke `max-w-3xl` agar form/detail tidak melebar seperti halaman operasional. | Test route serving foto tanpa storage symlink dan render modal profil lulus pada run #1460. Regression assertion untuk lebar halaman ditambahkan pada commit `e660a63`; follow-up 5 November mengganti input file native yang melebar dengan input visually-hidden dan tombol pemicu sempit berukuran `xs`, lebih pendek daripada tombol Simpan foto. Regression assertions ditambahkan pada commit `fd93365`; CI follow-up belum terverifikasi. | Belum dilakukan. |
| Employee Attendance | Urutan source: header pegawai → kalender → ringkasan jam → status kehadiran/lembur → satu form absensi → modal. Calendar, time summary, info card, dan photo preview dipisah menjadi Blade Components; satu Livewire SFC tetap menjadi orchestrator. | Test urutan halaman dan pembuka kalender bulanan lulus pada run #1460. | Belum dilakukan; terutama alur kamera/GPS dan ukuran layar kecil. |
| Employee Attendance History | **Scope implementasi V1 selesai.** Tiga indikator ringkasan; filter rentang tanggal/urutan collapsible (default tertutup); date-group maksimal 10 tanggal/halaman; status-only group; empty state; dialog detail event berisi waktu asli/efektif, timezone, lokasi/GPS/akurasi/koordinat/peta, konteks pekerjaan, catatan, dan foto privat; CSV mengikuti periode diterapkan. Surface memakai `x-ui.card` borderless dengan `shadow-sm`; komposisi page-local tanpa reusable component baru. Mobile mempertahankan tiga indikator ringkas satu baris dan aksi Export/Absen/Filter sejajar. Kontrak: [Employee Attendance History V1](../EMPLOYEE-ATTENDANCE-HISTORY-V1.md). | Run [#1513](https://github.com/leonsmith128/pt-sanmal/actions/runs/41850885136), HEAD `37e09a7`, workflow success; termasuk regression test disclosure filter. | Browser/device QA, light/dark, keyboard/focus, dan dialog interaction belum tercatat; lihat [Visual QA Log](VISUAL-QA-LOG.md). |
| Kalender Absensi | Weekly calendar dan monthly picker enam baris tersedia. Indikator memakai status domain; `no_record` tidak diartikan sebagai absent. | Feature test pemilihan tanggal dan sinkronisasi kalender lulus pada run #1418. | Belum dilakukan pada viewport dan theme yang ditentukan. |
| Photo preview | Komponen terpisah menampilkan thumbnail, modal foto penuh, unduh foto sementara, dan aksi foto ulang. | Test shared photo preview, download, dan state selfie tersedia lulus pada run #1460. Test diperbaiki agar memeriksa modal hanya ketika selfie memang tersedia. | Belum dilakukan; modal dan siklus capture perlu diuji di browser/perangkat. |
| Accessibility dan interaction-state | Beberapa kontrol sudah memiliki label/semantics; audit menyeluruh belum selesai. | Test otomatis hanya memeriksa kontrak yang tercakup di masing-masing test. | Audit keyboard, focus, dialog, loading, error, dan touch target masih terbuka. |

## Follow-up Employee Profile — 5 November 2026

Source review menemukan bahwa halaman Profil Pegawai masih memakai lebar default `x-ui.page` (`max-w-7xl`), padahal Design Foundation mengarahkan halaman form/detail menggunakan lebar yang lebih terfokus. Root halaman sekarang menggunakan preset yang sudah didukung, `max-width="3xl"`.

Perubahan dibatasi pada presentasi halaman:
- Tidak mengubah Livewire property `phone`/`photo`, action, validasi, penyimpanan foto, route, atau otorisasi.
- Tidak menambah nested Livewire Component maupun reusable component baru.
- Regression assertion ditambahkan pada `EmployeeProfileTest` untuk memastikan halaman merender `max-w-3xl`.

Commit: [e660a63](https://github.com/leonsmith128/pt-sanmal/commit/e660a634dadff927dee65ac4a9198c01730567ff).

**Verifikasi otomatis:** CI [run #1521](https://github.com/leonsmith128/pt-sanmal/actions/runs/41889276067) selesai dengan workflow success. Pint, targeted PHPStan, MariaDB, dan SQLite lulus; repository-wide PHPStan tetap memiliki baseline debt yang ditoleransi workflow. Browser QA Profil Pegawai belum dilakukan; perubahan lebar halaman belum boleh dianggap lulus visual QA.
## Follow-up photo picker Employee Profile — 5 November 2026

Input foto sebelumnya memakai `x-ui.input`/`flux:input` dengan kelas `w-full`. Untuk input type=file, area interaksi native yang melebar membuat sentuhan/scroll di sepanjang row dapat memicu file chooser.

Implementasi sekarang memakai `x-ui.field` untuk mempertahankan label dan error feedback, input file native yang visually-hidden (`sr-only`) dengan `wire:model="photo"`, serta `x-ui.button` ukuran `xs` yang memanggil input melalui Alpine `$refs`. Hanya tombol **Pilih file** yang menjadi target pointer untuk membuka file chooser. Tombol **Simpan foto** tetap memakai ukuran default `sm`, sehingga lebih tinggi daripada tombol pemilih file.

- Tidak mengubah upload validation, Livewire state/action, storage, atau alur penyimpanan.
- Regression assertion memeriksa input file tersembunyi dan pemicu tombol yang eksplisit.
- Tidak mengubah kontrak global `x-ui.input`.

Commit kode dan test: [fd93365](https://github.com/leonsmith128/pt-sanmal/commit/fd933654afe205cd845af0ba0885c1bfb3da8d2e).

**Verifikasi:** CI untuk follow-up ini belum tersedia saat catatan diperbarui. Browser/touch QA belum dilakukan; konfirmasi bahwa scroll pada area row tidak membuka file chooser tetap memerlukan pengujian perangkat nyata.
## CI terbaru untuk Employee Attendance History — 4 November 2026

Run terbaru [#1513](https://github.com/leonsmith128/pt-sanmal/actions/runs/41850885136) menguji PR head `37e09a7423f5ad7949c008a1f67d8986b8eb9147` dan selesai dengan **workflow success**. Run ini mencakup regression test disclosure filter (default tertutup, relasi `aria-controls`/`aria-expanded`, dan jumlah filter aktif). Rincian jumlah test lintas database pada validasi Batch 2 sebelumnya tetap dicatat pada run #1497 di bawah.

**Status: CI passed.**

| Pemeriksaan | Hasil |
|---|---|
| Vite asset build | Passed |
| Pint untuk file PHP yang berubah | Passed |
| Targeted PHPStan untuk changed application code | Passed |
| Repository-wide static analysis | Step selesai; baseline debt repository tetap berlaku dan bukan jaminan type-clean |
| MariaDB 11 | **285 passed, 1 skipped** — 1.182 assertions |
| SQLite | **284 passed, 2 skipped** — 1.180 assertions |
| Kesimpulan workflow | **Success** |

Test yang relevan mencakup render date-group, filter, summary, event-detail payload, private-photo route references, export URL dengan periode yang terlihat, GPS map-link guard, dan preservasi identity snapshots. Automated CI bukan browser/device QA; responsive, keyboard/focus, dialog behavior, dan theme review masih perlu dilakukan.

## CI sebelumnya yang diperiksa

Run [#1460](https://github.com/leonsmith128/pt-sanmal/actions/runs/41781436683) selesai pada 4 November 2026 dan menguji PR head `91f58f27fca388163ee17007c2ca20e2ddc0433a`. Setelah commit tersebut, perubahan branch hanya dokumentasi; source aplikasi dan test yang diuji tidak berubah.

**Status: CI passed.**

| Pemeriksaan | Hasil |
|---|---|
| Vite asset build | Passed |
| Pint untuk file PHP yang berubah | Passed |
| Targeted PHPStan untuk changed application code | Passed |
| Repository-wide static analysis | Step selesai; baseline error repository ditoleransi oleh `continue-on-error`. Ini bukan bukti repository type-clean. |
| MariaDB 11 | **279 passed, 1 skipped** — 1.141 assertions |
| SQLite | **278 passed, 2 skipped** — 1.139 assertions |
| Kesimpulan workflow | **Success** |

Employee Profile tests pada run #1460 mencakup upload/penggantian/penghapusan foto, validasi tipe dan ukuran, penyajian gambar melalui endpoint terautentikasi tanpa mengandalkan symbolic link, serta render trigger modal pratinjau. Semua test tersebut lulus.

Run #1455 dan run antara #1456–#1459 sempat gagal karena assertion UI lama yang masih mengharapkan markup kalender/deskripsi yang sudah dihapus, modal foto pada state tanpa selfie, dan pencarian divider pada seluruh halaman Dashboard. Assertion tersebut diperbarui agar memeriksa state dan container yang benar; run #1460 kemudian lulus pada MariaDB dan SQLite.

Automated CI bukan browser/device QA. Responsive, accessibility, dan pengujian kamera/GPS pada perangkat nyata tetap terbuka.

## Keputusan desain dan arsitektur yang sudah diterapkan

- Gunakan Flux UI dan primitive `x-ui.*` yang tersedia; jangan membuat design system paralel.
- Employee mobile-first; Admin mengutamakan keterbacaan informasi desktop/tablet dan tetap mobile-friendly.
- Card standar memakai surface token, tanpa border pada container luar, dan shadow untuk elevation. Border tetap boleh dipakai pada control, divider, tabel, atau kelompok inset jika memang menyampaikan struktur.
- Satu Livewire SFC menjadi orchestrator halaman interaktif. Blade Components memisahkan markup visual. Actions menangani proses bisnis; Services menangani kalkulasi/klasifikasi domain. Nested Livewire hanya jika state/lifecycle independen benar-benar diperlukan.
- Kalender dan ringkasan jam adalah Blade Components; state tanggal tetap dimiliki parent Attendance SFC.
- Employee Attendance History tetap menggunakan Controller + Blade. Flux UI menangani kontrol; Alpine.js mengelola disclosure filter dan dialog detail event/fokus. Tidak ada komponen reusable baru karena komposisi ini masih page-local.
- Camera component tetap terpisah dari photo-preview component. Jangan mengubah lifecycle kamera, GPS, watermark, upload, atau attendance submission sebagai pekerjaan visual tanpa scope dan test khusus.

## Pekerjaan tersisa — urutan yang disarankan

1. Lakukan browser QA Employee Dashboard, Employee Attendance, Employee Profile, dan halaman Admin pada 360–390 px, 414–430 px, 768 px, 1024 px, dan 1280–1440 px; periksa light/dark jika didukung.
2. Uji alur kamera/GPS pada perangkat nyata melalui HTTPS: permission, lokasi siap/gagal, orientasi, shutter, preview, foto ulang, upload, dan konfirmasi.
3. Selesaikan accessibility dan interaction-state review.
4. Rekonsiliasi seluruh scope V1 dan bukti QA sebelum meminta persetujuan merge PR #27.

## Dokumen sumber

- [Design Foundation](../DESIGN-FOUNDATION.md) — aturan visual dan token.
- [Page Inventory](../PAGE-INVENTORY.md) — halaman yang masuk cakupan.
- [Component Catalog](../COMPONENT-CATALOG.md) dan [Reusable Blade Components](../REUSABLE-BLADE-COMPONENTS.md) — peta dan kontrak komponen.
- [Employee Attendance Layout V1](../EMPLOYEE-ATTENDANCE-LAYOUT-V1.md) dan [Calendar V1](../EMPLOYEE-ATTENDANCE-CALENDAR-V1.md) — spesifikasi halaman absensi.
- [Employee Profile Layout V1](../EMPLOYEE-PROFILE-LAYOUT-V1.md) — spesifikasi profil, foto, dan upload.
- [Employee Dashboard Layout V1](../EMPLOYEE-DASHBOARD-LAYOUT-V1.md) dan [Data Contract](../EMPLOYEE-DASHBOARD-DATA-CONTRACT.md) — tampilan dan definisi data dashboard.
- [Visual QA Log](VISUAL-QA-LOG.md) — bukti browser/device aktual.


## Follow-up Admin Attendance reusable filters — 5 November 2026

The Admin Attendance page now uses a shared filter contract and presentation component on this PR branch:

- `app/Data/Attendance/AttendanceFilterData.php` normalizes optional date, employee, Client, Location, Project, search, attendance type, and work type values. One-sided date ranges become a single-day range; reversed bounds are swapped.
- `app/Filters/AttendanceRecordFilter.php` owns shared Eloquent predicates. The page retains sorting, eager loading, pagination, and authorization.
- `x-admin.attendance-filters` renders configurable controls from option data. Livewire SFC remains the state owner; no nested Livewire component was added.
- Client options are derived through Projects with attendance history. Location options use attendance-record foreign keys and include inactive historical Locations. Project options include Projects with attendance history and label non-active status as `Nonaktif`. Dropdowns remain independent and combine with AND.
- Export shares normalized filter data and day-qualification semantics. A matching Project/Location/etc. event qualifies the day, while CSV output retains the complete daily recap and events from other Projects on that day.
- Reversed date ranges are normalized consistently between page and export. No migration was added.

Regression coverage was added for combined Client/Location/Project filtering, historical Location identity after a Project moves, inactive option visibility, reversed date-range parity, and complete daily export context. CI run #1538 passed on the implementation head (Pint, repository static analysis, changed-code static analysis, MariaDB tests, and SQLite tests). Browser/device QA is not recorded; see [Visual QA Log](VISUAL-QA-LOG.md).


## Admin Attendance filter-panel layout follow-up — 5 November 2026

The Admin Attendance filter panel has been visually simplified without changing its filter contract or Livewire state ownership:

- Removed the redundant page-header description and the duplicate "Cakupan catatan" heading/date explanation.
- Kept the filter title and active-filter count as the panel's only heading row.
- Moved **Reset filter** below the controls and aligned it to the trailing edge, separated by a subtle divider. The action continues to reset only additional filters; the date range is preserved.
- Tightened horizontal and vertical filter-grid spacing while retaining the existing compact small control size and responsive 1 / 2 / 4-column grid.
- Kept **Export** in the activity toolbar, aligned with the activity heading/count rather than mixing it into filter-reset actions.
- No filter fields, query semantics, export parameters, authorization, pagination, or table behavior changed.

Automated regression coverage checks the visible hierarchy and absence of the redundant descriptions. Browser visual QA has not yet been performed; see [Visual QA Log](VISUAL-QA-LOG.md). The activity table's horizontal-overflow redesign is explicitly the next separate UI task.


## Admin Attendance responsive table layout — 5 November 2026

The Admin Attendance activity table now uses a fixed-width table layout on desktop and a stacked, labeled-row presentation below the large-screen breakpoint. The compact mobile presentation is intended to avoid horizontal scrolling while keeping all seven existing information groups available: timestamp, employee, attendance type, work type/project context, GPS location, photo evidence, and timestamp correction.

- The responsive presentation is implemented in the existing Admin Attendance Livewire SFC; no nested Livewire component or new business logic was introduced.
- Existing sort controls, pagination, empty state, not-yet-present rows, project snapshot dialog, private photo preview/download, map links, and timestamp correction form remain in the same page flow.
- Every cell receives a visible field label in the stacked layout. The table remains semantically tabular on large screens.
- The existing filter, export, query, authorization, and timezone contracts are unchanged.

Automated regression coverage checks the responsive wrapper, fixed-width table class, and mobile field labels. Browser/device QA has not yet been performed; verify actual overflow, card spacing, table readability, pagination, dialog, photo preview, map links, and correction form at the viewport checkpoints in [Visual QA Log](VISUAL-QA-LOG.md).

The mobile filter panel now starts collapsed behind a compact **Filter (n)** disclosure; desktop keeps the complete filter grid visible. Filters remain Livewire live-bound, so no separate Apply action was introduced.

## Admin Attendance layout refinement — 5 November 2026

Follow-up refinements based on browser feedback:

- The activity table is inset from the card edges with horizontal padding; Flux table bleed was removed so the inset is not canceled by negative bleed spacing.
- The Export trigger keeps its download icon, label, and chevron in one horizontal inline group.
- The filter panel is collapsible at all breakpoints. Its header remains visible while collapsed and displays the active-filter count.
- Desktop filter controls use a five-column grid at large widths. With the current nine controls (including the two date fields), the controls occupy two rows. Smaller breakpoints retain responsive two-/three-column layouts.
- The reset action remains outside the collapsible controls, so it is still available while the panel is closed.
- Filter state and Livewire bindings are unchanged.

Automated tests cover the active count, disclosure markup, desktop grid, table inset, and export trigger alignment. Browser QA is still required to verify the rendered spacing and exact two-row fit at supported desktop widths.


## Application shell consolidation — 5 November 2026

- Confirmed `resources/views/layouts/app.blade.php` as the single configured authenticated application layout. It delegates to the SanMal role-aware sidebar shell.
- Removed the legacy starter-kit `resources/views/layouts/app/header.blade.php` alternative from this branch.
- Retained `x-desktop-user-menu` because it is used by the active sidebar layout. Retained the existing `x-ui.page`, `x-ui.page-header`, and `x-ui.section` primitives; no parallel page-layout abstraction was added.
- Authentication layouts and authentication page views were explicitly excluded and were not changed.
- Updated the Page Inventory and Reusable Blade Components documentation with the shell contract and scope boundary.
- Sidebar data preparation is extracted to `App\\View\\Composers\\AppSidebarComposer`. The composer is registered for `*::app.sidebar` because Laravel resolves the anonymous component namespace from its component path; the generated namespace is a hash and is not the literal `layouts::` alias. Feature tests render `<x-layouts::app.sidebar>` itself and verify manager/employee composer data, while a focused composer test covers the guest-safe data contract.
- This is source-level consolidation only. Browser/device QA and exhaustive dynamic-reference indexing are not claimed; see the audit scope in [Reusable Blade Components](../REUSABLE-BLADE-COMPONENTS.md).

## Full-width main and collapsible sidebar — 5 November 2026

- Changed the shared authenticated main region to full-width `<flux:main>` presentation on every page.
- Made desktop sidebar collapsing available to every authenticated role; mobile continues to use the shared header drawer toggle.
- Preserved the shared page surface, normal document scrolling, role-aware navigation, and mobile navigation behavior.

## Employee and authentication visual polish — 5 November 2026

- Applied the existing SanMal palette and shared `x-ui.card` surface to employee request forms, overtime, and notifications; refined employee dashboard actions and attendance notification touch/focus affordance.
- Refined the active Fortify `simple` layout with the shared logo, a restrained brand accent, left-aligned auth headings, and responsive form surface. Updated the login recovery-link placement and email-verification feedback; Fortify routes, fields, CSRF, and submission behavior remain unchanged.
- Imported Lucide `shield-check`, `clock-3`, and `file-check` icons with `php artisan flux:icon` and used them in the shared auth header and employee empty states. Existing Flux Heroicons remain in use for standard action icons.
- Targeted automated tests: `php artisan test --compact` over Auth, Attendance, Dashboard, Employee Profile, Request Center, Leave, Correction, Overtime, Notification, and Security feature tests — **147 passed, 1 skipped (671 assertions)**.
- `npm run build` berhasil; build menampilkan notice opsional bahwa paket `fontaine` tidak terpasang. `git diff --check` lulus. Pint tidak dijalankan karena perubahan hanya Blade dan dokumentasi.
- Browser/device QA belum dilakukan. Verifikasi layout, focus, scrolling, dan touch pada viewport/tema nyata masih diperlukan.


## Admin master-data and approval-page design follow-up — 5 November 2026

Source-level UI refinements were implemented on PR #27 at commit `ffc5566bedfb97adfbec7eca44b6cb1da198067d`:

- Client list and detail now use shared SanMal card surfaces. Site summaries use a responsive sub-grid; Client detail Site cards use one, two, and three columns across mobile, tablet, and desktop.
- Project monitoring cards now use the shared card primitive and a one/two/three-column responsive grid. Project create/edit forms use the standard `max-w-2xl` page width and card-wrapped form surface.
- Client, Location, and Employee create/edit forms use the same shared form surface. No form field, method, action URL, validation rule, or submitted name was changed.
- Employee management keeps its existing `lg:grid-cols-2 xl:hidden` mobile/tablet cards and the wide-screen semantic table. The table remains inside its own horizontally scrollable region.
- Admin Leave Request review now has enum-driven quick status links, compact mobile cards, and the existing semantic table at medium and larger widths. The status values continue to use `LeaveRequestStatus`; no new status mapping was introduced.
- Attendance correction and overtime detail/review surfaces use shared cards and inset tokens. Overtime approval, operational state, finalization, and photo-exception actions remain unchanged.
- Audit Log retains a keyboard-focusable, labeled horizontal-scroll region inside the shared card. The daily attendance employee card and affected page metadata now use SanMal text/surface/divider tokens.
- No route, controller, Eloquent query, authorization rule, validation, Livewire action/state, database schema, camera/GPS flow, attendance calculation, or export contract was changed.
- No additional Flux icon import was necessary; existing Flux icons already cover the affected actions.

Regression assertions were extended in `AdminClientLocationTest`, `AdminProjectTest`, and `AdminEmployeePageTest`. A responsive admin Leave Request queue assertion was added to `LeaveRequestWorkflowTest`. CI for the implementation commit is pending. Browser/device visual QA has not been performed; see [Visual QA Log](VISUAL-QA-LOG.md).
