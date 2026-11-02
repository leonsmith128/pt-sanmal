# Audit UI P1-A dan P1-B

Basis audit: branch `main`, commit `0298a8f3fa3725eafb8fd7112f3ffe41cff033fe`.

## Tujuan dan prinsip responsivitas

Menstandardisasi container, spacing, dan page header tanpa mengubah aturan bisnis, query, validasi, route, otorisasi, atau perilaku filter.

SanMal harus nyaman digunakan pada ponsel, tablet/iPad, dan desktop. Pendekatan responsif menggunakan mobile-first CSS: layout dasar untuk layar sempit, lalu ditingkatkan pada breakpoint `sm`, `md`, dan `lg` sesuai kebutuhan. Jangan hanya menguji dua ekstrem (ponsel dan desktop); tablet dalam orientasi portrait/landscape juga harus diperiksa.

Halaman pegawai tetap menjadi mobile-first secara eksplisit, terutama alur absensi. P1-A/B saat ini memusatkan perubahan pada kerangka halaman admin; standar ini tidak boleh memaksakan pola desktop ke halaman pegawai. Pekerjaan mobile-first halaman pegawai dan kamera/GPS tetap berada pada tahap P1-E/P1-F.

## Komponen yang sudah tersedia

Di `resources/views/components/admin/` sudah ada:

- `x-admin.page-header`
- `x-admin.filter-panel`
- `x-admin.empty-state`
- `x-admin.flash-message`
- `x-admin.request-status-badge`

Kontrak `x-admin.page-header` saat ini menerima `title`, `description` opsional, dan default slot untuk aksi. Ini cukup untuk pola umum. Kebutuhan tombol kembali dan beberapa aksi dapat ditangani melalui slot; jangan menambah prop/API baru sebelum ada kebutuhan yang tidak dapat dipenuhi slot.

## Inventaris audit halaman admin

Semua 22 file Blade di `resources/views/pages/admin/` sudah diinventarisasi pada main.

| Halaman | Container/spacing | Header dan catatan |
|---|---|---|
| Kehadiran Pegawai | `max-w-7xl`, `gap-5` | Sudah memakai `x-admin.page-header`; filter panel; acuan awal P1-A/B |
| Rekap Harian | `max-w-7xl`, `space-y-6 p-6` | Header custom; dua form terpisah dalam panel yang sama |
| Proyek (daftar) | `max-w-7xl`, `gap-5` | Header custom; filter GET |
| Proyek (create/edit) | `max-w-3xl`, `space-y-5` | Judul/deskripsi custom; form bersama |
| Client (daftar) | `max-w-7xl`, `gap-5` | Header custom; pencarian; daftar Client dan Office |
| Client (create/edit) | `max-w-2xl`, `space-y-5` | Judul/deskripsi custom; form |
| Client (detail) | `max-w-6xl`, `gap-5` | Header custom dengan kembali, identitas, dan dua aksi |
| Pegawai (daftar) | `max-w-6xl`, `gap-5` | Header custom; filter GET; tabel overflow-x |
| Pegawai (create/edit) | `max-w-2xl`, `space-y-5` | Judul/deskripsi custom; form |
| Lokasi (create/edit) | `max-w-2xl`, `space-y-5` | Judul/deskripsi custom; form dengan grid field |
| Koreksi Absensi (daftar) | `max-w-6xl`, `space-y-5` | Header custom; filter satu baris |
| Koreksi Absensi (detail) | `max-w-3xl`, `space-y-5` | Header/detail dan form keputusan |
| Audit Log (daftar) | `max-w-7xl`, `space-y-5` | Header custom; filter GET |
| Persetujuan Permohonan (daftar) | `max-w-6xl`, `space-y-6 p-6` | Sudah memakai `x-admin.page-header`; filter dalam panel |
| Persetujuan Permohonan (detail) | `max-w-3xl`, `space-y-6 p-6` | Header custom; konten detail dan form keputusan |
| Lembur (daftar) | `max-w-6xl`, `gap-6` | Header custom; daftar berbentuk list/card |
| Lembur (detail) | `max-w-3xl`, `gap-6` | Header custom; banyak panel dan form tindakan |
| Lokasi | Create/edit tersedia; halaman daftar/detail belum ditemukan dalam folder admin | Verifikasi route/struktur bila ada halaman lain di luar folder ini |

Catatan: inventaris ini mendeskripsikan markup saat ini, bukan penilaian bahwa setiap variasi pasti salah. Form pendek dan halaman detail padat memang dapat membutuhkan lebar berbeda.

## Temuan utama

1. Daftar utama tidak seragam: beberapa menggunakan `max-w-7xl`, sementara Pegawai, Koreksi Absensi, Permohonan, dan Lembur menggunakan `max-w-6xl`.
2. Form create/edit cenderung memakai lebar sempit (`max-w-2xl` atau `max-w-3xl`), yang masuk akal; perlu menjaga konsistensi antarform dengan kompleksitas sebanding.
3. Padding lokal `p-6` pada Rekap Harian dan halaman permohonan berpotensi menghasilkan padding ganda tergantung layout induk. Layout saat ini membungkus slot dengan wrapper pegawai-only bottom padding, tetapi tidak menambahkan padding horizontal global.
4. `x-admin.page-header` telah dipakai pada Kehadiran dan Persetujuan Permohonan, sementara halaman lain menulis header sendiri.
5. Header custom memakai variasi `flex`, `flex-wrap`, `items-center`, dan `items-start`; aksi berpotensi tidak konsisten pada viewport sempit/tablet.
6. Form filter beberapa halaman memakai `flex` tanpa pembungkus panel atau lebar input yang konsisten. Ini masuk P1-C untuk filter; pada P1-A/B jangan mengubah perilaku maupun desain filter.
7. Tabel Pegawai dan Permohonan menggunakan horizontal overflow. Perilaku tabel mobile akan diaudit pada P1-D, bukan diubah dalam P1-A/B.
8. Komponen header tidak boleh membuat aksi terpotong atau menyebabkan horizontal overflow pada ponsel maupun tablet.

