# Spesifikasi Perilaku Absensi — Fase 2

Status: aturan produk dikunci untuk implementasi, dengan kebijakan jam kerja reguler dan geofence tetap tertunda.

## 1. Role

- Employee: wajib memiliki profil pegawai untuk melakukan absensi.
- Admin: wajib memiliki profil pegawai dan menggunakan halaman/alur absensi yang sama dengan employee.
- Superadmin: tidak otomatis memiliki profil pegawai. Tanpa profil tidak dapat absen; jika profil dibuat secara eksplisit, dapat absen sebagai pegawai.
- Admin dan superadmin dapat mengelola absensi sesuai kewenangan.
- Pengguna tidak boleh memproses/menyetujui absensi atau lembur miliknya sendiri. Admin lain atau superadmin dapat memproses catatan admin.

## 2. Sesi absensi

- Satu sesi kerja terdiri dari clock-in, event lembur (opsional), dan clock-out.
- Sesi dapat melewati tengah malam; perubahan tanggal kalender tidak membuat sesi baru.
- Tanggal acuan sesi adalah tanggal lokal clock-in.
- Satu sesi absensi diperbolehkan untuk setiap tanggal lokal: hanya satu pasangan Clock In–Clock Out. Setelah Clock Out, Clock In kedua pada tanggal yang sama ditolak oleh backend.
- Sesi tetap terbuka sampai Clock Out; pergantian tanggal dan lewatnya 24 jam tidak otomatis menutup sesi.
- Batas 24 jam hanya digunakan sebagai batas teknis untuk mengaitkan jadwal lembur ke sesi aktif versus sesi masa depan yang belum dimulai.
- Timestamp asli setiap event tetap menyimpan tanggal dan waktu aktualnya.
- Clock-out menutup sesi yang sama dan mempertahankan work type, lokasi, dan proyek dari clock-in.

## 3. Pengajuan dan jadwal lembur

- Pegawai dapat membuat pengajuan lembur kapan saja, termasuk beberapa hari sebelum tanggal pelaksanaan (contoh: tanggal 3 untuk tanggal 5) dan mendekati waktu mulai (hingga satu menit sebelum jadwal).
- Form pengajuan baru hanya meminta waktu mulai terjadwal; alasan bersifat opsional.
- Waktu selesai aktual berasal dari Clock Out. Kolom waktu selesai terjadwal lama dipertahankan hanya untuk kompatibilitas data historis.
- Satu pengajuan lembur diperbolehkan per sesi absensi. Pengajuan sebelum Clock In ikut dikaitkan ke tanggal sesi target; pengajuan yang dibatalkan atau selesai tetap menghabiskan jatah tersebut.
- Pengajuan tidak dapat dibuat ulang setelah sesi target ditutup. Pengajuan untuk tanggal masa depan tetap dapat dibuat sebelum sesi dimulai.
- Jadwal dapat melewati tengah malam.
- Approval admin dan status operasional jadwal adalah dua hal berbeda.
- Admin dapat menyetujui sebelum jadwal tiba.
- Jadwal yang sudah disetujui tetap dapat dibatalkan pegawai sebelum konfirmasi aktual mulai.
- Ketika jadwal tiba dan belum dibatalkan, jadwal tetap aktif meskipun belum di-approve.
- Jadwal harus dapat dikaitkan ke sesi pegawai pada tanggal target setelah clock-in, bukan mensyaratkan sesi telah ada ketika pengajuan dibuat.

## 4. Tombol dan alur pegawai

