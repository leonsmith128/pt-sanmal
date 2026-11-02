# Audit Menyeluruh Halaman Admin — UI Standardization

## Status dan batas audit

- Baseline branch: `feature/ui-standardization-p1-ab`
- Commit baseline: `a58d4e7908a49caeb40daf936f4b6c816a446b3f`
- Cakupan: seluruh Blade view di `resources/views/pages/admin/` pada baseline tersebut, komponen Blade Admin/UI yang terkait, serta struktur test Feature yang tersedia.
- Inventaris: 22 file Blade (21 view layar/halaman dan 1 partial form proyek).
- Bagian audit merekam kondisi struktur dan presentasi pada commit baseline. Pada tahap audit awal tersebut belum ada perubahan pada view halaman, komponen runtime, route, query, validasi, Livewire state, atau business logic; progres implementasi setelah audit dicatat pada bagian tersendiri di bawah.
- Audit ini berbasis inspeksi source. Tidak mengklaim hasil render visual, browser test, maupun Pest test.

## Progress implementasi

Pencatatan berikut diperbarui setiap kelompok halaman selesai dikerjakan. Status mencerminkan perubahan source yang sudah masuk ke branch; status pengujian dicatat terpisah agar tidak menyiratkan pengujian yang belum dijalankan.

| Kelompok/halaman | Status implementasi | Catatan perubahan | Status verifikasi |
|---|---|---|---|
| Client Create | Selesai | Mengadopsi `x-ui.page`, `x-ui.page-header`, `x-ui.input`, `x-ui.textarea`, dan `x-ui.button`; route, method, nama field, old input, serta pesan validasi tetap dipertahankan melalui wrapper field | Source telah diperiksa; test otomatis belum dijalankan |
| Client Edit | Selesai | Diselaraskan dengan Create; mempertahankan `PUT`, model fallback untuk seluruh field, serta tautan kembali/batal ke detail Client | Source telah diperiksa; test otomatis belum dijalankan |
| Location Create | Selesai | Mengadopsi page/header, field controls, dan tombol bersama; seluruh cabang Client terpilih, Office mode, serta mode pilihan bebas dipertahankan | Source telah diperiksa; test otomatis belum dijalankan |
| Location Edit | Selesai | Mengadopsi field controls dan tombol bersama; mempertahankan pilihan Client/Office, selected state, old/model fallback, status aktif, dan `PUT` | Source telah diperiksa; test otomatis belum dijalankan |
| Project Create | Selesai | Shell halaman dan partial form menggunakan primitive `x-ui`; pilihan Client, selected state, serta default Create dipertahankan | Source telah diperiksa; test otomatis belum dijalankan |
| Project Edit | Selesai | Diselaraskan dengan Create; Client tetap terkunci pada select disabled dengan hidden `client_id`, dan method tetap `PUT` | Source telah diperiksa; test otomatis belum dijalankan |
| Attendance Correction Detail | Selesai | Page shell/header, field keputusan, catatan, dan tombol memakai primitive `x-ui`; metadata, snapshot, callout, lampiran, dan kondisi pending dipertahankan | Source telah diperiksa; test otomatis belum dijalankan |

### Catatan implementasi Location Create/Edit — 2026-11-03

- Kedua halaman menggunakan `x-ui.page` dengan lebar maksimum yang sama seperti form Client, `x-ui.page-header`, dan `x-ui.button`.
- Input nama, koordinat, timezone, alamat, serta select Client, jenis lokasi, dan status menggunakan wrapper `x-ui` yang telah tersedia.
- Create tetap memiliki tiga mode yang berbeda: Client sudah dipilih, Office internal dipaksa, dan pilihan jenis lokasi/Client secara manual.
- Hidden field `client_id`, `type`, serta `is_active=1` pada Create tetap dipertahankan sesuai cabang aslinya.
- Edit tetap menggunakan `PUT`; opsi Client, jenis lokasi, dan status tetap mempertahankan kondisi `selected` berdasarkan `old()` dan model.
- Nilai default timezone Create tetap `Asia/Makassar`; fallback nilai Edit tetap berasal dari `$location`.
- Route/action, method, nama field, required attribute, koordinat `step="any"`, deskripsi timezone, flash message, dan tujuan navigasi tidak diubah.
- Tidak ada perubahan backend, validasi, atau business logic.

