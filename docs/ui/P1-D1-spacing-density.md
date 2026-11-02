# P1-D1 — Standar Spacing dan Density Halaman Admin

## Tujuan dan cakupan

P1-D1 menetapkan baseline visual untuk halaman pemantauan/list admin. Prioritasnya adalah konsistensi ruang dan efisiensi area layar, bukan mengecilkan semua komponen secara seragam.

Cakupan tahap awal:
- Halaman daftar/pemantauan seperti Proyek, Pegawai, Client, Kehadiran, Audit Log, Rekap Absensi, daftar permohonan dan koreksi.
- Form dan halaman detail tidak menjadi target compact redesign pada tahap ini. Keduanya tetap mengikuti bahasa spacing yang sama, tetapi mempertahankan ruang yang mendukung pengisian dan pembacaan.

## Baseline spacing

Nilai berikut adalah baseline awal berbasis utility Tailwind yang sudah digunakan di proyek. Pengecualian diperbolehkan jika kepadatan data atau interaksi memang membutuhkannya.

| Elemen | Baseline | Catatan |
|---|---|---|
| Container halaman | `w-full max-w-7xl` | Lebar penuh dengan batas konsisten; halaman kompleks boleh memakai batas lebih sempit bila ada alasan. |
| Jarak vertikal antarbagian halaman | `gap-4` (16px) | Gunakan `gap-5` (20px) bila ada pemisahan visual yang memang diperlukan. Hindari gap besar berulang. |
| Header halaman | Komponen `x-admin.page-header` | Deskripsi `mt-1`; jarak slot aksi `gap-2`. |
| Panel filter | `p-3` (12px) | Kontrol berukuran kecil bila sesuai; jarak kontrol `gap-2` atau `gap-3`. |
| Card/list pemantauan | `p-3` atau `p-4` | Gunakan padding paling kecil yang tetap memberi batas visual dan area interaksi nyaman. |
| Isi tabel padat | `px-3 py-2` | Hindari padding sel besar yang menambah tinggi baris tanpa manfaat. |
| Label dan nilai | `gap-1` | Jaga hierarki dan keterbacaan; jangan mengandalkan whitespace besar. |
| Pagination | Jarak dekat dengan daftar | Pertahankan ruang cukup untuk navigasi dan status pagination. |

## Prinsip density

1. Kurangi whitespace dekoratif yang tidak membantu pemisahan informasi.
2. Jangan mengurangi ukuran teks penting hanya untuk memadatkan tampilan.
3. Target interaksi tetap mudah disentuh, khususnya pada layar sentuh.
4. Informasi sekunder boleh memakai ukuran dan warna teks sekunder, tetapi harus tetap terbaca.
5. Jangan menyembunyikan status, peringatan, atau aksi penting demi density.
6. Jangan membuat scroll horizontal sebagai solusi pertama jika struktur informasi dapat disusun lebih baik.
7. Halaman Kehadiran memerlukan audit khusus karena tabelnya memuat bukti dan beberapa aksi per record; compact tidak boleh mengorbankan akses foto, GPS, timestamp, atau koreksi.

## Aturan responsive

- Mobile: prioritaskan ringkasan dan susunan vertikal; hindari ruang horizontal yang terbuang.
- Tablet portrait: adaptif terhadap lebar efektif dan kepadatan konten.
- Tablet landscape dan desktop: manfaatkan lebar untuk pemindaian data, tanpa memperbesar jarak kosong secara otomatis.
- Breakpoint ditentukan berdasarkan kebutuhan konten, bukan diterapkan seragam tanpa uji.

## Penerapan dan validasi

P1-D1 tidak melakukan refaktor global semua halaman secara otomatis. Standar diterapkan bertahap pada halaman yang sedang disentuh agar perubahan mudah diuji dan regresi mudah dilacak.

Urutan awal:
1. Proyek sebagai pilot expandable card.
2. Audit density Kehadiran secara khusus pada tahap berikutnya.
3. Pegawai dan halaman list lain setelah pola Proyek divalidasi.

Checklist:
- Header, filter, daftar, dan pagination memiliki ritme spacing konsisten.
- Tidak ada blank space besar yang tidak memberi fungsi.
- Teks, badge, dan kontrol tidak bertabrakan pada viewport kecil.
- Area aksi tetap jelas dan mudah digunakan.
- Dark mode dan empty state tetap terbaca.
