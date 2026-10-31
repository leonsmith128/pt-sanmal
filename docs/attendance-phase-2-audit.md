# Audit Absensi — Fase 2

Tanggal audit: 31 Oktober 2026  
Baseline: `main` — `4134b08c9f235044dc97f3634bd445c8f22e1871`  
Branch kerja: `feature/phase-2-attendance-workflow`

Dokumen ini mencatat kondisi kode yang diaudit dan gap terhadap prinsip absensi Fase 2 yang sudah disepakati. Audit dilakukan pada source code GitHub; test suite belum dijalankan dalam audit ini.

## 1. Ringkasan temuan

| Area | Kondisi saat ini | Gap utama |
|---|---|---|
| Database absensi | `attendance_records` menyimpan event clock-in, overtime-start, clock-out | Belum ada referensi sesi eksplisit dan belum ada nilai waktu final |
| Database lembur | `overtime_requests` terikat ke clock-in yang sudah ada | Tidak mendukung pengajuan tanggal 3 untuk jadwal tanggal 5 tanpa sesi aktif |
| Jadwal lembur | Hanya memiliki `scheduled_start_at` | Belum menyimpan waktu selesai terjadwal |
| Waktu aktual lembur | Mulai disimpan melalui `start_record_id`; selesai memakai clock-out | Belum ada rentang aktual/final yang dapat dipilih admin |
| Koreksi | `attendance_corrections` menyimpan permintaan koreksi; `daily_status_corrections` mengoreksi status harian | Belum ada finalisasi timestamp clock-in/out dengan pilihan sumber dan alasan |
| Sesi lintas hari | Sesi aktif dibatasi 24 jam | Event clock-out/overtime-start menggunakan tanggal kalender saat event dibuat, sehingga rekap per tanggal dapat memecah satu sesi |
| Otorisasi reviewer | Admin dan superadmin dapat mengakses pengelolaan | Belum ada larangan reviewer memproses absensi/lemburnya sendiri |
| Role dan profil | Halaman absensi mensyaratkan profil pegawai | `SuperadminSeeder` masih otomatis membuat profil superadmin |
| Status UI | Enum dan beberapa badge tersedia | Label/warna status tersebar di view; status kehadiran, sesi, dan approval belum dipisahkan secara konsisten |
| Geofence | Radius tersimpan nullable di tabel lokasi | Tidak ada perhitungan jarak/enforcement pada action absensi; kebijakan tetap ditunda |

## 2. Database dan relasi

### 2.1 `attendance_records`

Migration `2026_10_20_180954_create_attendance_records_table.php` menyimpan satu baris per event:

- `employee_id`, `type`, `work_type`, `location_id`, `project_id`
- `recorded_at` (komentar migration menyatakan UTC)
- `timezone`, `attendance_date`
- latitude, longitude, accuracy, address snapshot, selfie path/source
- `notes`

Index tersedia untuk employee + timestamp, employee + tanggal, employee + tipe + timestamp, dan tanggal.

**Gap:** tidak ada kolom yang secara eksplisit mengikat semua event ke sesi clock-in yang sama. Saat ini keterkaitan lembur sebagian dibentuk lewat foreign key pada `overtime_requests`.

### 2.2 `overtime_requests`

Migration `2026_10_29_000000_create_overtime_requests_table.php` memiliki:

- `employee_id`
- `clock_in_record_id` wajib
- `start_record_id` nullable dan unique
- `clock_out_record_id` nullable dan unique
- `reason`
- `scheduled_start_at`
- `timezone`, `state`, `approval_status`
- timestamp reminder, jadwal tiba, selesai, batal
- reviewer, waktu keputusan, catatan keputusan
- alasan pengecualian foto

**Gap terhadap keputusan baru:**

1. `clock_in_record_id` wajib, padahal pegawai boleh mengajukan jadwal lembur beberapa hari sebelum hari pelaksanaan.
2. Belum ada `scheduled_end_at`.
3. Waktu aktual mulai ada melalui event `start_record_id`, tetapi belum ada representasi rentang aktual/final yang terpisah.
4. Belum ada kolom sumber waktu final (terjadwal/aktual/manual), nilai final, alasan finalisasi, finalizer, dan timestamp finalisasi.
5. State operasional dan approval sudah dipisahkan, ini perlu dipertahankan.

