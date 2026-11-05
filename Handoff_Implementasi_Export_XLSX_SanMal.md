# Handoff Implementasi Ekspor XLSX — PT SanMal

**Status dokumen:** Handoff implementasi  
**Stack acuan:** Laravel 13, PHP 8.5, Livewire 4, Vite Plus  
**Tujuan:** Menjadi kontrak kerja bagi developer/agent yang mengimplementasikan ekspor XLSX.  
**Batasan:** Dokumen ini berisi kebutuhan dan rencana implementasi. Kondisi kode serta nama class/file terkini wajib diverifikasi langsung di branch kerja sebelum perubahan dilakukan.

---

## 1. Tujuan dan ruang lingkup

Mengganti/meningkatkan ekspor CSV yang ada menjadi ekspor Excel `.xlsx` untuk empat halaman:

1. Riwayat Absensi Pegawai — `/riwayat-absensi`
2. Permohonan Pegawai — `/permohonan`
3. Kehadiran Admin — `/admin/kehadiran`
4. Rekap Harian Admin — `/admin/rekap-harian`

Setiap ekspor menghasilkan **satu workbook dengan tepat dua worksheet**:

- `Ringkasan`
- `Detail`

Bukan dua file terpisah dan bukan satu worksheet untuk setiap pegawai/hari.

## 2. Kontrak workbook

### 2.1 Struktur umum

- Format file: `.xlsx`.
- Tepat dua worksheet: `Ringkasan` dan `Detail`.
- Header tegas dan mudah dibaca, dengan teks tebal dan latar pembeda.
- Aktifkan AutoFilter pada header tabel.
- Freeze baris header.
- Lebar kolom disesuaikan dengan isi secara wajar; hindari ukuran berlebihan.
- Format tanggal dan waktu konsisten. Waktu memakai `HH:mm:ss`.
- Zona waktu laporan: `Asia/Makassar` (WITA).
- Durasi ditampilkan sebagai durasi yang dapat dipahami (jam dan menit), bukan angka desimal ambigu.
- Data kosong ditampilkan secara konsisten; jangan mengubah data yang tidak diketahui menjadi nol.
- Lindungi sel dari formula injection. Teks pengguna yang diawali karakter formula seperti `=`, `+`, `-`, atau `@` tidak boleh dieksekusi sebagai formula.
- Jangan memasukkan password, token, URL lampiran privat, atau URL foto publik.
- Ekspor harus tetap berhasil jika foto, GPS, atau alamat snapshot tidak tersedia.

### 2.2 Granularitas baris

| Jenis laporan | Ringkasan | Detail |
|---|---|---|
| Riwayat Absensi Pegawai | Satu baris per tanggal sesi pegawai | Satu baris per tanggal sesi pegawai, dengan detail event/sesi yang diringkas sesuai kontrak |
| Permohonan Pegawai | Satu baris per permohonan | Satu baris per permohonan |
| Kehadiran Admin | Satu baris per pegawai per tanggal | Satu baris per pegawai per tanggal |
| Rekap Harian Admin | Satu baris per pegawai per tanggal | Satu baris per pegawai per tanggal |

Catatan: Detail event pada laporan absensi dikelompokkan ke dalam satu baris harian; jangan membuat satu baris spreadsheet untuk setiap event. Kolom `Detail Sesi` dan `Event Lain` dapat memuat representasi ringkas yang konsisten dan dapat dibaca.

## 3. Kontrak data absensi

### 3.1 Penentuan tanggal sesi

- `Clock In` menjadi anchor tanggal sesi.
- Tanggal laporan ditentukan dari Clock In dalam kalender WITA perusahaan.
- Sesi yang melewati tengah malam tetap menjadi bagian tanggal Clock In.
- Clock Out tidak membuat tanggal kehadiran baru.
- Durasi dihitung dari timestamp aktual, bukan pengurangan tanggal kalender.

### 3.2 Data master dan snapshot

Bedakan informasi master saat ini dari fakta yang direkam ketika absensi dilakukan.

Kolom administrasi yang relevan harus dipisahkan, bukan digabung:

- Client
- Project Code
- Project Name
- Location

Data lokasi aktual yang direkam pada event tetap dipisahkan:

- Alamat Snapshot
- Latitude
- Longitude
- Accuracy (meter)
- Status GPS
- Status alamat
- Status foto

