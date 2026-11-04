# Employee Attendance History — V1

> Status: scope implementasi V1 selesai pada 4 November 2026. Dokumen ini menjelaskan kontrak halaman berdasarkan source pada branch UI/UX V1. Selesai di tingkat implementasi dan regression test tidak berarti browser/device QA telah lulus.

## 1. Tujuan dan batas halaman

Halaman **Riwayat Absensi** memberi pegawai cara meninjau status kehadiran dan bukti absensi berdasarkan tanggal sesi. Halaman ini employee-scoped: pengguna hanya melihat data pegawai yang terhubung dengan akun aktifnya.

Route utama:
- `attendance-history.index` — menampilkan riwayat.
- `attendance-history.export` — mengunduh rekap CSV.
- Tombol **Absen** mengarahkan kembali ke route `attendance`.

Implementasi menggunakan Controller + Blade. Alpine.js mengelola state UI lokal untuk panel filter dan dialog detail; tidak ada nested Livewire component atau reusable component baru khusus halaman ini.

## 2. Susunan halaman

1. **Page header** — judul/deskripsi dan aksi Export, Absen, Filter.
2. **Ringkasan kehadiran** — tiga indikator: Hari hadir, Absensi lengkap, dan Perlu ditinjau.
3. **Periode aktif dan urutan** — rentang tanggal yang diterapkan dan label terbaru/terlama dahulu.
4. **Panel filter collapsible** — tersembunyi secara default.
5. **Riwayat per tanggal** — kelompok tanggal, status, alasan/koreksi, dan event.
6. **Pagination** — maksimal 10 kelompok tanggal per halaman.
7. **Dialog detail event** — detail waktu, lokasi, konteks pekerjaan, catatan, dan bukti foto.

## 3. Ringkasan dan klasifikasi

Ringkasan mengikuti hasil `DailyAttendanceStatusService` pada rentang tanggal terpilih.

| Indikator | Kontrak |
|---|---|
| Hari hadir | Menghitung tanggal berstatus `present`, termasuk koreksi administratif yang menetapkan status hadir. |
| Absensi lengkap | Tanggal berstatus hadir dengan tepat satu Clock In dan satu Clock Out, serta waktu efektif Clock Out lebih akhir dari Clock In. |
| Perlu ditinjau | Menghitung tanggal berstatus `needs_review`. |

Tanggal berstatus `no_record` tidak ditampilkan sebagai kelompok riwayat. Status-only group tetap dapat tampil walaupun tidak memiliki event, misalnya status yang berasal dari izin/cuti atau koreksi administratif.

## 4. Filter

Filter yang tersedia:
- **Dari tanggal**.
- **Sampai tanggal**.
- **Urutan tanggal**: terbaru dahulu (`desc`, default) atau terlama dahulu (`asc`).

Perilaku:
- Panel filter tertutup saat halaman pertama kali dirender, pada desktop maupun mobile.
- Tombol Filter membuka/menutup panel tanpa menghapus draft input yang sedang diketik.
- Tombol Terapkan mengirim GET request; halaman dimuat ulang dengan filter tervalidasi dan panel kembali tertutup.
- Tombol Reset kembali ke route index tanpa query filter sehingga default periode dan urutan diterapkan kembali.
- Periode aktif dan arah urutan ditampilkan di luar panel agar tetap terlihat saat panel tertutup.
- Badge Filter menghitung filter yang benar-benar dikirim melalui query: rentang tanggal dihitung sebagai satu filter jika `from` atau `to` ada; arah ascending dihitung satu filter karena descending adalah default.
- Validasi memastikan format tanggal `Y-m-d` dan tanggal akhir tidak lebih awal dari tanggal awal.

Default periode tanpa parameter tanggal adalah awal bulan berjalan sampai hari ini dalam kalender WITA (`Asia/Makassar`). Jika batas tanggal kosong pada request yang tervalidasi, controller melengkapi rentang dengan tanggal record paling awal dan/atau hari ini.

## 5. Kelompok tanggal, event, dan pagination

- Data dikelompokkan berdasarkan tanggal sesi dari hasil layanan status harian, bukan sekadar tanggal mentah event.
- Kelompok diurutkan mengikuti filter `direction`.
- Maksimal 10 tanggal ditampilkan per halaman.
- Informasi kelompok dapat memuat status, jumlah event, alasan status, dan catatan koreksi administratif.
- Event ditampilkan secara kronologis dalam konteks kelompok tanggal.
- Pagination mempertahankan query filter yang sedang diterapkan.

