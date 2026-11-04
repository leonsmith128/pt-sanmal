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
| Dashboard Admin | Monitoring seluruh pegawai; card statistik dan tindakan memakai ikon/label/nilai tersusun vertikal dan rata tengah; hierarki aktivitas, density, wrapping tablet/mobile |
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
| Attendance history | Ringkasan tiga indikator, filter, empty state, dan date-group menggunakan `x-ui.card` tanpa border dengan `shadow-sm`; filter rentang tanggal dan urutan; date group 10 tanggal/halaman; status-only group; detail event dalam dialog; timestamp asli/efektif, GPS, alamat, foto privat, pagination, dan ekspor |
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


## Source review checkpoint — 4 November 2026

Source review telah mencakup 37 page view (22 Admin, 7 Employee, 5 Authentication, serta Dashboard, Notifications, dan Settings), 33 reusable/domain components, 7 layout templates, dan 2 partials pada branch UI/UX V1. Review ini memeriksa konsistensi komposisi dan penggunaan primitive, bukan menyatakan browser QA selesai. Pertahankan native/Flux table untuk data komparatif atau interaksi Livewire, review list/article untuk antrean dan record, serta Livewire SFC untuk workflow interaktif. Root layout aktif memakai `layouts/app/sidebar`; `layouts/app/header` masih tersimpan sebagai template lama. Auth wrapper aktif memakai `layouts/auth/simple`; varian `card` dan `split` masih tersedia tetapi bukan layout yang dipilih wrapper saat ini. Jangan menghapus template tersebut sebelum pencarian referensi dan kebutuhan produk diverifikasi. Sejumlah filter/form/detail Admin masih memakai surface neutral `white/zinc`, sehingga konsistensi token perlu dituntaskan bertahap pada review halaman terkait. Sisa pekerjaan adalah browser/device verification, accessibility, interaction-state review, dan tindak lanjut inkonsistensi visual konkret yang ditemukan saat QA. Lihat [Implementation Status](progress/IMPLEMENTATION-STATUS.md) dan [Visual QA Log](progress/VISUAL-QA-LOG.md).
