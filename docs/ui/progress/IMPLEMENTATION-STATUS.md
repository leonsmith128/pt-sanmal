# UI/UX V1 Implementation Status

> Status harus mencerminkan bukti, bukan rencana. Branch tracking: `docs/ui-v1-scope-and-pr-workflow` · [PR #27](https://github.com/leonsmith128/pt-sanmal/pull/27).

## Vocabulary

- **Not started** — belum dikerjakan.
- **In progress** — pekerjaan sedang berlangsung.
- **Implemented, unverified** — source tersedia, tetapi pemeriksaan yang diperlukan belum lengkap.
- **Verified** — pemeriksaan otomatis dan/atau visual yang relevan memiliki bukti sesuai klaim.
- **Blocked** — dependency/keputusan menghalangi pekerjaan.
- **Accepted limitation** — owner menerima gap yang terdokumentasi.

Source inspection bukan browser QA. Component test bukan bukti seluruh workflow halaman. CI pass bukan bukti visual lintas viewport.

## Ringkasan milestone

| Milestone | Status | Catatan |
|---|---|---|
| Fondasi reusable UI `x-ui.*` | **Implemented** | Seluruh 14 primitive bersama tersedia: badge, button, card, checkbox, empty-state, field, flash-message, input, metadata-item, page-header, page, section, select, textarea. Kontrak dan aturan penggunaan ada di [Reusable Blade Components](../REUSABLE-BLADE-COMPONENTS.md). |
| Standardisasi token dan styling dasar | **Implemented** | Token brand/semantic serta fondasi button, field, focus, disabled, dark-surface, page/layout, dan metadata telah diterapkan. |
| Adopsi komponen pada halaman | **Implemented, partial coverage** | Komponen telah diadopsi pada sejumlah halaman Admin, Employee, Shared, dan Authentication. Tidak berarti seluruh halaman telah didesain ulang atau semua markup satu-off harus diekstrak. Periksa source dan [Page Inventory](../PAGE-INVENTORY.md). |
| Koreksi responsivitas temuan terakhir | **CI verified; visual pending** | Filter Admin Kehadiran, icon pada detail lembur, dan susunan tombol Pusat Permohonan telah diperbaiki. CI terbaru lulus pada commit `6da6b96ee1e8be19126cba43a62d6ec51aaf22ca`, [run 1065](https://github.com/leonsmith128/pt-sanmal/actions/runs/41566842563). Owner/browser visual recheck belum dicatat. |
| Kontrak data Employee Dashboard | **Specified** | Definisi agregasi status harian, jam kerja non-lembur, lembur disetujui, pengajuan tertunda, aktivitas terbaru, timezone, dan konsistensi Employee/Admin ditetapkan di [Employee Dashboard Data Contract](../EMPLOYEE-DASHBOARD-DATA-CONTRACT.md). |
| Employee Dashboard V1 layout | **Implemented, unverified** | Layout dan controller role branch tersedia di source; automated coverage baru mencakup pemisahan tampilan pegawai tanpa profil dari ringkasan admin. Agregasi kompleks belum memiliki coverage kontrak lengkap dan belum ada browser QA. Lihat [Employee Dashboard Layout V1](../EMPLOYEE-DASHBOARD-LAYOUT-V1.md). |
| Responsive audit menyeluruh | Not started | Audit per halaman/viewport setelah desain dan layouting diterapkan. |
| Accessibility dan interaction-state audit | Not started | Keyboard, focus, semantics, touch target, loading/error/empty, dan theme. |
| Final V1 review | Not started | Rekonsiliasi scope, test, visual QA, limitations, dan review PR sebelum merge. |

## Cakupan fondasi reusable yang sudah tersedia

- **Layout:** `x-ui.page`, `x-ui.page-header`, `x-ui.section`.
- **Action:** `x-ui.button`.
- **Surface:** `x-ui.card`.
- **Form:** `x-ui.field`, `x-ui.input`, `x-ui.select`, `x-ui.textarea`, `x-ui.checkbox`.
- **Feedback:** `x-ui.flash-message`, `x-ui.empty-state`.
- **Status dan metadata:** `x-ui.badge`, `x-ui.metadata-item`.

Komponen generik tidak mengambil alih business logic, authorization, route construction, enum-to-label/color mapping, query, atau state workflow. Domain adapter tetap dipertahankan bila dibutuhkan. Jangan menambah wrapper hanya untuk menghilangkan markup yang belum terbukti berulang.

## Adopsi halaman yang tercatat

- Admin: halaman monitoring kehadiran, CRUD/review yang diaudit, employee/project/client/location, serta beberapa detail dan dashboard memakai primitive sesuai kebutuhan.
- Employee: attendance, attendance history, correction submission, leave request, overtime, profile, dan beberapa halaman pendukung memakai primitive layout/form/status yang relevan.
- Shared dan Authentication: dashboard, notifications, account security, login, confirm-password, forgot-password, dan reset-password telah mengadopsi primitive terkait.

Daftar ini adalah ringkasan adopsi yang tercatat, bukan klaim seluruh halaman telah selesai direview secara visual. Gunakan [Page Inventory](../PAGE-INVENTORY.md) untuk cakupan halaman dan inspeksi source branch sebelum mengubah status.

## Catatan verifikasi

- Button: CI lulus pada commit `00d255c1d5384eb312e7842e85954c8ef7c9ca15`, [run 1004](https://github.com/leonsmith128/pt-sanmal/actions/runs/41543440506). Owner mengonfirmasi visual QA primary button pada light/dark dan beberapa ukuran layar; detail browser/device/viewport tidak tercatat di [Visual QA Log](VISUAL-QA-LOG.md).
- Form, layout, card, feedback, badge, metadata: focused tests tersedia dalam source; jangan mengklaim test suite terbaru lulus kecuali run yang sesuai sudah diperiksa.
- Responsive fixes terakhir: CI lulus pada commit `6da6b96ee1e8be19126cba43a62d6ec51aaf22ca`, [run 1065](https://github.com/leonsmith128/pt-sanmal/actions/runs/41566842563). Ini tidak menggantikan visual recheck halaman terkait.
- Tidak ada klaim bahwa seluruh halaman telah melewati browser/device QA.

## Handoff ke tahap berikutnya

Tahap standardisasi reusable component ditutup sebagai fondasi implementasi. Kontrak data Employee Dashboard sudah ditetapkan di [Employee Dashboard Data Contract](../EMPLOYEE-DASHBOARD-DATA-CONTRACT.md); kontrak ini belum diimplementasikan atau diuji. Jangan mengulang audit komponen generik kecuali ditemukan gap konkret dari penggunaan nyata.

Employee Dashboard V1 layout, controller role branch, aggregation service awal, dan view kini tersedia di source. Tahap ini **belum verified**: tambahkan coverage untuk durasi sesi/koreksi/overtime/pending request/timezone, periksa hasil CI terbaru, lalu lakukan browser QA responsif. Audit kontrak dengan teliti sebelum menganggap agregasi sebagai final; terutama finalisasi lembur dan interval yang perlu ditinjau. Setelah Dashboard lolos, lanjutkan layout Employee Attendance. Pertahankan route, authorization, validation, Livewire, query/filter, export, camera/GPS, attendance timestamp, dan domain workflow. Catat test dan browser QA aktual di [Visual QA Log](VISUAL-QA-LOG.md).
