# Kontrak Pengelolaan Pegawai dan Admin — V1

> **Status dokumen:** Kontrak produk dan handoff implementasi. Aturan bisnis di bawah telah disepakati; temuan pada bagian audit adalah kondisi source yang diperiksa pada branch ini. Dokumen ini tidak menyatakan seluruh kontrak sudah diimplementasikan.

## 1. Tujuan dan ruang lingkup

Dokumen ini menjadi acuan untuk pengelolaan data kepegawaian dan akun login Employee/Admin dalam satu UI. Cakupan V1:

- Melihat, mencari, dan mengelola data pegawai.
- Membuat akun Employee maupun Admin melalui satu alur pembuatan pegawai.
- Menghasilkan kode/nomor pegawai otomatis.
- Mengaktifkan akun dan mengganti password sementara.
- Mengelola status aktif/nonaktif.
- Memisahkan status aktivasi akun dari verifikasi email.
- Menjaga akun Superadmin tunggal dan menyediakan pemulihan hanya melalui shell production.

Di luar cakupan: pembuatan Superadmin melalui UI, self-registration, perubahan role oleh Admin, penghapusan permanen data pegawai, serta pemulihan Superadmin melalui endpoint web.

## 2. Model akun dan data

### 2.1 Relasi User dan Employee

- `users` adalah identitas autentikasi dan menyimpan role, email, password, status aktif, serta status verifikasi email.
- `employees` adalah profil kepegawaian yang terhubung ke satu User.
- Akun dengan role **Employee** dan **Admin** wajib mempunyai profil Employee.
- **Superadmin** merupakan akun khusus dan tidak wajib mempunyai profil Employee.
- Karena Admin juga pegawai, Admin tampil dalam daftar dan form pengelolaan pegawai yang sama. Perbedaan hak akses ditentukan oleh role pada User, bukan dengan membuat sistem data pegawai kedua.

### 2.2 Kode pegawai otomatis

Kode pegawai dibuat oleh server ketika data Employee dibuat. Field pada form tetap terlihat agar pengguna memahami kode yang akan diberikan, tetapi tidak dapat diedit.

Format awal:

- `EMP-01`
- `EMP-02`
- ...
- `EMP-99`
- `EMP-100`
- `EMP-101`

Dua digit adalah **lebar minimum** untuk bagian angka, bukan batas maksimum. Nilai harus tetap disimpan sebagai string dan tidak boleh dibatasi menjadi dua digit. Rekomendasi kolom: `VARCHAR(32)` atau kapasitas setara dengan batas validasi aplikasi yang terdokumentasi.

Aturan pembuatan:

1. Kode dibuat di backend; nilai dari browser hanya preview dan tidak dipercaya saat penyimpanan.
2. Pembuatan kode dan Employee dilakukan dalam transaksi.
3. Gunakan sequence/counter database yang dikunci saat mengambil nomor berikutnya. Hindari `MAX()+1` tanpa locking karena dua request bersamaan dapat menghasilkan nomor sama.
4. Tetap pasang unique constraint pada kolom kode sebagai pertahanan terakhir.
5. Kode yang sudah pernah dipakai tidak digunakan kembali setelah akun dinonaktifkan atau data diarsipkan. Jika penghapusan permanen kelak diizinkan, kebijakan nomor tetap tidak boleh menyebabkan duplikasi historis.
6. Kegagalan transaksi tidak boleh meninggalkan profil tanpa User atau mengonsumsi nomor secara tidak konsisten.

## 3. Role dan otorisasi

| Kemampuan | Superadmin | Admin | Employee |
|---|---:|---:|---:|
| Melihat daftar akun Employee | Ya | Ya | Tidak |
| Melihat/edit akun Employee | Ya | Ya | Tidak |
| Melihat/edit akun Admin | Ya | Tidak | Tidak |
| Membuat akun Employee | Ya | Tidak | Tidak |
| Membuat akun Admin | Ya | Tidak | Tidak |
| Menentukan role saat pembuatan akun | Ya | Tidak | Tidak |
| Mengubah role akun yang sudah ada | Superadmin saja, jika kelak disediakan secara eksplisit | Tidak | Tidak |
| Mengelola status akun Employee | Ya | Ya | Tidak |
| Mengelola status akun Admin | Ya | Tidak | Tidak |
| Menyetujui reset akun Employee | Ya | Ya | Tidak |
| Menyetujui reset akun Admin | Ya | Tidak | Tidak |
| Membuat/memulihkan Superadmin dari UI | Tidak | Tidak | Tidak |

