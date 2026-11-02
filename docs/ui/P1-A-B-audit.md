# Audit UI P1-A dan P1-B

Basis audit: branch `main`, commit `0298a8f3fa3725eafb8fd7112f3ffe41cff033fe`.

## Tujuan

Menyiapkan standardisasi container, spacing, dan page header untuk halaman admin tanpa mengubah aturan bisnis, query, validasi, route, atau perilaku filter.

## Komponen yang sudah tersedia

Di `resources/views/components/admin/` sudah ada:

- `x-admin.page-header`
- `x-admin.filter-panel`
- `x-admin.empty-state`
- `x-admin.flash-message`
- `x-admin.request-status-badge`

`page-header` saat ini menerima `title`, `description` opsional, dan slot untuk aksi. Ini cukup untuk header sederhana, tetapi belum menyediakan pola eksplisit untuk navigasi kembali atau beberapa aksi. Jangan menambah API komponen sebelum kebutuhan halaman-halaman terkait dipetakan.

## Temuan container dan spacing

| Halaman | Container saat ini | Catatan |
|---|---|---|
| Kehadiran Pegawai | `max-w-7xl`, `gap-5` | Sudah memakai page-header dan filter-panel |
| Rekap Harian | `max-w-7xl`, `space-y-6 p-6` | Padding lokal berbeda; header dan dua form filter/ekspor berada dalam satu section |
| Proyek (daftar) | `max-w-7xl`, `gap-5` | Header custom |
| Client (daftar) | `max-w-7xl`, `gap-5` | Header custom |
| Pegawai (daftar) | `max-w-6xl`, `gap-5` | Lebar berbeda dari daftar utama lain |
| Koreksi Absensi (daftar) | `max-w-6xl`, `space-y-5` | Header custom; filter satu baris |
| Proyek (create/edit) | `max-w-3xl`, `space-y-5` | Pola form konsisten satu sama lain |
| Client (create) | `max-w-2xl`, `space-y-5` | Form lebih sempit |
| Pegawai (create) | Tidak ada wrapper max-width; form langsung | Form memakai card/padding sendiri |
| Client (detail) | `max-w-6xl`, `gap-5` | Header memuat tombol kembali, identitas, dan dua aksi |

Perbedaan lebar form dan detail tidak otomatis salah. Lebar perlu disesuaikan dengan kepadatan field dan konten. Temuan yang perlu diselesaikan adalah konsistensi berdasarkan jenis halaman, bukan menyeragamkan seluruh halaman ke satu angka.

## Temuan page header

- Kehadiran Pegawai menggunakan `x-admin.page-header`.
- Rekap Harian, Proyek, Client, Pegawai, dan Koreksi Absensi masih memiliki markup header sendiri.
- Sebagian header hanya judul/deskripsi, sedangkan Client detail juga memuat tombol kembali dan beberapa aksi.
- Header daftar umumnya menggunakan `flex flex-wrap`, tetapi alignment dan margin deskripsi belum seragam.
- Komponen page-header yang ada sudah mendukung slot aksi; perlu diverifikasi apakah kebutuhan tombol kembali sebaiknya tetap berada di slot atau membutuhkan API tambahan.

## Standar awal untuk dibahas dan diterapkan

- Dashboard dan halaman daftar: `max-w-7xl`.
- Form tambah/edit: rentang `max-w-2xl` sampai `max-w-3xl`, sesuai panjang dan kompleksitas form.
- Halaman detail: rentang `max-w-5xl` sampai `max-w-6xl`, sesuai kepadatan konten.
- Padding halaman: `p-4 sm:p-6` jika halaman memerlukan padding lokal; hindari padding ganda dari layout dan halaman.
- Jarak vertikal: pilih satu pola untuk halaman sejenis; `gap-5` atau `space-y-5` menjadi kandidat, bukan aturan mutlak.
- Header halaman admin memakai `x-admin.page-header` untuk pola umum judul, deskripsi opsional, dan slot aksi. Kebutuhan tombol kembali ditetapkan setelah seluruh header relevan diaudit.

## Batas cakupan PR

Termasuk:

- Audit lanjutan halaman admin daftar, detail, create, dan edit.
- Menetapkan kontrak komponen page header yang sesuai kebutuhan nyata.
- Menyelaraskan container dan spacing untuk kelompok halaman admin yang disepakati.
- Mengganti header custom yang setara dengan komponen bersama jika tidak mengubah perilaku.

Tidak termasuk:

- Perubahan query, filter, validasi, route, otorisasi, atau aturan bisnis.
- Perombakan tabel dan filter (P1-C/P1-D).
- Refaktor UX kamera/GPS.
- Pembuatan komponen generik yang belum terbukti diperlukan.

## Kriteria penerimaan

- Halaman yang termasuk cakupan menggunakan standar container dan spacing sesuai jenis halaman.
- Header halaman memiliki hierarki judul/deskripsi/aksi yang konsisten dan responsif.
- Form dan halaman detail tetap memiliki lebar yang sesuai kebutuhan konten.
- Tidak ada perubahan perilaku bisnis, query, route, atau validasi.
- Test terkait lulus dan CI PR hijau.
- Tampilan diperiksa pada viewport desktop dan mobile.

## Catatan audit

Audit dilakukan terhadap layout utama, komponen admin, halaman admin daftar utama, serta sampel create/edit/detail. Cakupan halaman admin lain masih perlu ditelusuri sebelum implementasi final. Standar di atas adalah baseline untuk review, bukan keputusan final yang memaksa semua halaman memiliki struktur identik.
