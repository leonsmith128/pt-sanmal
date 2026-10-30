# Arsitektur View SanMal

Dokumen ini menjadi pedoman penempatan halaman dan pemilihan pola UI untuk developer SanMal.

## Prinsip utama

1. **Blade + Controller adalah pilihan default.** Gunakan untuk halaman yang menampilkan data atau memproses form melalui request HTTP biasa.
2. **Livewire Volt digunakan jika interaksi dinamis memang diperlukan.** Contohnya state yang berubah tanpa reload, filter interaktif, pagination dinamis, dan beberapa aksi server pada satu halaman.
3. **Livewire class-based bukan default kedua.** Gunakan hanya ketika komponen Volt terbukti terlalu kompleks dan pemisahan class PHP dari template membuat alur lebih mudah dipahami.
4. Jangan mengonversi halaman hanya karena file panjang. Evaluasi tanggung jawab, keterkaitan state, dan kemudahan menelusuri alur.
5. Refactoring struktur tidak boleh mengubah aturan bisnis, otorisasi, validasi, atau perilaku halaman tanpa pekerjaan dan pengujian terpisah.

## Struktur direktori

```text
resources/views/
├── pages/
│   ├── admin/       # Halaman khusus admin/superadmin
│   ├── employee/    # Halaman khusus pegawai
│   ├── auth/        # Halaman autentikasi Fortify
│   ├── settings/    # Halaman pengaturan akun
│   └── dashboard.blade.php # Dashboard bersama berdasarkan role
├── components/      # Komponen Blade reusable
├── layouts/         # Kerangka halaman
├── partials/        # Potongan tampilan pendukung
└── flux/            # View pendukung Flux; jangan pindahkan tanpa alasan
```

Gunakan namespace view `pages::` untuk halaman di dalam `resources/views/pages`.

## Memilih pola halaman

### Blade + Controller

Pilih pola ini jika:
- halaman terutama menampilkan data yang disiapkan controller;
- form dapat dikirim dengan request biasa;
- tidak ada kebutuhan state server yang harus diperbarui secara dinamis.

Controller menangani request, otorisasi, validasi, dan menyiapkan data. Blade bertanggung jawab pada presentasi.

### Livewire Volt

Pilih Volt jika:
- halaman membutuhkan state server yang interaktif;
- filter, sorting, atau pagination perlu memperbarui tampilan tanpa reload;
- aksi server dan beberapa state memang menjadi satu pengalaman UI.

Jaga komponen Volt tetap fokus pada orkestrasi UI. Pindahkan operasi bisnis yang dapat digunakan ulang ke Action/Service yang sesuai, dan pindahkan operasi browser seperti kamera/GPS ke JavaScript. Ekstrak markup berulang menjadi Blade Component.

### Livewire class-based

Pertimbangkan pola ini jika:
- jumlah state dan method saling berkaitan sehingga file Volt sulit ditelusuri;
- lifecycle atau logika komponen sudah kompleks;
- class PHP dan template terpisah membuat tanggung jawab lebih jelas.

Keputusan harus didasarkan pada keterbacaan dan pemeliharaan, bukan batas jumlah baris semata.

## Penamaan dan referensi

- Halaman admin berada di `pages/admin/`.
- Halaman pegawai berada di `pages/employee/`.
- Komponen Volt mengikuti lokasi file. Contoh: `pages::employee.attendance`.
- Controller menggunakan nama view lengkap, misalnya `view('pages::employee.attendance-history.index')`.
- Saat memindahkan halaman, periksa controller, route Volt, Fortify/provider, test Livewire, dan semua referensi view.
- Nama URL dan route publik tidak perlu berubah hanya karena file view dipindahkan.

## Keputusan untuk halaman V1

| Halaman | Pola |
|---|---|
| Dashboard | Blade + Controller |
| Riwayat absensi pegawai | Blade + Controller |
| Pusat permohonan dan form cuti/izin | Blade + Controller |
| Daftar lembur pegawai | Blade + Controller |
| CRUD pegawai admin | Blade + Controller |
| Approval cuti/lembur dan koreksi absensi | Blade + Controller |
| Audit log dan rekap harian | Blade + Controller |
| Absensi pegawai | Volt |
| Kehadiran admin | Volt |
| Profil pegawai | Volt |
| Keamanan akun | Volt |

## Checklist saat menambah halaman

1. Tentukan role pemilik halaman: admin, employee, atau bersama.
2. Mulai dari Blade + Controller.
3. Pilih Volt hanya jika kebutuhan interaksi dinamis membenarkannya.
4. Pisahkan operasi bisnis ke Action/Service bila tanggung jawabnya jelas dan reusable.
5. Gunakan Blade Component untuk markup yang benar-benar berulang.
6. Tambahkan atau perbarui feature test untuk akses role, validasi, dan perilaku utama.
