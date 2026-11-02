# Audit P1-D — Inventarisasi Tabel dan Daftar Admin

## Status

> **Catatan pembacaan:** dokumen ini adalah catatan audit baseline. Implementasi lanjutan setelah audit (termasuk standardisasi Admin pada PR #25) dicatat pada `docs/ui/ADMIN-PAGES-AUDIT.md`. Status terkini repository berada di `docs/PROJECT-STATUS.md`.

- Tahap: audit baseline selesai; P1-D1 spacing/density dan P1-D2 expandable card Proyek kemudian diimplementasikan. Standardisasi lanjutan halaman Admin telah di-merge melalui PR #25.
- Repository: `leonsmith128/pt-sanmal`
- PR: #25
- Branch: `feature/ui-standardization-p1-ab`
- Baseline audit: `f05e135c4a42309e9b85e6e31abf38c9efdb1975`
- P1-C: tampilan dan render telah diperiksa langsung oleh pengguna dan dinyatakan aman. CI ditunda ke tahap validasi berikutnya.
- Keputusan tablet portrait: adaptif berdasarkan lebar layar dan kepadatan konten.

## Tujuan audit

Menentukan pola presentasi per halaman berdasarkan struktur data, kepadatan kolom, dan interaksi aktual. Audit tidak mengubah query, state, filter, pagination, sorting, otorisasi, maupun aksi bisnis.

## Inventarisasi dan rekomendasi

| Halaman | Implementasi saat ini | Informasi/aksi utama | Rekomendasi P1-D |
|---|---|---|---|
| Client | Card per Client; Site ditampilkan sebagai daftar di dalam card. Office Internal ditampilkan sebagai list row. | Kode/nama, kontak, jumlah Location dan Project, Site dan alamat, status Location; aksi ubah/detail/tambah Site. | Pertahankan card sebagai pola dasar. Audit dan rapikan responsivitas, hierarki informasi, dan area aksi; jangan membuat ulang sebagai tabel. |
| Proyek | Expandable card pada desktop dan mobile; dua kolom mulai breakpoint md. | Ringkasan: nama/kode, jenis layanan, Client, Site, status proyek, peringatan Site nonaktif, jumlah absensi. Detail: periode dan status Site. Aksi ubah selalu tersedia. | Expandable card di semua ukuran layar; tidak menampilkan rincian absensi individu. |
| Pegawai | Tabel 5 kolom: identitas/email, nomor, jabatan, status akun, aksi ubah. | Nama/email, nomor pegawai, jabatan, status akun, edit. | Hybrid: tabel pada desktop/lebar cukup; card pada mobile. Tablet adaptif. |
| Koreksi Absensi | Daftar link per baris (sudah menyerupai card/list, bukan tabel HTML). | Nama pegawai, tanggal, jenis koreksi, waktu yang diminta, status, navigasi ke detail. | Pertahankan pola list/card dan tingkatkan pemisahan visual serta susunan informasi mobile bila dibutuhkan. Jangan menduplikasi isi detail atau aksi keputusan di daftar. |
| Persetujuan Permohonan | Tabel 6 kolom: pegawai, jenis, periode, waktu pengajuan, status, detail. | Identitas pegawai, jenis permohonan, periode, waktu masuk, status, buka detail. | Hybrid: card pada mobile; tabel pada desktop/lebar cukup. Tablet adaptif. |
| Pengelolaan Lembur | Daftar link berbentuk card/list. | Pegawai, waktu mulai terjadwal, ringkasan alasan, status persetujuan, state proses, buka detail. | Pertahankan card/list; audit wrapping teks panjang, badge, dan target interaksi. Detail dan keputusan tetap pada halaman detail. |
| Audit Log | Tabel 5 kolom: waktu, pelaku, aksi, objek, IP. | Kronologi, aktor, action identifier, objek audit, alamat IP. | Pertahankan tabel dengan horizontal scroll pada layar sempit. Kronologi dan perbandingan antar-record lebih tepat dalam tabel daripada card panjang. |
| Rekap Absensi Harian | Card per pegawai melalui `x-admin.attendance.daily-employee-card`. | Identitas pegawai, status harian, event dan waktu, alasan, flag review, form koreksi status. | Pertahankan card. Audit responsivitas form koreksi dan konten panjang; jangan mengubah alur koreksi. |
| Kehadiran Pegawai | Flux table 7 kolom di dalam container scroll: waktu, pegawai, jenis, lokasi kerja, GPS, foto, koreksi waktu. | Timestamp efektif dan bukti asli, identitas pegawai, event/work type, snapshot Client/Project/Location, GPS/alamat, foto dan backup Drive, koreksi timestamp. Terdapat sorting, pagination, preview foto, ekspor, dan form aksi per record. | Untuk tahap awal pertahankan tabel dengan horizontal/vertical scroll yang sudah ada. Card alternatif berisiko menduplikasi banyak aksi dan informasi bukti. Evaluasi ulang hanya jika pengujian perangkat menunjukkan tabel tidak layak digunakan. |

## Temuan teknis penting

### 1. Client sudah menggunakan card

File: `resources/views/pages/admin/clients/index.blade.php`

- Setiap Client dirender sebagai `article` dengan identitas dan ringkasan jumlah Location/Project.
- Daftar Site, status aktif, alamat, dan aksi ubah berada di dalam card.
- Office Internal memakai list row terpisah.
- Pagination Client tersedia.
- Keputusan: Client bukan kandidat migrasi tabel-ke-card; jadikan implementasi saat ini sebagai baseline untuk evaluasi kualitas card.

### 2. Proyek dan Pegawai adalah kandidat utama

File:
- `resources/views/pages/admin/projects/index.blade.php`
- `resources/views/pages/admin/employees/index.blade.php`

Pada baseline audit keduanya memakai tabel HTML di dalam `overflow-x-auto` dan pagination di luar tabel. Proyek kini sudah menggunakan expandable card pada desktop dan mobile. Pegawai tetap menjadi kandidat implementasi berikutnya. Data card Proyek memakai koleksi `$projects` yang sama; query dan state tidak diduplikasi.

### 3. Beberapa daftar sudah berupa card/list

File:
- `resources/views/pages/admin/attendance-corrections/index.blade.php`
- `resources/views/pages/admin/overtime-requests/index.blade.php`

Jangan mengubah hanya demi menyamakan markup. Fokus audit berikutnya adalah keterbacaan pada layar kecil, panjang teks, badge, dan aksesibilitas area klik.

### 4. Halaman dengan data kronologis atau tindakan padat

File:
- `resources/views/pages/admin/audit-logs/index.blade.php`
- `resources/views/pages/admin/⚡kehadiran.blade.php`

Audit Log bersifat read-only dan berorientasi kronologi. Kehadiran memiliki tujuh kolom serta kontrol sorting, pagination, pratinjau/unduh foto, pengelolaan backup Drive, tautan peta, dan koreksi timestamp. Keduanya sebaiknya tidak dipaksakan menjadi card dalam tahap awal.

### 5. Rekap Harian telah memakai komponen card khusus

File:
- `resources/views/pages/admin/daily-attendance.blade.php`
- `resources/views/components/admin/attendance/daily-employee-card.blade.php`

Card ini bukan sekadar ringkasan: terdapat status, event, alasan, indikator review, serta form koreksi status harian. Perubahan visual harus mempertahankan seluruh field, CSRF, validasi, dan aksi POST.

## Prinsip implementasi P1-D

1. Satu sumber data dan satu set state untuk representasi tabel/card.
2. Jangan membuat query berbeda untuk desktop dan mobile.
3. Pertahankan nama parameter filter, query string, pagination, sorting, dan reset state.
4. Pertahankan route, method HTTP, CSRF, policy/authorization, validasi, dan konfirmasi aksi.
5. Semua informasi tabel harus tetap tersedia pada card atau cara akses yang eksplisit.
6. Jangan menyembunyikan aksi penting pada mobile atau membuat area klik ambigu.
7. Tablet portrait adaptif berdasarkan lebar efektif dan kepadatan konten; jangan menerapkan breakpoint seragam tanpa pengujian.
8. Uji konten panjang, nilai kosong, jumlah data nol, pagination, dark mode, dan status yang berbeda.
9. Hindari duplikasi markup yang membuat dua tampilan mudah tidak sinkron. Jika diperlukan dua representasi Blade, gunakan koleksi/state yang sama dan uji keduanya.
10. Jangan melakukan refaktor query/controller bersamaan dengan perubahan presentasi kecuali ditemukan kebutuhan yang terbukti dan disetujui terpisah.

## Urutan implementasi yang direkomendasikan

1. **Proyek** — expandable card di desktop dan mobile; implementasi P1-D2 selesai, menunggu pengujian visual pengguna dan validasi CI.
2. **Pegawai** — implementasi berikutnya; validasi email panjang, nomor pegawai kosong, jabatan kosong, dan status akun.
3. **Client** — review responsivitas dan konsistensi card/list yang sudah ada; perubahan hanya jika audit visual menemukan masalah konkret.
4. **Persetujuan Permohonan** — hybrid card/tabel.
5. **Koreksi Absensi dan Pengelolaan Lembur** — review dan penyempurnaan list/card yang sudah ada.
6. **Audit Log dan Kehadiran** — pertahankan tabel scroll pada tahap awal; evaluasi dengan pengujian perangkat.
7. **Rekap Absensi Harian** — review card khusus dan form koreksinya.

## Pengujian regresi per halaman

- Render desktop, tablet portrait, tablet landscape, dan mobile.
- Data tetap lengkap dan tidak tertutup/terpotong.
- Filter dan pencarian menghasilkan data yang sama sebelum/sesudah perubahan.
- Pagination tetap mempertahankan filter aktif.
- Sorting (khusus Kehadiran) tetap bekerja.
- Semua link dan aksi menuju record yang benar.
- Otorisasi dan validasi server tetap berlaku.
- Empty state dan nilai nullable tetap terbaca.
- Teks panjang, badge berjumlah banyak, dan alamat panjang tidak merusak layout.

## Batas audit

Audit ini berdasarkan Blade view yang dibaca pada baseline branch di atas. Audit belum memeriksa seluruh controller/query, seluruh feature test, atau hasil render pada setiap ukuran viewport. Detail implementasi dan pengujian harus diverifikasi kembali saat masing-masing halaman mulai dikerjakan.


## Catatan implementasi P1-D1 dan P1-D2

- Standar spacing/density: `docs/ui/P1-D1-spacing-density.md`.
- Spesifikasi expandable card Proyek: `docs/ui/P1-D2-project-expandable-card.md`.
- Halaman Proyek kini memakai container `max-w-7xl` dengan `gap-4`, filter panel padding 12px, card padding 12px, dan gap antarkartu 12px.
- Feature test tambahan memverifikasi ringkasan card, detail periode, jumlah absensi nol, dan route edit.
- CI belum dijalankan sesuai keputusan untuk menunda CI; render visual lintas viewport masih perlu diperiksa pengguna.


## Perluasan audit seluruh halaman — P1-D3

Inventarisasi P1-D sebelumnya diperluas ke halaman Admin, Pegawai, Dashboard, Notifikasi, Pengaturan, Auth, dan layout global. Dokumen audit lengkap serta rekomendasi pola per kelompok halaman tersedia di:

- `docs/ui/P1-D3-expanded-page-audit.md`

Pada saat P1-D3 disusun, dokumen tersebut berfungsi sebagai audit dan penentuan pola, sementara penerapan pola ke halaman lain masih direncanakan bertahap. Implementasi Admin yang kemudian selesai dicatat pada `docs/ui/ADMIN-PAGES-AUDIT.md`. Halaman `/absensi` tetap memiliki audit khusus dan memerlukan verifikasi UX perangkat nyata sebelum perubahan lebih lanjut. Pengembangan transisi navigasi halaman tetap merupakan pekerjaan terpisah.