**Batas verifikasi Location:** source telah diperiksa setelah perubahan. Belum ditemukan test Feature khusus bernama `AdminLocationPageTest`, `AdminLocationTest`, `LocationManagementTest`, `AdminLocationManagementTest`, atau `LocationTest` pada path `tests/Feature` yang diperiksa. Hal ini tidak membuktikan tidak ada coverage melalui test lain. Laravel/Pest dan browser visual test belum dijalankan.

### Catatan implementasi Attendance Correction Detail — 2026-11-03

- Container halaman diselaraskan menggunakan `x-ui.page` dengan lebar maksimum `max-w-3xl`; judul, identitas pegawai, dan tombol kembali disusun melalui `x-ui.page-header`.
- Field keputusan menggunakan `x-ui.select`, catatan peninjau menggunakan `x-ui.textarea`, dan submit menggunakan `x-ui.button`.
- Metadata `dl`, pemilihan snapshot (`sessionAnchor ?? attendanceRecord`), seluruh kondisi metadata, timestamp asli/efektif, penjelasan koreksi terakhir, event Clock Out yang dihasilkan, alasan, catatan peninjau, dan unduhan lampiran dipertahankan.
- Form keputusan tetap hanya muncul saat status `pending`; action route, method `POST`, CSRF, nama field `decision` dan `review_note`, opsi `approved`/`rejected`, old input, serta required state keputusan tidak berubah.
- Callout sukses/peringatan tetap menggunakan Flux karena merupakan feedback kontekstual dengan ikon/varian yang spesifik; tidak diganti atau digeneralisasi.
- Tidak ada perubahan route, controller, validasi, timestamp calculation, authorization, persistence, atau business logic.

**Batas verifikasi Attendance Correction Detail:** source hasil perubahan telah di-fetch kembali dari branch dan diperiksa. Test otomatis dan browser visual test belum dijalankan; belum ada klaim kelulusan test.
### Catatan implementasi Project Create/Edit — 2026-11-03

- Kedua halaman memakai `x-ui.page` dengan lebar maksimum `max-w-3xl` dan `x-ui.page-header`; flash message tetap berada di antara header dan form.
- Partial `_form` kini menjadi satu sumber markup untuk Create dan Edit dengan `x-ui.input`, `x-ui.select`, `x-ui.textarea`, `x-ui.field`, dan `x-ui.button` yang sudah tersedia. Tidak ada komponen form baru.
- Field kode proyek, nama, tanggal mulai/selesai, keterangan, jenis layanan, lokasi Site, dan status mempertahankan nama input serta kondisi required yang sama.
- Create tetap menyediakan select Client dengan placeholder dan selected state dari `old('client_id')`.
- Edit tetap menampilkan Client sebagai select disabled tanpa name, mempertahankan hidden input `client_id` yang berisi Client proyek, dan menampilkan error untuk field `client_id` melalui `x-ui.field`.
- Selected state untuk jenis layanan, lokasi Site, dan status tetap menggunakan ekspresi `old()` dan fallback model yang sama. Default jenis layanan `calibration` dan status `pending` tetap dipertahankan.
- Deskripsi lokasi Site, termasuk aturan Site milik Client proyek dan alasan lokasi nonaktif tetap ditampilkan, dipindahkan ke slot description wrapper.
- Form action, CSRF, conditional method spoofing `PUT`, tujuan tombol Batal, label tombol submit Create/Edit, serta flash message tidak diubah. Tombol aksi kini tersusun vertikal pada layar sempit dan horizontal mulai breakpoint `sm`.
- Tidak ada perubahan route, controller, validasi, query, payload, atau business logic.