Ketentuan:

- Hanya Superadmin yang boleh membuat akun baru, termasuk akun Admin. Pembuatan akun Employee dan Admin berada pada halaman/form yang sama.
- Admin dapat mengelola data pegawai yang sudah ada sesuai route dan policy, tetapi tidak boleh membuat akun baru, mengangkat dirinya sendiri, atau mengubah role.
- Role ditentukan dan divalidasi di server. Jangan menerima role mentah dari browser sebagai otorisasi.
- Menyembunyikan tombol di UI bukan kontrol keamanan. GET, POST, dan setiap endpoint tindakan harus tetap dilindungi middleware/policy di backend.
- Pembuatan akun Admin menghasilkan User ber-role Admin sekaligus Employee profile, dalam satu transaksi.

## 4. Alur UI pembuatan pegawai dan Admin

Satu halaman **Tambah Pegawai** digunakan untuk dua jenis akun.

Form mencakup:

### Akun login
- Nama lengkap.
- Email login.
- Jenis akun/role: Employee atau Admin. Field ini hanya tersedia untuk Superadmin.

### Data kepegawaian
- Kode pegawai: otomatis terisi, read-only/disabled, format preview `EMP-01`.
- Nomor telepon.
- Jabatan.
- Tanggal bergabung.
- Field data kepegawaian lain yang memang sudah menjadi bagian domain Employee.

Perilaku:

- Kode yang tampil adalah preview nomor berikutnya; backend tetap menghasilkan dan menetapkan kode secara otoritatif ketika form disimpan.
- Setelah validasi gagal, preview boleh diminta ulang dari server; jangan mengandalkan nilai kode dari input lama.
- Saat disimpan, buat User dan Employee dalam satu database transaction.
- Password sementara dibuat server, disimpan dalam bentuk hash pada User dan salinan terenkripsi pada penyimpanan kredensial sementara.
- Kirim tautan aktivasi dengan token sekali pakai dan masa berlaku 24 jam.
- Admin/Superadmin yang berwenang dapat mengambil password sementara melalui endpoint khusus yang terlindungi; password tidak boleh ditampilkan pada HTML awal atau response daftar umum.
- Setelah pegawai mengganti password, hapus salinan password sementara dan batalkan token aktivasi yang belum digunakan.

## 5. Aktivasi, verifikasi email, dan status aktif

Tiga status ini memiliki arti berbeda dan tidak boleh digabung:

- `is_active`: menentukan apakah akun boleh menggunakan aplikasi.
- `must_change_password`: menentukan apakah pengguna masih wajib mengganti password sementara.
- `email_verified_at`: mencatat bahwa kepemilikan alamat email telah diverifikasi.

**Keputusan V1: akun tetap dapat digunakan walaupun email belum diverifikasi.** Aturan ini berlaku untuk Employee, Admin, dan Superadmin.

Alur akun baru:

1. Akun dibuat dengan `is_active = true`, `must_change_password = true`, dan `email_verified_at = null`.
2. Pengguna menyelesaikan aktivasi melalui tautan email atau menggunakan password sementara yang diberikan admin untuk masuk dan mengganti password pertama.
3. Setelah password pertama diganti, `must_change_password = false`; akun dapat menggunakan aplikasi.
4. `email_verified_at` tetap null sampai pengguna menyelesaikan verifikasi email secara terpisah melalui Keamanan Akun.
5. Status email belum diverifikasi tidak boleh memblokir akses aplikasi setelah aktivasi dan penggantian password selesai.

Perubahan email:

- Mengubah email harus mengosongkan `email_verified_at` dan mengirim tautan verifikasi ke email baru.
- Kirim notifikasi keamanan ke email lama bila memungkinkan.
- Akun tetap dapat digunakan selama `is_active = true` dan tidak sedang diwajibkan mengganti password.
- Perubahan email harus membatalkan token aktivasi/reset yang menjadi tidak relevan dan ditangani secara konsisten baik dari UI admin maupun self-service Keamanan Akun.
- Jangan memasang middleware `verified` secara global pada seluruh route aplikasi untuk V1.

