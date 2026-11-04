# Employee Attendance V1 — Page Layout

> Dokumen ini menjelaskan komposisi UI halaman absensi pegawai berdasarkan source pada branch UI/UX V1. Status test dan browser/device QA terkini hanya dicatat di [Implementation Status](progress/IMPLEMENTATION-STATUS.md) dan [Visual QA Log](progress/VISUAL-QA-LOG.md).

## 1. Tujuan halaman

Halaman ini adalah workflow utama pegawai untuk melihat ringkasan kehadiran tanggal terpilih dan melakukan Clock In, Clock Out, atau konfirmasi mulai lembur. Prioritasnya mobile-first, dengan satu alur tindakan yang mudah ditemukan.

## 2. Urutan konten

Urutan aktual pada halaman:

1. **Header pegawai** — avatar inisial, nama, nomor pegawai, jabatan, dan pintasan notifikasi.
2. **Kalender kehadiran** — weekly calendar sebagai navigasi utama dan monthly picker untuk berpindah ke tanggal lain. Detail perilaku ada di [Employee Attendance Calendar V1](EMPLOYEE-ATTENDANCE-CALENDAR-V1.md).
3. **Ringkasan jam** — tiga nilai: Masuk, Pulang, dan Total kerja.
4. **Status kehadiran dan lembur** — dua info card dalam satu grid; satu kolom pada layar kecil dan dua kolom mulai breakpoint `sm`.
5. **Form absensi** — satu form untuk jenis lokasi kerja, pemilihan kantor/proyek jika relevan, kamera, preview foto, pesan validasi, dan tombol aksi utama.
6. **Modal pendukung** — konfirmasi absensi, detail lembur, dan pengajuan lembur.

Jangan memindahkan tombol aksi utama keluar dari form atau menjadikannya sticky/fixed. Halaman menggunakan document flow normal agar konten tetap dapat digulir ketika preview foto atau konten lain bertambah.

## 3. Kalender dan tanggal terpilih

- Weekly calendar dan monthly picker berbagi state `selectedDate` yang dimiliki parent Livewire SFC.
- Pemilihan tanggal memperbarui ringkasan/status yang berasal dari data tanggal tersebut.
- Form absensi disembunyikan ketika tanggal terpilih bukan tanggal hari ini dalam konteks WITA yang digunakan halaman.
- Jangan mengartikan tanggal tanpa record sebagai ketidakhadiran. Ikuti klasifikasi dari service dan spesifikasi kalender.

## 4. Ringkasan waktu

Komponen: `resources/views/components/attendance/time-summary.blade.php` (`x-attendance.time-summary`).

- Menampilkan Masuk, Pulang, dan Total kerja.
- Nilai waktu berasal dari event efektif yang tersedia untuk tanggal terpilih.
- Jika sesi atau pasangan event ambigu, tampilan tidak boleh mengarang durasi; pertahankan nilai kosong/status yang disediakan oleh parent.
- Komponen hanya mempresentasikan nilai yang diberikan parent. Jangan memindahkan query atau perhitungan domain ke Blade Component.

## 5. Status kehadiran dan lembur

Komponen: `resources/views/components/attendance/info-card.blade.php` (`x-attendance.info-card`).

- Card kehadiran menampilkan tanggal, status, tempat/lokasi, alamat snapshot bila tersedia, dan jam masuk/pulang bila ada.
- Card lembur menampilkan status operasional dan status persetujuan sebagai informasi yang berbeda, serta rincian jadwal/aktual/finalisasi yang tersedia.
- Grid memakai satu kolom pada mobile dan dua kolom mulai `sm`. Badge status pada header info card harus compact (teks kecil dan padding vertikal rapat), sejajar tengah dengan judul, serta tidak mengambil perhatian lebih besar daripada label.
- Tinggi minimum dan area konten fleksibel digunakan agar dua card lebih stabil ketika jumlah informasinya berbeda.
- Container card menggunakan surface token dan shadow; jangan menambahkan border pada container luar. Border internal untuk pemisah konten masih boleh bila diperlukan.

## 6. Form absensi dan tindakan utama

Form berada setelah kalender, ringkasan, dan status. Form hanya ditampilkan untuk tanggal hari ini.

### Pilihan lokasi kerja