**Batas verifikasi Project:** source hasil perubahan telah di-fetch kembali dari branch dan diperiksa. Test otomatis dan browser visual test belum dijalankan pada runtime pengerjaan ini; belum ada klaim kelulusan test.

### Catatan implementasi Client Create/Edit — 2026-11-03

- Kedua halaman sekarang memakai page shell dengan lebar maksimum yang sama (`max-w-2xl`) dan header bersama.
- Semua input teks dan textarea menggunakan wrapper `x-ui`, sehingga label dan error field mengikuti kontrak yang sama dengan form Pegawai.
- Tombol kembali, batal, dan submit menggunakan `x-ui.button`; tombol aksi ditata responsif dengan susunan vertikal pada layar sempit dan horizontal mulai breakpoint `sm`.
- Surface form, CSRF token, flash message, dan konten field tetap dipertahankan.
- Tidak ada perubahan pada route/action, HTTP method, nama input, required attribute, old/model fallback, maupun payload.
- Tidak ada perubahan backend atau business logic.

**Batas verifikasi:** perubahan dilakukan melalui GitHub source. Laravel/Pest tidak tersedia pada runtime pengerjaan ini, sehingga belum ada klaim bahwa test render/feature atau browser visual test telah lulus. Verifikasi lanjutan perlu menjalankan test terkait pada lingkungan proyek dan memeriksa tampilan di viewport mobile hingga desktop serta light/dark mode.

## 1. Inventaris halaman

| Area | File | Pola utama yang ditemukan |
|---|---|---|
| Kehadiran | `attendance-corrections/index.blade.php` | Filter status + responsive review list + badge status |
| Kehadiran | `attendance-corrections/show.blade.php` | Detail koreksi, metadata `dl`, snapshot pekerjaan, form keputusan |
| Kehadiran | `daily-attendance.blade.php` | Filter tanggal dan ekspor terpisah, daftar kartu pegawai, koreksi inline per pegawai |
| Kehadiran | `⚡kehadiran.blade.php` | Livewire Volt, filter reaktif, Flux table sortable/paginated, ekspor, foto, GPS, koreksi timestamp, dialog Alpine |
| Audit | `audit-logs/index.blade.php` | Filter rentang tanggal/aksi + tabel audit |
| Pegawai | `employees/index.blade.php` | Filter + kartu responsif + tabel desktop |
| Pegawai | `employees/create.blade.php`, `employees/edit.blade.php` | Form create/edit dengan komponen input/select bersama |
| Client | `clients/index.blade.php` | Filter + kartu Client dengan daftar Site bersarang + daftar Office |
| Client | `clients/create.blade.php`, `clients/edit.blade.php` | Form Client dengan Flux field langsung |
| Client | `clients/show.blade.php` | Header detail + daftar Site berbentuk artikel |
| Lokasi | `locations/create.blade.php`, `locations/edit.blade.php` | Form Location bercabang menurut konteks Client/Office; Flux field langsung |
| Proyek | `projects/index.blade.php` | Filter + kartu proyek expandable melalui `details/summary` |
| Proyek | `projects/create.blade.php`, `projects/edit.blade.php` | Shell create/edit yang memakai partial bersama |
| Proyek | `projects/_form.blade.php` | Partial form bersama; Flux field langsung |
| Permohonan | `leave-requests/index.blade.php` | Filter status + tabel permohonan + badge status domain |
| Permohonan | `leave-requests/show.blade.php` | Detail permohonan, metadata, lampiran, alur keputusan/modal |
| Lembur | `overtime-requests/index.blade.php` | Filter status + responsive review list dengan dua status berbeda |
| Lembur | `overtime-requests/show.blade.php` | Detail dan beberapa form keputusan/finalisasi/pengecualian foto |

Catatan: `⚡kehadiran.blade.php` adalah halaman Volt/Livewire dan perlu diperlakukan sebagai halaman interaktif khusus, bukan dipaksa mengikuti pola form GET atau tabel Blade statis.

## 2. Peta pola berulang

### A. Page shell, header, dan section