Deaktivasi:

- Akun nonaktif tidak boleh login atau menggunakan endpoint aplikasi.
- Batalkan token aktivasi dan token reset yang masih berlaku.
- Jangan menghapus User, Employee, kode pegawai, atau histori absensi.

## 6. Password dan reset akun

- Password sementara: tepat 8 karakter, huruf dan angka saja, minimal satu huruf dan satu angka.
- Password yang dibuat sendiri saat aktivasi, perubahan password pertama, perubahan dari Keamanan Akun, atau reset: minimal 8 karakter, mengandung huruf dan angka; simbol boleh digunakan.
- Hash password menggunakan mekanisme hashing Laravel. Salinan sementara, bila diperlukan untuk onboarding, wajib dienkripsi dan dihapus setelah password diganti.
- Token aktivasi/reset disimpan dalam bentuk hash, memiliki expiry, dan hanya dapat digunakan sekali.
- Reset password akun Employee memerlukan permintaan dan persetujuan Admin/Superadmin.
- Akun Admin juga dapat mengajukan reset password, tetapi hanya Superadmin yang boleh menyetujui permintaan reset akun Admin.
- Superadmin tidak menggunakan reset publik; pemulihannya hanya melalui command terminal khusus.
- Reset publik Fortify tidak boleh menjadi jalur yang melewati approval.
- Jangan pernah mencatat password atau token mentah pada audit log, application log, atau response umum.

## 7. Superadmin tunggal: bootstrap dan recovery

### Bootstrap awal

- Akun Superadmin pertama dibuat dari konfigurasi environment melalui command Artisan, bukan melalui UI.
- Environment hanya menjadi sumber kredensial saat bootstrap pertama. Setelah akun tersimpan, perubahan nilai ENV tidak boleh diam-diam menimpa email atau password database.
- Database harus menegakkan maksimal satu akun Superadmin melalui unique constraint yang kompatibel dengan database produksi.
- Bootstrap tidak boleh menjadi mekanisme reset atau recovery akun yang sudah ada.

### Recovery production

Rekomendasi: sediakan command terpisah, misalnya:

```bash
php artisan sanmal:recover-superadmin
```

Ketentuan wajib:

1. Hanya dapat dijalankan dari terminal cPanel atau SSH pada environment production.
2. Tidak ada route HTTP, halaman UI, API, atau tombol untuk menjalankannya.
3. Command harus memastikan tepat satu akun Superadmin sudah ada. Jika tidak ada atau ditemukan kondisi duplikat, hentikan proses dan minta operator memperbaiki kondisi database; jangan otomatis membuat akun baru.
4. Email dan password dapat dipulihkan dari prompt terminal; password baru diminta secara interaktif dengan input tersembunyi. Jika email berubah, kosongkan `email_verified_at` dan kirim verifikasi ke alamat baru. Jangan menerima password melalui argument command, opsi CLI, atau output shell history.
5. Hash password baru dengan mekanisme Laravel.
6. Cabut sesi aktif dan token pemulihan/reset yang relevan sejauh didukung konfigurasi session/token aplikasi.
7. Catat audit event recovery tanpa menyimpan password, token, atau secret.
8. Email Superadmin tidak perlu diubah dalam operasi recovery password. Jika recovery email memang diperlukan, buat operasi terpisah yang secara eksplisit mengubah email, mengosongkan `email_verified_at`, mengirim verifikasi ke email baru, dan memberi notifikasi keamanan.
9. Command harus menolak dijalankan melalui konteks web dan tidak boleh mengeluarkan kredensial ke log.

Bootstrap dan recovery adalah dua command dengan tanggung jawab berbeda: bootstrap membuat Superadmin pertama; recovery memulihkan akun Superadmin yang sudah ada.

## 8. Audit source branch saat dokumen disusun

Pemeriksaan dilakukan pada source branch `feature/pengelolaan-akun-pegawai`, terutama route, controller, Action, model, migration, Blade form, dan workflow keamanan. Temuan berikut menggambarkan source yang diperiksa, bukan target kontrak.

### Sudah tersedia pada source

