# P1-D3 — Audit Pola Layout, Komponen, UI/UX, dan Responsivitas Seluruh Halaman

## Status

> **Catatan pembacaan:** dokumen ini mempertahankan temuan dan rekomendasi pada saat audit dilakukan. Baris yang menyebut implementasi belum dilakukan adalah kondisi historis, bukan pernyataan bahwa source `main` saat ini belum berubah. Audit ini juga bukan hasil browser visual QA menyeluruh.

- Tahap: dokumen audit baseline dan rekomendasi lintas halaman. Setelah audit ini, standardisasi beberapa halaman Admin dilanjutkan dan di-merge melalui PR #25. Untuk status implementasi Admin terkini, lihat `docs/ui/ADMIN-PAGES-AUDIT.md` dan `docs/PROJECT-STATUS.md`.
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

Urutan implementasi yang telah disepakati:

1. **Pegawai — daftar** — terapkan pola hybrid card/table; filter, pagination, route edit, dan otorisasi tetap dipertahankan.
2. **Kehadiran Admin** — audit dan perbaiki density tabel secara terukur; pertahankan akses foto, GPS, timestamp, koreksi, dan seluruh aksi.
3. **Halaman pemantauan Admin lainnya** — Client, Koreksi Absensi, Persetujuan Permohonan, Lembur, Audit Log, dan Rekap Harian sesuai pola masing-masing.
4. **Riwayat dan permohonan Pegawai** — selaraskan list, filter, detail ringkas, dan bukti.
5. **Dashboard, Notifikasi, Profil, dan Pengaturan** — konsistensi spacing, density, dan hierarchy.
6. **Form, detail, Auth, serta layout global** — konsistensi bahasa visual, grouping, field, feedback, aksi, dan responsivitas; form tidak dipadatkan secara agresif.
7. **Implementasi halaman Pegawai /absensi** — baru dilakukan setelah seluruh halaman lainnya di atas selesai. Audit dan skenario pengujiannya disiapkan lebih awal dalam dokumen ini.

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


## Audit khusus halaman Pegawai — /absensi (audit saja; implementasi ditunda)

Halaman `/absensi` diperlakukan sebagai halaman kritis karena merupakan entry point utama pegawai untuk Clock In, Clock Out, dan konfirmasi mulai lembur. Sesuai keputusan pengguna, pada tahap ini halaman hanya diaudit dan tidak diubah. Implementasi UI/UX halaman ini dijadwalkan setelah implementasi halaman lain dalam cakupan P1-D4 selesai.

### Struktur dan perilaku yang ditemukan

- Halaman merupakan Livewire single-file component dengan `wire:poll.30s` pada root section.
- Container konten saat ini `max-w-xl` dengan `gap-6`; ini membuat alur terfokus pada mobile, tetapi berpotensi menyisakan ruang besar pada tablet/desktop.
- Header memuat avatar inisial, nama, nomor pegawai, jabatan, serta pintasan notifikasi.
- Pemilih tanggal berbentuk kalender satu minggu (7 kolom), navigasi minggu sebelumnya/berikutnya, dan tombol Hari ini.
- Ringkasan jam kerja memakai 3 kartu (Masuk, Pulang, Total kerja).
- Kartu informasi menampilkan status kehadiran dan status/pengajuan lembur; daftar lembur dapat memuat detail, pembatalan, dan modal detail.
- Form absensi hanya ditampilkan untuk tanggal hari ini. Mode form berubah mengikuti state Clock In, Clock Out, Clock In yang sudah digunakan, menunggu jadwal lembur, atau mulai lembur.
- Clock In meminta jenis kerja (Kantor/Onsite), lalu pilihan Office atau Project/Site sesuai jenisnya.
- Komponen kamera membuka dialog kamera fullscreen, menangani orientasi portrait/landscape, kamera depan/belakang, preview, ambil ulang, status lokasi, loading, dan error.
- Data bukti yang dikirim meliputi selfie, sumber selfie, koordinat, akurasi, alamat snapshot, dan waktu capture; modal konfirmasi menampilkan ringkasan sebelum submit.
- Ada modal terpisah untuk konfirmasi absensi, detail lembur, dan pengajuan lembur.
- Layout aplikasi menambahkan ruang bawah untuk navigasi pegawai dan safe-area perangkat mobile.

### Temuan audit awal

