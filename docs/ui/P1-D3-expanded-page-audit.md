# P1-D3 — Audit Pola Layout, Komponen, UI/UX, dan Responsivitas Seluruh Halaman

## Status

- Tahap: audit diperluas; belum ada perubahan tampilan pada halaman selain Proyek.
- Repository: `leonsmith128/pt-sanmal`
- PR: #25
- Branch: `feature/ui-standardization-p1-ab`
- Acuan spacing/density: `docs/ui/P1-D1-spacing-density.md`
- Acuan pola card tervalidasi: `docs/ui/P1-D2-project-expandable-card.md`
- Transisi navigasi (`wire:navigate` / View Transitions API) tidak termasuk pekerjaan P1-D saat ini; dicatat untuk tahap P berikutnya.

## Tujuan

Memperluas audit dari halaman pemantauan admin ke seluruh kelompok halaman aplikasi. Hasil audit menentukan pola presentasi yang tepat per jenis halaman, bukan memaksakan satu pola card atau tabel ke semua halaman.

Audit berfokus pada:
1. Konsistensi container, header, jarak vertikal, padding, dan density.
2. Penggunaan ulang komponen Blade yang memang memiliki pola visual/interaksi sama.
3. Hierarki informasi, keterbacaan, empty state, feedback, dan kejelasan aksi.
4. Responsivitas pada mobile, tablet portrait, tablet landscape, dan desktop.
5. Perilaku form, validasi, modal, bukti, dan aksi penting agar tetap mudah digunakan.
6. Pengurangan blank space yang tidak fungsional tanpa mengecilkan teks atau target sentuh secara berlebihan.

Audit ini tidak mengubah query, state, filter, pagination, sorting, route, otorisasi, validasi, maupun aturan bisnis.

## Baseline yang dipertahankan

- Container halaman list/pemantauan mengikuti `w-full max-w-7xl` kecuali halaman berorientasi baca/form yang memiliki alasan memakai lebar lebih sempit.
- Ritme vertikal utama `gap-4`; `gap-5` hanya jika dibutuhkan.
- Header menggunakan `x-admin.page-header` pada halaman admin yang sesuai.
- Filter memakai `x-admin.filter-panel` dengan padding ringkas.
- Card/list pemantauan umumnya `p-3` atau `p-4`; form/detail boleh lebih lega jika membantu pengisian dan pembacaan.
- Tablet tidak diperlakukan sebagai mobile yang diperbesar atau desktop yang diperkecil. Layout ditentukan oleh lebar efektif dan kepadatan konten.
- Pola expandable card Proyek menjadi referensi untuk halaman pemantauan yang informasinya dapat diringkas dan dibuka secara progresif, bukan pola universal untuk semua halaman.

## Inventarisasi halaman dan rekomendasi

### A. Area Admin

