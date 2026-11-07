# Rencana Kesiapan Produksi UI (F0 sampai F6)

Branch `feature/ui-production-readiness`, PR [#30](https://github.com/leonsmith128/pt-sanmal/pull/30) ke `main`. Dasar: `main` pada commit `eac6d1a`, setelah PR #27 dan #29 di-merge. Disusun 7 November 2026.

Dokumen ini adalah **pelacak kerja**: urutan fase, tasklist, penerimaan, dan ketergantungan. Spesifikasi rinci (token, komponen, per halaman) ada di handoff "Handoff UI Produksi SanMal" dan di [Design Foundation](DESIGN-FOUNDATION.md). Bila dokumen ini dan source berbeda, periksa source lalu perbarui dokumen yang paling sempit.

Aturan bukti: centang hanya untuk pekerjaan yang benar-benar selesai dan terverifikasi. Hasil browser atau perangkat hanya dicatat di [Visual QA Log](progress/VISUAL-QA-LOG.md). Hasil yang belum dijalankan tetap ditulis *Not run*. Catatan: perubahan source F1 di bawah ditulis lewat konektor GitHub; **test, Pint, build, dan browser belum dijalankan** untuk perubahan itu. Kontras dihitung ulang dari nilai hex (rumus WCAG 2.x), bukan diukur di browser.

## Keadaan saat ini

| Fase | Keadaan |
|---|---|
| F0 | Amandemen dokumen masuk di branch. Pemeriksaan P0-1 sampai P0-9 **belum dijalankan** (butuh browser, perangkat, atau `.env` server). Lihat Visual QA Log |
| F1 | **Sebagian** ada di source (token, kartu, tombol, badge, modal, empty state, tema). Belum diverifikasi dengan test, build, atau browser. Sisa butir ditandai di bawah |
| F2 sampai F6 | Belum dimulai. Satu perubahan F2 ikut masuk bersama F1: kartu auth berborder di `layouts/auth/simple` |

## Keputusan desain (D1 sampai D10)

| ID | Keputusan | Keadaan |
|---|---|---|
| D1 | Panel berborder 1px di kedua tema; bayangan hanya untuk elemen melayang | Dokumen tercatat; kartu dan modal berborder di source (F1) |
| D2 | Tombol sekunder gelap transparan berborder netral; cokelat dihapus | Dokumen tercatat; CSS tombol dan badge sudah diubah (F1) |
| D3 | Sidebar gelap punya permukaan sendiri dan `border-e` | Dokumen tercatat; token `surface-sidebar-dark` ada, pemakaian di sidebar di F2 |
| D4 | Hapus `class="dark"` permanen di `<html>` (empat layout) | Dihapus di empat layout (F1). **Belum diverifikasi** (P0-3): perilaku `@fluxAppearance` dan kedipan tema |
| D5 | Radius: panel, modal, kartu auth `rounded-xl`; kontrol `rounded-lg` | Modal dan kartu auth sudah `rounded-xl`; sisanya F2 sampai F5 |
| D6 | Satu format tanggal dan satu sumber locale | Dokumen tercatat; syarat `APP_LOCALE=id` (P0-2) |
| D7 | Token teks bisa dipakai sebagai kelas Tailwind (`@theme inline`) | Ditambahkan di `app.css` (F1) |
| D8 | Satu branch dan satu PR, per fase | Tercatat di `AGENTS.md`, Foundation, Development Handoff |
| D9 | Pemilik tunggal gutter halaman | **Menunggu hasil P0-1** |
| D10 | Lebar dan jarak halaman lewat prop `x-ui.page`, bukan `!max-w-*` dan `!gap-*` | Dokumen tercatat; kode di F5 |

## Tasklist fase

### F0. Dasar dan verifikasi baseline (tanpa perubahan tampilan)

- [x] Amandemen `AGENTS.md` §7, `DESIGN-FOUNDATION.md`, `DEVELOPMENT-HANDOFF.md` (D1 sampai D8, D10)
- [x] Rencana lama `ui-ux-v1-all-pages-redesign-plan.md` ditandai superseded lalu dihapus
- [x] Visual QA Log memuat baris P0 dengan fakta source (bukan bukti QA)
- [ ] P0-1 gutter horizontal di 360px (menentukan D9)
- [ ] P0-2 locale dan `.env` target: `APP_LOCALE=id`, `APP_DEBUG=false`
- [ ] P0-3 tema tanpa `class="dark"`: localStorage kosong, OS terang dan gelap, kedipan
- [ ] P0-4 `npm run build`: file font ada di `public/build/assets`, tanpa permintaan domain lain
- [ ] P0-5 kontras bagian Flux di luar token: badge zinc, dropdown select, item sidebar, tombol `danger`
- [ ] P0-6 daftar test yang meng-assert kelas atau markup (lengkapi dengan `grep` lokal di `tests/`)
- [ ] P0-7 dialog 419 Livewire pada halaman absensi (bahasa dan perilaku)
- [ ] P0-8 kamera dan GPS di Android dan iOS nyata lewat HTTPS
- [ ] P0-9 tangkapan layar baseline 12 halaman prioritas di 360, 768, 1280, terang dan gelap

**Penerimaan F0:** D1 sampai D10 tercatat; keputusan P0-1 dan P0-2 terdokumentasi; tidak ada perubahan tampilan. Catatan: F1 dimulai atas permintaan pemilik sebelum P0-1 sampai P0-4 tercatat; hasilnya harus diverifikasi mundur terhadap P0 tersebut.

### F1. Token dan komponen dasar

Sudah di source (belum terverifikasi test, build, browser):

- [x] `app.css`: nilai permukaan, teks, `control-border`, `text-sidebar`, token teks semantik (terang dan gelap)
- [x] `@theme inline` supaya `text-sanmal-text-*` dan `*-text` menghasilkan warna (D7). Pencarian kode GitHub tidak menemukan definisi lama; konfirmasi dengan `grep -rn` lokal
- [x] Hapus `class="dark"` di `layouts/app/sidebar`, `layouts/auth/simple`, `layouts/auth/split`, `layouts/auth/card` (D4)
- [x] `sanmal-badge-neutral`; `zinc`, `gray`, `slate`, `neutral`, `stone` dipetakan ke netral (`x-ui.badge`)
- [x] Tombol sekunder gelap (transparan, border kontrol), kelas `sanmal-button-danger` dipasang pada `variant="danger"` tanpa `color`
- [x] `x-ui.card`: border 1px, tanpa bayangan, hover kartu bertautan memakai border `accent`, cincin fokus `var(--sanmal-focus)` (D1)
- [x] `x-ui.modal`: `rounded-xl`, `shadow-xl`, tetap di permukaan kartu, prop `describedby` dan `dismissibleBackdrop`, transisi overlay dan panel, `max-h-[calc(100dvh-2rem)]` dengan isi bergulir, `x-init` fokus dihapus
- [x] `x-ui.empty-state`: prop `icon`, `title`, slot `action` (kompatibel mundur)
- [x] `x-ui.page-header`: deskripsi memakai `text-sanmal-text-secondary`
- [x] Kelas `text-sanmal-{success,warning,danger,info}` diarahkan ke token teks AA lewat shim di `app.css` (angka metrik dashboard admin). Hapus shim setelah view memakai `*-text` langsung (F4/F5)
- [x] Test diperbarui: `UiCardComponentTest`, `UiButtonComponentTest`, `UiBadgeComponentTest`, `UiFeedbackComponentsTest`

Sisa F1:

- [ ] Fokus awal ke **Batal** pada dialog nada bahaya (butuh `x-ui.confirm-dialog`)
- [ ] Penjaga kirim ganda (Alpine, `pageshow` mengembalikan tombol) untuk form non-Livewire
- [ ] `x-ui.pill-nav` dan `x-ui.confirm-dialog` **hanya bila** pengulangan markup terbukti (`AGENTS.md`); bila tidak, markup biasa
- [ ] Ganti warna mentah di luar palet (`zinc`, `amber-*`, `red-*`, `emerald-*`, `purple`, `sky`) di view (dashboard, pil)
- [ ] Hapus override `!border-dashed !bg-transparent` di halaman daftar akun (F3)
- [ ] Periksa test lain yang meng-assert kelas yang berubah (`AdminEmployeePageTest`, `OvertimeWorkflowTest`, `RequestCenterTest`, `DashboardTest`); jalankan seluruh suite
- [ ] `npm run build`, Pint, dan verifikasi browser

**Penerimaan F1:** tidak ada tombol cokelat di tema gelap; kontras sesuai tabel handoff; cincin fokus kartu 3:1; kelas `text-sanmal-text-*` menghasilkan warna; tema mengikuti pilihan pengguna; test kartu diperbarui; seluruh test lulus.

### F2. Shell, ikon, auth, halaman error

- [ ] Sidebar gelap: `surface-sidebar-dark` dan `border-e` (D3); teks sidebar terang memakai `text-sidebar`. Perbarui assertion `AttendancePagesTest` yang menolak `border-e border-sanmal-border`
- [ ] Item aktif dengan penanda non-warna; header mobile minimal 56px; tombol 44x44px
- [ ] Menu tema dan pengguna berbahasa Indonesia (Tampilan, Terang, Gelap, Sistem, Keluar)
- [ ] Ikon sidebar tanpa tabrakan (tabel handoff bagian 9.3); cek nama ada di set ikon Flux terpasang
- [ ] Lencana hitungan antrean (butuh view composer, ketergantungan #3)
- [x] Layout auth `simple`: kartu `rounded-xl` berborder tanpa bayangan
- [ ] Layout auth: satu gaya header rata kiri; layar pendek bisa digulir
- [ ] Login dan aktivasi: string Indonesia, aturan password dari validasi aktual, `viewable`, penjaga kirim ganda
- [ ] Halaman error 403, 404, 419, 429, 500, 503 yang berdiri sendiri (tanpa Auth, sidebar, query)
- [ ] Hook JS 419 Livewire, hanya bila P0-7 menunjukkan dialog bawaan berbahasa Inggris
- [ ] Tautan **Lewati ke konten**, `id="konten-utama"`, `<html lang>` mengikuti locale

**Penerimaan F2:** tidak ada string Inggris di auth dan shell; semua kode error punya halaman; sidebar gelap terpisah dari halaman; ikon tanpa tabrakan.

### F3. Pengelolaan akun pegawai

Spesifikasi: Handoff PR 29 bagian 5 (di handoff produksi dirujuk sebagai bagian 14; bagian 14 tidak ikut dalam berkas yang dipakai menyusun dokumen ini, jadi ikuti bagian 5 handoff PR 29 sambil menunggu).

- [ ] Daftar akun: kosakata **Akun pegawai**, filter inline, `x-admin.account-status`, kosong dua keadaan
- [ ] Ubah akun: `x-admin.account-lifecycle`, tata letak dua kolom, panel **Serah terima akses**, pindah **Status akun** ke panel
- [ ] Konfirmasi: buat password baru dan nonaktifkan akun menyebut akibatnya
- [ ] Tambah akun: panel tunggal, teks **Setelah disimpan**
- [ ] Reset password: tab berbasis tautan, modal tinjau, `x-admin.reset-status-badge`
- [ ] Ketergantungan backend #1, #2, #3 terselesaikan atau diputuskan

**Penerimaan F3:** rotasi dan nonaktifkan tidak terjadi tanpa konfirmasi; klik ganda tidak mengirim dua kali; tabel keadaan ubah akun terpenuhi; keputusan reset lewat modal tinjau.

### F4. Halaman pegawai (mobile-first)

- [ ] Dashboard pegawai dan Dashboard Pribadi: pemetaan status (hadir sukses; belum lengkap peringatan; perlu ditinjau bahaya; cuti, izin, sakit info; tidak ada netral)
- [ ] Banner status hari ini; tombol absensi 48px; kosakata **Permohonan**
- [ ] Absensi: hanya token, ukuran sentuh, status kamera dan GPS beserta tindakan lanjutan; kamera, GPS, watermark, upload tidak diubah
- [ ] Tautan **Mulai absen** (anchor): **butuh persetujuan pemilik** sebelum dikerjakan
- [ ] Riwayat, Permohonan, Koreksi, Lembur: pil status 44px, konfirmasi pembatalan, aturan lampiran dari validasi aktual, dua badge terpisah di lembur
- [ ] Profil, Keamanan Akun, Notifikasi: state unggah, penanda **Baru** memakai aksen biru

**Penerimaan F4:** diff tidak menyentuh alur kamera, GPS, dan upload; target sentuh 44px; tidak ada warna di luar palet.

### F5. Halaman admin

- [ ] Pola tabel seragam (header, `scope="col"`, kolom aksi `sr-only`, wilayah gulir berlabel)
- [ ] Pil status antrean memakai komponen bersama atau markup seragam; titik status memakai token
- [ ] Ganti `!max-w-6xl !gap-6` dengan prop `x-ui.page` (D10)
- [ ] Rekap harian, klien, proyek: grid mengikuti lebar konten (`xl` untuk tiga kolom)
- [ ] Detail permohonan, koreksi, lembur: modal tinjau dan konfirmasi akibat
- [ ] Audit log: label aksi bahasa Indonesia dan filter `select` (ketergantungan #6)
- [ ] Format tanggal seragam (`translatedFormat`, `<time>`)

**Penerimaan F5:** tabel seragam; pil memakai pola bersama; audit log berlabel; dashboard admin tanpa kegagalan kontras.

### F6. QA lengkap dan dokumen

- [ ] Matriks QA (lebar 360 sampai 1440, terang dan gelap, sidebar terbuka dan tertutup, keyboard saja, reduced motion, jaringan lambat) dengan bukti nyata
- [ ] Android Chrome dan iOS Safari nyata untuk absensi
- [ ] Perbarui `PAGE-INVENTORY`, `COMPONENT-CATALOG`, `REUSABLE-BLADE-COMPONENTS`, `IMPLEMENTATION-STATUS` (masih menyebut PR #27 sebagai pelacak), `VISUAL-QA-LOG`
- [ ] Unggah build percobaan: `public/build` lengkap, tanpa 404 aset dan tanpa permintaan pihak ketiga
- [ ] `vendor/bin/pint --format agent`, `php artisan test --compact` oleh pemilik, `npm run build`

## Ketergantungan backend

Dikerjakan terpisah dari perubahan tampilan bila menyentuh logika.

| # | Kebutuhan | Fase |
|---|---|---|
| 1 | Endpoint nonaktifkan dan aktifkan kembali akun (atau form modal dengan field tersembunyi) | F3 |
| 2 | Filter `?tab=` pada daftar permintaan reset | F3 |
| 3 | Hitungan menunggu untuk sidebar lewat view composer | F2, F3 |
| 4 | Hitungan per status untuk pil (opsional) | F5 |
| 5 | Query "perlu tindakan" (opsional) | F3 |
| 6 | Pemetaan label aksi audit ke bahasa Indonesia | F5 |
| 7 | Aturan password untuk teks bantuan, dibaca dari validasi | F2 |
| 8 | `.env` produksi: `APP_LOCALE=id`, `APP_NAME`, `APP_DEBUG=false`, mail | F0 |
| 9 | Hook JS 419 Livewire (bila P0-7 menunjukkan perlu) | F2 |
| 10 | Keputusan umur sesi halaman absensi (`SESSION_LIFETIME=120`) | keputusan produk |
| 11 | Dukungan toast Flux dari controller non-Livewire | F1 |

## Risiko dan keputusan terbuka

- Hosting tanpa Node.js berasal dari pemilik dan belum dikonfirmasi di repo. `public/build` masuk `.gitignore`, jadi build harus diunggah manual.
- Container query tidak dipakai (keputusan bawaan). Evaluasi setelah F0.
- `AGENTS.md` tercantum di `.gitignore` padahal terlacak (temuan di Visual QA Log); butuh keputusan pemilik.
- Tautan **Mulai absen** dan perubahan urutan form absensi mengubah kontrak layout; butuh persetujuan eksplisit.
- Perubahan F1 di source belum dijalankan: pencarian kode GitHub tidak lengkap, jadi test lain yang meng-assert kelas kartu, modal, atau badge bisa saja gagal. Jalankan seluruh suite sebelum lanjut ke F2.
- Pergantian tema default (D4) mengubah tampilan untuk semua pengguna yang sebelumnya selalu gelap. Verifikasi P0-3 sebelum merge.
- Tidak ada render browser yang dipakai menyusun rencana ini. Semua temuan tampilan adalah dugaan dari source dan hitungan sampai F0 selesai.

## Definition of Done sebelum merge

Mengikuti bagian "Definition of Done sebelum merge" di [Development Handoff](DEVELOPMENT-HANDOFF.md). Ringkasnya: semua halaman diperiksa, kontras dihitung ulang dan tercatat, test relevan lulus, Pint bersih, build diunggah di staging, kamera dan GPS diuji di perangkat nyata, dokumen konsisten dengan source.