**Sudah tersedia**
- `x-ui.page`: container lebar halaman dan ritme vertikal.
- `x-ui.page-header`: judul, deskripsi, dan slot aksi.
- `x-ui.section`: grouping vertikal.
- `x-admin.page-header`: kompatibilitas domain yang mendelegasikan ke `x-ui.page-header`.

**Temuan**
- Mayoritas halaman daftar memakai `x-admin.page-header`.
- Halaman create/edit Pegawai, Client, dan Location masih menyusun judul secara manual.
- Detail Client, Attendance Correction, Leave Request, dan beberapa detail lain memiliki variasi header sendiri.
- `x-ui.page` dipakai secara jelas oleh halaman Livewire Kehadiran; halaman lain banyak memakai container utility manual.
- `x-ui.section` belum menjadi pola dominan di halaman Admin yang diaudit.

**Arah standardisasi**
- Pertahankan API `x-admin.page-header` untuk kompatibilitas, dengan `x-ui.page-header` sebagai implementasi generik.
- Gunakan page shell/header pada migrasi bertahap; jangan memaksakan semua halaman memiliki header yang sama jika halaman detail membutuhkan identitas record dan tombol kembali.
- Jangan membuat komponen section baru; evaluasi pemakaian `x-ui.section` yang sudah ada.

### B. Filter dan toolbar

**Sudah tersedia**
- `x-admin.filter-panel`.
- `x-ui.button`.

**Temuan**
- Filter panel sudah digunakan pada daftar Pegawai, Client, Proyek, Audit Log, Koreksi Absensi, Permohonan, Lembur, dan Kehadiran.
- Daily Attendance memisahkan filter tanggal dan form ekspor bulan menjadi dua unit berbeda.
- Livewire Kehadiran memakai filter panel dengan grid enam kolom dan `wire:model.live`.
- Bentuk filter berbeda: pencarian teks, status, rentang tanggal, pilihan pegawai, jenis absensi, jenis kerja, dan kombinasi beberapa filter.

**Arah standardisasi**
- Standarkan surface dan jarak panel, bukan isi atau mekanisme submit-nya.
- Pertahankan perbedaan GET form dan Livewire reactive filter.
- Toolbar ekspor dan aksi kontekstual tetap menjadi slot/komposisi halaman; jangan dimasukkan ke komponen filter generik.

### C. Form dan field controls

**Sudah tersedia**
- `x-ui.field`, `x-ui.input`, `x-ui.select`, `x-ui.textarea`, `x-ui.checkbox`, `x-ui.button`.

**Temuan**
- Form Pegawai Create/Edit sudah memakai `x-ui.input` dan `x-ui.select`.
- Form Client Create/Edit, Location Create/Edit, dan partial Proyek masih banyak memakai `flux:field`, `flux:input`, `flux:select`, `flux:textarea` secara langsung.
- Tombol submit/batal di beberapa form masih menggunakan `flux:button`, walaupun `x-ui.button` sudah tersedia.
- Daily Attendance memiliki form koreksi inline di komponen kartu pegawai.
- Livewire Kehadiran memiliki form koreksi timestamp inline dengan native HTML controls; ini memerlukan perlakuan khusus karena konteks interaksi dan tabelnya.
- Form keputusan pada detail Koreksi, Leave, dan Lembur memiliki struktur dan kontrol yang tidak identik.

**Arah standardisasi**
- Migrasikan field Client, Location, dan Project secara bertahap ke wrapper yang sudah ada jika atribut, selected state, error rendering, dan slot dapat dipertahankan persis.
- Jangan mengubah nama input, method, action, nilai old/model, kondisi field, atau payload.
- Pertahankan `x-ui.field` sebagai escape hatch untuk field khusus.
- Jangan membuat form component besar yang menggabungkan alur bisnis berbeda.

### D. Badge dan status

**Sudah tersedia**
- `x-ui.badge`.
- `x-admin.request-status-badge` sebagai adapter untuk status domain yang menyediakan `badgeColor()` dan `label()`.