| Halaman | Bentuk/temuan dari view | Rekomendasi audit dan arah pola |
|---|---|---|
| Dashboard | Ringkasan statistik, action card, aktivitas absensi terbaru; container `max-w-7xl gap-6`. | Audit density dan ritme section; evaluasi gap-6 yang lebih renggang dibanding baseline. Pertahankan stat/action card dan list aktivitas; cek urutan dan wrapping pada mobile/tablet. |
| Client — daftar | Card per Client, Site ditampilkan di dalam card, Office Internal sebagai list row. | Pertahankan card/list. Selaraskan padding, header, jarak antar-card, hierarki Site, dan area aksi. |
| Client — detail | Ringkasan Client dan daftar Site; container `max-w-6xl gap-5`. | Pertahankan ringkasan + daftar Site. Audit konsistensi lebar dengan halaman master, tombol kembali/aksi, dan kepadatan daftar. |
| Client — create/edit | Form master data. | Audit konsistensi lebar form, header/back navigation, jarak field, error, dan tombol aksi. Tidak perlu compact seperti halaman list. |
| Location — create/edit | Form dengan pilihan jenis, Client, nama, alamat, koordinat, timezone, status; form `p-5`, field berjarak `space-y-4`. | Audit pengelompokan field terkait, penjelasan kondisional Site/Office, koordinat, timezone, dan error pada mobile. Pertahankan ruang input yang nyaman. |
| Proyek — daftar | Expandable card sudah diterapkan; satu kolom mobile dan dua kolom mulai md. | Pola tervalidasi sebagai referensi. Uji ulang tablet portrait/landscape dan nama panjang sebelum dijadikan acuan implementasi halaman lain. |
| Proyek — create/edit | Form bersama `_form.blade.php`; grid dua kolom mulai sm. | Audit urutan field, field Client terkunci saat edit, pemilihan Site, penjelasan lokasi nonaktif, dan responsivitas grid. Jangan ubah aturan field. |
| Pegawai — daftar | Tabel 5 kolom: identitas/email, nomor, jabatan, status akun, aksi. | Kandidat hybrid: tabel saat lebar cukup, card mobile. Tablet adaptif. Validasi email/nama panjang, nomor/jabatan kosong, badge status, dan aksi edit. |
| Pegawai — create/edit | Form terpisah. | Audit konsistensi form dengan Client/Location/Project: lebar, header, grouping, error, password/account controls, dan urutan aksi. |
| Koreksi Absensi — daftar | List/card link per permohonan. | Pertahankan list/card; rapikan wrapping nama, jenis koreksi, waktu, status, dan target klik. |
| Koreksi Absensi — detail | Ringkasan data, bukti asli/effective timestamp, status koreksi, attachment, form keputusan. | Prioritas keterbacaan data pembanding dan bukti. Susun informasi asli vs efektif secara eksplisit; form keputusan responsif; jangan menghilangkan warning/callout. |
| Persetujuan Permohonan — daftar | Tabel 6 kolom dengan filter status. | Hybrid card mobile + tabel desktop. Filter, pagination, status, periode, dan navigasi detail harus tetap utuh. |
| Persetujuan Permohonan — detail | Detail pengajuan dan form keputusan admin dalam panel terpisah; modal konfirmasi. | Audit urutan baca, kepadatan detail, modal pada layar kecil, dan akses tombol keputusan. Pertahankan konfirmasi dan seluruh field. |
| Pengelolaan Lembur — daftar | List/card. | Pertahankan list/card; rapikan wrapping alasan, jadwal, status persetujuan dan state proses. |
| Pengelolaan Lembur — detail | Beberapa section status, rentang waktu final, form finalisasi, keputusan admin. | Audit panjang halaman, grouping section, form datetime pada mobile, pemisahan data jadwal/aktual/final, dan posisi aksi. Jangan mengubah workflow. |
| Audit Log | Tabel kronologis 5 kolom. | Pertahankan tabel dengan horizontal scroll yang terkontrol. Fokus pada density baris, wrapping objek/action, dan aksesibilitas pada layar sempit. |
| Rekap Absensi Harian | Card pegawai khusus dengan event, status, review, dan form koreksi. | Pertahankan komponen khusus. Audit form di dalam card, kepadatan beberapa event, dan responsivitas; pertahankan CSRF, error, dan aksi POST. |
| Kehadiran | Tabel padat 7 kolom dengan sorting, pagination, foto, GPS, backup Drive, peta, koreksi timestamp, dan ekspor. | Audit khusus prioritas tinggi. Pertahankan tabel sebagai baseline; evaluasi tinggi baris, padding sel, filter, kepadatan metadata, kontrol aksi, dan penggunaan viewport. Jangan memaksa card sebelum ada bukti tabel tidak layak. |

### B. Area Pegawai

| Halaman | Bentuk/temuan dari inventarisasi | Rekomendasi audit dan arah pola |
|---|---|---|
| Absensi | Halaman Livewire kompleks dengan kamera, GPS, pilihan lokasi/proyek, dan alur clock-in/clock-out. | Prioritas tertinggi untuk UX mobile. Audit urutan langkah, status izin kamera/lokasi, loading/error, ukuran preview, tombol utama, dan fallback. Jangan mengubah alur absensi atau validasi. |
| Profil Pegawai | Halaman profil tersendiri. | Audit struktur informasi profil, keterbacaan, konsistensi section/card, dan tampilan nilai kosong/panjang. |
| Riwayat Absensi | Halaman riwayat dengan ukuran view besar dan data absensi/evidence. | Audit filter, ringkasan, event, snapshot identitas, bukti, pagination/ekspor, dan responsivitas. Pertahankan perbedaan bukti asli dan nilai efektif. |
| Koreksi Absensi — pengajuan | Form pengajuan koreksi. | Audit urutan field, pemilihan record/timestamp, alasan, attachment, validasi, dan ringkasan bukti pada mobile. |
| Permohonan — daftar | Daftar permohonan pegawai. | Audit konsistensi pola list/card, status, rentang tanggal, alasan ringkas, empty state, dan navigasi detail jika tersedia. |
| Lembur — daftar | Daftar permohonan lembur pegawai. | Audit konsistensi card/list, ringkasan jadwal, status, dan aksi; pertahankan alur modal/form yang sudah ada. |

