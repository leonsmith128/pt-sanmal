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
| Standardisasi token dan styling dasar | **Implemented; browser QA pending** | Light primary button memakai biru; dark primary dan secondary memakai tingkatan aksen kuning. Card linked/non-linked memakai token surface-card terpisah (light `#F8FCFF`, dark `#192B40`), sementara main panel dark tetap `#122235`; linked-card hover memakai elevated surface. Notification panel memakai surface token SanMal. Browser visual QA masih diperlukan. |
| Adopsi komponen pada halaman | **Implemented, partial coverage** | Komponen telah diadopsi pada sejumlah halaman Admin, Employee, Shared, dan Authentication. Tidak berarti seluruh halaman telah didesain ulang atau semua markup satu-off harus diekstrak. Periksa source dan [Page Inventory](../PAGE-INVENTORY.md). |
| Koreksi responsivitas temuan terakhir | **CI verified; visual pending** | Filter Admin Kehadiran, icon pada detail lembur, dan susunan tombol Pusat Permohonan telah diperbaiki. Full CI terbaru untuk branch ini lulus pada commit `e85558fa29a5050d42a3b609ccac4442d29ae665`, [run 1297](https://github.com/leonsmith128/pt-sanmal/actions/runs/41671815016). Browser visual recheck tetap belum dicatat. |
| Kontrak data Employee Dashboard | **Policy approved; covered resolver, migration, and aggregation tests CI verified; browser and broader calendar review pending** | Kebijakan UTC, Office `Asia/Makassar`, Onsite berbasis GPS valid, fallback eksplisit, dan review tercatat di [Employee Dashboard Data Contract](../EMPLOYEE-DASHBOARD-DATA-CONTRACT.md). Resolver offline `aldesrahim/laravel-timezone-indonesia` v1.1.0 berada di balik `AttendanceTimezoneResolver`; event menyimpan `timezone_source`, dan fallback ditandai `needs_review`. Resolver, snapshot sesi, agregasi per timezone Clock In, batas tanggal, serta migrasi telah melewati full CI pada [run 1297](https://github.com/leonsmith128/pt-sanmal/actions/runs/41671815016) di MariaDB dan SQLite. Matriks kalender/timezone yang lebih luas dan browser/device QA masih diperlukan. |
| Navigation shell and responsive sidebar | **Implemented; regression tests CI verified; browser QA pending** | Admin Dashboard is a standalone first item; Admin/Superadmin with a linked employee profile receive `Aktivitas Saya` beginning with a separate employee-scoped Dashboard Pribadi. Admin groups follow Operasional, Persetujuan, Data Master, Sistem; personal request labels are distinct from approval queues. Employee navigation remains ungrouped. Shell uses Flux `<flux:main inset>` with the page surface (`#E5EBF1` light / `#0B1623` dark); body and sidebar share the sidebar surface (`#CAD6E2` light / `#0B1623` dark) without a divider. Sidebar icon sizing and collapsed-state visibility passed the latest CI run 1297. Browser QA at mobile, tablet, and desktop widths remains pending. |
| Employee notification dropdown | **Implemented; automated tests CI verified; browser QA pending** | Reusable `x-ui.notification-dropdown` menerima data dari caller, menampilkan maksimal 5 notifikasi terbaru, unread count/indicator, empty state, dan tautan ke daftar lengkap. Dashboard pegawai memasok data user-scoped; route baca yang ada tetap digunakan. Focused component dan endpoint ownership tests lulus dalam full CI run 1297. Popover memakai posisi fixed dan batas tinggi viewport untuk mobile/tablet, lalu posisi relatif di desktop. Ikon notifikasi pada header Employee Dashboard tetap tersedia pada viewport mobile. Browser QA lintas viewport masih diperlukan. |
| Employee Dashboard V1 layout | **Implemented; automated tests and CI verified; browser QA pending** | Layout dan controller role branch tersedia. View menampilkan rincian pending leave/permission/sick berdasarkan nama jenis permohonan dan ikon notifikasi tetap tersedia di mobile. Ringkasan bulanan memakai grid dua kolom di bawah breakpoint `xl` dan empat kolom mulai `xl`; label serta nilai rata tengah tanpa separator. Card statistik Admin dan card tindakan Admin juga memakai susunan vertikal dengan label/nilai rata tengah. Feature test ditambahkan untuk breakdown pengajuan, employee scoping, exclusion status approved, status `no_record` yang tidak berubah menjadi `absent`, koreksi status harian, sesi tertutup/terbuka, pemisahan durasi kerja bersih dari lembur disetujui, koreksi timestamp efektif, atribusi sesi lintas tengah malam ke tanggal Clock In, penolakan durasi pada status perlu ditinjau, dan penandaan interval lembur di luar sesi absensi. Unit test tambahan memeriksa pemilihan interval overtime `actual`, `scheduled`, `manual`, sesi berjalan, pembatalan, interval tidak valid, serta overlap. UI memberi peringatan bila interval lembur final berada di luar sesi absensi; nilai final tetap dipertahankan dan hanya overlap yang berada dalam sesi dikurangkan dari jam kerja. Coverage tambahan kini mencakup Clock In/Clock Out duplikat, event Clock Out unanchored saat ada lebih dari satu anchor terbuka, beberapa interval lembur approved dalam satu sesi, interval efektif Clock Out yang tidak lebih akhir dari Clock In, sesi lintas tengah malam yang melewati pergantian bulan pada timezone `Asia/Makassar`, transisi lembur pending menjadi approved, serta kesesuaian klasifikasi harian Employee dan ringkasan Admin untuk pegawai/tanggal yang sama. Batasan yang masih tersisa: perluasan matriks kalender/timezone dan parity durasi kerja/lembur karena metrik tersebut belum ditampilkan pada Admin Dashboard. Resolver, migrasi, agregasi per sesi, dan regression tests yang tercakup telah lulus full CI run 1297 pada MariaDB dan SQLite. Browser QA belum dilakukan. Lihat [Employee Dashboard Layout V1](../EMPLOYEE-DASHBOARD-LAYOUT-V1.md). |
| Audit source seluruh Views | **Source review completed; browser QA pending** | Seluruh kelompok page view Admin, Employee, Dashboard, Notifications, Settings, Authentication, layout, dan komponen bersama diperiksa ulang pada branch ini. Review mencakup struktur komposisi, adopsi primitive, surface/theme classes, responsive intent, dan kontrak interaksi yang tampak dari view. Satu mismatch tanggal ditemukan pada CTA Admin Dashboard: link rekap harian memakai tanggal server default, sementara kalender laporan memakai WITA. Controller kini memasok `todayDate` berbasis `Asia/Makassar` ke view dan regression test memeriksa pergantian tanggal UTC ke WITA. Audit juga mencatat beberapa surface Admin lama (terutama `x-admin.filter-panel` dan sebagian form/detail) masih memakai neutral `white/zinc` alih-alih token surface SanMal; normalisasi visual perlu dilakukan bertahap per halaman setelah QA, bukan lewat penggantian massal. Template `layouts/app/header.blade.php` serta varian auth `card`/`split` masih ada di source, sedangkan root layout aktif memakai `app.sidebar` dan auth wrapper aktif memakai `auth.simple`; verifikasi referensi sebelum menghapus template yang tampak tidak aktif. Ini bukan bukti browser rendering atau seluruh state runtime telah diuji. Pola native table, review list, Livewire SFC, dan Flux dipertahankan ketika semantiknya tepat. |
| Responsive audit menyeluruh | **Source-level pass completed; browser/device QA pending** | Pemeriksaan source menemukan deklarasi responsive pada halaman in-scope, tetapi tidak dapat membuktikan overflow, clipping, scroll, atau perilaku aktual pada viewport. Lanjutkan QA berdasarkan [Visual QA Log](VISUAL-QA-LOG.md). |
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
- Regression test memastikan form tindakan muncul sebelum pemilih tanggal dan lulus dalam full CI run 1297. Urutan kamera, izin GPS, shutter, preview, dan upload tetap membutuhkan browser/device QA.
- **Status: Implemented; automated CI verified, browser/device QA pending.** Full test suite dan pemeriksaan formatting Pint lulus pada run 1297. Pemeriksaan PHPStan terarah yang dikonfigurasi untuk tiga controller juga lulus; tetapi repository-wide PHPStan masih melaporkan 278 baseline errors dan langkah tersebut memakai `continue-on-error`, jadi static analysis seluruh repository belum dapat dinyatakan bersih. Regression coverage backend untuk pemblokiran Clock Out dan konfirmasi lembur ikut lulus. Browser/device QA untuk izin GPS/kamera, shutter, preview, upload, dan error handling masih diperlukan.

## Cakupan fondasi reusable yang sudah tersedia

- **Layout:** `x-ui.page`, `x-ui.page-header`, `x-ui.section`.
- **Action:** `x-ui.button`.
- **Surface:** `x-ui.card`.
- **Form:** `x-ui.field`, `x-ui.input`, `x-ui.select`, `x-ui.textarea`, `x-ui.checkbox`.
- **Feedback:** `x-ui.flash-message`, `x-ui.empty-state`.
- **Status dan metadata:** `x-ui.badge`, `x-ui.metadata-item`.
- **Identity:** `x-ui.avatar` untuk foto profil dan fallback inisial berbentuk lingkaran; sudah diterapkan pada header Dashboard dan avatar menu profil desktop/mobile yang teridentifikasi. Halaman Profil berada di `resources/views/pages/employee/⚡employee-profile.blade.php` dan masih memakai `flux:avatar` untuk mendukung Livewire temporary photo preview; migrasi ke `x-ui.avatar` belum diperlukan tanpa bukti bahwa kontrak preview tetap terjaga.
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

Tahap standardisasi reusable component ditutup sebagai fondasi implementasi. Kontrak data Employee Dashboard telah diterapkan pada resolver, persistence, dan agregasi yang tercakup oleh full CI run 1297; browser/device dan matriks kalender/timezone yang lebih luas masih terbuka. Jangan mengulang audit komponen generik kecuali ditemukan gap konkret dari penggunaan nyata.

Employee Dashboard V1 layout, controller role branch, aggregation service, dan view tersedia di source. Rincian jenis pengajuan tertunda dan visibilitas ikon notifikasi mobile telah diperbaiki. Feature test dan unit test untuk status, sesi ambigu/duplikat, batas bulan/timezone, transisi approval lembur, daily-status parity Employee/Admin, dan beberapa interval lembur telah lulus full CI run 1297 pada MariaDB dan SQLite.

Resolver timezone menggunakan dependency offline `aldesrahim/laravel-timezone-indonesia` v1.1.0 melalui `App\Services\AttendanceTimezoneResolver`. Clock In Office menyimpan `Asia/Makassar`; Clock In Onsite memakai IANA timezone hasil koordinat GPS yang valid; bila GPS/resolver tidak dapat menentukan zona, sistem memakai fallback WITA dengan `timezone_source=fallback` dan status harian `needs_review`. Clock Out dan konfirmasi mulai lembur mewarisi timezone dari anchor Clock In. Migration `timezone_source`, test resolver, dan snapshot sesi telah lulus full CI run 1297 pada MariaDB dan SQLite.

Gap yang masih terbuka: perluasan matriks kalender/timezone dan browser QA responsif. Kalender laporan Admin ditetapkan ke WITA dan diterapkan pada dashboard, rekap harian, monitoring kehadiran, serta ekspor CSV; cakupan automated test yang ada telah lulus full CI run 1297. Lanjutkan dengan browser QA lintas viewport dan perangkat. Admin Dashboard saat ini hanya menyajikan ringkasan status harian, sehingga parity durasi kerja/lembur belum dapat dibandingkan tanpa memperluas scope tampilan Admin. Pertahankan route, authorization, validation, Livewire, query/filter, export, camera/GPS, attendance timestamp, dan domain workflow. Catat test dan browser QA aktual di [Visual QA Log](VISUAL-QA-LOG.md).


## Navigation shell — implementasi awal

- Layout utama memakai `<flux:main inset>` dengan `lg:ms-0`, tetap menjadi sibling langsung sidebar/header. Sidebar inline menyatu dengan body surface tanpa divider; main inset memakai page surface. Body serta sidebar memakai tinggi viewport `dvh`.
- Employee memakai header atas dan drawer sidebar pada layar kecil; fixed bottom navigation dihapus. Daftar Employee tidak memakai grouping dan memuat Beranda, Absensi, Riwayat, Permohonan, serta Lembur.
- Link aktivitas pribadi Manager memakai label yang sama dengan Employee. Label item Admin dipersingkat sementara grouping Operasional, Master Data, dan Permohonan tetap dipertahankan.
- Komponen baru `x-ui.sidebar-section` menjaga tinggi baris heading. Saat sidebar terbuka, heading tampil; saat collapsed, heading diganti separator Flux subtle sehingga item ikon tidak bergeser naik. Employee tidak menggunakan komponen ini.
- Ikon item sidebar distandardisasi menjadi 20px persegi dengan ukuran tetap pada kondisi expanded/collapsed.
- Inset Main menjadi pemilik spacing shell. Padding root `x-ui.page` yang menggandakan gutter telah dihapus dari Rekap Harian, Persetujuan Permohonan, Riwayat Absensi, Pusat Permohonan, form Koreksi/Permohonan, dan Notifikasi. `x-ui.page` tetap mengatur lebar konten per halaman; halaman Absensi Pegawai diperlebar dari `max-w-xl` ke lebar default `max-w-7xl` agar tidak tampak sempit di desktop.
- Regression test menghitung 15 item navigasi Admin, lima separator grouping, tidak adanya separator pada Employee, dan lebar halaman Absensi; seluruhnya lulus full CI run 1297.
- **Status: Implemented; automated CI verified, browser QA pending.** Browser QA pada 360–430px, 768–1024px, dan 1280–1440px masih diperlukan. Periksa khusus outer margin, permukaan konten utama, lebar konten halaman Absensi, dan tinggi/posisi separator pada sidebar expanded/collapsed.

## Employee Attendance — weekly and monthly calendar

- Weekly calendar uses seven Monday-first date columns, selected-date styling, a separate today outline, and previous/next week plus Hari Ini controls.
- Monthly date picker uses a centered six-row grid, month navigation, adjacent-month dates, close control, Escape, and backdrop dismissal. Selecting a date synchronizes the existing `selectedDate` state and closes the picker.
- Daily indicators are sourced in batches from `DailyAttendanceStatusService::forEmployeesDateRange()`: `present` uses green `#54B66F`, `needs_review` uses warning amber, and `no_record` uses a subtle gray dot. There is no visible legend.
- Other domain states (incomplete, leave, sick, permission, explicit absence) are not silently relabelled as one of the three selected indicators. No record is not interpreted as absent.
- Camera, GPS, evidence upload, attendance submission, overtime, and history workflows are unchanged.
- Feature coverage added for the 42-cell monthly grid, month navigation, date selection, and selected-date synchronization.
- Calendar presentation now lives in `x-attendance.calendar`; weekly/monthly date-grid data remains owned by the parent Livewire SFC, while `AttendanceCalendarService` centralizes batch status classification and `x-attendance.calendar-indicator` centralizes dot color/accessible status labels. `x-attendance.time-summary` owns the three time-summary cards. This keeps one page-level state owner without introducing nested Livewire components.
- **Status: Source and regression test implemented.** On PR CI run [1393](https://github.com/leonsmith128/pt-sanmal/actions/runs/41766870500), Pint and changed-application-code PHPStan passed, and the new monthly-calendar feature test passed on MariaDB. The full MariaDB test step still failed on two unrelated existing assertions: the photo-preview test expects `previewOpen`, and the Employee Dashboard test expects the obsolete absence of `divide-sanmal-border`. SQLite was skipped because the MariaDB test step failed. Repository-wide PHPStan retains its documented baseline debt and is configured as non-blocking. Browser QA remains pending at 360–390px, 414–430px, 768px, 1024px, and 1280–1440px in light/dark themes. See [Employee Attendance Calendar V1](../EMPLOYEE-ATTENDANCE-CALENDAR-V1.md).

## Employee Attendance — layout and photo preview

- Main attendance action remains the final element inside the attendance form, after the camera component and validation messages. It is not sticky or fixed; the page retains normal document scrolling when the photo preview or lower history content increases page height.
- `x-attendance.photo-preview` owns the captured-selfie thumbnail and full-size centered modal. The image keeps its intrinsic aspect ratio (`object-contain`) and is constrained to the viewport; the modal closes through its close control, clicking the backdrop outside the preview, or Escape.
- `x-attendance.camera` remains dedicated to camera/GPS capture and watermark behavior; its source was restored unchanged after preview responsibilities were extracted.
- The full-size preview modal now includes a download link for the same temporary image shown in the preview. Its `arrow-down-tray` icon is a valid Heroicons v2 name. Status cards use a shared minimum height, flexible content area, and the approved borderless shadow surface to reduce layout movement between dates with and without records.
- Calendar and time-summary markup were extracted into Blade Components; the page remains a single Livewire SFC orchestrator. Business workflows remain in the existing Actions, and calendar status classification is handled by `AttendanceCalendarService`.
- Retake action and the existing camera/GPS/watermark/Livewire submission flow remain unchanged.
- Attendance summary and form surfaces now use the approved SanMal light/dark card and border tokens.
- Regression coverage was updated for the standalone preview component, its temporary-photo download action, and full-width bottom action placement.
- **Status: Implemented; CI verification pending for the latest PR head. Browser/device QA remains pending**, especially 360–390px and 414–430px widths, centered modal placement and backdrop dismissal, and the full camera → preview → retake → confirm sequence.