### 2.3 Koreksi

- `attendance_corrections` menyimpan permintaan pegawai untuk mengoreksi tipe/waktu event, lampiran, status, dan reviewer.
- `daily_status_corrections` menyimpan koreksi status harian per pegawai/tanggal dengan alasan dan pembuat.

Keduanya belum menyediakan finalisasi waktu clock-in/out yang tidak menimpa event asli. Prinsip Fase 2 mengharuskan event asli tetap menjadi bukti dan keputusan admin disimpan sebagai nilai final terpisah beserta alasan dan audit trail.

### 2.4 Lokasi dan geofence

`locations.geofence_radius_m` nullable dan migration mengomentari bahwa kebijakan geofence belum diputuskan. Kode absensi memvalidasi rentang koordinat dan akurasi GPS, tetapi tidak menghitung jarak ke lokasi dan tidak menerapkan radius.

Keputusan Fase 2: **jangan mengimplementasikan geofence sekarang**. GPS dan akurasi tetap menjadi bukti absensi. Kolom lama tidak perlu dihapus atau diubah.

## 3. Business logic

### 3.1 `RecordAttendance`

File: `app/Actions/RecordAttendance.php`

Hal yang sudah baik:
- Transaksi database dan row lock pada employee.
- Mencegah clock-in kedua ketika sesi masih terbuka.
- Clock-out harus memiliki sesi aktif.
- Clock-out mewarisi work type, lokasi, dan proyek dari clock-in.
- Server menghasilkan timestamp event.
- File selfie dibersihkan jika transaksi gagal.
- Ada guard lembur yang telah mencapai jadwal.

Temuan:
- `openSession()` mencari event clock-in/clock-out terakhir dan memakai batas 24 jam.
- Saat menyimpan event, `attendance_date` selalu berasal dari tanggal lokal saat event terjadi. Untuk clock-out setelah tengah malam, tanggal event menjadi tanggal kalender berikutnya, bukan tanggal acuan clock-in.
- Clock-out terkunci jika jadwal lembur tiba dan belum ada foto mulai, kecuali state pengecualian admin. Ini sejalan sebagian dengan keputusan baru.
- Kondisi pembatalan saat clock-out hanya menangani jadwal yang masih di masa depan; kebijakan baru mengizinkan pegawai membatalkan setelah jadwal tiba selama belum mengonfirmasi aktual mulai.

### 3.2 `CreateOvertimeRequest`

File: `app/Actions/CreateOvertimeRequest.php`

Saat ini:
- Wajib memiliki sesi clock-in aktif.
- Waktu jadwal harus tidak lebih awal dari saat ini dan tidak lebih awal dari clock-in.
- Satu sesi hanya boleh memiliki satu pengajuan yang belum selesai.
- Pengajuan dibuat sebagai `scheduled` atau `awaiting_photo`.

Ini tidak mendukung pengajuan tanggal 3 untuk tanggal 5, karena request harus terikat ke sesi yang sudah dimulai. Pengajuan perlu menjadi entitas jadwal mandiri untuk tanggal kerja target, lalu dihubungkan ke sesi saat pegawai clock-in pada tanggal tersebut.

### 3.3 `ConfirmOvertimeStart`

File: `app/Actions/ConfirmOvertimeStart.php`

Saat ini:
- Memerlukan kamera, selfie, dan GPS valid.
- Hanya menerima state `scheduled` / `awaiting_photo`.
- Tidak dapat dikonfirmasi sebelum waktu mulai.
- Memastikan sesi clock-in yang terhubung masih aktif.
- Membuat event `overtime_start` dan mengubah state menjadi `in_progress`.
- Tidak mensyaratkan approval admin terlebih dahulu.

Poin terakhir sesuai prinsip baru: jadwal yang belum dibatalkan dapat berjalan/diaktifkan tanpa menunggu admin approve.

### 3.4 Pembatalan lembur