## 6. Dialog detail event

Dialog menyediakan informasi berikut sesuai ketersediaan data:
- Waktu pencatatan asli dan waktu efektif.
- Zona waktu sesi dan jenis kerja.
- Lokasi kerja, status verifikasi GPS, akurasi, koordinat, alamat, dan tautan peta jika tersedia.
- Client dan proyek.
- Catatan event.
- Foto bukti, status cadangan di Drive, dan tautan unduh foto privat jika tersedia.
- Penanda ketika identitas pekerjaan berasal dari migration backfill, atau ketika zona waktu memakai fallback.

Dialog dapat ditutup melalui tombol close, klik backdrop, atau tombol Escape. Fokus dipindahkan ke tombol close ketika dialog dibuka dan dikembalikan ke pemicu ketika dialog ditutup. Detail yang tidak tersedia ditampilkan sebagai placeholder yang sesuai.

## 7. Export CSV

Tombol Export membawa parameter `from` dan `to` dari periode yang sedang diterapkan pada halaman. Nilai draft yang belum diterapkan tidak digunakan. Endpoint menghasilkan CSV untuk pegawai yang sedang login dan menggunakan periode yang dikirim; ketika parameter tidak diberikan langsung ke endpoint, perilaku endpoint mempertahankan cakupan dari tanggal paling awal yang tersedia sampai hari ini.

## 8. Arsitektur dan file utama

| Tanggung jawab | Lokasi |
|---|---|
| Otorisasi employee scope, validasi filter, ringkasan, grouping, pagination, dan export response | `app/Http/Controllers/AttendanceHistoryController.php` |
| Markup halaman, filter disclosure, date groups, pagination, dan event dialog | `resources/views/pages/employee/attendance-history/index.blade.php` |
| Klasifikasi status harian | `app/Services/DailyAttendanceStatusService.php` |
| Pembuatan stream CSV | `app/Services/DailyAttendanceExportService.php` |
| Regression coverage halaman | `tests/Feature/AttendancePagesTest.php` |
| Regression coverage filter tanggal | `tests/Feature/AttendanceDateFilterTest.php` |

Gunakan Controller + Blade untuk halaman ini. Jangan memindahkan filter atau dialog menjadi nested Livewire component tanpa kebutuhan independensi state yang terukur. Alpine hanya bertanggung jawab atas disclosure panel dan lifecycle dialog sisi klien.

## 9. Validasi otomatis dan batas QA

- CI run [#1513](https://github.com/leonsmith128/pt-sanmal/actions/runs/41850885136) pada commit `37e09a7423f5ad7949c008a1f67d8986b8eb9147` berstatus workflow **success**. Run ini mencakup regression test disclosure filter.
- CI run [#1497](https://github.com/leonsmith128/pt-sanmal/actions/runs/41788406320) sebelumnya lulus pada MariaDB 11 dan SQLite; rincian jumlah test/assertion ada di [Implementation Status](progress/IMPLEMENTATION-STATUS.md).
- Automated test tidak membuktikan kualitas visual pada browser/perangkat.
- Browser QA untuk responsive layout, light/dark theme, keyboard/focus, filter disclosure, dialog scrolling, dan interaksi dialog masih harus dicatat terpisah di [Visual QA Log](progress/VISUAL-QA-LOG.md).

## 10. Kriteria selesai V1

Scope implementasi halaman V1 dianggap selesai karena fungsi berikut tersedia dan memiliki regression coverage yang relevan:
- [x] Ringkasan kehadiran tiga indikator.
- [x] Filter rentang tanggal dan urutan.
- [x] Panel filter collapsible, default tertutup, dengan draft tetap terjaga saat panel ditutup/dibuka.
- [x] Periode aktif dan urutan selalu terlihat.
- [x] Kelompok status per tanggal, termasuk status-only group.
- [x] Pagination 10 tanggal per halaman.
- [x] Dialog detail event dan bukti.
- [x] Export CSV mengikuti periode yang diterapkan.
- [x] Regression test filter dan halaman pada CI.

Kriteria implementasi di atas tidak menutup pekerjaan QA lintas viewport/theme dan accessibility review yang masih menjadi bagian dari acceptance UI/UX V1 keseluruhan.
