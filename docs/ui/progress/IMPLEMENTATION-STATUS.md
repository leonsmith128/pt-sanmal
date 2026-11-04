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
| Design foundation dan token | Implemented. Palette, shell Flux inset, surface, dan aturan card dipusatkan di Design Foundation dan CSS aplikasi. | Tidak ada klaim bahwa seluruh halaman sudah lolos visual test. | Belum dilakukan secara menyeluruh. |
| Shared UI primitives | 14 primitive `x-ui.*` tersedia. Tiga utility tambahan: `x-ui.avatar`, `x-ui.photo-preview`, dan `x-ui.notification-dropdown`. `x-ui.sidebar-section` adalah helper navigasi shell. | Test button, card, layout, feedback, metadata, notification dropdown, dan photo preview lulus pada run #1460. | Belum dilakukan secara menyeluruh. |
| Application shell dan navigasi | Sidebar Admin/Manager berkelompok; Employee tetap tidak berkelompok. Mobile memakai header dan drawer, tanpa fixed bottom navigation. Scrollbar auto-hide diterapkan hanya pada Flux inset main desktop: lebar tipis, thumb semi-transparan, tampil selama scrolling dan kembali transparan setelah 850 ms idle; hover pada thumb meningkatkan kontras di browser yang mendukung pseudo-element scrollbar. Scroll document mobile dan scrollbar komponen lain tidak diubah. | Regression test shell/sidebar lulus pada run #1418; build untuk perubahan scrollbar menunggu CI terbaru. | Browser QA scrollbar belum dilakukan pada desktop, Firefox/WebKit, light/dark, serta pemeriksaan bahwa mobile tetap document-scroll. |
| Halaman Admin | Source review mencakup halaman Admin dan komponen terkait. Beberapa perbaikan filter, wrapping, density, dan layout sudah diterapkan. | Test yang terkait berjalan dalam CI; lihat hasil run terbaru di bawah. | Belum ada bukti QA lintas halaman dan viewport. |
| Employee Dashboard | Layout V1, ringkasan bulanan, Pengajuan Saya, Akses Cepat, Aktivitas Terbaru, dan notifikasi tersedia. | Test dashboard lulus pada run #1460. Assertion divider diperbaiki agar hanya memeriksa container Ringkasan Bulan Ini, bukan seluruh halaman. | Belum dilakukan. |
| Employee Profile | Source diperbarui: circular avatar melalui `x-ui.avatar`, modal pratinjau bersama `x-ui.photo-preview`, deskripsi redundan dihapus, dan pesan validasi upload menjadi feedback batas ukuran. URL foto tersimpan disajikan oleh endpoint authenticated yang tidak bergantung pada `public/storage` symlink. Follow-up 5 November membatasi lebar halaman ke `max-w-3xl` agar form/detail tidak melebar seperti halaman operasional. | Test route serving foto tanpa storage symlink dan render modal profil lulus pada run #1460. Regression assertion untuk lebar halaman ditambahkan pada commit `e660a63`; CI [run #1521](https://github.com/leonsmith128/pt-sanmal/actions/runs/41889276067) sukses pada Pint, targeted PHPStan, MariaDB, dan SQLite. | Belum dilakukan. |
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