File: `app/Http/Controllers/OvertimeRequestController.php`

Saat ini pembatalan hanya diizinkan sebelum `scheduled_start_at`. Setelah jadwal tiba, pegawai diarahkan menghubungi admin meskipun belum mengonfirmasi mulai.

Keputusan baru: pegawai boleh membatalkan melalui kartu status lembur selama belum ada event konfirmasi mulai. Pembatalan setelah aktual mulai tidak boleh menghapus event; penghentian dilakukan melalui clock-out.

### 3.5 Status harian

File: `app/Services/DailyAttendanceStatusService.php`

Saat ini:
- Koreksi admin memiliki prioritas tertinggi.
- Konflik beberapa jenis cuti/izin atau izin bersamaan dengan event absensi menghasilkan `needs_review`.
- Clock-in + clock-out pada tanggal yang sama menghasilkan `present`.
- Hanya salah satunya menghasilkan `incomplete`.
- Tidak ada event/izin menghasilkan `no_record`, bukan otomatis `absent`.

Gap:
- Event dikelompokkan menggunakan `attendance_date`; sesi yang melintasi tengah malam berisiko terpecah ke dua tanggal.
- Service belum memeriksa pasangan event berdasarkan sesi, hanya keberadaan event masuk dan keluar di tanggal tersebut.
- Tidak ada aturan shift/jam kerja/toleransi; karena itu jangan menambahkan kalkulasi terlambat atau absent otomatis.

### 3.6 Persetujuan dan audit

File: `app/Http/Controllers/AdminOvertimeRequestController.php`

- Admin/superadmin dapat approve/reject.
- Keputusan awal disimpan dengan reviewer, timestamp, note, dan AuditLog.
- Admin dapat memberi pengecualian foto dengan alasan, sehingga sesi bisa ditutup.
- Belum ada guard agar reviewer tidak memproses pengajuan miliknya sendiri.
- Belum ada finalisasi rentang lembur (terjadwal/aktual/manual).

## 4. MVC dan tampilan

### Pegawai

- `resources/views/pages/employee/⚡attendance.blade.php` adalah Volt page yang mengorkestrasi clock-in, clock-out, kamera/GPS, dan konfirmasi mulai lembur.
- Form utama memilih satu mode: clock-in, overtime-start, atau clock-out. Ketika jadwal lembur tiba, mode overtime-start menggantikan clock-out.
- Kartu lembur menampilkan jadwal dan status, tetapi belum memiliki tombol pembatalan langsung pada kartu.
- Form pengajuan lembur di halaman absensi hanya muncul ketika sesi sudah aktif.
- Halaman `pages/employee/overtime-requests/index.blade.php` hanya meminta waktu mulai, bukan rentang mulai-selesai; pembatalan hanya muncul sebelum jadwal tiba.
- Kamera/GPS dipisahkan ke JS dan Blade component; jangan menulis ulang alur kamera dari awal.

### Admin

- `pages/admin/overtime-requests/index/show.blade.php` menangani daftar, detail, approve/reject, dan pengecualian foto.
- Belum ada UI untuk memilih rentang waktu final, memilih sumber waktu, memasukkan waktu manual, dan mewajibkan alasan finalisasi.
- `pages/admin/daily-attendance.blade.php` menampilkan status harian dan koreksi status; belum menampilkan finalisasi timestamp.
- `pages/admin/⚡kehadiran.blade.php` berfokus pada event, filter, pagination, dan ekspor.
- Selfie disajikan melalui controller khusus dengan penyimpanan private.

### Konsistensi badge dan card

- `DailyAttendanceStatus` sudah memiliki label dan `badgeColor()`.
- `OvertimeApprovalStatus` dan `OvertimeState` memiliki label, tetapi pemetaan warna sebagian dilakukan inline pada view.
- `components/admin/request-status-badge.blade.php` menerima object status yang mendukung label/warna.
- `components/attendance/info-card.blade.php` menerima label dan warna mentah dari pemanggil.
- Ini memungkinkan label/warna yang sama ditulis berbeda di beberapa view.