- Clock In memilih jenis lokasi kerja: Kantor atau Onsite (Proyek).
- Jika hanya ada satu kantor aktif, nama kantor ditampilkan sebagai informasi; jika lebih dari satu, pegawai memilih kantor.
- Untuk Onsite, pegawai memilih proyek yang tersedia dan melihat ringkasan Client, lokasi Site, serta jenis layanan.
- Jangan mengubah aturan ketersediaan lokasi/proyek atau assignment sebagai pekerjaan UI.

### Mode dan tombol

Mode yang ditangani halaman: `clock_in`, `clock_out`, `overtime_start`, `overtime_locked`, dan `clock_in_blocked`.

- `overtime_locked` dan `clock_in_blocked`: tombol utama disabled dengan pesan kontekstual.
- Mode yang memerlukan foto: sebelum selfie tersedia, tombol utama membuka kamera.
- Setelah selfie tersedia, tombol utama berubah menjadi **Lanjutkan** dan memicu tahap validasi/konfirmasi. Tombol aksi utama yang membuka kamera atau melanjutkan workflow menggunakan tinggi sekitar 48 px; pertahankan lebar fleksibel yang sudah ada. Tombol navigasi biasa dan aksi sekunder tetap memakai ukuran standar.
- Submit final dilakukan setelah pegawai mengonfirmasi melalui modal; validasi server tetap menjadi sumber keputusan.
- Jangan menambah tombol kamera sekunder yang menduplikasi tombol utama.

## 7. Kamera dan photo preview

- `x-attendance.camera` bertanggung jawab atas capture kamera, GPS, dan watermark. Jangan memindahkan tanggung jawab tersebut ke photo-preview.
- `x-attendance.photo-preview` adalah komponen terpisah. Komponen hanya tampil ketika selfie tersedia dan dapat dipreview.
- Thumbnail membuka modal foto ukuran penuh yang terpusat, mempertahankan rasio gambar dengan `object-contain`, dan dibatasi ukuran viewport.
- Modal menyediakan tombol tutup, penutupan melalui backdrop dan Escape, serta tautan **Unduh** untuk file sementara yang sama dengan foto preview.
- Aksi **Foto ulang** tetap tersedia dan memanggil action parent untuk membersihkan selfie serta snapshot terkait.
- Jangan mengganti `object-contain` menjadi `object-cover` untuk modal foto penuh.
- Preview tidak menggantikan konfirmasi server dan tidak boleh dianggap sebagai bukti bahwa upload/pencatatan sudah selesai.

## 8. Batas arsitektur

- Parent: `resources/views/pages/employee/⚡attendance.blade.php` — satu Native Livewire 4 SFC yang memiliki state tanggal, mode, form, dan action halaman.
- Presentational Blade Components: `calendar`, `calendar-indicator`, `time-summary`, `info-card`, dan `photo-preview`.
- Camera: `x-attendance.camera` tetap menjadi komponen terpisah karena memiliki lifecycle browser untuk kamera/GPS.
- `AttendanceCalendarService` memetakan status harian ke indikator ringkas; klasifikasi domain tetap berada di `DailyAttendanceStatusService`.
- Actions yang sudah ada tetap menangani pencatatan absensi dan konfirmasi lembur. Jangan membuat nested Livewire component hanya untuk memecah markup.

## 9. Kontrak yang tidak boleh berubah dalam pekerjaan UI

Pertahankan route, authorization, validasi, property/action/event Livewire, query, timezone snapshot, Clock In/Out, status lembur, kamera, GPS, watermark, upload, bukti historis, dan alur konfirmasi. Jika perbaikan visual membutuhkan perubahan salah satu kontrak tersebut, hentikan pekerjaan dan minta scope terpisah.

## 10. Checklist review

- [ ] Urutan header → kalender → ringkasan → status → form tetap terjaga.
- [ ] Form tidak tampil untuk tanggal historis.
- [ ] Grid kalender tidak menyebabkan horizontal overflow.
- [ ] Ringkasan jam tetap terbaca pada lebar 360–390 px.
- [ ] Status kehadiran dan lembur tetap terbaca pada satu dan dua kolom.
- [ ] Tombol aksi tetap berada di bagian akhir form dalam document flow normal.
- [ ] Modal foto tidak terpotong dan mempertahankan rasio gambar.
- [ ] Unduh dan Foto ulang tersedia ketika selfie previewable.
- [ ] Mode disabled, loading, error, dan empty diperiksa.
- [ ] Browser/device QA serta bukti aktual dicatat di [Visual QA Log](progress/VISUAL-QA-LOG.md).
