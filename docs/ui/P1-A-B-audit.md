# Audit UI P1-A dan P1-B

Basis audit: branch `main`, commit `0298a8f3fa3725eafb8fd7112f3ffe41cff033fe`.

## Tujuan

Menyiapkan standardisasi container, spacing, dan page header untuk halaman admin tanpa mengubah aturan bisnis, query, validasi, route, atau perilaku filter.

## Temuan awal

Komponen admin yang sudah tersedia:

- `x-admin.page-header`
- `x-admin.filter-panel`
- `x-admin.empty-state`
- `x-admin.flash-message`
- `x-admin.request-status-badge`

Penerapan saat ini belum konsisten:

| Halaman | Container | Header |
|---|---|---|
| Kehadiran Pegawai | `max-w-7xl`, `gap-5` | `x-admin.page-header` |
| Rekap Harian | Wrapper khusus; perlu audit detail lanjutan | Pola khusus |
| Proyek | `max-w-7xl`, `gap-5` | Markup custom |
| Client | `max-w-7xl`, `gap-5` | Markup custom |
| Pegawai | `max-w-6xl`, `gap-5` | Markup custom |

Perbedaan lebar pada halaman Pegawai belum memiliki alasan UX yang terdokumentasi. Header Proyek, Client, dan Pegawai memiliki struktur yang mirip tetapi belum menggunakan komponen bersama.

## Standar awal untuk dibahas dan diterapkan

- Dashboard dan halaman daftar: `max-w-7xl`.
- Form tambah/edit: `max-w-3xl`.
- Halaman detail: `max-w-5xl`.
- Padding halaman: `p-4 sm:p-6` jika halaman memerlukan padding lokal.
- Jarak vertikal konten: konsisten; `gap-5` menjadi kandidat awal.
- Header halaman admin menggunakan `x-admin.page-header` dengan judul, deskripsi opsional, dan slot aksi.

Standar ini merupakan baseline awal. Penerapan akhir harus mempertimbangkan kebutuhan tiap halaman dan tidak boleh memaksakan lebar atau struktur yang merusak keterbacaan.

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

- Halaman yang termasuk cakupan menggunakan standar container dan spacing yang konsisten.
- Header halaman memiliki hierarki judul/deskripsi/aksi yang konsisten dan responsif.
- Form dan halaman detail tetap dapat menggunakan lebar yang lebih sempit sesuai kebutuhan.
- Tidak ada perubahan perilaku bisnis, query, route, atau validasi.
- Test terkait lulus dan CI PR hijau.
- Tampilan diperiksa pada viewport desktop dan mobile.

## Catatan audit

Audit ini merupakan audit awal dari file layout, komponen admin, dan halaman daftar utama. Detail halaman create/edit/show serta seluruh halaman admin lain perlu diperiksa sebelum implementasi final agar standar tidak ditetapkan hanya dari sampel halaman daftar.