Arah perbaikan: definisikan status domain (kehadiran harian, sesi, state lembur, approval) secara terpisah, tetapi gunakan satu konvensi komponen/pemetaan label dan warna yang konsisten. Jangan menyamakan status approval dengan status kehadiran.

## 5. Role dan akses

- Route absensi pegawai tersedia untuk user terautentikasi; Volt mount memerlukan akun aktif dan relasi employee.
- Ini memungkinkan admin yang memiliki profil employee menggunakan alur absensi yang sama.
- Superadmin tanpa profil tidak dapat membuka halaman absensi; superadmin yang memiliki profil saat ini dapat melewati guard yang sama.
- `database/seeders/SuperadminSeeder.php` masih membuat profil Employee otomatis untuk superadmin. Ini bertentangan dengan keputusan: superadmin tidak otomatis memiliki profil, tetapi boleh absen jika profil dibuat secara eksplisit.
- Halaman pengelolaan tersedia untuk admin dan superadmin.
- Belum ditemukan larangan eksplisit self-approval pada controller keputusan lembur/koreksi.

Kebijakan baru:
- Admin harus memiliki profil pegawai untuk absen.
- Superadmin tidak otomatis memiliki profil; tanpa profil tidak dapat absen.
- Jika superadmin memiliki profil, ia dapat absen seperti pegawai.
- User yang mengajukan/memiliki absensi tidak boleh memproses keputusan atas catatannya sendiri. Admin lain atau superadmin dapat memproses.

## 6. Test coverage yang ada dan gap

Test yang tersedia:
- `tests/Feature/AttendancePagesTest.php`
- `tests/Feature/AdminAttendancePageTest.php`
- `tests/Feature/OvertimeWorkflowTest.php`
- `tests/Unit/DailyAttendanceStatusServiceTest.php`

Sudah diuji pada test yang dibaca:
- akses halaman absensi per role;
- larangan employee membuka halaman admin;
- penguncian clock-out sebelum foto lembur;
- pencatatan foto mulai lembur tanpa menutup sesi;
- penyelesaian lembur saat clock-out;
- status harian present/incomplete/no-record dan konflik izin.

Gap yang perlu ditambahkan:
- admin sebagai pegawai melakukan clock-in/out;
- superadmin tanpa profil ditolak dan superadmin dengan profil dapat absen;
- superadmin seeder tidak membuat profil otomatis;
- pengajuan lembur sebelum hari/sesi clock-in target;
- jadwal lembur dengan start dan end, termasuk lintas tengah malam;
- jadwal yang disetujui sebelum hari pelaksanaan;
- pembatalan setelah jadwal tiba tetapi sebelum konfirmasi aktual;
- clock-out terkunci sampai konfirmasi atau pembatalan;
- jalur pemulihan admin dengan alasan;
- clock-in, overtime-start, dan clock-out pada satu sesi lintas tanggal;
- reviewer tidak boleh memproses pengajuan sendiri;
- finalisasi jadwal/aktual/manual dengan alasan dan audit trail;
- badge mapping konsisten lintas view.

Test suite **belum dijalankan** sebagai bagian dari audit ini.

## 7. Rekomendasi urutan implementasi Fase 2

1. **Fondasi role dan session date:** perbaiki seeder superadmin tanpa menghapus profil yang sudah ada; tetapkan tanggal acuan sesi sebagai tanggal clock-in untuk seluruh event sesi.
2. **Model jadwal lembur:** pengajuan mandiri berdasarkan tanggal target dengan waktu mulai dan selesai; hubungkan ke sesi clock-in saat pelaksanaan.
3. **Workflow pegawai:** tampilkan kartu lembur untuk jadwal target; tombol konfirmasi dan batalkan tersedia saat jadwal tiba; clock-out terkunci sampai salah satu aksi dilakukan.
4. **Finalisasi admin:** pilihan waktu terjadwal/aktual/manual, alasan wajib, nilai asli tidak ditimpa, reviewer dan timestamp tercatat.
5. **Otorisasi reviewer:** cegah self-review untuk lembur dan koreksi absensi.
6. **Status/UI:** status domain konsisten, badge dan status card konsisten; ringkasan total kerja tidak menghitung durasi yang sama dua kali.
7. **Regresi:** test feature/unit, browser/device test kamera-GPS, dan uji sesi lintas tengah malam.

