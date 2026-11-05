# Manajemen Akun Pegawai — Audit Baseline dan Rencana Implementasi

Tanggal audit: 5 November 2026  
Repository: `leonsmith128/pt-sanmal`  
Baseline: `main` pada commit `51dff090da85f645b287e4ab494e01b46ec4d638`  
Branch kerja: `feature/pengelolaan-akun-pegawai`

## Tujuan

Menyelesaikan pengelolaan akun pegawai dari pembuatan akun, aktivasi, kredensial sementara, perubahan data/status, sampai reset password yang memerlukan persetujuan admin. Dokumen ini mencatat kondisi source yang diperiksa sebelum implementasi; source branch tetap menjadi acuan utama.

## 1. Temuan audit baseline

### Model dan database

- `users` menyimpan identitas autentikasi, `role`, `is_active`, `must_change_password`, `email_verified_at`, dan password.
- `User` memakai atribut Laravel `#[Fillable(['name', 'email', 'password'])]`. Atribut sensitif `role`, `is_active`, dan `must_change_password` sengaja tidak mass assignable.
- `Employee` merupakan profil terpisah dengan relasi `belongsTo(User)`; `employees.user_id` unik dan foreign key memakai `restrictOnDelete()`. Data histori tidak boleh dihapus melalui penghapusan akun.
- `password_reset_requests` sudah tersedia dengan `user_id`, status, reviewer, catatan, hash token, expiry, dan waktu penggunaan. `PasswordResetRequest` saat ini belum memiliki relasi, fillable, atau cast.
- `audit_logs` sudah tersedia dan bersifat append-only. `AuditLog::record()` dapat dipakai untuk mencatat tindakan akun tanpa memasukkan password/token.

### HTTP, autentikasi, dan otorisasi

- `AdminEmployeeController` saat ini menangani index, create, store, edit, dan update.
- `store()` menggunakan `User::create()` dengan atribut role/status/`must_change_password` yang tidak fillable. Hal yang sama terjadi pada `update()` untuk `is_active`. Ini berisiko membuat nilai sensitif tidak tersimpan sebagaimana dimaksud.
- `store()` membuat password acak 40 karakter tetapi tidak menyediakan workflow aktivasi, tidak menyimpan salinan terenkripsi yang dapat diambil admin, dan tidak mengirim undangan aktivasi.
- Route resource pegawai mengecualikan `destroy`, tetapi belum mengecualikan `show`; controller tidak memiliki method `show()`. Route resource perlu diselaraskan dengan method yang benar-benar tersedia.
- `EnsureUserIsActive` sudah melindungi grup route terautentikasi.
- Grup route aplikasi saat ini menerapkan middleware `verified` secara global. Ini bertentangan dengan keputusan bahwa pegawai yang menggunakan password sementara boleh menyelesaikan aktivasi dan menggunakan aplikasi tanpa harus memverifikasi email terlebih dahulu.
- `must_change_password` belum ditegakkan oleh middleware pada route aplikasi.
- Fortify mengaktifkan `Features::resetPasswords()` dan `Features::emailVerification()`. Reset bawaan mengirim token melalui broker Laravel dan tidak mensyaratkan approval pada `password_reset_requests`; jalur ini harus ditutup agar tidak melewati workflow persetujuan.
- `ResetUserPassword` hanya mengganti password dan belum menghapus secret sementara atau mengelola lifecycle aktivasi.
- Route admin memakai middleware role Admin/Superadmin. UI bukan pengganti otorisasi server.

### Test baseline

- `AdminEmployeePageTest` menguji tampilan index, pencarian/filter status, dan empty state. Belum menguji CRUD akun, aktivasi, kredensial sementara, perubahan status, atau workflow reset approval.
- `AdminAuthorizationTest` menjadi baseline otorisasi admin.
- `Auth/PasswordResetTest` secara eksplisit menguji reset password Fortify publik; test ini perlu diganti dengan kontrak reset yang memerlukan persetujuan admin ketika fitur bawaan dinonaktifkan.
- CI repository menggunakan MariaDB 11 dan SQLite. Migration dan constraint perlu dipertimbangkan terhadap keduanya.

## 2. Keputusan produk yang telah disetujui

### Aktivasi dan verifikasi email adalah proses terpisah

- `is_active` menentukan akses aplikasi.
- `must_change_password` menentukan kewajiban mengganti password.
- `email_verified_at` hanya mencatat verifikasi kepemilikan email.
- Aktivasi melalui tautan email maupun password sementara hanya menyelesaikan aktivasi akun dan penggantian password pertama; keduanya tidak mengisi `email_verified_at`.
- Verifikasi kepemilikan email tetap dilakukan melalui workflow verifikasi email Fortify yang terpisah di menu Keamanan Akun. Pegawai dapat menggunakan aplikasi setelah aktivasi tanpa menunggu verifikasi email.