Nama Client/Project/Location dari relasi master bukan bukti GPS. Koordinat dan alamat snapshot merupakan konteks lokasi aktual yang direkam. Untuk data lama tanpa snapshot, referensi master yang masih tersedia boleh digunakan sebagai konteks, tetapi jangan diberi label seolah-olah snapshot historis.

### 3.3 Foto dan GPS

- Embed hanya thumbnail foto lokal yang memang dapat diakses dari storage privat aplikasi.
- Jangan melakukan retrieval foto dari Google Drive pada versi ini.
- Jika file foto lokal tidak tersedia, tulis status yang jelas dan lanjutkan ekspor.
- Sertakan koordinat GPS mentah dan hyperlink peta yang dibentuk dari koordinat valid.
- Sertakan accuracy dalam meter.
- Alamat yang diekspor harus berasal dari snapshot event, bukan reverse-geocoding baru pada saat ekspor.
- Jika koordinat/alamat/foto tidak ada, catat status ketiadaan; jangan menggagalkan seluruh workbook.

### 3.4 Total Lembur Final

Untuk `Riwayat Absensi Pegawai` dan `Kehadiran Admin`, `Total Lembur Final` wajib ada pada kedua worksheet.

Aturan perhitungan:
- Hanya gunakan overtime yang sudah difinalisasi.
- Gunakan pasangan `final_start_at` dan `final_end_at` yang valid.
- Agregasikan menurut `session_date`.
- Jangan menyimpulkan lembur dari durasi shift atau selisih Clock In–Clock Out.
- Bedakan tidak ada catatan lembur final dengan total lembur yang benar-benar nol.
- Jangan memasukkan overtime yang belum final.

## 4. Filter dan cakupan ekspor

- Ekspor mengikuti **filter yang sudah diterapkan**, bukan nilai draft yang belum di-apply pada form.
- Filter non-tanggal yang sedang aktif harus tetap dipertahankan.
- Minggu berjalan menggunakan Senin–Minggu.
- Interpretasi tanggal dan batas hari menggunakan WITA.
- Jika tidak ada filter tanggal, cakupan harus ditentukan secara eksplisit sesuai perilaku halaman yang sudah ada. Jangan diam-diam mengekspor seluruh histori tanpa batas.
- Untuk ekspor Admin Rekap Harian, jangan membuat baris untuk tanggal sebelum `joined_at` pegawai.

Sebelum implementasi, verifikasi bagaimana setiap halaman menyimpan/menerapkan filter (query string, applied state, request, atau mekanisme lain). Jangan menebak dari tampilan form.

## 5. Aturan khusus Rekap Harian

- Jangan menghasilkan baris pegawai sebelum tanggal `joined_at`.
- Pertahankan filter dan aturan status yang digunakan halaman rekap saat ini.
- Jangan membuat data absensi sintetis untuk tanggal sebelum pegawai bergabung.
- Audit khusus perilaku ekspor lama: pada audit sebelumnya, `AdminDailyAttendanceController` disebut berpotensi menghasilkan baris “Belum ada data” sebelum `joined_at`. Verifikasi apakah masalah ini masih ada di branch terkini sebelum memperbaikinya.

## 6. Kontrak nama file

Gunakan pola berikut:

### Admin

`sanmal_{jenis-laporan}_{periode}_{tanggal-ekspor}.xlsx`

### Pegawai

`sanmal_{jenis-laporan}_{nama-pegawai}_{nomor-pegawai}_{periode}_{tanggal-ekspor}.xlsx`

Ketentuan:
- Tanggal ekspor menggunakan WITA.
- Sanitasi nama pegawai dan bagian nama file agar aman untuk filesystem.
- Jika nomor pegawai kosong, gunakan internal ID sebagai fallback.
- Periode harus merepresentasikan filter yang benar-benar diterapkan.
- Hindari karakter ilegal dan nama file yang terlalu panjang.

## 7. Pilihan dependensi

### Rekomendasi

Gunakan **`maatwebsite/excel` 4.x** sebagai integrasi Laravel dan PhpSpreadsheet sebagai engine XLSX di bawahnya.