## 8. Kebijakan yang sengaja belum ditetapkan

- Geofence kantor: ditunda sampai ada keputusan perusahaan. Tidak ada radius enforcement pada Fase 2.
- Geofence proyek: tidak diterapkan.
- Jam kerja reguler, shift, hari kerja/libur, toleransi terlambat, dan perhitungan absent otomatis: belum ditentukan.
- Aturan istirahat/jeda yang mengurangi total durasi: belum ditentukan.
- Nilai final lembur adalah keputusan admin; data terjadwal dan aktual tidak boleh ditimpa.

## 9. Batas PR audit ini

PR ini menambahkan audit dan spesifikasi implementasi sebagai baseline bersama. Perubahan skema dan alur transaksi lembur yang besar tidak dicampur dengan dokumentasi audit sebelum model sesi, jadwal target, rentang aktual, dan finalisasi dapat diimplementasikan beserta test regresinya. Tidak ada migration destruktif dan tidak ada data produksi yang dihapus.


## 5. Tindak lanjut audit — 31 Oktober 2026

Temuan pengulangan sesi dan pengajuan lembur telah ditangani pada branch kerja:

- RecordAttendance::openSession() mempertahankan sesi sampai Clock Out; pergantian tanggal atau lewat 24 jam tidak lagi mengakhiri sesi otomatis.
- RecordAttendance::hasClockInOnDate() memeriksa Clock In pada tanggal lokal, termasuk tanggal efektif dari koreksi timestamp yang disetujui. Guard dijalankan di dalam transaksi setelah row lock employee, sehingga permintaan bersamaan untuk pegawai yang sama diserialisasi.
- Clock In kedua pada tanggal lokal yang sama ditolak walaupun sesi pertama sudah Clock Out.
- overtime_requests.session_date menjadi kunci tanggal sesi target. Migration additive mengisi data lama dari event Clock In yang tertaut; request lama yang belum tertaut menggunakan tanggal lokal jadwal.
- CreateOvertimeRequest memeriksa semua state untuk mencegah request kedua, termasuk request pra-Clock In, Cancelled, dan Completed. Request untuk tanggal yang sesi absensinya sudah ditutup ditolak.
- Clock In mengaitkan request pra-absensi yang cocok dengan tanggal sesi, termasuk request yang sudah dibatalkan, agar riwayat tetap menghabiskan jatah sesi.
- Kartu lembur pegawai menampilkan seluruh request pada tanggal sesi, jadwal, mulai/selesai aktual, nilai final, sumber, dan alasan finalisasi. Konflik historis lebih dari satu request ditandai Perlu ditinjau.
- DailyAttendanceStatusService menandai tanggal historis yang memiliki lebih dari satu Clock In/Clock Out sebagai needs_review; ringkasan durasi pegawai tidak menggabungkan event-event tersebut menjadi satu interval.

Batasan desain yang masih ada:
- Belum ditambahkan tabel attendance_sessions khusus. Identitas sesi absensi tetap diturunkan dari Clock In dan tanggal lokalnya; row lock employee menjadi mekanisme serialisasi.
- Index employee_id, session_date belum unique agar data historis ganda tetap dapat dipertahankan dan ditinjau. Pencegahan duplikasi request baru dilakukan dalam transaksi dengan row lock.
- Batas 24 jam masih dipakai hanya untuk memutuskan apakah jadwal jauh ke depan ditautkan ke sesi aktif atau disimpan sebagai request untuk sesi masa depan.

Validasi:
- GitHub Actions run #365 pada commit de6a5eaad94f9d69e5ad1a3afa5df68f4a4cb328 lulus: formatting, repository-wide static analysis, static analysis kode yang berubah, dan test suite (104 passed, 1 skipped, 307 assertions).
- Setelah commit yang diuji, hanya dokumen audit yang diperbarui; tidak ada perubahan kode aplikasi.
- PR #21 tetap Draft dan belum digabung.