**Temuan**
- `x-ui.badge` telah dipakai di halaman Pegawai, Client index, Proyek, dan Lembur.
- `x-admin.request-status-badge` dipakai pada daftar Permohonan.
- Flux badge langsung masih ditemukan di detail Client, daftar Koreksi, Daily Attendance card, detail Permohonan, dan Livewire Kehadiran.
- Beberapa status koreksi dirender menggunakan inline `match` di view.
- Lembur menampilkan approval status dan operational state secara terpisah; keduanya tidak boleh disatukan menjadi satu status generik.

**Arah standardisasi**
- Gunakan `x-ui.badge` sebagai primitive visual.
- Gunakan adapter domain hanya bila label/warna berasal dari status domain yang stabil.
- Jangan memindahkan pemetaan status atau keputusan bisnis ke primitive presentasional.
- Pertahankan dua indikator Lembur sebagai dua konsep yang berbeda.

### E. Metadata dan detail record

**Sudah tersedia**
- `x-ui.metadata-item`, untuk pasangan `dt/dd` di dalam `dl`.

**Temuan**
- Sudah digunakan pada detail Koreksi Absensi, detail Permohonan, kartu Pegawai, dan kartu Proyek.
- Halaman detail Client, Lembur, dan beberapa bagian detail lain masih memiliki label/value manual.
- Snapshot pekerjaan pada detail Koreksi memiliki struktur metadata yang lebih khusus dan bersyarat.
- Beberapa detail menyertakan lampiran, catatan, callout, atau form keputusan; bagian tersebut bukan metadata biasa.

**Arah standardisasi**
- Gunakan `x-ui.metadata-item` untuk pasangan label/value yang benar-benar bersifat deskriptif.
- Kelompokkan metadata dengan `dl` pada level halaman.
- Jangan memasukkan lampiran, tombol, callout, atau form ke dalam abstraksi metadata.
- Pertahankan conditional rendering dan sumber nilai yang saat ini dipakai.

### F. Collection, tabel, list, dan data cards

**Pola yang sudah ada**
1. **Table** — Audit Log, Pegawai, Permohonan, dan Livewire Kehadiran.
2. **Responsive review list** — Koreksi Absensi dan Lembur.
3. **Data cards** — Client, Proyek, Daily Attendance.
4. **Nested collection** — Client dengan Site di dalam kartu Client.
5. **Expandable record** — Proyek menggunakan `details/summary`.
6. **Hybrid cards/table** — Pegawai memakai kartu pada viewport lebih sempit dan tabel pada desktop.

**Temuan**
- Tidak semua collection mempunyai kebutuhan perbandingan kolom; review queue lebih cocok menjadi list.
- Table Blade statis dan Flux table Livewire memiliki kontrak interaksi berbeda (sorting, pagination, reactive state).
- Audit Log dan Permohonan menggunakan table horizontal, tetapi belum mempunyai kontrak scroll/accessibility yang seragam seperti tabel Pegawai.
- Pegawai sudah memiliki scroll region bernama, focusable, minimum width, dan scoped headers.
- Livewire Kehadiran menggunakan wrapper `overflow-auto` dengan sticky header dan pagination Flux; kepadatan tujuh kolom, foto, GPS, serta koreksi menjadikannya kasus khusus.
- Empty state dibuat melalui beberapa bentuk: `x-admin.empty-state`, wrapper kosong, baris tabel manual, atau teks Flux.

**Arah standardisasi**
- Jangan membuat satu komponen universal untuk semua tabel/list.
- Standarkan kontrak aksesibilitas tabel statis per halaman: header scope, scroll region jika dibutuhkan, min-width sesuai isi, dan empty state semantik.
- Pertahankan Flux table pada halaman Livewire selama sorting/pagination/reactivity-nya menjadi bagian interaksi.
- Pertahankan review list untuk antrean yang berorientasi membuka satu record.
- Gunakan `x-ui.card` sebagai primitive surface, tetapi jangan menghapus semantik `article` atau nested grouping yang diperlukan.
- Samakan kontrak empty state melalui primitive yang sudah ada, dengan bentuk table-row tetap mendukung `colspan`.