Alasan pemilihan:
- Dokumentasi Laravel Excel 4.x menyatakan dukungan Laravel `^12.0 || ^13.0` dan PHP `^8.3`.
- Rilis 4.0.6 pada metadata paket yang diperiksa mendukung Laravel 13 dan mensyaratkan PhpSpreadsheet `^5.9`.
- Laravel Excel menyediakan abstraksi ekspor Laravel, termasuk multi-sheet, mapping, styling, dan concerns; lebih sesuai daripada membangun integrasi file dan response sendiri.
- Jangan memasang `phpoffice/phpspreadsheet` secara terpisah jika Laravel Excel sudah menariknya sebagai dependency, kecuali ada kebutuhan eksplisit untuk menggunakan API engine secara langsung.

Dokumentasi yang menjadi acuan:
- Laravel Excel 4.x installation: https://www.docs.laravel-excel.com/4.x/getting-started/installation.html
- Laravel Excel 4.x upgrade guide: https://docs.laravel-excel.com/4.x/getting-started/upgrade.html
- PhpSpreadsheet: https://phpspreadsheet.readthedocs.io/

### Pemeriksaan sebelum instalasi

Jalankan dari root repository:

```bash
php -v
php artisan --version
composer show --locked maatwebsite/excel
composer show --locked phpoffice/phpspreadsheet
composer check-platform-reqs
php -m
```

Jika `composer show --locked` menyatakan paket belum terpasang, itu normal. Periksa ekstensi yang diperlukan oleh Laravel Excel/PhpSpreadsheet, terutama ZIP, GD, XML, iconv, SimpleXML, XMLReader, dan Zlib. Composer harus menjadi pemeriksa akhir untuk platform requirements.

### Instalasi yang direkomendasikan

Setelah working tree aman dan pemeriksaan ekstensi selesai:

```bash
composer require maatwebsite/excel:^4.0
```

Jangan menambahkan dependency PhpSpreadsheet langsung secara terpisah pada tahap ini. Jangan menggunakan `--ignore-platform-reqs`.

Setelah instalasi:

```bash
composer validate
composer check-platform-reqs
composer audit
composer show --locked maatwebsite/excel
composer show --locked phpoffice/phpspreadsheet
```

Jangan mengasumsikan versi yang terpasang hanya dari constraint `^4.0`; catat versi aktual yang terkunci pada `composer.lock`.

Publikasi konfigurasi `config/excel.php` tidak otomatis diperlukan. Lakukan hanya jika implementasi membutuhkan perubahan konfigurasi yang tidak dapat ditangani dengan default package.

## 8. Arsitektur implementasi yang disarankan

Ini adalah arah arsitektur, bukan instruksi untuk membuat class dengan nama persis tanpa audit kode.

- Pertahankan controller/route sebagai pintu masuk dan pemeriksa otorisasi.
- Pertahankan query/filter domain yang sudah ada sejauh benar dan dapat digunakan kembali.
- Pisahkan pembentukan dataset laporan dari presentasi workbook.
- Buat export class per jenis laporan atau kelompok domain yang jelas.
- Gunakan satu workbook export yang menggabungkan dua sheet `Ringkasan` dan `Detail`.
- Gunakan shared concern/helper untuk style header, format tanggal/waktu, sanitasi teks, formula-injection protection, dan status media.
- Hindari query N+1: eager-load relasi yang memang dibutuhkan.
- Hindari memuat semua histori dalam memori tanpa batas. Evaluasi query chunking/lazy collection dan batas ukuran ekspor.
- Jangan memindahkan business logic laporan ke Blade.
- Jangan membuat Service/Action abstrak hanya demi membungkus satu pemanggilan library; abstraksi harus punya tanggung jawab yang jelas.
- Otorisasi harus tetap ditegakkan di server. Jangan percaya ID pegawai atau filter yang dikirim client tanpa validasi akses.
- Ekspor pegawai hanya untuk data miliknya; admin mengikuti policy/role yang sudah berlaku pada halaman terkait.

## 9. Tahapan kerja developer

### Tahap 0 — Audit repository terkini

Sebelum coding:
1. Periksa `composer.json` dan `composer.lock`; pastikan Laravel Excel belum terpasang.
2. Periksa route, controller, policy/middleware, dan halaman untuk keempat fitur.
3. Periksa implementasi ekspor CSV yang sekarang dan service/query yang dipakai.
4. Verifikasi applied-filter state agar ekspor sama dengan data yang sedang ditampilkan.
5. Periksa model/relasi overtime final, session date, joined_at, snapshot GPS/alamat/foto, Client/Project/Location.
6. Catat file/class aktual dan gap terhadap kontrak ini.
7. Jangan menganggap hasil audit lama masih identik dengan branch sekarang.

