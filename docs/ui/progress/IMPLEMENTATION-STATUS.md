# UI/UX V1 — Status Implementasi

> Snapshot: 4 November 2026. Tracking: branch `docs/ui-v1-scope-and-pr-workflow` · [PR #27](https://github.com/leonsmith128/pt-sanmal/pull/27). Periksa ulang source, branch HEAD, dan CI sebelum memakai snapshot ini untuk keputusan berikutnya.

## Cara membaca status

- **Source implemented** berarti implementasi tersedia di branch; belum otomatis berarti tampilannya sudah benar di browser.
- **Automated test passed** berarti test terkait benar-benar lulus pada run yang disebut.
- **Browser/device QA passed** hanya boleh ditulis jika hasil browser atau perangkat dicatat di [Visual QA Log](VISUAL-QA-LOG.md).
- Source review, test komponen, dan CI tidak menggantikan visual QA atau pengujian kamera/GPS pada perangkat nyata.

## Ringkasan saat ini

| Area | Kondisi source | Bukti otomatis terbaru | Browser/device QA |
|---|---|---|---|
| Design foundation dan token | Implemented. Palette, shell Flux inset, surface, dan aturan card dipusatkan di Design Foundation dan CSS aplikasi. | Tidak ada klaim bahwa seluruh halaman sudah lolos visual test. | Belum dilakukan secara menyeluruh. |
| Shared UI primitives | 14 primitive `x-ui.*` tersedia. Dua utility tambahan: `x-ui.avatar` dan `x-ui.notification-dropdown`. `x-ui.sidebar-section` adalah helper navigasi shell. | Test button, card, layout, feedback, metadata, dan notification dropdown lulus pada run #1418. | Belum dilakukan secara menyeluruh. |
| Application shell dan navigasi | Sidebar Admin/Manager berkelompok; Employee tetap tidak berkelompok. Mobile memakai header dan drawer, tanpa fixed bottom navigation. | Regression test shell/sidebar lulus pada run #1418. | Mobile, tablet, desktop, light/dark masih perlu diperiksa. |
| Halaman Admin | Source review mencakup halaman Admin dan komponen terkait. Beberapa perbaikan filter, wrapping, density, dan layout sudah diterapkan. | Test yang terkait berjalan dalam CI; lihat hasil run terbaru di bawah. | Belum ada bukti QA lintas halaman dan viewport. |
| Employee Dashboard | Layout V1, ringkasan bulanan, Pengajuan Saya, Akses Cepat, Aktivitas Terbaru, dan notifikasi tersedia. | Sebagian besar test dashboard lulus pada run #1418. Satu assertion test gagal karena mencari class divider pada seluruh HTML, termasuk bagian Aktivitas Terbaru. | Belum dilakukan. |
| Employee Profile | Source diperbarui: circular avatar melalui `x-ui.avatar`, modal pratinjau bersama `x-ui.photo-preview`, deskripsi redundan dihapus, dan pesan validasi upload menjadi feedback batas ukuran. URL foto tersimpan disajikan oleh endpoint authenticated yang tidak bergantung pada `public/storage` symlink. | Test route serving dan render modal ditambahkan; belum dijalankan pada runtime lokal. | Belum dilakukan. |
| Employee Attendance | Urutan source: header pegawai → kalender → ringkasan jam → status kehadiran/lembur → satu form absensi → modal. Calendar, time summary, info card, dan photo preview dipisah menjadi Blade Components; satu Livewire SFC tetap menjadi orchestrator. | Test urutan halaman dan test pembuka kalender bulanan lulus pada run #1418. | Belum dilakukan; terutama alur kamera/GPS dan ukuran layar kecil. |
| Kalender Absensi | Weekly calendar dan monthly picker enam baris tersedia. Indikator memakai status domain; `no_record` tidak diartikan sebagai absent. | Feature test pemilihan tanggal dan sinkronisasi kalender lulus pada run #1418. | Belum dilakukan pada viewport dan theme yang ditentukan. |
| Photo preview | Komponen terpisah menampilkan thumbnail, modal foto penuh, unduh foto sementara, dan aksi foto ulang. | Test download foto dengan selfie tersedia lulus pada run #1418. Satu test render halaman gagal karena meminta markup preview saat halaman dibuka tanpa selfie. | Belum dilakukan; modal dan siklus capture perlu diuji di browser/perangkat. |
| Accessibility dan interaction-state | Beberapa kontrol sudah memiliki label/semantics; audit menyeluruh belum selesai. | Test otomatis hanya memeriksa kontrak yang tercakup di masing-masing test. | Audit keyboard, focus, dialog, loading, error, dan touch target masih terbuka. |

## CI terbaru yang diperiksa

Run [#1418](https://github.com/leonsmith128/pt-sanmal/actions/runs/41771398379) dijalankan pada PR #27 tanggal 4 November 2026. Workflow menguji merge ref PR:

- PR head: `7f887da6b44e2e43ea0c9e6ba1eb9764a4b36669`
- Merge ref yang diuji: `48f992148f546cb06264e2aae928581f345984a7`

Setelah run ini, perubahan yang ditambahkan pada branch adalah dokumentasi saja; tidak ada perubahan source aplikasi setelah PR head yang diuji. Run ini tetap bukan bukti browser/device QA.

**Status: CI gagal pada run #1418.**

| Pemeriksaan | Hasil |
|---|---|
| Vite asset build | Passed |
| Pint untuk file PHP yang berubah | Passed |
| Targeted PHPStan untuk changed application code | Passed |
| Repository-wide static analysis | Step selesai, tetapi baseline error repository ditoleransi oleh `continue-on-error`; jangan menyebut seluruh repository bersih |
| MariaDB 11 | **275 passed, 2 failed, 1 skipped** — 1.122 assertions |
| SQLite | Skipped karena test MariaDB gagal |
| Kesimpulan workflow | **Failure** |

### Dua kegagalan test yang perlu ditindaklanjuti

1. **Photo preview — `AttendancePagesTest.php:207`**

   Test `attendance page renders a separate full-size photo preview...` mengharapkan string `previewOpen` pada GET halaman absensi tanpa selfie. Namun `x-attendance.photo-preview` memang hanya merender markup ketika selfie tersedia dan previewable. Test khusus download yang mengisi selfie justru lulus. Perbaiki skenario/assertion test agar menguji state kosong dan state dengan selfie secara terpisah; jangan menghapus conditional render hanya untuk membuat test lulus.

2. **Dashboard — `DashboardTest.php:103`**

   Test `dashboard metric cards center labels and values without separators` memakai `assertDontSee('divide-sanmal-border')` terhadap seluruh HTML. Class divider memang dipakai pada daftar Aktivitas Terbaru, bukan sebagai separator di grid Ringkasan Bulan Ini. Persempit assertion ke elemen grid statistik yang sedang diuji; jangan menghapus divider yang memang digunakan oleh daftar aktivitas.

Test kalender bulanan, urutan konten halaman absensi, dan download foto dengan selfie lulus pada run yang sama. Hal ini tidak mengubah kesimpulan bahwa workflow CI secara keseluruhan masih gagal.

## Keputusan desain dan arsitektur yang sudah diterapkan

- Gunakan Flux UI dan primitive `x-ui.*` yang tersedia; jangan membuat design system paralel.
- Employee mobile-first; Admin mengutamakan keterbacaan informasi desktop/tablet dan tetap mobile-friendly.
- Card standar memakai surface token, tanpa border pada container luar, dan shadow untuk elevation. Border tetap boleh dipakai pada control, divider, tabel, atau kelompok inset jika memang menyampaikan struktur.
- Satu Livewire SFC menjadi orchestrator halaman interaktif. Blade Components memisahkan markup visual. Actions menangani proses bisnis; Services menangani kalkulasi/klasifikasi domain. Nested Livewire hanya jika state/lifecycle independen benar-benar diperlukan.
- Kalender dan ringkasan jam adalah Blade Components; state tanggal tetap dimiliki parent Attendance SFC.
- Camera component tetap terpisah dari photo-preview component. Jangan mengubah lifecycle kamera, GPS, watermark, upload, atau attendance submission sebagai pekerjaan visual tanpa scope dan test khusus.

## Pekerjaan tersisa — urutan yang disarankan

1. Perbaiki dua assertion CI di atas dengan menguji container/state yang tepat.
2. Jalankan ulang CI dan pastikan MariaDB serta SQLite selesai; jangan menyatakan lulus sebelum workflow terbaru benar-benar hijau.
3. Lakukan browser QA Employee Dashboard, Employee Attendance, dan halaman Admin pada 360–390 px, 414–430 px, 768 px, 1024 px, dan 1280–1440 px; periksa light/dark jika didukung.
4. Uji alur kamera/GPS pada perangkat nyata melalui HTTPS: permission, lokasi siap/gagal, orientasi, shutter, preview, foto ulang, upload, dan konfirmasi.
5. Selesaikan accessibility dan interaction-state review.
6. Rekonsiliasi seluruh scope V1 dan bukti QA sebelum meminta persetujuan merge PR #27.

## Dokumen sumber

- [Design Foundation](../DESIGN-FOUNDATION.md) — aturan visual dan token.
- [Page Inventory](../PAGE-INVENTORY.md) — halaman yang masuk cakupan.
- [Component Catalog](../COMPONENT-CATALOG.md) dan [Reusable Blade Components](../REUSABLE-BLADE-COMPONENTS.md) — peta dan kontrak komponen.
- [Employee Attendance Layout V1](../EMPLOYEE-ATTENDANCE-LAYOUT-V1.md) dan [Calendar V1](../EMPLOYEE-ATTENDANCE-CALENDAR-V1.md) — spesifikasi halaman absensi.
- [Employee Dashboard Layout V1](../EMPLOYEE-DASHBOARD-LAYOUT-V1.md) dan [Data Contract](../EMPLOYEE-DASHBOARD-DATA-CONTRACT.md) — tampilan dan definisi data dashboard.
- [Visual QA Log](VISUAL-QA-LOG.md) — bukti browser/device aktual.