### Pembuatan dan aktivasi akun

1. Admin membuat User dan Employee dalam satu database transaction.
2. Sistem menghasilkan password sementara kuat; hash menjadi password login dan salinan terenkripsi disimpan terpisah.
3. Sistem membuat activation token acak, menyimpan hash token, expiry 24 jam, dan mengirim email.
4. Activation link hanya dapat digunakan sekali. Resend membatalkan token sebelumnya.
5. Admin yang berwenang dapat mengambil password sementara berulang kali sampai password benar-benar diganti. Secret tidak boleh ada pada daftar pegawai atau response umum.
6. Pegawai yang login dengan password sementara hanya dapat mengakses alur penggantian password dan logout.
7. Setelah password diganti, secret terenkripsi dihapus, activation token dibatalkan, dan `must_change_password` menjadi false.
8. Akun nonaktif tidak dapat login, aktivasi, atau reset password.

### Reset password

1. Pegawai mengajukan permintaan reset.
2. Admin meninjau dan menyetujui atau menolak dengan catatan.
3. Persetujuan menerbitkan token reset satu kali dengan expiry 24 jam.
4. Pegawai mengganti password melalui token yang valid; permintaan ditandai digunakan.
5. Reset publik bawaan Fortify tidak boleh menjadi jalur bypass approval.

### Keamanan dan audit

- Password hanya disimpan dalam bentuk hash; salinan sementara menggunakan enkripsi Laravel.
- Token disimpan sebagai hash.
- Endpoint reveal password harus terotorisasi, dibatasi dan tidak mengekspos secret ke response umum.
- Password/token mentah tidak boleh dicatat ke log atau audit log.
- Tindakan pembuatan akun, reveal kredensial, resend/rotasi, aktivasi, perubahan status, dan keputusan reset dicatat tanpa nilai secret.
- Deaktivasi tidak menghapus User, Employee, atau histori absensi.

## 3. Arah implementasi

Pertahankan Laravel MVC untuk CRUD admin: Controller menangani HTTP, Action menjalankan use case, Model/relasi mewakili domain, Policy/middleware menjaga otorisasi, dan Blade menangani presentasi. Jangan memindahkan CRUD ke Livewire atau menambah abstraksi sebelum dibutuhkan.

Struktur final disesuaikan dengan konvensi source yang ada. Action dikelompokkan berdasarkan use case akun pegawai; jangan membuat Service generik tanpa kebutuhan nyata.

Tabel tambahan yang diperkirakan dibutuhkan:
- `employee_account_credentials`: satu secret password sementara terenkripsi per user.
- `employee_account_activations`: hash token aktivasi, expiry, dan consumed timestamp.

Tabel `password_reset_requests` yang sudah ada harus diperluas melalui model/Action sesuai kebutuhan, bukan diduplikasi. Token reset dapat tetap memakai kolom yang tersedia setelah pemeriksaan lifecycle dan concurrency. Gunakan transaksi/locking agar token satu kali tidak dapat dikonsumsi bersamaan.

## 4. Urutan pengerjaan

1. Audit baseline — selesai untuk source utama, route, migration, model, Fortify, middleware, dan test.
2. Database, relasi model, Action create/update — fondasi akun.
3. Aktivasi, password sementara, token, dan middleware — workflow aktivasi.
4. Reset password dengan approval admin.
5. Controller, Policy, route, dan UI Blade admin.
6. Feature test, regresi, security review, dan dokumentasi.

## 5. Kriteria penerimaan

- Mass assignment sensitif tidak melemah.
- Pembuatan User dan Employee atomik.
- Token aktivasi/reset one-time dan expiry diuji.
- Password sementara hanya dapat diambil admin berwenang, dapat disalin berulang sebelum diganti, dan terhapus setelah password diganti.
- Pegawai pending hanya dapat mengganti password atau logout.
- Email belum diverifikasi tidak menghalangi pegawai jalur password sementara setelah password diganti.
- Akun nonaktif ditolak.
- Reset publik tidak dapat melewati approval.
- Perubahan email menangani verifikasi email lama dengan benar.
- Data Employee dan histori absensi tetap terjaga.
- Audit log tidak memuat secret.
- Test targeted, Pint, dan pemeriksaan CI yang tersedia lulus; keterbatasan verifikasi dilaporkan apa adanya.