### G. Feedback, empty state, dan confirmation

**Sudah tersedia**
- `x-ui.flash-message` dan adapter `x-admin.flash-message`.
- `x-ui.empty-state` dan adapter `x-admin.empty-state`.

**Temuan**
- Flash message sudah digunakan luas pada halaman Admin.
- Empty state belum konsisten: ada wrapper Admin, markup manual, dan baris tabel manual.
- Detail Leave dan halaman Livewire Kehadiran memiliki confirmation/modal/dialog khusus yang tidak sama dengan flash message.

**Arah standardisasi**
- Pertahankan flash message sebagai feedback global.
- Gunakan empty-state component sesuai konteks block atau table row.
- Jangan membuat satu modal generik sebelum kontrak konfirmasi dan aksesibilitas antar-alur dipahami; konfirmasi ekspor, keputusan permohonan, dan pratinjau foto memiliki kebutuhan berbeda.

## 3. Kesenjangan lintas halaman yang perlu diperhatikan

| Area | Ketidakkonsistenan | Risiko presentasi | Prioritas audit implementasi |
|---|---|---|---|
| Form CRUD | Pegawai memakai `x-ui.*`, Client/Location/Project masih raw Flux | Label, error, spacing, dan tombol berpotensi berbeda | Tinggi |
| Header | Shared header bercampur header manual | Hierarki judul/aksi dan responsive alignment berbeda | Sedang |
| Tombol | `x-ui.button` bercampur `flux:button` dan native button | Variasi ukuran, variant, focus, dan action affordance | Tinggi |
| Badge | `x-ui.badge`, adapter domain, raw Flux, dan markup manual | Warna/spacing status tidak seragam | Tinggi |
| Metadata | `x-ui.metadata-item` dan label/value manual | Semantik dan tipografi detail tidak konsisten | Sedang |
| Table | Table Blade, Flux table, dan card/list | Kontrak overflow, header, dan mobile berbeda | Tinggi |
| Empty state | Komponen dan markup manual | Spacing, border, dan pesan kosong berbeda | Sedang |
| Page container | Utility max-width/spacing berbeda | Ritme halaman tidak konsisten | Sedang |
| Detail action/form | Form keputusan dan confirmation memiliki variasi | Perlu audit interaksi spesifik; abstraksi berisiko terlalu besar | Ditinjau per halaman |

## 4. Kontrak komponen yang direkomendasikan

Komponen yang sudah ada menjadi baseline. Tidak ada kebutuhan kuat untuk menambah primitive baru sebelum migrasi awal selesai.

| Komponen | Kontrak yang dipertahankan | Batas tanggung jawab |
|---|---|---|
| `x-ui.page` | Lebar maksimum, centering, vertical rhythm | Tidak mengatur query atau layout internal record |
| `x-ui.page-header` / `x-admin.page-header` | Title, description, action slot | Tidak menentukan tombol bisnis |
| `x-admin.filter-panel` | Surface panel filter | Tidak mengatur mekanisme GET/Livewire |
| `x-ui.button` | Link/action, variant, size, icon, attribute forwarding | Tidak membuat route atau keputusan otorisasi |
| `x-ui.field` + controls | Label, description, error, control slot | Tidak mengubah validasi atau payload |
| `x-ui.badge` | Presentasi badge | Tidak menentukan arti status |
| `x-admin.request-status-badge` | Label dan warna dari status domain | Tidak menggabungkan status domain berbeda |
| `x-ui.metadata-item` | Semantik `dt/dd` | Tidak membuat parent `dl` atau surface |
| `x-ui.card` | Surface generik/linked card | Tidak mengganti semantik article/list |
| `x-ui.empty-state` | Empty block atau table row | Tidak menentukan query kosong |
| `x-ui.flash-message` | Session status dan validation feedback | Tidak menggantikan confirmation/modal |

### Belum direkomendasikan untuk dibuat