### Tahap 1 — Instalasi dependency

1. Pastikan working tree aman.
2. Periksa PHP extensions/platform.
3. Instal `maatwebsite/excel:^4.0`.
4. Periksa `composer.lock`, platform requirements, dan audit.
5. Catat versi aktual paket.

### Tahap 2 — Fondasi ekspor

1. Buat pola workbook dua sheet.
2. Implementasikan style bersama.
3. Terapkan format WITA dan format durasi.
4. Terapkan formula-injection protection.
5. Terapkan sanitasi nama file.
6. Buat test kecil untuk memastikan file `.xlsx` valid dan dua sheet terbentuk.

### Tahap 3 — Implementasi empat laporan

Implementasikan satu per satu, pertahankan query domain dan otorisasi yang ada. Prioritaskan laporan yang paling sederhana untuk memvalidasi fondasi, lalu laporan absensi yang memiliki detail sesi, GPS, foto, dan lembur final.

### Tahap 4 — Pengujian

Minimal uji:
- Workbook memiliki tepat dua sheet dengan nama yang benar.
- Header, AutoFilter, freeze pane, format tanggal/waktu, dan format durasi.
- Filter yang diterapkan sama dengan hasil di halaman.
- Filter draft yang belum di-apply tidak ikut.
- Filter non-tanggal tetap bekerja.
- Rentang minggu Senin–Minggu dalam WITA.
- Sesi lintas tengah malam tetap berada pada tanggal Clock In.
- Overtime hanya menghitung record final yang valid.
- Tidak ada overtime final dibedakan dari total nol.
- Pegawai tidak memiliki baris sebelum `joined_at`.
- Foto/GPS/alamat yang hilang tidak menggagalkan ekspor.
- Foto lokal di-embed sesuai batas yang disepakati; tidak ada akses Google Drive.
- Koordinat dan hyperlink peta benar.
- Formula injection tidak dapat dieksekusi.
- Nama file aman dan sesuai pola.
- Otorisasi pegawai/admin tidak dapat dilewati.
- Dataset besar tidak menyebabkan penggunaan memori berlebihan.

### Tahap 5 — Penutupan

Jalankan:

```bash
composer validate
composer check-platform-reqs
composer audit
php artisan test
npm run build
```

Jika ada test/lint khusus project, jalankan juga. Catat hasil, perubahan file, versi dependency, serta keterbatasan yang belum selesai.

## 10. Kriteria penerimaan

Implementasi dinyatakan siap jika:
- Keempat halaman menghasilkan file XLSX yang dapat dibuka.
- Setiap file memiliki tepat dua sheet: `Ringkasan` dan `Detail`.
- Isi mengikuti filter yang diterapkan dan aturan domain yang berlaku.
- Nilai WITA, tanggal sesi, durasi, dan lembur akurat.
- Data master dibedakan dari snapshot event.
- Kehilangan media/GPS tidak merusak keseluruhan ekspor.
- Formula injection dan kebocoran data sensitif ditangani.
- Otorisasi sesuai kontrak role yang sudah berlaku.
- Test dan build lulus, atau kegagalan yang tersisa didokumentasikan secara spesifik.

## 11. Hal yang wajib dilaporkan saat handoff selesai

Developer harus mengembalikan:
1. Daftar file yang dibuat/diubah.
2. Versi `maatwebsite/excel` dan `phpoffice/phpspreadsheet` yang terkunci.
3. Ringkasan arsitektur export dan alur query.
4. Matriks hasil test per halaman dan per skenario penting.
5. Perbedaan implementasi dari kontrak beserta alasannya.
6. Catatan performa/batas ukuran ekspor.
7. Status `composer audit`, `composer check-platform-reqs`, test, dan build.
8. Daftar pekerjaan tersisa (jika ada).

---

## Catatan status awal

Audit sebelumnya menyebut ekspor pada empat halaman masih berupa CSV dan terdapat `DailyAttendanceExportService` dengan filter, pengelompokan, waktu asli/efektif, koreksi, dan snapshot project/location. Audit tersebut juga mencatat potensi masalah pada ekspor `AdminDailyAttendanceController` untuk tanggal sebelum `joined_at`.

**Semua temuan ini harus diverifikasi ulang pada branch terkini.** Dokumen ini adalah kontrak target; bukan bukti bahwa kode saat ini masih sama dengan audit terdahulu.
