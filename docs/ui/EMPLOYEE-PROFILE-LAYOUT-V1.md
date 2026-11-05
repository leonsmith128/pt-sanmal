# Employee Profile V1 — Page Layout

> Spesifikasi tampilan halaman Profil Pegawai. Kontrak upload dan data tetap dimiliki Employee Profile Livewire SFC. Status test dan browser QA dicatat di [Implementation Status](progress/IMPLEMENTATION-STATUS.md) dan [Visual QA Log](progress/VISUAL-QA-LOG.md).

## 1. Tujuan

Memberi pegawai kontrol atas foto profil dan nomor telepon, sementara data identitas/kepegawaian resmi tetap dikelola Admin.

## 2. Susunan halaman

1. Header halaman Profil Pegawai.
2. Section foto profil: avatar, nama, email, input file, feedback validasi, Simpan foto, dan Hapus foto jika foto tersimpan.
3. Section Informasi pribadi: nomor telepon yang dapat diedit dan metadata kepegawaian yang hanya ditampilkan.

Tidak perlu deskripsi tambahan yang mengulang fungsi field atau helper text permanen yang mengulang batas file. Batas ukuran dan format disampaikan melalui pesan validasi ketika upload gagal.

## 3. Foto profil

- Gunakan `x-ui.avatar` untuk avatar bulat dengan ukuran XL yang sudah digunakan pada halaman ini.
- Jika foto tersedia, avatar menjadi trigger `x-ui.photo-preview`; klik membuka dialog pratinjau ukuran penuh.
- Dialog memakai komponen generik `x-ui.photo-preview` yang juga digunakan oleh wrapper `x-attendance.photo-preview`.
- Preview file sementara menggunakan `temporaryUrl()`; setelah tersimpan, gunakan `User::profilePhotoUrl()`.
- Foto disajikan melalui endpoint terautentikasi `profile-photo.show` yang membaca disk `public` secara langsung. Ini menghindari ketergantungan tampilan avatar pada symbolic link `public/storage`.
- Jangan mengubah aturan penyimpanan, penggantian file lama, penghapusan foto, atau batas validasi upload tanpa scope terpisah.

## 4. Informasi pribadi

- Nomor telepon tetap editable oleh pegawai.
- Nomor pegawai, jabatan, status kepegawaian, dan tanggal bergabung tetap read-only.
- Hapus subheading yang hanya mengulang bahwa nomor telepon dapat diperbarui sendiri.
- Data identitas dan kepegawaian tetap dikelola Admin.

## 5. Kontrak yang harus dipertahankan

- Property Livewire `phone` dan `photo`.
- Action `saveContactDetails`, `saveProfilePhoto`, dan `removeProfilePhoto`.
- Validasi nomor telepon serta foto image JPG/JPEG/PNG/WebP maksimal 2048 KB.
- Penyimpanan di disk `public`, penggantian file lama, dan penghapusan file lama setelah penggantian berhasil.
- Toast feedback dan error bag Livewire.
- Authorization halaman profil yang sudah ada.

## 6. QA checklist

- [ ] Avatar tetap bulat pada foto portrait, landscape, dan square.
- [ ] Foto tersimpan dapat dimuat dari endpoint profile-photo.show.
- [ ] Klik avatar membuka modal foto ukuran penuh.
- [ ] Modal dapat ditutup dengan tombol, backdrop, dan Escape.
- [ ] Upload foto sementara dapat dipreview sebelum disimpan.
- [ ] File terlalu besar menampilkan pesan validasi tanpa helper text permanen.
- [ ] File bukan gambar atau format yang tidak didukung ditolak.
- [ ] Penggantian dan penghapusan foto tetap bekerja.
- [ ] Browser QA mobile/desktop dan light/dark dicatat.