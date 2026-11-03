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
| Kontrak data Employee Dashboard | **Policy approved; resolver implemented, unverified** | Kebijakan UTC, Office `Asia/Makassar`, Onsite berbasis GPS valid, fallback eksplisit, dan review tercatat di [Employee Dashboard Data Contract](../EMPLOYEE-DASHBOARD-DATA-CONTRACT.md). Resolver offline `aldesrahim/laravel-timezone-indonesia` v1.1.0 kini berada di balik `AttendanceTimezoneResolver`; event menyimpan `timezone_source`, dan fallback ditandai `needs_review`. Test resolver serta snapshot sesi sudah ditulis tetapi belum dijalankan. Agregasi Employee Dashboard kini mengevaluasi periode bulan berjalan secara independen memakai timezone dan tanggal efektif Clock In setiap sesi; verifikasi test dan CI masih pending. |
| Navigation shell and responsive sidebar | **Implemented, unverified** | Flux Inset Main spacing review, responsive grouped-section separators, Employee ungrouped drawer navigation, role-aligned labels, and 20px icons are implemented. Page-level duplicate padding was removed from affected views and Employee Attendance now uses the wider page container. Focused tests and browser QA remain pending. |
| Employee notification dropdown | **Implemented, browser QA pending** | Reusable `x-ui.notification-dropdown` menerima data dari caller, menampilkan maksimal 5 notifikasi terbaru, unread count/indicator, empty state, dan tautan ke daftar lengkap. Dashboard pegawai memasok data user-scoped; route baca yang ada tetap digunakan. Focused component dan endpoint ownership tests ditambahkan. Popover memakai posisi fixed dan batas tinggi viewport untuk mobile/tablet, lalu posisi relatif di desktop. Ikon notifikasi pada header Employee Dashboard kini tidak disembunyikan pada viewport mobile. Browser QA lintas viewport dan CI head terbaru tetap wajib. |
| Employee Dashboard V1 layout | **Implemented, unverified** | Layout dan controller role branch tersedia. View kini menampilkan rincian pending leave/permission/sick berdasarkan nama jenis permohonan dan ikon notifikasi tetap tersedia di mobile. Feature test ditambahkan untuk breakdown pengajuan, employee scoping, exclusion status approved, status `no_record` yang tidak berubah menjadi `absent`, koreksi status harian, sesi tertutup/terbuka, pemisahan durasi kerja bersih dari lembur disetujui, koreksi timestamp efektif, atribusi sesi lintas tengah malam ke tanggal Clock In, penolakan durasi pada status perlu ditinjau, dan penandaan interval lembur di luar sesi absensi. Unit test tambahan memeriksa pemilihan interval overtime `actual`, `scheduled`, `manual`, sesi berjalan, pembatalan, interval tidak valid, serta overlap. UI memberi peringatan bila interval lembur final berada di luar sesi absensi; nilai final tetap dipertahankan dan hanya overlap yang berada dalam sesi dikurangkan dari jam kerja. Coverage tambahan kini mencakup Clock In/Clock Out duplikat, event Clock Out unanchored saat ada lebih dari satu anchor terbuka, beberapa interval lembur approved dalam satu sesi, interval efektif Clock Out yang tidak lebih akhir dari Clock In, sesi lintas tengah malam yang melewati pergantian bulan pada timezone `Asia/Makassar`, transisi lembur pending menjadi approved, serta kesesuaian klasifikasi harian Employee dan ringkasan Admin untuk pegawai/tanggal yang sama. Batasan yang masih tersisa: verifikasi resolver dan migrasi `timezone_source`, rentang kalender/timezone yang lebih luas, isolasi menyeluruh seluruh tipe data, dan parity durasi kerja/lembur karena metrik tersebut belum ditampilkan pada Admin Dashboard. Agregasi bulan berjalan sudah memakai timezone Clock In per sesi; automated test baru ditambahkan tetapi belum dijalankan. Browser QA belum dilakukan. Lihat [Employee Dashboard Layout V1](../EMPLOYEE-DASHBOARD-LAYOUT-V1.md). |
| Responsive audit menyeluruh | Not started | Audit per halaman/viewport setelah desain dan layouting diterapkan. |
| Accessibility dan interaction-state audit | Not started | Keyboard, focus, semantics, touch target, loading/error/empty, dan theme. |
| Final V1 review | Not started | Rekonsiliasi scope, test, visual QA, limitations, dan review PR sebelum merge. |