- Setelah clock-in, jika terdapat jadwal lembur pada tanggal kerja tersebut, kartu status lembur ditampilkan.
- Ketika jadwal belum tiba, pegawai dapat melihat jadwal dan status approval.
- Ketika jadwal tiba dan belum dibatalkan/dikonfirmasi, tampilkan tombol **Konfirmasi Lembur** dan **Batalkan Lembur**.
- Selama pengajuan lembur masih aktif dan belum dikonfirmasi aktual, clock-out ditolak di backend dan UI, termasuk jika waktu jadwal belum tiba. Pegawai harus membatalkan pengajuan terlebih dahulu (pilihan C) untuk mencegah human error.
- Tombol **Batalkan Lembur** harus menggunakan warna merah yang menonjol dan meminta konfirmasi sebelum pembatalan.
- Konfirmasi lembur mencatat event aktual mulai menggunakan timestamp server dan bukti selfie/GPS.
- Setelah konfirmasi atau pembatalan, tombol clock-out tersedia.
- Jika pegawai tidak dapat konfirmasi/batal karena kendala, admin memiliki jalur pengecualian/pemulihan dengan alasan wajib.
- Setelah konfirmasi aktual mulai, lembur tidak dapat dibatalkan; sesi diakhiri melalui clock-out.

## 5. Tiga rentang waktu lembur

1. Terjadwal: rentang yang diajukan pegawai.
2. Aktual: mulai dari submit konfirmasi lembur dan berakhir saat clock-out, menggunakan timestamp server.
3. Final: rentang yang dipilih admin dari jadwal, aktual, atau koreksi manual.

Data terjadwal dan aktual adalah bukti immutable; keputusan final tidak menimpa keduanya.

Admin wajib memilih sumber waktu final:
- gunakan jadwal;
- gunakan aktual;
- koreksi manual (waktu mulai dan selesai).

Alasan wajib untuk keputusan finalisasi/koreksi. Simpan reviewer, waktu keputusan, sumber yang dipilih, nilai final, alasan, nilai sebelum/sesudah, dan audit trail. Nilai final tidak boleh dipilih otomatis tanpa keputusan yang dapat ditelusuri.

## 6. Total waktu kerja

- Total harian harus menggabungkan bagian kerja reguler dan lembur yang diakui, tanpa menghitung interval yang sama dua kali.
- Untuk sesi berurutan, batas lembur final memisahkan interval reguler dan interval lembur.
- Contoh konseptual: clock-in 08.00, lembur final mulai 17.00, clock-out 20.00 => reguler 08.00–17.00 + lembur 17.00–20.00 = 12 jam (sebelum aturan istirahat).
- Jika waktu lembur final dipilih admin berbeda dari aktual, perhitungan mengikuti rentang final dan menampilkan perbedaan dari data aktual.
- Kebijakan jam kerja reguler dan istirahat belum diputuskan; jangan menghitung keterlambatan/absent otomatis atau mengurangkan istirahat tanpa kebijakan.

## 7. Status

Status harus dibedakan menurut domain:
- status kehadiran harian;
- status sesi absensi;
- state operasional lembur;
- approval lembur;
- status finalisasi/review.

Clock-in tanpa clock-out = belum lengkap. Tanpa event = belum ada catatan, bukan absent otomatis. Konflik perlu ditinjau. Label dan warna badge harus konsisten di seluruh halaman, tanpa menyatukan arti status antar-domain.

## 8. Geofence dan bukti

- Tidak ada geofence untuk kantor maupun proyek pada Fase 2.
- Jangan menghitung jarak atau menolak absensi berdasarkan radius.
- GPS dan akurasi tetap dicatat sebagai bukti; reverse geocoding hanya pelengkap.
- Foto/selfie tetap disimpan private dan diakses melalui otorisasi.
- Watermark bukan jaminan anti-spoofing.

## 9. Audit dan keamanan

- Semua event absensi asli tetap dipertahankan.
- Semua finalisasi, koreksi, pengecualian, pembatalan, dan persetujuan dicatat dengan aktor, timestamp, alasan/catatan, serta nilai lama dan baru.
- Backend menjadi sumber kebenaran untuk lock/unlock clock-out dan otorisasi; UI hanya mencerminkan state.
- Semua operasi transisi state penting menggunakan transaksi dan row lock.
- Reviewer tidak dapat memproses catatan miliknya sendiri.