- Generic table component: struktur tabel statis, Flux table Livewire, dan hybrid card/table memiliki kontrak berbeda.
- Generic review-list component: belum terbukti semua antrean memiliki field, status, dan action layout yang sama.
- Generic detail-page component: detail Client, koreksi, permohonan, dan lembur memiliki bukti serta aksi yang berbeda.
- Generic CRUD form component: form Client, Employee, Location, dan Project memiliki conditional field serta request payload berbeda.
- Generic modal component: ekspor, foto, dan keputusan mempunyai tujuan/interaksi yang tidak sama.

Komponen domain baru hanya boleh diusulkan setelah setidaknya dua halaman menunjukkan markup dan interaksi yang benar-benar identik, bukan hanya sama-sama memakai card atau badge.

## 5. Urutan implementasi yang disarankan

Implementasi dilakukan per halaman/kelompok kecil, bukan perubahan massal lintas seluruh Admin sekaligus.

1. **Form Client Create/Edit** — migrasi field, tombol, header, dan form surface ke kontrak yang sudah dipakai Employee.
2. **Form Location Create/Edit** — migrasi field yang sama, dengan perhatian khusus pada conditional Client/Office, hidden fields, old values, dan selected state.
3. **Form Project Create/Edit** — standarkan partial `_form` tanpa mengubah perbedaan Client terkunci pada edit dan pilihan Client pada create.
4. **Form keputusan/detail** — Koreksi Absensi, Permohonan, dan Lembur; gunakan field/button/metadata/badge yang ada sesuai konteks masing-masing.
5. **Review queues** — Koreksi Absensi, Lembur, dan Permohonan; samakan identitas, waktu, badge, action affordance, dan empty state tanpa memaksa semua menjadi tabel.
6. **Master data cards** — Client dan Proyek; samakan metadata/badge/action serta responsive wrapping.
7. **Monitoring/reporting** — Daily Attendance, Audit Log, lalu Livewire Kehadiran; audit table, filter, empty state, evidence, export confirmation, dan accessibility secara terpisah.
8. **Final consistency pass** — page shell, header, section rhythm, focus states, dark mode, responsive breakpoints, dan komponen yang belum terpakai.

Urutan di atas adalah prioritas teknis, bukan izin untuk mengubah perilaku halaman. Setiap halaman tetap harus melalui inspeksi diff dan test yang relevan sebelum berpindah ke halaman berikutnya.

## 6. Checklist wajib setiap implementasi

- Tidak mengubah route, HTTP method, authorization, query, filter, sorting, pagination, export URL, atau request payload.
- Tidak mengubah validation rules, old/model value, selected state, hidden field, atau conditional rendering.
- Tidak mengubah Livewire property, event, method, lifecycle, atau Alpine state.
- Mempertahankan semantik link, button, form, table, `dl/dt/dd`, dan `article`.
- Memastikan atribut `wire:*`, `x-*`, ARIA, dan native HTML diteruskan ke elemen yang tepat.
- Memeriksa responsive layout pada narrow mobile, tablet portrait, tablet landscape, dan desktop.
- Memeriksa light/dark mode, keyboard focus, empty state, long text, dan overflow.
- Menambah atau menyesuaikan test render/feature yang relevan.
- Melaporkan test dan CI hanya sebagai berhasil jika benar-benar sudah dijalankan dan hasilnya tersedia.

## Kesimpulan audit

Fondasi primitive UI SanMal sudah cukup untuk memulai standardisasi Admin secara bertahap. Kesenjangan terbesar saat ini bukan kekurangan komponen baru, melainkan **adopsi komponen yang belum merata** pada form CRUD, badge, tombol, metadata, empty state, dan struktur collection.

Prioritas paling efisien adalah menyelesaikan satu keluarga pola form CRUD terlebih dahulu (Client, Location, Project), lalu antrean review dan halaman detail. Halaman Livewire Kehadiran diletakkan pada tahap monitoring terakhir karena interaksinya paling padat dan harus dijaga secara khusus.