## Employee Attendance — implementasi awal

- Tombol utama memakai label ringkas **Konfirmasi Lembur** ketika jadwal lembur sudah tiba; saat jadwal belum tiba, tombol tetap disabled dan pesan menjelaskan bahwa pengajuan harus dibatalkan sebelum Clock Out.
- Backend sudah menegakkan larangan konfirmasi sebelum `scheduled_start_at`, validasi state pengajuan, dan pemblokiran Clock Out untuk pengajuan aktif yang belum dikonfirmasi. Pembatalan pegawai dibatasi pada state `scheduled`/`awaiting_photo` tanpa `start_record_id`.
- Overtime card menampilkan waktu sejak jadwal tiba sebelum konfirmasi dan durasi aktual dari `startRecord.recorded_at` setelah konfirmasi. Tampilan timer hanya berjalan untuk pengajuan pada sesi yang masih terbuka; finalisasi admin tetap menjadi sumber durasi yang diakui.
- Tombol pembatalan pengajuan tetap berada di overtime card sebagai satu-satunya jalan pegawai membuka blokir Clock Out.
- Alur satu tombol: ketika belum ada foto, tombol utama memicu kamera; setelah foto berhasil diunggah, tombol berubah menjadi **Lanjutkan** untuk membuka modal konfirmasi. Tombol kamera sekunder dihapus dari halaman.
- Kamera baru dibuka setelah izin lokasi diperiksa, koordinat segar didapatkan, dan reverse geocoding menghasilkan alamat snapshot. Shutter tetap manual; preview menyediakan foto ulang/gunakan foto; upload tetap melalui Livewire; pencatatan tetap menunggu konfirmasi dan validasi server.
- Feature test menegaskan pemicu kamera dari tombol utama dan tahap **Lanjutkan**. Regression test lembur mencakup clock-out yang diblokir oleh jadwal aktif, pembatalan jadwal yang membuka kembali clock-out, dan state konfirmasi saat jadwal tiba.
- GPS unavailable tetap merupakan kondisi yang ditandai untuk review oleh backend pada alur pencatatan yang menerima foto; alur kamera saat ini mensyaratkan GPS dan alamat siap sebelum membuka kamera. Kegagalan izin GPS/kamera dan urutan shutter-preview-upload masih memerlukan pengujian browser/device karena belum ada test runtime JavaScript khusus.
- Pengujian browser/device untuk urutan GPS, izin kamera, shutter, preview, upload, dan error handling masih diperlukan.
- Pass layout awal: ringkasan waktu dan form tindakan absensi kini ditempatkan sebelum pemilih tanggal/riwayat, sehingga aksi utama lebih cepat ditemukan pada mobile. Form diberi heading dan instruksi kontekstual sesuai mode Clock In, Clock Out, atau konfirmasi lembur. Kartu detail dan kalender tetap tersedia di bawah; hanya urutan presentasi yang diubah.
- Regression test ditambahkan untuk memastikan form tindakan muncul sebelum pemilih tanggal. Test ini belum dijalankan; perubahan masih **unverified** dan belum melewati browser QA.
- **Status: Implemented, unverified.** Test suite, Pint, build, dan CI head terbaru belum lulus/terverifikasi. Regression coverage backend untuk pemblokiran clock-out dan konfirmasi lembur sudah ada, tetapi perlu dijalankan ulang bersama test UI yang diperbarui. Browser/device QA tetap ditunda sampai seluruh halaman pegawai selesai.

## Cakupan fondasi reusable yang sudah tersedia

- **Layout:** `x-ui.page`, `x-ui.page-header`, `x-ui.section`.
- **Action:** `x-ui.button`.
- **Surface:** `x-ui.card`.
- **Form:** `x-ui.field`, `x-ui.input`, `x-ui.select`, `x-ui.textarea`, `x-ui.checkbox`.
- **Feedback:** `x-ui.flash-message`, `x-ui.empty-state`.
- **Status dan metadata:** `x-ui.badge`, `x-ui.metadata-item`.
- **Identity:** `x-ui.avatar` untuk foto profil dan fallback inisial berbentuk lingkaran; sudah diterapkan pada header Dashboard dan avatar menu profil desktop/mobile yang teridentifikasi. Halaman Profil masih perlu migrasi setelah lokasi source view diverifikasi.
- **Navigation utility:** `x-ui.notification-dropdown` (domain-aware; data disediakan oleh controller).

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