## Standar yang diusulkan

### Container

| Jenis halaman | Standar |
|---|---|
| Dashboard dan daftar admin | `w-full max-w-7xl mx-auto` |
| Form create/edit | `w-full max-w-2xl` atau `max-w-3xl`, menurut jumlah field dan kebutuhan baca |
| Detail sederhana | `w-full max-w-3xl` sampai `max-w-5xl` |
| Detail padat/multi-panel | `w-full max-w-6xl` |
| Halaman dengan tabel lebar | Container `max-w-7xl`; overflow ditangani oleh area tabel, bukan seluruh halaman |

Lebar di atas adalah batas maksimum; container harus tetap `w-full` agar mengisi ruang pada ponsel dan tablet tanpa memaksa lebar minimum.

### Padding dan spacing

- Gunakan pola responsif `px-4 py-4 sm:px-6 sm:py-6` hanya jika halaman memang memiliki padding lokal.
- Hindari menambahkan padding ke semua halaman jika layout induk atau komponen konten sudah mengaturnya.
- Untuk susunan vertikal halaman, gunakan satu pola konsisten pada halaman sejenis; `gap-5` atau `space-y-5` dapat dipakai, tanpa mengubah jarak internal card/form.
- Pastikan tidak ada fixed width yang menyebabkan overflow pada 320px, 375px, 768px, dan lebar desktop.

### Page header

Kontrak `x-admin.page-header` yang diusulkan:

- `title`: wajib, satu judul utama halaman.
- `description`: opsional, satu kalimat konteks.
- default slot: aksi halaman, bisa kosong atau berisi satu/beberapa kontrol.
- Layout mobile: judul/deskripsi di atas, aksi berada di bawah dan dapat wrap; tidak ada pemaksaan satu baris.
- Layout tablet/desktop: judul di kiri dan aksi di kanan jika ruang cukup.
- Judul harus dapat wrap; teks deskripsi tidak boleh mendorong aksi keluar viewport.
- Tombol kembali tetap berupa bagian dari slot jika dibutuhkan, bukan prop baru.
- Gunakan heading level 1 secara konsisten.

### Breakpoint dan validasi visual

Pemeriksaan minimal:
- Ponsel kecil: 320–375px.
- Ponsel umum: 390–430px.
- Tablet portrait: sekitar 768px.
- Tablet landscape: sekitar 1024px.
- Desktop: 1280px ke atas.

Pada tablet, jangan langsung menerapkan layout desktop penuh jika ruang belum cukup. Gunakan wrap/stack berdasarkan kebutuhan konten. Periksa khusus header dengan beberapa tombol, container form, dan area yang berpotensi melebar.

## Rencana implementasi dalam PR ini

1. Selesaikan kontrak dan perilaku responsif `x-admin.page-header`.
2. Gunakan halaman Kehadiran Pegawai sebagai halaman acuan P1-A/B karena sudah memakai komponen header dan filter panel.
3. Terapkan container `w-full max-w-7xl mx-auto` tanpa menambah padding ganda.
4. Verifikasi desktop, tablet portrait/landscape, dan mobile.
5. Setelah pola acuan disetujui, terapkan ke halaman daftar admin lainnya.
6. Selanjutnya rapikan container create/edit/detail sesuai jenis halaman; jangan menyamakan semua ukuran.

## Batas cakupan PR

Termasuk:
- Audit semua halaman admin daftar, detail, create, dan edit.
- Menetapkan dan menerapkan kontrak page header.
- Standardisasi container dan spacing pada halaman acuan dan halaman admin yang disepakati.
- Perbaikan responsivitas header/container pada ponsel, tablet, dan desktop.

Tidak termasuk:
- Perubahan query, filter, validasi, route, otorisasi, atau aturan bisnis.
- Perombakan filter (P1-C) dan tabel (P1-D).
- Refaktor UX kamera/GPS (P1-F).
- Perombakan halaman pegawai mobile-first pada P1-E.
- Pembuatan komponen generik yang belum terbukti diperlukan.

## Kriteria penerimaan

- Container sesuai jenis halaman, full-width pada layar kecil, dan dibatasi max-width pada layar besar.
- Page header konsisten, dapat wrap, dan tidak overflow pada mobile/tablet.
- Form dan halaman detail tetap memiliki lebar sesuai kepadatan konten.
- Halaman acuan diperiksa pada ponsel, tablet portrait/landscape, dan desktop.
- Tidak ada perubahan perilaku bisnis, query, route, validasi, atau filter.
- Test terkait lulus dan CI PR hijau.
- Review visual dilakukan sebelum pola diterapkan ke seluruh halaman.

## Catatan audit

Audit dilakukan pada seluruh 22 file Blade di direktori halaman admin, komponen admin terkait, dan layout utama. Beberapa route master Location belum memiliki halaman daftar/detail pada direktori ini; keberadaannya perlu diverifikasi terhadap route sebelum menyatakan inventaris UI seluruh modul admin benar-benar lengkap. Standar di atas merupakan baseline implementasi yang responsif, bukan aturan untuk membuat semua halaman identik.