### C. Halaman lintas peran dan pendukung

| Halaman | Bentuk/temuan dari inventarisasi | Rekomendasi audit dan arah pola |
|---|---|---|
| Notifikasi | List artikel notifikasi pada container `max-w-3xl`; aksi tandai dibaca dan tandai semua. | Pertahankan list. Audit hierarki unread/read/resolved, panjang pesan, jarak antar-notifikasi, tombol aksi, dan empty state. |
| Pengaturan Keamanan | Halaman pengaturan akun/security. | Audit konsistensi layout section, form password/session, feedback, dan responsivitas. Tetap gunakan lebar baca/form yang sesuai. |
| Login, lupa/reset password, verifikasi email, konfirmasi password | Halaman autentikasi menggunakan layout auth. | Audit konsistensi visual antar-state auth, lebar card/split layout, spacing field, error, tombol, dan mobile fallback. Tidak perlu memakai layout admin. |
| Layout aplikasi, header, sidebar | Kerangka navigasi global. | Audit lebar konten, sidebar desktop/mobile, header, menu aktif, badge notifikasi, overflow, dan konsistensi tinggi/padding. Perubahan navigasi/transisi halaman ditunda ke tahap P berikutnya. |

## Prioritas pengerjaan setelah audit

Urutan berikut adalah rekomendasi berdasarkan dampak UX dan kompleksitas; dapat disesuaikan setelah pemeriksaan render langsung:

1. **Kehadiran Admin dan Absensi Pegawai** — audit density dan alur interaksi kritis (kamera/GPS/evidence).
2. **Pegawai — daftar** — terapkan hybrid card/table setelah pola Proyek tervalidasi.
3. **Halaman daftar dan pemantauan admin lain** — Client, Koreksi Absensi, Persetujuan Permohonan, Lembur, Audit Log, dan Rekap Harian sesuai pola masing-masing.
4. **Riwayat Absensi dan halaman permohonan Pegawai** — selaraskan list, filter, detail ringkas, dan bukti.
5. **Dashboard, Notifikasi, Profil, dan Pengaturan** — konsistensi spacing, density, dan hierarchy.
6. **Form dan halaman detail admin/pegawai** — konsistensi bahasa visual, grouping, field, feedback, dan aksi; tidak dipadatkan secara agresif.
7. **Auth dan layout global** — konsistensi shell dan responsivitas.

## Aturan implementasi

1. Jangan mengubah semua halaman sekaligus; kerjakan per kelompok dan validasi sebelum melanjutkan.
2. Pilih pola berdasarkan fungsi: card untuk ringkasan yang dapat dipindai, tabel untuk data tabular/kronologis, form layout untuk input, detail layout untuk pembacaan dan keputusan.
3. Hindari duplikasi query/state untuk layout desktop dan mobile.
4. Jangan menghilangkan informasi penting pada breakpoint tertentu.
5. Pertahankan route, parameter, filter, pagination, sorting, CSRF, policy, validasi, dan aksi bisnis.
6. Gunakan komponen bersama hanya jika struktur, semantik, dan perilakunya benar-benar sama; jangan membuat komponen generik yang terlalu banyak kondisi.
7. Konsistensi visual tidak berarti semua halaman harus memiliki ukuran container, card, atau padding identik. Pengecualian harus punya alasan UX yang jelas.
8. Uji long text, nullable data, empty state, loading/error, dark mode, dan semua state interaksi yang relevan.
9. Uji mobile, tablet portrait, tablet landscape, dan desktop.
10. Navigasi dinamis/transisi halaman tidak dikerjakan dalam P1-D; evaluasi terpisah pada tahap P.

## Batas audit saat ini

Inventarisasi struktur file telah mencakup halaman Admin, Pegawai, Dashboard, Notifikasi, Pengaturan, Auth, komponen admin, dan layout aplikasi pada branch ini. Pemeriksaan kode terperinci telah dilakukan pada sejumlah halaman representatif (dashboard, Client detail, Location form, Project form, daftar/detail permohonan, detail koreksi, lembur, dan notifikasi).

Audit ini belum merupakan validasi visual langsung pada semua route dan viewport, dan belum memeriksa setiap Blade view baris demi baris. Rekomendasi di atas adalah arah audit/implementasi; temuan visual final harus dikonfirmasi melalui render aplikasi dan pengujian interaksi per kelompok halaman.