- CRUD daftar, tambah, edit, dan update profil Employee menggunakan Laravel Controller + Action + Blade.
- Route GET create dan POST store hanya berada di middleware Superadmin; Admin dapat mengakses pengelolaan pegawai yang sudah ada.
- `CreateEmployee` membuat User dan Employee dalam transaksi serta menetapkan role `Employee` di server.
- Password sementara dibuat otomatis, di-hash melalui cast User, dan disimpan terenkripsi untuk kebutuhan onboarding.
- Token aktivasi disimpan sebagai hash, berlaku 24 jam, dan dapat dikirim ulang.
- Aktivasi dan penggantian password pertama menghapus kredensial sementara.
- Middleware `EnsurePasswordIsChanged` melindungi aplikasi sampai password sementara diganti.
- Route aplikasi tidak menerapkan middleware `verified` global; aktivasi/password pertama terpisah dari verifikasi email.
- Perubahan email melalui alur edit admin mengosongkan `email_verified_at`, membatalkan token aktivasi dan request reset yang belum selesai, lalu controller mengirim notifikasi verifikasi.
- UI edit menampilkan status akun, status aktivasi, dan status verifikasi email secara terpisah.
- Command bootstrap Superadmin tersedia dan migration menambahkan unique key untuk membatasi satu Superadmin.

### Gap terhadap kontrak terbaru

1. **Belum ada pembuatan akun Admin melalui UI.** Route dan controller saat ini membuat akun dengan role tetap `Employee`; belum ada pilihan role Admin pada form maupun Action.
2. **Kode pegawai belum otomatis.** Form saat ini menerima `employee_number` manual dan opsional; controller memvalidasi input browser, sedangkan Action menyimpan nilai tersebut. Belum ada generator, preview read-only, sequence, atau mekanisme locking.
3. **Kapasitas dan aturan kode pegawai belum sesuai kontrak baru.** Validasi controller saat ini membatasi input hingga 50 karakter, tetapi tidak ada format `EMP-XX` yang dijamin server.
4. **Perubahan email self-service belum membatalkan request reset yang masih berlaku.** Komponen Keamanan Akun mengubah email dan `email_verified_at`, tetapi tidak menginvalidasi request reset seperti yang dilakukan Action update admin.
5. **Bootstrap belum menjadi no-op idempotent.** Command memvalidasi konfigurasi terlebih dahulu dan mengembalikan FAILURE ketika Superadmin sudah ada. Ini tidak mereset akun, tetapi perilaku no-op sukses yang diinginkan belum diterapkan.
6. **Command recovery Superadmin belum tersedia.** Belum ada command shell terpisah dengan verifikasi tepat satu Superadmin, input password tersembunyi, pencabutan sesi/token, dan audit event recovery.
7. **Cakupan reset password untuk role Admin perlu ditentukan dan diuji.** Action request reset saat ini secara eksplisit hanya menerima role Employee, walaupun kontrak data baru mewajibkan Admin mempunyai Employee profile.
8. **Belum ada batas pengelolaan akun Admin oleh Admin lain.** Route edit/update saat ini tersedia untuk Admin dan Superadmin tanpa memeriksa role User yang terkait dengan Employee. Setelah akun Admin dibuat sebagai Employee profile, Admin berpotensi mengubah email atau menonaktifkan akun Admin lain. Rekomendasi keamanan: hanya Superadmin yang boleh mengubah atau menonaktifkan akun ber-role Admin; Admin hanya mengelola akun ber-role Employee. Ini perlu dikonfirmasi sebagai aturan produk sebelum implementasi.
9. **Unique Superadmin constraint perlu diverifikasi pada MariaDB production.** Migration menggunakan generated virtual column dan unique index; uji MariaDB CI serta pemeriksaan data duplikat sebelum deploy tetap wajib.

## 9. Status implementasi tahap lanjutan — 7 November 2026

Source branch kini menerapkan aturan tambahan berikut:

