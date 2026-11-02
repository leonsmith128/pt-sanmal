# P1-D2 — Implementasi Expandable Card Master Proyek

## Keputusan tampilan

Halaman Master Proyek menggunakan expandable card sebagai pola utama pada desktop dan mobile. Keputusan ini didasarkan pada fungsi halaman sebagai pemantauan proyek, bukan penelusuran absensi individu. Rincian kehadiran pegawai dicari melalui Rekap Absensi menggunakan filter Proyek dan filter absensi terkait.

## Ringkasan collapsed

Informasi yang terlihat tanpa membuka card:
1. Nama proyek.
2. Status proyek.
3. Kode proyek.
4. Jenis layanan di samping kode proyek.
5. Nama Client.
6. Nama Site.
7. Peringatan jika Site nonaktif.
8. Jumlah record absensi, termasuk angka 0.

## Detail expandable

- Periode mulai.
- Periode selesai.
- Status Site dalam bentuk teks.

Tidak ada daftar pegawai atau rincian event absensi di card proyek.

## Interaksi

- Semua card default collapsed.
- Setiap card membuka/menutup secara independen menggunakan elemen native `details/summary`.
- Tombol Ubah Proyek selalu tersedia di luar area toggle, termasuk saat card collapsed.
- Ringkasan dan detail menggunakan koleksi `$projects` yang sama.
- Card ditampilkan dalam satu kolom pada mobile dan dua kolom mulai breakpoint `md`.
- Filter GET, nama parameter, query, pagination, route edit, dan otorisasi tidak diubah.

## Kepadatan halaman

- Container memakai `max-w-7xl` dan `gap-4`.
- Filter panel memakai padding 12px dan kontrol kecil.
- Card memakai padding 12px dan jarak antarkartu 12px.
- Teks panjang nama proyek, Client, dan Site diperbolehkan wrap.

## Validasi

Feature test memverifikasi:
- Render expandable card.
- Identitas proyek dan jenis layanan.
- Client dan Site.
- Jumlah absensi nol tetap ditampilkan.
- Detail periode dan aksi edit tetap tersedia.

Pengujian visual manual tetap diperlukan pada mobile, tablet portrait/landscape, dan desktop. CI belum dijalankan pada tahap ini sesuai keputusan penundaan validasi CI.