| Area | Temuan | Hal yang perlu diverifikasi saat implementasi nanti |
|---|---|---|
| Lebar konten | `max-w-xl` menjaga fokus pada form utama, tetapi seluruh ringkasan, riwayat harian, dan form berada dalam satu kolom sempit pada layar lebar. | Uji apakah tablet/desktop lebih efektif dengan grid dua kolom yang memisahkan ringkasan/riwayat dari panel aksi, tanpa membuat form terlalu lebar. Mobile tetap satu kolom. |
| Vertical density | Root memakai `gap-6`, beberapa panel memakai padding 4–5, dan form memakai `gap-5`. | Ukur blank space dan scroll depth aktual; ringkas jarak antarbagian yang tidak membantu, tetapi pertahankan ruang kamera, bukti, dan form. |
| Kalender mingguan | Tujuh kolom dengan tombol tanggal ber-padding vertikal 2.5 dan kontrol navigasi di header panel. | Uji lebar layar kecil, keterbacaan label hari, ukuran target sentuh, dan wrapping tombol Hari ini pada perangkat sempit. |
| Ringkasan waktu | Tiga kartu memakai grid 3 kolom tanpa breakpoint. | Uji angka/jam dan label pada ponsel kecil serta pembesaran teks; jangan sampai isi terpotong. Pertimbangkan perubahan susunan hanya bila terbukti perlu. |
| Kartu status/lembur | Dua kolom mulai `sm`; informasi lembur bisa memanjang karena banyak timestamp, alasan, catatan, dan aksi. | Uji satu/lebih pengajuan lembur, teks panjang, status berbeda, dan urutan baca pada tablet portrait. Pastikan aksi pembatalan/detail tetap mudah ditemukan. |
| Form utama | Form memiliki state kondisional dan disembunyikan saat tanggal yang dipilih bukan hari ini. | Audit penjelasan state dan visibilitas aksi untuk tiap mode; cegah pegawai mengira form hilang karena error. Pertahankan seluruh state dan aturan. |
| Pilihan kerja | Pilihan Office/Onsite mengubah kontrol berikutnya; informasi Project menampilkan Client, Site, dan jenis layanan. | Uji label panjang, banyak pilihan, lokasi Office kosong/satu/banyak, proyek kosong, serta nama proyek/Client panjang. |
| Kamera | Kamera memakai dialog fullscreen adaptif portrait/landscape, video/canvas preview, status lokasi, dan kontrol foto. | Uji iOS Safari/Android Chrome, izin ditolak, kamera tidak tersedia, kamera tunggal, orientasi berubah, jaringan lambat, dan safe-area. Jangan mengecilkan kontrol kamera atau menghilangkan pesan status. |
| GPS dan bukti | Tombol capture terkunci sampai video siap dan location snapshot siap; akurasi dan alamat ditampilkan. | Perjelas hubungan status GPS, akurasi, dan kesiapan foto; uji lokasi gagal/timeout/akurasi rendah serta alamat kosong. Jangan mengubah kebijakan validasi atau bukti. |
| Modal konfirmasi | Menampilkan jenis absensi, lokasi kerja, tempat/proyek, akurasi, alamat snapshot, sumber foto, dan preview selfie. | Uji modal pada viewport pendek dan ponsel landscape; pastikan konten dapat discroll dan tombol aksi tidak tertutup keyboard/safe-area. |
| Modal lembur | Detail dan pengajuan lembur memakai modal dengan konten yang dapat panjang. | Uji scroll internal, datetime-local, error validasi, catatan panjang, dan tombol aksi pada layar pendek. |
| Polling dan interaksi | Root melakukan polling setiap 30 detik; komponen kamera memiliki lifecycle Alpine dan menghentikan kamera saat event navigasi. | Uji apakah polling mengganggu state kamera, upload, modal, atau form yang sedang diisi; jangan mengubah interval tanpa bukti dan analisis dampak. |
| Aksesibilitas | Banyak tombol ikon sudah memiliki aria-label; kamera menggunakan role dialog dan live status. | Audit fokus keyboard, focus trap/restore modal, kontras status, reduced motion bila animasi ditambahkan, dan screen reader. |

### Prioritas pengujian ketika implementasi tiba

1. Mobile portrait (ponsel kecil dan besar), mobile landscape.
2. Tablet portrait dan tablet landscape.
3. Desktop/laptop.
4. Clock In Office: tidak ada Office, satu Office, beberapa Office.
5. Clock In Onsite: tidak ada Project, banyak Project, nama panjang.
6. Clock Out, sesi aktif, dan Clock In yang sudah digunakan hari itu.
7. Seluruh state lembur: belum waktunya, waktu mulai tiba, foto mulai lembur, pembatalan, detail, dan error validasi.
8. Kamera/GPS: izin diberikan/ditolak, perangkat tanpa kamera, kamera depan/belakang, lokasi lambat/gagal, foto ulang, upload, dan submit.
9. Modal pada viewport pendek, keyboard terbuka, dan orientasi berubah.
10. Polling saat modal/kamera/upload/form sedang aktif.

### Batas dan keputusan

- Ini adalah audit kode dan daftar skenario; belum merupakan pengujian visual langsung pada perangkat atau browser.
- Tidak ada perubahan Blade, CSS, JavaScript, Livewire state, kamera, GPS, query, validasi, atau workflow pada halaman `/absensi` dalam tahap audit ini.
- Halaman `/absensi` tetap menjadi prioritas khusus, tetapi implementasi layout/UI/UX ditunda sampai implementasi seluruh halaman lain dalam cakupan P1-D4 selesai.
- Penundaan ini tidak berarti mengurangi prioritas UX-nya; justru perubahan akan dilakukan sebagai pekerjaan tersendiri setelah pola halaman lain stabil.