Employee Dashboard V1 layout, controller role branch, aggregation service awal, dan view kini tersedia di source. Rincian jenis pengajuan tertunda dan visibilitas ikon notifikasi mobile telah diperbaiki; feature test dan unit test tambahan untuk status, sesi ambigu/duplikat, batas bulan/timezone, transisi approval lembur, dan daily-status parity Employee/Admin telah ditulis, tetapi belum dijalankan.

Resolver timezone kini menggunakan dependency offline `aldesrahim/laravel-timezone-indonesia` v1.1.0 melalui `App\Services\AttendanceTimezoneResolver`. Clock In Office menyimpan `Asia/Makassar`; Clock In Onsite memakai IANA timezone hasil koordinat GPS yang valid; bila GPS/resolver tidak dapat menentukan zona, sistem memakai fallback WITA dengan `timezone_source=fallback` dan status harian `needs_review`. Clock Out dan konfirmasi mulai lembur mewarisi timezone dari anchor Clock In. Migration `timezone_source`, test koordinat empat zona, fallback, tanggal lokal, Clock Out, dan Overtime Start telah ditambahkan. Seluruh perubahan tersebut **belum diverifikasi melalui test run atau CI**.

Gap yang masih terbuka: verifikasi automated test untuk agregasi bulan berjalan per timezone Clock In sesi, perluasan cakupan kalender/timezone, dan isolasi data menyeluruh. Kalender laporan Admin kini ditetapkan ke WITA dan diterapkan pada dashboard, rekap harian, monitoring kehadiran, serta ekspor CSV; test baru sudah ditulis tetapi belum dijalankan. Lengkapi test kalender/timezone, jalankan test terfokus dan full suite setelah implementasi selesai, lalu lakukan browser QA responsif. Admin Dashboard saat ini hanya menyajikan ringkasan status harian, sehingga parity durasi kerja/lembur belum dapat dibandingkan tanpa memperluas scope tampilan Admin. Pertahankan route, authorization, validation, Livewire, query/filter, export, camera/GPS, attendance timestamp, dan domain workflow. Catat test dan browser QA aktual di [Visual QA Log](VISUAL-QA-LOG.md).


## Navigation shell — implementasi awal

- Layout utama memakai `<flux:main inset>` dengan `lg:ms-0 lg:mt-0`, tetap menjadi sibling langsung sidebar/header, dan menggunakan background luar yang konsisten. Body serta sidebar memakai tinggi viewport `dvh`.
- Employee memakai header atas dan drawer sidebar pada layar kecil; fixed bottom navigation dihapus. Daftar Employee tidak memakai grouping dan memuat Beranda, Absensi, Riwayat, Permohonan, serta Lembur.
- Link aktivitas pribadi Manager memakai label yang sama dengan Employee. Label item Admin dipersingkat sementara grouping Operasional, Master Data, dan Permohonan tetap dipertahankan.
- Komponen baru `x-ui.sidebar-section` menjaga tinggi baris heading. Saat sidebar terbuka, heading tampil; saat collapsed, heading diganti separator Flux subtle sehingga item ikon tidak bergeser naik. Employee tidak menggunakan komponen ini.
- Ikon item sidebar distandardisasi menjadi 20px persegi dengan ukuran tetap pada kondisi expanded/collapsed.
- Inset Main menjadi pemilik spacing shell. Padding root `x-ui.page` yang menggandakan gutter telah dihapus dari Rekap Harian, Persetujuan Permohonan, Riwayat Absensi, Pusat Permohonan, form Koreksi/Permohonan, dan Notifikasi. `x-ui.page` tetap mengatur lebar konten per halaman; halaman Absensi Pegawai diperlebar dari `max-w-xl` ke lebar default `max-w-7xl` agar tidak tampak sempit di desktop.
- Regression test diperbarui untuk menghitung 13 item navigasi Admin, lima separator grouping, tidak adanya separator pada Employee, dan lebar halaman Absensi. **Test belum dijalankan.**
- **Status: Implemented, unverified.** Browser QA pada 360–430px, 768–1024px, dan 1280–1440px masih diperlukan. Periksa khusus jarak gutter inset, lebar konten halaman Absensi, dan tinggi/posisi separator pada sidebar expanded/collapsed.