- Admin hanya melihat dan mengelola akun ber-role Employee. Admin tidak dapat membuka, mengubah, menonaktifkan, menyalin password sementara, merotasi password sementara, atau mengirim ulang aktivasi untuk akun Admin lain.
- Superadmin dapat mengelola akun Employee dan Admin. Pemeriksaan tersebut diterapkan pada controller, bukan hanya dengan menyembunyikan tombol UI.
- Route dan controller pembuatan akun sama-sama membatasi create/store kepada Superadmin.
- Akun Admin dapat mengajukan reset password. Admin hanya dapat menyetujui/menolak permintaan akun Employee; permintaan reset akun Admin hanya dapat diproses Superadmin.
- Alur reset password menolak role Superadmin; pemulihan Superadmin dilakukan lewat command shell.
- Perubahan email dari halaman pengelolaan akun mengirim verifikasi ke email baru dan notifikasi keamanan ke email lama. Perubahan tetap tidak menonaktifkan akun aktif.
- Command bootstrap menjadi no-op sukses ketika Superadmin sudah ada, bahkan ketika konfigurasi bootstrap tidak lagi tersedia.
- Command `sanmal:recover-superadmin` memulihkan akun Superadmin yang sudah ada, meminta password secara tersembunyi, mewajibkan verifikasi ulang bila email berubah, membatalkan request reset, mencabut sesi/token yang didukung, dan mencatat audit tanpa secret.

**Status verifikasi:** source dan test baru telah disiapkan, tetapi test suite, Pint, PHPStan, CI, dan QA browser belum dijalankan. CI tetap ditunda sampai seluruh implementasi fitur selesai.

---

## 10. Rencana implementasi yang disarankan

Urutan kerja untuk mengurangi risiko:

1. Tambahkan generator kode pegawai yang aman terhadap request bersamaan, unique constraint, dan test sequence termasuk transisi `EMP-99` ke `EMP-100`.
2. Perluas use case pembuatan pegawai agar menerima role yang diizinkan (Employee/Admin), dengan otorisasi Superadmin tetap di backend.
3. Perbarui form tunggal: role selector hanya untuk Superadmin dan kode pegawai read-only yang dipreview dari server.
4. Pastikan User + Employee + kode pegawai + kredensial onboarding dibuat atomik.
5. Tegaskan batas pengelolaan akun Admin (rekomendasi: hanya Superadmin yang dapat mengubah/menonaktifkan akun Admin), cakupan reset, dan tambahkan test role yang eksplisit.
6. Samakan invalidasi token/request reset pada perubahan email self-service dan admin.
7. Tambahkan command recovery Superadmin yang hanya berjalan melalui shell; rapikan perilaku bootstrap agar tidak mengubah akun yang sudah ada.
8. Jalankan Pint, feature tests, test MariaDB 11 dan SQLite, lalu review route/authorization dan audit log.
9. Lakukan QA browser pada desktop dan mobile untuk form role, preview kode, validasi, serta status akun.

## 11. Kriteria penerimaan

- [ ] Superadmin dapat membuat akun Employee dan Admin dari halaman/form yang sama.
- [ ] Admin tidak dapat membuat akun baru atau mengubah role melalui request langsung.
- [ ] Setiap Admin/Employee mempunyai tepat satu profil Employee; Superadmin tidak diwajibkan mempunyai profil Employee.
- [ ] Kode pegawai otomatis dibuat server, tampil read-only pada form, unik, dan aman terhadap request bersamaan.
- [ ] Format kode memakai minimal dua digit dan terus bertambah menjadi tiga digit atau lebih tanpa perubahan skema/aturan.
- [ ] User dan Employee dibuat atomik; kegagalan tidak meninggalkan data parsial.
- [ ] Akun aktif dapat digunakan setelah aktivasi/password pertama selesai meskipun email belum diverifikasi.
- [ ] Perubahan email meminta verifikasi ulang tetapi tidak memblokir akses akun aktif.
- [ ] Perubahan email membatalkan token/request lama secara konsisten.
- [ ] Deaktivasi memblokir akses tanpa menghapus data kepegawaian atau histori.
- [ ] Reset password tidak dapat melewati approval dan cakupan role Admin sudah jelas.
- [ ] Bootstrap tidak pernah menimpa akun Superadmin yang sudah ada.
- [ ] Recovery hanya tersedia melalui terminal cPanel/SSH dan tidak membuat akun baru.
- [ ] Audit log tidak menyimpan password/token mentah.
- [ ] Test targeted, Pint, MariaDB, SQLite, dan QA browser dilaporkan berdasarkan hasil yang benar-benar dijalankan.

---

**Catatan implementasi:** Jangan menganggap seluruh gap pada bagian 8 sudah diperbaiki hanya karena kontraknya tercatat di dokumen. Perubahan source harus dilakukan dan diverifikasi secara terpisah.
